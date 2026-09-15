<?php
session_start();

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
    <title>Scan Stock Invoice</title>
    <link rel="stylesheet" href="dashboard.css">

    <style>
        .ocr-container {
            max-width: 700px;
            margin: 50px auto;
            padding: 20px;
        }

        .ocr-card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            text-align: center;
        }

        .ocr-card h1 {
            margin-bottom: 10px;
        }

        .ocr-card p {
            color: #666;
            margin-bottom: 25px;
        }

        .upload-box {
            border: 2px dashed #6c2bd9;
            border-radius: 10px;
            padding: 35px 20px;
            margin-bottom: 20px;
        }

        .upload-box input {
            margin-top: 15px;
        }

        .scan-btn {
            background: #6c2bd9;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 7px;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
        }

        .scan-btn:hover {
            background: #5720b7;
        }

        .back-btn {
            display: inline-block;
            margin-bottom: 20px;
            text-decoration: none;
            color: #6c2bd9;
            font-weight: bold;
        }
        nav {
    display: flex;
    gap: 25px;
}

nav a {
    color: white;
    text-decoration: none;
    font-size: 15px;
}

nav a:hover {
    text-decoration: underline;
}
.mode-section {
    margin-bottom: 25px;
}

.mode-section h3 {
    margin-bottom: 15px;
}

.mode-options {
    display: flex;
    gap: 15px;
    justify-content: center;
    flex-wrap: wrap;
}

.mode-card {
    border: 2px solid #ddd;
    border-radius: 10px;
    padding: 18px;
    cursor: pointer;
    width: 200px;
    text-align: center;
    transition: 0.2s;
}

.mode-card:hover {
    border-color: #6c2bd9;
}

.mode-card input {
    margin-right: 6px;
}

.mode-card span {
    display: block;
    font-size: 17px;
    font-weight: bold;
    margin-bottom: 6px;
}

.mode-card small {
    color: #666;
}
    </style>
</head>

<body>

<header class="navbar">
    <div class="logo">Smart Inventory</div>

    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="inventory.php">Inventory</a>
        <a href="add-product.php">Add Product</a>
        <a href="sales-history.php">Sales History</a>
        <a href="profile.php">Profile</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<div class="ocr-container">

    <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>

    <div class="ocr-card">

        <h1>📷 Scan Stock Invoice</h1>

        <p>
            Upload a purchase invoice or receipt.
            The system will read the information and help fill your inventory automatically.
        </p>

        <form action="ocr-process-stock.php" method="post" enctype="multipart/form-data">
               
        <div class="mode-section">

    <h3>What are you scanning?</h3>

    <div class="mode-options">

        <label class="mode-card">
            <input
                type="radio"
                name="transaction_type"
                value="in"
                checked
            >
            <span>📥 Stock In</span>
            <small>Purchase / incoming stock</small>
        </label>

        <label class="mode-card">
            <input
                type="radio"
                name="transaction_type"
                value="out"
            >
            <span>📤 Stock Out</span>
            <small>Sales / outgoing stock</small>
        </label>

    </div>

</div>

            <div class="upload-box">

                <strong>Select Invoice / Receipt Image</strong>

                <br>

                <input
                    type="file"
                    name="receipt_image"
                    accept="image/*"
                    capture="environment"
                    required
                >

            </div>

            <button type="submit" class="scan-btn">
                🔍 Scan & Extract
            </button>

        </form>

    </div>

</div>

</body>
</html>