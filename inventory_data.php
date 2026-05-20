<?php
session_start();
require 'koneksi.php'; 

// ==============================================================================
// 1. CEK SESSION & AMBIL DATA DARI tb_users (BUG FIXED)
// ==============================================================================
// Cek apakah user sudah login
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$username_session = $_SESSION['username'];
$nama_user = $_SESSION['nama_lengkap'] ?? $username_session; // Fallback ke session aslimu

// Query ketat HANYA berdasarkan username agar tidak salah memanggil user lain
$user_avatar = ''; // Siapkan variabel avatar

try {
    // Tambahkan pemanggilan kolom 'avatar' (dan 'modul' jika butuh)
    $stmtUser = $pdo->prepare("SELECT nama_lengkap, username, modul, avatar FROM tb_users WHERE username = ? LIMIT 1");
    $stmtUser->execute([$username_session]);
    $userData = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if ($userData) {
        $nama_user = $userData['nama_lengkap']; 
        
        // Ambil avatar dari database. Jika kosong, panggil default avatar kartun dari DiceBear
        if (!empty($userData['avatar'])) {
            $user_avatar = $userData['avatar'];
        } else {
            $user_avatar = "https://api.dicebear.com/7.x/notionists/svg?seed=" . urlencode($userData['username']) . "&backgroundColor=e2e8f0";
        }
    }
} catch (PDOException $e) {}

$inisial_user = strtoupper(substr($nama_user, 0, 1));

// ==============================================================================
// 2. SINKRONISASI KPI ANALYTICS (Hanya untuk inventory_data.php)
// ==============================================================================
try {
    $kpiTotal = $pdo->query("SELECT COUNT(*) FROM jurnal_transaksi_atasi.db_inventory")->fetchColumn();
    // FIX: Hapus CAST(... AS SIGNED) karena stok sekarang pakai DECIMAL/Float. 
    $kpiAlert = $pdo->query("SELECT COUNT(*) FROM jurnal_transaksi_atasi.db_inventory WHERE current_stock <= min_stock")->fetchColumn();
    $kpiKategori = $pdo->query("SELECT COUNT(DISTINCT kategori) FROM jurnal_transaksi_atasi.db_inventory WHERE kategori IS NOT NULL AND kategori != ''")->fetchColumn();
} catch (PDOException $e) {
    $kpiTotal = 0; $kpiAlert = 0; $kpiKategori = 0;
}

