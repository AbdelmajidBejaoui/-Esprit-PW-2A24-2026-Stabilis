<?php
require_once __DIR__ . '/partials/auth.php';
require_once __DIR__ . '/../../../Controllers/UserC.php';

$userC = new UserC();
$token = trim($_GET['token'] ?? '');
$ok = false;

if ($token !== '') {
    $ok = $userC->verifyEmailToken($token);
}

?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Verification email</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <?php if ($ok): ?>
        <div class="alert alert-success">Verification reussie. Vous pouvez fermer cette page et vous connecter.</div>
    <?php else: ?>
        <div class="alert alert-danger">Jeton invalide ou expire.</div>
    <?php endif; ?>
</div>
</body>
</html>
