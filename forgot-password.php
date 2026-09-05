<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

require_once "db.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");

    if ($email === "") {
        $message = "Please enter your email address.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
    } else {

        $stmt = $conn->prepare(
            "SELECT id, role FROM users WHERE email = ?"
        );

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows === 1) {

            $user = $result->fetch_assoc();

            $_SESSION["reset_user_id"] = $user["id"];
            $_SESSION["reset_role"] = $user["role"];

            header("Location: reset-password.php");
            exit;

        } else {
            $message = "No account was found with this email address.";
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Forgot Password - Smart Inventory</title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f5f3fa;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .card {
            background: white;
            width: 100%;
            max-width: 420px;
            padding: 35px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        }

        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 10px;
        }

        .description {
            text-align: center;
            color: #666;
            margin-bottom: 25px;
            line-height: 1.5;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #444;
        }

        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 7px;
            margin-bottom: 18px;
            font-size: 15px;
        }

        button {
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

        button:hover {
            background: #5820b5;
        }

        .message {
            margin-bottom: 15px;
            text-align: center;
            color: #d32f2f;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #6c2bd9;
            text-decoration: none;
            font-weight: bold;
        }
    </style>
</head>

<body>

    <div class="card">

        <h1>Forgot Password?</h1>

        <p class="description">
            Enter your registered email address to reset your password.
        </p>

        <?php if ($message !== ""): ?>
            <p class="message">
                <?php echo htmlspecialchars($message); ?>
            </p>
        <?php endif; ?>

        <form method="post">

            <label for="email">Email Address</label>

            <input
                type="email"
                id="email"
                name="email"
                placeholder="Enter your registered email"
                required
            >

            <button type="submit">
                Continue
            </button>

        </form>

        <a href="login.html" class="back-link">
            ← Back to User Login
        </a>

        <a href="admin-login.html" class="back-link">
            ← Back to Admin Login
        </a>

    </div>
    <script>
window.addEventListener("pageshow", function (event) {
    if (event.persisted) {
        window.location.reload();
    }
});
</script>

</body>
</html>