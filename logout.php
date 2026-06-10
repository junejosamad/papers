<?php
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    logActivity($pdo, $_SESSION['user_id'], 'logout');
}

session_destroy();
header('Location: index.php');
exit;
?>