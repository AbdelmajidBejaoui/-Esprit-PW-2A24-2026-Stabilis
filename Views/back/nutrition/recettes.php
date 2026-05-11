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
.nutrition-list-hero { display:flex; justify-content:space-between; gap:18px; align-items:center; margin-bottom:22px; padding:24px 26px; border-radius:20px; background:linear-gradient(135deg,#1A4D3A,#129F72); color:#fff; box-shadow:0 18px 38px rgba(26,77,58,.16); }
.nutrition-list-hero h1 { margin:0 0 6px; color:#fff; font-size:28px; font-weight:850; }
.nutrition-list-hero p { margin:0; color:rgba(255,255,255,.82); }
.nutrition-filter { display:flex; gap:8px; flex-wrap:wrap; justify-content:flex-end; }
.nutrition-filter input { width:260px; }
.nutrition-filter .form-control { border:0; min-height:42px; }
.sort-field { display:flex; align-items:center; gap:8px; padding:0 12px; border-radius:999px; background:#fff; color:#1A4D3A; font-weight:850; }
.sort-field label { margin:0; font-size:12px; text-transform:uppercase; letter-spacing:.35px; white-space:nowrap; }
.sort-field select { min-width:190px; color:#1A4D3A; font-weight:700; }
.quick-sort-row { display:flex; gap:8px; flex-wrap:wrap; margin:0 0 18px; }
.quick-sort-row a { display:inline-flex; align-items:center; gap:7px; padding:9px 12px; border:1px solid #dfeae2; border-radius:999px; background:#fff; color:#1A4D3A; text-decoration:none; font-weight:800; font-size:13px; }
.quick-sort-row a.active { background:#1A4D3A; color:#fff; border-color:#1A4D3A; }
.score-pill { display:inline-flex; padding:5px 10px; border-radius:999px; background:var(--accent-herb-light); color:var(--accent-herb-dark); font-weight:800; font-size:12px; white-space:nowrap; }
.nutrition-name-cell strong { color:#1A4D3A; }
.nutrition-name-cell span { display:block; max-width:520px; margin-top:3px; line-height:1.35; }
@media (max-width:900px){ .nutrition-list-hero{align-items:flex-start; flex-direction:column;} .nutrition-filter{justify-content:flex-start;} }
</style>

<div class="nutrition-list-hero">
    <div>
        <h1><i class="fas fa-bowl-food"></i> Liste recettes</h1>
        <p>Comparez recettes, performance nutritionnelle, eco score et macros.</p>
    </div>
    <form class="nutrition-filter" method="GET">
        <input class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Recherche recette">
        <div class="sort-field">
            <label for="recetteSort">Trier</label>
            <select class="form-control" id="recetteSort" name="sort">
                <option value="performance_desc" <?php echo $sort === 'performance_desc' ? 'selected' : ''; ?>>Performance haute</option>
                <option value="nom_asc" <?php echo $sort === 'nom_asc' ? 'selected' : ''; ?>>Nom A-Z</option>
                <option value="calories_asc" <?php echo $sort === 'calories_asc' ? 'selected' : ''; ?>>Calories basses</option>
                <option value="proteines_desc" <?php echo $sort === 'proteines_desc' ? 'selected' : ''; ?>>Proteines hautes</option>
            </select>
        </div>
        <button class="btn-secondary" type="submit"><i class="fas fa-search"></i></button>
        <button class="btn-secondary" type="button" id="exportRecettesPdf"><i class="fas fa-file-pdf"></i></button>
        <a class="btn-primary" href="recette-ajout.php"><i class="fas fa-plus"></i> Ajouter</a>
    </form>
</div>

<div class="quick-sort-row">
    <a class="<?php echo $sort === 'performance_desc' ? 'active' : ''; ?>" href="?search=<?php echo urlencode($search); ?>&sort=performance_desc"><i class="fas fa-chart-line"></i> Performance</a>
    <a class="<?php echo $sort === 'nom_asc' ? 'active' : ''; ?>" href="?search=<?php echo urlencode($search); ?>&sort=nom_asc"><i class="fas fa-arrow-down-a-z"></i> Nom</a>
    <a class="<?php echo $sort === 'calories_asc' ? 'active' : ''; ?>" href="?search=<?php echo urlencode($search); ?>&sort=calories_asc"><i class="fas fa-fire-flame-simple"></i> Calories basses</a>
    <a class="<?php echo $sort === 'proteines_desc' ? 'active' : ''; ?>" href="?search=<?php echo urlencode($search); ?>&sort=proteines_desc"><i class="fas fa-dumbbell"></i> Proteines</a>
</div>

<div class="table-card">
    <div class="table-header"><h3>Catalogue recettes</h3><span class="record-count"><?php echo count($recettes); ?> resultat(s)</span></div>
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
</div>

<script>
document.getElementById('exportRecettesPdf')?.addEventListener('click', function () {
    window.exportStyledBackofficeTableToPdf({ tableSelector: '#recettesTable', title: 'Recettes - Stabilis', filename: 'recettes_stabilis.pdf' });
});
</script>
<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
