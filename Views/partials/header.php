<?php
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';

function sidebar_is_active($paths) {
    global $currentPath;
    foreach ((array)$paths as $path) {
        if ($currentPath === $path || strpos($currentPath, $path) !== false) {
            return true;
        }
    }
    return false;
}

$dashboardOpen = sidebar_is_active([
    '/AdminLTE3/Views/back/dashboard.php',
    '/AdminLTE3/Views/back/dashboard-defis.php',
    '/AdminLTE3/Views/back/users/statistics.php'
]);
$commandesOpen = sidebar_is_active([
    '/AdminLTE3/Views/back/commandes/liste.php',
    '/AdminLTE3/Views/back/commandes/voir.php',
    '/AdminLTE3/Views/back/commandes/preorders.php',
    '/AdminLTE3/Views/back/commandes/promo-code.php'
]);
$produitsOpen = sidebar_is_active([
    '/AdminLTE3/Views/back/produits/liste.php',
    '/AdminLTE3/Views/back/produits/ajout.php',
    '/AdminLTE3/Views/back/produits/modifier.php',
    '/AdminLTE3/Views/back/packs/ajout.php'
]);
$defisOpen = sidebar_is_active([
    '/AdminLTE3/Views/back/defis/',
    '/AdminLTE3/Views/back/participations/'
]);
$eventsActive = sidebar_is_active('/AdminLTE3/Views/back/evenements/index.php');
$usersActive = sidebar_is_active('/AdminLTE3/Views/back/users/');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $title ?? 'Stabilis&trade; - Gestion Sport Nutrition'; ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="/AdminLTE3/assets/css/stabilis.css?v=6">
    <link rel="stylesheet" href="/AdminLTE3/assets/css/back-style.css?v=4">
</head>
<body>

