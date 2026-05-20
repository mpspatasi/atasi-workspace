<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['username'])) {
    echo json_encode(['status' => 'error', 'pesan' => 'Sesi berakhir, silakan login ulang.']);
    exit;
}

require 'koneksi.php';

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? '';

try {
    switch ($action) {
        case 'get_all':
            $sql = "SELECT * FROM tb_complaints ORDER BY id DESC";
            $stmt = $pdo->query($sql);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['status' => 'success', 'data' => $data]);
            break;

        case 'save':
            $id = $input['id'] ?? '';
            $no_pesanan = $input['no_pesanan'] ?? '';
            $nama_pelanggan = $input['nama_pelanggan'] ?? '';
            $telp_raw = $input['telp'] ?? '';
            $platform = $input['platform'] ?? 'WhatsApp';
            $rating = $input['rating'] ?? 5;
            $kategori = $input['kategori'] ?? '';
            $detail = $input['detail_keluhan'] ?? '';
            $tindakan = $input['tindakan'] ?? '';
            $status = $input['status'] ?? 'Open';
            $pic = $_SESSION['nama_lengkap'] ?? 'CS System';

            if(empty($no_pesanan) || empty($nama_pelanggan)) {
                echo json_encode(['status' => 'error', 'pesan' => 'No Pesanan dan Nama Pelanggan wajib diisi!']);
                exit;
            }

            // ==========================================
            // LOGIKA PENCARIAN SEGMEN DARI db_rfm (REVISI SMART SEARCH)
            // ==========================================
            $segmen = 'Nothing'; // Default

            if (!empty($telp_raw)) {
                // 1. Buang semua karakter selain angka (spasi, +, -, dll)
                $clean_telp = preg_replace('/[^0-9]/', '', $telp_raw);
                $core_telp = $clean_telp;
                
                // 2. Ambil angka intinya saja (buang awalan 62 atau 0)
                if (substr($clean_telp, 0, 2) === '62') {
                    $core_telp = substr($clean_telp, 2);
                } elseif (substr($clean_telp, 0, 1) === '0') {
                    $core_telp = substr($clean_telp, 1);
                }

                // 3. Pencarian Pintar dengan LIKE
                // Kita cari nomor di db_rfm yang MENGANDUNG angka inti ini.
                // Mau format di db ada kurung, spasi, atau strip, pasti bakal terdeteksi.
                if (!empty($core_telp) && strlen($core_telp) >= 8) {
                    $stmt_rfm = $pdo->prepare("SELECT Segmen FROM db_rfm WHERE Telp LIKE ? LIMIT 1");
                    $stmt_rfm->execute(['%' . $core_telp . '%']);
                    $rfm_data = $stmt_rfm->fetch(PDO::FETCH_ASSOC);

                    if ($rfm_data && !empty($rfm_data['Segmen'])) {
                        $segmen = $rfm_data['Segmen'];
                    }
                }
            }

            if (empty($id)) {
                $sql = "INSERT INTO tb_complaints (no_pesanan, nama_pelanggan, telp, segmen, platform, rating, kategori, detail_keluhan, tindakan, pic, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$no_pesanan, $nama_pelanggan, $telp_raw, $segmen, $platform, $rating, $kategori, $detail, $tindakan, $pic, $status]);
                echo json_encode(['status' => 'success', 'pesan' => 'Tiket keluhan baru berhasil dibuat!']);
            } else {
                $sql = "UPDATE tb_complaints SET no_pesanan=?, nama_pelanggan=?, telp=?, segmen=?, platform=?, rating=?, kategori=?, detail_keluhan=?, tindakan=?, status=? WHERE id=?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$no_pesanan, $nama_pelanggan, $telp_raw, $segmen, $platform, $rating, $kategori, $detail, $tindakan, $status, $id]);
                echo json_encode(['status' => 'success', 'pesan' => 'Perubahan tiket berhasil disimpan!']);
            }
            break;

        case 'delete':
            $id = $input['id'] ?? '';
            if (!empty($id)) {
                $stmt = $pdo->prepare("DELETE FROM tb_complaints WHERE id = ?");
                $stmt->execute([$id]);
                echo json_encode(['status' => 'success', 'pesan' => 'Tiket berhasil dihapus permanen!']);
            } else {
                echo json_encode(['status' => 'error', 'pesan' => 'ID tidak ditemukan.']);
            }
            break;

        default:
            echo json_encode(['status' => 'error', 'pesan' => 'Aksi tidak valid!']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'pesan' => 'Error Server: ' . $e->getMessage()]);
}
?>