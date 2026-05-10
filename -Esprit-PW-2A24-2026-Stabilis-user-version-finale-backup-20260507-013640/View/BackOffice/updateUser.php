<?php
require_once __DIR__ . '/../../Controller/UserC.php';
require_once __DIR__ . '/../../Model/User.php';

$userC = new UserC();
$errors = [];

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    die('ID invalide.');
}

$currentUser = $userC->getUserById($id);
if (!$currentUser) {
    die('Utilisateur introuvable.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $passwordRequired = !empty($_POST['password']);
    $errors = $userC->validateUserData($_POST, $passwordRequired);

    if (empty($errors)) {
        $faceImage = $currentUser['face_image'] ?? null;
        $faceDescriptor = $currentUser['face_descriptor'] ?? null;
        $user = new User(
            $id,
            trim($_POST['nom']),
            trim($_POST['email']),
            $_POST['password'] ?? '',
            trim($_POST['role']),
            trim($_POST['preference_alimentaire']),
            trim($_POST['date_inscription']),
            (int) $_POST['statut_compte'],
            $faceImage,
            $faceDescriptor
        );

        $userC->updateUser($user, $id, $passwordRequired);
        header('Location: listUsers.php');
        exit;
    }
}

$data = $_POST ?: $currentUser;

$pageTitle = 'Modifier utilisateur';
$activePage = 'list';
require_once __DIR__ . '/partials/layout_top.php';
?>

<div class="card card-warning">
    <div class="card-header">
        <h3 class="card-title">Edition du compte #<?php echo (int) $id; ?></h3>
    </div>
    <form method="POST" action="">
        <div class="card-body">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <h5><i class="icon fas fa-ban"></i> Erreurs de saisie</h5>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

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
                <label>Role (admin/client)</label>
                <input class="form-control" type="text" name="role" value="<?php echo htmlspecialchars($data['role'] ?? 'client'); ?>">
            </div>

            <div class="form-group">
                <label>Preference alimentaire</label>
                <input class="form-control" type="text" name="preference_alimentaire" value="<?php echo htmlspecialchars($data['preference_alimentaire'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label>Date inscription (YYYY-MM-DD HH:MM:SS)</label>
                <input class="form-control" type="text" name="date_inscription" value="<?php echo htmlspecialchars($data['date_inscription'] ?? date('Y-m-d H:i:s')); ?>">
            </div>

            <div class="form-group">
                <label>Statut compte (1 actif, 0 inactif)</label>
                <input class="form-control" type="text" name="statut_compte" value="<?php echo htmlspecialchars((string) ($data['statut_compte'] ?? '1')); ?>">
            </div>
        </div>

        <div class="card-footer">
            <button type="submit" class="btn btn-warning">Mettre a jour</button>
            <a href="listUsers.php" class="btn btn-secondary">Retour</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/partials/layout_bottom.php'; ?>
