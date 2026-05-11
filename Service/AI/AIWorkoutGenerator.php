<?php
require_once __DIR__ . '/GeminiClient.php';
require_once __DIR__ . '/WorkoutPromptBuilder.php';
require_once __DIR__ . '/../CalorieService.php';

/**
 * AIWorkoutGenerator - Generates workouts using AI
 * 
 * High-level service that orchestrates AI workout generation.
 * Handles prompt building, API calls, and response processing.
 */
class AIWorkoutGenerator
{
    private GeminiClient $client;

    public function __construct(GeminiClient $client)
    {
        $this->client = $client;
    }

    /**
     * Generate workout from user prompt
     */
    public function generate(string $goal, string $niveau, string $userPrompt): array
    {
        // Build AI prompt
        $prompt = WorkoutPromptBuilder::build($goal, $niveau, $userPrompt);

        // Call AI API
        try {
            $aiResponse = $this->client->generateWorkout($prompt);
        } catch (Exception $e) {
            throw new RuntimeException(
                "Failed to generate workout: " . $e->getMessage()
            );
        }

        // Validate response
        if (!isset($aiResponse['exercises']) || empty($aiResponse['exercises'])) {
            throw new RuntimeException(
                "Invalid AI response: missing exercises"
            );
        }

        // Process and enrich response
        return $this->processResponse($aiResponse, $goal, $niveau);
    }

    /**
     * Process AI response and add metadata
     */
    private function processResponse(array $aiResponse, string $goal, string $niveau): array
    {
        $exercises = $aiResponse['exercises'];
        $totalCalories = 0;

        // Enrich exercises with missing fields
        foreach ($exercises as &$exercise) {
            $totalCalories += $exercise['calories'] ?? 0;
            
            // Add default values if missing
            $exercise['niveau'] = $exercise['niveau'] ?? $niveau;
            $exercise['id'] = $exercise['id'] ?? 0; // Fictitious ID for AI-generated
        }

        // Calculate duration if not provided
        $duration = $aiResponse['duree_estimee'] 
                 ?? CalorieService::dureeTotal($exercises);

        // Build final result
        return [
            'goal'              => $goal,
            'niveau'            => $niveau,
            'description_rule'  => $aiResponse['description_rule'] 
                                ?? 'Séance personnalisée selon votre demande',
            'exercises'         => $exercises,
            'total_calories'    => round($totalCalories, 1),
            'nb_exercises'      => count($exercises),
            'duree_estimee'     => $duration,
            'categories_cibles' => array_unique(array_column($exercises, 'category')),
            'interpretation'    => CalorieService::interpreterCalories($totalCalories),
            'ai_generated'      => true,
        ];
    }

    /**
     * Validate generation parameters
     */
    public static function validate(string $goal, string $niveau): array
    {
        $errors = [];

        $validGoals = ['perte_graisse', 'prise_muscle', 'endurance'];
        if (!in_array($goal, $validGoals)) {
            $errors[] = 'Objectif invalide. Valeurs acceptées: ' . implode(', ', $validGoals);
        }

        $validNiveaux = ['debutant', 'intermediaire', 'avance'];
        if (!in_array($niveau, $validNiveaux)) {
            $errors[] = 'Niveau invalide. Valeurs acceptées: ' . implode(', ', $validNiveaux);
        }

        return $errors;
    }
}
