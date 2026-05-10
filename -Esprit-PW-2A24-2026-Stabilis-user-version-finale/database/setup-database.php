<?php
/**
 * Database Setup Script - Run once to initialize the database
 * Access: http://localhost/-Esprit-PW-2A24-2026-Stabilis-user-version-finale/database/setup-database.php
 * 
 * This script will:
 * 1. Create the database if it doesn't exist
 * 2. Create all required tables (user, defis, participations)
 * 3. Insert test data
 * 
 * DELETE THIS FILE after running for security!
 */

$db_host = 'localhost';
$db_user = 'root';
$db_pass = ''; // Change if you have a password set

// Connect without selecting a database
$mysqli = new mysqli($db_host, $db_user, $db_pass);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

echo "<h1>Database Setup Script</h1>";
echo "<pre>";

// Create database if not exists
$db_name = 'stabilis';
$sql = "CREATE DATABASE IF NOT EXISTS $db_name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if ($mysqli->query($sql)) {
    echo "✓ Database '$db_name' created or already exists\n";
} else {
    die("✗ Error creating database: " . $mysqli->error . "\n");
}

// Select the database
$mysqli->select_db($db_name);

// Create user table
echo "\n--- Creating user table ---\n";
$sql = "CREATE TABLE IF NOT EXISTS `user` (
    id INT(11) NOT NULL AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'client',
    preference_alimentaire VARCHAR(50) NOT NULL,
    date_inscription DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    statut_compte TINYINT(1) NOT NULL DEFAULT 1,
    face_image LONGBLOB NULL,
    face_descriptor TEXT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_user_email (email),
    KEY idx_user_role (role),
    KEY idx_user_statut (statut_compte)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($mysqli->query($sql)) {
    echo "✓ Users table created\n";
} else {
    echo "✗ Error creating users table: " . $mysqli->error . "\n";
}

// Insert test user
echo "\n--- Inserting test user ---\n";
$sql = "INSERT IGNORE INTO `user` (id, nom, email, password, role, preference_alimentaire, statut_compte)
        VALUES (1, 'Test User', 'test@example.com', '', 'client', 'aucune', 1)";
if ($mysqli->query($sql)) {
    echo "✓ Test user inserted (ID: 1)\n";
} else {
    echo "✗ Error inserting test user: " . $mysqli->error . "\n";
}

// Create defis table
echo "\n--- Creating defis table ---\n";
$sql = "CREATE TABLE IF NOT EXISTS defis (
    id INT(11) NOT NULL AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    type ENUM('aliment', 'entrainement', 'compensation') NOT NULL,
    objectif TEXT NOT NULL,
    recompense VARCHAR(50) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($mysqli->query($sql)) {
    echo "✓ Defis table created\n";
} else {
    echo "✗ Error creating defis table: " . $mysqli->error . "\n";
}

// Insert sample challenges if table is empty
$result = $mysqli->query("SELECT COUNT(*) as count FROM defis");
$row = $result->fetch_assoc();
if ($row['count'] == 0) {
    echo "\n--- Inserting sample challenges ---\n";
    $challenges = [
        ['Protéines végétales', 'aliment', 'Remplacer les protéines animales par des protéines végétales pendant 7 jours', '150'],
        ['Circuit training', 'entrainement', 'Compléter un circuit training de 30 minutes, 3 fois par semaine', '100'],
        ['Zéro déchet cuisine', 'compensation', 'Réduire les déchets de cuisine de 50% en utilisant compost et conservation', '120'],
        ['5 fruits et légumes', 'aliment', 'Manger 5 fruits et légumes locaux et de saison chaque jour pendant 2 semaines', '200'],
        ['Marche quotidienne', 'entrainement', 'Marcher 30 minutes par jour pendant 1 mois', '80'],
        ['Réduire le sucre', 'aliment', 'Éliminer les sucres ajoutés de son alimentation pendant 10 jours', '180'],
    ];
    
    foreach ($challenges as $challenge) {
        $sql = "INSERT INTO defis (nom, type, objectif, recompense) VALUES (?, ?, ?, ?)";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('ssss', $challenge[0], $challenge[1], $challenge[2], $challenge[3]);
        if ($stmt->execute()) {
            echo "✓ Challenge '{$challenge[0]}' inserted\n";
        } else {
            echo "✗ Error inserting challenge: " . $stmt->error . "\n";
        }
    }
} else {
    echo "\n--- Defis table already has data, skipping sample insertion ---\n";
}

