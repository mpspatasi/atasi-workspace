<?php
require 'koneksi.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil semua data dari form (pakai fallback array kosong/0 kalau nggak diisi)
    $media = $_POST['Media'] ?? '';
    $kuartal = $_POST['Kuartal'] ?? '';
    $tgl_pesanan = $_POST['Tanggal_Pesanan'] ?? date('Y-m-d');
    $waktu_pesanan = $_POST['Waktu_Pesanan'] ?? date('H:i:s');
    $no_pesanan = $_POST['No_Pesanan'] ?? '';
    $resi = $_POST['Resi'] ?? '';
    // Tanggal dikirim/retur bisa null
    $tgl_dikirim = !empty($_POST['Tanggal_Dikirim']) ? $_POST['Tanggal_Dikirim'] : NULL;
    $status = $_POST['Status_Pesanan'] ?? 'Selesai';
    $tgl_retur = !empty($_POST['Tanggal_Retur']) ? $_POST['Tanggal_Retur'] : NULL;
    $produk = $_POST['Produk'] ?? '';
    $jumlah = $_POST['Jumlah'] ?? 0;
    $ekspedisi = $_POST['Ekspedisi'] ?? '';
    $username = $_POST['Username'] ?? '';
    $nama = $_POST['Nama'] ?? '';
    $telp = $_POST['Telp'] ?? '';
    $provinsi = $_POST['Provinsi'] ?? '';
    $kabupaten = $_POST['Kabupaten'] ?? '';
    $kecamatan = $_POST['Kecamatan'] ?? '';
    $kodepos = $_POST['Kodepos'] ?? '';
    $handle = $_POST['Handle'] ?? '';
    $keterangan = $_POST['Keterangan'] ?? '';
    $valuecx = $_POST['ValueCX'] ?? 0;
    $valueads = $_POST['ValueADS'] ?? 0;
    $komoditas = $_POST['Komoditas'] ?? '';
    $rfm = $_POST['RFM'] ?? 'Newcomer';

    // INSERT ke 25 kolom (id auto-increment)
    $sql = "INSERT INTO db_customer (Media, Kuartal, Tanggal_Pesanan, Waktu_Pesanan, No_Pesanan, Resi, Tanggal_Dikirim, Status_Pesanan, Tanggal_Retur, Produk, Jumlah, Ekspedisi, Username, Nama, Telp, Provinsi, Kabupaten, Kecamatan, Kodepos, Handle, Keterangan, ValueCX, ValueADS, Komoditas, RFM) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$media, $kuartal, $tgl_pesanan, $waktu_pesanan, $no_pesanan, $resi, $tgl_dikirim, $status, $tgl_retur, $produk, $jumlah, $ekspedisi, $username, $nama, $telp, $provinsi, $kabupaten, $kecamatan, $kodepos, $handle, $keterangan, $valuecx, $valueads, $komoditas, $rfm]);

    // Redirect pakai JS (Anti Gagal) setelah sukses simpan
    echo "<script>window.location.href = 'transaksi.php';</script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Data - ATASI</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 p-8">
    <div class="max-w-5xl mx-auto bg-white p-8 rounded-xl shadow-sm border border-slate-200">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-slate-700">Tambah Transaksi Baru</h2>
            <a href="transaksi.php" class="text-slate-500 hover:text-slate-700">Kembali</a>
        </div>

        <form method="POST" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                
                <div class="space-y-4 p-4 bg-slate-50 rounded-lg border border-slate-100">
                    <h3 class="font-semibold text-slate-600 border-b pb-2">Info Pesanan</h3>
                    <div><label class="block text-sm text-slate-600 mb-1">No. Pesanan</label><input type="text" name="No_Pesanan" class="w-full px-3 py-1.5 border rounded-md"></div>
                    <div><label class="block text-sm text-slate-600 mb-1">Media</label><input type="text" name="Media" placeholder="Shopee/Tiktok/WA" class="w-full px-3 py-1.5 border rounded-md"></div>
                    <div><label class="block text-sm text-slate-600 mb-1">Kuartal</label><input type="text" name="Kuartal" placeholder="Q1-2025" class="w-full px-3 py-1.5 border rounded-md"></div>
                    <div class="grid grid-cols-2 gap-2">
                        <div><label class="block text-sm text-slate-600 mb-1">Tanggal</label><input type="date" name="Tanggal_Pesanan" value="<?= date('Y-m-d') ?>" class="w-full px-3 py-1.5 border rounded-md"></div>
                        <div><label class="block text-sm text-slate-600 mb-1">Waktu</label><input type="time" name="Waktu_Pesanan" value="<?= date('H:i') ?>" class="w-full px-3 py-1.5 border rounded-md"></div>
                    </div>
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Status Pesanan</label>
                        <select name="Status_Pesanan" class="w-full px-3 py-1.5 border rounded-md bg-white">
                            <option value="Selesai">Selesai</option>
                            <option value="Dikirim">Dikirim</option>
                            <option value="Batal">Batal</option>
                            <option value="Retur">Retur</option>
                        </select>
                    </div>
                    <div><label class="block text-sm text-slate-600 mb-1">Produk</label><input type="text" name="Produk" value="Zat Stimulator Tanaman Fulka" class="w-full px-3 py-1.5 border rounded-md bg-white"></div>
                    <div><label class="block text-sm text-slate-600 mb-1">Jumlah (Qty)</label><input type="number" name="Jumlah" value="1" class="w-full px-3 py-1.5 border rounded-md"></div>
                    <div><label class="block text-sm text-slate-600 mb-1">Value CX (Rp)</label><input type="number" name="ValueCX" placeholder="150000" class="w-full px-3 py-1.5 border rounded-md"></div>
                    <div><label class="block text-sm text-slate-600 mb-1">Value ADS (Rp)</label><input type="number" name="ValueADS" placeholder="0" class="w-full px-3 py-1.5 border rounded-md"></div>
                </div>

                <div class="space-y-4 p-4 bg-slate-50 rounded-lg border border-slate-100">
                    <h3 class="font-semibold text-slate-600 border-b pb-2">Logistik & RFM</h3>
                    <div><label class="block text-sm text-slate-600 mb-1">Ekspedisi</label><input type="text" name="Ekspedisi" class="w-full px-3 py-1.5 border rounded-md"></div>
                    <div><label class="block text-sm text-slate-600 mb-1">Resi</label><input type="text" name="Resi" class="w-full px-3 py-1.5 border rounded-md"></div>
                    <div><label class="block text-sm text-slate-600 mb-1">Tanggal Dikirim</label><input type="date" name="Tanggal_Dikirim" class="w-full px-3 py-1.5 border rounded-md"></div>
                    <div><label class="block text-sm text-slate-600 mb-1">Tanggal Retur</label><input type="date" name="Tanggal_Retur" class="w-full px-3 py-1.5 border rounded-md"></div>
                    
                    <h3 class="font-semibold text-slate-600 border-b pb-2 mt-6">Segmentasi</h3>
                    <div><label class="block text-sm text-slate-600 mb-1">Komoditas</label><input type="text" name="Komoditas" placeholder="Padi / Jagung / dll" class="w-full px-3 py-1.5 border rounded-md"></div>
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Segmen RFM</label>
                        <select name="RFM" class="w-full px-3 py-1.5 border rounded-md bg-white">
                            <option value="Newcomer">Newcomer</option>
                            <option value="Potential">Potential</option>
                            <option value="Loyalist">Loyalist</option>
                            <option value="Champions">Champions</option>
                            <option value="At Risk">At Risk</option>
                            <option value="Lost">Lost</option>
                        </select>
                    </div>
                    <div><label class="block text-sm text-slate-600 mb-1">Handle By</label><input type="text" name="Handle" placeholder="Nama CS" class="w-full px-3 py-1.5 border rounded-md"></div>
                </div>

                <div class="space-y-4 p-4 bg-slate-50 rounded-lg border border-slate-100">
                    <h3 class="font-semibold text-slate-600 border-b pb-2">Info Pelanggan</h3>
                    <div><label class="block text-sm text-slate-600 mb-1">Username/Sosmed</label><input type="text" name="Username" class="w-full px-3 py-1.5 border rounded-md"></div>
                    <div><label class="block text-sm text-slate-600 mb-1">Nama Lengkap</label><input type="text" name="Nama" class="w-full px-3 py-1.5 border rounded-md"></div>
                    <div><label class="block text-sm text-slate-600 mb-1">No. Telp / WA</label><input type="text" name="Telp" class="w-full px-3 py-1.5 border rounded-md"></div>
                    <div><label class="block text-sm text-slate-600 mb-1">Provinsi</label><input type="text" name="Provinsi" class="w-full px-3 py-1.5 border rounded-md"></div>
                    <div><label class="block text-sm text-slate-600 mb-1">Kabupaten/Kota</label><input type="text" name="Kabupaten" class="w-full px-3 py-1.5 border rounded-md"></div>
                    <div><label class="block text-sm text-slate-600 mb-1">Kecamatan</label><input type="text" name="Kecamatan" class="w-full px-3 py-1.5 border rounded-md"></div>
                    <div><label class="block text-sm text-slate-600 mb-1">Kodepos</label><input type="text" name="Kodepos" class="w-full px-3 py-1.5 border rounded-md"></div>
                    <div><label class="block text-sm text-slate-600 mb-1">Keterangan / Catatan</label><textarea name="Keterangan" rows="2" class="w-full px-3 py-1.5 border rounded-md"></textarea></div>
                </div>

            </div>

            <div class="flex gap-4 justify-end pt-4 border-t border-slate-200">
                <a href="transaksi.php" class="px-6 py-2 text-slate-600 border border-slate-300 rounded-lg hover:bg-slate-50">Batal</a>
                <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium shadow-sm">Simpan Data</button>
            </div>
        </form>
    </div>
</body>
</html>