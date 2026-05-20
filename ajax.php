<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

try {
    require 'koneksi.php';
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    
    if (!$input) throw new Exception("Format request tidak valid.");

    $action = $input['action'] ?? '';

    if ($action === 'get_distinct') {
        $kolom = preg_replace('/[^a-zA-Z0-9_]/', '', $input['column']); 
        
        if ($kolom === 'Segmen') {
            $sql = "SELECT DISTINCT COALESCE(r.Segmen, 'Hot Lead') FROM db_customer c LEFT JOIN db_rfm r ON c.Telp = r.Telp WHERE COALESCE(r.Segmen, 'Hot Lead') != '' ORDER BY COALESCE(r.Segmen, 'Hot Lead') ASC";
        } elseif (in_array($kolom, ['Status_Penanganan', 'Nama_CS', 'Tindakan', 'Tgl_Konfirmasi'])) {
            $sql = "SELECT DISTINCT COALESCE(rd.`$kolom`, '') FROM db_customer c LEFT JOIN db_retur_detail rd ON c.id = rd.id_customer WHERE rd.`$kolom` IS NOT NULL AND rd.`$kolom` != '' ORDER BY rd.`$kolom` ASC";
        } else {
            $sql = "SELECT DISTINCT c.`$kolom` FROM db_customer c WHERE c.`$kolom` IS NOT NULL AND c.`$kolom` != '' ORDER BY c.`$kolom` ASC";
        }
        
        $stmt = $pdo->query($sql);
        $data = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (ob_get_length()) ob_clean();
        echo json_encode($data);
        exit;
    }

    if ($action === 'get_distinct_followup') {
        $kolom = preg_replace('/[^a-zA-Z0-9_]/', '', $input['column']);
        $interval = isset($input['interval']) ? (int)$input['interval'] : 0;

        $sql = "SELECT DISTINCT `$kolom` FROM (
                    SELECT c.Nama, c.Telp, c.Provinsi, c.Kabupaten, c.Kecamatan, 
                           COALESCE(r.Segmen, 'Hot Lead') AS Segmen,
                           DATEDIFF(CURDATE(), MAX(c.Tanggal_Pesanan)) AS Selisih_Hari
                    FROM db_customer c
                    LEFT JOIN db_rfm r ON c.Telp = r.Telp
                    WHERE c.Status_Pesanan IN ('Perlu Dikirim', 'Dikirim', 'Selesai')
                    GROUP BY c.Telp, c.Nama, c.Provinsi, c.Kabupaten, c.Kecamatan, r.Segmen
                ) AS temp_table 
                WHERE Selisih_Hari = :interval AND `$kolom` IS NOT NULL AND `$kolom` != '' ORDER BY `$kolom` ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['interval' => $interval]);
        $data = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (ob_get_length()) ob_clean();
        echo json_encode($data);
        exit;
    }

    if ($action === 'get_data') {
        $page = isset($input['page']) ? (int)$input['page'] : 1;
        $limit = isset($input['limit']) ? (int)$input['limit'] : 100;
        $search = $input['search'] ?? '';
        $filters = $input['filters'] ?? [];
        $sortField = $input['sortField'] ?? '';
        $sortOrder = $input['sortOrder'] ?? ''; 
        $offset = ($page - 1) * $limit;
        
        $stmtCols = $pdo->query("DESCRIBE db_customer");
        $columns = $stmtCols->fetchAll(PDO::FETCH_COLUMN);
        
        $selectCols = [];
        foreach ($columns as $col) {
            $selectCols[] = "c.`$col`";
        }
        $selectCols[] = "COALESCE(r.Segmen, 'Hot Lead') AS Segmen";
        $selectCols[] = "rd.Status_Penanganan"; // Diubah: Tidak ada COALESCE 'Baru'
        $selectCols[] = "rd.Nama_CS";
        $selectCols[] = "rd.Tindakan";
        $selectCols[] = "rd.Tgl_Konfirmasi";
        $selectStr = implode(', ', $selectCols);

        $whereSQL = "WHERE 1=1 ";
        $params = [];
        
        if (!empty($search)) {
            $searchSQL = [];
            foreach ($columns as $col) {
                $searchSQL[] = "c.`$col` LIKE ?";
                $params[] = "%$search%";
            }
            $searchSQL[] = "COALESCE(r.Segmen, 'Hot Lead') LIKE ?"; $params[] = "%$search%";
            $searchSQL[] = "rd.Status_Penanganan LIKE ?"; $params[] = "%$search%";
            $searchSQL[] = "rd.Nama_CS LIKE ?"; $params[] = "%$search%";
            $searchSQL[] = "rd.Tindakan LIKE ?"; $params[] = "%$search%";
            $whereSQL .= " AND (" . implode(" OR ", $searchSQL) . ") ";
        }
        
        foreach ($filters as $colName => $values) {
            if (!empty($values)) {
                $colName = preg_replace('/[^a-zA-Z0-9_]/', '', $colName);
                $inPlaceholders = str_repeat('?,', count($values) - 1) . '?';
                
                if ($colName === 'Segmen') {
                    $whereSQL .= " AND COALESCE(r.Segmen, 'Hot Lead') IN ($inPlaceholders) ";
                } elseif (in_array($colName, ['Status_Penanganan', 'Nama_CS', 'Tindakan', 'Tgl_Konfirmasi'])) {
                    $whereSQL .= " AND rd.`$colName` IN ($inPlaceholders) ";
                } else {
                    $whereSQL .= " AND c.`$colName` IN ($inPlaceholders) ";
                }
                
                foreach ($values as $val) {
                    $params[] = $val === '(Kosong)' ? '' : $val;
                }
            }
        }
        
        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM db_customer c LEFT JOIN db_rfm r ON c.Telp = r.Telp LEFT JOIN db_retur_detail rd ON c.id = rd.id_customer $whereSQL");
        $stmtCount->execute($params);
        $totalData = $stmtCount->fetchColumn();
        $totalPages = $totalData > 0 ? ceil($totalData / $limit) : 1;
        
        $orderBySQL = "ORDER BY c.id DESC"; 
        if (!empty($sortField)) {
            $sortOrder = ($sortOrder === 'DESC') ? 'DESC' : 'ASC';
            if ($sortField === 'Segmen') {
                $orderBySQL = "ORDER BY COALESCE(r.Segmen, 'Hot Lead') $sortOrder";
            } elseif (in_array($sortField, ['Status_Penanganan', 'Nama_CS', 'Tindakan', 'Tgl_Konfirmasi'])) {
                $orderBySQL = "ORDER BY rd.`$sortField` $sortOrder";
            } elseif (in_array($sortField, $columns)) {
                $orderBySQL = "ORDER BY c.`$sortField` $sortOrder";
            }
        }
        
        $sqlData = "SELECT $selectStr FROM db_customer c LEFT JOIN db_rfm r ON c.Telp = r.Telp LEFT JOIN db_retur_detail rd ON c.id = rd.id_customer $whereSQL $orderBySQL LIMIT $limit OFFSET $offset";
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

    if ($action === 'get_followup') {
        $interval = isset($input['interval']) ? (int)$input['interval'] : 0;
        $sortField = $input['sortField'] ?? '';
        $sortOrder = $input['sortOrder'] ?? '';
        $filters = $input['filters'] ?? [];

        $whereSQL = "WHERE Selisih_Hari = :interval";
        $params = ['interval' => $interval];

        foreach ($filters as $colName => $values) {
            if (!empty($values)) {
                $colName = preg_replace('/[^a-zA-Z0-9_]/', '', $colName);
                $placeholders = [];
                foreach ($values as $idx => $val) {
                    $paramName = "filter_{$colName}_{$idx}";
                    $placeholders[] = ":$paramName";
                    $params[$paramName] = $val === '(Kosong)' ? '' : $val;
                }
                $whereSQL .= " AND `$colName` IN (" . implode(', ', $placeholders) . ") ";
            }
        }

        $orderBySQL = "ORDER BY Nama ASC";
        $allowedSortColumns = ['Nama', 'Telp', 'Provinsi', 'Kabupaten', 'Kecamatan', 'Segmen'];
        if (!empty($sortField) && in_array($sortField, $allowedSortColumns)) {
            $sortOrder = ($sortOrder === 'DESC') ? 'DESC' : 'ASC';
            $orderBySQL = "ORDER BY `$sortField` $sortOrder";
        }

        $sql = "SELECT * FROM (
                    SELECT c.Nama, c.Telp, c.Provinsi, c.Kabupaten, c.Kecamatan, 
                           COALESCE(r.Segmen, 'Hot Lead') AS Segmen,
                           DATEDIFF(CURDATE(), MAX(c.Tanggal_Pesanan)) AS Selisih_Hari
                    FROM db_customer c
                    LEFT JOIN db_rfm r ON c.Telp = r.Telp
                    WHERE c.Status_Pesanan IN ('Perlu Dikirim', 'Dikirim', 'Selesai')
                    GROUP BY c.Telp, c.Nama, c.Provinsi, c.Kabupaten, c.Kecamatan, r.Segmen
                ) AS temp_table
                $whereSQL $orderBySQL";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (ob_get_length()) ob_clean();
        echo json_encode([
            'status' => 'success',
            'data' => $result
        ]);
        exit;
    }

    throw new Exception("Action '$action' tidak ditemukan.");

} catch (Exception $e) {
    if (ob_get_length()) ob_clean();
    echo json_encode(['status' => 'error', 'pesan' => 'Database bermasalah: ' . $e->getMessage()]);
    exit;
}
?>