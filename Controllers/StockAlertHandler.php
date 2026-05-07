<?php


require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Services/StockAlertService.php';

header('Content-Type: application/json');


$action = $_GET['action'] ?? $_POST['action'] ?? '';
$response = ['success' => false, 'message' => 'Invalid action'];

try {
    $pdo = Database::getConnection();
    $stockAlertService = new StockAlertService($pdo);

    switch ($action) {
        case 'test_email':
            
            $recipient = $_GET['recipient'] ?? $_POST['recipient'] ?? null;
            $response = $stockAlertService->sendTestEmail($recipient);
            
            $response['log_file'] = 'storage/mail_logs/emails_' . date('Y-m-d') . '.log';
            break;

        case 'check_low_stock':
            
            $response = $stockAlertService->sendLowStockAlert();
            
            $response['log_file'] = 'storage/mail_logs/emails_' . date('Y-m-d') . '.log';
            break;

        case 'get_low_stock':
            
            $threshold = $_GET['threshold'] ?? $_POST['threshold'] ?? null;
            $products = $stockAlertService->getLowStockProducts($threshold);
            $response = [
                'success' => true,
                'count' => count($products),
                'products' => $products
            ];
            break;

        default:
            $response = ['success' => false, 'message' => 'Action non reconnue'];
    }
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => 'Erreur : ' . $e->getMessage()
    ];
    error_log('Stock Alert Handler Error: ' . $e->getMessage());
}

echo json_encode($response);
exit;
