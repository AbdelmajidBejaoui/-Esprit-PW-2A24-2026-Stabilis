<?php
require_once __DIR__ . '/partials/auth.php';
requireLogin();
require_once __DIR__ . '/../../Controller/EntrainementC.php';
require_once __DIR__ . '/../../Controller/ProgrammeC.php';
require_once __DIR__ . '/../../Model/Entrainement.php';

$uid = frontUserId();
$eC = new EntrainementC();
$pC = new ProgrammeC();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['workout_data'])) {
    $workoutData = json_decode($_POST['workout_data'], true);
    $customPrompt = $_POST['custom_prompt'] ?? '';
    
    if ($workoutData && !empty($workoutData['exercises'])) {
        // Analyze exercises to generate smart workout name
        $exercises = $workoutData['exercises'];
        $categories = array_column($exercises, 'category');
        $exerciseNames = array_column($exercises, 'name');
        
        // Count categories
        $categoryCount = array_count_values($categories);
        arsort($categoryCount);
        $mainCategory = key($categoryCount);
        
        // Detect body parts from exercise names
        $bodyParts = [];
        $keywords = [
            'legs' => ['squat', 'lunge', 'leg', 'jambe', 'cuisse', 'mollet'],
            'chest' => ['push', 'press', 'chest', 'pec', 'pompe', 'poitrine'],
            'back' => ['pull', 'row', 'back', 'dos', 'traction'],
            'arms' => ['curl', 'tricep', 'bicep', 'bras', 'arm'],
            'shoulders' => ['shoulder', 'press', 'épaule', 'deltoid'],
            'abs' => ['crunch', 'plank', 'abs', 'abdo', 'core', 'ventre'],
            'full body' => ['burpee', 'thruster', 'clean', 'snatch', 'complet']
        ];
        
        foreach ($exerciseNames as $name) {
            $nameLower = strtolower($name);
            foreach ($keywords as $part => $words) {
                foreach ($words as $word) {
                    if (strpos($nameLower, $word) !== false) {
                        $bodyParts[$part] = ($bodyParts[$part] ?? 0) + 1;
                    }
                }
            }
        }
        
        // Generate smart name based on analysis
        arsort($bodyParts);
        $mainBodyPart = key($bodyParts);
        
        $nameTemplates = [
            'legs' => ['Leg Blast', 'Lower Body Power', 'Leg Day'],
            'chest' => ['Chest Builder', 'Upper Body Push', 'Chest Workout'],
            'back' => ['Back Strength', 'Pull Power', 'Back Day'],
            'arms' => ['Arm Pump', 'Biceps & Triceps', 'Arm Workout'],
            'shoulders' => ['Shoulder Burn', 'Deltoid Focus', 'Shoulder Day'],
            'abs' => ['Core Crusher', 'Abs Burner', 'Core Workout'],
            'full body' => ['Full Body Blast', 'Total Body', 'Complete Workout']
        ];
        
        // Category-based names
        $categoryNames = [
            'cardio' => ['Cardio Burn', 'Cardio Session', 'Heart Rate Blast'],
            'force' => ['Strength Training', 'Power Session', 'Muscle Builder'],
            'hiit' => ['HIIT Blast', 'Interval Training', 'HIIT Session'],
            'endurance' => ['Endurance Builder', 'Stamina Session', 'Endurance Workout'],
            'flexibilite' => ['Flexibility Flow', 'Stretch Session', 'Mobility Workout']
        ];
        
        // Choose name
        if ($mainBodyPart && isset($nameTemplates[$mainBodyPart])) {
            $workoutName = $nameTemplates[$mainBodyPart][array_rand($nameTemplates[$mainBodyPart])];
        } elseif (isset($categoryNames[$mainCategory])) {
            $workoutName = $categoryNames[$mainCategory][array_rand($categoryNames[$mainCategory])];
        } else {
            $workoutName = "AI Workout " . date('M d');
        }
        
        // Add intensity suffix based on niveau
        $intensitySuffix = [
            'debutant' => '',
            'intermediaire' => ' Pro',
            'avance' => ' Elite'
        ];
        $workoutName .= $intensitySuffix[$workoutData['niveau']] ?? '';
        
        // Calculate average MET value from exercises
        $totalMet = 0;
        foreach ($workoutData['exercises'] as $ex) {
            $totalMet += $ex['met_value'] ?? 5.0;
        }
        $avgMet = $totalMet / count($workoutData['exercises']);
        
        // Determine sport type based on categories
        $sportTypeMap = [
            'cardio' => 'Cardio',
            'force' => 'Musculation',
            'endurance' => 'Endurance',
            'hiit' => 'HIIT',
            'flexibilite' => 'Yoga'
        ];
        $sportType = $sportTypeMap[strtolower($mainCategory)] ?? 'Mixte';
        
        // Create description with exercise list
        $exerciseNames = array_slice(array_column($workoutData['exercises'], 'name'), 0, 5);
        $description = "Séance générée par IA: " . implode(', ', $exerciseNames);
        if (count($workoutData['exercises']) > 5) {
            $description .= '... (' . count($workoutData['exercises']) . ' exercices au total)';
        }
        $description .= "\n\nCalories totales: " . $workoutData['total_calories'] . " kcal";
        $description .= "\nDurée estimée: " . $workoutData['duree_estimee'] . " min";
        
        // Create the entrainement
        $entrainement = new Entrainement(
            null,
            $workoutName,
            $description,
            $sportType,
            $workoutData['niveau'],
            round($avgMet, 1),
            1, // is_custom = 1 (AI generated)
            $uid
        );
        
        $newId = $eC->insert($entrainement);
        
        // Create tutorial steps from exercises
        $etapes = [];
        foreach ($workoutData['exercises'] as $i => $ex) {
            $etapes[] = [
                'titre' => $ex['name'],
                'description' => ($ex['description'] ?? '') . "\n\n" 
                    . "📊 " . $ex['sets'] . " séries × " . $ex['reps'] . " reps"
                    . " | Repos: " . $ex['rest_sec'] . "s"
                    . " | Calories: " . round($ex['calories'], 1) . " kcal"
            ];
        }
        
        $eC->insertEtapes($newId, $etapes);
        
        // Add to user's program
        $pC->add($uid, $newId);
        
        flash('success', '✅ Séance IA sauvegardée et ajoutée à votre programme !');
        header('Location: programme.php');
        exit;
    }
}

flash('error', 'Erreur lors de la sauvegarde de la séance.');
header('Location: custom_workout.php');
exit;
