<?php
session_start();
require_once __DIR__ . '/../../controllers/ProduitController.php';
require_once __DIR__ . '/../../Services/GeminiRecommendationService.php';

$id = $_GET['id'] ?? null;
$controller = new ProduitController();
$product = $id ? $controller->getById($id) : null;
if (!$product) {
    header('Location: shop.php');
    exit();
}
$effectivePrice = $controller->getEffectivePrice($product);
$canPreOrder = $controller->canPreOrder($product);
$cartCount = array_sum($_SESSION['cart'] ?? []);
$availableRecommendationProducts = $controller->getAvailableProductsForRecommendations($product['id']);
$recommendationSource = 'fallback';
$recommendationError = '';
$recommendationPromptExample = '';
$recommendedProducts = [];

try {
    $mailConfig = require __DIR__ . '/../../config/mail.php';
    $recommendationService = new GeminiRecommendationService($mailConfig['gemini_api_key'] ?? getenv('GEMINI_API_KEY'));
    $recommendedIds = $recommendationService->recommend($product, $availableRecommendationProducts, 4);
    $recommendationPromptExample = $recommendationService->getLastPrompt();
    $recommendationError = $recommendationService->getLastError();

    if (!empty($recommendedIds)) {
        $productsById = [];
        foreach ($controller->getByIds($recommendedIds) as $recommendedProduct) {
            $productsById[(int)$recommendedProduct['id']] = $recommendedProduct;
        }
        foreach ($recommendedIds as $recommendedId) {
            if (isset($productsById[(int)$recommendedId])) {
                $recommendedProducts[] = $productsById[(int)$recommendedId];
            }
        }
        $recommendationSource = 'gemini';
    }
} catch (Exception $e) {
    $recommendationError = $e->getMessage();
}

if (count($recommendedProducts) < 3) {
    $recommendedProducts = $controller->getFallbackRecommendations($product, $availableRecommendationProducts, 4);
    $recommendationSource = 'fallback';
}
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
    <link rel="stylesheet" href="../../assets/css/stabilis.css?v=6">
    <link rel="stylesheet" href="../../assets/css/front-style.css?v=8">
    <style>
        .review-box,
        .notify-box {
            margin-top: 28px;
            padding: 22px;
            border: 1px solid #d9eadf;
            border-radius: 12px;
            background: #f3faf5;
        }
        .notify-box {
            margin: 18px 0 24px;
        }
        .review-box h2,
        .notify-box h2 {
            font-size: 20px;
            margin: 0 0 14px;
            color: #1A4D3A;
        }
        .notify-box p {
            margin: 0 0 16px;
            color: #51675a;
            line-height: 1.5;
        }
        .review-box .form-group,
        .notify-box .form-group {
            margin-bottom: 14px;
        }
        .review-box label,
        .notify-box label {
            display: block;
            margin-bottom: 6px;
            color: #1A4D3A;
            font-weight: 600;
            font-size: 14px;
        }
        .review-box .form-control,
        .notify-box .form-control {
            width: 100%;
            border: 1px solid #c9dfd0;
            border-radius: 8px;
            padding: 12px 14px;
            background: white;
            color: #24362b;
        }
        .review-box .form-control:focus,
        .notify-box .form-control:focus {
            outline: none;
            border-color: #3A6B4B;
            box-shadow: 0 0 0 3px rgba(58, 107, 75, 0.12);
        }
        .review-box .btn-review,
        .notify-box .btn-notify {
            border: none;
            border-radius: 999px;
            background: #1A4D3A;
            color: white;
            padding: 12px 20px;
            font-weight: 700;
            cursor: pointer;
        }
        .review-box .btn-review:hover,
        .notify-box .btn-notify:hover {
            background: #3A6B4B;
        }
    </style>
