<?php
session_start();
require_once __DIR__ . '/../../controllers/PackController.php';
require_once __DIR__ . '/../../controllers/CommandeController.php';
require_once __DIR__ . '/../../models/Commande.php';
require_once __DIR__ . '/../../Services/InvoiceService.php';
require_once __DIR__ . '/../../config/database.php';

$packId = (int)($_GET['id'] ?? $_POST['pack_id'] ?? 0);
$packController = new PackController();
$commandeController = new CommandeController();
$pack = $packId > 0 ? $packController->getById($packId) : null;

if (!$pack || (int)$pack['active'] !== 1 || !$packController->canPreOrderPack($pack)) {
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
        
    } elseif (!$packController->canPreOrderPack($pack)) {
        $errors['general'] = 'Ce pack est maintenant disponible. Ajoutez-le au panier pour commander normalement.';
    } else {
        $firstProductId = $packController->getFirstProductId($pack);
        if ($firstProductId <= 0) {
            $errors['general'] = 'Ce pack ne contient aucun produit valide.';
        } else {
            $total = (float)$pack['prix'] * $quantity;
            $notes = trim($values['notes']);
            $notes = trim($notes . "\nPRIORITE PRE-COMMANDE: pack a traiter avant les commandes classiques des que le stock est disponible.");
            $notes = trim($notes . "\nPACK_PREORDER_ID: " . (int)$pack['id']);
            $notes = trim($notes . "\nPre-order pack: " . $pack['nom']);

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
                $notes,
                $values['paiement'],
                $total,
                'pre-order'
            );

            $orderId = $commandeController->createPreOrder($commande);
            if ($orderId !== false) {
                $created = true;
                try {
                    $invoiceProducts = [[
                        'nom' => 'Pack: ' . $pack['nom'],
                        'prix' => (float)$pack['prix'],
                        'prix_original' => (float)$pack['prix'],
                        'quantite' => $quantity
                    ]];
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
                    ], $invoiceProducts);
                } catch (Exception $e) {
                    error_log('Pack pre-order invoice email failed: ' . $e->getMessage());
                }
            } else {
                $errors['general'] = 'Impossible de creer la pre-commande du pack pour le moment.';
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
    <title>Pre-commande pack - Stabilis</title>
    <link rel="stylesheet" href="../../assets/css/stabilis.css?v=5">
    <link rel="stylesheet" href="../../assets/css/front-style.css?v=7">
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
    <nav class="navbar"><div class="container"><a href="index.php" class="navbar-brand">Stabilis<sup>&trade;</sup></a><ul class="navbar-nav"><li><a href="index.php">Accueil</a></li><li><a href="shop.php">Boutique</a></li><li><a href="cart.php"><i class="fas fa-shopping-cart"></i><?php if($cartCount > 0): ?><span class="cart-badge"><span class="cart-count"><?php echo $cartCount; ?></span></span><?php endif; ?></a></li></ul></div></nav>
    <main class="preorder-wrap">
        <div class="container">
            <?php if ($created): ?>
                <div class="success-box">
                    <h1>Pre-commande pack creee</h1>
                    <p>Merci <?php echo htmlspecialchars($values['prenom']); ?>. Votre pack <?php echo htmlspecialchars($pack['nom']); ?> sera traite en priorite.</p>
                    <p style="font-size:14px; color:#466052; margin-top:12px;">Une facture de pre-commande a ete envoyee a <strong><?php echo htmlspecialchars($values['email']); ?></strong>.</p>
                    <a href="shop.php" class="btn-primary" style="display:inline-flex; width:auto; padding:14px 24px; margin-top:16px;">Retour boutique</a>
                </div>
            <?php else: ?>
                <div style="margin-bottom:28px;">
                    <span class="preorder-badge">Pre-order pack</span>
                    <h1 style="margin:0;">Pre-commander <?php echo htmlspecialchars($pack['nom']); ?></h1>
                    <p style="color:var(--text-secondary);">Votre pack sera prioritaire des que tous les produits inclus seront disponibles.</p>
                </div>
                <div class="preorder-grid">
                    <form method="POST" class="panel" novalidate>
                        <?php if (!empty($errors['general'])): ?><div class="error-message"><?php echo htmlspecialchars($errors['general']); ?></div><?php endif; ?>
                        <input type="hidden" name="pack_id" value="<?php echo (int)$pack['id']; ?>">
                        <div class="form-group"><label>Prenom</label><input class="form-control" name="prenom" value="<?php echo htmlspecialchars($values['prenom']); ?>"><?php if (!empty($errors['prenom'])): ?><div class="error-message"><?php echo htmlspecialchars($errors['prenom']); ?></div><?php endif; ?></div>
                        <div class="form-group"><label>Nom</label><input class="form-control" name="nom" value="<?php echo htmlspecialchars($values['nom']); ?>"><?php if (!empty($errors['nom'])): ?><div class="error-message"><?php echo htmlspecialchars($errors['nom']); ?></div><?php endif; ?></div>
                        <div class="form-group"><label>Email</label><input class="form-control" name="email" value="<?php echo htmlspecialchars($values['email']); ?>"><?php if (!empty($errors['email'])): ?><div class="error-message"><?php echo htmlspecialchars($errors['email']); ?></div><?php endif; ?></div>
                        <div class="form-group"><label>Telephone</label><input class="form-control" name="telephone" value="<?php echo htmlspecialchars($values['telephone']); ?>"><?php if (!empty($errors['telephone'])): ?><div class="error-message"><?php echo htmlspecialchars($errors['telephone']); ?></div><?php endif; ?></div>
                        <div class="form-group"><label>Adresse</label><input class="form-control" name="adresse" value="<?php echo htmlspecialchars($values['adresse']); ?>"><?php if (!empty($errors['adresse'])): ?><div class="error-message"><?php echo htmlspecialchars($errors['adresse']); ?></div><?php endif; ?></div>
                        <div class="form-group"><label>Code postal</label><input class="form-control" name="code_postal" value="<?php echo htmlspecialchars($values['code_postal']); ?>"><?php if (!empty($errors['code_postal'])): ?><div class="error-message"><?php echo htmlspecialchars($errors['code_postal']); ?></div><?php endif; ?></div>
                        <div class="form-group"><label>Ville</label><input class="form-control" name="ville" value="<?php echo htmlspecialchars($values['ville']); ?>"><?php if (!empty($errors['ville'])): ?><div class="error-message"><?php echo htmlspecialchars($errors['ville']); ?></div><?php endif; ?></div>
                        <input type="hidden" name="pays" value="Tunisie">
                        <div class="form-group"><label>Quantite de packs</label><input class="form-control" name="quantite" inputmode="numeric" value="<?php echo htmlspecialchars($values['quantite']); ?>"></div>
                        <div class="form-group"><label>Paiement</label><select class="form-control" name="paiement"><option value="cash"<?php echo $values['paiement'] === 'cash' ? ' selected' : ''; ?>>Paiement a la livraison</option><option value="card"<?php echo $values['paiement'] === 'card' ? ' selected' : ''; ?>>Carte bancaire</option><option value="paypal"<?php echo $values['paiement'] === 'paypal' ? ' selected' : ''; ?>>PayPal</option></select></div>
                        <div class="form-group"><label>Notes</label><textarea class="form-control" name="notes" rows="3"><?php echo htmlspecialchars($values['notes']); ?></textarea></div>
                        <button type="submit" class="btn-primary" style="width:100%;">Confirmer la pre-commande pack</button>
                    </form>
                    <aside class="panel">
                        <h3 style="margin:0 0 12px;"><?php echo htmlspecialchars($pack['nom']); ?></h3>
                        <div style="font-size:24px; font-weight:700; margin:18px 0;"><?php echo number_format((float)$pack['prix'], 2); ?> EUR</div>
                        <?php foreach ($pack['items'] as $item): ?>
                            <p style="color:var(--text-secondary); margin:8px 0;"><?php echo htmlspecialchars($item['nom']); ?> x<?php echo (int)$item['quantite']; ?> | stock <?php echo (int)$item['stock']; ?></p>
                        <?php endforeach; ?>
                    </aside>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
