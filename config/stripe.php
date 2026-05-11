<?php

$local = __DIR__ . '/stripe.local.php';
$localConfig = file_exists($local) ? require $local : [];

return array_merge([
    'publishable_key' => getenv('STRIPE_PUBLISHABLE_KEY') ?: '',
    'secret_key' => getenv('STRIPE_SECRET_KEY') ?: '',
    'currency' => 'eur',
], is_array($localConfig) ? $localConfig : []);

