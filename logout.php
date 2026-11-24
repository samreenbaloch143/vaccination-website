<?php
session_start();
$wasAdmin = (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin');
$_SESSION = [];
session_destroy();
if ($wasAdmin) {
    header("Location: admin/login.php");
} else {
    header("Location: login.php");
}
exit;