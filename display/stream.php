<?php
include("../config/db.php");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Display Antrian Farmasi</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
body {
    font-family: 'Segoe UI', sans-serif;
    background: linear-gradient(180deg, #27ae60, #1e8449);
    color: white;
    text-align: center;
    margin: 0;
    overflow: hidden;
}
.header {
    background: rgba(0,0,0,0.2);
    padding: 20px;
    font-size: 40px;
    font-weight: bold;
    letter-spacing: 2px;
}
.status {
    position: fixed;
    top: 10px;
    right: 20px;
    font-size: 16px;
    background: rgba(255,255,255,0.15);
    padding: 6px 14px;
    border-radius: 20px;
}
.status.online {
    background: #2ecc71;
    color: white;
}
.status.offline {
    background: #e74c3c;
    color: white;
}
.main {
    display: grid;
    grid-template-columns: 40% 60%;
    height: 70vh;
}
.left {
    display: flex;
    flex-direction: column;
    justify-content: center;
}
#noAntrian {
    font-size: 280px;
    font-weight: bold;
    color: #f1c40f;
    text-shadow: 4px 4px 10px rgba(0,0,0,0.4);
}
#loketTujuan {
    background: #e74c3c;
    color: white;
    display: inline-block;
    margin-top: 20px;
    padding: 15px 50px;
    border-radius: 12px;
    font-size: 40px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}
.right video {
    width: 100%;
    height: 100%;
    background: white;
    object-fit: cover;
}
.footer {
    position: absolute;
    bottom: 0;
    width: 100%;
    display: flex;
    justify-content: space-around;
    background: rgba(0,0,0,0.2);
    padding: 20px 0;
}
.loket-box {
    background: rgba(255,255,255,0.1);
    padding: 15px;
    border-radius: 15px;
    width: 25%;
    color: white;
}
.loket-box h2 {
    font-size: 28px;
    margin: 0;
    font-weight: 600;
}
.loket-box p {
    font-size: 48px;
    margin: 10px 0 0 0;
    color: #f1c40f;
    font-weight: bold;
}
#btnSuara {
    position: fixed;
    top: 20px; left: 20px;
    background: #f1c40f;
    border: none;
    color: #2c3e50;
    padding: 10px 20px;
    border-radius: 10px;
    font-size: 16px;
    cursor: pointer;
    font-weight: bold;
    z-index: 999;
}
</style>
</head>
<body>
<button id="btnSuara">🔊 Aktifkan Suara</button>

<div class="header">ANTRIAN FARMASI</div>
<div id="status" class="status offline">🔴 Putus koneksi</div>

<div class="main">
    <div class="left">
        <div id="noAntrian">---</div>
        <div id="loketTujuan">Menunggu panggilan...</div>
    </div>
    <div class="right">
        <!-- Area streaming dari OBS/NGINX -->
        <video id="tvStream" autoplay muted playsinline controls style="width:100%; height:100%; background:black;"></video>
        <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
        <script>
        document.addEventListener("DOMContentLoaded", function () {
            const video = document.getElementById("tvStream");
            const streamURL = "http://192.168.9.59:8080/live/stream.m3u8"; // ubah ke IP server kalau beda mesin

            if (Hls.isSupported()) {
                const hls = new Hls({
                    autoStartLoad: true,
                    enableWorker: true,
                    lowLatencyMode: true
                });
                hls.loadSource(streamURL);
                hls.attachMedia(video);
                hls.on(Hls.Events.MANIFEST_PARSED, function () {
                    video.play().catch(()=>{});
                });
            } else if (video.canPlayType("application/vnd.apple.mpegurl")) {
                // Safari native HLS
                video.src = streamURL;
                video.addEventListener("loadedmetadata", function () {
                    video.play().catch(()=>{});
                });
            } else {
                console.error("Browser tidak mendukung HLS.js.");
            }
        });
        </script>
    </div>
</div>

<div class="footer">
    <div class="loket-box"><h2>Loket 3</h2><p id="loket3">---</p></div>
    <div class="loket-box"><h2>Loket 2</h2><p id="loket2">---</p></div>
    <div class="loket-box"><h2>Loket 1</h2><p id="loket1">---</p></div>
</div>

<audio id="bell" src="../tingtong.mp3"></audio>

<script>
const bell = document.getElementById("bell");
const statusEl = document.getElementById("status");

// Konversi angka ke kata
function angkaKeKata(angka) {
    const s = ["","satu","dua","tiga","empat","lima","enam","tujuh","delapan","sembilan"];
    const b = ["sepuluh","sebelas","dua belas","tiga belas","empat belas","lima belas","enam belas","tujuh belas","delapan belas","sembilan belas"];
    const p = ["","", "dua puluh","tiga puluh","empat puluh","lima puluh","enam puluh","tujuh puluh","delapan puluh","sembilan puluh"];
    let n = parseInt(angka);
    if(isNaN(n)) return angka;
    if(n<10) return s[n];
    if(n<20) return b[n-10];
    if(n<100) return p[Math.floor(n/10)] + (n%10?" "+s[n%10]:"");
    return angka;
}

// TTS
function playVoice(teks) {
    if (!window.speechSynthesis) return;
    let u = new SpeechSynthesisUtterance(teks);
    u.lang = "id-ID";
    u.rate = 0.95;
    u.pitch = 1.05;
    u.volume = 1;
    speechSynthesis.cancel();
    setTimeout(()=>speechSynthesis.speak(u),300);
}

// Koneksi SSE
function updateKoneksi(ok) {
    statusEl.className = "status " + (ok ? "online":"offline");
    statusEl.innerHTML = ok ? "🟢 Terhubung ke Server" : "🔴 Putus koneksi";
}
const sse = new EventSource("event_stream.php");
sse.onopen = ()=>updateKoneksi(true);
sse.onerror = ()=>updateKoneksi(false);
sse.addEventListener("ping", ()=>updateKoneksi(true));

let lastTime=0;
sse.addEventListener("update", e=>{
    updateKoneksi(true);
    const d=JSON.parse(e.data);
    if(!d.no||d.time===lastTime)return;
    lastTime=d.time;

    document.getElementById("noAntrian").textContent=d.no;
    document.getElementById("loketTujuan").textContent="Menuju "+d.loket;
    const ln=d.loket.match(/\d+/);
    if(ln){
        const el=document.getElementById("loket"+ln[0]);
        if(el)el.textContent=d.no;
    }

    // TTS & bell
    bell.play().catch(()=>{});
    setTimeout(()=>playVoice("Panggilan penyerahan obat, nomor antrian "+angkaKeKata(d.no)+", silakan menuju "+d.loket),1000);
});

document.getElementById("btnSuara").addEventListener("click",()=>{
    let t=new SpeechSynthesisUtterance("Suara aktif. Display siap digunakan.");
    t.lang="id-ID";
    speechSynthesis.speak(t);
    document.getElementById("btnSuara").style.display="none";
});
</script>
</body>
</html>
