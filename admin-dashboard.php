<?php

session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION["user_id"])) {
    header("Location: admin-login.html");
    exit;
}

if ($_SESSION["role"] !== "admin") {
    die("Access denied. Administrator access required.");
}

$fullName = $_SESSION["full_name"];

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard | Smart Inventory</title>

    <link rel="stylesheet" href="admin-dashboard.css">
</head>

<body>

    <!-- Navigation -->

    <nav class="navbar">

        <div class="logo">
            Smart Inventory | Admin
        </div>

        <div class="nav-links">

            <a href="admin-dashboard.php">Dashboard</a>

            <a href="#">Users</a>

            <a href="#">Inventory</a>

            <a href="#">Exchange Requests</a>

            <a href="#">Reports</a>

            <a href="admin-login.html">Logout</a>

        </div>

    </nav>


    <!-- Main Dashboard -->

    <main class="dashboard">

        <section class="welcome-section">

            <h1>
                Welcome, <?php echo htmlspecialchars($fullName); ?> 👋
            </h1>

            <p>
                Manage users, inventory and system activities.
            </p>

        </section>


        <!-- Statistics -->

        <section class="dashboard-cards">

            <div class="card">

                <h3>Total Users</h3>

                <p class="number">0</p>

                <span>Registered users</span>

            </div>


            <div class="card">

                <h3>Total Products</h3>

                <p class="number">0</p>

                <span>Products in system</span>

            </div>


            <div class="card">

                <h3>Dead Stock</h3>

                <p class="number">0</p>

                <span>Detected dead-stock items</span>

            </div>


            <div class="card">

                <h3>Exchange Requests</h3>

                <p class="number">0</p>

                <span>Pending exchanges</span>

            </div>

        </section>


        <!-- Admin Actions -->

        <section class="admin-actions">

            <h2>Admin Management</h2>


            <div class="action-container">

                <a href="#" class="action-card">

                    <h3>👥 Manage Users</h3>

                    <p>
                        View and manage registered shopkeepers.
                    </p>

                </a>


                <a href="#" class="action-card">

                    <h3>📦 Inventory Overview</h3>

                    <p>
                        Monitor inventory across the system.
                    </p>

                </a>


                <a href="#" class="action-card">

                    <h3>🤖 AI Insights</h3>

                    <p>
                        Monitor dead-stock and demand predictions.
                    </p>

                </a>


                <a href="#" class="action-card">

                    <h3>🔄 Exchange Requests</h3>

                    <p>
                        Review and manage exchange requests.
                    </p>

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