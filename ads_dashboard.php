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
// 2. SISTEM FILTER & PENGELOMPOKAN (DINAMIS)
// ==============================================================================

// A. Filter Rentang Waktu (Time Range)
$time_range = isset($_GET['time_range']) ? $_GET['time_range'] : '30days';
$start_date = '';
$end_date = date('Y-m-d'); 
$time_range_label = '';

switch ($time_range) {
    case 'today': $start_date = $end_date; $time_range_label = 'Hari Ini'; break;
    case 'yesterday': $start_date = date('Y-m-d', strtotime('-1 days')); $end_date = $start_date; $time_range_label = 'Kemarin'; break;
    case '7days': $start_date = date('Y-m-d', strtotime('-7 days')); $time_range_label = '7 Hari Terakhir'; break;
    case 'thismonth': $start_date = date('Y-m-01'); $time_range_label = 'Bulan Ini'; break;
    case 'lastmonth': $start_date = date('Y-m-01', strtotime('-1 month')); $end_date = date('Y-m-t', strtotime('-1 month')); $time_range_label = 'Bulan Lalu'; break;
    case 'thisquarter': $start_date = date('Y-m-01', strtotime('first day of this quarter')); $time_range_label = 'Kuartal Ini'; break;
    case 'lastquarter': $start_date = date('Y-m-01', strtotime('first day of last quarter')); $end_date = date('Y-m-t', strtotime('last day of last quarter')); $time_range_label = 'Kuartal Lalu'; break;
    case 'thisyear': $start_date = date('Y-01-01'); $time_range_label = 'Tahun Ini'; break;
    case 'lastyear': $start_date = date('Y-01-01', strtotime('-1 year')); $end_date = date('Y-12-31', strtotime('-1 year')); $time_range_label = 'Tahun Lalu'; break;
    case '30days': $start_date = date('Y-m-d', strtotime('-30 days')); $time_range_label = '30 Hari Terakhir'; break; // default
    case '90days': $start_date = date('Y-m-d', strtotime('-90 days')); $time_range_label = '90 Hari Terakhir'; break;
    case 'kustom': 
        $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days')); 
        $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d'); 
        $time_range_label = 'Kustom'; 
        break;
    default:
        $start_date = date('Y-m-d', strtotime('-30 days')); 
        $time_range_label = '30 Hari Terakhir'; 
        break;
}

// B. Filter Platform Global
$platform_filter = isset($_GET['platform']) ? $_GET['platform'] : 'all';

// C. Filter Pengelompokan Data untuk Grafik Tren
$group_by = isset($_GET['group_by']) ? $_GET['group_by'] : 'daily';
$date_field_sql = "tanggal"; 
$group_by_sql = "tanggal";

switch ($group_by) {
    case 'daily': $date_field_sql = "DATE_FORMAT(tanggal, '%d %M %Y')"; $group_by_sql = "tanggal"; break;
    case 'weekly': $date_field_sql = "DATE_FORMAT(tanggal, '%Y-W%u')"; $group_by_sql = "WEEK(tanggal, 1)"; break; 
    case 'monthly': $date_field_sql = "DATE_FORMAT(tanggal, '%M %Y')"; $group_by_sql = "DATE_FORMAT(tanggal, '%Y-%m')"; break;
    case 'quarterly': $date_field_sql = "CONCAT(YEAR(tanggal), '-Q', QUARTER(tanggal))"; $group_by_sql = "CONCAT(YEAR(tanggal), '-', QUARTER(tanggal))"; break;
    case 'yearly': $date_field_sql = "YEAR(tanggal)"; $group_by_sql = "YEAR(tanggal)"; break;
}

// E. Bangun WHERE Clause SQL Dinamis
$whereClause = "WHERE tanggal BETWEEN ? AND ?";
$params = [$start_date, $end_date];
if ($platform_filter !== 'all') {
    $whereClause .= " AND platform = ?";
    $params[] = $platform_filter;
}

// ==============================================================================
// 3. QUERY DATA STATISTIK, KPI, EXPORT
// ==============================================================================
$kpi = ['spend' => 0, 'rev' => 0, 'conv' => 0, 'roas' => 0, 'cpa' => 0, 'imp' => 0, 'click' => 0];
$chartTrend = [];
$chartPlatformSpend = [];
$chartPlatformRoas = [];

