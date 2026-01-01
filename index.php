<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>ArtCritik - Atelier Curateur</title>
    <style>
        :root { --p: #8e7cc3; --bg: #fdfaf8; }
        body { font-family: 'Georgia', serif; background: var(--bg); color: #333; margin: 0; padding: 20px; line-height: 1.6; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 40px; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.05); }
        .slide { display: none; }
        .active { display: block; animation: fadeIn 0.5s; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        
        input { width: 100%; padding: 15px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 10px; box-sizing: border-box; }
        button { background: var(--p); color: white; border: none; padding: 18px; width: 100%; cursor: pointer; border-radius: 10px; font-size: 16px; transition: 0.3s; }
        button:hover { background: #7a6ba7; }
        
        .drop-zone { border: 2px dashed var(--p); padding: 40px; text-align: center; background: #faf9ff; cursor: pointer; border-radius: 15px; }
        .analysis-card { margin-top: 40px; padding-bottom: 40px; border-bottom: 2px solid #f0f0f0; }
        .art-image { width: 100%; max-height: 500px; object-fit: contain; background: #111; border-radius: 12px; margin-bottom: 25px; display: block; }
        .art-text { white-space: pre-wrap; font-size: 1.05em; text-align: justify; }
        
        .pagination { display: flex; justify-content: center; gap: 10px; margin-top: 30px; }
        .page-btn { padding: 8px 15px; background: #eee; border-radius: 5px; cursor: pointer; }
        .page-btn.active { background: var(--p); color: white; }
        .spinner { border: 4px solid #f3f3f3; border-top: 4px solid var(--p); border-radius: 50%; width: 30px; height: 30px; animation: spin 1s linear infinite; margin: 20px auto; }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>

<div class="container">
    <div id="slide1" class="slide active">
        <h1 style="text-align:center; color:var(--p);">Sanctuaire de l'Artiste</h1>
        <input type="email" id="email" placeholder="Email">
        <input type="password" id="pass" placeholder="Mot de passe">
        <button onclick="auth()">Entrer</button>
    </div>

    <div id="slide2" class="slide">
        <h1>Sélectionner des Œuvres</h1>
        <div class="drop-zone" onclick="document.getElementById('fIn').click()">
            Glissez ou cliquez pour charger vos créations
            <input type="file" id="fIn" multiple accept="image/*" style="display:none" onchange="preview()">
        </div>
        <div id="miniBox" style="display:flex; gap:10px; margin-top:15px; flex-wrap:wrap;"></div>
        <button id="btnGo" style="display:none" onclick="lancerAnalyse()">Lancer l'Analyse Totale</button>
    </div>

    <div id="slide3" class="slide">
        <h1>Critiques & Expositions</h1>
        <div id="traitementEnCours"></div>
        <div id="archives"></div>
        <div id="pagination" class="pagination"></div>
    </div>
</div>

<script>
let session = { email: '', pass: '' };
let filesToAnalyze = [];
let allArchives = [];
let currentPage = 1;
const itemsPerPage = 5;

function go(n) {
    document.querySelectorAll('.slide').forEach(s => s.classList.remove('active'));
    document.getElementById('slide'+n).classList.add('active');
}

async function auth() {
    session.email = document.getElementById('email').value;
    session.pass = document.getElementById('pass').value;
    const r = await fetch(`ajax.php?action=auth&email=${session.email}&password=${session.pass}`);
    const d = await r.json();
    if(d.success) { go(2); chargerArchives(); }
}

function preview() {
    filesToAnalyze = Array.from(document.getElementById('fIn').files);
    document.getElementById('miniBox').innerHTML = filesToAnalyze.map(f => `<small>${f.name}</small>`).join(' | ');
    document.getElementById('btnGo').style.display = 'block';
}

async function lancerAnalyse() {
    go(3);
    const zone = document.getElementById('traitementEnCours');
    for(let i=0; i < filesToAnalyze.length; i++) {
        const card = document.createElement('div');
        card.className = 'analysis-card';
        card.innerHTML = `<div class="spinner"></div><p style="text-align:center">L'IA prépare la critique et le concept d'expo pour "${filesToAnalyze[i].name}"...</p>`;
        zone.prepend(card);

        const fd = new FormData();
        fd.append('action', 'analyser_image');
        fd.append('email', session.email); fd.append('password', session.pass);
        fd.append('index_cle', i); fd.append('image', filesToAnalyze[i]);

        try {
            const resp = await fetch('ajax.php', { method: 'POST', body: fd });
            const data = await resp.json();
            renderCard(card, data);
        } catch(e) { card.innerHTML = "Erreur."; }
        await new Promise(r => setTimeout(r, 2000));
    }
    chargerArchives(); // Rafraîchir les archives après analyse
}

function renderCard(target, data) {
    target.innerHTML = `
        <img src="${data.url_img}" class="art-image">
        <div class="art-text">
            <h2 style="color:var(--p); margin:0;">${data.nom}</h2>
            <small>Analyse du ${data.date}</small><br><br>
            ${data.analyse_complete}
        </div>
    `;
}

async function chargerArchives() {
    const r = await fetch(`ajax.php?action=charger_archives&email=${session.email}&password=${session.pass}`);
    allArchives = await r.json();
    displayArchives();
}

function displayArchives() {
    const start = (currentPage - 1) * itemsPerPage;
    const paginatedItems = allArchives.slice(start, start + itemsPerPage);
    const arcDiv = document.getElementById('archives');
    arcDiv.innerHTML = allArchives.length ? '<h2 style="margin-top:60px; border-top:1px solid #eee; padding-top:20px;">Votre Collection</h2>' : '';
    
    paginatedItems.forEach(item => {
        const div = document.createElement('div');
        div.className = 'analysis-card';
        renderCard(div, item);
        arcDiv.appendChild(div);
    });
    renderPagination();
}

function renderPagination() {
    const totalPages = Math.ceil(allArchives.length / itemsPerPage);
    const nav = document.getElementById('pagination');
    nav.innerHTML = '';
    if (totalPages <= 1) return;

    for (let i = 1; i <= totalPages; i++) {
        const btn = document.createElement('div');
        btn.className = `page-btn ${i === currentPage ? 'active' : ''}`;
        btn.innerText = i;
        btn.onclick = () => { currentPage = i; displayArchives(); window.scrollTo(0, 0); };
        nav.appendChild(btn);
    }
}
</script>
</body>
</html>
