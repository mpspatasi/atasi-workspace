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


// ==============================================================================
// 2. AMBIL STRUKTUR KOLOM TABEL
// ==============================================================================
$stmtCols = $pdo->query("DESCRIBE db_customer");
$kolom_database = $stmtCols->fetchAll(PDO::FETCH_COLUMN);
// Buang kolom 'id' dari daftar header agar tidak dobel
$kolom_database = array_values(array_diff($kolom_database, ['id']));
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction History</title>
    <script src="https://cdn.tailwindcss.com"></script>
    
    <script src="https://bossanova.uk/jspreadsheet/v4/jexcel.js"></script>
    <link rel="stylesheet" href="https://bossanova.uk/jspreadsheet/v4/jexcel.css" type="text/css" />
    <script src="https://jsuites.net/v4/jsuites.js"></script>
    <link rel="stylesheet" href="https://jsuites.net/v4/jsuites.css" type="text/css" />

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        ::-webkit-scrollbar { width: 8px; height: 10px; }
        ::-webkit-scrollbar-track { background: #f8fafc; border-radius: 4px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        th { user-select: none; }
        .sidebar-transition { transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        
        .jexcel tbody td { font-family: inherit; font-size: 12px; }
        .jexcel thead td { font-family: inherit; font-size: 11px; font-weight: bold; background-color: #f8fafc; }

        /* Custom Style sedikit biar SweetAlert2 makin nyatu sama tema biru kita */
        .colored-toast.swal2-icon-success { background-color: #a5dc86 !important; }
        .colored-toast.swal2-icon-error { background-color: #f27474 !important; }
        .colored-toast.swal2-icon-warning { background-color: #f8bb86 !important; }
        .colored-toast.swal2-icon-info { background-color: #3fc3ee !important; }
        .colored-toast .swal2-title { color: white; }
        .colored-toast .swal2-close { color: white; }
        .colored-toast .swal2-html-container { color: white; }
    </style>
</head>
<body class="bg-slate-50 font-sans text-slate-800 h-screen flex overflow-hidden" onclick="closeFilterPopup(event)">

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
            <a href="dashboard.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-blue-100 hover:bg-white/10 hover:text-white transition-colors whitespace-nowrap group">
                <svg class="w-5 h-5 shrink-0 opacity-70 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                <span class="font-medium tracking-wide text-sm">Dashboard</span>
            </a>
            
            <a href="index.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-white/10 text-white shadow-inner whitespace-nowrap">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path></svg>
                <span class="font-semibold tracking-wide text-sm">Transaction History</span>
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
        <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6 shrink-0 shadow-sm z-40 relative">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="p-2 bg-slate-50 rounded-md hover:bg-slate-100 text-slate-600 transition border border-slate-200 shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                <div>
                    <h1 class="text-xl font-bold text-slate-800">Transaction History</h1>
                    <div class="flex items-center gap-2 text-xs font-medium mt-0.5">
                        <span class="text-[#3E54D3]">ATASI</span>
                        <span class="text-slate-400">/</span>
                        <span class="text-slate-500">Jurnal Transaksi</span>
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
            <div class="flex justify-between items-end mb-4 shrink-0">
                <div class="flex items-center gap-3">
                    <span id="loadingIndicator" class="text-sm font-semibold text-blue-600 hidden animate-pulse bg-blue-50 px-3 py-1.5 rounded-md border border-blue-100">Sedang menyinkronkan data...</span>
                </div>
                
                <div class="flex gap-3 items-center">
                    <div class="flex items-center bg-white border border-slate-300 rounded-md overflow-hidden shadow-sm">
                        <span class="pl-3 pr-1 text-xs text-slate-500 font-medium">Hal:</span>
                        <input type="number" id="pageJumpInput" placeholder="..." class="w-14 px-1 py-2 text-sm outline-none border-none text-center bg-transparent" min="1" onkeypress="if(event.key === 'Enter') jumpToPageTop()">
                        <button onclick="jumpToPageTop()" class="px-2 py-2 bg-slate-50 hover:bg-slate-100 text-slate-600 border-l border-slate-300 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </button>
                    </div>

                    <div class="relative">
                        <input type="text" id="globalSearchInput" placeholder="Cari data (Ctrl+F)..." class="pl-9 pr-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-1 focus:ring-[#3E54D3] w-64 shadow-sm text-sm">
                        <svg class="w-4 h-4 absolute left-3 top-2.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                    
                    <button onclick="clearAllFilters()" class="px-4 py-2 bg-white text-slate-700 border border-slate-300 rounded-md hover:bg-slate-50 shadow-sm text-sm font-semibold transition">Reset Filter</button>
                    
                    <button onclick="bukaModalEkspor()" class="px-4 py-2 bg-[#3E54D3] text-white rounded-md hover:bg-blue-800 flex items-center shadow-sm text-sm font-semibold transition gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        <span>Ekspor Data</span>
                    </button>

                    <button onclick="bukaModal()" class="px-4 py-2 bg-[#3E54D3] text-white rounded-md hover:bg-blue-800 flex items-center shadow-sm text-sm font-semibold transition gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                        <span>Impor Data</span>
                    </button>

                    <a href="tambah.php" class="px-4 py-2 bg-[#3E54D3] text-white rounded-md hover:bg-blue-800 flex items-center shadow-sm text-sm font-semibold transition">+ Tambah Baris</a>
                </div>
            </div>

            <div class="bg-white border border-slate-200 shadow-sm overflow-auto flex-grow rounded-t-lg relative" onscroll="closeFilterPopup()">
                <table class="w-max border-collapse text-sm whitespace-nowrap min-w-full">
                    <thead class="sticky top-0 z-20 shadow-sm">
                        <tr class="bg-slate-100 text-slate-600 uppercase tracking-wider text-[11px] font-bold">
                            <th class="border-b border-r border-slate-200 px-3 py-2.5 bg-slate-50 sticky left-0 z-30 w-24 align-middle">Aksi</th>
                            <th class="border-b border-r border-slate-200 px-3 py-2.5 bg-slate-50 align-middle text-center w-12">No.</th>
                            
                            <?php foreach ($kolom_database as $kolom): ?>
                                <th class="border-b border-r border-slate-200 px-4 py-2.5 bg-slate-50 hover:bg-slate-200 cursor-pointer group transition-colors column-header" data-colname="<?= $kolom ?>" onclick="toggleFilterPopup(event, '<?= $kolom ?>')">
                                    <div class="flex items-center justify-between gap-3">
                                        <span><?= htmlspecialchars(str_replace('_', ' ', $kolom)) ?></span>
                                        <div class="flex items-center gap-1">
                                            <span id="sort-icon-<?= $kolom ?>" class="text-[9px] text-blue-600 font-bold hidden"></span>
                                            <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-slate-800 transition-colors" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                                        </div>
                                    </div>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody id="tableBody" class="divide-y divide-slate-100 text-slate-600 relative">
                        </tbody>
                </table>
            </div>

            <div class="bg-white border-x border-b border-slate-200 rounded-b-lg p-3.5 flex justify-between items-center shadow-sm shrink-0">
                <div class="flex items-center gap-3 text-sm text-slate-600 font-medium">
                    <span>Tampilkan:</span>
                    <select id="limitSelect" onchange="changeLimit()" class="border border-slate-300 rounded-md px-2 py-1 outline-none bg-white focus:ring-2 focus:ring-[#3E54D3]">
                        <option value="50">50 Baris</option>
                        <option value="100" selected>100 Baris</option>
                        <option value="250">250 Baris</option>
                    </select>
                    <span id="dataInfoText" class="ml-2 text-slate-400">Menampilkan data...</span>
                </div>
                <div id="paginationContainer" class="flex gap-1.5 items-center"></div>
            </div>
        </div>
    </main>

    <div id="modal-import" class="fixed inset-0 bg-slate-900/60 hidden items-center justify-center z-50 backdrop-blur-sm transition-opacity">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-[95%] overflow-hidden flex flex-col max-h-[90vh]">
            <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center bg-slate-50">
                <div>
                    <h2 class="text-lg font-bold text-slate-800">Impor Data Customer</h2>
                    <p class="text-xs text-slate-500 mt-1">Copy-paste data dari Excel ke tabel di bawah ini.</p>
                </div>
                
                <div class="flex items-center gap-3">
                    <button onclick="tutupModal()" class="text-slate-400 hover:text-slate-600 transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
            </div>
            
            <div class="p-4 overflow-auto flex-1 bg-slate-50 w-full" id="spreadsheet-container">
                <div id="loadingCSV" class="hidden h-full flex flex-col items-center justify-center text-slate-500">
                    <svg class="w-10 h-10 animate-spin text-[#3E54D3] mb-3" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <span class="font-bold">Sedang memproses file...</span>
                </div>
                <div id="spreadsheet" class="w-full"></div>
            </div>
            <div class="px-6 py-4 border-t border-slate-200 bg-white flex justify-end gap-3">
                <button onclick="tutupModal()" class="px-4 py-2 bg-white border border-slate-300 text-slate-700 rounded-lg text-sm font-semibold hover:bg-slate-50 transition shadow-sm">Batal</button>
                <button onclick="prosesImpor(this)" class="px-4 py-2 bg-[#3E54D3] text-white rounded-lg text-sm font-semibold hover:bg-blue-800 transition shadow-sm flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                    Impor Data Manual
                </button>
            </div>
        </div>
    </div>

    <div id="modal-export" class="fixed inset-0 bg-slate-900/60 hidden items-center justify-center z-50 backdrop-blur-sm transition-opacity">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl overflow-hidden flex flex-col">
            <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center bg-slate-50">
                <div>
                    <h2 class="text-lg font-bold text-slate-800">Ekspor Data Customer</h2>
                    <p class="text-xs text-slate-500 mt-1">Pilih kriteria baris dan kolom yang ingin didownload.</p>
                </div>
                <button onclick="tutupModalEkspor()" class="text-slate-400 hover:text-slate-600 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <div class="p-6 overflow-auto max-h-[70vh] space-y-6">
                <div>
                    <h3 class="text-sm font-bold text-slate-800 mb-3 border-b pb-2">1. Pilih Baris Data</h3>
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 cursor-pointer bg-slate-50 p-3 rounded-lg border border-slate-200 w-full hover:bg-indigo-50 hover:border-indigo-200 transition">
                            <input type="radio" name="export_row_scope" value="filtered" checked class="w-4 h-4 text-indigo-600">
                            <span class="text-sm text-slate-700 font-medium">Sesuai Filter & Pencarian Saat Ini</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer bg-slate-50 p-3 rounded-lg border border-slate-200 w-full hover:bg-indigo-50 hover:border-indigo-200 transition">
                            <input type="radio" name="export_row_scope" value="all" class="w-4 h-4 text-indigo-600">
                            <span class="text-sm text-slate-700 font-medium">Seluruh Data Database</span>
                        </label>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between items-end mb-3 border-b pb-2">
                        <h3 class="text-sm font-bold text-slate-800">2. Pilih Kolom</h3>
                        <div class="text-xs">
                            <button onclick="toggleAllExportColumns(true)" class="text-indigo-600 hover:underline font-semibold">Pilih Semua</button>
                            <span class="mx-1 text-slate-300">|</span>
                            <button onclick="toggleAllExportColumns(false)" class="text-slate-500 hover:underline font-semibold">Kosongkan</button>
                        </div>
                    </div>
                    <div id="export-columns-container" class="grid grid-cols-3 gap-3"></div>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-slate-200 bg-white flex justify-end gap-3">
                <button onclick="tutupModalEkspor()" class="px-4 py-2 bg-white border border-slate-300 text-slate-700 rounded-md text-sm font-semibold hover:bg-slate-50 transition">Batal</button>
                <button onclick="prosesEkspor()" id="btnProsesEkspor" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold hover:bg-indigo-700 transition flex items-center gap-2 shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    Unduh CSV
                </button>
            </div>
        </div>
    </div>

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
                <input type="text" id="popupSearchInput" onkeyup="searchInsidePopup()" placeholder="Cari filter..." class="w-full pl-8 pr-2 py-1.5 border border-slate-300 rounded-md text-xs focus:outline-none focus:border-[#3E54D3]">
                <svg class="w-3.5 h-3.5 absolute left-2.5 top-2.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
            <div class="flex gap-3 mt-3 text-[#3E54D3] font-bold text-[10px] uppercase tracking-wider">
                <button type="button" onclick="toggleAllCheckboxes(true)" class="hover:underline">Pilih semua</button>
                <span class="text-slate-300">|</span>
                <button type="button" onclick="toggleAllCheckboxes(false)" class="hover:underline">Kosongkan</button>
            </div>
        </div>
        
        <div id="filterCheckboxContainer" class="max-h-48 overflow-y-auto p-2 space-y-1 border-b border-slate-100"></div>
        
        <div class="p-2.5 flex justify-end gap-1.5 bg-slate-50">
            <button onclick="closeFilterPopup()" class="px-3.5 py-1.5 border border-slate-300 bg-white text-slate-600 rounded-md hover:bg-slate-100 font-semibold text-xs transition">Batal</button>
            <button onclick="applyFilterFromPopup()" class="px-3.5 py-1.5 bg-[#3E54D3] text-white rounded-md hover:bg-blue-800 font-semibold text-xs transition">Simpan</button>
        </div>
    </div>

    <script>
        let currentState = { 
            page: 1, 
            limit: 100, 
            search: '', 
            filters: {},
            sortField: '', 
            sortOrder: ''  
        };
        let currentEditingColumn = null;
        let globalTotalPages = 1; 
        let columnsList = <?php echo json_encode($kolom_database); ?>; 

        const formatRupiah = (angka) => "Rp " + parseInt(angka || 0).toLocaleString('id-ID');

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

        // ================= FUNGSIONALITAS SWEETALERT2 UNTUK UNGGAH CSV =================
        async function prosesUploadCSV(event) {
            const file = event.target.files[0];
            if (!file) return;

            document.getElementById('spreadsheet').classList.add('hidden');
            document.getElementById('loadingCSV').classList.remove('hidden');

            const formData = new FormData();
            formData.append('file_csv', file);

            try {
                const response = await fetch('ajax_import_csv.php', {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    throw new Error(`Server Error: ${response.status} ${response.statusText}`);
                }

                const res = await response.json();
                
                if (res.status === 'success') {
                    Swal.fire({
                        title: 'Upload Completed',
                        text: 'Congrats! Your Upload successfully done',
                        icon: 'success',
                        confirmButtonText: 'Ok',
                        customClass: {
                            confirmButton: 'bg-blue-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-blue-700'
                        },
                        buttonsStyling: false
                    });
                    tutupModal();
                    loadTableData(); 
                } else {
                    Swal.fire({
                        title: 'Upload Error',
                        text: 'Sorry! Something went Wrong',
                        icon: 'error',
                        confirmButtonText: 'Try Again',
                        customClass: {
                            confirmButton: 'bg-red-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-red-700'
                        },
                        buttonsStyling: false
                    });
                }
            } catch (err) {
                console.error(err);
                Swal.fire({
                    title: 'System Error',
                    text: 'Sorry! Something went Wrong on our server',
                    icon: 'error',
                    confirmButtonText: 'Try Again',
                    customClass: {
                        confirmButton: 'bg-red-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-red-700'
                    },
                    buttonsStyling: false
                });
            }

            event.target.value = '';
            document.getElementById('spreadsheet').classList.remove('hidden');
            document.getElementById('loadingCSV').classList.add('hidden');
        }

        // ================= JAVASCRIPT EKSPOR SPREADSHEET =================
        function bukaModalEkspor() {
            const modal = document.getElementById('modal-export');
            modal.classList.remove('hidden');
            modal.classList.add('flex');

            const container = document.getElementById('export-columns-container');
            container.innerHTML = '';
            
            columnsList.forEach(col => {
                const div = document.createElement('label');
                div.className = 'flex items-center gap-2 cursor-pointer hover:bg-slate-50 p-1.5 rounded transition';
                const labelText = col.replace(/_/g, ' ');
                div.innerHTML = `
                    <input type="checkbox" value="${col}" checked class="export-col-cb w-4 h-4 text-indigo-600 rounded border-slate-300">
                    <span class="text-xs font-medium text-slate-700 truncate" title="${labelText}">${labelText}</span>
                `;
                container.appendChild(div);
            });
        }

        function tutupModalEkspor() {
            const modal = document.getElementById('modal-export');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function toggleAllExportColumns(state) {
            document.querySelectorAll('.export-col-cb').forEach(cb => cb.checked = state);
        }

        async function prosesEkspor() {
            const selectedColumns = [];
            document.querySelectorAll('.export-col-cb:checked').forEach(cb => selectedColumns.push(cb.value));

            if(selectedColumns.length === 0) {
                alert("Pilih minimal 1 kolom untuk diekspor!");
                return;
            }

            const rowScope = document.querySelector('input[name="export_row_scope"]:checked').value;
            const btn = document.getElementById('btnProsesEkspor');
            const oriText = btn.innerHTML;
            btn.innerHTML = 'Memproses...';
            btn.disabled = true;

            const payload = {
                table: 'db_customer',
                columns: selectedColumns,
                scope: rowScope,
                filters: rowScope === 'filtered' ? currentState : {} 
            };

            try {
                const response = await fetch('export_csv.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                if (!response.ok) throw new Error("Gagal mengekspor data");
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `Data_Customer_${new Date().toISOString().slice(0,10)}.csv`; 
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                a.remove();
                tutupModalEkspor();
            } catch(err) {
                alert("Terjadi kesalahan saat ekspor.");
            }
            btn.innerHTML = oriText;
            btn.disabled = false;
        }

        // ================= JAVASCRIPT IMPOR SPREADSHEET MANUAL =================
        let tabelImpor = null;

        function bukaModal() {
            const modal = document.getElementById('modal-import');
            modal.classList.remove('hidden');
            modal.classList.add('flex');

            if (!tabelImpor) {
                tabelImpor = jspreadsheet(document.getElementById('spreadsheet'), {
                    minDimensions: [24, 1000], 
                    columns: [
                        { type: 'text', title: 'Media', width: 100 },
                        { type: 'text', title: 'Kuartal', width: 80 },
                        { type: 'text', title: 'Tanggal Pesanan', width: 120 },
                        { type: 'text', title: 'Waktu Pesanan', width: 100 },
                        { type: 'text', title: 'No Pesanan', width: 150 },
                        { type: 'text', title: 'Resi', width: 150 },
                        { type: 'text', title: 'Tanggal Dikirim', width: 120 },
                        { type: 'text', title: 'Status Pesanan', width: 120 },
                        { type: 'text', title: 'Tanggal Retur', width: 120 },
                        { type: 'text', title: 'Produk', width: 150 },
                        { type: 'numeric', title: 'Jumlah', width: 80 },
                        { type: 'text', title: 'Ekspedisi', width: 100 },
                        { type: 'text', title: 'Username', width: 120 },
                        { type: 'text', title: 'Nama', width: 150 },
                        { type: 'text', title: 'Telp', width: 120 },
                        { type: 'text', title: 'Provinsi', width: 120 },
                        { type: 'text', title: 'Kabupaten', width: 120 },
                        { type: 'text', title: 'Kecamatan', width: 120 },
                        { type: 'text', title: 'Kodepos', width: 80 },
                        { type: 'text', title: 'Handle', width: 100 },
                        { type: 'text', title: 'Keterangan', width: 200 },
                        { type: 'numeric', title: 'ValueCX', width: 100 },
                        { type: 'numeric', title: 'ValueADS', width: 100 },
                        { type: 'text', title: 'Komoditas', width: 120 }
                    ],
                    defaultColWidth: 100,
                    tableOverflow: true, 
                    tableWidth: "100%",
                    tableHeight: "450px", 
                });
            }
        }

        function tutupModal() {
            const modal = document.getElementById('modal-import');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        // ================= FUNGSIONALITAS SWEETALERT2 UNTUK IMPOR MANUAL =================
        async function prosesImpor(btnElement) {
            const semuaData = tabelImpor.getData();
            const dataValid = semuaData.filter(baris => baris[4] !== '' || baris[13] !== '');
            if (dataValid.length === 0) {
                Swal.fire({
                    title: 'Peringatan!',
                    text: 'Tabel masih kosong cuy! Paste/isi data dulu.',
                    icon: 'warning',
                    confirmButtonText: 'Ok, siap',
                    customClass: {
                        confirmButton: 'bg-yellow-500 text-white px-6 py-2 rounded-lg font-semibold hover:bg-yellow-600'
                    },
                    buttonsStyling: false
                });
                return;
            }
            const btnTextOri = btnElement.innerHTML;
            btnElement.innerHTML = 'Memproses...';
            try {
                const response = await fetch('ajax_import.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'impor_customer', data: dataValid })
                });
                const res = await response.json();
                if (res.status === 'success') {
                    Swal.fire({
                        title: 'Impor Sukses!',
                        text: 'Sukses! ' + dataValid.length + ' baris data berhasil diimpor.',
                        icon: 'success',
                        confirmButtonText: 'Oke Mantap, Bosku!',
                        customClass: {
                            confirmButton: 'bg-blue-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-blue-700'
                        },
                        buttonsStyling: false
                    });
                    tutupModal();
                    tabelImpor.setData([]); 
                    loadTableData(); 
                } else {
                    Swal.fire({
                        title: 'Gagal Impor',
                        text: 'Gagal: ' + res.pesan,
                        icon: 'error',
                        confirmButtonText: 'Coba Lagi',
                        customClass: {
                            confirmButton: 'bg-red-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-red-700'
                        },
                        buttonsStyling: false
                    });
                }
            } catch (err) {
                Swal.fire({
                    title: 'System Error',
                    text: 'Gagal ngirim data ke server cuy!',
                    icon: 'error',
                    confirmButtonText: 'Coba Lagi',
                    customClass: {
                        confirmButton: 'bg-red-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-red-700'
                    },
                    buttonsStyling: false
                });
            }
            btnElement.innerHTML = btnTextOri;
        }

        async function loadTableData() {
            document.getElementById('loadingIndicator').classList.remove('hidden');
            const tbody = document.getElementById('tableBody');
            tbody.style.opacity = '0.4';
            try {
                const response = await fetch('ajax.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'get_data', ...currentState })
                });
                const res = await response.json();
                globalTotalPages = res.totalPages;
                renderTable(res.data);
                renderPagination(res.totalPages, res.totalData);
                updateSortHeaderIcons(); 
            } catch (error) {
                tbody.innerHTML = `<tr><td colspan="${columnsList.length + 2}" class="p-4 text-center text-red-500 font-medium">Gagal memuat data.</td></tr>`;
            }
            tbody.style.opacity = '1';
            document.getElementById('loadingIndicator').classList.add('hidden');
        }

        function renderTable(data) {
            const tbody = document.getElementById('tableBody');
            if (data.length === 0) {
                tbody.innerHTML = `<tr><td colspan="${columnsList.length + 2}" class="p-8 text-center text-slate-500 bg-slate-50/50">Tidak ada data ditemukan.</td></tr>`;
                return;
            }
            let html = '';
            let startIndex = (currentState.page - 1) * currentState.limit;
            data.forEach((row, index) => {
                let rowNumber = startIndex + index + 1;
                html += `<tr class="hover:bg-[#F2F4FD] transition-colors group">`;
                html += `<td class="border-b border-r border-slate-100 px-3 py-1.5 bg-white group-hover:bg-[#F2F4FD] sticky left-0 z-10 text-center shadow-[2px_0_5px_-2px_rgba(0,0,0,0.05)] transition-colors"><a href="edit.php?id=${row.id}" class="inline-block px-2 py-1 bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white rounded font-semibold mr-1 text-[10px] uppercase transition">Edit</a><a href="hapus.php?id=${row.id}" onclick="return confirm('Hapus?')" class="inline-block px-2 py-1 bg-red-50 text-red-600 hover:bg-red-600 hover:text-white rounded font-semibold text-[10px] uppercase transition">Del</a></td>`;
                html += `<td class="border-b border-r border-slate-100 px-3 py-1.5 text-center text-slate-400 font-medium text-xs">${rowNumber}</td>`;
                columnsList.forEach(col => {
                    let val = row[col] !== null ? row[col] : '';
                    let displayHtml = val;
                    if (col === 'Status_Pesanan') {
                        let bg = 'bg-slate-100 text-slate-600';
                        if(val.toLowerCase() === 'selesai') bg = 'bg-emerald-100 text-emerald-700';
                        else if(val.toLowerCase() === 'batal' || val.toLowerCase() === 'retur') bg = 'bg-red-100 text-red-700';
                        else if(val.toLowerCase() === 'perlu dikirim') bg = 'bg-amber-100 text-amber-700';
                        else if(val.toLowerCase() === 'dikirim') bg = 'bg-blue-100 text-blue-700';
                        displayHtml = `<span class="px-2 py-0.5 rounded-md text-xs font-semibold ${bg}">${val}</span>`;
                    } 
                    else if (col === 'ValueCX' || col === 'ValueADS') displayHtml = `<span class="text-slate-600 font-medium">${formatRupiah(val)}</span>`;
                    html += `<td class="border-b border-r border-slate-100 px-4 py-2">${displayHtml}</td>`;
                });
                html += `</tr>`;
            });
            tbody.innerHTML = html;
        }

        function applySort(order) { currentState.sortField = currentEditingColumn; currentState.sortOrder = order; currentState.page = 1; closeFilterPopup(); loadTableData(); }
        function updateSortHeaderIcons() { columnsList.forEach(col => { const el = document.getElementById(`sort-icon-${col}`); if (el) { el.innerText = ''; el.classList.add('hidden'); } }); if (currentState.sortField) { const activeIconEl = document.getElementById(`sort-icon-${currentState.sortField}`); if (activeIconEl) { activeIconEl.innerText = (currentState.sortOrder === 'ASC') ? '▲' : '▼'; activeIconEl.classList.remove('hidden'); } } }
        
        // ================= RENDERING PAGINASI BARU (STYLE KOTAK TERPISAH) =================
        function renderPagination(totalPages, totalData) {
            const container = document.getElementById('paginationContainer');
            // Pastikan class container menggunakan gap agar tombolnya terpisah
            container.className = "flex gap-1.5 items-center";

            const startData = totalData === 0 ? 0 : ((currentState.page - 1) * currentState.limit) + 1;
            const endData = Math.min(currentState.page * currentState.limit, totalData);
            document.getElementById('dataInfoText').innerText = `${startData} - ${endData} dari ${totalData.toLocaleString('id-ID')} total`;
            
            if (totalPages <= 1 && totalData === 0) {
                container.innerHTML = '';
                return;
            }

            let html = '';

            // Tombol Previous (<<)
            if (currentState.page > 1) {
                html += `<button onclick="goToPage(${currentState.page - 1})" class="w-8 h-8 flex items-center justify-center border border-slate-300 bg-white rounded-md hover:bg-slate-50 text-slate-600 transition shadow-sm">&laquo;</button>`;
            }

            let startPage = Math.max(1, currentState.page - 2);
            let endPage = Math.min(totalPages, currentState.page + 2);

            // Tampilkan angka 1 dan titik-titik jika awal terpotong
            if (startPage > 1) {
                html += `<button onclick="goToPage(1)" class="w-8 h-8 flex items-center justify-center border border-slate-300 bg-white rounded-md hover:bg-slate-50 text-slate-600 font-medium text-sm transition shadow-sm">1</button>`;
                if (startPage > 2) {
                    html += `<span class="w-8 h-8 flex items-center justify-center text-slate-500 font-medium text-sm">...</span>`;
                }
            }

            // Loop Angka Halaman
            for (let i = startPage; i <= endPage; i++) {
                if (i === currentState.page) {
                    html += `<button class="w-8 h-8 flex items-center justify-center border border-[#3E54D3] bg-[#3E54D3] text-white rounded-md font-bold text-sm shadow-md">${i}</button>`;
                } else {
                    html += `<button onclick="goToPage(${i})" class="w-8 h-8 flex items-center justify-center border border-slate-300 bg-white rounded-md hover:bg-slate-50 text-slate-600 font-medium text-sm transition shadow-sm">${i}</button>`;
                }
            }

            // Tampilkan titik-titik dan angka terakhir jika akhir terpotong
            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    html += `<span class="w-8 h-8 flex items-center justify-center text-slate-500 font-medium text-sm">...</span>`;
                }
                html += `<button onclick="goToPage(${totalPages})" class="w-8 h-8 flex items-center justify-center border border-slate-300 bg-white rounded-md hover:bg-slate-50 text-slate-600 font-medium text-sm transition shadow-sm">${totalPages}</button>`;
            }

            // Tombol Next (>>)
            if (currentState.page < totalPages) {
                html += `<button onclick="goToPage(${currentState.page + 1})" class="w-8 h-8 flex items-center justify-center border border-slate-300 bg-white rounded-md hover:bg-slate-50 text-slate-600 transition shadow-sm">&raquo;</button>`;
            }

            container.innerHTML = html;
        }

        function goToPage(p) { currentState.page = p; loadTableData(); }
        function changeLimit() { currentState.limit = parseInt(document.getElementById('limitSelect').value); currentState.page = 1; loadTableData(); }
        function jumpToPageTop() { let p = parseInt(document.getElementById('pageJumpInput').value); if (p >= 1 && p <= globalTotalPages) { goToPage(p); document.getElementById('pageJumpInput').value = ''; } else alert('Halaman tidak valid!'); }

        let searchTimer;
        document.getElementById('globalSearchInput').addEventListener('keyup', function() {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => { currentState.search = this.value; currentState.page = 1; loadTableData(); }, 500); 
        });

        async function toggleFilterPopup(event, colName) {
            event.stopPropagation(); const popup = document.getElementById('filterPopup');
            if (currentEditingColumn === colName && !popup.classList.contains('hidden')) { closeFilterPopup(); return; }
            currentEditingColumn = colName; const rect = event.currentTarget.getBoundingClientRect();
            popup.style.top = (rect.bottom + 5) + 'px'; popup.style.left = rect.left + 'px'; popup.classList.remove('hidden');
            const container = document.getElementById('filterCheckboxContainer'); container.innerHTML = '<div class="text-center py-6 text-xs animate-pulse">Menarik data...</div>';
            try {
                const response = await fetch('ajax.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'get_distinct', column: colName }) });
                const uniqueValues = await response.json(); container.innerHTML = '';
                uniqueValues.forEach(val => {
                    let isChecked = !currentState.filters[colName] || currentState.filters[colName].includes(val);
                    let div = document.createElement('div'); div.className = 'flex items-center px-2 py-1.5 hover:bg-slate-100 cursor-pointer';
                    div.innerHTML = `<input type="checkbox" value="${val}" class="w-4 h-4 rounded popup-cb" ${isChecked ? 'checked' : ''}><label class="ml-2 text-xs truncate">${val || '(Kosong)'}</label>`;
                    div.onclick = (e) => { if(e.target.tagName !== 'INPUT') div.querySelector('input').checked = !div.querySelector('input').checked; };
                    container.appendChild(div);
                });
            } catch (err) { container.innerHTML = 'Error'; }
        }

        function closeFilterPopup() { document.getElementById('filterPopup').classList.add('hidden'); currentEditingColumn = null; }
        function searchInsidePopup() { let s = document.getElementById('popupSearchInput').value.toLowerCase(); document.querySelectorAll('#filterCheckboxContainer div').forEach(d => d.style.display = d.innerText.toLowerCase().includes(s) ? '' : 'none'); }
        function toggleAllCheckboxes(state) { document.querySelectorAll('.popup-cb').forEach(cb => cb.checked = state); }
        function applyFilterFromPopup() {
            let checked = []; document.querySelectorAll('.popup-cb:checked').forEach(cb => checked.push(cb.value));
            if (checked.length === document.querySelectorAll('.popup-cb').length) delete currentState.filters[currentEditingColumn];
            else currentState.filters[currentEditingColumn] = checked;
            closeFilterPopup(); currentState.page = 1; loadTableData();
        }
        function clearAllFilters() { currentState.filters = {}; currentState.search = ""; currentState.page = 1; loadTableData(); }

        window.onload = loadTableData;
    </script>
    <?php include 'chat_widget.php'; ?>
</body>
</html>