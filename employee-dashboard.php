<?php

session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION["user_id"])) {
    header("Location: login.html");
    exit;
}

require_once "db.php";

$userId = (int) $_SESSION["user_id"];

/*
|--------------------------------------------------------------------------
| Verify employee directly from database
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare(
    "SELECT
        id,
        full_name,
        email,
        role,
        shop_id,
        account_status
     FROM users
     WHERE id = ?
     LIMIT 1"
);

$stmt->bind_param("i", $userId);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {

    $stmt->close();
    $conn->close();

    session_unset();
    session_destroy();

    header("Location: login.html");
    exit;
}

$user = $result->fetch_assoc();

$stmt->close();

/*
|--------------------------------------------------------------------------
| EMPLOYEE-ONLY PROTECTION
|--------------------------------------------------------------------------
*/

if ($user["role"] !== "employee") {

    $conn->close();

    header("Location: dashboard.php");
    exit;
}

if (
    $user["account_status"] !== "approved" ||
    empty($user["shop_id"])
) {

    $conn->close();

    session_unset();
    session_destroy();

    header("Location: login.html?access=pending");
    exit;
}

$shopId = (int) $user["shop_id"];

/*
|--------------------------------------------------------------------------
| Get shop name
|--------------------------------------------------------------------------
*/

$shopStmt = $conn->prepare(
    "SELECT shop_name
     FROM shops
     WHERE id = ?
     LIMIT 1"
);

$shopStmt->bind_param("i", $shopId);
$shopStmt->execute();

$shopResult = $shopStmt->get_result();

if ($shopResult->num_rows !== 1) {

    $shopStmt->close();
    $conn->close();

    die("Your shop could not be found.");
}

$shop = $shopResult->fetch_assoc();

$shopName = $shop["shop_name"];

$shopStmt->close();

/*
|--------------------------------------------------------------------------
| Current statistics
|
| TEMPORARY:
| Still user_id until shared inventory migration.
|--------------------------------------------------------------------------
*/
/*
|--------------------------------------------------------------------------
| Shop-wide statistics
|--------------------------------------------------------------------------
| All approved employees and the owner share the same inventory.
| Therefore statistics must be calculated using shop_id.
|--------------------------------------------------------------------------
*/

$statsStmt = $conn->prepare(
    "SELECT
        COUNT(*) AS total_products,
        COALESCE(SUM(quantity), 0) AS total_stock,
        COALESCE(
            SUM(
                CASE
                    WHEN quantity > 0 AND quantity <= 5
                    THEN 1
                    ELSE 0
                END
            ),
            0
        ) AS low_stock
     FROM products
     WHERE shop_id = ?"
);

$statsStmt->bind_param("i", $shopId);
$statsStmt->execute();

$stats = $statsStmt->get_result()->fetch_assoc();

$totalProducts = $stats["total_products"];
$totalStock = $stats["total_stock"];
$lowStock = $stats["low_stock"];

$statsStmt->close();

$conn->close();

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Employee Dashboard | Smart Inventory</title>

    <link
        rel="stylesheet"
        href="dashboard.css"
    >

    <style>

        .shop-label {
            display: inline-block;
            margin-top: 5px;
            padding: 5px 10px;
            border-radius: 20px;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 13px;
            font-weight: 600;
        }

    </style>

</head>

<body>

<nav class="navbar">

    <div class="logo">
        Smart Inventory
    </div>

    <div class="nav-links">

        <a href="employee-dashboard.php">
            Dashboard
        </a>

        <a href="inventory.php">
            Products
        </a>

        <a href="add-product.php">
            Add Product
        </a>

        <a href="profile.php">
            Profile
        </a>

        <a href="logout.php">
            Logout
        </a>

    </div>

</nav>


<main class="dashboard">

    <div class="welcome-section">

        <h1>

            Welcome,
            <?php
            echo htmlspecialchars($user["full_name"]);
            ?>
            👋

        </h1>

        <p>
            Manage products and stock for your shop.
        </p>

        <span class="shop-label">

            🏪
            <?php
            echo htmlspecialchars($shopName);
            ?>

        </span>

    </div>


    <!-- EMPLOYEE STATISTICS -->

   <section class="dashboard-cards">

    <div class="card">

        <h3>Total Products</h3>

        <p class="number">
            <?php echo $totalProducts; ?>
        </p>

        <span>
            Products in shop inventory
        </span>

    </div>


    <a
        href="inventory.php?stock_status=low_stock"
        class="card"
    >

        <h3>Low Stock</h3>

        <p class="number">
            <?php echo $lowStock; ?>
        </p>

        <span>
            Products needing attention
        </span>

    </a>


    <a
        href="ai-dead-stock.php"
        class="card"
    >

        <h3>Dead Stock</h3>

        <p class="number">
            AI
        </p>

        <span>
            Find products that are not selling
        </span>

    </a>


    <a
        href="#"
        class="card"
    >

        <h3>Exchange</h3>

        <p class="number">
            0
        </p>

        <span>
            Available exchange opportunities
        </span>

    </a>

</section>


    <!-- EMPLOYEE ACTIONS -->

    <section class="quick-actions">

    <h2>
        Inventory Actions
    </h2>

    <div class="action-container">

        <!-- ADD PRODUCT -->

        <a
            href="add-product.php"
            class="action-card"
        >

            <h3>
                ➕ Add Product
            </h3>

            <p>
                Add stock to your shop inventory.
            </p>

        </a>


        <!-- VIEW INVENTORY -->

        <a
            href="inventory.php"
            class="action-card"
        >

            <h3>
                📦 View Inventory
            </h3>

            <p>
                View and manage shop products.
            </p>

        </a>


        <!-- SCAN RECEIPT -->

        <a
            href="ocr-stock-in.php"
            class="action-card"
        >

            <h3>
                📷 Scan Receipt
            </h3>

            <p>
                Scan a receipt and automatically add products.
            </p>

        </a>


        <!-- SCAN PRODUCT -->

        <a
            href="scan-product.php"
            class="action-card"
        >

            <h3>
                🤖 Scan Product
            </h3>

            <p>
                Recognize products using AI.
            </p>

        </a>


        <!-- STOCK MANAGEMENT -->

       


        <!-- AI INSIGHTS / DEAD STOCK -->

        <a
            href="ai-dead-stock.php"
            class="action-card"
        >

            <h3>
                🤖 AI Insights
            </h3>

            <p>
                Identify dead stock and products with low demand.
            </p>

        </a>


        <!-- EXCHANGE -->

        <a
            href="#"
            class="action-card"
        >

            <h3>
                🔄 Exchange
            </h3>

            <p>
                View and manage inventory exchange opportunities.
            </p>

        </a>

    </div>

</section>

</main>

</body>

</html>