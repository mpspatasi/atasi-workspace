<?php
require 'koneksi.php';
$pesan = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_lengkap = htmlspecialchars($_POST['nama_lengkap']);
    $jabatan      = htmlspecialchars($_POST['jabatan']); // Input baru
    $username     = htmlspecialchars($_POST['username']);
    $password     = password_hash($_POST['password'], PASSWORD_DEFAULT); 
    $modul        = $_POST['modul'];

    try {
        // Pastikan kolom 'jabatan' sudah ada di tabel tb_users kamu
        $stmt = $pdo->prepare("INSERT INTO tb_users (nama_lengkap, jabatan, username, password, modul) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nama_lengkap, $jabatan, $username, $password, $modul]);
        $pesan = "<div class='bg-green-100 text-green-700 p-3 rounded-lg mb-4 text-sm'>Akun <b>$nama_lengkap</b> sebagai <b>$jabatan</b> berhasil didaftarkan!</div>";
    } catch (PDOException $e) {
        // Cek apakah kolom jabatan belum ada di database
        if (strpos($e->getMessage(), 'Unknown column \'jabatan\'') !== false) {
            $pesan = "<div class='bg-red-100 text-red-700 p-3 rounded-lg mb-4 text-sm'>Gagal: Kolom 'jabatan' belum ada di database. Jalankan SQL ALTER TABLE dulu.</div>";
        } else {
            $pesan = "<div class='bg-red-100 text-red-700 p-3 rounded-lg mb-4 text-sm'>Gagal: Username sudah ada atau database bermasalah.</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Karyawan - ATASI</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-900 text-slate-200 min-h-screen flex items-center justify-center p-6 font-sans">
    <div class="w-full max-w-md bg-slate-800 border border-slate-700 p-8 rounded-2xl shadow-xl">
        <h2 class="text-2xl font-bold mb-2 text-white text-center">Registrasi Karyawan</h2>
        <p class="text-slate-400 text-sm text-center mb-6">Daftarkan akun baru berdasarkan modul kerja.</p>

        <?= $pesan ?>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Nama Lengkap</label>
                <input type="text" name="nama_lengkap" class="w-full bg-slate-700 border border-slate-600 rounded-lg px-4 py-2.5 focus:outline-none focus:border-blue-500 text-white" placeholder="Contoh: Budi Santoso" required>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Jabatan / Role Spesifik</label>
                <input type="text" name="jabatan" class="w-full bg-slate-700 border border-slate-600 rounded-lg px-4 py-2.5 focus:outline-none focus:border-blue-500 text-white" placeholder="Contoh: Manager Marketing / Admin Gudang" required>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Username Login</label>
                <input type="text" name="username" class="w-full bg-slate-700 border border-slate-600 rounded-lg px-4 py-2.5 focus:outline-none focus:border-blue-500 text-white" placeholder="Contoh: budi_atasi" required>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Password</label>
                <input type="password" name="password" class="w-full bg-slate-700 border border-slate-600 rounded-lg px-4 py-2.5 focus:outline-none focus:border-blue-500 text-white" placeholder="******" required>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Akses Modul Utama</label>
                <select name="modul" class="w-full bg-slate-700 border border-slate-600 rounded-lg px-4 py-2.5 focus:outline-none focus:border-blue-500 text-white">
                    <option value="Marketing">Marketing</option>
                    <option value="Inventory">Inventory</option>
                    <option value="Employee">Employee</option>
                    <option value="Superadmin">Super Admin</option>
                </select>
            </div>

            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-lg mt-4 shadow-lg shadow-blue-900/20 transition-all active:scale-[0.98]">
                Daftarkan Akun
            </button>
            
            <div class="text-center mt-4">
                <a href="index.php" class="text-xs text-slate-500 hover:text-slate-300 transition-colors">&larr; Kembali ke Portal Utama</a>
            </div>
        </form>
    </div>

    <script>
        if ( window.history.replaceState ) {
            window.history.replaceState( null, null, window.location.href );
        }
    </script>
</body>
</html>