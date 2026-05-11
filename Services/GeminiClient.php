<?php
/**
 * GeminiClient - Google Gemini API client
 * 
 * Handles all communication with Google's Gemini AI API.
 * Provides clean interface for workout generation.
 */
class GeminiClient
{
    private string $apiKey;
    private string $model = 'gemini-2.5-flash';
    private string $baseUrl = 'https://generativelanguage.googleapis.com/v1';

    public function __construct(string $apiKey)
    {
        if (empty($apiKey) || $apiKey === 'votre_cle_api_ici') {
            throw new InvalidArgumentException(
                'Invalid Gemini API key. Get one at: https://aistudio.google.com/app/apikey'
            );
        }
        
        $this->apiKey = $apiKey;
    }

    /**
     * Generate workout based on prompt
     */
    public function generateWorkout(string $prompt, array $config = []): array
    {
        $temperature = $config['temperature'] ?? 0.7;
        $maxTokens = $config['maxTokens'] ?? 4096;

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => $temperature,
                'maxOutputTokens' => $maxTokens,
            ]
        ];

        $models = array_values(array_unique([
            $this->model,
            'gemini-2.0-flash',
        ]));

        $lastError = null;
        $temporaryError = null;
        foreach ($models as $index => $model) {
            try {
                $url = "{$this->baseUrl}/models/{$model}:generateContent?key={$this->apiKey}";
                $response = $this->makeRequest($url, $payload);

                return $this->parseResponse($response);
            } catch (RuntimeException $e) {
                $lastError = $e;
                if (!$this->isTemporaryGeminiError($e)) {
                    throw $temporaryError ?? $e;
                }

                $temporaryError = $temporaryError ?? $e;

                if ($index === count($models) - 1) {
                    throw $temporaryError;
                }

                error_log("Gemini model {$model} temporarily unavailable, trying fallback: " . $e->getMessage());
                usleep(350000);
            }
        }

        throw $lastError ?? new RuntimeException('Gemini generation failed.');
    }

    /**
     * Make HTTP request to Gemini API
     */
    private function makeRequest(string $url, array $payload): array
    {
        $ch = curl_init($url);
        
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new RuntimeException("cURL error: {$error}");
        }

        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            $errorMsg = $errorData['error']['message'] ?? 'Unknown API error';
            throw new RuntimeException("Gemini API error ({$httpCode}): {$errorMsg}");
        }

        $decoded = json_decode((string)$response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Invalid Gemini API response: ' . json_last_error_msg());
        }

        return $decoded;
    }

    /**
     * Parse Gemini API response
     */
    private function parseResponse(array $response): array
    {
        // Extract text from response (handle different formats)
        $text = $response['candidates'][0]['content']['parts'][0]['text'] 
             ?? $response['candidates'][0]['output'] 
             ?? $response['text'] 
             ?? '';

        if (empty($text)) {
            throw new RuntimeException('Empty response from Gemini API');
        }

        $data = $this->decodeJsonText($text);
        if (!is_array($data)) {
            throw new RuntimeException('Invalid JSON response: expected object');
        }

        return $data;
    }

    /**
     * Decode model JSON safely.
     *
     * Gemini sometimes returns valid-looking JSON wrapped in markdown, with
     * extra prose, or with raw control characters in generated strings. This
     * keeps generation usable while still failing when no JSON object exists.
     */
    private function decodeJsonText(string $text): array
    {
        $candidate = $this->extractJsonObject($text);
        $decoded = json_decode($candidate, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        $cleaned = preg_replace('/[\x00-\x1F\x7F]/', ' ', $candidate);
        $decoded = json_decode((string)$cleaned, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        throw new RuntimeException('Invalid JSON response: ' . json_last_error_msg());
    }

    private function extractJsonObject(string $text): string
    {
        $text = trim(preg_replace('/^\xEF\xBB\xBF/', '', $text));
        $text = preg_replace('/```(?:json)?\s*|\s*```/i', '', $text);
        $text = trim($text);

        $start = strpos($text, '{');
        $end = strrpos($text, '}');

        if ($start === false || $end === false || $end <= $start) {
            throw new RuntimeException('Invalid JSON response: no JSON object found');
        }

        return substr($text, $start, $end - $start + 1);
    }

    private function isTemporaryGeminiError(RuntimeException $e): bool
    {
        $message = $e->getMessage();

        return strpos($message, 'Gemini API error (503)') !== false
            || strpos($message, 'Gemini API error (429)') !== false
            || stripos($message, 'high demand') !== false
            || stripos($message, 'temporarily') !== false;
    }

    /**
     * Set model version
     */
    public function setModel(string $model): self
    {
        $this->model = $model;
        return $this;
    }

    /**
     * Test API connection
     */
    public function testConnection(): bool
    {
        try {
            $this->generateWorkout('Test', ['maxTokens' => 10]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
