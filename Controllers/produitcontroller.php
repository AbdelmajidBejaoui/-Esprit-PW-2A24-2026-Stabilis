<?php

require_once __DIR__ . '/../models/Produit.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Services/MailService.php';
require_once __DIR__ . '/../Services/PromoEmailService.php';

class ProduitController {
    private $pdo;
    private $mailService;

    public function __construct($mailConfig = null) {
        $this->pdo = Database::getConnection();
        $this->ensurePromotionColumns();
        $this->ensurePreOrderColumns();
        if ($mailConfig === null) {
            $mailConfig = require __DIR__ . '/../config/mail.php';
        }
        $this->mailService = new MailService($mailConfig);
    }

    private function ensurePromotionColumns() {
        try {
            $stmt = $this->pdo->query("SHOW COLUMNS FROM produits LIKE 'promo_prix'");
            if (!$stmt->fetch()) {
                $this->pdo->exec('ALTER TABLE produits ADD promo_prix DECIMAL(10,2) NULL DEFAULT NULL AFTER prix');
            }
        } catch (Exception $e) {
            error_log('Promotion column check failed: ' . $e->getMessage());
        }
    }

    private function ensurePreOrderColumns() {
        try {
            $stmt = $this->pdo->query("SHOW COLUMNS FROM produits LIKE 'coming_soon'");
            if (!$stmt->fetch()) {
                $this->pdo->exec('ALTER TABLE produits ADD coming_soon TINYINT(1) NOT NULL DEFAULT 0 AFTER stock');
            }
        } catch (Exception $e) {
            error_log('Pre-order column check failed: ' . $e->getMessage());
        }
    }

    public function getEffectivePrice(array $product) {
        $price = (float)($product['prix'] ?? 0);
        $promoPrice = $product['promo_prix'] ?? null;
        if ($promoPrice !== null && $promoPrice !== '' && (float)$promoPrice > 0 && (float)$promoPrice < $price) {
            return (float)$promoPrice;
        }
        return $price;
    }

    public function hasProductPromotion(array $product) {
        return $this->getEffectivePrice($product) < (float)($product['prix'] ?? 0);
    }

    public function canPreOrder(array $product) {
        return (int)($product['coming_soon'] ?? 0) === 1 || (int)($product['stock'] ?? 0) <= 0;
    }

