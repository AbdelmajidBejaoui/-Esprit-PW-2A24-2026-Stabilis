<?php
$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: index.php');
    exit();
}
header('Location: index.php?action=delete&id=' . urlencode($id));
exit();
