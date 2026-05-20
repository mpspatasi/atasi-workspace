<?php
ob_start(); // Tangkap semua error bawaan server
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

try {
    require 'koneksi.php';
    // Paksa PDO untuk mengubah error SQL menjadi Exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    $action = $input['action'] ?? '';

    // ==========================================
    // 1. AMBIL SEMUA DATA (GET ALL)
    // ==========================================
    if ($action === 'get_all') {
        // PERBAIKAN: Urutkan berdasarkan Prioritas dan Skor, BUKAN id
        $segmen = $pdo->query("SELECT * FROM db_segmen ORDER BY Prioritas ASC")->fetchAll(PDO::FETCH_ASSOC);
        $skor = $pdo->query("SELECT * FROM db_skor ORDER BY Skor ASC")->fetchAll(PDO::FETCH_ASSOC);
        
        if (ob_get_length()) ob_clean();
        echo json_encode(['status' => 'success', 'segmen' => $segmen, 'skor' => $skor]);
        exit;
    }

    // ==========================================
    // 2. UPDATE BARIS PADA DB_SEGMEN
    // ==========================================
    if ($action === 'update_segmen') {
        // PERBAIKAN: Tangkap parameter min/max sesuai struktur database aslinya
        // Patokannya menggunakan nama Segmen (bukan ID)
        $segmen = $input['segmen']; 
        
        $skor_r_min = $input['skor_r_min'];
        $skor_r_max = $input['skor_r_max'];
        $skor_f_min = $input['skor_f_min'];
        $skor_f_max = $input['skor_f_max'];
        $skor_m_min = $input['skor_m_min'];
        $skor_m_max = $input['skor_m_max'];
        $prioritas  = $input['prioritas'];

        // PERBAIKAN: Query update disesuaikan dengan nama kolom di phpMyAdmin
        $stmt = $pdo->prepare("UPDATE db_segmen 
                               SET Skor_R_Min=?, Skor_R_Max=?, Skor_F_Min=?, Skor_F_Max=?, Skor_M_Min=?, Skor_M_Max=?, Prioritas=? 
                               WHERE Segmen=?");
        
        $stmt->execute([$skor_r_min, $skor_r_max, $skor_f_min, $skor_f_max, $skor_m_min, $skor_m_max, $prioritas, $segmen]);

        if (ob_get_length()) ob_clean();
        echo json_encode(['status' => 'success', 'pesan' => 'Aturan segmen berhasil diperbarui!']);
        exit;
    }

    // ==========================================
    // 3. UPDATE BARIS PADA DB_SKOR
    // ==========================================
    if ($action === 'update_skor') {
        // PERBAIKAN: Tangkap parameter min/max. 
        // Patokannya menggunakan angka Skor (bukan ID)
        $skor = $input['skor']; 
        
        $r_min = $input['r_min'];
        $r_max = $input['r_max'];
        $f_min = $input['f_min'];
        $f_max = $input['f_max'];
        $m_min = $input['m_min'];
        $m_max = $input['m_max'];

        // PERBAIKAN: Query update disesuaikan dengan nama kolom di db_skor
        $stmt = $pdo->prepare("UPDATE db_skor 
                               SET R_Min=?, R_Max=?, F_Min=?, F_Max=?, M_Min=?, M_Max=? 
                               WHERE Skor=?");
        
        $stmt->execute([$r_min, $r_max, $f_min, $f_max, $m_min, $m_max, $skor]);

        if (ob_get_length()) ob_clean();
        echo json_encode(['status' => 'success', 'pesan' => 'Aturan skor berhasil diperbarui!']);
        exit;
    }

    throw new Exception("Aksi tidak valid.");

} catch (Throwable $e) { 
    // Tangkap SEMUA jenis error (termasuk salah kolom MySQL)
    if (ob_get_length()) ob_clean();
    echo json_encode(['status' => 'error', 'pesan' => 'Backend Error: ' . $e->getMessage()]);
    exit;
}
?>