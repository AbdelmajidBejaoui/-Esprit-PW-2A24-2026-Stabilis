<?php

require_once __DIR__ . '/../config/database.php';

class EventController {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getConnection();
        $this->ensureTable();
    }

    private function ensureTable() {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS site_events (
                id INT AUTO_INCREMENT PRIMARY KEY,
                titre VARCHAR(160) NOT NULL,
                message TEXT NOT NULL,
                code_promo VARCHAR(80) NULL,
                lien VARCHAR(255) NULL,
                bg_color VARCHAR(20) NOT NULL DEFAULT '#F9F3E6',
                active TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ");
        $this->ensureColumn('site_events', 'bg_color', "ALTER TABLE site_events ADD bg_color VARCHAR(20) NOT NULL DEFAULT '#F9F3E6' AFTER lien");
    }

    private function ensureColumn($table, $column, $alterSql) {
        try {
            $stmt = $this->pdo->query("SHOW COLUMNS FROM `$table` LIKE " . $this->pdo->quote($column));
            if (!$stmt->fetch()) {
                $this->pdo->exec($alterSql);
            }
        } catch (Exception $e) {
            error_log('Event column check failed: ' . $e->getMessage());
        }
    }

    public function getAll() {
        $stmt = $this->pdo->query('SELECT * FROM site_events ORDER BY active DESC, created_at DESC, id DESC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getActive() {
        $stmt = $this->pdo->query('SELECT * FROM site_events WHERE active = 1 ORDER BY created_at DESC, id DESC LIMIT 1');
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function add(array $data, array &$errors) {
        if (!$this->validate($data, $errors)) {
            return false;
        }

        if (!empty($data['active'])) {
            $this->pdo->exec('UPDATE site_events SET active = 0');
        }

        $bgColor = trim($data['bg_color'] ?? '#F9F3E6');
        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $bgColor)) {
            $errors['bg_color'] = 'Couleur invalide. Exemple: #1A4D3A.';
        }

        if (!empty($errors)) {
            return false;
        }

        $stmt = $this->pdo->prepare('INSERT INTO site_events (titre, message, code_promo, lien, bg_color, active) VALUES (?, ?, ?, ?, ?, ?)');
        return $stmt->execute([
            trim($data['titre']),
            trim($data['message']),
            trim($data['code_promo'] ?? ''),
            'index.php',
            $bgColor,
            !empty($data['active']) ? 1 : 0
        ]);
    }

    public function setActive($id, $active) {
        if ($active) {
            $this->pdo->exec('UPDATE site_events SET active = 0');
        }
        $stmt = $this->pdo->prepare('UPDATE site_events SET active = ? WHERE id = ?');
        return $stmt->execute([$active ? 1 : 0, (int)$id]);
    }

    public function delete($id) {
        $stmt = $this->pdo->prepare('DELETE FROM site_events WHERE id = ?');
        return $stmt->execute([(int)$id]);
    }

    private function validate(array $data, array &$errors) {
        $errors = [];
        $titre = trim($data['titre'] ?? '');
        $message = trim($data['message'] ?? '');

        if ($titre === '' || strlen($titre) < 3) {
            $errors['titre'] = 'Le titre doit contenir au moins 3 caracteres.';
        }
        if ($message === '' || strlen($message) < 8) {
            $errors['message'] = 'Le message doit contenir au moins 8 caracteres.';
        }
        return empty($errors);
    }
}
