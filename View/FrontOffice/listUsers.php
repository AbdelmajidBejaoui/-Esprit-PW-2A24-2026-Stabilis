<?php
require_once __DIR__ . '/partials/auth.php';
require_once __DIR__ . '/../../Controller/UserC.php';

$loggedUser = null;
if (frontofficeIsLoggedIn()) {
    $userC = new UserC();
    $loggedUser = $userC->getUserById((int) $_SESSION['front_user_id']);
}

$pageTitle = 'FrontOffice';
$heroTitle = 'Smart Nutrition Journey';
$heroSubtitle = 'Connectez-vous pour gerer votre profil nutritionnel';
$activePage = 'home';
require_once __DIR__ . '/partials/layout_top.php';
?>

<div class="row">
    <div class="col-lg-7 mb-4">
        <div class="card card-vege h-100">
            <div class="card-header">Espace Client</div>
            <div class="card-body">
                <h4 class="mb-3">Gestion de compte</h4>
                <p class="mb-4">Inscription, connexion et mise a jour du profil utilisateur.</p>
                <?php if (frontofficeIsLoggedIn()): ?>
                    <a href="updateUser.php" class="btn btn-vege mr-2">Mon profil</a>
                    <a href="logout.php" class="btn btn-outline-secondary">Se deconnecter</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-vege mr-2">Se connecter</a>
                    <a href="addUser.php" class="btn btn-outline-secondary">Creer un compte</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-5 mb-4">
        <div class="card card-vege h-100">
            <div class="card-header">Statut du compte</div>
            <div class="card-body">
                <?php if ($loggedUser): ?>
                    <p><strong>Nom:</strong> <?php echo htmlspecialchars($loggedUser['nom']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($loggedUser['email']); ?></p>
                    <p><strong>Preference:</strong> <?php echo htmlspecialchars($loggedUser['preference_alimentaire']); ?></p>
                    <p class="mb-0"><strong>Compte:</strong> <span class="pill-role">Connecte</span></p>
                <?php else: ?>
                    <p>Vous n'etes pas connecte actuellement.</p>
                    <p class="mb-0">Connectez-vous pour acceder a votre profil et gerer vos informations personnelles.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/partials/layout_bottom.php'; ?>
