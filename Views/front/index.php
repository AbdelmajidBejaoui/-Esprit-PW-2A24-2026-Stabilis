<?php
session_start();
require_once __DIR__ . '/../../controllers/ProduitController.php';
require_once __DIR__ . '/../../Controllers/EventController.php';

$controller = new ProduitController();
$eventController = new EventController();
$produits = $controller->getAll();
$featured = array_slice($produits, 0, 4);
$activeEvent = $eventController->getActive();
$cartCount = array_sum($_SESSION['cart'] ?? []);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Stabilis&trade; - Boutique Sport Nutrition</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/stabilis.css?v=1">
    <link rel="stylesheet" href="../../assets/css/front-style.css?v=7">
    <link rel="stylesheet" href="../../assets/css/front-pages.css?v=2">
</head>
<body class="page-home">
    <div class="top-bar">
        <div class="container">
            <div class="top-bar-item"><i class="fas fa-envelope"></i><span>stabilisatyourservice@gmail.com</span></div>
            <div class="top-bar-item"><i class="fas fa-truck"></i><span>Livraison sous 3-5 jours</span></div>
        </div>
    </div>
    <nav class="navbar">
        <div class="container">
            <a href="index.php" class="navbar-brand">Stabilis<sup>&trade;</sup></a>
            <ul class="navbar-nav">
                <li><a href="index.php">Accueil</a></li>
                <li><a href="shop.php">Boutique</a></li>
                <li><a href="cart.php"><i class="fas fa-shopping-cart"></i><?php if($cartCount > 0): ?><span class="cart-badge"><span class="cart-count"><?php echo $cartCount; ?></span></span><?php endif; ?></a></li>
                <li><a href="../../Views/back/produits/liste.php" class="admin-link"><i class="fas fa-cog"></i> Administration</a></li>
            </ul>
        </div>
    </nav>
    <?php if ($activeEvent): ?>
    <?php
        $eventLink = 'index.php';
        $eventCode = trim($activeEvent['code_promo'] ?? '');
        $eventBg = preg_match('/^#[0-9A-Fa-f]{6}$/', $activeEvent['bg_color'] ?? '') ? $activeEvent['bg_color'] : '#F9F3E6';
        $r = hexdec(substr($eventBg, 1, 2));
        $g = hexdec(substr($eventBg, 3, 2));
        $b = hexdec(substr($eventBg, 5, 2));
        $eventText = (($r * 299 + $g * 587 + $b * 114) / 1000) < 140 ? '#FFFFFF' : '#1A4D3A';
        $eventPillBg = $eventText === '#FFFFFF' ? 'rgba(255,255,255,0.18)' : '#1A4D3A';
        $eventPillText = '#FFFFFF';
    ?>
    <a href="<?php echo htmlspecialchars($eventLink); ?>" class="event-marquee" style="--event-bg: <?php echo htmlspecialchars($eventBg); ?>; --event-text: <?php echo $eventText; ?>; --event-pill-bg: <?php echo $eventPillBg; ?>; --event-pill-text: <?php echo $eventPillText; ?>;">
        <div class="event-marquee-track">
            <span><i class="fas fa-bullhorn"></i> <?php echo htmlspecialchars($activeEvent['titre']); ?></span>
            <span><?php echo htmlspecialchars($activeEvent['message']); ?></span>
            <?php if ($eventCode !== ''): ?><strong>Code: <?php echo htmlspecialchars($eventCode); ?></strong><?php endif; ?>
            <span><i class="fas fa-bullhorn"></i> <?php echo htmlspecialchars($activeEvent['titre']); ?></span>
            <span><?php echo htmlspecialchars($activeEvent['message']); ?></span>
            <?php if ($eventCode !== ''): ?><strong>Code: <?php echo htmlspecialchars($eventCode); ?></strong><?php endif; ?>
        </div>
    </a>
    <?php endif; ?>
    <section class="hero-section" data-shimmer>
        <div class="bubble" data-float="slow"></div>
        <div class="bubble" data-float></div>
        <div class="bubble" data-float="fast"></div>
        <div class="container">
            <h1 data-reveal="blur">Nutrition adaptative</h1>
            <p data-reveal="up">Performance durable &middot; Solutions intelligentes</p>
            <a href="shop.php" class="btn-primary u-hero-cta" data-reveal="zoom">
                <i class="fas fa-shopping-bag"></i>
                D&eacute;couvrir nos produits
            </a>
        </div>
    </section>
    <div class="container u-section-pad">
        <div data-reveal="up" class="u-section-header">
            <h2 class="u-section-title">Produits phares</h2>
            <p class="u-section-subtitle">Notre s&eacute;lection de compl&eacute;ments nutritionnels premium</p>
        </div>
        <div class="product-grid" data-stagger-children>
            <?php foreach ($featured as $produit): ?>
