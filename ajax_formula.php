<?php
session_start();
require 'koneksi.php';

// Pastikan output berupa JSON
header('Content-Type: application/json');

// Ambil request dari JavaScript (metode POST JSON)
$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['action']) && $data['action'] === 'get_detail') {
    $id_formula = $data['id'] ?? '';

    if (empty($id_formula)) {
        echo json_encode(['status' => 'error', 'pesan' => 'ID Formula tidak ditemukan.']);
        exit;
    }

    try {
        // Query disesuaikan dengan tabel db_recipe_items dan db_inventory milikmu
        // d.qty_dibutuhkan di-alias menjadi 'qty' agar Javascript tidak perlu diubah
        $query = "
            SELECT d.sku_bahan, m.nama_barang, d.qty_dibutuhkan AS qty, m.satuan 
            FROM db_recipe_items d
            LEFT JOIN db_inventory m ON d.sku_bahan = m.sku
            WHERE d.id_resep = ?
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$id_formula]);
        $hasil = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Kirim data ke JavaScript
        echo json_encode(['status' => 'success', 'data' => $hasil]);
        
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'pesan' => 'Query Error: ' . $e->getMessage()]);
    }
    exit;
}

// Jika action tidak dikenali
echo json_encode(['status' => 'error', 'pesan' => 'Aksi tidak valid.']);
?>