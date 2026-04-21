<?php
require_once __DIR__ . '/partials/auth.php';
require_once __DIR__ . '/../../Controller/UserC.php';
require_once __DIR__ . '/../../Model/User.php';

frontofficeRequireLogin();

$userC = new UserC();
$errors = [];
$successMessage = '';

$id = (int) $_SESSION['front_user_id'];

$currentUser = $userC->getUserById($id);
if (!$currentUser) {
    die('Utilisateur introuvable.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $passwordRequired = trim($_POST['password'] ?? '') !== '';
    $errors = $userC->validateProfileData($_POST, $passwordRequired, $id);

    if (empty($errors)) {
        $user = new User(
            $id,
            trim($_POST['nom']),
            trim($_POST['email']),
            $_POST['password'] ?? '',
            'client',
            trim($_POST['preference_alimentaire']),
            $currentUser['date_inscription'],
            (int) $currentUser['statut_compte']
        );

        $userC->updateUser($user, $id, $passwordRequired);
        $_SESSION['front_user_nom'] = trim($_POST['nom']);
        $_SESSION['front_user_email'] = trim($_POST['email']);
        $successMessage = 'Profil mis a jour avec succes.';
        $currentUser = $userC->getUserById($id);
    }
}

$data = $_POST ?: $currentUser;

$pageTitle = 'Modifier profil';
$heroTitle = 'Update Your Profile';
$heroSubtitle = 'Gardez vos informations a jour';
$activePage = 'profile';
require_once __DIR__ . '/partials/layout_top.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card card-vege">
            <div class="card-header">Modifier le compte #<?php echo (int) $id; ?></div>
            <div class="card-body">
                <?php if ($successMessage !== ''): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
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
                        <label>Nom</label>
                        <input class="form-control" type="text" name="nom" value="<?php echo htmlspecialchars($data['nom'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input class="form-control" type="text" name="email" value="<?php echo htmlspecialchars($data['email'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label>Nouveau mot de passe (laisser vide pour garder l'ancien)</label>
                        <input class="form-control" type="password" name="password">
                    </div>

                    <div class="form-group">
                        <label>Preference alimentaire</label>
                        <input class="form-control" type="text" name="preference_alimentaire" value="<?php echo htmlspecialchars($data['preference_alimentaire'] ?? ''); ?>">
                    </div>

                    <button type="submit" class="btn btn-vege">Mettre a jour</button>
                    <a href="listUsers.php" class="btn btn-outline-secondary">Retour accueil</a>
                </form>

                <hr>
                <form method="POST" action="deleteUser.php" onsubmit="return confirm('Voulez-vous vraiment supprimer votre compte ?');">
                    <button type="submit" class="btn btn-outline-danger">Supprimer mon compte</button>
                    <a href="logout.php" class="btn btn-outline-dark ml-2">Se deconnecter</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/partials/layout_bottom.php'; ?>
