<?php
require_once __DIR__ . '/../../../controllers/CommandeController.php';

if (isset($_GET['id'])) {
    $controller = new CommandeController();
    $controller->delete(intval($_GET['id']));
    header('Location: liste.php?deleted=1');
    exit();
}

header('Location: liste.php');
exit();
?>
