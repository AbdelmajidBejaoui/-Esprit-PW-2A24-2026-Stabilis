<?php

class DefiGeminiService
{
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $config = require __DIR__ . '/../config/mail.php';
        $this->apiKey = trim((string)($config['gemini_api_key'] ?? getenv('GEMINI_API_KEY') ?: ''));
        $this->model = trim((string)(getenv('GEMINI_MODEL') ?: 'gemini-2.5-flash'));
    }

    public function isConfigured(): bool
    {
        return $this->apiKey !== '';
    }

    public function generateChallenges(array $constraints): array
    {
        $count = (int)($constraints['count'] ?? 3);
        if ($count < 1 || $count > 6) {
            throw new InvalidArgumentException('Le nombre de defis doit etre entre 1 et 6.');
        }
        $topic = $this->clean($constraints['topic'] ?? 'nutrition durable');
        $difficulty = $this->clean($constraints['difficulty'] ?? 'moyen');

        if ($topic === '') {
            throw new InvalidArgumentException('Le sujet est obligatoire.');
        }

        if ($difficulty === '') {
            throw new InvalidArgumentException('La difficulte est obligatoire.');
        }

        $prompt = "Genere exactement {$count} defis Stabilis en francais pour nutrition durable, sport, habitudes ecologiques. " .
            "Retourne uniquement un tableau JSON. Champs exacts: nom, type, objectif, recompense. " .
            "type doit etre aliment, entrainement ou compensation. recompense doit etre entre 50 et 200 points. " .
            "Sujet: {$topic}. Difficulte: {$difficulty}.";

        $json = $this->generateJson($prompt, 1400);
        if (!is_array($json)) {
            throw new RuntimeException('Gemini n a pas retourne une liste valide.');
        }

        return array_values(array_filter(array_map([$this, 'normalizeChallenge'], $json)));
    }

    public function generateWeeklyStory(array $summary): string
    {
        $safeSummary = json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $basePrompt = "Tu es un assistant administratif pour Stabilis, une plateforme de defis autour de la nutrition durable, du bien-etre et des habitudes ecologiques. " .
            "Redige en francais un recit professionnel, clair et engageant a partir des donnees hebdomadaires. " .
            "Le texte doit faire entre 70 et 120 mots, en un seul paragraphe complet. " .
            "Mentionne les meilleurs utilisateurs, les progres remarquables et l'activite generale. " .
            "Ne mentionne jamais une universite, une ecole, une classe ou un enseignant. " .
            "Ne retourne que le texte final, sans titre, sans liste, sans markdown.\n" .
            "Donnees hebdomadaires resumees: {$safeSummary}";

        $story = $this->requestWeeklyStory($basePrompt);
        if ($this->isTooShortStory($story)) {
            $retryPrompt = $basePrompt . "\nImportant: ta reponse precedente etait incomplete. Retourne maintenant un paragraphe complet de 80 a 110 mots, avec une phrase de conclusion.";
            $story = $this->requestWeeklyStory($retryPrompt);
        }

        if ($this->isTooShortStory($story)) {
            return $this->buildFallbackWeeklyStory($summary);
        }

        return mb_substr($story, 0, 1200);
    }

    private function requestWeeklyStory(string $prompt): string
    {
        $payload = [
            'contents' => [[
                'role' => 'user',
                'parts' => [['text' => $prompt]],
            ]],
            'generationConfig' => [
                'temperature' => 0.35,
                'topP' => 0.9,
                'maxOutputTokens' => 420,
            ],
        ];

        $response = $this->post($payload, 30);
        $story = trim((string)($response['candidates'][0]['content']['parts'][0]['text'] ?? ''));
        if ($story === '') {
            throw new RuntimeException('Gemini n a pas retourne de recit.');
        }

        return preg_replace('/\s+/', ' ', $story);
    }

    private function isTooShortStory(string $story): bool
    {
        return str_word_count($story) < 35 || !preg_match('/[.!?]$/', trim($story));
    }

    private function buildFallbackWeeklyStory(array $summary): string
    {
        $stats = $summary['statistiques_hebdomadaires'] ?? [];
        $best = $summary['meilleur_utilisateur']['nom'] ?? 'la communaute';
        $active = $summary['utilisateur_le_plus_actif']['nom'] ?? $best;
        $points = (int)($stats['points_distribues'] ?? 0);
        $participations = (int)($stats['participations'] ?? 0);
        $completed = (int)($stats['defis_termines'] ?? 0);
        $users = (int)($stats['utilisateurs_actifs'] ?? 0);

        return "Cette semaine, l'activite Defis Stabilis montre une dynamique encourageante avec {$users} utilisateur(s) actif(s), {$participations} participation(s) et {$completed} defi(s) termine(s). {$best} se distingue dans le classement, tandis que {$active} confirme une belle regularite dans les actions suivies. Au total, {$points} point(s) ont ete distribue(s), ce qui traduit une progression concrete autour de la nutrition durable, du bien-etre et des habitudes responsables. La communaute garde un rythme positif pour la suite.";
    }

    public function reviewProof(array $participation, string $absoluteFilePath, string $mimeType): array
    {
        if (!is_file($absoluteFilePath) || !is_readable($absoluteFilePath)) {
            throw new RuntimeException('Fichier de preuve introuvable.');
        }

        if (filesize($absoluteFilePath) > 18 * 1024 * 1024) {
            return [
                'decision' => 'uncertain',
                'confidence' => 0,
                'progress_increment' => 0,
                'reason' => 'Fichier trop volumineux pour analyse IA automatique.',
            ];
        }

        $challenge = json_encode([
            'nom' => $participation['defi_nom'] ?? '',
            'type' => $participation['defi_type'] ?? '',
            'objectif' => $participation['defi_objectif'] ?? '',
            'progression_actuelle' => (int)($participation['progression'] ?? 0),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $prompt = "Analyse cette preuve pour le defi Stabilis suivant: {$challenge}. " .
            "Retourne uniquement JSON avec decision approved/rejected/uncertain, confidence 0-100, progress_increment 0-100, reason court en francais. " .
            "Sois conservateur si la preuve est floue ou hors sujet.";

        $payload = [
            'contents' => [[
                'role' => 'user',
                'parts' => [
                    ['text' => $prompt],
                    ['inlineData' => ['mimeType' => $mimeType, 'data' => base64_encode(file_get_contents($absoluteFilePath))]],
                ],
            ]],
            'generationConfig' => [
                'temperature' => 0.15,
                'maxOutputTokens' => 450,
                'responseMimeType' => 'application/json',
            ],
        ];

        $response = $this->post($payload, 75);
        $text = $response['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $review = $this->decodeJsonObject((string)$text);
        if (!$review) {
            return [
                'decision' => 'uncertain',
                'confidence' => 0,
                'progress_increment' => 0,
                'reason' => 'Gemini n a pas retourne une analyse JSON exploitable.',
            ];
        }
        return $this->normalizeReview(is_array($review) ? $review : []);
    }

    private function generateJson(string $prompt, int $maxTokens): array
    {
        $payload = [
            'contents' => [[
                'role' => 'user',
                'parts' => [['text' => $prompt]],
            ]],
            'generationConfig' => [
                'temperature' => 0.35,
                'maxOutputTokens' => $maxTokens,
                'responseMimeType' => 'application/json',
            ],
        ];
        $response = $this->post($payload);
        $text = (string)($response['candidates'][0]['content']['parts'][0]['text'] ?? '[]');
        return json_decode($text, true) ?: [];
    }

    private function post(array $payload, int $timeout = 25): array
    {
        if (!$this->isConfigured()) {
            throw new RuntimeException('Cle Gemini non configuree.');
        }
        if (!function_exists('curl_init')) {
            throw new RuntimeException('Extension cURL requise.');
        }

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($this->model) . ':generateContent?key=' . rawurlencode($this->apiKey);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => $timeout,
        ]);
        $body = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        if ($body === false || $error !== '') {
            throw new RuntimeException('Requete Gemini echouee: ' . $error);
        }
        $decoded = json_decode($body, true);
        if (!is_array($decoded) || $status < 200 || $status >= 300) {
            throw new RuntimeException($decoded['error']['message'] ?? 'Reponse Gemini invalide.');
        }
        return $decoded;
    }

    private function normalizeChallenge(array $challenge): array
    {
        $type = strtolower(trim((string)($challenge['type'] ?? 'aliment')));
        if (!in_array($type, ['aliment', 'entrainement', 'compensation'], true)) {
            $type = 'aliment';
        }
        $reward = trim((string)($challenge['recompense'] ?? '100 points'));
        if ($reward !== '' && !preg_match('/points?/i', $reward)) {
            $reward .= ' points';
        }
        return [
            'nom' => mb_substr(trim((string)($challenge['nom'] ?? '')), 0, 100),
            'type' => $type,
            'objectif' => trim((string)($challenge['objectif'] ?? '')),
            'recompense' => mb_substr($reward, 0, 50),
        ];
    }

    private function normalizeReview(array $review): array
    {
        $decision = strtolower(trim((string)($review['decision'] ?? 'uncertain')));
        if (!in_array($decision, ['approved', 'rejected', 'uncertain'], true)) {
            $decision = 'uncertain';
        }
        return [
            'decision' => $decision,
            'confidence' => max(0, min(100, (int)($review['confidence'] ?? 0))),
            'progress_increment' => $decision === 'approved' ? max(0, min(100, (int)($review['progress_increment'] ?? 0))) : 0,
            'reason' => mb_substr(trim((string)($review['reason'] ?? 'Analyse terminee.')), 0, 1000),
        ];
    }

    private function decodeJsonObject(string $text): array
    {
        $text = trim($text);
        $decoded = json_decode($text, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{.*\}/s', $text, $match)) {
            $decoded = json_decode($match[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    private function clean(string $value): string
    {
        return mb_substr(preg_replace('/\s+/', ' ', trim($value)), 0, 120);
    }
}
