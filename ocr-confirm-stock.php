<?php

session_start();

require_once "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.html");
    exit;
}

$userId = (int)$_SESSION["user_id"];

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ocr-stock-in.php");
    exit;
}


/*
 * ============================================================
 * GET CURRENT USER + SHOP
 * ============================================================
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
 * Only owner and approved employees can modify inventory.
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
 * ============================================================
 * GET OCR PRODUCTS
 * ============================================================
 */

$products = $_POST["products"] ?? [];

$transactionType =
    $_SESSION["ocr_transaction_type"] ?? "in";

if (!in_array($transactionType, ["in", "out"], true)) {
    $transactionType = "in";
}

if (empty($products)) {
    die("No products were submitted.");
}


/*
 * ============================================================
 * START DATABASE TRANSACTION
 * ============================================================
 */

$conn->begin_transaction();

try {

    foreach ($products as $product) {

        $productName =
            trim($product["product"] ?? "");

        $category =
            trim($product["category"] ?? "");

        $quantity =
            (int)($product["quantity"] ?? 0);

        $price =
            (float)($product["price"] ?? 0);

        $purchaseDate =
            trim($product["purchase_date"] ?? "");

        $expiryDate =
            trim($product["expiry_date"] ?? "");


        /*
         * Ignore invalid OCR rows.
         */

        if ($productName === "") {
            continue;
        }

        if ($quantity <= 0) {
            continue;
        }


        /*
         * ====================================================
         * STOCK OUT
         * ====================================================
         */

        if ($transactionType === "out") {

            /*
             * Find product in SHOP inventory.
             *
             * IMPORTANT:
             * Do NOT use user_id here.
             */

            $checkStmt = $conn->prepare(
                "SELECT id, quantity
                 FROM products
                 WHERE shop_id = ?
                   AND LOWER(TRIM(product_name))
                       = LOWER(TRIM(?))
                 LIMIT 1"
            );

            $checkStmt->bind_param(
                "is",
                $shopId,
                $productName
            );

            $checkStmt->execute();

            $result =
                $checkStmt->get_result();


            if ($result->num_rows !== 1) {

                throw new Exception(
                    "Product not found in shop inventory: " .
                    $productName
                );
            }


            $existingProduct =
                $result->fetch_assoc();

            $productId =
                (int)$existingProduct["id"];

            $currentStock =
                (int)$existingProduct["quantity"];


            /*
             * Prevent stock from becoming negative.
             */

            if ($quantity > $currentStock) {

                throw new Exception(
                    "Not enough stock for: " .
                    $productName .
                    ". Available stock: " .
                    $currentStock
                );
            }


            /*
             * Deduct shared shop inventory.
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
                throw new Exception(
                    "Unable to update inventory for " .
                    $productName
                );
            }


            /*
             * Record the person who performed
             * the stock-out.
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
                throw new Exception(
                    "Unable to record sale for " .
                    $productName
                );
            }
        }


        /*
         * ====================================================
         * STOCK IN
         * ====================================================
         */

        else {

            if ($category === "") {
                $category = "Other";
            }

            if ($price < 0) {
                continue;
            }

            if ($purchaseDate === "") {
                $purchaseDate = date("Y-m-d");
            }

            if ($expiryDate === "") {
                $expiryDate = null;
            }


            /*
             * Find existing product in SHOP inventory.
             */

            $checkStmt = $conn->prepare(
                "SELECT id
                 FROM products
                 WHERE shop_id = ?
                   AND LOWER(TRIM(product_name))
                       = LOWER(TRIM(?))
                 LIMIT 1"
            );

            $checkStmt->bind_param(
                "is",
                $shopId,
                $productName
            );

            $checkStmt->execute();

            $result =
                $checkStmt->get_result();


            /*
             * Product already exists.
             */

            if ($result->num_rows === 1) {

                $existingProduct =
                    $result->fetch_assoc();

                $productId =
                    (int)$existingProduct["id"];


                /*
                 * Increase shared shop stock.
                 */

                $updateStmt = $conn->prepare(
                    "UPDATE products
                     SET quantity = quantity + ?,
                         user_id = ?,
                         price = ?,
                         category = ?,
                         purchase_date = ?,
                         expiry_date = ?
                     WHERE id = ?
                       AND shop_id = ?"
                );

                $updateStmt->bind_param(
                    "iidsssii",
                    $quantity,
                    $userId,
                    $price,
                    $category,
                    $purchaseDate,
                    $expiryDate,
                    $productId,
                    $shopId
                );

                if (!$updateStmt->execute()) {
                    throw new Exception(
                        "Unable to update inventory for " .
                        $productName
                    );
                }
            }


            /*
             * Product does not exist.
             */

            else {

                /*
                 * IMPORTANT:
                 * shop_id is now stored.
                 */

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
                    throw new Exception(
                        "Unable to add product " .
                        $productName .
                        " to inventory."
                    );
                }
            }
        }
    }


    /*
     * ========================================================
     * EVERYTHING SUCCESSFUL
     * ========================================================
     */

    $conn->commit();


    /*
     * Clear OCR session data.
     */

    unset($_SESSION["ocr_data"]);
    unset($_SESSION["ocr_transaction_type"]);


    /*
     * Redirect.
     */

    if ($transactionType === "out") {

        header(
            "Location: sales-history.php?ocr=success"
        );

    } else {

        header(
            "Location: inventory.php?ocr=success"
        );
    }

    exit;


} catch (Exception $e) {

    $conn->rollback();

    die(
        "Unable to process receipt: " .
        htmlspecialchars($e->getMessage())
    );
}

?>