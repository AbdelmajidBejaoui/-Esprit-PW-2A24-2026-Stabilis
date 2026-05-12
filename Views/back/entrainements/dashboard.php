<?php
$title = "Dashboard entrainements - Stabilis";
require_once __DIR__ . '/../../../Views/partials/header.php';
require_once __DIR__ . '/../../../config/entrainements.php';
require_once __DIR__ . '/../../../Controllers/EntrainementC.php';
require_once __DIR__ . '/../../../Controllers/SeanceC.php';
require_once __DIR__ . '/../../../Controllers/UtilisateurC.php';
require_once __DIR__ . '/../../../Models/GeneratedSessionRepository.php';

$db = config::getConnexion();
$entrainementC = new EntrainementC();
$seanceC = new SeanceC();
$userC = new UtilisateurC();
$generationRepo = new GeneratedSessionRepository();

$totalWorkouts = $entrainementC->countAll();
$catalogueWorkouts = (int)$db->query("SELECT COUNT(*) FROM entrainements WHERE is_custom = 0")->fetchColumn();
$userWorkouts = (int)$db->query("SELECT COUNT(*) FROM entrainements WHERE is_custom = 1")->fetchColumn();
$totalSessions = $seanceC->countAll();
$totalCalories = $seanceC->totalCaloriesAll();
$totalUsers = $userC->count();
$activeTrainingUsers = (int)$db->query("SELECT COUNT(DISTINCT utilisateur_id) FROM seances_completees")->fetchColumn();
$generationStats = $generationRepo->getStats();

$recentSessions = $db->query(
    "SELECT s.*, e.nom AS entrainement_nom, u.nom AS user_nom
     FROM seances_completees s
     INNER JOIN entrainements e ON e.id = s.entrainement_id
     INNER JOIN `user` u ON u.id = s.utilisateur_id
     ORDER BY s.completed_at DESC
     LIMIT 8"
)->fetchAll();

$topUsers = $db->query(
    "SELECT u.nom, COUNT(s.id) AS sessions, COALESCE(SUM(s.calories),0) AS calories
     FROM seances_completees s
     INNER JOIN `user` u ON u.id = s.utilisateur_id
     GROUP BY u.id, u.nom
     ORDER BY sessions DESC, calories DESC
     LIMIT 5"
)->fetchAll();

$workoutTypes = $db->query(
    "SELECT type_sport, COUNT(*) AS total
     FROM entrainements
     GROUP BY type_sport
     ORDER BY total DESC
     LIMIT 5"
)->fetchAll();

