<?php
session_start();

require_once "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.html");
    exit;
}

$userId = (int)$_SESSION["user_id"];

/* Get current user's shop */
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
    die("User not found.");
}

$currentUser = $userResult->fetch_assoc();
$userStmt->close();

$role = $currentUser["role"];
$shopId = (int)$currentUser["shop_id"];

if ($role !== "owner" && $role !== "employee") {
    die("Access denied.");
}

if (empty($shopId)) {
    die("No shop assigned to this account.");
}

if (
    $role === "employee" &&
    $currentUser["account_status"] !== "approved"
) {
    die("Employee account is not approved.");
}

/* Get product ID */
$productId = $_GET["id"] ?? "";

if (!is_numeric($productId)) {
    die("Invalid product.");
}

$productId = (int)$productId;

/*
 * IMPORTANT:
 * Product belongs to the SHOP, not necessarily
 * the person currently selling it.
 */
$stmt = $conn->prepare(
    "SELECT id, product_name, quantity, price
     FROM products
     WHERE id = ?
       AND shop_id = ?
     LIMIT 1"
);

$stmt->bind_param("ii", $productId, $shopId);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    die(
        "Product not found for this shop. " .
        "Product ID: " . $productId .
        " | Shop ID: " . $shopId
    );
}

$product = $result->fetch_assoc();

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Sell Product - Smart Inventory</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f5f3fa;
}

.navbar {
    background: #6c2bd9;
    color: white;
    padding: 18px 40px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.logo {
    font-size: 22px;
    font-weight: bold;
}

.navbar a {
    color: white;
    text-decoration: none;
    margin-left: 20px;
}

.sell-container {
    max-width: 500px;
    margin: 60px auto;
    padding: 20px;
}

.sell-card {
    background: white;
    padding: 30px;
    border-radius: 14px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
    text-align: center;
}

.sell-card h1 {
    margin-top: 0;
    color: #333;
}

.product-name {
    font-size: 24px;
    font-weight: bold;
    color: #6c2bd9;
    margin: 15px 0;
}

.stock-info {
    color: #666;
    margin-bottom: 25px;
}

.quantity-control {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 15px;
    margin: 25px 0;
}

.quantity-control button {
    width: 45px;
    height: 45px;
    border: none;
    border-radius: 8px;
    background: #eee7ff;
    color: #6c2bd9;
    font-size: 24px;
    font-weight: bold;
    cursor: pointer;
}

.quantity-control button:hover {
    background: #6c2bd9;
    color: white;
}

#quantity {
    width: 70px;
    text-align: center;
    font-size: 22px;
    font-weight: bold;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 10px;
}

.confirm-btn {
    width: 100%;
    padding: 13px;
    border: none;
    border-radius: 8px;
    background: #6c2bd9;
    color: white;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
}

.confirm-btn:hover {
    background: #5720b7;
}

.cancel-btn {
    display: block;
    margin-top: 15px;
    color: #6c2bd9;
    text-decoration: none;
    font-weight: bold;
}

.cancel-btn:hover {
    text-decoration: underline;
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
    <a href="logout.php">Logout</a>
</nav>

</header>

<main class="sell-container">

<div class="sell-card">

<h1>Sell Product</h1>

<div class="product-name">
    <?php echo htmlspecialchars($product["product_name"]); ?>
</div>

<div class="stock-info">
    Available Stock:
    <strong><?php echo $product["quantity"]; ?></strong>
</div>

<?php if ($product["quantity"] > 0): ?>

<form action="record-sale.php" method="post">

<input
    type="hidden"
    name="product_id"
    value="<?php echo $product["id"]; ?>"
>

<div class="quantity-control">

<button
    type="button"
    onclick="changeQuantity(-1)"
>
    −
</button>

<input
    type="number"
    id="quantity"
    name="quantity_sold"
    value="1"
    min="1"
    max="<?php echo $product["quantity"]; ?>"
    readonly
>

<button
    type="button"
    onclick="changeQuantity(1)"
>
    +
</button>

</div>

<button
    type="submit"
    class="confirm-btn"
>
    Confirm Sale
</button>

</form>

<?php else: ?>

<p>
    This product is currently out of stock.
</p>

<?php endif; ?>

<a href="inventory.php" class="cancel-btn">
    ← Back to Inventory
</a>

</div>

</main>

<script>

const quantityInput = document.getElementById("quantity");

function changeQuantity(change) {

    if (!quantityInput) {
        return;
    }

    let quantity = parseInt(quantityInput.value);

    const min = parseInt(quantityInput.min);
    const max = parseInt(quantityInput.max);

    quantity += change;

    if (quantity < min) {
        quantity = min;
    }

    if (quantity > max) {
        quantity = max;
    }

    quantityInput.value = quantity;
}

</script>

</body>
</html>