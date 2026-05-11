<?php
require_once __DIR__ . '/partials/auth.php';
require_once __DIR__ . '/../../Controller/AIGeneratorC.php';

$aiGen = new AIGeneratorC();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $goal = $_POST['goal'] ?? '';
    $niveau = $_POST['niveau'] ?? '';
    $prompt = $_POST['prompt'] ?? '';
    
    // Store in session to pass to custom_workout.php
    $_SESSION['ai_generate'] = [
        'goal' => $goal,
        'niveau' => $niveau,
        'prompt' => $prompt
    ];
    
    header('Location: custom_workout.php');
    exit;
}

header('Location: catalogue.php');
exit;
