<?php
session_start();

// Anti-Back & Anti-Cache (PHP)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0"); 
header("Cache-Control: post-check=0, pre-check=0", false); 
header("Pragma: no-cache"); 
header("Expires: 0"); 

// Kalau udah login, tendang balik ke dashboard sesuai modulnya pakai JS
if (isset($_SESSION['username'])) {
    $modul_aktif = isset($_SESSION['modul_akses']) ? $_SESSION['modul_akses'] : 'Aplikasi';
    
    // Tentukan arah berdasarkan sesi modul aktif
    $redirect_terloging = 'dashboard.php'; 
    if (strtolower($modul_aktif) === 'inventory') {
        $redirect_terloging = 'inventory_dashboard.php'; // <--- SUDAH DISESUAIKAN
    }
    
    echo "<script>window.location.replace('$redirect_terloging');</script>";
    exit;
}

// Tangkap nama modul dari URL (Contoh: login.php?app=Inventory)
$app_target = isset($_GET['app']) ? trim($_GET['app']) : 'Aplikasi';

// LOGIKA OTOMATIS: Ubah Karyawan menjadi Employee
if (strtolower($app_target) === 'karyawan') {
    $app_target = 'Employee';
}

// =========================================================================
// LOGIKA REDIRECT DINAMIS: Tentukan URL tujuan setelah login berhasil
// =========================================================================
$redirect_url = 'dashboard.php'; // Default (Modul Marketing)
if (strtolower($app_target) === 'inventory') {
    $redirect_url = 'inventory_dashboard.php'; // <--- SUDAH DISESUAIKAN
}

$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require 'koneksi.php';

    $username_input = trim($_POST['username']);
    $password_input = trim($_POST['password']);

    try {
        $stmt = $pdo->prepare("SELECT * FROM tb_users WHERE username = ? LIMIT 1");
        $stmt->execute([$username_input]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // 1. Cek apakah usernya ada dan passwordnya cocok
        if ($user && password_verify($password_input, $user['password'])) {
            
            // 2. Cek apakah user ini berhak masuk ke modul yang dituju
            $user_modul = $user['modul'];
            if ($user_modul === $app_target || $user_modul === 'Superadmin' || ($app_target === 'Employee' && strtolower($user_modul) === 'karyawan')) {
                
                // ==========================================
                // FIX BUG: SET SESI GANDA UNTUK SEMUA MODUL
                // ==========================================
                
                // Sesi asli (Untuk Modul Marketing & Employee lama)
                $_SESSION['user_id']      = $user['id']; 
                $_SESSION['username']     = $user['username'];
                $_SESSION['nama_lengkap'] = $user['nama_lengkap']; 
                
                // Sesi duplikat (Untuk Modul Inventory baru)
                $_SESSION['id_user']      = $user['id']; 
                $_SESSION['nama']         = $user['nama_lengkap']; 
                
                // Sesi Global
                $_SESSION['modul_akses']  = ($user_modul === 'Superadmin') ? 'Superadmin' : $app_target;
                $_SESSION['inisial']      = strtoupper(substr($user['nama_lengkap'], 0, 1));

                // Redirect pakai JS secara DINAMIS
                echo "<script>window.location.replace('$redirect_url');</script>";
                exit;
            } else {
                $error_msg = "Akses ditolak! Akun ini bukan untuk modul $app_target.";
            }
        } else {
            $error_msg = 'Username atau Password salah!';
        }
    } catch (PDOException $e) {
        $error_msg = 'Terjadi kesalahan koneksi database.';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login <?= htmlspecialchars($app_target) ?> - Modul</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .split-bg { background-image: radial-gradient(circle at bottom left, rgba(255,255,255,0.15) 0%, transparent 40%), radial-gradient(circle at top right, rgba(255,255,255,0.1) 0%, transparent 30%); }
    </style>
    <script>
        // Anti bfcache (Back-Forward Cache) murni dari browser
        window.addEventListener('pageshow', function(event) {
            if (event.persisted || (window.performance && window.performance.navigation.type === 2)) {
                window.location.reload(); 
            }
        });
    </script>
</head>
<body class="bg-slate-50 font-sans text-slate-800 h-screen overflow-hidden flex">

    <div class="hidden md:flex w-1/2 bg-[#3E54D3] split-bg flex-col justify-center px-16 relative">
        <div class="absolute bottom-0 left-0 w-64 h-64 border border-white/20 rounded-full -translate-x-1/4 translate-y-1/4"></div>
        <div class="absolute bottom-0 left-0 w-96 h-96 border border-white/10 rounded-full -translate-x-1/3 translate-y-1/3"></div>
        
        <div class="relative z-10">
            <h1 class="text-4xl font-bold text-white mb-4 uppercase"><?= htmlspecialchars($app_target) ?></h1>
            <p class="text-blue-100 text-lg font-medium leading-relaxed max-w-sm">
                Login ke modul <?= htmlspecialchars($app_target) ?> sistem ATASI.
            </p>
            <a href="index.php" class="inline-block mt-8 px-6 py-2 border-2 border-white/30 text-white rounded-full text-sm font-semibold hover:bg-white hover:text-[#3E54D3] transition-colors">
                &larr; Kembali ke Portal
            </a>
        </div>
    </div>

    <div class="w-full md:w-1/2 flex flex-col justify-center items-center p-8 bg-white relative">
        
        <?php if ($error_msg): ?>
            <div class="absolute top-10 w-full max-w-sm bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-lg flex items-center gap-3 shadow-sm">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span class="text-sm font-semibold"><?= $error_msg ?></span>
            </div>
        <?php endif; ?>

        <div class="w-full max-w-[360px]">
            <h2 class="text-2xl font-extrabold text-slate-800 mb-1">Halo Guys!</h2>
            <p class="text-slate-500 font-medium mb-8">Silakan masuk pakai akunmu</p>

            <form action="" method="POST" class="space-y-5">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                    </div>
                    <input type="text" name="username" placeholder="Username" class="w-full pl-11 pr-4 py-3.5 border border-slate-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-[#3E54D3]/50 focus:border-[#3E54D3] text-sm font-medium transition-all bg-slate-50 focus:bg-white" required>
                </div>

                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    </div>
                    <input type="password" name="password" placeholder="Password" class="w-full pl-11 pr-4 py-3.5 border border-slate-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-[#3E54D3]/50 focus:border-[#3E54D3] text-sm font-medium transition-all bg-slate-50 focus:bg-white" required>
                </div>

                <button type="submit" class="w-full py-3.5 bg-[#3E54D3] hover:bg-blue-700 text-white font-bold rounded-2xl shadow-[0_8px_20px_-6px_rgba(62,84,211,0.5)] transition-all transform hover:-translate-y-0.5 active:translate-y-0">
                    Login
                </button>
            </form>
        </div>
    </div>

</body>
</html>