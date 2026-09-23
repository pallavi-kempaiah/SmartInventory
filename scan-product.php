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
    border: none;
    font-size: 15px;
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


/* ============================================================
   CAMERA MODAL
   ============================================================ */

.camera-modal {
    display: none;

    position: fixed;
    z-index: 9999;

    left: 0;
    top: 0;

    width: 100%;
    height: 100%;

    background: rgba(0,0,0,0.85);

    align-items: center;
    justify-content: center;

    padding: 20px;
}

.camera-box {

    width: 100%;
    max-width: 650px;

    background: white;

    border-radius: 15px;

    padding: 20px;

    text-align: center;
}

.camera-box h2 {
    margin-bottom: 15px;
}

#cameraVideo {

    width: 100%;

    max-height: 60vh;

    object-fit: cover;

    background: black;

    border-radius: 10px;
}

.camera-controls {

    display: flex;

    gap: 10px;

    justify-content: center;

    margin-top: 15px;

    flex-wrap: wrap;
}

.camera-controls button {

    border: none;

    padding: 12px 20px;

    border-radius: 8px;

    font-size: 15px;

    font-weight: bold;

    cursor: pointer;
}

.capture-btn {

    background: #16a34a;

    color: white;
}

.close-camera-btn {

    background: #dc2626;

    color: white;
}

.camera-status {

    margin-top: 12px;

    color: #666;

    font-size: 14px;
}


/* Hidden canvas */

