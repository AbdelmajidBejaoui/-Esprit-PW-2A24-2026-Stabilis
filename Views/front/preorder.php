<?php
session_start();
require_once __DIR__ . '/../../controllers/ProduitController.php';
require_once __DIR__ . '/../../controllers/CommandeController.php';
require_once __DIR__ . '/../../models/Commande.php';
require_once __DIR__ . '/../../Services/InvoiceService.php';

$productId = (int)($_GET['id'] ?? $_POST['product_id'] ?? 0);
$produitController = new ProduitController();
$commandeController = new CommandeController();
$product = $productId > 0 ? $produitController->getById($productId) : null;

if (!$product || !$produitController->canPreOrder($product)) {
    header('Location: shop.php');
    exit();
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
    'paiement' => 'cash',
    'quantite' => '1',
];
$errors = [];
$created = false;
$effectivePrice = $produitController->getEffectivePrice($product);
$cartCount = array_sum($_SESSION['cart'] ?? []);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($values as $field => $value) {
        if (isset($_POST[$field])) {
            $values[$field] = trim($_POST[$field]);
        }
    }

    $quantity = max(1, (int)$values['quantite']);
    $values['quantite'] = (string)$quantity;

    if (!$commandeController->validateData($values, $errors)) {
        
    } elseif (!$produitController->canPreOrder($product)) {
        $errors['general'] = 'Ce produit est maintenant disponible. Ajoutez-le au panier pour commander normalement.';
    } else {
        $total = $effectivePrice * $quantity;
        $notes = trim($values['notes']);
        $notes = trim($notes . "\nPRIORITE PRE-COMMANDE: a traiter avant les commandes classiques des que le stock est disponible.");
        $notes = trim($notes . "\nPre-order product: " . $product['nom']);

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
            $notes,
            $values['paiement'],
            $total,
            'pre-order'
        );

        $orderId = $commandeController->createPreOrder($commande);
        if ($orderId !== false) {
            $created = true;
            try {
                $invoiceService = new InvoiceService(Database::getConnection());
                $invoiceService->sendPreOrderInvoiceEmail([
                    'id' => $orderId,
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
                    'statut' => 'pre-order',
                    'is_pre_order' => true,
                    'discount_percent' => 0,
                    'discount_amount' => 0,
                    'final_total' => $total
                ], [[
                    'nom' => $product['nom'],
                    'prix' => $effectivePrice,
                    'prix_original' => $product['prix'],
                    'quantite' => $quantity
                ]]);
            } catch (Exception $e) {
                error_log('Pre-order invoice email failed: ' . $e->getMessage());
            }
        } else {
            $errors['general'] = 'Impossible de creer la pre-commande pour le moment.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pre-commande - Stabilis</title>
    <link rel="stylesheet" href="../../assets/css/stabilis.css?v=2">
    <link rel="stylesheet" href="../../assets/css/front-style.css?v=2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .preorder-wrap { padding: 56px 0; background: var(--bg-surface); }
        .preorder-grid { display: grid; grid-template-columns: 1fr 360px; gap: 28px; }
        .panel { background: white; border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 24px; box-shadow: var(--shadow-sm); }
        .form-group { margin-bottom: 16px; }
        .form-control { width: 100%; padding: 13px 15px; border: 1px solid var(--border-light); border-radius: var(--radius-md); }
        .error-message { color: #C55A4A; font-size: 13px; margin-top: 6px; }
        .success-box { background: #eaf7eb; color: #235d34; border-radius: 14px; padding: 24px; text-align: center; }
        .preorder-badge { display:inline-flex; padding: 7px 12px; border-radius:999px; background:#F9F3E6; color:#8A6425; font-weight:700; font-size:13px; margin-bottom:14px; }
        @media (max-width: 768px) { .preorder-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body class="page-order">
    <div class="top-bar"><div class="container"><div class="top-bar-item"><i class="fas fa-envelope"></i><span>stabilisatyourservice@gmail.com</span></div><div class="top-bar-item"><i class="fas fa-box"></i><span>Pre-commandes ouvertes</span></div></div></div>
    <nav class="navbar"><div class="container"><a href="index.php" class="navbar-brand">Stabilis<sup>&trade;</sup></a><ul class="navbar-nav"><li><a href="index.php">Accueil</a></li><li><a href="shop.php">Boutique</a></li><li><a href="cart.php"><i class="fas fa-shopping-cart"></i><?php if($cartCount > 0): ?><span class="cart-badge"><span class="cart-count"><?php echo $cartCount; ?></span></span><?php endif; ?></a></li><li><a href="../../Views/back/produits/liste.php" class="admin-link"><i class="fas fa-cog"></i> Administration</a></li></ul></div></nav>
    <main class="preorder-wrap">
        <div class="container">
            <?php if ($created): ?>
                <div class="success-box">
                    <h1>Pre-commande creee</h1>
                    <p>Merci <?php echo htmlspecialchars($values['prenom']); ?>. Nous vous contacterons lorsque <?php echo htmlspecialchars($product['nom']); ?> sera disponible.</p>
                    <p style="font-size: 14px; color: #466052; margin-top: 12px;">Une facture de pre-commande a ete envoyee a <strong><?php echo htmlspecialchars($values['email']); ?></strong>.</p>
                    <p style="font-size: 14px; color: #466052; margin-top: 8px;">Cette commande est marquee prioritaire pour le back office.</p>
                    <a href="shop.php" class="btn-primary" style="display:inline-flex; width:auto; padding:14px 24px; margin-top:16px;">Retour boutique</a>
                </div>
            <?php else: ?>
                <div style="margin-bottom:28px;">
                    <span class="preorder-badge">Pre-order</span>
                    <h1 style="margin:0;">Pre-commander <?php echo htmlspecialchars($product['nom']); ?></h1>
                    <p style="color:var(--text-secondary);">Votre demande sera traitee en priorite des que le stock sera disponible.</p>
                </div>
                <div class="preorder-grid">
                    <form method="POST" class="panel" novalidate>
                        <?php if (!empty($errors['general'])): ?><div class="error-message"><?php echo htmlspecialchars($errors['general']); ?></div><?php endif; ?>
                        <input type="hidden" name="product_id" value="<?php echo (int)$product['id']; ?>">
                        <div class="form-group"><label>Prenom</label><input class="form-control" name="prenom" value="<?php echo htmlspecialchars($values['prenom']); ?>"><?php if (!empty($errors['prenom'])): ?><div class="error-message"><?php echo htmlspecialchars($errors['prenom']); ?></div><?php endif; ?></div>
                        <div class="form-group"><label>Nom</label><input class="form-control" name="nom" value="<?php echo htmlspecialchars($values['nom']); ?>"><?php if (!empty($errors['nom'])): ?><div class="error-message"><?php echo htmlspecialchars($errors['nom']); ?></div><?php endif; ?></div>
                        <div class="form-group"><label>Email</label><input class="form-control" name="email" value="<?php echo htmlspecialchars($values['email']); ?>"><?php if (!empty($errors['email'])): ?><div class="error-message"><?php echo htmlspecialchars($errors['email']); ?></div><?php endif; ?></div>
                        <div class="form-group"><label>Telephone</label><input class="form-control" name="telephone" value="<?php echo htmlspecialchars($values['telephone']); ?>"><?php if (!empty($errors['telephone'])): ?><div class="error-message"><?php echo htmlspecialchars($errors['telephone']); ?></div><?php endif; ?></div>
                        <div class="form-group"><label>Adresse</label><input class="form-control" name="adresse" value="<?php echo htmlspecialchars($values['adresse']); ?>"><?php if (!empty($errors['adresse'])): ?><div class="error-message"><?php echo htmlspecialchars($errors['adresse']); ?></div><?php endif; ?></div>
                        <div class="form-group"><label>Code postal</label><input class="form-control" name="code_postal" value="<?php echo htmlspecialchars($values['code_postal']); ?>"><?php if (!empty($errors['code_postal'])): ?><div class="error-message"><?php echo htmlspecialchars($errors['code_postal']); ?></div><?php endif; ?></div>
                        <div class="form-group"><label>Ville</label><input class="form-control" name="ville" value="<?php echo htmlspecialchars($values['ville']); ?>"><?php if (!empty($errors['ville'])): ?><div class="error-message"><?php echo htmlspecialchars($errors['ville']); ?></div><?php endif; ?></div>
                        <input type="hidden" name="pays" value="Tunisie">
                        <div class="form-group"><label>Quantite</label><input class="form-control" name="quantite" inputmode="numeric" value="<?php echo htmlspecialchars($values['quantite']); ?>"></div>
                        <div class="form-group"><label>Paiement</label><select class="form-control" name="paiement"><option value="cash"<?php echo $values['paiement'] === 'cash' ? ' selected' : ''; ?>>Paiement a la livraison</option><option value="card"<?php echo $values['paiement'] === 'card' ? ' selected' : ''; ?>>Carte bancaire</option><option value="paypal"<?php echo $values['paiement'] === 'paypal' ? ' selected' : ''; ?>>PayPal</option></select></div>
                        <div class="form-group"><label>Notes</label><textarea class="form-control" name="notes" rows="3"><?php echo htmlspecialchars($values['notes']); ?></textarea></div>
                        <button type="submit" class="btn-primary" style="width:100%;">Confirmer la pre-commande</button>
                    </form>
                    <aside class="panel">
                        <img src="/AdminLTE3/dist/img/<?php echo htmlspecialchars($product['image_url'] ?? 'default-product.png'); ?>" alt="<?php echo htmlspecialchars($product['nom']); ?>" style="width:100%; height:220px; object-fit:cover; border-radius:12px;" onerror="this.src='/AdminLTE3/dist/img/default-product.png'">
                        <h3 style="margin:18px 0 8px;"><?php echo htmlspecialchars($product['nom']); ?></h3>
                        <p style="color:var(--text-secondary);"><?php echo htmlspecialchars($product['categorie']); ?></p>
                        <div style="font-size:24px; font-weight:700; margin:18px 0;"><?php echo number_format($effectivePrice, 2); ?> EUR</div>
                        <p style="color:#8A6425;">Statut: <?php echo (int)($product['coming_soon'] ?? 0) === 1 ? 'Coming soon' : 'Rupture de stock'; ?></p>
                    </aside>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
