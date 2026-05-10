<?php
require_once __DIR__ . '/../../Controller/UserC.php';

$userC = new UserC();
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id > 0) {
    $userC->deleteUser($id);
}

header('Location: listUsers.php');
exit;
