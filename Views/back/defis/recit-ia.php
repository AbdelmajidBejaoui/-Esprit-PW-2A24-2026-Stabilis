<?php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../Services/DefiGeminiService.php';

function recitNormalizePlayer(?array $row): ?array
{
    if (!$row) {
        return null;
    }

    return [
        'nom' => (string)($row['name'] ?? 'Utilisateur inconnu'),
        'points' => (int)($row['points'] ?? 0),
        'defis_participes' => (int)($row['challenges'] ?? 0),
        'defis_termines' => (int)($row['completed_challenges'] ?? 0),
    ];
}

function recitCollectWeeklySummary(PDO $db): array
{
    $rewardSql = "CAST(COALESCE(NULLIF(d.recompense, ''), '0') AS UNSIGNED)";
    $pointsSql = "
        CASE
            WHEN p.statut = 'completed' THEN $rewardSql
            WHEN p.statut = 'in_progress' THEN ROUND($rewardSql * GREATEST(0, LEAST(COALESCE(p.progression, 0), 100)) / 100)
            ELSE 0
        END
    ";
    $activityDateSql = "COALESCE(NULLIF(p.date_fin, '0000-00-00'), NULLIF(p.date_debut, '0000-00-00'), DATE(p.created_at))";
    $nameSql = "COALESCE(NULLIF(u.nom, ''), CONCAT('Utilisateur #', p.id_utilisateur))";

    $weeklyUserSql = "
        SELECT
            p.id_utilisateur AS id,
            $nameSql AS name,
            SUM($pointsSql) AS points,
            COUNT(p.id) AS challenges,
            SUM(CASE WHEN p.statut = 'completed' THEN 1 ELSE 0 END) AS completed_challenges
        FROM participations p
        LEFT JOIN `user` u ON u.id = p.id_utilisateur
        LEFT JOIN defis d ON d.id = p.id_defi
        WHERE $activityDateSql >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY)
        GROUP BY p.id_utilisateur, u.nom
    ";

    $topGainer = recitNormalizePlayer($db->query($weeklyUserSql . ' ORDER BY points DESC, completed_challenges DESC, challenges DESC LIMIT 1')->fetch(PDO::FETCH_ASSOC) ?: null);
    $mostActive = recitNormalizePlayer($db->query($weeklyUserSql . ' ORDER BY challenges DESC, completed_challenges DESC, points DESC LIMIT 1')->fetch(PDO::FETCH_ASSOC) ?: null);

    $leaderboardSql = "
        SELECT
            p.id_utilisateur AS id,
            $nameSql AS name,
            SUM($pointsSql) AS points,
            COUNT(p.id) AS challenges,
            SUM(CASE WHEN p.statut = 'completed' THEN 1 ELSE 0 END) AS completed_challenges
        FROM participations p
        LEFT JOIN `user` u ON u.id = p.id_utilisateur
        LEFT JOIN defis d ON d.id = p.id_defi
        GROUP BY p.id_utilisateur, u.nom
        ORDER BY points DESC, completed_challenges DESC, challenges DESC
        LIMIT 3
    ";
    $top3 = [];
    foreach ($db->query($leaderboardSql)->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $top3[] = recitNormalizePlayer($row);
    }

    $weeklyStatsSql = "
        SELECT
            COUNT(DISTINCT p.id_utilisateur) AS active_users,
            COUNT(p.id) AS total_participations,
            SUM(CASE WHEN p.statut = 'completed' THEN 1 ELSE 0 END) AS total_completions,
            SUM($pointsSql) AS total_points_distributed
        FROM participations p
        LEFT JOIN defis d ON d.id = p.id_defi
        WHERE $activityDateSql >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY)
    ";
    $stats = $db->query($weeklyStatsSql)->fetch(PDO::FETCH_ASSOC) ?: [];

    return [
        'semaine' => (int)date('W'),
        'meilleur_utilisateur' => $top3[0] ?? null,
        'plus_gros_gain_points' => $topGainer,
        'utilisateur_le_plus_actif' => $mostActive,
        'top_3_utilisateurs' => array_values(array_filter($top3)),
        'statistiques_hebdomadaires' => [
            'utilisateurs_actifs' => (int)($stats['active_users'] ?? 0),
            'participations' => (int)($stats['total_participations'] ?? 0),
            'defis_termines' => (int)($stats['total_completions'] ?? 0),
            'points_distribues' => (int)($stats['total_points_distributed'] ?? 0),
        ],
    ];
}

$db = Database::getConnection();
$summary = recitCollectWeeklySummary($db);
$story = '';
$errors = [];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    try {
        $service = new DefiGeminiService();
        $story = $service->generateWeeklyStory($summary);
    } catch (Throwable $e) {
        $errors[] = $e->getMessage();
    }
}

