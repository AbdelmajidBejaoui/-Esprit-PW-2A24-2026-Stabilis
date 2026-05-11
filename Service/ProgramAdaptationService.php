<?php
require_once __DIR__ . '/CalorieService.php';

/**
 * ProgramAdaptationService — Métier avancé : Personnalisation dynamique des programmes
 *
 * Responsabilités :
 *   - Adapter la difficulté d'un programme selon le profil utilisateur
 *   - Ajouter/retirer des exercices dynamiquement
 *   - Calculer des statistiques de programme (calories totales, durée)
 *   - Recommander un niveau de programme selon les performances passées
 */
class ProgramAdaptationService
{
    // ── Coefficients d'adaptation par niveau ─────────────────────────────────
    private const DIFFICULTY_FACTORS = [
        'debutant'      => ['sets_mult' => 0.75, 'reps_mult' => 0.80, 'rest_mult' => 1.30],
        'intermediaire' => ['sets_mult' => 1.00, 'reps_mult' => 1.00, 'rest_mult' => 1.00],
        'avance'        => ['sets_mult' => 1.25, 'reps_mult' => 1.20, 'rest_mult' => 0.75],
    ];

    // ── Seuils de recommandation de niveau ───────────────────────────────────
    private const LEVEL_THRESHOLDS = [
        'nb_seances_intermediaire' => 15,   // 15+ séances => peut passer intermédiaire
        'nb_seances_avance'        => 40,   // 40+ séances => peut passer avancé
        'cal_moy_intermediaire'    => 300,  // calories moyennes par séance
        'cal_moy_avance'           => 500,
    ];

    /**
     * Adapte les paramètres d'exercice selon un niveau cible.
     * Permet d'utiliser le même exercice dans différents programmes.
     *
     * @param array  $exercice  Exercice brut (sets, reps, rest_sec, met_value)
     * @param string $niveau    Niveau cible
     * @param float  $poids_kg  Poids pour calcul calorique
     * @return array Exercice adapté avec nouvelles valeurs et calories recalculées
     */
    public static function adapterExercice(array $exercice, string $niveau, float $poids_kg = 70.0): array
    {
        $f = self::DIFFICULTY_FACTORS[$niveau] ?? self::DIFFICULTY_FACTORS['intermediaire'];

        $sets    = max(1, (int)round($exercice['sets'] * $f['sets_mult']));
        $reps    = max(1, (int)round($exercice['reps'] * $f['reps_mult']));
        $rest    = max(10, (int)round($exercice['rest_sec'] * $f['rest_mult']));
        $calories = CalorieService::parSetsReps((float)$exercice['met_value'], $poids_kg, $sets, $reps, $rest);

        return array_merge($exercice, [
            'sets'             => $sets,
            'reps'             => $reps,
            'rest_sec'         => $rest,
            'calories'         => $calories,
            'adapted_to'       => $niveau,
            'original_sets'    => $exercice['sets'],
            'original_reps'    => $exercice['reps'],
        ]);
    }

    /**
     * Calcule les statistiques complètes d'une session de programme.
     *
     * @param array $exercises  Liste d'exercices avec sets/reps/rest_sec/met_value
     * @param float $poids_kg
     * @return array Stats : calories totales, durée, intensité moyenne, volume total
     */
    public static function statsSession(array $exercises, float $poids_kg = 70.0): array
    {
        if (empty($exercises)) {
            return ['calories' => 0, 'duree_min' => 0, 'volume_total' => 0, 'met_moyen' => 0, 'interpretation' => []];
        }

        $totalCal = CalorieService::totalSessionExercices($exercises, $poids_kg);
        $duree    = CalorieService::dureeTotal($exercises);

        $volume = 0;
        $metSum = 0.0;
        foreach ($exercises as $e) {
            $volume += (int)$e['sets'] * (int)$e['reps'];
            $metSum += (float)$e['met_value'];
        }

        return [
            'calories'       => $totalCal,
            'duree_min'      => $duree,
            'volume_total'   => $volume,
            'met_moyen'      => round($metSum / count($exercises), 1),
            'interpretation' => CalorieService::interpreterCalories($totalCal),
        ];
    }

