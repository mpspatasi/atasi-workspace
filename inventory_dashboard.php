<?php
session_start();
require 'koneksi.php'; 

// ==============================================================================
// 1. CEK SESSION & AMBIL DATA DARI tb_users
// ==============================================================================
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$username_session = $_SESSION['username'];
$nama_user = $_SESSION['nama'] ?? ($_SESSION['nama_lengkap'] ?? $username_session);

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
// FUNGSI HELPER: FORMAT DESIMAL CERDAS (2 Digit)
// ==============================================================================
function formatQty($qty) {
    $formatted = number_format((float)$qty, 2, ',', '.');
    if (strpos($formatted, ',') !== false) {
        $formatted = rtrim(rtrim($formatted, '0'), ',');
    }
    return $formatted;
}

// ==============================================================================
// 2. SINKRONISASI DATA DASHBOARD (MASTER, IN, OUT)
// ==============================================================================
try {
    // KPI Data Master
    $kpiTotal = $pdo->query("SELECT COUNT(*) FROM jurnal_transaksi_atasi.db_inventory")->fetchColumn();
    $kpiProduk = $pdo->query("SELECT COUNT(*) FROM jurnal_transaksi_atasi.db_inventory WHERE kategori = 'Produk Jadi'")->fetchColumn();
    $kpiBahan = $pdo->query("SELECT COUNT(*) FROM jurnal_transaksi_atasi.db_inventory WHERE kategori = 'Bahan Baku'")->fetchColumn();
    $kpiAlert = $pdo->query("SELECT COUNT(*) FROM jurnal_transaksi_atasi.db_inventory WHERE CAST(current_stock AS SIGNED) <= CAST(min_stock AS SIGNED)")->fetchColumn();
    
    // Inventory Health
    $healthScore = $kpiTotal > 0 ? round((($kpiTotal - $kpiAlert) / $kpiTotal) * 100) : 0;

    // LOGIKA WARNA ADAPTIF INVENTORY HEALTH
    if ($healthScore >= 80) {
        $healthBgClass = 'bg-[#10B981]'; // Emerald jika sehat
        $healthText = 'Sehat';
    } elseif ($healthScore >= 50) {
        $healthBgClass = 'bg-[#F59E0B]'; // Oranye jika mulai turun
        $healthText = 'Waspada';
    } else {
        $healthBgClass = 'bg-[#E11D48]'; // Merah jika kritis
        $healthText = 'Kritis';
    }

    // Live Produk Jadi (4 teratas)
    $stmtProduk = $pdo->query("SELECT nama_barang, current_stock, min_stock, satuan FROM jurnal_transaksi_atasi.db_inventory WHERE kategori = 'Produk Jadi' LIMIT 4");
    $liveProduk = $stmtProduk->fetchAll(PDO::FETCH_ASSOC);

    // Live Bahan Baku (4 teratas)
    $stmtBahan = $pdo->query("SELECT nama_barang, current_stock, min_stock, satuan FROM jurnal_transaksi_atasi.db_inventory WHERE kategori = 'Bahan Baku' LIMIT 4");
    $liveBahan = $stmtBahan->fetchAll(PDO::FETCH_ASSOC);

    // KPI Movement Bulan Ini
    $kpiTrxIn = $pdo->query("SELECT COUNT(*) FROM jurnal_transaksi_atasi.db_inventory_in WHERE MONTH(tanggal) = MONTH(CURDATE()) AND YEAR(tanggal) = YEAR(CURDATE())")->fetchColumn();
    $kpiTrxOut = $pdo->query("SELECT COUNT(*) FROM jurnal_transaksi_atasi.db_inventory_out WHERE MONTH(tanggal) = MONTH(CURDATE()) AND YEAR(tanggal) = YEAR(CURDATE())")->fetchColumn();
    $kpiTrxTotal = $kpiTrxIn + $kpiTrxOut;

    // Aktivitas Terbaru
    $sqlActivity = "
        SELECT 'IN' as tipe, i.tanggal, i.sku, i.qty, i.supplier as pihak, m.nama_barang 
        FROM jurnal_transaksi_atasi.db_inventory_in i 
        LEFT JOIN jurnal_transaksi_atasi.db_inventory m ON i.sku = m.sku
        UNION ALL
        SELECT 'OUT' as tipe, o.tanggal, o.sku, o.qty, o.penerima as pihak, m.nama_barang 
        FROM jurnal_transaksi_atasi.db_inventory_out o 
        LEFT JOIN jurnal_transaksi_atasi.db_inventory m ON o.sku = m.sku
        ORDER BY tanggal DESC LIMIT 6
    ";
    $recentActivities = $pdo->query($sqlActivity)->fetchAll(PDO::FETCH_ASSOC);

    // =======================================================
    // PERSIAPAN DATA UNTUK CHART (GRAFIK) - DIUBAH KE FLOAT
    // =======================================================
    $chartLabels = [];
    $chartDataIn = [];
    $chartDataOut = [];
    $dateMapIn = [];
    $dateMapOut = [];

    // 1. Data IN & OUT Harian (30 Hari) -> Cast ke Float untuk baca desimal
    $stmtChartIn = $pdo->query("SELECT DATE(tanggal) as tgl, SUM(qty) as total FROM jurnal_transaksi_atasi.db_inventory_in WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 29 DAY) GROUP BY DATE(tanggal)");
    while($row = $stmtChartIn->fetch(PDO::FETCH_ASSOC)) { $dateMapIn[$row['tgl']] = (float)$row['total']; }

    $stmtChartOut = $pdo->query("SELECT DATE(tanggal) as tgl, SUM(qty) as total FROM jurnal_transaksi_atasi.db_inventory_out WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 29 DAY) GROUP BY DATE(tanggal)");
    while($row = $stmtChartOut->fetch(PDO::FETCH_ASSOC)) { $dateMapOut[$row['tgl']] = (float)$row['total']; }

    for ($i = 29; $i >= 0; $i--) {
        $dateStr = date('Y-m-d', strtotime("-$i days"));
        $chartLabels[] = date('d M', strtotime($dateStr));
        $chartDataIn[] = $dateMapIn[$dateStr] ?? 0;
        $chartDataOut[] = $dateMapOut[$dateStr] ?? 0;
    }

    // 2. Data IN & OUT Bulanan (12 Bulan Terakhir)
    $chartMonthLabels = [];
    $chartMonthDataIn = [];
    $chartMonthDataOut = [];
    
    $startDate12Months = date('Y-m-01', strtotime('-11 months'));
    
    $stmtMonthIn = $pdo->prepare("SELECT DATE_FORMAT(tanggal, '%Y-%m') as ym, SUM(qty) as total FROM jurnal_transaksi_atasi.db_inventory_in WHERE tanggal >= ? GROUP BY ym");
    $stmtMonthIn->execute([$startDate12Months]);
    $mapMonthIn = [];
    while($row = $stmtMonthIn->fetch(PDO::FETCH_ASSOC)) { $mapMonthIn[$row['ym']] = (float)$row['total']; }

    $stmtMonthOut = $pdo->prepare("SELECT DATE_FORMAT(tanggal, '%Y-%m') as ym, SUM(qty) as total FROM jurnal_transaksi_atasi.db_inventory_out WHERE tanggal >= ? GROUP BY ym");
    $stmtMonthOut->execute([$startDate12Months]);
    $mapMonthOut = [];
    while($row = $stmtMonthOut->fetch(PDO::FETCH_ASSOC)) { $mapMonthOut[$row['ym']] = (float)$row['total']; }

    $monthNamesID = ['01'=>'Jan', '02'=>'Feb', '03'=>'Mar', '04'=>'Apr', '05'=>'Mei', '06'=>'Jun', '07'=>'Jul', '08'=>'Ags', '09'=>'Sep', '10'=>'Okt', '11'=>'Nov', '12'=>'Des'];

    for ($i = 11; $i >= 0; $i--) {
        $ym = date('Y-m', strtotime("-$i months")); 
        $m = explode('-', $ym)[1];
        $y = substr(explode('-', $ym)[0], 2, 2); 
        
        $chartMonthLabels[] = $monthNamesID[$m] . ' ' . $y; 
        $chartMonthDataIn[] = $mapMonthIn[$ym] ?? 0;
        $chartMonthDataOut[] = $mapMonthOut[$ym] ?? 0;
    }

    // 3. Kalkulasi Historis Total Stok 30 Hari Terakhir
    $totalStockToday = (float)$pdo->query("SELECT SUM(current_stock) FROM jurnal_transaksi_atasi.db_inventory")->fetchColumn();
    $runningStock = $totalStockToday;
    $tempTrend = [];
    
    for ($i = 0; $i < 30; $i++) {
        $dateStr = date('Y-m-d', strtotime("-$i days"));
        $tempTrend[] = $runningStock; 
        
        $inToday = $dateMapIn[$dateStr] ?? 0;
        $outToday = $dateMapOut[$dateStr] ?? 0;
        $runningStock = $runningStock - $inToday + $outToday;
    }
    $chartInventoryTrend = array_reverse($tempTrend);

} catch (PDOException $e) {
    $kpiTotal = 0; $kpiProduk = 0; $kpiBahan = 0; $kpiAlert = 0; $healthScore = 0;
    $healthBgClass = 'bg-slate-500'; $healthText = 'Unknown';
    $liveProduk = []; $liveBahan = []; $recentActivities = [];
    $kpiTrxIn = 0; $kpiTrxOut = 0; $kpiTrxTotal = 0;
    $chartLabels = []; $chartDataIn = []; $chartDataOut = [];
    $chartMonthLabels = []; $chartMonthDataIn = []; $chartMonthDataOut = [];
    $chartInventoryTrend = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visual Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        ::-webkit-scrollbar { width: 8px; height: 10px; }
        ::-webkit-scrollbar-track { background: #f8fafc; border-radius: 4px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        .glass-panel { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); }
    </style>
</head>
<body class="bg-slate-50 font-sans text-slate-800 h-screen flex overflow-hidden" onclick="closeProfileMenu(event); closeExportMenu(event);">

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

    <main id="mainWrapper" class="flex-1 flex flex-col h-screen min-w-0 relative">
        
        <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6 shrink-0 shadow-sm z-30 relative">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="p-2 bg-slate-50 rounded-md hover:bg-slate-100 text-slate-600 transition border border-slate-200 shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                <div>
                    <h1 class="text-xl font-bold text-slate-800">Visual Dashboard</h1>
                    <div class="flex items-center gap-2 text-xs font-medium mt-0.5">
                        <span class="text-[#F59E0B]">ATASI</span>
                        <span class="text-slate-400">/</span>
                        <span class="text-slate-500">Live Inventory Hub</span>
                    </div>
                </div>
            </div>
            
            <div class="flex items-center gap-4">
                <div class="relative">
                    <button onclick="toggleExportMenu(event)" class="flex items-center gap-2 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 rounded-md shadow-sm text-xs font-bold transition focus:outline-none">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        Export Report
                    </button>
                    <div id="exportMenu" class="hidden absolute right-0 mt-2 w-36 bg-white rounded-lg shadow-xl border border-slate-200 z-50 py-1.5 overflow-hidden transform transition-all">
                        <button onclick="exportToPDF()" class="w-full text-left px-4 py-2 text-sm text-slate-600 hover:bg-red-50 hover:text-red-600 font-medium transition-colors flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg> Export PDF
                        </button>
                        <button onclick="exportToJPEG()" class="w-full text-left px-4 py-2 text-sm text-slate-600 hover:bg-blue-50 hover:text-blue-600 font-medium transition-colors flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg> Export JPEG
                        </button>
                    </div>
                </div>

                <div class="h-6 w-px bg-slate-200"></div>
                
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

        <div id="scrollContainer" class="flex-1 overflow-y-auto bg-slate-50 w-full relative">
            <div id="exportArea" class="p-6 bg-slate-50 w-full">
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-6">
                    
                    <div class="<?= $healthBgClass ?> rounded-2xl p-5 relative overflow-hidden shadow-lg flex flex-col justify-between h-[140px] text-white transition-colors duration-500">
                        <div class="relative z-10 flex justify-between items-start">
                            <div>
                                <span class="text-white/80 text-xs font-bold uppercase tracking-wider">Inventory Health</span>
                                <div class="mt-2 flex items-baseline gap-1">
                                    <h3 class="text-3xl font-bold"><?= $healthScore ?></h3>
                                    <span class="text-lg font-medium opacity-80">%</span>
                                </div>
                            </div>
                            <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center backdrop-blur-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                        </div>
                        <div class="relative z-10 mt-auto flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-white animate-pulse"></span>
                            <span class="text-xs font-bold"><?= $healthText ?></span>
                        </div>
                        <svg viewBox="0 0 1440 320" class="absolute bottom-0 left-0 w-full text-white/10" preserveAspectRatio="none" style="height: 60px;">
                            <path fill="currentColor" d="M0,192L48,197.3C96,203,192,213,288,208C384,203,480,181,576,170.7C672,160,768,160,864,170.7C960,181,1056,203,1152,202.7C1248,203,1344,181,1392,170.7L1440,160L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path>
                        </svg>
                    </div>
                    
                    <div class="glass-panel p-5 rounded-2xl border border-slate-200 shadow-sm flex flex-col justify-between h-[140px] relative overflow-hidden bg-white">
                        <div class="relative z-10 flex justify-between items-start">
                            <div>
                                <span class="text-slate-500 text-xs font-bold uppercase tracking-wider">Total Registered SKU</span>
                                <h3 class="text-3xl font-bold text-slate-800 mt-2"><?= formatQty($kpiTotal) ?></h3>
                            </div>
                            <div class="w-8 h-8 bg-emerald-100 rounded-lg flex items-center justify-center text-[#10B981]">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                            </div>
                        </div>
                        <div class="relative z-10 mt-auto flex gap-4 text-xs font-medium text-slate-500">
                            <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-emerald-500"></span><strong class="text-slate-800"><?= formatQty($kpiProduk) ?></strong> Produk</span>
                            <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-blue-500"></span><strong class="text-slate-800"><?= formatQty($kpiBahan) ?></strong> Bahan</span>
                        </div>
                        <svg viewBox="0 0 1440 320" class="absolute bottom-0 left-0 w-full text-[#10B981]/[0.04]" preserveAspectRatio="none" style="height: 60px;">
                            <path fill="currentColor" d="M0,192L48,197.3C96,203,192,213,288,208C384,203,480,181,576,170.7C672,160,768,160,864,170.7C960,181,1056,203,1152,202.7C1248,203,1344,181,1392,170.7L1440,160L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path>
                        </svg>
                    </div>

                    <div class="glass-panel p-5 rounded-2xl border <?= $kpiAlert > 0 ? 'border-red-200' : 'border-slate-200' ?> shadow-sm relative overflow-hidden bg-white h-[140px] flex flex-col justify-between">
                        <div class="relative z-10 flex justify-between items-start">
                            <div>
                                <span class="<?= $kpiAlert > 0 ? 'text-red-500' : 'text-slate-500' ?> text-xs font-bold uppercase tracking-wider flex items-center gap-1.5">
                                    <?php if($kpiAlert > 0): ?>
                                    <span class="relative flex h-2 w-2"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span><span class="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span></span>
                                    <?php endif; ?>
                                    Critical Alerts
                                </span>
                                <h3 class="text-3xl font-bold <?= $kpiAlert > 0 ? 'text-red-600' : 'text-slate-800' ?> mt-2"><?= formatQty($kpiAlert) ?> <span class="text-sm font-semibold <?= $kpiAlert > 0 ? 'text-red-400' : 'text-slate-400' ?>">Items</span></h3>
                            </div>
                            <div class="w-8 h-8 <?= $kpiAlert > 0 ? 'bg-red-100 text-red-600' : 'bg-emerald-100 text-[#10B981]' ?> rounded-lg flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            </div>
                        </div>
                        <div class="relative z-10 mt-auto">
                            <a href="inventory_data.php?filter=low_stock" class="text-[11px] font-bold <?= $kpiAlert > 0 ? 'text-red-600 hover:text-red-800' : 'text-[#10B981] hover:text-emerald-800' ?> flex items-center gap-1 transition">Lihat Detail <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg></a>
                        </div>
                        <svg viewBox="0 0 1440 320" class="absolute bottom-0 left-0 w-full <?= $kpiAlert > 0 ? 'text-red-500/[0.04]' : 'text-[#10B981]/[0.04]' ?>" preserveAspectRatio="none" style="height: 60px;">
                            <path fill="currentColor" d="M0,192L48,197.3C96,203,192,213,288,208C384,203,480,181,576,170.7C672,160,768,160,864,170.7C960,181,1056,203,1152,202.7C1248,203,1344,181,1392,170.7L1440,160L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path>
                        </svg>
                    </div>

                    <div class="glass-panel p-5 rounded-2xl border border-slate-200 shadow-sm flex flex-col justify-between h-[140px] relative overflow-hidden bg-white">
                        <div class="relative z-10 flex justify-between items-start">
                            <div>
                                <span class="text-slate-500 text-xs font-bold uppercase tracking-wider">Movement (Bulan Ini)</span>
                                <h3 class="text-3xl font-bold text-slate-800 mt-2"><?= formatQty($kpiTrxTotal) ?> <span class="text-sm font-semibold text-slate-400">Trx</span></h3>
                            </div>
                            <div class="w-8 h-8 bg-emerald-100 rounded-lg flex items-center justify-center text-[#10B981]">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                            </div>
                        </div>
                        <div class="relative z-10 mt-auto flex gap-2 text-[11px] font-bold">
                            <span class="text-emerald-600 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-md flex items-center gap-1"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"></path></svg> <?= formatQty($kpiTrxIn) ?> IN</span>
                            <span class="text-rose-600 bg-rose-50 border border-rose-200 px-2 py-0.5 rounded-md flex items-center gap-1"><svg class="w-3 h-3 transform rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"></path></svg> <?= formatQty($kpiTrxOut) ?> OUT</span>
                        </div>
                        <svg viewBox="0 0 1440 320" class="absolute bottom-0 left-0 w-full text-[#10B981]/[0.04]" preserveAspectRatio="none" style="height: 60px;">
                            <path fill="currentColor" d="M0,192L48,197.3C96,203,192,213,288,208C384,203,480,181,576,170.7C672,160,768,160,864,170.7C960,181,1056,203,1152,202.7C1248,203,1344,181,1392,170.7L1440,160L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path>
                        </svg>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <div class="glass-panel border border-slate-200 rounded-xl shadow-sm flex flex-col overflow-hidden bg-white">
                        <div class="px-5 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                            <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-[#10B981]"></span> Live Produk Jadi
                            </h3>
                            <a href="inventory_data.php?filter=produk" class="text-xs font-bold text-[#10B981] hover:underline">Lihat Semua</a>
                        </div>
                        <div class="p-5 flex-1 space-y-4">
                            <?php if (empty($liveProduk)): ?>
                                <p class="text-xs text-slate-400 text-center py-4">Belum ada data produk.</p>
                            <?php else: ?>
                                <?php foreach ($liveProduk as $item): 
                                    $stock = (float)$item['current_stock'];
                                    $min = (float)$item['min_stock'];
                                    $percent = $min > 0 ? min(100, round(($stock / ($min * 3)) * 100)) : ($stock > 0 ? 100 : 0);
                                    $isAlert = $stock <= $min;
                                    // Gradasi warna progress bar sesuai kondisi
                                    $colorClass = $isAlert ? 'bg-red-500' : ($percent < 50 ? 'bg-amber-500' : 'bg-[#10B981]');
                                    $textColor = $isAlert ? 'text-red-600' : ($percent < 50 ? 'text-amber-600' : 'text-[#10B981]');
                                ?>
                                <div>
                                    <div class="flex justify-between items-end mb-1.5">
                                        <div class="flex items-center gap-2">
                                            <h4 class="text-xs font-bold text-slate-800"><?= htmlspecialchars($item['nama_barang']) ?></h4>
                                            <?php if($isAlert): ?><span class="px-1.5 py-0.5 bg-red-100 text-red-600 text-[9px] font-bold rounded uppercase">Kritis</span><?php endif; ?>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-sm font-bold <?= $textColor ?>"><?= formatQty($stock) ?> <span class="text-[10px] font-medium text-slate-500"><?= htmlspecialchars($item['satuan']) ?></span></span>
                                        </div>
                                    </div>
                                    <div class="text-[10px] font-medium text-slate-500 mb-1.5">Min: <?= formatQty($min) ?> <?= htmlspecialchars($item['satuan']) ?></div>
                                    <div class="w-full bg-slate-100 rounded-full h-1.5">
                                        <div class="<?= $colorClass ?> h-1.5 rounded-full <?= $isAlert ? 'animate-pulse' : '' ?> transition-all duration-500" style="width: <?= $percent ?>%"></div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="glass-panel border border-slate-200 rounded-xl shadow-sm flex flex-col overflow-hidden bg-white">
                        <div class="px-5 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                            <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-[#10B981]"></span> Live Bahan Baku
                            </h3>
                            <a href="inventory_data.php?filter=bahan_baku" class="text-xs font-bold text-[#10B981] hover:underline">Lihat Semua</a>
                        </div>
                        <div class="p-5 flex-1 space-y-4">
                            <?php if (empty($liveBahan)): ?>
                                <p class="text-xs text-slate-400 text-center py-4">Belum ada data bahan baku.</p>
                            <?php else: ?>
                                <?php foreach ($liveBahan as $item): 
                                    $stock = (float)$item['current_stock'];
                                    $min = (float)$item['min_stock'];
                                    $percent = $min > 0 ? min(100, round(($stock / ($min * 3)) * 100)) : ($stock > 0 ? 100 : 0);
                                    $isAlert = $stock <= $min;
                                    $colorClass = $isAlert ? 'bg-red-500' : ($percent < 50 ? 'bg-amber-500' : 'bg-[#10B981]');
                                    $textColor = $isAlert ? 'text-red-600' : ($percent < 50 ? 'text-amber-600' : 'text-[#10B981]');
                                ?>
                                <div>
                                    <div class="flex justify-between items-end mb-1.5">
                                        <div class="flex items-center gap-2">
                                            <h4 class="text-xs font-bold text-slate-800"><?= htmlspecialchars($item['nama_barang']) ?></h4>
                                            <?php if($isAlert): ?><span class="px-1.5 py-0.5 bg-red-100 text-red-600 text-[9px] font-bold rounded uppercase">Kritis</span><?php endif; ?>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-sm font-bold <?= $textColor ?>"><?= formatQty($stock) ?> <span class="text-[10px] font-medium text-slate-500"><?= htmlspecialchars($item['satuan']) ?></span></span>
                                        </div>
                                    </div>
                                    <div class="text-[10px] font-medium text-slate-500 mb-1.5">Min: <?= formatQty($min) ?> <?= htmlspecialchars($item['satuan']) ?></div>
                                    <div class="w-full bg-slate-100 rounded-full h-1.5">
                                        <div class="<?= $colorClass ?> h-1.5 rounded-full <?= $isAlert ? 'animate-pulse' : '' ?> transition-all duration-500" style="width: <?= $percent ?>%"></div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <div class="glass-panel border border-slate-200 rounded-xl shadow-sm p-5 bg-white">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-sm font-bold text-slate-800">Trend Keluar-Masuk (30 Hari Terakhir)</h3>
                        </div>
                        <div class="h-64 w-full relative">
                            <canvas id="movementChart"></canvas>
                        </div>
                    </div>

                    <div class="glass-panel border border-slate-200 rounded-xl shadow-sm p-5 bg-white">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-sm font-bold text-slate-800">Rekap In/Out Bulanan (12 Bulan Terakhir)</h3>
                        </div>
                        <div class="h-64 w-full relative">
                            <canvas id="monthlyChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <div class="col-span-2 glass-panel border border-slate-200 rounded-xl shadow-sm p-5 bg-white">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-sm font-bold text-slate-800">Trend Total Qty Master (30 Hari Terakhir)</h3>
                        </div>
                        <div class="h-64 w-full relative">
                            <canvas id="inventoryTrendChart"></canvas>
                        </div>
                    </div>

                    <div class="glass-panel border border-slate-200 rounded-xl shadow-sm flex flex-col overflow-hidden relative bg-white">
                        <div class="px-5 py-4 border-b border-slate-100 bg-slate-50">
                            <h3 class="text-sm font-bold text-slate-800">Aktivitas Terbaru</h3>
                        </div>
                        <div class="p-5 flex-1 space-y-4">
                            <?php if (empty($recentActivities)): ?>
                                <div class="flex gap-3 items-start border-b border-slate-50 pb-3">
                                    <div class="w-8 h-8 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center shrink-0 mt-0.5">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold text-slate-400">Belum ada aktivitas transaksi.</p>
                                    </div>
                                </div>
                            <?php else: ?>
                                <?php foreach($recentActivities as $act): 
                                    $is_in = $act['tipe'] === 'IN';
                                    $iconColor = $is_in ? 'text-[#10B981] bg-emerald-100' : 'text-rose-500 bg-rose-100';
                                    $sign = $is_in ? '+' : '-';
                                    $qtyColor = $is_in ? 'text-[#10B981]' : 'text-rose-600';
                                    $pihakLabel = $is_in ? 'Dari: ' : 'Ke: ';
                                ?>
                                <div class="flex gap-3 items-start border-b border-slate-100 pb-3 last:border-0 last:pb-0">
                                    <div class="w-8 h-8 rounded-full <?= $iconColor ?> flex items-center justify-center shrink-0 mt-0.5 shadow-sm">
                                        <?php if($is_in): ?>
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"></path></svg>
                                        <?php else: ?>
                                            <svg class="w-4 h-4 transform rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"></path></svg>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-[13px] font-bold text-slate-800 truncate"><?= htmlspecialchars($act['nama_barang'] ?? $act['sku']) ?></p>
                                        <p class="text-[11px] text-slate-500 truncate"><?= $pihakLabel . htmlspecialchars($act['pihak'] ?? '-') ?></p>
                                        <p class="text-[10px] text-slate-400 mt-1 flex items-center gap-1"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> <?= date('d M Y, H:i', strtotime($act['tanggal'])) ?></p>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <span class="text-[13px] font-bold <?= $qtyColor ?>"><?= $sign . formatQty($act['qty']) ?></span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
            </div> 
        </div> 
    </main>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('mainSidebar');
            if (sidebar.classList.contains('w-64')) {
                sidebar.classList.replace('w-64', 'w-0');
            } else {
                sidebar.classList.replace('w-0', 'w-64');
            }
        }

        // FUNGSI MENU PROFILE & EXPORT
        function toggleProfileMenu(event) {
            if(event) event.stopPropagation();
            document.getElementById('profileMenu').classList.toggle('hidden');
        }
        function toggleExportMenu(event) {
            if(event) event.stopPropagation();
            document.getElementById('exportMenu').classList.toggle('hidden');
        }
        function closeProfileMenu() {
            const pm = document.getElementById('profileMenu');
            if (pm && !pm.classList.contains('hidden')) pm.classList.add('hidden');
        }
        function closeExportMenu() {
            const em = document.getElementById('exportMenu');
            if (em && !em.classList.contains('hidden')) em.classList.add('hidden');
        }

        // =========================================================
        // FIX: FUNGSI EXPORT REPORT FULL SCREEN TANPA TERPOTONG
        // =========================================================
        async function captureDashboard() {
            Swal.fire({ 
                title: 'Menyiapkan Report...', 
                html: '<span class="text-sm text-slate-500">Merekam keseluruhan dashboard, mohon tunggu...</span>', 
                allowOutsideClick: false, 
                showConfirmButton: false, 
                didOpen: () => { Swal.showLoading() } 
            });

            const area = document.getElementById('exportArea');
            const scrollContainer = document.getElementById('scrollContainer');
            const mainContainer = document.getElementById('mainWrapper');

            const origScrollOverflow = scrollContainer.style.overflow;
            const origMainOverflow = mainContainer.style.overflow;
            
            scrollContainer.style.overflow = 'visible';
            mainContainer.style.overflow = 'visible';
            
            const canvas = await html2canvas(area, { 
                scale: 2, 
                useCORS: true, 
                backgroundColor: '#f8fafc',
                windowWidth: area.scrollWidth,
                windowHeight: area.scrollHeight,
                width: area.scrollWidth,
                height: area.scrollHeight,
                scrollY: -window.scrollY, 
                scrollX: 0
            });
            
            scrollContainer.style.overflow = origScrollOverflow;
            mainContainer.style.overflow = origMainOverflow;

            return canvas;
        }

        async function exportToJPEG() {
            closeExportMenu();
            try {
                const canvas = await captureDashboard();
                const link = document.createElement('a');
                link.download = 'Dashboard_Inventory_ATASI.jpeg';
                link.href = canvas.toDataURL('image/jpeg', 0.9);
                link.click();
                Swal.close();
            } catch (err) {
                console.error(err);
                Swal.fire('Error', 'Gagal membuat gambar report', 'error');
            }
        }

        async function exportToPDF() {
            closeExportMenu();
            try {
                const canvas = await captureDashboard();
                const imgData = canvas.toDataURL('image/jpeg', 1.0);
                const { jsPDF } = window.jspdf;
                
                const pdfWidth = 210; 
                const pdfHeight = (canvas.height * pdfWidth) / canvas.width;
                
                const pdf = new jsPDF('p', 'mm', [pdfWidth, pdfHeight]);
                pdf.addImage(imgData, 'JPEG', 0, 0, pdfWidth, pdfHeight);
                pdf.save('Dashboard_Inventory_ATASI.pdf');
                Swal.close();
            } catch (err) {
                console.error(err);
                Swal.fire('Error', 'Gagal membuat PDF report', 'error');
            }
        }

        // =========================================================
        // INISIALISASI CHART.JS (DENGAN PEMBACAAN DESIMAL)
        // =========================================================
        Chart.defaults.font.family = "'Inter', sans-serif";
        Chart.defaults.color = '#64748b';

        // Helper untuk tooltip Chart.js (merubah format float jadi desimal Indo)
        const tooltipCallback = {
            label: function(context) {
                let label = context.dataset.label || '';
                if (label) { label += ': '; }
                if (context.parsed.y !== null) {
                    label += new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(context.parsed.y);
                }
                return label;
            }
        };

        // 1. Line Chart: Movement 30 Hari
        const ctxMovement = document.getElementById('movementChart').getContext('2d');
        new Chart(ctxMovement, {
            type: 'line',
            data: {
                labels: <?= json_encode($chartLabels) ?>,
                datasets: [
                    {
                        label: 'Stock IN',
                        data: <?= json_encode($chartDataIn) ?>,
                        borderColor: '#10B981', backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderWidth: 2, tension: 0.4, fill: true, pointRadius: 0, pointHitRadius: 10
                    },
                    {
                        label: 'Stock OUT',
                        data: <?= json_encode($chartDataOut) ?>,
                        borderColor: '#F43F5E', backgroundColor: 'rgba(244, 63, 94, 0.1)',
                        borderWidth: 2, tension: 0.4, fill: true, pointRadius: 0, pointHitRadius: 10
                    }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { 
                    legend: { position: 'top', labels: { usePointStyle: true, boxWidth: 6 } },
                    tooltip: { callbacks: tooltipCallback } // Tooltip desimal
                },
                scales: {
                    y: { beginAtZero: true, grid: { borderDash: [4, 4], color: '#f1f5f9' }, ticks: { font: { size: 10 } } },
                    x: { grid: { display: false }, ticks: { font: { size: 10 }, maxRotation: 45, minRotation: 45 } }
                },
                interaction: { mode: 'index', intersect: false }
            }
        });

        // 2. Bar Chart: Movement 12 Bulan Terakhir
        const ctxMonthly = document.getElementById('monthlyChart').getContext('2d');
        new Chart(ctxMonthly, {
            type: 'bar',
            data: {
                labels: <?= json_encode($chartMonthLabels) ?>,
                datasets: [
                    {
                        label: 'Stock IN',
                        data: <?= json_encode($chartMonthDataIn) ?>,
                        backgroundColor: '#10B981', borderRadius: 4, barPercentage: 0.6
                    },
                    {
                        label: 'Stock OUT',
                        data: <?= json_encode($chartMonthDataOut) ?>,
                        backgroundColor: '#F43F5E', borderRadius: 4, barPercentage: 0.6
                    }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { 
                    legend: { position: 'top', labels: { usePointStyle: true, boxWidth: 6 } },
                    tooltip: { callbacks: tooltipCallback }
                },
                scales: {
                    y: { beginAtZero: true, grid: { borderDash: [4, 4], color: '#f1f5f9' }, ticks: { font: { size: 10 } } },
                    x: { grid: { display: false }, ticks: { font: { size: 10 }, maxRotation: 45, minRotation: 45 } }
                },
                interaction: { mode: 'index', intersect: false }
            }
        });

        // 3. Area Chart: Total Inventory Historis (30 Hari)
        const ctxTrend = document.getElementById('inventoryTrendChart').getContext('2d');
        new Chart(ctxTrend, {
            type: 'line',
            data: {
                labels: <?= json_encode($chartLabels) ?>,
                datasets: [
                    {
                        label: 'Total Qty Master Barang',
                        data: <?= json_encode($chartInventoryTrend) ?>,
                        borderColor: '#10B981', backgroundColor: 'rgba(16, 185, 129, 0.15)', // Ubah ke warna emerald tema
                        borderWidth: 2, tension: 0.3, fill: true,
                        pointRadius: 2, pointBackgroundColor: '#10B981', pointHitRadius: 10
                    }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { 
                    legend: { position: 'top', labels: { usePointStyle: true, boxWidth: 6 } },
                    tooltip: { callbacks: tooltipCallback }
                },
                scales: {
                    y: { 
                        beginAtZero: false, 
                        grid: { borderDash: [4, 4], color: '#f1f5f9' }, 
                        ticks: { font: { size: 10 } } 
                    },
                    x: { grid: { display: false }, ticks: { font: { size: 10 }, maxRotation: 45, minRotation: 45 } }
                },
                interaction: { mode: 'index', intersect: false }
            }
        });
    </script>
</body>
</html>