<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Commande.php';
require_once __DIR__ . '/../Services/MailService.php';
require_once __DIR__ . '/../Services/InvoiceService.php';
require_once __DIR__ . '/PackController.php';

class CommandeController {
    private $pdo;
    private $mailService;
    private $lastMailTransport = 'none';

    public function __construct() {
        $this->pdo = Database::getConnection();
        $this->mailService = new MailService(require __DIR__ . '/../config/mail.php');
    }

    public function getAll($search = '') {
        $sql = 'SELECT c.*, p.nom AS produit_nom, p.image_url FROM commandes c JOIN produits p ON p.id = c.produit_id';
        $params = [];

        if (trim($search) !== '') {
            $sql .= ' WHERE c.prenom LIKE ? OR c.nom LIKE ? OR c.email LIKE ? OR c.statut LIKE ?';
            $term = '%' . trim($search) . '%';
            $params = [$term, $term, $term, $term];
        }

        $sql .= " ORDER BY CASE WHEN c.statut = 'pre-order' OR c.notes LIKE '%PRIORITE PRE-COMMANDE%' THEN 0 ELSE 1 END ASC, c.date_commande DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllGroupedForBackoffice($search = '', $sort = 'recent') {
        $prioritySort = "CASE WHEN SUM(CASE WHEN c.statut = 'pre-order' OR c.notes LIKE '%PRIORITE PRE-COMMANDE%' THEN 1 ELSE 0 END) > 0 THEN 0 ELSE 1 END";
        $sortOptions = [
            'recent' => $prioritySort . ' ASC, MAX(c.date_commande) DESC',
            'oldest' => $prioritySort . ' ASC, MAX(c.date_commande) ASC',
            'client_asc' => 'c.nom ASC, c.prenom ASC',
            'client_desc' => 'c.nom DESC, c.prenom DESC',
            'total_asc' => 'final_total ASC',
            'total_desc' => 'final_total DESC',
            'status_asc' => 'statut ASC'
        ];
        $orderBy = $sortOptions[$sort] ?? $sortOptions['recent'];

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
                MAX(c.discount_percent) AS discount_percent,
                SUM(c.discount_amount) AS discount_amount,
                SUM(COALESCE(NULLIF(c.final_total, 0), c.total, 0)) AS final_total,
                SUM(c.quantite) AS quantite_totale,
                CASE
                    WHEN SUM(CASE WHEN c.statut = 'pre-order' THEN 1 ELSE 0 END) > 0 THEN 'pre-order'
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
                $orderBy
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
                'INSERT INTO commandes (produit_id, prenom, nom, email, telephone, adresse, code_postal, ville, pays, notes, paiement, statut, quantite, total, final_total)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
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
                MAX(c.discount_percent) AS discount_percent,
                SUM(c.discount_amount) AS discount_amount,
                SUM(COALESCE(NULLIF(c.final_total, 0), c.total, 0)) AS final_total,
                SUM(c.quantite) AS quantite_totale,
                TRIM(GROUP_CONCAT(NULLIF(TRIM(c.notes), '') SEPARATOR '\n')) AS notes,
                CASE
                    WHEN SUM(CASE WHEN c.statut = 'pre-order' THEN 1 ELSE 0 END) > 0 THEN 'pre-order'
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
    }

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

