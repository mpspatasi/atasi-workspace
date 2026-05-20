<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

try {
    require 'koneksi.php';

    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    
    if (!$input) throw new Exception("Format request tidak valid.");

    $action = $input['action'] ?? '';

    // ACTION 1: Nilai unik untuk Checkbox Filter di Pop-up RFM
    if ($action === 'get_distinct') {
        $kolom = preg_replace('/[^a-zA-Z0-9_]/', '', $input['column']); 
        $stmt = $pdo->query("SELECT DISTINCT `$kolom` FROM db_rfm WHERE `$kolom` IS NOT NULL AND `$kolom` != '' ORDER BY `$kolom` ASC");
        $data = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo json_encode($data);
        exit;
    }

    // ACTION 2: Server-Side Processing khusus db_rfm
    if ($action === 'get_data') {
        $page = isset($input['page']) ? (int)$input['page'] : 1;
        $limit = isset($input['limit']) ? (int)$input['limit'] : 100;
        $search = $input['search'] ?? '';
        $filters = $input['filters'] ?? [];
        
        // Parameter Sorting Baru
        $sortField = $input['sortField'] ?? '';
        $sortOrder = $input['sortOrder'] ?? '';
        
        $offset = ($page - 1) * $limit;
        
        $stmtCols = $pdo->query("DESCRIBE db_rfm");
        $columns = $stmtCols->fetchAll(PDO::FETCH_COLUMN);
        
        $whereSQL = "WHERE 1=1 ";
        $params = [];
        
        if (!empty($search)) {
            $searchSQL = [];
            foreach ($columns as $col) {
                $searchSQL[] = "`$col` LIKE ?";
                $params[] = "%$search%";
            }
            $whereSQL .= " AND (" . implode(" OR ", $searchSQL) . ") ";
        }
        
        foreach ($filters as $colName => $values) {
            if (!empty($values)) {
                $colName = preg_replace('/[^a-zA-Z0-9_]/', '', $colName);
                $inPlaceholders = str_repeat('?,', count($values) - 1) . '?';
                $whereSQL .= " AND `$colName` IN ($inPlaceholders) ";
                foreach ($values as $val) {
                    $params[] = $val === '(Kosong)' ? '' : $val;
                }
            }
        }
        
        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM db_rfm $whereSQL");
        $stmtCount->execute($params);
        $totalData = $stmtCount->fetchColumn();
        $totalPages = $totalData > 0 ? ceil($totalData / $limit) : 1;
        
        // Atur SQL Urutan
        $orderBySQL = "ORDER BY Monetary DESC, Frequency DESC"; // Default RFM sorting
        if (!empty($sortField) && in_array($sortField, $columns)) {
            $sortOrder = ($sortOrder === 'DESC') ? 'DESC' : 'ASC';
            $orderBySQL = "ORDER BY `$sortField` $sortOrder";
        }
        
        $sqlData = "SELECT * FROM db_rfm $whereSQL $orderBySQL LIMIT $limit OFFSET $offset";
        $stmtData = $pdo->prepare($sqlData);
        $stmtData->execute($params);
        $data = $stmtData->fetchAll(PDO::FETCH_ASSOC);
        
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

    throw new Exception("Action '$action' tidak ditemukan.");

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'pesan' => 'Error: ' . $e->getMessage()]);
    exit;
}
?>