<?php
/**
 * Stabilis Database Seeder
 * Run this script once to add admin user and initial data
 * 
 * Usage: php storage/seed.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../Controllers/UserC.php';

class StabilisSeeder {
    private $userController;
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
        $this->userController = new UserC();
    }

    /**
     * Seed the database with initial admin user
     */
    public function seedAdminUser() {
        $email = 'stabilisatyourservice@gmail.com';
        $password = '12341234';
        $nom = 'Stabilis Admin';
        
        echo "🌱 Starting database seeding...\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        
        // Check if user already exists
        if ($this->userController->emailExists($email)) {
            echo "❌ User with email '$email' already exists.\n";
            echo "ℹ️  Skipping admin user creation.\n\n";
            return false;
        }
        
        try {
            // Create admin user (without face_image and face_descriptor as they don't exist in user table)
            $adminUser = new User(
                null, // ID will be auto-generated
                $nom,
                $email,
                $password,
                'admin', // Role: admin
                '', // No food preference initially
                date('Y-m-d H:i:s'), // Current timestamp
                1 // Account status: active
            );
            
            // Insert user
            $userId = $this->userController->insertUser($adminUser);
            
            echo "✅ Admin user created successfully!\n";
            echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            echo "📋 User Details:\n";
            echo "   ID:       $userId\n";
            echo "   Name:     $nom\n";
            echo "   Email:    $email\n";
            echo "   Password: $password\n";
            echo "   Role:     admin\n";
            echo "   Status:   active\n";
            echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
            
            return true;
        } catch (Exception $e) {
            echo "❌ Error creating admin user: " . $e->getMessage() . "\n";
            return false;
        }
    }

    /**
     * Display database statistics
     */
    public function displayStatistics() {
        try {
            echo "📊 Database Statistics:\n";
            echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            
            // Count users
            $userCount = $this->db->query('SELECT COUNT(*) FROM user')->fetchColumn();
            echo "   Users:        " . (int)$userCount . "\n";
            
            // Count products
            $productCount = $this->db->query('SELECT COUNT(*) FROM produits')->fetchColumn();
            echo "   Products:     " . (int)$productCount . "\n";
            
            // Count orders
            $orderCount = $this->db->query('SELECT COUNT(*) FROM commandes')->fetchColumn();
            echo "   Orders:       " . (int)$orderCount . "\n";
            
            // Count defis
            $defiCount = $this->db->query('SELECT COUNT(*) FROM defis')->fetchColumn();
            echo "   Challenges:   " . (int)$defiCount . "\n";
            
            // Count active promo codes
            $promoCount = $this->db->query('SELECT COUNT(*) FROM promo_codes WHERE active = 1')->fetchColumn();
            echo "   Active Promos: " . (int)$promoCount . "\n";
            
            echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
            
        } catch (Exception $e) {
            echo "⚠️  Could not fetch statistics: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Verify database tables exist
     */
    public function verifyDatabase() {
        echo "🔍 Verifying database tables...\n";
        
        $requiredTables = ['user', 'produits', 'commandes', 'defis', 'packs', 'promo_codes'];
        
        foreach ($requiredTables as $table) {
            try {
                $result = $this->db->query("SHOW TABLES LIKE '$table'");
                if ($result->rowCount() > 0) {
                    echo "   ✅ Table '$table' exists\n";
                } else {
                    echo "   ❌ Table '$table' NOT FOUND\n";
                }
            } catch (Exception $e) {
                echo "   ⚠️  Error checking table '$table': " . $e->getMessage() . "\n";
            }
        }
        echo "\n";
    }

    /**
     * Run all seeding operations
     */
    public function runAll() {
        echo "\n";
        echo "╔═══════════════════════════════════╗\n";
        echo "║     STABILIS DATABASE SEEDER      ║\n";
        echo "╚═══════════════════════════════════╝\n\n";
        
        $this->verifyDatabase();
        $this->seedAdminUser();
        $this->displayStatistics();
        
        echo "✨ Seeding completed!\n\n";
    }
}

// Run seeder if executed directly
if (php_sapi_name() === 'cli' || isset($_GET['seed'])) {
    $seeder = new StabilisSeeder();
    $seeder->runAll();
}
?>
