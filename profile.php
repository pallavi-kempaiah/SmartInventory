<?php

session_start();

require_once "db.php";

/* Only logged-in users can access the profile */
if (!isset($_SESSION["user_id"])) {
    header("Location: login.html");
    exit;
}

$userId = $_SESSION["user_id"];

/* Get current user details */
$stmt = $conn->prepare(
    "SELECT full_name, email, role, created_at
     FROM users
     WHERE id = ?"
);

$stmt->bind_param("i", $userId);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    die("User profile not found.");
}

$user = $result->fetch_assoc();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Profile | Smart Inventory</title>

    <link rel="stylesheet" href="dashboard.css">

    <style>

        .profile-container {
            max-width: 700px;
            margin: 50px auto;
            padding: 20px;
        }

        .profile-card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        .profile-card h1 {
            margin-top: 0;
            margin-bottom: 25px;
        }

        .profile-item {
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }

        .profile-item:last-child {
            border-bottom: none;
        }

        .profile-item strong {
            display: block;
            margin-bottom: 5px;
            color: #555;
        }

        .profile-item span {
            color: #222;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #6c2bd9;
            text-decoration: none;
            font-weight: bold;
        }

        .back-link:hover {
            text-decoration: underline;
        }
        .edit-btn {
    display: block;
    margin-top: 25px;
    padding: 12px;
    background: #6c2bd9;
    color: white;
    text-align: center;
    text-decoration: none;
    border-radius: 7px;
    font-weight: bold;
}

.edit-btn:hover {
    background: #5720b7;
}
nav a {
    color: white;
    text-decoration: none;
    font-size: 15px;
}
nav a:hover {
    text-decoration: underline;
}
nav {
    display: flex;
    gap: 25px;
}

    </style>

</head>

<body>

    <header class="navbar">

        <div class="logo">
            Smart Inventory
        </div>

        <nav>

            <a href="dashboard.php">Dashboard</a>

            <a href="inventory.php">Inventory</a>

            <a href="add-product.php">Add Product</a>

            <a href="sales-history.php">Sales History</a>

            <a href="profile.php">Profile</a>

            <a href="logout.php">Logout</a>

        </nav>

    </header>


    <main class="profile-container">

        <a href="dashboard.php" class="back-link">
            ← Back to Dashboard
        </a>

        <div class="profile-card">

            <h1>My Profile</h1>

            <div class="profile-item">

                <strong>Full Name</strong>

                <span>
                    <?php echo htmlspecialchars($user["full_name"]); ?>
                </span>

            </div>


            <div class="profile-item">

                <strong>Email</strong>

                <span>
                    <?php echo htmlspecialchars($user["email"]); ?>
                </span>

            </div>


            <div class="profile-item">

                <strong>Role</strong>

                <span>
                    <?php echo htmlspecialchars($user["role"]); ?>
                </span>

            </div>


            <div class="profile-item">

                <strong>Account Created</strong>

                <span>
                    <?php echo htmlspecialchars($user["created_at"]); ?>
                </span>

            </div>
            <a href="edit-profile.php" class="edit-btn">
    ✏️ Edit Profile
</a>
<a href="change-password.php" class="edit-btn">
    🔐 Change Password
</a>
        </div>

    </main>

</body>

</html>