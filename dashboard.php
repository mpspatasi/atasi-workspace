<?php
session_start(); // Wajib ditambahkan untuk membaca data session user

require 'koneksi.php'; // Tambahkan ini agar kita bisa query data avatar dari database

// 1. Anti-Back & Anti-Cache Halaman
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// 2. Cek Sesi (Kalau belum login, tendang ke login.php pakai JS biar aman)
if (!isset($_SESSION['username'])) {
    echo "<script>window.location.replace('login.php');</script>";
    exit;
}

// 3. Ambil Data Avatar & Profil User
$username_session = $_SESSION['username'];
$nama_user = $_SESSION['nama_lengkap'] ?? $username_session;
$user_avatar = '';

try {
    $stmtUser = $pdo->prepare("SELECT nama_lengkap, username, avatar FROM tb_users WHERE username = ? LIMIT 1");
    $stmtUser->execute([$username_session]);
    $userData = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if ($userData) {
        $nama_user = $userData['nama_lengkap']; 
        // Set avatar
        if (!empty($userData['avatar'])) {
            $user_avatar = $userData['avatar'];
        } else {
            // Default avatar kartun jika belum setting foto profil
            $user_avatar = "https://api.dicebear.com/7.x/notionists/svg?seed=" . urlencode($userData['username']) . "&backgroundColor=e2e8f0";
        }
    }
} catch (PDOException $e) {}

