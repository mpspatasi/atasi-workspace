<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

try {
    require 'koneksi.php';

    // 1. TANGKAP PAYLOAD JSON DARI FRONTEND
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    
    $filter_tanggal = $input['tanggal'] ?? 'all';
    $filter_start   = $input['start'] ?? '';
    $filter_end     = $input['end'] ?? '';
    
    // Sekarang nangkapnya sebagai ARRAY
    $filter_media   = is_array($input['media'] ?? '') ? $input['media'] : [];
    $filter_prov    = is_array($input['provinsi'] ?? '') ? $input['provinsi'] : [];
    $filter_kab     = is_array($input['kabupaten'] ?? '') ? $input['kabupaten'] : [];
    $filter_status  = is_array($input['status'] ?? '') ? $input['status'] : [];

    // 2. RAKIT QUERY DINAMIS BERDASARKAN FILTER (WHERE CLAUSE)
    $conditions = ["1=1"];
    $params = [];

    // Filter Tanggal (Tetap sama)
    if ($filter_tanggal !== 'all') {
        $today = date('Y-m-d');
        if ($filter_tanggal == 'hari_ini') {
            $conditions[] = "Tanggal_Pesanan = ?"; $params[] = $today;
        } elseif ($filter_tanggal == 'kemarin') {
            $conditions[] = "Tanggal_Pesanan = DATE_SUB(?, INTERVAL 1 DAY)"; $params[] = $today;
        } elseif ($filter_tanggal == '7_hari') {
            $conditions[] = "Tanggal_Pesanan >= DATE_SUB(?, INTERVAL 7 DAY)"; $params[] = $today;
        } elseif ($filter_tanggal == '14_hari') {
            $conditions[] = "Tanggal_Pesanan >= DATE_SUB(?, INTERVAL 14 DAY)"; $params[] = $today;
        } elseif ($filter_tanggal == '30_hari') {
            $conditions[] = "Tanggal_Pesanan >= DATE_SUB(?, INTERVAL 30 DAY)"; $params[] = $today;
        } elseif ($filter_tanggal == 'bulan_ini') {
            $conditions[] = "MONTH(Tanggal_Pesanan) = MONTH(?) AND YEAR(Tanggal_Pesanan) = YEAR(?)"; 
            $params[] = $today; $params[] = $today;
        } elseif ($filter_tanggal == 'kuartal_ini') {
            $conditions[] = "QUARTER(Tanggal_Pesanan) = QUARTER(?) AND YEAR(Tanggal_Pesanan) = YEAR(?)"; 
            $params[] = $today; $params[] = $today;
        } elseif ($filter_tanggal == 'tahun_ini') {
            $conditions[] = "YEAR(Tanggal_Pesanan) = YEAR(?)"; $params[] = $today;
        } elseif ($filter_tanggal == 'custom' && $filter_start && $filter_end) {
            $conditions[] = "Tanggal_Pesanan BETWEEN ? AND ?";
            $params[] = $filter_start; $params[] = $filter_end;
        }
    }

    // Fungsi Helper untuk Multi-Select Filter
    function applyMultiSelectFilter(&$conditions, &$params, $column, $valuesArray) {
        // Bersihkan array dari 'null' atau kosong
        $cleanArray = array_filter($valuesArray, function($v) {
            return strtolower(trim($v)) !== 'null' && trim($v) !== '';
        });
        
        if (count($cleanArray) > 0) {
            // Bikin tanda tanya sebanyak isi array (?, ?, ?)
            $placeholders = implode(',', array_fill(0, count($cleanArray), '?'));
            $conditions[] = "$column IN ($placeholders)";
            foreach ($cleanArray as $val) {
                $params[] = $val;
            }
        }
    }

    // Terapkan Multi-Select Filter ke SQL
    applyMultiSelectFilter($conditions, $params, 'Media', $filter_media);
    applyMultiSelectFilter($conditions, $params, 'Provinsi', $filter_prov);
    applyMultiSelectFilter($conditions, $params, 'Kabupaten', $filter_kab);
    applyMultiSelectFilter($conditions, $params, 'Status_Pesanan', $filter_status);

    $whereSQL = implode(" AND ", $conditions);

    // 3. HITUNG KPI UTAMA (Metrics)
    $sql_kpi = "SELECT 
        COUNT(id) as total_transaksi,
        SUM(Jumlah) as total_botol,
        SUM(ValueCX) as total_omset,
        COUNT(DISTINCT Telp) as total_pelanggan,
        COUNT(CASE WHEN Status_Pesanan = 'Retur' THEN 1 END) as total_retur
    FROM db_customer WHERE $whereSQL";
    
    $stmt_kpi = $pdo->prepare($sql_kpi);
    $stmt_kpi->execute($params);
    $kpi = $stmt_kpi->fetch(PDO::FETCH_ASSOC);

    $total_transaksi = (int)($kpi['total_transaksi'] ?? 0);
    $total_pelanggan = (int)($kpi['total_pelanggan'] ?? 0);
    $total_retur = (int)($kpi['total_retur'] ?? 0);
    
    $rata_transaksi = $total_transaksi > 0 ? ($kpi['total_omset'] / $total_transaksi) : 0;
    $rasio_retur = $total_transaksi > 0 ? round(($total_retur / $total_transaksi) * 100, 1) : 0;
    
    $transaksi_ro = max(0, $total_transaksi - $total_pelanggan);

    $sql_botol_ro = "SELECT SUM(Jumlah) FROM db_customer WHERE $whereSQL AND Telp IN (SELECT Telp FROM db_customer GROUP BY Telp HAVING COUNT(id) > 1)";
    $stmt_botol = $pdo->prepare($sql_botol_ro);
    $stmt_botol->execute($params);
    $botol_ro = (int)$stmt_botol->fetchColumn();

    $champions = (int)$pdo->query("SELECT COUNT(*) FROM db_rfm WHERE LOWER(Segmen) LIKE '%champion%'")->fetchColumn();

    // 4. FUNGSI HELPER UNTUK RENDER DATA CHART
    function getChartData($pdo, $sql, $params = []) {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $labels = []; $data = [];
        foreach($res as $r) {
            $label = trim($r['label'] ?? '');
            if ($label === '' || strtolower($label) === 'null') continue;
            $labels[] = $label;
            $data[] = (float)$r['value'];
        }
        return ['labels' => $labels, 'data' => $data];
    }

    // 5. TARIK SEMUA DATA GRAFIK
    $chart_bulanan = getChartData($pdo, "SELECT DATE_FORMAT(Tanggal_Pesanan, '%Y-%m') as label, SUM(ValueCX) as value FROM db_customer WHERE $whereSQL AND Tanggal_Pesanan IS NOT NULL GROUP BY label ORDER BY label", $params);
    $chart_kuartal = getChartData($pdo, "SELECT Kuartal as label, SUM(ValueCX) as value FROM db_customer WHERE $whereSQL AND TRIM(Kuartal) != '' AND LOWER(Kuartal) != 'null' GROUP BY label ORDER BY label", $params);
    $chart_tahun = getChartData($pdo, "SELECT YEAR(Tanggal_Pesanan) as label, SUM(ValueCX) as value FROM db_customer WHERE $whereSQL AND Tanggal_Pesanan IS NOT NULL GROUP BY label ORDER BY label", $params);
    
    $chart_status = getChartData($pdo, "SELECT Status_Pesanan as label, COUNT(id) as value FROM db_customer WHERE $whereSQL AND TRIM(Status_Pesanan) != '' AND LOWER(Status_Pesanan) != 'null' GROUP BY label ORDER BY value DESC", $params);
    $chart_media = getChartData($pdo, "SELECT Media as label, COUNT(id) as value FROM db_customer WHERE $whereSQL AND TRIM(Media) != '' AND LOWER(Media) != 'null' GROUP BY label ORDER BY value DESC", $params);
    $chart_ekspedisi = getChartData($pdo, "SELECT Ekspedisi as label, COUNT(id) as value FROM db_customer WHERE $whereSQL AND TRIM(Ekspedisi) != '' AND LOWER(Ekspedisi) != 'null' GROUP BY label ORDER BY value DESC LIMIT 10", $params);
    
    $chart_rfm = getChartData($pdo, "SELECT Segmen as label, COUNT(*) as value FROM db_rfm WHERE Segmen IS NOT NULL AND TRIM(Segmen) != '' AND LOWER(Segmen) != 'null' GROUP BY label ORDER BY value DESC");

    $chart_produk = getChartData($pdo, "SELECT Produk as label, SUM(Jumlah) as value FROM db_customer WHERE $whereSQL AND TRIM(Produk) != '' AND LOWER(Produk) != 'null' GROUP BY label ORDER BY value DESC LIMIT 10", $params);
    $chart_produk_ro = getChartData($pdo, "SELECT Produk as label, SUM(Jumlah) as value FROM db_customer WHERE $whereSQL AND Telp IN (SELECT Telp FROM db_customer GROUP BY Telp HAVING COUNT(id) > 1) AND TRIM(Produk) != '' AND LOWER(Produk) != 'null' GROUP BY label ORDER BY value DESC LIMIT 10", $params);

    $chart_provinsi = getChartData($pdo, "SELECT Provinsi as label, COUNT(id) as value FROM db_customer WHERE $whereSQL AND TRIM(Provinsi) != '' AND LOWER(Provinsi) != 'null' GROUP BY label ORDER BY value DESC LIMIT 10", $params);
    $chart_kabupaten = getChartData($pdo, "SELECT Kabupaten as label, COUNT(id) as value FROM db_customer WHERE $whereSQL AND TRIM(Kabupaten) != '' AND LOWER(Kabupaten) != 'null' GROUP BY label ORDER BY value DESC LIMIT 10", $params);
    $chart_kecamatan = getChartData($pdo, "SELECT Kecamatan as label, COUNT(id) as value FROM db_customer WHERE $whereSQL AND TRIM(Kecamatan) != '' AND LOWER(Kecamatan) != 'null' GROUP BY label ORDER BY value DESC LIMIT 10", $params);

    $chart_komoditas = getChartData($pdo, "SELECT Komoditas as label, COUNT(id) as value FROM db_customer WHERE $whereSQL AND Komoditas IS NOT NULL AND TRIM(Komoditas) != '' AND LOWER(Komoditas) != 'null' GROUP BY label ORDER BY value DESC LIMIT 10", $params);
    $chart_retur = getChartData($pdo, "SELECT Keterangan as label, COUNT(id) as value FROM db_customer WHERE $whereSQL AND Status_Pesanan = 'Retur' AND TRIM(Keterangan) != '' AND LOWER(Keterangan) != 'null' GROUP BY label ORDER BY value DESC LIMIT 10", $params);

    // 6. AMBIL OPSI FILTER DROPDOWN GLOBAL & MAPPING PROVINSI -> KABUPATEN
    $opts_media = $pdo->query("SELECT DISTINCT Media FROM db_customer WHERE TRIM(Media) != '' AND LOWER(Media) != 'null' ORDER BY Media")->fetchAll(PDO::FETCH_COLUMN);
    $opts_provinsi = $pdo->query("SELECT DISTINCT Provinsi FROM db_customer WHERE TRIM(Provinsi) != '' AND LOWER(Provinsi) != 'null' ORDER BY Provinsi")->fetchAll(PDO::FETCH_COLUMN);

    $stmt_geo = $pdo->query("SELECT DISTINCT Provinsi, Kabupaten FROM db_customer WHERE TRIM(Provinsi) != '' AND LOWER(Provinsi) != 'null' AND TRIM(Kabupaten) != '' AND LOWER(Kabupaten) != 'null' ORDER BY Provinsi, Kabupaten");
    $geo_data = $stmt_geo->fetchAll(PDO::FETCH_ASSOC);
    
    $geo_map = [];
    $all_kabupaten = [];
    foreach($geo_data as $row) {
        $p = trim($row['Provinsi']);
        $k = trim($row['Kabupaten']);
        if (!isset($geo_map[$p])) {
            $geo_map[$p] = [];
        }
        $geo_map[$p][] = $k;
        $all_kabupaten[] = $k;
    }
    
    $all_kabupaten = array_values(array_unique($all_kabupaten));
    sort($all_kabupaten);

    // 7. KELUARKAN OUTPUT JSON UNTUK FRONTEND
    echo json_encode([
        'status' => 'success',
        'kpi' => [
            'total_transaksi' => $total_transaksi,
            'total_botol' => (int)$kpi['total_botol'],
            'total_pelanggan' => $total_pelanggan,
            'total_omset' => (float)$kpi['total_omset'],
            'rata_transaksi' => $rata_transaksi,
            'transaksi_ro' => $transaksi_ro,
            'botol_ro' => $botol_ro,
            'rasio_retur' => $rasio_retur,
            'total_champions' => $champions
        ],
        'chart_bulanan' => $chart_bulanan,
        'chart_kuartal' => $chart_kuartal,
        'chart_tahun' => $chart_tahun,
        'chart_status' => $chart_status,
        'chart_media' => $chart_media,
        'chart_ekspedisi' => $chart_ekspedisi,
        'chart_rfm' => $chart_rfm,
        'chart_produk' => $chart_produk,
        'chart_produk_ro' => $chart_produk_ro,
        'chart_provinsi' => $chart_provinsi,
        'chart_kabupaten' => $chart_kabupaten,
        'chart_kecamatan' => $chart_kecamatan,
        'chart_komoditas' => $chart_komoditas,
        'chart_retur' => $chart_retur,
        'filter_options' => [
            'media' => $opts_media,
            'provinsi' => $opts_provinsi,
            'kabupaten' => $all_kabupaten,
            'geo_map' => $geo_map
        ]
    ]);
    exit;

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'pesan' => 'Error Mesin Query: ' . $e->getMessage()]);
    exit;
}
?>