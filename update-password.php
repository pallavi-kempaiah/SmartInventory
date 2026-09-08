<?php

session_start();

require_once "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.html");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: profile.php");
    exit;
}

$userId = $_SESSION["user_id"];

$currentPassword = $_POST["current_password"] ?? "";
$newPassword = $_POST["new_password"] ?? "";
$confirmPassword = $_POST["confirm_password"] ?? "";

/* Basic validation */

if ($currentPassword === "" || $newPassword === "" || $confirmPassword === "") {
    die("All password fields are required.");
}

if (strlen($newPassword) < 6) {
    die("New password must contain at least 6 characters.");
}

if ($newPassword !== $confirmPassword) {
    die("New passwords do not match.");
}

/* Get current password */

$stmt = $conn->prepare(
    "SELECT password
     FROM users
     WHERE id = ?"
);

$stmt->bind_param("i", $userId);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    die("User not found.");
}

$user = $result->fetch_assoc();

/* Verify current password */

if (!password_verify($currentPassword, $user["password"])) {
    die("Current password is incorrect.");
}

/* Hash new password */

$newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

/* Update password */

$updateStmt = $conn->prepare(
    "UPDATE users
     SET password = ?
     WHERE id = ?"
);

$updateStmt->bind_param("si", $newPasswordHash, $userId);

if ($updateStmt->execute()) {

    header("Location: profile.php?password=changed");
    exit;

} else {

    die("Unable to change password.");

}

?>