<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.html");
    exit;
}

if (!isset($_SESSION["product_ai_predictions"])) {
    header("Location: scan-product.php");
    exit;
}

$aiResult = $_SESSION["product_ai_predictions"];

$transactionType =
    $_SESSION["product_ai_transaction_type"] ?? "in";

$isStockOut =
    ($transactionType === "out");


// ============================================================
// GET AI DATA
// ============================================================

$predictions =
    $aiResult["predictions"] ?? [];

$category =
    $aiResult["category"] ?? "Other";

$productName =
    $aiResult["product_name"] ?? "Unknown Product";


// ============================================================
// BEST PREDICTION
// ============================================================

$bestPrediction =
    $productName;

$bestConfidence = 0;

if (!empty($predictions)) {

    $bestConfidence =
        $predictions[0]["confidence"] ?? 0;
}


// ============================================================
// ALLOWED CATEGORIES
// ============================================================

$allowedCategories = [

    "Food",
    "Beverages",
    "Dairy",
    "Fruits & Vegetables",
    "Electronics",
    "Clothing",
    "Personal Care",
    "Household",
    "Stationery",
    "Bakery",
    "Grocery",
    "Other"

];

if (!in_array(
    $category,
    $allowedCategories,
    true
)) {

    $category = "Other";
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?php
        echo $isStockOut
            ? "Stock Out - AI Recognition"
            : "Stock In - AI Recognition";
        ?>
    </title>


    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }

        body {

            background: #f4f6f8;

            min-height: 100vh;

            padding: 30px 15px;

        }

        .container {

            max-width: 650px;

            margin: auto;

            background: white;

            padding: 30px;

            border-radius: 16px;

            box-shadow:
                0 8px 25px
                rgba(0,0,0,0.08);

        }

        h1 {

            text-align: center;

            margin-bottom: 10px;

        }

        .subtitle {

            text-align: center;

            color: #666;

            margin-bottom: 25px;

        }

        .prediction {

            background: #f1f5f9;

            padding: 20px;

            border-radius: 12px;

            margin-bottom: 20px;

        }

        .prediction h2 {

            margin-bottom: 8px;

        }

        .confidence {

            color: #555;

        }

        .category-box {

            background: #eefbf3;

            border: 1px solid #b7e4c7;

            padding: 15px;

            border-radius: 10px;

            margin-bottom: 25px;

        }

        .category-box strong {

            color: #15803d;

        }

        label {

            display: block;

            margin-bottom: 8px;

            font-weight: bold;

        }

        input {

            width: 100%;

            padding: 12px;

            border: 1px solid #ccc;

            border-radius: 8px;

            margin-bottom: 18px;

            font-size: 16px;

        }

        button {

            width: 100%;

            padding: 14px;

            border: none;

            border-radius: 8px;

            font-size: 16px;

            font-weight: bold;

            cursor: pointer;

        }

        .confirm-in {

            background: #16a34a;

            color: white;

        }

        .confirm-out {

            background: #dc2626;

            color: white;

        }

        .warning {

            background: #fff7ed;

            color: #9a3412;

            padding: 15px;

            border-radius: 8px;

            margin-bottom: 20px;

            line-height: 1.5;

        }

        .alternatives {

            margin-top: 25px;

        }

        .alternatives h3 {

            margin-bottom: 12px;

        }

        .alternative {

            padding: 10px;

            border-bottom: 1px solid #eee;

        }

        .back {

            display: block;

            text-align: center;

            margin-top: 20px;

            color: #555;

            text-decoration: none;

        }

    </style>

</head>


<body>


<div class="container">


    <h1>

        <?php

        echo $isStockOut
            ? "📤 Stock Out"
            : "📥 Stock In";

        ?>

    </h1>


    <p class="subtitle">

        AI product recognition result

    </p>


    <!-- PRODUCT -->

    <div class="prediction">

        <h2>

            <?php

            echo htmlspecialchars(
                $bestPrediction
            );

            ?>

        </h2>


        <p class="confidence">

            Visual AI confidence:

            <strong>

                <?php

                echo htmlspecialchars(
                    $bestConfidence
                );

                ?>%

            </strong>

        </p>

    </div>


    <!-- CATEGORY -->

    <div class="category-box">

        🤖 <strong>AI Predicted Category:</strong>

        <?php

        echo htmlspecialchars(
            $category
        );

        ?>

    </div>


    <div class="warning">

        ⚠️ Please verify the product name
        before confirming.

        AI recognition can sometimes
        be incorrect.

    </div>


<form action="confirm-product-ai.php" method="POST">

    <label for="product_name">
        Product Name
    </label>

    <input
        type="text"
        id="product_name"
        name="product_name"
        value="<?php echo htmlspecialchars($bestPrediction); ?>"
        required
    >

    <label for="quantity">
        Quantity
    </label>

    <input
        type="number"
        id="quantity"
        name="quantity"
        value="1"
        min="1"
        required
    >

    <label for="price">
        Price
    </label>

    <input
        type="number"
        id="price"
        name="price"
        step="0.01"
        min="0"
        placeholder="Enter product price"
        required
    >

    <label for="purchase_date">
        Purchase Date
    </label>

    <input
        type="date"
        id="purchase_date"
        name="purchase_date"
        required
    >

    <label for="expiry_date">
        Expiry Date
    </label>

    <input
        type="date"
        id="expiry_date"
        name="expiry_date"
    >

    <input
        type="hidden"
        name="transaction_type"
        value="<?php echo htmlspecialchars($transactionType); ?>"
    >

    <input
        type="hidden"
        name="category"
        value="<?php echo htmlspecialchars($predictions["category"] ?? "Other"); ?>"
    >

    <button
        type="submit"
        class="<?php echo $isStockOut ? 'confirm-out' : 'confirm-in'; ?>"
    >
        <?php
        echo $isStockOut
            ? "📤 Confirm Stock Out"
            : "📥 Add Product to Inventory";
        ?>
    </button>

</form>


    <?php if (count($predictions) > 1): ?>


        <div class="alternatives">

            <h3>

                Other Visual AI Predictions

            </h3>


            <?php

            foreach (
                array_slice(
                    $predictions,
                    1
                )
                as $prediction
            ):

            ?>


                <div class="alternative">

                    <?php

                    echo htmlspecialchars(
                        $prediction["product"]
                    );

                    ?>

                    —

                    <?php

                    echo htmlspecialchars(
                        $prediction["confidence"]
                    );

                    ?>%

                </div>


            <?php endforeach; ?>


        </div>


    <?php endif; ?>


    <a
        href="scan-product.php"
        class="back"
    >

        ← Scan another product

    </a>


</div>


</body>

</html>