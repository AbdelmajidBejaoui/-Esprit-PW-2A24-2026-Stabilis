<?php


require_once __DIR__ . '/../Services/DashboardService.php';

class DashboardPDFExportService {
    private $dashboardService;
    private $colors = [
        'primary' => '#1A4D3A',      
        'secondary' => '#3A6B4B',    
        'accent' => '#2C553E',       
        'light' => '#E8F0E9',        
        'text' => '#2B2D2A',         
        'muted' => '#9C9C94'         
    ];

    public function __construct() {
        $this->dashboardService = new DashboardService();
    }

    
    public function generateFullReport() {
        $metrics = $this->dashboardService->getDashboardMetrics();
        $customerStats = $this->dashboardService->getCustomerStatistics();
        $discountStats = $this->dashboardService->getDiscountStatistics();
        $salesByMonth = $this->dashboardService->getSalesDataByMonth();
        $salesByCategory = $this->dashboardService->getSalesByCategory();
        $topProducts = $this->dashboardService->getProductPerformance();
        $topCustomers = $this->dashboardService->getTopCustomers(5);

        $html = $this->buildFullReportHTML($metrics, $customerStats, $discountStats, $salesByMonth, $salesByCategory, $topProducts, $topCustomers);
        
        return $this->renderPDF($html, 'Rapport_Stabilis_' . date('Y-m-d'));
    }

    
    public function generateSalesReport() {
        $salesByMonth = $this->dashboardService->getSalesDataByMonth();
        $salesByCategory = $this->dashboardService->getSalesByCategory();
        $customerStats = $this->dashboardService->getCustomerStatistics();
        
        $html = $this->buildSalesReportHTML($salesByMonth, $salesByCategory, $customerStats);
        
        return $this->renderPDF($html, 'Rapport_Ventes_' . date('Y-m-d'));
    }

    
    public function generateProductReport() {
        $topProducts = $this->dashboardService->getProductPerformance();
        $discountStats = $this->dashboardService->getDiscountStatistics();
        
        $html = $this->buildProductReportHTML($topProducts, $discountStats);
        
        return $this->renderPDF($html, 'Rapport_Produits_' . date('Y-m-d'));
    }

    
    private function buildFullReportHTML($metrics, $customerStats, $discountStats, $salesByMonth, $salesByCategory, $topProducts, $topCustomers) {
        $reportDate = date('d/m/Y H:i');
        $totalRevenueFormatted = number_format($metrics['total_revenue'], 0, ',', ' ');
        $avgOrderValueFormatted = number_format($customerStats['avg_order_value'], 2, ',', ' ');
        $totalDiscountValueFormatted = number_format($discountStats['total_discount_value'], 2, ',', ' ');
        $avgDiscountPercentFormatted = number_format($discountStats['avg_discount_percent'], 0);
        $totalDiscountGivenFormatted = number_format($discountStats['total_discount_given'], 2, ',', ' ');
        
        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Rapport Stabilis</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; color: {$this->colors['text']}; line-height: 1.6; }
        .page-break { page-break-after: always; }
        
        
        .header {
            background: {$this->colors['primary']};
            color: white;
            padding: 40px;
            text-align: center;
            margin-bottom: 30px;
            border-radius: 10px;
        }
        .header h1 { font-size: 32px; margin-bottom: 5px; }
        .header p { font-size: 14px; opacity: 0.9; }
        
        
        .metrics-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 15px;
            margin-bottom: 30px;
        }
        .metric-card {
            background: {$this->colors['light']};
            border-left: 4px solid {$this->colors['secondary']};
            padding: 20px;
            border-radius: 8px;
        }
        .metric-label { font-size: 12px; color: {$this->colors['muted']}; text-transform: uppercase; margin-bottom: 5px; }
        .metric-value { font-size: 28px; font-weight: bold; color: {$this->colors['primary']}; }
        .metric-subtext { font-size: 12px; color: {$this->colors['muted']}; margin-top: 5px; }
        
        
        .section {
            margin-bottom: 40px;
            page-break-inside: avoid;
        }
        .section-title {
            font-size: 20px;
            font-weight: bold;
            color: {$this->colors['primary']};
            border-bottom: 2px solid {$this->colors['secondary']};
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th {
            background: {$this->colors['secondary']};
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: bold;
            font-size: 12px;
        }
        td {
            padding: 10px 12px;
            border-bottom: 1px solid #ddd;
            font-size: 12px;
        }
        tr:nth-child(even) { background: {$this->colors['light']}; }
        tr:hover { background: rgba(58, 107, 75, 0.1); }
        
        
        .stats-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .stat-box {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
        }
        .stat-box h3 { font-size: 14px; color: {$this->colors['secondary']}; margin-bottom: 8px; }
        .stat-value { font-size: 24px; font-weight: bold; color: {$this->colors['primary']}; }
        
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 11px;
            color: {$this->colors['muted']};
        }
    </style>
