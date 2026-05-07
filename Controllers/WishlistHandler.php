<?php
require_once __DIR__ . '/WishlistController.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../Views/front/shop.php');
    exit();
}

$productId = (int)($_POST['product_id'] ?? 0);
$controller = new WishlistController();
$errors = [];
$status = $controller->addRequest($_POST, $errors);

if ($status === 'available') {
    header('Location: ../Views/front/product.php?id=' . $productId);
    exit();
}

if (!in_array($status, ['sent', 'exists', 'invalid', 'error'], true)) {
    $status = 'error';
}

header('Location: ../Views/front/product.php?id=' . $productId . '&notify=' . $status);
exit();
