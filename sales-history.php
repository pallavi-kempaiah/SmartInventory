<?php
session_start();

require_once "db.php";

/* Only logged-in users can view sales history */
if (!isset($_SESSION["user_id"])) {
    header("Location: login.html");
    exit;
}

$userId = $_SESSION["user_id"];

/* Get this user's sales */
$stmt = $conn->prepare(
    "SELECT
        ps.id,
        p.product_name,
        p.category,
        ps.quantity_sold,
        ps.sale_date,
        ps.created_at
     FROM product_sales ps
     INNER JOIN products p
        ON ps.product_id = p.id
     WHERE ps.user_id = ?
     ORDER BY ps.sale_date DESC, ps.id DESC"
);

$stmt->bind_param("i", $userId);
$stmt->execute();

$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Sales History - Smart Inventory</title>

    <link rel="stylesheet" href="dashboard.css">

    <style>

        .sales-page {
            max-width: 1100px;
            margin: 0 auto;
            padding: 35px 20px;
        }

        .page-header {
            margin-bottom: 25px;
        }

        .page-header h1 {
            margin: 0 0 8px;
            font-size: 32px;
        }

        .page-header p {
            margin: 0;
            color: #777;
        }

        .sales-container {
            background: white;
            border-radius: 12px;
            overflow-x: auto;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        .sales-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 650px;
        }

        .sales-table th {
            background: #6c2bd9;
            color: white;
            padding: 15px;
            text-align: left;
            font-size: 14px;
        }

        .sales-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            color: #444;
            font-size: 14px;
        }

        .sales-table tr:last-child td {
            border-bottom: none;
        }

        .product-name {
            font-weight: bold;
            color: #333;
        }

        .quantity {
            font-weight: bold;
            color: #6c2bd9;
        }

        .empty-message {
            text-align: center;
            padding: 45px 20px;
            color: #777;
        }

        @media (max-width: 600px) {

            .sales-page {
                padding: 25px 15px;
            }

            .page-header h1 {
                font-size: 26px;
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
        <a href="logout.php">Logout</a>
    </nav>

</header>


<main class="sales-page">

    <div class="page-header">

        <h1>Sales History</h1>

        <p>
            View the sales recorded from your inventory.
        </p>

    </div>


    <div class="sales-container">

        <?php if ($result->num_rows > 0): ?>

            <table class="sales-table">

                <thead>

                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Quantity Sold</th>
                        <th>Sale Date</th>
                    </tr>

                </thead>

                <tbody>

                    <?php while ($sale = $result->fetch_assoc()): ?>

                        <tr>

                            <td>
                                <span class="product-name">
                                    <?php echo htmlspecialchars($sale["product_name"]); ?>
                                </span>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($sale["category"]); ?>
                            </td>

                            <td>
                                <span class="quantity">
                                    <?php echo $sale["quantity_sold"]; ?>
                                </span>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($sale["sale_date"]); ?>
                            </td>

                        </tr>

                    <?php endwhile; ?>

                </tbody>

            </table>

        <?php else: ?>

            <div class="empty-message">
                No sales recorded yet.
            </div>

        <?php endif; ?>

    </div>

</main>

</body>
</html>