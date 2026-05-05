<?php
require_once __DIR__ . '/partials/auth.php';
require_once __DIR__ . '/../../Controller/UserC.php';

if (frontofficeIsLoggedIn()) {
    header('Location: updateUser.php');
    exit;
}

$userC = new UserC();
$errors = [];
$info = '';
$token = trim($_GET['token'] ?? $_POST['token'] ?? '');

if ($token === '') {
    $errors[] = 'Jeton manquant.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if ($password === '' || strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
        $errors[] = 'Mot de passe invalide (min 8 caracteres avec lettres et chiffres).';
    }
    if ($password !== $confirm) {
        $errors[] = 'Les deux mots de passe ne correspondent pas.';
    }

    if (empty($errors)) {
        $ok = $userC->resetPasswordWithToken($token, $password);
        if ($ok) {
            header('Location: login.php?reset=1');
            exit;
        }
        $errors[] = 'Jeton invalide ou expire.';
    }
} else {
    // on GET, verify token exists and is valid
    if ($token !== '') {
        $row = $userC->verifyPasswordResetToken($token);
        if (!$row) {
            $errors[] = 'Jeton invalide ou expire.';
        }
    }
}

$pageTitle = 'Reinitialisation mot de passe';
$heroTitle = 'Reinitialisation mot de passe';
$activePage = 'login';
require_once __DIR__ . '/partials/layout_top.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card card-vege">
            <div class="card-header">Reinitialiser le mot de passe</div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    <div class="form-group">
                        <label>Nouveau mot de passe</label>
                        <input class="form-control" type="password" name="password">
                    </div>
                    <div class="form-group">
                        <label>Confirmer le mot de passe</label>
                        <input class="form-control" type="password" name="confirm">
                    </div>
                    <button type="submit" class="btn btn-vege">Redefinir le mot de passe</button>
                    <a href="login.php" class="btn btn-outline-secondary">Retour</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/partials/layout_bottom.php'; ?>
