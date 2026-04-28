<?php
require_once __DIR__ . '/../../Controller/UserC.php';

$userC = new UserC();
$stats = $userC->getStatistics();

$pageTitle = 'Statistiques';
$activePage = 'statistics';
require_once __DIR__ . '/partials/layout_top.php';
?>

<div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title">Statistiques Utilisateurs</h3>
    </div>
    <div class="card-body">
        <!-- Summary Cards -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?php echo $stats['totalUsers']; ?></h3>
                        <p>Utilisateurs Total</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row 1 -->
        <div class="row mt-4">
            <!-- Users by Role -->
            <div class="col-lg-6">
                <div class="card card-success card-outline">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Utilisateurs par Rôle</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="roleChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Users by Status -->
            <div class="col-lg-6">
                <div class="card card-warning card-outline">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Statut des Comptes</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row 2 -->
        <div class="row mt-4">
            <!-- Users by Dietary Preference -->
            <div class="col-lg-6">
                <div class="card card-danger card-outline">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Préférences Alimentaires</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="preferenceChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Registration Trend -->
            <div class="col-lg-6">
                <div class="card card-secondary card-outline">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Tendance d'Inscription (12 derniers mois)</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="monthChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include Chart.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>

<script>
    // Prepare data for Role Chart
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

    // Prepare data for Status Chart
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

    // Prepare data for Preference Chart
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

    // Prepare data for Month Chart
    const monthData = <?php 
        $monthLabels = [];
        $monthCounts = [];
        // Reverse to show from oldest to newest
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

    // Role Chart
    const roleChartCtx = document.getElementById('roleChart').getContext('2d');
    new Chart(roleChartCtx, {
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
        options: {
            responsive: true,
            maintainAspectRatio: true,
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
        }
    });

    // Status Chart
    const statusChartCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statusChartCtx, {
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
        options: {
            responsive: true,
            maintainAspectRatio: true,
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
        }
    });

    // Preference Chart
    const preferenceChartCtx = document.getElementById('preferenceChart').getContext('2d');
    new Chart(preferenceChartCtx, {
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
        options: {
            responsive: true,
            maintainAspectRatio: true,
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
        }
    });

    // Month Chart
    const monthChartCtx = document.getElementById('monthChart').getContext('2d');
    new Chart(monthChartCtx, {
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
        options: {
            responsive: true,
            maintainAspectRatio: true,
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
        }
    });
</script>

<?php
require_once __DIR__ . '/partials/layout_bottom.php';
?>
