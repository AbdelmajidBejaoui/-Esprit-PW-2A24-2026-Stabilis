<?php
if (!isset($pageTitle)) {
    $pageTitle = 'BackOffice';
}
if (!isset($activePage)) {
    $activePage = 'list';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($pageTitle); ?> | NutriSmart Admin</title>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="listUsers.php" class="nav-link">Gestion Utilisateurs</a>
            </li>
        </ul>
    </nav>

    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="listUsers.php" class="brand-link">
            <span class="brand-text font-weight-light ml-2">NutriSmart Admin</span>
        </a>

        <div class="sidebar">
            <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                <div class="info">
                    <span class="d-block text-white">Administrateur</span>
                </div>
            </div>

            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                    <li class="nav-item">
                        <a href="listUsers.php" class="nav-link <?php echo $activePage === 'list' ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-users"></i>
                            <p>Liste des utilisateurs</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="addUser.php" class="nav-link <?php echo $activePage === 'add' ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-user-plus"></i>
                            <p>Ajouter utilisateur</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="statistics.php" class="nav-link <?php echo $activePage === 'statistics' ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-chart-bar"></i>
                            <p>Statistiques</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="../FrontOffice/listUsers.php" class="nav-link">
                            <i class="nav-icon fas fa-globe"></i>
                            <p>Voir FrontOffice</p>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </aside>

    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
