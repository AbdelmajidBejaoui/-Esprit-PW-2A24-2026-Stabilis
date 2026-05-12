<?php
require_once __DIR__ . '/CalorieService.php';

/**
 * WorkoutGeneratorService — Métier avancé : Génération intelligente de séances
 *
 * Responsabilité unique : construire une séance optimisée selon :
 *   - l'objectif (goal)
 *   - le niveau (niveau)
 *   - les catégories d'exercices prioritaires
 *
 * Ce service est découplé de la base de données :
 * il reçoit un pool d'exercices et retourne un plan structuré.
 * La persistance reste dans AIGeneratorC (contrôleur).
 */
class WorkoutGeneratorService
{
    // ── Table de règles métier par objectif × niveau ──────────────────────────
    // Chaque règle définit l'architecture de la séance.
    private const RULES = [
        'perte_graisse' => [
            'debutant'      => [
                'sets' => 3, 'reps' => 15, 'rest' => 30,
                'nb_exercises' => 5,
                'categories'   => ['cardio', 'hiit'],
                'description'  => 'Cardio accessible, brûlage progressif',
            ],
            'intermediaire' => [
                'sets' => 4, 'reps' => 20, 'rest' => 20,
                'nb_exercises' => 6,
                'categories'   => ['hiit', 'cardio'],
                'description'  => 'HIIT et cardio combinés, haute dépense',
            ],
            'avance'        => [
                'sets' => 5, 'reps' => 25, 'rest' => 15,
                'nb_exercises' => 7,
                'categories'   => ['hiit', 'cardio', 'force'],
                'description'  => 'Circuit metabolique explosif',
            ],
        ],
        'prise_muscle' => [
            'debutant'      => [
                'sets' => 3, 'reps' => 12, 'rest' => 60,
                'nb_exercises' => 5,
                'categories'   => ['force'],
                'description'  => 'Apprentissage technique, charges légères',
            ],
            'intermediaire' => [
                'sets' => 4, 'reps' => 8, 'rest' => 75,
                'nb_exercises' => 6,
                'categories'   => ['force'],
                'description'  => 'Hypertrophie, charges modérées à lourdes',
            ],
            'avance'        => [
                'sets' => 5, 'reps' => 5, 'rest' => 120,
                'nb_exercises' => 6,
                'categories'   => ['force'],
                'description'  => 'Force maximale, charges lourdes 5×5',
            ],
        ],
        'endurance' => [
            'debutant'      => [
                'sets' => 3, 'reps' => 20, 'rest' => 45,
                'nb_exercises' => 5,
                'categories'   => ['endurance', 'cardio'],
                'description'  => 'Amélioration du souffle, effort continu',
            ],
            'intermediaire' => [
                'sets' => 4, 'reps' => 25, 'rest' => 30,
                'nb_exercises' => 6,
                'categories'   => ['endurance', 'cardio', 'hiit'],
                'description'  => 'VMA et résistance cardiovasculaire',
            ],
            'avance'        => [
                'sets' => 5, 'reps' => 30, 'rest' => 20,
                'nb_exercises' => 7,
                'categories'   => ['endurance', 'hiit'],
                'description'  => 'Haute intensité, intervalle long',
            ],
        ],
    ];

    /**
     * Génère une séance structurée avec randomisation contrôlée.
     *
     * @param string $goal   'perte_graisse' | 'prise_muscle' | 'endurance'
     * @param string $niveau 'debutant' | 'intermediaire' | 'avance'
     * @param array  $pool   Tableau d'exercices (raw DB rows)
     * @param float  $poids  Poids utilisateur en kg (pour le calcul calorique)
     * @param int|null $nbExercisesOverride Nombre d'exercices personnalisé (optionnel)
     * @return array Séance complète avec méta-données
     */
    public function generer(string $goal, string $niveau, array $pool, float $poids = 70.0, ?int $nbExercisesOverride = null): array
    {
        $rules = self::RULES[$goal][$niveau] ?? self::RULES['perte_graisse']['debutant'];
        
        // Utiliser le nombre d'exercices personnalisé si fourni
        $nbExercises = $nbExercisesOverride ?? $rules['nb_exercises'];
        $nbExercises = max(1, min($nbExercises, 15)); // Limiter entre 1 et 15

        // ── Sélection intelligente : priorité aux catégories cibles ──────────
        $preferred = array_values(array_filter($pool, fn($e) => in_array($e['category'], $rules['categories'])));
        $others    = array_values(array_filter($pool, fn($e) => !in_array($e['category'], $rules['categories'])));

        shuffle($preferred);
        shuffle($others);

        // Merge : catégories prioritaires en tête, complément si insuffisant
        $merged   = array_merge($preferred, $others);
        $selected = array_slice($merged, 0, $nbExercises);

        // ── Construction des exercices avec variation aléatoire controlée ────
        $exercises  = [];
        $totalCal   = 0.0;
        foreach ($selected as $i => $e) {
            // Variation ±1 set, ±2 reps, ±5s rest pour simuler l'adaptation
            $sets = max(1, $rules['sets'] + rand(-1, 1));
            $reps = max(1, $rules['reps'] + rand(-2, 2));
            $rest = max(10, $rules['rest'] + rand(-5, 10));

            $calories  = CalorieService::parSetsReps((float)$e['met_value'], $poids, $sets, $reps, $rest);
            $totalCal += $calories;

            $exercises[] = [
                'ordre'       => $i + 1,
                'id'          => $e['id'],
                'name'        => $e['name'],
                'category'    => $e['category'],
                'met_value'   => $e['met_value'],
                'sets'        => $sets,
                'reps'        => $reps,
                'rest_sec'    => $rest,
                'description' => $e['description'],
                'calories'    => $calories,
                'niveau'      => $e['niveau'],
            ];
        }

        $totalCal       = round($totalCal, 1);
        $dureeEstimee   = CalorieService::dureeTotal($exercises);
        $interpretation = CalorieService::interpreterCalories($totalCal);

        return [
            'goal'              => $goal,
            'niveau'            => $niveau,
            'description_rule'  => $rules['description'],
            'exercises'         => $exercises,
            'total_calories'    => $totalCal,
            'nb_exercises'      => count($exercises),
            'duree_estimee'     => $dureeEstimee,
            'categories_cibles' => $rules['categories'],
            'interpretation'    => $interpretation,
        ];
    }

    /**
     * Retourne les règles brutes pour affichage pédagogique.
     */
    public static function getRules(): array
    {
        return self::RULES;
    }

    /**
     * Valide les paramètres de génération.
     */
    public static function valider(string $goal, string $niveau): array
    {
        $errors = [];
        if (!array_key_exists($goal, self::RULES))
            $errors[] = 'Objectif invalide.';
        if (!in_array($niveau, ['debutant', 'intermediaire', 'avance']))
            $errors[] = 'Niveau invalide.';
        return $errors;
    }

    // ── Labels utilitaires ────────────────────────────────────────────────────
    public static function goalLabel(string $g): string
    {
        return ['perte_graisse' => 'Perte de graisse', 'prise_muscle' => 'Prise de muscle', 'endurance' => 'Endurance'][$g] ?? $g;
    }

    public static function niveauLabel(string $n): string
    {
        return ['debutant' => 'Débutant', 'intermediaire' => 'Intermédiaire', 'avance' => 'Avancé'][$n] ?? $n;
    }
}
