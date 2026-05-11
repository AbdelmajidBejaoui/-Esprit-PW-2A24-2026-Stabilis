<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($pageTitle)) $pageTitle = 'Admin';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> | FitTrack Admin</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../../public/adminlte/dist/css/adminlte.min.css">
    <style>
        .badge-debutant{background:#28a745!important;}.badge-intermediaire{background:#ffc107!important;color:#000!important;}
        .badge-avance{background:#dc3545!important;}.error-list{color:#dc3545;font-size:.85em;}
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
<nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <ul class="navbar-nav">
        <li class="nav-item"><a class="nav-link" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a></li>
        <li class="nav-item d-none d-sm-inline-block"><a href="dashboard.php" class="nav-link">Dashboard</a></li>
    </ul>
    <ul class="navbar-nav ml-auto">
        <li class="nav-item"><a href="../FrontOffice/catalogue.php" class="nav-link"><i class="fas fa-globe"></i> Front Office</a></li>
    </ul>
</nav>
<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="dashboard.php" class="brand-link"><span class="brand-text font-weight-light"><strong>🏋️ FitTrack</strong> Admin</span></a>
    <div class="sidebar"><nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
            <li class="nav-item"><a href="dashboard.php" class="nav-link <?= ($activePage??'')==='dashboard'?'active':'' ?>"><i class="nav-icon fas fa-tachometer-alt"></i><p>Dashboard</p></a></li>
            <li class="nav-item"><a href="listUsers.php" class="nav-link <?= ($activePage??'')==='users'?'active':'' ?>"><i class="nav-icon fas fa-users"></i><p>Utilisateurs</p></a></li>
            <li class="nav-item"><a href="listSeances.php" class="nav-link <?= ($activePage??'')==='seances'?'active':'' ?>"><i class="nav-icon fas fa-fire"></i><p>Séances</p></a></li>
            <li class="nav-header">MODULE ENTRAÎNEMENT</li>
            <li class="nav-item"><a href="listEntrainements.php" class="nav-link <?= ($activePage??'')==='entrainements'?'active':'' ?>"><i class="nav-icon fas fa-running"></i><p>Entraînements</p></a></li>
            <li class="nav-item"><a href="ai_history.php" class="nav-link <?= ($activePage??'')==='ai_history'?'active':'' ?>"><i class="nav-icon fas fa-robot"></i><p>Historique IA</p></a></li>
        </ul>
    </nav></div>
</aside>
<div class="content-wrapper">
<div class="content-header"><div class="container-fluid">
    <div class="row mb-2">
        <div class="col-sm-6"><h1 class="m-0"><?= htmlspecialchars($pageTitle) ?></h1></div>
        <?php if(isset($breadcrumb)): ?>
        <div class="col-sm-6"><ol class="breadcrumb float-sm-right"><?= $breadcrumb ?></ol></div>
        <?php endif; ?>
    </div>
</div></div>
<section class="content"><div class="container-fluid">
<?php
$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
if ($flash): ?>
<div class="alert alert-<?= $flash['type']==='success'?'success':'warning' ?> alert-dismissible">
    <?= htmlspecialchars($flash['msg']) ?><button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
</div>
<?php endif; ?>
