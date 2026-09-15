<?php
session_start();

require_once "db.php";

/* Only admins can access this page */
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: admin-login.html");
    exit;
}

/* Get member ID */
$memberId = $_GET["id"] ?? "";

if (!is_numeric($memberId)) {
    die("Invalid member.");
}

/* Get member details */
$memberStmt = $conn->prepare(
    "SELECT id, full_name, email
     FROM users
     WHERE id = ? AND role != 'admin'"
);

$memberStmt->bind_param("i", $memberId);
$memberStmt->execute();

$memberResult = $memberStmt->get_result();

if ($memberResult->num_rows !== 1) {
    die("Member not found.");
}

$member = $memberResult->fetch_assoc();

/* Get this member's products */
$productStmt = $conn->prepare(
    "SELECT id, product_name, category, quantity, price,
            purchase_date, expiry_date
     FROM products
     WHERE user_id = ?
     ORDER BY id DESC"
);

$productStmt->bind_param("i", $memberId);
$productStmt->execute();

$productResult = $productStmt->get_result();
$salesStmt = $conn->prepare(
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

$salesStmt->bind_param("i", $memberId);
$salesStmt->execute();

$salesResult = $salesStmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Member Inventory - Smart Inventory</title>

    <link rel="stylesheet" href="admin-dashboard.css">

    <style>

        .member-page {
            max-width: 1200px;
            margin: 0 auto;
            padding: 35px 20px;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #6c2bd9;
            text-decoration: none;
            font-weight: bold;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .member-header {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        .member-header h1 {
            margin: 0 0 8px;
            font-size: 30px;
        }

        .member-header p {
            margin: 4px 0;
            color: #666;
        }

        .section-title {
            margin: 25px 0 15px;
        }

        .section-title h2 {
            margin: 0 0 5px;
        }

        .section-title p {
            margin: 0;
            color: #777;
        }

        .products-container {
            background: white;
            border-radius: 12px;
            overflow-x: auto;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        .products-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 850px;
        }

        .products-table th {
            background: #6c2bd9;
            color: white;
            padding: 15px;
            text-align: left;
            font-size: 14px;
        }

        .products-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            color: #444;
            font-size: 14px;
        }

        .products-table tr:last-child td {
            border-bottom: none;
        }

        .product-name {
            font-weight: bold;
            color: #333;
        }

        .stock-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .in-stock {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .low-stock {
            background: #fff3e0;
            color: #ef6c00;
        }

        .out-of-stock {
            background: #ffebee;
            color: #c62828;
        }

        .empty-message {
            text-align: center;
            padding: 40px 20px;
            color: #777;
        }

        @media (max-width: 600px) {

            .member-page {
                padding: 25px 15px;
            }

            .member-header h1 {
                font-size: 25px;
            }

        }
 nav{
    display: flex;
    gap: 22px;
}

nav a {
    color: white;
    text-decoration: none;
    font-size: 15px;
}

nav a:hover {
    text-decoration: underline;
}
    </style>

</head>

<body>

<header class="navbar">

    <div class="logo">Smart Inventory</div>

    <nav>
        <a href="admin-dashboard.php">Dashboard</a>
        <a href="admin-users.php">Users</a>
        <a href="admin-inventory.php">Inventory</a>
        <a href="admin-exchange.php">Exchange Requests</a>
        <a href="admin-reports.php">Reports</a>
        <a href="logout.php">Logout</a>
    </nav>

</header>

<main class="member-page">

    <a href="admin-inventory.php" class="back-link">
        ← Back to Inventory Overview
    </a>

    <section class="member-header">

        <h1>
            <?php echo htmlspecialchars($member["full_name"]); ?>
        </h1>

        <p>
            📧 <?php echo htmlspecialchars($member["email"]); ?>
        </p>

    </section>


    <div class="section-title">

        <h2>Products</h2>

        <p>
            Products currently registered by this member.
        </p>

    </div>


    <div class="products-container">

        <?php if ($productResult->num_rows > 0): ?>

            <table class="products-table">

                <thead>

                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Purchase Date</th>
                        <th>Expiry Date</th>
                        <th>Stock Status</th>
                    </tr>

                </thead>

                <tbody>

                    <?php while ($product = $productResult->fetch_assoc()): ?>

                        <?php

                        if ($product["quantity"] == 0) {
                            $status = "Out of Stock";
                            $statusClass = "out-of-stock";
                        } elseif ($product["quantity"] <= 5) {
                            $status = "Low Stock";
                            $statusClass = "low-stock";
                        } else {
                            $status = "In Stock";
                            $statusClass = "in-stock";
                        }

                        ?>

                        <tr>

                            <td>
                                <span class="product-name">
                                    <?php echo htmlspecialchars($product["product_name"]); ?>
                                </span>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($product["category"]); ?>
                            </td>

                            <td>
                                <?php echo $product["quantity"]; ?>
                            </td>

                            <td>
                                ₹<?php echo number_format($product["price"], 2); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($product["purchase_date"] ?? "—"); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($product["expiry_date"] ?? "—"); ?>
                            </td>

                            <td>
                                <span class="stock-badge <?php echo $statusClass; ?>">
                                    <?php echo $status; ?>
                                </span>
                            </td>

                        </tr>

                    <?php endwhile; ?>

                </tbody>

            </table>

        <?php else: ?>

            <div class="empty-message">
                This member has no products in inventory.
            </div>

        <?php endif; ?>

    </div>
    <div class="section-title">

    <h2>Sales History</h2>

    <p>
        Sales recorded by this member.
    </p>

</div>

<div class="products-container">

    <?php if ($salesResult->num_rows > 0): ?>

        <table class="products-table">

            <thead>
                <tr>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Quantity Sold</th>
                    <th>Sale Date</th>
                </tr>
            </thead>

            <tbody>

                <?php while ($sale = $salesResult->fetch_assoc()): ?>

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
                            <?php echo $sale["quantity_sold"]; ?>
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
            No sales recorded by this member yet.
        </div>

    <?php endif; ?>

</div>

</main>

</body>
</html>