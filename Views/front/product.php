<?php
session_start();
require_once __DIR__ . '/../../controllers/ProduitController.php';

$id = $_GET['id'] ?? null;
$controller = new ProduitController();
$product = $id ? $controller->getById($id) : null;
if (!$product) {
    header('Location: shop.php');
    exit();
}
$cartCount = array_sum($_SESSION['cart'] ?? []);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($product['nom']); ?> - Stabilis&trade;</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/stabilis.css?v=1">
    <link rel="stylesheet" href="../../assets/css/front-style.css?v=1">
</head>
<body class="page-product">
    <div class="top-bar">
        <div class="container">
            <div class="top-bar-item"><i class="fas fa-envelope"></i><span>contact@stabilis.example</span></div>
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
                <li><a href="../back/produits/liste.php" class="admin-link"><i class="fas fa-cog"></i> Administration</a></li>
            </ul>
        </div>
    </nav>
    <div style="background: var(--bg-surface); padding: 16px 0; border-bottom: 1px solid var(--border-light);">
        <div class="container" data-reveal="up" style="font-size: 14px; color: var(--text-muted);">
            <a href="index.php" style="color: var(--text-muted); text-decoration: none;">Accueil</a>
            <span style="margin: 0 8px;">&gt;</span>
            <a href="shop.php" style="color: var(--text-muted); text-decoration: none;">Boutique</a>
            <span style="margin: 0 8px;">&gt;</span>
            <span style="color: var(--text-primary);"><?php echo htmlspecialchars($product['nom']); ?></span>
        </div>
    </div>
    <section class="product-detail">
        <div class="container">
            <div class="product-container">
                <div class="product-image-container" data-reveal="left">
                    <img src="/AdminLTE3/dist/img/<?php echo htmlspecialchars($product['image_url'] ?? 'default-product.png'); ?>" alt="<?php echo htmlspecialchars($product['nom']); ?>" class="product-image" onerror="this.src='/AdminLTE3/dist/img/default-product.png'">
                </div>
                <div class="product-info" data-reveal="right">
                    <div class="product-category"><?php echo htmlspecialchars($product['categorie']); ?></div>
                    <h1><?php echo htmlspecialchars($product['nom']); ?></h1>
                    <div class="stock-indicator <?php echo ((int) $product['stock'] > 0) ? 'in-stock' : 'out-of-stock'; ?>">
                        <i class="fas fa-<?php echo ((int) $product['stock'] > 0) ? 'check-circle' : 'times-circle'; ?>"></i>
                        <?php echo ((int) $product['stock'] > 0) ? 'En stock (' . (int) $product['stock'] . ' unit&eacute;s)' : 'Rupture de stock'; ?>
                    </div>
                    <div class="product-price"><?php echo number_format((float) $product['prix'], 2); ?> &euro;</div>
                    <div class="product-description">
                        <?php echo !empty($product['description']) ? nl2br(htmlspecialchars($product['description'])) : 'D&eacute;couvrez notre compl&eacute;ment nutritionnel premium pour optimiser vos performances sportives.'; ?>
                    </div>
                    <div class="product-meta" data-reveal="up">
                        <div class="meta-item"><span class="meta-label">Cat&eacute;gorie</span><span class="meta-value"><?php echo htmlspecialchars($product['categorie']); ?></span></div>
                        <div class="meta-item"><span class="meta-label">Stock disponible</span><span class="meta-value"><?php echo (int) $product['stock']; ?> unit&eacute;s</span></div>
                        <div class="meta-item"><span class="meta-label">Livraison</span><span class="meta-value">Sous 3-5 jours</span></div>
                    </div>
                    <?php if ((int) $product['stock'] > 0): ?>
                    <form method="POST" action="cart.php" style="margin-bottom: 24px;">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="product_id" value="<?php echo (int) $product['id']; ?>">
                        <div class="quantity-selector">
                            <span style="font-weight:500; color:var(--text-primary);">Quantit&eacute; :</span>
                            <div class="quantity-controls">
                                <button type="button" class="quantity-btn" onclick="changeQuantity(-1)">-</button>
                                <input type="number" name="quantity" value="1" min="1" max="<?php echo (int) $product['stock']; ?>" class="quantity-input" id="quantity">
                                <button type="button" class="quantity-btn" onclick="changeQuantity(1)">+</button>
                            </div>
                        </div>
                        <div class="action-buttons" data-reveal="up">
                            <button type="submit" class="btn-add-cart"><i class="fas fa-cart-plus"></i> Ajouter au panier</button>
                        </div>
                    </form>
                    <?php endif; ?>
                    <div class="action-buttons" data-reveal="up">
                        <a href="shop.php" class="btn-back"><i class="fas fa-arrow-left"></i> Retour &agrave; la boutique</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <footer class="shop-footer">
        <div class="container">
            <div class="footer-content" data-reveal="up">
                <h3>Stabilis<sup>&trade;</sup></h3>
                <p>Nutrition adaptative &middot; Performance durable</p>
                <div class="footer-note"><i class="fas fa-seedling"></i> low carbon &middot; high performance</div>
            </div>
        </div>
    </footer>
    <script src="../../assets/js/front-animations.js"></script>
    <script>
    function changeQuantity(delta) {
        const input = document.getElementById('quantity');
        const currentValue = parseInt(input.value, 10) || 1;
        const newValue = currentValue + delta;
        const max = parseInt(input.max, 10) || 1;
        if (newValue >= 1 && newValue <= max) {
            input.value = newValue;
        }
    }
    </script>
</body>
</html>