// ==============================================================================
// 3. AMBIL STRUKTUR KOLOM TABEL
// ==============================================================================
$stmtCols = $pdo->query("DESCRIBE jurnal_transaksi_atasi.db_inventory");
$kolom_database = $stmtCols->fetchAll(PDO::FETCH_COLUMN);
$kolom_database = array_values(array_diff($kolom_database, ['id']));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Barang</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        ::-webkit-scrollbar { width: 8px; height: 10px; }
        ::-webkit-scrollbar-track { background: #f8fafc; border-radius: 4px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        th { user-select: none; }
        .sidebar-transition { transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .modal-overlay { transition: opacity 0.2s ease-out; }
        .modal-box { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .column-header:hover svg { opacity: 1; }
        .bulk-checkbox { accent-color: #F59E0B; cursor: pointer; width: 16px; height: 16px; }
    </style>
</head>
<body class="bg-slate-50 font-sans text-slate-800 h-screen flex overflow-hidden" onclick="closeFilterPopup(event); closeProfileMenu(event)">

    <aside id="mainSidebar" class="w-64 bg-[#F59E0B] text-white flex flex-col shrink-0 overflow-hidden shadow-xl z-40 transition-all duration-300 relative">
        <div class="h-16 flex items-center px-6 border-b border-white/10 shrink-0 gap-3">
            <div class="w-8 h-8 bg-white rounded-md flex items-center justify-center text-[#F59E0B] shrink-0 shadow-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
            </div>
            <span class="text-lg font-bold tracking-wider whitespace-nowrap uppercase">INVENTORY</span>
        </div>
        
        <nav class="flex-1 py-6 px-3 space-y-2 overflow-y-auto">
            <a href="inventory_dashboard.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg <?= basename($_SERVER['PHP_SELF']) == 'inventory_dashboard.php' ? 'bg-white/20 text-white shadow-inner' : 'text-orange-50 hover:bg-white/10 hover:text-white' ?> transition-colors whitespace-nowrap group">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                <span class="font-semibold tracking-wide text-sm">Visual Dashboard</span>
            </a>

            <a href="inventory_data.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg <?= basename($_SERVER['PHP_SELF']) == 'inventory_data.php' ? 'bg-white/20 text-white shadow-inner' : 'text-orange-50 hover:bg-white/10 hover:text-white' ?> transition-colors whitespace-nowrap group">
                <svg class="w-5 h-5 shrink-0 opacity-70 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path></svg>
                <span class="font-medium tracking-wide text-sm">Master Barang</span>
            </a>
            
            <a href="inventory_in.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg <?= basename($_SERVER['PHP_SELF']) == 'inventory_in.php' ? 'bg-white/20 text-white shadow-inner' : 'text-orange-50 hover:bg-white/10 hover:text-white' ?> transition-colors whitespace-nowrap group">
                <svg class="w-5 h-5 shrink-0 opacity-70 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"></path></svg>
                <span class="font-medium tracking-wide text-sm">Stock In</span>
            </a>

            <a href="inventory_out.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg <?= basename($_SERVER['PHP_SELF']) == 'inventory_out.php' ? 'bg-white/20 text-white shadow-inner' : 'text-orange-50 hover:bg-white/10 hover:text-white' ?> transition-colors whitespace-nowrap group">
                <svg class="w-5 h-5 shrink-0 transform rotate-180 opacity-70 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"></path></svg>
                <span class="font-medium tracking-wide text-sm">Stock Out</span>
            </a>
            
            <div class="mt-8 pt-4 border-t border-white/10">
                <p class="px-3 mb-2 text-[10px] uppercase font-bold tracking-widest text-orange-200/60">Produksi & Formula</p>
                
                <a href="inventory_formula.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg <?= basename($_SERVER['PHP_SELF']) == 'inventory_formula.php' ? 'bg-white/20 text-white shadow-inner' : 'text-orange-50 hover:bg-white/10 hover:text-white' ?> transition-colors whitespace-nowrap group">
                    <svg class="w-5 h-5 shrink-0 opacity-70 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.618.309a2 2 0 01-1.789 0l-.618-.309a6 6 0 00-3.86-.517l-2.387.477a2 2 0 00-1.022.547V6.818a2 2 0 011.022-1.745l2.387-.477a6 6 0 013.86.517l.618.309a2 2 0 001.789 0l.618-.309a6 6 0 013.86-.517l2.387.477a2 2 0 011.022 1.745v8.61z"></path></svg>
                    <span class="font-medium tracking-wide text-sm">Formula Produksi</span>
                </a>

                <a href="inventory_cheat_sheet.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg <?= basename($_SERVER['PHP_SELF']) == 'inventory_cheat_sheet.php' ? 'bg-white/20 text-white shadow-inner' : 'text-orange-50 hover:bg-white/10 hover:text-white' ?> transition-colors whitespace-nowrap group">
                    <svg class="w-5 h-5 shrink-0 opacity-70 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    <span class="font-medium tracking-wide text-sm">Cheat Sheet</span>
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
                    <h1 class="text-xl font-bold text-slate-800">Master Barang</h1>
                    <div class="flex items-center gap-2 text-xs font-medium mt-0.5">
                        <span class="text-[#F59E0B]">ATASI</span><span class="text-slate-400">/</span><span class="text-slate-500">Inventory Hub</span><span class="text-slate-400">
                    </div>
                </div>
            </div>
            
            <div class="flex items-center gap-4">
                <div class="relative" id="profileDropdownContainer">
                    <button onclick="toggleProfileMenu(event)" class="flex items-center gap-3 focus:outline-none group">
                        <span class="text-sm font-semibold text-slate-700 group-hover:text-[#FFBF00] transition-colors">
                        <?= htmlspecialchars($nama_user) ?>
                        </span>
    
                        <?php if (!empty($user_avatar)): ?>
                        <img src="<?= htmlspecialchars($user_avatar) ?>" alt="Profil" class="w-9 h-9 rounded-full object-cover border-2 border-white shadow-sm group-hover:ring-2 group-hover:ring-amber-500/50 transition-all cursor-pointer bg-slate-100">
                        <?php else: ?>
                        <div class="w-9 h-9 rounded-full bg-slate-100 flex items-center justify-center text-slate-600 font-bold border border-slate-200 shadow-sm group-hover:ring-2 group-hover:ring-amber-500/50 transition-all cursor-pointer">
                        <?= htmlspecialchars($inisial_user) ?>
                        </div>
                        <?php endif; ?>
                    </button>
                    
                    <div id="profileMenu" class="hidden absolute right-0 mt-3 w-48 bg-white rounded-lg shadow-xl border border-slate-200 z-50 py-1.5 overflow-hidden transform transition-all" onclick="event.stopPropagation()">
                        <div class="px-4 py-2 border-b border-slate-100 bg-slate-50">
                            <p class="text-[10px] uppercase font-bold tracking-wider text-slate-400 mb-0.5">Signed in as</p>
                            <p class="text-sm font-semibold text-slate-800 truncate">
                                <?= htmlspecialchars($username_session) ?>
                            </p>
                        </div>
                        <a href="edit_profil.php" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-slate-600 hover:bg-orange-50 hover:text-[#F59E0B] font-medium transition-colors">
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

        <div class="p-6 flex flex-col flex-1 min-h-0 relative overflow-y-auto">
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5 shrink-0">
                <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Master SKU</p>
                        <h3 class="text-2xl font-bold text-slate-800 mt-1"><?= number_format($kpiTotal, 0, ',', '.') ?></h3>
                    </div>
                    <div class="w-12 h-12 bg-orange-50 text-[#F59E0B] rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                    </div>
                </div>
                <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Peringatan Stok Menipis</p>
                        <h3 class="text-2xl font-bold text-red-600 mt-1"><?= number_format($kpiAlert, 0, ',', '.') ?></h3>
                    </div>
                    <div class="w-12 h-12 bg-red-50 text-red-500 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                </div>
                <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Kategori</p>
                        <h3 class="text-2xl font-bold text-emerald-600 mt-1"><?= number_format($kpiKategori, 0, ',', '.') ?></h3>
                    </div>
                    <div class="w-12 h-12 bg-emerald-50 text-emerald-500 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                    </div>
                </div>
            </div>

            <div class="mb-4 shrink-0 bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col md:flex-row justify-between items-center gap-4">
                
                <div class="flex flex-col md:flex-row items-center gap-3 w-full md:w-auto">
                    <div class="relative w-full md:w-64">
                        <span class="absolute left-3 top-2.5 text-slate-400"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg></span>
                        <input type="text" id="globalSearchInput" placeholder="Cari SKU atau Nama..." class="w-full pl-9 pr-3 py-2 border border-slate-300 rounded-lg text-sm focus:border-[#F59E0B] outline-none transition-all focus:ring-1 focus:ring-orange-200">
                    </div>
                    
                    <div class="flex bg-slate-100 p-1 rounded-lg border border-slate-200 w-full md:w-auto">
                        <button onclick="quickFilter('')" id="qf_all" class="qf-btn px-4 py-1.5 rounded-md text-xs font-bold bg-white shadow-sm text-slate-700 transition">Semua</button>
                        <button onclick="quickFilter('low_stock')" id="qf_low_stock" class="qf-btn px-4 py-1.5 rounded-md text-xs font-bold text-slate-500 hover:text-slate-700 transition">Stok Kritis</button>
                    </div>

                    <span id="loadingIndicator" class="hidden text-xs font-bold text-orange-600 animate-pulse bg-orange-50 px-3 py-1.5 rounded-full border border-orange-100">Syncing database...</span>
                </div>

                <div class="flex items-center gap-2 w-full md:w-auto overflow-x-auto pb-1 md:pb-0">
                    <div id="bulkActionBar" class="hidden flex items-center gap-2 mr-2 border-r pr-4 border-slate-200">
                        <span class="text-xs font-bold text-slate-500"><span id="selectedCount">0</span> Terpilih</span>
                        <button onclick="bulkDelete()" class="p-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition tooltip" title="Hapus Massal">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </button>
                    </div>

                    <button onclick="clearAllFilters()" class="px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-bold hover:bg-slate-50 transition shadow-sm text-slate-600">Reset</button>
                    <button onclick="openModal('exportModal')" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold border border-slate-300 flex items-center gap-1.5 transition shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg> Ekspor
                    </button>
                    <button onclick="openModal('addModal')" class="px-4 py-2 bg-[#F59E0B] hover:bg-orange-600 text-white rounded-lg text-xs font-bold shadow-md flex items-center gap-1.5 transition whitespace-nowrap">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg> Tambah Barang
                    </button>
                </div>
            </div>

            <div class="bg-white border border-slate-200 shadow-sm overflow-auto flex-grow rounded-t-lg relative" onscroll="closeFilterPopup(event)">
                <table class="w-full border-collapse text-sm whitespace-nowrap min-w-full">
                    <thead class="sticky top-0 z-20 shadow-sm">
                        <tr class="bg-slate-100 text-slate-600 uppercase tracking-wider text-[11px] font-bold">
                            <th class="border-b border-r border-slate-200 px-3 py-3 bg-slate-50 sticky left-0 z-30 w-10 text-center">
                                <input type="checkbox" id="selectAllCheckbox" onclick="toggleSelectAll()" class="bulk-checkbox rounded border-slate-300">
                            </th>
                            <th class="border-b border-r border-slate-200 px-3 py-3 bg-slate-50 sticky left-[40px] z-30 w-28 text-center">Aksi</th>
                            <th class="border-b border-r border-slate-200 px-3 py-3 bg-slate-50 text-center w-12">No.</th>
                            <?php foreach ($kolom_database as $kolom): ?>
                                <th class="border-b border-r border-slate-200 px-4 py-3 bg-slate-50 hover:bg-slate-200 cursor-pointer group transition-colors column-header" data-colname="<?= $kolom ?>" onclick="toggleFilterPopup(event, '<?= $kolom ?>')">
                                    <div class="flex items-center justify-between gap-3">
                                        <span><?= htmlspecialchars(str_replace('_', ' ', $kolom)) ?></span>
                                        <div class="flex items-center gap-1">
                                            <span id="sort-icon-<?= $kolom ?>" class="text-[9px] text-orange-600 font-bold hidden"></span>
                                            <svg class="w-3.5 h-3.5 text-slate-400 opacity-0 group-hover:opacity-100 transition-opacity" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                                        </div>
                                    </div>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody id="tableBody" class="divide-y divide-slate-100 text-slate-600 relative"></tbody>
                </table>
            </div>

            <div class="bg-white border-x border-b border-slate-200 rounded-b-lg p-3.5 flex justify-between items-center shadow-sm shrink-0">
                <div class="flex items-center gap-3 text-sm text-slate-600 font-medium">
                    <span>Tampilkan:</span>
                    <select id="limitSelect" onchange="changeLimit()" class="border rounded-md px-2 py-1.5 outline-none text-[13px] font-medium text-slate-700 bg-white shadow-sm focus:border-[#F59E0B] focus:ring-1 focus:ring-orange-200">
                        <option value="50">50 Baris</option>
                        <option value="100" selected>100 Baris</option>
                        <option value="500">500 Baris</option>
                    </select>
                    <span id="dataInfoText" class="text-slate-500 text-[13px] ml-2">Menghitung data...</span>
                </div>
                <div id="paginationContainer" class="flex gap-1.5 items-center"></div>
            </div>
        </div>
    </main>

    <div id="filterPopup" class="hidden absolute bg-white border border-slate-200 shadow-2xl rounded-lg w-64 z-50 flex flex-col text-sm overflow-hidden" onclick="event.stopPropagation()">
        <div class="flex flex-col border-b bg-slate-50 p-2">
            <button onclick="applySort('ASC')" class="flex items-center gap-2.5 px-3 py-2 text-slate-700 hover:bg-slate-200 rounded text-xs font-semibold w-full transition-colors">Urutkan A ke Z</button>
            <button onclick="applySort('DESC')" class="flex items-center gap-2.5 px-3 py-2 text-slate-700 hover:bg-slate-200 rounded text-xs font-semibold w-full transition-colors">Urutkan Z ke A</button>
        </div>
        <div class="p-3 border-b">
            <input type="text" id="popupSearchInput" onkeyup="searchInsidePopup()" placeholder="Cari filter..." class="w-full px-2 py-1.5 border rounded-md text-xs outline-none focus:border-[#F59E0B]">
            <div class="flex gap-3 mt-3 text-[#F59E0B] font-bold text-[10px] uppercase tracking-wider">
                <button onclick="toggleAllCheckboxes(true)" class="hover:underline">Pilih semua</button>
                <span class="text-slate-300">|</span>
                <button onclick="toggleAllCheckboxes(false)" class="hover:underline">Kosongkan</button>
            </div>
        </div>
        <div id="filterCheckboxContainer" class="max-h-48 overflow-y-auto p-2 space-y-1"></div>
        <div class="p-2.5 flex justify-end gap-1.5 bg-slate-50">
            <button onclick="closeFilterPopup()" class="px-3 py-1.5 border bg-white rounded-md text-xs font-bold text-slate-500 hover:bg-slate-100">Batal</button>
            <button onclick="applyFilterFromPopup()" class="px-4 py-1.5 bg-[#F59E0B] text-white rounded-md text-xs font-bold hover:bg-orange-600 shadow-sm">Simpan</button>
        </div>
    </div>

    <div id="addModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm modal-overlay" onclick="closeModal('addModal')"></div>
        <form id="addForm" onsubmit="submitAction(event, 'add')" class="bg-white rounded-xl shadow-2xl w-full max-w-md relative z-10 modal-box transform scale-95 opacity-0">
            <div class="px-6 py-4 border-b flex justify-between items-center bg-slate-50 rounded-t-xl">
                <h3 class="font-bold text-slate-800">Tambah Data Master Barang</h3>
                <button type="button" onclick="closeModal('addModal')" class="text-slate-400 hover:text-red-500 transition text-xl font-bold">✕</button>
            </div>
            <div class="p-6 space-y-4">
                <div class="space-y-1">
                    <label class="text-[11px] font-bold text-slate-500 uppercase">Nama Barang <span class="text-red-500">*</span></label>
                    <input type="text" name="nama_barang" class="w-full border p-2.5 rounded-lg text-sm outline-none focus:border-[#F59E0B]" placeholder="Contoh: Fulka 1010ml" required>
                </div>
                <div class="space-y-1">
                    <label class="text-[11px] font-bold text-slate-500 uppercase">SKU (Kode Barang) <span class="text-red-500">*</span></label>
                    <input type="text" name="sku" class="w-full border p-2.5 rounded-lg text-sm outline-none focus:border-[#F59E0B]" placeholder="FLK-1010" required>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="text-[11px] font-bold text-slate-500 uppercase">Kategori <span class="text-red-500">*</span></label>
                        <select name="kategori" class="w-full border p-2.5 rounded-lg text-sm outline-none focus:border-[#F59E0B] bg-white">
                            <option value="Produk Jadi">Produk Jadi</option>
                            <option value="Bahan Baku">Bahan Baku</option>
                            <option value="Packaging">Packaging</option>
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label class="text-[11px] font-bold text-slate-500 uppercase">Satuan <span class="text-red-500">*</span></label>
                        <input type="text" name="satuan" placeholder="Box / Kg / Pcs" class="w-full border p-2.5 rounded-lg text-sm outline-none focus:border-[#F59E0B]" required>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="text-[11px] font-bold text-slate-500 uppercase">Min. Stok (Alert) <span class="text-red-500">*</span></label>
                        <input type="number" step="0.01" name="min_stock" class="w-full border p-2.5 rounded-lg text-sm outline-none focus:border-[#F59E0B]" placeholder="0" required>
                    </div>
                    <div class="space-y-1">
                        <label class="text-[11px] font-bold text-slate-500 uppercase">Current Stock (Awal)</label>
                        <input type="number" step="0.01" name="current_stock" class="w-full border p-2.5 rounded-lg text-sm outline-none focus:border-[#F59E0B]" placeholder="Boleh Kosong">
                    </div>
                </div>
            </div>
            <div class="px-6 py-4 bg-slate-50 flex justify-end gap-3 rounded-b-xl border-t shrink-0">
                <button type="button" onclick="closeModal('addModal')" class="px-5 py-2.5 text-xs font-bold text-slate-500 hover:bg-slate-200 rounded-lg transition">Batal</button>
                <button type="submit" class="px-6 py-2.5 bg-[#F59E0B] text-white rounded-lg text-xs font-bold hover:bg-orange-600 shadow-md transition">Simpan Barang</button>
            </div>
        </form>
    </div>

    <div id="editModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm modal-overlay" onclick="closeModal('editModal')"></div>
        <form id="editForm" onsubmit="submitAction(event, 'edit')" class="bg-white rounded-xl shadow-2xl w-full max-w-md relative z-10 modal-box transform scale-95 opacity-0">
            <input type="hidden" name="sku_lama" id="editSKUHid">
            <div class="px-6 py-4 border-b flex justify-between items-center bg-slate-50 rounded-t-xl">
                <h3 class="font-bold text-slate-800">Edit Data Master Barang</h3>
                <button type="button" onclick="closeModal('editModal')" class="text-slate-400 hover:text-red-500 transition text-xl font-bold">✕</button>
            </div>
            <div class="p-6 space-y-4">
                <div class="space-y-1">
                    <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">SKU (Kode Tetap)</label>
                    <input type="text" id="editSKUDisplay" class="w-full border p-2.5 rounded-lg text-sm bg-slate-100 text-slate-500 font-mono" readonly>
                </div>
                <div class="space-y-1">
                    <label class="text-[11px] font-bold text-slate-500 uppercase">Nama Barang</label>
                    <input type="text" name="nama_barang" id="editNama" class="w-full border p-2.5 rounded-lg text-sm outline-none focus:border-[#F59E0B]" required>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="text-[11px] font-bold text-slate-500 uppercase">Kategori</label>
                        <select name="kategori" id="editKat" class="w-full border p-2.5 rounded-lg text-sm outline-none focus:border-[#F59E0B] bg-white">
                            <option value="Produk Jadi">Produk Jadi</option>
                            <option value="Bahan Baku">Bahan Baku</option>
                            <option value="Packaging">Packaging</option>
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label class="text-[11px] font-bold text-slate-500 uppercase">Satuan</label>
                        <input type="text" name="satuan" id="editSatuan" class="w-full border p-2.5 rounded-lg text-sm outline-none focus:border-[#F59E0B]" required>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="text-[11px] font-bold text-slate-500 uppercase">Min. Stock</label>
                        <input type="number" step="0.01" name="min_stock" id="editMin" class="w-full border p-2.5 rounded-lg text-sm outline-none focus:border-[#F59E0B]" required>
                    </div>
                    <div class="space-y-1">
                        <label class="text-[11px] font-bold text-slate-500 uppercase">Current Stock</label>
                        <input type="number" step="0.01" name="current_stock" id="editCurrent" class="w-full border p-2.5 rounded-lg text-sm outline-none focus:border-[#F59E0B]">
                    </div>
                </div>
            </div>
            <div class="px-6 py-4 bg-slate-50 flex justify-end gap-3 rounded-b-xl border-t shrink-0">
                <button type="button" onclick="closeModal('editModal')" class="px-5 py-2.5 text-xs font-bold text-slate-500 hover:bg-slate-200 rounded-lg transition">Batal</button>
                <button type="submit" class="px-6 py-2.5 bg-[#F59E0B] text-white rounded-lg text-xs font-bold hover:bg-orange-600 shadow-md transition">Update Data</button>
            </div>
        </form>
    </div>

    <div id="exportModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm modal-overlay" onclick="closeModal('exportModal')"></div>
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm relative z-10 modal-box transform scale-95 opacity-0 overflow-hidden">
            <div class="px-6 py-4 border-b flex justify-between items-center bg-white">
                <h3 class="font-bold text-slate-800">Ekspor Laporan Master</h3>
                <button onclick="closeModal('exportModal')" class="text-slate-400 hover:text-red-500 text-lg transition">✕</button>
            </div>
            <div class="p-6 space-y-3">
                <button onclick="window.location.href='export_inventory.php?type=excel'" class="w-full flex items-center gap-4 p-4 border rounded-xl hover:border-emerald-500 hover:bg-emerald-50 transition group bg-white shadow-sm hover:shadow-md">
                    <div class="bg-emerald-100 text-emerald-600 p-3 rounded-lg group-hover:bg-emerald-200 font-bold tracking-wider">XLSX</div>
                    <div class="text-left">
                        <p class="font-bold text-sm text-slate-800 group-hover:text-emerald-700">Excel Spreadsheet</p>
                        <p class="text-xs text-slate-400">Cocok untuk olah data internal</p>
                    </div>
                </button>
                <button onclick="window.location.href='export_inventory.php?type=pdf'" class="w-full flex items-center gap-4 p-4 border rounded-xl hover:border-red-500 hover:bg-red-50 transition group bg-white shadow-sm hover:shadow-md">
                    <div class="bg-red-100 text-red-600 p-3 rounded-lg group-hover:bg-red-200 font-bold tracking-wider">PDF</div>
                    <div class="text-left">
                        <p class="font-bold text-sm text-slate-800 group-hover:text-red-700">Portable Document</p>
                        <p class="text-xs text-slate-400">Dokumen formal siap cetak</p>
                    </div>
                </button>
            </div>
        </div>
    </div>

    <script>
        let currentState = { page: 1, limit: 100, search: '', filters: {}, sortField: '', sortOrder: '', quickFilter: '' };
        let columnsList = <?php echo json_encode($kolom_database); ?>;
        let currentEditingColumn = null;
        let selectedRows = [];

        // FUNGSI JAVASCRIPT UNTUK PROFILE DROPDOWN MENU
        function toggleProfileMenu(event) {
            if(event) event.stopPropagation();
            const menu = document.getElementById('profileMenu');
            menu.classList.toggle('hidden');
        }

        function closeProfileMenu(event) {
            const menu = document.getElementById('profileMenu');
            if (menu && !menu.classList.contains('hidden')) {
                menu.classList.add('hidden');
            }
        }

        // AJAX Process untuk CRUD
        async function submitAction(e, action) {
            e.preventDefault();
            const formData = new FormData(e.target);
            formData.append('action', action);

            try {
                const response = await fetch('proses_inventory.php', { method: 'POST', body: formData });
                const res = await response.json();
                
                if(res.status === 'success') {
                    Swal.fire({ icon: 'success', title: 'Berhasil!', text: res.message, confirmButtonColor: '#F59E0B' })
                    .then(() => { 
                        closeModal(action + 'Modal'); 
                        loadTableData(); 
                        e.target.reset(); 
                        setTimeout(() => location.reload(), 1000); // Reload pelan untuk update KPI angka atas
                    });
                } else { Swal.fire('Gagal!', res.message, 'error'); }
            } catch (err) { Swal.fire('Error!', 'Terjadi kesalahan sistem.', 'error'); }
        }

        async function loadTableData() {
            document.getElementById('loadingIndicator').classList.remove('hidden');
            try {
                const response = await fetch('ajax_inventory.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'get_data', ...currentState })
                });
                let res = await response.json();
                
                // PENTING: Logika Filter Stok Kritis (Dijalankan di Front-End agar tidak perlu bongkar backend)
                if (currentState.quickFilter === 'low_stock') {
                    res.data = res.data.filter(row => {
                        // FIX: Ubah parseInt jadi parseFloat
                        let stock = parseFloat(row.current_stock || 0);
                        let min = parseFloat(row.min_stock || 0);
                        return stock <= min;
                    });
                    // Total data sementara di-override dengan data hasil filter
                    res.totalData = res.data.length; 
                }

                // Reset select all checkbox
                document.getElementById('selectAllCheckbox').checked = false;
                selectedRows = [];
                toggleBulkActionBar();

                renderTable(res.data);
                renderPagination(res.totalPages, res.totalData);
                updateSortHeaderIcons();
            } catch (error) { console.error("Fetch Error:", error); }
            document.getElementById('loadingIndicator').classList.add('hidden');
        }

        function renderTable(data) {
            const tbody = document.getElementById('tableBody');
            if (!data || data.length === 0) { 
                tbody.innerHTML = `<tr><td colspan="${columnsList.length + 3}" class="p-12 text-center text-slate-400 font-medium bg-slate-50">Data tidak ditemukan.</td></tr>`; 
                return; 
            }
            
            let html = '';
            let startIdx = (currentState.page - 1) * currentState.limit;
            data.forEach((row, idx) => {
                let skuId = row.sku || idx; 
                html += `<tr class="hover:bg-slate-50 group transition-colors">
                    <td class="border-b border-r px-3 py-2 bg-white sticky left-0 z-10 text-center shadow-[2px_0_5px_rgba(0,0,0,0.02)]">
                        <input type="checkbox" class="bulk-checkbox rounded border-slate-300 row-checkbox" value="${skuId}" onchange="toggleRowSelection(this)">
                    </td>
                    <td class="border-b border-r px-3 py-2 bg-white sticky left-[40px] z-10 text-center shadow-[2px_0_5px_rgba(0,0,0,0.02)]">
                        <div class="flex items-center justify-center gap-1.5">
                            <button onclick='fillEditModal(${JSON.stringify(row).replace(/'/g, "\\'")})' class="bg-orange-50 text-[#F59E0B] px-2.5 py-1.5 rounded-md text-[11px] font-semibold hover:bg-orange-100 transition">EDIT</button>
                            <button onclick="confirmDelete('${skuId}', '${row.nama_barang}')" class="bg-[#FEF2F2] text-[#DC2626] px-2.5 py-1.5 rounded-md text-[11px] font-semibold hover:bg-red-100 transition">DEL</button>
                        </div>
                    </td>
                    <td class="border-b border-r px-3 py-2 text-center text-slate-400 text-xs font-bold">${startIdx + idx + 1}</td>`;
                
                columnsList.forEach(col => {
                    let val = row[col] || '';
                    let cellStyle = '';
                    
                    if(col === 'current_stock' || col === 'min_stock') {
                        // FIX: Format jadi maksimal 2 desimal di layar depan (kalau 0 di belakang koma, hilang)
                        val = parseFloat(val).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
                        
                        if (col === 'current_stock') {
                            let stock = parseFloat(row['current_stock'] || 0);
                            let min = parseFloat(row['min_stock'] || 0);
                            if(stock <= min) cellStyle = 'bg-red-50 text-red-600 font-bold'; 
                            else cellStyle = 'font-semibold text-slate-700';
                        }
                    } else if(col === 'sku') {
                        cellStyle = 'font-mono text-[11px] text-[#F59E0B] font-bold';
                    }

                    html += `<td class="border-b border-r px-4 py-2 ${cellStyle}">${val}</td>`;
                });
                html += `</tr>`;
            });
            tbody.innerHTML = html;
        }

        // FUNGSI: Bulk Actions
        function toggleSelectAll() {
            const isChecked = document.getElementById('selectAllCheckbox').checked;
            const checkboxes = document.querySelectorAll('.row-checkbox');
            selectedRows = [];
            checkboxes.forEach(cb => {
                cb.checked = isChecked;
                if(isChecked) selectedRows.push(cb.value);
            });
            toggleBulkActionBar();
        }

        function toggleRowSelection(cb) {
            if(cb.checked) {
                selectedRows.push(cb.value);
            } else {
                selectedRows = selectedRows.filter(val => val !== cb.value);
                document.getElementById('selectAllCheckbox').checked = false;
            }
            toggleBulkActionBar();
        }

        function toggleBulkActionBar() {
            const bar = document.getElementById('bulkActionBar');
            document.getElementById('selectedCount').innerText = selectedRows.length;
            if(selectedRows.length > 0) bar.classList.remove('hidden');
            else bar.classList.add('hidden');
        }

        function bulkDelete() {
            Swal.fire({
                title: 'Hapus Massal?',
                text: `${selectedRows.length} barang terpilih akan dihapus permanen!`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'Ya, Hapus Semua!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire('Fitur Terkunci', 'Fungsi hapus massal masih dalam simulasi.', 'info');
                }
            });
        }

        // FUNGSI: Quick Filter Tab
        function quickFilter(type) {
            document.querySelectorAll('.qf-btn').forEach(btn => {
                btn.classList.remove('bg-white', 'shadow-sm', 'text-slate-700');
                btn.classList.add('text-slate-500');
            });
            
            if(type === '') {
                document.getElementById('qf_all').classList.add('bg-white', 'shadow-sm', 'text-slate-700');
            } else if(type === 'low_stock') {
                document.getElementById('qf_low_stock').classList.add('bg-white', 'shadow-sm', 'text-slate-700');
            }

            currentState.quickFilter = type;
            currentState.page = 1;
            loadTableData();
        }

        // FOOTER PAGINATION
        function renderPagination(totalPages, totalData) {
            const container = document.getElementById('paginationContainer');
            
            // Jika Quick filter aktif, kita atur tampilannya agar rapi
            let isQuickFiltered = currentState.quickFilter !== '';

            let startItem = totalData === 0 ? 0 : ((currentState.page - 1) * currentState.limit) + 1;
            let endItem = Math.min(currentState.page * currentState.limit, totalData);
            let totalDataFormatted = new Intl.NumberFormat('id-ID').format(totalData);
            document.getElementById('dataInfoText').innerHTML = `<span class="text-slate-500">${startItem} - ${endItem} dari ${totalDataFormatted} total</span>`;

            let html = '';
            
            if(!isQuickFiltered) {
                let prevDisabled = currentState.page === 1 ? 'opacity-40 cursor-not-allowed' : 'hover:bg-slate-100 cursor-pointer';
                let prevAction = currentState.page === 1 ? '' : `onclick="goToPage(${currentState.page - 1})"`;
                html += `<button ${prevAction} class="w-8 h-8 flex items-center justify-center border border-slate-200 rounded text-slate-500 bg-white transition-colors shadow-sm mx-0.5 ${prevDisabled}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                         </button>`;

                let startPage = Math.max(1, currentState.page - 2);
                let endPage = Math.min(totalPages, startPage + 4);
                if (endPage - startPage < 4) startPage = Math.max(1, endPage - 4);

                for (let i = startPage; i <= endPage; i++) {
                    let active = i === currentState.page 
                        ? 'bg-[#F59E0B] text-white border-[#F59E0B] shadow-md font-bold' 
                        : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50 font-medium';
                    html += `<button onclick="goToPage(${i})" class="min-w-[32px] h-8 px-2 border rounded text-sm transition-all shadow-sm mx-0.5 ${active}">${i}</button>`;
                }

                if(totalPages > endPage) {
                    html += `<span class="px-2 text-slate-400">...</span>`;
                    html += `<button onclick="goToPage(${totalPages})" class="min-w-[32px] h-8 px-2 border rounded text-sm transition-all shadow-sm mx-0.5 bg-white text-slate-700 border-slate-200 hover:bg-slate-50 font-medium">${totalPages}</button>`;
                }

                let nextDisabled = currentState.page === totalPages || totalPages === 0 ? 'opacity-40 cursor-not-allowed' : 'hover:bg-slate-100 cursor-pointer';
                let nextAction = currentState.page === totalPages || totalPages === 0 ? '' : `onclick="goToPage(${currentState.page + 1})"`;
                html += `<button ${nextAction} class="w-8 h-8 flex items-center justify-center border border-slate-200 rounded text-slate-500 bg-white transition-colors shadow-sm mx-0.5 ${nextDisabled}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                         </button>`;
            }

            container.innerHTML = html;
        }

        // UTILITIES
        function fillEditModal(data) {
            document.getElementById('editSKUHid').value = data.sku;
            document.getElementById('editSKUDisplay').value = data.sku;
            document.getElementById('editNama').value = data.nama_barang;
            document.getElementById('editKat').value = data.kategori;
            // FIX: Parsing Float saat masukin data ke modal edit
            document.getElementById('editMin').value = parseFloat(data.min_stock);
            document.getElementById('editCurrent').value = parseFloat(data.current_stock);
            document.getElementById('editSatuan').value = data.satuan;
            openModal('editModal');
        }

        function confirmDelete(sku, nama) {
            Swal.fire({
                title: 'Hapus Master Data?',
                text: `Data "${nama}" (SKU: ${sku}) akan hilang permanen dari sistem ERP!`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    const fd = new FormData(); fd.append('action', 'delete'); fd.append('sku', sku);
                    const res = await fetch('proses_inventory.php', { method: 'POST', body: fd }).then(r => r.json());
                    if(res.status === 'success') {
                        Swal.fire('Terhapus!', 'Barang berhasil dihapus.', 'success').then(() => {
                            loadTableData();
                            setTimeout(() => location.reload(), 1000); // Reload agar KPI terupdate
                        });
                    }
                }
            });
        }

        async function toggleFilterPopup(event, colName) {
            if(event) event.stopPropagation();
            const popup = document.getElementById('filterPopup');
            
            // Jika mengklik header kolom yang sama, tutup filterno
            if (currentEditingColumn === colName && !popup.classList.contains('hidden')) {
                popup.classList.add('hidden');
                return;
            }

            currentEditingColumn = colName;
            const rect = event.currentTarget.getBoundingClientRect();
            popup.style.top = (rect.bottom + 5) + 'px'; popup.style.left = Math.min(rect.left, window.innerWidth - 280) + 'px';
            popup.classList.remove('hidden');
            
            const container = document.getElementById('filterCheckboxContainer');
            container.innerHTML = '<div class="p-6 text-center animate-pulse text-xs text-slate-400">Memuat filter...</div>';
            
            const res = await fetch('ajax_inventory.php', { 
                method: 'POST', 
                body: JSON.stringify({ action: 'get_distinct', column: colName }) 
            }).then(r => r.json());
            
            container.innerHTML = res.map(v => `
                <div class="flex items-center gap-2 p-1.5 hover:bg-slate-50 rounded cursor-pointer popup-item">
                    <input type="checkbox" value="${v}" class="popup-cb accent-[#F59E0B] w-4 h-4" checked>
                    <label class="text-xs font-medium text-slate-600 truncate">${v || '(Kosong)'}</label>
                </div>`).join('');
        }

        // FIX: Fungsi Pilih Semua & Kosongkan Centang Filter
        function toggleAllCheckboxes(state) {
            document.querySelectorAll('.popup-cb').forEach(cb => {
                // Hanya centang yang terlihat (kalau lagi di-search)
                if(cb.closest('.popup-item').style.display !== 'none') {
                    cb.checked = state;
                }
            });
        }

        // FIX: Fungsi Cari dalam Popup
        function searchInsidePopup() {
            let input = document.getElementById('popupSearchInput').value.toLowerCase();
            document.querySelectorAll('#filterCheckboxContainer .popup-item').forEach(div => {
                let label = div.querySelector('label').innerText.toLowerCase();
                if (label.includes(input)) {
                    div.style.display = 'flex';
                } else {
                    div.style.display = 'none';
                }
            });
        }

        function applyFilterFromPopup() {
            let checked = []; 
            document.querySelectorAll('.popup-cb:checked').forEach(cb => checked.push(cb.value));
            
            // FIX: Fleksibel - Jika gak ada yang dicentang, hapus filter (kembali tampilkan semua)
            if(checked.length === 0) {
                delete currentState.filters[currentEditingColumn];
            } else {
                currentState.filters[currentEditingColumn] = checked; 
            }
            
            closeFilterPopup(); 
            currentState.page = 1; 
            loadTableData();
        }

        function applySort(order) { currentState.sortField = currentEditingColumn; currentState.sortOrder = order; closeFilterPopup(); loadTableData(); }
        function goToPage(p) { currentState.page = p; loadTableData(); }
        function changeLimit() { currentState.limit = document.getElementById('limitSelect').value; currentState.page = 1; loadTableData(); }
        
        function openModal(id) { 
            const m = document.getElementById(id); m.classList.remove('hidden'); 
            setTimeout(() => m.querySelector('.modal-box').classList.add('scale-100', 'opacity-100'), 10); 
        }
        function closeModal(id) { 
            document.getElementById(id).querySelector('.modal-box').classList.remove('scale-100', 'opacity-100'); 
            setTimeout(() => document.getElementById(id).classList.add('hidden'), 200); 
        }
        
        function toggleSidebar() { document.getElementById('mainSidebar').classList.toggle('w-64'); document.getElementById('mainSidebar').classList.toggle('w-0'); }
        function closeFilterPopup(event) { 
            const popup = document.getElementById('filterPopup');
            if (popup && !popup.classList.contains('hidden')) {
                popup.classList.add('hidden');
            }
        }
        function clearAllFilters() { currentState.filters = {}; currentState.search = ''; currentState.sortField = ''; currentState.quickFilter = ''; quickFilter(''); loadTableData(); }
        
        let searchTimer;
        document.getElementById('globalSearchInput').addEventListener('keyup', function() {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => { currentState.search = this.value; currentState.page = 1; loadTableData(); }, 500);
        });

        function updateSortHeaderIcons() {
            columnsList.forEach(c => { 
                const el = document.getElementById(`sort-icon-${c}`);
                if(el) { el.classList.add('hidden'); el.innerText = ''; }
            });
            if(currentState.sortField) {
                const activeIcon = document.getElementById(`sort-icon-${currentState.sortField}`);
                if(activeIcon) {
                    activeIcon.classList.remove('hidden');
                    activeIcon.innerText = currentState.sortOrder === 'ASC' ? '↑' : '↓';
                }
            }
        }

        window.onload = loadTableData;
    </script>
</body>
</html>