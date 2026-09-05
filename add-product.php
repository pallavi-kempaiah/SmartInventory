<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.html");
    exit;
}

$userId = $_SESSION["user_id"];

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Add Product | Smart Inventory</title>

    <link rel="stylesheet" href="add-product.css">
</head>

<body>

    <nav class="navbar">

        <div class="logo">
            Smart Inventory
        </div>

        <div class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="add-product.php">Add Product</a>
            <a href="#">Inventory</a>
            <a href="logout.php">Logout</a>
        </div>

    </nav>


    <main class="container">

        <div class="form-card">

            <h1>Add Product</h1>

            <p>Add a new product to your inventory.</p>

            <form action="save-product.php" method="post">

                <div class="form-group">

                    <label for="product_name">
                        Product Name
                    </label>

                    <input
                        type="text"
                        id="product_name"
                        name="product_name"
                        placeholder="Enter product name"
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
                        placeholder="e.g. Grocery, Electronics"
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
                    >

                </div>


                <button type="submit">
                    Add Product
                </button>

            </form>

        </div>

    </main>

</body>

</html>