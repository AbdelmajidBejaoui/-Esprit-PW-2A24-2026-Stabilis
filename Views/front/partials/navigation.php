<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$cartCount = $cartCount ?? array_sum($_SESSION['cart'] ?? []);
$activeFrontPage = $activeFrontPage ?? '';
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$isFrontLoggedIn = isset($_SESSION['front_user_id']);
$frontUserName = trim((string)($_SESSION['front_user_nom'] ?? 'Profil'));
$frontUserRole = strtolower((string)($_SESSION['front_user_role'] ?? ''));
$isFrontAdmin = $frontUserRole === 'admin';

if (!function_exists('stabilis_front_nav_active')) {
    function stabilis_front_nav_active(string $key, array $paths, string $activeFrontPage, string $currentPath): string
    {
        if ($activeFrontPage === $key) {
            return ' active-nav';
        }

        foreach ($paths as $path) {
            if (substr($currentPath, -strlen($path)) === $path) {
                return ' active-nav';
            }
        }

        return '';
    }
}
?>
<div class="top-bar">
    <div class="container">
        <div class="top-bar-item"><i class="fas fa-envelope"></i><span>stabilisatyourservice@gmail.com</span></div>
        <div class="top-bar-item"><i class="fas fa-truck"></i><span>Livraison sous 3-5 jours</span></div>
    </div>
</div>

<nav class="navbar">
    <div class="container">
        <a href="/AdminLTE3/Views/front/index.php" class="navbar-brand">Stabilis<sup>&trade;</sup></a>
        <ul class="navbar-nav">
            <li><a href="/AdminLTE3/Views/front/index.php" class="<?php echo stabilis_front_nav_active('home', ['/Views/front/index.php'], $activeFrontPage, $currentPath); ?>">Accueil</a></li>
            <li><a href="/AdminLTE3/Views/front/shop.php" class="<?php echo stabilis_front_nav_active('shop', ['/Views/front/shop.php', '/Views/front/product.php', '/Views/front/pack.php', '/Views/front/preorder.php', '/Views/front/preorder_pack.php'], $activeFrontPage, $currentPath); ?>">Boutique</a></li>
            <li><a href="/AdminLTE3/Views/front/defis/index.php" class="<?php echo stabilis_front_nav_active('defis', ['/Views/front/defis/'], $activeFrontPage, $currentPath); ?>">Defis</a></li>
            <li>
                <a href="/AdminLTE3/Views/front/cart.php" class="cart-nav-link<?php echo stabilis_front_nav_active('cart', ['/Views/front/cart.php', '/Views/front/order.php'], $activeFrontPage, $currentPath); ?>">
                    <i class="fas fa-shopping-cart"></i>
                    <?php if ($cartCount > 0): ?>
                    <span class="cart-badge"><span class="cart-count"><?php echo (int)$cartCount; ?></span></span>
                    <?php endif; ?>
                </a>
            </li>
            <?php if ($isFrontAdmin): ?>
            <li><a href="/AdminLTE3/Views/back/dashboard.php" class="admin-link"><i class="fas fa-cog"></i> Administration</a></li>
            <?php endif; ?>
            <?php if ($isFrontLoggedIn): ?>
            <li>
                <a href="/AdminLTE3/Views/front/users/updateUser.php" class="user-nav-pill<?php echo stabilis_front_nav_active('account', ['/Views/front/users/listUsers.php', '/Views/front/users/updateUser.php'], $activeFrontPage, $currentPath); ?>">
                    <i class="fas fa-user-circle"></i>
                    <span><?php echo htmlspecialchars($frontUserName !== '' ? $frontUserName : 'Profil'); ?></span>
                </a>
            </li>
            <li><a href="/AdminLTE3/Views/front/users/logout.php" class="logout-nav-link">Deconnexion</a></li>
            <?php else: ?>
            <li><a href="/AdminLTE3/Views/front/users/login.php" class="auth-nav-link<?php echo stabilis_front_nav_active('login', ['/Views/front/users/login.php'], $activeFrontPage, $currentPath); ?>">Connexion</a></li>
            <li><a href="/AdminLTE3/Views/front/users/addUser.php" class="signup-nav-link<?php echo stabilis_front_nav_active('signup', ['/Views/front/users/addUser.php'], $activeFrontPage, $currentPath); ?>">Inscription</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>
