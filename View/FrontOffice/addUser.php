<?php
require_once __DIR__ . '/partials/auth.php';
require_once __DIR__ . '/../../Controller/UserC.php';
require_once __DIR__ . '/../../Model/User.php';

if (frontofficeIsLoggedIn()) {
    header('Location: updateUser.php');
    exit;
}

$userC = new UserC();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = $userC->validateRegistrationData($_POST);

    if (empty($errors)) {
        $user = new User(
            null,
            trim($_POST['nom']),
            trim($_POST['email']),
            $_POST['password'],
            'client',
            trim($_POST['preference_alimentaire']),
            date('Y-m-d H:i:s'),
            1
        );

        $userC->insertUser($user);
        header('Location: login.php?created=1');
        exit;
    }
}

$pageTitle = 'Inscription';
$heroTitle = 'Create Your Athlete Profile';
$heroSubtitle = 'Rejoignez la communaute NutriSmart';
$activePage = 'signup';
require_once __DIR__ . '/partials/layout_top.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card card-vege">
            <div class="card-header">Inscription athlete</div>
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
                    <div class="form-group">
                        <label>Nom</label>
                        <input class="form-control" type="text" name="nom" value="<?php echo htmlspecialchars($_POST['nom'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input class="form-control" type="text" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label>Mot de passe</label>
                        <input class="form-control" type="password" name="password">
                    </div>

                    <div class="form-group">
                        <label>Preference alimentaire</label>
                        <input class="form-control" type="text" name="preference_alimentaire" value="<?php echo htmlspecialchars($_POST['preference_alimentaire'] ?? ''); ?>">
                    </div>

                    <button type="submit" class="btn btn-vege">S'inscrire</button>
                    <a href="login.php" class="btn btn-outline-secondary">J'ai deja un compte</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/partials/layout_bottom.php'; ?>
