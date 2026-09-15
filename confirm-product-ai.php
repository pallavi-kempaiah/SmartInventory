<?php

session_start();

require_once "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.html");
    exit;
}

$userId = $_SESSION["user_id"];

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: scan-product.php");
    exit;
}

$productName = trim($_POST["product_name"] ?? "");

$quantity = (int)($_POST["quantity"] ?? 0);

$transactionType = $_POST["transaction_type"] ?? "in";

if (!in_array($transactionType, ["in", "out"])) {
    $transactionType = "in";
}

if ($productName === "") {
    die("Product name is required.");
}

if ($quantity <= 0) {
    die("Quantity must be greater than zero.");
}


/*
 * Find the product in the current user's inventory.
 */

$checkStmt = $conn->prepare(
    "SELECT id, quantity
     FROM products
     WHERE user_id = ?
     AND product_name = ?
     LIMIT 1"
);

$checkStmt->bind_param(
    "is",
    $userId,
    $productName
);

$checkStmt->execute();

$result = $checkStmt->get_result();


/*
 * STOCK IN
 */

if ($transactionType === "in") {

    if ($result->num_rows === 1) {

        $product = $result->fetch_assoc();

        $productId = $product["id"];

        $updateStmt = $conn->prepare(
            "UPDATE products
             SET quantity = quantity + ?
             WHERE id = ?
             AND user_id = ?"
        );

        $updateStmt->bind_param(
            "iii",
            $quantity,
            $productId,
            $userId
        );

        if (!$updateStmt->execute()) {
            die("Unable to update inventory.");
        }

    } else {

        $category = "Other";
        $price = 0.00;
        $purchaseDate = date("Y-m-d");

        $insertStmt = $conn->prepare(
            "INSERT INTO products
            (
                user_id,
                product_name,
                category,
                quantity,
                price,
                purchase_date
            )
            VALUES (?, ?, ?, ?, ?, ?)"
        );

        $insertStmt->bind_param(
            "issids",
            $userId,
            $productName,
            $category,
            $quantity,
            $price,
            $purchaseDate
        );

        if (!$insertStmt->execute()) {
            die("Unable to add product to inventory.");
        }
    }
}


/*
 * STOCK OUT
 */

else {

    if ($result->num_rows !== 1) {

        die(
            "This product was not found in your inventory. " .
            "Please check the product name."
        );
    }

    $product = $result->fetch_assoc();

    $productId = $product["id"];

    $currentQuantity = (int)$product["quantity"];


    /*
     * Prevent selling more than available stock.
     */

    if ($quantity > $currentQuantity) {

        die(
            "Stock Out failed. You only have " .
            $currentQuantity .
            " unit(s) of this product."
        );
    }


    /*
     * Start transaction.
     */

    $conn->begin_transaction();

    try {

        /*
         * Decrease inventory.
         */

        $updateStmt = $conn->prepare(
            "UPDATE products
             SET quantity = quantity - ?
             WHERE id = ?
             AND user_id = ?"
        );

        $updateStmt->bind_param(
            "iii",
            $quantity,
            $productId,
            $userId
        );

        if (!$updateStmt->execute()) {
            throw new Exception("Unable to update inventory.");
        }


        /*
         * Record the sale.
         */

        $saleDate = date("Y-m-d");

        $saleStmt = $conn->prepare(
            "INSERT INTO product_sales
            (
                product_id,
                user_id,
                quantity_sold,
                sale_date
            )
            VALUES (?, ?, ?, ?)"
        );

        $saleStmt->bind_param(
            "iiis",
            $productId,
            $userId,
            $quantity,
            $saleDate
        );

        if (!$saleStmt->execute()) {
            throw new Exception("Unable to record sale.");
        }


        /*
         * Everything succeeded.
         */

        $conn->commit();

    } catch (Exception $e) {

        $conn->rollback();

        die(
            "Stock Out failed: " .
            htmlspecialchars($e->getMessage())
        );
    }
}


/*
 * Clear AI session data.
 */

unset($_SESSION["product_ai_predictions"]);
unset($_SESSION["product_ai_image"]);
unset($_SESSION["product_ai_transaction_type"]);


/*
 * Return to inventory.
 */

if ($transactionType === "out") {

    header("Location: inventory.php?ai=stockout-success");

} else {

    header("Location: inventory.php?ai=success");
}

exit;

?>