<?php
session_start();
require_once __DIR__ . '/../../controllers/PackController.php';

$controller = new PackController();
$id = (int)($_GET['id'] ?? 0);
$pack = $controller->getById($id);
if (!$pack || (int)$pack['active'] !== 1) {
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
    <title><?php echo htmlspecialchars($pack['nom']); ?> - Stabilis&trade;</title>
    <link rel="stylesheet" href="../../assets/css/stabilis.css?v=5">
    <link rel="stylesheet" href="../../assets/css/front-style.css?v=7">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="page-product">
    <nav class="navbar">
        <div class="container">
            <a href="index.php" class="navbar-brand">Stabilis<sup>&trade;</sup></a>
            <ul class="navbar-nav">
                <li><a href="index.php">Accueil</a></li>
                <li><a href="shop.php">Boutique</a></li>
                <li><a href="cart.php"><i class="fas fa-shopping-cart"></i><?php if($cartCount > 0): ?><span class="cart-badge"><span class="cart-count"><?php echo $cartCount; ?></span></span><?php endif; ?></a></li>
            </ul>
        </div>
    </nav>
    <section class="product-detail">
        <div class="container">
            <div class="product-container">
                <div class="product-image-container">
                    <?php if (!empty($pack['image_url'])): ?>
                    <img src="/AdminLTE3/dist/img/<?php echo htmlspecialchars($pack['image_url'] ?? 'default-product.png'); ?>" class="product-image" onerror="this.src='/AdminLTE3/dist/img/default-product.png'">
                    <?php else: ?>
                    <div class="pack-slideshow">
                        <?php foreach ($pack['items'] as $index => $item): ?>
                        <img src="/AdminLTE3/dist/img/<?php echo htmlspecialchars($item['image_url'] ?? 'default-product.png'); ?>" class="product-image pack-slide <?php echo $index === 0 ? 'is-active' : ''; ?>" alt="<?php echo htmlspecialchars($item['nom']); ?>" onerror="this.src='/AdminLTE3/dist/img/default-product.png'">
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="product-info">
                    <div class="product-category">Pack</div>
                    <h1><?php echo htmlspecialchars($pack['nom']); ?></h1>
                    <div class="product-price"><?php echo number_format((float)$pack['prix'], 2); ?> &euro;</div>
                    <div class="product-description"><?php echo nl2br(htmlspecialchars($pack['description'] ?: 'Pack compose de produits selectionnes.')); ?></div>
                    <div class="product-meta">
                        <?php foreach ($pack['items'] as $item): ?>
                        <div class="meta-item">
                            <span class="meta-label"><?php echo htmlspecialchars($item['nom']); ?></span>
                            <span class="meta-value">x<?php echo (int)$item['quantite']; ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($controller->canBuyPack($pack, 1)): ?>
                    <form method="POST" action="cart.php">
                        <input type="hidden" name="action" value="add_pack">
                        <input type="hidden" name="pack_id" value="<?php echo (int)$pack['id']; ?>">
                        <div class="action-buttons">
                            <button type="submit" class="btn-add-cart"><i class="fas fa-cart-plus"></i> Ajouter au panier</button>
                        </div>
                    </form>
                    <?php else: ?>
                    <div class="stock-indicator out-of-stock"><i class="fas fa-times-circle"></i> Pack indisponible</div>
                    <div class="action-buttons">
                        <a href="preorder_pack.php?id=<?php echo (int)$pack['id']; ?>" class="btn-add-cart" style="text-decoration:none;"><i class="fas fa-clock"></i> Pre-commander ce pack</a>
                    </div>
                    <?php endif; ?>
                    <div class="action-buttons">
                        <a href="shop.php" class="btn-back"><i class="fas fa-arrow-left"></i> Retour a la boutique</a>
                    </div>
                    <div class="review-box">
                        <h2>Avis pack</h2>
                        <?php if (($_GET['review'] ?? '') === 'sent'): ?>
                            <div class="stock-indicator in-stock">Avis envoye a l'administration.</div>
                        <?php elseif (($_GET['review'] ?? '') === 'invalid'): ?>
                            <div class="stock-indicator out-of-stock">Veuillez verifier votre avis.</div>
                        <?php elseif (($_GET['review'] ?? '') === 'mail_error'): ?>
                            <div class="stock-indicator out-of-stock">L'avis est valide, mais l'email n'a pas pu etre envoye.</div>
                        <?php endif; ?>
                        <form method="POST" action="../../Controllers/ReviewHandler.php" id="reviewForm" novalidate>
                            <input type="hidden" name="item_type" value="pack">
                            <input type="hidden" name="pack_id" value="<?php echo (int)$pack['id']; ?>">
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
    <style>
    .pack-slideshow { position:relative; width:100%; min-height:420px; overflow:hidden; border-radius:16px; background:#FCFCFA; }
    .pack-slide { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; opacity:0; transition:opacity .45s ease; }
    .pack-slide.is-active { opacity:1; }
    .review-box { margin-top:28px; padding:22px; border:1px solid #d9eadf; border-radius:12px; background:#f3faf5; }
    .review-box h2 { font-size:20px; margin:0 0 14px; color:#1A4D3A; }
    .review-box .form-group { margin-bottom:14px; }
    .review-box label { display:block; margin-bottom:6px; color:#1A4D3A; font-weight:600; font-size:14px; }
    .review-box .form-control { width:100%; border:1px solid #c9dfd0; border-radius:8px; padding:12px 14px; background:white; color:#24362b; }
    .review-box .btn-review { border:none; border-radius:999px; background:#1A4D3A; color:white; padding:12px 20px; font-weight:700; cursor:pointer; }
    </style>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const slides = Array.from(document.querySelectorAll('.pack-slide'));
        if (slides.length > 1) {
            let current = 0;
            setInterval(() => {
                slides[current].classList.remove('is-active');
                current = (current + 1) % slides.length;
                slides[current].classList.add('is-active');
            }, 2600);
        }
        document.getElementById('reviewRating')?.addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '').slice(0, 1);
        });
        document.getElementById('reviewName')?.addEventListener('input', function () {
            this.value = this.value.replace(/[^\p{L}\s'-]/gu, '');
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
    });
    </script>
</body>
</html>
