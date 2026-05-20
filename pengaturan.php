<?php
session_start();

// Anti-Back & Anti-Cache Halaman (Biar aman)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// Cek Sesi (Tendang ke login kalau belum masuk)
if (!isset($_SESSION['username'])) {
    echo "<script>window.location.replace('login.php');</script>";
    exit;
}

require 'koneksi.php';

// CEK HAK AKSES SUPERADMIN
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
    <title>Parameter Setting - ATASI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Desain Scrollbar Custom Biar Cantik */
        ::-webkit-scrollbar { width: 8px; height: 10px; }
        ::-webkit-scrollbar-track { background: #f8fafc; border-radius: 4px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        
        input[type="number"]::-webkit-inner-spin-button, 
        input[type="number"]::-webkit-outer-spin-button { 
            -webkit-appearance: none; margin: 0; 
        }
        input[type="number"] { -moz-appearance: textfield; }

        .input-edit { 
            width: 100%; border: 1px solid transparent; background: transparent; padding: 2px 4px; border-radius: 4px; transition: all 0.2s; 
        }
        .input-edit:hover { border-color: #cbd5e1; background: #fff; }
        .input-edit:focus { border-color: #3E54D3; background: #fff; outline: none; box-shadow: 0 0 0 2px rgba(62,84,211,0.2); }
        
        /* Style untuk input yang dilock (Read-Only) */
        .input-locked {
            width: 100%; background: transparent; padding: 2px 4px; color: #64748b; font-weight: 500; text-align: center; cursor: not-allowed; outline: none;
        }
    </style>
    <script>
        // Anti bfcache browser
        window.addEventListener('pageshow', function(event) {
            if (event.persisted || (window.performance && window.performance.navigation.type === 2)) {
                window.location.reload(); 
            }
        });
        
        // Oper status Superadmin dari PHP ke Javascript
        const isSuperadmin = <?= $is_superadmin ? 'true' : 'false' ?>;
    </script>
</head>
<body class="bg-slate-50 font-sans text-slate-800 h-screen flex overflow-hidden">

    <aside id="mainSidebar" class="w-64 bg-[#3E54D3] text-white flex flex-col shrink-0 overflow-hidden shadow-xl z-40 transition-all duration-300 relative">
        <div class="h-16 flex items-center px-6 border-b border-white/10 shrink-0 gap-3">
            <div class="w-8 h-8 bg-white rounded-md flex items-center justify-center text-[#3E54D3] shrink-0 shadow-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>
            <span class="text-lg font-bold tracking-wider whitespace-nowrap">MARKETING</span>
        </div>
        
        <nav class="flex-1 py-6 px-3 space-y-2 overflow-y-auto flex flex-col">
            <a href="dashboard.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-blue-100 hover:bg-white/10 hover:text-white transition-colors whitespace-nowrap group">
                <svg class="w-5 h-5 shrink-0 opacity-70 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
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
            <a href="followup.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-blue-100 hover:bg-white/10 hover:text-white transition-colors whitespace-nowrap group">
                <svg class="w-5 h-5 shrink-0 opacity-70 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                <span class="font-medium tracking-wide text-sm">Follow Up Space</span>
            </a>
            <a href="return_order.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-blue-100 hover:bg-white/10 hover:text-white transition-colors whitespace-nowrap group">
                <svg class="w-5 h-5 shrink-0 opacity-70 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path></svg>
                <span class="font-medium tracking-wide text-sm">Return Order</span>
            </a>
            <a href="complaint.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-blue-100 hover:bg-white/10 hover:text-white transition-colors whitespace-nowrap group">
                <svg class="w-5 h-5 shrink-0 opacity-70 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path></svg>
                <span class="font-medium tracking-wide text-sm">Complaint Ticket</span>
            </a>

            <div class="mt-auto pt-4 border-t border-white/10">
                <a href="pengaturan.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-white/10 text-white shadow-inner whitespace-nowrap group">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    <span class="font-semibold tracking-wide text-sm">Parameter Setting</span>
                </a>
            </div>
        </nav>
    </aside>

    <main class="flex-1 flex flex-col h-screen min-w-0 bg-slate-50 relative">
        
        <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6 shrink-0 shadow-sm z-40 relative">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="p-2 bg-slate-50 rounded-md hover:bg-slate-100 text-slate-600 transition border border-slate-200 shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-xl font-bold text-slate-800">Parameter Setting</h1>
                        <?php if($is_superadmin): ?>
                            <span class="bg-emerald-100 text-emerald-700 border border-emerald-200 text-[10px] px-2 py-0.5 rounded-full font-bold tracking-wide">MODE EDIT (SUPERADMIN)</span>
                        <?php else: ?>
                            <span class="bg-slate-100 text-slate-500 border border-slate-200 text-[10px] px-2 py-0.5 rounded-full font-bold tracking-wide flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                READ ONLY
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="flex items-center gap-2 text-xs font-medium mt-0.5">
                        <span class="text-[#3E54D3]">ATASI</span>
                        <span class="text-slate-400">/</span>
                        <span class="text-slate-500">Konfigurasi RFM</span>
                    </div>
                </div>
            </div>
            
            <div class="flex items-center gap-4">
            
                <button onclick="toggleChat()" class="relative p-2 text-slate-400 hover:text-[#3E54D3] transition bg-slate-50 hover:bg-blue-50 rounded-full border border-slate-200 shadow-sm flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
                    <span id="chatBadge" class="hidden absolute -top-1.5 -right-1.5 bg-[#FF3B30] text-white text-[10px] font-bold px-1 rounded-full border-2 border-white min-w-[20px] h-[20px] flex items-center justify-center shadow-sm">0</span>
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

        <div class="flex-1 overflow-y-auto p-6 space-y-5 pb-12">
            
            <div class="bg-amber-50 border border-amber-200 text-amber-800 p-3.5 rounded-xl flex gap-3 shadow-sm items-start">
                <svg class="w-6 h-6 shrink-0 text-amber-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <div>
                    <h4 class="font-bold text-sm">Perhatian Guys!</h4>
                    <p class="text-xs mt-1 text-amber-700 leading-relaxed">Pastikan setiap perubahan parameter sudah sesuai dengan strategi RFM yang telah disepakati bersama tim manajemen.</p>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-5 py-3.5 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                    <h2 class="font-bold text-slate-800 text-sm">Konfigurasi Parameter Segmen</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-slate-100 text-slate-600 uppercase tracking-wider text-[11px] font-bold">
                            <tr>
                                <th class="px-4 py-3 border-r border-b border-slate-200 whitespace-nowrap min-w-[150px]">Segmen</th>
                                <th class="px-2 py-3 text-center border-r border-b border-slate-200">R Min</th>
                                <th class="px-2 py-3 text-center border-r border-b border-slate-200">R Max</th>
                                <th class="px-2 py-3 text-center border-r border-b border-slate-200">F Min</th>
                                <th class="px-2 py-3 text-center border-r border-b border-slate-200">F Max</th>
                                <th class="px-2 py-3 text-center border-r border-b border-slate-200">M Min</th>
                                <th class="px-2 py-3 text-center border-r border-b border-slate-200">M Max</th>
                                <th class="px-2 py-3 text-center border-r border-b border-slate-200">Prioritas</th>
                                <th class="px-4 py-3 border-b border-slate-200 w-24 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="body-segmen" class="divide-y divide-slate-100">
                            <tr><td colspan="9" class="text-center py-8 text-slate-400 font-medium text-sm">Memuat data...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-5 py-3.5 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                    <h2 class="font-bold text-slate-800 text-sm">Konfigurasi Syarat Skor</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-slate-100 text-slate-600 uppercase tracking-wider text-[11px] font-bold">
                            <tr>
                                <th class="px-4 py-3 text-center border-r border-b border-slate-200 w-20">Skor</th>
                                <th class="px-3 py-3 text-center border-r border-b border-slate-200">R Min</th>
                                <th class="px-3 py-3 text-center border-r border-b border-slate-200">R Max</th>
                                <th class="px-3 py-3 text-center border-r border-b border-slate-200">F Min</th>
                                <th class="px-3 py-3 text-center border-r border-b border-slate-200">F Max</th>
                                <th class="px-3 py-3 text-center border-r border-b border-slate-200">M Min</th>
                                <th class="px-3 py-3 text-center border-r border-b border-slate-200">M Max</th>
                                <th class="px-4 py-3 border-b border-slate-200 w-24 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="body-skor" class="divide-y divide-slate-100">
                            <tr><td colspan="8" class="text-center py-8 text-slate-400 font-medium text-sm">Memuat data...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>

    <div id="toast" class="fixed bottom-6 right-6 bg-emerald-600 text-white px-5 py-3 rounded-lg shadow-xl font-bold text-sm transform transition-all translate-y-20 opacity-0 z-50 flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
        <span id="toast-msg">Tersimpan!</span>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('mainSidebar');
            if (sidebar.classList.contains('w-64')) {
                sidebar.classList.replace('w-64', 'w-0');
            } else {
                sidebar.classList.replace('w-0', 'w-64');
            }
        }

        // --- Logika Tambahan untuk Profile Dropdown Menu ---
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

        function showToast(message) {
            const toast = document.getElementById('toast');
            document.getElementById('toast-msg').innerText = message;
            toast.classList.remove('translate-y-20', 'opacity-0');
            setTimeout(() => {
                toast.classList.add('translate-y-20', 'opacity-0');
            }, 3000);
        }

        async function loadPengaturan() {
            try {
                const response = await fetch('ajax_pengaturan.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'get_all' })
                });
                const res = await response.json();
                if (res.status === 'success') {
                    renderSegmen(res.segmen);
                    renderSkor(res.skor);
                }
            } catch (err) { console.error(err); }
        }

        function renderSegmen(data) {
            const tbody = document.getElementById('body-segmen');
            let html = '';
            
            const inputAttr = isSuperadmin ? 'class="input-edit text-center text-slate-600 font-mono text-sm"' : 'readonly class="input-locked font-mono text-sm"';
            
            data.forEach(row => {
                const safeId = row.Segmen.replace(/\s+/g, '_'); 
                
                let btnHtml = '';
                if(isSuperadmin) {
                    btnHtml = `<button onclick="simpanSegmen(this, '${row.Segmen}')" class="px-3 py-1.5 bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white rounded text-[10px] font-semibold uppercase transition border border-blue-200 hover:border-blue-600 shadow-sm w-full">Simpan</button>`;
                } else {
                    btnHtml = `<svg class="w-5 h-5 text-slate-300 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>`;
                }

                html += `
                <tr class="hover:bg-[#F2F4FD] transition-colors group">
                    <td class="px-4 py-2.5 font-medium text-slate-700 border-r border-slate-100 whitespace-nowrap">${row.Segmen}</td>
                    <td class="px-2 py-2.5 border-r border-slate-100"><input type="number" id="seg_rmin_${safeId}" value="${row.Skor_R_Min}" ${inputAttr}></td>
                    <td class="px-2 py-2.5 border-r border-slate-100"><input type="number" id="seg_rmax_${safeId}" value="${row.Skor_R_Max}" ${inputAttr}></td>
                    <td class="px-2 py-2.5 border-r border-slate-100"><input type="number" id="seg_fmin_${safeId}" value="${row.Skor_F_Min}" ${inputAttr}></td>
                    <td class="px-2 py-2.5 border-r border-slate-100"><input type="number" id="seg_fmax_${safeId}" value="${row.Skor_F_Max}" ${inputAttr}></td>
                    <td class="px-2 py-2.5 border-r border-slate-100"><input type="number" id="seg_mmin_${safeId}" value="${row.Skor_M_Min}" ${inputAttr}></td>
                    <td class="px-2 py-2.5 border-r border-slate-100"><input type="number" id="seg_mmax_${safeId}" value="${row.Skor_M_Max}" ${inputAttr}></td>
                    <td class="px-2 py-2.5 border-r border-slate-100"><input type="number" id="seg_prio_${safeId}" value="${row.Prioritas}" ${isSuperadmin ? 'class="input-edit text-center text-slate-600 font-bold text-sm"' : 'readonly class="input-locked font-bold text-sm"'}></td>
                    <td class="px-3 py-2.5 text-center align-middle">${btnHtml}</td>
                </tr>`;
            });
            tbody.innerHTML = html;
        }

        function renderSkor(data) {
            const tbody = document.getElementById('body-skor');
            let html = '';
            
            const inputAttr = isSuperadmin ? 'class="input-edit text-center text-slate-600 font-mono text-sm"' : 'readonly class="input-locked font-mono text-sm"';

            data.forEach(row => {
                
                let btnHtml = '';
                if(isSuperadmin) {
                    btnHtml = `<button onclick="simpanSkor(this, ${row.Skor})" class="px-3 py-1.5 bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white rounded text-[10px] font-semibold uppercase transition border border-blue-200 hover:border-blue-600 shadow-sm w-full">Simpan</button>`;
                } else {
                    btnHtml = `<svg class="w-5 h-5 text-slate-300 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>`;
                }

                html += `
                <tr class="hover:bg-[#F2F4FD] transition-colors group">
                    <td class="px-4 py-2.5 text-center font-bold text-[#3E54D3] border-r border-slate-100 text-base">${row.Skor}</td>
                    <td class="px-3 py-2.5 border-r border-slate-100"><input type="number" id="skor_rmin_${row.Skor}" value="${row.R_Min}" ${inputAttr}></td>
                    <td class="px-3 py-2.5 border-r border-slate-100"><input type="number" id="skor_rmax_${row.Skor}" value="${row.R_Max}" ${inputAttr}></td>
                    <td class="px-3 py-2.5 border-r border-slate-100"><input type="number" id="skor_fmin_${row.Skor}" value="${row.F_Min}" ${inputAttr}></td>
                    <td class="px-3 py-2.5 border-r border-slate-100"><input type="number" id="skor_fmax_${row.Skor}" value="${row.F_Max}" ${inputAttr}></td>
                    <td class="px-3 py-2.5 border-r border-slate-100"><input type="number" id="skor_mmin_${row.Skor}" value="${row.M_Min}" ${inputAttr}></td>
                    <td class="px-3 py-2.5 border-r border-slate-100"><input type="number" id="skor_mmax_${row.Skor}" value="${row.M_Max}" ${inputAttr}></td>
                    <td class="px-3 py-2.5 text-center align-middle">${btnHtml}</td>
                </tr>`;
            });
            tbody.innerHTML = html;
        }

        async function simpanSegmen(btnElement, segmenStr) {
            if(!isSuperadmin) return; 
            
            const safeId = segmenStr.replace(/\s+/g, '_'); 
            const payload = {
                action: 'update_segmen',
                segmen: segmenStr,
                skor_r_min: document.getElementById(`seg_rmin_${safeId}`).value,
                skor_r_max: document.getElementById(`seg_rmax_${safeId}`).value,
                skor_f_min: document.getElementById(`seg_fmin_${safeId}`).value,
                skor_f_max: document.getElementById(`seg_fmax_${safeId}`).value,
                skor_m_min: document.getElementById(`seg_mmin_${safeId}`).value,
                skor_m_max: document.getElementById(`seg_mmax_${safeId}`).value,
                prioritas:  document.getElementById(`seg_prio_${safeId}`).value
            };
            btnElement.innerText = '...';
            try {
                const response = await fetch('ajax_pengaturan.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const res = await response.json();
                if(res.status === 'success') showToast(res.pesan);
            } catch (err) { alert('Gagal simpan!'); }
            btnElement.innerText = 'Simpan';
        }

        async function simpanSkor(btnElement, skorVal) {
            if(!isSuperadmin) return; 
            
            const payload = {
                action: 'update_skor',
                skor: skorVal,
                r_min: document.getElementById(`skor_rmin_${skorVal}`).value,
                r_max: document.getElementById(`skor_rmax_${skorVal}`).value,
                f_min: document.getElementById(`skor_fmin_${skorVal}`).value,
                f_max: document.getElementById(`skor_fmax_${skorVal}`).value,
                m_min: document.getElementById(`skor_mmin_${skorVal}`).value,
                m_max: document.getElementById(`skor_mmax_${skorVal}`).value
            };
            btnElement.innerText = '...';
            try {
                const response = await fetch('ajax_pengaturan.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const res = await response.json();
                if(res.status === 'success') showToast(res.pesan);
            } catch (err) { alert('Gagal simpan!'); }
            btnElement.innerText = 'Simpan';
        }

        window.onload = loadPengaturan;
    </script>
    <?php include 'chat_widget.php'; ?>
</body>
</html>