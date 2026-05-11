<?php
require_once __DIR__ . '/partials/auth.php';
requireLogin();
require_once __DIR__ . '/../../Controller/EntrainementC.php';
require_once __DIR__ . '/../../Controller/SeanceC.php';
require_once __DIR__ . '/../../Model/Seance.php';

$eC  = new EntrainementC();
$sC  = new SeanceC();
$uid = frontUserId();
$ud  = frontUser();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$entrainement = $eC->getById($id);
if (!$entrainement) { header('Location: programme.php'); exit; }

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = SeanceC::validate($_POST);
    if (empty($errors)) {
        $poids = !empty($ud['poids']) ? (float)$ud['poids'] : 70.0; // défaut 70kg si pas de profil
        $s = new Seance(null, $uid, $id,
            (int)$_POST['duree_minutes'],
            0, // calories calculées auto dans enregistrer()
            $_POST['intensite'],
            !empty($_POST['fc_moyenne']) ? (int)$_POST['fc_moyenne'] : null,
            !empty($_POST['notes']) ? trim(htmlspecialchars($_POST['notes'])) : null
        );
        $sC->enregistrer($s, $entrainement->getMetValue(), $poids);
        flash('success', 'Séance enregistrée ! Calories calculées automatiquement.');
        header('Location: profil.php'); exit;
    }
}

// Estimation calories en temps réel pour affichage
$poids = !empty($ud['poids']) ? (float)$ud['poids'] : 70.0;
$estim30  = round($entrainement->getMetValue() * $poids * 0.5, 0);
$estim45  = round($entrainement->getMetValue() * $poids * 0.75, 0);
$estim60  = round($entrainement->getMetValue() * $poids * 1.0, 0);

$pageTitle='Compléter séance'; $heroTitle='Enregistrer ma séance'; $heroBg='bg_3.jpg';
$activePage='programme';
$breadcrumb='<span class="mr-2"><a href="programme.php">Mon Programme</a></span><span>Compléter</span>';
require_once __DIR__ . '/partials/layout_top.php';
?>

<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="card card-vege">
            <div class="card-header"><i class="fas fa-check-circle mr-2"></i>Séance : <?= htmlspecialchars($entrainement->getNom()) ?></div>
            <div class="card-body p-4">

                <!-- Info entraînement -->
                <div style="background:#f0f8e8;border-radius:10px;padding:14px;margin-bottom:20px;">
                    <div class="d-flex justify-content-between flex-wrap" style="gap:8px;">
                        <span><i class="fas fa-dumbbell mr-1" style="color:#82ae46;"></i><?= htmlspecialchars($entrainement->getTypeSport()) ?></span>
                        <span><strong style="color:#82ae46;">MET <?= $entrainement->getMetValue() ?></strong></span>
                        <span class="badge-<?= $entrainement->getNiveau() ?>"><?= ucfirst($entrainement->getNiveau()) ?></span>
                    </div>
                </div>

                <!-- Estimation calories -->
                <div style="background:#fff9e6;border:1px solid #ffc107;border-radius:10px;padding:14px;margin-bottom:20px;">
                    <p class="mb-2" style="font-weight:700;font-size:.9rem;color:#856404;"><i class="fas fa-calculator mr-2"></i>Estimation calories (poids : <?= $poids ?>kg)</p>
                    <div class="row text-center">
                        <div class="col-4"><strong style="color:#82ae46;font-size:1.2rem;"><?= $estim30 ?></strong><br><small>30 min</small></div>
                        <div class="col-4"><strong style="color:#f5576c;font-size:1.4rem;"><?= $estim45 ?></strong><br><small>45 min</small></div>
                        <div class="col-4"><strong style="color:#82ae46;font-size:1.2rem;"><?= $estim60 ?></strong><br><small>60 min</small></div>
                    </div>
                    <?php if(empty($ud['poids'])): ?>
                    <p class="mt-2 mb-0 text-muted" style="font-size:.78rem;"><i class="fas fa-info-circle mr-1"></i>Complétez votre <a href="profil.php">profil</a> pour des estimations précises (poids utilisé: 70kg par défaut).</p>
                    <?php endif; ?>
                </div>

                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger"><ul class="mb-0 error-list"><?php foreach($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label style="font-weight:600;">Durée réelle (minutes) <span class="text-danger">*</span></label>
                                <input type="number" name="duree_minutes" class="form-control"
                                       value="<?= htmlspecialchars($_POST['duree_minutes']??'45') ?>"
                                       placeholder="Ex: 45" style="border-radius:8px;">
                                <small class="text-muted">Les calories seront calculées automatiquement.</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label style="font-weight:600;">Intensité ressentie <span class="text-danger">*</span></label>
                                <select name="intensite" class="form-control" style="border-radius:8px;">
                                    <option value="">-- Choisir --</option>
                                    <?php foreach(['faible'=>'Faible','moderee'=>'Modérée','elevee'=>'Élevée','maximale'=>'Maximale'] as $v=>$l): ?>
                                    <option value="<?= $v ?>" <?= ($_POST['intensite']??'')===$v?'selected':'' ?>><?= $l ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label style="font-weight:600;">Fréquence cardiaque moyenne (bpm) <small class="text-muted">— optionnel</small></label>
                        <input type="number" name="fc_moyenne" class="form-control"
                               value="<?= htmlspecialchars($_POST['fc_moyenne']??'') ?>"
                               placeholder="Ex: 145" style="border-radius:8px;">
                        <small class="text-muted">Entre 40 et 250 bpm.</small>
                    </div>
                    <div class="form-group">
                        <label style="font-weight:600;">Notes personnelles</label>
                        <textarea name="notes" rows="3" class="form-control" style="border-radius:8px;"
                                  placeholder="Comment s'est passée la séance ?"><?= htmlspecialchars($_POST['notes']??'') ?></textarea>
                    </div>
                    <div class="d-flex" style="gap:10px;">
                        <button type="submit" class="btn btn-vege px-4" style="border-radius:30px;">
                            <i class="fas fa-save mr-2"></i>Enregistrer la séance
                        </button>
                        <a href="programme.php" class="btn btn-outline-secondary px-4" style="border-radius:30px;">Annuler</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/partials/layout_bottom.php'; ?>
