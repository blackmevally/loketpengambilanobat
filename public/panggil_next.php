<?php
include("../config/db.php");
header('Content-Type: application/json');

$loket = $_POST['loket'] ?? '';
if (empty($loket)) {
    echo json_encode(["status" => "error", "msg" => "Loket tidak dipilih"]);
    exit;
}

$tanggalNow = date('Y-m-d');
$result = $conn->query("SELECT * FROM antrian WHERE status='waiting' AND DATE(created_at)='$tanggalNow' ORDER BY id ASC LIMIT 1");

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $no = $row['no_antrian'];

    $conn->query("UPDATE antrian SET status='called', loket='$loket' WHERE id=" . $row['id']);
    echo json_encode(["status" => "ok", "no" => $no, "loket" => $loket]);
} else {
    echo json_encode(["status" => "empty"]);
}
// Tulis ke file event SSE
$file = __DIR__ . "/../display/last_call.json";
file_put_contents($file, json_encode([
    "no" => $no,
    "loket" => $loket,
    "time" => time(),
    "type" => "baru"
]));
