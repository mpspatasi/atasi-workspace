<?php
session_start();
require 'koneksi.php';

// Wajibkan output berformat JSON
header('Content-Type: application/json');

// Cek apakah user sudah login
if (!isset($_SESSION['username'])) {
    echo json_encode(['status' => 'error', 'message' => 'Sesi berakhir, silakan login ulang.']);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'produksi') {
    $id_resep = $_POST['id_resep'] ?? '';
    $qty_produksi = floatval($_POST['qty_produksi'] ?? 0);
    $user = $_SESSION['username'];
    $tanggal = date('Y-m-d');
    $referensi = 'PROD-' . time(); // Bikin kode referensi otomatis

    if (empty($id_resep) || $qty_produksi <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Data input produksi tidak valid.']);
        exit;
    }

    try {
        // Mulai Mode Transaksi (Aman dari kegagalan query sebagian)
        $pdo->beginTransaction();

        // 1. Cari data formula/resep yang dipilih
        $stmtResep = $pdo->prepare("SELECT sku_produk, nama_resep FROM db_recipes WHERE id = ?");
        $stmtResep->execute([$id_resep]);
        $resep = $stmtResep->fetch(PDO::FETCH_ASSOC);

        if (!$resep) {
            throw new Exception("Formula/Resep tidak ditemukan di database.");
        }
        $sku_produk_jadi = $resep['sku_produk'];

        // 2. Ambil komposisi bahan baku
        $stmtBahan = $pdo->prepare("
            SELECT ri.sku_bahan, ri.qty_dibutuhkan, i.nama_barang, i.current_stock 
            FROM db_recipe_items ri
            JOIN db_inventory i ON ri.sku_bahan = i.sku
            WHERE ri.id_resep = ?
        ");
        $stmtBahan->execute([$id_resep]);
        $bahan_baku = $stmtBahan->fetchAll(PDO::FETCH_ASSOC);

        if (empty($bahan_baku)) {
            throw new Exception("Formula ini belum memiliki komposisi bahan baku.");
        }

        // 3. Pengecekan ganda (Validasi Stok Cukup)
        foreach ($bahan_baku as $bahan) {
            $total_kebutuhan = $bahan['qty_dibutuhkan'] * $qty_produksi;
            if ($bahan['current_stock'] < $total_kebutuhan) {
                throw new Exception("Stok bahan baku [{$bahan['nama_barang']}] tidak mencukupi!");
            }
        }

        // 4. EKSEKUSI PENGURANGAN BAHAN BAKU (Stock Out)
        $stmtUpdateStokMin = $pdo->prepare("UPDATE db_inventory SET current_stock = current_stock - ? WHERE sku = ?");
        $stmtInsertOut = $pdo->prepare("INSERT INTO db_inventory_out (tanggal, referensi, sku, qty, penerima, keterangan, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");

        foreach ($bahan_baku as $bahan) {
            $total_kebutuhan = $bahan['qty_dibutuhkan'] * $qty_produksi;
            
            // Kurangi saldo master
            $stmtUpdateStokMin->execute([$total_kebutuhan, $bahan['sku_bahan']]);
            
            // Catat log barang keluar
            $keterangan_out = "Bahan baku untuk produksi: " . $resep['nama_resep'];
            $stmtInsertOut->execute([$tanggal, $referensi, $bahan['sku_bahan'], $total_kebutuhan, 'Ruang Produksi', $keterangan_out, $user]);
        }

        // 5. EKSEKUSI PENAMBAHAN PRODUK JADI (Stock In)
        $stmtUpdateStokPlus = $pdo->prepare("UPDATE db_inventory SET current_stock = current_stock + ? WHERE sku = ?");
        $stmtUpdateStokPlus->execute([$qty_produksi, $sku_produk_jadi]);

        // Catat log barang masuk
        $keterangan_in = "Hasil produksi dari resep: " . $resep['nama_resep'];
        
        // (Asumsi kolom db_inventory_in ada: tanggal, referensi, sku, qty, keterangan, created_by)
        // Jika ada error karena kolom 'pengirim', script ini akan otomatis mencoba fallback pakai dummy
        try {
            $stmtInsertIn = $pdo->prepare("INSERT INTO db_inventory_in (tanggal, referensi, sku, qty, keterangan, created_by) VALUES (?, ?, ?, ?, ?, ?)");
            $stmtInsertIn->execute([$tanggal, $referensi, $sku_produk_jadi, $qty_produksi, $keterangan_in, $user]);
        } catch (PDOException $e) {
            // Fallback kalau tabel db_inventory_in kamu wajib isi kolom pengirim/suplier
            $stmtInsertInFallback = $pdo->prepare("INSERT INTO db_inventory_in (tanggal, referensi, sku, qty, pengirim, keterangan, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmtInsertInFallback->execute([$tanggal, $referensi, $sku_produk_jadi, $qty_produksi, 'Internal Produksi', $keterangan_in, $user]);
        }

        // Kalau semua berhasil, sahkan transaksi database!
        $pdo->commit();
        
        echo json_encode(['status' => 'success', 'message' => "Proses produksi terekam! Bahan baku terpotong dan produk jadi berhasil ditambahkan ke gudang."]);

    } catch (Exception $e) {
        // Kalau ada error 1 aja, batalkan semua perubahan!
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Request tidak dimengerti sistem.']);
?>