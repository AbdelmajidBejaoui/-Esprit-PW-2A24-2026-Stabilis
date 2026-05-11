<?php
require_once __DIR__ . '/../../../Controllers/NutritionController.php';

$controller = new NutritionController();
$model = $controller->model();
$selectedId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = '';

if (isset($_POST['apply_id'])) {
    $newId = $model->applyImprovedCopy((int)$_POST['apply_id']);
    $message = $newId ? 'Version amelioree creee.' : 'Impossible de creer la version amelioree.';
}

$unbalanced = $model->unbalancedRecipes();
$selected = $selectedId ? $model->recette($selectedId) : null;
$aiText = $selected ? $controller->improvementText($selectedId) : '';
$tips = $selected ? $model->proposeImprovements($selectedId) : [];
$title = 'Auto amelioration recettes - Stabilis';
require_once __DIR__ . '/../../partials/header.php';
?>

<style>
.improve-layout { display:grid; grid-template-columns:1fr 1fr; gap:22px; align-items:start; }
.issue-list { display:grid; gap:10px; }
.issue-card { display:flex; justify-content:space-between; gap:12px; background:#fff; border:1px solid var(--border-light); border-radius:10px; padding:14px; }
.ai-box { background:#fff; border:1px solid var(--border-light); border-left:4px solid var(--accent-herb); border-radius:12px; padding:18px; box-shadow:var(--shadow-sm); }
@media (max-width:900px){ .improve-layout{grid-template-columns:1fr;} }
</style>

<h1>Auto amelioration des recettes par IA</h1>
<?php if ($message): ?><div class="alert"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>

<div class="improve-layout">
    <div class="table-card">
        <div class="table-header"><h3>Recettes a ameliorer</h3><span class="record-count"><?php echo count($unbalanced); ?> detectee(s)</span></div>
        <div class="issue-list" style="padding:18px;">
            <?php if (!$unbalanced): ?><p class="text-muted">Aucune recette desequilibree.</p><?php endif; ?>
            <?php foreach ($unbalanced as $item): ?>
                <div class="issue-card">
                    <div>
                        <strong><?php echo htmlspecialchars($item['recipe']['nom']); ?></strong>
                        <div class="text-muted"><?php echo htmlspecialchars(implode(', ', $item['issues'])); ?></div>
                    </div>
                    <a class="btn-secondary" href="amelioration.php?id=<?php echo (int)$item['recipe']['id']; ?>">Analyser</a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="ai-box">
        <?php if (!$selected): ?>
            <h3>Analyse IA</h3>
            <p class="text-muted">Choisissez une recette pour obtenir une proposition d amelioration.</p>
        <?php else: ?>
            <h3><?php echo htmlspecialchars($selected['nom']); ?></h3>
            <p><?php echo nl2br(htmlspecialchars($aiText)); ?></p>
            <?php if ($tips): ?>
                <h4>Actions automatiques possibles</h4>
                <ul>
                    <?php foreach ($tips as $tip): ?><li><?php echo htmlspecialchars($tip['label']); ?></li><?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <form method="POST">
                <input type="hidden" name="apply_id" value="<?php echo (int)$selected['id']; ?>">
                <button class="btn-primary" type="submit"><i class="fas fa-wand-magic-sparkles"></i> Creer version amelioree</button>
                <a class="btn-secondary" href="recettes.php?edit=<?php echo (int)$selected['id']; ?>">Modifier manuellement</a>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
