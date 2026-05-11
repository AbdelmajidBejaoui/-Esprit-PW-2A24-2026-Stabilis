<?php
require_once __DIR__ . '/../../Controller/EntrainementC.php';
require_once __DIR__ . '/../../Model/Entrainement.php';
$eC = new EntrainementC();
$errors = [];
$sports = ['Course à pied','Musculation','Yoga','HIIT','Cyclisme','Natation','Football','Basketball','Tennis','Boxe','Pilates','CrossFit','Escalade','Cardio','Marche','Kettlebell','TRX','Aviron','Récupération','Autre'];

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$ent = $eC->getById($id);
if (!$ent) { header('Location: listEntrainements.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = EntrainementC::validate($_POST);
    if (empty($errors)) {
        $ent->setNom(trim(htmlspecialchars($_POST['nom'])));
        $ent->setDescription(trim(htmlspecialchars($_POST['description']??'')));
        $ent->setTypeSport($_POST['type_sport']);
        $ent->setNiveau($_POST['niveau']);
        $ent->setMetValue((float)$_POST['met_value']);
        $eC->update($ent);
        $_SESSION['flash'] = ['type'=>'success','msg'=>'Entraînement mis à jour !'];
        header('Location: listEntrainements.php'); exit;
    }
}

$pageTitle='Modifier Entraînement'; $activePage='entrainements';
$breadcrumb='<li class="breadcrumb-item"><a href="listEntrainements.php">Entraînements</a></li><li class="breadcrumb-item active">Modifier</li>';
require_once __DIR__ . '/partials/layout_top.php';
?>
<div class="row justify-content-center">
<div class="col-md-8">
<div class="card card-warning">
    <div class="card-header"><h3 class="card-title">Modifier : <?= htmlspecialchars($ent->getNom()) ?></h3></div>
    <form method="POST" action="">
    <div class="card-body">
        <?php if(!empty($errors)): ?><div class="alert alert-danger"><ul class="mb-0 error-list"><?php foreach($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
        <div class="form-group"><label>Nom <span class="text-danger">*</span></label>
            <input type="text" name="nom" class="form-control" value="<?= htmlspecialchars($_POST['nom'] ?? $ent->getNom()) ?>"></div>
        <div class="form-group"><label>Description</label>
            <textarea name="description" rows="3" class="form-control"><?= htmlspecialchars($_POST['description'] ?? $ent->getDescription()) ?></textarea></div>
        <div class="row">
            <div class="col-md-5"><div class="form-group"><label>Sport <span class="text-danger">*</span></label>
                <select name="type_sport" class="form-control"><option value="">--</option>
                <?php foreach($sports as $s): ?><option value="<?= htmlspecialchars($s) ?>" <?= ($_POST['type_sport']??$ent->getTypeSport())===$s?'selected':'' ?>><?= htmlspecialchars($s) ?></option><?php endforeach; ?>
                </select></div></div>
            <div class="col-md-4"><div class="form-group"><label>Niveau <span class="text-danger">*</span></label>
                <select name="niveau" class="form-control">
                    <?php foreach(['debutant'=>'Débutant','intermediaire'=>'Intermédiaire','avance'=>'Avancé'] as $v=>$l): ?>
                    <option value="<?= $v ?>" <?= ($_POST['niveau']??$ent->getNiveau())===$v?'selected':'' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select></div></div>
            <div class="col-md-3"><div class="form-group"><label>MET <span class="text-danger">*</span></label>
                <input type="number" name="met_value" class="form-control" step="0.1"
                       value="<?= htmlspecialchars($_POST['met_value'] ?? $ent->getMetValue()) ?>"></div></div>
        </div>
    </div>
    <div class="card-footer">
        <button type="submit" class="btn btn-warning"><i class="fas fa-save mr-1"></i>Mettre à jour</button>
        <a href="listEntrainements.php" class="btn btn-default ml-2">Annuler</a>
    </div>
    </form>
</div>
</div>
</div>
<?php require_once __DIR__ . '/partials/layout_bottom.php'; ?>
