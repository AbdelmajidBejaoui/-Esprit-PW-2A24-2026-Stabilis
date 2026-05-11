<?php
require_once __DIR__ . '/../../../Controllers/NutritionController.php';

$controller = new NutritionController();
$model = $controller->model();
$errors = [];
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$editing = $id ? $model->recette($id) : null;
$editingIngredients = $id ? $model->ingredients($id) : [];
$aliments = $model->aliments();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    [$ok, $errors] = $controller->saveRecette($_POST, ($_POST['id'] ?? '') !== '' ? (int)$_POST['id'] : null);
    if ($ok) {
        header('Location: recettes.php?success=1');
        exit;
    }
}

$title = ($editing ? 'Modifier recette' : 'Ajouter recette') . ' - Stabilis';
require_once __DIR__ . '/../../partials/header.php';
?>

<style>
.nutrition-form-card { max-width:700px; margin:0 auto 28px; }
.nutrition-form-head { padding:24px; border-bottom:1px solid var(--border-light); }
.nutrition-form-head h3 { margin:0; }
.nutrition-form-head p { margin-top:8px; }
.nutrition-form-body { padding:32px; }
.nutrition-form-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
.nutrition-form-grid .wide { grid-column:1 / -1; }
.ingredient-grid { display:grid; grid-template-columns:2fr 1fr 1fr; gap:8px; margin-bottom:8px; }
.ingredients-box { padding:14px; border:1px solid var(--border-light); border-radius:12px; background:#fbfdfb; margin-bottom:12px; }
.nutrition-form-card .form-group { margin-bottom:0; }
.nutrition-form-card label { display:block; margin-bottom:8px; }
.nutrition-form-card .form-control { width:100%; }
.nutrition-form-card textarea.form-control { min-height:128px; resize:vertical; }
.nutrition-form-card h3 { margin:24px 0 12px; }
.nutrition-form-actions { display:flex; gap:12px; flex-wrap:wrap; margin-top:32px; }
@media (max-width:760px){ .nutrition-form-grid,.ingredient-grid{grid-template-columns:1fr;} }
</style>

<?php if ($errors): ?><div class="alert"><ul><?php foreach ($errors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?></ul></div><?php endif; ?>

<div class="form-card nutrition-form-card">
    <div class="nutrition-form-head">
        <h3><?php echo $editing ? 'Modifier recette' : 'Nouvelle recette'; ?></h3>
        <p class="text-muted">Construisez une recette avec ingredients, quantites et instructions.</p>
    </div>
    <div class="nutrition-form-body">
        <form method="POST">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($editing['id'] ?? ''); ?>">
            <div class="nutrition-form-grid">
                <div class="form-group"><label>Nom</label><input class="form-control" name="nom" value="<?php echo htmlspecialchars($editing['nom'] ?? ''); ?>"></div>
                <div class="form-group"><label>Description</label><input class="form-control" name="description" value="<?php echo htmlspecialchars($editing['description'] ?? ''); ?>"></div>
                <div class="form-group wide"><label>Instructions</label><textarea class="form-control" name="instructions" rows="4"><?php echo htmlspecialchars($editing['instructions'] ?? ''); ?></textarea></div>
            </div>
            <h3>Ingredients</h3>
            <div class="ingredients-box" id="ingredientsList">
                <?php $rows = $editingIngredients ?: [['aliment_id' => '', 'quantite' => '', 'unite' => 'g']]; ?>
                <?php foreach ($rows as $idx => $ing): ?>
                    <div class="ingredient-grid">
                        <select class="form-control" name="ingredients[<?php echo $idx; ?>][aliment_id]">
                            <option value="">Choisir aliment</option>
                            <?php foreach ($aliments as $a): ?>
                                <option value="<?php echo (int)$a['id']; ?>" <?php echo (int)($ing['aliment_id'] ?? 0) === (int)$a['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($a['nom']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input class="form-control" type="number" step="0.1" name="ingredients[<?php echo $idx; ?>][quantite]" value="<?php echo htmlspecialchars($ing['quantite'] ?? ''); ?>" placeholder="Quantite">
                        <input class="form-control" name="ingredients[<?php echo $idx; ?>][unite]" value="<?php echo htmlspecialchars($ing['unite'] ?? 'g'); ?>" placeholder="Unite">
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="nutrition-form-actions">
                <button class="btn-secondary" type="button" id="addIngredient"><i class="fas fa-plus"></i> Ingredient</button>
                <button class="btn-primary" type="submit"><i class="fas fa-save"></i> Enregistrer</button>
                <a class="btn-secondary" href="recettes.php">Annuler</a>
            </div>
        </form>
    </div>
</div>

<script>
let ingredientIndex = <?php echo count($rows); ?>;
const alimentOptions = `<?php foreach ($aliments as $a): ?><option value="<?php echo (int)$a['id']; ?>"><?php echo htmlspecialchars($a['nom'], ENT_QUOTES); ?></option><?php endforeach; ?>`;
document.getElementById('addIngredient')?.addEventListener('click', function () {
    const row = document.createElement('div');
    row.className = 'ingredient-grid';
    row.innerHTML = `<select class="form-control" name="ingredients[${ingredientIndex}][aliment_id]"><option value="">Choisir aliment</option>${alimentOptions}</select><input class="form-control" type="number" step="0.1" name="ingredients[${ingredientIndex}][quantite]" placeholder="Quantite"><input class="form-control" name="ingredients[${ingredientIndex}][unite]" value="g" placeholder="Unite">`;
    ingredientIndex++;
    document.getElementById('ingredientsList').appendChild(row);
});
</script>
<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
