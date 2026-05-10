<?php
require_once __DIR__ . '/partials/auth.php';
require_once __DIR__ . '/../../../Controllers/UserC.php';

$userC = new UserC();
$errors = [];
$infoMessage = '';

if (!isset($_SESSION['pending_2fa_user'])) {
    header('Location: login.php');
    exit;
}

$userId = (int) $_SESSION['pending_2fa_user'];
$email = $_SESSION['pending_2fa_email'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['resend'])) {
        if ($userC->sendTwoFactorCode($userId, $email)) {
            $infoMessage = 'Un nouveau code a ete envoye.';
        } else {
            $errors[] = 'Impossible de renvoyer le code pour le moment.';
        }
    } else {
    $code = trim($_POST['code'] ?? '');
    if ($code === '') {
        $errors[] = 'Entrez le code de verification.';
    } else {
        $ok = $userC->verifyTwoFactorCode($userId, $code);
        if ($ok) {
            // finalise login
            $user = $userC->getUserById($userId);
            if ($user) {
                frontofficeLogin($user);
            }
            // cleanup pending
            unset($_SESSION['pending_2fa_user'], $_SESSION['pending_2fa_email']);
            header('Location: updateUser.php');
            exit;
        } else {
            $errors[] = 'Code invalide ou expire.';
        }
    }
    }
}

$pageTitle = 'Verification 2FA';
$heroTitle = 'Verification 2FA';
$activePage = 'login';
require_once __DIR__ . '/partials/layout_top.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card card-vege">
            <div class="card-header">Code de verification</div>
            <div class="card-body">
                <?php if (!empty($infoMessage)): ?>
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

                <p>Un code de verification a ete envoye a <?php echo htmlspecialchars($email); ?>. Il est valable 5 minutes.</p>

                <?php if (isset($_SESSION['debug_2fa_code'])): ?>
                    <div class="alert alert-info" style="background-color: #e3f2fd; border: 1px solid #90caf9; color: #1565c0; margin-bottom: 15px;">
                        <strong>🔧 Mode Developpement:</strong><br>
                        Votre code 2FA est: <code style="background: #fff; padding: 5px 10px; border-radius: 3px; font-weight: bold; font-size: 16px;"><?php echo htmlspecialchars($_SESSION['debug_2fa_code']); ?></code>
                        <br><small>(Code affiché pour les tests uniquement)</small>
                    </div>
                    <?php unset($_SESSION['debug_2fa_code']); // Clear after displaying ?>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label>Code</label>
                        <input class="form-control" type="text" name="code" value="<?php echo htmlspecialchars($_POST['code'] ?? ''); ?>">
                    </div>
                    <button type="submit" class="btn btn-vege">Verifier</button>
                    <a href="login.php" class="btn btn-outline-secondary">Annuler</a>
                </form>

                <hr>
                <form method="POST" action="">
                    <input type="hidden" name="resend" value="1">
                    <button type="submit" class="btn btn-link">Renvoyer le code</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/partials/layout_bottom.php'; ?>
