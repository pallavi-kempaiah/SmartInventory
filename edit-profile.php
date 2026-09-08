<?php

session_start();

require_once "db.php";

/* Only logged-in users can access this page */
if (!isset($_SESSION["user_id"])) {
    header("Location: login.html");
    exit;
}

$userId = $_SESSION["user_id"];

/* Get current user details */
$stmt = $conn->prepare(
    "SELECT full_name, email
     FROM users
     WHERE id = ?"
);

$stmt->bind_param("i", $userId);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    die("User not found.");
}

$user = $result->fetch_assoc();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Profile | Smart Inventory</title>

    <link rel="stylesheet" href="dashboard.css">

    <style>

        .profile-container {
            max-width: 600px;
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

        .save-btn {
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

        .save-btn:hover {
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


    <main class="profile-container">

        <a href="profile.php" class="back-link">
            ← Back to Profile
        </a>

        <div class="profile-card">

            <h1>Edit Profile</h1>

            <form action="update-profile.php" method="post">

                <div class="form-group">

                    <label for="full_name">
                        Full Name
                    </label>

                    <input
                        type="text"
                        id="full_name"
                        name="full_name"
                        value="<?php echo htmlspecialchars($user["full_name"]); ?>"
                        required
                        maxlength="100"
                    >

                </div>


                <div class="form-group">

                    <label for="email">
                        Email
                    </label>

                    <input
                        type="email"
                        id="email"
                        value="<?php echo htmlspecialchars($user["email"]); ?>"
                        readonly
                    >

                </div>


                <button type="submit" class="save-btn">
                    Save Changes
                </button>

            </form>

        </div>

    </main>

</body>

</html>