<div class="sidebar">
    <div class="sidebar-header">
        <h3>Stabilis<sup>&trade;</sup></h3>
        <div class="tagline">nutrition adaptative &middot; durable</div>
    </div>

    <ul class="sidebar-nav">
        <li class="sidebar-group <?php echo $dashboardOpen ? 'is-open' : ''; ?>" data-sidebar-group="dashboard">
            <button type="button" class="sidebar-group-toggle <?php echo $dashboardOpen ? 'active' : ''; ?>" aria-expanded="<?php echo $dashboardOpen ? 'true' : 'false'; ?>">
                <span><i class="fas fa-chart-line"></i> <span>Dashboard</span></span>
                <i class="fas fa-chevron-down sidebar-chevron"></i>
            </button>
            <ul class="sidebar-subnav">
                <li><a href="/AdminLTE3/Views/back/dashboard.php" class="<?php echo sidebar_is_active('/AdminLTE3/Views/back/dashboard.php') ? 'active' : ''; ?>"><i class="fas fa-store"></i> <span>E-commerce</span></a></li>
                <li><a href="/AdminLTE3/Views/back/dashboard-defis.php" class="<?php echo sidebar_is_active('/AdminLTE3/Views/back/dashboard-defis.php') ? 'active' : ''; ?>"><i class="fas fa-trophy"></i> <span>Defis</span></a></li>
                <li><a href="/AdminLTE3/Views/back/users/statistics.php" class="<?php echo sidebar_is_active('/AdminLTE3/Views/back/users/statistics.php') ? 'active' : ''; ?>"><i class="fas fa-users"></i> <span>Utilisateurs</span></a></li>
            </ul>
        </li>

        <li class="sidebar-group <?php echo $commandesOpen ? 'is-open' : ''; ?>" data-sidebar-group="commandes">
            <button type="button" class="sidebar-group-toggle <?php echo $commandesOpen ? 'active' : ''; ?>" aria-expanded="<?php echo $commandesOpen ? 'true' : 'false'; ?>">
                <span><i class="fas fa-receipt"></i> <span>Commandes</span></span>
                <i class="fas fa-chevron-down sidebar-chevron"></i>
            </button>
            <ul class="sidebar-subnav">
                <li><a href="/AdminLTE3/Views/back/commandes/liste.php" class="<?php echo sidebar_is_active(['/AdminLTE3/Views/back/commandes/liste.php', '/AdminLTE3/Views/back/commandes/voir.php']) ? 'active' : ''; ?>"><i class="fas fa-list"></i> <span>Toutes les commandes</span></a></li>
                <li><a href="/AdminLTE3/Views/back/commandes/preorders.php" class="<?php echo sidebar_is_active('/AdminLTE3/Views/back/commandes/preorders.php') ? 'active' : ''; ?>"><i class="fas fa-clock"></i> <span>Pre-commandes</span></a></li>
                <li><a href="/AdminLTE3/Views/back/commandes/promo-code.php" class="<?php echo sidebar_is_active('/AdminLTE3/Views/back/commandes/promo-code.php') ? 'active' : ''; ?>"><i class="fas fa-tags"></i> <span>Codes promo</span></a></li>
            </ul>
        </li>

        <li class="sidebar-group <?php echo $produitsOpen ? 'is-open' : ''; ?>" data-sidebar-group="produits">
            <button type="button" class="sidebar-group-toggle <?php echo $produitsOpen ? 'active' : ''; ?>" aria-expanded="<?php echo $produitsOpen ? 'true' : 'false'; ?>">
                <span><i class="fas fa-box-open"></i> <span>Produits</span></span>
                <i class="fas fa-chevron-down sidebar-chevron"></i>
            </button>
            <ul class="sidebar-subnav">
                <li><a href="/AdminLTE3/Views/back/produits/liste.php" class="<?php echo sidebar_is_active(['/AdminLTE3/Views/back/produits/liste.php', '/AdminLTE3/Views/back/produits/modifier.php']) ? 'active' : ''; ?>"><i class="fas fa-box"></i> <span>Catalogue</span></a></li>
                <li><a href="/AdminLTE3/Views/back/produits/ajout.php" class="<?php echo sidebar_is_active('/AdminLTE3/Views/back/produits/ajout.php') ? 'active' : ''; ?>"><i class="fas fa-plus"></i> <span>Nouveau produit</span></a></li>
                <li><a href="/AdminLTE3/Views/back/packs/ajout.php" class="<?php echo sidebar_is_active('/AdminLTE3/Views/back/packs/ajout.php') ? 'active' : ''; ?>"><i class="fas fa-boxes-stacked"></i> <span>Nouveau pack</span></a></li>
            </ul>
        </li>

        <li class="sidebar-group <?php echo $defisOpen ? 'is-open' : ''; ?>" data-sidebar-group="defis">
            <button type="button" class="sidebar-group-toggle <?php echo $defisOpen ? 'active' : ''; ?>" aria-expanded="<?php echo $defisOpen ? 'true' : 'false'; ?>">
                <span><i class="fas fa-trophy"></i> <span>Defis</span></span>
                <i class="fas fa-chevron-down sidebar-chevron"></i>
            </button>
            <ul class="sidebar-subnav">
                <li><a href="/AdminLTE3/Views/back/defis/liste.php" class="<?php echo sidebar_is_active(['/AdminLTE3/Views/back/defis/liste.php', '/AdminLTE3/Views/back/defis/modifier.php', '/AdminLTE3/Views/back/defis/supprimer.php']) ? 'active' : ''; ?>"><i class="fas fa-list"></i> <span>Tous les defis</span></a></li>
                <li><a href="/AdminLTE3/Views/back/defis/ajout.php" class="<?php echo sidebar_is_active('/AdminLTE3/Views/back/defis/ajout.php') ? 'active' : ''; ?>"><i class="fas fa-plus"></i> <span>Nouveau defi</span></a></li>
                <li><a href="/AdminLTE3/Views/back/defis/generer.php" class="<?php echo sidebar_is_active('/AdminLTE3/Views/back/defis/generer.php') ? 'active' : ''; ?>"><i class="fas fa-robot"></i> <span>Generation IA</span></a></li>
                <li><a href="/AdminLTE3/Views/back/defis/recit-ia.php" class="<?php echo sidebar_is_active('/AdminLTE3/Views/back/defis/recit-ia.php') ? 'active' : ''; ?>"><i class="fas fa-feather-pointed"></i> <span>Recit IA</span></a></li>
                <li><a href="/AdminLTE3/Views/back/participations/liste.php" class="<?php echo sidebar_is_active('/AdminLTE3/Views/back/participations/') ? 'active' : ''; ?>"><i class="fas fa-users"></i> <span>Participations</span></a></li>
            </ul>
        </li>

        <li>
            <a href="/AdminLTE3/Views/back/evenements/index.php" class="sidebar-link <?php echo $eventsActive ? 'active' : ''; ?>">
                <i class="fas fa-bullhorn"></i> <span>Evenement</span>
            </a>
        </li>

        <li>
            <a href="/AdminLTE3/Views/back/users/listUsers.php" class="sidebar-link <?php echo $usersActive ? 'active' : ''; ?>">
                <i class="fas fa-users"></i> <span>Utilisateurs</span>
            </a>
        </li>

        <li>
            <a href="/AdminLTE3/Views/front/index.php" class="sidebar-link">
                <i class="fas fa-store"></i> <span>Front Office</span>
            </a>
        </li>
    </ul>

    <div class="sidebar-footer">
        <i class="fas fa-seedling"></i> low carbon &middot; high performance
    </div>
</div>

<div class="main-content">
