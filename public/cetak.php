<?php
include("../config/db.php");
date_default_timezone_set("Asia/Jakarta");

// Ambil nomor antrian berikutnya
if (!isset($_POST['next'])) die("Nomor antrian tidak ditemukan.");
$noAntri = $_POST['next'];

// Simpan ke database
$conn->query("INSERT INTO antrian (no_antrian, status, created_at) VALUES ('$noAntri', 'waiting', NOW())");

// ====== FUNGSI CMD & DETEKSI PRINTER ======
function cmd($command) {
    exec($command . " 2>&1", $output, $status);
    return [$status, implode("\n", $output)];
}

function detectPrinterShare() {
    exec("wmic printer get ShareName,Default /format:list", $out);
    $share = null;
    foreach ($out as $line) {
        $line = trim($line);
        if (strpos($line, "ShareName=") === 0) {
            $share = substr($line, 10);
        }
        if (strpos($line, "Default=TRUE") !== false && !empty($share)) {
            return $share;
        }
    }
    return "ThermalPOS"; // fallback
}

// ====== ESC/POS FORMATTING ======
$ESC = chr(27);
$GS  = chr(29);
$ALIGN_CENTER = $ESC . "a" . chr(1);
$ALIGN_LEFT   = $ESC . "a" . chr(0);
$ALIGN_RIGHT  = $ESC . "a" . chr(2);
$DOUBLE_SIZE  = $ESC . "!" . chr(56); // double width & height
$NORMAL_SIZE  = $ESC . "!" . chr(0);
$BOLD_ON      = $ESC . "E" . chr(1);
$BOLD_OFF     = $ESC . "E" . chr(0);
$CUT          = $GS . "V" . chr(1);
$FEED3        = $ESC . "d" . chr(3); // feed 3 baris
$FEED6        = $ESC . "d" . chr(6); // feed 6 baris
$lineBreak    = "\r\n";

// ====== BUAT STRUK DUA LEMBAR ======
$tempFile = __DIR__ . "\\struk.txt";

function buatStruk($no, $tipe = "pasien") {
    global $ALIGN_CENTER, $ALIGN_LEFT, $ALIGN_RIGHT, $DOUBLE_SIZE, $NORMAL_SIZE, 
           $BOLD_ON, $BOLD_OFF, $CUT, $FEED3, $FEED6, $lineBreak;

    $str .= $ALIGN_CENTER;
    $str .= "========================" . $lineBreak;
    $str .= "ANTRIAN FARMASI" . $lineBreak;
    $str .= "========================" . $lineBreak . $lineBreak;
    $str .= $ALIGN_CENTER . "Nomor Antrian:" . $lineBreak;
    $str .= $BOLD_ON . $DOUBLE_SIZE;
    $str .= sprintf("%03d", $no) . $lineBreak;
    $str .= $BOLD_OFF . $NORMAL_SIZE;
    $str .= "------------------------" . $lineBreak;
    $str .= $ALIGN_CENTER;
    $str .= "Tgl: " . date("d-m-Y H:i:s") . $lineBreak;
    if ($tipe === "pasien") {
        $str .= "Lembar untuk pasien" . $lineBreak;
        $str .= "Harap menunggu panggilan" . $lineBreak;
    } else {
        $str .= "Lembar untuk petugas" . $lineBreak;
        $str .= "Klip Ke lembar Checklist" . $lineBreak;
    }
    $str .= "========================" . $lineBreak;
    $str .= $FEED6 . $CUT; // margin bawah + potong
    return $str;
}

// Gabungkan dua lembar
$struk  = buatStruk($noAntri, "pasien");
$struk .= buatStruk($noAntri, "petugas");

file_put_contents($tempFile, $struk);

// ====== CETAK ======
$printerShare = detectPrinterShare();
cmd("net use LPT1 /delete /y");
cmd("net use LPT1 \\\\localhost\\$printerShare /persistent:yes");

$cmdPrint = "copy /B \"$tempFile\" LPT1";
[$status, $output] = cmd($cmdPrint);

// ====== AUTO RECOVERY ======
if ($status !== 0) {
    cmd("net start spooler");
    sleep(2);
    cmd("net use LPT1 /delete /y");
    cmd("net use LPT1 \\\\localhost\\$printerShare /persistent:yes");
    [$status2, $output2] = cmd($cmdPrint);

    if ($status2 !== 0) {
        error_log("Gagal cetak setelah auto-recovery. CMD: $cmdPrint | Output: $output2");
        header("Location: index.php?error=1");
        exit;
    }
}

// Jika sukses
header("Location: index.php?success=1");
exit;
?>
