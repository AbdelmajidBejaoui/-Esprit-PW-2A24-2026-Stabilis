<?php
require_once __DIR__ . '/../Models/Nutrition.php';
require_once __DIR__ . '/../Services/NutritionAiService.php';

class NutritionController
{
    private Nutrition $nutrition;
    private NutritionAiService $ai;

    public function __construct()
    {
        $this->nutrition = new Nutrition();
        $this->ai = new NutritionAiService();
    }

    public function model(): Nutrition
    {
        return $this->nutrition;
    }

    public function saveAliment(array $post, ?int $id = null): array
    {
        return $this->nutrition->saveAliment([
            'nom' => trim((string)($post['nom'] ?? '')),
            'description' => trim((string)($post['description'] ?? '')),
            'calories' => $post['calories'] ?? 0,
            'proteines' => $post['proteines'] ?? 0,
            'glucides' => $post['glucides'] ?? 0,
            'lipides' => $post['lipides'] ?? 0,
        ], $id);
    }

    public function saveRecette(array $post, ?int $id = null): array
    {
        $ingredients = [];
        foreach (($post['ingredients'] ?? []) as $ing) {
            if (!empty($ing['aliment_id']) && (float)($ing['quantite'] ?? 0) > 0) {
                $ingredients[] = [
                    'aliment_id' => (int)$ing['aliment_id'],
                    'quantite' => (float)$ing['quantite'],
                    'unite' => trim((string)($ing['unite'] ?? 'g')),
                ];
            }
        }
        return $this->nutrition->saveRecette([
            'nom' => trim((string)($post['nom'] ?? '')),
            'description' => trim((string)($post['description'] ?? '')),
            'instructions' => trim((string)($post['instructions'] ?? '')),
        ], $ingredients, $id);
    }

    public function analyzePhoto(array $file): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return '';
        }
        $mime = mime_content_type($file['tmp_name']);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return 'Format image non supporte.';
        }
        if ((int)$file['size'] > 4 * 1024 * 1024) {
            return 'Image trop volumineuse.';
        }
        return $this->ai->analyzeFoodPhoto($file['tmp_name'], $mime);
    }

    public function estimatePhotoCalories(array $file): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return [];
        }
        $mime = mime_content_type($file['tmp_name']);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return ['summary' => 'Format image non supporte.', 'confidence' => 0, 'items' => [], 'totals' => ['calories' => 0, 'proteins' => 0, 'carbs' => 0, 'fats' => 0], 'advice' => ''];
        }
        if ((int)$file['size'] > 4 * 1024 * 1024) {
            return ['summary' => 'Image trop volumineuse.', 'confidence' => 0, 'items' => [], 'totals' => ['calories' => 0, 'proteins' => 0, 'carbs' => 0, 'fats' => 0], 'advice' => ''];
        }
        return $this->ai->estimateFoodPhotoCalories($file['tmp_name'], $mime);
    }

    public function generatedRecipe(string $objective): string
    {
        return $this->ai->generateRecipe($objective, $this->nutrition->aliments('', 'proteines_desc'));
    }

    public function improvementText(int $recetteId): string
    {
        $recipe = $this->nutrition->recette($recetteId);
        if (!$recipe) {
            return '';
        }
        return $this->ai->improveRecipe($recipe, $this->nutrition->ingredients($recetteId), $this->nutrition->totals($recetteId));
    }
}
