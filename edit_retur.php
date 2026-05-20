<?php
session_start();
require 'koneksi.php';

if (!isset($_GET['id'])) {
    header("Location: return_order.php");
    exit;
}

$id_customer = (int)$_GET['id'];
$pesan = '';
$status_pesan = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Jika kosong, simpan sebagai NULL di database agar tampil jadi strip (-) di tabel
    $status_penanganan = empty($_POST['status_penanganan']) ? null : $_POST['status_penanganan'];
    $nama_cs = empty($_POST['nama_cs']) ? null : $_POST['nama_cs'];
    $tindakan = empty($_POST['tindakan']) ? null : $_POST['tindakan'];
    $tgl_konfirmasi = empty($_POST['tgl_konfirmasi']) ? null : $_POST['tgl_konfirmasi'];

    try {
        $stmtCek = $pdo->prepare("SELECT id_customer FROM db_retur_detail WHERE id_customer = ?");
        $stmtCek->execute([$id_customer]);
        $exists = $stmtCek->fetchColumn();

        if ($exists) {
            $stmtUpdate = $pdo->prepare("UPDATE db_retur_detail SET Status_Penanganan = ?, Nama_CS = ?, Tindakan = ?, Tgl_Konfirmasi = ? WHERE id_customer = ?");
            $stmtUpdate->execute([$status_penanganan, $nama_cs, $tindakan, $tgl_konfirmasi, $id_customer]);
        } else {
            $stmtInsert = $pdo->prepare("INSERT INTO db_retur_detail (id_customer, Status_Penanganan, Nama_CS, Tindakan, Tgl_Konfirmasi) VALUES (?, ?, ?, ?, ?)");
            $stmtInsert->execute([$id_customer, $status_penanganan, $nama_cs, $tindakan, $tgl_konfirmasi]);
        }
        
        $pesan = "Data penanganan retur berhasil diperbarui!";
        $status_pesan = "success";
    } catch (Exception $e) {
        $pesan = "Gagal menyimpan data: " . $e->getMessage();
        $status_pesan = "error";
    }
}

$stmt = $pdo->prepare("
    SELECT 
        c.No_Pesanan, c.Nama, c.Telp, c.Keterangan AS Ket_Customer, c.Tanggal_Retur,
        rd.Status_Penanganan, rd.Nama_CS, rd.Tindakan, rd.Tgl_Konfirmasi
    FROM db_customer c
    LEFT JOIN db_retur_detail rd ON c.id = rd.id_customer
    WHERE c.id = ?
");
$stmt->execute([$id_customer]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    die("Data customer tidak ditemukan!");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Penanganan Retur - ATASI CRM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-slate-50 font-sans text-slate-800 flex items-center justify-center min-h-screen p-4">

    <div class="w-full max-w-2xl bg-white rounded-xl shadow-xl border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex justify-between items-center">
            <div>
                <h2 class="text-lg font-bold text-slate-800">Edit Penanganan Retur</h2>
                <p class="text-xs text-slate-500 mt-1">Order: <span class="font-bold text-indigo-600"><?= htmlspecialchars($data['No_Pesanan']) ?></span> | <?= htmlspecialchars($data['Nama']) ?></p>
            </div>
            <a href="return_order.php" class="text-sm font-semibold text-slate-500 hover:text-slate-800 transition">← Kembali</a>
        </div>

        <form method="POST" class="p-6 space-y-5">
            <div class="grid grid-cols-2 gap-5">
                <div class="space-y-1.5">
                    <label class="text-xs font-bold text-slate-600 uppercase tracking-wide">Status Penanganan</label>
                    <select name="status_penanganan" class="w-full px-4 py-2.5 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition bg-white">
                        <option value="">-- Belum Ditangani --</option>
                        <?php 
                        $opsi_status = ['Baru', 'Proses', 'Selesai', 'Gagal'];
                        $current_status = $data['Status_Penanganan'] ?? '';
                        foreach ($opsi_status as $opsi) {
                            $selected = ($current_status === $opsi) ? 'selected' : '';
                            echo "<option value='$opsi' $selected>$opsi</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-bold text-slate-600 uppercase tracking-wide">Nama CS</label>
                    <select name="nama_cs" class="w-full px-4 py-2.5 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition bg-white">
                        <option value="">-- Pilih CS --</option>
                        <?php 
                        $opsi_cs = ['Tomy', 'Tantri', 'Tika', 'Risa', 'Savi'];
                        $current_cs = $data['Nama_CS'] ?? '';
                        foreach ($opsi_cs as $cs) {
                            $selected = ($current_cs === $cs) ? 'selected' : '';
                            echo "<option value='$cs' $selected>$cs</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-600 uppercase tracking-wide">Tanggal Konfirmasi</label>
                <input type="date" name="tgl_konfirmasi" value="<?= htmlspecialchars($data['Tgl_Konfirmasi'] ?? '') ?>" class="w-full px-4 py-2.5 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition bg-white">
            </div>

            <div class="space-y-1.5">
                <label class="text-xs font-bold text-slate-600 uppercase tracking-wide">Tindakan / Catatan Retur</label>
                <textarea name="tindakan" rows="4" placeholder="Tuliskan detail tindakan yang sudah dilakukan..." class="w-full px-4 py-3 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition resize-y"><?= htmlspecialchars($data['Tindakan'] ?? '') ?></textarea>
            </div>

            <div class="pt-4 border-t border-slate-100 flex justify-end gap-3">
                <a href="return_order.php" class="px-5 py-2.5 bg-white border border-slate-300 text-slate-700 rounded-lg text-sm font-bold hover:bg-slate-50 transition shadow-sm">Batal</a>
                <button type="submit" class="px-5 py-2.5 bg-[#3E54D3] text-white rounded-lg text-sm font-bold hover:bg-blue-800 transition shadow-md">Simpan Perubahan</button>
            </div>
        </form>
    </div>

    <?php if ($pesan): ?>
    <script>
        Swal.fire({
            title: '<?= $status_pesan === "success" ? "Berhasil!" : "Gagal!" ?>',
            text: '<?= $pesan ?>',
            icon: '<?= $status_pesan ?>',
            confirmButtonText: 'OK',
            customClass: {
                confirmButton: '<?= $status_pesan === "success" ? "bg-[#3E54D3]" : "bg-red-600" ?> text-white px-6 py-2 rounded-lg font-bold'
            },
            buttonsStyling: false
        }).then(() => {
            <?php if($status_pesan === "success"): ?>
                window.location.href = 'return_order.php';
            <?php endif; ?>
        });
    </script>
    <?php endif; ?>

</body>
</html>