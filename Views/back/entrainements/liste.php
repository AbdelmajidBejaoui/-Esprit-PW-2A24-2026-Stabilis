<?php
$title = "Gestion des entrainements - Stabilis";
require_once __DIR__ . '/../../../Views/partials/header.php';
require_once __DIR__ . '/../../../config/entrainements.php';

$db = config::getConnexion();
$filter = $_GET['filter'] ?? 'all';
$where = '';
if ($filter === 'catalogue') {
    $where = 'WHERE e.is_custom = 0';
} elseif ($filter === 'users') {
    $where = 'WHERE e.is_custom = 1';
}

$rows = $db->query(
    "SELECT e.*, u.nom AS user_nom, u.email,
            COUNT(DISTINCT s.id) AS nb_seances,
            COUNT(DISTINCT et.id) AS nb_etapes,
            COALESCE(SUM(s.calories),0) AS calories
     FROM entrainements e
     LEFT JOIN `user` u ON u.id = e.user_id
     LEFT JOIN seances_completees s ON s.entrainement_id = e.id
     LEFT JOIN etapes_exercice et ON et.entrainement_id = e.id
     $where
     GROUP BY e.id
     ORDER BY e.created_at DESC"
)->fetchAll();

$catalogueCount = (int)$db->query("SELECT COUNT(*) FROM entrainements WHERE is_custom = 0")->fetchColumn();
$userCount = (int)$db->query("SELECT COUNT(*) FROM entrainements WHERE is_custom = 1")->fetchColumn();
?>
<style>
    .training-admin-head { display:flex; justify-content:space-between; gap:18px; align-items:end; margin-bottom:22px; }
    .training-admin-head h1 { margin:0; color:var(--accent-herb-dark); font-size:30px; }
    .training-admin-head p { color:var(--text-muted); margin:6px 0 0; }
    .training-admin-actions { display:flex; flex-wrap:wrap; gap:10px; }
    .training-admin-actions a,.training-action { border-radius:999px; padding:10px 13px; background:var(--accent-herb); color:#fff; border:0; text-decoration:none; font-weight:800; display:inline-flex; align-items:center; gap:7px; cursor:pointer; }
    .training-action.light { background:#edf6ef; color:var(--accent-herb-dark); }
    .training-action.warn { background:#fff4df; color:#946200; }
    .training-action.danger { background:#ffecec; color:#a51f1f; }
    .training-tabs { display:flex; flex-wrap:wrap; gap:10px; margin-bottom:18px; }
    .training-tabs a { border-radius:999px; padding:9px 12px; text-decoration:none; background:var(--bg-elevated); border:1px solid var(--border-light); color:var(--text-main); font-weight:800; }
    .training-tabs a.active { background:var(--accent-herb); color:#fff; }
    .training-admin-card { background:var(--bg-elevated); border:1px solid var(--border-light); border-radius:var(--radius-lg); padding:22px; box-shadow:var(--shadow-sm); }
    .training-admin-table { width:100%; border-collapse:collapse; }
    .training-admin-table th,.training-admin-table td { padding:12px; border-bottom:1px solid var(--border-light); text-align:left; vertical-align:top; }
    .training-admin-table th:last-child,
    .training-admin-table td:last-child { width:168px; white-space:nowrap; }
    .training-admin-table th { color:var(--text-muted); font-size:12px; text-transform:uppercase; letter-spacing:.35px; }
    .training-badge { display:inline-flex; border-radius:999px; padding:6px 9px; background:#edf6ef; color:var(--accent-herb-dark); font-size:12px; font-weight:800; }
    .row-actions { display:flex; flex-wrap:nowrap; align-items:center; gap:8px; }
    .row-actions .training-action { width:42px; height:42px; flex:0 0 42px; justify-content:center; padding:0; }
</style>

<div class="training-admin-head">
    <div>
        <h1>Gestion des entrainements</h1>
        <p>Pilotez le catalogue sportif, les programmes sauvegardes et le suivi associe aux utilisateurs.</p>
    </div>
    <div class="training-admin-actions">
        <a href="/AdminLTE3/Views/back/entrainements/ajout.php"><i class="fas fa-plus"></i> Ajouter entrainement</a>
    </div>
</div>

<div class="training-tabs">
    <a class="<?php echo $filter === 'all' ? 'active' : ''; ?>" href="liste.php?filter=all">Tous (<?php echo $catalogueCount + $userCount; ?>)</a>
    <a class="<?php echo $filter === 'catalogue' ? 'active' : ''; ?>" href="liste.php?filter=catalogue">Catalogue (<?php echo $catalogueCount; ?>)</a>
    <a class="<?php echo $filter === 'users' ? 'active' : ''; ?>" href="liste.php?filter=users">Utilisateurs (<?php echo $userCount; ?>)</a>
</div>

<div class="training-admin-card">
    <table class="training-admin-table">
        <thead>
            <tr><th>Nom</th><th>Source</th><th>Utilisateur</th><th>Niveau</th><th>Type</th><th>Suivi</th><th>Date</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($row['nom']); ?></strong><br><small><?php echo nl2br(htmlspecialchars(substr($row['description'] ?? '', 0, 140))); ?></small></td>
                <td><span class="training-badge"><?php echo (int)$row['is_custom'] === 1 ? 'Utilisateur' : 'Catalogue'; ?></span></td>
                <td><?php echo $row['user_nom'] ? htmlspecialchars($row['user_nom']) . '<br><small>' . htmlspecialchars($row['email']) . '</small>' : 'Admin'; ?></td>
                <td><span class="training-badge"><?php echo htmlspecialchars($row['niveau']); ?></span></td>
                <td><?php echo htmlspecialchars($row['type_sport']); ?><br><small>MET <?php echo number_format((float)$row['met_value'], 1); ?></small></td>
                <td><?php echo (int)$row['nb_seances']; ?> seance(s)<br><?php echo (int)$row['nb_etapes']; ?> etape(s)<br><?php echo number_format((float)$row['calories'], 0); ?> kcal</td>
                <td><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
                <td>
                    <div class="row-actions">
                        <a class="training-action light" href="detail.php?id=<?php echo (int)$row['id']; ?>"><i class="fas fa-eye"></i></a>
                        <a class="training-action warn" href="modifier.php?id=<?php echo (int)$row['id']; ?>"><i class="fas fa-edit"></i></a>
                        <a class="training-action danger" href="supprimer.php?id=<?php echo (int)$row['id']; ?>" onclick="return confirm('Supprimer cet entrainement ?');"><i class="fas fa-trash"></i></a>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="8">Aucun entrainement trouve.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../../../Views/partials/footer.php'; ?>


