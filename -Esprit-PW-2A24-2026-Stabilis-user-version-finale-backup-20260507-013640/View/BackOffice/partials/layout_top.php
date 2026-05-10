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
    <title><?php echo htmlspecialchars($pageTitle); ?> | Stabilis Admin</title>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <style>
        :root {
            --stb-green: #82ae46;
            --stb-green-dark: #6f953b;
            --stb-mint: #e9f5e5;
            --stb-ink: #1f2a1f;
        }
        .main-header.navbar {
            background: #fff !important;
            border-bottom: 1px solid #e8efe3;
        }
        .main-sidebar {
            background: #2f4a2a !important;
        }
        .brand-link {
            display: flex;
            align-items: center;
            background: #263b22 !important;
        }
        .brand-link .brand-text {
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .brand-logo {
            width: 36px;
            height: 36px;
            border-radius: 12px;
            background: var(--stb-mint);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 8px;
        }
        .brand-logo img {
            width: 24px;
            height: 24px;
        }
        .nav-sidebar .nav-link {
            border-radius: 12px;
            margin: 4px 10px;
        }
        .nav-sidebar .nav-link.active {
            background: var(--stb-green) !important;
            color: #fff !important;
            box-shadow: 0 10px 24px rgba(130, 174, 70, 0.3);
        }
                .card-primary:not(.card-outline) > .card-header {
                    background: var(--stb-green) !important;
                    border-color: var(--stb-green) !important;
                    color: #fff;
                }
                .card-primary.card-outline {
                    border-top: 3px solid var(--stb-green) !important;
                }
        .nav-sidebar .nav-link:hover {
            background: rgba(130, 174, 70, 0.18) !important;
            color: #fff !important;
        }
        .content-wrapper {
            background: #f7fbf4;
        }
        .card {
            border-radius: 18px;
            box-shadow: 0 12px 28px rgba(20, 30, 20, 0.08);
        }
        .card-header {
            border-top-left-radius: 18px;
            border-top-right-radius: 18px;
        }
        .btn-primary,
        .btn-success {
            background: var(--stb-green) !important;
            border-color: var(--stb-green) !important;
            border-radius: 999px;
        }
        .btn-primary:hover,
        .btn-success:hover {
            background: var(--stb-green-dark) !important;
            border-color: var(--stb-green-dark) !important;
        }
        .badge-success {
            background: var(--stb-green) !important;
        }
        .main-footer {
            background: #fff;
            border-top: 1px solid #e8efe3;
        }
    </style>
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
            <span class="brand-logo"><img src="/Projet/assets/logo-stabilis.svg" alt="Stabilis"></span>
            <span class="brand-text font-weight-light">Stabilis Admin</span>
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
