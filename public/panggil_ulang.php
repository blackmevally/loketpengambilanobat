<?php
include("../config/db.php");
header('Content-Type: application/json');

$loket = $_POST['loket'] ?? '';
if (empty($loket)) {
    echo json_encode(["status" => "error", "msg" => "Loket tidak dipilih"]);
    exit;
}

$res = $conn->query("SELECT no_antrian FROM antrian WHERE status='called' AND loket='$loket' ORDER BY updated_at DESC, created_at DESC LIMIT 1");

if ($res && $res->num_rows > 0) {
    $row = $res->fetch_assoc();
    $no = $row['no_antrian'];

    // 🟢 Simpan event agar SSE (display.php) tahu ada panggilan ulang
    $file = __DIR__ . "/../display/last_call.json";
    file_put_contents($file, json_encode([
        "no" => $no,
        "loket" => $loket,
        "time" => time(),        // Pastikan timestamp baru agar tidak diabaikan
        "type" => "ulang"
    ]));

    echo json_encode(["status" => "ok", "no" => $no, "loket" => $loket]);
} else {
    echo json_encode(["status" => "empty"]);
}
