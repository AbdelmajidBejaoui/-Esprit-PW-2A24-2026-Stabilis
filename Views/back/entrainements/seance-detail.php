<?php
$title = "Detail seance - Stabilis";
require_once __DIR__ . '/../../../Controllers/SeanceC.php';

$seance = (new SeanceC())->getById((int)($_GET['id'] ?? 0));
if (!$seance) {
    header('Location: seances.php?missing=1');
    exit;
}

require_once __DIR__ . '/../../../Views/partials/header.php';
?>
<style>
    .detail-card { background:var(--bg-elevated); border:1px solid var(--border-light); border-radius:var(--radius-lg); padding:24px; box-shadow:var(--shadow-sm); max-width:900px; }
    .detail-card h1 { margin:0 0 14px; color:var(--accent-herb-dark); font-size:30px; }
    .detail-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:14px; margin-top:18px; }
    .detail-item { border:1px solid var(--border-light); border-radius:12px; padding:14px; background:#fff; }
    .detail-item span { display:block; color:var(--text-muted); font-size:12px; font-weight:800; text-transform:uppercase; }
    .detail-item strong { display:block; color:var(--accent-herb-dark); font-size:20px; margin-top:6px; }
    .training-btn { border-radius:999px; padding:11px 15px; background:var(--accent-herb); color:#fff; text-decoration:none; font-weight:800; display:inline-flex; gap:8px; align-items:center; margin-top:18px; }
    .training-btn.light { background:#edf6ef; color:var(--accent-herb-dark); }
    @media (max-width:720px){ .detail-grid{grid-template-columns:1fr;} }
</style>

<div class="detail-card">
    <h1>Seance #<?php echo (int)$seance['id']; ?></h1>
    <p><?php echo htmlspecialchars($seance['user_nom']); ?> - <?php echo htmlspecialchars($seance['entrainement_nom']); ?></p>
    <div class="detail-grid">
        <div class="detail-item"><span>Utilisateur</span><strong><?php echo htmlspecialchars($seance['user_nom']); ?></strong></div>
        <div class="detail-item"><span>Entrainement</span><strong><?php echo htmlspecialchars($seance['entrainement_nom']); ?></strong></div>
        <div class="detail-item"><span>Duree</span><strong><?php echo (int)$seance['duree_minutes']; ?> min</strong></div>
        <div class="detail-item"><span>Calories</span><strong><?php echo number_format((float)$seance['calories'], 0); ?> kcal</strong></div>
        <div class="detail-item"><span>Intensite</span><strong><?php echo htmlspecialchars($seance['intensite']); ?></strong></div>
        <div class="detail-item"><span>FC moyenne</span><strong><?php echo $seance['fc_moyenne'] ? (int)$seance['fc_moyenne'] . ' bpm' : '-'; ?></strong></div>
        <div class="detail-item"><span>Date</span><strong><?php echo date('d/m/Y H:i', strtotime($seance['completed_at'])); ?></strong></div>
        <div class="detail-item"><span>Notes</span><strong><?php echo htmlspecialchars($seance['notes'] ?: '-'); ?></strong></div>
    </div>
    <a class="training-btn" href="seance-modifier.php?id=<?php echo (int)$seance['id']; ?>"><i class="fas fa-edit"></i> Modifier</a>
    <a class="training-btn light" href="seances.php">Retour</a>
</div>

<?php require_once __DIR__ . '/../../../Views/partials/footer.php'; ?>


