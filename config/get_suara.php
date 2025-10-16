<?php
header('Content-Type: application/json');
$configFile = __DIR__ . '/config_suara.json';

if (file_exists($configFile)) {
    echo file_get_contents($configFile);
} else {
    echo json_encode([
        "template" => "Panggilan penyerahan obat, nomor antrian {nomor}, silakan menuju ke {loket}",
        "voice" => "default",
        "lang" => "id-ID",
        "volume" => 1,
        "rate" => 1
    ]);
}
?>
