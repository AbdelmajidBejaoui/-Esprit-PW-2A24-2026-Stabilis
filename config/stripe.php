<?php

$local = __DIR__ . '/stripe.local.php';
$localConfig = file_exists($local) ? require $local : [];

return array_merge([
    'publishable_key' => 'pk_test_51TSsbX9OhgyCbyA9UizUmPqGMx4qK1FpvTfANoRQNcrCEXDgD2sLQgdTdWnSDLEMJzQiewMpCAkAPQVyObs5I1yX00Sct4KAJf',
    'secret_key' => getenv('STRIPE_SECRET_KEY') ?: '',
    'currency' => 'eur',
], is_array($localConfig) ? $localConfig : []);

