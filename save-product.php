<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.html");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: add-product.php");
    exit;
}

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

$success = $stmt->execute();

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Added - Smart Inventory</title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f5f3fa;
            min-height: 100vh;
        }

        .navbar {
            background: #6c2bd9;
            padding: 18px 40px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar h2 {
            font-size: 22px;
        }

        .navbar a {
            color: white;
            text-decoration: none;
            font-weight: bold;
        }

        .success-container {
            min-height: calc(100vh - 70px);
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 30px;
        }

        .success-card {
            background: white;
            width: 100%;
            max-width: 500px;
            padding: 40px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        }

        .success-icon {
            font-size: 55px;
            margin-bottom: 15px;
        }

        .success-card h1 {
            color: #333;
            margin-bottom: 10px;
        }

        .success-card p {
            color: #666;
            margin-bottom: 25px;
        }

        .product-name {
            color: #6c2bd9;
            font-weight: bold;
        }

        .buttons {
            display: flex;
            justify-content: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 20px;
            border-radius: 7px;
            text-decoration: none;
            font-weight: bold;
        }

        .btn-primary {
            background: #6c2bd9;
            color: white;
        }

        .btn-secondary {
            border: 1px solid #6c2bd9;
            color: #6c2bd9;
            background: white;
        }

        .error {
            color: #d32f2f;
        }
    </style>
</head>

<body>

    <nav class="navbar">
        <h2>Smart Inventory</h2>
        <a href="dashboard.php">Dashboard</a>
    </nav>

    <div class="success-container">

        <div class="success-card">

            <?php if ($success): ?>

                <div class="success-icon">✅</div>

                <h1>Product Added Successfully!</h1>

                <p>
                    <span class="product-name">
                        <?php echo htmlspecialchars($productName); ?>
                    </span>
                    has been added to your inventory.
                </p>

                <div class="buttons">
                    <a href="add-product.php" class="btn btn-primary">
                        Add Another Product
                    </a>

                    <a href="inventory.php" class="btn btn-secondary">
                        View Inventory
                    </a>

                    <a href="dashboard.php" class="btn btn-secondary">
                        Dashboard
                    </a>
                </div>

            <?php else: ?>

                <div class="success-icon">❌</div>

                <h1>Unable to Add Product</h1>

                <p class="error">
                    Something went wrong. Please try again.
                </p>

                <div class="buttons">
                    <a href="add-product.php" class="btn btn-primary">
                        Try Again
                    </a>

                    <a href="dashboard.php" class="btn btn-secondary">
                        Dashboard
                    </a>
                </div>

            <?php endif; ?>

        </div>

    </div>

</body>
</html>