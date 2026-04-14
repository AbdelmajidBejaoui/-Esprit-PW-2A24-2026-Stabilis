<?php
$controller = $_GET['controller'] ?? 'aliment';
$action = $_GET['action'] ?? 'index';
$id = $_GET['id'] ?? null;

switch ($controller) {
    case 'aliment':
        require_once 'controllers/AlimentController.php';
        $controllerObj = new AlimentController();
        break;
    case 'recette':
        require_once 'controllers/RecetteController.php';
        $controllerObj = new RecetteController();
        break;
    default:
        die('Controller not found');
}

switch ($action) {
    case 'index':
        $controllerObj->index();
        break;
    case 'create':
        $controllerObj->create();
        break;
    case 'edit':
        if ($id) {
            $controllerObj->edit($id);
        }
        break;
    case 'delete':
        if ($id) {
            $controllerObj->delete($id);
        }
        break;
    default:
        die('Action not found');
}
?>