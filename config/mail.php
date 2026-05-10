<?php
$secretsPath = __DIR__ . '/secrets.php';
$secrets = is_file($secretsPath) ? require $secretsPath : [];

return [
    'alert_recipient' => 'stabilisatyourservice@gmail.com',
    'alert_threshold' => 3,
    'from_email' => 'stabilisatyourservice@gmail.com',
    'from_name' => 'Stabilis - Alertes Stock',
    'method' => 'log',  // Changed from 'smtp' to 'log' for development
    'smtp' => [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'secure' => 'tls',
        'username' => getenv('STABILIS_SMTP_USERNAME') ?: ($secrets['smtp_username'] ?? 'stabilisatyourservice@gmail.com'),
        'password' => getenv('STABILIS_SMTP_PASSWORD') ?: ($secrets['smtp_password'] ?? ''),  
    ],
    
    
    'gemini_api_key' => getenv('GEMINI_API_KEY') ?: ($secrets['gemini_api_key'] ?? ''),  
];
