<?php 
$title = "Dashboard - Stabilis";

require_once __DIR__ . '/../../Views/partials/header.php';
require_once __DIR__ . '/../../Controllers/ProduitController.php';
require_once __DIR__ . '/../../Controllers/CommandeController.php';
require_once __DIR__ . '/../../Services/DashboardService.php';

$mailConfig = require __DIR__ . '/../../config/mail.php';
$produitController = new ProduitController($mailConfig);
$commandeController = new CommandeController();
$dashboardService = new DashboardService();

$produits = $produitController->getAll();
$commandes = $commandeController->getAllGroupedForBackoffice();

$totalProduits = count($produits);
$totalStock = array_sum(array_column($produits, 'stock'));
$lowStockProducts = 0;

foreach ($produits as $produit) {
    if ((int) $produit['stock'] < $mailConfig['alert_threshold']) {
        $lowStockProducts++;
    }
}

$totalCommandes = count($commandes);
$totalRevenue = array_sum(array_map(function ($commande) {
    $finalTotal = (float)($commande['final_total'] ?? 0);
    return $finalTotal > 0 ? $finalTotal : (float)($commande['total'] ?? 0);
}, $commandes));
$pendingOrders = 0;

foreach ($commandes as $commande) {
    if (trim(strtolower($commande['statut'])) === 'en attente') {
        $pendingOrders++;
    }
}

$salesByMonth = $dashboardService->getSalesDataByMonth();
$salesByCategory = $dashboardService->getSalesByCategory();
?>

