<?php
include("../config/db.php");
header('Content-Type: application/json');

$tanggal = date('Y-m-d');
$res = $conn->query("SELECT no_antrian, created_at FROM antrian WHERE status='waiting' AND DATE(created_at)='$tanggal' ORDER BY id ASC LIMIT 20");

$data = [];
while ($r = $res->fetch_assoc()) {
    $data[] = $r;
}
echo json_encode($data);
