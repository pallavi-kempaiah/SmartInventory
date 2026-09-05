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
$productName = trim($_POST["product_name"] ?? "");
$category = trim($_POST["category"] ?? "");
$quantity = $_POST["quantity"] ?? "";
$price = $_POST["price"] ?? "";
$purchaseDate = $_POST["purchase_date"] ?? "";
$expiryDate = $_POST["expiry_date"] ?? "";


if (!is_numeric($productId)) {
    die("Invalid product.");
}

if ($productName === "" || $quantity === "" || $price === "") {
    die("Product name, quantity and price are required.");
}

if ($quantity < 0 || $price < 0) {
    die("Quantity and price cannot be negative.");
}


/* Update only the logged-in user's product */

$stmt = $conn->prepare(
    "UPDATE products
     SET product_name = ?,
         category = ?,
         quantity = ?,
         price = ?,
         purchase_date = ?,
         expiry_date = ?
     WHERE id = ? AND user_id = ?"
);

$stmt->bind_param(
    "ssidssii",
    $productName,
    $category,
    $quantity,
    $price,
    $purchaseDate,
    $expiryDate,
    $productId,
    $userId
);


if ($stmt->execute()) {

    header("Location: inventory.php");
    exit;

} else {

    die("Unable to update product.");

}

?>