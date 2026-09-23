<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.html");
    exit;
}

$ocrData = $_SESSION["ocr_data"] ?? [];

$transactionType = $_GET["type"] ?? "in";

if (!in_array($transactionType, ["in", "out"])) {
    $transactionType = "in";
}
if (empty($ocrData)) {
    header("Location: ocr-stock-in.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Review OCR Data</title>

    <link rel="stylesheet" href="dashboard.css">

    <style>

        .review-container {
            max-width: 1100px;
            margin: 50px auto;
            padding: 20px;
        }

        .review-card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        .review-card h1 {
            margin-bottom: 10px;
        }

        .review-card p {
            color: #666;
            margin-bottom: 25px;
        }

        .review-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .review-table th,
        .review-table td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }

        .review-table th {
            background: #f5f5f5;
        }

        .review-table input,
        .review-table select {
            width: 100%;
            padding: 9px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
        }

        .confirm-btn {
            margin-top: 25px;
            background: #6c2bd9;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 7px;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
        }

        .confirm-btn:hover {
            background: #5720b7;
        }

        .back-btn {
            display: inline-block;
            margin-bottom: 20px;
            text-decoration: none;
            color: #6c2bd9;
            font-weight: bold;
        }

        .warning {
            background: #fff7e6;
            border: 1px solid #f0c36d;
            padding: 12px;
            border-radius: 7px;
            margin-bottom: 20px;
        }

        @media (max-width: 900px) {

            .review-card {
                overflow-x: auto;
            }

            .review-table {
                min-width: 850px;
                font-size: 13px;
            }

            .review-table th,
            .review-table td {
                padding: 8px;
            }

        }

    </style>

</head>

<body>

<header class="navbar">

    <div class="logo">
        Smart Inventory
    </div>

    <nav>

        <a href="dashboard.php">Dashboard</a>
        <a href="inventory.php">Inventory</a>
        <a href="add-product.php">Add Product</a>
        <a href="sales-history.php">Sales History</a>
        <a href="profile.php">Profile</a>
        <a href="logout.php">Logout</a>

    </nav>

</header>


<div class="review-container">

    <a href="ocr-stock-in.php" class="back-btn">
        ← Scan Another Receipt
    </a>


    <div class="review-card">

        <h1>
<?php
if ($transactionType === "out") {
    echo "📤 Review Stock Out";
} else {
    echo "📥 Review Stock In";
}
?>
</h1>

<p>
<?php
if ($transactionType === "out") {
    echo "Review the products and quantities that will be deducted from your inventory.";
} else {
    echo "Review the products and quantities that will be added to your inventory.";
}
?>
</p>


        <div class="warning">

            ⚠️ OCR can sometimes make mistakes.
            Please verify the product name, category, quantity,
            price and dates before confirming.

        </div>


        <form action="ocr-confirm-stock.php" method="post">

        <input
    type="hidden"
    name="transaction_type"
    value="<?php echo htmlspecialchars($transactionType); ?>"
>
            <table class="review-table">

                <thead>

                    <tr>

                        <th>Product</th>
                        <th>Category</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Purchase Date</th>
                        <th>Expiry Date</th>

                    </tr>

                </thead>


                <tbody>

                    <?php foreach ($ocrData as $index => $product): ?>

                    <tr>

                        <!-- Product -->

                        <td>

                            <input
                                type="text"
                                name="products[<?php echo $index; ?>][product]"
                                value="<?php echo htmlspecialchars($product["product"] ?? ""); ?>"
                                required
                            >

                        </td>


                        <!-- Category -->

                        <td>
<?php

$predictedCategory = $product["category"] ?? "Other";

$allowedCategories = [
    "Food",
    "Beverages",
    "Dairy",
    "Fruits & Vegetables",
    "Electronics",
    "Clothing",
    "Personal Care",
    "Household",
    "Stationery",
    "Other"
];

if (!in_array($predictedCategory, $allowedCategories, true)) {
    $predictedCategory = "Other";
}

?>

<select
    name="products[<?php echo $index; ?>][category]"
    required
>

    <?php foreach ($allowedCategories as $category): ?>

        <option
            value="<?php echo htmlspecialchars($category); ?>"
            <?php
            echo ($predictedCategory === $category)
                ? "selected"
                : "";
            ?>
        >
            <?php echo htmlspecialchars($category); ?>
        </option>

    <?php endforeach; ?>

</select>

<small style="display:block; margin-top:5px; color:#666;">
    🤖 AI predicted category
</small>

                        </td>


                        <!-- Quantity -->

                        <td>

                            <input
                                type="number"
                                name="products[<?php echo $index; ?>][quantity]"
                                value="<?php echo (int)($product["quantity"] ?? 1); ?>"
                                min="1"
                                required
                            >

                        </td>


                        <!-- Price -->

                        <td>

                            <input
                                type="number"
                                name="products[<?php echo $index; ?>][price]"
                                value="<?php echo htmlspecialchars($product["price"] ?? "0"); ?>"
                                min="0"
                                step="0.01"
                                required
                            >

                        </td>


                        <!-- Purchase Date -->

                        <td>

                            <input
                                type="date"
                                name="products[<?php echo $index; ?>][purchase_date]"
                                value="<?php echo date('Y-m-d'); ?>"
                                required
                            >

                        </td>


                        <!-- Expiry Date -->

                        <td>

                            <input
                                type="date"
                                name="products[<?php echo $index; ?>][expiry_date]"
                            >

                        </td>

                    </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>


            <button type="submit" class="confirm-btn">
<?php
if ($transactionType === "out") {
    echo "📤 Confirm Stock Out";
} else {
    echo "✅ Confirm & Add to Inventory";
}
?>
</button>

        </form>

    </div>

</div>

</body>

</html>