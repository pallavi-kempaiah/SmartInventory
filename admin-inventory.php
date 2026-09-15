<?php
session_start();

require_once "db.php";

/* Only logged-in admins can access this page */
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: admin-login.html");
    exit;
}

/* Search members */
$search = trim($_GET["search"] ?? "");

/* Get all members except admin */
if ($search !== "") {
    $searchTerm = "%" . $search . "%";

    $stmt = $conn->prepare(
        "SELECT id, full_name, email
         FROM users
         WHERE role != 'admin'
         AND (full_name LIKE ? OR email LIKE ?)
         ORDER BY full_name ASC"
    );

    $stmt->bind_param("ss", $searchTerm, $searchTerm);
} else {
    $stmt = $conn->prepare(
        "SELECT id, full_name, email
         FROM users
         WHERE role != 'admin'
         ORDER BY full_name ASC"
    );
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Inventory Overview - Smart Inventory</title>

    <link rel="stylesheet" href="admin-dashboard.css">

    <style>
        .inventory-page {
            max-width: 1200px;
            margin: 0 auto;
            padding: 35px 20px;
        }

        .page-header {
            margin-bottom: 25px;
        }

        .page-header h1 {
            margin: 0 0 8px;
            font-size: 32px;
        }

        .page-header p {
            margin: 0;
            color: #777;
        }

        .inventory-summary {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: white;
            padding: 20px 24px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        .member-count {
            font-size: 16px;
            color: #555;
        }

        .member-count strong {
            font-size: 25px;
            color: #6c2bd9;
            margin-right: 6px;
        }

        .member-search {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .member-search input {
            width: 280px;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            outline: none;
        }

        .member-search input:focus {
            border-color: #6c2bd9;
        }

        .member-search button {
            padding: 10px 16px;
            border: none;
            border-radius: 6px;
            background: #6c2bd9;
            color: white;
            font-weight: bold;
            cursor: pointer;
        }

        .member-search button:hover {
            background: #5720b7;
        }

        .member-search a {
            color: #6c2bd9;
            text-decoration: none;
            font-weight: bold;
            padding: 10px 5px;
        }

        .members-container {
            background: white;
            border-radius: 12px;
            overflow-x: auto;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        .members-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 650px;
        }

        .members-table th {
            background: #6c2bd9;
            color: white;
            padding: 15px;
            text-align: left;
            font-size: 14px;
        }

        .members-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            color: #444;
            font-size: 14px;
        }

        .members-table tr:last-child td {
            border-bottom: none;
        }

        .member-link {
            color: #6c2bd9;
            text-decoration: none;
            font-weight: bold;
        }

        .member-link:hover {
            text-decoration: underline;
        }

        .email-link {
            color: #555;
            text-decoration: none;
        }

        .email-link:hover {
            color: #6c2bd9;
            text-decoration: underline;
        }

        .view-btn {
            display: inline-block;
            padding: 8px 13px;
            background: #eee7ff;
            color: #6c2bd9;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            font-size: 13px;
        }

        .view-btn:hover {
            background: #6c2bd9;
            color: white;
        }

        .empty-message {
            text-align: center;
            padding: 40px 20px;
            color: #777;
        }

        @media (max-width: 700px) {

            .inventory-summary {
                align-items: flex-start;
                flex-direction: column;
                gap: 15px;
            }

            .member-search {
                width: 100%;
            }

            .member-search input {
                width: 100%;
            }
        }
        nav{
    display: flex;
    gap: 22px;
}

nav a {
    color: white;
    text-decoration: none;
    font-size: 15px;
}

nav a:hover {
    text-decoration: underline;
}

    </style>
</head>

<body>

<header class="navbar">

    <div class="logo">Smart Inventory</div>

    <nav>
        <a href="admin-dashboard.php">Dashboard</a>
        <a href="admin-users.php">Users</a>
        <a href="admin-inventory.php">Inventory</a>
        <a href="admin-exchange.php">Exchange Requests</a>
        <a href="admin-reports.php">Reports</a>
        <a href="logout.php">Logout</a>
    </nav>

</header>

<main class="inventory-page">

    <div class="page-header">
        <h1>Inventory Overview</h1>
        <p>Select a member to view their products and sales.</p>
    </div>

    <div class="inventory-summary">

        <div class="member-count">
            <strong><?php echo $result->num_rows; ?></strong>

            <?php echo ($search !== "")
                ? "Matching Members"
                : "Registered Members"; ?>
        </div>

        <form method="get" action="admin-inventory.php" class="member-search">

            <input
                type="text"
                name="search"
                placeholder="Search by name or email..."
                value="<?php echo htmlspecialchars($search); ?>"
            >

            <button type="submit">Search</button>

            <?php if ($search !== ""): ?>
                <a href="admin-inventory.php">Clear</a>
            <?php endif; ?>

        </form>

    </div>

    <div class="members-container">

        <?php if ($result->num_rows > 0): ?>

            <table class="members-table">

                <thead>
                    <tr>
                        <th>Member</th>
                        <th>Email</th>
                        <th>View Inventory</th>
                    </tr>
                </thead>

                <tbody>

                    <?php while ($user = $result->fetch_assoc()): ?>

                        <tr>

                            <td>
                                <a
                                    class="member-link"
                                    href="admin-member-inventory.php?id=<?php echo $user["id"]; ?>"
                                >
                                    <?php echo htmlspecialchars($user["full_name"]); ?>
                                </a>
                            </td>

                            <td>
                                <a
                                    class="email-link"
                                    href="admin-member-inventory.php?id=<?php echo $user["id"]; ?>"
                                >
                                    <?php echo htmlspecialchars($user["email"]); ?>
                                </a>
                            </td>

                            <td>
                                <a
                                    class="view-btn"
                                    href="admin-member-inventory.php?id=<?php echo $user["id"]; ?>"
                                >
                                    View
                                </a>
                            </td>

                        </tr>

                    <?php endwhile; ?>

                </tbody>

            </table>

        <?php else: ?>

            <div class="empty-message">
                No registered members found.
            </div>

        <?php endif; ?>

    </div>

</main>

</body>
</html>