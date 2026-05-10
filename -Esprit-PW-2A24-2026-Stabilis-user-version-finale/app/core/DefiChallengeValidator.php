<?php

function normalizeDefiChallenge(array $challenge): array
{
    $type = strtolower(trim((string)($challenge['type'] ?? '')));
    if (!in_array($type, ['aliment', 'entrainement', 'compensation'], true)) {
        $type = 'aliment';
    }

    $reward = trim((string)($challenge['recompense'] ?? ''));
    if ($reward !== '' && !preg_match('/\bpoints?\b/i', $reward)) {
        $reward .= ' points';
    }

    return [
        'nom' => trim((string)($challenge['nom'] ?? '')),
        'type' => $type,
        'objectif' => trim((string)($challenge['objectif'] ?? '')),
        'recompense' => $reward,
    ];
}

function validateDefiChallenge(array $challenge): array
{
    $errors = [];

    if ($challenge['nom'] === '' || mb_strlen($challenge['nom']) > 255) {
        $errors[] = 'Le nom est obligatoire et doit contenir 255 caracteres maximum.';
    }

    if (!in_array($challenge['type'], ['aliment', 'entrainement', 'compensation'], true)) {
        $errors[] = 'Le type doit etre aliment, entrainement ou compensation.';
    }

    if ($challenge['objectif'] === '') {
        $errors[] = 'L objectif est obligatoire.';
    }

    if ($challenge['recompense'] === '' || mb_strlen($challenge['recompense']) > 255) {
        $errors[] = 'La recompense est obligatoire et doit contenir 255 caracteres maximum.';
    }

    return $errors;
}

function normalizeAndValidateDefiChallenges(array $challenges, int $maxCount = 6): array
{
    $validChallenges = [];
    $allErrors = [];

    foreach (array_slice($challenges, 0, $maxCount) as $index => $challenge) {
        if (!is_array($challenge)) {
            $allErrors[] = 'Le defi #' . ($index + 1) . ' est invalide.';
            continue;
        }

        $normalized = normalizeDefiChallenge($challenge);
        $errors = validateDefiChallenge($normalized);

        if ($errors) {
            $allErrors[] = 'Defi #' . ($index + 1) . ': ' . implode(' ', $errors);
            continue;
        }

        $validChallenges[] = $normalized;
    }

    return [$validChallenges, $allErrors];
}
