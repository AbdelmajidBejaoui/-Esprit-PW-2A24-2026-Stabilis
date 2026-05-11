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
.ai-box { background:var(--bg-elevated); border:1px solid var(--border-light); border-radius:var(--radius-lg); box-shadow:0 12px 28px rgba(21,63,49,.08); overflow:hidden; }
.ai-box-head { padding:18px 20px; border-bottom:1px solid var(--border-light); background:linear-gradient(180deg,#FFFFFF 0%,#FBFDFB 100%); }
.ai-box-head h3 { margin:0; color:var(--text-primary); }
.ai-box-body { padding:20px; }
.ai-box-actions { display:flex; gap:12px; flex-wrap:wrap; margin-top:18px; }
.nutrition-page-title { margin:0 0 22px; color:var(--accent-herb-dark); font-size:38px; line-height:1.15; font-weight:850; letter-spacing:0; }
@media (max-width:900px){ .improve-layout{grid-template-columns:1fr;} }
</style>

<h1 class="nutrition-page-title">Auto amelioration recettes</h1>
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
            <div class="ai-box-head"><h3>Analyse IA</h3></div>
            <div class="ai-box-body">
                <p class="text-muted">Choisissez une recette pour obtenir une proposition d amelioration.</p>
            </div>
        <?php else: ?>
            <div class="ai-box-head"><h3><?php echo htmlspecialchars($selected['nom']); ?></h3></div>
            <div class="ai-box-body">
                <p><?php echo nl2br(htmlspecialchars($aiText)); ?></p>
                <?php if ($tips): ?>
                    <h4>Actions automatiques possibles</h4>
                    <ul>
                        <?php foreach ($tips as $tip): ?><li><?php echo htmlspecialchars($tip['label']); ?></li><?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <form method="POST" class="ai-box-actions">
                    <input type="hidden" name="apply_id" value="<?php echo (int)$selected['id']; ?>">
                    <button class="btn-primary" type="submit"><i class="fas fa-magic"></i> Creer version amelioree</button>
                    <a class="btn-secondary" href="recette-ajout.php?id=<?php echo (int)$selected['id']; ?>">Modifier manuellement</a>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
