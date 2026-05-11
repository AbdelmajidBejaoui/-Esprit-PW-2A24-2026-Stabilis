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
.nutrition-filter { display:flex; gap:10px; flex-wrap:wrap; justify-content:flex-end; align-items:center; }
.nutrition-filter input { width:260px; }
.nutrition-filter select { min-width:180px; }
.macro-pill { display:inline-flex; padding:5px 9px; border-radius:999px; background:#eef6f0; color:#1A4D3A; font-weight:800; font-size:12px; white-space:nowrap; }
.nutrition-name-cell strong { color:#1A4D3A; }
.nutrition-name-cell span { display:block; max-width:520px; margin-top:3px; line-height:1.35; }
@media (max-width:900px){ .nutrition-filter{justify-content:flex-start;} }
</style>

<div class="table-card">
    <div class="table-header">
        <div>
            <h3>Catalogue aliments</h3>
            <?php if ($search !== ''): ?><div class="text-muted" style="margin-top: 8px;">Resultats pour "<?php echo htmlspecialchars($search); ?>"</div><?php endif; ?>
        </div>
        <form class="nutrition-filter" method="GET">
            <input class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Recherche aliment">
            <select class="form-control" id="alimentSort" name="sort">
                <option value="nom_asc" <?php echo $sort === 'nom_asc' ? 'selected' : ''; ?>>Nom A-Z</option>
                <option value="calories_desc" <?php echo $sort === 'calories_desc' ? 'selected' : ''; ?>>Calories hautes</option>
                <option value="proteines_desc" <?php echo $sort === 'proteines_desc' ? 'selected' : ''; ?>>Proteines hautes</option>
                <option value="eco_desc" <?php echo $sort === 'eco_desc' ? 'selected' : ''; ?>>Eco score haut</option>
            </select>
            <button class="btn-secondary" type="submit">Rechercher</button>
        </form>
        <span class="record-count"><?php echo count($aliments); ?> resultat(s)</span>
    </div>
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
    <div class="table-header" style="border-top: 1px solid var(--border-light);">
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <a class="btn-primary" href="aliment-ajout.php"><i class="fas fa-plus"></i> Ajouter un aliment</a>
            <button class="btn-secondary" type="button" id="exportAlimentsPdf"><i class="fas fa-file-pdf"></i> Exporter</button>
        </div>
    </div>
</div>

<script>
document.getElementById('exportAlimentsPdf')?.addEventListener('click', function () {
    window.exportStyledBackofficeTableToPdf({ tableSelector: '#alimentsTable', title: 'Aliments - Stabilis', filename: 'aliments_stabilis.pdf' });
});
</script>
<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
