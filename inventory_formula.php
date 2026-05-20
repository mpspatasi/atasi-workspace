<?php
session_start();
require 'koneksi.php'; 

// ==============================================================================
// 1. CEK SESSION & PROTEKSI ROLE (SUPERADMIN ONLY)
// ==============================================================================
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
$username_session = $_SESSION['username'];
$nama_user = $_SESSION['nama'] ?? $username_session;

$isSuperAdmin = (isset($_SESSION['modul_akses']) && $_SESSION['modul_akses'] === 'Superadmin');
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
            $user_avatar = "https://api.dicebear.com/7.x/notionists/svg?seed=" . urlencode($userData['username']) . "&backgroundColor=e2e8f0";
        }
    }
} catch (PDOException $e) {}

$inisial_user = strtoupper(substr($nama_user, 0, 1));

// ==============================================================================
// 2. AMBIL DATA PRODUK JADI & BAHAN BAKU UNTUK DROPDOWN
// ==============================================================================
$list_produk = [];
$list_bahan = [];
$list_resep = [];

if ($isSuperAdmin) {
    try {
        $stmtProduk = $pdo->query("SELECT sku, nama_barang, satuan FROM jurnal_transaksi_atasi.db_inventory WHERE kategori = 'Produk Jadi' ORDER BY nama_barang ASC");
        $list_produk = $stmtProduk->fetchAll(PDO::FETCH_ASSOC);

        $stmtBahan = $pdo->query("SELECT sku, nama_barang, satuan FROM jurnal_transaksi_atasi.db_inventory WHERE kategori = 'Bahan Baku' ORDER BY nama_barang ASC");
        $list_bahan = $stmtBahan->fetchAll(PDO::FETCH_ASSOC);

        $stmtResep = $pdo->query("
            SELECT r.*, i.nama_barang, i.satuan 
            FROM jurnal_transaksi_atasi.db_recipes r 
            JOIN jurnal_transaksi_atasi.db_inventory i ON r.sku_produk = i.sku 
            ORDER BY r.id DESC
        ");
        $list_resep = $stmtResep->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {}
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formula Produksi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        ::-webkit-scrollbar { width: 8px; height: 10px; }
        ::-webkit-scrollbar-track { background: #f8fafc; border-radius: 4px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        .modal-overlay { transition: opacity 0.2s ease-out; }
        .modal-box { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .animate-fade-in { animation: fadeIn 0.3s ease-in-out; } 
        @keyframes fadeIn { 
            from { opacity: 0; transform: translateY(-5px); } 
            to { opacity: 1; transform: translateY(0); } 
        }
    </style>
</head>
<body class="bg-slate-50 font-sans text-slate-800 h-screen flex overflow-hidden" onclick="closeProfileMenu(event)">

    <aside id="mainSidebar" class="w-64 bg-[#F59E0B] text-white flex flex-col shrink-0 overflow-hidden shadow-xl z-40 transition-all duration-300 relative">
        <div class="h-16 flex items-center px-6 border-b border-white/10 shrink-0 gap-3">
            <div class="w-8 h-8 bg-white rounded-md flex items-center justify-center text-[#F59E0B] shrink-0 shadow-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
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
                <a href="inventory_formula.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg bg-white/20 text-white shadow-inner whitespace-nowrap">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.618.309a2 2 0 01-1.789 0l-.618-.309a6 6 0 00-3.86-.517l-2.387.477a2 2 0 00-1.022.547V6.818a2 2 0 011.022-1.745l2.387-.477a6 6 0 013.86.517l.618.309a2 2 0 001.789 0l.618-.309a6 6 0 013.86-.517l2.387.477a2 2 0 011.022 1.745v8.61z"></path></svg>
                    <span class="font-semibold tracking-wide text-sm">Formula Produksi</span>
                </a>
                <a href="inventory_cheat_sheet.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-orange-50 hover:bg-white/10 hover:text-white transition-colors whitespace-nowrap group">
                    <svg class="w-5 h-5 shrink-0 opacity-70 group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    <span class="font-medium tracking-wide text-sm">Cheat Sheet</span>
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
                    <h1 class="text-xl font-bold text-slate-800">Formula Produksi</h1>
                    <div class="flex items-center gap-2 text-xs font-medium mt-0.5">
                        <span class="text-[#F59E0B]">ATASI</span>
                        <span class="text-slate-400">/</span>
                        <span class="text-slate-500 font-bold">Master Resep BOM</span>
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
                        <p class="text-sm font-semibold text-slate-800 truncate"><?= htmlspecialchars($username_session) ?></p>
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

        <div class="relative flex flex-col flex-1 min-h-0">
            
            <?php if (!$isSuperAdmin): ?>
            <div class="absolute inset-0 z-[40] bg-white/40 backdrop-blur-sm flex items-center justify-center">
                <div class="bg-white p-8 rounded-2xl shadow-2xl border border-slate-200 text-center max-w-sm mx-4 transform -translate-y-12">
                    <div class="w-20 h-20 bg-rose-50 text-rose-500 rounded-full flex items-center justify-center mx-auto mb-6 shadow-inner">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    </div>
                    <h2 class="text-xl font-bold text-slate-800 mb-2">Akses Terbatas</h2>
                    <p class="text-slate-500 text-sm font-medium leading-relaxed">Hanya Superadmin yang Bisa Melihat dan Mengedit Halaman Ini.</p>
                    <a href="inventory_dashboard.php" class="mt-8 inline-block px-6 py-2.5 bg-slate-800 text-white text-xs font-bold rounded-lg hover:bg-slate-700 transition shadow-md">Kembali ke Dashboard</a>
                </div>
            </div>
            <?php endif; ?>

            <div class="p-6 flex flex-col flex-1 overflow-y-auto <?= !$isSuperAdmin ? 'blur-sm pointer-events-none select-none opacity-60' : '' ?>">
                
                <div class="mb-4 shrink-0 bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex justify-between items-center gap-4">
                    <div class="flex-1">
                        <p class="text-sm text-slate-500 font-medium">Atur resep (BOM) untuk setiap produk jadi ATASI di sini. Formula ini akan digunakan secara otomatis saat produksi di menu Cheat Sheet.</p>
                    </div>
                    <button onclick="openAddModal()" class="px-4 py-2 bg-[#F59E0B] hover:bg-orange-600 text-white rounded-lg text-sm font-bold shadow-md flex items-center gap-2 transition whitespace-nowrap">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg> 
                        Buat Formula Baru
                    </button>
                </div>

                <div class="bg-white border border-slate-200 shadow-sm rounded-lg overflow-hidden flex-grow relative">
                    <table class="w-full text-sm text-left whitespace-nowrap">
                        <thead class="bg-slate-50 text-slate-600 uppercase text-[11px] font-bold border-b border-slate-200">
                            <tr>
                                <th class="px-4 py-3 w-16 text-center">No</th>
                                <th class="px-4 py-3">Nama Formula</th>
                                <th class="px-4 py-3">Produk Jadi (Target)</th>
                                <th class="px-4 py-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <?php if(empty($list_resep)): ?>
                                <tr>
                                    <td colspan="4" class="p-8 text-center text-slate-400 font-medium">Belum ada formula produksi yang dibuat.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($list_resep as $index => $row): ?>
                                <?php 
                                    // Ambil nilai target_qty, default ke 1 jika null (buat data lama)
                                    $target_val = isset($row['target_qty']) ? $row['target_qty'] : 1;
                                    // Tampilkan bulat jika tidak ada desimal
                                    $target_display = (floor($target_val) == $target_val) ? number_format($target_val, 0, ',', '.') : number_format($target_val, 2, ',', '.');
                                ?>
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="px-4 py-3 text-center text-slate-400 font-bold"><?= $index + 1 ?></td>
                                    <td class="px-4 py-3 font-semibold text-slate-800"><?= htmlspecialchars($row['nama_resep']) ?></td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-blue-50 text-blue-700 border border-blue-200 font-bold text-xs">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> 
                                            <?= htmlspecialchars($row['nama_barang']) ?>
                                        </span>
                                        <span class="text-xs text-slate-400 ml-1">(Berdasarkan Produksi <?= $target_display ?> <?= htmlspecialchars($row['satuan']) ?>)</span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <button onclick="lihatDetail(<?= $row['id'] ?>, <?= htmlspecialchars($target_val) ?>)" class="bg-indigo-50 text-indigo-600 px-2.5 py-1.5 rounded-md text-[11px] font-bold hover:bg-indigo-100 transition">DETAIL</button>
                                            
                                            <button onclick='bukaEdit(<?= $row['id'] ?>, <?= json_encode($row['nama_resep']) ?>, <?= json_encode($row['sku_produk']) ?>, <?= json_encode($row['keterangan'] ?? '') ?>, <?= htmlspecialchars($target_val) ?>)' class="bg-orange-50 text-[#F59E0B] px-2.5 py-1.5 rounded-md text-[11px] font-bold hover:bg-orange-100 transition">EDIT</button>
                                            
                                            <button onclick="hapusResep(<?= $row['id'] ?>)" class="bg-rose-50 text-rose-600 px-2.5 py-1.5 rounded-md text-[11px] font-bold hover:bg-rose-100 transition">HAPUS</button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div> 
        </div>
    </main>

    <?php if ($isSuperAdmin): ?>
    <div id="addModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm modal-overlay" onclick="closeModal('addModal')"></div>
        <form id="formulaForm" onsubmit="submitFormula(event, 'add')" class="bg-white rounded-xl shadow-2xl w-full max-w-2xl relative z-10 modal-box transform scale-95 opacity-0 flex flex-col max-h-[90vh]">
            <div class="px-6 py-4 border-b flex justify-between items-center bg-slate-50 rounded-t-xl shrink-0">
                <h3 class="font-bold text-slate-800 text-lg flex items-center gap-2">
                    <svg class="w-5 h-5 text-[#F59E0B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.618.309a2 2 0 01-1.789 0l-.618-.309a6 6 0 00-3.86-.517l-2.387.477a2 2 0 00-1.022.547V6.818a2 2 0 011.022-1.745l2.387-.477a6 6 0 013.86.517l.618.309a2 2 0 001.789 0l.618-.309a6 6 0 013.86-.517l2.387.477a2 2 0 011.022 1.745v8.61z"></path></svg>
                    Buat Formula Baru
                </h3>
                <button type="button" onclick="closeModal('addModal')" class="text-slate-400 hover:text-red-500 transition text-xl font-bold">✕</button>
            </div>
            
            <div class="p-6 overflow-y-auto flex-1 space-y-5 bg-white">
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="text-[11px] font-bold text-slate-500 uppercase">Nama Formula <span class="text-red-500">*</span></label>
                        <input type="text" name="nama_resep" class="w-full border p-2.5 rounded-lg text-sm outline-none focus:border-[#F59E0B]" placeholder="Cth: Resep Standar" required>
                    </div>
                    <div class="space-y-1">
                        <label class="text-[11px] font-bold text-slate-500 uppercase">Pilih Produk Jadi <span class="text-red-500">*</span></label>
                        <select name="sku_produk" class="w-full border p-2.5 rounded-lg text-sm outline-none focus:border-[#F59E0B] bg-white" onchange="updateTargetSatuan(this, 'add')" required>
                            <option value="">-- Pilih Produk --</option>
                            <?php foreach($list_produk as $p): ?>
                                <option value="<?= $p['sku'] ?>" data-satuan="<?= htmlspecialchars($p['satuan']) ?>"><?= htmlspecialchars($p['nama_barang']) ?> (<?= $p['satuan'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="space-y-1">
                    <label class="text-[11px] font-bold text-slate-500 uppercase">Target Kuantitas Produk <span class="text-red-500">*</span></label>
                    <div class="flex">
                        <input type="number" name="target_qty" id="add_target_qty" class="w-full border p-2.5 rounded-l-lg text-sm outline-none focus:border-[#F59E0B]" placeholder="Cth: 220" value="1" min="0.01" step="0.01" oninput="updateLabelKomposisi('add')" required>
                        <input type="text" id="add_satuan_target" class="w-24 border border-l-0 p-2.5 rounded-r-lg text-sm bg-slate-100 text-slate-600 font-bold outline-none text-center" value="Satuan" readonly>
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1">*Ubah angka ini jika ingin memodelkan bahan baku secara borongan/batch.</p>
                </div>

                <div class="border-t border-slate-200 my-2"></div>

                <div>
                    <div class="flex justify-between items-center mb-3">
                        <label id="add_label_komposisi" class="text-[11px] font-bold text-slate-500 uppercase">Komposisi Bahan Baku (Untuk Produksi 1 Satuan)</label>
                        <button type="button" onclick="tambahBahan('bahanContainer')" class="px-3 py-1.5 bg-emerald-50 text-emerald-600 border border-emerald-200 rounded-md text-[11px] font-bold hover:bg-emerald-100 transition flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg> Tambah Bahan
                        </button>
                    </div>
                    
                    <div id="bahanContainer" class="space-y-3"></div>
                </div>

                <div class="space-y-1 mt-4">
                    <label class="text-[11px] font-bold text-slate-500 uppercase">Keterangan / Instruksi Khusus (Opsional)</label>
                    <textarea name="keterangan" rows="2" class="w-full border p-2.5 rounded-lg text-sm outline-none focus:border-[#F59E0B]"></textarea>
                </div>
            </div>

            <div class="px-6 py-4 bg-slate-50 flex justify-end gap-3 rounded-b-xl border-t shrink-0">
                <button type="button" onclick="closeModal('addModal')" class="px-5 py-2.5 text-sm font-bold text-slate-500 hover:bg-slate-200 rounded-lg transition">Batal</button>
                <button type="submit" class="px-6 py-2.5 bg-[#F59E0B] text-white rounded-lg text-sm font-bold hover:bg-orange-600 shadow-md transition">Simpan Formula</button>
            </div>
        </form>
    </div>

    <div id="editModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm modal-overlay" onclick="closeModal('editModal')"></div>
        <form id="editFormulaForm" onsubmit="submitFormula(event, 'edit')" class="bg-white rounded-xl shadow-2xl w-full max-w-2xl relative z-10 modal-box transform scale-95 opacity-0 flex flex-col max-h-[90vh]">
            <input type="hidden" name="id_resep" id="edit_id_resep">
            
            <div class="px-6 py-4 border-b flex justify-between items-center bg-slate-50 rounded-t-xl shrink-0">
                <h3 class="font-bold text-slate-800 text-lg flex items-center gap-2">
                    <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                    Edit Formula
                </h3>
                <button type="button" onclick="closeModal('editModal')" class="text-slate-400 hover:text-red-500 transition text-xl font-bold">✕</button>
            </div>
            
            <div class="p-6 overflow-y-auto flex-1 space-y-5 bg-white">
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="text-[11px] font-bold text-slate-500 uppercase">Nama Formula <span class="text-red-500">*</span></label>
                        <input type="text" name="nama_resep" id="edit_nama_resep" class="w-full border p-2.5 rounded-lg text-sm outline-none focus:border-[#F59E0B]" required>
                    </div>
                    <div class="space-y-1">
                        <label class="text-[11px] font-bold text-slate-500 uppercase">Pilih Produk Jadi <span class="text-red-500">*</span></label>
                        <select name="sku_produk" id="edit_sku_produk" class="w-full border p-2.5 rounded-lg text-sm outline-none focus:border-[#F59E0B] bg-white" onchange="updateTargetSatuan(this, 'edit')" required>
                            <?php foreach($list_produk as $p): ?>
                                <option value="<?= $p['sku'] ?>" data-satuan="<?= htmlspecialchars($p['satuan']) ?>"><?= htmlspecialchars($p['nama_barang']) ?> (<?= $p['satuan'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="space-y-1">
                    <label class="text-[11px] font-bold text-slate-500 uppercase">Target Kuantitas Produk <span class="text-red-500">*</span></label>
                    <div class="flex">
                        <input type="number" name="target_qty" id="edit_target_qty" class="w-full border p-2.5 rounded-l-lg text-sm outline-none focus:border-[#F59E0B]" value="1" min="0.01" step="0.01" oninput="updateLabelKomposisi('edit')" required>
                        <input type="text" id="edit_satuan_target" class="w-24 border border-l-0 p-2.5 rounded-r-lg text-sm bg-slate-100 text-slate-600 font-bold outline-none text-center" value="Satuan" readonly>
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1">*Ubah angka ini jika ingin memodelkan bahan baku secara borongan/batch.</p>
                </div>

                <div class="border-t border-slate-200 my-2"></div>

                <div>
                    <div class="flex justify-between items-center mb-3">
                        <label id="edit_label_komposisi" class="text-[11px] font-bold text-slate-500 uppercase">Komposisi Bahan Baku</label>
                        <button type="button" onclick="tambahBahan('edit_bahanContainer')" class="px-3 py-1.5 bg-emerald-50 text-emerald-600 border border-emerald-200 rounded-md text-[11px] font-bold hover:bg-emerald-100 transition flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg Tambah Bahan
                        </button>
                    </div>
                    
                    <div id="edit_bahanContainer" class="space-y-3"></div>
                </div>

                <div class="space-y-1 mt-4">
                    <label class="text-[11px] font-bold text-slate-500 uppercase">Keterangan / Instruksi Khusus (Opsional)</label>
                    <textarea name="keterangan" id="edit_keterangan" rows="2" class="w-full border p-2.5 rounded-lg text-sm outline-none focus:border-[#F59E0B]"></textarea>
                </div>
            </div>

            <div class="px-6 py-4 bg-slate-50 flex justify-end gap-3 rounded-b-xl border-t shrink-0">
                <button type="button" onclick="closeModal('editModal')" class="px-5 py-2.5 text-sm font-bold text-slate-500 hover:bg-slate-200 rounded-lg transition">Batal</button>
                <button type="submit" class="px-6 py-2.5 bg-[#F59E0B] text-white rounded-lg text-sm font-bold hover:bg-orange-600 shadow-md transition">Update Formula</button>
            </div>
        </form>
    </div>

    <div id="modalDetailBOM" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm modal-overlay" onclick="closeModal('modalDetailBOM')"></div>
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl relative z-10 modal-box transform scale-95 opacity-0 flex flex-col max-h-[90vh]">
            <div class="px-6 py-4 border-b flex justify-between items-center bg-slate-50 rounded-t-xl shrink-0">
                <h3 class="font-bold text-slate-800 text-lg">Rincian Bahan Baku <span id="label_detail_satuan" class="text-sm text-slate-500 font-medium ml-1"></span></h3>
                <button type="button" onclick="closeModal('modalDetailBOM')" class="text-slate-400 hover:text-red-500 transition text-xl font-bold">✕</button>
            </div>
            
            <div class="p-6 overflow-y-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-100 text-slate-600 uppercase tracking-wider text-[11px] font-bold">
                        <tr>
                            <th class="px-4 py-3 border-b border-slate-200">SKU Bahan</th>
                            <th class="px-4 py-3 border-b border-slate-200">Nama Bahan Baku</th>
                            <th class="px-4 py-3 border-b border-slate-200 text-right">Kebutuhan (Qty)</th>
                            <th class="px-4 py-3 border-b border-slate-200">Satuan</th>
                        </tr>
                    </thead>
                    <tbody id="tabel-detail-bahan" class="divide-y divide-slate-100"></tbody>
                </table>
            </div>
            
            <div class="px-6 py-4 bg-slate-50 flex justify-end gap-3 rounded-b-xl border-t shrink-0">
                <button type="button" onclick="closeModal('modalDetailBOM')" class="px-5 py-2.5 bg-[#F59E0B] text-white rounded-lg text-sm font-bold hover:bg-orange-600 shadow-md transition">Tutup</button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script>
        function toggleSidebar() { document.getElementById('mainSidebar').classList.toggle('w-64'); document.getElementById('mainSidebar').classList.toggle('w-0'); }
        function toggleProfileMenu(e) { if(e) e.stopPropagation(); document.getElementById('profileMenu').classList.toggle('hidden'); }
        function closeProfileMenu() { const m = document.getElementById('profileMenu'); if (m && !m.classList.contains('hidden')) { m.classList.add('hidden'); } }

        <?php if ($isSuperAdmin): ?>
        const listBahanJSON = <?= json_encode($list_bahan) ?>;

        function openAddModal() { 
            const m = document.getElementById('addModal'); 
            m.classList.remove('hidden'); 
            setTimeout(() => m.querySelector('.modal-box').classList.add('scale-100', 'opacity-100'), 10);
            
            document.getElementById('formulaForm').reset();
            document.getElementById('add_target_qty').value = 1;
            document.getElementById('add_satuan_target').value = 'Satuan';
            
            document.getElementById('bahanContainer').innerHTML = '';
            tambahBahan('bahanContainer'); 
            updateLabelKomposisi('add');
        }

        function closeModal(id) { 
            const m = document.getElementById(id);
            m.querySelector('.modal-box').classList.remove('scale-100', 'opacity-100'); 
            setTimeout(() => m.classList.add('hidden'), 200); 
        }

        function updateTargetSatuan(selectElem, prefix) {
            const selectedOption = selectElem.options[selectElem.selectedIndex];
            const satuan = selectedOption ? selectedOption.getAttribute('data-satuan') || 'Satuan' : 'Satuan';
            
            const targetSatuanInput = document.getElementById(prefix + '_satuan_target');
            if (targetSatuanInput) targetSatuanInput.value = satuan;
            
            updateLabelKomposisi(prefix);
        }

        function updateLabelKomposisi(prefix) {
            const qty = document.getElementById(prefix + '_target_qty').value || 1;
            const satuan = document.getElementById(prefix + '_satuan_target').value || 'Satuan';
            const label = document.getElementById(prefix + '_label_komposisi');
            // Menghilangkan format desimal nol koma agar lebih bersih di judul
            const cleanQty = parseFloat(qty);
            if(label) label.innerText = `Komposisi Bahan Baku (Untuk Produksi ${cleanQty} ${satuan})`;
        }

        function updateSatuanBahan(selectElem) {
            const selectedOption = selectElem.options[selectElem.selectedIndex];
            const satuan = selectedOption ? selectedOption.getAttribute('data-satuan') || '' : '';
            
            const row = selectElem.closest('.flex');
            const inputSatuan = row.querySelector('input[name="satuan_bahan_manual[]"]');
            if(inputSatuan) inputSatuan.value = satuan;
        }

        function tambahBahan(targetContainerId, sku_selected = '', qty_val = '') {
            const container = document.getElementById(targetContainerId);
            
            let optionsHTML = '<option value="">-- Pilih Bahan Baku --</option>';
            let initialSatuan = '';

            listBahanJSON.forEach(b => {
                let selected = '';
                if(b.sku === sku_selected) {
                    selected = 'selected';
                    initialSatuan = b.satuan;
                }
                optionsHTML += `<option value="${b.sku}" data-satuan="${b.satuan}" ${selected}>${b.nama_barang} (${b.satuan})</option>`;
            });

            // Tampilkan maksimal 2 desimal di layar depan (User Friendly)
            const qty_rounded = qty_val ? parseFloat(qty_val).toFixed(2) : '';

            const row = document.createElement('div');
            row.className = 'flex gap-2 items-start animate-fade-in';
            
            row.innerHTML = `
                <div class="flex-1">
                    <select name="sku_bahan[]" onchange="updateSatuanBahan(this)" class="w-full border p-2 rounded-lg text-sm outline-none focus:border-[#F59E0B] bg-white" required>
                        ${optionsHTML}
                    </select>
                </div>
                <div class="w-28">
                    <input type="number" step="0.01" min="0.01" name="qty_bahan_manual[]" value="${qty_rounded}" class="w-full border p-2 rounded-lg text-sm outline-none focus:border-[#F59E0B]" placeholder="Kuantitas" required>
                </div>
                <div class="w-24">
                    <input type="text" name="satuan_bahan_manual[]" value="${initialSatuan}" class="w-full border p-2 rounded-lg text-sm outline-none focus:border-[#F59E0B] bg-slate-50 text-slate-500" placeholder="Satuan">
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="w-9 h-9 shrink-0 flex items-center justify-center bg-red-50 text-red-500 rounded-lg hover:bg-red-100 transition border border-red-100">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                </button>
            `;
            container.appendChild(row);
        }

        async function submitFormula(e, actionType) {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            
            formData.append('action', actionType);

            const bahanArray = formData.getAll('sku_bahan[]');
            if(bahanArray.length === 0 || bahanArray[0] === '') {
                Swal.fire('Error', 'Pilih minimal 1 bahan baku!', 'warning'); 
                return;
            }

            // AMBIL ANGKA 220 LALU BAGI SEBELUM KIRIM KE DATABASE (Biar Presisi di DB, tapi bulat di layar)
            const targetQty = parseFloat(formData.get('target_qty')) || 1;
            const manualQtys = formData.getAll('qty_bahan_manual[]');
            
            formData.delete('qty_bahan_manual[]');
            formData.delete('satuan_bahan_manual[]'); 
            
            manualQtys.forEach(q => {
                const inputNum = parseFloat(q) || 0;
                const finalPerUnit = inputNum / targetQty; 
                formData.append('qty_bahan[]', finalPerUnit.toFixed(10));
            });

            Swal.fire({ title: 'Menyimpan...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

            try {
                const urlEncodedData = new URLSearchParams(formData).toString();

                const response = await fetch('proses_inventory_formula.php', { 
                    method: 'POST', 
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: urlEncodedData
                });
                
                const textResponse = await response.text();
                let res;
                try {
                    res = JSON.parse(textResponse);
                } catch (parseError) {
                    console.error("Server error mentah:", textResponse);
                    Swal.fire('Gagal!', 'Server mengalami gangguan!', 'error');
                    return;
                }
                
                if (res.status === 'success') {
                    Swal.fire('Berhasil!', res.message, 'success').then(() => location.reload());
                } else {
                    Swal.fire('Gagal!', res.message, 'error');
                }
            } catch (err) {
                Swal.fire('Error!', 'Terjadi kesalahan jaringan atau sistem.', 'error');
            }
        }

        async function bukaEdit(id, nama_resep, sku_produk, keterangan, targetQty) {
            const m = document.getElementById('editModal');
            
            document.getElementById('edit_id_resep').value = id;
            document.getElementById('edit_nama_resep').value = nama_resep;
            document.getElementById('edit_sku_produk').value = sku_produk;
            document.getElementById('edit_keterangan').value = keterangan;

            // Masukin nilai target_qty (misal 220) yang udah diingat sama DB
            document.getElementById('edit_target_qty').value = parseFloat(targetQty);
            
            const editSelectSku = document.getElementById('edit_sku_produk');
            updateTargetSatuan(editSelectSku, 'edit');
            
            const container = document.getElementById('edit_bahanContainer');
            container.innerHTML = '<p class="text-xs text-slate-500 py-3 text-center">Menarik rincian komposisi bahan baku...</p>';

            m.classList.remove('hidden');
            setTimeout(() => m.querySelector('.modal-box').classList.add('scale-100', 'opacity-100'), 10);

            try {
                const response = await fetch('ajax_formula.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'get_detail', id: id })
                });
                const res = await response.json();
                
                container.innerHTML = ''; 

                if (res.status === 'success') {
                    if (res.data.length === 0) {
                        tambahBahan('edit_bahanContainer'); 
                    } else {
                        res.data.forEach(item => {
                            // KALI BALIK DENGAN TARGET QTY BIAR MUNCUL ANGKA ASLI YANG DIINPUT (Misal 200, bukan 0.90)
                            const originalQty = parseFloat(item.qty) * parseFloat(targetQty);
                            tambahBahan('edit_bahanContainer', item.sku_bahan, originalQty);
                        });
                    }
                } else {
                    Swal.fire('Error memuat data', res.pesan, 'error');
                }
            } catch (error) {
                container.innerHTML = '<p class="text-xs text-red-500 py-3 text-center">Koneksi terputus.</p>';
            }
        }

        function hapusResep(id) {
            Swal.fire({
                title: 'Hapus Formula?',
                text: "Formula produksi yang dihapus tidak dapat dikembalikan.",
                icon: 'warning',
                showCancelButton: true, confirmButtonColor: '#ef4444', confirmButtonText: 'Ya, Hapus!'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    const params = new URLSearchParams();
                    params.append('action', 'delete');
                    params.append('id', id);
                    
                    try {
                        const response = await fetch('proses_inventory_formula.php', { 
                            method: 'POST', 
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: params.toString() 
                        });
                        const res = await response.json();
                        
                        if (res.status === 'success') {
                            Swal.fire('Terhapus!', '', 'success').then(() => location.reload());
                        } else {
                            Swal.fire('Gagal!', res.message, 'error');
                        }
                    } catch (err) {
                        Swal.fire('Error!', 'Gagal menghubungi server.', 'error');
                    }
                }
            });
        }

        async function lihatDetail(idFormula, targetQty) {
            const m = document.getElementById('modalDetailBOM');
            m.classList.remove('hidden');
            setTimeout(() => m.querySelector('.modal-box').classList.add('scale-100', 'opacity-100'), 10);
            
            document.getElementById('label_detail_satuan').innerText = `(Untuk Produksi ${parseFloat(targetQty)} Unit)`;

            const tbody = document.getElementById('tabel-detail-bahan');
            tbody.innerHTML = '<tr><td colspan="4" class="text-center py-6 text-slate-500 font-medium">Memuat data bahan baku...</td></tr>';

            try {
                const response = await fetch('ajax_formula.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'get_detail', id: idFormula })
                });
                const res = await response.json();

                if (res.status === 'success') {
                    let html = '';
                    if (res.data.length === 0) {
                        html = '<tr><td colspan="4" class="text-center py-6 text-red-500 font-medium">Bahan baku belum diatur.</td></tr>';
                    } else {
                        res.data.forEach(item => {
                            // Hitung balik angka aslinya buat di popup detail
                            const originalQty = parseFloat(item.qty) * parseFloat(targetQty);
                            const qtyFormatted = originalQty.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
                            
                            html += `
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-4 py-3 font-mono text-slate-500">${item.sku_bahan}</td>
                                <td class="px-4 py-3 font-semibold text-slate-700">${item.nama_barang}</td>
                                <td class="px-4 py-3 text-right font-bold text-[#F59E0B]">${qtyFormatted}</td>
                                <td class="px-4 py-3 text-slate-600">${item.satuan}</td>
                            </tr>`;
                        });
                    }
                    tbody.innerHTML = html;
                } else {
                    tbody.innerHTML = `<tr><td colspan="4" class="text-center py-6 text-red-500 font-medium">Gagal: ${res.pesan}</td></tr>`;
                }
            } catch (error) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center py-6 text-red-500 font-medium">Terjadi kesalahan koneksi.</td></tr>';
            }
        }
        <?php endif; ?>
    </script>
</body>
</html>