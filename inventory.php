<?php

session_start();

require_once "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.html");
    exit;
}

$userId = $_SESSION["user_id"];

$search = trim($_GET["search"] ?? "");
$category = trim($_GET["category"] ?? "");
$stockStatus = trim($_GET["stock_status"] ?? "");

/* Get categories for the logged-in user */

$categoryStmt = $conn->prepare(
    "SELECT DISTINCT category
     FROM products
     WHERE user_id = ?
     AND category IS NOT NULL
     AND category != ''
     ORDER BY category"
);

$categoryStmt->bind_param("i", $userId);
$categoryStmt->execute();

$categoryResult = $categoryStmt->get_result();


/* Build inventory query */

$sql = "SELECT id, product_name, category, quantity, price,
               purchase_date, expiry_date
        FROM products
        WHERE user_id = ?";

$params = [$userId];
$types = "i";


/* Search filter */

if ($search !== "") {
    $sql .= " AND product_name LIKE ?";
    $searchTerm = "%" . $search . "%";

    $params[] = $searchTerm;
    $types .= "s";
}


/* Category filter */

if ($category !== "") {
    $sql .= " AND category = ?";

    $params[] = $category;
    $types .= "s";
}


/* Stock status filter */

if ($stockStatus === "in_stock") {

    $sql .= " AND quantity > 5";

} elseif ($stockStatus === "low_stock") {

    $sql .= " AND quantity > 0 AND quantity <= 5";

} elseif ($stockStatus === "out_of_stock") {

    $sql .= " AND quantity = 0";
}


$sql .= " ORDER BY id DESC";


/* Prepare final query */

$stmt = $conn->prepare($sql);


/* Bind parameters dynamically */

$stmt->bind_param($types, ...$params);

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

<form method="get" action="inventory.php" class="filter-form">

    <input
        type="text"
        name="search"
        placeholder="Search products..."
        value="<?php echo htmlspecialchars($search); ?>"
    >

    <select name="category">

        <option value="">All Categories</option>

        <?php while ($cat = $categoryResult->fetch_assoc()): ?>

            <option
                value="<?php echo htmlspecialchars($cat["category"]); ?>"
                <?php echo ($category === $cat["category"]) ? "selected" : ""; ?>
            >
                <?php echo htmlspecialchars($cat["category"]); ?>
            </option>

        <?php endwhile; ?>

    </select>


    <select name="stock_status">

        <option value="">All Stock</option>

        <option
            value="in_stock"
            <?php echo ($stockStatus === "in_stock") ? "selected" : ""; ?>
        >
            In Stock
        </option>

        <option
            value="low_stock"
            <?php echo ($stockStatus === "low_stock") ? "selected" : ""; ?>
        >
            Low Stock
        </option>

        <option
            value="out_of_stock"
            <?php echo ($stockStatus === "out_of_stock") ? "selected" : ""; ?>
        >
            Out of Stock
        </option>

    </select>


    <button type="submit">
        Filter
    </button>


    <?php if ($search !== "" || $category !== "" || $stockStatus !== ""): ?>

        <a href="inventory.php">
            Clear
        </a>

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

                <td colspan="7" class="empty">

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