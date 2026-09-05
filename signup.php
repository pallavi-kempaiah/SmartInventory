<?php

require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirmPassword"] ?? "";

    if ($name === "" || $email === "" || $password === "" || $confirmPassword === "") {
        die("All fields are required.");
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Please enter a valid email address.");
    }

    if (strlen($password) < 6) {
        die("Password must contain at least 6 characters.");
    }

    if ($password !== $confirmPassword) {
        die("Passwords do not match.");
    }

    // Check whether the email already exists
    $checkStmt = $conn->prepare(
        "SELECT id FROM users WHERE email = ?"
    );

    $checkStmt->bind_param("s", $email);
    $checkStmt->execute();

    $result = $checkStmt->get_result();

    if ($result->num_rows > 0) {
        $checkStmt->close();
        $conn->close();

        die("Account already exists. Please use a different email.");
    }

    $checkStmt->close();

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Create account
    $stmt = $conn->prepare(
        "INSERT INTO users (full_name, email, password)
         VALUES (?, ?, ?)"
    );

    $stmt->bind_param("sss", $name, $email, $hashedPassword);

    if ($stmt->execute()) {
    header("Location: login.html?signup=success");
    exit;
} else {
    echo "Unable to create account. Please try again.";
}

    $stmt->close();
    $conn->close();
}
?>