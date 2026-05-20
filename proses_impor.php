<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require 'koneksi.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'pesan' => 'Metode request tidak valid.']);
    exit;
}

if (!isset($_FILES['file_csv']) || $_FILES['file_csv']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['status' => 'error', 'pesan' => 'Gagal mengunggah file atau file tidak ditemukan.']);
    exit;
}

$fileTmpPath = $_FILES['file_csv']['tmp_path'] ?? $_FILES['file_csv']['tmp_name'];
$fileName = $_FILES['file_csv']['name'];
$fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

if ($fileExtension !== 'csv') {
    echo json_encode(['status' => 'error', 'pesan' => 'Format file harus berupa .csv']);
    exit;
}

try {
    // 1. Dapatkan semua nama kolom riil dari database (kecuali 'id')
    $stmtCols = $pdo->query("DESCRIBE db_customer");
    $dbColumns = $stmtCols->fetchAll(PDO::FETCH_COLUMN);
    $dbColumnsFiltered = array_values(array_diff($dbColumns, ['id']));

    // Buka file CSV
    if (($handle = fopen($fileTmpPath, "r")) !== FALSE) {
        // Ambil baris pertama sebagai Header CSV
        $csvHeader = fgetcsv($handle, 10000, ",");
        if (!$csvHeader) {
            throw new Exception("File CSV kosong atau tidak memiliki baris header.");
        }

        // Normalisasi nama kolom header (hapus spasi, ubah underscore)
        $csvHeaderMapped = array_map(function($h) {
            return trim(str_replace([' ', '-'], '_', $h));
        }, $csvHeader);

        // Petakan indeks kolom CSV ke kolom database asli
        $colMapping = [];
        foreach ($dbColumnsFiltered as $dbCol) {
            $indexInCsv = array_search($dbCol, $csvHeaderMapped);
            if ($indexInCsv !== FALSE) {
                $colMapping[$dbCol] = $indexInCsv;
            }
        }

        // Validasi: No_Pesanan harus ada di CSV sebagai kunci unik penentu duplikat
        if (!isset($colMapping['No_Pesanan'])) {
            throw new Exception("Kolom 'No_Pesanan' wajib ada di file CSV sebagai referensi kunci pembaruan data.");
        }

        // 2. Susun Dynamic SQL Query dengan klausa ON DUPLICATE KEY UPDATE
        // Agar fitur ini bekerja, pastikan kolom 'No_Pesanan' di MySQL berstatus UNIQUE KEY / PRIMARY KEY
        $insertCols = array_keys($colMapping);
        $placeholders = implode(', ', array_fill(0, count($insertCols), '?'));
        
        $updateParts = [];
        foreach ($insertCols as $col) {
            if ($col !== 'No_Pesanan') { // No_Pesanan tidak perlu diupdate nilainya
                $updateParts[] = "`$col` = VALUES(`$col`)";
            }
        }
        
        $sql = "INSERT INTO db_customer (" . implode(', ', array_map(fn($c) => "`$c`", $insertCols)) . ") 
                VALUES ($placeholders) 
                ON DUPLICATE KEY UPDATE " . implode(', ', $updateParts);

        $stmt = $pdo->prepare($sql);

        $insertedCount = 0;
        $updatedCount = 0;
        $pdo->beginTransaction();

        // 3. Looping baris data CSV
        while (($row = fgetcsv($handle, 10000, ",")) !== FALSE) {
            // Abaikan jika baris kosong
            if (count($row) === 1 && empty($row[0])) continue;

            $params = [];
            foreach ($insertCols as $col) {
                $csvIdx = $colMapping[$col];
                $val = isset($row[$csvIdx]) ? trim($row[$csvIdx]) : null;
                
                // Normalisasi nilai kosong agar tersimpan NULL di database
                if ($val === '' || strtolower($val) === 'null') {
                    $val = null;
                }
                $params[] = $val;
            }

            // Eksekusi query
            $stmt->execute($params);
            
            // PDO rowCount() pada "INSERT ... ON DUPLICATE KEY UPDATE" akan bernilai:
            // 1 jika baris baru sukses dibuat (INSERT)
            // 2 jika baris lama sukses diperbarui (UPDATE)
            // 0 jika baris lama ada tapi tidak ada nilai data yang berubah
            $affected = $stmt->rowCount();
            if ($affected === 1) {
                $insertedCount++;
            } elseif ($affected === 2) {
                $updatedCount++;
            }
        }

        fclose($handle);
        $pdo->commit();

        echo json_encode([
            'status' => 'success',
            'pesan' => "Sinkronisasi selesai! " . ($insertedCount + $updatedCount) . " baris diproses.",
            'detail' => [
                'insert' => $insertedCount,
                'update' => $updatedCount
            ]
        ]);
        exit;
    } else {
        throw new Exception("Gagal membaca file CSV sementara.");
    }

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['status' => 'error', 'pesan' => 'Proses Impor Gagal: ' . $e->getMessage()]);
    exit;
}