<style>
        .main-content {
            width: auto;
            max-width: calc(100% - 280px);
            box-sizing: border-box;
        }

        body {
            overflow-x: hidden;
        }

        .dashboard-header {
            margin-bottom: 24px;
        }

        .dashboard-title {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-muted);
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }

        
        .stats-container {
            display: grid;
            grid-template-columns: minmax(320px, 0.95fr) minmax(360px, 1.05fr);
            gap: 24px;
            margin-bottom: 24px;
            align-items: stretch;
            max-width: 100%;
            min-width: 0;
        }

        .kpi-chart-wrapper {
            background: var(--bg-elevated);
            border-radius: var(--radius-lg);
            padding: 24px;
            border: 1px solid var(--border-light);
            transition: box-shadow var(--transition-normal);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 420px;
            min-width: 0;
        }

        .kpi-chart-wrapper:hover,
        .chart-card:hover {
            box-shadow: var(--shadow-md);
        }

        .kpi-chart-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 20px;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }

        .kpi-chart-container {
            position: relative;
            width: min(320px, 100%);
            height: 320px;
            margin: 0 auto;
        }

        .kpi-chart-center {
            position: absolute;
            inset: 50% auto auto 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            pointer-events: none;
        }

        .kpi-chart-center strong {
            display: block;
            font-size: 30px;
            line-height: 1;
            color: var(--accent-herb-dark);
        }

        .kpi-chart-center span {
            display: block;
            margin-top: 8px;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .kpi-legend {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-top: 24px;
            width: 100%;
        }

        .kpi-legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
        }

        .kpi-legend-color {
            width: 12px;
            height: 12px;
            border-radius: 2px;
        }

        .kpi-stats-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 16px;
            align-content: center;
            justify-content: center;
            min-height: 420px;
            min-width: 0;
        }

        .kpi-stat-item {
            background: var(--bg-elevated);
            border: 1px solid var(--border-light);
            border-left: 4px solid var(--accent-herb);
            padding: 16px 24px;
            border-radius: var(--radius-lg);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            transition: all var(--transition-fast);
        }

        .kpi-stat-item:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }

        .kpi-stat-item:nth-child(2) { border-left-color: #5a8f68; }
        .kpi-stat-item:nth-child(3) { border-left-color: #C6A15B; }
        .kpi-stat-item:nth-child(4) { border-left-color: #2C553E; }

        .kpi-stat-hint {
            margin-top: 5px;
            font-size: 12px;
            color: var(--text-muted);
        }

        .kpi-stat-label {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .kpi-stat-value {
            font-size: 22px;
            font-weight: 700;
            color: var(--accent-herb-dark);
            white-space: nowrap;
        }

        .kpi-health-strip {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            width: 100%;
            margin-top: 22px;
        }

        .kpi-health-pill {
            background: var(--accent-herb-light);
            border: 1px solid #DDE8DF;
            border-radius: var(--radius-full);
            padding: 12px;
            text-align: center;
        }

        .kpi-health-pill strong {
            display: block;
            color: var(--accent-herb-dark);
            font-size: 18px;
        }

        .kpi-health-pill span {
            display: block;
            margin-top: 4px;
            color: var(--text-muted);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .charts-section {
            display: grid;
            grid-template-columns: 1fr;
            gap: 24px;
            margin-bottom: 24px;
            max-width: 100%;
            overflow: hidden;
            min-width: 0;
        }
        
        @media (min-width: 900px) {
            .charts-section {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .chart-card {
            background: var(--bg-elevated);
            border-radius: var(--radius-lg);
            padding: 24px;
            border: 1px solid var(--border-light);
            transition: box-shadow var(--transition-normal);
            min-width: 0;
        }

        .chart-title {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 16px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }

        .chart-container {
            position: relative;
            height: 250px;
            width: 100%;
        }

        .chart-center-label {
            position: absolute;
            inset: 50% auto auto 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            pointer-events: none;
        }

        .chart-center-label strong {
            display: block;
            color: var(--accent-herb-dark);
            font-size: 22px;
            line-height: 1;
        }

        .chart-center-label span {
            display: block;
            margin-top: 6px;
            color: var(--text-muted);
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .export-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 24px;
            background: var(--bg-elevated);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-light);
            margin-bottom: 24px;
            max-width: 100%;
            overflow: hidden;
            flex-wrap: wrap;
            gap: 16px;
        }

        .export-text h3 {
            margin: 0 0 4px 0;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }

        .export-text p {
            margin: 0;
            font-size: 13px;
            color: var(--text-muted);
        }

        .btn-export-pdf:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .table-card {
            margin-bottom: 24px;
            max-width: 100%;
            width: 100%;
            min-width: 0;
        }

        .table-header {
            flex-wrap: wrap;
            gap: 12px;
        }

        .table-responsive {
            width: 100%;
            -webkit-overflow-scrolling: touch;
            max-width: 100%;
        }

        table {
            table-layout: fixed;
        }

        td {
            word-break: break-word;
            overflow-wrap: break-word;
        }

        .badge-success {
            background: rgba(58, 107, 75, 0.1);
            color: var(--accent-herb-dark);
        }

        .badge-warning {
            background: rgba(198, 161, 91, 0.1);
            color: var(--accent-earth-dark);
        }

        .badge-danger {
            background: rgba(197, 90, 74, 0.1);
            color: #c55a4a;
        }

        .loading-spinner {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 2px solid rgba(58, 107, 75, 0.2);
            border-top-color: var(--accent-herb);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        @media (max-width: 1024px) {
            .stats-container {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                max-width: calc(100% - 72px);
            }

            .charts-section {
                grid-template-columns: 1fr;
            }
            
            .export-section {
                flex-direction: column;
                gap: 16px;
                text-align: center;
            }

            .kpi-legend {
                grid-template-columns: 1fr;
            }

            .kpi-health-strip {
                grid-template-columns: 1fr;
            }
        }
    </style>

    
    <div class="dashboard-header">
        <h1 class="dashboard-title">Dashboard</h1>
    </div>

    
    <div class="stats-container">
        <div class="kpi-chart-wrapper">
            <div class="kpi-chart-title">Indicateurs Clés</div>
            <div class="kpi-chart-container">
                <canvas id="kpiChart"></canvas>
                <div class="kpi-chart-center">
                    <strong><?php echo $totalCommandes; ?></strong>
                    <span>Commandes</span>
                </div>
            </div>
            <div class="kpi-legend">
                <div class="kpi-legend-item">
                    <div class="kpi-legend-color" style="background: #1A4D3A;"></div>
                    <span>Produits</span>
                </div>
                <div class="kpi-legend-item">
                    <div class="kpi-legend-color" style="background: #3A6B4B;"></div>
                    <span>Stock</span>
                </div>
                <div class="kpi-legend-item">
                    <div class="kpi-legend-color" style="background: #5a8f68;"></div>
                    <span>Commandes</span>
                </div>
                <div class="kpi-legend-item">
                    <div class="kpi-legend-color" style="background: #C6A15B;"></div>
                    <span>Revenu</span>
                </div>
            </div>
            <div class="kpi-health-strip">
                <div class="kpi-health-pill">
                    <strong><?php echo max(0, $totalProduits - $lowStockProducts); ?></strong>
                    <span>Stock OK</span>
                </div>
                <div class="kpi-health-pill">
                    <strong><?php echo $lowStockProducts; ?></strong>
                    <span>Stock bas</span>
                </div>
                <div class="kpi-health-pill">
                    <strong><?php echo $pendingOrders; ?></strong>
                    <span>En attente</span>
                </div>
            </div>
        </div>

        <div class="kpi-stats-grid">
            <div class="kpi-stat-item">
                <div>
                    <div class="kpi-stat-label">Total Produits</div>
                    <div class="kpi-stat-hint">Catalogue actif</div>
                </div>
                <div class="kpi-stat-value"><?php echo $totalProduits; ?></div>
            </div>
            <div class="kpi-stat-item">
                <div>
                    <div class="kpi-stat-label">Stock Total</div>
                    <div class="kpi-stat-hint"><?php echo $lowStockProducts; ?> alerte(s) stock</div>
                </div>
                <div class="kpi-stat-value"><?php echo $totalStock; ?></div>
            </div>
            <div class="kpi-stat-item">
                <div>
                    <div class="kpi-stat-label">Total Commandes</div>
                    <div class="kpi-stat-hint"><?php echo $pendingOrders; ?> commande(s) en attente</div>
                </div>
                <div class="kpi-stat-value"><?php echo $totalCommandes; ?></div>
            </div>
            <div class="kpi-stat-item">
                <div>
                    <div class="kpi-stat-label">Chiffre d'Affaires</div>
                    <div class="kpi-stat-hint">Revenu net commande</div>
                </div>
                <div class="kpi-stat-value"><?php echo number_format($totalRevenue, 0, ',', ' '); ?> EUR</div>
            </div>
        </div>
    </div>

    
    <div class="charts-section">
        
        <div class="chart-card">
            <div class="chart-title">Ventes par Mois (12 derniers mois)</div>
            <div class="chart-container">
                <canvas id="salesChart"></canvas>
            </div>
        </div>

        
        <div class="chart-card">
            <div class="chart-title">Ventes par Categorie</div>
            <div class="chart-container">
                <canvas id="categoryChart"></canvas>
                <div class="chart-center-label">
                    <strong><?php echo number_format(array_sum(array_column($salesByCategory, 'total_revenue')), 0, ',', ' '); ?></strong>
                    <span>EUR</span>
                </div>
            </div>
        </div>
    </div>

    
    <div class="export-section">
        <div class="export-text">
            <h3>Exporter les Donnees</h3>
            <p>Telecharger un rapport complet en PDF</p>
        </div>
        <button class="btn-primary btn-export-pdf" onclick="exportToPDF()" id="exportBtn">
            <i class="fas fa-file-pdf"></i> Exporter en PDF
        </button>
    </div>

    
    <div class="table-card">
        <div class="table-header">
            <h3>Produits Recents</h3>
            <span class="record-count"><?php echo $totalProduits; ?> produits</span>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Produit</th>
                        <th>Categorie</th>
                        <th>Stock</th>
                        <th>Prix</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($produits)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center;">Aucun produit</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach (array_slice($produits, 0, 5) as $p): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($p['nom']); ?></strong></td>
                        <td><?php echo htmlspecialchars($p['categorie']); ?></td>
                        <td>
                            <?php if ($p['stock'] < 5): ?>
                                <span class="badge badge-danger">Stock bas: <?php echo $p['stock']; ?></span>
                            <?php else: ?>
                                <span class="badge badge-aliment"><?php echo $p['stock']; ?> unit&eacute;s</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo number_format($p['prix'], 2, ',', ' '); ?> EUR</td>
                        <td>
                            <a href="produits/modifier.php?id=<?php echo $p['id']; ?>" class="btn-icon">
                                <i class="fas fa-edit"></i> <span>Modifier</span>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    
    <div class="table-card">
        <div class="table-header">
            <h3>Commandes Recentes</h3>
            <span class="record-count"><?php echo $totalCommandes; ?> commandes</span>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Email</th>
                        <th>Quantite</th>
                        <th>Total</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($commandes)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center;">Aucune commande</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach (array_slice($commandes, 0, 8) as $cmd): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($cmd['prenom'] . ' ' . $cmd['nom']); ?></td>
                        <td><?php echo htmlspecialchars($cmd['email']); ?></td>
                        <td><?php echo htmlspecialchars($cmd['quantite_totale'] ?? $cmd['quantite'] ?? 0); ?></td>
                        <td><strong><?php echo number_format($cmd['final_total'], 2, ',', ' '); ?> EUR</strong></td>
                        <td>
                            <?php 
                            $status = strtolower(trim($cmd['statut']));
                            $badgeClass = 'badge-success';
                            if ($status === 'annulee') $badgeClass = 'badge-danger';
                            elseif ($status === 'en attente') $badgeClass = 'badge-warning';
                            ?>
                            <span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($cmd['statut']); ?></span>
                        </td>
                        <td>
                            <a href="commandes/voir.php?id=<?php echo $cmd['id']; ?>" class="btn-icon">
                                <i class="fas fa-eye"></i> <span>Voir</span>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
const chartColors = {
    primary: '#1A4D3A',
    secondary: '#3A6B4B',
    tertiary: '#5a8f68',
    accent: '#C6A15B',
    light: '#E8F0E9'
};

Chart.defaults.color = '#6E6E68';
Chart.defaults.borderColor = '#EDEDE9';


const kpiData = [
    <?php echo max(1, $totalProduits); ?>,
    <?php echo max(1, intval($totalStock / 100)); ?>,
    <?php echo max(1, $totalCommandes); ?>,
    <?php echo max(1, intval($totalRevenue / 1000)); ?>
];

new Chart(document.getElementById('kpiChart'), {
    type: 'doughnut',
    data: {
        labels: ['Produits', 'Stock (x100)', 'Commandes', 'Revenu (x1000€)'],
        labels: ['Produits', 'Stock (x100)', 'Commandes', 'Revenu (x1000 EUR)'],
        datasets: [{
            data: kpiData,
            backgroundColor: [
                chartColors.primary,
                chartColors.secondary,
                chartColors.tertiary,
                chartColors.accent
            ],
            borderColor: 'white',
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom', display: false },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.label || '';
                        if (label) label += ': ';
                        label += context.parsed;
                        return label;
                    }
                }
            }
        }
    }
});


const salesData = <?php echo json_encode(array_map(function($m) {
    return ['month' => substr($m['month_label'], 0, 3), 'revenue' => (float)$m['total_revenue']];
}, $salesByMonth)); ?>;

new Chart(document.getElementById('salesChart'), {
    type: 'line',
    data: {
        labels: salesData.map(d => d.month),
        datasets: [{
            label: 'Chiffre d\'Affaires',
            data: salesData.map(d => d.revenue),
            borderColor: chartColors.secondary,
            backgroundColor: 'rgba(58, 107, 75, 0.1)',
            borderWidth: 2,
            tension: 0.4,
            fill: true,
            pointRadius: 4,
            pointBackgroundColor: chartColors.primary,
            pointBorderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
    }
});


const categoryData = <?php echo json_encode(array_map(function($c) {
    return ['cat' => $c['categorie'], 'rev' => (float)$c['total_revenue']];
}, $salesByCategory)); ?>;

new Chart(document.getElementById('categoryChart'), {
    type: 'doughnut',
    data: {
        labels: categoryData.map(d => d.cat),
        datasets: [{
            data: categoryData.map(d => d.rev),
            backgroundColor: [
                chartColors.secondary,
                chartColors.accent,
                '#2C553E',
                '#5a8f68'
            ],
            borderColor: 'white',
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '62%',
        plugins: { legend: { position: 'bottom' } }
    }
});

async function exportToPDF() {
    const btn = document.getElementById('exportBtn');
    const originalText = btn.textContent;
    btn.innerHTML = '<span class="loading-spinner"></span> Telechargement...';
    btn.disabled = true;

    let reportFrame = null;

    try {
        if (!window.html2canvas || !window.jspdf || !window.jspdf.jsPDF) {
            throw new Error('Les bibliotheques PDF ne sont pas chargees.');
        }

        const { jsPDF } = window.jspdf;
        reportFrame = document.createElement('iframe');
        reportFrame.style.position = 'fixed';
        reportFrame.style.left = '-12000px';
        reportFrame.style.top = '0';
        reportFrame.style.width = '1120px';
        reportFrame.style.height = '1600px';
        reportFrame.style.border = '0';
        reportFrame.src = '../../Controllers/PdfExportWithCharts.php?action=export_pdf&format=html';
        document.body.appendChild(reportFrame);

        await new Promise((resolve, reject) => {
            reportFrame.onload = resolve;
            reportFrame.onerror = reject;
        });

        await new Promise(resolve => setTimeout(resolve, 700));

        const reportDocument = reportFrame.contentDocument || reportFrame.contentWindow.document;
        const report = reportDocument.querySelector('.report');
        if (!report) {
            throw new Error('Le rapport stylise est introuvable.');
        }

        const canvas = await html2canvas(report, {
            scale: 2,
            useCORS: true,
            backgroundColor: '#FFFFFF',
            windowWidth: report.scrollWidth,
            windowHeight: report.scrollHeight
        });

        const pdf = new jsPDF('p', 'mm', 'a4');
        const pageWidth = pdf.internal.pageSize.getWidth();
        const pageHeight = pdf.internal.pageSize.getHeight();
        const imgWidth = pageWidth;
        const imgHeight = (canvas.height * imgWidth) / canvas.width;
        const imageData = canvas.toDataURL('image/png', 1.0);

        let position = 0;
        let heightLeft = imgHeight;

        pdf.addImage(imageData, 'PNG', 0, position, imgWidth, imgHeight);
        heightLeft -= pageHeight;

        while (heightLeft > 0) {
            position = heightLeft - imgHeight;
            pdf.addPage();
            pdf.addImage(imageData, 'PNG', 0, position, imgWidth, imgHeight);
            heightLeft -= pageHeight;
        }

        const date = new Date().toISOString().slice(0, 10);
        pdf.save(`Rapport_Stabilis_${date}.pdf`);
    } catch (error) {
        console.error(error);
        window.location.href = '../../Controllers/PdfExportWithCharts.php?action=export_pdf';
    } finally {
        if (reportFrame) {
            reportFrame.remove();
        }
        btn.textContent = originalText;
        btn.disabled = false;
    }
}
</script>

<?php require_once __DIR__ . '/../../Views/partials/footer.php'; ?>
