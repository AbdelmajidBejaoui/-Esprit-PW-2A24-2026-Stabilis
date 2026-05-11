<?php
$local = __DIR__ . '/stripe.local.php';
$localConfig = file_exists($local) ? require $local : [];

return array_merge([
    'publishable_key' => '',
    'secret_key' => '',
    'currency' => 'eur',
], is_array($localConfig) ? $localConfig : []);
