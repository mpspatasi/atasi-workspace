<?php
session_start();
require 'koneksi.php'; 

// ==============================================================================
// 1. CEK SESSION & AMBIL DATA USER
// ==============================================================================
if (!isset($_SESSION['username'])) {
    echo "<script>window.location.replace('login.php');</script>";
    exit();
}

$username_session = $_SESSION['username'];
$nama_user = $_SESSION['nama_lengkap'] ?? $username_session;
$user_avatar = ''; 

try {
    $stmtUser = $pdo->prepare("SELECT nama_lengkap, username, avatar FROM tb_users WHERE username = ? LIMIT 1");
    $stmtUser->execute([$username_session]);
    $userData = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if ($userData) {
        $nama_user = $userData['nama_lengkap']; 
        if (!empty($userData['avatar'])) {
            $user_avatar = $userData['avatar'];
        } else {
            $user_avatar = "https://api.dicebear.com/7.x/notionists/svg?seed=" . urlencode($userData['username']) . "&backgroundColor=ffe4e6";
        }
    }
} catch (PDOException $e) {}

$inisial_user = strtoupper(substr($nama_user, 0, 1));

// ==============================================================================
// 2. PROSES SIMPAN, EDIT & HAPUS DATA
// ==============================================================================
$pesan = '';
$status = '';

// Proses Hapus Data
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_id'])) {
    try {
        $stmtDel = $pdo->prepare("DELETE FROM jurnal_transaksi_atasi.db_ads_analytics WHERE id = ?");
        $stmtDel->execute([$_POST['delete_id']]);
        $status = 'success';
        $pesan = "Data berhasil dihapus dari sistem!";
    } catch (PDOException $e) {
        $status = 'error';
        $pesan = "Gagal menghapus data: " . $e->getMessage();
    }
}

// Proses Edit Data (Lewat Pop-Up Modal)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_ads'])) {
    $id = $_POST['edit_id'];
    $tanggal = $_POST['tanggal'];
    $platform = $_POST['platform'];
    $ad_spend = str_replace(['Rp', '.', ' '], '', $_POST['ad_spend']); 
    $revenue = str_replace(['Rp', '.', ' '], '', $_POST['revenue']);
    $impressions = $_POST['impressions'];
    $clicks = $_POST['clicks'];
    $conversions = $_POST['conversions'];

    try {
        $sql = "UPDATE jurnal_transaksi_atasi.db_ads_analytics 
                SET tanggal=?, platform=?, ad_spend=?, revenue=?, impressions=?, clicks=?, conversions=? 
                WHERE id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$tanggal, $platform, $ad_spend, $revenue, $impressions, $clicks, $conversions, $id]);
        
        $status = 'success';
        $pesan = "Data performa iklan berhasil diperbarui!";
    } catch (PDOException $e) {
        $status = 'error';
        // Handle error kalau tanggal & platform bentrok dengan data lain yang udah ada
        if($e->getCode() == 23000) {
            $pesan = "Gagal update: Data untuk platform dan tanggal tersebut sudah ada di baris lain.";
        } else {
            $pesan = "Gagal mengupdate data: " . $e->getMessage();
        }
    }
}

// Proses Simpan Data Baru (Dari Form Kiri)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_ads'])) {
    $tanggal = $_POST['tanggal'];
    $platform = $_POST['platform'];
    $ad_spend = str_replace(['Rp', '.', ' '], '', $_POST['ad_spend']); 
    $revenue = str_replace(['Rp', '.', ' '], '', $_POST['revenue']);
    $impressions = $_POST['impressions'];
    $clicks = $_POST['clicks'];
    $conversions = $_POST['conversions'];

    try {
        $sql = "INSERT INTO jurnal_transaksi_atasi.db_ads_analytics 
                (tanggal, platform, ad_spend, revenue, impressions, clicks, conversions) 
                VALUES (?, ?, ?, ?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE 
                ad_spend = VALUES(ad_spend), revenue = VALUES(revenue), impressions = VALUES(impressions), clicks = VALUES(clicks), conversions = VALUES(conversions)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$tanggal, $platform, $ad_spend, $revenue, $impressions, $clicks, $conversions]);
        
        $status = 'success';
        $pesan = "Data $platform tanggal $tanggal berhasil disimpan/diperbarui!";
    } catch (PDOException $e) {
        $status = 'error';
        $pesan = "Gagal menyimpan data: " . $e->getMessage();
    }
}

