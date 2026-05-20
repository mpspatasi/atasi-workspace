<?php
session_start();

// Anti-Back & Anti-Cache Halaman
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['username'])) {
    echo "<script>window.location.replace('login.php');</script>";
    exit;
}

require 'koneksi.php';
$is_superadmin = (isset($_SESSION['modul_akses']) && $_SESSION['modul_akses'] === 'Superadmin') ? true : false;

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
    <title>Complaint Ticket</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        ::-webkit-scrollbar { width: 8px; height: 10px; }
        ::-webkit-scrollbar-track { background: #f8fafc; border-radius: 4px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        .modal-transition { transition: opacity 0.3s ease, transform 0.3s ease; }
    </style>
</head>
<body class="bg-slate-50 font-sans text-slate-800 h-screen flex overflow-hidden">

    <aside id="mainSidebar" class="w-64 bg-[#3E54D3] text-white flex flex-col shrink-0 overflow-hidden shadow-xl z-40 transition-all duration-300 relative">
        <div class="h-16 flex items-center px-6 border-b border-white/10 shrink-0 gap-3">
            <div class="w-8 h-8 bg-white rounded-md flex items-center justify-center text-[#3E54D3] shrink-0 shadow-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
            <span class="text-lg font-bold tracking-wider whitespace-nowrap">MARKETING</span>
        </div>
        
        <nav class="flex-1 py-6 px-3 space-y-2 overflow-y-auto flex flex-col">
            <a href="dashboard.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-blue-100 hover:bg-white/10 hover:text-white transition-colors group">
                <svg class="w-5 h-5 shrink-0 opacity-70 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                <span class="font-medium tracking-wide text-sm">Dashboard</span>
            </a>
            <a href="transaksi.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-blue-100 hover:bg-white/10 hover:text-white transition-colors group">
                <svg class="w-5 h-5 shrink-0 opacity-70 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path></svg>
                <span class="font-medium tracking-wide text-sm">Transaction History</span>
            </a>
            <a href="rfm.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-blue-100 hover:bg-white/10 hover:text-white transition-colors group">
                <svg class="w-5 h-5 shrink-0 opacity-70 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                <span class="font-medium tracking-wide text-sm">Customer RFM</span>
            </a>
            <a href="followup.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-blue-100 hover:bg-white/10 hover:text-white transition-colors group">
                <svg class="w-5 h-5 shrink-0 opacity-70 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                <span class="font-medium tracking-wide text-sm">Follow Up Space</span>
            </a>
            <a href="return_order.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-blue-100 hover:bg-white/10 hover:text-white transition-colors group">
                <svg class="w-5 h-5 shrink-0 opacity-70 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path></svg>
                <span class="font-medium tracking-wide text-sm">Return Order</span>
            </a>
            
            <a href="complaint.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-white/10 text-white shadow-inner whitespace-nowrap group">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path></svg>
                <span class="font-semibold tracking-wide text-sm">Complaint Ticket</span>
            </a>

            <div class="mt-auto pt-4 border-t border-white/10">
                <a href="pengaturan.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-blue-100 hover:bg-white/10 hover:text-white transition-colors group">
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
                    <h1 class="text-xl font-bold text-slate-800">Complaint Ticket</h1>
                    <div class="flex items-center gap-2 text-xs font-medium mt-0.5">
                        <span class="text-[#3E54D3]">ATASI</span>
                        <span class="text-slate-400">/</span>
                        <span class="text-slate-500">Customer Service</span>
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

        <div class="bg-white border-b border-slate-200 px-6 py-3 shadow-sm z-10 shrink-0 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3 flex-wrap">
                <select id="filter_platform" class="text-sm font-medium text-slate-600 bg-slate-50 border border-slate-300 rounded-md px-3 py-1.5 outline-none focus:border-[#3E54D3]">
                    <option value="">Semua Platform</option>
                    <option value="WhatsApp">WhatsApp</option>
                    <option value="Shopee">Shopee</option>
                    <option value="Tokopedia">Tokopedia</option>
                    <option value="TikTok">TikTok</option>
                    <option value="Lazada">Lazada</option>
                </select>

                <select id="filter_status" class="text-sm font-medium text-slate-600 bg-slate-50 border border-slate-300 rounded-md px-3 py-1.5 outline-none focus:border-[#3E54D3]">
                    <option value="">Semua Status</option>
                    <option value="Open">Open</option>
                    <option value="In Progress">In Progress</option>
                    <option value="Resolved">Resolved</option>
                    <option value="Closed">Closed</option>
                </select>

                <div class="relative">
                    <input type="text" id="search_keyword" placeholder="Cari pesanan/pelanggan..." class="pl-8 pr-3 py-1.5 w-60 text-sm font-medium text-slate-700 bg-slate-50 border border-slate-300 rounded-md outline-none focus:border-[#3E54D3]">
                    <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>

                <button class="px-4 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-md text-sm font-semibold transition border border-slate-300">
                    Filter
                </button>
            </div>

            <button onclick="openModal()" class="px-4 py-1.5 bg-[#3E54D3] hover:bg-blue-800 text-white rounded-md text-sm font-semibold transition shadow-sm flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Tambah Keluhan
            </button>
        </div>

        <div class="p-6 flex-1 overflow-hidden flex flex-col">
            <div class="bg-white border border-slate-200 rounded-xl shadow-sm flex-1 flex flex-col overflow-hidden">
                <div class="overflow-x-auto flex-1">
                    <table class="w-full text-sm text-left whitespace-nowrap">
                        <thead class="bg-slate-100 text-slate-600 uppercase tracking-wider text-[11px] font-bold sticky top-0 z-10 shadow-sm">
                            <tr>
                                <th class="px-4 py-3 border-r border-b border-slate-200 w-24 text-center">Aksi</th>
                                <th class="px-4 py-3 border-r border-b border-slate-200">Tanggal Keluhan</th>
                                <th class="px-4 py-3 border-r border-b border-slate-200">Platform</th>
                                <th class="px-4 py-3 border-r border-b border-slate-200">Pesanan & Nama</th>
                                <th class="px-4 py-3 border-r border-b border-slate-200">Telp & Segmen</th>
                                <th class="px-4 py-3 border-r border-b border-slate-200 text-center">Rating</th>
                                <th class="px-4 py-3 border-r border-b border-slate-200">Kategori & Detail</th>
                                <th class="px-4 py-3 border-r border-b border-slate-200 text-center">Status</th>
                                <th class="px-4 py-3 border-r border-b border-slate-200">PIC</th>
                                <th class="px-4 py-3 border-b border-slate-200 text-center">Tindakan</th>
                            </tr>
                        </thead>
                        <tbody id="table-body" class="divide-y divide-slate-100 text-slate-600">
                            </tbody>
                    </table>
                </div>
                
                <div class="bg-white border-t border-slate-200 p-3.5 flex justify-between items-center shadow-sm shrink-0">
                    <div class="flex items-center gap-3 text-sm text-slate-600 font-medium">
                        <span>Tampilkan:</span>
                        <select class="border border-slate-300 rounded-md px-2 py-1 outline-none bg-white focus:ring-2 focus:ring-[#3E54D3]">
                            <option>100 Baris</option>
                        </select>
                        <span class="ml-2 text-slate-400" id="dataInfoText">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <div id="complaintModal" class="fixed inset-0 bg-slate-900/50 z-50 hidden flex items-center justify-center modal-transition opacity-0 scale-95">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl overflow-hidden flex flex-col max-h-[90vh]">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex justify-between items-center shrink-0">
                <h3 id="modalTitle" class="font-bold text-slate-800 text-lg">Input Tiket Keluhan</h3>
                <button onclick="closeModal()" class="text-slate-400 hover:text-red-500 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <div class="p-6 overflow-y-auto">
                <form id="formComplaint" class="space-y-5">
                    <input type="hidden" id="complaint_id" name="complaint_id">
                    
                    <div class="grid grid-cols-3 gap-5">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">No Pesanan / Resi <span class="text-red-500">*</span></label>
                            <input type="text" id="no_pesanan" class="w-full text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-md px-3 py-2 outline-none focus:border-[#3E54D3] focus:ring-1 focus:ring-[#3E54D3]" placeholder="Contoh: 260508TRDN04AP">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Nama Pelanggan <span class="text-red-500">*</span></label>
                            <input type="text" id="nama_pelanggan" class="w-full text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-md px-3 py-2 outline-none focus:border-[#3E54D3] focus:ring-1 focus:ring-[#3E54D3]" placeholder="Budi Santoso">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">No WhatsApp (Opsional)</label>
                            <input type="text" id="telp" class="w-full text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-md px-3 py-2 outline-none focus:border-[#3E54D3] focus:ring-1 focus:ring-[#3E54D3]" placeholder="085111455255">
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-5">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Platform Media</label>
                            <select id="platform" class="w-full text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-md px-3 py-2 outline-none focus:border-[#3E54D3]">
                                <option value="WhatsApp">WhatsApp</option>
                                <option value="Shopee">Shopee</option>
                                <option value="Tokopedia">Tokopedia</option>
                                <option value="TikTok">TikTok</option>
                                <option value="Lazada">Lazada</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Rating Diberikan</label>
                            <select id="rating" class="w-full text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-md px-3 py-2 outline-none focus:border-[#3E54D3]">
                                <option value="0">Tanpa Bintang</option>
                                <option value="1">Bintang 1 (Sangat Buruk)</option>
                                <option value="2">Bintang 2 (Buruk)</option>
                                <option value="3">Bintang 3 (Cukup)</option>
                                <option value="4">Bintang 4 (Baik)</option>
                                <option value="5">Bintang 5 (Sangat Baik)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Status Penanganan</label>
                            <select id="status" class="w-full text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-md px-3 py-2 outline-none focus:border-[#3E54D3]">
                                <option value="Open" class="text-red-600 font-semibold">Open (Baru)</option>
                                <option value="In Progress" class="text-amber-600 font-semibold">In Progress (Diproses)</option>
                                <option value="Resolved" class="text-emerald-600 font-semibold">Resolved (Terselesaikan)</option>
                                <option value="Closed" class="text-slate-500 font-semibold">Closed (Selesai/Arsip)</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Kategori Masalah</label>
                        <input type="text" id="kategori" class="w-full text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-md px-3 py-2 outline-none focus:border-[#3E54D3]" placeholder="Contoh: Barang Rusak, Pengiriman Lama, Konsultasi Dosis">
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Isi Keluhan / Curhatan Pelanggan</label>
                        <textarea id="detail_keluhan" rows="3" class="w-full text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-md px-3 py-2 outline-none focus:border-[#3E54D3]" placeholder="Tulis rincian keluhan di sini..."></textarea>
                    </div>

                    <div class="border-t border-slate-200 pt-5">
                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Tindakan / Solusi yang Diberikan Tim CS</label>
                        <textarea id="tindakan" rows="2" class="w-full text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-md px-3 py-2 outline-none focus:border-[#3E54D3]" placeholder="Contoh: Sudah dikirim ulang barang pengganti resi XXX..."></textarea>
                    </div>
                </form>
            </div>
            
            <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 flex justify-end gap-3 shrink-0">
                <button onclick="closeModal()" class="px-5 py-2 bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 rounded-md text-sm font-bold transition shadow-sm">Batal</button>
                <button onclick="simpanKeluhan()" class="px-5 py-2 bg-[#3E54D3] hover:bg-blue-800 text-white rounded-md text-sm font-bold transition shadow-sm">Simpan Tiket</button>
            </div>
        </div>
    </div>

    <script>
        // --- LOGIKA SIDEBAR & MENU PROFIL ---
        function toggleSidebar() {
            const sidebar = document.getElementById('mainSidebar');
            if (sidebar.classList.contains('w-64')) {
                sidebar.classList.replace('w-64', 'w-0');
            } else {
                sidebar.classList.replace('w-0', 'w-64');
            }
        }

        function toggleProfileMenu() {
            const menu = document.getElementById('profileMenu');
            menu.classList.toggle('hidden');
        }

        window.addEventListener('click', function(e) {
            const container = document.getElementById('profileDropdownContainer');
            if (container && !container.contains(e.target)) {
                const profileMenu = document.getElementById('profileMenu');
                if (profileMenu) profileMenu.classList.add('hidden');
            }
        });

        // --- LOGIKA TABEL & KELUHAN ---
        const modal = document.getElementById('complaintModal');
        let globalComplaints = []; 
        
        async function fetchComplaints() {
            try {
                const response = await fetch('ajax_complaint.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'get_all' })
                });
                const res = await response.json();
                
                if (res.status === 'success') {
                    globalComplaints = res.data;
                    renderTable(res.data);
                } else {
                    alert('Gagal mengambil data: ' + res.pesan);
                }
            } catch (err) { console.error('Error:', err); }
        }

        function renderTable(data) {
            const tbody = document.getElementById('table-body');
            let html = '';
            
            document.getElementById('dataInfoText').innerText = `Total: ${data.length} keluhan`;

            if (data.length === 0) {
                tbody.innerHTML = `<tr><td colspan="10" class="text-center py-6 text-slate-400 font-medium">Belum ada tiket keluhan.</td></tr>`;
                return;
            }

            data.forEach(row => {
                let stars = '★'.repeat(row.rating) + '☆'.repeat(5 - row.rating);
                let starColor = row.rating <= 2 ? 'text-red-500' : (row.rating == 3 ? 'text-amber-400' : 'text-emerald-500');
                
                let platformColor = 'text-slate-700';
                if(row.platform === 'Shopee') platformColor = 'text-[#EE4D2D]';
                if(row.platform === 'Tokopedia') platformColor = 'text-[#42B549]';
                if(row.platform === 'WhatsApp') platformColor = 'text-[#25D366]';
                if(row.platform === 'TikTok') platformColor = 'text-black';
                if(row.platform === 'Lazada') platformColor = 'text-[#0F136D]';

                let statusBadge = '';
                if(row.status === 'Open') statusBadge = `<span class="bg-red-100 text-red-700 border border-red-200 text-[10px] px-2 py-0.5 rounded font-semibold uppercase tracking-wide">Open</span>`;
                if(row.status === 'In Progress') statusBadge = `<span class="bg-amber-100 text-amber-700 border border-amber-200 text-[10px] px-2 py-0.5 rounded font-semibold uppercase tracking-wide">In Progress</span>`;
                if(row.status === 'Resolved') statusBadge = `<span class="bg-emerald-100 text-emerald-700 border border-emerald-200 text-[10px] px-2 py-0.5 rounded font-semibold uppercase tracking-wide">Resolved</span>`;
                if(row.status === 'Closed') statusBadge = `<span class="bg-slate-100 text-slate-600 border border-slate-200 text-[10px] px-2 py-0.5 rounded font-semibold uppercase tracking-wide">Closed</span>`;

                // Logika format No HP buat link WA
                let rawTelp = row.telp || '';
                let cleanTelp = rawTelp.replace(/[^0-9]/g, '');
                if (cleanTelp.startsWith('0')) {
                    cleanTelp = '62' + cleanTelp.substring(1);
                }
                
                let waButton = '';
                if(cleanTelp.length > 8) {
                    waButton = `<a href="https://wa.me/${cleanTelp}" target="_blank" class="px-3 py-1.5 bg-emerald-50 text-emerald-600 hover:bg-emerald-500 hover:text-white border border-emerald-200 hover:border-emerald-600 rounded shadow-sm text-[10px] font-bold uppercase transition flex items-center justify-center gap-1.5 whitespace-nowrap">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                    Chat WA
                                </a>`;
                } else {
                    waButton = `<span class="text-xs text-slate-300">-</span>`;
                }

                // Warna Segmen
                let segmenColor = row.segmen === 'Nothing' ? 'text-slate-400' : 'text-indigo-600 font-bold';

                html += `
                <tr class="hover:bg-[#F2F4FD] transition-colors group">
                    <td class="px-4 py-2 border-r border-slate-100 text-center flex gap-1 justify-center">
                        <button onclick="openModal(${row.id})" class="px-2 py-1 bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white rounded text-[10px] font-semibold uppercase transition">EDIT</button>
                        <button onclick="hapusKeluhan(${row.id})" class="px-2 py-1 bg-red-50 text-red-600 hover:bg-red-600 hover:text-white rounded text-[10px] font-semibold uppercase transition">DEL</button>
                    </td>
                    <td class="px-4 py-2 border-r border-slate-100 text-slate-600">${row.tanggal_keluhan}</td>
                    <td class="px-4 py-2 border-r border-slate-100"><span class="font-medium ${platformColor}">${row.platform}</span></td>
                    <td class="px-4 py-2 border-r border-slate-100">
                        <div class="font-medium text-[#3E54D3]">${row.no_pesanan}</div>
                        <div class="text-slate-500 text-xs">${row.nama_pelanggan}</div>
                    </td>
                    <td class="px-4 py-2 border-r border-slate-100">
                        <div class="font-medium text-slate-700">${row.telp || '-'}</div>
                        <div class="text-[10px] ${segmenColor}">${row.segmen}</div>
                    </td>
                    <td class="px-4 py-2 border-r border-slate-100 text-center ${starColor} font-bold text-sm">${stars}</td>
                    <td class="px-4 py-2 border-r border-slate-100 max-w-[200px]">
                        <div class="font-medium text-slate-700 truncate">${row.kategori || '-'}</div>
                        <div class="text-slate-500 text-xs truncate" title="${row.detail_keluhan}">${row.detail_keluhan || '-'}</div>
                    </td>
                    <td class="px-4 py-2 border-r border-slate-100 text-center">${statusBadge}</td>
                    <td class="px-4 py-2 border-r border-slate-100 font-medium text-slate-700">${row.pic || '-'}</td>
                    <td class="px-4 py-2 text-center flex justify-center">${waButton}</td>
                </tr>`;
            });
            tbody.innerHTML = html;
        }

        function openModal(id = null) {
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.remove('opacity-0', 'scale-95');
                modal.classList.add('opacity-100', 'scale-100');
            }, 10);

            const form = document.getElementById('formComplaint');
            
            if(id) {
                document.getElementById('modalTitle').innerText = 'Edit Tiket Keluhan #' + id;
                const dt = globalComplaints.find(item => item.id == id);
                if(dt) {
                    document.getElementById('complaint_id').value = dt.id;
                    document.getElementById('no_pesanan').value = dt.no_pesanan;
                    document.getElementById('nama_pelanggan').value = dt.nama_pelanggan;
                    document.getElementById('telp').value = dt.telp || '';
                    document.getElementById('platform').value = dt.platform;
                    document.getElementById('rating').value = dt.rating;
                    document.getElementById('status').value = dt.status;
                    document.getElementById('kategori').value = dt.kategori;
                    document.getElementById('detail_keluhan').value = dt.detail_keluhan;
                    document.getElementById('tindakan').value = dt.tindakan;
                }
            } else {
                document.getElementById('modalTitle').innerText = 'Input Tiket Keluhan Baru';
                form.reset();
                document.getElementById('complaint_id').value = '';
                document.getElementById('status').value = 'Open';
            }
        }

        function closeModal() {
            modal.classList.remove('opacity-100', 'scale-100');
            modal.classList.add('opacity-0', 'scale-95');
            setTimeout(() => { modal.classList.add('hidden'); }, 300);
        }

        async function simpanKeluhan() {
            const btn = event.target;
            const originalText = btn.innerText;
            btn.innerText = 'Memproses...';
            btn.disabled = true;

            const payload = {
                action: 'save',
                id: document.getElementById('complaint_id').value,
                no_pesanan: document.getElementById('no_pesanan').value,
                nama_pelanggan: document.getElementById('nama_pelanggan').value,
                telp: document.getElementById('telp').value,
                platform: document.getElementById('platform').value,
                rating: document.getElementById('rating').value,
                status: document.getElementById('status').value,
                kategori: document.getElementById('kategori').value,
                detail_keluhan: document.getElementById('detail_keluhan').value,
                tindakan: document.getElementById('tindakan').value
            };

            try {
                const response = await fetch('ajax_complaint.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const res = await response.json();
                
                if (res.status === 'success') {
                    closeModal();
                    fetchComplaints(); 
                } else {
                    alert('Gagal: ' + res.pesan);
                }
            } catch (err) { alert('Terjadi kesalahan server.'); }
            
            btn.innerText = originalText;
            btn.disabled = false;
        }

        async function hapusKeluhan(id) {
            if(!confirm('Yakin ingin menghapus tiket ini? Tindakan tidak bisa dibatalkan.')) return;
            
            try {
                const response = await fetch('ajax_complaint.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'delete', id: id })
                });
                const res = await response.json();
                
                if (res.status === 'success') {
                    fetchComplaints(); 
                } else {
                    alert('Gagal menghapus: ' + res.pesan);
                }
            } catch (err) { alert('Terjadi kesalahan server.'); }
        }

        window.onload = fetchComplaints;
    </script>
    <?php include 'chat_widget.php'; ?>
</body>
</html>