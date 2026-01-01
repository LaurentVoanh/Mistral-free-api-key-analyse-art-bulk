<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Le Sanctuaire de l'Artiste</title>
    <style>
        :root { --p: #8e7cc3; --bg: #fcfaf8; --gold: #c5a059; }
        body { font-family: 'Times New Roman', serif; background: var(--bg); color: #2c2c2c; margin: 0; padding: 20px; line-height: 1.6; }
        .wrapper { max-width: 900px; margin: 0 auto; background: white; padding: 40px; border-radius: 8px; box-shadow: 0 4px 25px rgba(0,0,0,0.05); }
        
        .slide { display: none; }
        .active { display: block; animation: fadeIn 0.8s; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

        h1 { font-weight: normal; text-transform: uppercase; letter-spacing: 4px; color: var(--p); text-align: center; border-bottom: 1px solid #eee; padding-bottom: 20px; }
        input { width: 100%; padding: 15px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; font-size: 16px; }
        button { background: var(--p); color: white; border: none; padding: 20px; width: 100%; cursor: pointer; text-transform: uppercase; letter-spacing: 2px; transition: 0.3s; }
        button:hover { background: #5a4b81; }

        .drop-zone { border: 1px solid #ccc; padding: 60px; text-align: center; background: #fafafa; cursor: pointer; margin-bottom: 20px; }
        .preview-box { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 20px; }
        .preview-box img { width: 80px; height: 80px; object-fit: cover; border-radius: 4px; }

        .analysis-card { margin-bottom: 60px; border-bottom: 1px solid #eee; padding-bottom: 40px; }
        .analysis-image { width: 100%; max-height: 500px; object-fit: contain; background: #1a1a1a; border-radius: 8px; margin-bottom: 25px; display: block; }
        .analysis-text { padding: 0 20px; font-size: 1.1em; text-align: justify; white-space: pre-wrap; }
        .analysis-text h3 { color: var(--gold); font-variant: small-caps; border-bottom: 1px solid #f0f0f0; }

        .loader { text-align: center; padding: 20px; color: var(--p); font-style: italic; }
    </style>
</head>
<body>

<div class="wrapper">
    <div id="s1" class="slide active">
        <h1>Authentification</h1>
        <input type="email" id="email" placeholder="Email de l'Artiste">
        <input type="password" id="pass" placeholder="Clé de sécurité">
        <button onclick="auth()">Entrer dans l'Atelier</button>
    </div>

    <div id="s2" class="slide">
        <h1>Sélection des Œuvres</h1>
        <div class="drop-zone" onclick="document.getElementById('fileIn').click()">
            Déposez vos créations pour l'analyse
            <input type="file" id="fileIn" multiple accept="image/*" style="display:none" onchange="upPreview()">
        </div>
        <div id="upPreview" class="preview-box"></div>
        <button id="btnAnalyse" style="display:none" onclick="run()">Lancer la Critique</button>
    </div>

    <div id="s3" class="slide">
        <h1>Critiques d'Excellence</h1>
        <div id="running"></div>
        <div id="archives"></div>
    </div>
</div>

<script>
let creds = { email: '', pass: '' };
let files = [];

function go(n) {
    document.querySelectorAll('.slide').forEach(s => s.classList.remove('active'));
    document.getElementById('s'+n).classList.add('active');
    window.scrollTo(0,0);
}

async function auth() {
    creds.email = document.getElementById('email').value;
    creds.pass = document.getElementById('pass').value;
    const r = await fetch(`ajax.php?action=auth&email=${creds.email}&password=${creds.pass}`);
    const d = await r.json();
    if(d.success) { go(2); loadArchives(); }
}

function upPreview() {
    const input = document.getElementById('fileIn');
    files = Array.from(input.files);
    const box = document.getElementById('upPreview');
    box.innerHTML = '';
    files.forEach(f => {
        const reader = new FileReader();
        reader.onload = e => box.innerHTML += `<img src="${e.target.result}">`;
        reader.readAsDataURL(f);
    });
    document.getElementById('btnAnalyse').style.display = 'block';
}

async function run() {
    go(3);
    const runDiv = document.getElementById('running');
    
    for(let i=0; i < files.length; i++) {
        const card = document.createElement('div');
        card.className = 'analysis-card';
        card.innerHTML = `<div class="loader">L'IA contemple votre œuvre...</div>`;
        runDiv.prepend(card);

        const fd = new FormData();
        fd.append('action', 'analyser_image');
        fd.append('email', creds.email);
        fd.append('password', creds.pass);
        fd.append('index_cle', i);
        fd.append('image', files[i]);

        try {
            const resp = await fetch('ajax.php', { method: 'POST', body: fd });
            const res = await resp.json();
            renderCard(card, res);
        } catch(e) { card.innerHTML = "Erreur sur cette œuvre."; }
        
        await new Promise(r => setTimeout(r, 2000));
    }
}

function renderCard(target, data) {
    target.innerHTML = `
        <img src="${data.url_img}" class="analysis-image">
        <div class="analysis-text">
            <small style="color:#999">Analyse du ${data.date}</small>
            <h2>${data.nom}</h2>
            ${data.analyse}
        </div>
    `;
}

async function loadArchives() {
    const r = await fetch(`ajax.php?action=charger_archives&email=${creds.email}&password=${creds.pass}`);
    const d = await r.json();
    const arcDiv = document.getElementById('archives');
    arcDiv.innerHTML = '<h2>Archives de l\'Âme</h2>';
    d.forEach(item => {
        const div = document.createElement('div');
        div.className = 'analysis-card';
        renderCard(div, item);
        arcDiv.appendChild(div);
    });
}
</script>

</body>
</html>
