<?php
require_once __DIR__ . '/../../../Controllers/NutritionController.php';

$controller = new NutritionController();
$model = $controller->model();
$errors = [];
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$editing = $id ? $model->aliment($id) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    [$ok, $errors] = $controller->saveAliment($_POST, ($_POST['id'] ?? '') !== '' ? (int)$_POST['id'] : null);
    if ($ok) {
        header('Location: aliments.php?success=1');
        exit;
    }
}

$title = ($editing ? 'Modifier aliment' : 'Ajouter aliment') . ' - Stabilis';
require_once __DIR__ . '/../../partials/header.php';
?>

<style>
.nutrition-form-card { max-width:700px; margin:0 auto 28px; }
.nutrition-form-head { padding:24px; border-bottom:1px solid var(--border-light); }
.nutrition-form-head h3 { margin:0; }
.nutrition-form-head p { margin-top:8px; }
.nutrition-form-body { padding:32px; }
.nutrition-form-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:16px; }
.nutrition-form-grid .wide { grid-column:1 / -1; }
.nutrition-form-card .form-group { margin-bottom:0; }
.nutrition-form-card label { display:block; margin-bottom:8px; }
.nutrition-form-card .form-control { width:100%; }
.nutrition-form-card textarea.form-control { min-height:128px; resize:vertical; }
.nutrition-form-actions { display:flex; gap:12px; flex-wrap:wrap; margin-top:32px; }
@media (max-width:720px){ .nutrition-form-grid{grid-template-columns:1fr;} }
</style>

<?php if ($errors): ?><div class="alert"><ul><?php foreach ($errors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?></ul></div><?php endif; ?>

<div class="form-card nutrition-form-card">
    <div class="nutrition-form-head">
        <h3><?php echo $editing ? 'Modifier aliment' : 'Nouvel aliment'; ?></h3>
        <p class="text-muted">Renseignez les macros pour alimenter les recettes et recommandations.</p>
    </div>
    <div class="nutrition-form-body">
        <form method="POST">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($editing['id'] ?? ''); ?>">
            <div class="nutrition-form-grid">
                <div class="form-group wide"><label>Nom</label><input class="form-control" name="nom" value="<?php echo htmlspecialchars($editing['nom'] ?? ''); ?>"></div>
                <div class="form-group"><label>Calories</label><input class="form-control" type="number" step="1" name="calories" value="<?php echo htmlspecialchars($editing['calories'] ?? '0'); ?>"></div>
                <div class="form-group"><label>Proteines</label><input class="form-control" type="number" step="0.1" name="proteines" value="<?php echo htmlspecialchars($editing['proteines'] ?? '0'); ?>"></div>
                <div class="form-group"><label>Glucides</label><input class="form-control" type="number" step="0.1" name="glucides" value="<?php echo htmlspecialchars($editing['glucides'] ?? '0'); ?>"></div>
                <div class="form-group"><label>Lipides</label><input class="form-control" type="number" step="0.1" name="lipides" value="<?php echo htmlspecialchars($editing['lipides'] ?? '0'); ?>"></div>
                <div class="form-group wide"><label>Description</label><textarea class="form-control" name="description" rows="4"><?php echo htmlspecialchars($editing['description'] ?? ''); ?></textarea></div>
            </div>
            <div class="nutrition-form-actions">
                <button class="btn-primary" type="submit"><i class="fas fa-save"></i> Enregistrer</button>
                <a class="btn-secondary" href="aliments.php">Annuler</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
