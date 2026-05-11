<?php
session_start();
require_once __DIR__ . '/../../controllers/ProduitController.php';
require_once __DIR__ . '/../../Controllers/EventController.php';
require_once __DIR__ . '/../../Controllers/DefiController.php';
require_once __DIR__ . '/../../Controllers/NutritionController.php';

$controller = new ProduitController();
$eventController = new EventController();
$defiController = new DefiController();
$nutritionController = new NutritionController();
$nutritionModel = $nutritionController->model();
$produits = $controller->getAll();
$featured = array_slice($produits, 0, 4);
$homeDefis = array_slice($defiController->getAll(), 0, 3);
$homeAliments = $nutritionModel->aliments('', 'proteines_desc');
$homeRecettes = $nutritionModel->recettes('', 'performance_desc');
$activeEvent = $eventController->getActive();
$cartCount = array_sum($_SESSION['cart'] ?? []);

function homeDefiIcon(string $type): string
{
    return ['aliment' => 'fa-apple-alt', 'entrainement' => 'fa-dumbbell', 'compensation' => 'fa-leaf'][$type] ?? 'fa-star';
}
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
    <link rel="stylesheet" href="../../assets/css/front-style.css?v=10">
    <link rel="stylesheet" href="../../assets/css/front-pages.css?v=5">
