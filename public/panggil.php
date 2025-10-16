<?php
include("../config/db.php");

// 🔹 Ambil daftar loket dari tabel loket
$loketList = [];
$loketQuery = $conn->query("SELECT nama_loket AS loket FROM loket ORDER BY id ASC");
while ($row = $loketQuery->fetch_assoc()) {
    $loketList[] = $row['loket'];
}
if (empty($loketList)) $loketList = ["Loket 1", "Loket 2", "Loket 3"];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Panel Pemanggil Antrian Farmasi</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: linear-gradient(135deg, #27ae60, #1e8449);
    color: white;
    text-align: center;
    padding: 40px;
}
h1 { font-size: 40px; margin-bottom: 10px; }
form { margin-top: 30px; }
select {
    font-size: 20px;
    padding: 10px 20px;
    border-radius: 10px;
}
button {
    font-size: 22px;
    padding: 14px 40px;
    margin: 10px;
    border: none;
    border-radius: 10px;
    background: #f1c40f;
    color: #2c3e50;
    cursor: pointer;
    font-weight: bold;
}
button:hover { background: #f39c12; }
.info { margin-top: 20px; font-size: 22px; }
table {
    margin: 30px auto;
    width: 75%;
    border-collapse: collapse;
    background: rgba(0,0,0,0.2);
    border-radius: 10px;
    overflow: hidden;
}
th, td {
    border: 1px solid rgba(255,255,255,0.3);
    padding: 10px;
}
th { background: rgba(0,0,0,0.4); }
td { font-size: 20px; }
.highlight {
    background: #f1c40f !important;
    color: #2c3e50;
    font-weight: bold;
}
</style>
</head>
<body>
<h1>Panel Pemanggil Antrian Farmasi</h1>

<form id="formPanggil">
    <label for="loket">Pilih Loket:</label>
    <select id="loket" required>
        <?php foreach ($loketList as $l): ?>
            <option value="<?= htmlspecialchars($l) ?>"><?= htmlspecialchars($l) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="button" id="btnPanggil">📢 Panggil Berikutnya</button>
    <button type="button" id="btnUlang">🔁 Panggil Ulang</button>
</form>

<p id="notif" class="info"></p>
<div class="info">Jumlah antrian menunggu: <b id="waitingCount">0</b></div>

<h2>Daftar Nomor Menunggu</h2>
<table>
    <thead><tr><th>No Antrian</th><th>Waktu Ambil</th></tr></thead>
    <tbody id="waitingList"><tr><td colspan="2">Memuat...</td></tr></tbody>
</table>

<audio id="sound" src="../tingtong.mp3"></audio>

<script>
// Fungsi memuat daftar antrian menunggu
async function loadWaiting() {
    const res = await fetch("waiting_data.php");
    const data = await res.json();
    const tbody = document.getElementById("waitingList");
    const countEl = document.getElementById("waitingCount");
    tbody.innerHTML = "";
    countEl.textContent = data.length;

    if (data.length === 0) {
        tbody.innerHTML = `<tr><td colspan="2">Tidak ada antrian menunggu</td></tr>`;
        return;
    }

    data.forEach(row => {
        const tr = document.createElement("tr");
        tr.innerHTML = `<td>${row.no_antrian}</td><td>${row.created_at}</td>`;
        tbody.appendChild(tr);
    });
}

// Fungsi panggil berikutnya
async function panggilBerikutnya() {
    const loket = document.getElementById("loket").value;
    const notif = document.getElementById("notif");
    notif.textContent = "⏳ Memanggil nomor berikutnya...";
    notif.style.color = "#f1c40f";

    const res = await fetch("panggil_next.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ loket })
    });
    const data = await res.json();

    if (data.status === "ok") {
        notif.innerHTML = `✅ Nomor antrian <b>${data.no}</b> dipanggil ke <b>${data.loket}</b>`;
        notif.style.color = "#fff";
        document.getElementById("sound").play().catch(()=>{});
        highlightRow(data.no);
        loadWaiting();
    } else if (data.status === "empty") {
        notif.innerHTML = "⚠️ Tidak ada antrian menunggu.";
        notif.style.color = "orange";
    } else {
        notif.innerHTML = "❌ Gagal memanggil antrian.";
        notif.style.color = "red";
    }
}

// Fungsi panggil ulang
async function panggilUlang() {
    const loket = document.getElementById("loket").value;
    const notif = document.getElementById("notif");
    notif.textContent = "🔁 Memanggil ulang nomor terakhir...";
    notif.style.color = "#f1c40f";

    const res = await fetch("panggil_ulang.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ loket })
    });
    const data = await res.json();

    if (data.status === "ok") {
        notif.innerHTML = `🔁 Nomor antrian <b>${data.no}</b> dipanggil ulang ke <b>${data.loket}</b>`;
        notif.style.color = "#fff";

        // Bunyi tingtong
        document.getElementById("sound").play().catch(()=>{});

        // 🔊 Kirim trigger ke display agar TTS berbunyi
        await fetch("../display/trigger_tts.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({ no: data.no, loket: data.loket })
        });
    } else {
        notif.innerHTML = "⚠️ Belum ada nomor yang dipanggil di loket ini.";
        notif.style.color = "orange";
    }
}

// Efek highlight baris yang baru dipanggil
function highlightRow(no) {
    document.querySelectorAll("#waitingList tr").forEach(tr => {
        if (tr.children[0] && tr.children[0].textContent.trim() === no) {
            tr.classList.add("highlight");
            setTimeout(() => tr.classList.remove("highlight"), 3000);
        }
    });
}

document.getElementById("btnPanggil").addEventListener("click", panggilBerikutnya);
document.getElementById("btnUlang").addEventListener("click", panggilUlang);

// Auto refresh daftar setiap 5 detik
setInterval(loadWaiting, 5000);
loadWaiting();
</script>
</body>
</html>