</head>
<body>
    
    <div class="header">
        <h1>📊 Rapport Stabilis™</h1>
        <p>Statistiques Complètes • {$reportDate}</p>
    </div>
    
    
    <div class="section">
        <div class="section-title">📈 Indicateurs Clés</div>
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-label">Produits</div>
                <div class="metric-value">{$metrics['total_products']}</div>
                <div class="metric-subtext">en catalogue</div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Commandes</div>
                <div class="metric-value">{$metrics['total_orders']}</div>
                <div class="metric-subtext">au total</div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Chiffre d'Affaires</div>
                <div class="metric-value">{$totalRevenueFormatted} €</div>
                <div class="metric-subtext">revenue généré</div>
            </div>
        </div>
    </div>

    
    <div class="section">
        <div class="section-title">👥 Statistiques Clients</div>
        <div class="stats-row">
            <div class="stat-box">
                <h3>Total Clients</h3>
                <div class="stat-value">{$customerStats['total_customers']}</div>
            </div>
            <div class="stat-box">
                <h3>Panier Moyen</h3>
                <div class="stat-value">{$avgOrderValueFormatted} €</div>
            </div>
        </div>
        
        <div class="section-title" style="margin-top: 20px; font-size: 16px;">Top 5 Meilleurs Clients</div>
        <table>
            <thead>
                <tr>
                    <th>Email</th>
                    <th>Commandes</th>
                    <th>Total Dépensé</th>
                    <th>Dernière Commande</th>
                </tr>
            </thead>
            <tbody>
HTML;
        
        foreach ($topCustomers as $customer) {
            $lastOrder = date('d/m/Y', strtotime($customer['last_order_date']));
            $customerTotalSpent = number_format($customer['total_spent'], 2, ',', ' ');
            $html .= <<<HTML
                <tr>
                    <td>{$customer['email']}</td>
                    <td>{$customer['order_count']}</td>
                    <td>{$customerTotalSpent} €</td>
                    <td>{$lastOrder}</td>
                </tr>
HTML;
        }
        
        $html .= <<<HTML
            </tbody>
        </table>
    </div>

    
    <div class="section page-break">
        <div class="section-title">🏪 Ventes par Catégorie</div>
        <table>
            <thead>
                <tr>
                    <th>Catégorie</th>
                    <th>Commandes</th>
                    <th>Quantité Vendue</th>
                    <th>Chiffre d'Affaires</th>
                </tr>
            </thead>
            <tbody>
HTML;
        
        foreach ($salesByCategory as $category) {
            $categoryRevenue = number_format($category['total_revenue'], 2, ',', ' ');
            $html .= <<<HTML
                <tr>
                    <td>{$category['categorie']}</td>
                    <td>{$category['order_count']}</td>
                    <td>{$category['total_quantity']}</td>
                    <td>{$categoryRevenue} €</td>
                </tr>
HTML;
        }
        
        $html .= <<<HTML
            </tbody>
        </table>
    </div>

    
    <div class="section">
        <div class="section-title">🏆 Top 10 Produits</div>
        <table>
            <thead>
                <tr>
                    <th>Produit</th>
                    <th>Fois Vendu</th>
                    <th>Quantité</th>
                    <th>Chiffre d'Affaires</th>
                    <th>Stock</th>
                </tr>
            </thead>
            <tbody>
