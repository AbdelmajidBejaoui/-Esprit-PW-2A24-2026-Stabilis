<?php
session_start();
require_once __DIR__ . '/../../controllers/ProduitController.php';
require_once __DIR__ . '/../../controllers/PackController.php';

$controller = new ProduitController();
$packController = new PackController();
$cart = $_SESSION['cart'] ?? [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['action'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        $productId = (int) ($_POST['product_id'] ?? 0);
        $packId = (int) ($_POST['pack_id'] ?? 0);
        $quantity = max(1, (int) ($_POST['quantity'] ?? 1));
    } else {
        $action = $_GET['action'] ?? '';
        $productId = (int) ($_GET['id'] ?? 0);
        $packId = (int) ($_GET['id'] ?? 0);
        $quantity = 1;
    }

    if ($action === 'add' && $productId > 0) {
        $product = $controller->getById($productId);
        if ($product && !$controller->canPreOrder($product) && (int)$product['stock'] > 0) {
            $maxStock = (int)$product['stock'];
            $_SESSION['cart'][$productId] = min(($_SESSION['cart'][$productId] ?? 0) + $quantity, $maxStock);
        }
    }

    if ($action === 'add_pack' && $packId > 0) {
        $pack = $packController->getById($packId);
        if ($pack && $packController->canBuyPack($pack, $quantity)) {
            $key = 'pack_' . $packId;
            $_SESSION['cart'][$key] = ($_SESSION['cart'][$key] ?? 0) + $quantity;
        }
    }

    if ($action === 'update' && $productId > 0) {
        $product = $controller->getById($productId);
        $maxStock = $product ? (int)$product['stock'] : $quantity;
        if ($quantity > 0) {
            $_SESSION['cart'][$productId] = min($quantity, $maxStock);
        } else {
            unset($_SESSION['cart'][$productId]);
        }
    }

    if ($action === 'remove' && $productId > 0) {
        unset($_SESSION['cart'][$productId]);
    }

    if ($action === 'remove_pack' && $packId > 0) {
        unset($_SESSION['cart']['pack_' . $packId]);
    }

    if ($action === 'clear') {
        $_SESSION['cart'] = [];
    }

    header('Location: cart.php');
    exit();
}

