<?php
require_once __DIR__ . '/../../../Controllers/NutritionController.php';

$controller = new NutritionController();
$model = $controller->model();

if (isset($_GET['delete'])) {
    $model->deleteRecette((int)$_GET['delete']);
    header('Location: recettes.php?deleted=1');
    exit;
}

$search = trim($_GET['search'] ?? '');
$sort = $_GET['sort'] ?? 'performance_desc';
$recettes = $model->recettes($search, $sort);
$title = 'Liste recettes - Stabilis';
require_once __DIR__ . '/../../partials/header.php';
?>

<style>
.nutrition-filter { display:flex; gap:10px; flex-wrap:wrap; justify-content:flex-end; align-items:center; }
.nutrition-filter input { width:260px; }
.nutrition-filter select { min-width:190px; }
.score-pill { display:inline-flex; padding:5px 10px; border-radius:999px; background:var(--accent-herb-light); color:var(--accent-herb-dark); font-weight:800; font-size:12px; white-space:nowrap; }
.nutrition-name-cell strong { color:#1A4D3A; }
.nutrition-name-cell span { display:block; max-width:520px; margin-top:3px; line-height:1.35; }
@media (max-width:900px){ .nutrition-filter{justify-content:flex-start;} }
</style>

<div class="table-card">
    <div class="table-header">
        <div>
            <h3>Catalogue recettes</h3>
            <?php if ($search !== ''): ?><div class="text-muted" style="margin-top: 8px;">Resultats pour "<?php echo htmlspecialchars($search); ?>"</div><?php endif; ?>
        </div>
        <form class="nutrition-filter" method="GET">
            <input class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Recherche recette">
            <select class="form-control" id="recetteSort" name="sort">
                <option value="performance_desc" <?php echo $sort === 'performance_desc' ? 'selected' : ''; ?>>Performance haute</option>
                <option value="nom_asc" <?php echo $sort === 'nom_asc' ? 'selected' : ''; ?>>Nom A-Z</option>
                <option value="calories_asc" <?php echo $sort === 'calories_asc' ? 'selected' : ''; ?>>Calories basses</option>
                <option value="proteines_desc" <?php echo $sort === 'proteines_desc' ? 'selected' : ''; ?>>Proteines hautes</option>
            </select>
            <button class="btn-secondary" type="submit">Rechercher</button>
        </form>
        <span class="record-count"><?php echo count($recettes); ?> resultat(s)</span>
    </div>
    <div class="table-responsive">
        <table id="recettesTable">
            <thead><tr><th>Nom</th><th>Calories</th><th>Proteines</th><th>Lipides</th><th>Eco</th><th>Performance</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($recettes as $r): ?>
                <tr>
                    <td class="nutrition-name-cell"><strong><?php echo htmlspecialchars($r['nom']); ?></strong><span class="text-muted"><?php echo htmlspecialchars($r['description'] ?? ''); ?></span></td>
                    <td><?php echo (int)$r['totals']['calories']; ?> kcal</td>
                    <td><?php echo number_format((float)$r['totals']['proteines'], 1); ?> g</td>
                    <td><?php echo number_format((float)$r['totals']['lipides'], 1); ?> g</td>
                    <td><?php echo number_format((float)$r['totals']['eco_score'], 1); ?>/10</td>
                    <td><span class="score-pill"><?php echo number_format((float)$r['performance_score'], 1); ?>/10</span></td>
                    <td>
                        <a class="btn-icon" href="recette-ajout.php?id=<?php echo (int)$r['id']; ?>" title="Modifier"><i class="fas fa-edit"></i></a>
                        <a class="btn-icon" href="amelioration.php?id=<?php echo (int)$r['id']; ?>" title="IA"><i class="fas fa-robot"></i></a>
                        <a class="btn-icon btn-icon-danger" href="recettes.php?delete=<?php echo (int)$r['id']; ?>" title="Supprimer" onclick="return confirm('Supprimer cette recette ?')"><i class="fas fa-trash"></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="table-header" style="border-top: 1px solid var(--border-light);">
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <a class="btn-primary" href="recette-ajout.php"><i class="fas fa-plus"></i> Ajouter une recette</a>
            <button class="btn-secondary" type="button" id="exportRecettesPdf"><i class="fas fa-file-pdf"></i> Exporter</button>
        </div>
    </div>
</div>

<script>
document.getElementById('exportRecettesPdf')?.addEventListener('click', function () {
    window.exportStyledBackofficeTableToPdf({ tableSelector: '#recettesTable', title: 'Recettes - Stabilis', filename: 'recettes_stabilis.pdf' });
});
</script>
<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