HTML;
        
        foreach ($topProducts as $product) {
            $stockClass = $product['stock'] < 5 ? 'style="color: #c55a4a; font-weight: bold;"' : '';
            $productRevenue = number_format($product['revenue'], 2, ',', ' ');
            $html .= <<<HTML
                <tr>
                    <td>{$product['nom']}</td>
                    <td>{$product['times_sold']}</td>
                    <td>{$product['total_sold']}</td>
                    <td>{$productRevenue} €</td>
                    <td $stockClass>{$product['stock']}</td>
                </tr>
HTML;
        }
        
        $html .= <<<HTML
            </tbody>
        </table>
    </div>

    
    <div class="section">
        <div class="section-title">💰 Statistiques Promotions</div>
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-label">Réductions Accordées</div>
                <div class="metric-value">{$discountStats['orders_with_discount']}</div>
                <div class="metric-subtext">sur {$discountStats['total_orders']} commandes</div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Valeur Totale Réduite</div>
                <div class="metric-value">{$totalDiscountValueFormatted} €</div>
                <div class="metric-subtext">soit {$avgDiscountPercentFormatted} % en moyenne</div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Économies Clients</div>
                <div class="metric-value">{$totalDiscountGivenFormatted} €</div>
                <div class="metric-subtext">économies générées</div>
            </div>
        </div>
    </div>

    
    <div class="section page-break">
        <div class="section-title">📊 Évolution des Ventes (12 derniers mois)</div>
        <table>
            <thead>
                <tr>
                    <th>Mois</th>
                    <th>Commandes</th>
                    <th>Chiffre d'Affaires</th>
                    <th>Réduction Accordée</th>
                    <th>Net</th>
                </tr>
            </thead>
            <tbody>
HTML;
        
        foreach ($salesByMonth as $month) {
            $monthBeforeDiscount = number_format($month['total_before_discount'], 2, ',', ' ');
            $monthDiscount = number_format($month['total_discount'], 2, ',', ' ');
            $monthRevenue = number_format($month['total_revenue'], 2, ',', ' ');
            $html .= <<<HTML
                <tr>
                    <td>{$month['month_label']}</td>
                    <td>{$month['order_count']}</td>
                    <td>{$monthBeforeDiscount} €</td>
                    <td>-{$monthDiscount} €</td>
                    <td><strong>{$monthRevenue} €</strong></td>
                </tr>
HTML;
        }
        
        $html .= <<<HTML
            </tbody>
        </table>
    </div>

    
    <div class="footer">
        <p><strong>Stabilis™ - Nutrition Adaptative · Durable</strong></p>
        <p>Rapport généré le {$reportDate}</p>
        <p>Tous les montants sont en euros (€)</p>
    </div>
