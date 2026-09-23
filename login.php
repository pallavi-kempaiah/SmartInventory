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
        "SELECT id, full_name, email, password, role, shop_id, account_status
         FROM users
         WHERE email = ?"
    );

    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows === 1) {

        $user = $result->fetch_assoc();

        if (password_verify($password, $user["password"])) {

            // Employee must be approved by shop owner
            if (
                $user["role"] === "employee" &&
                $user["account_status"] !== "approved"
            ) {
                $stmt->close();
                $conn->close();

                die(
                    "Your shop access is still pending. " .
                    "Please wait for the shop owner to approve your request."
                );
            }

            // Create session
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["full_name"] = $user["full_name"];
            $_SESSION["email"] = $user["email"];
            $_SESSION["role"] = $user["role"];
            $_SESSION["shop_id"] = $user["shop_id"];
            $_SESSION["account_status"] = $user["account_status"];

            header("Location: dashboard.php");
            exit;

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