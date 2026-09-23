<?php

require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: signup.html");
    exit;
}

$name = trim($_POST["name"] ?? "");
$email = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";
$confirmPassword = $_POST["confirmPassword"] ?? "";

$shopAction = $_POST["shop_action"] ?? "";
$shopName = trim($_POST["shopName"] ?? "");

if (
    $name === "" ||
    $email === "" ||
    $password === "" ||
    $confirmPassword === "" ||
    $shopAction === "" ||
    $shopName === ""
) {
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

if (!in_array($shopAction, ["create", "join"], true)) {
    die("Invalid shop option.");
}

/* Check whether email already exists */
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

/* Hash password */
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

/*
|--------------------------------------------------------------------------
| CREATE NEW SHOP
|--------------------------------------------------------------------------
*/
if ($shopAction === "create") {

    $conn->begin_transaction();

    try {

        /* Create owner account first */
        $stmt = $conn->prepare(
            "INSERT INTO users
            (full_name, email, password, role, account_status)
            VALUES (?, ?, ?, 'owner', 'approved')"
        );

        $stmt->bind_param(
            "sss",
            $name,
            $email,
            $hashedPassword
        );

        if (!$stmt->execute()) {
            throw new Exception("Unable to create account.");
        }

        $userId = $stmt->insert_id;
        $stmt->close();

        /* Create shop */
        $shopStmt = $conn->prepare(
            "INSERT INTO shops
            (shop_name, owner_user_id)
            VALUES (?, ?)"
        );

        $shopStmt->bind_param(
            "si",
            $shopName,
            $userId
        );

        if (!$shopStmt->execute()) {
            throw new Exception("Unable to create shop.");
        }

        $shopId = $shopStmt->insert_id;
        $shopStmt->close();

        /* Connect owner to shop */
        $updateStmt = $conn->prepare(
            "UPDATE users
             SET shop_id = ?
             WHERE id = ?"
        );

        $updateStmt->bind_param(
            "ii",
            $shopId,
            $userId
        );

        if (!$updateStmt->execute()) {
            throw new Exception("Unable to connect account to shop.");
        }

        $updateStmt->close();

        $conn->commit();

        header("Location: login.html?signup=owner_success");
        exit;

    } catch (Exception $e) {

        $conn->rollback();
        $conn->close();

        die("Unable to create shop account. Please try again.");
    }
}

/*
|--------------------------------------------------------------------------
| JOIN EXISTING SHOP
|--------------------------------------------------------------------------
*/
if ($shopAction === "join") {

    /* Find shop */
    $shopStmt = $conn->prepare(
        "SELECT id, owner_user_id
         FROM shops
         WHERE shop_name = ?
         LIMIT 1"
    );

    $shopStmt->bind_param("s", $shopName);
    $shopStmt->execute();

    $shopResult = $shopStmt->get_result();

    if ($shopResult->num_rows === 0) {

        $shopStmt->close();
        $conn->close();

        die(
            "Shop not found. Please check the shop name or ask the shop owner for the exact name."
        );
    }

    $shop = $shopResult->fetch_assoc();

    $shopId = (int)$shop["id"];

    $shopStmt->close();

    $conn->begin_transaction();

    try {

        /* Create employee account as pending */
        $stmt = $conn->prepare(
            "INSERT INTO users
            (full_name, email, password, role, shop_id, account_status)
            VALUES (?, ?, ?, 'employee', ?, 'pending')"
        );

        $stmt->bind_param(
            "sssi",
            $name,
            $email,
            $hashedPassword,
            $shopId
        );

        if (!$stmt->execute()) {
            throw new Exception("Unable to create employee account.");
        }

        $employeeUserId = $stmt->insert_id;

        $stmt->close();

        /* Create approval request */
        $requestStmt = $conn->prepare(
            "INSERT INTO shop_join_requests
            (shop_id, employee_user_id, status)
            VALUES (?, ?, 'pending')"
        );

        $requestStmt->bind_param(
            "ii",
            $shopId,
            $employeeUserId
        );

        if (!$requestStmt->execute()) {
            throw new Exception("Unable to create join request.");
        }

        $requestStmt->close();

        $conn->commit();

        $conn->close();

        die(
            "Your account has been created, but it is waiting for approval from the shop owner."
        );

    } catch (Exception $e) {

        $conn->rollback();
        $conn->close();

        die("Unable to create employee account. Please try again.");
    }
}

$conn->close();

?>