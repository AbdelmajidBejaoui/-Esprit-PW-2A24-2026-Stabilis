<?php
require_once __DIR__ . '/../Services/DashboardService.php';

try {
    $action = $_POST['action'] ?? $_GET['action'] ?? null;
    
    if ($action !== 'export_all') {
        throw new Exception('Invalid action');
    }

    $dashboardService = new DashboardService();
    
    $metrics = $dashboardService->getDashboardMetrics();
    $topProducts = $dashboardService->getProductPerformance();
    $topCustomers = $dashboardService->getTopCustomers(5);
    $recentOrders = $dashboardService->getRecentOrders(10);
    $salesByMonth = $dashboardService->getSalesDataByMonth();
    $salesByCategory = $dashboardService->getSalesByCategory();

    $reportDate = date('d/m/Y H:i');
    $fileName = 'Dashboard_Report_' . date('Y-m-d_H-i-s');
    
    
    $html = '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Report - Stabilis</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { 
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: white;
            color: #333;
        }
        body { padding: 20px; }
        .page { 
            max-width: 1000px; 
            margin: 0 auto; 
            background: white;
            page-break-after: always;
        }
        .header {
            background: linear-gradient(135deg, #1A4D3A 0%, #3A6B4B 100%);
            color: white;
            padding: 40px;
            text-align: center;
            border-bottom: 5px solid #C6A15B;
            margin-bottom: 30px;
        }
        .header h1 { font-size: 32px; margin-bottom: 10px; font-weight: 700; }
        .header p { font-size: 14px; opacity: 0.9; }
        .section { margin-bottom: 40px; }
        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: white;
            background: linear-gradient(90deg, #1A4D3A 0%, #3A6B4B 100%);
            padding: 12px 16px;
            margin-bottom: 20px;
            border-radius: 4px;
            border-left: 5px solid #C6A15B;
        }
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        .metric-card {
            background: linear-gradient(135deg, #E8F0E9 0%, #F5F9F6 100%);
            border-left: 4px solid #3A6B4B;
            padding: 20px;
            border-radius: 6px;
            text-align: center;
        }
        .metric-label {
            font-size: 11px;
            font-weight: 600;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        .metric-value {
            font-size: 28px;
            font-weight: 700;
            color: #1A4D3A;
        }
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 30px;
            margin: 30px 0;
        }
        .chart-container {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 6px;
            border: 1px solid #E8F0E9;
            min-height: 300px;
        }
        .chart-title {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
        }
        canvas { max-width: 100%; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            font-size: 12px;
        }
        thead {
            background: linear-gradient(90deg, #1A4D3A 0%, #3A6B4B 100%);
        }
        th {
            padding: 10px;
            text-align: left;
            font-weight: 600;
            color: white;
            font-size: 11px;
            text-transform: uppercase;
        }
        td {
            padding: 10px;
            border-bottom: 1px solid #E8F0E9;
            color: #333;
        }
        tbody tr:nth-child(odd) { background: #F9FAFB; }
        tbody tr:hover { background: #E8F0E9; }
        .accent { color: #C6A15B; font-weight: 700; }
        .footer {
            background: linear-gradient(90deg, #1A4D3A 0%, #3A6B4B 100%);
            color: white;
            padding: 20px;
            text-align: center;
            font-size: 11px;
            border-top: 5px solid #C6A15B;
            margin-top: 40px;
        }
        @media print {
            body { padding: 0; }
            .page { page-break-after: always; }
            .charts-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="header">
            <h1>Stabilis Dashboard Report</h1>
            <p>Complete Business Analysis • Generated on ' . $reportDate . '</p>
        </div>

        <div class="section">
            <div class="section-title">Key Metrics</div>
            <div class="metrics-grid">
                <div class="metric-card">
                    <div class="metric-label">Total Products</div>
                    <div class="metric-value">' . $metrics['total_products'] . '</div>
                </div>
                <div class="metric-card">
                    <div class="metric-label">Total Stock</div>
                    <div class="metric-value">' . $metrics['total_stock'] . '</div>
                </div>
                <div class="metric-card">
                    <div class="metric-label">Total Orders</div>
                    <div class="metric-value">' . $metrics['total_orders'] . '</div>
                </div>
                <div class="metric-card">
                    <div class="metric-label">Revenue</div>
                    <div class="metric-value"><span class="accent">€' . number_format($metrics['total_revenue'], 0, ',', ' ') . '</span></div>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Dashboard Charts</div>
            <div class="charts-grid">
                <div class="chart-container">
                    <div class="chart-title">Sales by Month</div>
                    <canvas id="salesChart"></canvas>
                </div>
                <div class="chart-container">
                    <div class="chart-title">Sales by Category</div>
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Top Products</div>
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Revenue</th>
                    </tr>
                </thead>
                <tbody>';

    foreach (array_slice($topProducts, 0, 8) as $p) {
        $html .= '<tr>
                        <td><strong>' . htmlspecialchars($p['nom']) . '</strong></td>
                        <td>' . intval($p['total_sold']) . '</td>
                        <td class="accent">€' . number_format($p['revenue'], 2, ',', ' ') . '</td>
                    </tr>';
    }

    $html .= '</tbody>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Recent Orders</div>
            <table>
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Product</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>';

    foreach (array_slice($recentOrders, 0, 8) as $order) {
        $html .= '<tr>
                        <td>' . htmlspecialchars($order['email']) . '</td>
                        <td>' . htmlspecialchars(substr($order['product_name'], 0, 25)) . '</td>
                        <td class="accent">€' . number_format($order['final_total'], 2, ',', ' ') . '</td>
                        <td>' . htmlspecialchars($order['statut']) . '</td>
                    </tr>';
    }

    $html .= '</tbody>
            </table>
        </div>

        <div class="footer">
            <strong>Stabilis - Professional Dashboard Report</strong><br>
            All figures as of ' . $reportDate . '
        </div>
    </div>

    <script>
        const chartColors = {
            primary: "#1A4D3A",
            secondary: "#3A6B4B",
            accent: "#C6A15B"
        };

        Chart.defaults.color = "#666";
        Chart.defaults.borderColor = "#E8F0E9";
        Chart.defaults.font.family = "\"Segoe UI\", sans-serif";

        
        const salesData = ' . json_encode(array_map(function($m) {
            return ['month' => substr($m['month_label'], 0, 3), 'revenue' => (float)$m['total_revenue']];
        }, $salesByMonth)) . ';

        new Chart(document.getElementById("salesChart"), {
            type: "line",
            data: {
                labels: salesData.map(d => d.month),
                datasets: [{
                    label: "Revenue",
                    data: salesData.map(d => d.revenue),
                    borderColor: chartColors.secondary,
                    backgroundColor: "rgba(58, 107, 75, 0.1)",
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointRadius: 5,
                    pointBackgroundColor: chartColors.primary,
                    pointBorderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });

        
        const categoryData = ' . json_encode(array_map(function($c) {
            return ['cat' => $c['categorie'], 'rev' => (float)$c['total_revenue']];
        }, $salesByCategory)) . ';

        new Chart(document.getElementById("categoryChart"), {
            type: "doughnut",
            data: {
                labels: categoryData.map(d => d.cat),
                datasets: [{
                    data: categoryData.map(d => d.rev),
                    backgroundColor: [
                        chartColors.secondary,
                        chartColors.accent,
                        "#2C553E",
                        "#5a8f68"
                    ],
                    borderColor: "white",
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: { legend: { position: "bottom" } }
            }
        });

        
        setTimeout(() => {
            window.print();
        }, 1500);
    </script>
</body>
</html>';

    
    $exportDir = __DIR__ . '/../storage/exports/';
    if (!is_dir($exportDir)) {
        mkdir($exportDir, 0755, true);
    }
    
    $htmlFile = $exportDir . $fileName . '.html';
    file_put_contents($htmlFile, $html);
    
    
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: inline; filename="' . $fileName . '.html"');
    echo $html;
    exit;

} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
