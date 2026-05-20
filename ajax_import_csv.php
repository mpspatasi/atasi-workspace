<?php
// Silent Mode: Mencegah error PHP merusak respon JSON
error_reporting(0);
ini_set('display_errors', 0);
ob_start(); 

require 'koneksi.php';

ob_clean();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_FILES['file_csv']) || $_FILES['file_csv']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['status' => 'error', 'pesan' => 'File CSV tidak terdeteksi.']);
    exit;
}

$fileTmpPath = $_FILES['file_csv']['tmp_name'];
$handle = fopen($fileTmpPath, 'r');

if ($handle !== FALSE) {
    $pdo->beginTransaction();
    try {
        // Cek duplikat berdasarkan No Pesanan, Nama, dan Produk
        $stmtCheck = $pdo->prepare("SELECT id FROM db_customer WHERE No_Pesanan = ? AND Nama = ? AND Produk = ? LIMIT 1");

        // INSERT (22 Kolom: Mengabaikan Kuartal & Kodepos agar Trigger phpMyAdmin bekerja)
        $sqlInsert = "INSERT INTO db_customer (
            Media, Tanggal_Pesanan, Waktu_Pesanan, No_Pesanan, Resi, Tanggal_Dikirim, 
            Status_Pesanan, Tanggal_Retur, Produk, Jumlah, Ekspedisi, Username, Nama, Telp, 
            Provinsi, Kabupaten, Kecamatan, Handle, Keterangan, ValueCX, ValueADS, Komoditas
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmtInsert = $pdo->prepare($sqlInsert);

        // UPDATE
        $sqlUpdate = "UPDATE db_customer SET 
            Media=?, Tanggal_Pesanan=?, Waktu_Pesanan=?, Resi=?, Tanggal_Dikirim=?, 
            Status_Pesanan=?, Tanggal_Retur=?, Produk=?, Jumlah=?, Ekspedisi=?, Username=?, Nama=?, Telp=?, 
            Provinsi=?, Kabupaten=?, Kecamatan=?, Handle=?, Keterangan=?, ValueCX=?, ValueADS=?, Komoditas=? 
            WHERE id=?";
        $stmtUpdate = $pdo->prepare($sqlUpdate);

        $rowInsert = 0; $rowUpdate = 0; $isHeader = true;

        while (($line = fgets($handle)) !== FALSE) {
            $delimiter = (strpos($line, ';') !== false) ? ';' : ',';
            $data = str_getcsv($line, $delimiter);

            if ($isHeader) {
                $isHeader = false;
                if (strcasecmp(trim($data[0] ?? ''), 'media') === 0) continue;
            }

            $noPesan = trim($data[4] ?? '');
            $nm      = trim($data[13] ?? '');
            $prod    = trim($data[9] ?? '');

            if (empty($noPesan) && empty($nm)) continue;

            // Bersihkan format tanggal & uang
            $tglP = cleanDate($data[2] ?? '');
            $tglD = cleanDate($data[6] ?? '');
            $tglR = cleanDate($data[8] ?? '');
            $jam  = cleanTime($data[3] ?? '');
            $vCX  = cleanMoney($data[21] ?? '');
            $vAds = cleanMoney($data[22] ?? '');
            $jml  = (int)cleanMoney($data[10] ?? '0');

            $stmtCheck->execute([$noPesan, $nm, $prod]);
            $exist = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if ($exist) {
                // Jalankan Update
                $stmtUpdate->execute([
                    $data[0]??'', $tglP, $jam, $data[5]??'', $tglD, $data[7]??'', $tglR, 
                    $prod, $jml, $data[11]??'', $data[12]??'', $nm, $data[14]??'', 
                    $data[15]??'', $data[16]??'', $data[17]??'', $data[19]??'', $data[20]??'', 
                    $vCX, $vAds, $data[23]??'', $exist['id']
                ]);
                $rowUpdate++;
            } else {
                // Jalankan Insert
                $stmtInsert->execute([
                    $data[0]??'', $tglP, $jam, $noPesan, $data[5]??'', $tglD, $data[7]??'', 
                    $tglR, $prod, $jml, $data[11]??'', $data[12]??'', $nm, $data[14]??'', 
                    $data[15]??'', $data[16]??'', $data[17]??'', $data[19]??'', $data[20]??'', 
                    $vCX, $vAds, $data[23]??''
                ]);
                $rowInsert++;
            }
        }
        $pdo->commit();
        echo json_encode(['status' => 'success', 'pesan' => "Mantap! Selesai diproses:\n- $rowInsert Data Baru\n- $rowUpdate Data Diperbarui"]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'pesan' => 'DB Error: ' . $e->getMessage()]);
    }
    fclose($handle);
}

function cleanDate($s) { $s = trim($s); if(!$s || $s=='-') return null; $t = strtotime(str_replace(['/','.'],'-',$s)); return $t ? date('Y-m-d',$t) : null; }
function cleanTime($s) { $s = trim($s); if(!$s || $s=='-') return null; $t = strtotime($s); return $t ? date('H:i:s',$t) : null; }
function cleanMoney($s) { return preg_replace('/[^0-9\-]/', '', trim($s)); }
?>