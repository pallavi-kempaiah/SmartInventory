<?php

session_start();

require_once "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.html");
    exit;
}

$userId = $_SESSION["user_id"];

$productId = $_GET["id"] ?? "";

if (!is_numeric($productId)) {
    die("Invalid product.");
}


/* Get product belonging to logged-in user */

$stmt = $conn->prepare(
    "SELECT id, product_name, category, quantity, price,
            purchase_date, expiry_date
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

$stmt->close();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Edit Product | Smart Inventory</title>

    <link rel="stylesheet"
          href="add-product.css">

</head>

<body>

    <nav class="navbar">

        <div class="logo">
            Smart Inventory
        </div>

        <div class="nav-links">

            <a href="dashboard.php">Dashboard</a>

            <a href="add-product.php">Add Product</a>

            <a href="inventory.php">Inventory</a>

            <a href="logout.php">Logout</a>

        </div>

    </nav>


    <main class="container">

        <div class="form-card">

            <h1>Edit Product</h1>

            <p>
                Update your product information.
            </p>


            <form action="update-product.php"
                  method="post">


                <input
                    type="hidden"
                    name="id"
                    value="<?php echo $product["id"]; ?>"
                >


                <div class="form-group">

                    <label for="product_name">
                        Product Name
                    </label>

                    <input
                        type="text"
                        id="product_name"
                        name="product_name"
                        value="<?php echo htmlspecialchars($product["product_name"]); ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="category">
                        Category
                    </label>

                    <input
                        type="text"
                        id="category"
                        name="category"
                        value="<?php echo htmlspecialchars($product["category"]); ?>"
                    >

                </div>


                <div class="form-group">

                    <label for="quantity">
                        Quantity
                    </label>

                    <input
                        type="number"
                        id="quantity"
                        name="quantity"
                        min="0"
                        value="<?php echo $product["quantity"]; ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="price">
                        Price
                    </label>

                    <input
                        type="number"
                        id="price"
                        name="price"
                        min="0"
                        step="0.01"
                        value="<?php echo $product["price"]; ?>"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="purchase_date">
                        Purchase Date
                    </label>

                    <input
                        type="date"
                        id="purchase_date"
                        name="purchase_date"
                        value="<?php echo $product["purchase_date"]; ?>"
                    >

                </div>


                <div class="form-group">

                    <label for="expiry_date">
                        Expiry Date
                    </label>

                    <input
                        type="date"
                        id="expiry_date"
                        name="expiry_date"
                        value="<?php echo $product["expiry_date"]; ?>"
                    >

                </div>


                <button type="submit">
                    Update Product
                </button>

            </form>

        </div>

    </main>

</body>

</html>

<?php

$conn->close();

?>