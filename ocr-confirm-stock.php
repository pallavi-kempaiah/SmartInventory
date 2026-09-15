<?php

session_start();

require_once "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.html");
    exit;
}

$userId = $_SESSION["user_id"];

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ocr-stock-in.php");
    exit;
}

$products = $_POST["products"] ?? [];

$transactionType = $_SESSION["ocr_transaction_type"] ?? "in";

if (!in_array($transactionType, ["in", "out"])) {
    $transactionType = "in";
}

if (empty($products)) {
    die("No products were submitted.");
}

$conn->begin_transaction();

try {

    foreach ($products as $product) {

        $productName = trim($product["product"] ?? "");

        $category = trim($product["category"] ?? "");

        $quantity = (int)($product["quantity"] ?? 0);

        $price = (float)($product["price"] ?? 0);

        $purchaseDate = $product["purchase_date"] ?? "";

        $expiryDate = $product["expiry_date"] ?? "";

        if ($productName === "") {
            continue;
        }

        if ($quantity <= 0) {
            continue;
        }


        /*
         * STOCK OUT
         */

        if ($transactionType === "out") {

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

            if ($result->num_rows !== 1) {

                throw new Exception(
                    "Product not found in inventory: " .
                    $productName
                );

            }

            $existingProduct = $result->fetch_assoc();

            $productId = $existingProduct["id"];

            $currentStock = (int)$existingProduct["quantity"];


            if ($quantity > $currentStock) {

                throw new Exception(
                    "Not enough stock for: " .
                    $productName
                );

            }


            /*
             * Deduct inventory
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

            $updateStmt->execute();


            /*
             * Record sale
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

            $saleStmt->execute();

        }


        /*
         * STOCK IN
         */

        else {

            if ($category === "") {
                $category = "Other";
            }

            if ($price < 0) {
                continue;
            }

            if ($expiryDate === "") {
                $expiryDate = null;
            }


            /*
             * Check existing product
             */

            $checkStmt = $conn->prepare(
                "SELECT id
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


            if ($result->num_rows === 1) {

                $existingProduct = $result->fetch_assoc();

                $productId = $existingProduct["id"];


                /*
                 * Increase stock
                 */

                $updateStmt = $conn->prepare(
                    "UPDATE products
                     SET quantity = quantity + ?,
                         price = ?,
                         category = ?,
                         purchase_date = ?,
                         expiry_date = ?
                     WHERE id = ?
                     AND user_id = ?"
                );

                $updateStmt->bind_param(
                    "idsssii",
                    $quantity,
                    $price,
                    $category,
                    $purchaseDate,
                    $expiryDate,
                    $productId,
                    $userId
                );

                $updateStmt->execute();

            }

            else {

                /*
                 * Create new product
                 */

                $insertStmt = $conn->prepare(
                    "INSERT INTO products
                    (
                        user_id,
                        product_name,
                        category,
                        quantity,
                        price,
                        purchase_date,
                        expiry_date
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?)"
                );

                $insertStmt->bind_param(
                    "issidss",
                    $userId,
                    $productName,
                    $category,
                    $quantity,
                    $price,
                    $purchaseDate,
                    $expiryDate
                );

                $insertStmt->execute();

            }

        }

    }


    $conn->commit();

    unset($_SESSION["ocr_data"]);
    unset($_SESSION["ocr_transaction_type"]);


    /*
     * Redirect based on transaction type
     */

    if ($transactionType === "out") {

        header("Location: sales-history.php?ocr=success");

    } else {

        header("Location: inventory.php?ocr=success");

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