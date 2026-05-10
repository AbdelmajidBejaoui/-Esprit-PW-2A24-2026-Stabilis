<?php
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in JSON response

try {
    require_once __DIR__ . '/../core/Database.php';
    require_once __DIR__ . '/../models/Participation.php';
    require_once __DIR__ . '/../models/Defi.php';

    // Get JSON data from request body
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    // Log the request for debugging
    $log_file = __DIR__ . '/../../back-office/api.log';
    $log_message = "[" . date('Y-m-d H:i:s') . "] Request: " . $json . "\n";
    file_put_contents($log_file, $log_message, FILE_APPEND);

    // Validate required fields
    if (!is_array($data)) {
        throw new Exception('Requête JSON invalide.');
    }

    if (!isset($data['id_utilisateur']) || !isset($data['id_defi'])) {
        throw new Exception('Utilisateur ou défi manquant.');
    }

    // Connect to database
    $db = Database::connect();
    if (!$db) {
        throw new Exception('Connexion à la base de données impossible.');
    }

    // Initialize Participation model
    $participation = new Participation($db);
    $defi = new Defi($db);

    // Prepare participation data. The user can only start an attempt:
    // progress and status are controlled by the server/admin flow.
    $participationData = [
        'id_utilisateur' => intval($data['id_utilisateur']),
        'id_defi' => intval($data['id_defi']),
        'progression' => 0,
        'statut' => 'in_progress',
        'date_debut' => date('Y-m-d'),
        'date_fin' => null
    ];

    if ($participationData['id_utilisateur'] <= 0) {
        throw new Exception('ID utilisateur invalide.');
    }

    if (!$participation->userExists($participationData['id_utilisateur'])) {
        throw new Exception('Utilisateur introuvable ou inactif.');
    }

    if ($participationData['id_defi'] <= 0 || !$defi->getById($participationData['id_defi'])) {
        throw new Exception('ID défi invalide.');
    }

    // Log prepared data
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] Data: " . json_encode($participationData) . "\n", FILE_APPEND);

    // Check if user already participates in this challenge
    if ($participation->existsForUserAndDefi($participationData['id_utilisateur'], $participationData['id_defi'])) {
        throw new Exception('Cet utilisateur participe déjà à ce défi.');
    }

    // Create participation
    if ($participation->create($participationData)) {
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] Success: Participation created with ID " . $db->insert_id . "\n", FILE_APPEND);
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Participation démarrée avec succès.',
            'id' => $db->insert_id
        ]);
    } else {
        throw new Exception('Impossible de démarrer la participation.');
    }

} catch (Exception $e) {
    $log_file = __DIR__ . '/../../back-office/api.log';
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] Error: " . $e->getMessage() . "\n", FILE_APPEND);
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
