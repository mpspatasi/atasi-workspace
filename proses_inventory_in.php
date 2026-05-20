<?php
ob_start();
session_start();
header('Content-Type: application/json; charset=utf-8');
require 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Metode request tidak diizinkan.']);
    exit;
}

$action = $_POST['action'] ?? '';
$user = $_SESSION['username'] ?? 'System';

try {
    // ==========================================
    // 1. TAMBAH TRANSAKSI MASUK
    // ==========================================
    if ($action === 'add') {
        $tanggal = trim($_POST['tanggal']);
        $referensi = trim($_POST['referensi']);
        $sku = trim($_POST['sku']);
        $qty = (int)$_POST['qty'];
        $supplier = trim($_POST['supplier']);
        $keterangan = trim($_POST['keterangan']);

        if ($qty <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Qty barang masuk harus lebih dari 0!']); exit;
        }

        $pdo->beginTransaction();

        $sqlIn = "INSERT INTO jurnal_transaksi_atasi.db_inventory_in (tanggal, referensi, sku, qty, supplier, keterangan, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $pdo->prepare($sqlIn)->execute([$tanggal, $referensi, $sku, $qty, $supplier, $keterangan, $user]);

        $sqlUpdate = "UPDATE jurnal_transaksi_atasi.db_inventory SET current_stock = current_stock + ?, last_update = NOW() WHERE sku = ?";
        $pdo->prepare($sqlUpdate)->execute([$qty, $sku]);

        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'Stock IN berhasil dicatat dan Master Stok telah diperbarui.']);
        exit;
    } 
    
    // ==========================================
    // 2. EDIT TRANSAKSI MASUK (KHUSUS SUPERADMIN)
    // ==========================================
    elseif ($action === 'edit') {
        $id = $_POST['id'];
        $new_tanggal = trim($_POST['tanggal']);
        $new_referensi = trim($_POST['referensi']);
        $new_sku = trim($_POST['sku']);
        $new_qty = (int)$_POST['qty'];
        $new_supplier = trim($_POST['supplier']);
        $new_keterangan = trim($_POST['keterangan']);

        if ($new_qty <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Qty barang masuk harus lebih dari 0!']); exit;
        }

        $pdo->beginTransaction();

        // Ambil data transaksi lama
        $stmtOld = $pdo->prepare("SELECT sku, qty FROM jurnal_transaksi_atasi.db_inventory_in WHERE id = ?");
        $stmtOld->execute([$id]);
        $oldData = $stmtOld->fetch();

        if (!$oldData) {
            throw new Exception("Data riwayat transaksi tidak ditemukan.");
        }

        $old_sku = $oldData['sku'];
        $old_qty = (int)$oldData['qty'];

        // Cek Logika Perubahan Master Stok
        if ($old_sku === $new_sku) {
            // Jika Barangnya Sama: Cari selisihnya lalu tambahkan/kurangi ke stok saat ini
            $selisih = $new_qty - $old_qty; 
            $pdo->prepare("UPDATE jurnal_transaksi_atasi.db_inventory SET current_stock = current_stock + ?, last_update = NOW() WHERE sku = ?")
                ->execute([$selisih, $new_sku]);
        } else {
            // Jika Barangnya Beda (Salah Input Barang): Kembalikan stok lama, lalu masukkan stok baru
            // 1. Kurangi Qty lama dari SKU yang lama
            $pdo->prepare("UPDATE jurnal_transaksi_atasi.db_inventory SET current_stock = current_stock - ?, last_update = NOW() WHERE sku = ?")
                ->execute([$old_qty, $old_sku]);
            
            // 2. Tambahkan Qty baru ke SKU yang baru
            $pdo->prepare("UPDATE jurnal_transaksi_atasi.db_inventory SET current_stock = current_stock + ?, last_update = NOW() WHERE sku = ?")
                ->execute([$new_qty, $new_sku]);
        }

        // Update riwayat tabel transaksi masuk
        $sqlUpdtIn = "UPDATE jurnal_transaksi_atasi.db_inventory_in SET tanggal=?, referensi=?, sku=?, qty=?, supplier=?, keterangan=? WHERE id=?";
        $pdo->prepare($sqlUpdtIn)->execute([$new_tanggal, $new_referensi, $new_sku, $new_qty, $new_supplier, $new_keterangan, $id]);

        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'Data histori berhasil diupdate dan stok sudah disinkron ulang.']);
        exit;
    }

    // ==========================================
    // 3. HAPUS TRANSAKSI MASUK (KHUSUS SUPERADMIN)
    // ==========================================
    elseif ($action === 'delete') {
        $id = $_POST['id'];

        $pdo->beginTransaction();

        // Ambil data sebelum dihapus (untuk memotong stok master)
        $stmtOld = $pdo->prepare("SELECT sku, qty FROM jurnal_transaksi_atasi.db_inventory_in WHERE id = ?");
        $stmtOld->execute([$id]);
        $oldData = $stmtOld->fetch();

        if ($oldData) {
            $old_sku = $oldData['sku'];
            $old_qty = (int)$oldData['qty'];

            // Potong Master Stok karena transaksi masuknya dibatalkan/dihapus
            $pdo->prepare("UPDATE jurnal_transaksi_atasi.db_inventory SET current_stock = current_stock - ?, last_update = NOW() WHERE sku = ?")
                ->execute([$old_qty, $old_sku]);

            // Hapus riwayat dari database
            $pdo->prepare("DELETE FROM jurnal_transaksi_atasi.db_inventory_in WHERE id = ?")->execute([$id]);
            
            $pdo->commit();
            echo json_encode(['status' => 'success', 'message' => 'Transaksi dihapus. Master Stok berhasil dipotong kembali sesuai jumlah.']);
            exit;
        } else {
            throw new Exception("Data tidak ditemukan.");
        }
    }

    echo json_encode(['status' => 'error', 'message' => 'Action tidak valid.']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack(); // Batalkan semua eksekusi query jika ada salah satu yang gagal
    }
    echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()]);
}

ob_end_flush();