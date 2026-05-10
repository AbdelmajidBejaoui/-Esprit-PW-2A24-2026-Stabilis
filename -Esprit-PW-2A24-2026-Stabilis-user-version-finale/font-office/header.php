<?php
if (!isset($page_title)) $page_title = "Accueil";
$site_name = "Stabilis";
$site_tagline = "Nutrition durable · Performance intelligente";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <title><?php echo $page_title; ?> — <?php echo $site_name; ?>™</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Plateforme de nutrition durable pour une alimentation performante et respectueuse de l'environnement">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- AOS Animations -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <!-- Custom Styles -->
    <link rel="stylesheet" href="assets/css/front-style.css">

    <!-- Preload -->
    <link rel="preload" href="assets/css/front-style.css" as="style">
</head>
<body>

<!-- Clean Navigation -->
<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom sticky-top">
    <div class="container px-4">
        <!-- Logo -->
        <a class="navbar-brand d-flex align-items-center" href="../front-office.php">
            <i class="fas fa-leaf text-success fa-2x me-2"></i>
            <span class="fw-bold fs-5 text-dark"><?php echo $site_name; ?></span>
        </a>

        <!-- Mobile Toggle -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navigation Links -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="../front-office.php">
                        Accueil
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'challenges.php' ? 'active fw-semibold' : ''; ?>" href="challenges.php">
                        Défis
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'my-challenges.php' ? 'active fw-semibold' : ''; ?>" href="my-challenges.php">
                        Mes défis
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'about.php' ? 'active fw-semibold' : ''; ?>" href="about.php">
                        À propos
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'contact.php' ? 'active fw-semibold' : ''; ?>" href="contact.php">
                        Contact
                    </a>
                </li>
                <li class="nav-item ms-lg-3 mt-2 mt-lg-0">
                    <a class="btn btn-outline-success btn-sm px-3" href="weekly-recap.html" title="Voir le classement">
                        <i class="fas fa-trophy me-1"></i>Classement
                    </a>
                </li>
                <li class="nav-item ms-lg-2 mt-2 mt-lg-0">
                    <a class="btn btn-outline-secondary btn-sm px-3" href="../View/FrontOffice/listUsers.php" title="Acceder a l'espace utilisateurs">
                        <i class="fas fa-user me-1"></i>Utilisateurs
                    </a>
                </li>
                <li class="nav-item ms-lg-2 mt-2 mt-lg-0">
                    <a class="btn btn-outline-primary btn-sm px-3" href="../admin.php" title="Accéder au back office">
                        <i class="fas fa-lock me-1"></i>Admin
                    </a>
                </li>
                <li class="nav-item ms-lg-2 mt-2 mt-lg-0">
                    <a class="btn btn-primary btn-sm px-4" href="challenges.php">
                        Commencer
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<main class="main-content">
