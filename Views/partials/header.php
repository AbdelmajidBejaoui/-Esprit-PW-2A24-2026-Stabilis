<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $title ?? 'Stabilis™ - Gestion Sport Nutrition'; ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="/AdminLTE3/assets/css/stabilis.css?v=1">
    <link rel="stylesheet" href="/AdminLTE3/assets/css/back-style.css?v=1">
</head>
<body>

<div class="sidebar">
    <div class="sidebar-header">
        <h3>Stabilis<sup>™</sup></h3>
        <div class="tagline">nutrition adaptative · durable</div>
    </div>
    <ul class="sidebar-nav">
        <li><a href="/AdminLTE3/index.php"><i class="fas fa-bolt"></i> <span>Dashboard</span></a></li>
        <li><a href="/AdminLTE3/Views/back/produits/liste.php"><i class="fas fa-box"></i> <span>Produits</span></a></li>
        <li><a href="/AdminLTE3/Views/back/produits/ajout.php"><i class="fas fa-plus"></i> <span>Nouveau produit</span></a></li>
        <li><a href="/AdminLTE3/Views/back/commandes/liste.php"><i class="fas fa-receipt"></i> <span>Commandes</span></a></li>
        <li><a href="/AdminLTE3/Views/front/index.php"><i class="fas fa-store"></i> <span>Front Office</span></a></li>
    </ul>
    <div class="sidebar-footer">
        <i class="fas fa-seedling"></i> low carbon · high performance
    </div>
</div>

<div class="main-content">