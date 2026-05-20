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

    // Logika Pencarian (termasuk kolom penerima)
    if (!empty($search)) {
        $whereClauses[] = "(o.referensi LIKE ? OR o.sku LIKE ? OR m.nama_barang LIKE ? OR o.penerima LIKE ?)";
        $searchParam = "%$search%";
        array_push($params, $searchParam, $searchParam, $searchParam, $searchParam);
    }

    // Filter Tanggal
    if ($datePreset === 'today') {
        $whereClauses[] = "DATE(o.tanggal) = CURDATE()";
    } elseif ($datePreset === '7_days') {
        $whereClauses[] = "o.tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    } elseif ($datePreset === '30_days') {
        $whereClauses[] = "o.tanggal >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    } elseif ($datePreset === 'this_month') {
        $whereClauses[] = "MONTH(o.tanggal) = MONTH(CURDATE()) AND YEAR(o.tanggal) = YEAR(CURDATE())";
    } elseif ($datePreset === 'this_quarter') {
        // Ambil kuartal saat ini
        $whereClauses[] = "QUARTER(o.tanggal) = QUARTER(CURDATE()) AND YEAR(o.tanggal) = YEAR(CURDATE())";
    } elseif ($datePreset === 'this_year') {
        $whereClauses[] = "YEAR(o.tanggal) = YEAR(CURDATE())";
    } elseif ($datePreset === 'custom' && !empty($startDate) && !empty($endDate)) {
        $whereClauses[] = "DATE(o.tanggal) BETWEEN ? AND ?";
        array_push($params, $startDate, $endDate);
    }

    $whereSql = implode(' AND ', $whereClauses);

    // Hitung Total Data (Pake alias 'o' untuk db_inventory_out)
    $sqlCount = "SELECT COUNT(*) FROM jurnal_transaksi_atasi.db_inventory_out o 
                 LEFT JOIN jurnal_transaksi_atasi.db_inventory m ON o.sku = m.sku 
                 WHERE $whereSql";
    $stmtCount = $pdo->prepare($sqlCount);
    $stmtCount->execute($params);
    $totalData = $stmtCount->fetchColumn();
    $totalPages = ceil($totalData / $limit);

    // Ambil Data limit & offset
    $sqlData = "SELECT o.*, m.nama_barang, m.satuan 
                FROM jurnal_transaksi_atasi.db_inventory_out o 
                LEFT JOIN jurnal_transaksi_atasi.db_inventory m ON o.sku = m.sku 
                WHERE $whereSql 
                ORDER BY o.tanggal DESC, o.id DESC 
                LIMIT $limit OFFSET $offset";
    $stmtData = $pdo->prepare($sqlData);
    $stmtData->execute($params);
    $results = $stmtData->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'data' => $results,
        'totalPages' => $totalPages,
        'totalData' => $totalData,
        'page' => $page
    ]);

} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>