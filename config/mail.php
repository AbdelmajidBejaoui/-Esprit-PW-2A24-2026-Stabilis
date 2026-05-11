<?php
$secretsPath = __DIR__ . '/secrets.php';
$secrets = is_file($secretsPath) ? require $secretsPath : [];

$smtpUsername = getenv('STABILIS_SMTP_USERNAME') ?: ($secrets['smtp_username'] ?? 'stabilisatyourservice@gmail.com');
$smtpPassword = getenv('STABILIS_SMTP_PASSWORD') ?: ($secrets['smtp_password'] ?? '');
$mailMethod = getenv('STABILIS_MAIL_METHOD') ?: ($secrets['mail_method'] ?? null);
if ($mailMethod === null) {
    $mailMethod = $smtpPassword !== '' ? 'smtp' : 'log';
}

return [
    'alert_recipient' => 'stabilisatyourservice@gmail.com',
    'alert_threshold' => 3,
    'from_email' => 'stabilisatyourservice@gmail.com',
    'from_name' => 'Stabilis - Mail',
    'method' => $mailMethod,
    'smtp' => [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'secure' => 'tls',
        'username' => $smtpUsername,
        'password' => $smtpPassword,
    ],

    'gemini_api_key' => getenv('GEMINI_API_KEY') ?: ($secrets['gemini_api_key'] ?? ''),  
];
