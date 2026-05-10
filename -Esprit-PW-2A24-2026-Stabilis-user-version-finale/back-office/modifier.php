<?php
$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: index.php');
    exit();
}
header('Location: index.php?action=edit&id=' . urlencode($id));
exit();
