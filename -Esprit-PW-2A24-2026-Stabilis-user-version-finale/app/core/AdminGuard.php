<?php

function requireBackOfficeRequest(string $featureName = 'ai-generator'): void
{
    // This project currently has no central login/session guard.
    // Keep AI endpoints tied to the admin UI until real auth is added.
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    $featureHeader = $_SERVER['HTTP_X_ADMIN_FEATURE'] ?? '';
    $isBackOfficeReferer = strpos($referer, '/back-office/') !== false;

    if (!$isBackOfficeReferer || $featureHeader !== $featureName) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Accès back-office requis',
        ]);
        exit;
    }
}
