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
    // 1. TAMBAH TRANSAKSI KELUAR
    // ==========================================
    if ($action === 'add') {
        $tanggal = trim($_POST['tanggal']);
        $referensi = trim($_POST['referensi']);
        $sku = trim($_POST['sku']);
        // Pastikan pakai float agar bisa terima desimal
        $qty = (float) $_POST['qty'];
        $penerima = trim($_POST['penerima']);
        $keterangan = trim($_POST['keterangan']);

        if ($qty <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Qty barang keluar harus lebih dari 0!']); exit;
        }

        $pdo->beginTransaction();

        // [PROTEKSI] Cek apakah Master Stok cukup!
        $stmtCek = $pdo->prepare("SELECT current_stock, nama_barang FROM jurnal_transaksi_atasi.db_inventory WHERE sku = ? FOR UPDATE");
        $stmtCek->execute([$sku]);
        $cekStok = $stmtCek->fetch();

        if (!$cekStok) {
            throw new Exception("Barang tidak ditemukan di Master Data.");
        }
        if ($cekStok['current_stock'] < $qty) {
            throw new Exception("STOK TIDAK CUKUP! Sisa stok '{$cekStok['nama_barang']}' saat ini hanya {$cekStok['current_stock']}.");
        }

        // Catat ke db_inventory_out
        $sqlOut = "INSERT INTO jurnal_transaksi_atasi.db_inventory_out (tanggal, referensi, sku, qty, penerima, keterangan, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $pdo->prepare($sqlOut)->execute([$tanggal, $referensi, $sku, $qty, $penerima, $keterangan, $user]);

        // POTONG Master Stok (-)
        $sqlUpdate = "UPDATE jurnal_transaksi_atasi.db_inventory SET current_stock = current_stock - ?, last_update = NOW() WHERE sku = ?";
        $pdo->prepare($sqlUpdate)->execute([$qty, $sku]);

        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'Stock OUT berhasil dicatat dan Master Stok telah terpotong.']);
        exit;
    } 
    
    // ==========================================
    // 2. EDIT TRANSAKSI KELUAR (SUPERADMIN)
    // ==========================================
    elseif ($action === 'edit') {
        $id = $_POST['id'];
        $new_tanggal = trim($_POST['tanggal']);
        $new_referensi = trim($_POST['referensi']);
        $new_sku = trim($_POST['sku']);
        // FIX: Ubah (int) menjadi (float) agar terbaca sebagai desimal
        $new_qty = (float)$_POST['qty'];
        $new_penerima = trim($_POST['penerima']);
        $new_keterangan = trim($_POST['keterangan']);

        if ($new_qty <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Qty barang keluar harus lebih dari 0!']); exit;
        }

        $pdo->beginTransaction();

        // Ambil data transaksi lama
        $stmtOld = $pdo->prepare("SELECT sku, qty FROM jurnal_transaksi_atasi.db_inventory_out WHERE id = ? FOR UPDATE");
        $stmtOld->execute([$id]);
        $oldData = $stmtOld->fetch();

        if (!$oldData) throw new Exception("Data riwayat transaksi tidak ditemukan.");

        $old_sku = $oldData['sku'];
        // FIX: Ubah (int) menjadi (float)
        $old_qty = (float)$oldData['qty'];

        if ($old_sku === $new_sku) {
            // Barangnya sama, hitung selisihnya
            $selisih = $new_qty - $old_qty; 
            
            // [PROTEKSI] Kalau qty yg diedit JAUH LEBIH BESAR, cek stok dulu cukup gak buat motong selisihnya
            if ($selisih > 0) {
                $stmtCek = $pdo->prepare("SELECT current_stock FROM jurnal_transaksi_atasi.db_inventory WHERE sku = ?");
                $stmtCek->execute([$new_sku]);
                $stokSkrg = (float)$stmtCek->fetchColumn();
                if ($stokSkrg < $selisih) throw new Exception("STOK TIDAK CUKUP! Anda butuh tambahan $selisih stok untuk mengupdate ke angka ini.");
            }

            $pdo->prepare("UPDATE jurnal_transaksi_atasi.db_inventory SET current_stock = current_stock - ?, last_update = NOW() WHERE sku = ?")
                ->execute([$selisih, $new_sku]);
        } else {
            // Jika Barangnya Beda (Salah pilih barang saat input lama)
            // 1. REFUND/Kembalikan Master Stok lama (+)
            $pdo->prepare("UPDATE jurnal_transaksi_atasi.db_inventory SET current_stock = current_stock + ?, last_update = NOW() WHERE sku = ?")
                ->execute([$old_qty, $old_sku]);
            
            // 2. [PROTEKSI] Cek Master Stok baru
            $stmtCek = $pdo->prepare("SELECT current_stock FROM jurnal_transaksi_atasi.db_inventory WHERE sku = ?");
            $stmtCek->execute([$new_sku]);
            $stokSkrg = (float)$stmtCek->fetchColumn();
            if ($stokSkrg < $new_qty) throw new Exception("Stok barang SKU baru ini tidak mencukupi untuk dikeluarkan!");

            // 3. POTONG Master Stok baru (-)
            $pdo->prepare("UPDATE jurnal_transaksi_atasi.db_inventory SET current_stock = current_stock - ?, last_update = NOW() WHERE sku = ?")
                ->execute([$new_qty, $new_sku]);
        }

        // Update ke db_inventory_out
        $sqlUpdtOut = "UPDATE jurnal_transaksi_atasi.db_inventory_out SET tanggal=?, referensi=?, sku=?, qty=?, penerima=?, keterangan=? WHERE id=?";
        $pdo->prepare($sqlUpdtOut)->execute([$new_tanggal, $new_referensi, $new_sku, $new_qty, $new_penerima, $new_keterangan, $id]);

        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'Data histori berhasil diupdate.']);
        exit;
    }

    // ==========================================
    // 3. HAPUS TRANSAKSI KELUAR (SUPERADMIN)
    // ==========================================
    elseif ($action === 'delete') {
        $id = $_POST['id'];

        $pdo->beginTransaction();

        $stmtOld = $pdo->prepare("SELECT sku, qty FROM jurnal_transaksi_atasi.db_inventory_out WHERE id = ? FOR UPDATE");
        $stmtOld->execute([$id]);
        $oldData = $stmtOld->fetch();

        if ($oldData) {
            $old_sku = $oldData['sku'];
            // FIX: Ubah (int) menjadi (float)
            $old_qty = (float)$oldData['qty'];

            // REFUND! Balikin lagi barang yg sempet keluar ke Master Stok (+)
            $pdo->prepare("UPDATE jurnal_transaksi_atasi.db_inventory SET current_stock = current_stock + ?, last_update = NOW() WHERE sku = ?")
                ->execute([$old_qty, $old_sku]);

            // Hapus dari histori
            $pdo->prepare("DELETE FROM jurnal_transaksi_atasi.db_inventory_out WHERE id = ?")->execute([$id]);
            
            $pdo->commit();
            echo json_encode(['status' => 'success', 'message' => 'Transaksi dihapus. Master Stok berhasil ditambah kembali (+).']);
            exit;
        } else {
            throw new Exception("Data tidak ditemukan.");
        }
    }

    echo json_encode(['status' => 'error', 'message' => 'Action tidak valid.']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack(); // Batal semua transaksi database klo ada yg error
    }
    // Tangkap error buat SweetAlert
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
ob_end_flush();
?>