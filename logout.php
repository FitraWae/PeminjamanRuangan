<?php
require_once __DIR__ . '/boothstrap.php';

$_SESSION = [];
session_destroy();

header('Location: /project/login.php');
exit;