// ==============================================================================
// 3. AMBIL SELURUH DATA KE PHP ARRAY (Untuk diolah JS)
// ==============================================================================
$historyData = [];
try {
    $stmtData = $pdo->query("SELECT * FROM jurnal_transaksi_atasi.db_ads_analytics ORDER BY tanggal DESC");
    $historyData = $stmtData->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Entry</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 8px; height: 10px; }
        ::-webkit-scrollbar-track { background: #f8fafc; border-radius: 4px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        
        .input-rupiah { text-align: right; font-family: monospace; font-size: 1.1rem; }
        th { user-select: none; }
        
        /* Utility untuk drag scroll tabel */
        .cursor-grab { cursor: grab; }
        .cursor-grabbing { cursor: grabbing !important; }
    </style>
</head>
<body class="bg-slate-50 font-sans text-slate-800 h-screen flex overflow-hidden" onclick="closeAllPopups(event)">

    <aside id="mainSidebar" class="w-64 bg-[#E11D48] text-white flex flex-col shrink-0 overflow-hidden shadow-xl z-40 transition-all duration-300 relative">
        <div class="h-16 flex items-center px-6 border-b border-white/10 shrink-0 gap-3">
            <div class="w-8 h-8 bg-white rounded-md flex items-center justify-center text-[#E11D48] shrink-0 shadow-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"></path></svg>
            </div>
            <span class="text-lg font-bold tracking-wider whitespace-nowrap uppercase">ADS HUB</span>
        </div>
        
        <nav class="flex-1 py-6 px-3 space-y-2 overflow-y-auto">
            <a href="ads_dashboard.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-rose-100 hover:bg-white/10 hover:text-white transition-colors whitespace-nowrap group">
                <svg class="w-5 h-5 shrink-0 opacity-70 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                <span class="font-medium tracking-wide text-sm">Master Dashboard</span>
            </a>
            <a href="ads_platforms.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-rose-100 hover:bg-white/10 hover:text-white transition-colors whitespace-nowrap group">
                <svg class="w-5 h-5 shrink-0 opacity-70 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                <span class="font-medium tracking-wide text-sm">Platform Analytics</span>
            </a>
            <a href="ads_strategy.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-rose-100 hover:bg-white/10 hover:text-white transition-colors whitespace-nowrap group">
                <svg class="w-5 h-5 shrink-0 opacity-70 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                <span class="font-medium tracking-wide text-sm">Health & Strategy</span>
            </a>
            <a href="ads_data_entry.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-white/20 text-white shadow-inner transition-colors whitespace-nowrap group">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                <span class="font-bold tracking-wide text-sm">Data Entry Hub</span>
            </a>
        </nav>
    </aside>

    <main class="flex-1 flex flex-col h-screen min-w-0 overflow-hidden relative bg-slate-50">
        
        <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6 shrink-0 shadow-sm z-30">
            <div class="flex items-center gap-4">
                <button onclick="document.getElementById('mainSidebar').classList.toggle('w-0'); document.getElementById('mainSidebar').classList.toggle('w-64');" class="p-2 bg-slate-50 rounded-md hover:bg-slate-100 text-slate-600 transition border border-slate-200 shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                <div>
                    <h1 class="text-xl font-bold text-slate-800">Daily Data Entry</h1>
                    <div class="flex items-center gap-2 text-xs font-medium mt-0.5">
                        <span class="text-[#E11D48]">Advertisement</span><span class="text-slate-400">/</span><span class="text-slate-500">Sync Hub</span>
                    </div>
                </div>
            </div>
            
            <div class="flex items-center gap-4">
                <div class="relative" id="profileDropdownContainer">
                    <button onclick="toggleProfileMenu(event)" class="flex items-center gap-3 focus:outline-none group">
                        <span class="text-sm font-semibold text-slate-700 group-hover:text-[#E11D48] transition-colors">
                            <?= htmlspecialchars($nama_user) ?>
                        </span>
                        
                        <?php if (!empty($user_avatar)): ?>
                            <img src="<?= htmlspecialchars($user_avatar) ?>" alt="Profil" class="w-9 h-9 rounded-full object-cover border-2 border-white shadow-sm group-hover:ring-2 group-hover:ring-[#E11D48]/50 transition-all cursor-pointer bg-slate-100">
                        <?php else: ?>
                            <div class="w-9 h-9 rounded-full bg-rose-100 flex items-center justify-center text-rose-700 font-bold border border-rose-200 group-hover:ring-2 group-hover:ring-[#E11D48]/50 transition-all cursor-pointer">
                                <?= htmlspecialchars($inisial_user) ?>
                            </div>
                        <?php endif; ?>
                    </button>
                    
                    <div id="profileMenu" class="hidden absolute right-0 mt-3 w-48 bg-white rounded-lg shadow-xl border border-slate-200 z-50 py-1.5 overflow-hidden transform transition-all" onclick="event.stopPropagation()">
                        <div class="px-4 py-2 border-b border-slate-100 bg-slate-50">
                            <p class="text-[10px] uppercase font-bold tracking-wider text-slate-400 mb-0.5">Signed in as</p>
                            <p class="text-sm font-semibold text-slate-800 truncate">
                                <?= isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : '@cs_atasi' ?>
                            </p>
                        </div>
                        <a href="edit_profil.php" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-slate-600 hover:bg-rose-50 hover:text-[#E11D48] font-medium transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            Edit Profil
                        </a>
                        <div class="border-t border-slate-100 my-0.5"></div>
                        <a href="logout.php" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 font-medium transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                            Logout
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <div class="p-6 flex-1 min-h-0 flex flex-col md:flex-row gap-6 overflow-y-auto">
            
            <div class="w-full md:w-[320px] lg:w-[350px] shrink-0">
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden sticky top-0">
                    <div class="bg-slate-50 border-b border-slate-200 px-5 py-4">
                        <h2 class="font-bold text-slate-800 text-sm flex items-center gap-2">
                            <svg class="w-4 h-4 text-[#E11D48]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                            Input Performa Iklan
                        </h2>
                        <p class="text-[11px] text-slate-500 mt-1 leading-tight">Sistem otomatis menimpa data jika inputan memiliki tanggal & platform yang sama.</p>
                    </div>
                    
                    <form method="POST" action="" class="p-5 space-y-4" id="formAds">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Tanggal <span class="text-red-500">*</span></label>
                            <input type="date" name="tanggal" id="inpTanggal" value="<?= date('Y-m-d') ?>" required class="w-full border border-slate-300 px-3 py-2 rounded-lg text-sm focus:outline-none focus:border-[#E11D48] focus:ring-1 focus:ring-rose-200">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Platform <span class="text-red-500">*</span></label>
                            <select name="platform" id="inpPlatform" required class="w-full border border-slate-300 px-3 py-2 rounded-lg text-sm bg-white focus:outline-none focus:border-[#E11D48] font-semibold text-slate-700">
                                <option value="Meta">Meta Ads</option>
                                <option value="Shopee">Shopee Ads</option>
                                <option value="TikTok">TikTok Ads</option>
                            </select>
                        </div>
                        
                        <hr class="border-slate-100">
                        
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Ad Spend (Rp) <span class="text-red-500">*</span></label>
                            <input type="text" name="ad_spend" id="inpSpend" placeholder="0" required class="input-rupiah w-full border border-slate-300 px-3 py-2 rounded-lg text-rose-600 focus:outline-none focus:border-[#E11D48]">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Revenue (Rp) <span class="text-red-500">*</span></label>
                            <input type="text" name="revenue" id="inpRevenue" placeholder="0" required class="input-rupiah w-full border border-slate-300 px-3 py-2 rounded-lg text-emerald-600 focus:outline-none focus:border-[#E11D48]">
                        </div>

                        <hr class="border-slate-100">

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Impresi <span class="text-red-500">*</span></label>
                                <input type="number" name="impressions" id="inpImp" placeholder="0" min="0" required class="w-full border border-slate-300 px-3 py-2 rounded-lg text-sm focus:outline-none focus:border-[#E11D48]">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Klik <span class="text-red-500">*</span></label>
                                <input type="number" name="clicks" id="inpClick" placeholder="0" min="0" required class="w-full border border-slate-300 px-3 py-2 rounded-lg text-sm focus:outline-none focus:border-[#E11D48]">
                            </div>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Conversions (Order) <span class="text-red-500">*</span></label>
                            <input type="number" name="conversions" id="inpConv" placeholder="0" min="0" required class="w-full border border-slate-300 px-3 py-2 rounded-lg text-sm focus:outline-none focus:border-[#E11D48]">
                        </div>

                        <button type="submit" name="submit_ads" class="w-full bg-[#E11D48] hover:bg-rose-700 text-white font-bold py-2.5 rounded-lg text-sm transition shadow-md mt-2">
                            Simpan Data
                        </button>
                    </form>
                </div>
            </div>

            <div class="flex-1 flex flex-col bg-white rounded-xl border border-slate-200 shadow-sm min-w-0 h-[calc(113vh-8rem)]">
                
                <div class="p-4 border-b border-slate-200 bg-slate-50/50 rounded-t-xl shrink-0">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="text-sm font-bold text-slate-700">Database</div>
                        <div class="flex items-center gap-2 w-full md:w-auto">
                            <div class="relative flex-1 md:w-64">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                </div>
                                <input type="text" id="globalSearch" placeholder="Cari data (Ctrl+F)..." class="block w-full pl-10 pr-3 py-1.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:border-[#E11D48] focus:ring-1 focus:ring-rose-200 transition">
                            </div>
                            <button type="button" onclick="resetFilters()" class="border border-slate-300 px-3 py-1.5 rounded-lg text-sm bg-white hover:bg-slate-100 text-slate-700 font-medium transition hidden md:block">Reset Filter</button>
                            <button type="button" onclick="bukaModalEkspor()" class="bg-[#E11D48] hover:bg-rose-700 text-white px-3 py-1.5 rounded-lg text-sm font-medium transition flex items-center gap-2 shadow-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg> Ekspor Data
                            </button>
                        </div>
                    </div>
                </div>

                <div id="tableScrollArea" class="flex-1 overflow-auto cursor-grab active:cursor-grabbing select-none relative">
                    <table class="w-full text-left border-collapse whitespace-nowrap min-w-[1100px]">
                        <thead class="sticky top-0 z-20 bg-slate-100 shadow-sm text-slate-600 text-[10px] uppercase tracking-wider font-bold">
                            <tr>
                                <th class="border-b border-r border-slate-200 px-3 py-3 text-center w-24 align-middle bg-slate-100 sticky left-0 z-30 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)]">Aksi</th>
                                <th class="border-b border-r border-slate-200 px-3 py-3 text-center w-12 align-middle">No</th>
                                
                                <th class="border-b border-r border-slate-200 px-4 py-3 cursor-pointer group hover:bg-slate-200 transition-colors" onclick="toggleFilterPopup(event, 'tanggal')">
                                    <div class="flex items-center justify-between gap-2">
                                        <span>Tanggal</span>
                                        <div class="flex items-center gap-1">
                                            <span id="sort-icon-tanggal" class="text-[#E11D48] font-bold text-[10px]"></span>
                                            <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-[#E11D48]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                        </div>
                                    </div>
                                </th>
                                <th class="border-b border-r border-slate-200 px-4 py-3 cursor-pointer group hover:bg-slate-200 transition-colors" onclick="toggleFilterPopup(event, 'platform')">
                                    <div class="flex items-center justify-between gap-2">
                                        <span>Platform</span>
                                        <div class="flex items-center gap-1">
                                            <span id="sort-icon-platform" class="text-[#E11D48] font-bold text-[10px]"></span>
                                            <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-[#E11D48]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                        </div>
                                    </div>
                                </th>
                                <th class="border-b border-r border-slate-200 px-4 py-3 cursor-pointer group hover:bg-slate-200 transition-colors" onclick="toggleFilterPopup(event, 'ad_spend')">
                                    <div class="flex items-center justify-between gap-2">
                                        <span>Ad Spend (Rp)</span>
                                        <div class="flex items-center gap-1">
                                            <span id="sort-icon-ad_spend" class="text-[#E11D48] font-bold text-[10px]"></span>
                                            <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-[#E11D48]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                        </div>
                                    </div>
                                </th>
                                <th class="border-b border-r border-slate-200 px-4 py-3 cursor-pointer group hover:bg-slate-200 transition-colors" onclick="toggleFilterPopup(event, 'revenue')">
                                    <div class="flex items-center justify-between gap-2">
                                        <span>Revenue (Rp)</span>
                                        <div class="flex items-center gap-1">
                                            <span id="sort-icon-revenue" class="text-[#E11D48] font-bold text-[10px]"></span>
                                            <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-[#E11D48]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                        </div>
                                    </div>
                                </th>
                                <th class="border-b border-r border-slate-200 px-4 py-3 cursor-pointer group hover:bg-slate-200 transition-colors text-center" onclick="toggleFilterPopup(event, 'roas')">
                                    <div class="flex items-center justify-center gap-2">
                                        <span>ROAS</span>
                                        <div class="flex items-center gap-1">
                                            <span id="sort-icon-roas" class="text-[#E11D48] font-bold text-[10px]"></span>
                                            <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-[#E11D48]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                        </div>
                                    </div>
                                </th>
                                <th class="border-b border-r border-slate-200 px-4 py-3 cursor-pointer group hover:bg-slate-200 transition-colors" onclick="toggleFilterPopup(event, 'impressions')">
                                    <div class="flex items-center justify-between gap-2">
                                        <span>Impresi</span>
                                        <div class="flex items-center gap-1">
                                            <span id="sort-icon-impressions" class="text-[#E11D48] font-bold text-[10px]"></span>
                                            <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-[#E11D48]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                        </div>
                                    </div>
                                </th>
                                <th class="border-b border-r border-slate-200 px-4 py-3 cursor-pointer group hover:bg-slate-200 transition-colors" onclick="toggleFilterPopup(event, 'clicks')">
                                    <div class="flex items-center justify-between gap-2">
                                        <span>Klik</span>
                                        <div class="flex items-center gap-1">
                                            <span id="sort-icon-clicks" class="text-[#E11D48] font-bold text-[10px]"></span>
                                            <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-[#E11D48]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                        </div>
                                    </div>
                                </th>
                                <th class="border-b border-slate-200 px-4 py-3 cursor-pointer group hover:bg-slate-200 transition-colors" onclick="toggleFilterPopup(event, 'conversions')">
                                    <div class="flex items-center justify-between gap-2">
                                        <span>Conversions</span>
                                        <div class="flex items-center gap-1">
                                            <span id="sort-icon-conversions" class="text-[#E11D48] font-bold text-[10px]"></span>
                                            <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-[#E11D48]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                        </div>
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody id="tableBody" class="divide-y divide-slate-100 text-sm bg-white relative">
                            </tbody>
                    </table>
                </div>

                <div class="p-3.5 border-t border-slate-200 bg-white rounded-b-xl flex flex-col md:flex-row items-center justify-between gap-4 shrink-0">
                    <div class="flex items-center gap-3">
                        <span class="text-sm text-slate-500 font-medium">Tampilkan:</span>
                        <select id="limitSelect" onchange="changeLimit()" class="border border-slate-300 rounded-lg px-2 py-1.5 text-sm outline-none focus:ring-2 focus:ring-[#E11D48] cursor-pointer font-medium text-slate-700 shadow-sm transition">
                            <option value="50">50 Baris</option>
                            <option value="100" selected>100 Baris</option>
                            <option value="250">250 Baris</option>
                        </select>
                        <span class="text-sm text-slate-400 mx-1">|</span>
                        <span id="dataInfoText" class="text-sm text-slate-600 font-medium">Memuat data...</span>
                    </div>
                    
                    <div id="paginationContainer" class="flex gap-1.5 items-center">
                        </div>
                </div>

            </div>
        </div>
    </main>

    <div id="modal-edit" class="fixed inset-0 bg-slate-900/60 hidden items-center justify-center z-[100] backdrop-blur-sm transition-opacity">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden flex flex-col transform scale-95 transition-transform duration-300" id="modal-edit-content">
            <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center bg-slate-50">
                <div>
                    <h2 class="text-lg font-bold text-slate-800">Edit Data Ads</h2>
                    <p class="text-xs text-slate-500 mt-1">Perbarui metrik performa iklan untuk baris ini.</p>
                </div>
                <button type="button" onclick="tutupModalEdit()" class="text-slate-400 hover:text-[#E11D48] transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <form method="POST" action="" class="p-6 space-y-4">
                <input type="hidden" name="edit_id" id="editId">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Tanggal</label>
                        <input type="date" name="tanggal" id="editTanggal" required class="w-full border border-slate-300 px-3 py-2 rounded-lg text-sm focus:outline-none focus:border-[#E11D48] focus:ring-1 focus:ring-rose-200">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Platform</label>
                        <select name="platform" id="editPlatform" required class="w-full border border-slate-300 px-3 py-2 rounded-lg text-sm bg-white focus:outline-none focus:border-[#E11D48] focus:ring-1 focus:ring-rose-200 font-semibold text-slate-700">
                            <option value="Meta">Meta Ads (FB/IG)</option>
                            <option value="Shopee">Shopee Ads</option>
                            <option value="TikTok">TikTok Ads</option>
                        </select>
                    </div>
                </div>
                
                <hr class="border-slate-100 my-2">
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Ad Spend (Rp)</label>
                        <input type="text" name="ad_spend" id="editSpend" required class="input-rupiah w-full border border-slate-300 px-3 py-2 rounded-lg text-rose-600 focus:outline-none focus:border-[#E11D48] focus:ring-1 focus:ring-rose-200">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Revenue (Rp)</label>
                        <input type="text" name="revenue" id="editRevenue" required class="input-rupiah w-full border border-slate-300 px-3 py-2 rounded-lg text-emerald-600 focus:outline-none focus:border-[#E11D48] focus:ring-1 focus:ring-rose-200">
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Impresi</label>
                        <input type="number" name="impressions" id="editImp" min="0" required class="w-full border border-slate-300 px-3 py-2 rounded-lg text-sm focus:outline-none focus:border-[#E11D48] focus:ring-1 focus:ring-rose-200">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Klik</label>
                        <input type="number" name="clicks" id="editClick" min="0" required class="w-full border border-slate-300 px-3 py-2 rounded-lg text-sm focus:outline-none focus:border-[#E11D48] focus:ring-1 focus:ring-rose-200">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1">Order</label>
                        <input type="number" name="conversions" id="editConv" min="0" required class="w-full border border-slate-300 px-3 py-2 rounded-lg text-sm focus:outline-none focus:border-[#E11D48] focus:ring-1 focus:ring-rose-200">
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-200 flex justify-end gap-3 mt-6">
                    <button type="button" onclick="tutupModalEdit()" class="px-4 py-2 bg-white border border-slate-300 text-slate-700 rounded-lg text-sm font-semibold hover:bg-slate-50 transition shadow-sm">Batal</button>
                    <button type="submit" name="update_ads" class="px-4 py-2 bg-[#E11D48] text-white rounded-lg text-sm font-semibold hover:bg-rose-700 transition shadow-sm">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <div id="filterPopup" class="hidden fixed bg-white border border-slate-200 shadow-2xl rounded-xl w-60 z-50 text-sm flex flex-col overflow-hidden" onclick="event.stopPropagation()">
        <button onclick="applySort('ASC')" class="flex items-center gap-3 px-4 py-3 hover:bg-rose-50 text-slate-700 text-left w-full transition border-b border-slate-50 group">
            <svg class="w-4 h-4 text-slate-400 group-hover:text-[#E11D48]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"></path></svg> 
            Urutkan A ke Z (Kecil ke Besar)
        </button>
        <button onclick="applySort('DESC')" class="flex items-center gap-3 px-4 py-3 hover:bg-rose-50 text-slate-700 text-left w-full transition group">
            <svg class="w-4 h-4 text-slate-400 group-hover:text-[#E11D48]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h9m5-4v12m0 0l-4-4m4 4l4-4"></path></svg> 
            Urutkan Z ke A (Besar ke Kecil)
        </button>
        
        <div class="p-3 border-t border-slate-100 bg-slate-50/50">
            <div class="relative">
                <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                <input type="text" id="popupSearchInput" onkeyup="searchFilterCheckboxes()" placeholder="Cari filter..." class="w-full border border-slate-300 rounded-md pl-8 pr-3 py-1.5 focus:outline-none focus:border-[#E11D48] focus:ring-1 focus:ring-rose-200 text-xs">
            </div>
        </div>
        <div class="px-4 py-2 text-xs font-bold text-[#E11D48] flex gap-2">
            <button onclick="toggleAllCheckboxes(true)" class="hover:underline">Pilih semua</button> 
            <span class="text-slate-300">|</span> 
            <button onclick="toggleAllCheckboxes(false)" class="hover:underline">Kosongkan</button>
        </div>
        
        <div id="filterCheckboxContainer" class="max-h-40 overflow-y-auto px-4 pb-3 space-y-1.5 text-slate-600 border-b border-slate-100">
            </div>
        
        <div class="p-3 flex justify-end gap-2 bg-slate-50">
            <button onclick="closeAllPopups()" class="px-4 py-1.5 bg-white border border-slate-300 rounded-md text-xs font-bold text-slate-600 hover:bg-slate-100 shadow-sm">Batal</button>
            <button onclick="applyColumnFilter()" class="px-4 py-1.5 bg-[#E11D48] text-white rounded-md text-xs font-bold hover:bg-rose-700 shadow-sm">Simpan</button>
        </div>
    </div>

    <div id="modal-export" class="fixed inset-0 bg-slate-900/60 hidden items-center justify-center z-[100] backdrop-blur-sm transition-opacity">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl overflow-hidden flex flex-col">
            <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center bg-slate-50">
                <div>
                    <h2 class="text-lg font-bold text-slate-800">Ekspor Ads Analytics</h2>
                    <p class="text-xs text-slate-500 mt-1">Pilih kolom yang ingin didownload ke CSV.</p>
                </div>
                <button onclick="tutupModalEkspor()" class="text-slate-400 hover:text-rose-600 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <div class="p-6 overflow-auto max-h-[70vh] space-y-6">
                <div>
                    <h3 class="text-sm font-bold text-slate-800 mb-3 border-b pb-2">1. Pilih Baris Data</h3>
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 cursor-pointer bg-slate-50 p-3 rounded-lg border border-slate-200 w-full hover:bg-rose-50 hover:border-rose-200 transition">
                            <input type="radio" name="export_row_scope" value="filtered" checked class="w-4 h-4 text-[#E11D48] focus:ring-[#E11D48]">
                            <span class="text-sm text-slate-700 font-medium">Sesuai Filter & Pencarian Saat Ini</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer bg-slate-50 p-3 rounded-lg border border-slate-200 w-full hover:bg-rose-50 hover:border-rose-200 transition">
                            <input type="radio" name="export_row_scope" value="all" class="w-4 h-4 text-[#E11D48] focus:ring-[#E11D48]">
                            <span class="text-sm text-slate-700 font-medium">Seluruh Data (Abaikan Filter)</span>
                        </label>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between items-end mb-3 border-b pb-2">
                        <h3 class="text-sm font-bold text-slate-800">2. Pilih Kolom</h3>
                        <div class="text-xs">
                            <button onclick="toggleAllExportCols(true)" class="text-[#E11D48] hover:underline font-semibold">Pilih Semua</button>
                            <span class="mx-1 text-slate-300">|</span>
                            <button onclick="toggleAllExportCols(false)" class="text-slate-500 hover:underline font-semibold">Kosongkan</button>
                        </div>
                    </div>
                    <div id="export-columns-container" class="grid grid-cols-2 md:grid-cols-3 gap-3">
                        </div>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-slate-200 bg-white flex justify-end gap-3">
                <button onclick="tutupModalEkspor()" class="px-4 py-2 bg-white border border-slate-300 text-slate-700 rounded-lg text-sm font-semibold hover:bg-slate-50 transition shadow-sm">Batal</button>
                <button onclick="prosesEksporCSV()" class="px-4 py-2 bg-[#E11D48] text-white rounded-lg text-sm font-semibold hover:bg-rose-700 transition flex items-center gap-2 shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    Unduh CSV
                </button>
            </div>
        </div>
    </div>


    <script>
        // Data Mentah dari PHP (Langsung di-parse jadi Object JavaScript)
        const rawData = <?php echo json_encode($historyData); ?>;
        
        // State Variabel Tabel
        let filteredData = [];
        let state = {
            page: 1,
            limit: 100,
            search: '',
            sortCol: '',
            sortDir: '', // 'ASC' atau 'DESC'
            colFilters: {} // cth: { platform: ['Meta', 'Shopee'] }
        };

        const kolomList = ['tanggal', 'platform', 'ad_spend', 'revenue', 'roas', 'impressions', 'clicks', 'conversions'];
        let activePopupCol = '';

        // --- INISIALISASI ---
        window.onload = () => {
            // Hitung ROAS manual untuk tiap baris agar bisa disorting/filter
            rawData.forEach(row => {
                const spend = parseFloat(row.ad_spend) || 0;
                const rev = parseFloat(row.revenue) || 0;
                row.roas = (spend > 0) ? (rev / spend).toFixed(2) : '0.00';
            });
            processData();
        };

        // --- CORE PROCESS DATA (Search, Filter, Sort) ---
        function processData() {
            let data = [...rawData];

            // 1. Global Search
            if (state.search.trim() !== '') {
                const q = state.search.toLowerCase();
                data = data.filter(row => {
                    return Object.values(row).some(val => 
                        String(val).toLowerCase().includes(q)
                    );
                });
            }

            // 2. Column Filters
            for (const col in state.colFilters) {
                const allowedValues = state.colFilters[col];
                if (allowedValues && allowedValues.length > 0) {
                    data = data.filter(row => allowedValues.includes(String(row[col])));
                }
            }

            // 3. Sorting
            if (state.sortCol && state.sortDir) {
                data.sort((a, b) => {
                    let valA = a[state.sortCol];
                    let valB = b[state.sortCol];
                    
                    if (['ad_spend', 'revenue', 'roas', 'impressions', 'clicks', 'conversions'].includes(state.sortCol)) {
                        valA = parseFloat(valA) || 0;
                        valB = parseFloat(valB) || 0;
                    } else {
                        valA = String(valA).toLowerCase();
                        valB = String(valB).toLowerCase();
                    }

                    if (valA < valB) return state.sortDir === 'ASC' ? -1 : 1;
                    if (valA > valB) return state.sortDir === 'ASC' ? 1 : -1;
                    return 0;
                });
            }

            filteredData = data;
            
            updateSortIcons();
            renderTable();
            renderPagination();
        }

        // --- RENDER TABEL ---
        function renderTable() {
            const tbody = document.getElementById('tableBody');
            if (filteredData.length === 0) {
                tbody.innerHTML = `<tr><td colspan="10" class="px-4 py-12 text-center text-slate-400 bg-slate-50/50">Tidak ada data yang cocok dengan filter.</td></tr>`;
                return;
            }

            const start = (state.page - 1) * state.limit;
            const end = start + state.limit;
            const pageData = filteredData.slice(start, end);

            let html = '';
            pageData.forEach((row, index) => {
                const no = start + index + 1;
                const spend = parseFloat(row.ad_spend);
                const rev = parseFloat(row.revenue);
                const roas = parseFloat(row.roas);

                let badge = 'bg-slate-100 text-slate-600';
                if(row.platform === 'Meta') badge = 'bg-blue-100 text-blue-700';
                if(row.platform === 'Shopee') badge = 'bg-orange-100 text-orange-700';
                if(row.platform === 'TikTok') badge = 'bg-zinc-800 text-white';

                let roasColor = 'text-slate-600';
                if(roas >= 3) roasColor = 'text-emerald-600 font-bold';
                else if(roas < 1.5) roasColor = 'text-red-500 font-bold';

                html += `
                <tr class="hover:bg-rose-50/50 transition-colors group/row">
                    <td class="border-b border-r border-slate-100 px-3 py-2.5 bg-white group-hover/row:bg-rose-50/50 sticky left-0 z-10 text-center shadow-[2px_0_5px_-2px_rgba(0,0,0,0.05)] transition-colors">
                        <div class="flex items-center justify-center gap-1.5 opacity-70 group-hover/row:opacity-100 transition-opacity">
                            <button type="button" onclick="bukaModalEdit(${row.id}, '${row.tanggal}', '${row.platform}', ${spend}, ${rev}, ${row.impressions}, ${row.clicks}, ${row.conversions})" class="text-[10px] font-bold text-[#E11D48] border border-[#E11D48]/30 rounded px-2 py-0.5 hover:bg-rose-100 hover:border-[#E11D48] transition">EDIT</button>
                            <button type="button" onclick="hapusDataJS(${row.id})" class="text-[10px] font-bold text-red-500 border border-red-500/30 rounded px-2 py-0.5 hover:bg-red-50 hover:border-red-500 transition">DEL</button>
                        </div>
                    </td>
                    <td class="border-b border-r border-slate-100 px-3 py-2.5 text-center text-slate-400 font-medium">${no}</td>
                    <td class="border-b border-r border-slate-100 px-4 py-2.5 font-medium text-slate-700">${row.tanggal}</td>
                    <td class="border-b border-r border-slate-100 px-4 py-2.5"><span class="px-2 py-0.5 rounded text-[10px] font-bold tracking-wide ${badge}">${row.platform}</span></td>
                    <td class="border-b border-r border-slate-100 px-4 py-2.5 text-right font-mono text-rose-600 text-[13px]">${formatUang(spend)}</td>
                    <td class="border-b border-r border-slate-100 px-4 py-2.5 text-right font-mono text-emerald-600 text-[13px]">${formatUang(rev)}</td>
                    <td class="border-b border-r border-slate-100 px-4 py-2.5 text-center ${roasColor} text-[13px] bg-slate-50/30">${roas}x</td>
                    <td class="border-b border-r border-slate-100 px-4 py-2.5 text-right text-slate-600 text-[13px]">${formatUang(row.impressions)}</td>
                    <td class="border-b border-r border-slate-100 px-4 py-2.5 text-right text-slate-600 text-[13px]">${formatUang(row.clicks)}</td>
                    <td class="border-b border-slate-100 px-4 py-2.5 text-right text-slate-800 font-semibold text-[13px]">${formatUang(row.conversions)}</td>
                </tr>`;
            });
            tbody.innerHTML = html;
        }

        // --- RENDER PAGINATION ---
        function renderPagination() {
            const totalData = filteredData.length;
            const totalPages = Math.ceil(totalData / state.limit);
            const container = document.getElementById('paginationContainer');
            
            const startData = totalData === 0 ? 0 : ((state.page - 1) * state.limit) + 1;
            const endData = Math.min(state.page * state.limit, totalData);
            document.getElementById('dataInfoText').innerHTML = `${startData} - ${endData} dari <span class="font-bold text-slate-800">${totalData}</span> total`;

            if (totalPages <= 1) {
                container.innerHTML = '';
                return;
            }

            let html = '';

            if (state.page > 1) {
                html += `<button onclick="goToPage(${state.page - 1})" class="w-8 h-8 flex items-center justify-center border border-slate-300 bg-white rounded-md hover:bg-rose-50 hover:text-[#E11D48] hover:border-rose-200 text-slate-600 transition shadow-sm">&laquo;</button>`;
            }

            let startPage = Math.max(1, state.page - 2);
            let endPage = Math.min(totalPages, state.page + 2);

            if (startPage > 1) {
                html += `<button onclick="goToPage(1)" class="w-8 h-8 flex items-center justify-center border border-slate-300 bg-white rounded-md hover:bg-rose-50 text-slate-600 font-medium text-sm transition shadow-sm">1</button>`;
                if (startPage > 2) html += `<span class="w-8 h-8 flex items-center justify-center text-slate-400 font-medium text-sm">...</span>`;
            }

            for (let i = startPage; i <= endPage; i++) {
                if (i === state.page) {
                    html += `<button class="w-8 h-8 flex items-center justify-center border border-[#E11D48] bg-[#E11D48] text-white rounded-md font-bold text-sm shadow-md cursor-default">${i}</button>`;
                } else {
                    html += `<button onclick="goToPage(${i})" class="w-8 h-8 flex items-center justify-center border border-slate-300 bg-white rounded-md hover:bg-rose-50 hover:text-[#E11D48] hover:border-rose-200 text-slate-600 font-medium text-sm transition shadow-sm">${i}</button>`;
                }
            }

            if (endPage < totalPages) {
                if (endPage < totalPages - 1) html += `<span class="w-8 h-8 flex items-center justify-center text-slate-400 font-medium text-sm">...</span>`;
                html += `<button onclick="goToPage(${totalPages})" class="w-8 h-8 flex items-center justify-center border border-slate-300 bg-white rounded-md hover:bg-rose-50 hover:text-[#E11D48] text-slate-600 font-medium text-sm transition shadow-sm">${totalPages}</button>`;
            }

            if (state.page < totalPages) {
                html += `<button onclick="goToPage(${state.page + 1})" class="w-8 h-8 flex items-center justify-center border border-slate-300 bg-white rounded-md hover:bg-rose-50 hover:text-[#E11D48] hover:border-rose-200 text-slate-600 transition shadow-sm">&raquo;</button>`;
            }

            container.innerHTML = html;
        }

        function goToPage(p) { state.page = p; renderTable(); renderPagination(); }
        function changeLimit() { state.limit = parseInt(document.getElementById('limitSelect').value); state.page = 1; processData(); }

        let searchTimer;
        document.getElementById('globalSearch').addEventListener('keyup', function(e) {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => { state.search = e.target.value; state.page = 1; processData(); }, 300);
        });

        function resetFilters() {
            state.search = '';
            document.getElementById('globalSearch').value = '';
            state.colFilters = {};
            state.sortCol = '';
            state.sortDir = '';
            state.page = 1;
            processData();
        }

        // --- FILTER POPUP LOGIC ---
        function toggleFilterPopup(event, col) {
            event.stopPropagation();
            closeAllPopups();

            activePopupCol = col;
            const popup = document.getElementById('filterPopup');
            const th = event.currentTarget;
            const rect = th.getBoundingClientRect();
            
            popup.style.top = (rect.bottom + 5) + 'px';
            popup.style.left = rect.left + 'px';
            if (rect.left + 240 > window.innerWidth) popup.style.left = (window.innerWidth - 250) + 'px';

            popup.classList.remove('hidden');
            renderPopupCheckboxes();
        }

        function renderPopupCheckboxes() {
            const container = document.getElementById('filterCheckboxContainer');
            const uniqueVals = [...new Set(rawData.map(row => String(row[activePopupCol])))].sort();
            document.getElementById('popupSearchInput').value = ''; 

            let html = '';
            uniqueVals.forEach(val => {
                let isChecked = true;
                if (state.colFilters[activePopupCol] && state.colFilters[activePopupCol].length > 0) {
                    isChecked = state.colFilters[activePopupCol].includes(val);
                }
                html += `
                <label class="flex items-center gap-2.5 p-1.5 hover:bg-slate-100 rounded cursor-pointer transition popup-item">
                    <input type="checkbox" value="${val}" class="popup-cb w-4 h-4 rounded text-[#E11D48] focus:ring-[#E11D48] border-slate-300" ${isChecked ? 'checked' : ''}>
                    <span class="text-xs truncate popup-text">${val === '' ? '(Kosong)' : val}</span>
                </label>`;
            });
            container.innerHTML = html;
        }

        function searchFilterCheckboxes() {
            const keyword = document.getElementById('popupSearchInput').value.toLowerCase();
            const items = document.querySelectorAll('.popup-item');
            items.forEach(item => {
                const text = item.querySelector('.popup-text').innerText.toLowerCase();
                item.style.display = text.includes(keyword) ? '' : 'none';
            });
        }

        function toggleAllCheckboxes(status) {
            document.querySelectorAll('.popup-cb').forEach(cb => {
                if(cb.closest('.popup-item').style.display !== 'none') cb.checked = status;
            });
        }

        function applyColumnFilter() {
            const checkedBoxes = Array.from(document.querySelectorAll('.popup-cb:checked')).map(cb => cb.value);
            const totalBoxes = document.querySelectorAll('.popup-cb').length;

            if (checkedBoxes.length === 0) {
                alert("Harus ada minimal 1 filter terpilih, atau klik Batal.");
                return;
            }

            if (checkedBoxes.length === totalBoxes) delete state.colFilters[activePopupCol];
            else state.colFilters[activePopupCol] = checkedBoxes;

            state.page = 1;
            closeAllPopups();
            processData();
        }

        function applySort(direction) {
            state.sortCol = activePopupCol;
            state.sortDir = direction;
            state.page = 1;
            closeAllPopups();
            processData();
        }

        function updateSortIcons() {
            kolomList.forEach(col => {
                const el = document.getElementById(`sort-icon-${col}`);
                if(el) el.innerText = '';
            });
            
            if (state.sortCol && state.sortDir) {
                const activeEl = document.getElementById(`sort-icon-${state.sortCol}`);
                if (activeEl) activeEl.innerText = state.sortDir === 'ASC' ? '▲' : '▼';
            }
        }

        function toggleProfileMenu(e) {
            e.stopPropagation();
            closeAllPopups();
            document.getElementById('profileMenu').classList.toggle('hidden');
        }

        function closeAllPopups(e) {
            const pMenu = document.getElementById('profileMenu');
            if (pMenu && !pMenu.classList.contains('hidden')) pMenu.classList.add('hidden');
            
            const fPopup = document.getElementById('filterPopup');
            if (fPopup && !fPopup.classList.contains('hidden')) fPopup.classList.add('hidden');
        }

        // --- FORMATTER UANG ---
        const formatRupiah = (angka) => {
            let number_string = angka.toString().replace(/[^,\d]/g, ''),
                split = number_string.split(','),
                sisa = split[0].length % 3,
                rupiah = split[0].substr(0, sisa),
                ribuan = split[0].substr(sisa).match(/\d{3}/gi);
            if (ribuan) rupiah += (sisa ? '.' : '') + ribuan.join('.');
            return rupiah;
        }

        const formatUang = (angka) => { return parseInt(angka).toLocaleString('id-ID'); }

        // Format untuk form Tambah (Kiri)
        document.getElementById('inpSpend').addEventListener('keyup', function(e) { this.value = formatRupiah(this.value); });
        document.getElementById('inpRevenue').addEventListener('keyup', function(e) { this.value = formatRupiah(this.value); });
        
        // Format untuk form Edit (Modal)
        document.getElementById('editSpend').addEventListener('keyup', function(e) { this.value = formatRupiah(this.value); });
        document.getElementById('editRevenue').addEventListener('keyup', function(e) { this.value = formatRupiah(this.value); });

        // --- MODAL EDIT LOGIC ---
        function bukaModalEdit(id, tanggal, platform, spend, revenue, impressions, clicks, conversions) {
            document.getElementById('editId').value = id;
            document.getElementById('editTanggal').value = tanggal;
            document.getElementById('editPlatform').value = platform;
            document.getElementById('editSpend').value = formatRupiah(spend);
            document.getElementById('editRevenue').value = formatRupiah(revenue);
            document.getElementById('editImp').value = impressions;
            document.getElementById('editClick').value = clicks;
            document.getElementById('editConv').value = conversions;
            
            const modal = document.getElementById('modal-edit');
            const content = document.getElementById('modal-edit-content');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            
            // Animasi pop-in
            setTimeout(() => {
                content.classList.remove('scale-95');
                content.classList.add('scale-100');
            }, 10);
        }

        function tutupModalEdit() {
            const modal = document.getElementById('modal-edit');
            const content = document.getElementById('modal-edit-content');
            
            content.classList.remove('scale-100');
            content.classList.add('scale-95');
            
            setTimeout(() => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }, 200); // durasi sesuai transition tailwind
        }

        function hapusDataJS(id) {
            Swal.fire({
                title: 'Yakin Hapus?', text: "Data performa iklan ini tidak bisa dikembalikan!",
                icon: 'warning', showCancelButton: true, confirmButtonColor: '#E11D48',
                cancelButtonColor: '#94a3b8', confirmButtonText: 'Ya, Hapus!'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `<input type="hidden" name="delete_id" value="${id}">`;
                    document.body.appendChild(form);
                    form.submit();
                }
            })
        }

        // --- DRAG TO SCROLL TABLE ---
        const slider = document.getElementById('tableScrollArea');
        let isDown = false;
        let startX;
        let scrollLeft;

        slider.addEventListener('mousedown', (e) => {
            isDown = true;
            slider.classList.add('cursor-grabbing');
            startX = e.pageX - slider.offsetLeft;
            scrollLeft = slider.scrollLeft;
        });
        slider.addEventListener('mouseleave', () => { isDown = false; slider.classList.remove('cursor-grabbing'); });
        slider.addEventListener('mouseup', () => { isDown = false; slider.classList.remove('cursor-grabbing'); });
        slider.addEventListener('mousemove', (e) => {
            if (!isDown) return;
            e.preventDefault();
            const x = e.pageX - slider.offsetLeft;
            const walk = (x - startX) * 2;
            slider.scrollLeft = scrollLeft - walk;
        });

        // --- EXPORT CSV LOGIC ---
        function bukaModalEkspor() {
            const modal = document.getElementById('modal-export');
            const container = document.getElementById('export-columns-container');
            container.innerHTML = '';
            
            kolomList.forEach(col => {
                const labelName = col.replace('_', ' ').toUpperCase();
                container.innerHTML += `
                <label class="flex items-center gap-2 cursor-pointer bg-slate-50 p-2 rounded border border-slate-200 hover:bg-rose-50 transition">
                    <input type="checkbox" value="${col}" checked class="export-col-cb w-4 h-4 text-[#E11D48] focus:ring-[#E11D48] rounded border-slate-300">
                    <span class="text-xs font-bold text-slate-700 truncate">${labelName}</span>
                </label>`;
            });
            
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function tutupModalEkspor() {
            const modal = document.getElementById('modal-export');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function toggleAllExportCols(state) { document.querySelectorAll('.export-col-cb').forEach(cb => cb.checked = state); }

        function prosesEksporCSV() {
            const selectedCols = Array.from(document.querySelectorAll('.export-col-cb:checked')).map(cb => cb.value);
            if(selectedCols.length === 0) { alert("Pilih minimal 1 kolom!"); return; }

            const scope = document.querySelector('input[name="export_row_scope"]:checked').value;
            const targetData = scope === 'filtered' ? filteredData : rawData;

            if(targetData.length === 0) { alert("Tidak ada data untuk diekspor."); return; }

            let csvContent = selectedCols.map(c => c.toUpperCase()).join(",") + "\n";
            
            targetData.forEach(row => {
                let rowArray = selectedCols.map(col => {
                    let val = row[col] === null ? '' : String(row[col]);
                    if (val.includes(",")) val = `"${val}"`;
                    return val;
                });
                csvContent += rowArray.join(",") + "\n";
            });

            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement("a");
            link.setAttribute("href", url);
            link.setAttribute("download", `Ads_Analytics_${new Date().toISOString().slice(0,10)}.csv`);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            tutupModalEkspor();
        }

        // --- NOTIFIKASI DARI PHP ---
        <?php if($status == 'success'): ?>
            Swal.fire({ icon: 'success', title: 'Mantap!', text: '<?= $pesan ?>', confirmButtonColor: '#E11D48' });
        <?php elseif($status == 'error'): ?>
            Swal.fire({ icon: 'error', title: 'Waduh...', text: '<?= $pesan ?>', confirmButtonColor: '#E11D48' });
        <?php endif; ?>
    </script>
</body>
</html>