<div class="product-card" data-reveal="zoom">
                <img src="/AdminLTE3/dist/img/<?php echo htmlspecialchars($produit['image_url'] ?? 'default-product.png'); ?>" alt="<?php echo htmlspecialchars($produit['nom']); ?>" class="product-image" onerror="this.src='/AdminLTE3/dist/img/default-product.png'">
                <div class="product-content">
                    <div class="product-category"><?php echo htmlspecialchars($produit['categorie']); ?></div>
                    <h3 class="product-title"><?php echo htmlspecialchars($produit['nom']); ?></h3>
                    <?php $effectivePrice = $controller->getEffectivePrice($produit); ?>
                    <div class="product-price">
                        <?php if ($controller->hasProductPromotion($produit)): ?>
                            <span style="text-decoration: line-through; color: var(--text-muted); font-size: 14px; margin-right: 8px;"><?php echo number_format((float) $produit['prix'], 2); ?> &euro;</span>
                            <span style="color: #1b5e20;"><?php echo number_format($effectivePrice, 2); ?> &euro;</span>
                        <?php else: ?>
                            <?php echo number_format($effectivePrice, 2); ?> &euro;
                        <?php endif; ?>
                    </div>
                    <div class="product-actions">
                        <a href="product.php?id=<?php echo (int) $produit['id']; ?>" class="btn-view"><i class="fas fa-eye"></i> Voir</a>
                        <a href="cart.php?action=add&id=<?php echo (int) $produit['id']; ?>" class="btn-cart"><i class="fas fa-cart-plus"></i> Ajouter</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div data-reveal="up" class="u-section-footer">
            <a href="shop.php" class="btn-primary u-section-footer-cta"><i class="fas fa-th-large"></i> Voir tous les produits</a>
        </div>
    </div>
    <footer class="u-footer">
        <div class="container">
            <div data-reveal="up" class="u-center">
                <h3 class="u-footer-title">Stabilis<sup>&trade;</sup></h3>
                <p class="u-footer-subtitle">Nutrition adaptative &middot; Performance durable</p>
                <div class="u-footer-meta"><i class="fas fa-seedling"></i> low carbon &middot; high performance</div>
            </div>
        </div>
    </footer>
    <style>
    .event-marquee {
        display:block;
        overflow:hidden;
        background:var(--event-bg);
        color:var(--event-text);
        border-top:1px solid rgba(0,0,0,0.08);
        border-bottom:1px solid rgba(0,0,0,0.08);
        text-decoration:none;
        font-weight:800;
        white-space:nowrap;
    }
    .event-marquee-track {
        display:inline-flex;
        align-items:center;
        gap:34px;
        min-width:max-content;
        padding:10px 0;
        animation:eventMarquee 22s linear infinite;
    }
    .event-marquee:hover .event-marquee-track { animation-play-state:paused; }
    .event-marquee strong {
        background:var(--event-pill-bg);
        color:var(--event-pill-text);
        border-radius:999px;
        padding:5px 12px;
    }
    @keyframes eventMarquee {
        from { transform:translateX(0); }
        to { transform:translateX(-50%); }
    }
    </style>
    <script src="../../assets/js/front-animations.js"></script>
</body>
</html>