    public function getById($id) {
        $stmt = $this->pdo->prepare('SELECT * FROM produits WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getByIds(array $ids) {
        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare("SELECT * FROM produits WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAvailableProductsForRecommendations($currentProductId, $limit = 30) {
        $stmt = $this->pdo->prepare('SELECT id, nom, categorie, prix, promo_prix, description, stock, coming_soon, image_url FROM produits WHERE id <> ? ORDER BY categorie ASC, id DESC LIMIT ?');
        $stmt->bindValue(1, (int)$currentProductId, PDO::PARAM_INT);
        $stmt->bindValue(2, (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getFallbackRecommendations(array $currentProduct, array $availableProducts, $limit = 4) {
        $currentId = (int)($currentProduct['id'] ?? 0);
        $currentCategory = strtolower(trim((string)($currentProduct['categorie'] ?? '')));
        $limit = max(3, min(4, (int)$limit));

        $sameCategory = [];
        $otherProducts = [];

        foreach ($availableProducts as $product) {
            if ((int)($product['id'] ?? 0) === $currentId) {
                continue;
            }

            if ($currentCategory !== '' && strtolower(trim((string)($product['categorie'] ?? ''))) === $currentCategory) {
                $sameCategory[] = $product;
            } else {
                $otherProducts[] = $product;
            }
        }

        return array_slice(array_merge($sameCategory, $otherProducts), 0, $limit);
    }

    public function getCategories() {
        $stmt = $this->pdo->query('SELECT DISTINCT categorie FROM produits ORDER BY categorie ASC');
        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'categorie');
    }

    public function getAll($search = '', $categorie = '', $sort = 'recent') {
        $filters = [];
        $params = [];

        if (trim($search) !== '') {
            $term = '%' . trim($search) . '%';
            $filters[] = '(nom LIKE ? OR categorie LIKE ?)';
            $params[] = $term;
            $params[] = $term;
        }

        if (trim($categorie) !== '') {
            $filters[] = 'categorie = ?';
            $params[] = trim($categorie);
        }

        $sortOptions = [
            'recent' => 'id DESC',
            'name_asc' => 'nom ASC',
            'name_desc' => 'nom DESC',
            'price_asc' => 'COALESCE(NULLIF(promo_prix, 0), prix) ASC',
            'price_desc' => 'COALESCE(NULLIF(promo_prix, 0), prix) DESC',
            'stock_asc' => 'stock ASC',
            'stock_desc' => 'stock DESC',
            'category_asc' => 'categorie ASC, nom ASC'
        ];

        $orderBy = $sortOptions[$sort] ?? $sortOptions['recent'];

        $sql = 'SELECT * FROM produits';
        if (!empty($filters)) {
            $sql .= ' WHERE ' . implode(' AND ', $filters);
        }
        $sql .= ' ORDER BY ' . $orderBy;

        if (empty($params)) {
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLowStockProducts($threshold = 10) {
        $stmt = $this->pdo->prepare('SELECT * FROM produits WHERE stock <= ? ORDER BY stock ASC');
        $stmt->execute([$threshold]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getLowStockTrackerPath() {
        $storageDir = __DIR__ . '/../storage';
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0755, true);
        }
        return $storageDir . '/low_stock_alerts.json';
    }

    private function loadLowStockTracker() {
        $path = $this->getLowStockTrackerPath();
        if (!file_exists($path)) {
            return [];
        }
        $contents = file_get_contents($path);
        if ($contents === false) {
            return [];
        }
        $data = json_decode($contents, true);
        return is_array($data) ? $data : [];
    }

    private function saveLowStockTracker(array $tracker) {
        file_put_contents($this->getLowStockTrackerPath(), json_encode($tracker, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public function sendLowStockAlert($recipient, $threshold = 10, $fromEmail = 'stabilisatyourservice@gmail.com', $fromName = 'Stabilis Backoffice') {
        $products = $this->getLowStockProducts($threshold);
        if (empty($products)) {
            return false;
        }

        $tracker = $this->loadLowStockTracker();
        $toNotify = [];
        foreach ($products as $produit) {
            $productId = (int) $produit['id'];
            $stock = (int) $produit['stock'];
            if (!isset($tracker[$productId]) || $tracker[$productId] !== $stock) {
                $toNotify[] = $produit;
            }
        }

        if (empty($toNotify)) {
            return false;
        }

        $subject = 'Alerte stock bas - Stabilis';
        $body = "Bonjour,\n\nLes produits suivants ont un stock faible (<= {$threshold}) :\n\n";
        foreach ($toNotify as $produit) {
            $body .= sprintf("- %s : %s unités\n", $produit['nom'], $produit['stock']);
        }
        $body .= "\nMerci de réapprovisionner ces références le plus rapidement possible.\n";

        $sent = $this->mailService->send($recipient, $subject, $body, $fromEmail, $fromName);
        
        if ($sent) {
            foreach ($toNotify as $produit) {
                $tracker[(int) $produit['id']] = (int) $produit['stock'];
            }
            $this->saveLowStockTracker($tracker);
        }

        return $sent;
    }

    public function sendTestEmail($recipient, $fromEmail = 'stabilisatyourservice@gmail.com', $fromName = 'Stabilis Backoffice') {
        $subject = '🧪 Test Email - Stabilis Backoffice';
        $body = "Bonjour,\n\nCeci est un email de test envoyé depuis le tableau de bord Stabilis.\n\n";
        $body .= "Détails du système :\n";
        $body .= "- Date/Heure : " . date('d/m/Y H:i:s') . "\n";
        $body .= "- Serveur : " . $_SERVER['SERVER_NAME'] . "\n";
        $body .= "- PHP Version : " . phpversion() . "\n";
        $body .= "\nSi vous recevez cet email, le système d'alertes fonctionne correctement ✓\n";

        return $this->mailService->send($recipient, $subject, $body, $fromEmail, $fromName);
    }

    public function add($produit, array $promoOptions = []) {
        $stmt = $this->pdo->prepare('INSERT INTO produits (nom, prix, promo_prix, description, stock, coming_soon, categorie, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $inserted = $stmt->execute([$produit->nom, $produit->prix, $produit->promo_prix, $produit->description, $produit->stock, $produit->coming_soon, $produit->categorie, $produit->image_url]);
        
        if ($inserted) {
            $productId = $this->pdo->lastInsertId();
            $mailMode = $promoOptions['mode'] ?? 'promo';
            $audience = $promoOptions['audience'] ?? 'all';

            try {
                $config = require __DIR__ . '/../config/mail.php';
                $geminiApiKey = $config['gemini_api_key'] ?? getenv('GEMINI_API_KEY');
                
                if ($mailMode !== 'none' && $geminiApiKey !== 'YOUR_GEMINI_API_KEY_HERE' && $geminiApiKey) {
                    $promoService = new PromoEmailService($this->pdo, $geminiApiKey, $config);
                    if ($mailMode === 'announcement') {
                        $result = $promoService->sendAnnouncementToCustomers($produit->nom, $produit->categorie, $produit->description, $audience);
                    } else {
                        $result = $promoService->generateAndSendPromo(
                            $productId,
                            $produit->nom,
                            $produit->categorie,
                            $produit->prix,
                            $produit->description,
                            $audience
                        );
                    }
                    error_log("Promo generated for product $productId: " . json_encode($result));
                }
            } catch (Exception $e) {
                error_log("Failed to generate promo: " . $e->getMessage());
            }
        }
        
        return $inserted;
    }

    public function update($id, $produit) {
        $stmt = $this->pdo->prepare('UPDATE produits SET nom = ?, prix = ?, promo_prix = ?, description = ?, stock = ?, coming_soon = ?, categorie = ?, image_url = ? WHERE id = ?');
        return $stmt->execute([$produit->nom, $produit->prix, $produit->promo_prix, $produit->description, $produit->stock, $produit->coming_soon, $produit->categorie, $produit->image_url, $id]);
    }

    public function decrementStock($id, $quantity) {
        $quantity = max(1, (int)$quantity);
        $stmt = $this->pdo->prepare('UPDATE produits SET stock = stock - ? WHERE id = ? AND stock >= ?');
        $stmt->execute([$quantity, (int)$id, $quantity]);
        return $stmt->rowCount() > 0;
    }

    public function delete($id) {
        $stmt = $this->pdo->prepare('DELETE FROM produits WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public function validateData(array $data, array &$errors) {
        $errors = [];

        $nom = trim($data['nom'] ?? '');
        $description = trim($data['description'] ?? '');
        $prix = $data['prix'] ?? null;
        $promoPrix = $data['promo_prix'] ?? null;
        $stock = $data['stock'] ?? null;
        $comingSoon = isset($data['coming_soon']) ? (int)$data['coming_soon'] : 0;
        $categorie = trim($data['categorie'] ?? '');

        if ($nom === '') {
            $errors['nom'] = 'Le nom est requis.';
        } elseif (strlen($nom) < 3) {
            $errors['nom'] = 'Le nom doit contenir au moins 3 caracteres.';
        }

        if ($prix === '' || $prix === null) {
            $errors['prix'] = 'Le prix est requis.';
        } elseif (!is_numeric($prix) || floatval($prix) <= 0) {
            $errors['prix'] = 'Le prix doit etre un nombre positif.';
        }

        if ($promoPrix !== '' && $promoPrix !== null) {
            if (!is_numeric($promoPrix) || floatval($promoPrix) <= 0) {
                $errors['promo_prix'] = 'Le prix promo doit etre un nombre positif.';
            } elseif (is_numeric($prix) && floatval($promoPrix) >= floatval($prix)) {
                $errors['promo_prix'] = 'Le prix promo doit etre inferieur au prix actuel.';
            }
        }

        if ($stock === '' || $stock === null) {
            $errors['stock'] = 'Le stock est requis.';
        } elseif (filter_var($stock, FILTER_VALIDATE_INT) === false || intval($stock) < 0) {
            $errors['stock'] = 'Le stock doit etre un entier positif ou zero.';
        }

        if (!in_array($comingSoon, [0, 1], true)) {
            $errors['coming_soon'] = 'La valeur coming soon est invalide.';
        }

        if ($categorie === '') {
            $errors['categorie'] = 'La categorie est requise.';
        }

        if ($description !== '' && strlen($description) < 10) {
            $errors['description'] = 'La description doit contenir au moins 10 caracteres si elle est renseignee.';
        }

        return empty($errors);
    }

    public function saveImage(array $file) {
        if (empty($file['name']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }

        $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!array_key_exists($file['type'], $allowedTypes)) {
            return false;
        }

        $extension = $allowedTypes[$file['type']];
        $filename = uniqid('prod_', true) . '.' . $extension;
        $targetDir = __DIR__ . '/../dist/img';

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $destination = $targetDir . '/' . $filename;
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return $filename;
        }

        return false;
    }
}