$cart = $_SESSION['cart'] ?? [];
$cartCount = array_sum($cart);
$productIds = [];
$packIds = [];
foreach (array_keys($cart) as $cartKey) {
    if (is_numeric($cartKey)) {
        $productIds[] = (int)$cartKey;
    } elseif (strpos((string)$cartKey, 'pack_') === 0) {
        $packIds[] = (int)substr((string)$cartKey, 5);
    }
}
$products = empty($productIds) ? [] : $controller->getByIds($productIds);
$packs = empty($packIds) ? [] : $packController->getByIds($packIds);
$total = 0;
foreach ($products as $product) {
    $quantity = $cart[$product['id']] ?? 0;
    $total += $controller->getEffectivePrice($product) * $quantity;
}
foreach ($packs as $pack) {
    $quantity = $cart['pack_' . $pack['id']] ?? 0;
    $total += (float)$pack['prix'] * $quantity;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Panier - Stabilis&trade;</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/stabilis.css?v=2">
    <link rel="stylesheet" href="../../assets/css/front-style.css?v=2">
    <link rel="stylesheet" href="../../assets/css/front-pages.css?v=3">
</head>
<body class="page-cart">
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
                <li><a href="cart.php" class="active-nav"><i class="fas fa-shopping-cart"></i><?php if($cartCount > 0): ?><span class="cart-badge"><span class="cart-count"><?php echo $cartCount; ?></span></span><?php endif; ?></a></li>
                <li><a href="../../Views/back/produits/liste.php" class="admin-link"><i class="fas fa-cog"></i> Administration</a></li>
            </ul>
        </div>
    </nav>
    <section class="cart-section">
        <div class="container">
            <div class="u-cart-title-wrap">
                <h1 class="u-cart-main-title">Votre panier</h1>
                <p class="u-cart-subtitle"><?php echo $cartCount; ?> article<?php echo $cartCount > 1 ? 's' : ''; ?> dans votre panier</p>
            </div>
            <?php if (empty($products) && empty($packs)): ?>
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <h2>Votre panier est vide</h2>
                <p>Découvrez nos produits et commencez vos achats.</p>
                <a href="shop.php" class="btn-primary u-inline-flex-cta"><i class="fas fa-shopping-bag"></i> Voir la boutique</a>
            </div>
            <?php else: ?>
            <div class="cart-container">
                <div class="cart-items">
                    <div class="cart-header"><i class="fas fa-shopping-cart"></i> Articles dans votre panier</div>
                    <?php foreach ($packs as $pack): $quantity = $cart['pack_' . $pack['id']] ?? 0; $itemTotal = (float)$pack['prix'] * $quantity; ?>
                    <div class="cart-item">
                        <img src="/AdminLTE3/dist/img/<?php echo htmlspecialchars($pack['image_url'] ?? 'default-product.png'); ?>" alt="<?php echo htmlspecialchars($pack['nom']); ?>" class="item-image" onerror="this.src='/AdminLTE3/dist/img/default-product.png'">
                        <div class="item-details">
                            <div class="item-title"><?php echo htmlspecialchars($pack['nom']); ?></div>
                            <div class="item-category">Pack</div>
                            <div class="item-price"><?php echo number_format((float)$pack['prix'], 2); ?> &euro;</div>
                        </div>
                        <div class="item-total"><?php echo $quantity; ?> x <?php echo number_format($itemTotal, 2); ?> &euro;</div>
                        <form method="POST" class="u-remove-form">
                            <input type="hidden" name="action" value="remove_pack">
                            <input type="hidden" name="pack_id" value="<?php echo (int)$pack['id']; ?>">
                            <button type="submit" class="remove-btn" title="Retirer du panier"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                    <?php foreach ($products as $product): $quantity = $cart[$product['id']] ?? 0; $unitPrice = $controller->getEffectivePrice($product); $itemTotal = $unitPrice * $quantity; ?>
                    <div class="cart-item">
                        <img src="/AdminLTE3/dist/img/<?php echo htmlspecialchars($product['image_url'] ?? 'default-product.png'); ?>" alt="<?php echo htmlspecialchars($product['nom']); ?>" class="item-image" onerror="this.src='/AdminLTE3/dist/img/default-product.png'">
                        <div class="item-details">
                            <div class="item-title"><?php echo htmlspecialchars($product['nom']); ?></div>
                            <div class="item-category"><?php echo htmlspecialchars($product['categorie']); ?></div>
                            <div class="item-price">
                                <?php if ($controller->hasProductPromotion($product)): ?>
                                    <span style="text-decoration: line-through; color: var(--text-muted); margin-right: 6px;"><?php echo number_format((float) $product['prix'], 2); ?> &euro;</span>
                                    <strong style="color: #1b5e20;"><?php echo number_format($unitPrice, 2); ?> &euro;</strong>
                                <?php else: ?>
                                    <?php echo number_format($unitPrice, 2); ?> &euro;
                                <?php endif; ?>
                            </div>
                        </div>
                        <form method="POST" class="u-qty-form">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="product_id" value="<?php echo (int) $product['id']; ?>">
                            <div class="quantity-controls">
                                <button type="button" class="quantity-btn" onclick="changeQuantity(<?php echo (int) $product['id']; ?>, -1)">-</button>
                                <input type="text" name="quantity" value="<?php echo $quantity; ?>" class="quantity-input" inputmode="numeric" data-lower="1" data-upper="<?php echo (int)$product['stock']; ?>" onchange="updateQuantity(<?php echo (int) $product['id']; ?>, this.value)">
                                <button type="button" class="quantity-btn" onclick="changeQuantity(<?php echo (int) $product['id']; ?>, 1)">+</button>
                            </div>
                        </form>
                        <div class="item-total"><?php echo number_format($itemTotal, 2); ?> &euro;</div>
                        <form method="POST" class="u-remove-form">
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="product_id" value="<?php echo (int) $product['id']; ?>">
                            <button type="submit" class="remove-btn" title="Retirer du panier"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                    <div class="u-cart-bottom">
                        <form method="POST">
                            <input type="hidden" name="action" value="clear">
                            <button type="submit" class="clear-cart" onclick="return confirm('Voulez-vous vider votre panier ?')"><i class="fas fa-trash-alt"></i> Vider le panier</button>
                        </form>
                    </div>
                </div>
                <div class="cart-summary">
                    <div class="summary-title">Récapitulatif</div>
                    <div class="summary-row"><span>Sous-total</span><span><?php echo number_format($total, 2); ?> &euro;</span></div>
                    <div class="summary-row"><span>Livraison</span><span>Gratuite</span></div>
                    <div class="summary-row total"><span>Total</span><span><?php echo number_format($total, 2); ?> &euro;</span></div>
                    <a href="order.php" class="checkout-btn"><i class="fas fa-credit-card"></i> Procéder au paiement</a>
                    <a href="shop.php" class="continue-shopping"><i class="fas fa-arrow-left"></i> Continuer mes achats</a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>
    <footer class="u-footer">
        <div class="container">
            <div class="u-center">
                <h3 class="u-footer-title">Stabilis<sup>&trade;</sup></h3>
                <p class="u-footer-subtitle">Nutrition adaptative · Performance durable</p>
                <div class="u-footer-meta"><i class="fas fa-seedling"></i> low carbon · high performance</div>
            </div>
        </div>
    </footer>
    <script>
    function changeQuantity(productId, delta) {
        const input = document.querySelector(`input[name="quantity"][onchange*="${productId}"]`);
        const currentValue = parseInt(input.value, 10) || 1;
        const newValue = currentValue + delta;
        const max = parseInt(input.dataset.upper, 10) || 1;
        if (newValue >= 1 && newValue <= max) {
            input.value = newValue;
            updateQuantity(productId);
        }
    }
    function updateQuantity(productId) {
        const input = document.querySelector(`input[name="quantity"][onchange*="${productId}"]`);
        const min = parseInt(input.dataset.lower, 10) || 1;
        const max = parseInt(input.dataset.upper, 10) || min;
        const value = parseInt(input.value, 10);
        input.value = Math.max(min, Math.min(max, Number.isNaN(value) ? min : value));
        const form = input.closest('form');
        form.submit();
    }
    </script>
</body>
</html>

