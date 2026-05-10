<?php
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/controllers/DefisController.php';
require_once __DIR__ . '/../app/controllers/ParticipationsController.php';
require_once __DIR__ . '/../app/controllers/AiGeneratorController.php';
require_once __DIR__ . '/../app/controllers/AiWeeklyStoryController.php';

$db = Database::connect();

$entity = $_GET['entity'] ?? 'defis';
$action = $_GET['action'] ?? 'index';

if ($entity === 'participations') {
    $controller = new ParticipationsController($db);
} elseif ($entity === 'ai-generator') {
    $controller = new AiGeneratorController($db);
} elseif ($entity === 'ai-weekly-story') {
    $controller = new AiWeeklyStoryController($db);
} else {
    $controller = new DefisController($db);
}

switch ($action) {
    case 'create':
        $controller->create();
        break;
    case 'edit':
        $controller->edit();
        break;
    case 'delete':
        $controller->delete();
        break;
    default:
        $controller->index();
        break;
}
