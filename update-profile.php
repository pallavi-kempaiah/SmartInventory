<?php

session_start();

require_once "db.php";

/* Only logged-in users can update their profile */
if (!isset($_SESSION["user_id"])) {
    header("Location: login.html");
    exit;
}

$userId = $_SESSION["user_id"];

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: profile.php");
    exit;
}

$fullName = trim($_POST["full_name"] ?? "");

if ($fullName === "") {
    die("Full name is required.");
}

if (strlen($fullName) > 100) {
    die("Full name is too long.");
}

/* Update name */
$stmt = $conn->prepare(
    "UPDATE users
     SET full_name = ?
     WHERE id = ?"
);

$stmt->bind_param("si", $fullName, $userId);

if ($stmt->execute()) {

    /* Update session name immediately */
    $_SESSION["full_name"] = $fullName;

    header("Location: profile.php?updated=success");
    exit;

} else {

    die("Unable to update profile.");

}

?>