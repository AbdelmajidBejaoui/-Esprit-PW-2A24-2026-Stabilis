<?php
require_once __DIR__ . '/partials/auth.php';
require_once __DIR__ . '/../../Controller/UserC.php';

frontofficeRequireLogin();

$userC = new UserC();
$id = (int) $_SESSION['front_user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id > 0) {
    $userC->deleteUser($id);
    frontofficeLogout();
    header('Location: login.php?deleted=1');
    exit;
}

header('Location: updateUser.php');
exit;
