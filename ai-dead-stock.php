<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.html");
    exit;
}

$userId = $_SESSION["user_id"];

/*
    Dead Stock Detection Logic

    A product is considered dead stock when:
    - It has stock available
    - AND it has never been sold
      OR
    - Its last sale was 30 or more days ago
*/

$sql = "
    SELECT
        p.id,
        p.product_name,
        p.category,
        p.quantity,
        p.price,
        p.purchase_date,
        p.expiry_date,
        MAX(ps.sale_date) AS last_sale_date,
        COALESCE(SUM(ps.quantity_sold), 0) AS total_sold

    FROM products p

    LEFT JOIN product_sales ps
        ON p.id = ps.product_id
        AND ps.user_id = p.user_id

    WHERE p.user_id = ?

    GROUP BY
        p.id,
        p.product_name,
        p.category,
        p.quantity,
        p.price,
        p.purchase_date,
        p.expiry_date

    ORDER BY p.id DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();

$result = $stmt->get_result();

$deadStockCount = 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>AI Dead Stock Detection</title>

    <link rel="stylesheet" href="dashboard.css">

    <style>
        .ai-container {
            max-width: 1100px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .ai-header {
            margin-bottom: 25px;
        }

        .ai-header h1 {
            margin-bottom: 8px;
        }

        .ai-header p {
            color: #666;
        }

        .ai-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        .product-row {
            display: grid;
            grid-template-columns: 1.5fr 1fr 1fr 1fr 1.5fr;
            gap: 15px;
            align-items: center;
            padding: 18px 0;
            border-bottom: 1px solid #eee;
        }

        .product-row:last-child {
            border-bottom: none;
        }

        .product-name {
            font-weight: bold;
        }

        .status-dead {
            display: inline-block;
            background: #ffe1e1;
            color: #c62828;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: bold;
        }

        .status-active {
            display: inline-block;
            background: #e1f7e8;
            color: #218838;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: bold;
        }

        .summary-box {
            background: #f5f1ff;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .summary-number {
            font-size: 32px;
            font-weight: bold;
            color: #6c2bd9;
        }

        .back-btn {
            display: inline-block;
            margin-bottom: 20px;
            text-decoration: none;
            color: #6c2bd9;
            font-weight: bold;
        }

        @media (max-width: 800px) {
            .product-row {
                grid-template-columns: 1fr;
                gap: 8px;
            }
        }
         nav a {
    color: white;
    text-decoration: none;
    font-size: 15px;
}
nav a:hover {
    text-decoration: underline;
}
nav {
    display: flex;
    gap: 25px;
}
    </style>
</head>

<body>

<header class="navbar">
    <div class="logo">Smart Inventory</div>

    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="inventory.php">Inventory</a>
        <a href="add-product.php">Add Product</a>
        <a href="sales-history.php">Sales History</a>
        <a href="profile.php">Profile</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<div class="ai-container">

    <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>

    <div class="ai-header">
        <h1>🤖 AI Dead Stock Detection</h1>
        <p>
            The system analyzes your inventory and sales history
            to identify products that may be becoming dead stock.
        </p>
    </div>

    <?php
    /*
        We first calculate the dead-stock count separately
        so the summary number is accurate.
    */

    $deadStockCount = 0;

    $result->data_seek(0);

    while ($product = $result->fetch_assoc()) {

        if ((int)$product["quantity"] <= 0) {
            continue;
        }

        $isDeadStock = false;

        if ($product["last_sale_date"] === null) {
            $isDeadStock = true;
        } else {

            $lastSale = new DateTime($product["last_sale_date"]);
            $today = new DateTime();

            $daysSinceSale = $today->diff($lastSale)->days;

            if ($daysSinceSale >= 30) {
                $isDeadStock = true;
            }
        }

        if ($isDeadStock) {
            $deadStockCount++;
        }
    }

    $result->data_seek(0);
    ?>

    <div class="summary-box">
        <div>Potential Dead Stock Items</div>
        <div class="summary-number">
            <?php echo $deadStockCount; ?>
        </div>
        <div>
            Based on current stock and sales activity
        </div>
    </div>

    <div class="ai-card">

        <h2>Inventory Analysis</h2>

        <?php if ($result->num_rows > 0): ?>

            <?php while ($product = $result->fetch_assoc()): ?>

                <?php
                $isDeadStock = false;
                $daysSinceSale = null;

                if ((int)$product["quantity"] > 0) {

                    if ($product["last_sale_date"] === null) {
                        $isDeadStock = true;
                    } else {

                        $lastSale = new DateTime($product["last_sale_date"]);
                        $today = new DateTime();

                        $daysSinceSale = $today->diff($lastSale)->days;

                        if ($daysSinceSale >= 30) {
                            $isDeadStock = true;
                        }
                    }
                }

                if ($isDeadStock) {
                    $status = "Potential Dead Stock";
                    $statusClass = "status-dead";
                } else {
                    $status = "Active";
                    $statusClass = "status-active";
                }
                ?>

                <div class="product-row">

                    <div>
                        <div class="product-name">
                            <?php echo htmlspecialchars($product["product_name"]); ?>
                        </div>

                        <small>
                            <?php echo htmlspecialchars($product["category"]); ?>
                        </small>
                    </div>

                    <div>
                        <strong>Stock</strong><br>
                        <?php echo $product["quantity"]; ?>
                    </div>

                    <div>
                        <strong>Total Sold</strong><br>
                        <?php echo $product["total_sold"]; ?>
                    </div>

                    <div>
                        <strong>Last Sale</strong><br>

                        <?php
                        if ($product["last_sale_date"] === null) {
                            echo "Never";
                        } else {
                            echo htmlspecialchars($product["last_sale_date"]);
                        }
                        ?>
                    </div>

                    <div>
                        <span class="<?php echo $statusClass; ?>">
                            <?php echo $status; ?>
                        </span>

                        <?php if ($isDeadStock): ?>
                            <br>
                            <small>
                                Consider selling or exchanging this stock.
                            </small>
                        <?php endif; ?>
                    </div>

                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <p>No products found.</p>

        <?php endif; ?>

    </div>

</div>

</body>
</html>