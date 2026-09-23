<?php

session_start();

require_once "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.html");
    exit;
}

$userId = (int)$_SESSION["user_id"];

/*
 * Get current user's role, shop and approval status
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
    die("User account not found.");
}

$user = $userResult->fetch_assoc();

$role = $user["role"];
$shopId = $user["shop_id"];
$accountStatus = $user["account_status"];

$userStmt->close();


/*
 * Only owners and approved employees can modify shop inventory.
 */
if (
    $role !== "owner" &&
    !(
        $role === "employee" &&
        $accountStatus === "approved"
    )
) {
    die("You do not have permission to modify inventory.");
}

if (empty($shopId)) {
    die("You are not connected to a shop.");
}

$shopId = (int)$shopId;


/*
 * Get submitted data
 */
$productName = trim($_POST["product_name"] ?? "");

$quantity = (int)($_POST["quantity"] ?? 0);

$transactionType = $_POST["transaction_type"] ?? "in";

$category = trim($_POST["category"] ?? "Other");

$price = (float)($_POST["price"] ?? 0);

$purchaseDate = trim($_POST["purchase_date"] ?? "");

$expiryDate = trim($_POST["expiry_date"] ?? "");


/*
 * Validate transaction type
 */
if (!in_array($transactionType, ["in", "out"], true)) {
    $transactionType = "in";
}


/*
 * Validate product
 */
if ($productName === "") {
    die("Product name is required.");
}

if ($quantity <= 0) {
    die("Quantity must be greater than zero.");
}

if ($price < 0) {
    die("Price cannot be negative.");
}

if ($category === "") {
    $category = "Other";
}


/*
 * Date handling
 */
if ($purchaseDate === "") {
    $purchaseDate = date("Y-m-d");
}

if ($expiryDate === "") {
    $expiryDate = null;
}


/*
 * Find product in the SHOP inventory.
 *
 * IMPORTANT:
 * We use shop_id, NOT user_id.
 *
 * This means owner + approved employees
 * all work with the same product record.
 */
$checkStmt = $conn->prepare(
    "SELECT id, quantity, price, purchase_date, expiry_date
     FROM products
     WHERE shop_id = ?
       AND LOWER(TRIM(product_name)) = LOWER(TRIM(?))
     LIMIT 1"
);

$checkStmt->bind_param(
    "is",
    $shopId,
    $productName
);

$checkStmt->execute();

$result = $checkStmt->get_result();


/*
 * ============================================================
 * STOCK IN
 * ============================================================
 */
if ($transactionType === "in") {

    /*
     * Product already exists in this shop.
     * Increase its quantity.
     */
    if ($result->num_rows === 1) {

        $product = $result->fetch_assoc();

        $productId = (int)$product["id"];

        $updateStmt = $conn->prepare(
            "UPDATE products
             SET quantity = quantity + ?,
                 user_id = ?,
                 category = ?,
                 price = ?,
                 purchase_date = ?,
                 expiry_date = ?
             WHERE id = ?
               AND shop_id = ?"
        );

        $updateStmt->bind_param(
            "iisdssii",
            $quantity,
            $userId,
            $category,
            $price,
            $purchaseDate,
            $expiryDate,
            $productId,
            $shopId
        );

        if (!$updateStmt->execute()) {
            die(
                "Unable to update inventory: " .
                htmlspecialchars($updateStmt->error)
            );
        }

        $updateStmt->close();

    }

    /*
     * Product does not exist in this shop.
     * Create a new shared product.
     */
    else {

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

        if (!$insertStmt->execute()) {
            die(
                "Unable to add product to inventory: " .
                htmlspecialchars($insertStmt->error)
            );
        }

        $insertStmt->close();
    }
}


/*
 * ============================================================
 * STOCK OUT
 * ============================================================
 */
else {

    /*
     * Product must exist in this SHOP.
     */
    if ($result->num_rows !== 1) {

        die(
            "This product was not found in your shop inventory. " .
            "Please check the product name."
        );
    }

    $product = $result->fetch_assoc();

    $productId = (int)$product["id"];

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
         * Decrease shared shop inventory.
         */
        $updateStmt = $conn->prepare(
            "UPDATE products
             SET quantity = quantity - ?
             WHERE id = ?
               AND shop_id = ?"
        );

        $updateStmt->bind_param(
            "iii",
            $quantity,
            $productId,
            $shopId
        );

        if (!$updateStmt->execute()) {
            throw new Exception("Unable to update inventory.");
        }

        $updateStmt->close();


        /*
         * Record who performed the stock-out.
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

        $saleStmt->close();


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
 * Return to shared inventory.
 */
if ($transactionType === "out") {

    header(
        "Location: inventory.php?ai=stockout-success"
    );

} else {

    header(
        "Location: inventory.php?ai=success"
    );
}

exit;

?>