<?php
session_start();
require_once __DIR__ . '/../../controllers/ProduitController.php';
require_once __DIR__ . '/../../controllers/PackController.php';
require_once __DIR__ . '/../../controllers/CommandeController.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../Services/InvoiceService.php';
require_once __DIR__ . '/../../Services/StockAlertService.php';
require_once __DIR__ . '/../../Services/StripeCheckoutService.php';

$produitController = new ProduitController();
$packController = new PackController();
$commandeController = new CommandeController();
$cart = $_SESSION['cart'] ?? [];
$stripeConfirmed = false;
$stripeStatusMessage = '';

if (isset($_GET['stripe'])) {
    if ($_GET['stripe'] === 'success' && !empty($_GET['session_id']) && !empty($_SESSION['stripe_pending_order'])) {
        $pendingStripe = $_SESSION['stripe_pending_order'];
        $sessionId = (string)$_GET['session_id'];

        if (!empty($pendingStripe['session_id']) && hash_equals((string)$pendingStripe['session_id'], $sessionId)) {
            $stripeService = new StripeCheckoutService();
            $stripeResult = $stripeService->retrieveCheckoutSession($sessionId);
            $stripeSession = $stripeResult['data'] ?? [];

            if ($stripeResult['success'] && ($stripeSession['payment_status'] ?? '') === 'paid') {
                $stripeConfirmed = true;
                $_POST = $pendingStripe['post'] ?? [];
                $_POST['paiement'] = 'card';
                $_SESSION['cart'] = $pendingStripe['cart'] ?? [];
                $cart = $_SESSION['cart'];
                $_POST['notes'] = trim(($_POST['notes'] ?? '') . "\nPaiement Stripe confirme: " . $sessionId);
            } else {
                $stripeStatusMessage = $stripeResult['message'] ?? 'Paiement Stripe non confirme.';
            }
        } else {
            $stripeStatusMessage = 'Session Stripe differente de la commande en attente.';
        }
    } elseif ($_GET['stripe'] === 'cancel') {
        $stripeStatusMessage = 'Paiement Stripe annule. Votre panier est toujours disponible.';
    }
}

$cartCount = array_sum($cart);
$products = [];
$packs = [];
$total = 0;

if (!empty($cart)) {
    $productIds = [];
    $packIds = [];
    foreach (array_keys($cart) as $cartKey) {
        if (is_numeric($cartKey)) {
            $productIds[] = (int)$cartKey;
        } elseif (strpos((string)$cartKey, 'pack_') === 0) {
            $packIds[] = (int)substr((string)$cartKey, 5);
        }
    }
    $products = $produitController->getByIds($productIds);
    $packs = $packController->getByIds($packIds);
    foreach ($products as $product) {
        $quantity = $cart[$product['id']] ?? 0;
        $total += $produitController->getEffectivePrice($product) * $quantity;
    }
    foreach ($packs as $pack) {
        $quantity = $cart['pack_' . $pack['id']] ?? 0;
        $total += (float)$pack['prix'] * $quantity;
    }
}

