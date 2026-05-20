<?php
session_start();
require 'koneksi.php'; 

// ==============================================================================
// 1. CEK SESSION & AMBIL DATA DARI tb_users (FOKUS KOLOM 'modul')
// ==============================================================================
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$username_session = $_SESSION['username'];
$nama_user = $_SESSION['nama'] ?? ($_SESSION['nama_lengkap'] ?? $username_session);

// FIX BUG: Sesuaikan nama session role dengan sistem ATASI
$user_modul = $_SESSION['modul_akses'] ?? ($_SESSION['modul'] ?? ''); 

$user_avatar = ''; // Siapkan variabel avatar

try {
    // Tambahkan pemanggilan kolom 'avatar' (dan 'modul' jika butuh)
    $stmtUser = $pdo->prepare("SELECT nama_lengkap, username, modul, avatar FROM tb_users WHERE username = ? LIMIT 1");
    $stmtUser->execute([$username_session]);
    $userData = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if ($userData) {
        $nama_user = $userData['nama_lengkap']; 
        
        // Pastikan role terisi dari database jika di session tidak terbaca
        if (empty($user_modul) && !empty($userData['modul'])) {
            $user_modul = $userData['modul'];
        }
        
        // Ambil avatar dari database. Jika kosong, panggil default avatar kartun dari DiceBear
        if (!empty($userData['avatar'])) {
            $user_avatar = $userData['avatar'];
        } else {
            $user_avatar = "https://api.dicebear.com/7.x/notionists/svg?seed=" . urlencode($userData['username']) . "&backgroundColor=e2e8f0";
        }
    }
} catch (PDOException $e) {}

$inisial_user = strtoupper(substr($nama_user, 0, 1));

// LOGIKA SUPERADMIN
$modul_clean = strtolower(str_replace(' ', '', $user_modul));
$is_superadmin = ($modul_clean === 'superadmin') ? 'true' : 'false';

// ==============================================================================
// 2. AMBIL DATA SKU UNTUK DROPDOWN FORM & KPI
// ==============================================================================
$list_barang = [];
try {
    $stmtBarang = $pdo->query("SELECT sku, nama_barang, satuan, current_stock FROM jurnal_transaksi_atasi.db_inventory ORDER BY nama_barang ASC");
    $list_barang = $stmtBarang->fetchAll(PDO::FETCH_ASSOC);

    // Hitung KPI dari db_inventory_out
    $kpiHariIni = $pdo->query("SELECT IFNULL(SUM(qty), 0) FROM jurnal_transaksi_atasi.db_inventory_out WHERE DATE(tanggal) = CURDATE()")->fetchColumn();
    $kpiBulanIni = $pdo->query("SELECT COUNT(*) FROM jurnal_transaksi_atasi.db_inventory_out WHERE MONTH(tanggal) = MONTH(CURDATE()) AND YEAR(tanggal) = YEAR(CURDATE())")->fetchColumn();
} catch (PDOException $e) {
    $kpiHariIni = 0; 
    $kpiBulanIni = 0;
}

// Bikin tampilan KPI dinamis (kalau ada desimal dimunculin, kalau bulat dihilangin)
$kpiHariIniDisplay = (floor($kpiHariIni) == $kpiHariIni) ? number_format($kpiHariIni, 0, ',', '.') : number_format($kpiHariIni, 2, ',', '.');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Out</title>
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
    </style>
