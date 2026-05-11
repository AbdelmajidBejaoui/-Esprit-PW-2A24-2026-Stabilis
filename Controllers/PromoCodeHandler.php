<?php
ob_start();

try {
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../Services/PromoCodeValidator.php';
    
    header('Content-Type: application/json; charset=utf-8');
    
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    $pdo = Database::getConnection();
    $validator = new PromoCodeValidator($pdo);
    
    if ($action === 'validate_code') {
        $code = trim($_POST['code'] ?? '');
        $productId = intval($_POST['product_id'] ?? 0);
        $productIds = array_filter(array_map('intval', explode(',', $_POST['product_ids'] ?? '')));
        $customerEmail = trim($_POST['customer_email'] ?? '');
        
        error_log("Promo validation: code=$code, product=$productId, email=$customerEmail");

        if (empty($code) || empty($customerEmail)) {
            http_response_code(200);
            ob_end_clean();
            echo json_encode([
                'success' => false,
                'message' => 'Code promo, email et produit requis.',
                'discount' => 0
            ]);
            exit;
        }
        
        $result = ['valid' => false, 'message' => 'Code promo invalide ou non applicable pour ce panier.', 'discount' => 0];
        $idsToCheck = !empty($productIds) ? $productIds : [$productId];
        if (empty($idsToCheck)) {
            $idsToCheck = [0];
        }
        foreach ($idsToCheck as $idToCheck) {
            $result = $validator->validatePromoCode($code, $idToCheck, $customerEmail);
            if (!empty($result['valid'])) {
                break;
            }
        }
        
        http_response_code(200);
        ob_end_clean();
        echo json_encode([
            'success' => $result['valid'],
            'message' => $result['message'],
            'discount' => $result['discount'] ?? 0,
            'code_id' => $result['code_id'] ?? null,
            'product_id' => $result['product_id'] ?? null
        ]);
        exit;
    }

    if ($action === 'create_manual_code') {
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $scope = $_POST['scope'] ?? 'all';
        $productId = $scope === 'product' ? intval($_POST['product_id'] ?? 0) : null;
        $discount = intval($_POST['discount'] ?? 0);
        $usageLimit = max(1, intval($_POST['usage_limit'] ?? 1));
        $days = max(1, intval($_POST['days'] ?? 7));

        if ($code === '' || !preg_match('/^[A-Z0-9-]{4,30}$/', $code)) {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Code invalide. Utilisez 4 a 30 caracteres: lettres, chiffres ou tirets.']);
            exit;
        }

        if ($scope === 'product' && $productId <= 0) {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Selectionnez un produit pour ce code promo.']);
            exit;
        }

        if ($discount < 1 || $discount > 90) {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'La reduction doit etre comprise entre 1% et 90%.']);
            exit;
        }

        $existing = $validator->getPromoCodeInfo($code);
        if ($existing) {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Ce code existe deja. Generez un autre code.']);
            exit;
        }

        $expiresAt = date('Y-m-d H:i:s', strtotime('+' . $days . ' days'));
        $saved = $validator->saveManualPromoCode($code, $productId, $discount, $expiresAt, $usageLimit);

        ob_end_clean();
        echo json_encode([
            'success' => $saved,
            'message' => $saved ? 'Code promo cree avec succes.' : 'Impossible de creer le code promo.',
            'code' => $code,
            'discount' => $discount,
            'expires_at' => $expiresAt
        ]);
        exit;
    }
    
    if ($action === 'get_active_codes') {
        $sql = "
            SELECT pc.id, pc.code, pc.product_id, pc.customer_email, pc.discount, pc.usage_limit, pc.times_used, pc.expires_at, pc.used, pc.created_at, p.nom AS product_name
            FROM promo_codes pc
            LEFT JOIN produits p ON p.id = pc.product_id
            WHERE used = 0 AND expires_at > NOW()
            ORDER BY created_at DESC
            LIMIT 50
        ";
        
        $stmt = $pdo->query($sql);
        $codes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        http_response_code(200);
        ob_end_clean();
        echo json_encode([
            'success' => true,
            'count' => count($codes),
            'codes' => $codes
        ]);
        exit;
    }
    
    http_response_code(400);
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Action non reconnue.',
        'discount' => 0
    ]);
    exit;
    
} catch (Exception $e) {
    error_log("PromoCodeHandler Error: " . $e->getMessage());
    error_log("Stack: " . $e->getTraceAsString());
    
    http_response_code(500);
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur: ' . $e->getMessage(),
        'discount' => 0
    ]);
    exit;
}
