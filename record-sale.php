<?php
session_start();

require_once "db.php";

/* Only logged-in users can record sales */
if (!isset($_SESSION["user_id"])) {
    header("Location: login.html");
    exit;
}

$userId = $_SESSION["user_id"];

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: inventory.php");
    exit;
}

/* Get submitted data */
$productId = $_POST["product_id"] ?? "";
$quantitySold = $_POST["quantity_sold"] ?? "";

if (!is_numeric($productId) || !is_numeric($quantitySold)) {
    die("Invalid sale information.");
}

$productId = (int)$productId;
$quantitySold = (int)$quantitySold;

if ($quantitySold <= 0) {
    die("Invalid sale quantity.");
}

/*
 * Get the product and make sure it belongs
 * to the currently logged-in user.
 */
$stmt = $conn->prepare(
    "SELECT quantity
     FROM products
     WHERE id = ? AND user_id = ?"
);

$stmt->bind_param("ii", $productId, $userId);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    die("Product not found.");
}

$product = $result->fetch_assoc();

$currentStock = (int)$product["quantity"];

/* Make sure the user isn't selling more than available */
if ($quantitySold > $currentStock) {
    die("You cannot sell more than the available stock.");
}

/*
 * Use a transaction so both operations
 * succeed together.
 */
$conn->begin_transaction();

try {

    /* Record the sale */
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

    /* Reduce inventory */
    $newStock = $currentStock - $quantitySold;

    $updateStmt = $conn->prepare(
        "UPDATE products
         SET quantity = ?
         WHERE id = ? AND user_id = ?"
    );

    $updateStmt->bind_param(
        "iii",
        $newStock,
        $productId,
        $userId
    );

    $updateStmt->execute();

    /* Save both changes */
    $conn->commit();

    header("Location: inventory.php?sale=success");
    exit;

} catch (Exception $e) {

    /* Undo everything if something failed */
    $conn->rollback();

    die("Unable to record the sale. Please try again.");
}
?>