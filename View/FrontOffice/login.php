<?php
require_once __DIR__ . '/partials/auth.php';
require_once __DIR__ . '/../../Controller/UtilisateurC.php';

if (frontIsLoggedIn()) { header('Location: catalogue.php'); exit; }

$errors = [];
$uC = new UtilisateurC();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = $uC->validateLogin($_POST);
    if (empty($errors)) {
        $u = $uC->getByEmail(trim($_POST['email']));
        if ($u && password_verify($_POST['password'], $u->getPassword())) {
            $_SESSION['user_id']   = $u->getId();
            $_SESSION['user_data'] = ['id'=>$u->getId(),'nom'=>$u->getNom(),'email'=>$u->getEmail(),
                                      'poids'=>$u->getPoids(),'taille'=>$u->getTaille(),'age'=>$u->getAge(),'sexe'=>$u->getSexe()];
            flash('success', 'Bienvenue, '.$u->getNom().' !');
            header('Location: catalogue.php'); exit;
        }
        $errors[] = 'Email ou mot de passe incorrect.';
    }
}

$pageTitle='Connexion'; $heroTitle='Connexion Athlète'; $heroBg='bg_2.jpg'; $activePage='';
require_once __DIR__ . '/partials/layout_top.php';
?>
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card card-vege">
            <div class="card-header"><i class="fas fa-sign-in-alt mr-2"></i>Connexion</div>
            <div class="card-body p-4">
                <?php if(!empty($errors)): ?>
                <div class="alert alert-danger"><ul class="mb-0 error-list"><?php foreach($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul></div>
                <?php endif; ?>
                <?php if(isset($_GET['created'])): ?>
                <div class="alert alert-success">Compte créé ! Connectez-vous.</div>
                <?php endif; ?>
                <form method="POST" action="">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="text" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email']??'') ?>" placeholder="votre@email.com">
                    </div>
                    <div class="form-group">
                        <label>Mot de passe</label>
                        <input type="password" name="password" class="form-control" placeholder="••••••">
                    </div>
                    <button type="submit" class="btn btn-vege btn-block">Se connecter</button>
                </form>
                <hr>
                <p class="text-center mb-0">Pas de compte ? <a href="register.php">S'inscrire</a></p>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/partials/layout_bottom.php'; ?>
