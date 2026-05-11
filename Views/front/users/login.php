<?php
require_once __DIR__ . '/partials/auth.php';
require_once __DIR__ . '/../../../Controllers/UserC.php';

// load site key for reCAPTCHA if configured
$siteKey = '';
$recfg = __DIR__ . '/../../../config/recaptcha.php';
if (file_exists($recfg)) {
    $cfg = include $recfg;
    $siteKey = $cfg['site_key'] ?? '';
}

if (frontofficeIsLoggedIn()) {
    header('Location: updateUser.php');
    exit;
}

$userC = new UserC();
$errors = [];
$infoMessage = '';

if (isset($_GET['created'])) {
    $infoMessage = 'Inscription reussie. Vous pouvez vous connecter.';
}
if (isset($_GET['verify_email'])) {
    $infoMessage = 'Inscription reussie. Verifiez votre email pour activer le compte avant connexion.';
}
if (isset($_GET['logout'])) {
    $infoMessage = 'Vous etes maintenant deconnecte.';
}
if (isset($_GET['deleted'])) {
    $infoMessage = 'Compte supprime avec succes.';
}
if (isset($_GET['invalid'])) {
    $infoMessage = 'Session invalide. Veuillez vous reconnecter.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = $userC->validateLoginData($_POST);

    if (empty($errors)) {
        // verify reCAPTCHA first (if configured)
        $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';
        if (!$userC->verifyRecaptcha($recaptchaResponse)) {
            $errors[] = 'Veuillez confirmer que vous n\'etes pas un robot.';
        }

        if (empty($errors)) {
            $email = trim($_POST['email']);
            $password = $_POST['password'];

            $user = $userC->authenticateUser($email, $password);
            if (!$user) {
                $errors[] = 'Email ou mot de passe incorrect, ou compte inactif.';
            } else {
                if (!empty($user['face_descriptor'])) {
                    $_SESSION['pending_face_user'] = (int) $user['id'];
                    $_SESSION['pending_face_email'] = $user['email'];
                    header('Location: verifyFace.php');
                    exit;
                }

                if (!$userC->sendTwoFactorCode((int)$user['id'], $user['email'])) {
                    $errors[] = 'Impossible d\'envoyer le code 2FA pour le moment.';
                } else {
                    $_SESSION['pending_2fa_user'] = (int) $user['id'];
                    $_SESSION['pending_2fa_email'] = $user['email'];
                    header('Location: verify2fa.php');
                    exit;
                }
            }
        }
    }
}

$pageTitle = 'Connexion';
$heroTitle = 'Welcome Back Athlete';
$heroSubtitle = 'Connectez-vous a votre espace personnel';
$activePage = 'login';
require_once __DIR__ . '/partials/layout_top.php';
?>

<div class="auth-shell" data-reveal="up">
    <div class="auth-panel is-compact" data-lift-hover>
        <aside class="auth-aside">
            <div>
                <span class="auth-kicker"><i class="fa-solid fa-user-shield"></i> Espace securise</span>
                <h2>Reprends ton parcours Stabilis.</h2>
                <p>Connecte-toi pour retrouver ton profil, tes preferences et ton acces boutique.</p>
            </div>
            <ul class="auth-aside-list">
                <li><i class="fa-solid fa-face-smile"></i> Verification Face ID</li>
                <li><i class="fa-solid fa-key"></i> Code 2FA par email</li>
                <li><i class="fa-solid fa-leaf"></i> Profil nutritionnel</li>
                <li><i class="fa-solid fa-bag-shopping"></i> Acces boutique rapide</li>
            </ul>
        </aside>

        <div class="auth-card">
            <h3>Connexion</h3>
            <p class="auth-copy">Entre tes identifiants, confirme ton visage, puis valide le code 2FA.</p>
                <?php if ($infoMessage !== ''): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($infoMessage); ?></div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="auth-form">
                    <div class="form-group auth-field">
                        <label>Email</label>
                        <input class="form-control" type="email" name="email" autocomplete="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                    <div class="form-group auth-field">
                        <label>Mot de passe</label>
                        <input class="form-control" type="password" name="password" autocomplete="current-password">
                    </div>
                    <?php if ($siteKey !== ''): ?>
                        <div class="mt-3">
                            <script src="https://www.google.com/recaptcha/api.js" async defer></script>
                            <div class="g-recaptcha" data-sitekey="<?php echo htmlspecialchars($siteKey); ?>"></div>
                        </div>
                    <?php endif; ?>
                    <div class="auth-actions">
                        <button type="submit" class="btn btn-vege"><i class="fa-solid fa-arrow-right-to-bracket mr-1"></i> Se connecter</button>
                        <a href="addUser.php" class="btn btn-outline-secondary">Creer un compte</a>
                        <a href="forgotPassword.php" class="btn btn-link">Mot de passe oublie ?</a>
                    </div>
                </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/partials/layout_bottom.php'; ?>
