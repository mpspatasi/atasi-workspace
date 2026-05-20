<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['username'])) {
    echo json_encode(['status' => 'error', 'pesan' => 'Sesi berakhir']);
    exit;
}

require 'koneksi.php';
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? '';
$TOTAL_ANGGOTA_LAIN = 28; 

try {
    if ($action === 'fetch') {
        $me_username = $_SESSION['username'];
        $me_nama = $_SESSION['nama_lengkap'];

        $updateStmt = $pdo->prepare("UPDATE tb_chat_global SET read_by = CONCAT(IFNULL(read_by,''), ?) WHERE sender != ? AND (read_by IS NULL OR read_by NOT LIKE ?)");
        $updateStmt->execute([$me_username . ',', $me_nama, '%' . $me_username . ',%']);

        // Ambil 50 pesan terakhir + JOIN dengan pesan yang di-reply
        $stmt = $pdo->query("SELECT t1.*, t2.sender as reply_sender, t2.message as reply_message, t2.is_retracted as reply_retracted 
                             FROM (SELECT * FROM tb_chat_global ORDER BY id DESC LIMIT 50) t1 
                             LEFT JOIN tb_chat_global t2 ON t1.reply_to = t2.id 
                             ORDER BY t1.id ASC");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $filtered_data = [];
        foreach ($data as $row) {
            if (strpos($row['deleted_by'] ?? '', $me_username . ',') !== false) continue;
            $readers = array_filter(explode(',', $row['read_by'] ?? ''));
            $row['ticks'] = (count($readers) >= $TOTAL_ANGGOTA_LAIN) ? 2 : 1; 
            $filtered_data[] = $row;
        }

        echo json_encode(['status' => 'success', 'data' => $filtered_data]);
    } 
    elseif ($action === 'send') {
        $message = trim($input['message'] ?? '');
        $reply_to = $input['reply_to'] ?? null;
        $sender = $_SESSION['nama_lengkap'] ?? 'Tim ATASI';
        $inisial = $_SESSION['inisial'] ?? 'C';

        if ($message !== '') {
            $safe_message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
            $stmt = $pdo->prepare("INSERT INTO tb_chat_global (sender, inisial, message, reply_to) VALUES (?, ?, ?, ?)");
            $stmt->execute([$sender, $inisial, $safe_message, $reply_to]);
            echo json_encode(['status' => 'success']);
        }
    }
    elseif ($action === 'edit') {
        $id = $input['id'] ?? 0;
        $message = trim($input['message'] ?? '');
        $sender = $_SESSION['nama_lengkap'];

        if ($id && $message !== '') {
            $safe_message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
            $stmt = $pdo->prepare("UPDATE tb_chat_global SET message = ?, is_edited = 1 WHERE id = ? AND sender = ? AND is_retracted = 0");
            $stmt->execute([$safe_message, $id, $sender]);
            echo json_encode(['status' => 'success']);
        }
    }
    elseif ($action === 'retract') {
        $id = $input['id'] ?? 0;
        $sender = $_SESSION['nama_lengkap'];
        $stmt = $pdo->prepare("UPDATE tb_chat_global SET is_retracted = 1 WHERE id = ? AND sender = ?");
        $stmt->execute([$id, $sender]);
        echo json_encode(['status' => 'success']);
    }
    elseif ($action === 'delete') {
        $id = $input['id'] ?? 0;
        $me_username = $_SESSION['username'];
        $stmt = $pdo->prepare("UPDATE tb_chat_global SET deleted_by = CONCAT(IFNULL(deleted_by,''), ?) WHERE id = ?");
        $stmt->execute([$me_username . ',', $id]);
        echo json_encode(['status' => 'success']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'pesan' => 'Server Error']);
}
?>