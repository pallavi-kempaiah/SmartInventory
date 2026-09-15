<?php

set_time_limit(120);

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.html");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: scan-product.php");
    exit;
}


/*
 * Get transaction type
 */

$transactionType = $_POST["transaction_type"] ?? "in";

if (!in_array($transactionType, ["in", "out"])) {
    $transactionType = "in";
}


/*
 * Check uploaded image
 */

if (!isset($_FILES["product_image"])) {
    die("No product image was uploaded.");
}

$image = $_FILES["product_image"];

if ($image["error"] !== UPLOAD_ERR_OK) {
    die("Unable to upload the product image.");
}


/*
 * Check image type
 */

$allowedTypes = [
    "image/jpeg",
    "image/png",
    "image/webp"
];

if (!in_array($image["type"], $allowedTypes)) {
    die("Only JPG, PNG and WEBP images are allowed.");
}


/*
 * Create uploads folder
 */

$uploadDir = __DIR__ . "/uploads/";

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}


/*
 * Save uploaded image
 */

$fileName = "product_" . time() . "_" . basename($image["name"]);

$filePath = $uploadDir . $fileName;

if (!move_uploaded_file($image["tmp_name"], $filePath)) {
    die("Unable to save the uploaded image.");
}


/*
 * Run Python AI recognition
 */

$pythonPath = "python";

$pythonScript = __DIR__ . "/product_ai.py";

$command =
    $pythonPath . " " .
    escapeshellarg($pythonScript) . " " .
    escapeshellarg($filePath);

$output = shell_exec($command . " 2>&1");


if ($output === null || trim($output) === "") {
    die("AI recognition did not return any data.");
}


/*
 * Find JSON returned by Python
 */

$lines = preg_split(
    "/\r\n|\n|\r/",
    trim($output)
);

$jsonLine = "";

foreach ($lines as $line) {

    $line = trim($line);

    if ($line === "") {
        continue;
    }

    if (str_starts_with($line, "[")) {
        $jsonLine = $line;
        break;
    }

    if (str_starts_with($line, "{")) {
        $jsonLine = $line;
        break;
    }
}


if ($jsonLine === "") {
    die("AI completed, but no structured prediction was returned.");
}


$predictions = json_decode($jsonLine, true);


if (!is_array($predictions)) {
    die("Unable to read the AI prediction.");
}


/*
 * Check whether Python returned an error
 */

if (isset($predictions["error"])) {

    die(
        "AI recognition error: " .
        htmlspecialchars($predictions["error"])
    );

}


if (empty($predictions)) {
    die("No product was recognized.");
}


/*
 * Store AI results in session
 */

$_SESSION["product_ai_predictions"] = $predictions;

$_SESSION["product_ai_image"] = $fileName;


/*
 * Store transaction type
 */

$_SESSION["product_ai_transaction_type"] = $transactionType;


/*
 * Go to result page
 */

header("Location: product-ai-result.php");

exit;

?>