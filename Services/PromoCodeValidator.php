<?php

class PromoCodeValidator {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->ensurePromoCodeColumns();
    }

    private function ensurePromoCodeColumns() {
        try {
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS promo_codes (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    code VARCHAR(30) NOT NULL UNIQUE,
                    product_id INT NULL,
                    customer_email VARCHAR(255) NULL,
                    discount INT NOT NULL,
                    active TINYINT(1) NOT NULL DEFAULT 1,
                    usage_limit INT NOT NULL DEFAULT 1,
                    times_used INT NOT NULL DEFAULT 0,
                    expires_at DATETIME NOT NULL,
                    created_at DATETIME NOT NULL,
                    used TINYINT(1) NOT NULL DEFAULT 0,
                    used_date DATETIME NULL,
                    used_order_id INT NULL
                )
            ");

            $columns = [];
            $stmt = $this->pdo->query('SHOW COLUMNS FROM promo_codes');
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $column) {
                $columns[$column['Field']] = true;
            }

            if (!isset($columns['usage_limit'])) {
                $this->pdo->exec('ALTER TABLE promo_codes ADD usage_limit INT NOT NULL DEFAULT 1 AFTER discount');
            }
            if (!isset($columns['times_used'])) {
                $this->pdo->exec('ALTER TABLE promo_codes ADD times_used INT NOT NULL DEFAULT 0 AFTER usage_limit');
            }
            if (!isset($columns['active'])) {
                $this->pdo->exec('ALTER TABLE promo_codes ADD active TINYINT(1) NOT NULL DEFAULT 1 AFTER discount');
            }

            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS promo_code_usages (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    promo_code_id INT NOT NULL,
                    customer_email VARCHAR(255) NOT NULL,
                    order_id INT NULL,
                    used_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                )
            ");

            $this->pdo->exec('ALTER TABLE promo_codes MODIFY product_id INT NULL');
            $this->pdo->exec('ALTER TABLE promo_codes MODIFY customer_email VARCHAR(255) NULL');
        } catch (Exception $e) {
            error_log('Promo code schema check failed: ' . $e->getMessage());
        }
    }

    public function savePromoCode($code, $productId, $customerEmail, $discount, $expiresAt) {
        $sql = "
            INSERT INTO promo_codes (code, product_id, customer_email, discount, active, usage_limit, times_used, expires_at, created_at, used, used_date)
            VALUES (?, ?, ?, ?, 1, 1, 0, ?, NOW(), 0, NULL)
        ";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$code, $productId, $customerEmail, $discount, $expiresAt]);
    }

    public function saveManualPromoCode($code, $productId, $discount, $expiresAt, $usageLimit = 1) {
        $productId = $productId > 0 ? $productId : null;
        $usageLimit = max(1, (int)$usageLimit);
        $sql = "
            INSERT INTO promo_codes (code, product_id, customer_email, discount, active, usage_limit, times_used, expires_at, created_at, used, used_date)
            VALUES (?, ?, NULL, ?, 1, ?, 0, ?, NOW(), 0, NULL)
        ";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$code, $productId, $discount, $usageLimit, $expiresAt]);
    }

    public function validatePromoCode($code, $productId, $customerEmail) {
        $sql = "
            SELECT *
            FROM promo_codes
            WHERE code = ?
            AND (product_id = ? OR product_id IS NULL OR product_id = 0)
            AND (customer_email = ? OR customer_email IS NULL OR customer_email = '')
            ORDER BY
                CASE WHEN customer_email = ? THEN 0 ELSE 1 END,
                CASE WHEN product_id = ? THEN 0 ELSE 1 END,
                created_at DESC
            LIMIT 1
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$code, $productId, $customerEmail, $customerEmail, $productId]);
        $promo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$promo) {
            return [
                'valid' => false,
                'message' => 'Code promo invalide ou non applicable pour ce produit.',
                'discount' => null
            ];
        }

        if ((int)($promo['active'] ?? 1) !== 1) {
            return [
                'valid' => false,
                'message' => 'Ce code promo est inactif.',
                'discount' => null
            ];
        }

        $usageLimit = (int)($promo['usage_limit'] ?? 1);
        $timesUsed = (int)($promo['times_used'] ?? 0);

        if ((int)$promo['used'] === 1 || ($usageLimit > 0 && $timesUsed >= $usageLimit)) {
            return [
                'valid' => false,
                'message' => 'Ce code promo a déjà été utilisé.',
                'discount' => null
            ];
        }

        if ($promo['expires_at'] < date('Y-m-d H:i:s')) {
            return [
                'valid' => false,
                'message' => 'Ce code promo a expiré.',
                'discount' => null
            ];
        }

        $usageStmt = $this->pdo->prepare('SELECT id FROM promo_code_usages WHERE promo_code_id = ? AND customer_email = ? LIMIT 1');
        $usageStmt->execute([(int)$promo['id'], trim($customerEmail)]);
        if ($usageStmt->fetch()) {
            return [
                'valid' => false,
                'message' => 'Vous avez deja utilise ce code promo.',
                'discount' => null
            ];
        }

        return [
            'valid' => true,
            'message' => 'Code promo appliqué avec succès!',
            'discount' => (int)$promo['discount'],
            'code_id' => (int)$promo['id'],
            'product_id' => $promo['product_id'] !== null ? (int)$promo['product_id'] : null
        ];
    }

    public function markCodeAsUsed($codeId, $orderId = null, $customerEmail = '') {
        $promo = $this->getPromoCodeById($codeId);
        if (!$promo) {
            return false;
        }

        $nextUsed = ((int)($promo['times_used'] ?? 0) + 1) >= (int)($promo['usage_limit'] ?? 1) ? 1 : 0;
        $sql = "UPDATE promo_codes SET used = ?, times_used = times_used + 1, used_date = NOW(), used_order_id = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $updated = $stmt->execute([$nextUsed, $orderId, $codeId]);

        if ($updated && trim($customerEmail) !== '') {
            $usageStmt = $this->pdo->prepare('INSERT INTO promo_code_usages (promo_code_id, customer_email, order_id, used_at) VALUES (?, ?, ?, NOW())');
            $usageStmt->execute([$codeId, trim($customerEmail), $orderId]);
        }

        return $updated;
    }

    public function getPromoCodeById($id) {
        $sql = "SELECT * FROM promo_codes WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getPromoCodeInfo($code) {
        $sql = "SELECT * FROM promo_codes WHERE code = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$code]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
