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
| Verify that the logged-in user is an OWNER
|--------------------------------------------------------------------------
*/

$userStmt = $conn->prepare(
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

$userStmt->bind_param("i", $userId);
$userStmt->execute();

$userResult = $userStmt->get_result();

if ($userResult->num_rows !== 1) {

    $userStmt->close();
    $conn->close();

    session_unset();
    session_destroy();

    header("Location: login.html");
    exit;
}

$user = $userResult->fetch_assoc();

$userStmt->close();

/*
|--------------------------------------------------------------------------
| OWNER ONLY
|--------------------------------------------------------------------------
*/

if ($user["role"] !== "owner") {

    $conn->close();

    header("Location: dashboard.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Get owner's shop
|
| First use the shop_id connected to the owner account.
|--------------------------------------------------------------------------
*/

$shopId = (int) ($user["shop_id"] ?? 0);

if ($shopId <= 0) {

    /*
    | Fallback for older owner accounts where shop_id
    | may not have been connected correctly.
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

} else {

    /*
    | Verify that the shop actually exists.
    */

    $shopStmt = $conn->prepare(
        "SELECT id, shop_name
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
}


/*
|--------------------------------------------------------------------------
| Pending Join Requests
|--------------------------------------------------------------------------
*/

$requestStmt = $conn->prepare(
    "SELECT
        r.id AS request_id,
        u.id AS employee_id,
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

$requests = $requestStmt->get_result();

$requestStmt->close();


/*
|--------------------------------------------------------------------------
| Approved Employees
|--------------------------------------------------------------------------
*/

$employeeStmt = $conn->prepare(
    "SELECT
        id,
        full_name,
        email,
        created_at
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

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Shop Management | Smart Inventory</title>

    <link
        rel="stylesheet"
        href="dashboard.css"
    >

    <style>

        .management-container {
            max-width: 1100px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .shop-header {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
        }

        .shop-header h1 {
            margin-bottom: 8px;
        }

        .section-box {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
        }

        .section-box h2 {
            margin-bottom: 20px;
        }

        .person-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            padding: 18px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .person-row:last-child {
            border-bottom: none;
        }

        .person-info h3 {
            margin: 0 0 5px;
        }

        .person-info p {
            margin: 0;
            color: #64748b;
        }

        .person-actions {
            display: flex;
            gap: 10px;
            flex-shrink: 0;
        }

        .btn {
            border: none;
            padding: 9px 16px;
            border-radius: 7px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
        }

        .btn-allow {
            background: #16a34a;
            color: white;
        }

        .btn-reject,
        .btn-remove {
            background: #dc2626;
            color: white;
        }

        .empty-message {
            color: #64748b;
            padding: 10px 0;
        }

        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            background: #fef3c7;
            color: #92400e;
            margin-top: 6px;
        }

        @media (max-width: 700px) {

            .person-row {
                flex-direction: column;
                align-items: flex-start;
            }

            .person-actions {
                width: 100%;
            }

            .person-actions form {
                flex: 1;
            }

            .person-actions .btn {
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

        <a href="owner-dashboard.php">
            Dashboard
        </a>

        <a href="inventory.php">
            Products
        </a>

        <a href="add-product.php">
            Add Product
        </a>

        <a href="sales-history.php">
            Sales History
        </a>

        <a href="profile.php">
            Profile
        </a>

        <a href="shop-management.php">
            Shop Management
        </a>

        <a href="logout.php">
            Logout
        </a>

    </div>

</nav>


<main class="management-container">

    <div class="shop-header">

        <h1>
            Shop Management 🏪
        </h1>

        <p>
            Manage employees and control access to
            <strong>
                <?php echo htmlspecialchars($shopName); ?>
            </strong>
        </p>

    </div>


    <!-- PENDING REQUESTS -->

    <div class="section-box">

        <h2>
            🔔 Join Requests
        </h2>

        <?php if ($requests->num_rows === 0): ?>

            <p class="empty-message">
                No pending join requests.
            </p>

        <?php else: ?>

            <?php while ($request = $requests->fetch_assoc()): ?>

                <div class="person-row">

                    <div class="person-info">

                        <h3>
                            <?php
                            echo htmlspecialchars(
                                $request["full_name"]
                            );
                            ?>
                        </h3>

                        <p>
                            <?php
                            echo htmlspecialchars(
                                $request["email"]
                            );
                            ?>
                        </p>

                        <span class="badge">
                            Waiting for approval
                        </span>

                    </div>


                    <div class="person-actions">

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
                                echo (int) $request["request_id"];
                                ?>"
                            >

                            <button
                                type="submit"
                                class="btn btn-allow"
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
                                echo (int) $request["request_id"];
                                ?>"
                            >

                            <button
                                type="submit"
                                class="btn btn-reject"
                            >
                                ✕ Reject
                            </button>

                        </form>

                    </div>

                </div>

            <?php endwhile; ?>

        <?php endif; ?>

    </div>


    <!-- APPROVED EMPLOYEES -->

    <div class="section-box">

        <h2>
            👥 Employees
        </h2>

        <?php if ($employees->num_rows === 0): ?>

            <p class="empty-message">
                No approved employees yet.
            </p>

        <?php else: ?>

            <?php while ($employee = $employees->fetch_assoc()): ?>

                <div class="person-row">

                    <div class="person-info">

                        <h3>
                            <?php
                            echo htmlspecialchars(
                                $employee["full_name"]
                            );
                            ?>
                        </h3>

                        <p>
                            <?php
                            echo htmlspecialchars(
                                $employee["email"]
                            );
                            ?>
                        </p>

                    </div>


                    <div class="person-actions">

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
                                echo (int) $employee["id"];
                                ?>"
                            >

                            <button
                                type="submit"
                                class="btn btn-remove"
                            >
                                🗑 Remove
                            </button>

                        </form>

                    </div>

                </div>

            <?php endwhile; ?>

        <?php endif; ?>

    </div>

</main>

</body>

</html>