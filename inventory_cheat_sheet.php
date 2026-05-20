<?php
session_start();
require 'koneksi.php'; 

// ==============================================================================
// 1. CEK SESSION 
// ==============================================================================
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
$username_session = $_SESSION['username'];
$nama_user = $_SESSION['nama'] ?? $username_session;

$user_avatar = ''; // Siapkan variabel avatar

try {
    // Tambahkan pemanggilan kolom 'avatar'
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
// 2. AMBIL DATA RESEP & BAHAN BAKUNYA (Termasuk target_qty hasil update terbaru)
// ==============================================================================
$recipe_data = [];

try {
    // Ambil Data Kepala Resep + target_qty + Info Produk Jadinya
    $sqlRecipes = "
        SELECT r.id, r.nama_resep, r.sku_produk, r.target_qty, i.nama_barang as nama_produk, i.satuan as satuan_produk 
        FROM jurnal_transaksi_atasi.db_recipes r 
        JOIN jurnal_transaksi_atasi.db_inventory i ON r.sku_produk = i.sku
        ORDER BY r.nama_resep ASC
    ";
    $recipes = $pdo->query($sqlRecipes)->fetchAll(PDO::FETCH_ASSOC);

    // Ambil Data Rincian Bahan Baku + Sisa Stok Gudang Saat Ini
    $sqlItems = "
        SELECT ri.id_resep, ri.sku_bahan, ri.qty_dibutuhkan, i.nama_barang as nama_bahan, i.satuan as satuan_bahan, i.current_stock 
        FROM jurnal_transaksi_atasi.db_recipe_items ri 
        JOIN jurnal_transaksi_atasi.db_inventory i ON ri.sku_bahan = i.sku
    ";
    $items = $pdo->query($sqlItems)->fetchAll(PDO::FETCH_ASSOC);

    // Gabungkan Items ke dalam Masing-Masing Resep
    foreach ($recipes as $r) {
        $r['bahan'] = [];
        foreach ($items as $item) {
            if ($item['id_resep'] == $r['id']) {
                $r['bahan'][] = $item;
            }
        }
        $recipe_data[$r['id']] = $r; // Jadikan ID resep sebagai Key Array
    }

} catch (PDOException $e) {
    // Abaikan jika error saat load awal
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cheat Sheet Produksi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        ::-webkit-scrollbar { width: 8px; height: 10px; }
        ::-webkit-scrollbar-track { background: #f8fafc; border-radius: 4px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        .glass-panel { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); }
        .animate-fade-in { animation: fadeIn 0.3s ease-in-out; } 
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }
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
            <a href="inventory_dashboard.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-orange-50 hover:bg-white/10 hover:text-white transition-colors whitespace-nowrap group">
                <svg class="w-5 h-5 shrink-0 opacity-70 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                <span class="font-medium tracking-wide text-sm">Visual Dashboard</span>
            </a>
            <a href="inventory_data.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-orange-50 hover:bg-white/10 hover:text-white transition-colors whitespace-nowrap group">
                <svg class="w-5 h-5 shrink-0 opacity-70 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path></svg>
                <span class="font-medium tracking-wide text-sm">Master Barang</span>
            </a>
            <a href="inventory_in.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-orange-50 hover:bg-white/10 hover:text-white transition-colors whitespace-nowrap group">
                <svg class="w-5 h-5 shrink-0 opacity-70 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"></path></svg>
                <span class="font-medium tracking-wide text-sm">Stock In</span>
            </a>
            <a href="inventory_out.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-orange-50 hover:bg-white/10 hover:text-white transition-colors whitespace-nowrap group">
                <svg class="w-5 h-5 shrink-0 transform rotate-180 opacity-70 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"></path></svg>
                <span class="font-medium tracking-wide text-sm">Stock Out</span>
            </a>
            
            <div class="mt-8 pt-4 border-t border-white/10">
                <p class="px-3 mb-2 text-[10px] uppercase font-bold tracking-widest text-orange-200/60">Produksi & Formula</p>
                <a href="inventory_formula.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-orange-50 hover:bg-white/10 hover:text-white transition-colors whitespace-nowrap group">
                    <svg class="w-5 h-5 shrink-0 opacity-70 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.618.309a2 2 0 01-1.789 0l-.618-.309a6 6 0 00-3.86-.517l-2.387.477a2 2 0 00-1.022.547V6.818a2 2 0 011.022-1.745l2.387-.477a6 6 0 013.86.517l.618.309a2 2 0 001.789 0l.618-.309a6 6 0 013.86-.517l2.387.477a2 2 0 011.022 1.745v8.61z"></path></svg>
                    <span class="font-medium tracking-wide text-sm">Formula Produksi</span>
                </a>
                <a href="inventory_cheat_sheet.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-white/20 text-white shadow-inner whitespace-nowrap">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    <span class="font-semibold tracking-wide text-sm">Cheat Sheet</span>
                </a>
            </div>
        </nav>
    </aside>

    <main class="flex-1 flex flex-col h-screen min-w-0 overflow-hidden relative">
        <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6 shrink-0 shadow-sm z-30">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="p-2 bg-slate-50 rounded-md hover:bg-slate-100 text-slate-600 transition border border-slate-200 shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                <div>
                    <h1 class="text-xl font-bold text-slate-800">Cheat Sheet Produksi</h1>
                    <div class="flex items-center gap-2 text-xs font-medium mt-0.5">
                        <span class="text-[#F59E0B]">ATASI</span>
                        <span class="text-slate-400">/</span>
                        <span class="text-slate-500">Eksekusi BOM</span>
                    </div>
                </div>
            </div>
            
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
                
                <div id="profileMenu" class="hidden absolute right-0 mt-3 w-48 bg-white rounded-lg shadow-xl border border-slate-200 z-50 py-1.5 overflow-hidden" onclick="event.stopPropagation()">
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
        </header>

        <div class="p-6 flex flex-col flex-1 min-h-0 overflow-y-auto">
            
            <div class="max-w-4xl mx-auto w-full">
                <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm mb-6">
                    <h2 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-[#F59E0B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        Konfigurasi Produksi
                    </h2>
                    
                    <form id="cheatSheetForm" onsubmit="submitProduksi(event)">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-xs font-bold text-slate-500 uppercase tracking-wide">Pilih Formula (Resep) <span class="text-red-500">*</span></label>
                                <select id="selectResep" name="id_resep" class="w-full border border-slate-300 p-3 rounded-lg text-sm outline-none focus:border-[#F59E0B] focus:ring-1 focus:ring-[#F59E0B] bg-white transition shadow-sm" onchange="kalkulasiKebutuhan()" required>
                                    <option value="">-- Pilih Formula --</option>
                                    <?php foreach($recipe_data as $id => $r): ?>
                                        <option value="<?= $id ?>"><?= htmlspecialchars($r['nama_resep']) ?> (<?= htmlspecialchars($r['nama_produk']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="space-y-2">
                                <label class="text-xs font-bold text-slate-500 uppercase tracking-wide">Jumlah Produksi <span class="text-red-500">*</span></label>
                                <div class="flex">
                                    <input type="number" id="inputQty" name="qty_produksi" min="0.01" step="0.01" value="1" class="w-full border border-slate-300 p-3 rounded-l-lg text-sm outline-none focus:border-[#F59E0B] focus:ring-1 focus:ring-[#F59E0B] transition shadow-sm" oninput="kalkulasiKebutuhan()" required disabled>
                                    <span id="satuanProdukLabel" class="bg-slate-100 border border-l-0 border-slate-300 px-4 py-3 rounded-r-lg text-sm font-bold text-slate-500 flex items-center">-</span>
                                </div>
                            </div>
                        </div>

                        <div id="panelBahan" class="mt-8 hidden animate-fade-in">
                            <h3 class="text-sm font-bold text-slate-800 mb-3 border-b border-slate-200 pb-2">Analisis Kebutuhan Bahan Baku</h3>
                            <div class="bg-slate-50 border border-slate-200 rounded-lg overflow-hidden">
                                <table class="w-full text-sm text-left">
                                    <thead class="bg-slate-100 text-slate-600 uppercase text-[10px] font-bold border-b border-slate-200">
                                        <tr>
                                            <th class="px-4 py-3">Nama Bahan Baku</th>
                                            <th class="px-4 py-3 text-right">Kebutuhan</th>
                                            <th class="px-4 py-3 text-right">Stok Gudang</th>
                                            <th class="px-4 py-3 text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbodyBahan" class="divide-y divide-slate-200 text-slate-700 font-medium">
                                        </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="mt-8 flex justify-end">
                            <button type="submit" id="btnProduksi" class="px-8 py-3 bg-[#F59E0B] hover:bg-orange-600 text-white rounded-lg text-sm font-bold shadow-lg shadow-orange-500/30 transition flex items-center gap-2 opacity-50 cursor-not-allowed" disabled>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                Eksekusi Produksi Sekarang
                            </button>
                        </div>
                    </form>
                </div>
                
                <div class="bg-blue-50 border border-emerald-200 p-4 rounded-xl flex gap-4 text-emerald-800 text-sm">
                    <svg class="w-6 h-6 shrink-0 mt-0.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <div>
                        <p class="font-bold mb-1">Bagaimana Fitur Ini Bekerja?</p>
                        <p class="text-emerald-700/80 leading-relaxed">Ketika Anda mengeklik Eksekusi Produksi, sistem akan otomatis melakukan jurnal <strong>Stock Out</strong> untuk semua bahan baku yang digunakan, and mencatat <strong>Stock In</strong> untuk produk jadi yang dihasilkan. Saldo di Master Barang akan ter-update secara otomatis secara real-time.</p>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <script>
        function toggleSidebar() { 
            document.getElementById('mainSidebar').classList.toggle('w-64'); 
            document.getElementById('mainSidebar').classList.toggle('w-0'); 
        }
        function toggleProfileMenu(e) { 
            if(e) e.stopPropagation(); 
            document.getElementById('profileMenu').classList.toggle('hidden'); 
        }
        function closeProfileMenu() { 
            const m = document.getElementById('profileMenu'); 
            if (m && !m.classList.contains('hidden')) {
                m.classList.add('hidden'); 
            }
        }

        // DATABASE RESEP DARI PHP DI-CONVERT KE JSON JAVASCRIPT
        const dataResep = <?= json_encode($recipe_data) ?>;
        
        function kalkulasiKebutuhan() {
            const select = document.getElementById('selectResep');
            const inputQty = document.getElementById('inputQty');
            const labelSatuan = document.getElementById('satuanProdukLabel');
            const panelBahan = document.getElementById('panelBahan');
            const tbodyBahan = document.getElementById('tbodyBahan');
            const btnProduksi = document.getElementById('btnProduksi');

            const idResep = select.value;

            // Jika tidak ada resep yang dipilih
            if (!idResep) {
                inputQty.disabled = true;
                inputQty.value = 1;
                inputQty.removeAttribute('data-last-resep');
                labelSatuan.textContent = '-';
                panelBahan.classList.add('hidden');
                btnProduksi.disabled = true;
                btnProduksi.classList.add('opacity-50', 'cursor-not-allowed');
                return;
            }

            const resep = dataResep[idResep];
            labelSatuan.textContent = resep.satuan_produk;

            // === PINTAR: Auto-fill Kuantitas Produksi sesuai target_qty rancangan awal formula ===
            if (inputQty.dataset.lastResep !== idResep) {
                inputQty.value = parseFloat(resep.target_qty) || 1;
                inputQty.dataset.lastResep = idResep; // Kunci biar ga nge-reset pas user ngetik manual
            }

            inputQty.disabled = false;
            panelBahan.classList.remove('hidden');

            const qtyProduksi = parseFloat(inputQty.value) || 0;
            
            let htmlTable = '';
            let bisaProduksi = true;

            resep.bahan.forEach(item => {
                const totalKebutuhan = parseFloat(item.qty_dibutuhkan) * qtyProduksi;
                const stokGudang = parseFloat(item.current_stock) || 0;
                const isCukup = stokGudang >= totalKebutuhan;

                if (!isCukup) bisaProduksi = false;

                const statusColor = isCukup ? 'text-emerald-600 bg-emerald-50 border-emerald-200' : 'text-red-600 bg-red-50 border-red-200';
                const statusIcon = isCukup ? 
                    `<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> AMAN` : 
                    `<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg> KURANG`;

                // PEMBULATAN BARU: Maksimal 2 digit di belakang koma biar bersih and rapi sesuai permintaan
                const formatConfig = { minimumFractionDigits: 0, maximumFractionDigits: 2 };

                htmlTable += `
                    <tr class="hover:bg-white transition">
                        <td class="px-4 py-3 flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full ${isCukup ? 'bg-emerald-500' : 'bg-red-500'}"></span>
                            ${item.nama_bahan}
                        </td>
                        <td class="px-4 py-3 text-right font-bold text-slate-800">
                            ${totalKebutuhan.toLocaleString('id-ID', formatConfig)} <span class="text-xs text-slate-400 font-medium">${item.satuan_bahan}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <span class="${isCukup ? 'text-slate-600' : 'text-red-600 font-bold'}">${stokGudang.toLocaleString('id-ID', formatConfig)}</span> <span class="text-xs text-slate-400 font-medium">${item.satuan_bahan}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold border ${statusColor}">
                                ${statusIcon}
                            </span>
                        </td>
                    </tr>
                `;
            });

            tbodyBahan.innerHTML = htmlTable;

            // Kontrol Tombol Eksekusi
            if (bisaProduksi && qtyProduksi > 0) {
                btnProduksi.disabled = false;
                btnProduksi.classList.remove('opacity-50', 'cursor-not-allowed');
            } else {
                btnProduksi.disabled = true;
                btnProduksi.classList.add('opacity-50', 'cursor-not-allowed');
            }
        }

        async function submitProduksi(e) {
            e.preventDefault();
            
            const form = e.target;
            const select = document.getElementById('selectResep');
            const qty = document.getElementById('inputQty').value;
            
            const resep = dataResep[select.value];

            Swal.fire({
                title: 'Konfirmasi Produksi',
                html: `Anda akan memproduksi <b>${qty} ${resep.satuan_produk}</b> dari <b>${resep.nama_produk}</b>.<br><br>Sistem akan memotong otomatis stok bahan baku di gudang. Lanjutkan?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#F59E0B',
                cancelButtonColor: '#94a3b8',
                confirmButtonText: 'Ya, Eksekusi!',
                cancelButtonText: 'Batal'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    
                    Swal.fire({ title: 'Memproses Jurnal...', html: 'Mohon jangan tutup halaman ini.', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

                    const formData = new FormData(form);
                    formData.append('action', 'produksi');

                    try {
                        const response = await fetch('proses_inventory_cheat_sheet.php', { method: 'POST', body: formData });
                        const res = await response.json();
                        
                        if (res.status === 'success') {
                            Swal.fire({
                                title: 'Produksi Berhasil!',
                                text: res.message,
                                icon: 'success',
                                confirmButtonColor: '#10B981'
                            }).then(() => {
                                location.reload(); // Refresh halaman agar data stok terbaru masuk
                            });
                        } else {
                            Swal.fire('Gagal!', res.message, 'error');
                        }
                    } catch (err) {
                        Swal.fire('Error!', 'Terjadi kesalahan komunikasi dengan server.', 'error');
                    }
                }
            });
        }
    </script>
</body>
</html>