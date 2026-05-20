<?php
header('Content-Type: application/json; charset=utf-8');
require 'koneksi.php';

// ==========================================
// 1. FUNGSI "MESIN CUCI" TANGGAL
// ==========================================
function bersihkanTanggal($tgl) {
    if (empty(trim($tgl))) return null;
    $tgl_bersih = str_replace('/', '-', $tgl);
    $time = strtotime($tgl_bersih);
    if ($time !== false) {
        return date('Y-m-d', $time);
    }
    return null;
}

// ==========================================
// 2. FUNGSI "MESIN CUCI" ANGKA / UANG
// ==========================================
function bersihkanAngka($angka) {
    if (empty(trim($angka))) return 0;
    $hanyaAngka = preg_replace('/[^0-9]/', '', $angka);
    return $hanyaAngka === '' ? 0 : (int)$hanyaAngka;
}

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);

    if (isset($input['action']) && $input['action'] === 'impor_customer') {
        $dataValid = $input['data'];

        $pdo->beginTransaction();

        // 1. Siapkan Query Pengecekan
        $stmtCheck = $pdo->prepare("SELECT id FROM db_customer WHERE No_Pesanan = ? LIMIT 1");

        // 2. Siapkan Query INSERT (Kalau No_Pesanan belum ada)
        $sqlInsert = "INSERT INTO db_customer (
                    Media, Tanggal_Pesanan, Waktu_Pesanan, No_Pesanan, 
                    Resi, Tanggal_Dikirim, Status_Pesanan, Tanggal_Retur, Produk, 
                    Jumlah, Ekspedisi, Username, Nama, Telp, 
                    Provinsi, Kabupaten, Kecamatan, Handle, 
                    Keterangan, ValueCX, ValueADS, Komoditas
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmtInsert = $pdo->prepare($sqlInsert);

        // 3. Siapkan Query UPDATE (Kalau No_Pesanan sudah ada)
        $sqlUpdate = "UPDATE db_customer SET 
                    Media = ?, Tanggal_Pesanan = ?, Waktu_Pesanan = ?, 
                    Resi = ?, Tanggal_Dikirim = ?, Status_Pesanan = ?, Tanggal_Retur = ?, Produk = ?, 
                    Jumlah = ?, Ekspedisi = ?, Username = ?, Nama = ?, Telp = ?, 
                    Provinsi = ?, Kabupaten = ?, Kecamatan = ?, Handle = ?, 
                    Keterangan = ?, ValueCX = ?, ValueADS = ?, Komoditas = ?
                WHERE No_Pesanan = ?";
        $stmtUpdate = $pdo->prepare($sqlUpdate);

        // Hitung statistik untuk dikembalikan ke popup
        $countInsert = 0;
        $countUpdate = 0;

        foreach ($dataValid as $row) {
            $row_bersih = array_map(function($val) {
                return $val === '' ? null : trim($val);
            }, $row);

            $no_pesanan = $row_bersih[4]; // Ambil No_Pesanan dari index ke-4

            // Variabel data yang sudah dibersihkan
            $media = $row_bersih[0];
            $tgl_pesan = bersihkanTanggal($row_bersih[2]);
            $waktu = $row_bersih[3];
            $resi = $row_bersih[5];
            $tgl_kirim = bersihkanTanggal($row_bersih[6]);
            $status = $row_bersih[7];
            $tgl_retur = bersihkanTanggal($row_bersih[8]);
            $produk = $row_bersih[9];
            $jumlah = bersihkanAngka($row_bersih[10]);
            $ekspedisi = $row_bersih[11];
            $username = $row_bersih[12];
            $nama = $row_bersih[13];
            $telp = $row_bersih[14];
            $provinsi = $row_bersih[15];
            $kabupaten = $row_bersih[16];
            $kecamatan = $row_bersih[17];
            $handle = $row_bersih[19];
            $ket = $row_bersih[20];
            $valCX = bersihkanAngka($row_bersih[21]);
            $valADS = bersihkanAngka($row_bersih[22]);
            $komoditas = $row_bersih[23];

            // Pengecekan pakai Satpam PHP
            if (!empty($no_pesanan)) {
                $stmtCheck->execute([$no_pesanan]);
                $exists = $stmtCheck->fetchColumn();

                if ($exists) {
                    // Kalau ADA -> Lakukan UPDATE
                    $stmtUpdate->execute([
                        $media, $tgl_pesan, $waktu, 
                        $resi, $tgl_kirim, $status, $tgl_retur, $produk, 
                        $jumlah, $ekspedisi, $username, $nama, $telp, 
                        $provinsi, $kabupaten, $kecamatan, $handle, 
                        $ket, $valCX, $valADS, $komoditas,
                        $no_pesanan // Klausa WHERE No_Pesanan
                    ]);
                    $countUpdate++;
                } else {
                    // Kalau TIDAK ADA -> Lakukan INSERT
                    $stmtInsert->execute([
                        $media, $tgl_pesan, $waktu, $no_pesanan, 
                        $resi, $tgl_kirim, $status, $tgl_retur, $produk, 
                        $jumlah, $ekspedisi, $username, $nama, $telp, 
                        $provinsi, $kabupaten, $kecamatan, $handle, 
                        $ket, $valCX, $valADS, $komoditas
                    ]);
                    $countInsert++;
                }
            } else {
                // Khusus jika No_Pesanan kosong tapi tetap mau diimpor (misal manual lead) -> langsung INSERT
                $stmtInsert->execute([
                    $media, $tgl_pesan, $waktu, $no_pesanan, 
                    $resi, $tgl_kirim, $status, $tgl_retur, $produk, 
                    $jumlah, $ekspedisi, $username, $nama, $telp, 
                    $provinsi, $kabupaten, $kecamatan, $handle, 
                    $ket, $valCX, $valADS, $komoditas
                ]);
                $countInsert++;
            }
        }

        $pdo->commit();
        
        // Kembalikan status ke frontend
        echo json_encode([
            'status' => 'success', 
            'pesan' => "Data berhasil disinkronisasi!",
            'detail' => ['insert' => $countInsert, 'update' => $countUpdate]
        ]);
        exit;
    }
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['status' => 'error', 'pesan' => $e->getMessage()]);
    exit;
}
?>