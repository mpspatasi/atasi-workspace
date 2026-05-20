<?php
require 'koneksi.php';

// Terima request JSON
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!$data) {
    die("Akses ditolak.");
}

$table = $data['table'] ?? 'db_customer';
$selectedCols = $data['columns'] ?? [];
$scope = $data['scope'] ?? 'all';
$filters = $data['filters'] ?? [];

// Pastikan kolom tidak kosong
if (empty($selectedCols)) {
    die("Kolom tidak valid.");
}

// Keamanan: Validasi nama kolom agar terhindar dari SQL Injection
$validColumns = [];
$stmtCols = $pdo->query("DESCRIBE $table");
$dbCols = $stmtCols->fetchAll(PDO::FETCH_COLUMN);

foreach ($selectedCols as $col) {
    if (in_array($col, $dbCols)) {
        $validColumns[] = "`$col`";
    }
}
$selectQuery = implode(", ", $validColumns);

// Dasar Query
$sql = "SELECT $selectQuery FROM `$table` WHERE 1=1";
$params = [];

// JIKA SCOPE = FILTERED (Terapkan pencarian dan filter yang aktif)
if ($scope === 'filtered' && !empty($filters)) {
    
    // 1. Global Search
    if (!empty($filters['search'])) {
        $search = "%" . $filters['search'] . "%";
        $searchQuery = [];
        foreach ($dbCols as $col) {
            $searchQuery[] = "`$col` LIKE ?";
            $params[] = $search;
        }
        $sql .= " AND (" . implode(" OR ", $searchQuery) . ")";
    }

    // 2. Kolom Filter (Checkboxes)
    if (!empty($filters['filters'])) {
        foreach ($filters['filters'] as $colName => $values) {
            if (in_array($colName, $dbCols) && is_array($values) && count($values) > 0) {
                $placeholders = implode(',', array_fill(0, count($values), '?'));
                $sql .= " AND `$colName` IN ($placeholders)";
                $params = array_merge($params, $values);
            }
        }
    }
    
    // 3. Pengurutan (Sorting)
    if (!empty($filters['sortField']) && in_array($filters['sortField'], $dbCols)) {
        $order = (strtoupper($filters['sortOrder']) === 'DESC') ? 'DESC' : 'ASC';
        $sql .= " ORDER BY `" . $filters['sortField'] . "` " . $order;
    }
}

// Persiapkan Query
$stmt = $pdo->prepare($sql);
$stmt->execute($params);

// Set header agar browser mendownload sebagai file CSV
header('Content-Type: text/csv; charset=utf-8');
// Nama file aslinya diatur oleh JavaScript, header ini sebagai fallback
header('Content-Disposition: attachment; filename="data_export.csv"'); 

// Buka output stream
$output = fopen('php://output', 'w');

// Tambahkan BOM untuk UTF-8 (agar karakter khusus dan bahasa tertampil rapi di Excel)
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// 1. Tulis Header Kolom ke baris pertama CSV (hapus underscore)
$cleanHeaders = array_map(function($col) {
    return str_replace('_', ' ', $col);
}, $selectedCols);
fputcsv($output, $cleanHeaders);

// 2. Tulis Data (Baris demi Baris)
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, $row);
}

fclose($output);
exit;
?>