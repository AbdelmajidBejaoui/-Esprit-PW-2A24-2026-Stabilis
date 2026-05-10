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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email invalide.';
    } else {
        // create reset token and send email; always show same message
        $userC->createPasswordReset($email);
        $info = 'Si un compte existe pour cet email, un message contenant les instructions vous a ete envoye.';
    }
}

$pageTitle = 'Mot de passe oublie';
$heroTitle = 'Mot de passe oublie';
$activePage = 'login';
require_once __DIR__ . '/partials/layout_top.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card card-vege">
            <div class="card-header">Mot de passe oublie</div>
            <div class="card-body">
                <?php if ($info !== ''): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($info); ?></div>
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

                <form method="POST" action="">
                    <div class="form-group">
                        <label>Adresse email</label>
                        <input class="form-control" type="text" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                    <button type="submit" class="btn btn-vege">Envoyer les instructions</button>
                    <a href="login.php" class="btn btn-outline-secondary">Retour</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/partials/layout_bottom.php'; ?>
