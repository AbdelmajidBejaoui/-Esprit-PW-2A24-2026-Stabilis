<?php
/**
 * WorkoutPromptBuilder - Builds AI prompts for workout generation
 * 
 * Centralizes all prompt engineering logic for consistent AI responses.
 */
class WorkoutPromptBuilder
{
    private const GOAL_LABELS = [
        'perte_graisse' => 'Perte de graisse',
        'prise_muscle'  => 'Prise de muscle',
        'endurance'     => 'Endurance',
    ];

    private const NIVEAU_LABELS = [
        'debutant'      => 'Débutant',
        'intermediaire' => 'Intermédiaire',
        'avance'        => 'Avancé',
    ];

    private const NIVEAU_RULES = [
        'debutant'      => '3-4 séries, 12-15 reps, repos 45-60s, exercices simples',
        'intermediaire' => '4-5 séries, 8-12 reps, repos 60-90s, exercices variés',
        'avance'        => '5-6 séries, 5-10 reps, repos 90-120s, exercices complexes',
    ];

    /**
     * Build complete workout generation prompt
     */
    public static function build(string $goal, string $niveau, string $userPrompt = ''): string
    {
        $goalLabel = self::GOAL_LABELS[$goal] ?? $goal;
        $niveauLabel = self::NIVEAU_LABELS[$niveau] ?? $niveau;
        $niveauRules = self::NIVEAU_RULES[$niveau] ?? self::NIVEAU_RULES['debutant'];

        $systemPrompt = self::getSystemPrompt();
        $context = self::getContext($goalLabel, $niveauLabel, $userPrompt);
        $instructions = self::getInstructions($niveauRules);
        $jsonFormat = self::getJsonFormat();
        $important = self::getImportantNotes();

        return implode("\n\n", [
            $systemPrompt,
            $context,
            $instructions,
            $jsonFormat,
            $important,
            "Demande: {$userPrompt}"
        ]);
    }

    private static function getSystemPrompt(): string
    {
        return "Tu es un coach sportif expert qui crée des programmes d'entraînement personnalisés.";
    }

    private static function getContext(string $goal, string $niveau, string $userPrompt): string
    {
        return "CONTEXTE:\n"
            . "- Objectif: {$goal}\n"
            . "- Niveau: {$niveau}\n"
            . "- Demande utilisateur: {$userPrompt}";
    }

    private static function getInstructions(string $niveauRules): string
    {
        return "INSTRUCTIONS:\n"
            . "1. Crée une séance d'entraînement qui répond EXACTEMENT à la demande de l'utilisateur\n"
            . "2. Respecte l'objectif et le niveau spécifiés\n"
            . "3. Si l'utilisateur demande des muscles spécifiques, concentre-toi sur ces muscles\n"
            . "4. Si l'utilisateur demande un nombre d'exercices, respecte-le\n"
            . "5. Si l'utilisateur exclut des exercices, ne les inclus pas\n\n"
            . "RÈGLES SELON LE NIVEAU:\n"
            . "- {$niveauRules}";
    }

    private static function getJsonFormat(): string
    {
        return "RÉPONDS UNIQUEMENT avec un JSON valide (sans markdown, sans ```json) contenant:\n"
            . "{\n"
            . "  \"exercises\": [\n"
            . "    {\n"
            . "      \"ordre\": 1,\n"
            . "      \"name\": \"Nom de l'exercice\",\n"
            . "      \"category\": \"cardio|force|endurance|hiit|flexibilite\",\n"
            . "      \"sets\": 4,\n"
            . "      \"reps\": 12,\n"
            . "      \"rest_sec\": 60,\n"
            . "      \"met_value\": 5.0,\n"
            . "      \"description\": \"Description technique de l'exercice\",\n"
            . "      \"calories\": 45.5\n"
            . "    }\n"
            . "  ],\n"
            . "  \"description_rule\": \"Description de la stratégie de la séance\",\n"
            . "  \"duree_estimee\": 45\n"
            . "}";
    }

    private static function getImportantNotes(): string
    {
        return "IMPORTANT:\n"
            . "- Calcule les calories avec la formule: MET × poids(70kg) × durée(minutes) / 60\n"
            . "- Durée par exercice = (sets × reps × 3 secondes + repos × sets) / 60\n"
            . "- Adapte les valeurs MET: cardio léger=5, cardio intense=9, force=5-7, HIIT=10-12\n"
            . "- Sois précis et réaliste dans les descriptions\n"
            . "- RÉPONDS UNIQUEMENT AVEC LE JSON, RIEN D'AUTRE";
    }
}
