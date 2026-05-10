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

$isLoggedIn = isset($_SESSION['front_user_id']);
$loggedUserName = $_SESSION['front_user_nom'] ?? 'Athlete';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?php echo htmlspecialchars($pageTitle); ?> | Stabilis</title>

    <link href="https://fonts.googleapis.com/css?family=Poppins:200,300,400,500,600,700,800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Lora:400,400i,700,700i&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Amatic+SC:400,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        :root {
            --stb-green: #82ae46;
            --stb-green-dark: #6f953b;
            --stb-mint: #e9f5e5;
            --stb-ink: #1f2a1f;
        }
        body {
            font-family: 'Poppins', Arial, sans-serif;
            color: var(--stb-ink);
            background: #f8fbf6;
        }
        .top-banner {
            background: var(--stb-green);
            color: #fff;
            font-size: 13px;
            padding: 8px 0;
        }
        .navbar-vege {
            background: #ffffff;
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.07);
        }
        .navbar-vege .navbar-brand {
            color: var(--stb-green);
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .brand-logo {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            background: var(--stb-mint);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 8px;
            box-shadow: 0 6px 16px rgba(130, 174, 70, 0.25);
        }
        .brand-logo img {
            width: 24px;
            height: 24px;
        }
        .navbar-vege .nav-link {
            color: #222 !important;
            font-size: 14px;
            font-weight: 500;
        }
        .navbar-vege .nav-item.active .nav-link {
            color: var(--stb-green) !important;
        }
        .hero-bread {
            background: linear-gradient(rgba(0,0,0,0.35), rgba(0,0,0,0.35)),
                        url('https://images.unsplash.com/photo-1518611012118-696072aa579a?auto=format&fit=crop&w=1400&q=80') center/cover;
            padding: 110px 0;
            color: #fff;
            text-align: center;
        }
        .hero-bread .bread {
            font-family: 'Amatic SC', cursive;
            font-size: 64px;
            line-height: 1;
            margin: 0;
        }
        .hero-bread p {
            letter-spacing: 2px;
            text-transform: uppercase;
            font-size: 12px;
            margin-bottom: 10px;
        }
        .ftco-section {
            padding: 60px 0;
        }
        .card-vege {
            border: 0;
            border-radius: 18px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }
        .card-vege .card-header {
            background: #fff;
            border-bottom: 1px solid #edf2e7;
            font-weight: 600;
        }
        .thead-vege {
            background: var(--stb-green);
            color: #fff;
        }
        .btn-vege {
            background: var(--stb-green);
            border-color: var(--stb-green);
            color: #fff;
            border-radius: 999px;
            padding-left: 18px;
            padding-right: 18px;
        }
        .btn-vege:hover {
            background: var(--stb-green-dark);
            border-color: var(--stb-green-dark);
            color: #fff;
        }
        .pill-role {
            background: var(--stb-mint);
            color: var(--stb-green);
            border-radius: 30px;
            padding: 4px 10px;
            font-size: 12px;
            font-weight: 600;
        }
        .site-footer {
            background: #fff;
            border-top: 1px solid #ececec;
            color: #666;
            padding: 24px 0;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="top-banner">
        <div class="container d-flex justify-content-between">
            <span><i class="fa-solid fa-dumbbell mr-1"></i> Stabilis for Athletes</span>
            <?php if ($isLoggedIn): ?>
                <span><i class="fa-solid fa-user mr-1"></i> Bonjour <?php echo htmlspecialchars($loggedUserName); ?></span>
            <?php else: ?>
                <span><i class="fa-solid fa-leaf mr-1"></i> Nutrition personnalisee et objectifs sportifs</span>
            <?php endif; ?>
        </div>
    </div>

    <nav class="navbar navbar-expand-lg navbar-light navbar-vege">
        <div class="container">
            <a class="navbar-brand" href="listUsers.php">
                <span class="brand-logo"><img src="/Projet/assets/logo-stabilis.svg" alt="Stabilis"></span>
                Stabilis
            </a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item <?php echo $activePage === 'home' ? 'active' : ''; ?>">
                        <a class="nav-link" href="listUsers.php">Accueil</a>
                    </li>
                    <?php if ($isLoggedIn): ?>
                        <li class="nav-item <?php echo $activePage === 'profile' ? 'active' : ''; ?>">
                            <a class="nav-link" href="updateUser.php">Mon profil</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">Deconnexion</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item <?php echo $activePage === 'login' ? 'active' : ''; ?>">
                            <a class="nav-link" href="login.php">Connexion</a>
                        </li>
                        <li class="nav-item <?php echo $activePage === 'signup' ? 'active' : ''; ?>">
                            <a class="nav-link" href="addUser.php">Inscription</a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="../BackOffice/listUsers.php">BackOffice</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <section class="hero-bread">
        <div class="container">
            <p><?php echo htmlspecialchars($pageTitle); ?></p>
            <h1 class="bread"><?php echo htmlspecialchars($heroTitle); ?></h1>
            <h5 class="mt-2 mb-0 text-white-50"><?php echo htmlspecialchars($heroSubtitle); ?></h5>
        </div>
    </section>

    <section class="ftco-section">
        <div class="container">
