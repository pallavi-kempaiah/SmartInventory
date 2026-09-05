<?php

session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION["user_id"])) {
    header("Location: login.html");
    exit;
}

$fullName = $_SESSION["full_name"];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard | Smart Inventory</title>

    <link rel="stylesheet" href="dashboard.css">
</head>

<body>

    <!-- Navigation Bar -->
    <nav class="navbar">

        <div class="logo">
            Smart Inventory
        </div>

        <div class="nav-links">
            <a href="dashboard.html">Dashboard</a>
            <a href="inventory.php">Products</a>
            <a href="add-product.php">Add Product</a>
            <a href="#">Reports</a>
            <a href="#">Profile</a>
            <a href="logout.php">Logout</a>
        </div>

    </nav>


    <!-- Main Dashboard -->
    <main class="dashboard">

        <div class="welcome-section">
           <h1>Welcome, <?php echo htmlspecialchars($fullName); ?> 👋</h1>
            <p>
                Manage your inventory, monitor stock,
                and discover dead stock.
            </p>
        </div>


        <!-- Dashboard Cards -->
        <section class="dashboard-cards">

            <div class="card">
                <h3>Total Products</h3>
                <p class="number">0</p>
                <span>Products in inventory</span>
            </div>


            <div class="card">
                <h3>Low Stock</h3>
                <p class="number">0</p>
                <span>Products need attention</span>
            </div>


            <div class="card">
                <h3>Dead Stock</h3>
                <p class="number">0</p>
                <span>Products not selling</span>
            </div>


            <div class="card">
                <h3>Exchange Requests</h3>
                <p class="number">0</p>
                <span>Pending requests</span>
            </div>

        </section>


        <!-- Quick Actions -->
        <section class="quick-actions">

            <h2>Quick Actions</h2>

            <div class="action-container">

               <a href="add-product.php" class="action-card">
    <h3>➕ Add Product</h3>
                    <p>Add a new product to your inventory.</p>
                </a>

                <a href="inventory.php" class="action-card">
                    <h3>📦 View Inventory</h3>
                    <p>View and manage your products.</p>
                </a>

                <a href="#" class="action-card">
                    <h3>🤖 AI Insights</h3>
                    <p>Check dead-stock and demand predictions.</p>
                </a>

                <a href="#" class="action-card">
                    <h3>🔄 Exchange</h3>
                    <p>Exchange slow-moving inventory.</p>
                </a>

            </div>

        </section>

    </main>


    <script>
    window.addEventListener("pageshow", function (event) {
        if (event.persisted) {
            window.location.reload();
        }
    });
</script>
</body>
</html>