<?php
require_once __DIR__ . '/../../../Controllers/NutritionController.php';

$controller = new NutritionController();
$model = $controller->model();

if (isset($_GET['delete'])) {
    $model->deleteAliment((int)$_GET['delete']);
    header('Location: aliments.php?deleted=1');
    exit;
}

$search = trim($_GET['search'] ?? '');
$sort = $_GET['sort'] ?? 'nom_asc';
$aliments = $model->aliments($search, $sort);
$title = 'Liste aliments - Stabilis';
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
.sort-field select { min-width:180px; color:#1A4D3A; font-weight:700; }
.quick-sort-row { display:flex; gap:8px; flex-wrap:wrap; margin:0 0 18px; }
.quick-sort-row a { display:inline-flex; align-items:center; gap:7px; padding:9px 12px; border:1px solid #dfeae2; border-radius:999px; background:#fff; color:#1A4D3A; text-decoration:none; font-weight:800; font-size:13px; }
.quick-sort-row a.active { background:#1A4D3A; color:#fff; border-color:#1A4D3A; }
.macro-pill { display:inline-flex; padding:5px 9px; border-radius:999px; background:#eef6f0; color:#1A4D3A; font-weight:800; font-size:12px; white-space:nowrap; }
.nutrition-name-cell strong { color:#1A4D3A; }
.nutrition-name-cell span { display:block; max-width:520px; margin-top:3px; line-height:1.35; }
@media (max-width:900px){ .nutrition-list-hero{align-items:flex-start; flex-direction:column;} .nutrition-filter{justify-content:flex-start;} }
</style>

<div class="nutrition-list-hero">
    <div>
        <h1><i class="fas fa-apple-alt"></i> Liste aliments</h1>
        <p>Consultez, triez, exportez et corrigez le catalogue nutritionnel.</p>
    </div>
    <form class="nutrition-filter" method="GET">
        <input class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Recherche aliment">
        <div class="sort-field">
            <label for="alimentSort">Trier</label>
            <select class="form-control" id="alimentSort" name="sort">
                <option value="nom_asc" <?php echo $sort === 'nom_asc' ? 'selected' : ''; ?>>Nom A-Z</option>
                <option value="calories_desc" <?php echo $sort === 'calories_desc' ? 'selected' : ''; ?>>Calories hautes</option>
                <option value="proteines_desc" <?php echo $sort === 'proteines_desc' ? 'selected' : ''; ?>>Proteines hautes</option>
                <option value="eco_desc" <?php echo $sort === 'eco_desc' ? 'selected' : ''; ?>>Eco score haut</option>
            </select>
        </div>
        <button class="btn-secondary" type="submit"><i class="fas fa-search"></i></button>
        <button class="btn-secondary" type="button" id="exportAlimentsPdf"><i class="fas fa-file-pdf"></i></button>
        <a class="btn-primary" href="aliment-ajout.php"><i class="fas fa-plus"></i> Ajouter</a>
    </form>
</div>

<div class="quick-sort-row">
    <a class="<?php echo $sort === 'nom_asc' ? 'active' : ''; ?>" href="?search=<?php echo urlencode($search); ?>&sort=nom_asc"><i class="fas fa-arrow-down-a-z"></i> Nom</a>
    <a class="<?php echo $sort === 'proteines_desc' ? 'active' : ''; ?>" href="?search=<?php echo urlencode($search); ?>&sort=proteines_desc"><i class="fas fa-dumbbell"></i> Proteines</a>
    <a class="<?php echo $sort === 'calories_desc' ? 'active' : ''; ?>" href="?search=<?php echo urlencode($search); ?>&sort=calories_desc"><i class="fas fa-fire"></i> Calories</a>
    <a class="<?php echo $sort === 'eco_desc' ? 'active' : ''; ?>" href="?search=<?php echo urlencode($search); ?>&sort=eco_desc"><i class="fas fa-leaf"></i> Eco score</a>
</div>

<div class="table-card">
    <div class="table-header"><h3>Catalogue aliments</h3><span class="record-count"><?php echo count($aliments); ?> resultat(s)</span></div>
    <div class="table-responsive">
        <table id="alimentsTable">
            <thead><tr><th>Nom</th><th>Calories</th><th>Proteines</th><th>Glucides</th><th>Lipides</th><th>Eco</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($aliments as $a): ?>
                <tr>
                    <td class="nutrition-name-cell"><strong><?php echo htmlspecialchars($a['nom']); ?></strong><span class="text-muted"><?php echo htmlspecialchars($a['description'] ?? ''); ?></span></td>
                    <td><?php echo (int)$a['calories']; ?></td>
                    <td><?php echo number_format((float)$a['proteines'], 1); ?> g</td>
                    <td><?php echo number_format((float)$a['glucides'], 1); ?> g</td>
                    <td><?php echo number_format((float)$a['lipides'], 1); ?> g</td>
                    <td><span class="macro-pill"><?php echo $model->ecoScore($a); ?>/10</span></td>
                    <td>
                        <a class="btn-icon" href="aliment-ajout.php?id=<?php echo (int)$a['id']; ?>" title="Modifier"><i class="fas fa-edit"></i></a>
                        <a class="btn-icon btn-icon-danger" href="aliments.php?delete=<?php echo (int)$a['id']; ?>" title="Supprimer" onclick="return confirm('Supprimer cet aliment ?')"><i class="fas fa-trash"></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.getElementById('exportAlimentsPdf')?.addEventListener('click', function () {
    window.exportStyledBackofficeTableToPdf({ tableSelector: '#alimentsTable', title: 'Aliments - Stabilis', filename: 'aliments_stabilis.pdf' });
});
</script>
<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
