<?php
session_start();
require 'koneksi.php'; // Pastikan koneksi.php sudah disetting dengan benar

// Ambil tipe ekspor dari URL (excel atau pdf)
$type = isset($_GET['type']) ? $_GET['type'] : 'excel';

// Ambil seluruh data dari tabel inventory (Bisa disesuaikan jika ingin mengekspor data yang difilter saja)
$stmt = $pdo->query("SELECT * FROM jurnal_transaksi_atasi.db_inventory ORDER BY id DESC");
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($data)) {
    die("<script>alert('Tidak ada data untuk diekspor!'); window.history.back();</script>");
}

$filename = "Export_Master_Barang_ATASI_" . date('Y-m-d_Hi');

if ($type === 'excel') {
    // Header agar browser membaca file sebagai format Excel (.xls)
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"$filename.xls\"");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo '<table border="1">';
    echo '<tr>';
    
    // Looping untuk membuat header tabel dinamis (kecuali kolom id)
    foreach (array_keys($data[0]) as $col) {
        if ($col !== 'id') {
            echo '<th style="background-color: #F59E0B; color: white; font-weight: bold; text-align: center; padding: 5px;">' . strtoupper(str_replace('_', ' ', $col)) . '</th>';
        }
    }
    echo '</tr>';

    // Looping isian data
    foreach ($data as $row) {
        echo '<tr>';
        foreach ($row as $key => $val) {
            if ($key !== 'id') {
                // Tambahkan spasi pada SKU jika berbentuk angka panjang agar tidak diubah excel jadi scientific format (misal E+12)
                $format = ($key === 'sku') ? 'style="mso-number-format:\'@\';"' : ''; 
                echo '<td ' . $format . '>' . htmlspecialchars($val) . '</td>';
            }
        }
        echo '</tr>';
    }
    echo '</table>';
    exit;

} elseif ($type === 'pdf') {
    // Ekspor PDF ringan menggunakan Print Browser Script
    echo '<!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Export Laporan Inventory</title>
        <style>
            body { font-family: Arial, sans-serif; font-size: 12px; }
            h2 { text-align: center; color: #333; margin-bottom: 20px; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            th, td { border: 1px solid #333; padding: 8px; text-align: left; }
            th { background-color: #F59E0B; color: white; text-transform: uppercase; }
            @media print {
                @page { size: landscape; }
                button { display: none; }
            }
        </style>
    </head>
    <body onload="window.print()">
        <h2>LAPORAN MASTER BARANG INVENTORY ATASI</h2>
        <p>Tanggal Unduh: ' . date('d F Y H:i:s') . '</p>
        <table>
            <tr>';
            
            // Header
            foreach (array_keys($data[0]) as $col) {
                if ($col !== 'id') {
                    echo '<th>' . htmlspecialchars(str_replace('_', ' ', $col)) . '</th>';
                }
            }
            
            echo '</tr>';
            
            // Row Data
            foreach ($data as $row) {
                echo '<tr>';
                foreach ($row as $key => $val) {
                    if ($key !== 'id') {
                        echo '<td>' . htmlspecialchars($val) . '</td>';
                    }
                }
                echo '</tr>';
            }
            
        echo '</table>
    </body>
    </html>';
    exit;
} else {
    echo "Tipe ekspor tidak valid.";
}
?>