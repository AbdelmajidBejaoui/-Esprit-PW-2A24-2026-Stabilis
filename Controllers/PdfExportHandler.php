<?php


header('Content-Type: application/json');

require_once __DIR__ . '/../Services/DashboardPDFExportService.php';

try {
    $action = $_POST['action'] ?? $_GET['action'] ?? null;
    
    if (!$action) {
        throw new Exception('No action specified');
    }

    $exportService = new DashboardPDFExportService();
    $filePath = null;
    $filename = null;

    switch ($action) {
        case 'export_full':
            $filePath = $exportService->generateFullReport();
            $filename = 'Rapport_Stabilis_' . date('Y-m-d_H-i-s') . '.html';
            break;
            
        case 'export_sales':
            $filePath = $exportService->generateSalesReport();
            $filename = 'Rapport_Ventes_' . date('Y-m-d_H-i-s') . '.html';
            break;
            
        case 'export_products':
            $filePath = $exportService->generateProductReport();
            $filename = 'Rapport_Produits_' . date('Y-m-d_H-i-s') . '.html';
            break;
            
        default:
            throw new Exception('Unknown action: ' . $action);
    }

    if ($filePath && file_exists($filePath)) {
        $baseName = basename($filePath);
        echo json_encode([
            'success' => true,
            'message' => 'Rapport généré avec succès - Impression en cours...',
            'file' => $baseName,
            'download_url' => '/AdminLTE3/storage/exports/' . $baseName
        ]);
    } else {
        throw new Exception('Impossible de générer le rapport');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur : ' . $e->getMessage()
    ]);
}

