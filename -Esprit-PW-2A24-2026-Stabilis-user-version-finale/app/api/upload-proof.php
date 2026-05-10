<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/GeminiClient.php';
require_once __DIR__ . '/../models/Participation.php';
require_once __DIR__ . '/../models/ParticipationProof.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Methode non autorisee.');
    }

    $participationId = (int)($_POST['participation_id'] ?? 0);
    $userId = (int)($_POST['id_utilisateur'] ?? 0);

    if ($participationId <= 0 || $userId <= 0) {
        throw new Exception('Participation ou utilisateur invalide.');
    }

    if (!isset($_FILES['proof_file']) || $_FILES['proof_file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Fichier de preuve obligatoire.');
    }

    $file = $_FILES['proof_file'];
    $allowedMimeTypes = [
        'image/jpeg' => ['extension' => 'jpg', 'max_size' => 3 * 1024 * 1024],
        'image/png' => ['extension' => 'png', 'max_size' => 3 * 1024 * 1024],
        'image/webp' => ['extension' => 'webp', 'max_size' => 3 * 1024 * 1024],
        'video/mp4' => ['extension' => 'mp4', 'max_size' => 25 * 1024 * 1024],
        'video/webm' => ['extension' => 'webm', 'max_size' => 25 * 1024 * 1024],
        'video/quicktime' => ['extension' => 'mov', 'max_size' => 25 * 1024 * 1024],
    ];
    $mimeType = mime_content_type($file['tmp_name']);

    if (!isset($allowedMimeTypes[$mimeType])) {
        throw new Exception('Format de preuve non autorise. Utilisez JPG, PNG, WEBP, MP4, WEBM ou MOV.');
    }

    if ($file['size'] > $allowedMimeTypes[$mimeType]['max_size']) {
        throw new Exception(str_starts_with($mimeType, 'video/')
            ? 'La video de preuve ne doit pas depasser 25 Mo.'
            : 'L image de preuve ne doit pas depasser 3 Mo.');
    }

    $db = Database::connect();
    $participationModel = new Participation($db);

    if (!$participationModel->userOwnsParticipation($participationId, $userId)) {
        throw new Exception('Cette participation ne correspond pas a cet utilisateur.');
    }
    $participation = $participationModel->getById($participationId);

    $uploadDir = __DIR__ . '/../../uploads/proofs';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }

    $extension = $allowedMimeTypes[$mimeType]['extension'];
    $fileName = 'proof_' . $participationId . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
    $targetPath = $uploadDir . '/' . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception('Impossible d enregistrer la preuve.');
    }

    $relativePath = 'uploads/proofs/' . $fileName;
    $proofModel = new ParticipationProof($db);
    if (!$proofModel->create($participationId, $relativePath)) {
        throw new Exception('Impossible de creer la preuve.');
    }
    $proofId = (int)$db->insert_id;

    $aiReview = null;
    try {
        $client = new GeminiClient();
        $aiReview = $client->reviewProof($participation ?? [], $targetPath, $mimeType);
        $proofModel->saveAiReview($proofId, $aiReview);
    } catch (Throwable $aiError) {
        $aiReview = [
            'decision' => 'error',
            'confidence' => 0,
            'progress_increment' => 0,
            'reason' => 'Analyse IA indisponible: ' . $aiError->getMessage(),
        ];
        try {
            $proofModel->saveAiReview($proofId, $aiReview);
        } catch (Throwable $ignored) {
            // The proof remains valid even if the optional AI review cannot be stored.
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Preuve envoyee. Elle est maintenant en attente de revision.',
        'file_path' => $relativePath,
        'ai_review' => $aiReview,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
