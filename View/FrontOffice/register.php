<?php
require_once __DIR__ . '/partials/auth.php';
require_once __DIR__ . '/../../Controller/UtilisateurC.php';
require_once __DIR__ . '/../../Model/Utilisateur.php';

if (frontIsLoggedIn()) { header('Location: catalogue.php'); exit; }

$errors = [];
$uC = new UtilisateurC();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = $uC->validateRegister($_POST);
    if (empty($errors)) {
        $u = new Utilisateur(null,
            trim($_POST['nom']),
            trim($_POST['email']),
            password_hash($_POST['password'], PASSWORD_BCRYPT),
            !empty($_POST['poids']) ? (float)$_POST['poids'] : null,
            !empty($_POST['taille']) ? (int)$_POST['taille'] : null,
            !empty($_POST['age']) ? (int)$_POST['age'] : null,
            in_array($_POST['sexe']??'',['H','F']) ? $_POST['sexe'] : 'H'
        );
        $uC->insert($u);
        header('Location: login.php?created=1'); exit;
    }
}

$pageTitle='Inscription'; $heroTitle='Créer mon profil athlète'; $heroBg='bg_3.jpg'; $activePage='';
require_once __DIR__ . '/partials/layout_top.php';
?>
<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="card card-vege">
            <div class="card-header"><i class="fas fa-user-plus mr-2"></i>Inscription</div>
            <div class="card-body p-4">
                <?php if(!empty($errors)): ?>
                <div class="alert alert-danger"><ul class="mb-0 error-list"><?php foreach($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul></div>
                <?php endif; ?>
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Nom complet <span class="text-danger">*</span></label>
                                <input type="text" name="nom" class="form-control" value="<?= htmlspecialchars($_POST['nom']??'') ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Email <span class="text-danger">*</span></label>
                                <input type="text" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email']??'') ?>">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Mot de passe <span class="text-danger">*</span> <small class="text-muted">(min 6 caractères)</small></label>
                        <input type="password" name="password" class="form-control">
                    </div>
                    <hr><p class="text-muted small">Informations physiques (facultatives, utilisées pour calculer vos calories)</p>
                    <div class="row">
                        <div class="col-6 col-md-3">
                            <div class="form-group">
                                <label>Poids (kg)</label>
                                <input type="number" name="poids" class="form-control" value="<?= htmlspecialchars($_POST['poids']??'') ?>" placeholder="75">
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="form-group">
                                <label>Taille (cm)</label>
                                <input type="number" name="taille" class="form-control" value="<?= htmlspecialchars($_POST['taille']??'') ?>" placeholder="175">
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="form-group">
                                <label>Âge</label>
                                <input type="number" name="age" class="form-control" value="<?= htmlspecialchars($_POST['age']??'') ?>" placeholder="24">
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="form-group">
                                <label>Sexe</label>
                                <select name="sexe" class="form-control">
                                    <option value="H" <?= ($_POST['sexe']??'H')==='H'?'selected':'' ?>>Homme</option>
                                    <option value="F" <?= ($_POST['sexe']??'')==='F'?'selected':'' ?>>Femme</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-vege btn-block">Créer mon compte</button>
                </form>
                <hr>
                <p class="text-center mb-0">Déjà un compte ? <a href="login.php">Se connecter</a></p>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/partials/layout_bottom.php'; ?>
