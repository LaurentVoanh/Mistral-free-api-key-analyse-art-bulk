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
$urlUser = 'uploads/' . $userKey; // Pour l'affichage HTML
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
        foreach (glob("$cheminUser/*.json") as $f) {
            $archives[] = json_decode(file_get_contents($f), true);
        }
        usort($archives, function($a, $b) { return strtotime($b['date_tri']) - strtotime($a['date_tri']); });
    }
    echo json_encode($archives);
    exit;
}

if ($action === 'analyser_image') {
    $indexCle = (int)$_POST['index_cle'];
    $apiKey = $clesAPI[$indexCle % count($clesAPI)];
    $file = $_FILES['image'];
    
    // Sauvegarde physique de l'image pour l'affichage futur
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $nomImagePhysique = uniqid() . '.' . $extension;
    $cheminImagePhysique = $cheminUser . '/' . $nomImagePhysique;
    move_uploaded_file($file['tmp_name'], $cheminImagePhysique);

    $imgData = base64_encode(file_get_contents($cheminImagePhysique));
    $mime = $file['type'];

    $prompt = "Tu es un Maître Critique d'Art renommé, doté d'une sensibilité poétique et d'une expertise technique encyclopédique. 
    Analyse cette œuvre avec une éloquence rare. Ta critique doit être une célébration vibrante structurée comme suit :
    
    1. EXORDE (L'Âme) : Une phrase métaphorique capturant l'essence ontologique de l'œuvre.
    2. ARCHITECTURE VISUELLE : Analyse la structure, l'équilibre des masses, la dynamique des lignes et la profondeur de champ.
    3. ALCHIMIE CHROMATIQUE : Explore la psychologie de la palette utilisée, le dialogue entre les tons et la gestion de la luminance (clair-obscur).
    4. SÉMIOTIQUE ET SYMBOLES : Décrypte les messages invisibles et ce que l'œuvre raconte sur la condition humaine ou l'univers.
    5. VERDICT DE L'EXPERT : Une conclusion élogieuse sur la singularité du geste artistique.
    
    Utilise un ton noble, inspirant et des termes académiques (ex: sfumato, impasto, tension narrative, chromatisme, onirisme). Termine par une 'Note d'Excellence' sur 10.";

    $payload = [
        'model' => 'pixtral-12b-2409',
        'messages' => [[
            'role' => 'user',
            'content' => [
                ['type' => 'text', 'text' => $prompt],
                ['type' => 'image_url', 'image_url' => ['url' => "data:$mime;base64,$imgData"]]
            ]
        ]]
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
        $saveData = [
            'nom' => $file['name'],
            'url_img' => $urlUser . '/' . $nomImagePhysique,
            'analyse' => $data['choices'][0]['message']['content'],
            'date' => date('d/m/Y H:i'),
            'date_tri' => date('Y-m-d H:i:s')
        ];
        file_put_contents($cheminUser . '/' . uniqid() . '.json', json_encode($saveData));
        echo json_encode($saveData);
    } else {
        http_response_code(500);
        echo json_encode(['erreur' => 'Erreur API']);
    }
    exit;
}
