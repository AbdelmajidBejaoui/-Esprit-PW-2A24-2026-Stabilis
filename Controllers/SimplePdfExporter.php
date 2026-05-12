<?php
require_once __DIR__ . '/../Services/DashboardService.php';
require_once __DIR__ . '/../Services/StyledPDFExporter.php';

try {
    $action = $_POST['action'] ?? null;
    
    if (!$action) {
        throw new Exception('No action specified');
    }

    $dashboardService = new DashboardService();
    
    $metrics = $dashboardService->getDashboardMetrics();
    $topProducts = $dashboardService->getProductPerformance();
    $topCustomers = $dashboardService->getTopCustomers(5);
    $recentOrders = $dashboardService->getRecentOrders(10);
    $salesByCategory = $dashboardService->getSalesByCategory();

    
    $reportDate = date('d/m/Y H:i');
    $fileName = 'Dashboard_Report_' . date('Y-m-d_H-i-s');
    
    $html = '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Report - Stabilis</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html, body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: #ffffff;
            color: #333;
            line-height: 1.6;
        }
        
        body {
            padding: 20px;
            background: #f5f5f5;
        }
        
        .container {
            max-width: 950px;
            margin: 0 auto;
            background: white;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        .header {
            background: linear-gradient(135deg, #1A4D3A 0%, #3A6B4B 100%);
            color: white;
            padding: 50px 40px;
            text-align: center;
            border-bottom: 5px solid #C6A15B;
            position: relative;
            overflow: hidden;
        }
        
        .header::before {
            content: "";
            position: absolute;
            top: -50%;
            right: -10%;
            width: 400px;
            height: 400px;
            background: rgba(255,255,255,0.05);
            border-radius: 50%;
        }
        
        .header-content {
            position: relative;
            z-index: 1;
        }
        
        .header h1 {
            font-size: 38px;
            margin-bottom: 8px;
            font-weight: 700;
            letter-spacing: 1px;
        }
        
        .header .subtitle {
            font-size: 15px;
            opacity: 0.95;
            letter-spacing: 2px;
            text-transform: uppercase;
        }
        
        .header .date {
            font-size: 12px;
            opacity: 0.85;
            margin-top: 15px;
            font-weight: 500;
        }
        
        .section {
            padding: 40px;
            border-bottom: 2px solid #E8F0E9;
            page-break-inside: avoid;
        }
        
        .section:last-child {
            border-bottom: none;
        }
        
        .section-title {
            font-size: 20px;
            font-weight: 700;
            color: white;
            background: linear-gradient(90deg, #1A4D3A 0%, #3A6B4B 100%);
            padding: 12px 16px;
            margin-bottom: 25px;
            border-radius: 4px;
            border-left: 5px solid #C6A15B;
            display: inline-block;
        }
        
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .metric-card {
            background: linear-gradient(135deg, #E8F0E9 0%, #F5F9F6 100%);
            border-left: 5px solid #3A6B4B;
            border-top: 1px solid #3A6B4B;
            padding: 25px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(26, 77, 58, 0.1);
            page-break-inside: avoid;
        }
        
        .metric-label {
            font-size: 11px;
            font-weight: 700;
            color: #1A4D3A;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }
        
        .metric-value {
            font-size: 32px;
            font-weight: 700;
            color: #1A4D3A;
            line-height: 1;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 13px;
            page-break-inside: avoid;
        }
        
        thead {
            background: linear-gradient(90deg, #1A4D3A 0%, #3A6B4B 100%);
        }
        
        th {
            padding: 14px 16px;
            text-align: left;
            font-weight: 600;
            color: white;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        td {
            padding: 12px 16px;
            border-bottom: 1px solid #E8F0E9;
            color: #333;
        }
        
        tbody tr:nth-child(odd) {
            background: #F9FAFB;
        }
        
        tbody tr:nth-child(even) {
            background: white;
        }
        
        tbody tr:hover {
            background: #E8F0E9;
        }
        
        .accent {
            color: #C6A15B;
            font-weight: 700;
        }
        
        .footer {
            background: linear-gradient(90deg, #1A4D3A 0%, #3A6B4B 100%);
            color: white;
            padding: 25px 40px;
            text-align: center;
            font-size: 11px;
            border-top: 5px solid #C6A15B;
        }
        
        .footer strong {
            display: block;
            font-size: 14px;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .container {
                box-shadow: none;
                max-width: 100%;
            }
            .section {
                page-break-inside: avoid;
                padding: 30px;
            }
            table {
                page-break-inside: avoid;
            }
            thead {
                display: table-header-group;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-content">
                <h1>Stabilis Dashboard Report</h1>
                <div class="subtitle">Complete Business Analysis</div>
                <div class="date">Generated on ' . $reportDate . '</div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Key Metrics Overview</div>
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
            <div class="section-title">Top Products Performance</div>
            <table>
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>Units Sold</th>
                        <th>Revenue</th>
                    </tr>
                </thead>
                <tbody>';

    foreach (array_slice($topProducts, 0, 8) as $product) {
        $html .= '<tr>
                        <td><strong>' . htmlspecialchars($product['nom']) . '</strong></td>
                        <td>' . intval($product['total_sold']) . '</td>
                        <td class="accent">€' . number_format($product['revenue'], 2, ',', ' ') . '</td>
                    </tr>';
    }

    $html .= '</tbody>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Top Customers</div>
            <table>
                <thead>
                    <tr>
                        <th>Customer Email</th>
                        <th>Orders Placed</th>
                        <th>Total Spent</th>
                    </tr>
                </thead>
                <tbody>';

    foreach (array_slice($topCustomers, 0, 8) as $customer) {
        $html .= '<tr>
                        <td>' . htmlspecialchars($customer['email']) . '</td>
                        <td>' . $customer['order_count'] . '</td>
                        <td class="accent">€' . number_format($customer['total_spent'], 2, ',', ' ') . '</td>
                    </tr>';
    }

    $html .= '</tbody>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Sales by Category</div>
            <table>
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Revenue</th>
                        <th>Share</th>
                    </tr>
                </thead>
                <tbody>';

    $totalCategoryRevenue = array_sum(array_column($salesByCategory, 'total_revenue'));
    foreach ($salesByCategory as $cat) {
        $percentage = $totalCategoryRevenue > 0 ? ($cat['total_revenue'] / $totalCategoryRevenue * 100) : 0;
        $html .= '<tr>
                        <td><strong>' . htmlspecialchars($cat['categorie']) . '</strong></td>
                        <td class="accent">€' . number_format($cat['total_revenue'], 2, ',', ' ') . '</td>
                        <td>' . number_format($percentage, 1, ',', ' ') . '%</td>
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
                        <th>Customer Email</th>
                        <th>Product</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>';

    foreach (array_slice($recentOrders, 0, 10) as $order) {
        $html .= '<tr>
                        <td>' . htmlspecialchars($order['email']) . '</td>
                        <td>' . htmlspecialchars(substr($order['product_name'], 0, 30)) . '</td>
                        <td class="accent">€' . number_format($order['final_total'], 2, ',', ' ') . '</td>
                        <td>' . htmlspecialchars($order['statut']) . '</td>
                    </tr>';
    }

    $html .= '</tbody>
            </table>
        </div>

        <div class="footer">
            <strong>Stabilis - Professional Dashboard Report</strong>
            Generated automatically at ' . $reportDate . '<br>
            All figures are as of report generation date
        </div>
    </div>
</body>
</html>';

    
    $exportDir = __DIR__ . '/../storage/exports/';
    if (!is_dir($exportDir)) {
        mkdir($exportDir, 0755, true);
    }
    
    
    $pdf = StyledPDFExporter::generatePDF($html);
    
    
    $pdfFileName = $fileName . '.pdf';
    $filePath = $exportDir . $pdfFileName;
    file_put_contents($filePath, $pdf);
    
    
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $pdfFileName . '"');
    header('Content-Length: ' . strlen($pdf));
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    
    echo $pdf;
    exit;

} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Error generating PDF: ' . $e->getMessage()
    ]);
    exit;
}
?>
