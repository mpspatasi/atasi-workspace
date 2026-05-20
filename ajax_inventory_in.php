<?php
session_start();
header('Content-Type: application/json');
require 'koneksi.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $page = isset($data['page']) ? (int)$data['page'] : 1;
    $limit = isset($data['limit']) ? (int)$data['limit'] : 100;
    $search = isset($data['search']) ? trim($data['search']) : '';
    $datePreset = isset($data['datePreset']) ? $data['datePreset'] : '7_days';
    $startDate = isset($data['startDate']) ? $data['startDate'] : '';
    $endDate = isset($data['endDate']) ? $data['endDate'] : '';

    $offset = ($page - 1) * $limit;
    
    $whereClauses = ["1=1"];
    $params = [];

    // Search Logika
    if (!empty($search)) {
        $whereClauses[] = "(i.referensi LIKE ? OR i.sku LIKE ? OR m.nama_barang LIKE ?)";
        $searchParam = "%$search%";
        array_push($params, $searchParam, $searchParam, $searchParam);
    }

    // Filter Tanggal Logika
    if ($datePreset === 'today') {
        $whereClauses[] = "DATE(i.tanggal) = CURDATE()";
    } elseif ($datePreset === '7_days') {
        $whereClauses[] = "i.tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    } elseif ($datePreset === '30_days') {
        $whereClauses[] = "i.tanggal >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    } elseif ($datePreset === 'this_month') {
        $whereClauses[] = "MONTH(i.tanggal) = MONTH(CURDATE()) AND YEAR(i.tanggal) = YEAR(CURDATE())";
    } elseif ($datePreset === '3_quarters') {
        $whereClauses[] = "i.tanggal >= DATE_SUB(CURDATE(), INTERVAL 9 MONTH)";
    } elseif ($datePreset === 'this_year') {
        $whereClauses[] = "YEAR(i.tanggal) = YEAR(CURDATE())";
    } elseif ($datePreset === 'custom' && !empty($startDate) && !empty($endDate)) {
        $whereClauses[] = "i.tanggal BETWEEN ? AND ?";
        array_push($params, $startDate, $endDate);
    }

    $whereSql = implode(' AND ', $whereClauses);

    // Hitung Total Data
    $sqlCount = "SELECT COUNT(*) FROM jurnal_transaksi_atasi.db_inventory_in i 
                 LEFT JOIN jurnal_transaksi_atasi.db_inventory m ON i.sku = m.sku 
                 WHERE $whereSql";
    $stmtCount = $pdo->prepare($sqlCount);
    $stmtCount->execute($params);
    $totalData = $stmtCount->fetchColumn();
    $totalPages = ceil($totalData / $limit);

    // Ambil Data limit
    $sqlData = "SELECT i.*, m.nama_barang, m.satuan 
                FROM jurnal_transaksi_atasi.db_inventory_in i 
                LEFT JOIN jurnal_transaksi_atasi.db_inventory m ON i.sku = m.sku 
                WHERE $whereSql 
                ORDER BY i.tanggal DESC, i.id DESC 
                LIMIT $limit OFFSET $offset";
    $stmtData = $pdo->prepare($sqlData);
    $stmtData->execute($params);
    $results = $stmtData->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => $results,
        'totalData' => $totalData,
        'totalPages' => $totalPages
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>