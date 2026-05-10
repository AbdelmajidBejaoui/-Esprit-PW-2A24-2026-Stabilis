<?php
/**
 * Stabilis Configuration Array
 * Contains all necessary application data and configuration
 */

require_once __DIR__ . '/database.php';

class StabilisConfig {
    private static $stabilis = null;

    public static function getStabilis() {
        if (self::$stabilis === null) {
            self::$stabilis = self::buildStabilisArray();
        }
        return self::$stabilis;
    }

    private static function buildStabilisArray() {
        $db = Database::getConnection();
        
        // Initialize Stabilis array with app metadata
        $stabilis = [
            'app_name' => 'Stabilis',
            'app_version' => '1.0.0',
            'tagline' => 'Sustainable Nutritional Performance Platform',
            'description' => 'Transform sports nutrition into a lever for sustainable performance',
            
            // Database configuration
            'database' => [
                'host' => 'localhost',
                'name' => 'stabilis',
                'charset' => 'utf8mb4'
            ],
            
            // Categories from products
            'categories' => self::getCategories($db),
            
            // All products
            'products' => self::getProducts($db),
            
            // Promo codes
            'promo_codes' => self::getPromoCodes($db),
            
            // Packs
            'packs' => self::getPacks($db),
            
            // Site events/banners
            'site_events' => self::getSiteEvents($db),
            
            // Challenges/Defis
            'defis' => self::getDefis($db),
            
            // Statistics
            'stats' => self::getStatistics($db),
            
            // Users count
            'total_users' => self::getTotalUsers($db),
        ];
        
        return $stabilis;
    }

    private static function getCategories($db) {
        try {
            $sql = 'SELECT DISTINCT categorie FROM produits WHERE categorie IS NOT NULL ORDER BY categorie';
            $query = $db->query($sql);
            $categories = [];
            
            while ($row = $query->fetch()) {
                $categories[] = $row['categorie'];
            }
            
            return $categories;
        } catch (Exception $e) {
            return [];
        }
    }

    private static function getProducts($db) {
        try {
            $sql = 'SELECT id, nom, prix, promo_prix, description, stock, coming_soon, categorie, image_url, date_creation 
                    FROM produits ORDER BY date_creation DESC';
            $query = $db->query($sql);
            return $query->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    private static function getPromoCodes($db) {
        try {
            $sql = 'SELECT code, discount, active, expires_at FROM promo_codes WHERE active = 1 ORDER BY expires_at DESC';
            $query = $db->query($sql);
            return $query->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    private static function getPacks($db) {
        try {
            $sql = 'SELECT id, nom, description, prix, image_url, active FROM packs WHERE active = 1 ORDER BY created_at DESC';
            $query = $db->query($sql);
            $packs = $query->fetchAll();
            
            // Attach pack items
            foreach ($packs as &$pack) {
                try {
                    $pack['items'] = self::getPackItems($db, $pack['id']);
                } catch (Exception $e) {
                    $pack['items'] = [];
                }
            }
            
            return $packs;
        } catch (Exception $e) {
            return [];
        }
    }

    private static function getPackItems($db, $packId) {
        try {
            $sql = 'SELECT pi.id, pi.produit_id, pi.quantite, p.nom, p.prix 
                    FROM pack_items pi 
                    JOIN produits p ON pi.produit_id = p.id 
                    WHERE pi.pack_id = :pack_id';
            $query = $db->prepare($sql);
            $query->execute(['pack_id' => $packId]);
            return $query->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    private static function getSiteEvents($db) {
        try {
            $sql = 'SELECT id, titre, message, code_promo, lien, bg_color, active 
                    FROM site_events WHERE active = 1 ORDER BY created_at DESC';
            $query = $db->query($sql);
            return $query->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    private static function getDefis($db) {
        try {
            $sql = 'SELECT id, nom, type, objectif, recompense, created_at FROM defis ORDER BY created_at DESC';
            $query = $db->query($sql);
            return $query->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    private static function getStatistics($db) {
        try {
            $stats = [
                'total_products' => 0,
                'total_orders' => 0,
                'total_users' => 0,
                'total_revenue' => 0,
            ];
            
            // Total products
            try {
                $result = $db->query('SELECT COUNT(*) FROM produits');
                $stats['total_products'] = (int)$result->fetchColumn();
            } catch (Exception $e) {}
            
            // Total orders (if table exists)
            try {
                $result = $db->query('SELECT COUNT(*) FROM commandes');
                $stats['total_orders'] = (int)$result->fetchColumn();
            } catch (Exception $e) {}
            
            // Total users
            try {
                $result = $db->query('SELECT COUNT(*) FROM user');
                $stats['total_users'] = (int)$result->fetchColumn();
            } catch (Exception $e) {}
            
            // Total revenue (if table exists)
            try {
                $result = $db->query('SELECT SUM(final_total) FROM commandes WHERE final_total IS NOT NULL');
                $stats['total_revenue'] = (float)($result->fetchColumn() ?? 0);
            } catch (Exception $e) {}
            
            return $stats;
        } catch (Exception $e) {
            return [];
        }
    }

    private static function getTotalUsers($db) {
        try {
            $result = $db->query('SELECT COUNT(*) FROM user');
            return (int)$result->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }
}

// Usage: $stabilis = StabilisConfig::getStabilis();
?>
