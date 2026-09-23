<?php
session_start();

require_once "db.php";

/* Only logged-in users can record sales */
if (!isset($_SESSION["user_id"])) {
    header("Location: login.html");
    exit;
}

$userId = (int) $_SESSION["user_id"];

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: inventory.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Get current user's shop and access
|--------------------------------------------------------------------------
*/

$userStmt = $conn->prepare(
    "SELECT role, shop_id, account_status
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
    die("User not found.");
}

$currentUser = $userResult->fetch_assoc();
$userStmt->close();

$role = $currentUser["role"];
$shopId = (int) $currentUser["shop_id"];

/*
|--------------------------------------------------------------------------
| Only shop owner or approved employee can sell
|--------------------------------------------------------------------------
*/

if (
    ($role !== "owner" && $role !== "employee") ||
    empty($shopId)
) {
    $conn->close();
    die("You do not have access to shop inventory.");
}

if (
    $role === "employee" &&
    $currentUser["account_status"] !== "approved"
) {
    $conn->close();
    die("Your employee account is not approved.");
}

/*
|--------------------------------------------------------------------------
| Get submitted data
|--------------------------------------------------------------------------
*/

$productId = $_POST["product_id"] ?? "";
$quantitySold = $_POST["quantity_sold"] ?? "";

if (!is_numeric($productId) || !is_numeric($quantitySold)) {
    die("Invalid sale information.");
}

$productId = (int) $productId;
$quantitySold = (int) $quantitySold;

if ($quantitySold <= 0) {
    die("Invalid sale quantity.");
}

/*
|--------------------------------------------------------------------------
| Get product from the shared shop inventory
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare(
    "SELECT quantity
     FROM products
     WHERE id = ? AND shop_id = ?
     LIMIT 1"
);

$stmt->bind_param("ii", $productId, $shopId);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $stmt->close();
    $conn->close();
    die("Product not found.");
}

$product = $result->fetch_assoc();
$stmt->close();

$currentStock = (int) $product["quantity"];

/*
|--------------------------------------------------------------------------
| Check available stock
|--------------------------------------------------------------------------
*/

if ($quantitySold > $currentStock) {
    $conn->close();
    die("You cannot sell more than the available stock.");
}

/*
|--------------------------------------------------------------------------
| Record sale + reduce inventory together
|--------------------------------------------------------------------------
*/

$conn->begin_transaction();

try {

    /*
     * Record who actually performed the sale.
     * This remains userId, NOT the product creator.
     */
    $saleStmt = $conn->prepare(
        "INSERT INTO product_sales
        (product_id, user_id, quantity_sold, sale_date)
        VALUES (?, ?, ?, CURDATE())"
    );

    $saleStmt->bind_param(
        "iii",
        $productId,
        $userId,
        $quantitySold
    );

    $saleStmt->execute();
    $saleStmt->close();

    /*
     * Reduce the shared shop inventory.
     */
    $updateStmt = $conn->prepare(
        "UPDATE products
         SET quantity = quantity - ?
         WHERE id = ?
           AND shop_id = ?
           AND quantity >= ?"
    );

    $updateStmt->bind_param(
        "iiii",
        $quantitySold,
        $productId,
        $shopId,
        $quantitySold
    );

    $updateStmt->execute();

    if ($updateStmt->affected_rows !== 1) {
        throw new Exception("Inventory update failed.");
    }

    $updateStmt->close();

    /* Save both operations */
    $conn->commit();

    header("Location: inventory.php?sale=success");
    exit;

} catch (Exception $e) {

    /* Undo sale if inventory update fails */
    $conn->rollback();

    $conn->close();

    die("Unable to record the sale. Please try again.");
}
?>