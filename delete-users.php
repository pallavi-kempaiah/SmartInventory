<?php

session_start();

require_once "db.php";

/* Only logged-in admins can delete users */

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: admin-login.html");
    exit;
}

/* Only allow POST requests */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: admin-users.php");
    exit;
}

/* Get the member ID */

$userId = $_POST["id"] ?? "";

if (!is_numeric($userId)) {
    die("Invalid user.");
}

/* Prevent admin from deleting their own account */

if ((int)$userId === (int)$_SESSION["user_id"]) {
    die("You cannot delete your own admin account.");
}

/* Delete the member */

$stmt = $conn->prepare(
    "DELETE FROM users
     WHERE id = ?"
);

$stmt->bind_param("i", $userId);

if ($stmt->execute()) {
    header("Location: admin-users.php");
    exit;
} else {
    die("Unable to delete user.");
}

?>