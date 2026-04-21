<?php
require_once __DIR__ . '/partials/auth.php';
require_once __DIR__ . '/../../Controller/UserC.php';

if (frontofficeIsLoggedIn()) {
    header('Location: updateUser.php');
    exit;
}

$userC = new UserC();
$errors = [];
$infoMessage = '';

if (isset($_GET['created'])) {
    $infoMessage = 'Inscription reussie. Connectez-vous.';
}
if (isset($_GET['logout'])) {
    $infoMessage = 'Vous etes maintenant deconnecte.';
}
if (isset($_GET['deleted'])) {
    $infoMessage = 'Compte supprime avec succes.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = $userC->validateLoginData($_POST);

    if (empty($errors)) {
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        $user = $userC->authenticateUser($email, $password);
        if (!$user) {
            $errors[] = 'Email ou mot de passe incorrect, ou compte inactif.';
        } else {
            frontofficeLogin($user);
            header('Location: updateUser.php');
            exit;
        }
    }
}

$pageTitle = 'Connexion';
$heroTitle = 'Welcome Back Athlete';
$heroSubtitle = 'Connectez-vous a votre espace personnel';
$activePage = 'login';
require_once __DIR__ . '/partials/layout_top.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card card-vege">
            <div class="card-header">Connexion</div>
            <div class="card-body">
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

                <form method="POST" action="">
                    <div class="form-group">
                        <label>Email</label>
                        <input class="form-control" type="text" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Mot de passe</label>
                        <input class="form-control" type="password" name="password">
                    </div>
                    <button type="submit" class="btn btn-vege">Se connecter</button>
                    <a href="addUser.php" class="btn btn-outline-secondary">Creer un compte</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/partials/layout_bottom.php'; ?>