$inisial_user = strtoupper(substr($nama_user, 0, 1));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Analitik</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        ::-webkit-scrollbar { width: 8px; height: 10px; }
        ::-webkit-scrollbar-track { background: #f8fafc; border-radius: 4px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        .sidebar-transition { transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .custom-dropdown-content { max-height: 200px; overflow-y: auto; }
    </style>
    <script>
        // Pelindung tambahan bfcache murni dari sisi frontend browser
        window.addEventListener('pageshow', function(event) {
            if (event.persisted || (window.performance && window.performance.navigation.type === 2)) {
                window.location.reload(); 
            }
        });
    </script>
</head>
<body class="bg-slate-50 font-sans text-slate-800 h-screen flex overflow-hidden">

    <aside id="mainSidebar" class="w-64 bg-[#3E54D3] text-white flex flex-col shrink-0 overflow-hidden shadow-xl z-40 sidebar-transition relative">
        <div class="h-16 flex items-center px-6 border-b border-white/10 shrink-0 gap-3">
    <div class="w-8 h-8 bg-white rounded-md flex items-center justify-center text-[#3E54D3] shrink-0 shadow-sm">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
        </svg>
    </div>
            <span class="text-lg font-bold tracking-wider whitespace-nowrap">MARKETING</span>
        </div>
        <nav class="flex-1 py-6 px-3 space-y-2 overflow-y-auto">
            <a href="dashboard.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-white/10 text-white shadow-inner whitespace-nowrap">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                <span class="font-semibold tracking-wide text-sm">Dashboard</span>
            </a>
            <a href="transaksi.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-blue-100 hover:bg-white/10 hover:text-white transition-colors whitespace-nowrap group">
                <svg class="w-5 h-5 shrink-0 opacity-70 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path></svg>
                <span class="font-medium tracking-wide text-sm">Transaction History</span>
            </a>
            <a href="rfm.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-blue-100 hover:bg-white/10 hover:text-white transition-colors whitespace-nowrap group">
                <svg class="w-5 h-5 shrink-0 opacity-70 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                <span class="font-medium tracking-wide text-sm">Customer RFM</span>
            </a>
            <a href="followup.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-blue-100 hover:bg-white/10 hover:text-white transition-colors whitespace-nowrap group">
                <svg class="w-5 h-5 shrink-0 opacity-70 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                <span class="font-medium tracking-wide text-sm">Follow Up Space</span>
            </a>
            
            <a href="return_order.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-blue-100 hover:bg-white/10 hover:text-white transition-colors whitespace-nowrap group">
                <svg class="w-5 h-5 shrink-0 opacity-70 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                </svg>
                <span class="font-medium tracking-wide text-sm">Return Order</span>
            </a>
            
            <a href="complaint.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-blue-100 hover:bg-white/10 hover:text-white transition-colors whitespace-nowrap group">
                <svg class="w-5 h-5 shrink-0 opacity-70 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path>
                </svg>
                <span class="font-medium tracking-wide text-sm">Complaint Ticket</span>
            </a>
            
            <div class="mt-8 pt-4 border-t border-white/10">
                <a href="pengaturan.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-300 hover:bg-white/10 hover:text-white transition-colors whitespace-nowrap group">
                    <svg class="w-5 h-5 shrink-0 opacity-70 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    <span class="font-medium tracking-wide text-sm">Parameter Setting</span>
                </a>
            </div>
        </nav>
    </aside>

    <main class="flex-1 flex flex-col h-screen min-w-0 overflow-hidden relative bg-slate-50">
        
        <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6 shrink-0 shadow-sm z-30">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="p-2 bg-slate-50 rounded-md hover:bg-slate-100 text-slate-600 transition border border-slate-200 shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                <div>
                    <h1 class="text-xl font-bold text-slate-800">Visual Dashboard</h1>
                    <div class="flex items-center gap-2 text-xs font-medium mt-0.5">
                        <span class="text-[#3E54D3]">ATASI</span>
                        <span class="text-slate-400">/</span>
                        <span class="text-slate-500">Ringkasan & Visualisasi</span>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-4">
            
            <button onclick="toggleChat()" class="relative p-2 text-slate-400 hover:text-[#3E54D3] transition bg-slate-50 hover:bg-blue-50 rounded-full border border-slate-200 shadow-sm flex-shrink-0">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
    
    <span id="chatBadge" class="hidden absolute -top-1.5 -right-1.5 bg-[#FF3B30] text-white text-[10px] font-bold px-1 rounded-full border-2 border-white min-w-[20px] h-[20px] flex items-center justify-center shadow-sm">
        0
    </span>
</button>

            <div class="h-6 w-px bg-slate-200"></div>

            <div class="relative" id="profileDropdownContainer">
                <button onclick="toggleProfileMenu()" class="flex items-center gap-3 focus:outline-none group">
                    <span class="text-sm font-semibold text-slate-700 group-hover:text-[#3E54D3] transition-colors">
                        <?= htmlspecialchars($nama_user) ?>
                    </span>
                    
                    <?php if (!empty($user_avatar)): ?>
                        <img src="<?= htmlspecialchars($user_avatar) ?>" alt="Profil" class="w-9 h-9 rounded-full object-cover border-2 border-white shadow-sm group-hover:ring-2 group-hover:ring-[#3E54D3]/50 transition-all cursor-pointer bg-slate-100">
                    <?php else: ?>
                        <div class="w-9 h-9 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 font-bold border border-blue-200 group-hover:ring-2 group-hover:ring-[#3E54D3]/50 transition-all cursor-pointer">
                            <?= htmlspecialchars($inisial_user) ?>
                        </div>
                    <?php endif; ?>

                </button>
                
                <div id="profileMenu" class="hidden absolute right-0 mt-3 w-48 bg-white rounded-lg shadow-xl border border-slate-200 z-50 py-1.5 overflow-hidden transform transition-all">
                    <div class="px-4 py-2 border-b border-slate-100 bg-slate-50">
                        <p class="text-[10px] uppercase font-bold tracking-wider text-slate-400 mb-0.5">Signed in as</p>
                        <p class="text-sm font-semibold text-slate-800 truncate">
                            <?= isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : '@cs_atasi' ?>
                        </p>
                    </div>
                    <a href="edit_profil.php" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-slate-600 hover:bg-blue-50 hover:text-[#3E54D3] font-medium transition-colors">
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

        <div class="bg-white border-b border-slate-200 px-6 py-4 shadow-sm z-20 shrink-0">
            <div class="flex flex-wrap items-end gap-3">
                <div class="flex-1 min-w-[150px]">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Rentang Tanggal</label>
                    <select id="filter_tanggal" onchange="toggleCustomDate()" class="w-full text-xs font-semibold text-slate-700 bg-slate-50 border border-slate-300 rounded-md px-3 py-2 outline-none focus:border-[#3E54D3] focus:ring-1 focus:ring-[#3E54D3]">
                        <option value="all">Semua Waktu</option>
                        <option value="hari_ini">Hari Ini</option>
                        <option value="kemarin">Kemarin</option>
                        <option value="7_hari">7 Hari Terakhir</option>
                        <option value="14_hari">14 Hari Terakhir</option>
                        <option value="30_hari">30 Hari Terakhir</option>
                        <option value="bulan_ini">Bulan Ini</option>
                        <option value="kuartal_ini">Kuartal Ini</option>
                        <option value="tahun_ini">Tahun Ini</option>
                        <option value="custom">Custom Tanggal...</option>
                    </select>
                </div>
                
                <div id="custom_date_wrap" class="hidden flex gap-2 min-w-[220px]">
                    <div class="flex-1">
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Mulai</label>
                        <input type="date" id="filter_start" class="w-full text-xs font-semibold text-slate-700 bg-slate-50 border border-slate-300 rounded-md px-2 py-2 outline-none focus:border-[#3E54D3]">
                    </div>
                    <div class="flex-1">
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Sampai</label>
                        <input type="date" id="filter_end" class="w-full text-xs font-semibold text-slate-700 bg-slate-50 border border-slate-300 rounded-md px-2 py-2 outline-none focus:border-[#3E54D3]">
                    </div>
                </div>

                <div class="flex-1 min-w-[130px] relative filter-dropdown-wrapper">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Media Order</label>
                    <button onclick="toggleDropdown(event, 'dd_media')" class="w-full text-left text-xs font-semibold text-slate-700 bg-slate-50 border border-slate-300 rounded-md px-3 py-2 flex justify-between items-center focus:border-[#3E54D3]">
                        <span id="label_media" class="truncate pr-2">Semua Media</span>
                        <svg class="w-3 h-3 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div id="dd_media" class="hidden absolute top-full left-0 mt-1 w-full bg-white border border-slate-200 shadow-xl rounded-md z-50 p-1 custom-dropdown-content">
                        </div>
                </div>
                
                <div class="flex-1 min-w-[130px] relative filter-dropdown-wrapper">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Provinsi</label>
                    <button onclick="toggleDropdown(event, 'dd_provinsi')" class="w-full text-left text-xs font-semibold text-slate-700 bg-slate-50 border border-slate-300 rounded-md px-3 py-2 flex justify-between items-center focus:border-[#3E54D3]">
                        <span id="label_provinsi" class="truncate pr-2">Semua Provinsi</span>
                        <svg class="w-3 h-3 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div id="dd_provinsi" class="hidden absolute top-full left-0 mt-1 w-[200px] bg-white border border-slate-200 shadow-xl rounded-md z-50 p-1 custom-dropdown-content">
                    </div>
                </div>

                <div class="flex-1 min-w-[130px] relative filter-dropdown-wrapper">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Kabupaten/Kota</label>
                    <button onclick="toggleDropdown(event, 'dd_kabupaten')" class="w-full text-left text-xs font-semibold text-slate-700 bg-slate-50 border border-slate-300 rounded-md px-3 py-2 flex justify-between items-center focus:border-[#3E54D3]">
                        <span id="label_kabupaten" class="truncate pr-2">Semua Kabupaten</span>
                        <svg class="w-3 h-3 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div id="dd_kabupaten" class="hidden absolute top-full right-0 mt-1 w-[220px] bg-white border border-slate-200 shadow-xl rounded-md z-50 p-1 custom-dropdown-content">
                    </div>
                </div>

                <div class="flex-1 min-w-[130px] relative filter-dropdown-wrapper">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Status Pesanan</label>
                    <button onclick="toggleDropdown(event, 'dd_status')" class="w-full text-left text-xs font-semibold text-slate-700 bg-slate-50 border border-slate-300 rounded-md px-3 py-2 flex justify-between items-center focus:border-[#3E54D3]">
                        <span id="label_status" class="truncate pr-2">Semua Status</span>
                        <svg class="w-3 h-3 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div id="dd_status" class="hidden absolute top-full right-0 mt-1 w-full bg-white border border-slate-200 shadow-xl rounded-md z-50 p-1 custom-dropdown-content">
                        <?php 
                            $statusOps = ["Selesai", "Dikirim", "Perlu Dikirim", "Belum Bayar", "Retur", "Batal"];
                            foreach($statusOps as $so):
                        ?>
                            <label class="flex items-center gap-2 p-1.5 hover:bg-slate-50 rounded cursor-pointer text-xs font-medium text-slate-700">
                                <input type="checkbox" name="chk_status" value="<?= $so ?>" class="w-3.5 h-3.5 text-[#3E54D3] rounded border-slate-300 focus:ring-[#3E54D3]" onchange="updateLabel('status', 'Semua Status')">
                                <span class="truncate"><?= $so ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button onclick="clearFilters()" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-md text-xs font-bold transition shadow-sm h-[34px] flex items-center justify-center border border-slate-200" title="Reset Filter">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                    <button onclick="fetchDashboardData()" class="px-5 py-2 bg-[#3E54D3] hover:bg-blue-800 text-white rounded-md text-xs font-bold transition shadow-sm h-[34px] flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                        Terapkan
                    </button>
                </div>
            </div>
        </div>

        <div class="flex-grow overflow-y-auto p-6 space-y-6 pb-20">
            <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-4">
                <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between"><p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Total Transaksi</p><h2 id="kpi-transaksi" class="text-xl font-black text-slate-800">0</h2></div>
                <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between"><p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Botol Terjual</p><h2 id="kpi-botol" class="text-xl font-black text-slate-800">0</h2></div>
                <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between"><p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Total Pelanggan</p><h2 id="kpi-pelanggan" class="text-xl font-black text-[#3E54D3]">0</h2></div>
                <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between"><p class="text-[10px] font-bold text-emerald-500 uppercase tracking-wider mb-1">Total Omset</p><h2 id="kpi-omset" class="text-xl font-black text-emerald-700">Rp 0</h2></div>
                <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between"><p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Rata2 Nilai Trx</p><h2 id="kpi-rata" class="text-xl font-black text-slate-800">Rp 0</h2></div>
                <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between"><p class="text-[10px] font-bold text-blue-400 uppercase tracking-wider mb-1">Transaksi RO</p><h2 id="kpi-trx-ro" class="text-xl font-black text-blue-700">0</h2></div>
                <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between"><p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Botol RO</p><h2 id="kpi-botol-ro" class="text-xl font-black text-slate-800">0</h2></div>
                <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between"><p class="text-[10px] font-bold text-red-400 uppercase tracking-wider mb-1">Rasio Retur</p><h2 id="kpi-retur" class="text-xl font-black text-red-600">0%</h2></div>
                <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between xl:col-span-2">
                    <p class="text-[10px] font-bold text-amber-500 uppercase tracking-wider mb-1">Petani Champions</p>
                    <div class="flex items-center gap-2">
                        <svg class="w-6 h-6 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                        <h2 id="kpi-champion" class="text-xl font-black text-amber-600">0</h2>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 bg-white p-5 rounded-xl border border-slate-200 shadow-sm"><h3 class="font-bold text-slate-800 text-sm mb-4">Grafik Penjualan Bulanan</h3><div class="relative h-64"><canvas id="chartBulanan"></canvas></div></div>
                <div class="flex flex-col gap-6">
                    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex-1"><h3 class="font-bold text-slate-800 text-sm mb-4">Penjualan Kuartalan</h3><div class="relative h-24"><canvas id="chartKuartalan"></canvas></div></div>
                    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex-1"><h3 class="font-bold text-slate-800 text-sm mb-4">Penjualan Tahunan</h3><div class="relative h-24"><canvas id="chartTahunan"></canvas></div></div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm"><h3 class="font-bold text-slate-800 text-sm mb-4">Segmen RFM</h3><div class="relative h-56"><canvas id="chartRFM"></canvas></div></div>
                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm"><h3 class="font-bold text-slate-800 text-sm mb-4">Status Pesanan</h3><div class="relative h-56"><canvas id="chartStatus"></canvas></div></div>
                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm"><h3 class="font-bold text-slate-800 text-sm mb-4">Media Pesanan</h3><div class="relative h-56"><canvas id="chartMedia"></canvas></div></div>
                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm"><h3 class="font-bold text-slate-800 text-sm mb-4">Ekspedisi</h3><div class="relative h-56"><canvas id="chartEkspedisi"></canvas></div></div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm"><h3 class="font-bold text-slate-800 text-sm mb-4">Top Jenis Produk Terjual</h3><div class="relative h-64"><canvas id="chartProduk"></canvas></div></div>
                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm"><h3 class="font-bold text-slate-800 text-sm mb-4">Top Produk Repeat Order (RO)</h3><div class="relative h-64"><canvas id="chartProdukRO"></canvas></div></div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm"><h3 class="font-bold text-slate-800 text-sm mb-4">Top Provinsi</h3><div class="relative h-56"><canvas id="chartProvinsi"></canvas></div></div>
                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm"><h3 class="font-bold text-slate-800 text-sm mb-4">Top Kabupaten</h3><div class="relative h-56"><canvas id="chartKabupaten"></canvas></div></div>
                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm"><h3 class="font-bold text-slate-800 text-sm mb-4">Top Kecamatan</h3><div class="relative h-56"><canvas id="chartKecamatan"></canvas></div></div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm"><h3 class="font-bold text-slate-800 text-sm mb-4">Komoditas</h3><div class="relative h-64"><canvas id="chartKomoditas"></canvas></div></div>
                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm"><h3 class="font-bold text-slate-800 text-sm mb-4">Alasan Retur</h3><div class="relative h-64"><canvas id="chartRetur"></canvas></div></div>
            </div>

        </div>
    </main>

    <script>
        let chartInstances = {};
        
        // Simpan Data Global Agar Cepat Dipanggil
        let geoMapGlobal = {};
        let allKabupatenGlobal = [];
        let isFilterLoaded = false;

        function toggleSidebar() {
            const sidebar = document.getElementById('mainSidebar');
            if (sidebar.classList.contains('w-64')) {
                sidebar.classList.replace('w-64', 'w-0');
            } else {
                sidebar.classList.replace('w-0', 'w-64');
            }
        }

        // --- MANAJEMEN MENU PROFIL (BARU) ---
        function toggleProfileMenu() {
            const menu = document.getElementById('profileMenu');
            menu.classList.toggle('hidden');
        }

        // --- MANAJEMEN CUSTOM MULTI-SELECT DROPDOWN ---
        function toggleDropdown(event, id) {
            event.stopPropagation();
            document.querySelectorAll('.custom-dropdown-content').forEach(el => {
                if(el.id !== id) el.classList.add('hidden');
            });
            document.getElementById(id).classList.toggle('hidden');
        }

        // Tutup dropdown jika klik di luar
        document.addEventListener('click', function(e) {
            // Tutup dropdown filter
            if (!e.target.closest('.filter-dropdown-wrapper')) {
                document.querySelectorAll('.custom-dropdown-content').forEach(el => el.classList.add('hidden'));
            }
            // Tutup dropdown profil
            const profileContainer = document.getElementById('profileDropdownContainer');
            if (profileContainer && !profileContainer.contains(e.target)) {
                document.getElementById('profileMenu').classList.add('hidden');
            }
        });

        // Update teks label saat checkbox dicentang/dihapus
        function updateLabel(type, defaultText) {
            const checkedBoxes = document.querySelectorAll(`input[name="chk_${type}"]:checked`);
            const labelEl = document.getElementById(`label_${type}`);
            
            if (checkedBoxes.length === 0) {
                labelEl.innerText = defaultText;
                labelEl.classList.remove('text-[#3E54D3]', 'font-bold');
            } else if (checkedBoxes.length === 1) {
                labelEl.innerText = checkedBoxes[0].value;
                labelEl.classList.add('text-[#3E54D3]', 'font-bold');
            } else {
                labelEl.innerText = `Terpilih (${checkedBoxes.length})`;
                labelEl.classList.add('text-[#3E54D3]', 'font-bold');
            }

            if (type === 'provinsi') {
                updateKabupatenDropdown(false);
            }
        }

        // PERBAIKAN PERFORMA 1: Render checkbox sekaligus pakai string concatenation
        function populateCheckboxes(containerId, checkboxName, optionsArray, defaultLabel) {
            const container = document.getElementById(containerId);
            const currentChecked = Array.from(container.querySelectorAll('input:checked')).map(cb => cb.value);
            
            let htmlContent = '';
            optionsArray.forEach(opt => {
                const isChecked = currentChecked.includes(opt) ? 'checked' : '';
                htmlContent += `
                    <label class="flex items-center gap-2 p-1.5 hover:bg-slate-50 rounded cursor-pointer text-xs font-medium text-slate-700">
                        <input type="checkbox" name="${checkboxName}" value="${opt}" ${isChecked} class="w-3.5 h-3.5 text-[#3E54D3] rounded border-slate-300 focus:ring-[#3E54D3]" onchange="updateLabel('${checkboxName.replace('chk_', '')}', '${defaultLabel}')">
                        <span class="truncate">${opt}</span>
                    </label>
                `;
            });
            container.innerHTML = htmlContent;
        }

        // PERBAIKAN PERFORMA 2: Render Kabupaten sekaligus pakai string concatenation
        function updateKabupatenDropdown(resetSelection = true) {
            const selectedProvs = Array.from(document.querySelectorAll('input[name="chk_provinsi"]:checked')).map(cb => cb.value);
            const container = document.getElementById('dd_kabupaten');
            const currentChecked = Array.from(container.querySelectorAll('input:checked')).map(cb => cb.value);

            let optionsArray = [];
            
            if (selectedProvs.length > 0) {
                selectedProvs.forEach(prov => {
                    if (geoMapGlobal[prov]) {
                        optionsArray = optionsArray.concat(geoMapGlobal[prov]);
                    }
                });
                optionsArray = [...new Set(optionsArray)].sort();
            } else {
                optionsArray = allKabupatenGlobal;
            }

            let htmlContent = '';
            optionsArray.forEach(opt => {
                const isChecked = (!resetSelection && currentChecked.includes(opt)) ? 'checked' : '';
                htmlContent += `
                    <label class="flex items-center gap-2 p-1.5 hover:bg-slate-50 rounded cursor-pointer text-xs font-medium text-slate-700">
                        <input type="checkbox" name="chk_kabupaten" value="${opt}" ${isChecked} class="w-3.5 h-3.5 text-[#3E54D3] rounded border-slate-300 focus:ring-[#3E54D3]" onchange="updateLabel('kabupaten', 'Semua Kabupaten')">
                        <span class="truncate">${opt}</span>
                    </label>
                `;
            });
            container.innerHTML = htmlContent;
            updateLabel('kabupaten', 'Semua Kabupaten');
        }

        // PERBAIKAN LOGIKA: Reset manual, cegah updateLabel bertabrakan
        function clearFilters() {
            // Reset Tanggal
            document.getElementById('filter_tanggal').value = 'all';
            document.getElementById('filter_start').value = '';
            document.getElementById('filter_end').value = '';
            toggleCustomDate();

            // Uncheck semua checkbox
            document.querySelectorAll('.custom-dropdown-content input[type="checkbox"]').forEach(cb => cb.checked = false);
            
            // Fungsi kecil reset label UI saja
            const resetLabelUI = (type, text) => {
                const el = document.getElementById(`label_${type}`);
                if(el) {
                    el.innerText = text;
                    el.classList.remove('text-[#3E54D3]', 'font-bold');
                }
            };

            resetLabelUI('media', 'Semua Media');
            resetLabelUI('provinsi', 'Semua Provinsi');
            resetLabelUI('kabupaten', 'Semua Kabupaten');
            resetLabelUI('status', 'Semua Status');
            
            // Reset Kabupaten ke list utuh secara aman
            updateKabupatenDropdown(true);

            // Fetch ulang (reset chart)
            fetchDashboardData();
        }

        function toggleCustomDate() {
            const val = document.getElementById('filter_tanggal').value;
            const wrap = document.getElementById('custom_date_wrap');
            if(val === 'custom') wrap.classList.remove('hidden');
            else wrap.classList.add('hidden');
        }

        // --- RENDER CHART ---
        function renderGenericChart(canvasId, type, labels, dataArr, bgColor, horizontal = false, isDonut = false) {
            const ctx = document.getElementById(canvasId).getContext('2d');
            if (chartInstances[canvasId]) chartInstances[canvasId].destroy();

            let options = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: isDonut, position: isDonut ? 'bottom' : 'top', labels: { boxWidth: 10, font: {size: 10} } }
                }
            };

            if (type === 'bar' || type === 'line') {
                options.indexAxis = horizontal ? 'y' : 'x';
                options.scales = {
                    x: { ticks: { font: {size: 10} } },
                    y: { ticks: { font: {size: 10} }, beginAtZero: true }
                };
            }
            if(isDonut) options.cutout = '60%';

            chartInstances[canvasId] = new Chart(ctx, {
                type: type,
                data: {
                    labels: labels,
                    datasets: [{
                        data: dataArr,
                        backgroundColor: bgColor,
                        borderColor: type === 'line' ? bgColor : undefined,
                        borderWidth: isDonut ? 2 : 1,
                        borderRadius: type === 'bar' ? 4 : 0,
                        tension: 0.3,
                        fill: type === 'line' ? true : false,
                        backgroundColor: type === 'line' ? bgColor + '33' : bgColor
                    }]
                },
                options: options
            });
        }

        const colors = ['#3E54D3', '#10B981', '#F59E0B', '#EF4444', '#06B6D4', '#8B5CF6', '#EC4899', '#64748B'];

        // --- MAIN FETCH FUNCTION ---
        async function fetchDashboardData() {
            const getChecked = (name) => Array.from(document.querySelectorAll(`input[name="chk_${name}"]:checked`)).map(cb => cb.value);

            const filterData = {
                tanggal: document.getElementById('filter_tanggal').value,
                start: document.getElementById('filter_start').value,
                end: document.getElementById('filter_end').value,
                media: getChecked('media'),
                provinsi: getChecked('provinsi'),
                kabupaten: getChecked('kabupaten'),
                status: getChecked('status'),
            };

            try {
                const response = await fetch('ajax_dashboard.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(filterData)
                });
                
                if(!response.ok) return;
                const res = await response.json();

                if (res.status === 'success') {
                    if (res.filter_options && !isFilterLoaded) {
                        geoMapGlobal = res.filter_options.geo_map;
                        allKabupatenGlobal = res.filter_options.kabupaten;
                        
                        populateCheckboxes('dd_media', 'chk_media', res.filter_options.media, 'Semua Media');
                        populateCheckboxes('dd_provinsi', 'chk_provinsi', res.filter_options.provinsi, 'Semua Provinsi');
                        
                        updateKabupatenDropdown(false); 
                        isFilterLoaded = true;
                    }

                    document.getElementById('kpi-transaksi').innerText = res.kpi.total_transaksi.toLocaleString('id-ID');
                    document.getElementById('kpi-botol').innerText = res.kpi.total_botol.toLocaleString('id-ID');
                    document.getElementById('kpi-pelanggan').innerText = res.kpi.total_pelanggan.toLocaleString('id-ID');
                    document.getElementById('kpi-omset').innerText = 'Rp ' + Math.round(res.kpi.total_omset).toLocaleString('id-ID');
                    document.getElementById('kpi-rata').innerText = 'Rp ' + Math.round(res.kpi.rata_transaksi).toLocaleString('id-ID');
                    document.getElementById('kpi-trx-ro').innerText = res.kpi.transaksi_ro.toLocaleString('id-ID');
                    document.getElementById('kpi-botol-ro').innerText = res.kpi.botol_ro.toLocaleString('id-ID');
                    document.getElementById('kpi-retur').innerText = res.kpi.rasio_retur + '%';
                    document.getElementById('kpi-champion').innerText = res.kpi.total_champions.toLocaleString('id-ID');

                    renderGenericChart('chartBulanan', 'line', res.chart_bulanan.labels, res.chart_bulanan.data, '#3E54D3');
                    renderGenericChart('chartKuartalan', 'bar', res.chart_kuartal.labels, res.chart_kuartal.data, '#10B981');
                    renderGenericChart('chartTahunan', 'bar', res.chart_tahun.labels, res.chart_tahun.data, '#F59E0B');
                    renderGenericChart('chartRFM', 'bar', res.chart_rfm.labels, res.chart_rfm.data, colors);
                    renderGenericChart('chartStatus', 'doughnut', res.chart_status.labels, res.chart_status.data, colors, false, true);
                    renderGenericChart('chartMedia', 'doughnut', res.chart_media.labels, res.chart_media.data, colors, false, true);
                    renderGenericChart('chartEkspedisi', 'pie', res.chart_ekspedisi.labels, res.chart_ekspedisi.data, colors, false, true);
                    renderGenericChart('chartProduk', 'bar', res.chart_produk.labels, res.chart_produk.data, '#3E54D3', true);
                    renderGenericChart('chartProdukRO', 'bar', res.chart_produk_ro.labels, res.chart_produk_ro.data, '#10B981', true);
                    renderGenericChart('chartProvinsi', 'bar', res.chart_provinsi.labels, res.chart_provinsi.data, '#06B6D4', true);
                    renderGenericChart('chartKabupaten', 'bar', res.chart_kabupaten.labels, res.chart_kabupaten.data, '#6366F1', true);
                    renderGenericChart('chartKecamatan', 'bar', res.chart_kecamatan.labels, res.chart_kecamatan.data, '#8B5CF6', true);
                    renderGenericChart('chartKomoditas', 'bar', res.chart_komoditas.labels, res.chart_komoditas.data, '#F59E0B', true);
                    renderGenericChart('chartRetur', 'bar', res.chart_retur.labels, res.chart_retur.data, '#EF4444', true);
                }
            } catch (err) {
                console.error("Gagal koneksi ke ajax_dashboard.php", err);
            }
        }

        window.onload = fetchDashboardData;
    </script>
    <?php include 'chat_widget.php'; ?>
</body>
</html>