<?php
require_once __DIR__ . '/../config/entrainements.php';
require_once __DIR__ . '/../Services/WorkoutGeneratorService.php';
require_once __DIR__ . '/../Services/GeminiClient.php';
require_once __DIR__ . '/../Services/WorkoutPromptBuilder.php';
require_once __DIR__ . '/../Services/AIWorkoutGenerator.php';
require_once __DIR__ . '/../Models/GeneratedSessionRepository.php';
require_once __DIR__ . '/EntrainementC.php';

/**
 * AIGeneratorC — Clean AI Workout Generator Controller (REFACTORED)
 *
 * Responsibilities:
 * - Validate user input
 * - Orchestrate services (no business logic here)
 * - Format responses
 * - Handle errors
 *
 * Business logic delegated to:
 * - AIWorkoutGenerator (AI-based generation)
 * - GeneratedSessionRepository (persistence)
 */
class AIGeneratorC
{
    private ?AIWorkoutGenerator $aiGenerator = null;
    private GeneratedSessionRepository $repository;
    private float $userWeight;

    public function __construct(float $userWeight = 70.0)
    {
        $this->repository = new GeneratedSessionRepository();
        $this->userWeight = $userWeight;
        
        // Initialize AI generator if API key is configured
        if ($this->isAIConfigured()) {
            try {
                $client = new GeminiClient(GEMINI_API_KEY);
                $this->aiGenerator = new AIWorkoutGenerator($client);
            } catch (Exception $e) {
                // AI not available
                error_log("AI Generator initialization failed: " . $e->getMessage());
            }
        }
    }

    /**
     * Main generation method - AI-based only
     */
    public function generer(string $goal, string $niveau, ?array $customPool = null, ?int $nbExercises = null): array
    {
        // Default prompt when no custom prompt provided
        $defaultPrompt = "Créer une séance d'entraînement complète";
        return $this->generate($goal, $niveau, $defaultPrompt);
    }

    /**
     * Generate with custom prompt (AI-based)
     */
    public function genererAvecPrompt(string $goal, string $niveau, string $customPrompt = ''): array
    {
        return $this->generate($goal, $niveau, $customPrompt);
    }

    /**
     * Unified generation method - AI only
     */
    private function generate(string $goal, string $niveau, string $customPrompt = ''): array
    {
        // Validate input
        $errors = $this->validate($goal, $niveau, $customPrompt);
        if (!empty($errors)) {
            return ['error' => implode(', ', $errors)];
        }

        // Generate with AI
        $result = $this->generateWithAI($goal, $niveau, $customPrompt);

        // Handle errors
        if (isset($result['error'])) {
            return $result;
        }

        // Persist to database
        $this->saveGeneration($result, $customPrompt);

        return $result;
    }

    /**
     * Generate workout using AI
     */
    private function generateWithAI(string $goal, string $niveau, string $prompt): array
    {
        if (!$this->aiGenerator) {
            $fallback = $this->generateLocalFallback($goal, $niveau);
            if ($fallback) {
                $fallback['warning'] = 'Gemini n est pas configure. Une seance locale a ete generee depuis le catalogue.';
                return $fallback;
            }

            return ['error' => 'Configuration API Gemini manquante et catalogue local indisponible.'];
        }

        try {
            return $this->aiGenerator->generate($goal, $niveau, $prompt);
        } catch (Exception $e) {
            error_log("AI Generation failed: " . $e->getMessage());

            $fallback = $this->generateLocalFallback($goal, $niveau);
            if ($fallback) {
                $fallback['warning'] = 'Gemini est temporairement indisponible. Une seance locale a ete generee depuis le catalogue.';
                return $fallback;
            }

            return ['error' => $this->formatGenerationError($e)];
        }
    }

    /**
     * Generate a usable session from the local catalogue when Gemini is busy
     * or returns malformed JSON.
     */
    private function generateLocalFallback(string $goal, string $niveau): ?array
    {
        try {
            $catalogue = (new EntrainementC())->listCatalogue();
            $pool = array_values(array_filter(array_map([$this, 'mapCatalogueExercise'], $catalogue)));
            if (empty($pool)) {
                $pool = $this->defaultExercisePool();
            }
        } catch (Exception $e) {
            error_log("Local workout fallback failed: " . $e->getMessage());
            $pool = $this->defaultExercisePool();
        }

        $result = (new WorkoutGeneratorService())->generer($goal, $niveau, $pool, $this->userWeight);
        $result['ai_generated'] = false;
        $result['source'] = 'local_fallback';
        $result['description_rule'] = ($result['description_rule'] ?? 'Seance locale') . ' (mode secours)';
        return $result;
    }

    private function mapCatalogueExercise(array $row): ?array
    {
        $name = trim((string)($row['nom'] ?? ''));
        if ($name === '') {
            return null;
        }

        return [
            'id' => (int)($row['id'] ?? 0),
            'name' => $name,
            'category' => $this->normalizeCategory((string)($row['type_sport'] ?? 'force')),
            'met_value' => (float)($row['met_value'] ?? 5.0),
            'description' => (string)($row['description'] ?? ''),
            'niveau' => (string)($row['niveau'] ?? 'intermediaire'),
        ];
    }

