<?php
session_start();
require_once __DIR__ . '/../../controllers/ProduitController.php';
require_once __DIR__ . '/../../controllers/PackController.php';

$search = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');
$sort = $_GET['sort'] ?? 'recent';
$controller = new ProduitController();
$packController = new PackController();
$produits = $controller->getAll($search, $category, $sort);
$packs = ($category === '') ? $packController->hydrateItems($packController->getAll(true, $search, $sort)) : [];
$categories = $controller->getCategories();
$cartCount = array_sum($_SESSION['cart'] ?? []);
$sortOptions = [
    'recent' => 'Pertinence / Nouveautes',
    'price_asc' => 'Prix croissant',
    'price_desc' => 'Prix decroissant',
    'name_asc' => 'Nom A-Z',
    'name_desc' => 'Nom Z-A',
    'category_asc' => 'Categorie'
];

$promoProducts = array_values(array_filter($produits, fn($product) => $controller->hasProductPromotion($product)));
$preOrderProducts = array_values(array_filter($produits, fn($product) => $controller->canPreOrder($product)));
$availableProducts = array_values(array_filter($produits, fn($product) => !$controller->canPreOrder($product)));
$newProducts = array_slice($availableProducts, 0, 4);
$catalogProducts = $produits;

function canBuyPackCard(array $pack) {
    foreach ($pack['items'] ?? [] as $item) {
        if ((int)($item['stock'] ?? 0) < (int)($item['quantite'] ?? 1)) {
            return false;
        }
    }
    return !empty($pack['items']);
}