#captureCanvas {
    display: none;
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

    .camera-box {
        padding: 15px;
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


<!-- ============================================================
     STOCK IN / STOCK OUT
     ============================================================ -->

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



<!-- ============================================================
     PRODUCT IMAGE
     ============================================================ -->

<div class="upload-box">

<strong>📦 Select Product Image</strong>


<div class="scan-options">


<!-- CAMERA BUTTON -->

<button
    type="button"
    class="option-btn"
    id="takePhotoBtn"
>
📷 Take Photo
</button>


<!-- GALLERY -->

<label class="option-btn">

🖼️ Choose from Gallery

<input
    type="file"
    id="galleryInput"
    accept="image/*"
>

</label>

</div>


<div
    class="selected-file"
    id="selectedFile"
>
No image selected
</div>

</div>



<!-- ============================================================
     ACTUAL FILE SUBMITTED TO PHP
     ============================================================ -->

<input
    type="file"
    name="product_image"
    id="finalInput"
    style="display:none;"
    required
>


<button
    type="submit"
    class="scan-btn"
>
🤖 Recognize Product
</button>


</form>

</div>

</div>



<!-- ============================================================
     CAMERA MODAL
     ============================================================ -->

<div
    class="camera-modal"
    id="cameraModal"
>

<div class="camera-box">

<h2>📷 Take Product Photo</h2>


<video
    id="cameraVideo"
    autoplay
    playsinline
></video>


<canvas
    id="captureCanvas"
></canvas>


<div class="camera-controls">

<button
    type="button"
    class="capture-btn"
    id="captureBtn"
>
📸 Capture Photo
</button>


<button
    type="button"
    class="close-camera-btn"
    id="closeCameraBtn"
>
✕ Close Camera
</button>

</div>


<div
    class="camera-status"
    id="cameraStatus"
>
Allow camera access when Chrome asks.
</div>

</div>

</div>



<script>


/* ============================================================
   ELEMENTS
   ============================================================ */

const takePhotoBtn =
    document.getElementById("takePhotoBtn");

const galleryInput =
    document.getElementById("galleryInput");

const finalInput =
    document.getElementById("finalInput");

const selectedFile =
    document.getElementById("selectedFile");

const cameraModal =
    document.getElementById("cameraModal");

const cameraVideo =
    document.getElementById("cameraVideo");

const captureCanvas =
    document.getElementById("captureCanvas");

const captureBtn =
    document.getElementById("captureBtn");

const closeCameraBtn =
    document.getElementById("closeCameraBtn");

const cameraStatus =
    document.getElementById("cameraStatus");


let cameraStream = null;



/* ============================================================
   SELECT IMAGE
   ============================================================ */

function selectImage(file) {

    if (!file) {
        return;
    }


    const dataTransfer =
        new DataTransfer();

    dataTransfer.items.add(file);

    finalInput.files =
        dataTransfer.files;


    selectedFile.textContent =
        "Selected: " + file.name;
}



/* ============================================================
   GALLERY
   ============================================================ */

galleryInput.addEventListener(
    "change",
    function () {

        if (
            galleryInput.files &&
            galleryInput.files.length > 0
        ) {

            const file =
                galleryInput.files[0];

            selectImage(file);
        }

    }
);



/* ============================================================
   OPEN CAMERA
   ============================================================ */

takePhotoBtn.addEventListener(
    "click",
    async function () {

        cameraModal.style.display =
            "flex";

        cameraStatus.textContent =
            "Starting camera...";


        try {

            /*
             * Request webcam access.
             */

            cameraStream =
                await navigator.mediaDevices.getUserMedia({

                    video: {
                        facingMode: {
                            ideal: "environment"
                        },

                        width: {
                            ideal: 1280
                        },

                        height: {
                            ideal: 720
                        }
                    },

                    audio: false

                });


            cameraVideo.srcObject =
                cameraStream;


            cameraStatus.textContent =
                "Camera ready. Position the product and capture the photo.";

        }

        catch (error) {

            console.error(
                "Camera error:",
                error
            );


            cameraStatus.textContent =
                "Unable to access camera. Please allow camera permission in Chrome.";


            alert(
                "Camera access was blocked or unavailable.\n\n" +
                "Please allow camera permission in Chrome and try again."
            );

        }

    }
);



/* ============================================================
   CAPTURE PHOTO
   ============================================================ */

captureBtn.addEventListener(
    "click",
    function () {

        if (!cameraStream) {

            alert(
                "Camera is not active."
            );

            return;
        }


        /*
         * Use actual camera resolution.
         */

        const width =
            cameraVideo.videoWidth;

        const height =
            cameraVideo.videoHeight;


        if (!width || !height) {

            alert(
                "Camera is not ready yet. Please wait a moment."
            );

            return;
        }


        captureCanvas.width =
            width;

        captureCanvas.height =
            height;


        const context =
            captureCanvas.getContext("2d");


        /*
         * Draw camera frame to canvas.
         */

        context.drawImage(
            cameraVideo,
            0,
            0,
            width,
            height
        );


        /*
         * Convert canvas to image.
         */

        captureCanvas.toBlob(
            function (blob) {

                if (!blob) {

                    alert(
                        "Unable to capture photo."
                    );

                    return;
                }


                const file =
                    new File(
                        [blob],
                        "camera_product_" +
                        Date.now() +
                        ".jpg",
                        {
                            type: "image/jpeg"
                        }
                    );


                /*
                 * Put captured image into
                 * the existing form input.
                 */

                selectImage(file);


                /*
                 * Close camera.
                 */

                stopCamera();


                cameraModal.style.display =
                    "none";


                /*
                 * Show success.
                 */

                selectedFile.textContent =
                    "📷 Captured: " +
                    file.name;

            },

            "image/jpeg",

            0.92

        );

    }
);



/* ============================================================
   STOP CAMERA
   ============================================================ */

function stopCamera() {

    if (cameraStream) {

        cameraStream
            .getTracks()
            .forEach(
                function (track) {
                    track.stop();
                }
            );

        cameraStream = null;
    }


    cameraVideo.srcObject =
        null;
}



/* ============================================================
   CLOSE CAMERA
   ============================================================ */

closeCameraBtn.addEventListener(
    "click",
    function () {

        stopCamera();

        cameraModal.style.display =
            "none";

    }
);



/* ============================================================
   CLOSE CAMERA WHEN CLICKING OUTSIDE
   ============================================================ */

cameraModal.addEventListener(
    "click",
    function (event) {

        if (
            event.target ===
            cameraModal
        ) {

            stopCamera();

            cameraModal.style.display =
                "none";
        }

    }
);



/* ============================================================
   STOP CAMERA WHEN LEAVING PAGE
   ============================================================ */

window.addEventListener(
    "beforeunload",
    function () {

        stopCamera();

    }
);


</script>


</body>

</html>