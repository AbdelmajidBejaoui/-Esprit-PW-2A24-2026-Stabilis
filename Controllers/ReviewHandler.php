<?php

require_once __DIR__ . '/../config/mail.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Services/MailService.php';
require_once __DIR__ . '/../Services/SimpleSmtpMailer.php';

function redirectReview($itemId, $status, $itemType = 'product') {
    $page = $itemType === 'pack' ? 'pack.php' : 'product.php';
    header('Location: ../Views/front/' . $page . '?id=' . (int)$itemId . '&review=' . $status);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../Views/front/shop.php');
    exit();
}

$itemType = ($_POST['item_type'] ?? 'product') === 'pack' ? 'pack' : 'product';
$productId = (int)($_POST['product_id'] ?? 0);
$packId = (int)($_POST['pack_id'] ?? 0);
$itemId = $itemType === 'pack' ? $packId : $productId;
$name = trim($_POST['name'] ?? '');
$rating = (int)($_POST['rating'] ?? 0);
$comment = trim($_POST['comment'] ?? '');
$errors = [];

if ($itemId <= 0) {
    $errors[] = 'Element invalide.';
}
if ($name === '' || strlen($name) < 2 || !preg_match('/^[\p{L}\s\-\']+$/u', $name)) {
    $errors[] = 'Nom invalide.';
}
if ($rating < 1 || $rating > 5) {
    $errors[] = 'Note invalide.';
}
if ($comment === '' || strlen($comment) < 5) {
    $errors[] = 'Commentaire invalide.';
}

if (!empty($errors)) {
    redirectReview($itemId, 'invalid', $itemType);
}

$pdo = Database::getConnection();
if ($itemType === 'pack') {
    $stmt = $pdo->prepare('SELECT nom FROM packs WHERE id = ? AND active = 1');
    $stmt->execute([$packId]);
} else {
    $stmt = $pdo->prepare('SELECT nom FROM produits WHERE id = ?');
    $stmt->execute([$productId]);
}
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: ../Views/front/shop.php');
    exit();
}

$config = require __DIR__ . '/../config/mail.php';
$mailService = new MailService($config);
$to = $config['alert_recipient'] ?? $config['from_email'];
$subject = ($itemType === 'pack' ? 'Nouvel avis pack - ' : 'Nouvel avis produit - ') . $product['nom'];
$safeProduct = htmlspecialchars($product['nom'], ENT_QUOTES, 'UTF-8');
$safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$safeComment = nl2br(htmlspecialchars($comment, ENT_QUOTES, 'UTF-8'));
$stars = str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
$body = '
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
</head>
<body style="margin:0; padding:0; background:#f3faf5; font-family:Arial, sans-serif; color:#24362b;">
    <div style="max-width:640px; margin:24px auto; background:#ffffff; border:1px solid #d9eadf; border-radius:14px; overflow:hidden;">
        <div style="background:#1A4D3A; color:#ffffff; padding:24px;">
            <h1 style="margin:0; font-size:24px;">Nouvel avis ' . ($itemType === 'pack' ? 'pack' : 'produit') . '</h1>
            <p style="margin:6px 0 0; color:#d9eadf;">Stabilis - retour client</p>
        </div>
        <div style="padding:24px;">
            <div style="background:#f3faf5; border-left:4px solid #3A6B4B; padding:16px; border-radius:10px; margin-bottom:18px;">
                <div style="font-size:13px; color:#587260; text-transform:uppercase; letter-spacing:.4px;">' . ($itemType === 'pack' ? 'Pack' : 'Produit') . '</div>
                <div style="font-size:20px; font-weight:700; color:#1A4D3A; margin-top:4px;">' . $safeProduct . '</div>
            </div>
            <p style="margin:0 0 10px;"><strong>Client:</strong> ' . $safeName . '</p>
            <p style="margin:0 0 18px;"><strong>Note:</strong> <span style="color:#1A4D3A; font-size:18px;">' . $stars . '</span> (' . $rating . '/5)</p>
            <div style="border-top:1px solid #d9eadf; padding-top:18px;">
                <div style="font-weight:700; color:#1A4D3A; margin-bottom:8px;">Commentaire</div>
                <div style="line-height:1.6; font-size:15px;">' . $safeComment . '</div>
            </div>
        </div>
        <div style="background:#f3faf5; color:#587260; padding:14px 24px; font-size:12px; border-top:1px solid #d9eadf;">
            Email automatique envoye depuis le formulaire avis ' . ($itemType === 'pack' ? 'pack' : 'produit') . '.
        </div>
    </div>
</body>
</html>';

$sent = false;
if (($config['method'] ?? '') === 'smtp' && !empty($config['smtp']['username']) && !empty($config['smtp']['password'])) {
    $smtp = new SimpleSmtpMailer(
        $config['smtp']['host'] ?? 'smtp.gmail.com',
        $config['smtp']['port'] ?? 587,
        $config['smtp']['username'],
        $config['smtp']['password'],
        $config['smtp']['secure'] ?? 'tls'
    );
    $sent = $smtp->send($to, $subject, $body, $config['from_email'], 'Stabilis Avis', true);
    if (!$sent) {
        $sent = $mailService->send($to, $subject, $body, $config['from_email'], 'Stabilis Avis');
    }
} else {
    $sent = $mailService->send($to, $subject, $body, $config['from_email'], 'Stabilis Avis');
}

redirectReview($itemId, $sent ? 'sent' : 'mail_error', $itemType);
