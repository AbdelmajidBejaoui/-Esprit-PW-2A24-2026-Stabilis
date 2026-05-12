<?php
require_once __DIR__ . '/Repository.php';

/**
 * GeneratedSessionRepository - Data access for AI-generated sessions
 */
class GeneratedSessionRepository extends Repository
{
    protected string $table = 'generated_sessions';

    /**
     * Save generated session
     */
    public function save(string $goal, string $niveau, array $exercises, float $totalCalories, string $prompt = ''): int
    {
        return $this->insert([
            'goal'           => $goal,
            'niveau'         => $niveau,
            'prompt'         => $prompt,
            'exercises_json' => json_encode($exercises),
            'total_calories' => $totalCalories,
        ]);
    }

    /**
     * Get recent generation history
     */
    public function getRecent(int $limit = 10): array
    {
        return $this->findAll('created_at DESC', $limit);
    }

    /**
     * Get recent generation history (alias for compatibility)
     */
    public function getHistory(int $limit = 10): array
    {
        return $this->getRecent($limit);
    }

    /**
     * Get user statistics
     */
    public function getStats(): array
    {
        return $this->queryOne(
            "SELECT 
                COUNT(*) as total_generated,
                AVG(total_calories) as avg_calories,
                SUM(total_calories) as total_calories
             FROM {$this->table}"
        ) ?? [
            'total_generated' => 0,
            'avg_calories' => 0,
            'total_calories' => 0
        ];
    }
}

