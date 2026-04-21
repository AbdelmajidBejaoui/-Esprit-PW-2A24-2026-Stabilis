<?php
session_start();
require_once __DIR__ . '/../../controllers/ProduitController.php';

$controller = new ProduitController();
$cart = $_SESSION['cart'] ?? [];
$cartCount = array_sum($cart);
$orderComplete = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['cart'] = [];
    $cart = [];
    $cartCount = 0;
    $orderComplete = true;
}

$products = [];
$total = 0;
if (!empty($cart)) {
    $products = $controller->getByIds(array_keys($cart));
    foreach ($products as $product) {
        $quantity = $cart[$product['id']] ?? 0;
        $total += $product['prix'] * $quantity;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Commande - Stabilis™</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/stabilis.css?v=1">
    <link rel="stylesheet" href="../../assets/css/front-style.css?v=1">
    <link rel="stylesheet" href="../../assets/css/front-pages.css?v=2">
</head>
<body>
    <div class="top-bar">
        <div class="container">
            <div class="top-bar-item">
                <i class="fas fa-envelope"></i>
                <span>contact@stabilis.example</span>
            </div>
            <div class="top-bar-item">
                <i class="fas fa-truck"></i>
                <span>Livraison sous 3-5 jours</span>
            </div>
        </div>
    </div>

    <nav class="navbar">
        <div class="container">
            <a href="index.php" class="navbar-brand">Stabilis<sup>™</sup></a>
            <ul class="navbar-nav">
                <li><a href="index.php">Accueil</a></li>
                <li><a href="shop.php">Boutique</a></li>
                <li><a href="cart.php" class="checkout-cart-link">
                    <i class="fas fa-shopping-cart"></i>
                    <?php if ($cartCount > 0): ?>
                        <span class="cart-badge">
                            <span class="cart-count"><?php echo $cartCount; ?></span>
                        </span>
                    <?php endif; ?>
                </a></li>
            </ul>
        </div>
    </nav>

    <section class="checkout-section">
        <div class="container">
            <?php if ($orderComplete): ?>
            <div class="order-success">
                <div class="success-icon">
                    <i class="fas fa-check"></i>
                </div>
                <h1 class="success-title">Commande confirmée !</h1>
                <p class="success-message">
                    Merci pour votre commande. Vous recevrez un email de confirmation dans quelques instants.
                </p>
                <a href="index.php" class="back-to-shop">
                    <i class="fas fa-home"></i>
                    Retour à l'accueil
                </a>
            </div>
            <?php elseif (empty($products)): ?>
            <div class="order-success">
                <div class="empty-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <h1 class="success-title">Panier vide</h1>
                <p class="success-message">
                    Votre panier est vide. Ajoutez des produits avant de procéder au paiement.
                </p>
                <a href="shop.php" class="back-to-shop">
                    <i class="fas fa-shopping-bag"></i>
                    Voir la boutique
                </a>
            </div>
            <?php else: ?>
            <div class="checkout-header">
                <h1 class="checkout-main-title">Finaliser ma commande</h1>
                <p class="checkout-subtitle">Informations de livraison et paiement</p>
            </div>

            <div class="checkout-container">
                <form method="POST" class="checkout-form">
                    <div class="form-section">
                        <h2 class="form-section-title">
                            <i class="fas fa-user"></i>
                            Informations de contact
                        </h2>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Prénom *</label>
                                <input type="text" name="first_name" class="form-input" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Nom *</label>
                                <input type="text" name="last_name" class="form-input" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Téléphone *</label>
                            <input type="tel" name="phone" class="form-input" required>
                        </div>
                    </div>

                    <div class="form-section">
                        <h2 class="form-section-title">
                            <i class="fas fa-map-marker-alt"></i>
                            Adresse de livraison
                        </h2>
                        <div class="form-group">
                            <label class="form-label">Adresse *</label>
                            <input type="text" name="address" class="form-input" placeholder="Numéro et rue" required>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Code postal *</label>
                                <input type="text" name="postal_code" class="form-input" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Ville *</label>
                                <input type="text" name="city" class="form-input" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Pays *</label>
                            <select name="country" class="form-input" required>
                                <option value="FR">France</option>
                                <option value="BE">Belgique</option>
                                <option value="CH">Suisse</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Instructions de livraison</label>
                            <textarea name="notes" class="form-input form-textarea" placeholder="Instructions spéciales pour la livraison..."></textarea>
                        </div>
                    </div>

                    <div class="form-section">
                        <h2 class="form-section-title">
                            <i class="fas fa-credit-card"></i>
                            Mode de paiement
                        </h2>
                        <div class="payment-methods">
                            <label class="payment-method selected">
                                <input type="radio" name="payment_method" value="card" class="payment-radio" checked>
                                <i class="fas fa-credit-card payment-icon"></i>
                                <div>
                                    <div class="payment-title">Carte bancaire</div>
                                    <div class="payment-subtitle">Visa, MasterCard, American Express</div>
                                </div>
                            </label>
                            <label class="payment-method">
                                <input type="radio" name="payment_method" value="paypal" class="payment-radio">
                                <i class="fab fa-paypal payment-icon"></i>
                                <div>
                                    <div class="payment-title">PayPal</div>
                                    <div class="payment-subtitle">Paiement sécurisé PayPal</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="place-order-btn">
                        <i class="fas fa-lock"></i>
                        Confirmer la commande
                    </button>
                </form>

                <div class="order-summary">
                    <div class="summary-title">Récapitulatif de commande</div>

                    <?php foreach ($products as $product):
                        $quantity = $cart[$product['id']] ?? 0;
                        $itemTotal = $product['prix'] * $quantity;
                    ?>
                    <div class="summary-item">
                        <img src="/AdminLTE3/dist/img/<?php echo $product['image_url'] ?? 'default-product.png'; ?>"
                             alt="<?php echo htmlspecialchars($product['nom']); ?>"
                             onerror="this.src='/AdminLTE3/dist/img/default-product.png'">
                        <div class="summary-item-details">
                            <div class="summary-item-title"><?php echo htmlspecialchars($product['nom']); ?></div>
                            <div class="summary-item-meta">Quantité: <?php echo $quantity; ?> × <?php echo number_format($product['prix'], 2); ?> €</div>
                        </div>
                        <div class="summary-item-price"><?php echo number_format($itemTotal, 2); ?> €</div>
                    </div>
                    <?php endforeach; ?>

                    <div class="summary-divider"></div>

                    <div class="summary-item">
                        <span>Sous-total</span>
                        <span><?php echo number_format($total, 2); ?> €</span>
                    </div>

                    <div class="summary-item">
                        <span>Livraison</span>
                        <span>Gratuite</span>
                    </div>

                    <div class="summary-divider"></div>

                    <div class="summary-item summary-total">
                        <span>Total</span>
                        <span><?php echo number_format($total, 2); ?> €</span>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <footer class="u-footer">
        <div class="container">
            <div class="u-center">
                <h3 class="u-footer-title">Stabilis<sup>™</sup></h3>
                <p class="u-footer-subtitle">Nutrition adaptative · Performance durable</p>
                <div class="u-footer-meta">
                    <i class="fas fa-seedling"></i> low carbon · high performance
                </div>
            </div>
        </div>
    </footer>

    <script>
    const paymentMethods = document.querySelectorAll('.payment-method');
    if (paymentMethods.length > 0) {
        paymentMethods.forEach(method => {
            method.addEventListener('click', function() {
                paymentMethods.forEach(m => m.classList.remove('selected'));
                this.classList.add('selected');
                const radio = this.querySelector('.payment-radio');
                if (radio) {
                    radio.checked = true;
                }
            });
        });
    }
    </script>
</body>
</html>
