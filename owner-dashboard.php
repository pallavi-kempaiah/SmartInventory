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
| Verify owner directly from database
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare(
    "SELECT id, full_name, email, role, shop_id, account_status
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

if ($user["role"] !== "owner") {
    $conn->close();
    header("Location: dashboard.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Get owner's shop
|--------------------------------------------------------------------------
*/

$shopStmt = $conn->prepare(
    "SELECT id, shop_name
     FROM shops
     WHERE owner_user_id = ?
     LIMIT 1"
);

$shopStmt->bind_param("i", $userId);
$shopStmt->execute();

$shopResult = $shopStmt->get_result();

if ($shopResult->num_rows !== 1) {
    $shopStmt->close();
    $conn->close();

    die("Your owner account is not connected to a shop.");
}

$shop = $shopResult->fetch_assoc();

$shopId = (int) $shop["id"];
$shopName = $shop["shop_name"];

$shopStmt->close();

/*
|--------------------------------------------------------------------------
| Pending employee requests
|--------------------------------------------------------------------------
*/

$requestStmt = $conn->prepare(
    "SELECT
        r.id AS request_id,
        u.full_name,
        u.email,
        r.created_at
     FROM shop_join_requests r
     INNER JOIN users u
        ON r.employee_user_id = u.id
     WHERE r.shop_id = ?
       AND r.status = 'pending'
       AND u.role = 'employee'
     ORDER BY r.created_at DESC"
);

$requestStmt->bind_param("i", $shopId);
$requestStmt->execute();

$pendingRequests = $requestStmt->get_result();

$pendingRequestCount = $pendingRequests->num_rows;

$requestStmt->close();

/*
|--------------------------------------------------------------------------
| Approved employees
|--------------------------------------------------------------------------
*/

$employeeStmt = $conn->prepare(
    "SELECT id, full_name, email
     FROM users
     WHERE shop_id = ?
       AND role = 'employee'
       AND account_status = 'approved'
     ORDER BY full_name ASC"
);

$employeeStmt->bind_param("i", $shopId);
$employeeStmt->execute();

$employees = $employeeStmt->get_result();

$employeeStmt->close();

/*
|--------------------------------------------------------------------------
| Inventory statistics
|
| IMPORTANT:
| We will change this to shop_id after the shared inventory migration.
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

    <title>Owner Dashboard | Smart Inventory</title>

    <link
        rel="stylesheet"
        href="dashboard.css"
    >

    <style>

        .owner-section {
            margin-top: 35px;
        }

        .owner-box {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
        }

        .owner-box h2 {
            margin-bottom: 8px;
        }

        .owner-box-subtitle {
            color: #64748b;
            margin-bottom: 20px;
        }

        .request-row,
        .employee-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            padding: 16px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .request-row:last-child,
        .employee-row:last-child {
            border-bottom: none;
        }

        .person-name {
            font-weight: 600;
            margin-bottom: 4px;
        }

        .person-email {
            color: #64748b;
            font-size: 14px;
        }

        .owner-actions {
            display: flex;
            gap: 10px;
            flex-shrink: 0;
        }

        .owner-btn {
            border: none;
            padding: 9px 15px;
            border-radius: 7px;
            cursor: pointer;
            font-weight: 600;
        }

        .allow-btn {
            background: #16a34a;
            color: white;
        }

        .reject-btn,
        .remove-btn {
            background: #dc2626;
            color: white;
        }

        .empty-owner-message {
            color: #64748b;
            padding: 10px 0;
        }

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

        @media (max-width: 700px) {

            .request-row,
            .employee-row {
                flex-direction: column;
                align-items: flex-start;
            }

            .owner-actions {
                width: 100%;
            }

            .owner-actions form {
                flex: 1;
            }

            .owner-btn {
                width: 100%;
            }

        }

    </style>

</head>

<body>

<nav class="navbar">

    <div class="logo">
        Smart Inventory
    </div>

    <div class="nav-links">

        <a href="owner-dashboard.php">Dashboard</a>

        <a href="inventory.php">Products</a>

        <a href="add-product.php">Add Product</a>

        <a href="sales-history.php">Sales History</a>

        <a href="#">Reports</a>

        <a href="profile.php">Profile</a>

        <a href="shop-management.php">Shop Management</a>

        <a href="logout.php">Logout</a>

    </div>

</nav>

<main class="dashboard">

    <div class="welcome-section">

        <h1>
            Welcome,
            <?php echo htmlspecialchars($user["full_name"]); ?>
            👋
        </h1>

        <p>
            Manage your shop, inventory and employees.
        </p>

        <span class="shop-label">
            🏪 <?php echo htmlspecialchars($shopName); ?>
        </span>

    </div>


    <!-- OWNER MANAGEMENT -->

    <section class="owner-section">

        <div class="owner-box">

            <h2>
                🔔 Employee Join Requests
                <?php if ($pendingRequestCount > 0): ?>
                    (<?php echo $pendingRequestCount; ?>)
                <?php endif; ?>
            </h2>

            <p class="owner-box-subtitle">
                Approve or reject employees requesting access
                to your shop.
            </p>

            <?php if ($pendingRequestCount === 0): ?>

                <p class="empty-owner-message">
                    No pending employee requests.
                </p>

            <?php else: ?>

                <?php while ($request = $pendingRequests->fetch_assoc()): ?>

                    <div class="request-row">

                        <div>

                            <div class="person-name">
                                <?php
                                echo htmlspecialchars(
                                    $request["full_name"]
                                );
                                ?>
                            </div>

                            <div class="person-email">
                                <?php
                                echo htmlspecialchars(
                                    $request["email"]
                                );
                                ?>
                            </div>

                        </div>

                        <div class="owner-actions">

                            <form
                                action="shop-action.php"
                                method="POST"
                            >

                                <input
                                    type="hidden"
                                    name="action"
                                    value="approve"
                                >

                                <input
                                    type="hidden"
                                    name="request_id"
                                    value="<?php
                                    echo (int)$request["request_id"];
                                    ?>"
                                >

                                <button
                                    type="submit"
                                    class="owner-btn allow-btn"
                                >
                                    ✓ Allow
                                </button>

                            </form>

                            <form
                                action="shop-action.php"
                                method="POST"
                            >

                                <input
                                    type="hidden"
                                    name="action"
                                    value="reject"
                                >

                                <input
                                    type="hidden"
                                    name="request_id"
                                    value="<?php
                                    echo (int)$request["request_id"];
                                    ?>"
                                >

                                <button
                                    type="submit"
                                    class="owner-btn reject-btn"
                                >
                                    ✕ Reject
                                </button>

                            </form>

                        </div>

                    </div>

                <?php endwhile; ?>

            <?php endif; ?>

        </div>


        <!-- EMPLOYEES -->

        <div class="owner-box">

            <h2>👥 Employees</h2>

            <p class="owner-box-subtitle">
                Employees currently approved to access your shop.
            </p>

            <?php if ($employees->num_rows === 0): ?>

                <p class="empty-owner-message">
                    No approved employees yet.
                </p>

            <?php else: ?>

                <?php while ($employee = $employees->fetch_assoc()): ?>

                    <div class="employee-row">

                        <div>

                            <div class="person-name">
                                <?php
                                echo htmlspecialchars(
                                    $employee["full_name"]
                                );
                                ?>
                            </div>

                            <div class="person-email">
                                <?php
                                echo htmlspecialchars(
                                    $employee["email"]
                                );
                                ?>
                            </div>

                        </div>

                        <div class="owner-actions">

                            <form
                                action="shop-action.php"
                                method="POST"
                                onsubmit="return confirm(
                                    'Remove this employee from the shop?'
                                );"
                            >

                                <input
                                    type="hidden"
                                    name="action"
                                    value="remove"
                                >

                                <input
                                    type="hidden"
                                    name="employee_id"
                                    value="<?php
                                    echo (int)$employee["id"];
                                    ?>"
                                >

                                <button
                                    type="submit"
                                    class="owner-btn remove-btn"
                                >
                                    🗑 Remove
                                </button>

                            </form>

                        </div>

                    </div>

                <?php endwhile; ?>

            <?php endif; ?>

        </div>

    </section>


    <!-- OWNER STATISTICS -->

    <section class="dashboard-cards">

        <div class="card">

            <h3>Total Products</h3>

            <p class="number">
                <?php echo $totalProducts; ?>
            </p>

            <span>
                Products in inventory
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
                Products need attention
            </span>

        </a>


        <div class="card">

            <h3>Dead Stock</h3>

            <p class="number">
                0
            </p>

            <span>
                Products not selling
            </span>

        </div>


        <div class="card">

            <h3>Exchange Requests</h3>

            <p class="number">
                0
            </p>

            <span>
                Pending requests
            </span>

        </div>

    </section>


    <!-- OWNER QUICK ACTIONS -->

    <section class="quick-actions">

        <h2>Management</h2>

        <div class="action-container">

            <a
                href="inventory.php"
                class="action-card"
            >
                <h3>📦 Shared Inventory</h3>
                <p>
                    View and manage your shop's inventory.
                </p>
            </a>


            <a
                href="sales-history.php"
                class="action-card"
            >
                <h3>📜 Sales History</h3>
                <p>
                    Review sales recorded for your shop.
                </p>
            </a>


            <a
                href="#"
                class="action-card"
            >
                <h3>📊 Reports</h3>
                <p>
                    View shop performance and reports.
                </p>
            </a>


            <a
                href="ai-dead-stock.php"
                class="action-card"
            >
                <h3>🤖 AI Insights</h3>
                <p>
                    Check dead-stock and demand predictions.
                </p>
            </a>


            <a
                href="#"
                class="action-card"
            >
                <h3>🔄 Exchange</h3>
                <p>
                    Manage slow-moving inventory exchanges.
                </p>
            </a>


            <a
                href="shop-management.php"
                class="action-card"
            >
                <h3>🏪 Shop Management</h3>
                <p>
                    Manage employees and shop access.
                </p>
            </a>
                  
            <a
    href="ocr-stock-in.php"
    class="action-card"
>
    <h3>📷 Scan Receipt</h3>
    <p>
        Scan a receipt and automatically add products.
    </p>
</a>

<a
    href="scan-product.php"
    class="action-card"
>
    <h3>🤖 Scan Product</h3>
    <p>
        Recognize products using AI.
    </p>
</a>
        </div>

    </section>

</main>

</body>

</html>