<?php
require_once __DIR__ . '/../../../Controllers/NutritionController.php';

$controller = new NutritionController();
$model = $controller->model();
$stats = $model->stats();
$issues = $model->validationIssues();
$recommended = $model->recommendRecipes(null, 5);
$title = 'Nutrition - Stabilis';
require_once __DIR__ . '/../../partials/header.php';
?>

<style>
.nutrition-board { background:#f7faf8; border:1px solid #dfeae2; border-radius:20px; overflow:hidden; box-shadow:0 22px 48px rgba(28,54,38,.12); margin-bottom:24px; }
.nutrition-board-head { display:flex; justify-content:space-between; gap:18px; align-items:center; padding:24px 28px; background:linear-gradient(135deg,#1A4D3A,#129F72); color:#fff; }
.nutrition-board-head h1 { margin:0 0 6px; color:#fff; font-size:28px; font-weight:850; }
.nutrition-board-head p { margin:0; color:rgba(255,255,255,.84); }
.nutrition-actions { display:flex; gap:8px; flex-wrap:wrap; justify-content:flex-end; }
.nutrition-actions a,.nutrition-actions button { display:inline-flex; gap:8px; align-items:center; border:0; border-radius:999px; padding:10px 14px; background:#fff; color:#1A4D3A; text-decoration:none; font-weight:800; cursor:pointer; }
.nutrition-board-body { padding:22px 24px 24px; }
.nutrition-feature-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:14px; }
.nutrition-feature { background:#fff; border:1px solid #e4eee6; border-left:4px solid #12B981; border-radius:15px; padding:18px; box-shadow:0 10px 22px rgba(24,39,31,.07); }
.nutrition-feature i { width:42px; height:42px; display:inline-flex; align-items:center; justify-content:center; border-radius:50%; background:#E8F0E9; color:#1A4D3A; margin-bottom:12px; }
.nutrition-feature strong { display:block; color:#20352a; font-size:16px; }
.nutrition-feature span { display:block; margin-top:5px; color:#6f7b72; font-size:13px; line-height:1.45; }
.score-pill { display:inline-flex; padding:5px 10px; border-radius:999px; background:var(--accent-herb-light); color:var(--accent-herb-dark); font-weight:700; font-size:12px; }
@media (max-width:900px){ .nutrition-board-head{align-items:flex-start; flex-direction:column;} .nutrition-feature-grid{grid-template-columns:1fr;} }
</style>

<section class="nutrition-board">
    <div class="nutrition-board-head">
        <div>
            <h1><i class="fas fa-utensils"></i> Nutrition</h1>
            <p>Les statistiques principales sont dans le dashboard general. Ici, gerez les aliments, les recettes et les analyses IA.</p>
        </div>
        <div class="nutrition-actions">
            <button type="button" id="exportNutritionPdf"><i class="fas fa-file-pdf"></i> Exporter</button>
        </div>
    </div>
    <div class="nutrition-board-body">
        <div class="nutrition-feature-grid">
            <div class="nutrition-feature"><i class="fas fa-shield-halved"></i><strong>Validation des donnees</strong><span>Detection des recettes sans ingredient, calories invalides et macros suspectes.</span></div>
            <div class="nutrition-feature"><i class="fas fa-chart-line"></i><strong>Performance nutritionnelle</strong><span>Classement des recettes selon proteines, lipides, calories et eco score.</span></div>
            <div class="nutrition-feature"><i class="fas fa-wand-magic-sparkles"></i><strong>Auto amelioration IA</strong><span>Propositions automatiques pour rendre les recettes plus equilibrees.</span></div>
        </div>
    </div>
</section>

<div class="table-card">
    <div class="table-header"><h3>Detection automatique d erreur par IA</h3><span class="record-count"><?php echo count($issues); ?> alerte(s)</span></div>
    <div class="table-responsive">
        <table id="nutritionIssuesTable">
            <thead><tr><th>Recette</th><th>Probleme detecte</th><th>Performance</th></tr></thead>
            <tbody>
            <?php if (!$issues): ?><tr><td colspan="3" class="text-center">Aucune erreur detectee.</td></tr><?php endif; ?>
            <?php foreach ($issues as $issue): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($issue['recipe']['nom']); ?></strong></td>
                    <td><?php echo htmlspecialchars($issue['issue']); ?></td>
                    <td><span class="score-pill"><?php echo number_format((float)$issue['recipe']['performance_score'], 1); ?>/10</span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="table-card" style="margin-top:22px;">
    <div class="table-header"><h3>Recommandation et performance</h3><span class="record-count"><?php echo count($recommended); ?> recette(s)</span></div>
    <div class="table-responsive">
        <table id="nutritionRecommendedTable">
            <thead><tr><th>Recette</th><th>Calories</th><th>Proteines</th><th>Eco score</th><th>Performance</th></tr></thead>
            <tbody>
            <?php foreach ($recommended as $recipe): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($recipe['nom']); ?></strong></td>
                    <td><?php echo (int)$recipe['totals']['calories']; ?> kcal</td>
                    <td><?php echo number_format((float)$recipe['totals']['proteines'], 1); ?> g</td>
                    <td><?php echo number_format((float)$recipe['totals']['eco_score'], 1); ?>/10</td>
                    <td><span class="score-pill"><?php echo number_format((float)$recipe['performance_score'], 1); ?>/10</span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.getElementById('exportNutritionPdf')?.addEventListener('click', function () {
    window.exportStyledBackofficeTableToPdf({
        tableSelector: '#nutritionRecommendedTable',
        title: 'Nutrition - Recommandations et performance',
        filename: 'nutrition_stabilis.pdf',
    });
});
</script>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