</body>
</html>
HTML;
        
        return $html;
    }

    
    private function buildSalesReportHTML($salesByMonth, $salesByCategory, $customerStats) {
        $reportDate = date('d/m/Y H:i');
        
        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; color: {$this->colors['text']}; line-height: 1.6; }
        .header {
            background: {$this->colors['primary']};
            color: white;
            padding: 40px;
            text-align: center;
            margin-bottom: 30px;
            border-radius: 10px;
        }
        .header h1 { font-size: 32px; margin-bottom: 5px; }
        .section-title {
            font-size: 20px;
            font-weight: bold;
            color: {$this->colors['primary']};
            border-bottom: 2px solid {$this->colors['secondary']};
            padding-bottom: 10px;
            margin: 30px 0 20px 0;
        }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background: {$this->colors['secondary']}; color: white; padding: 12px; text-align: left; }
        td { padding: 10px 12px; border-bottom: 1px solid #ddd; }
        tr:nth-child(even) { background: {$this->colors['light']}; }
    </style>
</head>
<body>
    <div class="header">
        <h1>📊 Rapport Ventes</h1>
        <p>{$reportDate}</p>
    </div>
    
    <div class="section-title">📈 Ventes par Mois</div>
    <table>
        <thead><tr><th>Mois</th><th>Commandes</th><th>Chiffre d'Affaires</th><th>Net (après réduction)</th></tr></thead>
        <tbody>
HTML;
        foreach ($salesByMonth as $month) {
            $totalBeforeDiscount = number_format($month['total_before_discount'], 2, ',', ' ');
            $totalRevenue = number_format($month['total_revenue'], 2, ',', ' ');
            $html .= "<tr><td>{$month['month_label']}</td><td>{$month['order_count']}</td><td>{$totalBeforeDiscount} €</td><td>{$totalRevenue} €</td></tr>";
        }
        $html .= <<<HTML
        </tbody>
    </table>
    
    <div class="section-title">🏪 Ventes par Catégorie</div>
    <table>
        <thead><tr><th>Catégorie</th><th>Commandes</th><th>Quantité</th><th>Chiffre d'Affaires</th></tr></thead>
        <tbody>
HTML;
        foreach ($salesByCategory as $cat) {
            $categoryRevenue = number_format($cat['total_revenue'], 2, ',', ' ');
            $html .= "<tr><td>{$cat['categorie']}</td><td>{$cat['order_count']}</td><td>{$cat['total_quantity']}</td><td>{$categoryRevenue} €</td></tr>";
        }
        $html .= <<<HTML
        </tbody>
    </table>
</body>
</html>
HTML;
        return $html;
    }

    
    private function buildProductReportHTML($topProducts, $discountStats) {
        $reportDate = date('d/m/Y H:i');
        
        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; color: {$this->colors['text']}; line-height: 1.6; }
        .header { background: {$this->colors['primary']}; color: white; padding: 40px; text-align: center; margin-bottom: 30px; border-radius: 10px; }
        .header h1 { font-size: 32px; }
        .section-title { font-size: 20px; font-weight: bold; color: {$this->colors['primary']}; border-bottom: 2px solid {$this->colors['secondary']}; padding-bottom: 10px; margin: 30px 0 20px 0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background: {$this->colors['secondary']}; color: white; padding: 12px; text-align: left; }
        td { padding: 10px 12px; border-bottom: 1px solid #ddd; }
        tr:nth-child(even) { background: {$this->colors['light']}; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🏆 Rapport Produits</h1>
        <p>{$reportDate}</p>
    </div>
    
    <div class="section-title">Top 10 Produits</div>
    <table>
        <thead><tr><th>Produit</th><th>Fois Vendu</th><th>Quantité</th><th>Chiffre d'Affaires</th><th>Stock</th></tr></thead>
        <tbody>
HTML;
        foreach ($topProducts as $product) {
            $productRevenue = number_format($product['revenue'], 2, ',', ' ');
            $html .= "<tr><td>{$product['nom']}</td><td>{$product['times_sold']}</td><td>{$product['total_sold']}</td><td>{$productRevenue} €</td><td>{$product['stock']}</td></tr>";
        }
        $html .= <<<HTML
        </tbody>
    </table>
</body>
</html>
HTML;
        return $html;
    }

    
    private function renderPDF($html, $filename) {
        
        
        
        $exportDir = __DIR__ . '/../storage/exports/';
        if (!is_dir($exportDir)) {
            mkdir($exportDir, 0755, true);
        }

        
        $fullHtml = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$filename}</title>
    <style>
        @page { margin: 20mm; }
        * { margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; color: #2B2D2A; line-height: 1.6; }
        @media print { body { font-size: 10pt; } }
    </style>
</head>
<body>
{$html}
<script>
    
    window.addEventListener('load', function() {
        window.print();
    });
</script>
</body>
</html>
HTML;

        $htmlPath = $exportDir . $filename . '.html';
        file_put_contents($htmlPath, $fullHtml);
        
        return $htmlPath;
    }
}