function renderPackCard(array $pack) {
    $canBuy = canBuyPackCard($pack);
    ob_start();
    ?>
    <div class="product-card shop-card" data-reveal="zoom">
        <?php if (!empty($pack['image_url'])): ?>
        <img src="/AdminLTE3/dist/img/<?php echo htmlspecialchars($pack['image_url']); ?>" alt="<?php echo htmlspecialchars($pack['nom']); ?>" class="product-image" onerror="this.src='/AdminLTE3/dist/img/default-product.png'">
        <?php else: ?>
        <div class="pack-card-slideshow">
            <?php foreach (($pack['items'] ?? []) as $index => $item): ?>
            <img src="/AdminLTE3/dist/img/<?php echo htmlspecialchars($item['image_url'] ?? 'default-product.png'); ?>" alt="<?php echo htmlspecialchars($item['nom']); ?>" class="product-image pack-card-slide <?php echo $index === 0 ? 'is-active' : ''; ?>" onerror="this.src='/AdminLTE3/dist/img/default-product.png'">
            <?php endforeach; ?>
            <?php if (empty($pack['items'])): ?><img src="/AdminLTE3/dist/img/default-product.png" class="product-image" alt="Pack"><?php endif; ?>
        </div>
        <?php endif; ?>
        <div class="product-content">
            <div class="product-category">Pack</div>
            <h3 class="product-title"><?php echo htmlspecialchars($pack['nom']); ?></h3>
            <div class="product-price"><?php echo number_format((float)$pack['prix'], 2); ?> &euro;</div>
            <div class="product-actions">
                <a href="pack.php?id=<?php echo (int)$pack['id']; ?>" class="btn-view"><i class="fas fa-eye"></i> Voir</a>
                <?php if ($canBuy): ?>
                <a href="cart.php?action=add_pack&id=<?php echo (int)$pack['id']; ?>" class="btn-cart"><i class="fas fa-cart-plus"></i> Ajouter</a>
                <?php else: ?>
                <a href="preorder_pack.php?id=<?php echo (int)$pack['id']; ?>" class="btn-cart"><i class="fas fa-clock"></i> Pre-order</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function renderProductCard(array $produit, ProduitController $controller) {
    $effectivePrice = $controller->getEffectivePrice($produit);
    ob_start();
    ?>
    <div class="product-card shop-card" data-reveal="zoom">
        <img src="/AdminLTE3/dist/img/<?php echo htmlspecialchars($produit['image_url'] ?? 'default-product.png'); ?>" alt="<?php echo htmlspecialchars($produit['nom']); ?>" class="product-image" onerror="this.src='/AdminLTE3/dist/img/default-product.png'">
        <div class="product-content">
            <div class="product-category"><?php echo htmlspecialchars($produit['categorie']); ?></div>
            <h3 class="product-title"><?php echo htmlspecialchars($produit['nom']); ?></h3>
            <div class="product-price">
                <?php if ($controller->hasProductPromotion($produit)): ?>
                    <span class="old-price"><?php echo number_format((float)$produit['prix'], 2); ?> &euro;</span>
                    <span><?php echo number_format($effectivePrice, 2); ?> &euro;</span>
                <?php else: ?>
                    <?php echo number_format($effectivePrice, 2); ?> &euro;
                <?php endif; ?>
            </div>
            <div class="product-actions">
                <a href="product.php?id=<?php echo (int)$produit['id']; ?>" class="btn-view"><i class="fas fa-eye"></i> Voir</a>
                <?php if ($controller->canPreOrder($produit)): ?>
                    <a href="preorder.php?id=<?php echo (int)$produit['id']; ?>" class="btn-cart"><i class="fas fa-clock"></i> Pre-order</a>
                <?php else: ?>
                    <a href="cart.php?action=add&id=<?php echo (int)$produit['id']; ?>" class="btn-cart"><i class="fas fa-cart-plus"></i> Ajouter</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Boutique - Stabilis&trade;</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/stabilis.css?v=5">
    <link rel="stylesheet" href="../../assets/css/front-style.css?v=7">
</head>
<body class="page-shop">
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
                <li><a href="shop.php" class="active-nav">Boutique</a></li>
                <li><a href="cart.php"><i class="fas fa-shopping-cart"></i><?php if($cartCount > 0): ?><span class="cart-badge"><span class="cart-count"><?php echo $cartCount; ?></span></span><?php endif; ?></a></li>
                <li><a href="../../Views/back/produits/liste.php" class="admin-link"><i class="fas fa-cog"></i> Administration</a></li>
            </ul>
        </div>
    </nav>

    <section class="shop-header shop-hero-upgraded" data-shimmer>
        <div class="container">
            <span class="shop-kicker">Boutique Stabilis</span>
            <h1 class="shop-title" data-reveal="blur">Produits, packs et pre-commandes</h1>
            <p class="shop-subtitle" data-reveal="up">Trouvez plus vite ce qui est nouveau, en promotion, disponible en pack ou bientot en stock.</p>
        </div>
    </section>

    <section class="filters-section" data-reveal="up">
        <div class="container">
            <form method="GET" class="filters-container">
                <div class="filter-group">
                    <label class="filter-label">Rechercher</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Nom du produit..." class="search-input">
                </div>
                <div class="filter-group">
                    <label class="filter-label">Categorie</label>
                    <select name="category" class="filter-select">
                        <option value="">Toutes les categories</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category === $cat ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label">Trier</label>
                    <select name="sort" class="filter-select">
                        <?php foreach ($sortOptions as $value => $label): ?>
                        <option value="<?php echo htmlspecialchars($value); ?>" <?php echo $sort === $value ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn-filter"><i class="fas fa-search"></i> Filtrer</button>
                <?php if ($search || $category || $sort !== 'recent'): ?>
                <a href="shop.php" class="btn-secondary no-underline"><i class="fas fa-times"></i> Effacer</a>
                <?php endif; ?>
            </form>
        </div>
    </section>

    <main class="container shop-content shop-sections">
        <?php if (empty($produits) && empty($packs)): ?>
        <div class="empty-state" data-reveal="up">
            <i class="fas fa-search"></i>
            <h3>Aucun produit trouve</h3>
            <p>Essayez de modifier vos criteres de recherche.</p>
            <a href="shop.php" class="btn-primary"><i class="fas fa-th-large"></i> Voir tous les produits</a>
        </div>
        <?php else: ?>
            <?php if (!$search && !$category && !empty($newProducts)): ?>
            <section class="shop-section">
                <div class="shop-section-header"><h2>Nouveautes</h2><span><?php echo count($newProducts); ?> produits recents</span></div>
                <div class="product-grid shop-row-grid"><?php foreach ($newProducts as $product) echo renderProductCard($product, $controller); ?></div>
            </section>
            <?php endif; ?>

            <?php if (!empty($promoProducts)): ?>
            <section class="shop-section">
                <div class="shop-section-header"><h2>Promotions</h2><span>Prix reduits</span></div>
                <div class="product-grid shop-row-grid"><?php foreach (array_slice($promoProducts, 0, 6) as $product) echo renderProductCard($product, $controller); ?></div>
            </section>
            <?php endif; ?>

            <?php if (!empty($preOrderProducts)): ?>
            <section class="shop-section">
                <div class="shop-section-header"><h2>Pre-commandes</h2><span>Coming soon ou rupture</span></div>
                <div class="product-grid shop-row-grid"><?php foreach (array_slice($preOrderProducts, 0, 6) as $product) echo renderProductCard($product, $controller); ?></div>
            </section>
            <?php endif; ?>

            <?php if (!empty($packs)): ?>
            <section class="shop-section">
                <div class="shop-section-header"><h2>Packs</h2><span>Bundles prets</span></div>
                <div class="product-grid shop-row-grid"><?php foreach ($packs as $pack) echo renderPackCard($pack); ?></div>
            </section>
            <?php endif; ?>

            <section class="shop-section">
                <div class="shop-section-header"><h2>Catalogue complet</h2><span><?php echo count($catalogProducts); ?> produit<?php echo count($catalogProducts) > 1 ? 's' : ''; ?></span></div>
                <div class="product-grid" data-stagger-children>
                    <?php foreach ($catalogProducts as $produit) echo renderProductCard($produit, $controller); ?>
                </div>
            </section>
        <?php endif; ?>
    </main>

    <footer class="shop-footer">
        <div class="container">
            <div class="footer-content" data-reveal="up">
                <h3>Stabilis<sup>&trade;</sup></h3>
                <p>Nutrition adaptative &middot; Performance durable</p>
                <div class="footer-note"><i class="fas fa-seedling"></i> low carbon &middot; high performance</div>
            </div>
        </div>
    </footer>

    <style>
    .shop-hero-upgraded { text-align:left; }
    .shop-kicker { display:inline-flex; color:#F9F3E6; background:rgba(255,255,255,.12); padding:7px 12px; border-radius:999px; font-size:13px; font-weight:700; margin-bottom:12px; }
    .shop-sections { display:grid; gap:42px; }
    .shop-section-header { display:flex; align-items:end; justify-content:space-between; gap:16px; margin-bottom:16px; border-bottom:1px solid var(--border-light); padding-bottom:12px; }
    .shop-section-header h2 { margin:0; font-size:24px; }
    .shop-section-header span { color:var(--text-muted); font-size:13px; font-weight:600; }
    .shop-row-grid { grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); justify-content:start; }
    .shop-card .old-price { text-decoration:line-through; color:var(--text-muted); font-size:14px; margin-right:8px; }
    .pack-card-slideshow { position:relative; height:240px; overflow:hidden; background:#FCFCFA; }
    .pack-card-slide { position:absolute; inset:0; opacity:0; transition:opacity .4s ease; }
    .pack-card-slide.is-active { opacity:1; }
    </style>
    <script src="../../assets/js/front-animations.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.pack-card-slideshow').forEach(slider => {
            const slides = Array.from(slider.querySelectorAll('.pack-card-slide'));
            if (slides.length > 1) {
                let current = 0;
                setInterval(() => {
                    slides[current].classList.remove('is-active');
                    current = (current + 1) % slides.length;
                    slides[current].classList.add('is-active');
                }, 2400);
            }
        });
    });
    </script>
</body>
</html>