$stats = $summary['statistiques_hebdomadaires'];
$title = 'Recit IA Defis - Stabilis';
require_once __DIR__ . '/../../partials/header.php';
?>

<style>
.recit-shell { display:grid; gap:22px; }
.recit-hero {
    border-radius: 20px;
    padding: 28px;
    color: #fff;
    background: linear-gradient(135deg, #129f72, #1a4d3a);
    box-shadow: 0 18px 38px rgba(18, 95, 68, 0.22);
}
.recit-hero h1 { color:#fff; margin:10px 0 8px; font-size:34px; }
.recit-hero p { color:rgba(255,255,255,.82); margin:0; }
.recit-kicker {
    display:inline-flex; align-items:center; gap:8px;
    padding:8px 12px; border-radius:999px;
    background:rgba(255,255,255,.16); font-weight:800;
}
.recit-grid { display:grid; grid-template-columns: 1.1fr .9fr; gap:22px; }
.recit-panel {
    background:#fff; border:1px solid var(--border-light); border-radius:18px;
    padding:22px; box-shadow:var(--shadow-sm);
}
.recit-panel h2 { margin:0 0 8px; }
.recit-output {
    min-height:180px;
    border:1px dashed #cfe2d5;
    border-radius:16px;
    padding:20px;
    background:#f8fcf9;
    color:#334155;
    line-height:1.75;
    white-space:pre-wrap;
    margin-top:16px;
}
.recit-summary-grid {
    display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px;
}
.recit-summary-card {
    border:1px solid #e7eee8; border-radius:14px; padding:16px; background:#fbfdfb;
}
.recit-summary-card strong { display:block; color:var(--accent-herb-dark); font-size:26px; line-height:1; }
.recit-summary-card span { display:block; margin-top:7px; color:var(--text-muted); font-size:12px; text-transform:uppercase; font-weight:800; }
.recit-user-card { margin-top:14px; padding:14px; border-radius:14px; background:var(--accent-herb-light); color:var(--accent-herb-dark); }
.recit-actions { display:flex; gap:10px; flex-wrap:wrap; margin-top:16px; }
@media (max-width:900px){ .recit-grid{grid-template-columns:1fr;} }
@media (max-width:560px){ .recit-summary-grid{grid-template-columns:1fr;} }
</style>

<div class="recit-shell">
    <section class="recit-hero">
        <span class="recit-kicker"><i class="fas fa-feather-pointed"></i> Back-office Defis</span>
        <h1>Recit IA de la semaine</h1>
        <p>Generez un resume narratif court a partir des donnees hebdomadaires deja agregees. Le recit n est pas enregistre en base.</p>
    </section>

    <div class="recit-grid">
        <section class="recit-panel">
            <h2>Generation du recit</h2>
            <p class="text-muted">Seules des donnees resumees sont envoyees a Gemini: statistiques, top utilisateurs et progression globale.</p>

            <?php if ($errors): ?><div class="alert"><ul><?php foreach ($errors as $error): ?><li><?php echo htmlspecialchars($error); ?></li><?php endforeach; ?></ul></div><?php endif; ?>

            <form method="POST" class="recit-actions">
                <button class="btn-primary" type="submit"><i class="fas fa-feather-pointed"></i> Generer le recit</button>
                <a class="btn-secondary" href="/AdminLTE3/Views/back/defis/dashboard.php"><i class="fas fa-chart-bar"></i> Dashboard defis</a>
            </form>

            <div class="recit-output">
<?php echo $story !== '' ? htmlspecialchars($story) : 'Cliquez sur "Generer le recit" pour creer un resume professionnel de l activite de la semaine.'; ?>
            </div>
        </section>

        <aside class="recit-panel">
            <h2>Donnees utilisees</h2>
            <div class="recit-summary-grid">
                <div class="recit-summary-card"><strong><?php echo (int)$stats['utilisateurs_actifs']; ?></strong><span>Utilisateurs actifs</span></div>
                <div class="recit-summary-card"><strong><?php echo (int)$stats['defis_termines']; ?></strong><span>Defis termines</span></div>
                <div class="recit-summary-card"><strong><?php echo (int)$stats['points_distribues']; ?></strong><span>Points distribues</span></div>
                <div class="recit-summary-card"><strong><?php echo (int)$stats['participations']; ?></strong><span>Participations</span></div>
            </div>

            <div class="recit-user-card">
                <strong>Meilleur utilisateur</strong><br>
                <?php echo htmlspecialchars($summary['meilleur_utilisateur']['nom'] ?? '-'); ?>
            </div>
            <div class="recit-user-card">
                <strong>Plus actif</strong><br>
                <?php echo htmlspecialchars($summary['utilisateur_le_plus_actif']['nom'] ?? '-'); ?>
            </div>
        </aside>
    </div>
</div>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>

