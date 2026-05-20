<?php
ob_start();
session_start();
header('Content-Type: application/json; charset=utf-8');
require 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Metode request tidak diizinkan.']);
    exit;
}

$action = $_POST['action'] ?? ($_GET['action'] ?? '');

try {
    // ==========================================
    // 1. TAMBAH FORMULA BARU
    // ==========================================
    if ($action === 'add') {
        $nama_resep = trim($_POST['nama_resep'] ?? '');
        $sku_produk = trim($_POST['sku_produk'] ?? '');
        $keterangan = trim($_POST['keterangan'] ?? '');
        $target_qty = filter_var($_POST['target_qty'] ?? 1, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        
        $sku_bahan_arr = $_POST['sku_bahan'] ?? [];
        $qty_bahan_arr = $_POST['qty_bahan'] ?? [];

        if (empty($nama_resep) || empty($sku_produk)) {
            throw new Exception("Nama Formula dan Produk Jadi wajib diisi!");
        }
        if (empty($sku_bahan_arr) || count($sku_bahan_arr) === 0 || empty($sku_bahan_arr[0])) {
            throw new Exception("Minimal harus ada 1 bahan baku yang dipilih!");
        }

        $pdo->beginTransaction();

        // A. Insert ke tabel Induk TERMASUK target_qty
        $sqlRecipe = "INSERT INTO db_recipes (sku_produk, nama_resep, keterangan, target_qty) VALUES (?, ?, ?, ?)";
        $stmtRecipe = $pdo->prepare($sqlRecipe);
        $stmtRecipe->execute([$sku_produk, $nama_resep, $keterangan, $target_qty]);
        
        $id_resep = $pdo->lastInsertId();

        // B. Insert bahan baku (Tetap simpan rasio per 1 unit)
        $sqlItem = "INSERT INTO db_recipe_items (id_resep, sku_bahan, qty_dibutuhkan) VALUES (?, ?, ?)";
        $stmtItem = $pdo->prepare($sqlItem);

        for ($i = 0; $i < count($sku_bahan_arr); $i++) {
            $sku_bahan = trim($sku_bahan_arr[$i]);
            $qty_bahan = filter_var($qty_bahan_arr[$i], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

            if (!empty($sku_bahan) && (float)$qty_bahan > 0) {
                $stmtItem->execute([$id_resep, $sku_bahan, $qty_bahan]);
            }
        }

        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'Formula Produksi berhasil disimpan.']);
        exit;
    } 
    
    // ==========================================
    // 2. EDIT FORMULA
    // ==========================================
    elseif ($action === 'edit') {
        $id = $_POST['id_resep'] ?? '';
        $nama_resep = trim($_POST['nama_resep'] ?? '');
        $sku_produk = trim($_POST['sku_produk'] ?? '');
        $keterangan = trim($_POST['keterangan'] ?? '');
        $target_qty = filter_var($_POST['target_qty'] ?? 1, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        
        $sku_bahan = $_POST['sku_bahan'] ?? [];
        $qty_bahan = $_POST['qty_bahan'] ?? [];

        if (empty($id) || empty($nama_resep) || empty($sku_produk) || empty($sku_bahan)) {
            throw new Exception("Data tidak lengkap.");
        }

        $pdo->beginTransaction();

        // 1. Update nama formula, produk utama & target_qty
        $stmtUpdate = $pdo->prepare("UPDATE db_recipes SET nama_resep = ?, sku_produk = ?, keterangan = ?, target_qty = ? WHERE id = ?");
        $stmtUpdate->execute([$nama_resep, $sku_produk, $keterangan, $target_qty, $id]);

        // 2. Hapus bahan baku lama
        $stmtDel = $pdo->prepare("DELETE FROM db_recipe_items WHERE id_resep = ?");
        $stmtDel->execute([$id]);

        // 3. Masukkan komposisi bahan baku yang baru (Tetap rasio per 1 unit)
        $stmtItem = $pdo->prepare("INSERT INTO db_recipe_items (id_resep, sku_bahan, qty_dibutuhkan) VALUES (?, ?, ?)");
        for ($i = 0; $i < count($sku_bahan); $i++) {
            $bahan = trim($sku_bahan[$i]);
            $qty = filter_var($qty_bahan[$i], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            
            if (!empty($bahan) && (float)$qty > 0) {
                $stmtItem->execute([$id, $bahan, $qty]);
            }
        }

        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'Formula berhasil diupdate!']);
        exit;
    }

    // ==========================================
    // 3. HAPUS FORMULA
    // ==========================================
    elseif ($action === 'delete') {
        $id = $_POST['id'] ?? 0;
        if (empty($id)) throw new Exception("ID Formula tidak valid.");

        $pdo->beginTransaction();
        $stmtDelItems = $pdo->prepare("DELETE FROM db_recipe_items WHERE id_resep = ?");
        $stmtDelItems->execute([$id]);
        $stmtDelRecipe = $pdo->prepare("DELETE FROM db_recipes WHERE id = ?");
        $stmtDelRecipe->execute([$id]);

        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'Formula Produksi berhasil dihapus.']);
        exit;
    }

    throw new Exception("Action '$action' tidak valid.");

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack(); 
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}
ob_end_flush();
?>