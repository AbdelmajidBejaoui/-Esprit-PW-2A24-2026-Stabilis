<?php
$title = 'Dashboard Defis - Stabilis';
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../../config/database.php';

$db = Database::getConnection();

$totalDefis = (int)$db->query('SELECT COUNT(*) FROM defis')->fetchColumn();
$totalParticipations = (int)$db->query('SELECT COUNT(*) FROM participations')->fetchColumn();
$completedParticipations = (int)$db->query("SELECT COUNT(*) FROM participations WHERE statut = 'completed'")->fetchColumn();
$inProgressParticipations = (int)$db->query("SELECT COUNT(*) FROM participations WHERE statut = 'in_progress'")->fetchColumn();
$pendingProofs = (int)$db->query("SELECT COUNT(*) FROM participation_proofs WHERE review_state = 'pending'")->fetchColumn();
$co2Estimate = $totalDefis * 2.3;
$lastUpdated = date('H:i:s');

$typeRows = $db->query("SELECT type, COUNT(*) AS total FROM defis GROUP BY type ORDER BY FIELD(type, 'aliment', 'entrainement', 'compensation')")->fetchAll(PDO::FETCH_ASSOC);
$statusRows = $db->query("SELECT statut, COUNT(*) AS total FROM participations GROUP BY statut ORDER BY FIELD(statut, 'in_progress', 'completed', 'failed')")->fetchAll(PDO::FETCH_ASSOC);
$recentRows = $db->query("SELECT p.*, d.nom AS defi_nom, u.nom AS user_nom, u.email AS user_email
    FROM participations p
    LEFT JOIN defis d ON d.id = p.id_defi
    LEFT JOIN `user` u ON u.id = p.id_utilisateur
    ORDER BY p.id DESC
    LIMIT 8")->fetchAll(PDO::FETCH_ASSOC);

$typeLabels = ['aliment' => 'Alimentaire', 'entrainement' => 'Entrainement', 'compensation' => 'Compensation'];
$statusLabels = ['in_progress' => 'En cours', 'completed' => 'Terminee', 'failed' => 'Echouee'];
$typeColors = ['aliment' => '#22c55e', 'entrainement' => '#f59e0b', 'compensation' => '#06b6d4'];
$statusColors = ['in_progress' => '#1d72f3', 'completed' => '#16a165', 'failed' => '#ef4444'];

$typeStats = [];
foreach (['aliment', 'entrainement', 'compensation'] as $type) {
    $typeStats[$type] = 0;
}
foreach ($typeRows as $row) {
    $typeStats[$row['type']] = (int)$row['total'];
}

$statusStats = [];
foreach (['in_progress', 'completed', 'failed'] as $status) {
    $statusStats[$status] = 0;
}
foreach ($statusRows as $row) {
    $statusStats[$row['statut']] = (int)$row['total'];
}

function pct(int $value, int $total): float
{
    return $total > 0 ? round(($value / $total) * 100, 1) : 0;
}
?>

<style>
.defis-board {
    background: #f7faf8;
    border: 1px solid #e3ebe4;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 22px 48px rgba(28, 54, 38, 0.13);
}

.defis-board-header {
    background: linear-gradient(135deg, #12b981, #0f9f72);
    color: #fff;
    padding: 24px 28px;
}

.defis-board-title {
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 0 0 8px;
    color: #fff;
    font-size: 28px;
    font-weight: 850;
}

.defis-board-subtitle {
    margin: 0;
    color: rgba(255,255,255,.86);
    font-size: 16px;
}

.defis-board-body {
    padding: 22px 24px 28px;
}

.defis-stat-row {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
    margin-bottom: 28px;
}

.defis-stat-tile {
    position: relative;
    display: grid;
    grid-template-columns: 54px 1fr;
    gap: 14px;
    align-items: center;
    min-height: 96px;
    background: #fff;
    border: 1px solid #e4e9e6;
    border-left: 4px solid #14b87a;
    border-radius: 16px;
    padding: 18px;
    box-shadow: 0 8px 18px rgba(24, 39, 31, 0.08);
    transition: transform .18s ease, box-shadow .18s ease;
}

.defis-stat-tile:hover {
    transform: translateY(-3px);
    box-shadow: 0 16px 28px rgba(24, 39, 31, 0.12);
}

.defis-stat-tile:nth-child(2) { border-left-color: #10b981; }
.defis-stat-tile:nth-child(3) { border-left-color: #22c55e; }
.defis-stat-tile:nth-child(4) { border-left-color: #f59e0b; }

.defis-stat-icon {
    width: 48px;
    height: 48px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: #e9fbf3;
    color: #10b981;
    font-size: 22px;
}

.defis-stat-tile:nth-child(4) .defis-stat-icon {
    background: #fff7e6;
    color: #f59e0b;
}

.defis-stat-value {
    display: block;
    color: #111827;
    font-size: 32px;
    line-height: 1;
    font-weight: 900;
}

.defis-stat-label {
    display: block;
    margin-top: 7px;
    color: #4b5563;
    text-transform: uppercase;
    font-size: 13px;
    letter-spacing: .45px;
    font-weight: 850;
}

.defis-analysis-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 28px;
    margin-bottom: 26px;
}

.defis-analysis-card {
    background: #fff;
    border: 1px solid #cceee0;
    border-top: 4px solid #10b981;
    border-radius: 18px;
    padding: 20px;
    box-shadow: 0 16px 30px rgba(24, 39, 31, 0.08);
}

.analysis-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    padding-bottom: 14px;
    border-bottom: 1px solid #edf2ee;
    margin-bottom: 18px;
}

.analysis-title {
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 0;
    font-size: 24px;
    color: #111827;
}

.analysis-title i {
    color: #10b981;
}

.analysis-badge {
    display: inline-flex;
    padding: 7px 11px;
    border-radius: 8px;
    background: #f59e0b;
    color: #fff;
    font-size: 13px;
    font-weight: 900;
}

.analysis-badge.blue {
    background: #06b6d4;
}

.analysis-content {
    display: grid;
    grid-template-columns: minmax(0, 1.1fr) 280px;
    gap: 22px;
    align-items: center;
}

.metric-list {
    display: grid;
    gap: 18px;
}

.metric-row-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-bottom: 9px;
}

.metric-label {
    display: inline-flex;
    padding: 6px 10px;
    border-radius: 8px;
    color: #fff;
    font-size: 15px;
    font-weight: 900;
}

.metric-percent {
    color: #10b981;
    font-weight: 900;
    font-size: 18px;
}

.metric-bar {
    height: 13px;
    border-radius: 999px;
    background: #e5e7eb;
    overflow: hidden;
}

.metric-fill {
    height: 100%;
    border-radius: inherit;
    background: #10b981;
}

.metric-count {
    margin-top: 8px;
    color: #4b5563;
    font-size: 18px;
    font-weight: 800;
}

.chart-side {
    min-height: 260px;
    background: #f8fcfb;
    border: 1px solid #e8f1ed;
    border-radius: 18px;
    padding: 18px;
}

.chart-box {
    height: 170px;
}

.chart-legend {
    display: grid;
    gap: 8px;
    margin-top: 12px;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #374151;
    font-weight: 800;
}

.legend-dot {
    width: 14px;
    height: 14px;
    border-radius: 50%;
}

.defis-impact-strip {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
    background: #fff;
    border: 1px solid #e4e9e6;
    border-radius: 18px;
    padding: 18px 22px;
    margin-bottom: 24px;
    box-shadow: 0 10px 22px rgba(24, 39, 31, 0.07);
}

.defis-impact-strip h3 {
    margin: 0 0 5px;
    font-size: 18px;
}

.defis-impact-strip p {
    margin: 0;
    color: #6b7280;
}

.progress-mini {
    width: 120px;
    height: 8px;
    background: #edf1ed;
    border-radius: 999px;
    overflow: hidden;
}

.progress-mini span {
    display: block;
    height: 100%;
    background: #10b981;
}

@media (max-width: 1180px) {
    .defis-stat-row {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .defis-analysis-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 760px) {
    .defis-stat-row,
    .analysis-content {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="defis-board">
    <header class="defis-board-header">
        <h1 class="defis-board-title"><i class="fas fa-chart-bar"></i> Tableau de Bord - Statistiques</h1>
        <p class="defis-board-subtitle">Derniere mise a jour: <?php echo htmlspecialchars($lastUpdated); ?></p>
    </header>

    <div class="defis-board-body">
        <section class="defis-stat-row">
            <div class="defis-stat-tile">
                <span class="defis-stat-icon"><i class="fas fa-list-check"></i></span>
                <div><strong class="defis-stat-value"><?php echo $totalDefis; ?></strong><span class="defis-stat-label">Defis totaux</span></div>
            </div>
            <div class="defis-stat-tile">
                <span class="defis-stat-icon"><i class="fas fa-users"></i></span>
                <div><strong class="defis-stat-value"><?php echo $totalParticipations; ?></strong><span class="defis-stat-label">Participations</span></div>
            </div>
            <div class="defis-stat-tile">
                <span class="defis-stat-icon"><i class="fas fa-check"></i></span>
                <div><strong class="defis-stat-value"><?php echo $completedParticipations; ?></strong><span class="defis-stat-label">Terminees</span></div>
            </div>
            <div class="defis-stat-tile">
                <span class="defis-stat-icon"><i class="fas fa-hourglass-half"></i></span>
                <div><strong class="defis-stat-value"><?php echo $inProgressParticipations; ?></strong><span class="defis-stat-label">En cours</span></div>
            </div>
        </section>

        <section class="defis-analysis-grid">
            <article class="defis-analysis-card">
                <div class="analysis-header">
                    <h2 class="analysis-title"><i class="fas fa-trophy"></i> Statistiques Defis</h2>
                    <span class="analysis-badge">Dynamique</span>
                </div>
                <div class="analysis-content">
                    <div class="metric-list">
                        <?php foreach (['aliment', 'entrainement', 'compensation'] as $type): ?>
                        <?php $value = (int)$typeStats[$type]; $percent = pct($value, $totalDefis); ?>
                        <div class="metric-row">
                            <div class="metric-row-top">
                                <span class="metric-label" style="background: <?php echo $typeColors[$type]; ?>;"><?php echo $typeLabels[$type]; ?></span>
                                <span class="metric-percent"><?php echo number_format($percent, 1, '.', ''); ?>%</span>
                            </div>
                            <div class="metric-bar"><div class="metric-fill" style="width: <?php echo $percent; ?>%; background: <?php echo $typeColors[$type]; ?>;"></div></div>
                            <div class="metric-count"><?php echo $value; ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="chart-side">
                        <div class="chart-box"><canvas id="typeChart"></canvas></div>
                        <div class="chart-legend">
                            <?php foreach (['aliment', 'entrainement', 'compensation'] as $type): ?>
                            <div class="legend-item"><span class="legend-dot" style="background: <?php echo $typeColors[$type]; ?>;"></span><?php echo $typeLabels[$type]; ?>: <?php echo (int)$typeStats[$type]; ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </article>

            <article class="defis-analysis-card">
                <div class="analysis-header">
                    <h2 class="analysis-title"><i class="fas fa-chart-pie"></i> Statistiques Participations</h2>
                    <span class="analysis-badge blue">Temps reel</span>
                </div>
                <div class="analysis-content">
                    <div class="metric-list">
                        <?php foreach (['in_progress', 'completed', 'failed'] as $status): ?>
                        <?php $value = (int)$statusStats[$status]; $percent = pct($value, $totalParticipations); ?>
                        <div class="metric-row">
                            <div class="metric-row-top">
                                <span class="metric-label" style="background: <?php echo $statusColors[$status]; ?>;"><?php echo $statusLabels[$status]; ?></span>
                                <span class="metric-percent"><?php echo number_format($percent, 1, '.', ''); ?>%</span>
                            </div>
                            <div class="metric-bar"><div class="metric-fill" style="width: <?php echo $percent; ?>%; background: <?php echo $statusColors[$status]; ?>;"></div></div>
                            <div class="metric-count"><?php echo $value; ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="chart-side">
                        <div class="chart-box"><canvas id="statusChart"></canvas></div>
                        <div class="chart-legend">
                            <?php foreach (['in_progress', 'completed', 'failed'] as $status): ?>
                            <div class="legend-item"><span class="legend-dot" style="background: <?php echo $statusColors[$status]; ?>;"></span><?php echo $statusLabels[$status]; ?>: <?php echo (int)$statusStats[$status]; ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </article>
        </section>

        <div class="defis-impact-strip">
            <div>
                <h3>Impact estime & preuves</h3>
                <p><?php echo number_format($co2Estimate, 1, ',', ' '); ?> kg CO2 evites. <?php echo $pendingProofs; ?> preuve(s) en attente de revision.</p>
            </div>
            <a class="btn-primary" href="/AdminLTE3/Views/back/defis/liste.php"><i class="fas fa-trophy"></i> Gerer les defis</a>
            <a class="btn-secondary" href="/AdminLTE3/Views/back/participations/liste.php"><i class="fas fa-users"></i> Participations</a>
        </div>

        <div class="table-card">
            <div class="table-header">
                <h3>Participations recentes</h3>
                <span class="record-count"><?php echo count($recentRows); ?> lignes</span>
            </div>
            <div class="table-responsive">
                <table>
                    <thead><tr><th>Utilisateur</th><th>Defi</th><th>Progression</th><th>Statut</th><th>Date debut</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php if (!$recentRows): ?><tr><td colspan="6" class="text-center">Aucune participation.</td></tr><?php endif; ?>
                    <?php foreach ($recentRows as $row): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['user_nom'] ?? ('#' . $row['id_utilisateur'])); ?></strong><br><span class="text-muted"><?php echo htmlspecialchars($row['user_email'] ?? ''); ?></span></td>
                            <td><?php echo htmlspecialchars($row['defi_nom'] ?? 'Defi supprime'); ?></td>
                            <td><div class="progress-mini"><span style="width:<?php echo (int)$row['progression']; ?>%;"></span></div><?php echo (int)$row['progression']; ?>%</td>
                            <td><?php echo htmlspecialchars($statusLabels[$row['statut']] ?? $row['statut']); ?></td>
                            <td><?php echo htmlspecialchars($row['date_debut']); ?></td>
                            <td><a class="btn-icon" href="/AdminLTE3/Views/back/participations/modifier.php?id=<?php echo (int)$row['id']; ?>"><i class="fas fa-eye"></i> Voir</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
<script>
const typeData = <?php echo json_encode([
    'labels' => array_values($typeLabels),
    'values' => array_values($typeStats),
    'colors' => array_values($typeColors),
]); ?>;
const statusData = <?php echo json_encode([
    'labels' => array_values($statusLabels),
    'values' => array_values($statusStats),
    'colors' => array_values($statusColors),
]); ?>;

function makeRing(id, data) {
    new Chart(document.getElementById(id), {
        type: 'doughnut',
        data: {
            labels: data.labels,
            datasets: [{
                data: data.values.some(Number) ? data.values : [1],
                backgroundColor: data.values.some(Number) ? data.colors : ['#dbe5de'],
                borderColor: '#f8fcfb',
                borderWidth: 8,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '62%',
            plugins: {
                legend: { display: false },
                tooltip: { enabled: data.values.some(Number) }
            }
        }
    });
}

makeRing('typeChart', typeData);
makeRing('statusChart', statusData);
</script>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
