<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($pageTitle)) $pageTitle = 'FitTrack';
if (!isset($heroTitle)) $heroTitle = 'FitTrack';
if (!isset($heroBg))    $heroBg    = 'bg_1.jpg';
if (!isset($activePage)) $activePage = '';

require_once __DIR__ . '/auth.php';

// Detect depth: partials is always inside View/FrontOffice/partials/
// The pages that include this are in View/FrontOffice/ (one level up from partials)
// So from the browser's perspective the page is at View/FrontOffice/page.php
// and assets are at ../../public/vegefoods/
$ASSETS = '../../public/vegefoods';

$progCount = 0;
if (frontIsLoggedIn()) {
    require_once __DIR__ . '/../../../Controller/ProgrammeC.php';
    $_pc = new ProgrammeC();
    $progCount = $_pc->count(frontUserId());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> | FitTrack</title>
    <link href="https://fonts.googleapis.com/css?family=Poppins:300,400,600,700,800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Lora:400,400i,700,700i&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Amatic+SC:400,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="<?= $ASSETS ?>/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= $ASSETS ?>/css/open-iconic-bootstrap.min.css">
    <link rel="stylesheet" href="<?= $ASSETS ?>/css/animate.css">
    <link rel="stylesheet" href="<?= $ASSETS ?>/css/owl.carousel.min.css">
    <link rel="stylesheet" href="<?= $ASSETS ?>/css/owl.theme.default.min.css">
    <link rel="stylesheet" href="<?= $ASSETS ?>/css/magnific-popup.css">
    <link rel="stylesheet" href="<?= $ASSETS ?>/css/ionicons.min.css">
    <link rel="stylesheet" href="<?= $ASSETS ?>/css/flaticon.css">
    <link rel="stylesheet" href="<?= $ASSETS ?>/css/icomoon.css">
    <link rel="stylesheet" href="<?= $ASSETS ?>/css/style.css">
    <style>
        #ftco-navbar { background:#fff !important; box-shadow:0 2px 12px rgba(0,0,0,.08); }
        #ftco-navbar .navbar-brand { color:#82ae46 !important; font-weight:800; }
        .hero-bread { padding:7em 0 5em !important; }
        .badge-debutant      { background:#28a745;color:#fff;padding:3px 11px;border-radius:20px;font-size:.75rem;font-weight:600; }
        .badge-intermediaire { background:#ffc107;color:#000;padding:3px 11px;border-radius:20px;font-size:.75rem;font-weight:600; }
        .badge-avance        { background:#dc3545;color:#fff;padding:3px 11px;border-radius:20px;font-size:.75rem;font-weight:600; }
        .btn-vege       { background:#82ae46;color:#fff !important;border-color:#82ae46;border-radius:30px; }
        .btn-vege:hover { background:#6b952f; }
        .card-vege { border:none;border-radius:14px;box-shadow:0 4px 20px rgba(0,0,0,.08); }
        .card-vege .card-header { background:#82ae46;color:#fff;border-radius:14px 14px 0 0 !important;font-weight:700;border:none; }
        .product { background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08);margin-bottom:28px;transition:transform .2s;opacity:1 !important;visibility:visible !important; }
        .product:hover { transform:translateY(-4px); }
        .product .img-prod { display:block;position:relative;overflow:hidden; }
        .product .img-prod img { height:180px;width:100%;object-fit:cover;display:block; }
        .product .img-prod .overlay { position:absolute;inset:0;background:rgba(0,0,0,.1);transition:.3s; }
        .product .img-prod:hover .overlay { background:rgba(0,0,0,.3); }
        .product .img-prod .status { position:absolute;top:10px;left:10px;padding:3px 12px;border-radius:20px;font-size:.75rem;font-weight:700; }
        .product .text { padding:16px; }
        .product .text h3 { font-size:1rem;font-weight:700;margin-bottom:5px; }
        .product .text h3 a { color:#333; }
        .product .text h3 a:hover { color:#82ae46; }
        .error-list { color:#dc3545;font-size:.85em; }
    </style>
</head>
<body>
<div id="ftco-loader" class="fullscreen">
    <svg class="circular" width="48px" height="48px"><circle class="path-bg" cx="24" cy="24" r="22" fill="none" stroke-width="4" stroke="#eeeeee"/><circle class="path" cx="24" cy="24" r="22" fill="none" stroke-width="4" stroke-miterlimit="10" stroke="#82ae46"/></svg>
</div>

<nav class="navbar navbar-expand-lg navbar-light ftco-navbar-light" id="ftco-navbar">
    <div class="container">
        <a class="navbar-brand" href="catalogue.php">🏋️ FitTrack</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#ftco-nav">
            <span class="oi oi-menu"></span> Menu
        </button>
        <div class="collapse navbar-collapse" id="ftco-nav">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item <?= $activePage==='catalogue'?'active':'' ?>">
                    <a href="catalogue.php" class="nav-link">Catalogue</a>
                </li>
                <?php if (frontIsLoggedIn()): ?>
                <li class="nav-item <?= $activePage==='programme'?'active':'' ?>">
                    <a href="programme.php" class="nav-link">
                        Mon Programme
                        <?php if ($progCount > 0): ?>
                        <span class="badge badge-pill" style="background:#82ae46;color:#fff;font-size:.65rem;"><?= $progCount ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item <?= $activePage==='ai_gen'?'active':'' ?>">
                    <a href="custom_workout.php" class="nav-link"><i class="fas fa-magic" style="color:#82ae46;"></i> Générateur IA</a>
                </li>
                <li class="nav-item <?= $activePage==='profil'?'active':'' ?>">
                    <a href="profil.php" class="nav-link">
                        <i class="fas fa-user" style="color:#82ae46;"></i> <?= htmlspecialchars(frontUser()['nom'] ?? 'Profil') ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="logout.php" class="nav-link text-danger">Déconnexion</a>
                </li>
                <?php else: ?>
                <li class="nav-item">
                    <a href="login.php" class="nav-link btn-vege px-3 ml-2" style="border-radius:20px;">Connexion</a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a href="../../View/BackOffice/dashboard.php" class="nav-link" style="color:#aaa;font-size:.82rem;">Admin</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<section class="hero-wrap hero-bread" style="background-image:url('<?= $ASSETS ?>/images/<?= htmlspecialchars($heroBg) ?>');" data-stellar-background-ratio="0.5">
    <div class="overlay"></div>
    <div class="container">
        <div class="row no-gutters slider-text align-items-center justify-content-center">
            <div class="col-md-9 text-center">
                <?php if (isset($breadcrumb)): ?><p class="breadcrumbs"><?= $breadcrumb ?></p><?php endif; ?>
                <h1 class="mb-0 bread"><?= htmlspecialchars($heroTitle) ?></h1>
            </div>
        </div>
    </div>
</section>

<?php $flash = getFlash(); if ($flash): ?>
<div class="container mt-3">
    <div class="alert alert-<?= $flash['type']==='success'?'success':'warning' ?> alert-dismissible">
        <?= htmlspecialchars($flash['msg']) ?>
        <button class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
</div>
<?php endif; ?>

<section class="ftco-section">
<div class="container">
