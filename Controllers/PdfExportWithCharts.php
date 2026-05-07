<?php
require_once __DIR__ . '/../Services/DashboardService.php';
require_once __DIR__ . '/../Services/StyledPDFExporter.php';
require_once __DIR__ . '/../Controllers/ProduitController.php';
require_once __DIR__ . '/../Controllers/CommandeController.php';

class PdfExportWithCharts {
    private DashboardService $dashboardService;
    private ProduitController $produitController;
    private CommandeController $commandeController;
    private array $mailConfig;

    private array $palette = [
        '#1A4D3A',
        '#3A6B4B',
        '#5A8F68',
        '#C6A15B',
        '#2C553E',
        '#7FA98A'
    ];

    public function __construct() {
        $this->dashboardService = new DashboardService();
        $this->mailConfig = require __DIR__ . '/../config/mail.php';
        $this->produitController = new ProduitController($this->mailConfig);
        $this->commandeController = new CommandeController();
    }

    public function exportDashboard(): void {
        try {
            $produits = $this->produitController->getAll();
            $groupedOrders = $this->commandeController->getAllGroupedForBackoffice();
            $metrics = $this->getBackofficeMetrics($produits, $groupedOrders);
            $topProducts = $this->dashboardService->getProductPerformance();
            $recentOrders = array_slice($groupedOrders, 0, 10);
            $salesByMonth = $this->dashboardService->getSalesDataByMonth();
            $salesByCategory = $this->dashboardService->getSalesByCategory();
            $statusBreakdown = $this->dashboardService->getOrderStatusBreakdown();

            $html = $this->generateHtmlReport($metrics, $topProducts, $recentOrders, $salesByMonth, $salesByCategory, $statusBreakdown);

            if (isset($_GET['format']) && $_GET['format'] === 'html') {
                header('Content-Type: text/html; charset=utf-8');
                echo $html;
                return;
            }

            $pdf = StyledPDFExporter::generatePDF($html);
            $fileName = 'Rapport_Stabilis_' . date('Y-m-d_H-i-s') . '.pdf';

            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $fileName . '"');
            header('Content-Length: ' . strlen($pdf));
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
            echo $pdf;
        } catch (Exception $e) {
            http_response_code(500);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Erreur export PDF: ' . $e->getMessage();
        }
    }

    private function getBackofficeMetrics(array $produits, array $groupedOrders): array {
        $totalProducts = count($produits);
        $totalStock = array_sum(array_map(fn($product) => (int)($product['stock'] ?? 0), $produits));
        $lowStockProducts = 0;

        foreach ($produits as $produit) {
            if ((int)($produit['stock'] ?? 0) < (int)($this->mailConfig['alert_threshold'] ?? 5)) {
                $lowStockProducts++;
            }
        }

        $totalOrders = count($groupedOrders);
        $pendingOrders = 0;
        $totalRevenue = 0;

        foreach ($groupedOrders as $order) {
            $status = trim(strtolower($order['statut'] ?? ''));
            if ($status === 'en attente') {
                $pendingOrders++;
            }

            $finalTotal = (float)($order['final_total'] ?? 0);
            $totalRevenue += $finalTotal > 0 ? $finalTotal : (float)($order['total'] ?? 0);
        }

        return [
            'total_products' => $totalProducts,
            'total_orders' => $totalOrders,
            'total_stock' => $totalStock,
            'total_revenue' => $totalRevenue,
            'avg_order_value' => $totalOrders > 0 ? $totalRevenue / $totalOrders : 0,
            'pending_orders' => $pendingOrders,
            'low_stock_products' => $lowStockProducts
        ];
    }

    private function generateHtmlReport(array $metrics, array $topProducts, array $recentOrders, array $salesByMonth, array $salesByCategory, array $statusBreakdown): string {
        $reportDate = date('d/m/Y H:i');
        $totalProducts = (int)($metrics['total_products'] ?? 0);
        $totalStock = (int)($metrics['total_stock'] ?? 0);
        $totalOrders = (int)($metrics['total_orders'] ?? 0);
        $totalRevenue = (float)($metrics['total_revenue'] ?? 0);
        $pendingOrders = (int)($metrics['pending_orders'] ?? 0);
        $lowStockProducts = (int)($metrics['low_stock_products'] ?? 0);
        $avgOrder = (float)($metrics['avg_order_value'] ?? 0);

        $kpiSvg = $this->buildDonutSvg([
            ['label' => 'Produits', 'value' => max(1, $totalProducts), 'color' => $this->palette[0]],
            ['label' => 'Stock / 100', 'value' => max(1, round($totalStock / 100)), 'color' => $this->palette[1]],
            ['label' => 'Commandes', 'value' => max(1, $totalOrders), 'color' => $this->palette[2]],
            ['label' => 'Revenu / 1000', 'value' => max(1, round($totalRevenue / 1000)), 'color' => $this->palette[3]],
        ], $totalOrders, 'Commandes');

        $categoryTotalRevenue = array_sum(array_map(fn($row) => (float)($row['total_revenue'] ?? 0), $salesByCategory));
        $categorySvg = $this->buildDonutSvg(array_map(function ($row, $index) {
            return [
                'label' => $row['categorie'] ?? 'Categorie',
                'value' => (float)($row['total_revenue'] ?? 0),
                'color' => $this->palette[$index % count($this->palette)]
            ];
        }, $salesByCategory, array_keys($salesByCategory)), $categoryTotalRevenue, 'EUR');

        $maxRevenue = max(1, ...array_map(fn($row) => (float)($row['total_revenue'] ?? 0), $salesByMonth ?: [['total_revenue' => 1]]));

        ob_start();
        ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport Stabilis</title>
    <style>
        * { box-sizing: border-box; }
        :root {
            --green-900: #1A4D3A;
            --green-700: #3A6B4B;
            --green-500: #5A8F68;
            --green-100: #E8F0E9;
            --gold: #C6A15B;
            --ink: #26302A;
            --muted: #6E756D;
            --line: #DDE8DF;
            --paper: #FBFCFA;
        }
        body {
            margin: 0;
            background: #E9EFEA;
            color: var(--ink);
            font-family: Arial, Helvetica, sans-serif;
            line-height: 1.45;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .report {
            width: 1040px;
            max-width: calc(100% - 32px);
            margin: 24px auto;
            background: white;
            border: 1px solid var(--line);
            box-shadow: 0 18px 45px rgba(26, 77, 58, 0.12);
        }
        .cover {
            background: linear-gradient(135deg, var(--green-900), var(--green-700));
            color: white;
            padding: 34px 42px;
            border-bottom: 6px solid var(--gold);
        }
        .brand { font-size: 13px; letter-spacing: 1.6px; text-transform: uppercase; opacity: 0.82; }
        h1 { margin: 8px 0 8px; font-size: 34px; line-height: 1.1; }
        .cover p { margin: 0; color: #DCEBE1; }
        .content { padding: 34px 42px 42px; }
        .section { margin-bottom: 30px; page-break-inside: avoid; }
        .section-title {
            margin: 0 0 16px;
            color: var(--green-900);
            font-size: 18px;
            border-bottom: 2px solid var(--green-100);
            padding-bottom: 10px;
        }
        .metrics {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            margin-bottom: 24px;
        }
        .metric {
            background: linear-gradient(180deg, #FFFFFF 0%, var(--paper) 100%);
            border: 1px solid var(--line);
            border-left: 5px solid var(--green-700);
            padding: 16px;
            min-height: 108px;
        }
        .metric:nth-child(2) { border-left-color: var(--green-500); }
        .metric:nth-child(3) { border-left-color: var(--gold); }
        .metric:nth-child(4) { border-left-color: var(--green-900); }
        .metric-label { color: var(--muted); font-size: 11px; text-transform: uppercase; letter-spacing: 0.6px; }
        .metric-value { margin-top: 8px; color: var(--green-900); font-size: 26px; font-weight: 700; }
        .metric-note { margin-top: 6px; color: var(--muted); font-size: 12px; }
        .chart-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            align-items: stretch;
        }
        .chart-card {
            border: 1px solid var(--line);
            background: var(--paper);
            padding: 20px;
            min-height: 340px;
        }
        .chart-card h3 { margin: 0 0 16px; color: var(--green-900); font-size: 15px; }
        .donut-wrap { display: flex; justify-content: center; align-items: center; min-height: 220px; }
        .legend { display: grid; grid-template-columns: 1fr 1fr; gap: 8px 12px; margin-top: 14px; }
        .legend-item { display: flex; align-items: center; gap: 8px; color: var(--muted); font-size: 12px; }
        .swatch { width: 11px; height: 11px; border-radius: 2px; flex: 0 0 auto; }
        .bar-list { display: grid; gap: 11px; }
        .bar-row { display: grid; grid-template-columns: 92px 1fr 88px; gap: 10px; align-items: center; font-size: 12px; }
        .bar-track { height: 12px; background: #E5EDE7; border-radius: 999px; overflow: hidden; }
        .bar-fill { height: 100%; background: linear-gradient(90deg, var(--green-700), var(--green-500)); border-radius: 999px; }
        .bar-value { text-align: right; color: var(--green-900); font-weight: 700; }
        .status-strip {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }
        .status {
            background: var(--green-100);
            border: 1px solid var(--line);
            padding: 13px;
            text-align: center;
        }
        .status strong { display: block; color: var(--green-900); font-size: 22px; }
        .status span { color: var(--muted); font-size: 11px; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th {
            background: var(--green-700);
            color: white;
            text-align: left;
            padding: 10px 12px;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.5px;
        }
        td { padding: 10px 12px; border-bottom: 1px solid var(--line); }
        tr:nth-child(even) td { background: var(--paper); }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            background: var(--green-100);
            color: var(--green-900);
            font-weight: 700;
            font-size: 10px;
        }
        .footer {
            margin-top: 28px;
            padding-top: 16px;
            border-top: 1px solid var(--line);
            color: var(--muted);
            font-size: 11px;
            text-align: center;
        }
        @page { margin: 12mm; size: A4; }
        @media print {
            body { background: white; }
            .report {
                width: auto;
                max-width: none;
                margin: 0;
                border: 0;
                box-shadow: none;
            }
            .content { padding: 24px 0 0; }
            .cover { margin: 0 0 24px; }
            .chart-card, .metric, .status { break-inside: avoid; }
        }
    </style>
</head>
<body>
    <main class="report">
        <header class="cover">
            <div class="brand">Stabilis dashboard</div>
            <h1>Rapport de performance</h1>
            <p>Statistiques visuelles et capture synthetique du dashboard - <?php echo htmlspecialchars($reportDate); ?></p>
        </header>

        <div class="content">
            <section class="section">
                <h2 class="section-title">Indicateurs principaux</h2>
                <div class="metrics">
                    <div class="metric">
                        <div class="metric-label">Produits</div>
                        <div class="metric-value"><?php echo $totalProducts; ?></div>
                        <div class="metric-note">en catalogue</div>
                    </div>
                    <div class="metric">
                        <div class="metric-label">Stock total</div>
                        <div class="metric-value"><?php echo $totalStock; ?></div>
                        <div class="metric-note"><?php echo $lowStockProducts; ?> alerte(s) stock</div>
                    </div>
                    <div class="metric">
                        <div class="metric-label">Commandes</div>
                        <div class="metric-value"><?php echo $totalOrders; ?></div>
                        <div class="metric-note"><?php echo $pendingOrders; ?> en attente</div>
                    </div>
                    <div class="metric">
                        <div class="metric-label">Chiffre d'affaires</div>
                        <div class="metric-value"><?php echo number_format($totalRevenue, 0, ',', ' '); ?> EUR</div>
                        <div class="metric-note">panier moyen <?php echo number_format($avgOrder, 2, ',', ' '); ?> EUR</div>
                    </div>
                </div>
                <div class="status-strip">
                    <div class="status"><strong><?php echo max(0, $totalProducts - $lowStockProducts); ?></strong><span>Produits stock OK</span></div>
                    <div class="status"><strong><?php echo $lowStockProducts; ?></strong><span>Stock bas</span></div>
                    <div class="status"><strong><?php echo $pendingOrders; ?></strong><span>Commandes a suivre</span></div>
                </div>
            </section>

            <section class="section">
                <h2 class="section-title">Capture visuelle des stats</h2>
                <div class="chart-grid">
                    <div class="chart-card">
                        <h3>Vue circulaire des indicateurs</h3>
                        <div class="donut-wrap"><?php echo $kpiSvg; ?></div>
                        <?php echo $this->buildLegend([
                            ['label' => 'Produits', 'color' => $this->palette[0]],
                            ['label' => 'Stock', 'color' => $this->palette[1]],
                            ['label' => 'Commandes', 'color' => $this->palette[2]],
                            ['label' => 'Revenu', 'color' => $this->palette[3]],
                        ]); ?>
                    </div>
                    <div class="chart-card">
                        <h3>Ventes par categorie</h3>
                        <div class="donut-wrap"><?php echo $categorySvg; ?></div>
                        <?php echo $this->buildLegendFromCategories($salesByCategory); ?>
                    </div>
                </div>
            </section>

            <section class="section">
                <h2 class="section-title">Evolution des ventes</h2>
                <div class="chart-card">
                    <div class="bar-list">
                        <?php foreach ($salesByMonth as $month): 
                            $revenue = (float)($month['total_revenue'] ?? 0);
                            $width = min(100, max(2, ($revenue / $maxRevenue) * 100));
                        ?>
                        <div class="bar-row">
                            <div><?php echo htmlspecialchars($month['month_label'] ?? 'Mois'); ?></div>
                            <div class="bar-track"><div class="bar-fill" style="width: <?php echo $width; ?>%;"></div></div>
                            <div class="bar-value"><?php echo number_format($revenue, 0, ',', ' '); ?> EUR</div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <section class="section">
                <h2 class="section-title">Top produits</h2>
                <table>
                    <thead><tr><th>Produit</th><th>Vendu</th><th>Quantite</th><th>Revenue</th><th>Stock</th></tr></thead>
                    <tbody>
                        <?php foreach (array_slice($topProducts, 0, 8) as $product): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($product['nom'] ?? 'Produit'); ?></strong></td>
                            <td><?php echo (int)($product['times_sold'] ?? 0); ?></td>
                            <td><?php echo (int)($product['total_sold'] ?? 0); ?></td>
                            <td><?php echo number_format((float)($product['revenue'] ?? 0), 2, ',', ' '); ?> EUR</td>
                            <td><?php echo (int)($product['stock'] ?? 0); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>

            <section class="section">
                <h2 class="section-title">Commandes recentes</h2>
                <table>
                    <thead><tr><th>Client</th><th>Produit</th><th>Total</th><th>Statut</th></tr></thead>
                    <tbody>
                        <?php foreach ($recentOrders as $order): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($order['email'] ?? 'Client'); ?></td>
                            <td><?php echo htmlspecialchars($order['produits_resume'] ?? $order['product_name'] ?? 'Produit'); ?></td>
                            <td><?php echo number_format((float)($order['effective_total'] ?? $order['final_total'] ?? $order['total'] ?? 0), 2, ',', ' '); ?> EUR</td>
                            <td><span class="badge"><?php echo htmlspecialchars($order['statut'] ?? ''); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>

            <div class="footer">
                Stabilis - rapport genere le <?php echo htmlspecialchars($reportDate); ?>. Les montants sont exprimes en EUR.
            </div>
        </div>
    </main>
</body>
</html>
        <?php
        return ob_get_clean();
    }

    private function buildDonutSvg(array $items, ?float $centerValue, string $centerLabel): string {
        $total = array_sum(array_map(fn($item) => max(0, (float)$item['value']), $items));
        if ($total <= 0) {
            $items = [['label' => 'Vide', 'value' => 1, 'color' => '#DDE8DF']];
            $total = 1;
        }

        $radius = 78;
        $circumference = 2 * pi() * $radius;
        $offset = 0;
        $svg = '<svg width="230" height="230" viewBox="0 0 230 230" role="img" aria-label="' . htmlspecialchars($centerLabel) . '">';
        $svg .= '<circle cx="115" cy="115" r="' . $radius . '" fill="none" stroke="#E8F0E9" stroke-width="34"/>';

        foreach ($items as $item) {
            $value = max(0, (float)$item['value']);
            if ($value <= 0) {
                continue;
            }
            $length = ($value / $total) * $circumference;
            $svg .= '<circle cx="115" cy="115" r="' . $radius . '" fill="none" stroke="' . htmlspecialchars($item['color']) . '" stroke-width="34" stroke-dasharray="' . $length . ' ' . ($circumference - $length) . '" stroke-dashoffset="' . (-$offset) . '" transform="rotate(-90 115 115)"/>';
            $offset += $length;
        }

        $centerText = $centerValue === null ? '' : number_format($centerValue, 0, ',', ' ');
        $svg .= '<circle cx="115" cy="115" r="48" fill="#FFFFFF"/>';
        $svg .= '<text x="115" y="110" text-anchor="middle" font-family="Arial" font-size="25" font-weight="700" fill="#1A4D3A">' . htmlspecialchars($centerText) . '</text>';
        $svg .= '<text x="115" y="131" text-anchor="middle" font-family="Arial" font-size="11" font-weight="700" fill="#3A6B4B">' . htmlspecialchars($centerLabel) . '</text>';
        $svg .= '</svg>';

        return $svg;
    }

    private function buildLegend(array $items): string {
        $html = '<div class="legend">';
        foreach ($items as $item) {
            $html .= '<div class="legend-item"><span class="swatch" style="background:' . htmlspecialchars($item['color']) . '"></span><span>' . htmlspecialchars($item['label']) . '</span></div>';
        }
        return $html . '</div>';
    }

    private function buildLegendFromCategories(array $categories): string {
        $items = [];
        foreach (array_slice($categories, 0, 6) as $index => $category) {
            $items[] = [
                'label' => $category['categorie'] ?? 'Categorie',
                'color' => $this->palette[$index % count($this->palette)]
            ];
        }
        return $this->buildLegend($items ?: [['label' => 'Aucune vente', 'color' => '#DDE8DF']]);
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'export_pdf') {
    $exporter = new PdfExportWithCharts();
    $exporter->exportDashboard();
}
?>
