<?php
require_once __DIR__ . '/partials/auth.php';
requireLogin();
require_once __DIR__ . '/../../Controller/UtilisateurC.php';
require_once __DIR__ . '/../../Controller/SeanceC.php';
require_once __DIR__ . '/../../Model/Utilisateur.php';

$uC  = new UtilisateurC();
$sC  = new SeanceC();
$uid = frontUserId();
$u   = $uC->getById($uid);
if (!$u) { header('Location: logout.php'); exit; }

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = $uC->validateProfile($_POST, $uid);
    if (empty($errors)) {
        $u->setNom(trim($_POST['nom']));
        $u->setEmail(trim($_POST['email']));
        $u->setPoids(!empty($_POST['poids']) ? (float)$_POST['poids'] : null);
        $u->setTaille(!empty($_POST['taille']) ? (int)$_POST['taille'] : null);
        $u->setAge(!empty($_POST['age']) ? (int)$_POST['age'] : null);
        $u->setSexe(in_array($_POST['sexe']??'',['H','F']) ? $_POST['sexe'] : 'H');
        $uC->update($u);
        // Refresh session
        $_SESSION['user_data'] = ['id'=>$u->getId(),'nom'=>$u->getNom(),'email'=>$u->getEmail(),
                                  'poids'=>$u->getPoids(),'taille'=>$u->getTaille(),'age'=>$u->getAge(),'sexe'=>$u->getSexe()];
        flash('success', 'Profil mis à jour !');
        header('Location: profil.php'); exit;
    }
}

$stats    = $sC->statsUser($uid);
$seances  = $sC->listByUser($uid, 10);

// IMC si profil complet
$imc = null; $imcLabel = '';
if ($u->getPoids() && $u->getTaille()) {
    $imc = round($u->getPoids() / pow($u->getTaille()/100, 2), 1);
    $imcLabel = $imc < 18.5 ? 'Maigreur' : ($imc < 25 ? 'Normal' : ($imc < 30 ? 'Surpoids' : 'Obésité'));
}

// Métabolisme de base (Harris-Benedict)
$mbj = null;
if ($u->getPoids() && $u->getTaille() && $u->getAge()) {
    $mbj = $u->getSexe()==='H'
        ? round(88.362 + 13.397*$u->getPoids() + 4.799*$u->getTaille() - 5.677*$u->getAge(), 0)
        : round(447.593 + 9.247*$u->getPoids() + 3.098*$u->getTaille() - 4.330*$u->getAge(), 0);
}

$pageTitle='Mon Profil'; $heroTitle='Mon Profil Athlète'; $heroBg='bg_3.jpg';
$activePage='profil';
$breadcrumb='<span>Mon Profil</span>';
require_once __DIR__ . '/partials/layout_top.php';
?>

