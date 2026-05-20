<?php
ob_start();
session_start();
header('Content-Type: application/json; charset=utf-8');
require 'koneksi.php';

// Pastikan request datang via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Metode request tidak diizinkan.']);
    exit;
}

// Ambil action dari FormData
$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        // ==========================================
        // 1. TAMBAH BARANG BARU
        // ==========================================
        case 'add':
            $sku = trim($_POST['sku']);
            $nama = trim($_POST['nama_barang']);
            $kategori = $_POST['kategori'];
            $satuan = trim($_POST['satuan']);
            
            // PERBAIKAN: Ubah (int) menjadi (float) agar bisa baca desimal
            $min_stock = (float)$_POST['min_stock'];
            
            // PERBAIKAN: Tangkap current_stock pakai (float). Jika kosong, default ke 0
            $current_stock = (isset($_POST['current_stock']) && $_POST['current_stock'] !== '') ? (float)$_POST['current_stock'] : 0.0;

            // Cek apakah SKU sudah ada agar tidak duplikat
            $check = $pdo->prepare("SELECT sku FROM jurnal_transaksi_atasi.db_inventory WHERE sku = ?");
            $check->execute([$sku]);
            if ($check->fetch()) {
                echo json_encode(['status' => 'error', 'message' => 'SKU sudah terdaftar! Gunakan kode lain.']);
                exit;
            }

            // PERBAIKAN: Masukkan current_stock ke query INSERT (ubah 0 jadi ?)
            $sql = "INSERT INTO jurnal_transaksi_atasi.db_inventory 
                    (sku, nama_barang, kategori, min_stock, satuan, current_stock, last_update) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $pdo->prepare($sql);
            // Eksekusi dengan variabel $current_stock
            $stmt->execute([$sku, $nama, $kategori, $min_stock, $satuan, $current_stock]);

            echo json_encode(['status' => 'success', 'message' => 'Barang berhasil disimpan ke database.']);
            break;

        // ==========================================
        // 2. UPDATE DATA BARANG
        // ==========================================
        case 'edit':
            $sku_lama = $_POST['sku_lama']; // Identifier unik
            $nama = trim($_POST['nama_barang']);
            $kategori = $_POST['kategori'];
            $satuan = trim($_POST['satuan']);
            
            // PERBAIKAN: Ubah (int) menjadi (float)
            $min_stock = (float)$_POST['min_stock'];
            
            // PERBAIKAN: Tangkap current_stock pakai (float)
            $current_stock = (isset($_POST['current_stock']) && $_POST['current_stock'] !== '') ? (float)$_POST['current_stock'] : 0.0;

            // PERBAIKAN: Tambahkan current_stock = ? ke query UPDATE
            $sql = "UPDATE jurnal_transaksi_atasi.db_inventory 
                    SET nama_barang = ?, kategori = ?, min_stock = ?, satuan = ?, current_stock = ?, last_update = NOW() 
                    WHERE sku = ?";
            
            $stmt = $pdo->prepare($sql);
            // Eksekusi dengan variabel $current_stock
            $stmt->execute([$nama, $kategori, $min_stock, $satuan, $current_stock, $sku_lama]);

            echo json_encode(['status' => 'success', 'message' => 'Data barang berhasil diperbarui.']);
            break;

        // ==========================================
        // 3. HAPUS BARANG
        // ==========================================
        case 'delete':
            $sku = $_POST['sku'];

            $sql = "DELETE FROM jurnal_transaksi_atasi.db_inventory WHERE sku = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$sku]);

            echo json_encode(['status' => 'success', 'message' => 'Barang telah dihapus dari sistem.']);
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Action tidak dikenal!']);
            break;
    }

} catch (PDOException $e) {
    // Jika ada error database (misal kolom kurang atau salah nama tabel)
    echo json_encode(['status' => 'error', 'message' => 'Database Error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'System Error: ' . $e->getMessage()]);
}

ob_end_flush();