    public function getPreOrders($onlyReady = false) {
        $sql = "
            SELECT
                c.*,
                CASE
                    WHEN c.notes LIKE '%PACK_PREORDER_ID:%' THEN COALESCE(pk.nom, p.nom)
                    ELSE p.nom
                END AS produit_nom,
                pk.id AS pack_id,
                p.stock,
                p.coming_soon,
                p.image_url,
                CASE
                    WHEN c.notes LIKE '%PACK_PREORDER_ID:%' THEN 0
                    WHEN p.stock >= c.quantite AND COALESCE(p.coming_soon, 0) = 0 THEN 1
                    ELSE 0
                END AS is_ready
            FROM commandes c
            JOIN produits p ON p.id = c.produit_id
            LEFT JOIN packs pk ON pk.id = CAST(TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(c.notes, 'PACK_PREORDER_ID:', -1), '\n', 1)) AS UNSIGNED)
            WHERE c.statut = 'pre-order'
        ";

        $sql .= " ORDER BY c.date_commande ASC, c.id ASC";
        $orders = $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        foreach ($orders as &$order) {
            $order['is_ready'] = $this->canFulfillPreOrder($order) ? 1 : 0;
        }
        unset($order);

        if ($onlyReady) {
            $orders = array_values(array_filter($orders, function ($order) {
                return (int)($order['is_ready'] ?? 0) === 1;
            }));
        }

        usort($orders, function ($a, $b) {
            return ((int)$b['is_ready'] <=> (int)$a['is_ready'])
                ?: strcmp((string)$a['date_commande'], (string)$b['date_commande'])
                ?: ((int)$a['id'] <=> (int)$b['id']);
        });

        return $orders;
    }

    public function getPreOrderById($id) {
        $stmt = $this->pdo->prepare("
            SELECT
                c.*,
                CASE
                    WHEN c.notes LIKE '%PACK_PREORDER_ID:%' THEN COALESCE(pk.nom, p.nom)
                    ELSE p.nom
                END AS produit_nom,
                pk.id AS pack_id,
                p.stock,
                p.coming_soon
            FROM commandes c
            JOIN produits p ON p.id = c.produit_id
            LEFT JOIN packs pk ON pk.id = CAST(TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(c.notes, 'PACK_PREORDER_ID:', -1), '\n', 1)) AS UNSIGNED)
            WHERE c.id = ? AND c.statut = 'pre-order'
            LIMIT 1
        ");
        $stmt->execute([(int)$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createPreOrder(Commande $commande) {
        $commande->statut = 'pre-order';
        return $this->add($commande);
    }

    public function canFulfillPreOrder(array $preOrder) {
        $packId = $this->extractPackPreOrderId($preOrder['notes'] ?? '');
        if ($packId > 0) {
            $packController = new PackController();
            $pack = $packController->getById($packId);
            return $pack && $packController->canBuyPack($pack, (int)($preOrder['quantite'] ?? 1));
        }

        return (int)($preOrder['stock'] ?? 0) >= (int)($preOrder['quantite'] ?? 0)
            && (int)($preOrder['coming_soon'] ?? 0) === 0;
    }

    private function extractPackPreOrderId($notes) {
        if (preg_match('/PACK_PREORDER_ID:\s*(\d+)/', (string)$notes, $matches)) {
            return (int)$matches[1];
        }
        return 0;
    }

    public function sendPreOrderReadyEmail($id) {
        $preOrder = $this->getPreOrderById($id);
        if (!$preOrder || !$this->canFulfillPreOrder($preOrder)) {
            return false;
        }

        $subject = 'Votre pre-commande Stabilis est disponible';
        $body = $this->buildPreOrderReadyEmail($preOrder);

        $sent = $this->mailService->send($preOrder['email'], $subject, $body);
        $this->lastMailTransport = $this->mailService->getLastTransport();
        return $sent;
    }

    public function sendPreOrderInvoiceEmail(array $orderData, array $products) {
        $invoiceService = new InvoiceService($this->pdo);
        return $invoiceService->sendPreOrderInvoiceEmail($orderData, $products);
    }

    private function buildPreOrderReadyEmail(array $preOrder) {
        $name = htmlspecialchars(trim(($preOrder['prenom'] ?? '') . ' ' . ($preOrder['nom'] ?? '')));
        $product = htmlspecialchars($preOrder['produit_nom'] ?? 'votre produit');
        $orderId = htmlspecialchars((string)($preOrder['id'] ?? ''));

        return '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { margin:0; background:#F8F9F6; color:#2B2D2A; font-family:Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", Arial, sans-serif; }
        .email-container { max-width:640px; margin:20px auto; background:#fff; border:1px solid #EDEDE9; border-radius:16px; overflow:hidden; box-shadow:0 8px 30px rgba(26,77,58,0.08); }
        .header { background:#1A4D3A; color:#fff; padding:32px 30px; border-bottom:5px solid #C6A15B; }
        .header h1 { margin:0 0 8px; font-size:28px; }
        .header p { margin:0; opacity:.95; font-size:13px; }
        .content { padding:32px 30px; line-height:1.65; }
        .notice { background:#E8F0E9; border-left:4px solid #3A6B4B; border-radius:12px; padding:18px; margin:24px 0; }
        .notice strong { color:#1A4D3A; }
        .details { border:1px solid #EDEDE9; border-radius:12px; overflow:hidden; margin:22px 0; }
        .row { display:flex; justify-content:space-between; gap:16px; padding:12px 16px; border-bottom:1px solid #F0F0F0; font-size:14px; }
        .row:last-child { border-bottom:none; }
        .label { color:#6E6E68; font-weight:700; }
        .footer { background:#FCFCFA; color:#7A7A72; text-align:center; padding:24px 30px; border-top:1px solid #EDEDE9; font-size:12px; line-height:1.6; }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>Stabilis</h1>
            <p>Votre pre-commande est maintenant disponible</p>
        </div>
        <div class="content">
            <p>Bonjour <strong>' . $name . '</strong>,</p>
            <p>Bonne nouvelle: <strong>' . $product . '</strong> est disponible.</p>
            <div class="notice">
                <strong>Commande prioritaire</strong><br>
                Votre pre-commande sera traitee avant les commandes classiques. Nous allons preparer votre commande ou vous contacter si une confirmation est necessaire.
            </div>
            <div class="details">
                <div class="row"><span class="label">Pre-commande</span><span>#' . $orderId . '</span></div>
                <div class="row"><span class="label">Produit</span><span>' . $product . '</span></div>
                <div class="row"><span class="label">Quantite</span><span>' . (int)($preOrder['quantite'] ?? 1) . '</span></div>
            </div>
            <p>Merci pour votre patience,<br>L equipe Stabilis</p>
        </div>
        <div class="footer">
            <p>Des questions ? Contactez-nous: <strong>stabilisatyourservice@gmail.com</strong></p>
        </div>
    </div>
</body>
</html>';
    }

    public function getLastMailTransport() {
        return $this->lastMailTransport;
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
