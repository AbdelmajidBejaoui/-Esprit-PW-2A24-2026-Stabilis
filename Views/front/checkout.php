<?php
session_start();
require_once __DIR__ . '/../../controllers/ProduitController.php';

$controller = new ProduitController();
$cart = $_SESSION['cart'] ?? [];
$cartCount = array_sum($cart);
$orderComplete = false;

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['cart'] = [];
    $cart = [];
    $cartCount = 0;
    $orderComplete = true;
}

$products = [];
$total = 0;
if(!empty($cart)) {
    $products = $controller->getByIds(array_keys($cart));
    foreach($products as $product) {
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

    <style>
        .checkout-section {
            padding: 60px 0;
        }

        .checkout-container {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 40px;
        }

        .checkout-form {
            background: var(--bg-elevated);
            border-radius: var(--radius-lg);
            padding: 32px;
            box-shadow: var(--shadow-sm);
        }

        .form-section {
            margin-bottom: 32px;
        }

        .form-section-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 16px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: var(--text-primary);
            margin-bottom: 6px;
        }

        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            background: var(--bg-elevated);
            color: var(--text-primary);
            font-size: 14px;
            transition: border-color var(--transition-fast);
        }

        .form-input:focus {
            outline: none;
            border-color: var(--accent-herb);
            box-shadow: 0 0 0 3px rgba(58, 107, 75, 0.1);
        }

        .form-textarea {
            resize: vertical;
            min-height: 80px;
        }

        .order-summary {
            background: var(--bg-elevated);
            border-radius: var(--radius-lg);
            padding: 24px;
            box-shadow: var(--shadow-sm);
            height: fit-content;
        }

        .summary-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 20px;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 14px;
        }

        .summary-item img {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: var(--radius-sm);
            margin-right: 12px;
        }

        .summary-item-details {
            flex: 1;
        }

        .summary-item-title {
            font-weight: 500;
            color: var(--text-primary);
            font-size: 13px;
        }

        .summary-item-meta {
            color: var(--text-muted);
            font-size: 12px;
        }

        .summary-divider {
            border-top: 1px solid var(--border-light);
            margin: 16px 0;
        }

        .summary-total {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-primary);
        }

        .payment-methods {
            margin-top: 24px;
        }

        .payment-method {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px;
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            margin-bottom: 12px;
            cursor: pointer;
            transition: all var(--transition-fast);
        }

        .payment-method:hover {
            border-color: var(--accent-herb);
            background: var(--accent-herb-light);
        }

        .payment-method.selected {
            border-color: var(--accent-herb);
            background: var(--accent-herb-light);
        }

        .payment-radio {
            display: none;
        }

        .payment-icon {
            width: 24px;
            height: 24px;
            opacity: 0.6;
        }

        .payment-method.selected .payment-icon {
            opacity: 1;
        }

        .place-order-btn {
            background: var(--accent-herb);
            color: white;
            border: none;
            padding: 16px 24px;
            border-radius: var(--radius-full);
            font-size: 16px;
            font-weight: 600;
            width: 100%;
            cursor: pointer;
            transition: all var(--transition-normal);
            margin-top: 24px;
        }

        .place-order-btn:hover {
            background: var(--accent-herb-dark);
            transform: translateY(-1px);
        }

        .place-order-btn:disabled {
            background: var(--text-muted);
            cursor: not-allowed;
            transform: none;
        }

        .order-success {
            text-align: center;
            padding: 80px 20px;
        }

        .success-icon {
            width: 80px;
            height: 80px;
            background: var(--accent-herb);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 32px;
            margin: 0 auto 24px;
        }

        .success-title {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 8px;
        }

        .success-message {
            color: var(--text-secondary);
            font-size: 16px;
            margin-bottom: 32px;
        }

        .back-to-shop {
            background: var(--accent-herb);
            color: white;
            padding: 14px 32px;
            border-radius: var(--radius-full);
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all var(--transition-normal);
        }

        .back-to-shop:hover {
            background: var(--accent-herb-dark);
            transform: translateY(-1px);
        }

        @media (max-width: 768px) {
            .checkout-container {
                grid-template-columns: 1fr;
            }

            .form-row {
                grid-template-columns: 1fr;
            }
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
    </style>
</head>
<body>
    <!-- Top Bar -->
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

    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <a href="index.php" class="navbar-brand">Stabilis<sup>™</sup></a>
            <ul class="navbar-nav">
                <li><a href="index.php">Accueil</a></li>
                <li><a href="shop.php">Boutique</a></li>
                <li><a href="cart.php" style="color: var(--accent-herb);">
                    <i class="fas fa-shopping-cart"></i>
                    <?php if($cartCount > 0): ?>
                        <span class="cart-badge">
                            <span class="cart-count"><?php echo $cartCount; ?></span>
                        </span>
                    <?php endif; ?>
                </a></li>
            </ul>
        </div>
    </nav>

    <!-- Checkout Section -->
    <section class="checkout-section">
        <div class="container">
            <?php if($orderComplete): ?>
            <!-- Order Success -->
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
            <?php elseif(empty($products)): ?>
            <!-- Empty Cart -->
            <div class="order-success">
                <div style="width: 80px; height: 80px; background: var(--text-muted); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 32px; margin: 0 auto 24px;">
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
            <div style="text-align: center; margin-bottom: 40px;">
                <h1 style="font-size: 32px; font-weight: 700; color: var(--text-primary); margin-bottom: 8px;">Finaliser ma commande</h1>
                <p style="color: var(--text-secondary); font-size: 16px;">Informations de livraison et paiement</p>
            </div>

            <div class="checkout-container">
                <!-- Checkout Form -->
                <form method="POST" class="checkout-form">
                    <!-- Contact Information -->
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

                    <!-- Shipping Address -->
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

                    <!-- Payment Methods -->
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
                                    <div style="font-weight: 500; color: var(--text-primary);">Carte bancaire</div>
                                    <div style="font-size: 12px; color: var(--text-muted);">Visa, MasterCard, American Express</div>
                                </div>
                            </label>
                            <label class="payment-method">
                                <input type="radio" name="payment_method" value="paypal" class="payment-radio">
                                <i class="fab fa-paypal payment-icon"></i>
                                <div>
                                    <div style="font-weight: 500; color: var(--text-primary);">PayPal</div>
                                    <div style="font-size: 12px; color: var(--text-muted);">Paiement sécurisé PayPal</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="place-order-btn">
                        <i class="fas fa-lock"></i>
                        Confirmer la commande
                    </button>
                </form>

                <!-- Order Summary -->
                <div class="order-summary">
                    <div class="summary-title">Récapitulatif de commande</div>

                    <?php foreach($products as $product):
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
                        <div style="font-weight: 600; color: var(--text-primary);"><?php echo number_format($itemTotal, 2); ?> €</div>
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

    <!-- Footer -->
    <footer style="background: var(--sidebar-green); color: var(--sidebar-text); padding: 40px 0; margin-top: 60px;">
        <div class="container">
            <div style="text-align: center;">
                <h3 style="color: white; margin-bottom: 8px;">Stabilis<sup>™</sup></h3>
                <p style="opacity: 0.8; margin-bottom: 16px;">Nutrition adaptative · Performance durable</p>
                <div style="opacity: 0.6;">
                    <i class="fas fa-seedling"></i> low carbon · high performance
        </div>
    </footer>

    <script>
    // Payment method selection
    document.querySelectorAll('.payment-method').forEach(method => {
        method.addEventListener('click', function() {
            document.querySelectorAll('.payment-method').forEach(m => m.classList.remove('selected'));
            this.classList.add('selected');
            this.querySelector('.payment-radio').checked = true;
        });
    </script>
</body>
</html>
      <div class="container">
        <div class="row no-gutters d-flex align-items-start align-items-center px-md-0">
          <div class="col-lg-12 d-block">
            <div class="row d-flex">
              <div class="col-md pr-4 d-flex topper align-items-center">
                <div class="icon mr-2 d-flex justify-content-center align-items-center"><span class="icon-phone2"></span></div>
                <span class="text">contact@stabilis.example</span>
              </div>
              <div class="col-md pr-4 d-flex topper align-items-center text-lg-right">
                <span class="text">Livraison sous 3-5 jours</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <nav class="navbar navbar-expand-lg navbar-dark ftco_navbar bg-dark ftco-navbar-light" id="ftco-navbar">
      <div class="container">
        <a class="navbar-brand" href="index.php">Stabilis</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#ftco-nav" aria-controls="ftco-nav" aria-expanded="false" aria-label="Toggle navigation">
          <span class="oi oi-menu"></span> Menu
        </button>
        <div class="collapse navbar-collapse" id="ftco-nav">
          <ul class="navbar-nav ml-auto">
            <li class="nav-item"><a href="index.php" class="nav-link">Accueil</a></li>
            <li class="nav-item"><a href="shop.php" class="nav-link">Boutique</a></li>
            <li class="nav-item"><a href="cart.php" class="nav-link">Panier<?php echo $cartCount ? ' (' . $cartCount . ')' : ''; ?></a></li>
            <li class="nav-item cta cta-colored"><a href="../../Views/back/produits/liste.php" class="nav-link">Back Office</a></li>
          </ul>
        </div>
      </div>
    </nav>
    <section class="hero-wrap hero-bread" style="background-image: url('../../FrontOfficeFreeSource/images/bg_1.jpg');">
      <div class="container">
        <div class="row no-gutters slider-text align-items-center justify-content-center">
          <div class="col-md-9 ftco-animate text-center">
            <p class="breadcrumbs"><span class="mr-2"><a href="index.php">Accueil</a></span> <span><a href="cart.php">Panier</a></span> <span>Caisse</span></p>
            <h1 class="mb-0 bread">Paiement</h1>
          </div>
        </div>
      </div>
    </section>
    <section class="ftco-section bg-light">
      <div class="container">
        <?php if($orderComplete): ?>
          <div class="row justify-content-center">
            <div class="col-md-8 text-center py-5">
              <h2>Merci pour votre commande !</h2>
              <p class="lead">Votre panier a été vidé et nous préparons votre commande.</p>
              <p><a href="index.php" class="btn btn-primary py-3 px-5">Retour à l'accueil</a></p>
            </div>
          </div>
        <?php elseif(empty($products)): ?>
          <div class="row">
            <div class="col-12 text-center py-5">
              <h2>Votre panier est vide.</h2>
              <p><a href="shop.php" class="btn btn-primary py-3 px-5">Voir les produits</a></p>
            </div>
          </div>
        <?php else: ?>
          <div class="row">
            <div class="col-lg-8">
              <div class="table-responsive">
                <table class="table cart-table">
                  <thead>
                    <tr>
                      <th>Produit</th>
                      <th>Quantité</th>
                      <th>Prix</th>
                      <th>Total</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach($products as $product):
                      $quantity = $cart[$product['id']] ?? 0;
                      $lineTotal = $product['prix'] * $quantity;
                    ?>
                    <tr>
                      <td><?php echo htmlspecialchars($product['nom']); ?></td>
                      <td><?php echo $quantity; ?></td>
                      <td><?php echo number_format($product['prix'], 2); ?> €</td>
                      <td><?php echo number_format($lineTotal, 2); ?> €</td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="col-lg-4">
              <div class="checkout-total">
                <h3>Résumé</h3>
                <p>Articles: <strong><?php echo $cartCount; ?></strong></p>
                <p>Total: <strong><?php echo number_format($total, 2); ?> €</strong></p>
                <form method="POST">
                  <button type="submit" class="btn btn-primary btn-block py-3">Confirmer la commande</button>
                </form>
              </div>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </section>
    <footer class="ftco-footer ftco-section">
      <div class="container">
        <div class="row">
          <div class="col-md-12 text-center">
            <p>&copy; <?php echo date('Y'); ?> Stabilis. Tous droits réservés.</p>
          </div>
        </div>
      </div>
    </footer>
    <script src="../../FrontOfficeFreeSource/js/jquery.min.js"></script>
    <script src="../../FrontOfficeFreeSource/js/jquery-migrate-3.0.1.min.js"></script>
    <script src="../../FrontOfficeFreeSource/js/popper.min.js"></script>
    <script src="../../FrontOfficeFreeSource/js/bootstrap.min.js"></script>
    <script src="../../FrontOfficeFreeSource/js/jquery.easing.1.3.js"></script>
    <script src="../../FrontOfficeFreeSource/js/jquery.waypoints.min.js"></script>
    <script src="../../FrontOfficeFreeSource/js/jquery.stellar.min.js"></script>
    <script src="../../FrontOfficeFreeSource/js/owl.carousel.min.js"></script>
    <script src="../../FrontOfficeFreeSource/js/jquery.magnific-popup.min.js"></script>
    <script src="../../FrontOfficeFreeSource/js/aos.js"></script>
    <script src="../../FrontOfficeFreeSource/js/jquery.animateNumber.min.js"></script>
    <script src="../../FrontOfficeFreeSource/js/bootstrap-datepicker.js"></script>
    <script src="../../FrontOfficeFreeSource/js/scrollax.min.js"></script>
    <script src="../../FrontOfficeFreeSource/js/main.js"></script>
  </body>
</html>
