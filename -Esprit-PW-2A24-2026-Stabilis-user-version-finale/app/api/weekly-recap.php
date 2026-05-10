<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../core/Database.php';

function emptyPlayer(string $name = 'No Data'): array
{
    return [
        'rank' => 0,
        'id' => null,
        'name' => $name,
        'nom' => $name,
        'points' => 0,
        'total_points' => 0,
        'challenges' => 0,
        'completed_challenges' => 0,
        'avatar' => '--',
    ];
}

function normalizePlayer(array $row, int $rank = 0): array
{
    $name = $row['name'] ?? $row['nom'] ?? ('Utilisateur #' . ($row['id'] ?? '?'));
    $points = (int)($row['points'] ?? $row['total_points'] ?? $row['points_gained'] ?? 0);
    $challenges = (int)($row['challenges'] ?? $row['challenges_completed'] ?? 0);
    $avatar = $row['avatar'] ?? strtoupper(substr($name, 0, 2));

    return [
        'rank' => $rank,
        'id' => isset($row['id']) ? (int)$row['id'] : null,
        'name' => $name,
        'nom' => $name,
        'points' => $points,
        'total_points' => $points,
        'challenges' => $challenges,
        'completed_challenges' => (int)($row['completed_challenges'] ?? $challenges),
        'avatar' => $avatar,
    ];
}

function tableExists(mysqli $db, string $table): bool
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

function columnExists(mysqli $db, string $table, string $column): bool
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

try {
    $db = Database::connect();

    $weekNumber = date('W');
    $userTable = tableExists($db, 'users') ? 'users' : (tableExists($db, 'user') ? 'user' : null);
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
    if (columnExists($db, 'participations', 'date_fin')) {
        $activityDates[] = "NULLIF(p.date_fin, '0000-00-00')";
    }
    if (columnExists($db, 'participations', 'date_debut')) {
        $activityDates[] = "NULLIF(p.date_debut, '0000-00-00')";
    }
    if (columnExists($db, 'participations', 'created_at')) {
        $activityDates[] = 'DATE(p.created_at)';
    }
    $activityDateSql = count($activityDates) > 0 ? 'COALESCE(' . implode(', ', $activityDates) . ')' : 'CURRENT_DATE';
    $avatarSql = "UPPER(SUBSTRING($nameSql, 1, 2))";

    $topGainerQuery = "
        SELECT
            p.id_utilisateur AS id,
            $nameSql AS name,
            SUM($pointsSql) AS points,
            COUNT(p.id) AS challenges,
            SUM(CASE WHEN p.statut IN ('completed', 'reussi') THEN 1 ELSE 0 END) AS completed_challenges,
            $avatarSql AS avatar
        FROM participations p
        $userJoinSql
        LEFT JOIN defis d ON d.id = p.id_defi
        WHERE $activityDateSql >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY)
        GROUP BY p.id_utilisateur" . ($userTable !== null ? ', u.nom' : '') . "
        ORDER BY points DESC, completed_challenges DESC, challenges DESC
        LIMIT 1
    ";
    $result = $db->query($topGainerQuery);
    $topGainer = ($result && $result->num_rows > 0) ? normalizePlayer($result->fetch_assoc(), 1) : emptyPlayer();

    $mostActiveQuery = "
        SELECT
            p.id_utilisateur AS id,
            $nameSql AS name,
            SUM($pointsSql) AS points,
            COUNT(p.id) AS challenges,
            SUM(CASE WHEN p.statut IN ('completed', 'reussi') THEN 1 ELSE 0 END) AS completed_challenges,
            $avatarSql AS avatar
        FROM participations p
        $userJoinSql
        LEFT JOIN defis d ON d.id = p.id_defi
        WHERE $activityDateSql >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY)
        GROUP BY p.id_utilisateur" . ($userTable !== null ? ', u.nom' : '') . "
        ORDER BY challenges DESC, completed_challenges DESC, points DESC
        LIMIT 1
    ";
    $result = $db->query($mostActiveQuery);
    $mostActive = ($result && $result->num_rows > 0) ? normalizePlayer($result->fetch_assoc(), 1) : emptyPlayer();

    $leaderboardQuery = "
        SELECT
            p.id_utilisateur AS id,
            $nameSql AS name,
            SUM($pointsSql) AS points,
            COUNT(p.id) AS challenges,
            SUM(CASE WHEN p.statut IN ('completed', 'reussi') THEN 1 ELSE 0 END) AS completed_challenges,
            $avatarSql AS avatar
        FROM participations p
        $userJoinSql
        LEFT JOIN defis d ON d.id = p.id_defi
        GROUP BY p.id_utilisateur" . ($userTable !== null ? ', u.nom' : '') . "
        ORDER BY points DESC, completed_challenges DESC, challenges DESC
        LIMIT 10
    ";
    $result = $db->query($leaderboardQuery);
    $leaderboard = [];
    $rank = 1;
    while ($result && ($row = $result->fetch_assoc())) {
        $leaderboard[] = normalizePlayer($row, $rank++);
    }

    $top3 = array_slice($leaderboard, 0, 3);
    while (count($top3) < 3) {
        $placeholderRank = count($top3) + 1;
        $placeholder = emptyPlayer('TBD');
        $placeholder['rank'] = $placeholderRank;
        $top3[] = $placeholder;
    }

    $weeklyStatsQuery = "
        SELECT
            COUNT(DISTINCT p.id_utilisateur) AS active_users,
            SUM(CASE WHEN p.statut IN ('completed', 'reussi') THEN 1 ELSE 0 END) AS total_completions,
            SUM($pointsSql) AS total_points_distributed
        FROM participations p
        LEFT JOIN defis d ON d.id = p.id_defi
        WHERE $activityDateSql >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY)
    ";
    $result = $db->query($weeklyStatsQuery);
    $weeklyStats = $result ? $result->fetch_assoc() : [
        'active_users' => 0,
        'total_completions' => 0,
        'total_points_distributed' => 0,
    ];

    echo json_encode([
        'success' => true,
        'data' => [
            'weekNumber' => (int)$weekNumber,
            'topGainer' => [
                'name' => $topGainer['name'],
                'points' => $topGainer['points'],
                'avatar' => $topGainer['avatar'],
            ],
            'mostActive' => [
                'name' => $mostActive['name'],
                'challenges' => $mostActive['challenges'],
                'avatar' => $mostActive['avatar'],
            ],
            'top3' => $top3,
            'fullLeaderboard' => $leaderboard,
            'weeklyStats' => [
                'activeUsers' => (int)($weeklyStats['active_users'] ?? 0),
                'totalCompletions' => (int)($weeklyStats['total_completions'] ?? 0),
                'totalPointsDistributed' => (int)($weeklyStats['total_points_distributed'] ?? 0),
            ],
        ],
        'timestamp' => date('Y-m-d H:i:s'),
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error fetching weekly recap data',
        'message' => $e->getMessage(),
    ]);
}
?>
