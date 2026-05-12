<?php

class NutritionAiService
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
        return $this->apiKey !== '' && function_exists('curl_init');
    }

    public function improveRecipe(array $recipe, array $ingredients, array $totals): string
    {
        if (!$this->isConfigured()) {
            return $this->fallbackImprove($recipe, $totals);
        }

        $payload = [
            'contents' => [[
                'role' => 'user',
                'parts' => [[
                    'text' => "Tu es nutritionniste pour Stabilis. Propose une amelioration courte en francais pour cette recette. Retourne 4 phrases maximum, concret, sans markdown.\nRecette: " . json_encode([
                        'recette' => $recipe,
                        'ingredients' => $ingredients,
                        'totaux' => $totals,
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]],
            ]],
            'generationConfig' => ['temperature' => 0.35, 'maxOutputTokens' => 420],
        ];

        return $this->textRequest($payload) ?: $this->fallbackImprove($recipe, $totals);
    }

    public function generateRecipe(string $objective, array $aliments): string
    {
        $names = array_map(fn($a) => $a['nom'] . ' (' . $a['calories'] . ' kcal, ' . $a['proteines'] . 'g prot.)', array_slice($aliments, 0, 18));
        if (!$this->isConfigured()) {
            return $this->fallbackGenerateRecipe($objective, $aliments);
        }

        $payload = [
            'contents' => [[
                'role' => 'user',
                'parts' => [[
                    'text' => "Cree une recette Stabilis en francais pour l'objectif: {$objective}. Utilise seulement ces aliments si possible: " . implode('; ', $names) . ". Donne un nom, ingredients avec quantites, et instructions courtes. Pas de markdown.",
                ]],
            ]],
            'generationConfig' => ['temperature' => 0.55, 'maxOutputTokens' => 700],
        ];

        return $this->textRequest($payload) ?: $this->fallbackGenerateRecipe($objective, $aliments);
    }

    public function analyzeFoodPhoto(string $path, string $mime): string
    {
        $estimate = $this->estimateFoodPhotoCalories($path, $mime);
        if (!empty($estimate['summary'])) {
            $lines = [$estimate['summary']];
            foreach (($estimate['items'] ?? []) as $item) {
                $lines[] = '- ' . ($item['name'] ?? 'Aliment') . ': ' . (int)($item['calories'] ?? 0) . ' kcal';
            }
            $lines[] = 'Total estime: ' . (int)($estimate['totals']['calories'] ?? 0) . ' kcal';
            return implode("\n", $lines);
        }

        return 'Analyse photo indisponible. Verifiez la cle IA et la connexion internet.';
    }

    public function estimateFoodPhotoCalories(string $path, string $mime): array
    {
        if (!$this->isConfigured() || !is_file($path)) {
            return $this->emptyEstimate('Analyse photo indisponible.');
        }

        $payload = [
            'contents' => [[
                'role' => 'user',
                'parts' => [
                    ['text' => 'Analyse cette photo alimentaire pour Stabilis et estime les calories. Retourne uniquement un objet JSON valide avec cette structure exacte: {"summary":"phrase courte en francais","confidence":0-100,"items":[{"name":"aliment","portion":"portion estimee","calories":0,"proteins":0,"carbs":0,"fats":0}],"totals":{"calories":0,"proteins":0,"carbs":0,"fats":0},"advice":"conseil court"}. Si la portion est incertaine, fais une estimation prudente et baisse confidence.'],
                    ['inlineData' => ['mimeType' => $mime, 'data' => base64_encode(file_get_contents($path))]],
                ],
            ]],
            'generationConfig' => [
                'temperature' => 0.2,
                'maxOutputTokens' => 760,
                'responseMimeType' => 'application/json',
            ],
        ];

        $text = $this->textRequest($payload, 70);
        $data = $this->decodeJsonObject($text);
        if (!$data) {
            return $this->emptyEstimate('La photo n a pas pu etre analysee.');
        }

        return $this->normalizeEstimate($data);
    }

    private function textRequest(array $payload, int $timeout = 35): string
    {
        try {
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
            if ($body === false || $error !== '' || $status < 200 || $status >= 300) {
                error_log('Nutrition AI failed: HTTP ' . $status . ' ' . $error . ' ' . substr((string)$body, 0, 300));
                return '';
            }
            $json = json_decode($body, true);
            return trim((string)($json['candidates'][0]['content']['parts'][0]['text'] ?? ''));
        } catch (Throwable $e) {
            error_log('Nutrition AI exception: ' . $e->getMessage());
            return '';
        }
    }

    private function fallbackImprove(array $recipe, array $totals): string
    {
        $tips = [];
        if (($totals['proteines'] ?? 0) < 20) {
            $tips[] = 'ajouter une source de proteines';
        }
        if (($totals['lipides'] ?? 0) > 25) {
            $tips[] = 'reduire les ingredients tres gras';
        }
        if (($totals['calories'] ?? 0) > 650) {
            $tips[] = 'alleger les portions caloriques';
        }
        if (!$tips) {
            $tips[] = 'garder cette base et renforcer les legumes';
        }
        return 'Amelioration proposee pour ' . ($recipe['nom'] ?? 'la recette') . ': ' . implode(', ', $tips) . '.';
    }

    private function fallbackGenerateRecipe(string $objective, array $aliments): string
    {
        if (!$aliments) {
            return 'Ajoutez quelques aliments dans la base nutritionnelle pour generer une recette.';
        }

        $picked = array_slice($aliments, 0, 4);
        $names = array_values(array_filter(array_map(fn($a) => trim((string)($a['nom'] ?? '')), $picked)));
        $objectiveLabels = [
            'musculation' => 'riche en proteines',
            'regime' => 'legere et rassasiante',
            'equilibre' => 'equilibree',
        ];
        $style = $objectiveLabels[$objective] ?? 'equilibree';
        $titleBase = $names ? implode(', ', array_slice($names, 0, 2)) : 'assiette Stabilis';

        $lines = [
            'Recette Stabilis ' . $style . ': bol ' . $titleBase . '.',
            '',
            'Ingredients:',
        ];

        foreach ($picked as $index => $aliment) {
            $quantity = $index === 0 ? '150 g' : ($index === 1 ? '120 g' : '80 g');
            $lines[] = '- ' . $quantity . ' de ' . trim((string)($aliment['nom'] ?? 'aliment'));
        }

        if ($objective === 'musculation') {
            $lines[] = '- 1 filet d huile olive ou une sauce legere selon besoin';
        } elseif ($objective === 'regime') {
            $lines[] = '- Herbes, citron, epices et peu de matiere grasse';
        } else {
            $lines[] = '- Assaisonnement simple: citron, herbes, poivre';
        }

        $lines[] = '';
        $lines[] = 'Instructions: cuire les ingredients qui le demandent, assembler dans un bol, assaisonner simplement et ajuster la portion selon votre faim.';
        $lines[] = 'Note: recette locale generee automatiquement car le service IA est temporairement limite.';

        return implode("\n", $lines);
    }

    private function decodeJsonObject(string $text): array
    {
        $decoded = json_decode(trim($text), true);
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

    private function normalizeEstimate(array $data): array
    {
        $items = [];
        foreach (($data['items'] ?? []) as $item) {
            if (!is_array($item)) {
                continue;
            }
            $items[] = [
                'name' => mb_substr(trim((string)($item['name'] ?? 'Aliment')), 0, 80),
                'portion' => mb_substr(trim((string)($item['portion'] ?? 'portion estimee')), 0, 80),
                'calories' => max(0, (int)($item['calories'] ?? 0)),
                'proteins' => max(0, round((float)($item['proteins'] ?? 0), 1)),
                'carbs' => max(0, round((float)($item['carbs'] ?? 0), 1)),
                'fats' => max(0, round((float)($item['fats'] ?? 0), 1)),
            ];
        }

        $totals = $data['totals'] ?? [];
        if (!$totals || (int)($totals['calories'] ?? 0) <= 0) {
            $totals = [
                'calories' => array_sum(array_column($items, 'calories')),
                'proteins' => array_sum(array_column($items, 'proteins')),
                'carbs' => array_sum(array_column($items, 'carbs')),
                'fats' => array_sum(array_column($items, 'fats')),
            ];
        }

        return [
            'summary' => mb_substr(trim((string)($data['summary'] ?? 'Estimation nutritionnelle de la photo.')), 0, 220),
            'confidence' => max(0, min(100, (int)($data['confidence'] ?? 45))),
            'items' => $items,
            'totals' => [
                'calories' => max(0, (int)($totals['calories'] ?? 0)),
                'proteins' => max(0, round((float)($totals['proteins'] ?? 0), 1)),
                'carbs' => max(0, round((float)($totals['carbs'] ?? 0), 1)),
                'fats' => max(0, round((float)($totals['fats'] ?? 0), 1)),
            ],
            'advice' => mb_substr(trim((string)($data['advice'] ?? 'Ajustez selon la portion reelle.')), 0, 240),
        ];
    }

    private function emptyEstimate(string $message): array
    {
        return [
            'summary' => $message,
            'confidence' => 0,
            'items' => [],
            'totals' => ['calories' => 0, 'proteins' => 0, 'carbs' => 0, 'fats' => 0],
            'advice' => '',
        ];
    }
}
