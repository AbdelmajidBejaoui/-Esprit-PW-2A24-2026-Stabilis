<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../models/Defi.php';
require_once __DIR__ . '/../models/Participation.php';

try {
    $db = Database::connect();
    $defiModel = new Defi($db);
    $participationModel = new Participation($db);

    // Get all défis
    $defis = $defiModel->getAll();
    
    // Count défis by type with percentages
    $defisStats = [
        'total' => count($defis),
        'aliment' => 0,
        'entrainement' => 0,
        'compensation' => 0,
        'percentages' => [
            'aliment' => 0,
            'entrainement' => 0,
            'compensation' => 0
        ]
    ];

    foreach ($defis as $defi) {
        if ($defi['type'] === 'aliment') {
            $defisStats['aliment']++;
        } elseif ($defi['type'] === 'entrainement') {
            $defisStats['entrainement']++;
        } elseif ($defi['type'] === 'compensation') {
            $defisStats['compensation']++;
        }
    }

    // Calculate percentages
    if ($defisStats['total'] > 0) {
        $defisStats['percentages']['aliment'] = round(($defisStats['aliment'] / $defisStats['total']) * 100, 1);
        $defisStats['percentages']['entrainement'] = round(($defisStats['entrainement'] / $defisStats['total']) * 100, 1);
        $defisStats['percentages']['compensation'] = round(($defisStats['compensation'] / $defisStats['total']) * 100, 1);
    }

    // Get all participations
    $participations = $participationModel->getAll();
    
    // Count participations by status with percentages
    $participationsStats = [
        'total' => count($participations),
        'en_cours' => 0,
        'reussi' => 0,
        'echoue' => 0,
        'in_progress' => 0,
        'completed' => 0,
        'failed' => 0,
        'percentages' => [
            'en_cours' => 0,
            'reussi' => 0,
            'echoue' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'failed' => 0
        ]
    ];

    foreach ($participations as $participation) {
        if (in_array($participation['statut'], ['in_progress', 'en_cours'], true)) {
            $participationsStats['en_cours']++;
            $participationsStats['in_progress']++;
        } elseif (in_array($participation['statut'], ['completed', 'reussi'], true)) {
            $participationsStats['reussi']++;
            $participationsStats['completed']++;
        } elseif (in_array($participation['statut'], ['failed', 'echoue'], true)) {
            $participationsStats['echoue']++;
            $participationsStats['failed']++;
        }
    }

    // Calculate percentages
    if ($participationsStats['total'] > 0) {
        $participationsStats['percentages']['en_cours'] = round(($participationsStats['en_cours'] / $participationsStats['total']) * 100, 1);
        $participationsStats['percentages']['reussi'] = round(($participationsStats['reussi'] / $participationsStats['total']) * 100, 1);
        $participationsStats['percentages']['echoue'] = round(($participationsStats['echoue'] / $participationsStats['total']) * 100, 1);
        $participationsStats['percentages']['in_progress'] = $participationsStats['percentages']['en_cours'];
        $participationsStats['percentages']['completed'] = $participationsStats['percentages']['reussi'];
        $participationsStats['percentages']['failed'] = $participationsStats['percentages']['echoue'];
    }

    echo json_encode([
        'success' => true,
        'defis' => $defisStats,
        'participations' => $participationsStats,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de la récupération des statistiques'
    ]);
}
?>
