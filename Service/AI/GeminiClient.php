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

        $url = "{$this->baseUrl}/models/{$this->model}:generateContent?key={$this->apiKey}";

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

        $response = $this->makeRequest($url, $payload);
        
        return $this->parseResponse($response);
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

        return json_decode($response, true);
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

        // Clean markdown formatting if present
        $text = preg_replace('/```json\s*|\s*```/', '', $text);
        $text = trim($text);

        // Parse JSON
        $data = json_decode($text, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Invalid JSON response: ' . json_last_error_msg());
        }

        return $data;
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
