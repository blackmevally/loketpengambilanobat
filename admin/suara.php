<?php
$configFile = __DIR__ . '/../config/config_suara.json';

// Simpan jika ada POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        "template" => $_POST['template'],
        "voice" => $_POST['voice'],
        "lang" => $_POST['lang'],
        "volume" => floatval($_POST['volume']),
        "rate" => floatval($_POST['rate'])
    ];
    file_put_contents($configFile, json_encode($data, JSON_PRETTY_PRINT));
    $saved = true;
}

// Ambil config lama
$config = file_exists($configFile)
    ? json_decode(file_get_contents($configFile), true)
    : [
        "template" => "Panggilan penyerahan obat, nomor antrian {nomor}, silakan menuju ke {loket}",
        "voice" => "default",
        "lang" => "id-ID",
        "volume" => 1,
        "rate" => 1
    ];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>🔊 Pengaturan Template Suara</title>
<style>
body {
    font-family: "Segoe UI", sans-serif;
    background: #1e8449;
    color: white;
    margin: 0;
    padding: 30px;
}
h1 {
    margin-top: 0;
    font-size: 28px;
}
a {
    color: #fff;
    text-decoration: none;
    font-weight: bold;
    margin-right: 15px;
}
.container {
    background: rgba(255,255,255,0.1);
    padding: 25px;
    border-radius: 12px;
    width: 700px;
    margin: 20px auto;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}
label {
    display: block;
    margin-top: 10px;
    font-weight: bold;
}
textarea, select, input[type=number] {
    width: 100%;
    padding: 10px;
    border-radius: 8px;
    border: none;
    margin-top: 5px;
    font-size: 16px;
}
button {
    padding: 12px 25px;
    font-size: 16px;
    border: none;
    border-radius: 8px;
    margin-top: 15px;
    cursor: pointer;
    font-weight: bold;
}
#btnSave { background: #27ae60; color: white; }
#btnTest { background: #f1c40f; color: #2c3e50; margin-left: 10px; }
#btnBack { background: #3498db; color: white; }
.notice {
    background: rgba(255,255,255,0.2);
    padding: 8px;
    border-radius: 8px;
    margin-bottom: 10px;
}
</style>
</head>
<body>
<h1>🔊 Pengaturan Template Suara</h1>
<div class="notice">
    <a href="dashboard.php">🏠 Dashboard</a> |
    <a href="loket.php">📦 Loket</a> |
    <a href="reset.php">♻️ Reset Harian</a>
</div>

<div class="container">
    <?php if (!empty($saved)): ?>
        <div style="background:#2ecc71;padding:10px;border-radius:6px;">
            ✅ Pengaturan suara berhasil disimpan.
        </div>
    <?php endif; ?>

    <form method="POST">
        <label>🎙️ Template Suara:</label>
        <small>Gunakan variabel: <b>{nomor}</b> untuk nomor antrian, <b>{loket}</b> untuk tujuan loket.</small>
        <textarea name="template" rows="3" required><?= htmlspecialchars($config['template']) ?></textarea>

        <label>🗣️ Jenis Suara:</label>
        <select name="voice" id="voiceSelect">
            <option value="default">Default Browser</option>
        </select>

        <label>🌐 Bahasa:</label>
        <select name="lang" id="langSelect">
            <option value="id-ID" <?= $config['lang']=='id-ID'?'selected':'' ?>>Indonesia</option>
            <option value="en-US" <?= $config['lang']=='en-US'?'selected':'' ?>>English</option>
        </select>

        <label>🔉 Volume (0–1):</label>
        <input type="number" name="volume" step="0.1" min="0" max="1" value="<?= htmlspecialchars($config['volume']) ?>">

        <label>⚡ Kecepatan (0.5–2):</label>
        <input type="number" name="rate" step="0.1" min="0.5" max="2" value="<?= htmlspecialchars($config['rate']) ?>">

        <div>
            <button id="btnSave" type="submit">💾 Simpan Template</button>
            <button id="btnTest" type="button">🔁 Uji Suara</button>
        </div>
    </form>
</div>

<script>
// Ambil list suara dari browser
function loadVoices() {
    const select = document.getElementById('voiceSelect');
    const voices = speechSynthesis.getVoices();
    const currentVoice = "<?= $config['voice'] ?>";
    select.innerHTML = "";
    voices.forEach(v => {
        const opt = document.createElement('option');
        opt.value = v.name;
        opt.textContent = `${v.name} (${v.lang})`;
        if (v.name === currentVoice) opt.selected = true;
        select.appendChild(opt);
    });
    if (voices.length === 0) {
        const opt = document.createElement('option');
        opt.textContent = "Suara tidak tersedia (reload halaman)";
        select.appendChild(opt);
    }
}
window.speechSynthesis.onvoiceschanged = loadVoices;
loadVoices();

// Fungsi uji suara langsung
document.getElementById('btnTest').addEventListener('click', () => {
    const template = document.querySelector('textarea[name=template]').value;
    const voiceName = document.getElementById('voiceSelect').value;
    const lang = document.getElementById('langSelect').value;
    const volume = parseFloat(document.querySelector('input[name=volume]').value) || 1;
    const rate = parseFloat(document.querySelector('input[name=rate]').value) || 1;

    const teks = template
        .replace("{nomor}", "sepuluh")
        .replace("{loket}", "loket satu");

    const utter = new SpeechSynthesisUtterance(teks);
    utter.lang = lang;
    utter.volume = volume;
    utter.rate = rate;

    const voices = speechSynthesis.getVoices();
    const selectedVoice = voices.find(v => v.name === voiceName);
    if (selectedVoice) utter.voice = selectedVoice;

    speechSynthesis.cancel();
    speechSynthesis.speak(utter);
});
</script>
</body>
</html>