</head>
<body class="bg-slate-50 font-sans text-slate-800 h-screen flex overflow-hidden" onclick="closeProfileMenu(event)">

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
                    <h1 class="text-xl font-bold text-slate-800">Stock Out</h1>
                    <div class="flex items-center gap-2 text-xs font-medium mt-0.5">
                        <span class="text-[#F59E0B]">ATASI</span><span class="text-slate-400">/</span><span class="text-slate-500">Inventory Out</span>
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
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5 shrink-0">
                <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Item Keluar Hari Ini</p>
                        <h3 class="text-2xl font-bold text-slate-800 mt-1"><?= $kpiHariIniDisplay ?> <span class="text-sm text-slate-500 font-medium">Pcs</span></h3>
                    </div>
                    <div class="w-12 h-12 bg-rose-50 text-rose-500 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 transform rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"></path></svg>
                    </div>
                </div>
                <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Transaksi OUT Bulan Ini</p>
                        <h3 class="text-2xl font-bold text-orange-600 mt-1"><?= number_format($kpiBulanIni, 0, ',', '.') ?> <span class="text-sm text-slate-500 font-medium">Trx</span></h3>
                    </div>
                    <div class="w-12 h-12 bg-orange-50 text-orange-500 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                    </div>
                </div>
            </div>

            <div class="mb-4 shrink-0 bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col md:flex-row justify-between items-center gap-4">
                <div class="flex flex-col md:flex-row items-center gap-3 w-full md:w-auto">
                    <div class="relative w-full md:w-56">
                        <span class="absolute left-3 top-2.5 text-slate-400"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg></span>
                        <input type="text" id="globalSearchInput" placeholder="Cari Dokumen / Barang..." class="w-full pl-9 pr-3 py-2 border border-slate-300 rounded-lg text-sm focus:border-[#F59E0B] outline-none transition-all focus:ring-1 focus:ring-orange-200">
                    </div>

                    <div class="flex flex-col md:flex-row items-center gap-2 w-full md:w-auto">
                        <select id="dateFilterPreset" onchange="handleDatePreset(this.value)" class="w-full md:w-44 px-3 py-2 border border-slate-300 rounded-lg text-sm focus:border-[#F59E0B] outline-none bg-white text-slate-600 font-medium">
                            <option value="today">Hari Ini</option>
                            <option value="7_days" selected>7 Hari Terakhir</option>
                            <option value="30_days">30 Hari Terakhir</option>
                            <option value="this_month">Bulan Ini</option>
                            <option value="this_quarter">Kuartal Ini</option>
                            <option value="this_year">Tahun Ini</option>
                            <option value="custom">Rentang Kustom</option>
                        </select>

                        <div id="customDateRange" class="hidden flex items-center gap-2">
                            <input type="date" id="startDate" class="px-2 py-2 border border-slate-300 rounded-lg text-xs focus:border-[#F59E0B] outline-none">
                            <span class="text-slate-400 font-bold">-</span>
                            <input type="date" id="endDate" class="px-2 py-2 border border-slate-300 rounded-lg text-xs focus:border-[#F59E0B] outline-none">
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2 w-full md:w-auto">
                    <button onclick="loadTableData()" class="px-3 py-2 bg-white border border-slate-300 rounded-lg text-xs font-bold hover:bg-slate-50 transition shadow-sm text-slate-600">Refresh</button>
                    <button onclick="openModal('addModal')" class="px-4 py-2 bg-rose-500 hover:bg-rose-600 text-white rounded-lg text-xs font-bold shadow-md flex items-center gap-1.5 transition whitespace-nowrap">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg> Catat Stock OUT
                    </button>
                </div>
            </div>

            <div class="bg-white border border-slate-200 shadow-sm overflow-auto flex-grow rounded-t-lg relative">
                <table class="w-full border-collapse text-sm whitespace-nowrap min-w-full">
                    <thead class="sticky top-0 z-20 shadow-sm">
                        <tr class="bg-slate-100 text-slate-600 uppercase tracking-wider text-[11px] font-bold">
                            <th class="border-b border-r border-slate-200 px-3 py-3 bg-slate-50 text-center w-12">No.</th>
                            <th class="border-b border-r border-slate-200 px-4 py-3 bg-slate-50 text-left">Tanggal</th>
                            <th class="border-b border-r border-slate-200 px-4 py-3 bg-slate-50 text-left">No. Referensi</th>
                            <th class="border-b border-r border-slate-200 px-4 py-3 bg-slate-50 text-left">SKU</th>
                            <th class="border-b border-r border-slate-200 px-4 py-3 bg-slate-50 text-left">Nama Barang</th>
                            <th class="border-b border-r border-slate-200 px-4 py-3 bg-slate-50 text-center">Qty Keluar</th>
                            <th class="border-b border-r border-slate-200 px-4 py-3 bg-slate-50 text-left">Penerima / Tujuan</th>
                            <th class="border-b border-slate-200 px-3 py-3 bg-slate-50 text-center w-28">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody" class="divide-y divide-slate-100 text-slate-600 relative">
                        </tbody>
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

    <div id="addModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm modal-overlay" onclick="closeModal('addModal')"></div>
        <form id="addForm" onsubmit="submitAction(event, 'add')" class="bg-white rounded-xl shadow-2xl w-full max-w-lg relative z-10 modal-box transform scale-95 opacity-0">
            <div class="px-6 py-4 border-b flex justify-between items-center bg-slate-50 rounded-t-xl">
                <h3 class="font-bold text-slate-800">Form Barang Keluar (Stock OUT)</h3>
                <button type="button" onclick="closeModal('addModal')" class="text-slate-400 hover:text-red-500 transition text-xl font-bold">✕</button>
            </div>
            <div class="p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="text-[11px] font-bold text-slate-500 uppercase">Tanggal Keluar <span class="text-red-500">*</span></label>
                        <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" class="w-full border p-2.5 rounded-lg text-sm outline-none focus:border-rose-400" required>
                    </div>
                    <div class="space-y-1">
                        <label class="text-[11px] font-bold text-slate-500 uppercase">No. Referensi / Surat Jalan</label>
                        <input type="text" name="referensi" class="w-full border p-2.5 rounded-lg text-sm outline-none focus:border-rose-400" placeholder="Cth: SJ-2026-05-001">
                    </div>
                </div>
                <div class="space-y-1">
                    <label class="text-[11px] font-bold text-slate-500 uppercase">Pilih Barang <span class="text-red-500">*</span></label>
                    <select name="sku" id="selectBarang" class="w-full border p-2.5 rounded-lg text-sm outline-none focus:border-rose-400 bg-white" required onchange="updateSatuan('add')">
                        <option value="">-- Pilih Barang dari Master Data --</option>
                        <?php foreach($list_barang as $brg): ?>
                            <?php $stok_display = (floor($brg['current_stock']) == $brg['current_stock']) ? $brg['current_stock'] : number_format($brg['current_stock'], 2, ',', '.'); ?>
                            <option value="<?= $brg['sku'] ?>" data-satuan="<?= htmlspecialchars($brg['satuan']) ?>">
                                <?= htmlspecialchars($brg['sku']) ?> - <?= htmlspecialchars($brg['nama_barang']) ?> (Stok: <?= $stok_display ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="text-[11px] font-bold text-slate-500 uppercase">Qty Keluar <span class="text-red-500">*</span></label>
                        <div class="flex">
                            <input type="number" name="qty" min="0.01" step="0.01" class="w-full border border-r-0 rounded-l-lg p-2.5 text-sm outline-none focus:border-rose-400" placeholder="0" required>
                            <span id="labelSatuanAdd" class="bg-slate-100 border border-slate-300 rounded-r-lg px-3 py-2.5 text-sm text-slate-500 font-semibold flex items-center">-</span>
                        </div>
                    </div>
                    <div class="space-y-1">
                        <label class="text-[11px] font-bold text-slate-500 uppercase">Penerima / Tujuan <span class="text-red-500">*</span></label>
                        <input type="text" name="penerima" class="w-full border p-2.5 rounded-lg text-sm outline-none focus:border-rose-400" placeholder="Cth: Divisi Produksi" required>
                    </div>
                </div>
                <div class="space-y-1">
                    <label class="text-[11px] font-bold text-slate-500 uppercase">Catatan / Keterangan</label>
                    <textarea name="keterangan" rows="2" class="w-full border p-2.5 rounded-lg text-sm outline-none focus:border-rose-400" placeholder="Keterangan untuk apa..."></textarea>
                </div>
            </div>
            <div class="px-6 py-4 bg-slate-50 flex justify-end gap-3 rounded-b-xl border-t shrink-0">
                <button type="button" onclick="closeModal('addModal')" class="px-5 py-2.5 text-xs font-bold text-slate-500 hover:bg-slate-200 rounded-lg transition">Batal</button>
                <button type="submit" class="px-6 py-2.5 bg-rose-500 text-white rounded-lg text-xs font-bold hover:bg-rose-600 shadow-md transition">Simpan Stock OUT</button>
            </div>
        </form>
    </div>

    <div id="editModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm modal-overlay" onclick="closeModal('editModal')"></div>
        <form id="editForm" onsubmit="submitAction(event, 'edit')" class="bg-white rounded-xl shadow-2xl w-full max-w-lg relative z-10 modal-box transform scale-95 opacity-0">
            <input type="hidden" name="id" id="edit_id">
            <div class="px-6 py-4 border-b flex justify-between items-center bg-slate-50 rounded-t-xl">
                <h3 class="font-bold text-slate-800 flex items-center gap-2"><svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg> Edit Transaksi OUT</h3>
                <button type="button" onclick="closeModal('editModal')" class="text-slate-400 hover:text-red-500 transition text-xl font-bold">✕</button>
            </div>
            <div class="p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="text-[11px] font-bold text-slate-500 uppercase">Tanggal Keluar <span class="text-red-500">*</span></label>
                        <input type="date" name="tanggal" id="edit_tanggal" class="w-full border p-2.5 rounded-lg text-sm outline-none focus:border-rose-400" required>
                    </div>
                    <div class="space-y-1">
                        <label class="text-[11px] font-bold text-slate-500 uppercase">No. Referensi</label>
                        <input type="text" name="referensi" id="edit_referensi" class="w-full border p-2.5 rounded-lg text-sm outline-none focus:border-rose-400">
                    </div>
                </div>
                <div class="space-y-1">
                    <label class="text-[11px] font-bold text-slate-500 uppercase">Barang <span class="text-red-500">*</span></label>
                    <select name="sku" id="edit_sku" class="w-full border p-2.5 rounded-lg text-sm outline-none focus:border-rose-400 bg-white" required onchange="updateSatuan('edit')">
                        <?php foreach($list_barang as $brg): ?>
                            <option value="<?= $brg['sku'] ?>" data-satuan="<?= htmlspecialchars($brg['satuan']) ?>">
                                <?= htmlspecialchars($brg['sku']) ?> - <?= htmlspecialchars($brg['nama_barang']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="text-[11px] font-bold text-slate-500 uppercase">Qty Keluar <span class="text-red-500">*</span></label>
                        <div class="flex">
                            <input type="number" name="qty" id="edit_qty" min="0.01" step="0.01" class="w-full border border-r-0 rounded-l-lg p-2.5 text-sm outline-none focus:border-rose-400" required>
                            <span id="labelSatuanEdit" class="bg-slate-100 border border-slate-300 rounded-r-lg px-3 py-2.5 text-sm text-slate-500 font-semibold flex items-center">-</span>
                        </div>
                    </div>
                    <div class="space-y-1">
                        <label class="text-[11px] font-bold text-slate-500 uppercase">Penerima / Tujuan <span class="text-red-500">*</span></label>
                        <input type="text" name="penerima" id="edit_penerima" class="w-full border p-2.5 rounded-lg text-sm outline-none focus:border-rose-400" required>
                    </div>
                </div>
                <div class="space-y-1">
                    <label class="text-[11px] font-bold text-slate-500 uppercase">Keterangan</label>
                    <textarea name="keterangan" id="edit_keterangan" rows="2" class="w-full border p-2.5 rounded-lg text-sm outline-none focus:border-rose-400"></textarea>
                </div>
            </div>
            <div class="px-6 py-4 bg-slate-50 flex justify-end gap-3 rounded-b-xl border-t shrink-0">
                <button type="button" onclick="closeModal('editModal')" class="px-5 py-2.5 text-xs font-bold text-slate-500 hover:bg-slate-200 rounded-lg transition">Batal</button>
                <button type="submit" class="px-6 py-2.5 bg-rose-500 text-white rounded-lg text-xs font-bold hover:bg-rose-600 shadow-md transition">Update Perubahan</button>
            </div>
        </form>
    </div>

    <script>
        // Set variabel flag Superadmin dari PHP ke Javascript
        const isSuperadmin = <?= $is_superadmin ?>;
        let currentState = { page: 1, limit: 100, search: '', datePreset: '7_days', startDate: '', endDate: '' };

        function toggleSidebar() { document.getElementById('mainSidebar').classList.toggle('w-64'); document.getElementById('mainSidebar').classList.toggle('w-0'); }
        function toggleProfileMenu(e) { if(e) e.stopPropagation(); document.getElementById('profileMenu').classList.toggle('hidden'); }
        function closeProfileMenu(e) { const m = document.getElementById('profileMenu'); if (m && !m.classList.contains('hidden')) m.classList.add('hidden'); }

        function openModal(id) { 
            const m = document.getElementById(id); m.classList.remove('hidden'); 
            setTimeout(() => m.querySelector('.modal-box').classList.add('scale-100', 'opacity-100'), 10); 
        }
        function closeModal(id) { 
            document.getElementById(id).querySelector('.modal-box').classList.remove('scale-100', 'opacity-100'); 
            setTimeout(() => document.getElementById(id).classList.add('hidden'), 200); 
        }

        function updateSatuan(type) {
            const selectId = type === 'add' ? 'selectBarang' : 'edit_sku';
            const labelId = type === 'add' ? 'labelSatuanAdd' : 'labelSatuanEdit';
            const select = document.getElementById(selectId);
            const satuan = select.options[select.selectedIndex].getAttribute('data-satuan');
            document.getElementById(labelId).innerText = satuan ? satuan : '-';
        }

        // --- FILTER & SEARCH EVENTS ---
        let searchTimer;
        document.getElementById('globalSearchInput').addEventListener('keyup', function() {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => { currentState.search = this.value; currentState.page = 1; loadTableData(); }, 500);
        });
        document.getElementById('startDate').addEventListener('change', function() { currentState.startDate = this.value; if (currentState.endDate !== '') { currentState.page = 1; loadTableData(); } });
        document.getElementById('endDate').addEventListener('change', function() { currentState.endDate = this.value; if (currentState.startDate !== '') { currentState.page = 1; loadTableData(); } });
        function handleDatePreset(val) {
            const customRange = document.getElementById('customDateRange');
            if (val === 'custom') customRange.classList.remove('hidden');
            else { customRange.classList.add('hidden'); currentState.datePreset = val; currentState.page = 1; loadTableData(); }
        }

        // --- PAGINATION & DATA LOAD ---
        function renderPagination(totalPages, totalData) {
            const container = document.getElementById('paginationContainer');
            let startItem = totalData === 0 ? 0 : ((currentState.page - 1) * currentState.limit) + 1;
            let endItem = Math.min(currentState.page * currentState.limit, totalData);
            document.getElementById('dataInfoText').innerHTML = `<span class="text-slate-500">${startItem} - ${endItem} dari ${totalData} total</span>`;

            let html = '';
            let prevDisabled = currentState.page === 1 ? 'opacity-40 cursor-not-allowed' : 'hover:bg-slate-100 cursor-pointer';
            let prevAction = currentState.page === 1 ? '' : `onclick="goToPage(${currentState.page - 1})"`;
            html += `<button ${prevAction} class="w-8 h-8 flex items-center justify-center border border-slate-200 rounded text-slate-500 bg-white transition-colors shadow-sm mx-0.5 ${prevDisabled}"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg></button>`;

            for (let i = 1; i <= totalPages; i++) {
                if (i === 1 || i === totalPages || (i >= currentState.page - 1 && i <= currentState.page + 1)) {
                    let active = i === currentState.page ? 'bg-[#F59E0B] text-white border-[#F59E0B] shadow-md font-bold' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50 font-medium';
                    html += `<button onclick="goToPage(${i})" class="min-w-[32px] h-8 px-2 border rounded text-sm transition-all shadow-sm mx-0.5 ${active}">${i}</button>`;
                } else if (i === currentState.page - 2 || i === currentState.page + 2) html += `<span class="px-1 text-slate-400">...</span>`;
            }

            let nextDisabled = currentState.page === totalPages || totalPages === 0 ? 'opacity-40 cursor-not-allowed' : 'hover:bg-slate-100 cursor-pointer';
            let nextAction = currentState.page === totalPages || totalPages === 0 ? '' : `onclick="goToPage(${currentState.page + 1})"`;
            html += `<button ${nextAction} class="w-8 h-8 flex items-center justify-center border border-slate-200 rounded text-slate-500 bg-white transition-colors shadow-sm mx-0.5 ${nextDisabled}"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg></button>`;
            container.innerHTML = html;
        }

        function goToPage(p) { currentState.page = p; loadTableData(); }
        function changeLimit() { currentState.limit = document.getElementById('limitSelect').value; currentState.page = 1; loadTableData(); }

        // --- AJAX FETCH KE SERVER ---
        async function loadTableData() {
            const tbody = document.getElementById('tableBody');
            tbody.innerHTML = `<tr><td colspan="8" class="p-12 text-center text-slate-400 font-medium bg-slate-50 animate-pulse">Menarik data transaksi OUT...</td></tr>`;
            
            try {
                // Endpoint diubah ke ajax_inventory_out.php
                const response = await fetch('ajax_inventory_out.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(currentState)
                });
                
                const res = await response.json();
                
                if (res.data && res.data.length > 0) {
                    let html = '';
                    let startIdx = (currentState.page - 1) * currentState.limit;
                    
                    res.data.forEach((row, idx) => {
                        let rowNum = startIdx + idx + 1;
                        
                        let actionBtn = '';
                        if (isSuperadmin) {
                            actionBtn = `
                                <div class="flex items-center justify-center gap-1.5">
                                    <button onclick='fillEditModal(${JSON.stringify(row).replace(/'/g, "\\'")})' class="bg-orange-50 text-[#F59E0B] px-2.5 py-1.5 rounded-md text-[11px] font-semibold hover:bg-orange-100 transition">EDIT</button>
                                    <button onclick="confirmDelete('${row.id}')" class="bg-[#FEF2F2] text-[#DC2626] px-2.5 py-1.5 rounded-md text-[11px] font-semibold hover:bg-red-100 transition">DEL</button>
                                </div>
                            `;
                        } else {
                            actionBtn = `<button class="bg-slate-100 text-slate-400 px-2.5 py-1.5 rounded text-[10px] font-bold cursor-not-allowed tooltip" title="Akses Dibatasi (Khusus Superadmin)">LOCKED</button>`;
                        }

                        // MODIF TAMPILAN: Parse parseFloat untuk memformat angka dengan 2 desimal maksimum jika ada
                        let formattedQty = parseFloat(row.qty).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 });

                        html += `<tr class="hover:bg-slate-50 group transition-colors">
                            <td class="border-b border-r px-3 py-2 bg-white text-center text-slate-400 text-xs font-bold">${rowNum}</td>
                            <td class="border-b border-r px-4 py-2 text-slate-600 font-medium">${row.tanggal.split(' ')[0]}</td>
                            <td class="border-b border-r px-4 py-2 font-mono text-[11px] text-slate-500">${row.referensi || '-'}</td>
                            <td class="border-b border-r px-4 py-2 font-mono text-[11px] text-[#F59E0B] font-bold">${row.sku}</td>
                            <td class="border-b border-r px-4 py-2 text-slate-700 font-semibold">${row.nama_barang || '(Barang Terhapus)'}</td>
                            <td class="border-b border-r px-4 py-2 text-center font-bold text-rose-500">-${formattedQty} <span class="text-[10px] font-medium text-slate-500">${row.satuan || ''}</span></td>
                            <td class="border-b border-r px-4 py-2 text-slate-500 text-xs">
                                ${row.penerima ? `<span class="font-bold text-slate-600">${row.penerima}</span><br>` : ''}
                                ${row.keterangan || '-'}
                            </td>
                            <td class="border-b px-3 py-2 text-center">${actionBtn}</td>
                        </tr>`;
                    });
                    tbody.innerHTML = html;
                } else {
                    tbody.innerHTML = `<tr><td colspan="8" class="p-12 text-center text-slate-400 font-medium bg-slate-50">Tidak ada riwayat barang keluar.</td></tr>`;
                }
                
                renderPagination(res.totalPages || 1, res.totalData || 0);

            } catch (error) {
                console.error("Error Loading Data:", error);
                tbody.innerHTML = `<tr><td colspan="8" class="p-12 text-center text-red-500 font-medium bg-slate-50">Gagal terhubung ke server (Pastikan file ajax_inventory_out.php sudah ada).</td></tr>`;
            }
        }

        // --- SUBMIT DATA (ADD / EDIT) ---
        async function submitAction(e, actionType) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            formData.append('action', actionType);

            const qty = formData.get('qty');
            // Ngeceknya tetep > 0 aja biar valid
            if (qty <= 0) {
                Swal.fire('Peringatan', 'Qty harus lebih dari 0.', 'warning'); return;
            }

            const titleConf = actionType === 'add' ? 'Keluarkan Barang?' : 'Update Perubahan?';
            const textConf = actionType === 'add' ? 'Stok utama barang ini akan otomatis berkurang.' : 'Master stok akan otomatis dikalkulasi ulang.';

            Swal.fire({
                title: titleConf, text: textConf, icon: 'warning',
                showCancelButton: true, confirmButtonColor: '#F59E0B',
                confirmButtonText: 'Ya, Lanjutkan!', cancelButtonText: 'Batal'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    try {
                        const response = await fetch('proses_inventory_out.php', { method: 'POST', body: formData });
                        const res = await response.json();
                        if (res.status === 'success') {
                            Swal.fire({ icon: 'success', title: 'Berhasil!', text: res.message, confirmButtonColor: '#F59E0B' })
                            .then(() => {
                                closeModal(actionType + 'Modal');
                                if (actionType === 'add') form.reset();
                                loadTableData();
                                setTimeout(() => location.reload(), 1000); 
                            });
                        } else Swal.fire('Gagal!', res.message, 'error');
                    } catch (err) { Swal.fire('Error!', 'Terjadi gangguan.', 'error'); }
                }
            })
        }

        // --- POPULATE MODAL EDIT ---
        function fillEditModal(data) {
            document.getElementById('edit_id').value = data.id;
            document.getElementById('edit_tanggal').value = data.tanggal.split(' ')[0]; 
            document.getElementById('edit_referensi').value = data.referensi;
            document.getElementById('edit_sku').value = data.sku;
            // Parse nilai desimal supaya muncul sesuai dengan di DB
            document.getElementById('edit_qty').value = parseFloat(data.qty);
            document.getElementById('edit_penerima').value = data.penerima;
            document.getElementById('edit_keterangan').value = data.keterangan;
            updateSatuan('edit');
            openModal('editModal');
        }

        // --- DELETE ACTION ---
        function confirmDelete(id) {
            Swal.fire({
                title: 'Batal Keluarkan Barang?',
                text: "Menghapus ini akan mengembalikan / menambah ulang Master Stok sebesar Qty yang dibatalkan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444', confirmButtonText: 'Ya, Hapus histori!', cancelButtonText: 'Batal'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    const fd = new FormData(); fd.append('action', 'delete'); fd.append('id', id);
                    const res = await fetch('proses_inventory_out.php', { method: 'POST', body: fd }).then(r => r.json());
                    if (res.status === 'success') {
                        Swal.fire('Terhapus!', res.message, 'success').then(() => {
                            loadTableData(); setTimeout(() => location.reload(), 1000);
                        });
                    } else Swal.fire('Gagal!', res.message, 'error');
                }
            });
        }

        window.onload = loadTableData;
    </script>
</body>
</html>