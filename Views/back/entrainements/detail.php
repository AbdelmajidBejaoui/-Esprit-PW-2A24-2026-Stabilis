<?php
$title = "Detail entrainement - Stabilis";
require_once __DIR__ . '/../../../Controllers/EntrainementC.php';
require_once __DIR__ . '/../../../config/entrainements.php';

$controller = new EntrainementC();
$id = (int)($_GET['id'] ?? 0);
$entrainement = $controller->getById($id);
if (!$entrainement) {
    header('Location: liste.php?missing=1');
    exit;
}

$db = config::getConnexion();
$user = null;
if ($entrainement->getUserId()) {
    $stmt = $db->prepare("SELECT nom, email FROM `user` WHERE id = :id");
    $stmt->execute([':id' => $entrainement->getUserId()]);
    $user = $stmt->fetch();
}
$steps = $controller->getEtapes($id);
$sessions = $db->prepare("SELECT s.*, u.nom AS user_nom FROM seances_completees s LEFT JOIN `user` u ON u.id = s.utilisateur_id WHERE s.entrainement_id = :id ORDER BY s.completed_at DESC LIMIT 10");
$sessions->execute([':id' => $id]);
$sessions = $sessions->fetchAll();
require_once __DIR__ . '/../../../Views/partials/header.php';
?>
<style>
    .detail-head { display:flex; justify-content:space-between; gap:18px; align-items:start; margin-bottom:20px; }
    .detail-head h1 { margin:0; color:var(--accent-herb-dark); font-size:30px; }
    .detail-grid { display:grid; grid-template-columns:.9fr 1.1fr; gap:20px; }
    .detail-card { background:var(--bg-elevated); border:1px solid var(--border-light); border-radius:var(--radius-lg); padding:22px; box-shadow:var(--shadow-sm); }
    .detail-card h2 { margin:0 0 14px; color:var(--accent-herb-dark); font-size:21px; }
    .training-btn { border-radius:999px; padding:11px 15px; background:var(--accent-herb); color:#fff; text-decoration:none; font-weight:800; display:inline-flex; gap:8px; align-items:center; }
    .training-btn.light { background:#edf6ef; color:var(--accent-herb-dark); }
    .meta { display:flex; flex-wrap:wrap; gap:8px; margin:14px 0; }
    .meta span { border-radius:999px; padding:7px 10px; background:#edf6ef; color:var(--accent-herb-dark); font-size:12px; font-weight:800; }
    .step { display:grid; grid-template-columns:34px 1fr; gap:10px; padding:12px; border:1px solid var(--border-light); border-radius:12px; margin-bottom:10px; }
    .step b { width:34px; height:34px; border-radius:50%; background:var(--accent-herb); color:#fff; display:flex; align-items:center; justify-content:center; }
    .detail-table { width:100%; border-collapse:collapse; }
    .detail-table th,.detail-table td { padding:10px; border-bottom:1px solid var(--border-light); text-align:left; }
    @media (max-width:900px){ .detail-grid{grid-template-columns:1fr;} }
</style>

<div class="detail-head">
    <div>
        <h1><?php echo htmlspecialchars($entrainement->getNom()); ?></h1>
        <div class="meta">
            <span><?php echo (int)$entrainement->getIsCustom() === 1 ? 'Utilisateur' : 'Catalogue'; ?></span>
            <span><?php echo htmlspecialchars($entrainement->getTypeSport()); ?></span>
            <span><?php echo htmlspecialchars($entrainement->getNiveau()); ?></span>
            <span>MET <?php echo number_format((float)$entrainement->getMetValue(), 1); ?></span>
        </div>
    </div>
    <div>
        <a class="training-btn light" href="liste.php">Retour</a>
        <a class="training-btn" href="modifier.php?id=<?php echo $id; ?>"><i class="fas fa-edit"></i> Modifier</a>
    </div>
</div>

<div class="detail-grid">
    <div class="detail-card">
        <h2>Informations</h2>
        <p><?php echo nl2br(htmlspecialchars($entrainement->getDescription() ?: 'Aucune description.')); ?></p>
        <p><strong>Createur:</strong> <?php echo $user ? htmlspecialchars($user['nom'] . ' (' . $user['email'] . ')') : 'Admin'; ?></p>
        <p><strong>Date:</strong> <?php echo $entrainement->getCreatedAt() ? date('d/m/Y H:i', strtotime($entrainement->getCreatedAt())) : '-'; ?></p>
    </div>
    <div class="detail-card">
        <h2>Tutoriel</h2>
        <?php foreach ($steps as $index => $step): ?>
            <div class="step"><b><?php echo $index + 1; ?></b><div><strong><?php echo htmlspecialchars($step['titre']); ?></strong><br><?php echo nl2br(htmlspecialchars($step['description'])); ?></div></div>
        <?php endforeach; ?>
        <?php if (!$steps): ?><p>Aucune etape pour cet entrainement.</p><?php endif; ?>
    </div>
    <div class="detail-card" style="grid-column:1 / -1;">
        <h2>Dernieres seances</h2>
        <table class="detail-table">
            <thead><tr><th>Utilisateur</th><th>Duree</th><th>Kcal</th><th>Intensite</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($sessions as $session): ?>
                <tr><td><?php echo htmlspecialchars($session['user_nom'] ?? '-'); ?></td><td><?php echo (int)$session['duree_minutes']; ?> min</td><td><?php echo number_format((float)$session['calories'], 0); ?></td><td><?php echo htmlspecialchars($session['intensite']); ?></td><td><?php echo date('d/m/Y H:i', strtotime($session['completed_at'])); ?></td></tr>
            <?php endforeach; ?>
            <?php if (!$sessions): ?><tr><td colspan="5">Aucune seance completee.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../../Views/partials/footer.php'; ?>