// Create participations table
echo "\n--- Creating participations table ---\n";
$sql = "CREATE TABLE IF NOT EXISTS participations (
    id INT(11) NOT NULL AUTO_INCREMENT,
    id_utilisateur INT(11) NOT NULL,
    id_defi INT(11) NOT NULL,
    progression INT(11) DEFAULT 0,
    statut ENUM('in_progress','completed','failed') DEFAULT 'in_progress',
    date_debut DATE DEFAULT (CURRENT_DATE),
    date_fin DATE NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_participation_user_defi (id_utilisateur, id_defi),
    INDEX idx_id_utilisateur (id_utilisateur),
    INDEX idx_id_defi (id_defi),
    CONSTRAINT fk_participations_user FOREIGN KEY (id_utilisateur) REFERENCES `user`(id) ON DELETE CASCADE,
    CONSTRAINT fk_participations_defi FOREIGN KEY (id_defi) REFERENCES defis(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($mysqli->query($sql)) {
    echo "✓ Participations table created\n";
} else {
    echo "✗ Error creating participations table: " . $mysqli->error . "\n";
}

// Create participation proofs table
echo "\n--- Creating participation_proofs table ---\n";
$sql = "CREATE TABLE IF NOT EXISTS participation_proofs (
    id INT(11) NOT NULL AUTO_INCREMENT,
    participation_id INT(11) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    review_state ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_participation_id (participation_id),
    INDEX idx_review_state (review_state),
    CONSTRAINT fk_participation_proofs_participation FOREIGN KEY (participation_id) REFERENCES participations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($mysqli->query($sql)) {
    echo "✓ Participation proofs table created\n";
} else {
    echo "✗ Error creating participation proofs table: " . $mysqli->error . "\n";
}

// Create proof AI reviews table
echo "\n--- Creating proof_ai_reviews table ---\n";
$sql = "CREATE TABLE IF NOT EXISTS proof_ai_reviews (
    id INT(11) NOT NULL AUTO_INCREMENT,
    proof_id INT(11) NOT NULL,
    ai_decision ENUM('approved','rejected','uncertain','error') NOT NULL DEFAULT 'uncertain',
    ai_confidence TINYINT UNSIGNED NOT NULL DEFAULT 0,
    ai_progress_increment TINYINT UNSIGNED NOT NULL DEFAULT 0,
    ai_reason TEXT NOT NULL,
    ai_raw_response TEXT NULL,
    ai_reviewed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_proof_ai_review (proof_id),
    INDEX idx_ai_decision (ai_decision),
    CONSTRAINT fk_proof_ai_reviews_proof FOREIGN KEY (proof_id) REFERENCES participation_proofs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($mysqli->query($sql)) {
    echo "âœ“ Proof AI reviews table created\n";
} else {
    echo "âœ— Error creating proof AI reviews table: " . $mysqli->error . "\n";
}

// Show summary
echo "\n";
echo "==========================================\n";
echo "         DATABASE SETUP COMPLETE          \n";
echo "==========================================\n";
echo "\n";

// Count records
$users = $mysqli->query("SELECT COUNT(*) as count FROM `user`")->fetch_assoc()['count'];
$defis = $mysqli->query("SELECT COUNT(*) as count FROM defis")->fetch_assoc()['count'];
$participations = $mysqli->query("SELECT COUNT(*) as count FROM participations")->fetch_assoc()['count'];

echo "Summary:\n";
echo "  - Users: $users\n";
echo "  - Challenges (Defis): $defis\n";
echo "  - Participations: $participations\n";
echo "\n";

echo "Test User: ID=1, Name='Test User', Email='test@example.com'\n";
echo "\n";

echo "Next steps:\n";
echo "1. Go to front-office: http://localhost/-Esprit-PW-2A24-2026-Stabilis-user-version-finale/font-office/\n";
echo "2. Click on a challenge and fill the participation form\n";
echo "3. Check back-office: http://localhost/-Esprit-PW-2A24-2026-Stabilis-user-version-finale/back-office/\n";
echo "\n";

echo "⚠️  SECURITY WARNING: DELETE THIS FILE AFTER RUNNING! ⚠️\n";
echo "</pre>";

$mysqli->close();
?>
