<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../core/AdminGuard.php';
require_once __DIR__ . '/../core/GeminiClient.php';
require_once __DIR__ . '/../core/DefiChallengeValidator.php';

requireBackOfficeRequest();

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        throw new InvalidArgumentException('Requete invalide.');
    }

    $topic = trim((string)($input['topic'] ?? ''));
    $difficulty = trim((string)($input['difficulty'] ?? 'medium'));
    $count = max(1, min((int)($input['count'] ?? 3), 6));
    $technology = trim((string)($input['technology'] ?? ''));

    if ($topic === '' || mb_strlen($topic) > 120) {
        throw new InvalidArgumentException('Le sujet est obligatoire et doit contenir 120 caracteres maximum.');
    }

    if (!in_array($difficulty, ['beginner', 'easy', 'medium', 'hard', 'advanced'], true)) {
        throw new InvalidArgumentException('Difficulte invalide.');
    }

    if (mb_strlen($technology) > 80) {
        throw new InvalidArgumentException('Le focus optionnel doit contenir 80 caracteres maximum.');
    }

    $client = new GeminiClient();
    $rawChallenges = $client->generateChallenges([
        'topic' => $topic,
        'difficulty' => $difficulty,
        'count' => $count,
        'technology' => $technology,
    ]);

    [$challenges, $errors] = normalizeAndValidateDefiChallenges($rawChallenges, $count);

    if (!$challenges) {
        throw new RuntimeException('Aucun defi valide n\'a ete retourne. ' . implode(' ', $errors));
    }

    echo json_encode([
        'success' => true,
        'challenges' => $challenges,
        'validationWarnings' => $errors,
    ]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ]);
}
