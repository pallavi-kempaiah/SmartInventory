<?php

session_start();

require_once "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.html");
    exit;
}
$userId = $_SESSION["user_id"];
$search = trim($_GET["search"] ?? "");

if ($search !== "") {
    $stmt = $conn->prepare(
        "SELECT id, product_name, category, quantity, price,
                purchase_date, expiry_date
         FROM products
         WHERE user_id = ?
         AND product_name LIKE ?
         ORDER BY id DESC"
    );

    $searchTerm = "%" . $search . "%";
    $stmt->bind_param("is", $userId, $searchTerm);
} else {
    $stmt = $conn->prepare(
        "SELECT id, product_name, category, quantity, price,
                purchase_date, expiry_date
         FROM products
         WHERE user_id = ?
         ORDER BY id DESC"
    );

    $stmt->bind_param("i", $userId);
}
$stmt->execute();

$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Inventory | Smart Inventory</title>

    <link rel="stylesheet"
          href="inventory.css">

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

        <div class="header">

            <div>

                <h1>My Inventory</h1>

<form method="get" action="inventory.php" class="search-form">
    <input
        type="text"
        name="search"
        placeholder="Search products..."
        value="<?php echo htmlspecialchars($search); ?>"
    >

    <button type="submit">Search</button>

    <?php if ($search !== ""): ?>
        <a href="inventory.php">Clear</a>
    <?php endif; ?>
</form>

                <p>
                    Manage your products and stock.
                </p>

            </div>

            <a href="add-product.php"
               class="add-btn">
                + Add Product
            </a>

        </div>


        <div class="table-container">

            <table>

                <thead>

                    <tr>

                        <th>Product</th>
                        <th>Category</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Purchase Date</th>
                        <th>Expiry Date</th>
                        <th>Actions</th>

                    </tr>

                </thead>

                <tbody>

                <?php if ($result->num_rows > 0): ?>

                    <?php while ($product = $result->fetch_assoc()): ?>

                        <tr>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $product["product_name"]
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $product["category"]
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $product["quantity"]
                                );
                                ?>
                            </td>

                            <td>
                                ₹<?php
                                echo htmlspecialchars(
                                    $product["price"]
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $product["purchase_date"]
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $product["expiry_date"]
                                );
                                ?>
                            </td>

                            <td>

                                <a href="edit-product.php?id=<?php echo $product["id"]; ?>">
    Edit
</a>

                                |

                                <form
    action="delete-product.php"
    method="post"
    style="display:inline;"
    onsubmit="return confirm('Are you sure you want to delete this product?');"
>

    <input
        type="hidden"
        name="id"
        value="<?php echo $product["id"]; ?>"
    >

    <button
        type="submit"
        style="background:none;
               border:none;
               padding:0;
               color:#6c2bd9;
               font-weight:bold;
               cursor:pointer;"
    >
        Delete
    </button>

</form>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>

                        <td colspan="7"
                            class="empty">

                            No products found.

                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </main>

</body>

</html>

<?php

$stmt->close();
$conn->close();

?>