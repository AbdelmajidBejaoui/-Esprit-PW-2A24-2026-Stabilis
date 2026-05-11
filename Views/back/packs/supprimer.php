<?php
require_once __DIR__ . '/../../../controllers/PackController.php';

$id = (int)($_GET['id'] ?? 0);
$controller = new PackController();
if ($id > 0) {
    $controller->delete($id);
}

header('Location: ../produits/liste.php?pack_deleted=1');
exit();