// Proses Ekspor CSV
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Ads_Report_' . $time_range_label . '_' . date('Ymd_His') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Periode', 'Platform', 'Ad Spend', 'Revenue', 'Conversions', 'Impressions', 'Clicks']);
    
    try {
        $stmtEx = $pdo->prepare("SELECT 
            tanggal as period, platform, ad_spend, revenue, conversions, impressions, clicks 
            FROM jurnal_transaksi_atasi.db_ads_analytics 
            $whereClause 
            ORDER BY tanggal ASC, platform ASC");
        $stmtEx->execute($params);
        while ($row = $stmtEx->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, $row);
        }
    } catch (PDOException $e) {}
    fclose($output);
    exit();
}

try {
    // A. Query KPI Global
    $stmtKPI = $pdo->prepare("SELECT 
        SUM(ad_spend) as spend, 
        SUM(revenue) as rev, 
        SUM(conversions) as conv,
        SUM(impressions) as imp,
        SUM(clicks) as click
        FROM jurnal_transaksi_atasi.db_ads_analytics 
        $whereClause");
    $stmtKPI->execute($params);
    $resKPI = $stmtKPI->fetch(PDO::FETCH_ASSOC);
    
    if ($resKPI['spend'] !== null) {
        $kpi['spend'] = $resKPI['spend'];
        $kpi['rev'] = $resKPI['rev'];
        $kpi['conv'] = $resKPI['conv'];
        $kpi['imp'] = $resKPI['imp'];
        $kpi['click'] = $resKPI['click'];
        
        if ($kpi['spend'] > 0) $kpi['roas'] = round($kpi['rev'] / $kpi['spend'], 2);
        if ($kpi['conv'] > 0) $kpi['cpa'] = round($kpi['spend'] / $kpi['conv'], 0);
    }

    // B. Query Trend (Line Chart) dengan MIN(tanggal) agar grouping tidak blank
    $stmtTrend = $pdo->prepare("SELECT 
        $date_field_sql as period, 
        MIN(tanggal) as raw_date,
        SUM(ad_spend) as ad_spend, 
        SUM(revenue) as revenue,
        SUM(conversions) as conversions,
        SUM(impressions) as impressions,
        SUM(clicks) as clicks
        FROM jurnal_transaksi_atasi.db_ads_analytics 
        $whereClause 
        GROUP BY $group_by_sql 
        ORDER BY raw_date ASC");
    $stmtTrend->execute($params);
    $chartTrend = $stmtTrend->fetchAll(PDO::FETCH_ASSOC);

    // C. Query Distribusi Platform (Doughnut Ad Spend)
    $stmtPlatSpend = $pdo->prepare("SELECT 
        platform, SUM(ad_spend) as spend 
        FROM jurnal_transaksi_atasi.db_ads_analytics 
        $whereClause 
        GROUP BY platform ORDER BY spend DESC");
    $stmtPlatSpend->execute($params);
    $chartPlatformSpend = $stmtPlatSpend->fetchAll(PDO::FETCH_ASSOC);

    // D. Query ROAS Platform (Bar Chart)
    $stmtPlatRoas = $pdo->prepare("SELECT 
        platform, AVG(revenue/NULLIF(ad_spend, 0)) as roas 
        FROM jurnal_transaksi_atasi.db_ads_analytics 
        $whereClause 
        GROUP BY platform ORDER BY roas DESC");
    $stmtPlatRoas->execute($params);
    $chartPlatformRoas = $stmtPlatRoas->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Dashboard - Advertisement</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        ::-webkit-scrollbar { width: 8px; height: 10px; }
        ::-webkit-scrollbar-track { background: #f8fafc; border-radius: 4px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body class="bg-slate-50 font-sans text-slate-800 h-screen flex overflow-hidden" onclick="closeAllMenus(event)">

    <aside id="mainSidebar" class="w-64 bg-[#E11D48] text-white flex flex-col shrink-0 overflow-hidden shadow-xl z-40 transition-all duration-300 relative">
        <div class="h-16 flex items-center px-6 border-b border-white/10 shrink-0 gap-3">
            <div class="w-8 h-8 bg-white rounded-md flex items-center justify-center text-[#E11D48] shrink-0 shadow-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"></path></svg>
            </div>
            <span class="text-lg font-bold tracking-wider whitespace-nowrap uppercase">ADS HUB</span>
        </div>
        
        <nav class="flex-1 py-6 px-3 space-y-2 overflow-y-auto">
            <a href="ads_dashboard.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-white/20 text-white shadow-inner transition-colors whitespace-nowrap group">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                <span class="font-bold tracking-wide text-sm">Master Dashboard</span>
            </a>
            <a href="ads_platforms.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-rose-100 hover:bg-white/10 hover:text-white transition-colors whitespace-nowrap group">
                <svg class="w-5 h-5 shrink-0 opacity-70 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                <span class="font-medium tracking-wide text-sm">Platform Analytics</span>
            </a>
            <a href="ads_strategy.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-rose-100 hover:bg-white/10 hover:text-white transition-colors whitespace-nowrap group">
                <svg class="w-5 h-5 shrink-0 opacity-70 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                <span class="font-medium tracking-wide text-sm">Health & Strategy</span>
            </a>
            <a href="ads_data_entry.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-rose-100 hover:bg-white/10 hover:text-white transition-colors whitespace-nowrap group">
                <svg class="w-5 h-5 shrink-0 opacity-70 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                <span class="font-medium tracking-wide text-sm">Data Entry Hub</span>
            </a>
        </nav>
    </aside>

    <main class="flex-1 flex flex-col h-screen min-w-0 overflow-hidden relative bg-slate-50">
        
        <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6 shrink-0 shadow-sm z-30 relative">
            <div class="flex items-center gap-4">
                <button onclick="document.getElementById('mainSidebar').classList.toggle('w-0'); document.getElementById('mainSidebar').classList.toggle('w-64');" class="p-2 bg-slate-50 rounded-md hover:bg-slate-100 text-slate-600 transition border border-slate-200 shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                <div>
                    <h1 class="text-xl font-bold text-slate-800">Master Dashboard</h1>
                    <div class="flex items-center gap-2 text-xs font-medium mt-0.5">
                        <span class="text-[#E11D48]">Advertisement</span><span class="text-slate-400">/</span><span class="text-slate-500">Global Overview</span>
                    </div>
                </div>
            </div>
            
            <div class="flex items-center gap-4 relative">
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
                                <?= htmlspecialchars($username_session) ?>
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

        <div class="p-6 flex-1 overflow-y-auto space-y-6">
            
            <form method="GET" action="" id="filterForm" class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4 relative z-20">
                
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1.5 tracking-wider">Rentang Waktu</label>
                    <select name="time_range" id="timeRangeSelect" onchange="toggleCustomDate(this.value); submitForm();" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm bg-slate-50 focus:outline-none focus:ring-1 focus:ring-rose-200 font-semibold text-slate-700 shadow-sm cursor-pointer transition">
                        <option value="today" <?= $time_range=='today'?'selected':'' ?>>Hari Ini</option>
                        <option value="yesterday" <?= $time_range=='yesterday'?'selected':'' ?>>Kemarin</option>
                        <option value="7days" <?= $time_range=='7days'?'selected':'' ?>>Seminggu Terakhir</option>
                        <option value="thismonth" <?= $time_range=='thismonth'?'selected':'' ?>>Bulan Ini</option>
                        <option value="lastmonth" <?= $time_range=='lastmonth'?'selected':'' ?>>Bulan Lalu</option>
                        <option value="thisquarter" <?= $time_range=='thisquarter'?'selected':'' ?>>Kuartal Ini</option>
                        <option value="lastquarter" <?= $time_range=='lastquarter'?'selected':'' ?>>Kuartal Lalu</option>
                        <option value="thisyear" <?= $time_range=='thisyear'?'selected':'' ?>>Tahun Ini</option>
                        <option value="lastyear" <?= $time_range=='lastyear'?'selected':'' ?>>Tahun Lalu</option>
                        <option value="30days" <?= $time_range=='30days'?'selected':'' ?>>30 Hari Terakhir</option>
                        <option value="90days" <?= $time_range=='90days'?'selected':'' ?>>90 Hari Terakhir</option>
                        <option value="kustom" <?= $time_range=='kustom'?'selected':'' ?>>Rentang Kustom...</option>
                    </select>
                </div>

                <div id="customDateRange" class="<?= $time_range=='kustom'?'':'hidden' ?> flex items-end gap-2 col-span-2 md:col-span-1 lg:col-span-2">
                    <div class="flex-1">
                        <label class="block text-[10px] font-medium text-slate-400 mb-0.5">Dari</label>
                        <input type="date" name="start_date" value="<?= $start_date ?>" class="w-full border border-slate-300 rounded-lg px-3 py-1.5 text-sm bg-white focus:outline-none focus:ring-1 focus:ring-rose-200">
                    </div>
                    <span class="text-slate-400 pb-2">-</span>
                    <div class="flex-1">
                        <label class="block text-[10px] font-medium text-slate-400 mb-0.5">Sampai</label>
                        <input type="date" name="end_date" value="<?= $end_date ?>" class="w-full border border-slate-300 rounded-lg px-3 py-1.5 text-sm bg-white focus:outline-none focus:ring-1 focus:ring-rose-200">
                    </div>
                    <button type="submit" class="bg-rose-50 border border-rose-200 text-[#E11D48] px-3 py-2 rounded-lg text-xs font-bold hover:bg-rose-100 transition shadow-sm">OK</button>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1.5 tracking-wider">Kelompokkan Data</label>
                    <select name="group_by" onchange="submitForm()" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm bg-slate-50 focus:outline-none focus:ring-1 focus:ring-rose-200 font-semibold text-slate-700 shadow-sm cursor-pointer transition">
                        <option value="daily" <?= $group_by=='daily'?'selected':'' ?>>Harian</option>
                        <option value="weekly" <?= $group_by=='weekly'?'selected':'' ?>>Mingguan</option>
                        <option value="monthly" <?= $group_by=='monthly'?'selected':'' ?>>Bulanan</option>
                        <option value="quarterly" <?= $group_by=='quarterly'?'selected':'' ?>>Kuartalan</option>
                        <option value="yearly" <?= $group_by=='yearly'?'selected':'' ?>>Tahunan</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-500 uppercase mb-1.5 tracking-wider">Platform</label>
                    <select name="platform" onchange="submitForm()" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm bg-slate-50 focus:outline-none focus:ring-1 focus:ring-rose-200 font-semibold text-slate-700 shadow-sm cursor-pointer transition">
                        <option value="all" <?= $platform_filter=='all'?'selected':'' ?>>Semua Platform</option>
                        <option value="Meta" <?= $platform_filter=='Meta'?'selected':'' ?>>Meta Ads</option>
                        <option value="Shopee" <?= $platform_filter=='Shopee'?'selected':'' ?>>Shopee Ads</option>
                        <option value="TikTok" <?= $platform_filter=='TikTok'?'selected':'' ?>>TikTok Ads</option>
                    </select>
                </div>

                <div class="flex items-end gap-2 col-span-2 md:col-span-1 lg:col-span-1 lg:col-start-5 justify-end">
                    <button type="button" onclick="resetDashboardFilters()" class="border border-slate-300 px-3 py-2 rounded-lg text-sm bg-white hover:bg-slate-100 text-slate-700 font-semibold transition hidden lg:block shadow-sm">Reset</button>
                    <button type="submit" name="export" value="csv" class="bg-[#E11D48] hover:bg-rose-700 text-white px-3 py-2 rounded-lg text-sm font-semibold transition flex items-center gap-2 shadow-md">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg> CSV
                    </button>
                </div>
            </form>

            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6">
                <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm relative overflow-hidden group hover:border-rose-200 transition-colors">
                    <div class="absolute right-0 top-0 w-24 h-24 bg-rose-50 rounded-bl-full -mr-4 -mt-4 z-0 transition-transform group-hover:scale-110"></div>
                    <div class="relative z-10 flex flex-col h-full">
                        <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2">Total Ad Spend</p>
                        <h3 class="text-xl font-extrabold text-rose-600 truncate flex-1">Rp <?= number_format($kpi['spend'], 0, ',', '.') ?></h3>
                        <p class="text-xs text-slate-400 mt-1.5 font-medium truncate">Periode <?= $time_range_label ?></p>
                    </div>
                </div>
                <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm relative overflow-hidden group hover:border-emerald-200 transition-colors">
                    <div class="absolute right-0 top-0 w-24 h-24 bg-emerald-50 rounded-bl-full -mr-4 -mt-4 z-0 transition-transform group-hover:scale-110"></div>
                    <div class="relative z-10 flex flex-col h-full">
                        <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2">Total Revenue</p>
                        <h3 class="text-xl font-extrabold text-emerald-600 truncate flex-1">Rp <?= number_format($kpi['rev'], 0, ',', '.') ?></h3>
                        <p class="text-xs text-slate-400 mt-1.5 font-medium truncate">Gross Sales Iklan</p>
                    </div>
                </div>
                <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm relative overflow-hidden group hover:border-blue-200 transition-colors">
                    <div class="absolute right-0 top-0 w-24 h-24 bg-blue-50 rounded-bl-full -mr-4 -mt-4 z-0 transition-transform group-hover:scale-110"></div>
                    <div class="relative z-10 flex flex-col h-full">
                        <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2">Total ROAS</p>
                        <?php $roasColor = ($kpi['roas'] >= 3 ? 'emerald' : ($kpi['roas'] < 1.5 ? 'red' : 'blue')); ?>
                        <h3 class="text-2xl font-extrabold text-<?= $roasColor ?>-600 truncate flex-1"><?= $kpi['roas'] ?>x</h3>
                        <p class="text-xs text-slate-400 mt-1.5 font-medium truncate">Return on Ad Spend</p>
                    </div>
                </div>
                <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm relative overflow-hidden group hover:border-amber-200 transition-colors">
                    <div class="absolute right-0 top-0 w-24 h-24 bg-amber-50 rounded-bl-full -mr-4 -mt-4 z-0 transition-transform group-hover:scale-110"></div>
                    <div class="relative z-10 flex flex-col h-full">
                        <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2">Conversions</p>
                        <h3 class="text-2xl font-extrabold text-amber-600 truncate flex-1"><?= number_format($kpi['conv'], 0, ',', '.') ?></h3>
                        <p class="text-xs text-slate-400 mt-1.5 font-medium truncate">Total Order Masuk</p>
                    </div>
                </div>
                <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm relative overflow-hidden group hover:border-indigo-200 transition-colors">
                    <div class="absolute right-0 top-0 w-24 h-24 bg-indigo-50 rounded-bl-full -mr-4 -mt-4 z-0 transition-transform group-hover:scale-110"></div>
                    <div class="relative z-10 flex flex-col h-full">
                        <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2">Cost Per CPA</p>
                        <h3 class="text-xl font-extrabold text-indigo-700 truncate flex-1">Rp <?= number_format($kpi['cpa'], 0, ',', '.') ?></h3>
                        <p class="text-xs text-slate-400 mt-1.5 font-medium truncate">Biaya per 1 Konversi</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 relative z-10">
                <div class="lg:col-span-3 bg-white rounded-xl shadow-sm border border-slate-200 p-5">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-5 pb-3 border-b border-slate-100">
                        <div>
                            <h3 class="text-sm font-bold text-slate-800">Tren Performa Iklan</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Analisis perbandingan metrik secara <?= htmlspecialchars($group_by) ?></p>
                        </div>
                        <div class="flex items-center flex-wrap gap-2 bg-slate-100 border border-slate-200 rounded-lg p-1.5 shadow-inner">
                            <select id="ui_metric_y1" onchange="renderTrendChart()" class="text-xs bg-white border border-slate-300 rounded px-2.5 py-1 font-semibold text-emerald-600 focus:outline-none cursor-pointer">
                                <option value="revenue" selected>Revenue (Y1)</option>
                                <option value="ad_spend">Ad Spend (Y1)</option>
                                <option value="impressions">Impresi (Y1)</option>
                                <option value="clicks">Klik (Y1)</option>
                                <option value="conversions">Order (Y1)</option>
                            </select>
                            <span class="text-xs text-slate-400 font-medium">vs</span>
                            <select id="ui_metric_y2" onchange="renderTrendChart()" class="text-xs bg-white border border-slate-300 rounded px-2.5 py-1 font-semibold text-rose-600 focus:outline-none cursor-pointer">
                                <option value="ad_spend" selected>Ad Spend (Y2)</option>
                                <option value="revenue">Revenue (Y2)</option>
                                <option value="impressions">Impresi (Y2)</option>
                                <option value="clicks">Klik (Y2)</option>
                                <option value="conversions">Order (Y2)</option>
                            </select>
                            <span class="text-slate-300 mx-1">|</span>
                            <select id="ui_ma_period" onchange="renderTrendChart()" class="text-xs bg-white border border-slate-300 rounded px-2.5 py-1 font-semibold text-amber-600 focus:outline-none cursor-pointer">
                                <option value="0">MA: Off</option>
                                <option value="7">MA: 7 Periode (Y1)</option>
                                <option value="30">MA: 30 Periode (Y1)</option>
                                <option value="90">MA: 90 Periode (Y1)</option>
                            </select>
                        </div>
                    </div>
                    <div class="relative h-96 w-full">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 relative z-0">
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 flex flex-col">
                    <div class="mb-5 pb-3 border-b border-slate-100">
                        <h3 class="text-sm font-bold text-slate-800">Alokasi Ad Spend</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Proporsi pembakaran budget antar platform</p>
                    </div>
                    <div class="relative flex-1 flex justify-center items-center min-h-[250px]">
                        <canvas id="platformSpendChart"></canvas>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 flex flex-col">
                    <div class="mb-5 pb-3 border-b border-slate-100">
                        <h3 class="text-sm font-bold text-slate-800">Efisiensi Iklan (ROAS)</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Platform mana yang memberikan ROI tertinggi?</p>
                    </div>
                    <div class="relative h-64 w-full">
                        <canvas id="platformRoasChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        const chartTrendData = <?= json_encode($chartTrend) ?>;
        const chartPlatSpendData = <?= json_encode($chartPlatformSpend) ?>;
        const chartPlatRoasData = <?= json_encode($chartPlatformRoas) ?>;
        const group_by_state = '<?= htmlspecialchars($group_by) ?>';
    </script>

    <script>
        // Menu Profil
        function toggleProfileMenu(e) { e.stopPropagation(); document.getElementById('profileMenu').classList.toggle('hidden'); }
        function closeAllMenus() { const menu = document.getElementById('profileMenu'); if(menu) menu.classList.add('hidden'); }
        
        // Filter Submit
        function submitForm() { document.getElementById('filterForm').submit(); }
        function toggleCustomDate(val) { document.getElementById('customDateRange').classList.toggle('hidden', val !== 'kustom'); }
        function resetDashboardFilters() { window.location.href = 'ads_dashboard.php'; }

        // Pengaturan Chart Global
        Chart.defaults.font.family = "'Inter', 'Segoe UI', sans-serif";
        Chart.defaults.color = '#64748b'; 
        Chart.defaults.plugins.tooltip.padding = 12;
        Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(15, 23, 42, 0.9)'; 

        const currencyFormatter = (v) => 'Rp ' + v.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        const numberFormatter = (v) => v.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        const percentFormatter = (v) => v.toFixed(2) + 'x';
        const getFormatter = (metric) => (['ad_spend', 'revenue'].includes(metric) ? currencyFormatter : numberFormatter);

        const platColors = { 'Meta': '#3b82f6', 'Shopee': '#f97316', 'TikTok': '#18181b', 'Lainnya': '#94a3b8' };

        // 1. TREN CHART & MOVING AVERAGE REALTIME
        let trendChartInstance = null;

        function calculateMovingAverage(dataArray, period) {
            let ma = [];
            for (let i = 0; i < dataArray.length; i++) {
                if (i < period - 1) {
                    ma.push(null); 
                } else {
                    let sum = 0;
                    for (let j = 0; j < period; j++) {
                        sum += parseFloat(dataArray[i - j] || 0);
                    }
                    ma.push(sum / period);
                }
            }
            return ma;
        }

        function renderTrendChart() {
            if (!chartTrendData || chartTrendData.length === 0) return;

            const m1_val = document.getElementById('ui_metric_y1').value;
            const m2_val = document.getElementById('ui_metric_y2').value;
            const ma_period = parseInt(document.getElementById('ui_ma_period').value);

            const labels = chartTrendData.map(d => d.period);
            const dataY1 = chartTrendData.map(d => parseFloat(d[m1_val]) || 0);
            const dataY2 = chartTrendData.map(d => parseFloat(d[m2_val]) || 0);

            const isY1Money = ['revenue', 'ad_spend'].includes(m1_val);
            const isY2Money = ['revenue', 'ad_spend'].includes(m2_val);

            const labelY1Obj = document.getElementById('ui_metric_y1');
            const labelY2Obj = document.getElementById('ui_metric_y2');
            const labelY1 = labelY1Obj.options[labelY1Obj.selectedIndex].text;
            const labelY2 = labelY2Obj.options[labelY2Obj.selectedIndex].text;

            let datasets = [
                {
                    label: labelY1,
                    data: dataY1,
                    yAxisID: 'y1',
                    borderColor: '#10b981', // Emerald
                    backgroundColor: 'rgba(16, 185, 129, 0.05)',
                    borderWidth: 2, tension: 0.35, fill: true,
                    pointRadius: labels.length > 30 ? 0 : 3, pointHoverRadius: 5
                },
                {
                    label: labelY2,
                    data: dataY2,
                    yAxisID: 'y2',
                    borderColor: '#E11D48', // Rose
                    borderWidth: 2, tension: 0.35, fill: false, borderDash: [6, 4],
                    pointRadius: labels.length > 30 ? 0 : 3, pointHoverRadius: 5
                }
            ];

            if (ma_period > 0) {
                datasets.push({
                    label: `Moving Avg (${ma_period})`,
                    data: calculateMovingAverage(dataY1, ma_period),
                    yAxisID: 'y1',
                    borderColor: '#f59e0b', // Amber
                    borderWidth: 2, tension: 0.4, fill: false,
                    pointRadius: 0, pointHoverRadius: 5
                });
            }

            if (trendChartInstance) trendChartInstance.destroy();

            const ctxTrend = document.getElementById('trendChart').getContext('2d');
            trendChartInstance = new Chart(ctxTrend, {
                type: 'line',
                data: { labels: labels, datasets: datasets },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { position: 'top', align: 'end', labels: { usePointStyle: true, boxWidth: 8, font: { weight: '600' } } },
                        tooltip: {
                            callbacks: {
                                label: (ctx) => {
                                    let formatter = getFormatter(m1_val); 
                                    if (ctx.datasetIndex === 1) formatter = getFormatter(m2_val);
                                    if (ctx.datasetIndex === 2) formatter = getFormatter(m1_val); // MA ikut Y1 format
                                    return ctx.dataset.label + ': ' + formatter(ctx.raw);
                                }
                            }
                        }
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { maxRotation: group_by_state === 'daily' ? 45 : 0 } },
                        y1: {
                            type: 'linear', display: true, position: 'left',
                            title: { display: true, text: labelY1, color: '#10b981', font: { size: 11, weight: 'bold' } },
                            grid: { color: '#f1f5f9' },
                            ticks: { callback: (v) => isY1Money ? 'Rp ' + (v / 1000000).toFixed(0) + ' Jt' : numberFormatter(v) }
                        },
                        y2: {
                            type: 'linear', display: true, position: 'right',
                            title: { display: true, text: labelY2, color: '#E11D48', font: { size: 11, weight: 'bold' } },
                            grid: { drawOnChartArea: false },
                            ticks: { callback: (v) => isY2Money ? 'Rp ' + (v / 1000000).toFixed(0) + ' Jt' : numberFormatter(v) }
                        }
                    }
                }
            });
        }
        
        renderTrendChart();

        // 2. GRAFIK DOUGHNUT
        if (chartPlatSpendData.length > 0) {
            new Chart(document.getElementById('platformSpendChart').getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: chartPlatSpendData.map(d => d.platform),
                    datasets: [{
                        data: chartPlatSpendData.map(d => parseFloat(d.spend)),
                        backgroundColor: chartPlatSpendData.map(d => platColors[d.platform] || platColors['Lainnya']),
                        borderWidth: 2, borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false, cutout: '70%',
                    plugins: {
                        legend: { position: 'bottom', labels: { usePointStyle: true, padding: 25 } },
                        tooltip: { callbacks: { label: (ctx) => ctx.label + ': ' + currencyFormatter(ctx.raw) } }
                    }
                }
            });
        }

        // 3. GRAFIK BAR
        if (chartPlatRoasData.length > 0) {
            const sortedRoas = [...chartPlatRoasData].sort((a,b) => b.roas - a.roas);
            new Chart(document.getElementById('platformRoasChart').getContext('2d'), {
                type: 'bar',
                data: {
                    labels: sortedRoas.map(d => d.platform),
                    datasets: [{
                        data: sortedRoas.map(d => parseFloat(d.roas)),
                        backgroundColor: sortedRoas.map(d => platColors[d.platform] + 'CC'),
                        borderColor: sortedRoas.map(d => platColors[d.platform]),
                        borderWidth: 1, borderRadius: 6, barThickness: 45,
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false }, tooltip: { callbacks: { label: (ctx) => 'ROAS: ' + percentFormatter(ctx.raw) } } },
                    scales: {
                        x: { grid: { display: false } },
                        y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { callback: (v) => v + 'x' } }
                    }
                }
            });
        }
    </script>
</body>
</html>