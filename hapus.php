<?php
require 'koneksi.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "DELETE FROM db_customer WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
}

// Balik ke halaman utama setelah menghapus
header("Location: index.php");
exit;
?>