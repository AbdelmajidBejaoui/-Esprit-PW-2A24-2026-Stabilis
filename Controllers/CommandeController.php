<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Commande.php';

class CommandeController {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getConnection();
    }

    public function getAll($search = '') {
        $sql = 'SELECT c.*, p.nom AS produit_nom, p.image_url FROM commandes c JOIN produits p ON p.id = c.produit_id';
        $params = [];

        if (trim($search) !== '') {
            $sql .= ' WHERE c.prenom LIKE ? OR c.nom LIKE ? OR c.email LIKE ? OR c.statut LIKE ?';
            $term = '%' . trim($search) . '%';
            $params = [$term, $term, $term, $term];
        }

        $sql .= ' ORDER BY c.date_commande DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllGroupedForBackoffice($search = '') {
        $sql = "
            SELECT
                MIN(c.id) AS id,
                c.prenom,
                c.nom,
                c.email,
                c.telephone,
                c.adresse,
                c.code_postal,
                c.ville,
                c.pays,
                MAX(c.date_commande) AS date_commande,
                SUM(c.total) AS total,
                SUM(c.quantite) AS quantite_totale,
                CASE
                    WHEN SUM(CASE WHEN c.statut = 'En attente' THEN 1 ELSE 0 END) > 0 THEN 'En attente'
                    WHEN SUM(CASE WHEN c.statut = 'Expédiée' THEN 1 ELSE 0 END) > 0 THEN 'Expédiée'
                    WHEN SUM(CASE WHEN c.statut = 'Validée' THEN 1 ELSE 0 END) > 0 THEN 'Validée'
                    WHEN SUM(CASE WHEN c.statut = 'Annulée' THEN 1 ELSE 0 END) > 0 THEN 'Annulée'
                    ELSE 'En attente'
                END AS statut,
                GROUP_CONCAT(CONCAT(p.nom, ' x', c.quantite) ORDER BY c.id SEPARATOR ', ') AS produits_resume
            FROM commandes c
            JOIN produits p ON p.id = c.produit_id
        ";

        $params = [];
        if (trim($search) !== '') {
            $sql .= "
                WHERE
                    c.prenom LIKE ?
                    OR c.nom LIKE ?
                    OR c.email LIKE ?
                    OR c.statut LIKE ?
                    OR p.nom LIKE ?
            ";
            $term = '%' . trim($search) . '%';
            $params = [$term, $term, $term, $term, $term];
        }

        $sql .= "
            GROUP BY
                c.prenom, c.nom, c.email, c.telephone, c.adresse, c.code_postal, c.ville, c.pays
            ORDER BY
                MAX(c.date_commande) DESC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $stmt = $this->pdo->prepare('SELECT c.*, p.nom AS produit_nom, p.prix AS produit_prix, p.image_url FROM commandes c JOIN produits p ON p.id = c.produit_id WHERE c.id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function add(Commande $commande) {
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO commandes (produit_id, prenom, nom, email, telephone, adresse, code_postal, ville, pays, notes, paiement, statut, quantite, total)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );

            $result = $stmt->execute([
                $commande->produit_id,
                $commande->prenom,
                $commande->nom,
                $commande->email,
                $commande->telephone,
                $commande->adresse,
                $commande->code_postal,
                $commande->ville,
                $commande->pays,
                $commande->notes,
                $commande->paiement,
                $commande->statut,
                $commande->quantite,
                $commande->total,
            ]);

            return $result ? $this->pdo->lastInsertId() : false;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function getGroupedOrderByIdForBackoffice($id) {
        $identitySql = '
            SELECT prenom, nom, email, telephone, adresse, code_postal, ville, pays
            FROM commandes
            WHERE id = ?
            LIMIT 1
        ';
        $identityStmt = $this->pdo->prepare($identitySql);
        $identityStmt->execute([$id]);
        $identity = $identityStmt->fetch(PDO::FETCH_ASSOC);

        if (!$identity) {
            return false;
        }

        $sql = "
            SELECT
                MIN(c.id) AS id,
                c.prenom,
                c.nom,
                c.email,
                c.telephone,
                c.adresse,
                c.code_postal,
                c.ville,
                c.pays,
                MAX(c.date_commande) AS date_commande,
                SUM(c.total) AS total,
                SUM(c.quantite) AS quantite_totale,
                TRIM(GROUP_CONCAT(NULLIF(TRIM(c.notes), '') SEPARATOR '\n')) AS notes,
                CASE
                    WHEN SUM(CASE WHEN c.statut = 'En attente' THEN 1 ELSE 0 END) > 0 THEN 'En attente'
                    WHEN SUM(CASE WHEN c.statut = 'Expédiée' THEN 1 ELSE 0 END) > 0 THEN 'Expédiée'
                    WHEN SUM(CASE WHEN c.statut = 'Validée' THEN 1 ELSE 0 END) > 0 THEN 'Validée'
                    WHEN SUM(CASE WHEN c.statut = 'Annulée' THEN 1 ELSE 0 END) > 0 THEN 'Annulée'
                    ELSE 'En attente'
                END AS statut
            FROM commandes c
            WHERE
                c.prenom = ?
                AND c.nom = ?
                AND c.email = ?
                AND c.telephone = ?
                AND c.adresse = ?
                AND c.code_postal = ?
                AND c.ville = ?
                AND c.pays = ?
            GROUP BY
                c.prenom, c.nom, c.email, c.telephone, c.adresse, c.code_postal, c.ville, c.pays
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $identity['prenom'],
            $identity['nom'],
            $identity['email'],
            $identity['telephone'],
            $identity['adresse'],
            $identity['code_postal'],
            $identity['ville'],
            $identity['pays']
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);

    public function getOrderLinesForGroupById($id) {
        $identitySql = '
            SELECT prenom, nom, email, telephone, adresse, code_postal, ville, pays
            FROM commandes
            WHERE id = ?
            LIMIT 1
        ';
        $identityStmt = $this->pdo->prepare($identitySql);
        $identityStmt->execute([$id]);
        $identity = $identityStmt->fetch(PDO::FETCH_ASSOC);

        if (!$identity) {
            return [];
        }

        $sql = '
            SELECT
                c.id,
                c.quantite,
                c.total,
                c.statut,
                c.date_commande,
                p.nom AS produit_nom,
                p.prix AS produit_prix,
                p.image_url
            FROM commandes c
            JOIN produits p ON p.id = c.produit_id
            WHERE
                c.prenom = ?
                AND c.nom = ?
                AND c.email = ?
                AND c.telephone = ?
                AND c.adresse = ?
                AND c.code_postal = ?
                AND c.ville = ?
                AND c.pays = ?
            ORDER BY c.id ASC
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $identity['prenom'],
            $identity['nom'],
            $identity['email'],
            $identity['telephone'],
            $identity['adresse'],
            $identity['code_postal'],
            $identity['ville'],
            $identity['pays']
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStatusForGroupById($id, $statut) {
        $lignes = $this->getOrderLinesForGroupById($id);

        if (empty($lignes)) {
            return false;
        }

        $ids = array_map(function ($ligne) {
            return (int) $ligne['id'];
        }, $lignes);

        $ids = array_values(array_unique(array_filter($ids, function ($value) {
            return $value > 0;
        })));

        if (empty($ids)) {
            return false;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $sql = "UPDATE commandes SET statut = ? WHERE id IN ($placeholders)";
        $stmt = $this->pdo->prepare($sql);

        $params = array_merge([$statut], $ids);

        if (!$stmt->execute($params)) {
            return false;
        }

        return $stmt->rowCount() > 0;
    }

    public function updateStatus($id, $statut) {
        $stmt = $this->pdo->prepare('UPDATE commandes SET statut = ? WHERE id = ?');
        return $stmt->execute([$statut, $id]);
    }

    public function delete($id) {
        $stmt = $this->pdo->prepare('DELETE FROM commandes WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public function validateData(array $data, array &$errors) {
        $errors = [];

        $prenom = trim($data['prenom'] ?? '');
        $nom = trim($data['nom'] ?? '');
        $email = trim($data['email'] ?? '');
        $telephone = trim($data['telephone'] ?? '');
        $adresse = trim($data['adresse'] ?? '');
        $code_postal = trim($data['code_postal'] ?? '');
        $ville = trim($data['ville'] ?? '');
        $pays = trim($data['pays'] ?? '');
        $paiement = trim($data['paiement'] ?? '');

        if ($prenom === '') {
            $errors['prenom'] = 'Le prenom est requis.';
        } elseif (strlen($prenom) < 2) {
            $errors['prenom'] = 'Le prenom doit contenir au moins 2 caracteres.';
        } elseif (!preg_match('/^[\p{L}\s\-\']+$/u', $prenom)) {
            $errors['prenom'] = 'Le prenom ne peut contenir que des lettres.';
        }

        if ($nom === '') {
            $errors['nom'] = 'Le nom est requis.';
        } elseif (strlen($nom) < 2) {
            $errors['nom'] = 'Le nom doit contenir au moins 2 caracteres.';
        } elseif (!preg_match('/^[\p{L}\s\-\']+$/u', $nom)) {
            $errors['nom'] = 'Le nom ne peut contenir que des lettres.';
        }

        if ($email === '') {
            $errors['email'] = 'L\'email est requis.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'L\'email n\'est pas valide.';
        }

        if ($telephone === '') {
            $errors['telephone'] = 'Le telephone est requis.';
        } elseif (!preg_match('/^[0-9+\s\-\.()]+$/', $telephone)) {
            $errors['telephone'] = 'Le telephone contient des caracteres invalides.';
        } elseif (strlen(preg_replace('/[^0-9]/', '', $telephone)) < 8) {
            $errors['telephone'] = 'Le telephone doit contenir au moins 8 chiffres.';
        } elseif (strlen(preg_replace('/[^0-9]/', '', $telephone)) > 15) {
            $errors['telephone'] = 'Le telephone ne peut pas depasser 15 chiffres.';
        }

        if ($adresse === '') {
            $errors['adresse'] = 'L\'adresse est requise.';
        } elseif (strlen($adresse) < 5) {
            $errors['adresse'] = 'L\'adresse doit contenir au moins 5 caracteres.';
        }

        if ($code_postal === '') {
            $errors['code_postal'] = 'Le code postal est requis.';
        } elseif (!preg_match('/^[0-9A-Za-z\s\-]+$/', $code_postal)) {
            $errors['code_postal'] = 'Le code postal est invalide.';
        }

        if ($ville === '') {
            $errors['ville'] = 'La ville est requise.';
        } elseif (strlen($ville) < 2) {
            $errors['ville'] = 'La ville doit contenir au moins 2 caracteres.';
        } elseif (!preg_match('/^[\p{L}\s\-\']+$/u', $ville)) {
            $errors['ville'] = 'La ville ne peut contenir que des lettres.';
        }

        if ($pays === '') {
            $errors['pays'] = 'Le pays est requis.';
        }

        $allowedPayments = ['card', 'paypal', 'cash'];
        if ($paiement === '' || !in_array($paiement, $allowedPayments, true)) {
            $errors['paiement'] = 'Le mode de paiement selectionne n\'est pas valide.';
        }

        return empty($errors);
    }
}
