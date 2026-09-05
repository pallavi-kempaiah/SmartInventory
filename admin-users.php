<?php

session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

require_once "db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: admin-login.html");
    exit;
}

$stmt = $conn->prepare(
    "SELECT id, full_name, email, role, created_at
     FROM users
     WHERE role != 'admin'
     ORDER BY id DESC"
);

$stmt->execute();

$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Users | Smart Inventory</title>

    <link rel="stylesheet" href="admin-dashboard.css">

    <style>
        .users-page {
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

        .users-summary {
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

        .table-container {
            background: white;
            border-radius: 12px;
            overflow-x: auto;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        .users-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 700px;
        }

        .users-table th {
            background: #6c2bd9;
            color: white;
            padding: 15px;
            text-align: left;
            font-size: 14px;
        }

        .users-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            color: #444;
            font-size: 14px;
        }

        .users-table tr:last-child td {
            border-bottom: none;
        }

        .users-table tbody tr {
            transition: background 0.2s ease;
        }

        .users-table tbody tr:hover {
            background: #f8f5ff;
        }

        .member-name {
            font-weight: bold;
            color: #333;
        }

        .member-email {
            color: #666;
        }

        .role-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            background: #eee7ff;
            color: #6c2bd9;
            font-size: 12px;
            font-weight: bold;
        }

        .delete-btn {
            border: none;
            background: #fff0f0;
            color: #d32f2f;
            padding: 8px 13px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            transition: 0.2s ease;
        }

        .delete-btn:hover {
            background: #d32f2f;
            color: white;
        }

        .empty-message {
            text-align: center;
            padding: 40px 20px;
            color: #777;
        }

        @media (max-width: 600px) {
            .users-page {
                padding: 25px 12px;
            }

            .page-header h1 {
                font-size: 26px;
            }

            .users-summary {
                padding: 16px;
            }
        }
    </style>
</head>

<body>

<header class="navbar">

    <div class="logo">
        Smart Inventory
    </div>

    <nav>
        <a href="admin-dashboard.php">Dashboard</a>
        <a href="admin-users.php">Users</a>
        <a href="admin-inventory.php">Inventory</a>
        <a href="admin-exchange.php">Exchange Requests</a>
        <a href="admin-reports.php">Reports</a>
        <a href="logout.php">Logout</a>
    </nav>

</header>


<main class="users-page">

    <div class="page-header">
        <h1>Manage Users</h1>
        <p>View and manage registered members of Smart Inventory.</p>
    </div>


    <div class="users-summary">

        <div class="member-count">
            <strong>
                <?php echo $result->num_rows; ?>
            </strong>
            Registered Members
        </div>

    </div>


    <div class="table-container">

        <?php if ($result->num_rows > 0): ?>

            <table class="users-table">

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Member</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Joined</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                    <?php while ($user = $result->fetch_assoc()): ?>

                        <tr>

                            <td>
                                <?php echo $user["id"]; ?>
                            </td>

                            <td>
                                <span class="member-name">
                                    <?php echo htmlspecialchars($user["full_name"]); ?>
                                </span>
                            </td>

                            <td>
                                <span class="member-email">
                                    <?php echo htmlspecialchars($user["email"]); ?>
                                </span>
                            </td>

                            <td>
                                <span class="role-badge">
                                    <?php echo htmlspecialchars($user["role"]); ?>
                                </span>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($user["created_at"]); ?>
                            </td>

                            <td>

                                <form
                                    action="delete-users.php"
                                    method="post"
                                    onsubmit="return confirm('Are you sure you want to delete this member?');"
                                >

                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?php echo $user["id"]; ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="delete-btn"
                                    >
                                        Delete
                                    </button>

                                </form>

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