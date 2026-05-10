<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../core/AdminGuard.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/DefiChallengeValidator.php';
require_once __DIR__ . '/../models/Defi.php';

requireBackOfficeRequest();

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input) || !isset($input['challenges']) || !is_array($input['challenges'])) {
        throw new InvalidArgumentException('La liste des defis est obligatoire.');
    }

    [$challenges, $errors] = normalizeAndValidateDefiChallenges($input['challenges'], 6);
    if (!$challenges) {
        throw new InvalidArgumentException('Aucun defi valide a enregistrer. ' . implode(' ', $errors));
    }

    $db = Database::connect();
    $defiModel = new Defi($db);
    $savedIds = [];

    foreach ($challenges as $challenge) {
        if (!$defiModel->create($challenge)) {
            throw new RuntimeException('Erreur lors de l enregistrement d un defi genere.');
        }
        $savedIds[] = (int)$db->insert_id;
    }

    echo json_encode([
        'success' => true,
        'savedIds' => $savedIds,
        'savedCount' => count($savedIds),
        'validationWarnings' => $errors,
    ]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ]);
}
