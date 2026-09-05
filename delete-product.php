<?php

session_start();

require_once "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.html");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: inventory.php");
    exit;
}

$userId = $_SESSION["user_id"];

$productId = $_POST["id"] ?? "";

if (!is_numeric($productId)) {
    die("Invalid product.");
}


/* Delete only the logged-in user's product */

$stmt = $conn->prepare(
    "DELETE FROM products
     WHERE id = ? AND user_id = ?"
);

$stmt->bind_param(
    "ii",
    $productId,
    $userId
);


if ($stmt->execute()) {

    header("Location: inventory.php");
    exit;

} else {

    die("Unable to delete product.");

}

?>