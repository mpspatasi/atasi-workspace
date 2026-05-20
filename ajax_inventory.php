<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

try {
    require 'koneksi.php';
    // Pastikan PDO menggunakan mode exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    
    if (!$input) throw new Exception("Format request tidak valid.");

    $action = $input['action'] ?? '';

    // =================================================================
    // 1. GET DISTINCT (Untuk Filter Popup di Header Tabel)
    // =================================================================
    if ($action === 'get_distinct') {
        $kolom = preg_replace('/[^a-zA-Z0-9_]/', '', $input['column']); 
        
        // Query ke database jurnal_transaksi_atasi.db_inventory
        $sql = "SELECT DISTINCT `$kolom` 
                FROM jurnal_transaksi_atasi.db_inventory 
                WHERE `$kolom` IS NOT NULL AND `$kolom` != '' 
                ORDER BY `$kolom` ASC";
        
        $stmt = $pdo->query($sql);
        $data = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (ob_get_length()) ob_clean();
        echo json_encode($data);
        exit;
    }

    // =================================================================
    // 2. GET DATA (Utama: Tabel Master Barang)
    // =================================================================
    if ($action === 'get_data') {
        $page = isset($input['page']) ? (int)$input['page'] : 1;
        $limit = isset($input['limit']) ? (int)$input['limit'] : 100;
        $search = $input['search'] ?? '';
        $filters = $input['filters'] ?? [];
        $sortField = $input['sortField'] ?? '';
        $sortOrder = $input['sortOrder'] ?? ''; 
        $offset = ($page - 1) * $limit;
        
        // Ambil semua kolom untuk pencarian global
        $stmtCols = $pdo->query("DESCRIBE jurnal_transaksi_atasi.db_inventory");
        $columns = $stmtCols->fetchAll(PDO::FETCH_COLUMN);
        
        $whereSQL = "WHERE 1=1 ";
        $params = [];
        
        // Logic Pencarian Global
        if (!empty($search)) {
            $searchSQL = [];
            foreach ($columns as $col) {
                $searchSQL[] = "`$col` LIKE ?";
                $params[] = "%$search%";
            }
            $whereSQL .= " AND (" . implode(" OR ", $searchSQL) . ") ";
        }
        
        // Logic Filter per Kolom (Checkbox Popup)
        foreach ($filters as $colName => $values) {
            if (!empty($values)) {
                $colName = preg_replace('/[^a-zA-Z0-9_]/', '', $colName);
                $inPlaceholders = str_repeat('?,', count($values) - 1) . '?';
                $whereSQL .= " AND `$colName` IN ($inPlaceholders) ";
                
                foreach ($values as $val) {
                    $params[] = ($val === '(Kosong)' ? '' : $val);
                }
            }
        }
        
        // Hitung Total Data (untuk Paginasi)
        $sqlCount = "SELECT COUNT(*) FROM jurnal_transaksi_atasi.db_inventory $whereSQL";
        $stmtCount = $pdo->prepare($sqlCount);
        $stmtCount->execute($params);
        $totalData = $stmtCount->fetchColumn();
        $totalPages = $totalData > 0 ? ceil($totalData / $limit) : 1;
        
        // Sorting
        $orderBySQL = "ORDER BY id DESC"; // Default
        if (!empty($sortField) && in_array($sortField, $columns)) {
            $sortOrder = (strtoupper($sortOrder) === 'DESC') ? 'DESC' : 'ASC';
            $orderBySQL = "ORDER BY `$sortField` $sortOrder";
        }
        
        // Ambil Data Akhir
        $sqlData = "SELECT * FROM jurnal_transaksi_atasi.db_inventory $whereSQL $orderBySQL LIMIT $limit OFFSET $offset";
        $stmtData = $pdo->prepare($sqlData);
        $stmtData->execute($params);
        $data = $stmtData->fetchAll(PDO::FETCH_ASSOC);
        
        if (ob_get_length()) ob_clean();
        echo json_encode([
            'status' => 'success',
            'data' => $data,
            'totalData' => $totalData,
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'limit' => $limit
        ]);
        exit;
    }

    throw new Exception("Action '$action' tidak dikenal.");

} catch (Exception $e) {
    if (ob_get_length()) ob_clean();
    echo json_encode([
        'status' => 'error', 
        'pesan' => 'Masalah Server: ' . $e->getMessage()
    ]);
    exit;
}