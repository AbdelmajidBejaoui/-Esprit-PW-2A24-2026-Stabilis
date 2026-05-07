<?php

class StripeCheckoutService {
    private $config;

    public function __construct(array $config = null) {
        $this->config = $config ?? require __DIR__ . '/../config/stripe.php';
    }

    public function isConfigured() {
        $secretKey = trim((string)($this->config['secret_key'] ?? ''));
        return $secretKey !== '' && strpos($secretKey, 'sk_test_') === 0;
    }

    public function createCheckoutSession(array $order, array $lineItems, $amountTotal) {
        if (!$this->isConfigured()) {
            return ['success' => false, 'message' => 'La cle secrete Stripe n est pas configuree.'];
        }

        if (!function_exists('curl_init')) {
            return ['success' => false, 'message' => 'L extension PHP cURL est requise pour Stripe.'];
        }

        $amountCents = (int)round(((float)$amountTotal) * 100);
        if ($amountCents < 50) {
            return ['success' => false, 'message' => 'Stripe demande un total minimum de 0.50 EUR.'];
        }

        $baseUrl = $this->baseUrl();
        $description = $this->buildDescription($lineItems);
        $payload = [
            'mode' => 'payment',
            'success_url' => $baseUrl . '/Views/front/order.php?stripe=success&session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $baseUrl . '/Views/front/order.php?stripe=cancel',
            'customer_email' => $order['email'] ?? '',
            'client_reference_id' => session_id(),
            'metadata' => [
                'customer' => trim(($order['prenom'] ?? '') . ' ' . ($order['nom'] ?? '')),
                'source' => 'stabilis_front_checkout',
            ],
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => $this->config['currency'] ?? 'eur',
                    'unit_amount' => $amountCents,
                    'product_data' => [
                        'name' => 'Commande Stabilis',
                        'description' => $description,
                    ],
                ],
            ]],
        ];

        $response = $this->request('POST', 'https://api.stripe.com/v1/checkout/sessions', $payload);
        if (!$response['success']) {
            return $response;
        }

        if (empty($response['data']['url']) || empty($response['data']['id'])) {
            return ['success' => false, 'message' => 'Stripe n a pas retourne de lien de paiement.'];
        }

        return [
            'success' => true,
            'id' => $response['data']['id'],
            'url' => $response['data']['url'],
        ];
    }

    public function retrieveCheckoutSession($sessionId) {
        if (!$this->isConfigured()) {
            return ['success' => false, 'message' => 'La cle secrete Stripe n est pas configuree.'];
        }

        $sessionId = trim((string)$sessionId);
        if ($sessionId === '' || !preg_match('/^cs_(test|live)_[A-Za-z0-9]+/', $sessionId)) {
            return ['success' => false, 'message' => 'Session Stripe invalide.'];
        }

        return $this->request('GET', 'https://api.stripe.com/v1/checkout/sessions/' . rawurlencode($sessionId));
    }

    private function request($method, $url, array $payload = []) {
        $ch = curl_init();
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD => $this->config['secret_key'] . ':',
            CURLOPT_TIMEOUT => 25,
        ];

        if ($method === 'POST') {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = http_build_query($payload);
        }

        curl_setopt_array($ch, $options);
        $raw = curl_exec($ch);
        $error = curl_error($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($raw === false) {
            error_log('Stripe cURL error: ' . $error);
            return ['success' => false, 'message' => 'Connexion Stripe impossible: ' . $error];
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            error_log('Stripe invalid JSON response: ' . $raw);
            return ['success' => false, 'message' => 'Reponse Stripe invalide.'];
        }

        if ($status < 200 || $status >= 300) {
            $message = $data['error']['message'] ?? 'Erreur Stripe inconnue.';
            error_log('Stripe API error: ' . $message);
            return ['success' => false, 'message' => $message, 'data' => $data];
        }

        return ['success' => true, 'data' => $data];
    }

    private function baseUrl() {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') === '443');
        $scheme = $https ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $scheme . '://' . $host . '/AdminLTE3';
    }

    private function buildDescription(array $lineItems) {
        $parts = [];
        foreach ($lineItems as $item) {
            $parts[] = ($item['quantity'] ?? 1) . ' x ' . ($item['name'] ?? 'Article');
        }
        return substr(implode(', ', $parts), 0, 480);
    }
}
