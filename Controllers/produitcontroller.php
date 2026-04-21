<?php

require_once __DIR__ . '/../models/Produit.php';
require_once __DIR__ . '/../config/database.php';

class ProduitController {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getConnection();
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

    public function getCategories() {
        $stmt = $this->pdo->query('SELECT DISTINCT categorie FROM produits ORDER BY categorie ASC');
        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'categorie');
    }

    public function getAll($search = '', $categorie = '') {
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

        $sql = 'SELECT * FROM produits';
        if (!empty($filters)) {
            $sql .= ' WHERE ' . implode(' AND ', $filters);
        }
        $sql .= ' ORDER BY id DESC';

        if (empty($params)) {
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function add($produit) {
        $stmt = $this->pdo->prepare('INSERT INTO produits (nom, prix, description, stock, categorie, image_url) VALUES (?, ?, ?, ?, ?, ?)');
        return $stmt->execute([$produit->nom, $produit->prix, $produit->description, $produit->stock, $produit->categorie, $produit->image_url]);
    }

    public function update($id, $produit) {
        $stmt = $this->pdo->prepare('UPDATE produits SET nom = ?, prix = ?, description = ?, stock = ?, categorie = ?, image_url = ? WHERE id = ?');
        return $stmt->execute([$produit->nom, $produit->prix, $produit->description, $produit->stock, $produit->categorie, $produit->image_url, $id]);
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
        $stock = $data['stock'] ?? null;
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

        if ($stock === '' || $stock === null) {
            $errors['stock'] = 'Le stock est requis.';
        } elseif (filter_var($stock, FILTER_VALIDATE_INT) === false || intval($stock) < 0) {
            $errors['stock'] = 'Le stock doit etre un entier positif ou zero.';
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