$values = [
    'prenom' => '',
    'nom' => '',
    'email' => '',
    'telephone' => '',
    'adresse' => '',
    'code_postal' => '',
    'ville' => '',
    'pays' => 'Tunisie',
    'notes' => '',
    'paiement' => 'card',
];
$errors = [];
$orderComplete = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' || $stripeConfirmed) {
    foreach ($values as $field => $value) {
        if (isset($_POST[$field])) {
            $values[$field] = trim($_POST[$field]);
        }
    }

    if (empty($cart)) {
        $errors['cart'] = 'Votre panier est vide. Ajoutez des produits avant de valider la commande.';
    }

    foreach ($products as $product) {
        $quantity = (int)($cart[$product['id']] ?? 0);
        $stock = (int)($product['stock'] ?? 0);
        if ($quantity > $stock) {
            $errors['cart'] = 'Stock insuffisant pour ' . $product['nom'] . '. Stock disponible : ' . $stock . '.';
            break;
        }
    }

    foreach ($packs as $pack) {
        $quantity = (int)($cart['pack_' . $pack['id']] ?? 0);
        if (!$packController->canBuyPack($pack, $quantity)) {
            $errors['cart'] = 'Stock insuffisant pour le pack ' . $pack['nom'] . '.';
            break;
        }
    }

    $validationErrors = [];
    $isDataValid = $commandeController->validateData($values, $validationErrors);
    $errors = array_merge($errors, $validationErrors);

    if ($isDataValid && empty($errors['cart'])) {
        $created = false;
        $lastOrderId = null;
        
        $discountPercent = intval($_POST['discount_percent'] ?? 0);
        $promoCodeId = intval($_POST['promo_code_id'] ?? 0);
        $promoProductId = intval($_POST['promo_product_id'] ?? 0);
        $promoMarked = false;

        if (!$stripeConfirmed && $values['paiement'] === 'card') {
            $stripeLineItems = [];
            $stripeBaseTotal = 0;
            $stripeDiscountAmount = 0;

            foreach ($products as $product) {
                $quantity = (int)($cart[$product['id']] ?? 0);
                if ($quantity < 1) {
                    continue;
                }
                $unitPrice = $produitController->getEffectivePrice($product);
                $lineTotal = $unitPrice * $quantity;
                $lineDiscountPercent = ($promoProductId <= 0 || $promoProductId === (int)$product['id']) ? $discountPercent : 0;
                $stripeBaseTotal += $lineTotal;
                $stripeDiscountAmount += ($lineTotal * $lineDiscountPercent) / 100;
                $stripeLineItems[] = [
                    'name' => $product['nom'],
                    'quantity' => $quantity,
                ];
            }

            foreach ($packs as $pack) {
                $quantity = (int)($cart['pack_' . $pack['id']] ?? 0);
                if ($quantity < 1) {
                    continue;
                }
                $lineTotal = (float)$pack['prix'] * $quantity;
                $lineDiscountPercent = $promoProductId <= 0 ? $discountPercent : 0;
                $stripeBaseTotal += $lineTotal;
                $stripeDiscountAmount += ($lineTotal * $lineDiscountPercent) / 100;
                $stripeLineItems[] = [
                    'name' => 'Pack: ' . $pack['nom'],
                    'quantity' => $quantity,
                ];
            }

            $stripeTotal = max(0, $stripeBaseTotal - $stripeDiscountAmount);
            $stripeService = new StripeCheckoutService();
            $_SESSION['stripe_pending_order'] = [
                'post' => $_POST,
                'cart' => $cart,
                'created_at' => time(),
            ];

            $stripeSession = $stripeService->createCheckoutSession($values, $stripeLineItems, $stripeTotal);
            if ($stripeSession['success']) {
                $_SESSION['stripe_pending_order']['session_id'] = $stripeSession['id'];
                header('Location: ' . $stripeSession['url']);
                exit();
            }

            unset($_SESSION['stripe_pending_order']);
            $errors['general'] = 'Stripe: ' . ($stripeSession['message'] ?? 'Impossible de creer le paiement.');
        }

        if (!empty($errors['general'])) {
            $created = false;
        } else {
        
        foreach ($products as $product) {
            $quantity = $cart[$product['id']] ?? 0;
            if ($quantity < 1) {
                continue;
            }
            
            $unitPrice = $produitController->getEffectivePrice($product);
            $baseTotal = $unitPrice * $quantity;
            $lineDiscountPercent = ($promoProductId <= 0 || $promoProductId === (int)$product['id']) ? $discountPercent : 0;
            $discountAmount = ($baseTotal * $lineDiscountPercent) / 100;
            $finalTotal = $baseTotal - $discountAmount;
            
            $commande = new Commande(
                $product['id'],
                $quantity,
                $values['prenom'],
                $values['nom'],
                $values['email'],
                $values['telephone'],
                $values['adresse'],
                $values['code_postal'],
                $values['ville'],
                $values['pays'],
                $values['notes'],
                $values['paiement'],
                $baseTotal,
                'En attente'
            );
            $orderId = $commandeController->add($commande);
            if ($orderId !== false) {
                $created = true;
                $lastOrderId = $orderId;
                $produitController->decrementStock($product['id'], $quantity);
                
                if ($discountPercent > 0) {
                    $pdo = Database::getConnection();
                    $updateSql = "UPDATE commandes SET discount_percent = ?, discount_amount = ?, final_total = ? WHERE id = ?";
                    $updateStmt = $pdo->prepare($updateSql);
                    $updateStmt->execute([$lineDiscountPercent, $discountAmount, $finalTotal, $orderId]);
                }
                
                if ($promoCodeId > 0 && $lineDiscountPercent > 0 && !$promoMarked) {
                    require_once __DIR__ . '/../../Services/PromoCodeValidator.php';
                    $pdo = Database::getConnection();
                    $validator = new PromoCodeValidator($pdo);
                    $validator->markCodeAsUsed($promoCodeId, $orderId, $values['email']);
                    $promoMarked = true;
                }
            }
        }
        foreach ($packs as $pack) {
            $quantity = $cart['pack_' . $pack['id']] ?? 0;
            if ($quantity < 1 || empty($pack['items'])) {
                continue;
            }

            $baseTotal = (float)$pack['prix'] * $quantity;
            $lineDiscountPercent = $promoProductId <= 0 ? $discountPercent : 0;
            $discountAmount = ($baseTotal * $lineDiscountPercent) / 100;
            $finalTotal = $baseTotal - $discountAmount;
            $firstProductId = (int)$pack['items'][0]['produit_id'];
            $packNotes = trim($values['notes'] . "\nPack: " . $pack['nom']);

            $commande = new Commande(
                $firstProductId,
                $quantity,
                $values['prenom'],
                $values['nom'],
                $values['email'],
                $values['telephone'],
                $values['adresse'],
                $values['code_postal'],
                $values['ville'],
                $values['pays'],
                $packNotes,
                $values['paiement'],
                $baseTotal,
                'En attente'
            );
            $orderId = $commandeController->add($commande);
            if ($orderId !== false) {
                $created = true;
                $lastOrderId = $orderId;
                foreach ($pack['items'] as $item) {
                    $produitController->decrementStock($item['produit_id'], (int)$item['quantite'] * $quantity);
                }

                if ($lineDiscountPercent > 0) {
                    $pdo = Database::getConnection();
                    $updateStmt = $pdo->prepare("UPDATE commandes SET discount_percent = ?, discount_amount = ?, final_total = ? WHERE id = ?");
                    $updateStmt->execute([$lineDiscountPercent, $discountAmount, $finalTotal, $orderId]);
                }

                if ($promoCodeId > 0 && $lineDiscountPercent > 0 && !$promoMarked) {
                    require_once __DIR__ . '/../../Services/PromoCodeValidator.php';
                    $validator = new PromoCodeValidator(Database::getConnection());
                    $validator->markCodeAsUsed($promoCodeId, $orderId, $values['email']);
                    $promoMarked = true;
                }
            }
        }
        }
        if ($created) {
            try {
                $stockAlertService = new StockAlertService(Database::getConnection());
                $stockAlertService->sendLowStockAlert();
            } catch (Exception $e) {
                error_log("Error sending stock alert: " . $e->getMessage());
            }

            try {
                $pdo = Database::getConnection();
                $invoiceService = new InvoiceService($pdo);
                
                $orderDetails = [
                    'id' => $lastOrderId,
                    'prenom' => $values['prenom'],
                    'nom' => $values['nom'],
                    'email' => $values['email'],
                    'telephone' => $values['telephone'],
                    'adresse' => $values['adresse'],
                    'code_postal' => $values['code_postal'],
                    'ville' => $values['ville'],
                    'pays' => $values['pays'],
                    'paiement' => $values['paiement'],
                    'date_commande' => date('Y-m-d H:i:s'),
                    'statut' => 'En attente',
                    'discount_percent' => $discountPercent,
                    'discount_amount' => array_sum(array_map(function ($product) use ($cart, $discountPercent, $promoProductId) {
                        $quantity = $cart[$product['id']] ?? 0;
                        $lineDiscountPercent = ($promoProductId <= 0 || $promoProductId === (int)$product['id']) ? $discountPercent : 0;
                        $unitPrice = ((isset($product['promo_prix']) && $product['promo_prix'] !== '' && (float)$product['promo_prix'] > 0 && (float)$product['promo_prix'] < (float)$product['prix']) ? (float)$product['promo_prix'] : (float)$product['prix']);
                        return $quantity > 0 ? (($unitPrice * $quantity) * $lineDiscountPercent / 100) : 0;
                    }, $products)) + array_sum(array_map(function ($pack) use ($cart, $discountPercent, $promoProductId) {
                        $quantity = $cart['pack_' . $pack['id']] ?? 0;
                        $lineDiscountPercent = $promoProductId <= 0 ? $discountPercent : 0;
                        return $quantity > 0 ? (((float)$pack['prix'] * $quantity) * $lineDiscountPercent / 100) : 0;
                    }, $packs)),
                    'final_total' => array_sum(array_map(function ($product) use ($cart, $discountPercent, $promoProductId) {
                        $quantity = $cart[$product['id']] ?? 0;
                        $lineDiscountPercent = ($promoProductId <= 0 || $promoProductId === (int)$product['id']) ? $discountPercent : 0;
                        $unitPrice = ((isset($product['promo_prix']) && $product['promo_prix'] !== '' && (float)$product['promo_prix'] > 0 && (float)$product['promo_prix'] < (float)$product['prix']) ? (float)$product['promo_prix'] : (float)$product['prix']);
                        $baseTotal = $quantity > 0 ? ($unitPrice * $quantity) : 0;
                        return $baseTotal - ($baseTotal * $lineDiscountPercent / 100);
                    }, $products)) + array_sum(array_map(function ($pack) use ($cart, $discountPercent, $promoProductId) {
                        $quantity = $cart['pack_' . $pack['id']] ?? 0;
                        $lineDiscountPercent = $promoProductId <= 0 ? $discountPercent : 0;
                        $baseTotal = $quantity > 0 ? ((float)$pack['prix'] * $quantity) : 0;
                        return $baseTotal - ($baseTotal * $lineDiscountPercent / 100);
                    }, $packs))
                ];
                
                $invoiceProducts = [];
                foreach ($products as $product) {
                    $quantity = $cart[$product['id']] ?? 0;
                    if ($quantity >= 1) {
                        $invoiceProducts[] = [
                            'nom' => $product['nom'],
                            'prix' => $produitController->getEffectivePrice($product),
                            'prix_original' => $product['prix'],
                            'quantite' => $quantity
                        ];
                    }
                }
                foreach ($packs as $pack) {
                    $quantity = $cart['pack_' . $pack['id']] ?? 0;
                    if ($quantity >= 1) {
                        $invoiceProducts[] = [
                            'nom' => 'Pack: ' . $pack['nom'],
                            'prix' => $pack['prix'],
                            'prix_original' => $pack['prix'],
                            'quantite' => $quantity
                        ];
                    }
                }
                
                if (!$invoiceService->sendInvoiceEmail($orderDetails, $invoiceProducts)) {
                    error_log("Order invoice delivery failed after checkout. Order ID: " . $lastOrderId . " Email: " . $values['email']);
                }
            } catch (Exception $e) {
                error_log("Error sending invoice email: " . $e->getMessage());
            }
            
            $_SESSION['cart'] = [];
            unset($_SESSION['stripe_pending_order']);
            $cart = [];
            $cartCount = 0;
            $orderComplete = true;
        } else {
            if (empty($errors['general'])) {
                $errors['general'] = 'Impossible d\'enregistrer la commande pour le moment.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Commande - Stabilis&trade;</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/stabilis.css?v=2">
    <link rel="stylesheet" href="../../assets/css/front-style.css?v=2">
    <style>
        .checkout-section { padding: 60px 0; }
        .checkout-container { display: grid; grid-template-columns: 1fr 380px; gap: 30px; }
        .checkout-form, .summary-card { background: var(--bg-elevated); border-radius: var(--radius-lg); padding: 24px; box-shadow: var(--shadow-sm); }
        .form-section { margin-bottom: 24px; }
        .form-section-title { font-size: 18px; font-weight: 600; margin-bottom: 18px; display:flex; align-items:center; gap:10px; }
        .form-group { margin-bottom: 16px; }
        .form-label { display: block; font-size:14px; font-weight:500; margin-bottom:8px; }
        .form-input, .form-textarea, .form-select { width:100%; padding:14px 16px; border:1px solid var(--border-light); border-radius:var(--radius-md); background:var(--bg-elevated); font-size:14px; transition: all 0.2s ease; }
        .form-input.input-valid { border-color: #28a745; box-shadow: 0 0 0 0.2rem rgba(40,167,69,.25); }
        .form-input.input-invalid { border-color: #dc3545; box-shadow: 0 0 0 0.2rem rgba(220,53,69,.25); }
        .form-textarea { min-height: 100px; resize: vertical; }
        .summary-title { font-size:18px; font-weight:600; margin-bottom:20px; }
        .summary-item { display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; }
        .summary-product-info { display:flex; align-items:center; }
        .summary-item img { width:50px; height:50px; object-fit:cover; border-radius: var(--radius-sm); margin-right:12px; }
        .summary-product-name { font-weight:500; }
        .summary-product-qty { font-size:12px; color: #465348; }
        .success-box p { color: #465348; }
        .place-order-btn { background: var(--accent-herb); color:white; border:none; padding:16px 20px; border-radius: var(--radius-full); font-size:16px; cursor:pointer; width:100%; }
        .error-message { color:#C55A4A; font-size:13px; margin-top:6px; }
        .success-box { background:#eaf7eb; color:#235d34; border-radius:14px; padding:24px; text-align:center; }
        .promo-result { padding: 12px; border-radius: var(--radius-md); font-size: 14px; font-weight: 500; }
        .promo-result.valid { background-color: #e8f5e9; color: #1b5e20; border: 1px solid #1b5e20; }
        .promo-result.invalid { background-color: #ffebee; color: #c62828; border: 1px solid #c62828; }
        @media (max-width: 768px) { .checkout-container { grid-template-columns: 1fr; } }
    </style>
</head>
<body class="page-order">
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
    <section class="checkout-section">
        <div class="container">
            <?php if ($orderComplete): ?>
            <div class="success-box">
                <h1>Commande confirmee !</h1>
                <p>Merci <?php echo htmlspecialchars($values['prenom']); ?>, votre commande a bien ete creee.</p>
                <p style="font-size: 14px; color: #666; margin-top: 12px;">
                    Vous recevrez une facture detaillee a l'adresse email : <strong><?php echo htmlspecialchars($values['email']); ?></strong>
                </p>
                <p style="font-size: 14px; color: #666; margin-top: 8px;">
                    Votre commande sera expediee dans 3 a 5 jours
                </p>
                <a href="index.php" class="btn-primary" style="display:inline-flex; width:auto; padding:14px 24px; margin-top:16px;">Retour a la boutique</a>
            </div>
            <?php elseif (empty($products) && empty($packs)): ?>
            <div class="success-box">
                <h1>Panier vide</h1>
                <p>Ajoutez des produits avant de passer commande.</p>
                <a href="shop.php" class="btn-primary" style="display:inline-flex; width:auto; padding:14px 24px; margin-top:16px;">Voir la boutique</a>
            </div>
            <?php else: ?>
            <div style="margin-bottom:32px;">
                <h1 style="font-size:32px; margin:0;">Finaliser ma commande</h1>
                <p style="color:var(--text-secondary);">Completez les informations de livraison pour valider la commande.</p>
            </div>
            <div class="checkout-container">
                <div class="checkout-form">
                    <?php if ($stripeStatusMessage !== ''): ?><div class="promo-result invalid" style="display:block; margin-bottom:16px;"><?php echo htmlspecialchars($stripeStatusMessage); ?></div><?php endif; ?>
                    <?php if (!empty($errors['general'])): ?><div class="error-message"><?php echo htmlspecialchars($errors['general']); ?></div><?php endif; ?>
                    <?php if (!empty($errors['cart'])): ?><div class="error-message"><?php echo htmlspecialchars($errors['cart']); ?></div><?php endif; ?>
                    <form method="POST" novalidate>
                        <div class="form-section">
                            <h2 class="form-section-title"><i class="fas fa-user"></i> Informations client</h2>
                            <div class="form-group"><label class="form-label">Prenom</label><input type="text" name="prenom" class="form-input" data-fieldtype="name" value="<?php echo htmlspecialchars($values['prenom']); ?>"><?php if (!empty($errors['prenom'])): ?><div class="error-message"><?php echo htmlspecialchars($errors['prenom']); ?></div><?php endif; ?></div>
                            <div class="form-group"><label class="form-label">Nom</label><input type="text" name="nom" class="form-input" data-fieldtype="name" value="<?php echo htmlspecialchars($values['nom']); ?>"><?php if (!empty($errors['nom'])): ?><div class="error-message"><?php echo htmlspecialchars($errors['nom']); ?></div><?php endif; ?></div>
                            <div class="form-group"><label class="form-label">Email</label><input type="text" name="email" class="form-input" data-fieldtype="email" value="<?php echo htmlspecialchars($values['email']); ?>"><?php if (!empty($errors['email'])): ?><div class="error-message"><?php echo htmlspecialchars($errors['email']); ?></div><?php endif; ?></div>
                            <div class="form-group"><label class="form-label">Telephone</label><input type="text" name="telephone" class="form-input" data-fieldtype="phone" value="<?php echo htmlspecialchars($values['telephone']); ?>"><?php if (!empty($errors['telephone'])): ?><div class="error-message"><?php echo htmlspecialchars($errors['telephone']); ?></div><?php endif; ?></div>
                        </div>
                        <div class="form-section">
                            <h2 class="form-section-title"><i class="fas fa-map-marker-alt"></i> Adresse de livraison</h2>
                            <div class="form-group"><label class="form-label">Adresse</label><input type="text" name="adresse" class="form-input" value="<?php echo htmlspecialchars($values['adresse']); ?>"><?php if (!empty($errors['adresse'])): ?><div class="error-message"><?php echo htmlspecialchars($errors['adresse']); ?></div><?php endif; ?></div>
                            <div class="form-group"><label class="form-label">Code postal</label><input type="text" name="code_postal" class="form-input" data-fieldtype="postal" value="<?php echo htmlspecialchars($values['code_postal']); ?>"><?php if (!empty($errors['code_postal'])): ?><div class="error-message"><?php echo htmlspecialchars($errors['code_postal']); ?></div><?php endif; ?></div>
                            <div class="form-group"><label class="form-label">Ville</label><input type="text" name="ville" class="form-input" value="<?php echo htmlspecialchars($values['ville']); ?>"><?php if (!empty($errors['ville'])): ?><div class="error-message"><?php echo htmlspecialchars($errors['ville']); ?></div><?php endif; ?></div>
                            <div class="form-group"><label class="form-label">Pays</label><select name="pays" class="form-select"><option value="Tunisie"<?php echo $values['pays'] === 'Tunisie' ? ' selected' : ''; ?>>Tunisie</option></select><?php if (!empty($errors['pays'])): ?><div class="error-message"><?php echo htmlspecialchars($errors['pays']); ?></div><?php endif; ?></div>
                            <div class="form-group"><label class="form-label">Instructions de livraison</label><textarea name="notes" class="form-textarea"><?php echo htmlspecialchars($values['notes']); ?></textarea></div>
                        </div>
                        <div class="form-section">
                            <h2 class="form-section-title"><i class="fas fa-credit-card"></i> Mode de paiement</h2>
                            <div class="form-group"><select name="paiement" class="form-select"><option value="card"<?php echo $values['paiement'] === 'card' ? ' selected' : ''; ?>>Carte bancaire (Stripe)</option><option value="paypal"<?php echo $values['paiement'] === 'paypal' ? ' selected' : ''; ?>>PayPal</option><option value="cash"<?php echo $values['paiement'] === 'cash' ? ' selected' : ''; ?>>Paiement a la livraison</option></select><?php if (!empty($errors['paiement'])): ?><div class="error-message"><?php echo htmlspecialchars($errors['paiement']); ?></div><?php endif; ?></div>
                        </div>
                        <div class="form-section">
                            <h2 class="form-section-title"><i class="fas fa-tag"></i> Code promo</h2>
                            <div class="form-group">
                                <label class="form-label">Avez-vous un code promo?</label>
                                <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                                    <input 
                                        type="text" 
                                        id="promoCode" 
                                        class="form-input" 
                                        placeholder="Entrez votre code promo"
                                        style="flex: 1;"
                                    />
                                    <button 
                                        type="button" 
                                        onclick="validatePromoCode()" 
                                        style="background: var(--accent-herb); color: white; border: none; padding: 14px 20px; border-radius: var(--radius-md); cursor: pointer; white-space: nowrap; font-weight: 500;"
                                    >
                                        Valider
                                    </button>
                                </div>
                                <div id="promoResult" style="display: none; padding: 12px; border-radius: var(--radius-md); margin-bottom: 10px; font-size: 14px;"></div>
                            </div>
                        </div>
                        <input type="hidden" id="hiddenDiscount" name="discount_percent" value="0" />
                        <input type="hidden" id="promoCodeId" name="promo_code_id" value="" />
                        <input type="hidden" id="promoProductId" name="promo_product_id" value="" />
                        <button type="submit" class="place-order-btn">Valider la commande</button>
                    </form>
                </div>
                <div class="summary-card">
                    <div class="summary-title">Recapitulatif de commande</div>
                    <?php foreach ($packs as $pack): $quantity = $cart['pack_' . $pack['id']] ?? 0; $itemTotal = (float)$pack['prix'] * $quantity; ?>
                    <div class="summary-item">
                        <div class="summary-product-info">
                            <img src="/AdminLTE3/dist/img/<?php echo htmlspecialchars($pack['image_url'] ?? 'default-product.png'); ?>" alt="<?php echo htmlspecialchars($pack['nom']); ?>" onerror="this.src='/AdminLTE3/dist/img/default-product.png'">
                            <div>
                                <div class="summary-product-name"><?php echo htmlspecialchars($pack['nom']); ?></div>
                                <div class="summary-product-qty"><?php echo $quantity; ?> x <?php echo number_format((float)$pack['prix'], 2); ?> &euro;</div>
                            </div>
                        </div>
                        <span><?php echo number_format($itemTotal, 2); ?> &euro;</span>
                    </div>
                    <?php endforeach; ?>
                    <?php foreach ($products as $product): $quantity = $cart[$product['id']] ?? 0; $unitPrice = $produitController->getEffectivePrice($product); $itemTotal = $unitPrice * $quantity; ?>
                    <div class="summary-item">
                        <div class="summary-product-info">
                            <img src="/AdminLTE3/dist/img/<?php echo htmlspecialchars($product['image_url'] ?? 'default-product.png'); ?>" alt="<?php echo htmlspecialchars($product['nom']); ?>" onerror="this.src='/AdminLTE3/dist/img/default-product.png'">
                            <div>
                                <div class="summary-product-name"><?php echo htmlspecialchars($product['nom']); ?></div>
                                <div class="summary-product-qty">
                                    <?php echo $quantity; ?> x
                                    <?php if ($produitController->hasProductPromotion($product)): ?>
                                        <span style="text-decoration: line-through; color: var(--text-muted);"><?php echo number_format((float) $product['prix'], 2); ?> &euro;</span>
                                        <strong style="color: #1b5e20;"><?php echo number_format($unitPrice, 2); ?> &euro;</strong>
                                    <?php else: ?>
                                        <?php echo number_format($unitPrice, 2); ?> &euro;
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <span><?php echo number_format($itemTotal, 2); ?> &euro;</span>
                    </div>
                    <?php endforeach; ?>
                    <hr style="margin:18px 0; border-color: var(--border-light);">
                    <div class="summary-item" style="font-weight:600;"><span>Total</span><strong><?php echo number_format($total, 2); ?> &euro;</strong></div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>
    <footer style="background: var(--sidebar-green); color: var(--sidebar-text); padding: 40px 0; margin-top: 60px;">
        <div class="container">
            <div style="text-align:center;">
                <h3 style="color:white; margin-bottom:8px;">Stabilis<sup>&trade;</sup></h3>
                <p style="opacity:0.8; margin-bottom:16px;">Nutrition adaptative &middot; Performance durable</p>
                <div style="opacity:0.6;"><i class="fas fa-seedling"></i> low carbon &middot; high performance</div>
            </div>
        </div>
    </footer>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form');
        const submitBtn = document.querySelector('.place-order-btn');
        const inputs = form.querySelectorAll('.form-input, .form-select, .form-textarea');
        const fieldInputs = form.querySelectorAll('input[data-fieldtype]');
        const cartBadge = document.querySelector('.cart-count');

        fieldInputs.forEach(input => {
            const fieldType = input.dataset.fieldtype;
            
            input.addEventListener('keypress', function(e) {
                const char = String.fromCharCode(e.which || e.keyCode);
                
                if (fieldType === 'name') {
                    if (!/^[A-Za-z\u00C0-\u017F\s'-]$/.test(char)) {
                        e.preventDefault();
                    }
                } else if (fieldType === 'phone' && input.value.length >= 8) {
                    e.preventDefault();
                } else if (fieldType === 'postal' && input.value.length >= 4) {
                    e.preventDefault();
                }
            });
            
            input.addEventListener('input', function() {
                validateField(this);
            });
            
            input.addEventListener('blur', function() {
                validateField(this);
                this.parentElement.classList.remove('focused');
            });
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
        });
        
        inputs.forEach(input => {
            if (!input.dataset.fieldtype) {
                input.addEventListener('blur', function() {
                    validateField(this);
                });
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('focused');
                });
                input.addEventListener('blur', function() {
                    this.parentElement.classList.remove('focused');
                });
            }
        });

        function validateField(field) {
            const value = field.value.trim();
            const fieldType = field.dataset.fieldtype;
            let error = '';

            if (fieldType === 'name' && (!/^[A-Za-z\u00C0-\u017F\s'-]*$/.test(value) || value.length < 2)) {
                error = value.length < 2 ? 'Minimum 2 caracteres requis' : 'Seules les lettres sont autorisees';
            } else if (fieldType === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                error = 'Email invalide (doit contenir @domaine.tld)';
            } else if (fieldType === 'phone' && (!/^\d*$/.test(value) || value.length > 8 || value.length < 8)) {
                error = value.length !== 8 ? '8 chiffres requis' : 'Seuls les chiffres sont autorises';
            } else if (fieldType === 'postal' && (!/^\d*$/.test(value) || value.length !== 4)) {
                error = '4 chiffres requis';
            }

            const errorEl = field.parentElement.querySelector('.error-message');
            if (error) {
                if (!errorEl || errorEl.textContent !== error) {
                    if (errorEl) errorEl.remove();
                    const newError = document.createElement('div');
                    newError.className = 'error-message';
                    newError.textContent = error;
                    field.parentElement.appendChild(newError);
                }
                field.classList.remove('input-valid');
                field.classList.add('input-invalid');
            } else if (field.value.trim()) {
                if (errorEl) errorEl.remove();
                field.classList.remove('input-invalid');
                field.classList.add('input-valid');
            } else {
                if (errorEl) errorEl.remove();
                field.classList.remove('input-invalid', 'input-valid');
            }
        }

        form.addEventListener('submit', function(e) {
            let isValid = true;
            inputs.forEach(input => {
                if (!input.value.trim() && input.hasAttribute('required')) {
                    isValid = false;
                    input.classList.add('input-error');
                }
            });

            if (!isValid) {
                e.preventDefault();
                showToast('Veuillez remplir tous les champs obligatoires', 'error');
                return false;
            }

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="loading-spinner-custom"></span>Validation en cours...';
            submitBtn.style.background = 'var(--accent-herb-soft)';
        });

        <?php if ($orderComplete): ?>
        showToast('Commande enregistree avec succes! Merci pour votre achat.', 'success');
        <?php endif; ?>

        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `toast-notification ${type}`;
            toast.textContent = message;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 4000);
        }

        if (cartBadge) {
            cartBadge.style.animation = 'softPulse 2.2s ease-in-out infinite';
        }

        let appliedDiscount = 0;
        let appliedCodeId = null;

        window.validatePromoCode = function() {
            const code = document.getElementById('promoCode').value.trim();
            const resultDiv = document.getElementById('promoResult');
            const emailInput = form.querySelector('input[name="email"]');
            const customerEmail = emailInput.value.trim();
            
            if (!code) {
                resultDiv.textContent = 'Veuillez entrer un code promo.';
                resultDiv.className = 'promo-result invalid';
                resultDiv.style.display = 'block';
                return;
            }

            if (!customerEmail) {
                resultDiv.textContent = "Veuillez d'abord entrer votre email.";
                resultDiv.className = 'promo-result invalid';
                resultDiv.style.display = 'block';
                return;
            }

                    const cartProductIds = Object.keys(<?php echo json_encode($cart); ?>);
                    const firstProductId = cartProductIds[0] || null;
            
            if (!firstProductId) {
                resultDiv.textContent = 'Erreur: aucun produit trouve.';
                resultDiv.className = 'promo-result invalid';
                resultDiv.style.display = 'block';
                return;
            }

            resultDiv.textContent = 'Validation en cours...';
            resultDiv.className = 'promo-result';
            resultDiv.style.display = 'block';

            fetch('../../Controllers/PromoCodeHandler.php?action=validate_code', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    code: code,
                    product_id: firstProductId,
                    product_ids: cartProductIds.join(','),
                    customer_email: customerEmail
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    appliedDiscount = data.discount || 0;
                    appliedCodeId = data.code_id;
                    const appliedProductId = data.product_id || '';
                    document.getElementById('hiddenDiscount').value = appliedDiscount;
                    document.getElementById('promoCodeId').value = appliedCodeId;
                    document.getElementById('promoProductId').value = appliedProductId;
                    
                    resultDiv.textContent = `${data.message} | Reduction: -${appliedDiscount}%`;
                    resultDiv.className = 'promo-result valid';
                    resultDiv.style.display = 'block';
                    
                    updateCartSummaryWithDiscount(appliedDiscount, appliedProductId);
                } else {
                    appliedDiscount = 0;
                    appliedCodeId = null;
                    document.getElementById('hiddenDiscount').value = 0;
                    document.getElementById('promoCodeId').value = '';
                    document.getElementById('promoProductId').value = '';
                    
                    resultDiv.textContent = `${data.message}`;
                    resultDiv.className = 'promo-result invalid';
                    resultDiv.style.display = 'block';
                    
                    updateCartSummaryWithDiscount(0, '');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                resultDiv.textContent = 'Erreur de communication avec le serveur.';
                resultDiv.className = 'promo-result invalid';
                resultDiv.style.display = 'block';
            });
        };

        window.updateCartSummaryWithDiscount = function(discount, productId) {
            const totalElement = document.querySelector('.summary-item[style*="font-weight"] strong');
            if (totalElement) {
                const baseTotal = <?php echo json_encode($total); ?>;
                const productTotals = <?php echo json_encode(array_reduce($products, function ($carry, $product) use ($cart, $produitController) {
                    $quantity = $cart[$product['id']] ?? 0;
                    $carry[(string)$product['id']] = $produitController->getEffectivePrice($product) * $quantity;
                    return $carry;
                }, [])); ?>;
                const discountBase = productId && productTotals[String(productId)] ? productTotals[String(productId)] : baseTotal;
                const discountAmount = (discountBase * discount) / 100;
                const finalTotal = baseTotal - discountAmount;
                
                if (discount > 0) {
                    totalElement.innerHTML = `${finalTotal.toFixed(2)} EUR <span style="font-size: 12px; color: #1b5e20;">(-${discount}%)</span>`;
                } else {
                    totalElement.textContent = `${baseTotal.toFixed(2)} EUR`;
                }
            }
        };

        document.getElementById('promoCode').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                validatePromoCode();
            }
        });
    });
    </script>
</body>
</html>

