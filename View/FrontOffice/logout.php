<?php
require_once __DIR__ . '/partials/auth.php';

frontofficeLogout();
header('Location: login.php?logout=1');
exit;
