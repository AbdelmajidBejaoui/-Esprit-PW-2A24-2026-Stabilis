<?php
/**
 * EntityHelper — Utilitaires partagés pour les entités du domaine
 *
 * Ce helper centralise les labels, couleurs et icônes associés
 * aux valeurs des entités TrainingProgram et Exercise.
 *
 * Principe DRY : une seule définition, utilisable partout (Model, View, Controller).
 */
class EntityHelper
{
    // ── Objectifs ─────────────────────────────────────────────────────────────
    public const GOALS = [
        'perte_graisse' => ['label' => 'Perte de graisse', 'color' => '#f5576c', 'icon' => 'fa-fire',     'emoji' => '🔥'],
        'prise_muscle'  => ['label' => 'Prise de muscle',  'color' => '#82ae46', 'icon' => 'fa-dumbbell', 'emoji' => '💪'],
        'endurance'     => ['label' => 'Endurance',         'color' => '#4facfe', 'icon' => 'fa-heartbeat','emoji' => '🏃'],
    ];

    // ── Niveaux ───────────────────────────────────────────────────────────────
    public const NIVEAUX = [
        'debutant'      => ['label' => 'Débutant',      'color' => '#28a745', 'badge' => 'success', 'emoji' => '🌱'],
        'intermediaire' => ['label' => 'Intermédiaire', 'color' => '#fd7e14', 'badge' => 'warning', 'emoji' => '⚡'],
        'avance'        => ['label' => 'Avancé',        'color' => '#dc3545', 'badge' => 'danger',  'emoji' => '🔥'],
    ];

    // ── Catégories d'exercices ────────────────────────────────────────────────
    public const CATEGORIES = [
        'cardio'       => ['label' => 'Cardio',       'color' => 'info',    'icon' => 'fa-heartbeat'],
        'force'        => ['label' => 'Force',         'color' => 'success', 'icon' => 'fa-dumbbell'],
        'endurance'    => ['label' => 'Endurance',     'color' => 'primary', 'icon' => 'fa-running'],
        'hiit'         => ['label' => 'HIIT',          'color' => 'danger',  'icon' => 'fa-bolt'],
        'flexibilite'  => ['label' => 'Flexibilité',   'color' => 'warning', 'icon' => 'fa-child'],
    ];

    // ── Intensités de séance ──────────────────────────────────────────────────
    public const INTENSITES = [
        'faible'  => ['label' => 'Faible',   'color' => '#4facfe', 'badge' => 'info'],
        'moderee' => ['label' => 'Modérée',  'color' => '#82ae46', 'badge' => 'success'],
        'elevee'  => ['label' => 'Élevée',   'color' => '#fd7e14', 'badge' => 'warning'],
        'maximale'=> ['label' => 'Maximale', 'color' => '#dc3545', 'badge' => 'danger'],
    ];

    // ── Accesseurs ────────────────────────────────────────────────────────────

    public static function goal(string $key, string $prop = 'label'): string
    {
        return self::GOALS[$key][$prop] ?? $key;
    }

    public static function niveau(string $key, string $prop = 'label'): string
    {
        return self::NIVEAUX[$key][$prop] ?? $key;
    }

    public static function category(string $key, string $prop = 'label'): string
    {
        return self::CATEGORIES[$key][$prop] ?? $key;
    }

    public static function intensite(string $key, string $prop = 'label'): string
    {
        return self::INTENSITES[$key][$prop] ?? $key;
    }

    /**
     * Génère un badge HTML Bootstrap pour un niveau.
     */
    public static function niveauBadge(string $niveau): string
    {
        $n = self::NIVEAUX[$niveau] ?? null;
        if (!$n) return htmlspecialchars($niveau);
        return '<span class="badge badge-' . $n['badge'] . '">' . $n['label'] . '</span>';
    }

    /**
     * Génère un badge HTML Bootstrap pour un objectif.
     */
    public static function goalBadge(string $goal): string
    {
        $g = self::GOALS[$goal] ?? null;
        if (!$g) return htmlspecialchars($goal);
        return '<span class="badge" style="background:' . $g['color'] . ';color:#fff;">'
             . $g['emoji'] . ' ' . $g['label'] . '</span>';
    }

    /**
     * Retourne le gradient CSS d'un objectif.
     */
    public static function goalGradient(string $goal): string
    {
        $color = self::GOALS[$goal]['color'] ?? '#666';
        return "linear-gradient(135deg, {$color}, #333)";
    }

    /**
     * Liste toutes les valeurs valides d'un type.
     */
    public static function validGoals(): array   { return array_keys(self::GOALS); }
    public static function validNiveaux(): array  { return array_keys(self::NIVEAUX); }
    public static function validCategories(): array { return array_keys(self::CATEGORIES); }
    public static function validIntensites(): array { return array_keys(self::INTENSITES); }
}
