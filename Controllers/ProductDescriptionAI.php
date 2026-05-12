<?php
require_once __DIR__ . '/../config/mail.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Methode non autorisee.');
    }

    $config = require __DIR__ . '/../config/mail.php';
    $apiKey = $config['gemini_api_key'] ?? getenv('GEMINI_API_KEY');
    $productName = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $promoPrice = trim($_POST['promo_price'] ?? '');
    $stock = trim($_POST['stock'] ?? '');
    $currentDescription = trim($_POST['current_description'] ?? '');

    if ($productName === '') {
        throw new Exception('Veuillez entrer le nom du produit avant de generer une description.');
    }

    $contextLines = [
        'Produit: ' . $productName,
        'Categorie: ' . ($category !== '' ? $category : 'nutrition sportive')
    ];

    if ($currentDescription !== '') {
        $contextLines[] = 'Description actuelle a ameliorer: ' . $currentDescription;
    }

    $prompt = "Tu es un redacteur e-commerce expert en nutrition sportive pour la boutique Stabilis.

Objectif: generer une description produit claire, credible et specifique qui aide le client a comprendre exactement a quoi sert le produit.

Informations disponibles:
" . implode("\n", $contextLines) . "

Contraintes:
- Ecris en francais naturel
- 4 a 5 phrases maximum, environ 80 a 120 mots
- Ton professionnel, utile et vendeur sans exageration
- Mentionne le type de produit, son usage concret, le contexte general d'utilisation et le profil de client concerne
- Utilise les indices du nom du produit pour deduire les caracteristiques probables, sans inventer de composition precise
- Si une description actuelle existe, garde ses bonnes informations mais rends-la plus fluide et plus complete
- Pas d emojis
- Pas de markdown, pas de titre, pas de liste
- Pas de promesses medicales
- Pas de fausses garanties comme guerit, brule les graisses, prise de muscle assuree
- Ne mentionne jamais le prix, le stock ou la disponibilite
- Ne donne jamais de dosage, frequence ou conseil de consommation precis sauf si le nom du produit le contient
- Retourne uniquement la description finale.";

    $payload = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.45,
            'topP' => 0.9,
            'maxOutputTokens' => 1024,
            'thinkingConfig' => [
                'thinkingBudget' => 0
            ]
        ]
    ];

    $description = '';
    $source = 'fallback';
    $aiError = '';
    if ($apiKey && $apiKey !== 'YOUR_GEMINI_API_KEY_HERE' && function_exists('curl_init')) {
        $aiResult = callGeminiDescription($apiKey, $payload);
        $description = $aiResult['text'];
        $aiError = $aiResult['error'];
        if ($description !== '') {
            $source = 'gemini';
        }
    } elseif (!function_exists('curl_init')) {
        $aiError = 'cURL PHP indisponible';
    } else {
        $aiError = 'cle Gemini manquante';
    }

    if ($description === '') {
        $description = buildLocalDescription($productName, $category, $currentDescription);
    }

    $description = preg_replace('/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}]/u', '', $description);
    $description = cleanGeneratedDescription($description);

    echo json_encode([
        'success' => true,
        'description' => trim($description),
        'source' => $source,
        'ai_error' => $source === 'fallback' ? $aiError : ''
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function callGeminiDescription($apiKey, $payload) {
    $models = [
        'gemini-3-flash-preview',
        'gemini-2.5-flash',
        'gemini-flash-latest',
        'gemini-2.0-flash',
        'gemini-1.5-flash-latest'
    ];
    $endpoints = [
        'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=' . urlencode($apiKey),
        'https://generativelanguage.googleapis.com/v1/models/%s:generateContent?key=' . urlencode($apiKey)
    ];

    $lastError = 'aucune reponse Gemini exploitable';

    foreach ($models as $model) {
        foreach ($endpoints as $endpoint) {
            $url = sprintf($endpoint, rawurlencode($model));
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 25);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError || $httpCode !== 200) {
                $lastError = 'HTTP ' . $httpCode . ($curlError ? ' - ' . $curlError : '');
                error_log('Gemini description generation failed. HTTP: ' . $httpCode . ' Error: ' . $curlError . ' Response: ' . substr((string)$response, 0, 500));
                continue;
            }

            $data = json_decode($response, true);
            $parts = $data['candidates'][0]['content']['parts'] ?? [];
            $text = '';
            foreach ($parts as $part) {
                $text .= ' ' . ($part['text'] ?? '');
            }
            $text = trim($text);
            if (isUsableAiDescription($text)) {
                return ['text' => $text, 'error' => ''];
            }

            $lastError = $text !== '' ? 'reponse Gemini trop courte' : 'reponse Gemini vide';
        }
    }

    return ['text' => '', 'error' => $lastError];
}

