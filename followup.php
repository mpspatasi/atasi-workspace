<?php
session_start(); // Wajib ditambahkan untuk membaca data session user yang login

// Cek Sesi (Kalau belum login, tendang ke login.php pakai JS biar aman)
if (!isset($_SESSION['username'])) {
    echo "<script>window.location.replace('login.php');</script>";
    exit;
}

require 'koneksi.php';

// ==============================================================================
// 1. AMBIL DATA AVATAR & PROFIL USER
// ==============================================================================
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
    <title>Follow Up Space</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        ::-webkit-scrollbar { width: 8px; height: 10px; }
        ::-webkit-scrollbar-track { background: #f8fafc; border-radius: 4px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        th { user-select: none; }
    </style>
</head>
<body class="bg-slate-50 font-sans text-slate-800 h-screen flex overflow-hidden" onclick="closeFilterPopup(event)">

    <aside id="mainSidebar" class="w-64 bg-[#3E54D3] text-white flex flex-col shrink-0 overflow-hidden shadow-xl z-40 transition-all duration-300 relative">
        <div class="h-16 flex items-center px-6 border-b border-white/10 shrink-0 gap-3">
    <div class="w-8 h-8 bg-white rounded-md flex items-center justify-center text-[#3E54D3] shrink-0 shadow-sm">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
        </svg>
    </div>
            <span class="text-lg font-bold tracking-wider whitespace-nowrap">MARKETING</span>
        </div>
        <nav class="flex-1 py-6 px-3 space-y-2 overflow-y-auto">
            <a href="dashboard.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-blue-100 hover:bg-white/10 hover:text-white transition-colors whitespace-nowrap group">
                <svg class="w-5 h-5 shrink-0 opacity-70 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                <span class="font-medium tracking-wide text-sm">Dashboard</span>
            </a>
            <a href="transaksi.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-blue-100 hover:bg-white/10 hover:text-white transition-colors whitespace-nowrap group">
                <svg class="w-5 h-5 shrink-0 opacity-70 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path></svg>
                <span class="font-medium tracking-wide text-sm">Transaction History</span>
            </a>
            <a href="rfm.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-blue-100 hover:bg-white/10 hover:text-white transition-colors whitespace-nowrap group">
                <svg class="w-5 h-5 shrink-0 opacity-70 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                <span class="font-medium tracking-wide text-sm">Customer RFM</span>
            </a>
            <a href="followup.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-white/10 text-white shadow-inner whitespace-nowrap">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                <span class="font-semibold tracking-wide text-sm">Follow Up Space</span>
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
        
        <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6 shrink-0 shadow-sm z-40 relative">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="p-2 bg-slate-50 rounded-md hover:bg-slate-100 text-slate-600 transition border border-slate-200 shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                <div>
                    <h1 class="text-xl font-bold text-slate-800">Follow Up Space</h1>
                    <div class="flex items-center gap-2 text-xs font-medium mt-0.5">
                        <span class="text-[#3E54D3]">ATASI</span>
                        <span class="text-slate-400">/</span>
                        <span class="text-slate-500">Scheduled Follow Up</span>
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
                            <?= htmlspecialchars($username_session) ?>
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

        <div class="p-6 flex flex-col flex-1 min-h-0 relative">
            
            <div class="mb-5 shrink-0 bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                <div class="flex justify-between items-center">
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Pilih Interval Hari Pembelian Sejak Terakhir Belakangan (Recency):</label>
                    <button onclick="clearAllFilters()" class="px-3 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 rounded-md shadow-sm text-xs font-bold transition">Reset Filter & Sort</button>
                </div>
                <div class="flex flex-wrap gap-2 mt-2.5">
                    <button onclick="changeInterval(0, this)" class="interval-btn px-5 py-2.5 bg-[#3E54D3] text-white hover:bg-blue-800 border border-[#3E54D3] font-bold text-sm rounded-lg shadow-md transition-all flex items-center gap-2">
                        <span>H+0</span>
                        <span class="text-[10px] bg-white/20 px-1.5 py-0.5 rounded text-white font-normal">Hari Ini</span>
                    </button>
                    <button onclick="changeInterval(7, this)" class="interval-btn px-5 py-2.5 bg-white text-slate-600 hover:bg-slate-50 border border-slate-300 font-bold text-sm rounded-lg transition-all flex items-center gap-2">
                        <span>H+7</span>
                        <span class="text-[10px] bg-slate-100 px-1.5 py-0.5 rounded text-slate-500 font-normal">1 Minggu</span>
                    </button>
                    <button onclick="changeInterval(14, this)" class="interval-btn px-5 py-2.5 bg-white text-slate-600 hover:bg-slate-50 border border-slate-300 font-bold text-sm rounded-lg transition-all flex items-center gap-2">
                        <span>H+14</span>
                        <span class="text-[10px] bg-slate-100 px-1.5 py-0.5 rounded text-slate-500 font-normal">2 Minggu</span>
                    </button>
                    <button onclick="changeInterval(30, this)" class="interval-btn px-5 py-2.5 bg-white text-slate-600 hover:bg-slate-50 border border-slate-300 font-bold text-sm rounded-lg transition-all flex items-center gap-2">
                        <span>H+30</span>
                        <span class="text-[10px] bg-slate-100 px-1.5 py-0.5 rounded text-slate-500 font-normal">1 Bulan</span>
                    </button>
                    <button onclick="changeInterval(60, this)" class="interval-btn px-5 py-2.5 bg-white text-slate-600 hover:bg-slate-50 border border-slate-300 font-bold text-sm rounded-lg transition-all flex items-center gap-2">
                        <span>H+60</span>
                        <span class="text-[10px] bg-slate-100 px-1.5 py-0.5 rounded text-slate-500 font-normal">2 Bulan</span>
                    </button>
                    <button onclick="changeInterval(90, this)" class="interval-btn px-5 py-2.5 bg-white text-slate-600 hover:bg-slate-50 border border-slate-300 font-bold text-sm rounded-lg transition-all flex items-center gap-2">
                        <span>H+90</span>
                        <span class="text-[10px] bg-slate-100 px-1.5 py-0.5 rounded text-slate-500 font-normal">3 Bulan</span>
                    </button>
                </div>
            </div>

            <div class="bg-white border border-slate-200 shadow-sm overflow-auto flex-grow rounded-t-lg relative" onscroll="closeFilterPopup()">
                <table class="w-full border-collapse text-sm whitespace-nowrap">
                    <thead class="sticky top-0 z-20 shadow-sm bg-slate-100">
                        <tr class="text-slate-600 uppercase tracking-wider text-[11px] font-bold text-left">
                            <th class="border-b border-r border-slate-200 px-3 py-3 bg-slate-50 text-center w-12 sticky left-0 z-30">
                                <input type="checkbox" id="selectAllHeader" onchange="toggleSelectAllPage(this)" class="w-4 h-4 text-[#3E54D3] bg-white border-slate-300 rounded focus:ring-[#3E54D3] focus:ring-2 cursor-pointer">
                            </th>
                            <th class="border-b border-r border-slate-200 px-4 py-3 bg-slate-50 text-center w-12">No.</th>
                            
                            <th class="border-b border-r border-slate-200 px-5 py-3 bg-slate-50 hover:bg-slate-200 cursor-pointer group transition-colors column-header" data-colname="Nama" onclick="toggleFilterPopup(event, 'Nama')">
                                <div class="flex items-center justify-between gap-3">
                                    <span>Nama Pelanggan</span>
                                    <div class="flex items-center gap-1">
                                        <span id="sort-icon-Nama" class="text-[9px] text-blue-600 font-bold hidden"></span>
                                        <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-slate-800 transition-colors" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                                    </div>
                                </div>
                            </th>
                            <th class="border-b border-r border-slate-200 px-5 py-3 bg-slate-50 hover:bg-slate-200 cursor-pointer group transition-colors column-header" data-colname="Telp" onclick="toggleFilterPopup(event, 'Telp')">
                                <div class="flex items-center justify-between gap-3">
                                    <span>No. Telepon</span>
                                    <div class="flex items-center gap-1">
                                        <span id="sort-icon-Telp" class="text-[9px] text-blue-600 font-bold hidden"></span>
                                        <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-slate-800 transition-colors" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                                    </div>
                                </div>
                            </th>
                            <th class="border-b border-r border-slate-200 px-5 py-3 bg-slate-50 hover:bg-slate-200 cursor-pointer group transition-colors column-header" data-colname="Provinsi" onclick="toggleFilterPopup(event, 'Provinsi')">
                                <div class="flex items-center justify-between gap-3">
                                    <span>Provinsi</span>
                                    <div class="flex items-center gap-1">
                                        <span id="sort-icon-Provinsi" class="text-[9px] text-blue-600 font-bold hidden"></span>
                                        <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-slate-800 transition-colors" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                                    </div>
                                </div>
                            </th>
                            <th class="border-b border-r border-slate-200 px-5 py-3 bg-slate-50 hover:bg-slate-200 cursor-pointer group transition-colors column-header" data-colname="Kabupaten" onclick="toggleFilterPopup(event, 'Kabupaten')">
                                <div class="flex items-center justify-between gap-3">
                                    <span>Kabupaten</span>
                                    <div class="flex items-center gap-1">
                                        <span id="sort-icon-Kabupaten" class="text-[9px] text-blue-600 font-bold hidden"></span>
                                        <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-slate-800 transition-colors" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                                    </div>
                                </div>
                            </th>
                            <th class="border-b border-r border-slate-200 px-5 py-3 bg-slate-50 hover:bg-slate-200 cursor-pointer group transition-colors column-header" data-colname="Kecamatan" onclick="toggleFilterPopup(event, 'Kecamatan')">
                                <div class="flex items-center justify-between gap-3">
                                    <span>Kecamatan</span>
                                    <div class="flex items-center gap-1">
                                        <span id="sort-icon-Kecamatan" class="text-[9px] text-blue-600 font-bold hidden"></span>
                                        <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-slate-800 transition-colors" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                                    </div>
                                </div>
                            </th>
                            <th class="border-b border-r border-slate-200 px-5 py-3 bg-slate-50 hover:bg-slate-200 cursor-pointer group transition-colors column-header" data-colname="Segmen" onclick="toggleFilterPopup(event, 'Segmen')">
                                <div class="flex items-center justify-between gap-3">
                                    <span>Segmen RFM</span>
                                    <div class="flex items-center gap-1">
                                        <span id="sort-icon-Segmen" class="text-[9px] text-blue-600 font-bold hidden"></span>
                                        <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-slate-800 transition-colors" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                                    </div>
                                </div>
                            </th>
                            <th class="border-b border-slate-200 px-5 py-3 bg-slate-50 text-center w-36">Aksi CRM</th>
                        </tr>
                    </thead>
                    <tbody id="followupTableBody" class="divide-y divide-slate-100 text-slate-600">
                        </tbody>
                </table>
            </div>

            <div class="bg-white border-x border-b border-slate-200 rounded-b-lg p-3.5 flex justify-between items-center shadow-sm shrink-0 flex-none">
                <div class="flex gap-2 items-center">
                    <select id="actionDropdown" class="border border-slate-300 rounded-md px-3 py-2 outline-none bg-white text-xs font-semibold text-slate-700 shadow-sm focus:border-[#3E54D3] focus:ring-1 focus:ring-[#3E54D3] cursor-pointer">
                        <option value="copy_selected">Salin Centang (<span id="selectedCountDisplay">0</span>)</option>
                        <option value="copy_all">Salin Semua Halaman Ini</option>
                        <option value="download_selected">Unduh Centang</option>
                        <option value="download_all">Unduh Semua Halaman Ini</option>
                    </select>
                    
                    <button onclick="executeAction()" class="px-4 py-2 bg-[#3E54D3] hover:bg-blue-800 text-white rounded-md text-xs font-bold transition flex items-center shadow-sm">
                        Lakukan
                    </button>
                    
                    <span id="actionFeedback" class="text-xs font-semibold text-emerald-600 hidden ml-2 animate-bounce">✓ Berhasil!</span>
                </div>

                <div class="text-xs font-bold text-slate-400" id="dataCounterText">
                    Memuat data...
                </div>
            </div>

        </div>
    </main>

    <div id="filterPopup" class="hidden absolute bg-white border border-slate-200 shadow-2xl rounded-lg w-64 z-50 flex flex-col text-sm overflow-hidden" onclick="event.stopPropagation()">
        <div class="flex flex-col border-b border-slate-100 bg-slate-50 p-2">
            <button onclick="applySort('ASC')" class="flex items-center gap-2.5 px-3 py-2 text-slate-700 hover:bg-slate-200/70 rounded text-xs font-semibold text-left transition w-full">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"></path></svg>
                <span>Urutkan A ke Z (Kecil ke Besar)</span>
            </button>
            <button onclick="applySort('DESC')" class="flex items-center gap-2.5 px-3 py-2 text-slate-700 hover:bg-slate-200/70 rounded text-xs font-semibold text-left transition w-full">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h9m5-1l4 4m0 0l4-4m-4 4V10"></path></svg>
                <span>Urutkan Z ke A (Besar ke Kecil)</span>
            </button>
        </div>

        <div class="p-3 border-b border-slate-100">
            <div class="relative">
                <input type="text" id="popupSearchInput" onkeyup="searchInsidePopup()" placeholder="Cari filter..." class="w-full pl-8 pr-2 py-2 border border-slate-300 rounded-md text-sm">
            </div>
            <div class="flex gap-3 mt-3 text-[#3E54D3] font-semibold text-[11px] uppercase tracking-wider">
                <button type="button" onclick="toggleAllCheckboxes(true)" class="hover:underline">Pilih semua</button>
                <span class="text-slate-300">|</span>
                <button type="button" onclick="toggleAllCheckboxes(false)" class="hover:underline">Kosongkan</button>
            </div>
        </div>
        <div id="filterCheckboxContainer" class="max-h-56 overflow-y-auto p-2 space-y-1 border-b border-slate-100"></div>
        <div class="p-3 flex justify-end gap-2 bg-slate-50">
            <button onclick="closeFilterPopup()" class="px-4 py-1.5 border border-slate-300 bg-white rounded-md text-xs font-semibold">Batal</button>
            <button onclick="applyFilterFromPopup()" class="px-4 py-1.5 bg-[#3E54D3] text-white rounded-md text-xs font-semibold">Simpan</button>
        </div>
    </div>

    <script>
        // State data dengan filter dan sorting dinamis khusus Follow Up Space
        let currentState = {
            interval: 0,
            sortField: '',
            sortOrder: '',
            filters: {}
        };
        
        let currentEditingColumn = null;
        let currentLoadedData = []; 

        const columnsToCopy = ['Nama', 'Telp', 'Provinsi', 'Kabupaten', 'Kecamatan', 'Segmen'];

        function toggleSidebar() {
            const sidebar = document.getElementById('mainSidebar');
            if (sidebar.classList.contains('w-64')) {
                sidebar.classList.replace('w-64', 'w-0');
            } else {
                sidebar.classList.replace('w-0', 'w-64');
            }
        }

        // --- SCRIPT UNTUK MENU PROFIL ---
        function toggleProfileMenu() {
            const menu = document.getElementById('profileMenu');
            menu.classList.toggle('hidden');
        }

        window.addEventListener('click', function(e) {
            const container = document.getElementById('profileDropdownContainer');
            if (container && !container.contains(e.target)) {
                const profileMenu = document.getElementById('profileMenu');
                if (profileMenu) {
                    profileMenu.classList.add('hidden');
                }
            }
        });
        // --------------------------------

        // Fungsi mengganti tab interval hari
        function changeInterval(days, btnElement) {
            currentState.interval = days;
            
            // 1. Reset semua button ke versi inactive (abu-abu/putih) beserta hover effect-nya
            document.querySelectorAll('.interval-btn').forEach(btn => {
                // Buang warna aktif & efek hover biru
                btn.classList.remove('bg-[#3E54D3]', 'text-white', 'shadow-md', 'hover:bg-blue-800', 'border-[#3E54D3]');
                // Pasang warna pasif & efek hover abu-abu
                btn.classList.add('bg-white', 'text-slate-600', 'border-slate-300', 'hover:bg-slate-50');
                
                const badge = btn.querySelector('span:last-child');
                badge.classList.remove('bg-white/20', 'text-white');
                badge.classList.add('bg-slate-100', 'text-slate-500');
            });

            // 2. Set button yang sedang diklik ke versi aktif (biru) beserta hover effect birunya
            // Buang warna pasif & efek hover abu-abu
            btnElement.classList.remove('bg-white', 'text-slate-600', 'border-slate-300', 'hover:bg-slate-50');
            // Pasang warna aktif & efek hover biru
            btnElement.classList.add('bg-[#3E54D3]', 'text-white', 'shadow-md', 'hover:bg-blue-800', 'border-[#3E54D3]');
            
            const badge = btnElement.querySelector('span:last-child');
            badge.classList.remove('bg-slate-100', 'text-slate-500');
            badge.classList.add('bg-white/20', 'text-white');

            loadFollowupData();
        }

        // Ambil data dari server-side
        async function loadFollowupData() {
            const tbody = document.getElementById('followupTableBody');
            tbody.innerHTML = `<tr><td colspan="9" class="text-center py-12 text-slate-400 font-semibold animate-pulse">Menghitung recency & memproses data...</td></tr>`;
            
            document.getElementById('selectAllHeader').checked = false;
            updateSelectedCount();

            try {
                const response = await fetch('ajax.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'get_followup',
                        ...currentState
                    })
                });

                const res = await response.json();
                if(res.status === 'error') throw new Error(res.pesan);

                currentLoadedData = res.data; 
                renderTable(res.data);
                updateSortHeaderIcons();
            } catch (error) {
                console.error(error);
                tbody.innerHTML = `<tr><td colspan="9" class="text-center py-8 text-red-500 font-medium">Gagal mengambil data: ${error.message}</td></tr>`;
            }
        }

        function renderTable(data) {
            const tbody = document.getElementById('followupTableBody');
            const counter = document.getElementById('dataCounterText');
            
            if (data.length === 0) {
                tbody.innerHTML = `<tr><td colspan="9" class="text-center py-12 text-slate-400 bg-slate-50/50">🎉 Tidak ada customer yang terjadwal untuk follow up di kategori ini hari ini.</td></tr>`;
                counter.innerText = "Total: 0 customer ditemukan.";
                return;
            }

            let html = '';
            data.forEach((row, idx) => {
                let segmentClass = 'bg-slate-100 text-slate-700';
                if(row.Segmen === 'Champions') segmentClass = 'bg-emerald-100 text-emerald-800 border border-emerald-200';
                else if(row.Segmen === 'Loyalist') segmentClass = 'bg-blue-100 text-blue-800 border border-blue-200';
                else if(row.Segmen === 'At Risk') segmentClass = 'bg-orange-100 text-orange-800 border border-orange-200';
                else if(row.Segmen === 'Lost') segmentClass = 'bg-red-100 text-red-800 border border-red-200';

                let waPhone = row.Telp ? row.Telp.toString().replace(/[^0-9]/g, '') : '';
                if (waPhone.startsWith('0')) {
                    waPhone = '62' + waPhone.slice(1);
                }

                let waText = `Halo Pak/Bu ${row.Nama}, semoga sehat selalu ya! Kami dari ATASI ingin menyapa...`;
                let waUrl = `https://wa.me/${waPhone}?text=${encodeURIComponent(waText)}`;

                html += `
                <tr class="hover:bg-slate-50 transition-colors group">
                    <td class="px-3 py-3 text-center border-r border-slate-100 sticky left-0 bg-white group-hover:bg-slate-50 z-10">
                        <input type="checkbox" name="rowCheckbox" value="${idx}" onchange="updateSelectedCount()" class="row-checkbox w-4 h-4 text-[#3E54D3] bg-white border-slate-300 rounded focus:ring-[#3E54D3] focus:ring-2 cursor-pointer">
                    </td>
                    <td class="px-4 py-3 text-center text-slate-400 font-semibold border-r border-slate-100">${idx + 1}</td>
                    <td class="px-5 py-3 font-bold text-slate-800 border-r border-slate-100">${row.Nama || '-'}</td>
                    <td class="px-5 py-3 text-slate-600 font-mono border-r border-slate-100">${row.Telp || '-'}</td>
                    <td class="px-5 py-3 text-slate-600 border-r border-slate-100">${row.Provinsi || '-'}</td>
                    <td class="px-5 py-3 text-slate-600 border-r border-slate-100">${row.Kabupaten || '-'}</td>
                    <td class="px-5 py-3 text-slate-600 border-r border-slate-100">${row.Kecamatan || '-'}</td>
                    <td class="px-5 py-3 border-r border-slate-100">
                        <span class="px-2.5 py-1 rounded-full text-xs font-bold ${segmentClass}">${row.Segmen || 'Unsegmented'}</span>
                    </td>
                    <td class="px-5 py-3 text-center">
                        <a href="${waUrl}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-md shadow-sm transition">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946C.06 5.348 5.397.01 12.008.01c3.202.001 6.212 1.246 8.477 3.513 2.262 2.268 3.502 5.282 3.499 8.484-.003 6.66-5.338 11.997-11.95 11.997-2.005-.001-3.973-.5-5.739-1.453L0 24zm6.59-4.846c1.6.95 3.188 1.449 4.825 1.451 5.436 0 9.86-4.42 9.863-9.864.001-2.63-1.023-5.102-2.884-6.964C16.57 1.916 14.09 1.893 11.46 1.893c-5.438 0-9.863 4.42-9.866 9.865-.001 1.702.451 3.361 1.307 4.8l-.988 3.606 3.734-.98z"/></svg>
                            <span>Chat WA</span>
                        </a>
                    </td>
                </tr>`;
            });

            tbody.innerHTML = html;
            counter.innerText = `Total: ${data.length} customer ditemukan.`;
        }

        // ================= PENANGANAN POPUP FILTER & SORTING =================

        async function toggleFilterPopup(event, colName) {
            event.stopPropagation();
            const popup = document.getElementById('filterPopup');
            if (currentEditingColumn === colName && !popup.classList.contains('hidden')) { closeFilterPopup(); return; }

            currentEditingColumn = colName;
            const rect = event.currentTarget.getBoundingClientRect();
            popup.style.top = (rect.bottom + window.scrollY + 5) + 'px';
            popup.style.left = rect.left + 'px';
            popup.classList.remove('hidden');

            document.getElementById('popupSearchInput').value = '';
            const container = document.getElementById('filterCheckboxContainer');
            container.innerHTML = '<div class="text-center text-slate-400 py-6 text-xs font-medium animate-pulse">Menarik data filter...</div>';

            try {
                // Tarik data filter spesifik berdasarkan interval hari yang aktif
                const response = await fetch('ajax.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        action: 'get_distinct_followup', 
                        column: colName,
                        interval: currentState.interval 
                    })
                });
                const uniqueValues = await response.json();
                
                container.innerHTML = '';
                let isAllSelected = !currentState.filters[colName];
                let selectedValues = currentState.filters[colName] || [];

                if (uniqueValues.includes("")) uniqueValues[uniqueValues.indexOf("")] = "(Kosong)";

                uniqueValues.forEach(val => {
                    let isChecked = isAllSelected || selectedValues.includes(val);
                    let div = document.createElement('div');
                    div.className = 'flex items-center group cb-item hover:bg-slate-100 px-2 py-1.5 rounded cursor-pointer transition';
                    div.innerHTML = `
                        <input type="checkbox" value="${val}" class="w-4 h-4 text-[#3E54D3] bg-white border-slate-300 rounded popup-cb" ${isChecked ? 'checked' : ''}>
                        <label class="ml-2.5 text-slate-700 cursor-pointer select-none truncate w-full text-xs font-medium">${val}</label>
                    `;
                    div.addEventListener('click', (e) => {
                        if(e.target.tagName !== 'INPUT') { e.preventDefault(); let cb = div.querySelector('input'); cb.checked = !cb.checked; }
                    });
                    container.appendChild(div);
                });
            } catch (err) { container.innerHTML = '<div class="text-center text-red-500 py-4 text-xs font-medium">Gagal memuat data.</div>'; }
        }

        function closeFilterPopup() { document.getElementById('filterPopup').classList.add('hidden'); currentEditingColumn = null; }

        function searchInsidePopup() {
            const searchVal = document.getElementById('popupSearchInput').value.toLowerCase();
            document.querySelectorAll('.cb-item').forEach(item => {
                item.style.display = item.querySelector('label').innerText.toLowerCase().indexOf(searchVal) > -1 ? '' : 'none';
            });
        }

        function toggleAllCheckboxes(state) {
            document.querySelectorAll('.popup-cb').forEach(cb => {
                if (cb.closest('.cb-item').style.display !== 'none') cb.checked = state;
            });
        }

        function applyFilterFromPopup() {
            const checkboxes = document.querySelectorAll('.popup-cb');
            let checkedValues = [];
            let allChecked = true;
            checkboxes.forEach(cb => { if (cb.checked) checkedValues.push(cb.value); else allChecked = false; });

            const th = document.querySelector(`th[data-colname="${currentEditingColumn}"]`);
            if (allChecked || checkedValues.length === 0) {
                delete currentState.filters[currentEditingColumn];
                th.classList.remove('bg-indigo-100', 'text-[#3E54D3]');
            } else {
                currentState.filters[currentEditingColumn] = checkedValues;
                th.classList.add('bg-indigo-100', 'text-[#3E54D3]');
            }
            closeFilterPopup();
            loadFollowupData();
        }

        function applySort(order) {
            currentState.sortField = currentEditingColumn;
            currentState.sortOrder = order;
            closeFilterPopup();
            loadFollowupData();
        }

        function updateSortHeaderIcons() {
            columnsToCopy.forEach(col => {
                const el = document.getElementById(`sort-icon-${col}`);
                if (el) {
                    el.innerText = ''; el.classList.add('hidden');
                }
            });

            if (currentState.sortField) {
                const activeIconEl = document.getElementById(`sort-icon-${currentState.sortField}`);
                if (activeIconEl) {
                    activeIconEl.innerText = (currentState.sortOrder === 'ASC') ? '▲' : '▼';
                    activeIconEl.classList.remove('hidden');
                }
            }
        }

        function clearAllFilters() {
            currentState.filters = {}; currentState.sortField = ""; currentState.sortOrder = "";
            document.querySelectorAll('th.column-header').forEach(th => th.classList.remove('bg-indigo-100', 'text-[#3E54D3]'));
            loadFollowupData();
        }

        // ================= LOGIKA SELECT ALL, DROPDOWN & EXPORT =================

        function toggleSelectAllPage(headerCheckbox) {
            const checkboxes = document.querySelectorAll('.row-checkbox');
            checkboxes.forEach(cb => {
                cb.checked = headerCheckbox.checked;
            });
            updateSelectedCount();
        }

        function updateSelectedCount() {
            const checkedCount = document.querySelectorAll('.row-checkbox:checked').length;
            const dropdown = document.getElementById('actionDropdown');
            dropdown.options[0].text = `Salin Centang (${checkedCount})`;
            dropdown.options[2].text = `Unduh Centang (${checkedCount})`;
        }

        function executeAction() {
            const action = document.getElementById('actionDropdown').value;
            if (action === 'copy_selected') copySelectedRows();
            else if (action === 'copy_all') copyAllPageRows();
            else if (action === 'download_selected') downloadSelectedRows();
            else if (action === 'download_all') downloadAllPageRows();
        }

        function showFeedback(message) {
            const feedback = document.getElementById('actionFeedback');
            feedback.innerText = message;
            feedback.classList.remove('hidden');
            setTimeout(() => {
                feedback.classList.add('hidden');
            }, 2000);
        }

        // --- FUNGSI COPY ---
        function copySelectedRows() {
            const checkedBoxes = document.querySelectorAll('.row-checkbox:checked');
            if (checkedBoxes.length === 0) {
                alert('Pilih/centang minimal satu baris terlebih dahulu!');
                return;
            }

            let rowsToCopy = [];
            rowsToCopy.push(columnsToCopy.join("\t"));

            checkedBoxes.forEach(cb => {
                let index = parseInt(cb.value);
                let rowData = currentLoadedData[index];
                if (rowData) {
                    let line = columnsToCopy.map(col => rowData[col] !== null ? rowData[col] : '').join("\t");
                    rowsToCopy.push(line);
                }
            });

            executeCopy(rowsToCopy.join("\n"), '✓ Berhasil disalin ke Clipboard!');
        }

        function copyAllPageRows() {
            const checkboxes = document.querySelectorAll('.row-checkbox');
            if (checkboxes.length === 0) {
                alert('Tidak ada data yang bisa disalin!');
                return;
            }

            document.getElementById('selectAllHeader').checked = true;
            checkboxes.forEach(cb => cb.checked = true);
            updateSelectedCount();

            let rowsToCopy = [];
            rowsToCopy.push(columnsToCopy.join("\t"));

            currentLoadedData.forEach(rowData => {
                let line = columnsToCopy.map(col => rowData[col] !== null ? rowData[col] : '').join("\t");
                rowsToCopy.push(line);
            });

            executeCopy(rowsToCopy.join("\n"), '✓ Berhasil disalin ke Clipboard!');
        }

        function executeCopy(text, successMsg) {
            navigator.clipboard.writeText(text).then(() => {
                showFeedback(successMsg);
            }).catch(err => {
                console.error('Gagal menyalin:', err);
                alert('Gagal menyalin data ke clipboard!');
            });
        }

        // --- FUNGSI DOWNLOAD CSV ---
        function downloadSelectedRows() {
            const checkedBoxes = document.querySelectorAll('.row-checkbox:checked');
            if (checkedBoxes.length === 0) {
                alert('Pilih/centang minimal satu baris terlebih dahulu!');
                return;
            }

            let rowsToCopy = [];
            rowsToCopy.push(columnsToCopy.join(","));

            checkedBoxes.forEach(cb => {
                let index = parseInt(cb.value);
                let rowData = currentLoadedData[index];
                if (rowData) {
                    let line = columnsToCopy.map(col => {
                        let val = rowData[col] !== null ? String(rowData[col]) : '';
                        if(val.includes(',') || val.includes('"')) {
                            val = '"' + val.replace(/"/g, '""') + '"';
                        }
                        return val;
                    }).join(",");
                    rowsToCopy.push(line);
                }
            });

            executeDownload(rowsToCopy.join("\n"), 'FollowUp_Selected.csv');
        }

        function downloadAllPageRows() {
            const checkboxes = document.querySelectorAll('.row-checkbox');
            if (checkboxes.length === 0) {
                alert('Tidak ada data yang bisa diunduh!');
                return;
            }

            document.getElementById('selectAllHeader').checked = true;
            checkboxes.forEach(cb => cb.checked = true);
            updateSelectedCount();

            let rowsToCopy = [];
            rowsToCopy.push(columnsToCopy.join(","));

            currentLoadedData.forEach(rowData => {
                let line = columnsToCopy.map(col => {
                    let val = rowData[col] !== null ? String(rowData[col]) : '';
                    if(val.includes(',') || val.includes('"')) {
                        val = '"' + val.replace(/"/g, '""') + '"';
                    }
                    return val;
                }).join(",");
                rowsToCopy.push(line);
            });

            executeDownload(rowsToCopy.join("\n"), 'FollowUp_All.csv');
        }

        function executeDownload(csvContent, filename) {
            const blob = new Blob(["\ufeff", csvContent], { type: 'text/csv;charset=utf-8;' }); 
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            showFeedback('✓ Berhasil diunduh!');
        }

        window.onload = loadFollowupData;
    </script>
    <?php include 'chat_widget.php'; ?>
</body>
</html>