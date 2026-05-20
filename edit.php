<?php
require 'koneksi.php';

// 1. AMBIL DATA LAMA BERDASARKAN ID
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM db_customer WHERE id = ?");
    $stmt->execute([$id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
        echo "<script>alert('Data tidak ditemukan!'); window.location.href = 'transaksi.php';</script>";
        exit;
    }
} else {
    // Balik ke transaksi.php pakai JS biar lebih kebal error
    echo "<script>window.location.href = 'transaksi.php';</script>";
    exit;
}

// 2. PROSES UPDATE DATA SAAT FORM DI-SUBMIT
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    
    // A. KELOMPOK TANGGAL & WAKTU (Ubah kosong jadi NULL)
    $tgl_p   = empty(trim($_POST['Tanggal_Pesanan'] ?? '')) ? null : trim($_POST['Tanggal_Pesanan']);
    $waktu_p = empty(trim($_POST['Waktu_Pesanan'] ?? '')) ? null : trim($_POST['Waktu_Pesanan']);
    $tgl_d   = empty(trim($_POST['Tanggal_Dikirim'] ?? '')) ? null : trim($_POST['Tanggal_Dikirim']);
    $tgl_r   = empty(trim($_POST['Tanggal_Retur'] ?? '')) ? null : trim($_POST['Tanggal_Retur']);

    // B. KELOMPOK ANGKA / NOMINAL (Ubah kosong jadi 0)
    $jumlah   = trim($_POST['Jumlah'] ?? '') === '' ? 0 : trim($_POST['Jumlah']);
    $valuecx  = trim($_POST['ValueCX'] ?? '') === '' ? 0 : trim($_POST['ValueCX']);
    $valueads = trim($_POST['ValueADS'] ?? '') === '' ? 0 : trim($_POST['ValueADS']);

    // C. KELOMPOK TEKS / STRING
    $media      = trim($_POST['Media'] ?? '');
    $kuartal    = trim($_POST['Kuartal'] ?? '');
    $no_p       = trim($_POST['No_Pesanan'] ?? '');
    $resi       = trim($_POST['Resi'] ?? '');
    $status     = trim($_POST['Status_Pesanan'] ?? '');
    $produk     = trim($_POST['Produk'] ?? '');
    $ekspedisi  = trim($_POST['Ekspedisi'] ?? '');
    $username   = trim($_POST['Username'] ?? '');
    $nama       = trim($_POST['Nama'] ?? '');
    $telp       = trim($_POST['Telp'] ?? '');
    $provinsi   = trim($_POST['Provinsi'] ?? '');
    $kabupaten  = trim($_POST['Kabupaten'] ?? '');
    $kecamatan  = trim($_POST['Kecamatan'] ?? '');
    $kodepos    = trim($_POST['Kodepos'] ?? '');
    $handle     = trim($_POST['Handle'] ?? '');
    $keterangan = trim($_POST['Keterangan'] ?? '');
    $komoditas  = trim($_POST['Komoditas'] ?? '');

    try {
        $sql = "UPDATE db_customer SET 
                Media=?, Kuartal=?, Tanggal_Pesanan=?, Waktu_Pesanan=?, No_Pesanan=?, Resi=?, 
                Tanggal_Dikirim=?, Status_Pesanan=?, Tanggal_Retur=?, Produk=?, Jumlah=?, 
                Ekspedisi=?, Username=?, Nama=?, Telp=?, Provinsi=?, Kabupaten=?, 
                Kecamatan=?, Kodepos=?, Handle=?, Keterangan=?, ValueCX=?, ValueADS=?, 
                Komoditas=? 
                WHERE id=?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $media, $kuartal, $tgl_p, $waktu_p, $no_p, $resi, $tgl_d, $status, $tgl_r, 
            $produk, $jumlah, $ekspedisi, $username, $nama, $telp, $provinsi, $kabupaten, 
            $kecamatan, $kodepos, $handle, $keterangan, $valuecx, $valueads, $komoditas, $id
        ]);

        // Redirect pakai JS (Anti Gagal) setelah sukses simpan
        echo "<script>window.location.href = 'transaksi.php';</script>";
        exit;
    } catch (PDOException $e) {
        die("Gagal update cuy! Error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Data - ATASI</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 p-8">
    <div class="max-w-5xl mx-auto bg-white p-8 rounded-xl shadow-sm border border-slate-200">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-slate-700">Edit Data Transaksi</h2>
            <a href="transaksi.php" class="text-slate-500 hover:text-slate-700 font-medium">Kembali</a>
        </div>

        <form method="POST" class="space-y-6">
            <input type="hidden" name="id" value="<?= $data['id'] ?>">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                
                <div class="space-y-4 p-4 bg-slate-50 rounded-lg border border-slate-100">
                    <h3 class="font-semibold text-slate-600 border-b pb-2 text-sm uppercase">Info Pesanan</h3>
                    <div><label class="block text-xs text-slate-500 mb-1">No. Pesanan</label>
                        <input type="text" name="No_Pesanan" value="<?= htmlspecialchars($data['No_Pesanan'] ?? '') ?>" class="w-full px-3 py-1.5 border rounded-md focus:ring-2 focus:ring-[#3E54D3] outline-none text-sm">
                    </div>
                    <div><label class="block text-xs text-slate-500 mb-1">Media</label>
                        <input type="text" name="Media" value="<?= htmlspecialchars($data['Media'] ?? '') ?>" class="w-full px-3 py-1.5 border rounded-md focus:ring-2 focus:ring-[#3E54D3] outline-none text-sm">
                    </div>
                    <div><label class="block text-xs text-slate-500 mb-1">Kuartal</label>
                        <input type="text" name="Kuartal" value="<?= htmlspecialchars($data['Kuartal'] ?? '') ?>" class="w-full px-3 py-1.5 border rounded-md focus:ring-2 focus:ring-[#3E54D3] outline-none text-sm">
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div><label class="block text-xs text-slate-500 mb-1">Tanggal</label>
                            <input type="date" name="Tanggal_Pesanan" value="<?= htmlspecialchars($data['Tanggal_Pesanan'] ?? '') ?>" class="w-full px-3 py-1.5 border rounded-md focus:ring-2 focus:ring-[#3E54D3] outline-none text-sm">
                        </div>
                        <div><label class="block text-xs text-slate-500 mb-1">Waktu</label>
                            <input type="time" name="Waktu_Pesanan" value="<?= htmlspecialchars($data['Waktu_Pesanan'] ?? '') ?>" class="w-full px-3 py-1.5 border rounded-md focus:ring-2 focus:ring-[#3E54D3] outline-none text-sm">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs text-slate-500 mb-1">Status Pesanan</label>
                        <select name="Status_Pesanan" class="w-full px-3 py-1.5 border rounded-md bg-white text-sm focus:ring-2 focus:ring-[#3E54D3] outline-none cursor-pointer">
                            <?php 
                            $s = $data['Status_Pesanan'] ?? ''; 
                            $status_options = ["Batal", "Belum Bayar", "Dikirim", "Pembatalan Diajukan", "Perlu Dikirim", "Retur", "Selesai"];
                            foreach ($status_options as $opt):
                            ?>
                                <option value="<?= $opt ?>" <?= $s == $opt ? 'selected' : '' ?>><?= $opt ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div><label class="block text-xs text-slate-500 mb-1">Produk</label>
                        <input type="text" name="Produk" value="<?= htmlspecialchars($data['Produk'] ?? '') ?>" class="w-full px-3 py-1.5 border rounded-md focus:ring-2 focus:ring-[#3E54D3] outline-none text-sm">
                    </div>
                    <div><label class="block text-xs text-slate-500 mb-1">Jumlah</label>
                        <input type="number" name="Jumlah" value="<?= htmlspecialchars($data['Jumlah'] ?? '') ?>" class="w-full px-3 py-1.5 border rounded-md focus:ring-2 focus:ring-[#3E54D3] outline-none text-sm">
                    </div>
                    <div><label class="block text-xs text-slate-500 mb-1">Value CX (Rp)</label>
                        <input type="number" name="ValueCX" value="<?= htmlspecialchars($data['ValueCX'] ?? '') ?>" class="w-full px-3 py-1.5 border rounded-md focus:ring-2 focus:ring-[#3E54D3] outline-none text-sm font-medium">
                    </div>
                    <div><label class="block text-xs text-slate-500 mb-1">Value ADS (Rp)</label>
                        <input type="number" name="ValueADS" value="<?= htmlspecialchars($data['ValueADS'] ?? '') ?>" class="w-full px-3 py-1.5 border rounded-md focus:ring-2 focus:ring-[#3E54D3] outline-none text-sm">
                    </div>
                </div>

                <div class="space-y-4 p-4 bg-slate-50 rounded-lg border border-slate-100">
                    <h3 class="font-semibold text-slate-600 border-b pb-2 text-sm uppercase">Logistik & Segmentasi</h3>
                    <div><label class="block text-xs text-slate-500 mb-1">Ekspedisi</label>
                        <input type="text" name="Ekspedisi" value="<?= htmlspecialchars($data['Ekspedisi'] ?? '') ?>" class="w-full px-3 py-1.5 border rounded-md focus:ring-2 focus:ring-[#3E54D3] outline-none text-sm">
                    </div>
                    <div><label class="block text-xs text-slate-500 mb-1">Resi</label>
                        <input type="text" name="Resi" value="<?= htmlspecialchars($data['Resi'] ?? '') ?>" class="w-full px-3 py-1.5 border rounded-md focus:ring-2 focus:ring-[#3E54D3] outline-none text-sm">
                    </div>
                    <div><label class="block text-xs text-slate-500 mb-1">Tanggal Dikirim</label>
                        <input type="date" name="Tanggal_Dikirim" value="<?= htmlspecialchars($data['Tanggal_Dikirim'] ?? '') ?>" class="w-full px-3 py-1.5 border rounded-md focus:ring-2 focus:ring-[#3E54D3] outline-none text-sm">
                    </div>
                    <div><label class="block text-xs text-slate-500 mb-1">Tanggal Retur</label>
                        <input type="date" name="Tanggal_Retur" value="<?= htmlspecialchars($data['Tanggal_Retur'] ?? '') ?>" class="w-full px-3 py-1.5 border rounded-md focus:ring-2 focus:ring-[#3E54D3] outline-none text-sm">
                    </div>
                    
                    <h3 class="font-semibold text-slate-600 border-b pb-2 mt-6 text-sm uppercase">Lainnya</h3>
                    <div><label class="block text-xs text-slate-500 mb-1">Komoditas</label>
                        <input type="text" name="Komoditas" value="<?= htmlspecialchars($data['Komoditas'] ?? '') ?>" class="w-full px-3 py-1.5 border rounded-md focus:ring-2 focus:ring-[#3E54D3] outline-none text-sm">
                    </div>
                    <div><label class="block text-xs text-slate-500 mb-1">Handle By</label>
                        <input type="text" name="Handle" value="<?= htmlspecialchars($data['Handle'] ?? '') ?>" class="w-full px-3 py-1.5 border rounded-md focus:ring-2 focus:ring-[#3E54D3] outline-none text-sm">
                    </div>
                </div>

                <div class="space-y-4 p-4 bg-slate-50 rounded-lg border border-slate-100">
                    <h3 class="font-semibold text-slate-600 border-b pb-2 text-sm uppercase">Info Pelanggan</h3>
                    <div><label class="block text-xs text-slate-500 mb-1">Username/Sosmed</label>
                        <input type="text" name="Username" value="<?= htmlspecialchars($data['Username'] ?? '') ?>" class="w-full px-3 py-1.5 border rounded-md focus:ring-2 focus:ring-[#3E54D3] outline-none text-sm">
                    </div>
                    <div><label class="block text-xs text-slate-500 mb-1">Nama Lengkap</label>
                        <input type="text" name="Nama" value="<?= htmlspecialchars($data['Nama'] ?? '') ?>" class="w-full px-3 py-1.5 border rounded-md focus:ring-2 focus:ring-[#3E54D3] outline-none text-sm">
                    </div>
                    <div><label class="block text-xs text-slate-500 mb-1">No. Telp / WA</label>
                        <input type="text" name="Telp" value="<?= htmlspecialchars($data['Telp'] ?? '') ?>" class="w-full px-3 py-1.5 border rounded-md focus:ring-2 focus:ring-[#3E54D3] outline-none text-sm">
                    </div>
                    <div><label class="block text-xs text-slate-500 mb-1">Provinsi</label>
                        <input type="text" name="Provinsi" value="<?= htmlspecialchars($data['Provinsi'] ?? '') ?>" class="w-full px-3 py-1.5 border rounded-md focus:ring-2 focus:ring-[#3E54D3] outline-none text-sm">
                    </div>
                    <div><label class="block text-xs text-slate-500 mb-1">Kabupaten/Kota</label>
                        <input type="text" name="Kabupaten" value="<?= htmlspecialchars($data['Kabupaten'] ?? '') ?>" class="w-full px-3 py-1.5 border rounded-md focus:ring-2 focus:ring-[#3E54D3] outline-none text-sm">
                    </div>
                    <div><label class="block text-xs text-slate-500 mb-1">Kecamatan</label>
                        <input type="text" name="Kecamatan" value="<?= htmlspecialchars($data['Kecamatan'] ?? '') ?>" class="w-full px-3 py-1.5 border rounded-md focus:ring-2 focus:ring-[#3E54D3] outline-none text-sm">
                    </div>
                    <div><label class="block text-xs text-slate-500 mb-1">Kodepos</label>
                        <input type="text" name="Kodepos" value="<?= htmlspecialchars($data['Kodepos'] ?? '') ?>" class="w-full px-3 py-1.5 border rounded-md focus:ring-2 focus:ring-[#3E54D3] outline-none text-sm">
                    </div>
                    <div><label class="block text-xs text-slate-500 mb-1">Catatan</label>
                        <textarea name="Keterangan" rows="2" class="w-full px-3 py-1.5 border rounded-md focus:ring-2 focus:ring-[#3E54D3] outline-none text-sm"><?= htmlspecialchars($data['Keterangan'] ?? '') ?></textarea>
                    </div>
                </div>

            </div>

            <div class="flex gap-4 justify-end pt-4 border-t border-slate-200">
                <a href="transaksi.php" class="px-6 py-2 text-slate-600 border border-slate-300 rounded-lg hover:bg-slate-50 font-medium">Batal</a>
                <button type="submit" class="px-6 py-2 bg-[#3E54D3] text-white rounded-lg hover:bg-blue-800 font-medium shadow-sm transition-colors">Update Data</button>
            </div>
        </form>
    </div>
</body>
</html>