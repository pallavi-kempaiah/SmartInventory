<?php

session_start();

$role = $_SESSION["role"] ?? "user";

session_unset();
session_destroy();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

if ($role === "admin") {
    header("Location: admin-login.html");
} else {
    header("Location: login.html");
}

exit;

?>