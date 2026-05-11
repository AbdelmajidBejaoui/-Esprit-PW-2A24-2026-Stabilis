<?php
require_once __DIR__ . '/../../../Controllers/DefiController.php';

$controller = new DefiController();
$search = trim($_GET['search'] ?? '');
$sort = $_GET['sort'] ?? 'recent';
$allowedSorts = ['recent', 'oldest', 'name_asc', 'name_desc', 'type_asc', 'reward_desc', 'reward_asc'];
if (!in_array($sort, $allowedSorts, true)) {
    $sort = 'recent';
}
$defis = $controller->getAll($search, $sort);
$totalDefis = $controller->count();
$co2 = $controller->getEcoImpact();
$title = 'Defis - Stabilis';
require_once __DIR__ . '/../../partials/header.php';
?>

<style>
.defis-toolbar { display:flex; justify-content:space-between; align-items:center; gap:14px; margin-bottom:20px; flex-wrap:wrap; }
.defis-actions { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
.defis-search { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
.defis-search input { width:260px; }
.defis-search select { width:190px; }
.defis-search input { border-radius:4px 0 0 4px; background:#fff; }
.defis-search button { border-radius:0 4px 4px 0; min-width:42px; }
.defis-stats { display:grid; grid-template-columns:repeat(2,minmax(220px,1fr)); gap:16px; margin-bottom:22px; }
.defis-stat { background:#fff; border:1px solid var(--border-light); border-left:4px solid var(--accent-herb); border-radius:12px; padding:18px; box-shadow:var(--shadow-sm); }
.defis-stat strong { display:block; font-size:28px; color:var(--accent-herb-dark); }
.defis-stat span { color:var(--text-muted); font-size:13px; }
.type-pill { display:inline-flex; padding:5px 10px; border-radius:999px; font-weight:700; font-size:12px; background:var(--accent-herb-light); color:var(--accent-herb-dark); }
.type-pill.entrainement { background:#fff3cd; color:#8a6d1f; }
.type-pill.compensation { background:#e7f0f7; color:#2f647d; }
.row-actions { white-space:nowrap; }
.row-actions .btn-icon { width:30px; height:30px; justify-content:center; padding:0; border-radius:5px; }
@media (max-width:700px){ .defis-search,.defis-search input,.defis-search select{width:100%;} .defis-stats{grid-template-columns:1fr;} }
</style>

<div class="defis-toolbar">
    <h1>Gestion des defis</h1>
    <div class="defis-actions">
        <form method="GET" class="defis-search">
            <input class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Recherche: ID / nom / type">
            <select class="form-control" name="sort" title="Trier">
                <option value="recent" <?php echo $sort === 'recent' ? 'selected' : ''; ?>>Trier: plus recent</option>
                <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Plus ancien</option>
                <option value="name_asc" <?php echo $sort === 'name_asc' ? 'selected' : ''; ?>>Nom A-Z</option>
                <option value="name_desc" <?php echo $sort === 'name_desc' ? 'selected' : ''; ?>>Nom Z-A</option>
                <option value="type_asc" <?php echo $sort === 'type_asc' ? 'selected' : ''; ?>>Type</option>
                <option value="reward_desc" <?php echo $sort === 'reward_desc' ? 'selected' : ''; ?>>Recompense haute</option>
                <option value="reward_asc" <?php echo $sort === 'reward_asc' ? 'selected' : ''; ?>>Recompense basse</option>
            </select>
            <button class="btn-secondary" type="submit"><i class="fas fa-search"></i></button>
        </form>
        <a class="btn-primary" href="ajout.php"><i class="fas fa-plus"></i> Ajouter</a>
        <button class="btn-secondary" type="button" id="exportDefisPdf"><i class="fas fa-file-pdf"></i> Exporter PDF</button>
        <a class="btn-secondary" href="../participations/liste.php"><i class="fas fa-users"></i> Participations</a>
    </div>
</div>

<div class="defis-stats">
    <div class="defis-stat"><strong><?php echo $totalDefis; ?></strong><span>Defis disponibles</span></div>
    <div class="defis-stat"><strong><?php echo number_format($co2, 1, ',', ' '); ?> kg</strong><span>Impact eco estime</span></div>
</div>

<div class="table-card">
    <div class="table-header">
        <h3>Catalogue des defis</h3>
        <span class="record-count"><?php echo count($defis); ?> resultat(s)</span>
    </div>
    <div class="table-responsive">
        <table id="defisTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Type</th>
                    <th>Objectif</th>
                    <th>Recompense</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($defis)): ?>
                <tr><td colspan="6" class="text-center">Aucun defi trouve.</td></tr>
            <?php endif; ?>
            <?php foreach ($defis as $defi): ?>
                <tr>
                    <td><?php echo (int)$defi['id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($defi['nom']); ?></strong></td>
                    <td><span class="type-pill <?php echo htmlspecialchars($defi['type']); ?>"><?php echo htmlspecialchars($defi['type']); ?></span></td>
                    <td><?php echo htmlspecialchars($defi['objectif']); ?></td>
                    <td><?php echo htmlspecialchars($defi['recompense']); ?></td>
                    <td class="row-actions">
                        <a class="btn-icon" href="modifier.php?id=<?php echo (int)$defi['id']; ?>" title="Modifier"><i class="fas fa-edit"></i></a>
                        <a class="btn-icon btn-icon-danger" href="supprimer.php?id=<?php echo (int)$defi['id']; ?>" title="Supprimer"><i class="fas fa-trash"></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.getElementById('exportDefisPdf')?.addEventListener('click', function () {
    window.exportStyledBackofficeTableToPdf({
        tableSelector: '#defisTable',
        title: 'Liste des defis - Stabilis',
        filename: 'defis_stabilis.pdf',
    });
});
</script>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
