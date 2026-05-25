<?php
$host = 'mysql://mysql:E9Kn79QRhLOkJ5BXKMWUprzhyarW0JLXmiwzZkSF8zESQbJ2UqiSdjdmoiAG21VZ@e6yyn6xv9yiprmm1esqltamu:3306/default';
$db   = 'jurnal_transaksi_atasi';
$user = 'root';
$pass = 'CButdzzrksIHIcWtSki9kIG64an0F7fEV60xdgSfAuzizKxhCVnmueqagdIpp6qC';
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
