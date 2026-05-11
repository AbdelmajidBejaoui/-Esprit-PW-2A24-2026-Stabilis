<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Service/WorkoutGeneratorService.php';
require_once __DIR__ . '/../Service/AI/GeminiClient.php';
require_once __DIR__ . '/../Service/AI/WorkoutPromptBuilder.php';
require_once __DIR__ . '/../Service/AI/AIWorkoutGenerator.php';
require_once __DIR__ . '/../Repository/GeneratedSessionRepository.php';

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
            return [
                'error' => 'Configuration API manquante. '
                         . 'Veuillez ajouter votre clé API Gemini dans config.php '
                         . '(GRATUIT sur aistudio.google.com)'
            ];
        }

        try {
            return $this->aiGenerator->generate($goal, $niveau, $prompt);
        } catch (Exception $e) {
            error_log("AI Generation failed: " . $e->getMessage());
            return ['error' => 'Erreur de génération: ' . $e->getMessage()];
        }
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