$recentGenerations = $generationRepo->getRecent(6);
?>
<style>
    .training-admin-head { display:flex; justify-content:space-between; gap:18px; align-items:end; margin-bottom:22px; }
    .training-admin-head h1 { margin:0; color:var(--accent-herb-dark); font-size:30px; }
    .training-admin-head p { color:var(--text-muted); margin:6px 0 0; }
    .training-kpis { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:16px; margin-bottom:22px; }
    .training-kpi { background:var(--bg-elevated); border:1px solid var(--border-light); border-left:4px solid var(--accent-herb); border-radius:12px; padding:18px; box-shadow:var(--shadow-sm); }
    .training-kpi strong { display:block; color:var(--accent-herb-dark); font-size:28px; line-height:1; }
    .training-kpi span { display:block; color:var(--text-muted); margin-top:8px; font-size:12px; font-weight:800; text-transform:uppercase; letter-spacing:.35px; }
    .training-dashboard-grid { display:grid; grid-template-columns:1.05fr .95fr; gap:20px; }
    .training-admin-card { background:var(--bg-elevated); border:1px solid var(--border-light); border-radius:var(--radius-lg); padding:22px; box-shadow:var(--shadow-sm); }
    .training-admin-card h2 { margin:0 0 14px; color:var(--accent-herb-dark); font-size:21px; }
    .training-admin-table { width:100%; border-collapse:collapse; }
    .training-admin-table th,.training-admin-table td { padding:12px; border-bottom:1px solid var(--border-light); text-align:left; vertical-align:top; }
    .training-admin-table th { color:var(--text-muted); font-size:12px; text-transform:uppercase; letter-spacing:.35px; }
    .metric-list { display:grid; gap:12px; }
    .metric-row { display:grid; grid-template-columns:1fr auto; gap:12px; align-items:center; border-bottom:1px solid var(--border-light); padding-bottom:10px; }
    .metric-row strong { color:var(--accent-herb-dark); }
    .metric-row span { color:var(--text-muted); font-size:13px; }
    .training-badge { display:inline-flex; border-radius:999px; padding:6px 9px; background:#edf6ef; color:var(--accent-herb-dark); font-size:12px; font-weight:800; }
    @media (max-width:1050px){ .training-kpis,.training-dashboard-grid{grid-template-columns:1fr 1fr;} }
    @media (max-width:760px){ .training-kpis,.training-dashboard-grid{grid-template-columns:1fr;} }
</style>

<div class="training-admin-head">
    <div>
        <h1>Dashboard entrainements</h1>
        <p>Vue claire des entrainements, seances terminees, calories et generations IA.</p>
    </div>
</div>

<div class="training-kpis">
    <div class="training-kpi"><strong><?php echo (int)$totalWorkouts; ?></strong><span>Entrainements</span></div>
    <div class="training-kpi"><strong><?php echo (int)$totalSessions; ?></strong><span>Seances terminees</span></div>
    <div class="training-kpi"><strong><?php echo number_format((float)$totalCalories, 0); ?></strong><span>Kcal brulees</span></div>
    <div class="training-kpi"><strong><?php echo (int)($generationStats['total_generated'] ?? 0); ?></strong><span>Generations IA</span></div>
</div>

<div class="training-dashboard-grid">
    <div class="training-admin-card">
        <h2>Dernieres seances</h2>
        <table class="training-admin-table">
            <thead><tr><th>Utilisateur</th><th>Entrainement</th><th>Duree</th><th>Kcal</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($recentSessions as $session): ?>
                <tr>
                    <td><?php echo htmlspecialchars($session['user_nom']); ?></td>
                    <td><?php echo htmlspecialchars($session['entrainement_nom']); ?></td>
                    <td><?php echo (int)$session['duree_minutes']; ?> min</td>
                    <td><strong><?php echo number_format((float)$session['calories'], 0); ?></strong></td>
                    <td><?php echo date('d/m/Y H:i', strtotime($session['completed_at'])); ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$recentSessions): ?><tr><td colspan="5">Aucune seance terminee.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="training-admin-card">
        <h2>Resume module</h2>
        <div class="metric-list">
            <div class="metric-row"><span>Catalogue admin</span><strong><?php echo (int)$catalogueWorkouts; ?></strong></div>
            <div class="metric-row"><span>Programmes utilisateurs</span><strong><?php echo (int)$userWorkouts; ?></strong></div>
            <div class="metric-row"><span>Utilisateurs avec seances</span><strong><?php echo (int)$activeTrainingUsers; ?> / <?php echo (int)$totalUsers; ?></strong></div>
            <div class="metric-row"><span>Kcal moyenne / seance</span><strong><?php echo $totalSessions > 0 ? number_format((float)$totalCalories / $totalSessions, 0) : 0; ?></strong></div>
            <div class="metric-row"><span>Kcal moyenne IA</span><strong><?php echo number_format((float)($generationStats['avg_calories'] ?? 0), 0); ?></strong></div>
        </div>
    </div>

    <div class="training-admin-card">
        <h2>Top utilisateurs</h2>
        <table class="training-admin-table">
            <thead><tr><th>Utilisateur</th><th>Seances</th><th>Kcal</th></tr></thead>
            <tbody>
            <?php foreach ($topUsers as $user): ?>
                <tr><td><?php echo htmlspecialchars($user['nom']); ?></td><td><?php echo (int)$user['sessions']; ?></td><td><?php echo number_format((float)$user['calories'], 0); ?></td></tr>
            <?php endforeach; ?>
            <?php if (!$topUsers): ?><tr><td colspan="3">Aucune donnee.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="training-admin-card">
        <h2>Types populaires</h2>
        <div class="metric-list">
            <?php foreach ($workoutTypes as $type): ?>
                <div class="metric-row"><span><?php echo htmlspecialchars($type['type_sport']); ?></span><strong><?php echo (int)$type['total']; ?></strong></div>
            <?php endforeach; ?>
            <?php if (!$workoutTypes): ?><p>Aucun entrainement.</p><?php endif; ?>
        </div>
    </div>

    <div class="training-admin-card" style="grid-column:1 / -1;">
        <h2>Dernieres generations IA</h2>
        <table class="training-admin-table">
            <thead><tr><th>ID</th><th>Objectif</th><th>Niveau</th><th>Prompt</th><th>Kcal</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($recentGenerations as $generation): ?>
                <tr>
                    <td>#<?php echo (int)$generation['id']; ?></td>
                    <td><span class="training-badge"><?php echo htmlspecialchars($generation['goal']); ?></span></td>
                    <td><?php echo htmlspecialchars($generation['niveau']); ?></td>
                    <td><?php echo htmlspecialchars($generation['prompt'] ?: '-'); ?></td>
                    <td><?php echo number_format((float)$generation['total_calories'], 0); ?></td>
                    <td><?php echo date('d/m/Y H:i', strtotime($generation['created_at'])); ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$recentGenerations): ?><tr><td colspan="6">Aucune generation pour le moment.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../../Views/partials/footer.php'; ?>


