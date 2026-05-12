<?php
$title = "Suivi des seances - Stabilis";
require_once __DIR__ . '/../../../Views/partials/header.php';
require_once __DIR__ . '/../../../config/entrainements.php';
require_once __DIR__ . '/../../../Controllers/SeanceC.php';

$seanceC = new SeanceC();
$db = config::getConnexion();
$rows = $db->query(
    "SELECT s.*, e.nom AS entrainement_nom, e.type_sport, u.nom AS user_nom, u.email AS user_email
     FROM seances_completees s
     INNER JOIN entrainements e ON e.id = s.entrainement_id
     INNER JOIN `user` u ON u.id = s.utilisateur_id
     ORDER BY s.completed_at DESC
     LIMIT 150"
)->fetchAll();

$totalCalories = $seanceC->totalCaloriesAll();
$totalSessions = $seanceC->countAll();
$activeUsers = (int)$db->query("SELECT COUNT(DISTINCT utilisateur_id) FROM seances_completees")->fetchColumn();
?>
<style>
    .training-admin-head { display:flex; justify-content:space-between; gap:18px; align-items:end; margin-bottom:22px; }
    .training-admin-head h1 { margin:0; color:var(--accent-herb-dark); font-size:30px; }
    .training-admin-head p { color:var(--text-muted); margin:6px 0 0; }
    .training-admin-actions { display:flex; flex-wrap:wrap; gap:10px; }
    .training-admin-actions a { border-radius:999px; padding:10px 13px; background:var(--accent-herb); color:#fff; text-decoration:none; font-weight:800; display:inline-flex; gap:7px; align-items:center; }
    .training-kpis { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:16px; margin-bottom:22px; }
    .training-kpi { background:var(--bg-elevated); border:1px solid var(--border-light); border-left:4px solid var(--accent-herb); border-radius:12px; padding:18px; box-shadow:var(--shadow-sm); }
    .training-kpi strong { display:block; color:var(--accent-herb-dark); font-size:28px; line-height:1; }
    .training-kpi span { display:block; color:var(--text-muted); margin-top:8px; font-size:12px; font-weight:800; text-transform:uppercase; letter-spacing:.35px; }
    .training-admin-card { background:var(--bg-elevated); border:1px solid var(--border-light); border-radius:var(--radius-lg); padding:22px; box-shadow:var(--shadow-sm); }
    .training-admin-table { width:100%; border-collapse:collapse; }
    .training-admin-table th,.training-admin-table td { padding:12px; border-bottom:1px solid var(--border-light); text-align:left; vertical-align:top; }
    .training-admin-table th { color:var(--text-muted); font-size:12px; text-transform:uppercase; letter-spacing:.35px; }
    .training-badge { display:inline-flex; border-radius:999px; padding:6px 9px; background:#edf6ef; color:var(--accent-herb-dark); font-size:12px; font-weight:800; }
    .row-actions { display:flex; flex-wrap:wrap; gap:7px; }
    .training-action { width:34px; height:34px; border-radius:50%; border:0; text-decoration:none; display:inline-flex; align-items:center; justify-content:center; background:#edf6ef; color:var(--accent-herb-dark); cursor:pointer; }
    .training-action.warn { background:#fff4df; color:#946200; }
    .training-action.danger { background:#ffecec; color:#a51f1f; }
    @media (max-width:900px){ .training-kpis{grid-template-columns:1fr 1fr;} }
    @media (max-width:620px){ .training-kpis{grid-template-columns:1fr;} }
</style>

<div class="training-admin-head">
    <div>
        <h1>Suivi des seances</h1>
        <p>Analysez les efforts enregistres, les calories brulees et les retours notes par les utilisateurs.</p>
    </div>
    <div class="training-admin-actions">
        <a href="seance-ajout.php"><i class="fas fa-plus"></i> Ajouter seance</a>
    </div>
</div>

<div class="training-kpis">
    <div class="training-kpi"><strong><?php echo (int)$totalSessions; ?></strong><span>Seances</span></div>
    <div class="training-kpi"><strong><?php echo number_format((float)$totalCalories, 0); ?></strong><span>Kcal brulees</span></div>
    <div class="training-kpi"><strong><?php echo $totalSessions > 0 ? number_format((float)$totalCalories / $totalSessions, 0) : 0; ?></strong><span>Kcal / seance</span></div>
    <div class="training-kpi"><strong><?php echo (int)$activeUsers; ?></strong><span>Utilisateurs actifs</span></div>
</div>

<div class="training-admin-card">
    <table class="training-admin-table">
        <thead>
            <tr><th>ID</th><th>Utilisateur</th><th>Entrainement</th><th>Duree</th><th>Kcal</th><th>Intensite</th><th>FC</th><th>Date</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td>#<?php echo (int)$row['id']; ?></td>
                <td><strong><?php echo htmlspecialchars($row['user_nom']); ?></strong><br><small><?php echo htmlspecialchars($row['user_email']); ?></small></td>
                <td><?php echo htmlspecialchars($row['entrainement_nom']); ?><br><small><?php echo htmlspecialchars($row['type_sport']); ?></small></td>
                <td><?php echo (int)$row['duree_minutes']; ?> min</td>
                <td><strong><?php echo number_format((float)$row['calories'], 0); ?></strong></td>
                <td><span class="training-badge"><?php echo htmlspecialchars($row['intensite']); ?></span></td>
                <td><?php echo $row['fc_moyenne'] ? (int)$row['fc_moyenne'] . ' bpm' : '-'; ?></td>
                <td><?php echo date('d/m/Y H:i', strtotime($row['completed_at'])); ?></td>
                <td>
                    <div class="row-actions">
                        <a class="training-action" href="seance-detail.php?id=<?php echo (int)$row['id']; ?>" title="Voir"><i class="fas fa-eye"></i></a>
                        <a class="training-action warn" href="seance-modifier.php?id=<?php echo (int)$row['id']; ?>" title="Modifier"><i class="fas fa-edit"></i></a>
                        <a class="training-action danger" href="seance-supprimer.php?id=<?php echo (int)$row['id']; ?>" onclick="return confirm('Supprimer cette seance ?');" title="Supprimer"><i class="fas fa-trash"></i></a>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="9">Aucune seance completee.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../../../Views/partials/footer.php'; ?>


