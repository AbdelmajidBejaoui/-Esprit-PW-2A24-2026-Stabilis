<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../core/AdminGuard.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/GeminiClient.php';

requireBackOfficeRequest('ai-weekly-story');

function storyTableExists(mysqli $db, string $table): bool
{
    $stmt = $db->prepare('
        SELECT COUNT(*) AS total
        FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = ?
    ');
    $stmt->bind_param('s', $table);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    return $row && (int)$row['total'] > 0;
}

function storyColumnExists(mysqli $db, string $table, string $column): bool
{
    $stmt = $db->prepare('
        SELECT COUNT(*) AS total
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = ?
        AND COLUMN_NAME = ?
    ');
    $stmt->bind_param('ss', $table, $column);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    return $row && (int)$row['total'] > 0;
}

function storyNormalizePlayer(?array $row): ?array
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

function collectWeeklyStorySummary(mysqli $db): array
{
    $userTable = storyTableExists($db, 'users') ? 'users' : (storyTableExists($db, 'user') ? 'user' : null);
    $userJoinSql = '';
    $nameSql = "CONCAT('Utilisateur #', p.id_utilisateur)";

    if ($userTable !== null) {
        $safeUserTable = '`' . str_replace('`', '``', $userTable) . '`';
        $userJoinSql = "LEFT JOIN $safeUserTable u ON u.id = p.id_utilisateur";
        $nameSql = "COALESCE(NULLIF(u.nom, ''), CONCAT('Utilisateur #', p.id_utilisateur))";
    }

    $rewardSql = "CAST(COALESCE(NULLIF(d.recompense, ''), '0') AS UNSIGNED)";
    $pointsSql = "
        CASE
            WHEN p.statut IN ('completed', 'reussi') THEN $rewardSql
            WHEN p.statut IN ('in_progress', 'en_cours') THEN ROUND($rewardSql * GREATEST(0, LEAST(COALESCE(p.progression, 0), 100)) / 100)
            ELSE 0
        END
    ";

    $activityDates = [];
    if (storyColumnExists($db, 'participations', 'date_fin')) {
        $activityDates[] = "NULLIF(p.date_fin, '0000-00-00')";
    }
    if (storyColumnExists($db, 'participations', 'date_debut')) {
        $activityDates[] = "NULLIF(p.date_debut, '0000-00-00')";
    }
    if (storyColumnExists($db, 'participations', 'created_at')) {
        $activityDates[] = 'DATE(p.created_at)';
    }
    $activityDateSql = count($activityDates) > 0 ? 'COALESCE(' . implode(', ', $activityDates) . ')' : 'CURRENT_DATE';
    $groupByName = $userTable !== null ? ', u.nom' : '';

    $weeklyUserSql = "
        SELECT
            p.id_utilisateur AS id,
            $nameSql AS name,
            SUM($pointsSql) AS points,
            COUNT(p.id) AS challenges,
            SUM(CASE WHEN p.statut IN ('completed', 'reussi') THEN 1 ELSE 0 END) AS completed_challenges
        FROM participations p
        $userJoinSql
        LEFT JOIN defis d ON d.id = p.id_defi
        WHERE $activityDateSql >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY)
        GROUP BY p.id_utilisateur $groupByName
    ";

    $topGainer = null;
    $result = $db->query($weeklyUserSql . ' ORDER BY points DESC, completed_challenges DESC, challenges DESC LIMIT 1');
    if ($result && $result->num_rows > 0) {
        $topGainer = storyNormalizePlayer($result->fetch_assoc());
    }

    $mostActive = null;
    $result = $db->query($weeklyUserSql . ' ORDER BY challenges DESC, completed_challenges DESC, points DESC LIMIT 1');
    if ($result && $result->num_rows > 0) {
        $mostActive = storyNormalizePlayer($result->fetch_assoc());
    }

    $leaderboardSql = "
        SELECT
            p.id_utilisateur AS id,
            $nameSql AS name,
            SUM($pointsSql) AS points,
            COUNT(p.id) AS challenges,
            SUM(CASE WHEN p.statut IN ('completed', 'reussi') THEN 1 ELSE 0 END) AS completed_challenges
        FROM participations p
        $userJoinSql
        LEFT JOIN defis d ON d.id = p.id_defi
        GROUP BY p.id_utilisateur $groupByName
        ORDER BY points DESC, completed_challenges DESC, challenges DESC
        LIMIT 3
    ";
    $top3 = [];
    $result = $db->query($leaderboardSql);
    while ($result && ($row = $result->fetch_assoc())) {
        $top3[] = storyNormalizePlayer($row);
    }

    $weeklyStatsSql = "
        SELECT
            COUNT(DISTINCT p.id_utilisateur) AS active_users,
            COUNT(p.id) AS total_participations,
            SUM(CASE WHEN p.statut IN ('completed', 'reussi') THEN 1 ELSE 0 END) AS total_completions,
            SUM($pointsSql) AS total_points_distributed
        FROM participations p
        LEFT JOIN defis d ON d.id = p.id_defi
        WHERE $activityDateSql >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY)
    ";
    $result = $db->query($weeklyStatsSql);
    $stats = $result ? $result->fetch_assoc() : [];

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

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new InvalidArgumentException('Méthode non autorisée.');
    }

    $db = Database::connect();
    $summary = collectWeeklyStorySummary($db);
    $client = new GeminiClient();
    $story = $client->generateWeeklyStory($summary);

    echo json_encode([
        'success' => true,
        'story' => $story,
        'summary' => $summary,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
