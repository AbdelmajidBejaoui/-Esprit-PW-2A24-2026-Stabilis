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
.nutrition-form-hero { margin-bottom:34px; padding:26px 30px; border-radius:20px; background:linear-gradient(135deg,#1A4D3A,#129F72); color:#fff; box-shadow:0 18px 38px rgba(26,77,58,.16); overflow:hidden; }
.nutrition-form-hero h1 { margin:0 0 6px; color:#fff; font-size:28px; font-weight:850; }
.nutrition-form-hero p { margin:0; color:rgba(255,255,255,.82); }
.nutrition-form-card { max-width:860px; margin:0 auto 28px; padding:28px; border-radius:18px; }
.nutrition-form-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:16px; }
.nutrition-form-grid .wide { grid-column:1 / -1; }
.nutrition-form-card .form-group { margin-bottom:0; }
.nutrition-form-card label { display:block; margin-bottom:8px; color:#6E6E68; font-size:13px; font-weight:800; text-transform:uppercase; letter-spacing:.35px; }
.nutrition-form-card .form-control { width:100%; min-height:48px; border-radius:12px; }
.nutrition-form-card textarea.form-control { min-height:128px; resize:vertical; }
.nutrition-form-actions { display:flex; gap:10px; flex-wrap:wrap; margin-top:22px; }
@media (max-width:720px){ .nutrition-form-grid{grid-template-columns:1fr;} }
</style>

<section class="nutrition-form-hero">
    <h1><i class="fas fa-apple-alt"></i> <?php echo $editing ? 'Modifier aliment' : 'Ajouter aliment'; ?></h1>
    <p>Renseignez les macros propres pour alimenter les recettes et recommandations.</p>
</section>

<?php if ($errors): ?><div class="alert"><ul><?php foreach ($errors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?></ul></div><?php endif; ?>

<div class="form-card nutrition-form-card">
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
            <a class="btn-secondary" href="aliments.php">Voir la liste</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
