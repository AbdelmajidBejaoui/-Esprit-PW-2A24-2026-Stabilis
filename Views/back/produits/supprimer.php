<?php
require_once __DIR__ . '/../../../controllers/ProduitController.php';

if(isset($_GET['id'])) {
    $controller = new ProduitController();
    $controller->delete($_GET['id']);
    header('Location: liste.php?deleted=1');
    exit();
}

header('Location: liste.php');
exit();
?>