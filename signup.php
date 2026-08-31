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

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare(
        "INSERT INTO users (full_name, email, password) VALUES (?, ?, ?)"
    );

    $stmt->bind_param("sss", $name, $email, $hashedPassword);

    if ($stmt->execute()) {
        echo "Account created successfully!";
    } else {
        if ($conn->errno === 1062) {
            echo "An account with this email already exists.";
        } else {
            echo "Signup failed.";
        }
    }

    $stmt->close();
    $conn->close();
}
?>