    private function normalizeCategory(string $typeSport): string
    {
        $value = strtolower(trim($typeSport));

        if (strpos($value, 'cardio') !== false || strpos($value, 'course') !== false) {
            return 'cardio';
        }
        if (strpos($value, 'hiit') !== false || strpos($value, 'interval') !== false) {
            return 'hiit';
        }
        if (strpos($value, 'endurance') !== false) {
            return 'endurance';
        }
        if (strpos($value, 'souplesse') !== false || strpos($value, 'flex') !== false || strpos($value, 'yoga') !== false) {
            return 'flexibilite';
        }

        return 'force';
    }

    private function formatGenerationError(Exception $e): string
    {
        $message = $e->getMessage();

        if (stripos($message, '503') !== false || stripos($message, 'high demand') !== false) {
            return 'Gemini est temporairement sature. Reessayez dans quelques instants.';
        }

        if (stripos($message, '429') !== false) {
            return 'Trop de requetes Gemini pour le moment. Reessayez dans quelques instants.';
        }

        if (stripos($message, 'Invalid JSON') !== false || stripos($message, 'JSON response') !== false) {
            return 'Gemini a renvoye une reponse JSON invalide. Reessayez avec une demande plus courte.';
        }

        return 'Erreur de generation: ' . $message;
    }

    private function defaultExercisePool(): array
    {
        return [
            ['id' => 0, 'name' => 'Squats au poids du corps', 'category' => 'force', 'met_value' => 5.0, 'description' => 'Descendez les hanches vers l arriere, gardez le dos droit, puis remontez en poussant dans les talons.', 'niveau' => 'debutant'],
            ['id' => 0, 'name' => 'Pompes adaptees', 'category' => 'force', 'met_value' => 5.5, 'description' => 'Gardez le corps gaine et descendez la poitrine avec controle. Posez les genoux si necessaire.', 'niveau' => 'debutant'],
            ['id' => 0, 'name' => 'Fentes alternees', 'category' => 'force', 'met_value' => 5.5, 'description' => 'Avancez une jambe, pliez les deux genoux, puis revenez en position debout en alternant.', 'niveau' => 'intermediaire'],
            ['id' => 0, 'name' => 'Gainage planche', 'category' => 'force', 'met_value' => 4.0, 'description' => 'Alignez epaules, bassin et chevilles. Gardez le ventre serre et respirez regulierement.', 'niveau' => 'debutant'],
            ['id' => 0, 'name' => 'Mountain climbers', 'category' => 'hiit', 'met_value' => 8.0, 'description' => 'En position de planche, ramenez les genoux vers la poitrine en alternant rapidement.', 'niveau' => 'intermediaire'],
            ['id' => 0, 'name' => 'Burpees controles', 'category' => 'hiit', 'met_value' => 10.0, 'description' => 'Descendez au sol, passez en planche, revenez debout et ajoutez un petit saut si possible.', 'niveau' => 'avance'],
            ['id' => 0, 'name' => 'Jumping jacks', 'category' => 'cardio', 'met_value' => 7.0, 'description' => 'Ouvrez bras et jambes en sautant, puis revenez au centre avec un rythme regulier.', 'niveau' => 'debutant'],
            ['id' => 0, 'name' => 'Course sur place', 'category' => 'cardio', 'met_value' => 7.5, 'description' => 'Montez legerement les genoux, restez souple sur les appuis et gardez une respiration fluide.', 'niveau' => 'debutant'],
            ['id' => 0, 'name' => 'Step-ups', 'category' => 'endurance', 'met_value' => 6.0, 'description' => 'Montez sur une marche stable, redescendez avec controle et alternez la jambe de depart.', 'niveau' => 'intermediaire'],
            ['id' => 0, 'name' => 'Corde a sauter imaginaire', 'category' => 'endurance', 'met_value' => 8.0, 'description' => 'Faites de petits rebonds rapides en simulant la corde avec les poignets.', 'niveau' => 'intermediaire'],
        ];
    }

    /**
     * Save generation to database
     */
    private function saveGeneration(array $result, string $prompt = ''): void
    {
        try {
            $this->repository->save(
                $result['goal'],
                $result['niveau'],
                $result['exercises'],
                $result['total_calories'],
                $prompt
            );
        } catch (Exception $e) {
            // Log error but don't fail the request
            error_log("Failed to save generation: " . $e->getMessage());
        }
    }

    /**
     * Get generation history
     */
    public function getHistory(int $limit = 10): array
    {
        try {
            return $this->repository->getHistory($limit);
        } catch (Exception $e) {
            error_log("Failed to get history: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Validate generation parameters
     */
    private function validate(string $goal, string $niveau, string $customPrompt): array
    {
        $errors = [];

        // Validate goal and niveau
        $validationErrors = WorkoutGeneratorService::valider($goal, $niveau);
        $errors = array_merge($errors, $validationErrors);

        // Validate custom prompt length if provided
        if (!empty($customPrompt) && strlen($customPrompt) > 500) {
            $errors[] = 'Le prompt personnalisé ne peut pas dépasser 500 caractères.';
        }

        return $errors;
    }

    /**
     * Check if AI is configured
     */
    private function isAIConfigured(): bool
    {
        return defined('GEMINI_API_KEY') 
            && !empty(GEMINI_API_KEY) 
            && GEMINI_API_KEY !== 'votre_cle_api_ici';
    }

    // ── Static helper methods (for backward compatibility) ────────────────────

    public static function goalLabel(string $g): string
    {
        return WorkoutGeneratorService::goalLabel($g);
    }

    public static function niveauLabel(string $n): string
    {
        return WorkoutGeneratorService::niveauLabel($n);
    }

    public static function getRules(): array
    {
        return WorkoutGeneratorService::getRules();
    }
}

