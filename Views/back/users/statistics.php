<?php
require_once __DIR__ . '/../../../Controllers/UserC.php';

$userC = new UserC();
$stats = $userC->getStatistics();

$pageTitle = 'Statistiques';
$activePage = 'statistics';
require_once __DIR__ . '/partials/layout_top.php';
?>

<style>
.user-stats-title {
    font-size: 28px;
    font-weight: 400;
    margin: 0 0 24px;
    color: #1f2933;
}

.user-stats-page {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-top: 3px solid #8bc34a;
    border-radius: 18px 18px 10px 10px;
    box-shadow: 0 18px 30px rgba(0, 0, 0, 0.08);
    overflow: hidden;
}

.user-stats-page > .card-header {
    padding: 18px 24px;
    background: #fff;
    border-bottom: 1px solid #e5e7eb;
}

.user-stats-page > .card-header .card-title {
    font-size: 18px;
    font-weight: 500;
}

.user-stats-page > .card-body {
    padding: 26px 24px 44px;
}

.user-stats-summary {
    display: grid;
    grid-template-columns: minmax(240px, 350px);
    margin-bottom: 56px;
}

.user-total-box {
    position: relative;
    min-height: 140px;
    padding: 22px 24px;
    background: #20a7b5;
    color: #fff;
    border-radius: 4px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.15);
    overflow: hidden;
    transition: transform 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease;
}

.user-total-box:hover {
    transform: translateY(-2px) scale(1.005);
    background: #22adbc;
    box-shadow: 0 8px 16px rgba(32, 167, 181, 0.22);
}

.user-total-box strong {
    display: block;
    font-size: 36px;
    line-height: 1;
    margin-bottom: 24px;
}

.user-total-box span {
    font-size: 17px;
    font-weight: 500;
}

.user-total-box i {
    position: absolute;
    right: 18px;
    top: 22px;
    font-size: 72px;
    color: rgba(0, 0, 0, 0.12);
}

.user-stats-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 28px 22px;
}

.user-chart-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-top: 3px solid #22a25f;
    border-radius: 18px;
    box-shadow: 0 18px 24px rgba(0, 0, 0, 0.08);
    overflow: hidden;
}

