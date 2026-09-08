<?php

session_start();

require_once "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.html");
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Change Password | Smart Inventory</title>

    <link rel="stylesheet" href="dashboard.css">

    <style>

        .password-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
        }

        .password-card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        .password-card h1 {
            margin-top: 0;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 7px;
            font-weight: bold;
            color: #444;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 7px;
            box-sizing: border-box;
        }

        .form-group input:focus {
            outline: none;
            border-color: #6c2bd9;
        }

        .change-btn {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 7px;
            background: #6c2bd9;
            color: white;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        .change-btn:hover {
            background: #5720b7;
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


    <main class="password-container">

        <a href="profile.php" class="back-link">
            ← Back to Profile
        </a>

        <div class="password-card">

            <h1>Change Password</h1>

            <form action="update-password.php" method="post">

                <div class="form-group">

                    <label for="current_password">
                        Current Password
                    </label>

                    <input
                        type="password"
                        id="current_password"
                        name="current_password"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="new_password">
                        New Password
                    </label>

                    <input
                        type="password"
                        id="new_password"
                        name="new_password"
                        minlength="6"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="confirm_password">
                        Confirm New Password
                    </label>

                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        minlength="6"
                        required
                    >

                </div>


                <button type="submit" class="change-btn">
                    Change Password
                </button>

            </form>

        </div>

    </main>

</body>

</html>