<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

require_once "db.php";

if (!isset($_SESSION["reset_user_id"])) {
    header("Location: forgot-password.php");
    exit;
}
$userId = $_SESSION["reset_user_id"];
$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirmPassword"] ?? "";

    if ($password === "" || $confirmPassword === "") {
        $message = "Please fill in all fields.";
    } elseif (strlen($password) < 6) {
        $message = "Password must contain at least 6 characters.";
    } elseif ($password !== $confirmPassword) {
        $message = "Passwords do not match.";
    } else {

        $hashedPassword = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        $stmt = $conn->prepare(
            "UPDATE users
             SET password = ?
             WHERE id = ?"
        );

        $stmt->bind_param(
            "si",
            $hashedPassword,
            $userId
        );

        if ($stmt->execute()) {

            unset($_SESSION["reset_user_id"]);
            unset($_SESSION["reset_role"]);

            header("Location: login.html");
            exit;

        } else {
            $message = "Unable to reset password. Please try again.";
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

    <title>Reset Password - Smart Inventory</title>

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

        <h1>Reset Password</h1>

        <p class="description">
            Create a new password for your account.
        </p>

        <?php if ($message !== ""): ?>

            <p class="message">
                <?php echo htmlspecialchars($message); ?>
            </p>

        <?php endif; ?>

        <form method="post">

            <label for="password">
                New Password
            </label>

            <input
                type="password"
                id="password"
                name="password"
                placeholder="Enter new password"
                required
            >

            <label for="confirmPassword">
                Confirm Password
            </label>

            <input
                type="password"
                id="confirmPassword"
                name="confirmPassword"
                placeholder="Confirm new password"
                required
            >

            <button type="submit">
                Reset Password
            </button>

        </form>

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