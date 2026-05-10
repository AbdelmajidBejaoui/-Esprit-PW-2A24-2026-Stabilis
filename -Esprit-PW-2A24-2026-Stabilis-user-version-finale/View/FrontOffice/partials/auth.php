<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function frontofficeIsLoggedIn()
{
    return isset($_SESSION['front_user_id']);
}

function frontofficeLogin($user)
{
    session_regenerate_id(true);
    $_SESSION['front_user_id'] = (int) $user['id'];
    $_SESSION['front_user_nom'] = $user['nom'];
    $_SESSION['front_user_email'] = $user['email'];
}

function frontofficeLogout()
{
    unset($_SESSION['front_user_id'], $_SESSION['front_user_nom'], $_SESSION['front_user_email']);
    session_regenerate_id(true);
}

function frontofficeRequireLogin()
{
    if (!frontofficeIsLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}
?>