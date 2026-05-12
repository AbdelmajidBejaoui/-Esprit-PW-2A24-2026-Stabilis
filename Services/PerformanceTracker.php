<?php
require_once __DIR__ . '/CalorieService.php';

/**
 * PerformanceTracker — Métier avancé : Suivi de performance sportive
 *
 * Responsabilités :
 *   - Agréger et analyser l'historique de séances
 *   - Calculer les tendances (progression, stagnation, régression)
 *   - Générer des indicateurs de performance (KPI sportifs)
 *   - Produire des données pour les graphiques de tableau de bord
 */
class PerformanceTracker
{
    // ── Seuils de performance ─────────────────────────────────────────────────
    private const FREQ_OPTIMALE_PAR_SEMAINE = 3;
    private const PROGRESSION_SEUIL_PCT     = 0.05; // +5% = progression

    /**
     * Agrège les KPIs principaux d'un utilisateur.
     *
     * @param array $seances  Séances complétées (raw DB rows)
     * @return array KPIs structurés
     */
    public static function calculerKPIs(array $seances): array
    {
        if (empty($seances)) {
            return [
                'nb_seances'       => 0,
                'total_calories'   => 0.0,
                'total_minutes'    => 0,
                'cal_par_seance'   => 0.0,
                'min_par_seance'   => 0.0,
                'intensite_dom'    => null,
                'progression'      => 'debut',
                'serie_actuelle'   => 0,
                'meilleure_serie'  => 0,
            ];
        }

        $nbSeances    = count($seances);
        $totalCal     = array_sum(array_column($seances, 'calories'));
        $totalMin     = array_sum(array_column($seances, 'duree_minutes'));

        // Intensité dominante
        $intensites   = array_count_values(array_column($seances, 'intensite'));
        arsort($intensites);
        $intensiteDom = array_key_first($intensites);

        // Progression : comparer première moitié vs deuxième moitié
        $half          = (int)ceil($nbSeances / 2);
        $firstHalf     = array_slice($seances, 0, $half);
        $secondHalf    = array_slice($seances, $half);
        $calFirst      = count($firstHalf) > 0 ? array_sum(array_column($firstHalf, 'calories')) / count($firstHalf) : 0;
        $calSecond     = count($secondHalf) > 0 ? array_sum(array_column($secondHalf, 'calories')) / count($secondHalf) : 0;

        $progressionPct = $calFirst > 0 ? ($calSecond - $calFirst) / $calFirst : 0;
        if ($progressionPct >= self::PROGRESSION_SEUIL_PCT) {
            $progression = 'progression';
        } elseif ($progressionPct <= -self::PROGRESSION_SEUIL_PCT) {
            $progression = 'regression';
        } else {
            $progression = 'stable';
        }

        // Série consécutive (jours avec séance)
        [$serieActuelle, $meilleureSerie] = self::calculerSeries($seances);

        return [
            'nb_seances'       => $nbSeances,
            'total_calories'   => round($totalCal, 1),
            'total_minutes'    => $totalMin,
            'cal_par_seance'   => $nbSeances > 0 ? round($totalCal / $nbSeances, 1) : 0,
            'min_par_seance'   => $nbSeances > 0 ? round($totalMin / $nbSeances, 1) : 0,
            'intensite_dom'    => $intensiteDom,
            'progression'      => $progression,
            'progression_pct'  => round($progressionPct * 100, 1),
            'serie_actuelle'   => $serieActuelle,
            'meilleure_serie'  => $meilleureSerie,
        ];
    }

    /**
     * Prépare les données de graphique (calories + durée par séance).
     * Retourne les 20 dernières séances triées chronologiquement.
     *
     * @param array $seances Séances triées DESC par date
     * @return array ['labels' => [...], 'calories' => [...], 'duree' => [...]]
     */
    public static function donneesGraphique(array $seances, int $limit = 20): array
    {
        $seances = array_reverse(array_slice($seances, 0, $limit));
        $labels   = [];
        $calories = [];
        $durees   = [];

        foreach ($seances as $s) {
            $date     = $s['completed_at'] ?? $s['date'] ?? 'N/A';
            $labels[] = date('d/m', strtotime($date));
            $calories[] = (float)($s['calories'] ?? 0);
            $durees[]   = (int)($s['duree_minutes'] ?? 0);
        }

        return ['labels' => $labels, 'calories' => $calories, 'duree' => $durees];
    }

