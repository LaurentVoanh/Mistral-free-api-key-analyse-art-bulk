<?php
header('Content-Type: application/json');
set_time_limit(600); 

$clesAPI = [
  '1xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
    '2xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
    '3xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'
];

$dossierBase = __DIR__ . '/uploads/';
if (!file_exists($dossierBase)) mkdir($dossierBase, 0777, true);

$email = filter_var($_REQUEST['email'] ?? '', FILTER_SANITIZE_EMAIL);
$pass = $_REQUEST['password'] ?? '';
$userKey = md5($email . $pass); 
$cheminUser = $dossierBase . $userKey;
$urlUser = 'uploads/' . $userKey;
$action = $_REQUEST['action'] ?? '';

if ($action === 'auth') {
    if (!empty($email) && !empty($pass)) {
        if (!file_exists($cheminUser)) mkdir($cheminUser, 0777, true);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit;
}

if ($action === 'charger_archives') {
    $archives = [];
    if (file_exists($cheminUser)) {
        foreach (glob("$cheminUser/*.json") as $f) $archives[] = json_decode(file_get_contents($f), true);
        usort($archives, function($a, $b) { return strtotime($b['date_tri']) - strtotime($a['date_tri']); });
    }
    echo json_encode($archives);
    exit;
}

if ($action === 'analyser_image') {
    $indexCle = (int)$_POST['index_cle'];
    $apiKey = $clesAPI[$indexCle % count($clesAPI)];
    $file = $_FILES['image'];
    
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $imgNom = uniqid() . '.' . $ext;
    $imgChemin = $cheminUser . '/' . $imgNom;
    move_uploaded_file($file['tmp_name'], $imgChemin);

    $imgData = base64_encode(file_get_contents($imgChemin));
$prompt = "Tu es une entité hybride : Expert d'art international chez Christie's, Sémiologue et Philosophe de l'esthétique. Ton regard est celui d'un commissaire-priseur doté d'une culture encyclopédique.

    STRUCTURE DE TON EXPERTISE :

    # I. PHÉNOMÉNOLOGIE VISUELLE (Ce que l'on voit)
    - Analyse descriptive radicale : Décompose l'image par strates (Premier plan, arrière-plan, focale).
    - Morphologie de la lumière : Étude de la source lumineuse, de l'incidence des ombres et de la température chromatique.
    - Gestualité : Analyse de la texture perçue (touche, grain, pixellisation, empâtements) et de la tension compositionnelle.

    # II. ANALYSE INTELLECTUELLE & ARCHÉTYPALE
    - Ontologie : Rattache l'œuvre aux fondements de l'histoire de l'art (du Paléolithique à la Post-Modernité).
    - Dialogue philosophique : Invoque la pensée de maîtres (Benjamin, Adorno, Baudrillard ou Foucault) pour décrypter le message caché.
    - Mythologie & Psychologie : Quelle pulsion humaine ou quelle figure mythique est ici réactivée ?

    # III. ESTIMATION & COTATION PRÉVISIONNELLE
    - Dimensions présumées : Estime la taille physique de l'œuvre selon sa densité visuelle et son sujet.
    - Valeur de marché : Si cet artiste était coté sur le marché international (Artprice/Sotheby's), établis une estimation basse et une estimation haute en Euros (€).
    - Justification de la cote : Analyse la rareté du sujet et la signature stylistique pour justifier ce prix.

    # IV. PROPOSITION D'EXPOSITION & ARGUMENTAIRE DE VENTE
    - **Titre de l'Exposition** : (Titre métaphysique et curatorial).
    - **Motivation des Collectionneurs** : Pourquoi cette œuvre est-elle un 'must-have' ? Explique pourquoi la demande pour ce type d'œuvre est en explosion. 
    - **Scénographie & Placement** : Comment exposer l'œuvre pour maximiser son aura et sa valeur perçue.
    - **Argumentaire de Marché** : Convainc le collectionneur que cette pièce est un actif tangible majeur avec un fort potentiel de plus-value.

    # V. CODEX GÉNÉRATIF (Prompt IA)
    - Rédige un prompt ultra-sophistiqué en anglais pour reproduire cette esthétique exacte.

    Ton ton doit être celui d'une autorité incontestée : savant, complexe, utilisant un jargon technique précis (e.g., anamorphose, clair-obscur, paradigme, spéculatif, valeur fiduciaire).";

    $payload = [
        'model' => 'pixtral-12b-2409',
        'messages' => [['role' => 'user', 'content' => [
            ['type' => 'text', 'text' => $prompt],
            ['type' => 'image_url', 'image_url' => ['url' => "data:".$file['type'].";base64,$imgData"]]
        ]]]
    ];

    $ch = curl_init('https://api.mistral.ai/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer '.$apiKey],
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    $res = curl_exec($ch);
    $data = json_decode($res, true);
    curl_close($ch);

    if (isset($data['choices'][0]['message']['content'])) {
        $resultat = [
            'nom' => $file['name'],
            'url_img' => $urlUser . '/' . $imgNom,
            'analyse_complete' => $data['choices'][0]['message']['content'],
            'date' => date('d/m/Y H:i'),
            'date_tri' => date('Y-m-d H:i:s')
        ];
        file_put_contents($cheminUser . '/' . uniqid() . '.json', json_encode($resultat));
        echo json_encode($resultat);
    }
    exit;
}
