<?php
require_once __DIR__ . '/../../../Controllers/DefiController.php';

$controller = new DefiController();
$errors = [];
$values = ['id' => '', 'nom' => '', 'type' => 'aliment', 'objectif' => '', 'recompense' => '100 points'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values = $controller->sanitize($_POST);
    $errors = $controller->validate($values, true);
    if (!$errors && $controller->add($values)) {
        header('Location: liste.php');
        exit;
    }
    if (!$errors) {
        $errors[] = 'Erreur lors de l ajout du defi.';
    }
}

$title = 'Ajouter un defi - Stabilis';
require_once __DIR__ . '/../../partials/header.php';
?>

<div class="form-card" style="padding:24px;">
    <h1>Ajouter un defi</h1>
    <?php if ($errors): ?><div class="alert"><ul><?php foreach ($errors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <form method="POST">
        <div class="form-group"><label>ID optionnel</label><input class="form-control" name="id" value="<?php echo htmlspecialchars($values['id']); ?>"></div>
        <div class="form-group"><label>Nom</label><input class="form-control" name="nom" value="<?php echo htmlspecialchars($values['nom']); ?>"></div>
        <div class="form-group"><label>Type</label><select class="form-control" name="type">
            <?php foreach (['aliment','entrainement','compensation'] as $type): ?><option value="<?php echo $type; ?>" <?php echo $values['type'] === $type ? 'selected' : ''; ?>><?php echo $type; ?></option><?php endforeach; ?>
        </select></div>
        <div class="form-group"><label>Objectif</label><textarea class="form-control" name="objectif"><?php echo htmlspecialchars($values['objectif']); ?></textarea></div>
        <div class="form-group"><label>Recompense</label><input class="form-control" name="recompense" value="<?php echo htmlspecialchars($values['recompense']); ?>"></div>
        <button class="btn-primary" type="submit"><i class="fas fa-save"></i> Enregistrer</button>
        <a class="btn-secondary" href="liste.php">Annuler</a>
    </form>
</div>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
