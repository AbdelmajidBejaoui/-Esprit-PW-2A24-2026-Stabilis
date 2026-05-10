<?php
$params = ['entity' => 'participations'];

if (isset($_GET['action'])) {
    $params['action'] = $_GET['action'];
}

if (isset($_GET['id'])) {
    $params['id'] = $_GET['id'];
}

header('Location: index.php?' . http_build_query($params));
exit();