.user-chart-card.is-warning { border-top-color: #f1c40f; }
.user-chart-card.is-danger { border-top-color: #c94f5c; }
.user-chart-card.is-secondary { border-top-color: #6c7a80; }

.user-chart-card .card-header {
    padding: 18px 24px;
    background: #fff;
    border-bottom: 1px solid #e5e7eb;
}

.user-chart-card .card-title {
    font-size: 18px;
    font-weight: 400;
}

.user-chart-card .card-body {
    height: 350px;
    padding: 28px 28px 22px;
}

.user-chart-card canvas {
    width: 100% !important;
    height: 100% !important;
}

@media (max-width: 1100px) {
    .user-stats-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 640px) {
    .user-stats-title {
        font-size: 24px;
    }

    .user-stats-page > .card-body {
        padding: 20px 14px 28px;
    }

    .user-stats-summary {
        grid-template-columns: 1fr;
        margin-bottom: 28px;
    }

    .user-chart-card .card-body {
        height: 300px;
        padding: 18px 12px;
    }
}
</style>

<h1 class="user-stats-title">Statistiques</h1>

<div class="card user-stats-page">
    <div class="card-header">
        <h3 class="card-title">Statistiques Utilisateurs</h3>
    </div>
    <div class="card-body">
        <div class="user-stats-summary">
            <div class="user-total-box">
                <strong><?php echo $stats['totalUsers']; ?></strong>
                <span>Utilisateurs Total</span>
                <i class="fas fa-users"></i>
            </div>
        </div>

        <div class="user-stats-grid">
            <div class="user-chart-card">
                <div class="card-header">
                    <h5 class="card-title">Utilisateurs par Role</h5>
                </div>
                <div class="card-body">
                    <canvas id="roleChart"></canvas>
                </div>
            </div>

            <div class="user-chart-card is-warning">
                <div class="card-header">
                    <h5 class="card-title">Statut des Comptes</h5>
                </div>
                <div class="card-body">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>

            <div class="user-chart-card is-danger">
                <div class="card-header">
                    <h5 class="card-title">Preferences Alimentaires</h5>
                </div>
                <div class="card-body">
                    <canvas id="preferenceChart"></canvas>
                </div>
            </div>

            <div class="user-chart-card is-secondary">
                <div class="card-header">
                    <h5 class="card-title">Tendance d'Inscription (12 derniers mois)</h5>
                </div>
                <div class="card-body">
                    <canvas id="monthChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>

<script>
    const roleData = <?php
        $roleLabels = [];
        $roleCounts = [];
        foreach ($stats['usersByRole'] as $role) {
            $roleLabels[] = ucfirst($role['role']);
            $roleCounts[] = (int)$role['count'];
        }
        echo json_encode([
            'labels' => $roleLabels,
            'counts' => $roleCounts
        ]);
    ?>;

    const statusData = <?php
        $statusLabels = [];
        $statusCounts = [];
        foreach ($stats['usersByStatus'] as $status) {
            $statusLabels[] = $status['statut_compte'] == 1 ? 'Actif' : 'Inactif';
            $statusCounts[] = (int)$status['count'];
        }
        echo json_encode([
            'labels' => $statusLabels,
            'counts' => $statusCounts
        ]);
    ?>;

    const preferenceData = <?php
        $prefLabels = [];
        $prefCounts = [];
        foreach ($stats['usersByPreference'] as $pref) {
            $prefLabels[] = ucfirst($pref['preference_alimentaire']);
            $prefCounts[] = (int)$pref['count'];
        }
        echo json_encode([
            'labels' => $prefLabels,
            'counts' => $prefCounts
        ]);
    ?>;

    const monthData = <?php
        $monthLabels = [];
        $monthCounts = [];
        $reversedMonths = array_reverse($stats['usersByMonth']);
        foreach ($reversedMonths as $month) {
            $monthLabels[] = $month['month'];
            $monthCounts[] = (int)$month['count'];
        }
        echo json_encode([
            'labels' => $monthLabels,
            'counts' => $monthCounts
        ]);
    ?>;

    const baseOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'top'
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    };

    new Chart(document.getElementById('roleChart'), {
        type: 'bar',
        data: {
            labels: roleData.labels,
            datasets: [{
                label: 'Nombre d\'utilisateurs',
                data: roleData.counts,
                backgroundColor: [
                    'rgba(54, 162, 235, 0.7)',
                    'rgba(75, 192, 192, 0.7)'
                ],
                borderColor: [
                    'rgba(54, 162, 235, 1)',
                    'rgba(75, 192, 192, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: baseOptions
    });

    new Chart(document.getElementById('statusChart'), {
        type: 'bar',
        data: {
            labels: statusData.labels,
            datasets: [{
                label: 'Nombre de comptes',
                data: statusData.counts,
                backgroundColor: [
                    'rgba(75, 192, 192, 0.7)',
                    'rgba(255, 99, 132, 0.7)'
                ],
                borderColor: [
                    'rgba(75, 192, 192, 1)',
                    'rgba(255, 99, 132, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: baseOptions
    });

    new Chart(document.getElementById('preferenceChart'), {
        type: 'bar',
        data: {
            labels: preferenceData.labels,
            datasets: [{
                label: 'Nombre d\'utilisateurs',
                data: preferenceData.counts,
                backgroundColor: 'rgba(255, 159, 64, 0.7)',
                borderColor: 'rgba(255, 159, 64, 1)',
                borderWidth: 1
            }]
        },
        options: baseOptions
    });

    new Chart(document.getElementById('monthChart'), {
        type: 'bar',
        data: {
            labels: monthData.labels,
            datasets: [{
                label: 'Inscriptions',
                data: monthData.counts,
                backgroundColor: 'rgba(153, 102, 255, 0.7)',
                borderColor: 'rgba(153, 102, 255, 1)',
                borderWidth: 1
            }]
        },
        options: baseOptions
    });
</script>

<?php require_once __DIR__ . '/partials/layout_bottom.php'; ?>
