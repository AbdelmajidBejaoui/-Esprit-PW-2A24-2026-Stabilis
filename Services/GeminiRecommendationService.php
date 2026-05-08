<?php

class GeminiRecommendationService {
    private $apiKey;
    private $lastPrompt = '';
    private $lastError = '';
    private $lastResponseText = '';

    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
    }

    public function getLastPrompt() {
        return $this->lastPrompt;
    }

    public function getLastError() {
        return $this->lastError;
    }

    public function getLastResponseText() {
        return $this->lastResponseText;
    }

    public function recommend(array $currentProduct, array $availableProducts, $limit = 4) {
        $this->lastError = '';
        $this->lastResponseText = '';
        $limit = max(3, min(4, (int)$limit));

        if (!$this->apiKey || $this->apiKey === 'YOUR_GEMINI_API_KEY_HERE' || !function_exists('curl_init')) {
            $this->lastError = !function_exists('curl_init') ? 'cURL PHP indisponible.' : 'Cle Gemini manquante.';
            return [];
        }

        $catalog = [];
        foreach ($availableProducts as $product) {
            if ((int)($product['id'] ?? 0) === (int)($currentProduct['id'] ?? 0)) {
                continue;
            }

            $catalog[] = [
                'id' => (int)$product['id'],
                'name' => (string)($product['nom'] ?? ''),
                'category' => (string)($product['categorie'] ?? ''),
                'price' => $this->getDisplayPrice($product),
                'description' => substr(strip_tags((string)($product['description'] ?? '')), 0, 180)
            ];
        }

        if (empty($catalog)) {
            $this->lastError = 'Catalogue vide.';
            return [];
        }

        $this->lastPrompt = $this->buildPrompt($currentProduct, $catalog, $limit);
        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $this->lastPrompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.35,
                'topP' => 0.9,
                'maxOutputTokens' => 128,
                'thinkingConfig' => [
                    'thinkingBudget' => 0
                ]
            ]
        ];

        $responseText = $this->callGemini($payload);
        if ($responseText === '') {
            return [];
        }

        $ids = $this->extractIds($responseText);
        if (empty($ids)) {
            $this->lastError = 'Gemini n a pas retourne de JSON utilisable.';
            return [];
        }

        $validIds = array_column($catalog, 'id');
        $currentId = (int)($currentProduct['id'] ?? 0);
        $cleanIds = [];
        foreach ($ids as $id) {
            $id = (int)$id;
            if ($id !== $currentId && in_array($id, $validIds, true) && !in_array($id, $cleanIds, true)) {
                $cleanIds[] = $id;
            }
            if (count($cleanIds) >= $limit) {
                break;
            }
        }

        if (count($cleanIds) < 3) {
            $this->lastError = 'Gemini a retourne moins de 3 produits valides.';
            return [];
        }

        return $cleanIds;
    }

    private function buildPrompt(array $currentProduct, array $catalog, $limit) {
        $current = [
            'id' => (int)($currentProduct['id'] ?? 0),
            'name' => (string)($currentProduct['nom'] ?? ''),
            'category' => (string)($currentProduct['categorie'] ?? ''),
            'price' => $this->getDisplayPrice($currentProduct),
            'description' => substr(strip_tags((string)($currentProduct['description'] ?? '')), 0, 300)
        ];

        return "You are an ecommerce recommendation assistant for a sports nutrition shop.

Current product:
" . json_encode($current, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "

Available products:
" . json_encode(array_slice($catalog, 0, 30), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "

Task:
Choose {$limit} products that are similar or complementary to the current product.
Exclude the current product.
Use only product IDs from the available products list.
Prefer products from the same category, then complementary products from another category.

Return only valid JSON in this exact format:
{\"recommendation_ids\":[1,2,3,4]}";
    }

    private function callGemini(array $payload) {
        $models = [
            'gemini-3-flash-preview',
            'gemini-2.5-flash',
            'gemini-flash-latest',
            'gemini-2.0-flash',
            'gemini-1.5-flash-latest'
        ];
        $endpoints = [
            'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=' . urlencode($this->apiKey),
            'https://generativelanguage.googleapis.com/v1/models/%s:generateContent?key=' . urlencode($this->apiKey)
        ];

        foreach ($models as $model) {
            foreach ($endpoints as $endpoint) {
                $ch = curl_init(sprintf($endpoint, rawurlencode($model)));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                curl_setopt($ch, CURLOPT_TIMEOUT, 12);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlError = curl_error($ch);
                curl_close($ch);

                if ($curlError || $httpCode !== 200) {
                    $this->lastError = 'Gemini HTTP ' . $httpCode . ($curlError ? ' - ' . $curlError : '');
                    error_log('Gemini recommendations failed: ' . $this->lastError . ' Response: ' . substr((string)$response, 0, 500));
                    continue;
                }

                $data = json_decode($response, true);
                $parts = $data['candidates'][0]['content']['parts'] ?? [];
                $text = '';
                foreach ($parts as $part) {
                    $text .= ' ' . ($part['text'] ?? '');
                }

                $text = trim($text);
                if ($text !== '') {
                    $this->lastResponseText = $text;
                    return $text;
                }

                $this->lastError = 'Reponse Gemini vide.';
            }
        }

        return '';
    }

    private function getDisplayPrice(array $product) {
        $price = (float)($product['prix'] ?? 0);
        $promoPrice = $product['promo_prix'] ?? null;

        if ($promoPrice !== null && $promoPrice !== '' && (float)$promoPrice > 0 && (float)$promoPrice < $price) {
            return (float)$promoPrice;
        }

        return $price;
    }

    private function extractIds($text) {
        $text = trim((string)$text);
        $text = preg_replace('/^```json\s*|\s*```$/i', '', $text);
        $data = json_decode($text, true);

        if (!is_array($data)) {
            $start = strpos($text, '{');
            $end = strrpos($text, '}');
            if ($start !== false && $end !== false && $end > $start) {
                $data = json_decode(substr($text, $start, $end - $start + 1), true);
            }
        }

        if (!is_array($data)) {
            return [];
        }

        if (isset($data['recommendation_ids']) && is_array($data['recommendation_ids'])) {
            return array_map('intval', $data['recommendation_ids']);
        }

        $recommendations = $data['recommendations'] ?? $data;
        $ids = [];
        foreach ($recommendations as $item) {
            if (isset($item['id'])) {
                $ids[] = (int)$item['id'];
            } elseif (is_numeric($item)) {
                $ids[] = (int)$item;
            }
        }

        if (empty($ids) && preg_match_all('/["\']?id["\']?\s*[:=]\s*([0-9]+)/i', $text, $matches)) {
            $ids = array_map('intval', $matches[1]);
        }

        return $ids;
    }
}
