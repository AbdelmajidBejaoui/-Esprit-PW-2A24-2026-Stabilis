<?php
require_once __DIR__ . '/../../../Controllers/SeanceC.php';

$id = (int)($_GET['id'] ?? 0);
if ($id > 0) {
    (new SeanceC())->deleteAdmin($id);
}

header('Location: seances.php?deleted=1');
exit;


