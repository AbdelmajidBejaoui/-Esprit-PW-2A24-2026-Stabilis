<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Services/SimpleSmtpMailer.php';

class WishlistController {
    private $pdo;
    private $mailConfig;

    public function __construct($mailConfig = null) {
        $this->pdo = Database::getConnection();
        $this->mailConfig = $mailConfig ?: require __DIR__ . '/../config/mail.php';
        $this->ensureTable();
    }

    private function ensureTable() {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS wishlist_notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                produit_id INT NOT NULL,
                nom VARCHAR(120) NOT NULL,
                email VARCHAR(255) NOT NULL,
                notified TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                notified_at DATETIME NULL,
                UNIQUE KEY unique_product_email (produit_id, email)
            )
        ");
    }

    public function validateRequest(array $data, array &$errors) {
        $errors = [];
        $productId = filter_var($data['product_id'] ?? null, FILTER_VALIDATE_INT);
        $name = trim($data['name'] ?? '');
        $email = trim($data['email'] ?? '');

        if (!$productId || $productId <= 0) {
            $errors['product_id'] = 'Produit invalide.';
        }

        if ($name === '') {
            $errors['name'] = 'Le nom est requis.';
        } elseif (strlen($name) < 2 || !preg_match("/^[\\p{L}\\s'\\-]+$/u", $name)) {
            $errors['name'] = 'Nom invalide.';
        }

        if ($email === '') {
            $errors['email'] = 'L email est requis.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email invalide.';
        }

        return empty($errors);
    }

    public function addRequest(array $data, array &$errors) {
        if (!$this->validateRequest($data, $errors)) {
            return 'invalid';
        }

        $stmt = $this->pdo->prepare('SELECT stock FROM produits WHERE id = ?');
        $stmt->execute([(int)$data['product_id']]);
        $product = $stmt->fetch();

        if (!$product) {
            $errors['product_id'] = 'Produit introuvable.';
            return 'invalid';
        }

        if ((int)$product['stock'] > 0) {
            return 'available';
        }

        try {
            $stmt = $this->pdo->prepare('
                INSERT INTO wishlist_notifications (produit_id, nom, email)
                VALUES (?, ?, ?)
            ');
            $stmt->execute([
                (int)$data['product_id'],
                trim($data['name']),
                strtolower(trim($data['email']))
            ]);
            return 'sent';
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                return 'exists';
            }
            error_log('Wishlist request failed: ' . $e->getMessage());
            return 'error';
        }
    }

    public function notifyProductAvailable(array $product) {
        if (empty($product['id']) || (int)($product['stock'] ?? 0) <= 0) {
            return ['sent' => 0, 'failed' => 0, 'total' => 0];
        }

        $stmt = $this->pdo->prepare('
            SELECT id, nom, email
            FROM wishlist_notifications
            WHERE produit_id = ? AND notified = 0
            ORDER BY created_at ASC
        ');
        $stmt->execute([(int)$product['id']]);
        $requests = $stmt->fetchAll();

        if (empty($requests)) {
            return ['sent' => 0, 'failed' => 0, 'total' => 0];
        }

        $smtp = $this->mailConfig['smtp'] ?? [];
        $mailer = new SimpleSmtpMailer(
            $smtp['host'] ?? 'smtp.gmail.com',
            (int)($smtp['port'] ?? 587),
            $smtp['username'] ?? '',
            $smtp['password'] ?? '',
            $smtp['secure'] ?? 'tls'
        );

        $sent = 0;
        $failed = 0;
        foreach ($requests as $request) {
            $ok = $mailer->send(
                $request['email'],
                'Votre produit Stabilis est de retour',
                $this->buildAvailableEmail($request['nom'], $product),
                $this->mailConfig['from_email'] ?? 'stabilisatyourservice@gmail.com',
                'Stabilis',
                true
            );

            if ($ok) {
                $mark = $this->pdo->prepare('UPDATE wishlist_notifications SET notified = 1, notified_at = NOW() WHERE id = ?');
                $mark->execute([(int)$request['id']]);
                $sent++;
            } else {
                $failed++;
            }
        }

        return ['sent' => $sent, 'failed' => $failed, 'total' => count($requests)];
    }

    private function buildAvailableEmail($name, array $product) {
        $productName = htmlspecialchars($product['nom'] ?? 'ce produit', ENT_QUOTES, 'UTF-8');
        $customerName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $description = trim($product['description'] ?? '');
        $description = $description !== ''
            ? htmlspecialchars($description, ENT_QUOTES, 'UTF-8')
            : 'Il est pret a rejoindre votre routine sportive et nutritionnelle Stabilis.';

        return '<!DOCTYPE html>
<html>
<body style="margin:0;padding:0;background:#f3faf5;font-family:Arial,sans-serif;color:#20352a;">
  <div style="max-width:620px;margin:0 auto;padding:28px 16px;">
    <div style="background:#ffffff;border:1px solid #d7eadf;border-radius:12px;overflow:hidden;">
      <div style="background:#1A4D3A;color:#ffffff;padding:22px 26px;">
        <h1 style="margin:0;font-size:24px;">Bonne nouvelle</h1>
      </div>
      <div style="padding:26px;">
        <p style="font-size:16px;line-height:1.6;margin:0 0 14px;">Bonjour ' . $customerName . ',</p>
        <p style="font-size:16px;line-height:1.6;margin:0 0 14px;"><strong>' . $productName . '</strong> est de nouveau disponible chez Stabilis.</p>
        <p style="font-size:15px;line-height:1.6;margin:0 0 20px;color:#4f6658;">' . $description . '</p>
        <a href="http://localhost/AdminLTE3/Views/front/product.php?id=' . (int)$product['id'] . '" style="display:inline-block;background:#1A4D3A;color:#ffffff;text-decoration:none;padding:12px 18px;border-radius:999px;font-weight:bold;">Voir le produit</a>
      </div>
    </div>
  </div>
</body>
</html>';
    }
}
