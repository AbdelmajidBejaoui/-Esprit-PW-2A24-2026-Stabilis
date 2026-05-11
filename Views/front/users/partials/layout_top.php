<?php
if (!isset($pageTitle)) {
    $pageTitle = 'Stabilis';
}
if (!isset($heroTitle)) {
    $heroTitle = 'Athlete Community';
}
if (!isset($heroSubtitle)) {
    $heroSubtitle = 'Gestion des profils';
}
if (!isset($activePage)) {
    $activePage = 'home';
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$cartCount = array_sum($_SESSION['cart'] ?? []);
$activeFrontPage = 'account';
if ($activePage === 'login') {
    $activeFrontPage = 'login';
} elseif ($activePage === 'signup') {
    $activeFrontPage = 'signup';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?php echo htmlspecialchars($pageTitle); ?> | Stabilis</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="/AdminLTE3/assets/css/stabilis.css?v=5">
    <link rel="stylesheet" href="/AdminLTE3/assets/css/front-style.css?v=10">
    <link rel="stylesheet" href="/AdminLTE3/assets/css/front-pages.css?v=5">
    <link rel="stylesheet" href="/AdminLTE3/assets/css/user-auth.css?v=1">
</head>
<body class="user-auth-page">
    <?php require __DIR__ . '/../../partials/navigation.php'; ?>

    <section class="hero-bread">
        <div class="container">
            <p><?php echo htmlspecialchars($pageTitle); ?></p>
            <h1 class="bread"><?php echo htmlspecialchars($heroTitle); ?></h1>
            <h5 class="mt-2 mb-0 text-white-50"><?php echo htmlspecialchars($heroSubtitle); ?></h5>
        </div>
    </section>

    <section class="ftco-section">
        <div class="container">
