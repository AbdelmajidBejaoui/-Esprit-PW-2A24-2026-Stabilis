<?php
require_once __DIR__ . '/../../../Controllers/ParticipationController.php';

$controller = new ParticipationController();
$search = trim($_GET['search'] ?? '');
$sort = $_GET['sort'] ?? 'recent';
$allowedSorts = ['recent', 'oldest', 'user_asc', 'defi_asc', 'progress_desc', 'progress_asc', 'status_asc', 'date_desc', 'date_asc'];
if (!in_array($sort, $allowedSorts, true)) {
    $sort = 'recent';
}
$participations = $controller->getAll($search, $sort);
$title = 'Participations defis - Stabilis';
require_once __DIR__ . '/../../partials/header.php';
?>

<style>
.progress-wrap { width:120px; height:9px; background:#edf1ed; border-radius:999px; overflow:hidden; }
.progress-bar-mini { height:100%; background:var(--accent-herb); }
.status-pill { display:inline-flex; padding:5px 10px; border-radius:999px; font-size:12px; font-weight:800; background:#fff3cd; color:#8a6d1f; }
.status-pill.completed { background:var(--accent-herb-light); color:var(--accent-herb-dark); }
.status-pill.failed { background:#fdecea; color:#b94a48; }
.proof-badge { display:inline-flex; padding:4px 8px; border-radius:999px; background:#eef6f9; color:#2f647d; font-size:12px; font-weight:700; }
.participation-toolbar { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
.participation-toolbar input { width:260px; }
.participation-toolbar select { width:200px; }
@media (max-width:760px){ .participation-toolbar,.participation-toolbar input,.participation-toolbar select{width:100%;} }
</style>

<div class="table-header" style="margin-bottom:18px;">
    <h1>Participations aux defis</h1>
    <form method="GET" class="participation-toolbar">
        <input class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Recherche: ID / utilisateur / defi">
        <select class="form-control" name="sort" title="Trier">
            <option value="recent" <?php echo $sort === 'recent' ? 'selected' : ''; ?>>Trier: plus recent</option>
            <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Plus ancien</option>
            <option value="user_asc" <?php echo $sort === 'user_asc' ? 'selected' : ''; ?>>Utilisateur A-Z</option>
            <option value="defi_asc" <?php echo $sort === 'defi_asc' ? 'selected' : ''; ?>>Defi A-Z</option>
            <option value="progress_desc" <?php echo $sort === 'progress_desc' ? 'selected' : ''; ?>>Progression haute</option>
            <option value="progress_asc" <?php echo $sort === 'progress_asc' ? 'selected' : ''; ?>>Progression basse</option>
            <option value="status_asc" <?php echo $sort === 'status_asc' ? 'selected' : ''; ?>>Statut</option>
            <option value="date_desc" <?php echo $sort === 'date_desc' ? 'selected' : ''; ?>>Date recente</option>
            <option value="date_asc" <?php echo $sort === 'date_asc' ? 'selected' : ''; ?>>Date ancienne</option>
        </select>
        <button class="btn-secondary" type="submit"><i class="fas fa-search"></i></button>
        <a class="btn-primary" href="ajout.php"><i class="fas fa-plus"></i> Ajouter</a>
        <button class="btn-secondary" type="button" id="exportParticipationsPdf"><i class="fas fa-file-pdf"></i> Exporter PDF</button>
        <a class="btn-secondary" href="../defis/liste.php"><i class="fas fa-trophy"></i> Defis</a>
    </form>
</div>

<div class="table-card">
    <div class="table-header">
        <h3>Suivi des participations</h3>
        <span class="record-count"><?php echo count($participations); ?> participation(s)</span>
    </div>
    <div class="table-responsive">
        <table id="participationsTable">
            <thead><tr><th>ID</th><th>Utilisateur</th><th>Defi</th><th>Progression</th><th>Statut</th><th>Preuves</th><th>Dates</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if (!$participations): ?><tr><td colspan="8" class="text-center">Aucune participation.</td></tr><?php endif; ?>
            <?php foreach ($participations as $p): ?>
                <tr>
                    <td><?php echo (int)$p['id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($p['utilisateur_nom'] ?? ('#' . $p['id_utilisateur'])); ?></strong><br><span class="text-muted"><?php echo htmlspecialchars($p['utilisateur_email'] ?? ''); ?></span></td>
                    <td><?php echo htmlspecialchars($p['defi_nom'] ?? 'Defi supprime'); ?></td>
                    <td><div class="progress-wrap"><div class="progress-bar-mini" style="width:<?php echo (int)$p['progression']; ?>%;"></div></div><span class="text-muted"><?php echo (int)$p['progression']; ?>%</span></td>
                    <td><span class="status-pill <?php echo htmlspecialchars($p['statut']); ?>"><?php echo htmlspecialchars($p['statut']); ?></span></td>
                    <td><span class="proof-badge"><?php echo (int)$p['proof_count']; ?> preuve(s)</span><?php if ((int)$p['pending_proof_count'] > 0): ?><br><span class="text-muted"><?php echo (int)$p['pending_proof_count']; ?> en attente</span><?php endif; ?></td>
                    <td><?php echo htmlspecialchars($p['date_debut']); ?><?php if ($p['date_fin']): ?><br><?php echo htmlspecialchars($p['date_fin']); ?><?php endif; ?></td>
                    <td>
                        <a class="btn-icon" href="modifier.php?id=<?php echo (int)$p['id']; ?>" title="Modifier"><i class="fas fa-edit"></i></a>
                        <a class="btn-icon btn-icon-danger" href="supprimer.php?id=<?php echo (int)$p['id']; ?>" title="Supprimer"><i class="fas fa-trash"></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.getElementById('exportParticipationsPdf')?.addEventListener('click', function () {
    window.exportStyledBackofficeTableToPdf({
        tableSelector: '#participationsTable',
        title: 'Liste des participations - Stabilis',
        filename: 'participations_defis_stabilis.pdf',
    });
});
</script>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
