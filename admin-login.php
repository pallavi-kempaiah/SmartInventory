<?php

session_start();

require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {
        die("Email and password are required.");
    }

    $stmt = $conn->prepare(
        "SELECT id, full_name, email, password, role
         FROM users
         WHERE email = ?"
    );

    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows === 1) {

        $user = $result->fetch_assoc();

        if ($user["role"] !== "admin") {
            die("Access denied. Admin account required.");
        }

        if (password_verify($password, $user["password"])) {

            $_SESSION["user_id"] = $user["id"];
            $_SESSION["full_name"] = $user["full_name"];
            $_SESSION["email"] = $user["email"];
            $_SESSION["role"] = $user["role"];

            echo "Admin login successful! Welcome, "
                 . htmlspecialchars($user["full_name"]);

        } else {
            echo "Invalid email or password.";
        }

    } else {
        echo "Invalid email or password.";
    }

    $stmt->close();
    $conn->close();
}
?>