</head>
<body class="page-product">
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
                    <div class="stock-indicator <?php echo (!$canPreOrder && (int) $product['stock'] > 0) ? 'in-stock' : 'out-of-stock'; ?>">
                        <i class="fas fa-<?php echo (!$canPreOrder && (int) $product['stock'] > 0) ? 'check-circle' : 'clock'; ?>"></i>
                        <?php
                        if ((int)($product['coming_soon'] ?? 0) === 1) {
                            echo 'Coming soon - pre-commande ouverte';
                        } elseif ((int)$product['stock'] > 0) {
                            echo 'En stock (' . (int)$product['stock'] . ' unit&eacute;s)';
                        } else {
                            echo 'Rupture de stock - pre-commande ouverte';
                        }
                        ?>
                    </div>
                    <div class="product-price">
                        <?php if ($controller->hasProductPromotion($product)): ?>
                            <span style="text-decoration: line-through; color: var(--text-muted); font-size: 18px; margin-right: 10px;"><?php echo number_format((float) $product['prix'], 2); ?> &euro;</span>
                            <span style="color: #1b5e20;"><?php echo number_format($effectivePrice, 2); ?> &euro;</span>
                        <?php else: ?>
                            <?php echo number_format($effectivePrice, 2); ?> &euro;
                        <?php endif; ?>
                    </div>
                    <div class="product-description">
                        <?php echo !empty($product['description']) ? nl2br(htmlspecialchars($product['description'])) : 'D&eacute;couvrez notre compl&eacute;ment nutritionnel premium pour optimiser vos performances sportives.'; ?>
                    </div>
                    <div class="product-meta" data-reveal="up">
                        <div class="meta-item"><span class="meta-label">Cat&eacute;gorie</span><span class="meta-value"><?php echo htmlspecialchars($product['categorie']); ?></span></div>
                        <div class="meta-item"><span class="meta-label">Stock disponible</span><span class="meta-value"><?php echo (int) $product['stock']; ?> unit&eacute;s</span></div>
                        <div class="meta-item"><span class="meta-label">Livraison</span><span class="meta-value">Sous 3-5 jours</span></div>
                    </div>
                    <?php if (!$canPreOrder && (int) $product['stock'] > 0): ?>
                    <form method="POST" action="cart.php" style="margin-bottom: 24px;">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="product_id" value="<?php echo (int) $product['id']; ?>">
                        <div class="quantity-selector">
                            <span style="font-weight:500; color:var(--text-primary);">Quantit&eacute; :</span>
                            <div class="quantity-controls">
                                <button type="button" class="quantity-btn" onclick="changeQuantity(-1)">-</button>
                                <input type="text" name="quantity" value="1" class="quantity-input" id="quantity" inputmode="numeric" data-lower="1" data-upper="<?php echo (int)$product['stock']; ?>">
                                <button type="button" class="quantity-btn" onclick="changeQuantity(1)">+</button>
                            </div>
                        </div>
                        <div class="action-buttons" data-reveal="up">
                            <button type="submit" class="btn-add-cart"><i class="fas fa-cart-plus"></i> Ajouter au panier</button>
                        </div>
                    </form>
                    <?php else: ?>
                    <div class="notify-box">
                        <h2>Pre-commander ce produit</h2>
                        <p>Ce produit n'est pas disponible immediatement. Creez une pre-commande et vous serez prioritaire des que le stock arrive.</p>
                        <a href="preorder.php?id=<?php echo (int)$product['id']; ?>" class="btn-notify" style="display:inline-flex; text-decoration:none; margin-bottom:16px;">
                            <i class="fas fa-clock" style="margin-right:8px;"></i> Pre-commander
                        </a>
                        <h2 style="font-size:16px; margin-top:14px;">Ou me prevenir du retour en stock</h2>
                        <p>Laissez vos informations et nous vous enverrons un email des que ce produit sera disponible.</p>
                        <?php if (($_GET['notify'] ?? '') === 'sent'): ?>
                            <div class="stock-indicator in-stock">Votre demande a ete enregistree.</div>
                        <?php elseif (($_GET['notify'] ?? '') === 'exists'): ?>
                            <div class="stock-indicator in-stock">Vous etes deja inscrit pour ce produit.</div>
                        <?php elseif (($_GET['notify'] ?? '') === 'invalid'): ?>
                            <div class="stock-indicator out-of-stock">Veuillez verifier votre nom et votre email.</div>
                        <?php elseif (($_GET['notify'] ?? '') === 'error'): ?>
                            <div class="stock-indicator out-of-stock">Impossible d'enregistrer la demande pour le moment.</div>
                        <?php endif; ?>
                        <form method="POST" action="../../Controllers/WishlistHandler.php" id="notifyForm" novalidate>
                            <input type="hidden" name="product_id" value="<?php echo (int)$product['id']; ?>">
                            <div class="form-group">
                                <label>Nom</label>
                                <input type="text" name="name" class="form-control" id="notifyName">
                                <div class="error-message" id="notifyNameError"></div>
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="text" name="email" class="form-control" id="notifyEmail">
                                <div class="error-message" id="notifyEmailError"></div>
                            </div>
                            <button type="submit" class="btn-notify">Notify me when available</button>
                        </form>
                    </div>
                    <?php endif; ?>
                    <div class="action-buttons" data-reveal="up">
                        <a href="shop.php" class="btn-back"><i class="fas fa-arrow-left"></i> Retour &agrave; la boutique</a>
                    </div>
                    <div class="review-box">
                        <h2>Avis produit</h2>
                        <?php if (($_GET['review'] ?? '') === 'sent'): ?>
                            <div class="stock-indicator in-stock">Avis envoye a l'administration.</div>
                        <?php elseif (($_GET['review'] ?? '') === 'invalid'): ?>
                            <div class="stock-indicator out-of-stock">Veuillez verifier votre avis.</div>
                        <?php elseif (($_GET['review'] ?? '') === 'mail_error'): ?>
                            <div class="stock-indicator out-of-stock">L'avis est valide, mais l'email n'a pas pu etre envoye.</div>
                        <?php endif; ?>
                        <form method="POST" action="../../Controllers/ReviewHandler.php" id="reviewForm" novalidate>
                            <input type="hidden" name="product_id" value="<?php echo (int)$product['id']; ?>">
                            <div class="form-group">
                                <label>Nom</label>
                                <input type="text" name="name" class="form-control" id="reviewName">
                                <div class="error-message" id="reviewNameError"></div>
                            </div>
                            <div class="form-group">
                                <label>Note (1 a 5)</label>
                                <input type="text" name="rating" class="form-control" id="reviewRating" inputmode="numeric">
                                <div class="error-message" id="reviewRatingError"></div>
                            </div>
                            <div class="form-group">
                                <label>Commentaire</label>
                                <textarea name="comment" class="form-control" id="reviewComment" rows="3"></textarea>
                                <div class="error-message" id="reviewCommentError"></div>
                            </div>
                            <button type="submit" class="btn-review">Envoyer l'avis</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php if (!empty($recommendedProducts)): ?>
    <section class="ai-recommendations">
        <div class="container">
            <div class="recommendations-header" data-reveal="up">
                <div>
                    <span class="recommendations-kicker">AI recommendations</span>
                    <h2>You may also like</h2>
                </div>
                <?php if ($recommendationSource === 'fallback'): ?>
                    <span class="recommendations-note">Suggestions similaires</span>
                <?php endif; ?>
            </div>
            <div class="recommendations-grid">
                <?php foreach ($recommendedProducts as $recommendedProduct): ?>
                    <?php
                    $recommendedEffectivePrice = $controller->getEffectivePrice($recommendedProduct);
                    $recommendedCanPreOrder = $controller->canPreOrder($recommendedProduct);
                    ?>
                    <div class="recommendation-card" data-reveal="zoom">
                        <a href="product.php?id=<?php echo (int)$recommendedProduct['id']; ?>" class="recommendation-image-link">
                            <img src="/AdminLTE3/dist/img/<?php echo htmlspecialchars($recommendedProduct['image_url'] ?? 'default-product.png'); ?>" alt="<?php echo htmlspecialchars($recommendedProduct['nom']); ?>" onerror="this.src='/AdminLTE3/dist/img/default-product.png'">
                        </a>
                        <div class="recommendation-content">
                            <div class="recommendation-category"><?php echo htmlspecialchars($recommendedProduct['categorie']); ?></div>
                            <h3><?php echo htmlspecialchars($recommendedProduct['nom']); ?></h3>
                            <div class="recommendation-price">
                                <?php if ($controller->hasProductPromotion($recommendedProduct)): ?>
                                    <span class="old-price"><?php echo number_format((float)$recommendedProduct['prix'], 2); ?> &euro;</span>
                                    <span><?php echo number_format($recommendedEffectivePrice, 2); ?> &euro;</span>
                                <?php else: ?>
                                    <?php echo number_format($recommendedEffectivePrice, 2); ?> &euro;
                                <?php endif; ?>
                            </div>
                            <div class="recommendation-actions">
                                <a href="product.php?id=<?php echo (int)$recommendedProduct['id']; ?>" class="btn-recommend-view"><i class="fas fa-eye"></i> Voir</a>
                                <?php if ($recommendedCanPreOrder): ?>
                                    <a href="preorder.php?id=<?php echo (int)$recommendedProduct['id']; ?>" class="btn-recommend-cart"><i class="fas fa-clock"></i> Pre-order</a>
                                <?php else: ?>
                                    <a href="cart.php?action=add&id=<?php echo (int)$recommendedProduct['id']; ?>" class="btn-recommend-cart"><i class="fas fa-cart-plus"></i> Ajouter</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if ($recommendationSource === 'fallback' && $recommendationError !== ''): ?>
                <div class="recommendations-error">Les recommandations AI sont temporairement indisponibles. Nous affichons des produits proches.</div>
            <?php endif; ?>
        </div>
    </section>
    <?php endif; ?>
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
        const max = parseInt(input.dataset.upper, 10) || 1;
        if (newValue >= 1 && newValue <= max) {
            input.value = newValue;
        }
    }
    document.getElementById('quantity')?.addEventListener('input', function () {
        const max = parseInt(this.dataset.upper, 10) || 1;
        const value = parseInt(this.value, 10);
        this.value = Math.max(1, Math.min(max, Number.isNaN(value) ? 1 : value));
    });
    document.getElementById('reviewRating')?.addEventListener('input', function () {
        this.value = this.value.replace(/\D/g, '').slice(0, 1);
    });
    document.getElementById('reviewName')?.addEventListener('input', function () {
        this.value = this.value.replace(/[^\p{L}\s'-]/gu, '');
    });
    document.getElementById('notifyName')?.addEventListener('input', function () {
        this.value = this.value.replace(/[^\p{L}\s'-]/gu, '');
    });
    document.getElementById('notifyForm')?.addEventListener('submit', function (event) {
        const name = document.getElementById('notifyName').value.trim();
        const email = document.getElementById('notifyEmail').value.trim();
        let valid = true;
        document.getElementById('notifyNameError').textContent = '';
        document.getElementById('notifyEmailError').textContent = '';

        if (name.length < 2 || !/^[\p{L}\s'-]+$/u.test(name)) {
            document.getElementById('notifyNameError').textContent = 'Nom invalide.';
            valid = false;
        }
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            document.getElementById('notifyEmailError').textContent = 'Email invalide.';
            valid = false;
        }

        if (!valid) {
            event.preventDefault();
        }
    });
    document.getElementById('reviewForm')?.addEventListener('submit', function (event) {
        const name = document.getElementById('reviewName').value.trim();
        const rating = parseInt(document.getElementById('reviewRating').value, 10);
        const comment = document.getElementById('reviewComment').value.trim();
        let valid = true;
        document.getElementById('reviewNameError').textContent = '';
        document.getElementById('reviewRatingError').textContent = '';
        document.getElementById('reviewCommentError').textContent = '';

        if (name.length < 2 || !/^[\p{L}\s'-]+$/u.test(name)) {
            document.getElementById('reviewNameError').textContent = 'Nom invalide.';
            valid = false;
        }
        if (Number.isNaN(rating) || rating < 1 || rating > 5) {
            document.getElementById('reviewRatingError').textContent = 'La note doit etre entre 1 et 5.';
            valid = false;
        }
        if (comment.length < 5) {
            document.getElementById('reviewCommentError').textContent = 'Commentaire trop court.';
            valid = false;
        }

        if (!valid) {
            event.preventDefault();
        }
    });
    </script>
</body>
</html>

