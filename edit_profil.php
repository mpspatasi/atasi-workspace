<?php
session_start();
require 'koneksi.php';

// 1. PROTEKSI HALAMAN: Wajib Login
if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); 
    exit; 
}

$user_id = $_SESSION['user_id'];

// Ambil data terbaru dari database untuk mengisi form
$stmt_get = $pdo->prepare("SELECT * FROM tb_users WHERE id = ?");
$stmt_get->execute([$user_id]);
$user = $stmt_get->fetch(PDO::FETCH_ASSOC);

// Tentukan avatar saat ini (kalau masih kosong, pakai inisial default/random seed)
$default_avatar = "https://api.dicebear.com/7.x/notionists/svg?seed=" . urlencode($user['username']);
$current_avatar = !empty($user['avatar']) ? $user['avatar'] : $default_avatar;

// 2. LOGIKA UPDATE PROFIL (VIA FETCH/AJAX)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_profil') {
    $nama_lengkap = htmlspecialchars($_POST['nama_lengkap']);
    $username     = htmlspecialchars($_POST['username']);
    $password_baru = $_POST['password_baru'];
    $konfirmasi_password = $_POST['konfirmasi_password'];
    $avatar_baru = $_POST['avatar'] ?? $current_avatar; // Tangkap data avatar
    
    // Logika Jabatan: Hanya Superadmin yang bisa kirim input jabatan
    $jabatan = ($_SESSION['modul_akses'] === 'Superadmin') ? htmlspecialchars($_POST['jabatan']) : $user['jabatan'];

    $status = 'success';
    $pesan = 'Profil ATASI berhasil diperbarui!';

    try {
        if (!empty($password_baru)) {
            if ($password_baru !== $konfirmasi_password) {
                $status = 'error';
                $pesan = 'Password baru dan konfirmasi tidak cocok!';
            } else {
                // Update dengan Password Baru (Enkripsi Hash)
                $hashed_password = password_hash($password_baru, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE tb_users SET nama_lengkap = ?, jabatan = ?, username = ?, password = ?, avatar = ? WHERE id = ?");
                $stmt->execute([$nama_lengkap, $jabatan, $username, $hashed_password, $avatar_baru, $user_id]);
            }
        } else {
            // Update Identitas & Avatar Saja
            $stmt = $pdo->prepare("UPDATE tb_users SET nama_lengkap = ?, jabatan = ?, username = ?, avatar = ? WHERE id = ?");
            $stmt->execute([$nama_lengkap, $jabatan, $username, $avatar_baru, $user_id]);
        }

        if ($status === 'success') {
            // Update Session agar perubahan langsung terlihat di UI
            $_SESSION['nama_lengkap'] = $nama_lengkap;
            $_SESSION['username']     = $username;
            $_SESSION['inisial']      = strtoupper(substr($nama_lengkap, 0, 1));
            $_SESSION['avatar']       = $avatar_baru; // Simpan avatar di session
        }

    } catch (PDOException $e) {
        $status = 'error';
        $pesan = 'Gagal update database: ' . $e->getMessage();
    }

    echo json_encode(['status' => $status, 'pesan' => $pesan]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akun Google - Edit Profil ATASI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap');
        body { font-family: 'Google Sans', sans-serif; background-color: #f0f4f9; }
        .google-card { box-shadow: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24); border-radius: 24px; }
        .avatar-option { transition: transform 0.2s, outline 0.2s; cursor: pointer; }
        .avatar-option:hover { transform: scale(1.05); }
        .avatar-selected { outline: 4px solid #1a73e8; outline-offset: 4px; border-radius: 50%; }
        
        /* Modal Animasi */
        .modal-overlay { transition: opacity 0.2s ease-out; }
        .modal-box { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
    </style>
</head>
<body class="text-slate-800 min-h-screen flex flex-col items-center justify-start pt-8 pb-12 px-4 relative overflow-y-auto">

    <div class="w-full max-w-3xl flex justify-start mb-6">
        <button onclick="window.history.back()" class="flex items-center gap-2 text-slate-600 hover:text-slate-900 bg-white hover:bg-slate-100 px-4 py-2 rounded-full font-medium transition shadow-sm border border-slate-200">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Kembali ke Aplikasi
        </button>
    </div>

    <div class="w-full max-w-3xl bg-white google-card overflow-hidden relative">
        
        <div class="px-10 py-10 flex flex-col items-center text-center border-b border-slate-100 relative">
            <h1 class="text-3xl font-normal text-slate-800 mb-1">Info dasar</h1>
            <p class="text-slate-500 font-medium mb-8">Beberapa info mungkin terlihat oleh orang lain yang menggunakan layanan ATASI.</p>

            <div class="relative group cursor-pointer" onclick="bukaModalAvatar()">
                <div class="w-28 h-28 rounded-full bg-blue-50 border-4 border-white shadow-lg overflow-hidden flex items-center justify-center relative">
                    <img id="displayAvatar" src="<?= htmlspecialchars($current_avatar) ?>" alt="Profil" class="w-full h-full object-cover">
                    <div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    </div>
                </div>
                <div class="absolute bottom-0 right-0 bg-white p-2 rounded-full shadow-md border border-slate-100 text-emerald-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                </div>
            </div>
            <p class="mt-4 text-slate-800 text-2xl font-normal"><?= htmlspecialchars($user['nama_lengkap']) ?></p>
            <p class="text-emerald-600 font-medium text-sm mt-1 bg-emerald-50 px-3 py-1 rounded-full border border-emerald-100"><?= $_SESSION['modul_akses'] ?></p>
        </div>

        <form id="formProfil" class="px-10 py-8" onsubmit="simpanProfil(event)">
            <input type="hidden" id="inputAvatar" name="avatar" value="<?= htmlspecialchars($current_avatar) ?>">

            <div class="space-y-6">
                
                <div>
                    <h3 class="text-sm font-bold text-slate-500 uppercase tracking-widest mb-4">Profil Publik</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Nama Lengkap</label>
                            <input type="text" name="nama_lengkap" value="<?= htmlspecialchars($user['nama_lengkap']) ?>" class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all bg-white" required>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Jabatan / Role</label>
                            <?php if ($_SESSION['modul_akses'] === 'Superadmin'): ?>
                                <input type="text" name="jabatan" value="<?= htmlspecialchars($user['jabatan']) ?>" class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all bg-white" placeholder="Contoh: Manager Marketing">
                            <?php else: ?>
                                <input type="text" value="<?= htmlspecialchars($user['jabatan']) ?>" class="w-full px-4 py-3 border border-slate-200 rounded-xl bg-slate-50 text-slate-500 cursor-not-allowed" readonly>
                                <p class="text-[11px] text-slate-400 mt-1.5">*Hanya Superadmin yang dapat mengubah role.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="border-t border-slate-100"></div>

                <div>
                    <h3 class="text-sm font-bold text-slate-500 uppercase tracking-widest mb-4">Akses & Keamanan</h3>
                    
                    <div class="mb-5">
                        <label class="block text-sm font-medium text-slate-700 mb-2">Username Login</label>
                        <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" class="w-full md:w-1/2 px-4 py-3 border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" required>
                    </div>

                    <div class="bg-emerald-50/50 border border-emerald-100 rounded-2xl p-6 relative overflow-hidden">
                        <div class="absolute -right-4 -top-4 w-24 h-24 bg-emerald-100 rounded-full opacity-50"></div>
                        <h4 class="text-sm font-bold text-slate-800 mb-1 relative z-10">Ubah Password</h4>
                        <p class="text-xs text-slate-500 mb-4 relative z-10">Kosongkan kedua kolom di bawah ini jika kamu tidak ingin mengganti password.</p>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 relative z-10">
                            <div>
                                <input type="password" name="password_baru" placeholder="Password Baru" class="w-full px-4 py-3 border border-white bg-white rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all shadow-sm">
                            </div>
                            <div>
                                <input type="password" name="konfirmasi_password" placeholder="Ulangi Password Baru" class="w-full px-4 py-3 border border-white bg-white rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all shadow-sm">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-10 flex justify-end gap-3">
                <button type="button" onclick="window.history.back()" class="px-6 py-2.5 text-emerald-600 hover:bg-emerald-50 rounded-full text-sm font-medium transition">
                    Batal
                </button>
                <button type="submit" id="btnSimpan" class="px-8 py-2.5 bg-[#50C878] hover:bg-emerald-700 text-white rounded-full text-sm font-medium transition shadow-md flex items-center gap-2">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>

    <div id="modalAvatar" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm modal-overlay" onclick="tutupModalAvatar()"></div>
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-xl relative z-10 modal-box transform scale-95 opacity-0 flex flex-col max-h-[85vh]">
            
            <div class="px-8 py-6 border-b border-slate-100 flex justify-between items-center shrink-0">
                <h3 class="font-normal text-slate-800 text-2xl">Pilih Ilustrasi</h3>
                <button type="button" onclick="tutupModalAvatar()" class="w-8 h-8 bg-slate-100 hover:bg-slate-200 text-slate-500 rounded-full flex items-center justify-center transition">✕</button>
            </div>
            
            <div class="p-8 overflow-y-auto flex-1 bg-slate-50">
                <p class="text-sm text-slate-500 text-center mb-6">Pilih salah satu karakter lucu di bawah ini untuk profil ATASI kamu.</p>
                
                <div class="grid grid-cols-3 md:grid-cols-4 gap-6 justify-items-center" id="avatarGrid">
                    </div>
            </div>

            <div class="px-8 py-5 border-t border-slate-100 flex justify-end bg-white rounded-b-3xl shrink-0">
                <button type="button" onclick="tutupModalAvatar()" class="px-6 py-2 bg-[#1a73e8] text-white rounded-full text-sm font-medium hover:bg-blue-700 transition">Selesai</button>
            </div>
        </div>
    </div>

    <script>
        // --- LOGIKA AVATAR KARTUN (DICEBEAR API) ---
        // Daftar nama "seed" untuk generate karakter lucu
        const avatarSeeds = ['Felix', 'Aneka', 'Jasper', 'Mimi', 'Buster', 'Salem', 'Loki', 'Garfield', 'Missy', 'Peanut', 'Bandit', 'Boo'];
        
        // Fungsi untuk merender grid avatar di dalam Modal
        function renderAvatars() {
            const grid = document.getElementById('avatarGrid');
            const currentSelected = document.getElementById('inputAvatar').value;
            
            let html = '';
            avatarSeeds.forEach(seed => {
                const url = `https://api.dicebear.com/7.x/notionists/svg?seed=${seed}&backgroundColor=e2e8f0`;
                const isSelected = currentSelected === url ? 'avatar-selected' : '';
                
                html += `
                    <div class="w-20 h-20 rounded-full bg-white shadow-sm overflow-hidden avatar-option ${isSelected}" 
                         onclick="pilihAvatar('${url}', this)">
                        <img src="${url}" class="w-full h-full object-cover">
                    </div>
                `;
            });
            grid.innerHTML = html;
        }

        function bukaModalAvatar() {
            renderAvatars(); // Render ulang supaya tahu mana yang sedang dipilih
            const m = document.getElementById('modalAvatar');
            m.classList.remove('hidden');
            setTimeout(() => m.querySelector('.modal-box').classList.add('scale-100', 'opacity-100'), 10);
        }

        function tutupModalAvatar() {
            const m = document.getElementById('modalAvatar');
            m.querySelector('.modal-box').classList.remove('scale-100', 'opacity-100');
            setTimeout(() => m.classList.add('hidden'), 200);
        }

        function pilihAvatar(url, element) {
            // Hapus seleksi sebelumnya
            document.querySelectorAll('.avatar-option').forEach(el => el.classList.remove('avatar-selected'));
            
            // Tambahkan seleksi ke yang di-klik
            element.classList.add('avatar-selected');
            
            // Update input hidden & gambar di form
            document.getElementById('inputAvatar').value = url;
            document.getElementById('displayAvatar').src = url;
        }


        // --- LOGIKA SIMPAN PROFIL ---
        async function simpanProfil(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSimpan');
            const originalText = btn.innerHTML;
            
            const pass = document.querySelector('input[name="password_baru"]').value;
            const conf = document.querySelector('input[name="konfirmasi_password"]').value;
            if (pass !== '' && pass !== conf) {
                Swal.fire({ title: 'Oops!', text: 'Password baru dan konfirmasi tidak cocok!', icon: 'warning' });
                return;
            }

            btn.innerHTML = 'Menyimpan...';
            btn.disabled = true;

            const formData = new FormData(document.getElementById('formProfil'));
            formData.append('action', 'update_profil');

            try {
                const response = await fetch('edit_profil.php', {
                    method: 'POST',
                    body: formData
                });
                const res = await response.json();

                if (res.status === 'success') {
                    Swal.fire({
                        title: 'Tersimpan!',
                        text: res.pesan,
                        icon: 'success',
                        confirmButtonText: 'Oke Mantap',
                        customClass: { confirmButton: 'bg-blue-600 text-white px-6 py-2 rounded-full font-medium' },
                        buttonsStyling: false
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({ title: 'Gagal', text: res.pesan, icon: 'error' });
                }
            } catch (err) {
                Swal.fire({ title: 'Error', text: 'Terjadi kesalahan koneksi!', icon: 'error' });
            }
            
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    </script>
</body>
</html>