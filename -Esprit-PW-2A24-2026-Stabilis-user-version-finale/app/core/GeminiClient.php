<?php

class GeminiClient
{
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $config = $this->loadConfig();
        $this->apiKey = trim((string)($config['gemini_api_key'] ?? ''));
        $this->model = trim((string)($config['gemini_model'] ?? 'gemini-2.5-flash-lite'));

        if ($this->apiKey === '' || $this->apiKey === 'PASTE_YOUR_GEMINI_API_KEY_HERE') {
            throw new RuntimeException('La cle API Gemini n\'est pas configuree.');
        }
    }

    public function generateChallenges(array $constraints): array
    {
        $prompt = $this->buildPrompt($constraints);
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' .
            rawurlencode($this->model) . ':generateContent?key=' . rawurlencode($this->apiKey);

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 0.35,
                'topP' => 0.8,
                'maxOutputTokens' => 1400,
                'responseMimeType' => 'application/json',
            ],
        ];

        $response = $this->postJson($url, $payload);
        $text = $response['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $json = json_decode($text, true);

        if (!is_array($json)) {
            throw new RuntimeException('Gemini n\'a pas retourne un tableau JSON valide.');
        }

        return $json;
    }

    public function generateWeeklyStory(array $summary): string
    {
        $prompt = $this->buildWeeklyStoryPrompt($summary);
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' .
            rawurlencode($this->model) . ':generateContent?key=' . rawurlencode($this->apiKey);

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 0.45,
                'topP' => 0.8,
                'maxOutputTokens' => 220,
            ],
        ];

        $response = $this->postJson($url, $payload);
        $story = trim((string)($response['candidates'][0]['content']['parts'][0]['text'] ?? ''));

        if ($story === '') {
            throw new RuntimeException('Gemini n\'a pas retourné de récit.');
        }

        return mb_substr($story, 0, 900);
    }

    public function reviewProof(array $participation, string $absoluteFilePath, string $mimeType): array
    {
        if (!is_file($absoluteFilePath) || !is_readable($absoluteFilePath)) {
            throw new RuntimeException('Le fichier de preuve est introuvable.');
        }

        // Inline Gemini uploads are kept small so proof upload stays responsive.
        if (filesize($absoluteFilePath) > 18 * 1024 * 1024) {
            return [
                'decision' => 'uncertain',
                'confidence' => 0,
                'progress_increment' => 0,
                'reason' => 'Fichier trop volumineux pour une analyse IA automatique. Verification manuelle recommandee.',
            ];
        }

        $prompt = $this->buildProofReviewPrompt($participation);
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' .
            rawurlencode($this->model) . ':generateContent?key=' . rawurlencode($this->apiKey);

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $prompt],
                        [
                            'inlineData' => [
                                'mimeType' => $mimeType,
                                'data' => base64_encode(file_get_contents($absoluteFilePath)),
                            ],
                        ],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 0.15,
                'topP' => 0.7,
                'maxOutputTokens' => 450,
                'responseMimeType' => 'application/json',
            ],
        ];

        $response = $this->postJson($url, $payload, 75);
        $text = $response['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $json = json_decode($text, true);

        if (!is_array($json)) {
            throw new RuntimeException('Gemini n a pas retourne une revue JSON valide.');
        }

        return $this->normalizeProofReview($json);
    }

    private function loadConfig(): array
    {
        $envKey = getenv('GEMINI_API_KEY');
        if ($envKey !== false && trim($envKey) !== '') {
            return [
                'gemini_api_key' => $envKey,
                'gemini_model' => getenv('GEMINI_MODEL') ?: 'gemini-2.5-flash-lite',
            ];
        }

        $localConfig = __DIR__ . '/../ai-config.local.php';
        if (file_exists($localConfig)) {
            $config = require $localConfig;
            return is_array($config) ? $config : [];
        }

        return [];
    }

    private function buildPrompt(array $constraints): string
    {
        $topic = $this->cleanPromptValue($constraints['topic'] ?? '');
        $difficulty = $this->cleanPromptValue($constraints['difficulty'] ?? 'medium');
        $count = max(1, min((int)($constraints['count'] ?? 3), 6));
        $focus = $this->cleanPromptValue($constraints['technology'] ?? '');

        return "Genere des defis Stabilis concis pour une plateforme de nutrition durable et de bien-etre. Return only valid JSON. No markdown. No extra text.\n" .
            "Return a JSON array with exactly {$count} objects.\n" .
            "Each object must use exactly the same fields as the defis database table: nom, type, objectif, recompense.\n" .
            "Do not return id. The database will auto-generate it.\n" .
            "All values must be written in French for a Tunisian/French-speaking website demo.\n" .
            "The type value must be exactly one of: aliment, entrainement, compensation.\n" .
            "Match the website domain: nutrition durable, alimentation saine, produits locaux/de saison, sport durable, reduction des dechets, compensation carbone, habitudes ecologiques quotidiennes.\n" .
            "Do not mention a university, school, class, teacher, or academic context.\n" .
            "Do not generate programming, code, SQL, API, algorithm, or software development exercises.\n" .
            "Write instructions for a regular platform user, not a developer.\n" .
            "objectif must describe the measurable completion condition, like the existing defis rows.\n" .
            "recompense must be a string like '100 points' between 50 and 200 points.\n" .
            "Keep each challenge practical, realistic, and easy for an admin to edit.\n" .
            "Sujet: {$topic}\n" .
            "Difficulte: {$difficulty}\n" .
            "Contrainte ou focus optionnel: " . ($focus !== '' ? $focus : 'aucun') . "\n";
    }

    private function buildWeeklyStoryPrompt(array $summary): string
    {
        $safeSummary = json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return "Tu es un assistant administratif pour Stabilis, une plateforme de defis autour de la nutrition durable, du bien-etre et des habitudes ecologiques. " .
            "A partir des donnees hebdomadaires fournies, redige un court recit professionnel, clair et engageant en francais. " .
            "Mentionne les meilleurs utilisateurs, les progres remarquables et l'activite generale. " .
            "Ne mentionne jamais une universite, un cadre universitaire, une ecole, une classe ou un enseignant. " .
            "Ne depasse pas 120 mots. Ne retourne que le texte final.\n" .
            "Donnees hebdomadaires resumees, sans base complete: {$safeSummary}\n";
    }

    private function buildProofReviewPrompt(array $participation): string
    {
        $challenge = [
            'nom' => $participation['defi_nom'] ?? '',
            'type' => $participation['defi_type'] ?? '',
            'objectif' => $participation['defi_objectif'] ?? '',
            'recompense' => $participation['defi_recompense'] ?? '',
            'progression_actuelle' => (int)($participation['progression'] ?? 0),
            'statut_actuel' => $participation['statut'] ?? '',
        ];
        $safeChallenge = json_encode($challenge, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return "Tu es un assistant de pre-revision pour Stabilis. Analyse la preuve fournie par rapport au defi. " .
            "Tu ne prends pas la decision finale: tu proposes seulement une aide a l administrateur. " .
            "Sois conservateur: si la preuve est floue, hors sujet, insuffisante ou impossible a verifier, retourne uncertain ou rejected. " .
            "Retourne uniquement un objet JSON valide avec exactement ces champs: " .
            "decision (approved, rejected ou uncertain), confidence (0-100), progress_increment (0-100), reason (court, en francais). " .
            "progress_increment represente l augmentation suggeree, pas la progression totale. " .
            "Ne suggere 100 que si la preuve montre clairement que l objectif complet est atteint. " .
            "Defi et participation: {$safeChallenge}";
    }

    private function normalizeProofReview(array $review): array
    {
        $decision = strtolower(trim((string)($review['decision'] ?? 'uncertain')));
        if (!in_array($decision, ['approved', 'rejected', 'uncertain'], true)) {
            $decision = 'uncertain';
        }

        $confidence = max(0, min(100, (int)($review['confidence'] ?? 0)));
        $progressIncrement = max(0, min(100, (int)($review['progress_increment'] ?? 0)));
        if ($decision !== 'approved' && $progressIncrement > 20) {
            $progressIncrement = 0;
        }

        $reason = trim((string)($review['reason'] ?? 'Analyse IA terminee.'));
        if ($reason === '') {
            $reason = 'Analyse IA terminee.';
        }

        return [
            'decision' => $decision,
            'confidence' => $confidence,
            'progress_increment' => $progressIncrement,
            'reason' => mb_substr($reason, 0, 1000),
        ];
    }

    private function cleanPromptValue(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('/\s+/', ' ', $value);
        return mb_substr($value, 0, 120);
    }

    private function postJson(string $url, array $payload, int $timeout = 25): array
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('L\'extension PHP cURL est requise pour appeler Gemini.');
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => $timeout,
        ]);

        $body = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($body === false || $error !== '') {
            throw new RuntimeException('La requete Gemini a echoue: ' . $error);
        }

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Gemini a retourne une reponse non JSON.');
        }

        if ($status < 200 || $status >= 300) {
            $message = $decoded['error']['message'] ?? 'La requete vers l\'API Gemini a echoue.';
            throw new RuntimeException($message);
        }

        return $decoded;
    }
}
