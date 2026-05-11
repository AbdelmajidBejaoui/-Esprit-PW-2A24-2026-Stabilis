<?php
require_once __DIR__ . '/../config/database.php';

class Nutrition
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
        $this->ensureSchema();
    }

    private function ensureSchema(): void
    {
        $this->db->exec("CREATE TABLE IF NOT EXISTS aliments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nom VARCHAR(255) NOT NULL,
            description TEXT,
            calories INT DEFAULT 0,
            proteines DECIMAL(7,2) DEFAULT 0,
            glucides DECIMAL(7,2) DEFAULT 0,
            lipides DECIMAL(7,2) DEFAULT 0,
            image LONGBLOB NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->exec("CREATE TABLE IF NOT EXISTS recettes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nom VARCHAR(255) NOT NULL,
            description TEXT,
            instructions TEXT,
            image LONGBLOB NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->exec("CREATE TABLE IF NOT EXISTS ingredients (
            id INT AUTO_INCREMENT PRIMARY KEY,
            recette_id INT NOT NULL,
            aliment_id INT NOT NULL,
            quantite DECIMAL(8,2) NOT NULL,
            unite VARCHAR(50) DEFAULT 'g',
            INDEX(recette_id),
            INDEX(aliment_id),
            CONSTRAINT fk_ingredients_recette FOREIGN KEY (recette_id) REFERENCES recettes(id) ON DELETE CASCADE,
            CONSTRAINT fk_ingredients_aliment FOREIGN KEY (aliment_id) REFERENCES aliments(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        if ((int)$this->db->query('SELECT COUNT(*) FROM aliments')->fetchColumn() === 0) {
            $seed = [
                ['Poulet', 'Blanc de poulet grille', 165, 31, 0, 3.6],
                ['Riz', 'Riz blanc cuit', 130, 2.7, 28, 0.3],
                ['Brocoli', 'Brocoli vapeur', 55, 3.7, 11.2, 0.6],
                ['Beurre', 'Beurre demi-sel', 717, 0.9, 0.1, 81],
                ['Saumon', 'Saumon frais', 208, 22.1, 0, 13.6],
                ['Quinoa', 'Quinoa cuit', 120, 4.4, 21.3, 1.9],
                ['Avocat', 'Avocat mur', 160, 2, 8.5, 14.7],
                ['Lentilles', 'Lentilles cuites', 116, 9, 20, 0.4],
                ['Epinards', 'Epinards cuits', 23, 2.9, 3.6, 0.4],
                ['Pates', 'Pates cuites', 157, 5.8, 31, 0.9],
                ['Huile olive', 'Huile olive extra vierge', 884, 0, 0, 100],
            ];
            $stmt = $this->db->prepare('INSERT INTO aliments (nom, description, calories, proteines, glucides, lipides) VALUES (?, ?, ?, ?, ?, ?)');
            foreach ($seed as $row) {
                $stmt->execute($row);
            }
            $this->seedRecipes();
        }
    }

    private function seedRecipes(): void
    {
        $this->db->exec("INSERT INTO recettes (nom, description, instructions) VALUES
            ('Poulet quinoa brocoli', 'Plat equilibre riche en proteines.', 'Cuire le quinoa, griller le poulet, ajouter le brocoli vapeur.'),
            ('Pates au beurre', 'Recette volontairement desequilibree pour demonstration IA.', 'Cuire les pates puis ajouter beaucoup de beurre.')");
        $ids = $this->db->query('SELECT id, nom FROM aliments')->fetchAll(PDO::FETCH_KEY_PAIR);
        $map = array_flip($ids);
        $this->addIngredient(1, $map['Poulet'] ?? 1, 150, 'g');
        $this->addIngredient(1, $map['Quinoa'] ?? 6, 180, 'g');
        $this->addIngredient(1, $map['Brocoli'] ?? 3, 120, 'g');
        $this->addIngredient(2, $map['Pates'] ?? 10, 220, 'g');
        $this->addIngredient(2, $map['Beurre'] ?? 4, 90, 'g');
    }

    public function aliments(string $search = '', string $sort = 'nom_asc'): array
    {
        $rows = $this->db->query('SELECT * FROM aliments')->fetchAll();
        if ($search !== '') {
            $rows = array_values(array_filter($rows, fn($a) => stripos($a['nom'] . ' ' . $a['description'], $search) !== false));
        }
        $map = [
            'nom_asc' => fn($a, $b) => strcasecmp($a['nom'], $b['nom']),
            'calories_desc' => fn($a, $b) => $b['calories'] <=> $a['calories'],
            'proteines_desc' => fn($a, $b) => $b['proteines'] <=> $a['proteines'],
            'eco_desc' => fn($a, $b) => $this->ecoScore($b) <=> $this->ecoScore($a),
        ];
        usort($rows, $map[$sort] ?? $map['nom_asc']);
        return $rows;
    }

    public function recettes(string $search = '', string $sort = 'nom_asc'): array
    {
        $rows = $this->db->query('SELECT * FROM recettes')->fetchAll();
        foreach ($rows as &$row) {
            $row['totals'] = $this->totals((int)$row['id']);
            $row['performance_score'] = $this->performanceScore((int)$row['id']);
        }
        unset($row);
        if ($search !== '') {
            $rows = array_values(array_filter($rows, fn($r) => stripos($r['nom'] . ' ' . $r['description'], $search) !== false));
        }
        $map = [
            'nom_asc' => fn($a, $b) => strcasecmp($a['nom'], $b['nom']),
            'calories_asc' => fn($a, $b) => $a['totals']['calories'] <=> $b['totals']['calories'],
            'proteines_desc' => fn($a, $b) => $b['totals']['proteines'] <=> $a['totals']['proteines'],
            'performance_desc' => fn($a, $b) => $b['performance_score'] <=> $a['performance_score'],
        ];
        usort($rows, $map[$sort] ?? $map['nom_asc']);
        return $rows;
    }

    public function aliment(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM aliments WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function recette(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM recettes WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function saveAliment(array $data, ?int $id = null): array
    {
        $errors = $this->validateAliment($data);
        if ($errors) {
            return [false, $errors];
        }
        $values = [$data['nom'], $data['description'], (int)$data['calories'], (float)$data['proteines'], (float)$data['glucides'], (float)$data['lipides']];
        if ($id) {
            $stmt = $this->db->prepare('UPDATE aliments SET nom=?, description=?, calories=?, proteines=?, glucides=?, lipides=? WHERE id=?');
            $stmt->execute([...$values, $id]);
        } else {
            $stmt = $this->db->prepare('INSERT INTO aliments (nom, description, calories, proteines, glucides, lipides) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute($values);
        }
        return [true, []];
    }

    public function deleteAliment(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM aliments WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function saveRecette(array $data, array $ingredients, ?int $id = null): array
    {
        $errors = $this->validateRecette($data, $ingredients);
        if ($errors) {
            return [false, $errors];
        }
        $values = [$data['nom'], $data['description'], $data['instructions']];
        if ($id) {
            $stmt = $this->db->prepare('UPDATE recettes SET nom=?, description=?, instructions=? WHERE id=?');
            $stmt->execute([...$values, $id]);
            $recetteId = $id;
        } else {
            $stmt = $this->db->prepare('INSERT INTO recettes (nom, description, instructions) VALUES (?, ?, ?)');
            $stmt->execute($values);
            $recetteId = (int)$this->db->lastInsertId();
        }
        $this->updateIngredients($recetteId, $ingredients);
        return [true, []];
    }

    public function deleteRecette(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM recettes WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function ingredients(int $recetteId): array
    {
        $stmt = $this->db->prepare('SELECT i.*, a.nom AS aliment_nom, a.calories, a.proteines, a.glucides, a.lipides FROM ingredients i JOIN aliments a ON a.id=i.aliment_id WHERE i.recette_id=?');
        $stmt->execute([$recetteId]);
        return $stmt->fetchAll();
    }

    public function updateIngredients(int $recetteId, array $ingredients): void
    {
        $stmt = $this->db->prepare('DELETE FROM ingredients WHERE recette_id = ?');
        $stmt->execute([$recetteId]);
        foreach ($ingredients as $ing) {
            if (!empty($ing['aliment_id']) && (float)($ing['quantite'] ?? 0) > 0) {
                $this->addIngredient($recetteId, (int)$ing['aliment_id'], (float)$ing['quantite'], trim((string)($ing['unite'] ?? 'g')));
            }
        }
    }

    public function addIngredient(int $recetteId, int $alimentId, float $quantite, string $unite): void
    {
        $stmt = $this->db->prepare('INSERT INTO ingredients (recette_id, aliment_id, quantite, unite) VALUES (?, ?, ?, ?)');
        $stmt->execute([$recetteId, $alimentId, $quantite, $unite ?: 'g']);
    }

    public function totals(int $recetteId): array
    {
        $totals = ['calories' => 0, 'proteines' => 0, 'glucides' => 0, 'lipides' => 0, 'eco_score' => 0, 'ingredient_count' => 0];
        foreach ($this->ingredients($recetteId) as $ing) {
            $portion = $this->portion((float)$ing['quantite'], (string)$ing['unite']);
            $totals['calories'] += $portion * (float)$ing['calories'];
            $totals['proteines'] += $portion * (float)$ing['proteines'];
            $totals['glucides'] += $portion * (float)$ing['glucides'];
            $totals['lipides'] += $portion * (float)$ing['lipides'];
            $totals['eco_score'] += $this->ecoScore($ing);
            $totals['ingredient_count']++;
        }
        $count = max(1, $totals['ingredient_count']);
        return [
            'calories' => round($totals['calories']),
            'proteines' => round($totals['proteines'], 1),
            'glucides' => round($totals['glucides'], 1),
            'lipides' => round($totals['lipides'], 1),
            'eco_score' => round($totals['eco_score'] / $count, 1),
            'ingredient_count' => $totals['ingredient_count'],
        ];
    }

    public function ecoScore(array $aliment): int
    {
        $score = 11 - min(4, (float)$aliment['calories'] / 120) - min(3, (float)$aliment['lipides'] / 10) - min(2, (float)$aliment['glucides'] / 20) + min(2, (float)$aliment['proteines'] / 20);
        return max(1, min(10, (int)round($score)));
    }

    public function performanceScore(int $recetteId): float
    {
        $t = $this->totals($recetteId);
        return min(10, max(0, round(($t['proteines'] * 3) - ($t['lipides'] * 1.5) - ($t['calories'] / 120) + ($t['eco_score'] * 0.5), 1)));
    }

    public function validationIssues(): array
    {
        $issues = [];
        foreach ($this->recettes() as $recipe) {
            $ingredients = $this->ingredients((int)$recipe['id']);
            $totals = $recipe['totals'];
            if (!$ingredients) {
                $issues[] = ['recipe' => $recipe, 'issue' => 'Recette sans ingredient'];
            }
            if ($totals['calories'] <= 0) {
                $issues[] = ['recipe' => $recipe, 'issue' => 'Calories totales invalides'];
            }
            if ($totals['proteines'] < 10) {
                $issues[] = ['recipe' => $recipe, 'issue' => 'Proteines faibles'];
            }
            if ($totals['lipides'] > 35) {
                $issues[] = ['recipe' => $recipe, 'issue' => 'Lipides eleves'];
            }
            foreach ($ingredients as $ing) {
                if ((float)$ing['quantite'] <= 0 || trim((string)$ing['unite']) === '') {
                    $issues[] = ['recipe' => $recipe, 'issue' => 'Ingredient invalide: ' . $ing['aliment_nom']];
                }
            }
        }
        return $issues;
    }

    public function stats(): array
    {
        $recipes = $this->recettes();
        $count = max(1, count($recipes));
        $avgCalories = array_sum(array_column(array_column($recipes, 'totals'), 'calories')) / $count;
        $avgProteines = array_sum(array_column(array_column($recipes, 'totals'), 'proteines')) / $count;
        $avgEco = array_sum(array_column(array_column($recipes, 'totals'), 'eco_score')) / $count;
        return [
            'aliments' => count($this->aliments()),
            'recettes' => count($recipes),
            'issues' => count($this->validationIssues()),
            'avg_calories' => round($avgCalories),
            'avg_proteines' => round($avgProteines, 1),
            'avg_eco' => round($avgEco, 1),
        ];
    }

    public function unbalancedRecipes(): array
    {
        $rows = [];
        foreach ($this->recettes() as $recipe) {
            $issues = array_values(array_filter($this->validationIssues(), fn($i) => (int)$i['recipe']['id'] === (int)$recipe['id']));
            if ($issues) {
                $rows[] = ['recipe' => $recipe, 'issues' => array_column($issues, 'issue')];
            }
        }
        return $rows;
    }

    public function proposeImprovements(int $recetteId): array
    {
        $totals = $this->totals($recetteId);
        $tips = [];
        foreach ($this->ingredients($recetteId) as $ing) {
            if ((float)$ing['lipides'] > 20 && (float)$ing['quantite'] > 50) {
                $tips[] = ['type' => 'quantity', 'label' => 'Reduire ' . $ing['aliment_nom'], 'ingredient_id' => $ing['id'], 'new_quantity' => round((float)$ing['quantite'] * 0.7, 1)];
            }
            if ($totals['proteines'] < 20 && (float)$ing['proteines'] > 10) {
                $tips[] = ['type' => 'quantity', 'label' => 'Augmenter ' . $ing['aliment_nom'], 'ingredient_id' => $ing['id'], 'new_quantity' => round((float)$ing['quantite'] * 1.25, 1)];
            }
        }
        return array_slice($tips, 0, 5);
    }

    public function applyImprovedCopy(int $recetteId): ?int
    {
        $recipe = $this->recette($recetteId);
        if (!$recipe) {
            return null;
        }
        [$ok] = $this->saveRecette([
            'nom' => $recipe['nom'] . ' (amelioree)',
            'description' => trim((string)$recipe['description']) . ' Version optimisee automatiquement.',
            'instructions' => $recipe['instructions'],
        ], array_map(function ($ing) {
            if ((float)$ing['lipides'] > 20) {
                $ing['quantite'] = round((float)$ing['quantite'] * 0.7, 1);
            }
            return $ing;
        }, $this->ingredients($recetteId)));
        return $ok ? (int)$this->db->lastInsertId() : null;
    }

    public function recommendRecipes(?int $recetteId = null, int $limit = 3): array
    {
        $recipes = $this->recettes('', 'performance_desc');
        if ($recetteId) {
            $recipes = array_values(array_filter($recipes, fn($r) => (int)$r['id'] !== $recetteId));
        }
        return array_slice($recipes, 0, $limit);
    }

    public function smartFridge(array $ids, string $objective): array
    {
        $aliments = array_values(array_filter($this->aliments(), fn($a) => in_array((int)$a['id'], $ids, true)));
        usort($aliments, function ($a, $b) use ($objective) {
            $scoreA = $this->foodObjectiveScore($a, $objective);
            $scoreB = $this->foodObjectiveScore($b, $objective);
            return $scoreB <=> $scoreA;
        });
        $chosen = array_slice($aliments, 0, 5);
        $totals = ['calories' => 0, 'proteines' => 0, 'glucides' => 0, 'lipides' => 0];
        foreach ($chosen as $a) {
            $totals['calories'] += (float)$a['calories'] * 1.5;
            $totals['proteines'] += (float)$a['proteines'] * 1.5;
            $totals['glucides'] += (float)$a['glucides'] * 1.5;
            $totals['lipides'] += (float)$a['lipides'] * 1.5;
        }
        return ['objective' => $objective, 'aliments' => $chosen, 'totals' => array_map(fn($v) => round($v, 1), $totals)];
    }

    private function foodObjectiveScore(array $a, string $objective): float
    {
        if ($objective === 'regime') {
            return -((float)$a['calories'] * 0.7) - ((float)$a['lipides'] * 1.2) + ((float)$a['proteines'] * 0.8) + $this->ecoScore($a);
        }
        if ($objective === 'musculation') {
            return ((float)$a['proteines'] * 2.2) - ((float)$a['lipides'] * 0.4) + $this->ecoScore($a);
        }
        return ((float)$a['proteines'] * 1.1) - ((float)$a['lipides'] * 0.5) + $this->ecoScore($a);
    }

    private function portion(float $qty, string $unit): float
    {
        return in_array(strtolower(trim($unit)), ['g', 'gr', 'gramme', 'grammes', 'ml', 'l'], true) ? max(0, $qty) / 100 : 1;
    }

    private function validateAliment(array $data): array
    {
        $errors = [];
        if (!preg_match("/^[A-Za-zÀ-ÿ0-9\\s'\\-]{2,}$/u", trim((string)($data['nom'] ?? '')))) {
            $errors[] = 'Nom aliment invalide.';
        }
        foreach (['calories', 'proteines', 'glucides', 'lipides'] as $field) {
            if (!is_numeric($data[$field] ?? null) || (float)$data[$field] < 0) {
                $errors[] = ucfirst($field) . ' invalide.';
            }
        }
        return $errors;
    }

    private function validateRecette(array $data, array $ingredients): array
    {
        $errors = [];
        if (strlen(trim((string)($data['nom'] ?? ''))) < 2) {
            $errors[] = 'Nom recette invalide.';
        }
        if (!$ingredients) {
            $errors[] = 'Ajoutez au moins un ingredient.';
        }
        return $errors;
    }
}
