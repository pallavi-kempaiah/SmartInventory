<?php

$transactionType = $_POST["transaction_type"] ?? "in";

if (!in_array($transactionType, ["in", "out"])) {
    $transactionType = "in";
}
set_time_limit(120);

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.html");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ocr-stock-in.php");
    exit;
}


if (!isset($_FILES["receipt_image"])) {
    die("No image uploaded.");
}

$image = $_FILES["receipt_image"];


if ($image["error"] !== UPLOAD_ERR_OK) {
    die("Unable to upload the image.");
}


$allowedTypes = [
    "image/jpeg",
    "image/png",
    "image/webp"
];


if (!in_array($image["type"], $allowedTypes)) {
    die("Only JPG, PNG and WEBP images are allowed.");
}


$uploadDir = __DIR__ . "/uploads/";


if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}


$fileName =
    "receipt_" .
    time() .
    "_" .
    basename($image["name"]);


$filePath = $uploadDir . $fileName;


if (!move_uploaded_file($image["tmp_name"], $filePath)) {
    die("Unable to save uploaded image.");
}


/* ---------------------------------------
   Run Python OCR
--------------------------------------- */

$pythonPath = "python";

$pythonScript = __DIR__ . "/ocr_process.py";


$command =
    $pythonPath .
    " " .
    escapeshellarg($pythonScript) .
    " " .
    escapeshellarg($filePath);


$output = shell_exec($command . " 2>&1");


if ($output === null || trim($output) === "") {
    die("OCR did not return any data.");
}


/* ---------------------------------------
   Remove warning/error lines
--------------------------------------- */

$lines = preg_split("/\r\n|\n|\r/", trim($output));

$jsonLine = "";


foreach ($lines as $line) {

    $line = trim($line);

    if ($line === "") {
        continue;
    }

    /*
       Our Python script prints the final
       product data as JSON beginning with [
    */

    if (str_starts_with($line, "[")) {

        $jsonLine = $line;

        break;
    }
}


if ($jsonLine === "") {

    die(
        "OCR completed, but structured product data was not found."
    );

}


/* ---------------------------------------
   Convert JSON → PHP array
--------------------------------------- */

$ocrData = json_decode($jsonLine, true);


if (!is_array($ocrData)) {

    die(
        "Unable to read the OCR product data."
    );

}


/* ---------------------------------------
   Make sure products were detected
--------------------------------------- */

if (empty($ocrData)) {

    die(
        "No products were detected from the receipt."
    );

}


/* ---------------------------------------
   Store temporarily in session
--------------------------------------- */

$_SESSION["ocr_data"] = $ocrData;
$_SESSION["ocr_transaction_type"] = $transactionType;

/* ---------------------------------------
   Open review page
--------------------------------------- */

header(
    "Location: ocr-review.php?type=" .
    urlencode($transactionType)
);
exit;

?>