    /**
     * Calcule la fréquence hebdomadaire et l'assiduité.
     *
     * @param array $seances Séances triées DESC par date
     * @return array ['freq_par_semaine' => float, 'assiduite_pct' => float, 'appreciation' => string]
     */
    public static function analyserAssiduité(array $seances): array
    {
        if (count($seances) < 2) {
            return ['freq_par_semaine' => 0, 'assiduite_pct' => 0, 'appreciation' => 'Démarrage'];
        }

        $oldest  = strtotime(end($seances)['completed_at'] ?? 'now');
        $newest  = strtotime(reset($seances)['completed_at'] ?? 'now');
        $nbJours = max(1, ($newest - $oldest) / 86400);
        $nbSem   = $nbJours / 7;

        $freqSemaine = round(count($seances) / max(1, $nbSem), 1);
        $assiduite   = min(100, round($freqSemaine / self::FREQ_OPTIMALE_PAR_SEMAINE * 100));

        if ($freqSemaine >= 4)       $appreciation = 'Excellent';
        elseif ($freqSemaine >= 3)   $appreciation = 'Très bien';
        elseif ($freqSemaine >= 2)   $appreciation = 'Bien';
        elseif ($freqSemaine >= 1)   $appreciation = 'À améliorer';
        else                         $appreciation = 'Insuffisant';

        return [
            'freq_par_semaine' => $freqSemaine,
            'assiduite_pct'    => $assiduite,
            'appreciation'     => $appreciation,
        ];
    }

    /**
     * Calcule la série de séances consécutives.
     * @return array [serie_actuelle, meilleure_serie] (en jours)
     */
    private static function calculerSeries(array $seances): array
    {
        if (empty($seances)) return [0, 0];

        // Extraire les dates uniques
        $dates = [];
        foreach ($seances as $s) {
            $d = date('Y-m-d', strtotime($s['completed_at'] ?? 'now'));
            $dates[$d] = true;
        }
        ksort($dates);
        $dates = array_keys($dates);

        $serieMax    = 1;
        $serieEnCours = 1;
        for ($i = 1; $i < count($dates); $i++) {
            $diff = (strtotime($dates[$i]) - strtotime($dates[$i - 1])) / 86400;
            if ($diff <= 1.5) { // Tolérance 1.5 jours
                $serieEnCours++;
                $serieMax = max($serieMax, $serieEnCours);
            } else {
                $serieEnCours = 1;
            }
        }

        // Série actuelle = série la plus récente
        $lastDate  = end($dates);
        $today     = date('Y-m-d');
        $diffToday = (strtotime($today) - strtotime($lastDate)) / 86400;
        $actuelle  = $diffToday <= 1.5 ? $serieEnCours : 0;

        return [$actuelle, $serieMax];
    }

    /**
     * Génère un message de motivation contextuel.
     */
    public static function messageMotivation(array $kpis): string
    {
        if ($kpis['nb_seances'] === 0) {
            return "Commencez dès aujourd'hui — votre premier entraînement est le plus important !";
        }
        if ($kpis['progression'] === 'progression') {
            return "Excellente progression ! Vos performances s'améliorent de " . abs($kpis['progression_pct']) . "% 🚀";
        }
        if ($kpis['serie_actuelle'] >= 7) {
            return "Série de {$kpis['serie_actuelle']} jours consécutifs — vous êtes inarrêtable ! 🔥";
        }
        if ($kpis['nb_seances'] >= 10) {
            return "{$kpis['nb_seances']} séances au compteur — la régularité, c'est la clé du succès !";
        }
        return "Chaque séance compte. Continuez sur cette lancée ! 💪";
    }
}
