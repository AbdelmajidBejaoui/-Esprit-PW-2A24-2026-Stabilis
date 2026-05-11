<?php
require_once __DIR__ . '/../../Controller/EntrainementC.php';
require_once __DIR__ . '/../../Model/Entrainement.php';
$eC = new EntrainementC();
$errors = [];
$sports = ['Course à pied','Musculation','Yoga','HIIT','Cyclisme','Natation','Football','Basketball','Tennis','Boxe','Pilates','CrossFit','Escalade','Cardio','Marche','Kettlebell','TRX','Aviron','Récupération','Autre'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = EntrainementC::validate($_POST);
    if (empty($errors)) {
        $e = new Entrainement(null,
            trim(htmlspecialchars($_POST['nom'])),
            trim(htmlspecialchars($_POST['description']??'')),
            $_POST['type_sport'], $_POST['niveau'],
            (float)($_POST['met_value']??5.0), 0, null
        );
        $eC->insert($e);
        $_SESSION['flash'] = ['type'=>'success','msg'=>'Entraînement ajouté !'];
        header('Location: listEntrainements.php'); exit;
    }
}

$pageTitle='Ajouter Entraînement'; $activePage='entrainements';
$breadcrumb='<li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li><li class="breadcrumb-item"><a href="listEntrainements.php">Entraînements</a></li><li class="breadcrumb-item active">Ajouter</li>';
require_once __DIR__ . '/partials/layout_top.php';
?>
<div class="row justify-content-center">
<div class="col-md-8">
<div class="card card-primary">
    <div class="card-header"><h3 class="card-title">Nouvel entraînement</h3></div>
    <form method="POST" action="">
    <div class="card-body">
        <?php if(!empty($errors)): ?><div class="alert alert-danger"><ul class="mb-0 error-list"><?php foreach($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
        <div class="form-group"><label>Nom <span class="text-danger">*</span></label>
            <input type="text" name="nom" class="form-control" value="<?= htmlspecialchars($_POST['nom']??'') ?>"></div>
        <div class="form-group"><label>Description</label>
            <textarea name="description" rows="3" class="form-control"><?= htmlspecialchars($_POST['description']??'') ?></textarea></div>
        <div class="row">
            <div class="col-md-5"><div class="form-group"><label>Type de sport <span class="text-danger">*</span></label>
                <select name="type_sport" class="form-control"><option value="">-- Choisir --</option>
                <?php foreach($sports as $s): ?><option value="<?= htmlspecialchars($s) ?>" <?= ($_POST['type_sport']??'')===$s?'selected':'' ?>><?= htmlspecialchars($s) ?></option><?php endforeach; ?>
                </select></div></div>
            <div class="col-md-4"><div class="form-group"><label>Niveau <span class="text-danger">*</span></label>
                <select name="niveau" class="form-control">
                    <option value="">--</option>
                    <option value="debutant" <?= ($_POST['niveau']??'')==='debutant'?'selected':'' ?>>Débutant</option>
                    <option value="intermediaire" <?= ($_POST['niveau']??'')==='intermediaire'?'selected':'' ?>>Intermédiaire</option>
                    <option value="avance" <?= ($_POST['niveau']??'')==='avance'?'selected':'' ?>>Avancé</option>
                </select></div></div>
            <div class="col-md-3"><div class="form-group"><label>Valeur MET <span class="text-danger">*</span></label>
                <input type="number" name="met_value" class="form-control" step="0.1" value="<?= htmlspecialchars($_POST['met_value']??'5.0') ?>">
                <small class="text-muted">1.0 – 20.0</small></div></div>
        </div>
    </div>
    <div class="card-footer">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Enregistrer</button>
        <a href="listEntrainements.php" class="btn btn-default ml-2">Annuler</a>
    </div>
    </form>
</div>
</div>
</div>
<?php require_once __DIR__ . '/partials/layout_bottom.php'; ?>
