<?php
/**
 * CalorieService — Métier avancé : Calcul calorique intelligent (formule MET)
 *
 * Centralise TOUTE la logique de calcul calorique du projet.
 * Formule : Calories = MET × poids(kg) × durée(h)
 *
 * Ce service est la source unique de vérité pour les calories.
 * Il est utilisé par : SeanceC, AIGeneratorC, EntrainementC.
 */
class CalorieService
{
    // ── Constantes MET par intensité ──────────────────────────────────────────
    public const INTENSITE_MULTIPLIER = [
        'faible'   => 0.75,
        'moderee'  => 1.00,
        'elevee'   => 1.20,
        'maximale' => 1.40,
    ];

    /**
     * Calcul par durée continue (séances, entrainements)
     * Calories = MET × poids_kg × duree_h × coeff_intensité
     */
    public static function parDuree(
        float  $met,
        float  $poids_kg,
        int    $duree_min,
        string $intensite = 'moderee'
    ): float {
        $coeff = self::INTENSITE_MULTIPLIER[$intensite] ?? 1.0;
        return round($met * $poids_kg * ($duree_min / 60) * $coeff, 1);
    }

    /**
     * Calcul par séries/répétitions (exercises structurés)
     * Durée estimée = sets × reps × 3s (effort) + sets × rest_sec (repos)
     */
    public static function parSetsReps(
        float $met,
        float $poids_kg,
        int   $sets,
        int   $reps,
        int   $rest_sec
    ): float {
        $effort_sec = $sets * $reps * 3;
        $repos_sec  = $sets * $rest_sec;
        $total_h    = ($effort_sec + $repos_sec) / 3600;
        return round($met * $poids_kg * $total_h, 1);
    }

    /**
     * Durée estimée d'un exercise (en secondes)
     */
    public static function dureeExercice(int $sets, int $reps, int $rest_sec): int
    {
        return ($sets * $reps * 3) + ($sets * $rest_sec);
    }

    /**
     * Total calories d'un tableau d'exercices
     * Chaque élément doit avoir: met_value, sets, reps, rest_sec
     */
    public static function totalSessionExercices(array $exercises, float $poids_kg): float
    {
        $total = 0.0;
        foreach ($exercises as $e) {
            $total += self::parSetsReps(
                (float)$e['met_value'],
                $poids_kg,
                (int)$e['sets'],
                (int)$e['reps'],
                (int)$e['rest_sec']
            );
        }
        return round($total, 1);
    }

    /**
     * Durée totale estimée d'une liste d'exercices (en minutes)
     */
    public static function dureeTotal(array $exercises): int
    {
        $sec = 0;
        foreach ($exercises as $e) {
            $sec += self::dureeExercice((int)$e['sets'], (int)$e['reps'], (int)$e['rest_sec']);
        }
        return (int)ceil($sec / 60);
    }

    /**
     * Interprétation de la dépense calorique
     */
    public static function interpreterCalories(float $calories): array
    {
        if ($calories < 150) {
            return ['label' => 'Légère', 'color' => '#4facfe', 'icon' => 'fa-leaf'];
        } elseif ($calories < 350) {
            return ['label' => 'Modérée', 'color' => '#82ae46', 'icon' => 'fa-fire'];
        } elseif ($calories < 600) {
            return ['label' => 'Intense', 'color' => '#f5a623', 'icon' => 'fa-bolt'];
        } else {
            return ['label' => 'Extrême', 'color' => '#f5576c', 'icon' => 'fa-dragon'];
        }
    }

    /**
     * Estimation BMR (Mifflin-St Jeor) pour contextualiser la dépense
     * Retourne les kcal/jour de base selon le profil utilisateur
     */
    public static function estimerBMR(float $poids_kg, int $taille_cm, int $age, string $sexe = 'H'): float
    {
        // Formule Mifflin-St Jeor
        if ($sexe === 'H') {
            return round(10 * $poids_kg + 6.25 * $taille_cm - 5 * $age + 5, 0);
        }
        return round(10 * $poids_kg + 6.25 * $taille_cm - 5 * $age - 161, 0);
    }
}
