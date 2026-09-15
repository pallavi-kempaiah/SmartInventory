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

<title>Scan Product | Smart Inventory</title>

<link rel="stylesheet" href="dashboard.css">

<style>

.scan-container {
    max-width: 700px;
    margin: 50px auto;
    padding: 20px;
}

.scan-card {
    background: white;
    padding: 35px;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    text-align: center;
}

.scan-card h1 {
    margin-bottom: 10px;
}

.scan-card p {
    color: #666;
    margin-bottom: 25px;
}

/* Transaction selection */

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

/* Upload */

.upload-box {
    border: 2px dashed #6c2bd9;
    border-radius: 10px;
    padding: 30px 20px;
    margin-top: 20px;
    background: #faf8ff;
}

.upload-box strong {
    display: block;
    margin-bottom: 20px;
}

.scan-options {
    display: flex;
    gap: 15px;
    justify-content: center;
    flex-wrap: wrap;
}

.option-btn {
    display: inline-block;
    padding: 13px 20px;
    background: #6c2bd9;
    color: white;
    border-radius: 7px;
    cursor: pointer;
    font-weight: bold;
}

.option-btn:hover {
    background: #5720b7;
}

.option-btn input {
    display: none;
}

.selected-file {
    margin-top: 18px;
    color: #555;
    font-size: 14px;
}

.scan-btn {
    margin-top: 25px;
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

@media (max-width: 600px) {

    .scan-container {
        margin: 25px auto;
        padding: 15px;
    }

    .scan-card {
        padding: 25px 18px;
    }

    .mode-options,
    .scan-options {
        flex-direction: column;
    }

    .mode-card,
    .option-btn {
        width: 100%;
        box-sizing: border-box;
    }

}
nav {
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


<div class="scan-container">

<a href="dashboard.php" class="back-btn">
← Back to Dashboard
</a>


<div class="scan-card">

<h1>📷 Scan Product</h1>

<p>
Take a photo of a product packet or choose
an existing image from your device.
</p>


<form
    action="process-product-image.php"
    method="post"
    enctype="multipart/form-data"
    id="productForm"
>


<!-- Stock In / Stock Out -->

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

<small>
Add product to inventory
</small>

</label>


<label class="mode-card">

<input
    type="radio"
    name="transaction_type"
    value="out"
>

<span>📤 Stock Out</span>

<small>
Remove product from inventory
</small>

</label>

</div>

</div>


<!-- Product image -->

<div class="upload-box">

<strong>📦 Select Product Image</strong>


<div class="scan-options">

<!-- Camera -->

<label class="option-btn">

📷 Take Photo

<input
    type="file"
    id="cameraInput"
    accept="image/*"
    capture="environment"
>

</label>


<!-- Gallery -->

<label class="option-btn">

🖼️ Choose from Gallery

<input
    type="file"
    id="galleryInput"
    accept="image/*"
>

</label>

</div>


<div class="selected-file" id="selectedFile">

No image selected

</div>

</div>


<!-- Actual file submitted to PHP -->

<input
    type="file"
    name="product_image"
    id="finalInput"
    style="display:none;"
    required
>


<button type="submit" class="scan-btn">

🤖 Recognize Product

</button>

</form>

</div>

</div>


<script>

const cameraInput = document.getElementById("cameraInput");
const galleryInput = document.getElementById("galleryInput");
const finalInput = document.getElementById("finalInput");
const selectedFile = document.getElementById("selectedFile");


function selectImage(input) {

    if (input.files.length > 0) {

        const file = input.files[0];

        const dataTransfer = new DataTransfer();

        dataTransfer.items.add(file);

        finalInput.files = dataTransfer.files;

        selectedFile.textContent =
            "Selected: " + file.name;

    }

}


cameraInput.addEventListener("change", function () {

    selectImage(cameraInput);

});


galleryInput.addEventListener("change", function () {

    selectImage(galleryInput);

});

</script>


</body>

</html>