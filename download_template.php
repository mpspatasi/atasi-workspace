<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
require 'koneksi.php';

try {
    // 1. Dapatkan semua nama kolom asli dari db_customer
    $stmtCols = $pdo->query("DESCRIBE db_customer");
    $dbColumns = $stmtCols->fetchAll(PDO::FETCH_COLUMN);
    
    // Buang kolom 'id' karena diisi otomatis oleh database (Auto Increment)
    $templateColumns = array_values(array_diff($dbColumns, ['id']));

    if (empty($templateColumns)) {
        throw new Exception("Struktur kolom database tidak ditemukan.");
    }

    // 2. Set header agar browser mendownload sebagai file CSV
    $filename = "template_customer_database_" . date('Ymd_His') . ".csv";
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    // 3. Tulis data ke output stream
    $output = fopen('php://output', 'w');
    
    // Tambahkan BOM (Byte Order Mark) UTF-8 agar karakter spesial/simbol di Excel tidak rusak/berantakan
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // Masukkan baris header kolom
    fputcsv($output, $templateColumns, ",");
    
    // Tambahkan 1 baris contoh data (opsional, sebagai panduan tim CRM)
    // Kita isi contoh data kosong yang valid sesuai tipe kolom
    $exampleRow = [];
    foreach ($templateColumns as $col) {
        if ($col === 'No_Pesanan') $exampleRow[] = "250101XXXXXX";
        else if ($col === 'Tanggal_Pesanan') $exampleRow[] = date('Y-m-d');
        else if ($col === 'Waktu_Pesanan') $exampleRow[] = date('H:i:s');
        else if ($col === 'Status_Pesanan') $exampleRow[] = "Perlu Dikirim";
        else if ($col === 'Jumlah') $exampleRow[] = "1";
        else if ($col === 'ValueCX' || $col === 'ValueADS') $exampleRow[] = "150000";
        else $exampleRow[] = "Contoh_" . str_replace('_', ' ', $col);
    }
    fputcsv($output, $exampleRow, ",");

    fclose($output);
    exit;

} catch (Exception $e) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<script>alert('Gagal membuat template: " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
    exit;
}
?>