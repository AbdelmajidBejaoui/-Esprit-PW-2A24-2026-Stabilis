<?php
require_once __DIR__ . '/../config/mail.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Methode non autorisee.');
    }

    $config = require __DIR__ . '/../config/mail.php';
    $apiKey = $config['gemini_api_key'] ?? getenv('GEMINI_API_KEY');
    $packName = trim($_POST['name'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $packStock = max(1, (int)($_POST['pack_stock'] ?? 1));
    $currentDescription = trim($_POST['current_description'] ?? '');
    $products = json_decode($_POST['products'] ?? '[]', true);

    if (!is_array($products) || count($products) < 2) {
        throw new Exception('Selectionnez au moins deux produits.');
    }

    $productLines = [];
    foreach ($products as $product) {
        $name = trim($product['name'] ?? '');
        if ($name !== '') {
            $category = trim($product['category'] ?? 'nutrition sportive');
            $productLines[] = '- ' . $name . ' (' . $category . ') x' . $packStock;
        }
    }

    if (count($productLines) < 2) {
        throw new Exception('Produits invalides.');
    }

    $context = [
        'Nom du pack: ' . ($packName !== '' ? $packName : 'Pack Stabilis'),
        'Prix: ' . ($price !== '' ? $price : 'non precise'),
        'Composition: ' . $packStock . ' unite(s) de chaque produit',
        'Produits:',
        implode("\n", $productLines)
    ];

    if ($currentDescription !== '') {
        $context[] = 'Description actuelle a ameliorer: ' . $currentDescription;
    }

    $prompt = "Tu es un redacteur e-commerce expert en nutrition sportive pour Stabilis.

Objectif: generer une description de pack claire, credible et vendeuse, basee uniquement sur les produits selectionnes.

Informations:
" . implode("\n", $context) . "

Contraintes:
- Ecris en francais naturel
- 4 a 5 phrases maximum, environ 90 a 130 mots
- Explique la logique du pack et pourquoi ces produits vont ensemble
- Mentionne que chaque produit selectionne est present en quantite egale
- Ton professionnel, utile, sans exageration
- Pas d emojis, pas de markdown, pas de liste
- Pas de promesses medicales ni de garanties de resultat
- Ne donne pas de dosage ni de conseil medical
- Retourne uniquement la description finale.";

    $payload = [
        'contents' => [[
            'parts' => [['text' => $prompt]]
        ]],
        'generationConfig' => [
            'temperature' => 0.45,
            'topP' => 0.9,
            'maxOutputTokens' => 1024,
            'thinkingConfig' => ['thinkingBudget' => 0]
        ]
    ];

    $description = '';
    $source = 'fallback';
    $aiError = '';

    if ($apiKey && $apiKey !== 'YOUR_GEMINI_API_KEY_HERE' && function_exists('curl_init')) {
        $aiResult = callGeminiPackDescription($apiKey, $payload);
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
        $description = buildLocalPackDescription($packName, $productLines, $packStock, $currentDescription);
    }

    echo json_encode([
        'success' => true,
        'description' => cleanPackDescription($description),
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

function callGeminiPackDescription($apiKey, array $payload) {
    $models = ['gemini-2.5-flash', 'gemini-flash-latest', 'gemini-2.0-flash', 'gemini-1.5-flash-latest'];
    $lastError = 'aucune reponse Gemini exploitable';

    foreach ($models as $model) {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($model) . ':generateContent?key=' . urlencode($apiKey);
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
            error_log('Gemini pack description failed. HTTP: ' . $httpCode . ' Error: ' . $curlError . ' Response: ' . substr((string)$response, 0, 500));
            continue;
        }

        $data = json_decode($response, true);
        $parts = $data['candidates'][0]['content']['parts'] ?? [];
        $text = '';
        foreach ($parts as $part) {
            $text .= ' ' . ($part['text'] ?? '');
        }
        $text = trim($text);
        if (strlen($text) >= 160 && preg_match('/[.!?]$/', $text)) {
            return ['text' => $text, 'error' => ''];
        }

        $lastError = $text !== '' ? 'reponse Gemini trop courte' : 'reponse Gemini vide';
    }

    return ['text' => '', 'error' => $lastError];
}

function cleanPackDescription($description) {
    $description = trim((string)$description);
    $description = preg_replace('/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}]/u', '', $description);
    $description = preg_replace('/^["\'`]+|["\'`]+$/', '', $description);
    $description = preg_replace('/\*\*?|#{1,6}|^-+\s*/m', '', $description);
    $description = preg_replace('/\s+/', ' ', $description);
    return trim($description);
}

function buildLocalPackDescription($packName, array $productLines, $packStock, $currentDescription = '') {
    $name = trim($packName) !== '' ? trim($packName) : 'Ce pack Stabilis';
    $products = implode(', ', array_map(function ($line) {
        return trim(preg_replace('/^- /', '', $line));
    }, $productLines));

    if (trim($currentDescription) !== '') {
        return $name . ' rassemble des produits complementaires dans une composition simple et equilibree. ' . trim($currentDescription) . ' Chaque reference selectionnee est presente en quantite egale, ce qui rend le pack plus clair a comprendre et plus facile a gerer en stock. Il convient aux clients qui veulent une solution pratique pour organiser leur routine sportive sans composer eux-memes leur panier.';
    }

    return $name . ' rassemble ' . $products . ' dans une composition simple et coherente pour une routine sportive mieux organisee. Chaque produit selectionne est present en quantite egale x' . (int)$packStock . ', afin de garder un pack lisible, facile a comparer et simple a gerer en stock. Cette combinaison convient aux clients qui veulent acheter plusieurs essentiels en une seule fois, avec une logique claire autour de la performance, de la regularite et de la praticite. Le pack reste professionnel et utile sans promettre de resultat miraculeux.';
}
?>
