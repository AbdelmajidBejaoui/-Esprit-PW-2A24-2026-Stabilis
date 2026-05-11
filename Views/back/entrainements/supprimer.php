<?php
require_once __DIR__ . '/../../../Controllers/EntrainementC.php';

$id = (int)($_GET['id'] ?? 0);
if ($id > 0) {
    (new EntrainementC())->delete($id);
}

header('Location: liste.php?deleted=1');
exit;


