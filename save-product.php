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

$userId = (int) $_SESSION["user_id"];

/*
|--------------------------------------------------------------------------
| Get the user's current shop
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
    die("User account not found.");
}

$user = $userResult->fetch_assoc();
$userStmt->close();

$role = $user["role"];
$shopId = $user["shop_id"];
$accountStatus = $user["account_status"];

/*
|--------------------------------------------------------------------------
| Only owners and approved employees can add shared inventory
|--------------------------------------------------------------------------
*/
if ($role !== "owner" && $role !== "employee") {
    $conn->close();
    die("Only shop owners and approved employees can add products.");
}

if ($role === "employee" && $accountStatus !== "approved") {
    $conn->close();
    die("Your employee account is not approved yet.");
}

if (empty($shopId)) {
    $conn->close();
    die("You are not connected to a shop.");
}

$shopId = (int) $shopId;

/*
|--------------------------------------------------------------------------
| Get product information
|--------------------------------------------------------------------------
*/
$productName = trim($_POST["product_name"] ?? "");
$category = trim($_POST["category"] ?? "");
$quantity = $_POST["quantity"] ?? "";
$price = $_POST["price"] ?? "";
$purchaseDate = !empty($_POST["purchase_date"])
    ? $_POST["purchase_date"]
    : null;

$expiryDate = !empty($_POST["expiry_date"])
    ? $_POST["expiry_date"]
    : null;

/*
|--------------------------------------------------------------------------
| Validation
|--------------------------------------------------------------------------
*/
if ($productName === "" || $quantity === "" || $price === "") {
    die("Product name, quantity and price are required.");
}

if (!is_numeric($quantity) || !is_numeric($price)) {
    die("Quantity and price must be valid numbers.");
}

$quantity = (int) $quantity;
$price = (float) $price;

if ($quantity < 0 || $price < 0) {
    die("Quantity and price cannot be negative.");
}

/*
|--------------------------------------------------------------------------
| Insert product into shared shop inventory
|--------------------------------------------------------------------------
|
| user_id = person who added the product
| shop_id = shop whose inventory the product belongs to
|
*/
/*
|--------------------------------------------------------------------------
| Add to existing product or create a new product
|--------------------------------------------------------------------------
| Products with the same name inside the same shop share one quantity.
|--------------------------------------------------------------------------
*/

$checkStmt = $conn->prepare(
    "SELECT id
     FROM products
     WHERE shop_id = ?
       AND LOWER(TRIM(product_name)) = LOWER(TRIM(?))
     LIMIT 1"
);

$checkStmt->bind_param("is", $shopId, $productName);
$checkStmt->execute();

$existingResult = $checkStmt->get_result();

if ($existingResult->num_rows === 1) {

    // Product already exists → increase its quantity
    $existingProduct = $existingResult->fetch_assoc();
    $existingProductId = (int) $existingProduct["id"];

    $updateStmt = $conn->prepare(
        "UPDATE products
         SET quantity = quantity + ?,
             user_id = ?,
             category = ?,
             price = ?,
             purchase_date = ?,
             expiry_date = ?
         WHERE id = ? AND shop_id = ?"
    );

    $updateStmt->bind_param(
    "iisdssii",
    $quantity,
    $userId,
    $category,
    $price,
    $purchaseDate,
    $expiryDate,
    $existingProductId,
    $shopId
);

    $updateStmt->execute();
    $updateStmt->close();

} else {

    // Product does not exist → create it
    $insertStmt = $conn->prepare(
        "INSERT INTO products
        (
            user_id,
            shop_id,
            product_name,
            category,
            quantity,
            price,
            purchase_date,
            expiry_date
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );

    $insertStmt->bind_param(
        "iissidss",
        $userId,
        $shopId,
        $productName,
        $category,
        $quantity,
        $price,
        $purchaseDate,
        $expiryDate
    );

    $insertStmt->execute();
    $insertStmt->close();
}

$checkStmt->close();



$dashboardPage =
    ($role === "owner")
        ? "owner-dashboard.php"
        : "employee-dashboard.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Product Added | Smart Inventory</title>

<style>
body {
    font-family: Arial, sans-serif;
    background: #f8fafc;
    margin: 0;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
}

.card {
    background: white;
    padding: 40px;
    border-radius: 16px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
    text-align: center;
    max-width: 450px;
    width: 90%;
}

h1 {
    color: #16a34a;
    margin-bottom: 10px;
}

p {
    color: #475569;
    margin-bottom: 25px;
}

a {
    display: inline-block;
    text-decoration: none;
    background: #2563eb;
    color: white;
    padding: 12px 20px;
    border-radius: 8px;
    margin: 5px;
}

a:hover {
    background: #1d4ed8;
}
</style>
</head>

<body>

<div class="card">

    <h1>✓ Product Added</h1>

    <p>
        The product has been added to your shop's shared inventory.
    </p>

    <a href="inventory.php">View Inventory</a>

    <a href="<?php echo $dashboardPage; ?>">Dashboard</a>

</div>

</body>
</html>