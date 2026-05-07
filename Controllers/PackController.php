<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Pack.php';

class PackController {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getConnection();
        $this->ensureTables();
    }

    private function ensureTables() {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS packs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nom VARCHAR(120) NOT NULL,
                description TEXT NULL,
                prix DECIMAL(10,2) NOT NULL,
                image_url VARCHAR(255) NULL,
                active TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $this->ensureColumn('packs', 'description', 'ALTER TABLE packs ADD description TEXT NULL AFTER nom');
        $this->ensureColumn('packs', 'image_url', 'ALTER TABLE packs ADD image_url VARCHAR(255) NULL AFTER prix');
        $this->ensureColumn('packs', 'active', 'ALTER TABLE packs ADD active TINYINT(1) NOT NULL DEFAULT 1 AFTER image_url');
        $this->ensureColumn('packs', 'created_at', 'ALTER TABLE packs ADD created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER active');

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS pack_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                pack_id INT NOT NULL,
                produit_id INT NOT NULL,
                quantite INT NOT NULL DEFAULT 1,
                FOREIGN KEY (pack_id) REFERENCES packs(id) ON DELETE CASCADE
            )
        ");
    }

    private function ensureColumn($table, $column, $alterSql) {
        try {
            $stmt = $this->pdo->query("SHOW COLUMNS FROM `$table` LIKE " . $this->pdo->quote($column));
            if (!$stmt->fetch()) {
                $this->pdo->exec($alterSql);
            }
        } catch (Exception $e) {
            error_log('Pack column check failed: ' . $e->getMessage());
        }
    }

    public function getAll($onlyActive = true, $search = '', $sort = 'recent') {
        $sortOptions = [
            'recent' => 'p.id DESC',
            'name_asc' => 'p.nom ASC',
            'name_desc' => 'p.nom DESC',
            'price_asc' => 'p.prix ASC',
            'price_desc' => 'p.prix DESC'
        ];
        $orderBy = $sortOptions[$sort] ?? $sortOptions['recent'];

        $sql = "
            SELECT p.*, COUNT(pi.id) AS produits_count
            FROM packs p
            LEFT JOIN pack_items pi ON pi.pack_id = p.id
        ";
        $filters = [];
        $params = [];
        if ($onlyActive) {
            $filters[] = 'p.active = 1';
        }
        if (trim($search) !== '') {
            $filters[] = '(p.nom LIKE ? OR p.description LIKE ?)';
            $term = '%' . trim($search) . '%';
            $params[] = $term;
            $params[] = $term;
        }
        if (!empty($filters)) {
            $sql .= ' WHERE ' . implode(' AND ', $filters);
        }
        $sql .= " GROUP BY p.id ORDER BY $orderBy";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function hydrateItems(array $packs) {
        foreach ($packs as &$pack) {
            $pack['items'] = $this->getItems((int)$pack['id']);
        }
        unset($pack);
        return $packs;
    }

    public function getById($id) {
        $stmt = $this->pdo->prepare('SELECT * FROM packs WHERE id = ?');
        $stmt->execute([(int)$id]);
        $pack = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$pack) {
            return false;
        }
        $pack['items'] = $this->getItems((int)$id);
        return $pack;
    }

    public function getByIds(array $ids) {
        $ids = array_values(array_filter(array_map('intval', $ids), function ($id) {
            return $id > 0;
        }));
        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare("SELECT * FROM packs WHERE id IN ($placeholders) AND active = 1");
        $stmt->execute($ids);
        $packs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($packs as &$pack) {
            $pack['items'] = $this->getItems((int)$pack['id']);
        }
        return $packs;
    }

    public function getItems($packId) {
        $stmt = $this->pdo->prepare("
            SELECT pi.produit_id, pi.quantite, pr.nom, pr.prix, pr.stock, pr.image_url
            FROM pack_items pi
            JOIN produits pr ON pr.id = pi.produit_id
            WHERE pi.pack_id = ?
            ORDER BY pr.nom ASC
        ");
        $stmt->execute([(int)$packId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function add(Pack $pack, array $items) {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare('INSERT INTO packs (nom, description, prix, image_url, active) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$pack->nom, $pack->description, $pack->prix, $pack->image_url, $pack->active]);
            $packId = (int)$this->pdo->lastInsertId();

            $itemStmt = $this->pdo->prepare('INSERT INTO pack_items (pack_id, produit_id, quantite) VALUES (?, ?, ?)');
            foreach ($items as $productId => $quantity) {
                $productId = (int)$productId;
                $quantity = max(1, (int)$quantity);
                if ($productId > 0) {
                    $itemStmt->execute([$packId, $productId, $quantity]);
                }
            }

            $this->pdo->commit();
            return $packId;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    public function update($id, Pack $pack, array $items, $keepCurrentImage = true) {
        $current = $this->getById($id);
        if (!$current) {
            return false;
        }

        $imageUrl = $pack->image_url;
        if ($keepCurrentImage && ($imageUrl === null || $imageUrl === '')) {
            $imageUrl = $current['image_url'] ?? null;
        }

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare('UPDATE packs SET nom = ?, description = ?, prix = ?, image_url = ?, active = ? WHERE id = ?');
            $stmt->execute([$pack->nom, $pack->description, $pack->prix, $imageUrl, $pack->active, (int)$id]);

            $deleteItems = $this->pdo->prepare('DELETE FROM pack_items WHERE pack_id = ?');
            $deleteItems->execute([(int)$id]);

            $itemStmt = $this->pdo->prepare('INSERT INTO pack_items (pack_id, produit_id, quantite) VALUES (?, ?, ?)');
            foreach ($items as $productId => $quantity) {
                $productId = (int)$productId;
                $quantity = max(1, (int)$quantity);
                if ($productId > 0) {
                    $itemStmt->execute([(int)$id, $productId, $quantity]);
                }
            }

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    public function delete($id) {
        $stmt = $this->pdo->prepare('DELETE FROM packs WHERE id = ?');
        return $stmt->execute([(int)$id]);
    }

    public function saveImage(array $file) {
        if (empty($file['name']) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            return false;
        }

        $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $mime = $file['type'] ?? '';
        if (!array_key_exists($mime, $allowedTypes)) {
            return false;
        }

        $targetDir = __DIR__ . '/../dist/img';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $filename = uniqid('pack_', true) . '.' . $allowedTypes[$mime];
        $destination = $targetDir . '/' . $filename;
        return move_uploaded_file($file['tmp_name'], $destination) ? $filename : false;
    }

    public function validateData(array $data, array $items, array &$errors) {
        $errors = [];
        $nom = trim($data['nom'] ?? '');
        $prix = $data['prix'] ?? '';
        $packStock = (int)($data['pack_stock'] ?? 0);

        if ($nom === '' || strlen($nom) < 3) {
            $errors['nom'] = 'Le nom du pack doit contenir au moins 3 caracteres.';
        }

        if ($prix === '' || !is_numeric($prix) || (float)$prix <= 0) {
            $errors['prix'] = 'Le prix du pack doit etre un nombre positif.';
        }

        if ($packStock < 1) {
            $errors['pack_stock'] = 'Le stock du pack doit etre au moins 1.';
        }

        $validItems = array_filter($items, function ($quantity, $productId) {
            return (int)$productId > 0 && (int)$quantity > 0;
        }, ARRAY_FILTER_USE_BOTH);

        if (count($validItems) < 2) {
            $errors['items'] = 'Selectionnez au moins deux produits pour creer un pack.';
        }

        if (!isset($errors['pack_stock']) && !empty($validItems)) {
            $placeholders = implode(',', array_fill(0, count($validItems), '?'));
            $stmt = $this->pdo->prepare("SELECT id, nom, stock FROM produits WHERE id IN ($placeholders)");
            $stmt->execute(array_keys($validItems));
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($products as $product) {
                if ((int)$product['stock'] < $packStock) {
                    $errors['items'] = 'Stock insuffisant pour ' . $product['nom'] . '. Chaque produit choisi doit avoir au moins ' . $packStock . ' unites.';
                    break;
                }
            }
        }

        return empty($errors);
    }

    public function canBuyPack(array $pack, $packQuantity = 1) {
        foreach ($pack['items'] ?? [] as $item) {
            if ((int)$item['stock'] < ((int)$item['quantite'] * (int)$packQuantity)) {
                return false;
            }
        }
        return !empty($pack['items']);
    }

    public function canPreOrderPack(array $pack) {
        return !empty($pack['items']) && !$this->canBuyPack($pack, 1);
    }

    public function getFirstProductId(array $pack) {
        $first = $pack['items'][0] ?? null;
        return $first ? (int)$first['produit_id'] : 0;
    }
}