</head>
<body class="page-home">
    <?php $activeFrontPage = 'home'; require __DIR__ . '/partials/navigation.php'; ?>
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
    <section class="home-hero" data-shimmer>
        <div class="container home-hero-grid">
            <div class="home-hero-copy">
                <span class="home-kicker" data-reveal="up"><i class="fas fa-seedling"></i> Stabilis Defis & Boutique</span>
                <h1 data-reveal="blur">Mangez mieux, relevez des defis, progressez chaque semaine.</h1>
                <p data-reveal="up">Un espace vivant pour decouvrir des defis durables, suivre votre progression et completer votre routine avec nos produits nutrition.</p>
                <div class="home-hero-actions" data-reveal="zoom">
                    <a href="defis/index.php" class="home-btn primary"><i class="fas fa-trophy"></i> Voir les defis</a>
                    <a href="shop.php" class="home-btn secondary"><i class="fas fa-shopping-bag"></i> Boutique</a>
                </div>
            </div>
            <div class="home-hero-media" data-reveal="zoom">
                <img src="/AdminLTE3/assets/img/home/hero-bowl.jpg" alt="Nutrition saine">
                <div class="home-floating-card card-a"><i class="fas fa-leaf"></i><strong>Nutrition durable</strong><span>Objectifs simples</span></div>
                <div class="home-floating-card card-b"><i class="fas fa-coins"></i><strong>Points</strong><span>Classement</span></div>
            </div>
        </div>
    </section>

    <section class="home-defis-preview">
        <div class="container">
            <div class="home-section-head" data-reveal="up">
                <span>Defis Stabilis</span>
                <h2>Commencez par un petit objectif concret.</h2>
                <p>Choisissez un defi, participez, envoyez vos preuves et gagnez des points dans le classement.</p>
            </div>
            <div class="home-defis-layout">
                <div class="home-defis-image" data-reveal="up">
                    <img src="/AdminLTE3/assets/img/home/defis-nutrition.jpg" alt="Repas sain">
                    <a href="defis/index.php" class="home-image-cta"><i class="fas fa-arrow-right"></i> Aller aux defis</a>
                </div>
                <div class="home-defis-list" data-stagger-children>
                    <?php foreach ($homeDefis as $defi): ?>
                    <a class="home-defi-mini" href="defis/detail.php?id=<?php echo (int)$defi['id']; ?>" data-reveal="up">
                        <span><i class="fas <?php echo homeDefiIcon($defi['type']); ?>"></i></span>
                        <div>
                            <strong><?php echo htmlspecialchars($defi['nom']); ?></strong>
                            <small><?php echo htmlspecialchars($defi['recompense']); ?></small>
                        </div>
                    </a>
                    <?php endforeach; ?>
                    <a class="home-defi-more" href="defis/weekly-recap.php" data-reveal="up"><i class="fas fa-ranking-star"></i> Voir le classement</a>
                </div>
            </div>
        </div>
    </section>

    <section class="home-nutrition-hook">
        <div class="container home-nutrition-grid">
            <div class="home-nutrition-copy" data-reveal="up">
                <span>Nutrition</span>
                <h2>Entrez dans votre espace aliments, recettes et outils IA.</h2>
                <p>Un raccourci clair vers la partie nutrition: cherchez un aliment, composez un menu, estimez une assiette et gardez une routine plus precise.</p>
                <div class="home-nutrition-actions">
                    <a href="nutrition/index.php" class="home-btn primary"><i class="fas fa-utensils"></i> Explorer la nutrition</a>
                    <a href="nutrition/index.php#outils-ia" class="home-btn secondary dark"><i class="fas fa-robot"></i> Outils IA</a>
                </div>
            </div>
            <div class="home-nutrition-panel" data-stagger-children>
                <a href="nutrition/index.php#aliments" class="home-nutrition-card" data-reveal="zoom">
                    <i class="fas fa-apple-alt"></i>
                    <strong><?php echo count($homeAliments); ?> aliments</strong>
                    <small>Macros, calories et tri proteines</small>
                </a>
                <a href="nutrition/index.php#recettes" class="home-nutrition-card" data-reveal="zoom">
                    <i class="fas fa-bowl-food"></i>
                    <strong><?php echo count($homeRecettes); ?> recettes</strong>
                    <small>Menus equilibres et score performance</small>
                </a>
                <a href="nutrition/index.php#menu-jour" class="home-nutrition-card wide" data-reveal="zoom">
                    <i class="fas fa-calendar-day"></i>
                    <strong>Menu de jour</strong>
                    <small>Une selection rapide pour commencer sans friction</small>
                </a>
            </div>
        </div>
    </section>

    <section class="home-shop-preview">
        <div class="container">
            <div class="home-section-head" data-reveal="up">
                <span>Boutique</span>
                <h2>Completez votre routine avec nos produits.</h2>
                <p>Une selection courte pour commencer vite, puis tout le catalogue dans la boutique.</p>
            </div>
            <div class="product-grid" data-stagger-children>
                <?php foreach ($featured as $produit): ?>
                <div class="product-card" data-reveal="zoom">
                    <img src="/AdminLTE3/assets/img/<?php echo htmlspecialchars($produit['image_url'] ?? 'default-product.png'); ?>" alt="<?php echo htmlspecialchars($produit['nom']); ?>" class="product-image" onerror="this.src='/AdminLTE3/assets/img/default-product.png'">
                    <div class="product-content">
                        <div class="product-category"><?php echo htmlspecialchars($produit['categorie']); ?></div>
                        <h3 class="product-title"><?php echo htmlspecialchars($produit['nom']); ?></h3>
                        <?php $effectivePrice = $controller->getEffectivePrice($produit); ?>
                        <div class="product-price"><?php echo number_format($effectivePrice, 2); ?> &euro;</div>
                        <div class="product-actions">
                            <a href="product.php?id=<?php echo (int) $produit['id']; ?>" class="btn-view"><i class="fas fa-eye"></i> Voir</a>
                            <a href="cart.php?action=add&id=<?php echo (int) $produit['id']; ?>" class="btn-cart"><i class="fas fa-cart-plus"></i> Ajouter</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div data-reveal="up" class="u-section-footer">
                <a href="shop.php" class="btn-primary u-section-footer-cta"><i class="fas fa-th-large"></i> Voir toute la boutique</a>
            </div>
        </div>
    </section>

    <section class="home-about">
        <div class="container home-about-grid">
            <div class="home-about-copy" data-reveal="up">
                <span>Qui sommes-nous</span>
                <h2>Une communaute autour de la nutrition, du sport et des habitudes durables.</h2>
                <p>Stabilis aide chaque utilisateur a transformer de petites actions quotidiennes en progression visible: defis, preuves, points, classement et produits adaptes a une routine plus saine.</p>
                <p>Notre objectif est simple: rendre le bien-etre plus motivant, plus mesurable et plus responsable.</p>
            </div>
            <div class="home-about-image" data-reveal="zoom">
                <img src="/AdminLTE3/assets/img/home/defis-sport.jpg" alt="Sport et bien-etre">
            </div>
        </div>
    </section>
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
    .home-hero {
        position:relative;
        overflow:hidden;
        padding:86px 0 74px;
        background:
            linear-gradient(90deg, rgba(18,56,38,.92), rgba(18,56,38,.58)),
            url('/AdminLTE3/assets/img/home/defis-nutrition.jpg');
        background-size:cover;
        background-position:center;
        color:#fff;
    }
    .home-hero-grid { display:grid; grid-template-columns:1fr .88fr; gap:48px; align-items:center; }
    .home-kicker { display:inline-flex; gap:8px; align-items:center; padding:8px 12px; border-radius:999px; background:rgba(255,255,255,.15); border:1px solid rgba(255,255,255,.22); font-weight:800; }
    .home-hero h1 { max-width:760px; margin:18px 0 16px; color:#fff; font-size:clamp(42px,7vw,78px); line-height:.96; font-weight:900; }
    .home-hero p { max-width:620px; color:rgba(255,255,255,.82); font-size:18px; line-height:1.65; }
    .home-hero-actions { display:flex; gap:12px; flex-wrap:wrap; margin-top:26px; }
    .home-btn { min-height:48px; display:inline-flex; align-items:center; justify-content:center; gap:9px; padding:0 20px; border-radius:999px; font-weight:900; text-decoration:none; }
    .home-btn.primary { background:#fff; color:#1a4d3a; }
    .home-btn.secondary { background:rgba(255,255,255,.14); color:#fff; border:1px solid rgba(255,255,255,.25); }
    .home-hero-media { position:relative; min-height:520px; }
    .home-hero-media img { width:100%; height:520px; object-fit:cover; border-radius:28px; box-shadow:0 28px 70px rgba(0,0,0,.32); }
    .home-floating-card { position:absolute; display:grid; gap:3px; min-width:170px; padding:15px; border-radius:18px; background:rgba(255,255,255,.92); color:#1a4d3a; box-shadow:0 18px 40px rgba(0,0,0,.18); animation:homeFloat 4s ease-in-out infinite; }
    .home-floating-card i { font-size:22px; color:#129f72; }
    .home-floating-card span { color:#6e756f; font-size:12px; font-weight:700; }
    .home-floating-card.card-a { left:-24px; top:48px; }
    .home-floating-card.card-b { right:-18px; bottom:64px; animation-delay:1s; }
    @keyframes homeFloat { 50% { transform:translateY(-12px); } }
    .home-defis-preview, .home-nutrition-hook, .home-shop-preview, .home-about { padding:72px 0; }
    .home-defis-preview { background:#fff; }
    .home-nutrition-hook { position:relative; overflow:hidden; background:#f2f7f1; }
    .home-nutrition-hook::before { content:""; position:absolute; inset:0; background:linear-gradient(90deg, rgba(242,247,241,.98), rgba(242,247,241,.82)), url('/AdminLTE3/assets/img/home/hero-bowl.jpg'); background-size:cover; background-position:center; }
    .home-nutrition-grid { position:relative; display:grid; grid-template-columns:.95fr 1.05fr; gap:30px; align-items:center; }
    .home-nutrition-copy span { display:inline-flex; color:#129f72; font-size:13px; font-weight:900; text-transform:uppercase; letter-spacing:.5px; margin-bottom:10px; }
    .home-nutrition-copy h2 { margin:0 0 14px; color:#183d2f; font-size:clamp(30px,4vw,52px); line-height:1.08; font-weight:900; }
    .home-nutrition-copy p { margin:0; max-width:620px; color:#56635a; font-size:17px; line-height:1.65; }
    .home-nutrition-actions { display:flex; gap:12px; flex-wrap:wrap; margin-top:24px; }
    .home-btn.secondary.dark { color:#1a4d3a; background:#fff; border:1px solid #dbe9de; box-shadow:0 12px 26px rgba(35,56,41,.08); }
    .home-nutrition-panel { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:16px; }
    .home-nutrition-card { min-height:160px; display:flex; flex-direction:column; justify-content:flex-end; gap:8px; padding:22px; border-radius:22px; text-decoration:none; background:rgba(255,255,255,.9); border:1px solid rgba(210,228,215,.9); color:#1a4d3a; box-shadow:0 18px 40px rgba(35,56,41,.10); transition:transform .18s ease, box-shadow .18s ease, border-color .18s ease; }
    .home-nutrition-card:hover { transform:translateY(-6px); box-shadow:0 24px 48px rgba(35,56,41,.16); border-color:#bcdcc6; }
    .home-nutrition-card i { width:48px; height:48px; display:inline-flex; align-items:center; justify-content:center; border-radius:16px; background:#e7f4ea; color:#129f72; font-size:22px; }
    .home-nutrition-card strong { color:#17291f; font-size:22px; line-height:1.15; }
    .home-nutrition-card small { color:#657166; font-weight:750; line-height:1.45; }
    .home-nutrition-card.wide { grid-column:1 / -1; min-height:145px; background:#1f6f51; color:#fff; border:0; }
    .home-nutrition-card.wide i { background:rgba(255,255,255,.16); color:#fff; }
    .home-nutrition-card.wide strong, .home-nutrition-card.wide small { color:#fff; }
    .home-shop-preview { background:#fff; }
    .home-section-head { max-width:760px; margin-bottom:30px; }
    .home-section-head.center { max-width:900px; margin:0 auto 44px; text-align:center; }
    .home-section-head span { display:inline-flex; color:#129f72; font-size:13px; font-weight:900; text-transform:uppercase; letter-spacing:.5px; margin-bottom:9px; }
    .home-section-head h2 { margin:0 0 10px; font-size:clamp(30px,4vw,52px); line-height:1.08; font-weight:900; }
    .home-section-head h2 em { color:#1769ff; font-style:normal; }
    .home-section-head p { margin:0; color:#5f675f; font-size:17px; line-height:1.6; }
    .home-defis-layout { display:grid; grid-template-columns:1.08fr .92fr; gap:28px; align-items:stretch; }
    .home-defis-image { position:relative; min-height:390px; border-radius:24px; overflow:hidden; box-shadow:0 22px 46px rgba(35,56,41,.14); }
    .home-defis-image img { width:100%; height:100%; object-fit:cover; display:block; }
    .home-image-cta { position:absolute; left:22px; bottom:22px; display:inline-flex; align-items:center; gap:9px; min-height:46px; padding:0 18px; border-radius:999px; background:#fff; color:#1a4d3a; text-decoration:none; font-weight:900; box-shadow:0 14px 32px rgba(0,0,0,.18); }
    .home-defis-list { display:grid; gap:14px; align-content:center; }
    .home-defi-mini, .home-defi-more { display:flex; align-items:center; gap:14px; padding:18px; border-radius:18px; text-decoration:none; background:#f7fbf8; border:1px solid #e3eee6; color:#1a4d3a; transition:transform .18s ease, box-shadow .18s ease; }
    .home-defi-mini:hover, .home-defi-more:hover { transform:translateX(6px); box-shadow:0 14px 26px rgba(35,56,41,.10); }
    .home-defi-mini span { width:46px; height:46px; display:inline-flex; align-items:center; justify-content:center; border-radius:15px; background:#e8f4eb; color:#129f72; font-size:20px; flex:0 0 auto; }
    .home-defi-mini strong { display:block; color:#18251c; }
    .home-defi-mini small { display:block; margin-top:4px; color:#8a6d1f; font-weight:800; }
    .home-defi-more { justify-content:center; background:#1f6f51; color:#fff; border:0; font-weight:900; }
    .home-about { background:#f7fbf8; }
    .home-about-grid { display:grid; grid-template-columns:1fr .9fr; gap:34px; align-items:center; }
    .home-about-copy { padding:34px; border-radius:24px; background:#fff; border:1px solid #e5eee7; box-shadow:0 18px 38px rgba(35,56,41,.08); }
    .home-about-copy span { display:inline-flex; color:#129f72; font-size:13px; font-weight:900; text-transform:uppercase; letter-spacing:.5px; margin-bottom:10px; }
    .home-about-copy h2 { margin:0 0 16px; font-size:clamp(28px,4vw,46px); line-height:1.08; font-weight:900; }
    .home-about-copy p { color:#55615a; line-height:1.75; font-size:16px; margin:0 0 12px; }
    .home-about-image { min-height:380px; border-radius:24px; overflow:hidden; box-shadow:0 22px 48px rgba(35,56,41,.16); }
    .home-about-image img { width:100%; height:100%; object-fit:cover; display:block; }
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
    @media (max-width: 980px) {
        .home-hero-grid, .home-defis-layout, .home-nutrition-grid { grid-template-columns:1fr; }
        .home-hero-media { min-height:auto; }
        .home-about-grid { grid-template-columns:1fr; }
    }
    @media (max-width: 640px) {
        .home-hero { padding:60px 0; }
        .home-hero-media img { height:360px; }
        .home-floating-card { position:static; margin-top:12px; }
        .home-nutrition-panel { grid-template-columns:1fr; }
        .home-nutrition-card { min-height:140px; }
        .home-about-copy { padding:24px; }
        .home-about-image { min-height:300px; }
    }
    </style>
    <script src="../../assets/js/front-animations.js"></script>
</body>
</html>