    /**
     * Calcule les statistiques globales d'un programme (toutes sessions).
     *
     * @param array $sessions  Chaque session contient 'exercises' (tableau)
     * @param float $poids_kg
     * @return array Stats agrégées du programme complet
     */
    public static function statsProgramme(array $sessions, float $poids_kg = 70.0): array
    {
        $totalCal   = 0.0;
        $totalMin   = 0;
        $totalVol   = 0;
        $sessionStats = [];

        foreach ($sessions as $s) {
            $exos  = $s['exercises'] ?? [];
            $stats = self::statsSession($exos, $poids_kg);
            $totalCal += $stats['calories'];
            $totalMin += $stats['duree_min'];
            $totalVol += $stats['volume_total'];
            $sessionStats[] = array_merge($s, ['stats' => $stats]);
        }

        return [
            'sessions'          => $sessionStats,
            'total_calories'    => round($totalCal, 1),
            'total_duree_min'   => $totalMin,
            'total_volume'      => $totalVol,
            'cal_par_session'   => count($sessions) > 0 ? round($totalCal / count($sessions), 1) : 0,
            'interpretation'    => CalorieService::interpreterCalories($totalCal / max(1, count($sessions))),
        ];
    }

    /**
     * Recommande un niveau selon l'historique de l'utilisateur.
     *
     * @param int   $nb_seances    Nombre de séances complétées
     * @param float $calories_moy  Calories moyennes par séance
     * @return array ['niveau' => string, 'raison' => string]
     */
    public static function recommanderNiveau(int $nb_seances, float $calories_moy): array
    {
        $t = self::LEVEL_THRESHOLDS;

        if ($nb_seances >= $t['nb_seances_avance'] && $calories_moy >= $t['cal_moy_avance']) {
            return [
                'niveau' => 'avance',
                'raison' => "Avec {$nb_seances} séances et " . round($calories_moy) . " kcal/séance, vous êtes prêt pour le niveau avancé.",
            ];
        }
        if ($nb_seances >= $t['nb_seances_intermediaire'] && $calories_moy >= $t['cal_moy_intermediaire']) {
            return [
                'niveau' => 'intermediaire',
                'raison' => "Avec {$nb_seances} séances complétées, le niveau intermédiaire vous correspond.",
            ];
        }
        return [
            'niveau' => 'debutant',
            'raison' => "Continuez à pratiquer régulièrement. Le niveau intermédiaire sera accessible après {$t['nb_seances_intermediaire']} séances.",
        ];
    }

    /**
     * Trie et filtre les exercices pour la composition d'une session.
     * Utilisé lors de l'ajout dynamique d'exercices à un programme.
     *
     * @param array  $pool         Pool d'exercices disponibles
     * @param string $goal         Objectif prioritaire
     * @param string $niveau       Niveau
     * @param array  $excludeIds   IDs à exclure (déjà dans la session)
     * @param int    $limit        Nombre max à retourner
     * @return array Exercices triés par pertinence
     */
    public static function filtrerExercicesCompatibles(
        array  $pool,
        string $goal,
        string $niveau,
        array  $excludeIds = [],
        int    $limit = 10
    ): array {
        // Score de compatibilité
        $scored = [];
        foreach ($pool as $e) {
            if (in_array($e['id'], $excludeIds)) continue;

            $score = 0;
            // Bonus objectif
            if (str_contains($e['goal'] ?? '', $goal)) $score += 3;
            // Bonus niveau exact
            if (($e['niveau'] ?? '') === $niveau) $score += 2;
            // Bonus niveau adjacent
            $niveaux = ['debutant', 'intermediaire', 'avance'];
            $idxE    = array_search($e['niveau'] ?? '', $niveaux);
            $idxN    = array_search($niveau, $niveaux);
            if ($idxE !== false && $idxN !== false && abs($idxE - $idxN) === 1) $score += 1;

            $e['_score'] = $score;
            $scored[] = $e;
        }

        usort($scored, fn($a, $b) => $b['_score'] <=> $a['_score']);
        return array_slice($scored, 0, $limit);
    }
}
