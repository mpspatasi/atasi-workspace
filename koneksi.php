<?php
$host = '172.16.1.4';
$db   = 'jurnal_transaksi_atasi';
$user = 'mysql';
$pass = 'E9Kn79QRhLOkJ5BXKMWUprzhyarW0JLXmiwzZkSF8zESQbJ2UqiSdjdmoiAG21VZ';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;port=3306;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>