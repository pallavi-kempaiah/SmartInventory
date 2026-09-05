<?php

session_start();

require_once "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.html");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $userId = $_SESSION["user_id"];

    $productName = trim($_POST["product_name"] ?? "");
    $category = trim($_POST["category"] ?? "");
    $quantity = $_POST["quantity"] ?? "";
    $price = $_POST["price"] ?? "";
    $purchaseDate = $_POST["purchase_date"] ?? "";
    $expiryDate = $_POST["expiry_date"] ?? "";

    if ($productName === "" || $quantity === "" || $price === "") {
        die("Product name, quantity and price are required.");
    }

    if ($quantity < 0 || $price < 0) {
        die("Quantity and price cannot be negative.");
    }

    $stmt = $conn->prepare(
        "INSERT INTO products
        (user_id, product_name, category, quantity, price, purchase_date, expiry_date)
        VALUES (?, ?, ?, ?, ?, ?, ?)"
    );

    $stmt->bind_param(
        "issidss",
        $userId,
        $productName,
        $category,
        $quantity,
        $price,
        $purchaseDate,
        $expiryDate
    );

    if ($stmt->execute()) {

        echo "Product added successfully!";

        echo '<br><br>';
        echo '<a href="add-product.php">Add another product</a>';
        echo '<br>';
        echo '<a href="dashboard.php">Go to Dashboard</a>';

    } else {

        echo "Unable to add product. Please try again.";

    }

    $stmt->close();
    $conn->close();
}

?>