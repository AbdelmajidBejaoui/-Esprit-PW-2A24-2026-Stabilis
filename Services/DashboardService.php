<?php


require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Models/Commande.php';
require_once __DIR__ . '/../Models/Produit.php';

class DashboardService {
    private $db;
    private $effectiveTotal = "COALESCE(NULLIF(final_total, 0), total, 0)";
    private $effectiveTotalWithAlias = "COALESCE(NULLIF(c.final_total, 0), c.total, 0)";

    public function __construct() {
        $this->db = Database::getConnection();
    }

    
    public function getSalesDataByMonth() {
        $sql = "
            SELECT 
                DATE_FORMAT(date_commande, '%Y-%m') AS month,
                DATE_FORMAT(date_commande, '%b %Y') AS month_label,
                COUNT(*) AS order_count,
                SUM({$this->effectiveTotal}) AS total_revenue,
                SUM(total) AS total_before_discount,
                SUM(COALESCE(discount_amount, 0)) AS total_discount
            FROM commandes
            WHERE date_commande >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(date_commande, '%Y-%m')
            ORDER BY month ASC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    
    public function getSalesByCategory() {
        $sql = "
            SELECT 
                p.categorie,
                COUNT(c.id) AS order_count,
                SUM({$this->effectiveTotalWithAlias}) AS total_revenue,
                SUM(c.quantite) AS total_quantity
            FROM commandes c
            JOIN produits p ON c.produit_id = p.id
            GROUP BY p.categorie
            ORDER BY total_revenue DESC
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    
    public function getProductPerformance() {
        $sql = "
            SELECT 
                p.id,
                p.nom,
                p.stock,
                p.prix,
                COUNT(c.id) AS times_sold,
                SUM(c.quantite) AS total_sold,
                SUM({$this->effectiveTotalWithAlias}) AS revenue
            FROM produits p
            LEFT JOIN commandes c ON p.id = c.produit_id
            GROUP BY p.id
            ORDER BY times_sold DESC
            LIMIT 10
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    
    public function getOrderStatusBreakdown() {
        $sql = "
            SELECT 
                statut,
                COUNT(*) AS count,
                SUM({$this->effectiveTotal}) AS total_revenue
            FROM commandes
            GROUP BY statut
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    
    public function getDiscountStatistics() {
        $sql = "
            SELECT 
                COUNT(*) AS total_orders,
                SUM(CASE WHEN discount_percent > 0 THEN 1 ELSE 0 END) AS orders_with_discount,
                AVG(COALESCE(discount_percent, 0)) AS avg_discount_percent,
                SUM(COALESCE(discount_amount, 0)) AS total_discount_given,
                SUM(total) - SUM({$this->effectiveTotal}) AS total_discount_value
            FROM commandes
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    
    public function getDashboardMetrics() {
        $sql = "
            SELECT 
                (SELECT COUNT(*) FROM produits) AS total_products,
                (SELECT COUNT(*) FROM commandes) AS total_orders,
                (SELECT SUM(stock) FROM produits) AS total_stock,
                (SELECT SUM(COALESCE(NULLIF(final_total, 0), total, 0)) FROM commandes) AS total_revenue,
                (SELECT AVG(COALESCE(NULLIF(final_total, 0), total, 0)) FROM commandes) AS avg_order_value,
                (SELECT COUNT(*) FROM commandes WHERE LOWER(TRIM(statut)) = 'en attente') AS pending_orders,
                (SELECT COUNT(*) FROM produits WHERE stock < 5) AS low_stock_products
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    
    public function getRecentOrders($limit = 10) {
        $limit = intval($limit);
        $sql = "
            SELECT 
                c.*,
                {$this->effectiveTotalWithAlias} AS effective_total,
                p.nom AS product_name,
                p.categorie
            FROM commandes c
            JOIN produits p ON c.produit_id = p.id
            ORDER BY c.date_commande DESC
            LIMIT " . $limit . "
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    
    public function getCustomerStatistics() {
        $sql = "
            SELECT 
                COUNT(DISTINCT email) AS total_customers,
                COUNT(*) AS total_orders,
                SUM({$this->effectiveTotal}) AS total_revenue,
                AVG({$this->effectiveTotal}) AS avg_order_value,
                MAX({$this->effectiveTotal}) AS highest_order,
                MIN({$this->effectiveTotal}) AS lowest_order
            FROM commandes
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    
    public function getTopCustomers($limit = 5) {
        $limit = intval($limit);
        $sql = "
            SELECT 
                email,
                COUNT(*) AS order_count,
                SUM({$this->effectiveTotal}) AS total_spent,
                MAX(date_commande) AS last_order_date
            FROM commandes
            GROUP BY email
            ORDER BY total_spent DESC
            LIMIT " . $limit . "
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
