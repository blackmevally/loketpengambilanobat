<?php 
include("../config/db.php");

// Cari nomor terakhir hari ini
$today = date("Y-m-d");
$result = $conn->query("SELECT no_antrian FROM antrian WHERE DATE(created_at)='$today' ORDER BY id DESC LIMIT 1");
$row = $result->fetch_assoc();
$last = $row ? intval($row['no_antrian']) : 0;
$next = str_pad($last + 1, 3, "0", STR_PAD_LEFT);

// Hitung jumlah waiting
$count = $conn->query("SELECT COUNT(*) as jml FROM antrian WHERE status='waiting' AND DATE(created_at)='$today'")->fetch_assoc();
$jml_waiting = $count['jml'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Ambil Nomor Antrian Farmasi</title>
    <meta http-equiv="refresh" content="10"> <!-- refresh otomatis tiap 10 detik -->
    <style>
        body {
            background: linear-gradient(135deg, #2ecc71, #27ae60); /* hijau farmasi */
            font-family: Arial, sans-serif;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            color: white;
        }
        h1 {
            font-size: 42px;
            margin-bottom: 20px;
            text-shadow: 2px 2px 5px rgba(0,0,0,0.3);
        }
        .nomor-box {
            background: white;
            color: #2ecc71;
            font-size: 140px;
            font-weight: bold;
            padding: 40px 80px;
            border-radius: 20px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.3);
            margin-bottom: 30px;
            border: 10px solid #f1c40f; /* kuning */
        }
        .btn-ambil {
            background: #e74c3c; /* merah */
            color: white;
            border: none;
            padding: 20px 60px;
            font-size: 28px;
            font-weight: bold;
            border-radius: 12px;
            cursor: pointer;
            transition: 0.3s;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .btn-ambil:hover {
            background: #c0392b;
            transform: scale(1.05);
        }
        .waiting {
            font-size: 24px;
            margin-top: 15px;
            color: #f1c40f;
        }
        .footer {
            position: absolute;
            bottom: 20px;
            font-size: 14px;
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <h1>Ambil Nomor Antrian Farmasi</h1>
    <div class="nomor-box">
        <?= $next ?>
    </div>
    <form action="cetak.php" method="POST">
        <input type="hidden" name="next" value="<?= $next ?>">
        <button type="submit" name="ambil" class="btn-ambil">TEKAN 1x DI SINI</button>
    </form>
    <div class="waiting">Jumlah antrian menunggu: <?= $jml_waiting ?></div>
    <div class="footer">RSU Permata Medika Kebumen - Sistem Antrian Farmasi</div>
</body>
</html>
