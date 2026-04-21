<?php
session_start();
require_once __DIR__ . '/../../controllers/ProduitController.php';
require_once __DIR__ . '/../../controllers/CommandeController.php';

$produitController = new ProduitController();
$commandeController = new CommandeController();
$cart = $_SESSION['cart'] ?? [];
$cartCount = array_sum($cart);
$products = [];
$total = 0;

if (!empty($cart)) {
    $products = $produitController->getByIds(array_keys($cart));
    foreach ($products as $product) {
        $quantity = $cart[$product['id']] ?? 0;
        $total += (float) $product['prix'] * $quantity;
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($values as $field => $value) {
        if (isset($_POST[$field])) {
            $values[$field] = trim($_POST[$field]);
        }
    }

    if (empty($cart)) {
        $errors['cart'] = 'Votre panier est vide. Ajoutez des produits avant de valider la commande.';
    }

    if ($commandeController->validateData($values, $errors) && empty($errors['cart'])) {
        $created = false;
        foreach ($products as $product) {
            $quantity = $cart[$product['id']] ?? 0;
            if ($quantity < 1) {
                continue;
            }
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
                (float) $product['prix'] * $quantity,
                'En attente'
            );
            if ($commandeController->add($commande) !== false) {
                $created = true;
            }
        }
        if ($created) {
            $_SESSION['cart'] = [];
            $cart = [];
            $cartCount = 0;
            $orderComplete = true;
        } else {
            $errors['general'] = 'Impossible d\'enregistrer la commande pour le moment.';
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
        .summary-product-qty { font-size:12px; color: var(--text-muted); }
        .place-order-btn { background: var(--accent-herb); color:white; border:none; padding:16px 20px; border-radius: var(--radius-full); font-size:16px; cursor:pointer; width:100%; }
        .error-message { color:#C55A4A; font-size:13px; margin-top:6px; }
        .success-box { background:#eaf7eb; color:#235d34; border-radius:14px; padding:24px; text-align:center; }
        @media (max-width: 768px) { .checkout-container { grid-template-columns: 1fr; } }
    </style>
</head>
<body class="page-order">
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
                <li><a href="cart.php" class="active-nav"><i class="fas fa-shopping-cart"></i><?php if($cartCount > 0): ?><span class="cart-badge"><span class="cart-count"><?php echo $cartCount; ?></span></span><?php endif; ?></a></li>
                <li><a href="../../Views/back/produits/liste.php" class="admin-link"><i class="fas fa-cog"></i> Administration</a></li>
            </ul>
        </div>
    </nav>
    <section class="checkout-section">
        <div class="container">
            <?php if ($orderComplete): ?>
            <div class="success-box">
                <h1>Commande enregistrée</h1>
                <p>Merci <?php echo htmlspecialchars($values['prenom']); ?>, votre commande a bien été créée.</p>
                <a href="index.php" class="btn-primary" style="display:inline-flex; width:auto; padding:14px 24px; margin-top:16px;">Retour à la boutique</a>
            </div>
            <?php elseif (empty($products)): ?>
            <div class="success-box">
                <h1>Panier vide</h1>
                <p>Ajoutez des produits avant de passer commande.</p>
                <a href="shop.php" class="btn-primary" style="display:inline-flex; width:auto; padding:14px 24px; margin-top:16px;">Voir la boutique</a>
            </div>
            <?php else: ?>
            <div style="margin-bottom:32px;">
                <h1 style="font-size:32px; margin:0;">Finaliser ma commande</h1>
                <p style="color:var(--text-secondary);">Complétez les informations de livraison pour valider la commande.</p>
            </div>
            <div class="checkout-container">
                <div class="checkout-form">
                    <?php if (!empty($errors['general'])): ?><div class="error-message"><?php echo htmlspecialchars($errors['general']); ?></div><?php endif; ?>
                    <?php if (!empty($errors['cart'])): ?><div class="error-message"><?php echo htmlspecialchars($errors['cart']); ?></div><?php endif; ?>
                    <form method="POST" novalidate>
                        <div class="form-section">
                            <h2 class="form-section-title"><i class="fas fa-user"></i> Informations client</h2>
                            <div class="form-group"><label class="form-label">Prénom</label><input type="text" name="prenom" class="form-input" data-fieldtype="name" value="<?php echo htmlspecialchars($values['prenom']); ?>"><?php if (!empty($errors['prenom'])): ?><div class="error-message"><?php echo htmlspecialchars($errors['prenom']); ?></div><?php endif; ?></div>
                            <div class="form-group"><label class="form-label">Nom</label><input type="text" name="nom" class="form-input" data-fieldtype="name" value="<?php echo htmlspecialchars($values['nom']); ?>"><?php if (!empty($errors['nom'])): ?><div class="error-message"><?php echo htmlspecialchars($errors['nom']); ?></div><?php endif; ?></div>
                            <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" class="form-input" data-fieldtype="email" value="<?php echo htmlspecialchars($values['email']); ?>"><?php if (!empty($errors['email'])): ?><div class="error-message"><?php echo htmlspecialchars($errors['email']); ?></div><?php endif; ?></div>
                            <div class="form-group"><label class="form-label">Téléphone</label><input type="text" name="telephone" class="form-input" data-fieldtype="phone" value="<?php echo htmlspecialchars($values['telephone']); ?>"><?php if (!empty($errors['telephone'])): ?><div class="error-message"><?php echo htmlspecialchars($errors['telephone']); ?></div><?php endif; ?></div>
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
                            <div class="form-group"><select name="paiement" class="form-select"><option value="card"<?php echo $values['paiement'] === 'card' ? ' selected' : ''; ?>>Carte bancaire</option><option value="paypal"<?php echo $values['paiement'] === 'paypal' ? ' selected' : ''; ?>>PayPal</option><option value="cash"<?php echo $values['paiement'] === 'cash' ? ' selected' : ''; ?>>Paiement à la livraison</option></select><?php if (!empty($errors['paiement'])): ?><div class="error-message"><?php echo htmlspecialchars($errors['paiement']); ?></div><?php endif; ?></div>
                        </div>
                        <button type="submit" class="place-order-btn">Valider la commande</button>
                    </form>
                </div>
                <div class="summary-card">
                    <div class="summary-title">Récapitulatif de commande</div>
                    <?php foreach ($products as $product): $quantity = $cart[$product['id']] ?? 0; $itemTotal = (float) $product['prix'] * $quantity; ?>
                    <div class="summary-item">
                        <div class="summary-product-info">
                            <img src="/AdminLTE3/dist/img/<?php echo htmlspecialchars($product['image_url'] ?? 'default-product.png'); ?>" alt="<?php echo htmlspecialchars($product['nom']); ?>" onerror="this.src='/AdminLTE3/dist/img/default-product.png'">
                            <div>
                                <div class="summary-product-name"><?php echo htmlspecialchars($product['nom']); ?></div>
                                <div class="summary-product-qty"><?php echo $quantity; ?> × <?php echo number_format((float) $product['prix'], 2); ?> &euro;</div>
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
                <p style="opacity:0.8; margin-bottom:16px;">Nutrition adaptative · Performance durable</p>
                <div style="opacity:0.6;"><i class="fas fa-seedling"></i> low carbon · high performance</div>
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

        // Real-time validation & focus effects
        // Real-time input blocking and validation
        fieldInputs.forEach(input => {
            const fieldType = input.dataset.fieldtype;
            
            // Block invalid keystrokes
            input.addEventListener('keypress', function(e) {
                const char = String.fromCharCode(e.which || e.keyCode);
                
                if (fieldType === 'name') {
                    // Only letters, spaces, and accented characters (French)
                    if (!/^[a-zA-ZàâäéèêëîïôöùûüÿçÀÂÄÉÈÊËÎÏÔÖÙÛÜŸÇ\s]$/.test(char)) {
                        e.preventDefault();
                    }
                } else if (fieldType === 'phone' && input.value.length >= 8) {
                    e.preventDefault(); // Max 8 digits
                } else if (fieldType === 'postal' && input.value.length >= 4) {
                    e.preventDefault(); // Max 4 digits
                }
            });
            
            // Real-time validation on input
            input.addEventListener('input', function() {
                validateField(this);
            });
            
            // Existing blur/focus
            input.addEventListener('blur', function() {
                validateField(this);
                this.parentElement.classList.remove('focused');
            });
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
        });
        
        // Apply to other inputs too
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

            if (fieldType === 'name' && (!/^[a-zA-ZàâäéèêëîïôöùûüÿçÀÂÄÉÈÊËÎÏÔÖÙÛÜŸÇ\s]*$/.test(value) || value.length < 2)) {
                error = value.length < 2 ? 'Minimum 2 caractères requis' : 'Seules les lettres sont autorisées';
            } else if (fieldType === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                error = 'Email invalide (doit contenir @domaine.tld)';
            } else if (fieldType === 'phone' && (!/^\d*$/.test(value) || value.length > 8 || value.length < 8)) {
                error = value.length !== 8 ? '8 chiffres requis' : 'Seuls les chiffres sont autorisés';
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

        // Form submit with loading
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

            // Loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="loading-spinner-custom"></span>Validation en cours...';
            submitBtn.style.background = 'var(--accent-herb-soft)';
        });

        // Success toast (server-side handled)
        <?php if ($orderComplete): ?>
        showToast('Commande enregistrée avec succès! Merci pour votre achat.', 'success');
        <?php endif; ?>

        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `toast-notification ${type}`;
            toast.textContent = message;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 4000);
        }

        // Cart badge animation
        if (cartBadge) {
            cartBadge.style.animation = 'softPulse 2.2s ease-in-out infinite';
        }
    });
    </script>
</body>
</html>