function isUsableAiDescription($text) {
    $text = trim((string)$text);
    if (strlen($text) < 180) {
        return false;
    }

    return (bool)preg_match('/[.!?]$/', $text);
}

function cleanGeneratedDescription($description) {
    $description = trim((string)$description);
    $description = preg_replace('/^["\'`]+|["\'`]+$/', '', $description);
    $description = preg_replace('/\*\*?|#{1,6}|^-+\s*/m', '', $description);
    $description = preg_replace('/\s+/', ' ', $description);
    return trim($description);
}

function buildLocalDescription($productName, $category, $currentDescription = '') {
    $haystack = strtolower(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $productName . ' ' . $category));

    if (trim($currentDescription) !== '') {
        return "$productName reprend les points forts de votre description actuelle en les presentant de facon plus claire pour la fiche produit. " . trim($currentDescription) . " Il s'integre facilement dans une routine sportive ou nutritionnelle et aide le client a identifier rapidement son utilite au quotidien.";
    }

    if (strpos($haystack, 'creatine') !== false) {
        return "$productName est pense pour les sportifs qui veulent renforcer leur routine de performance sur les efforts courts, explosifs et repetes. Facile a integrer avant ou apres l'entrainement selon vos habitudes, il convient aux pratiquants de musculation, de fitness ou de sports intenses. Sa place est simple dans un programme regulier: accompagner la progression, la constance et une nutrition mieux organisee, sans compliquer votre quotidien.";
    }

    if (strpos($haystack, 'whey') !== false || strpos($haystack, 'protein') !== false || strpos($haystack, 'proteine') !== false) {
        return "$productName est une solution pratique pour completer vos apports en proteines dans une routine sportive active. Elle s'utilise facilement apres l'entrainement, en collation ou lorsque vos repas ne couvrent pas suffisamment vos besoins. Ideale pour les pratiquants de musculation, fitness ou remise en forme, elle aide a garder une alimentation plus structuree et un rythme plus regulier autour de vos objectifs.";
    }

    if (strpos($haystack, 'vitamin') !== false || strpos($haystack, 'multivit') !== false) {
        return "$productName complete facilement une routine quotidienne lorsque l'entrainement, le travail et les journees chargees demandent plus d'organisation. Ce produit convient aux personnes qui veulent soutenir leur equilibre nutritionnel avec une solution simple et reguliere. Il s'integre au quotidien sans complexite et accompagne une approche sportive plus stable, centree sur la constance et le bien-etre general.";
    }

    if (strpos($haystack, 'snack') !== false || strpos($haystack, 'bar') !== false) {
        return "$productName est concu pour les moments ou vous avez besoin d'une collation rapide, pratique et mieux adaptee a une routine active. Facile a emporter au bureau, a la salle ou en deplacement, il aide a rester organise entre deux repas ou autour de l'entrainement. C'est une option utile pour les sportifs et les clients qui veulent garder une alimentation plus maitrisee sans perdre en simplicite.";
    }

    if (strpos($haystack, 'pre') !== false || strpos($haystack, 'workout') !== false || strpos($haystack, 'booster') !== false) {
        return "$productName est adapte aux sportifs qui veulent aborder leurs seances avec plus de concentration et une routine mieux preparee. Il trouve naturellement sa place avant l'entrainement, surtout lors des journees ou la motivation et l'organisation comptent. Pense pour le fitness, la musculation ou les efforts soutenus, il accompagne votre programme sans promettre de resultat miraculeux.";
    }

    if (strpos($haystack, 'bcaa') !== false || strpos($haystack, 'eaa') !== false || strpos($haystack, 'amino') !== false || strpos($haystack, 'acide') !== false) {
        return "$productName s'adresse aux sportifs qui veulent structurer leur nutrition autour de l'entrainement avec une solution simple a utiliser. Il peut accompagner les seances intenses, les periodes de regularite sportive ou les routines de recuperation selon vos habitudes. C'est un choix pratique pour les clients qui cherchent un produit facile a integrer dans un programme fitness ou musculation.";
    }

    return "$productName est concu pour s'ajouter facilement a une routine sportive et nutritionnelle serieuse. Sa description, son format et sa categorie en font une option pratique pour les clients qui veulent mieux organiser leur programme au quotidien. Il accompagne les objectifs de performance, de regularite ou de remise en forme avec une utilisation simple et une approche credible, adaptee a l'univers Stabilis.";
}
?>