<div class="row">
    <!-- STATS -->
    <div class="col-lg-4 mb-4">
        <div style="border-radius:16px;overflow:hidden;box-shadow:0 8px 30px rgba(0,0,0,.1);">
            <div style="background:linear-gradient(135deg,#82ae46,#4a7c20);color:#fff;padding:28px;text-align:center;">
                <div style="width:80px;height:80px;background:rgba(255,255,255,.2);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;font-size:2rem;">
                    <?= strtoupper(substr($u->getNom(),0,1)) ?>
                </div>
                <h4 class="mb-0"><?= htmlspecialchars($u->getNom()) ?></h4>
                <small><?= htmlspecialchars($u->getEmail()) ?></small>
            </div>
            <div style="background:#fff;padding:20px;">
                <!-- Stats séances -->
                <div class="row text-center mb-3">
                    <div class="col-6" style="border-right:1px solid #f0f0f0;">
                        <div style="font-size:1.6rem;font-weight:800;color:#82ae46;"><?= $stats['nb_seances'] ?></div>
                        <small class="text-muted">Séances</small>
                    </div>
                    <div class="col-6">
                        <div style="font-size:1.6rem;font-weight:800;color:#f5576c;"><?= number_format($stats['total_calories'],0) ?></div>
                        <small class="text-muted">kcal brûlées</small>
                    </div>
                </div>
                <div class="row text-center">
                    <div class="col-6" style="border-right:1px solid #f0f0f0;">
                        <div style="font-size:1.3rem;font-weight:700;color:#4facfe;"><?= round($stats['total_minutes']/60,1) ?>h</div>
                        <small class="text-muted">Temps total</small>
                    </div>
                    <div class="col-6">
                        <div style="font-size:1.3rem;font-weight:700;color:#43e97b;"><?= number_format($stats['avg_calories'],0) ?></div>
                        <small class="text-muted">kcal/séance</small>
                    </div>
                </div>
                <?php if ($imc): ?>
                <hr>
                <div class="text-center">
                    <div style="font-size:1.8rem;font-weight:800;color:<?= $imc<25?'#28a745':'#ffc107' ?>;"><?= $imc ?></div>
                    <small class="text-muted">IMC — <?= $imcLabel ?></small>
                </div>
                <?php endif; ?>
                <?php if ($mbj): ?>
                <div class="text-center mt-2">
                    <div style="font-size:1.4rem;font-weight:700;color:#4facfe;"><?= number_format($mbj,0) ?> kcal/j</div>
                    <small class="text-muted">Métabolisme de base</small>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- FORM + HISTORY -->
    <div class="col-lg-8">
        <!-- Edit form -->
        <div class="card card-vege mb-4">
            <div class="card-header"><i class="fas fa-user-edit mr-2"></i>Modifier mon profil</div>
            <div class="card-body p-4">
                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger"><ul class="mb-0 error-list"><?php foreach($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul></div>
                <?php endif; ?>
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label style="font-weight:600;">Nom <span class="text-danger">*</span></label>
                                <input type="text" name="nom" class="form-control" style="border-radius:8px;"
                                       value="<?= htmlspecialchars($_POST['nom'] ?? $u->getNom()) ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label style="font-weight:600;">Email <span class="text-danger">*</span></label>
                                <input type="text" name="email" class="form-control" style="border-radius:8px;"
                                       value="<?= htmlspecialchars($_POST['email'] ?? $u->getEmail()) ?>">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6 col-md-3">
                            <div class="form-group">
                                <label style="font-weight:600;">Poids (kg)</label>
                                <input type="number" name="poids" class="form-control" style="border-radius:8px;" step="0.1"
                                       value="<?= htmlspecialchars($_POST['poids'] ?? $u->getPoids()) ?>" placeholder="75">
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="form-group">
                                <label style="font-weight:600;">Taille (cm)</label>
                                <input type="number" name="taille" class="form-control" style="border-radius:8px;"
                                       value="<?= htmlspecialchars($_POST['taille'] ?? $u->getTaille()) ?>" placeholder="175">
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="form-group">
                                <label style="font-weight:600;">Âge</label>
                                <input type="number" name="age" class="form-control" style="border-radius:8px;"
                                       value="<?= htmlspecialchars($_POST['age'] ?? $u->getAge()) ?>" placeholder="24">
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="form-group">
                                <label style="font-weight:600;">Sexe</label>
                                <select name="sexe" class="form-control" style="border-radius:8px;">
                                    <option value="H" <?= ($u->getSexe()==='H')?'selected':'' ?>>Homme</option>
                                    <option value="F" <?= ($u->getSexe()==='F')?'selected':'' ?>>Femme</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-vege px-4" style="border-radius:30px;"><i class="fas fa-save mr-2"></i>Enregistrer</button>
                </form>
            </div>
        </div>

        <!-- Historique séances -->
        <div class="card card-vege">
            <div class="card-header"><i class="fas fa-history mr-2"></i>Mes 10 dernières séances</div>
            <div class="card-body p-0">
                <?php if (empty($seances)): ?>
                <p class="text-muted p-4 mb-0">Aucune séance enregistrée. <a href="programme.php">Commencer une séance</a></p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="thead-light">
                            <tr><th>Entraînement</th><th>Durée</th><th>Calories</th><th>Intensité</th><th>Date</th><th></th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($seances as $s): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($s['entrainement_nom']) ?></strong><br><small class="text-muted"><?= htmlspecialchars($s['type_sport']) ?></small></td>
                            <td><?= $s['duree_minutes'] ?> min</td>
                            <td><strong style="color:#f5576c;"><?= number_format($s['calories'],0) ?></strong> kcal</td>
                            <td><span style="font-size:.78rem;"><?= ucfirst($s['intensite']) ?></span></td>
                            <td><small><?= date('d/m/Y', strtotime($s['completed_at'])) ?></small></td>
                            <td>
                                <a href="profil.php?del_seance=<?= $s['id'] ?>" class="text-danger" style="font-size:.82rem;"
                                   onclick="return confirm('Supprimer ?')"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
if (isset($_GET['del_seance'])) {
    $sC->delete((int)$_GET['del_seance'], $uid);
    flash('warning', 'Séance supprimée.');
    header('Location: profil.php'); exit;
}
require_once __DIR__ . '/partials/layout_bottom.php';
?>
