<?php

session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION["user_id"])) {
    header("Location: login.html");
    exit;
}

require_once "db.php";

$userId = (int) $_SESSION["user_id"];

/*
|--------------------------------------------------------------------------
| Get the latest role directly from the database
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare(
    "SELECT role, account_status, shop_id
     FROM users
     WHERE id = ?
     LIMIT 1"
);

$stmt->bind_param("i", $userId);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {

    $stmt->close();
    $conn->close();

    session_unset();
    session_destroy();

    header("Location: login.html");
    exit;
}

$user = $result->fetch_assoc();

$stmt->close();

$role = $user["role"];
$accountStatus = $user["account_status"];
$shopId = $user["shop_id"];

/*
|--------------------------------------------------------------------------
| ADMIN
|--------------------------------------------------------------------------
*/

if ($role === "admin") {

    $conn->close();

    header("Location: admin-dashboard.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| OWNER
|--------------------------------------------------------------------------
*/

if ($role === "owner") {

    /*
    | Make sure the owner actually owns a shop.
    */

    $ownerStmt = $conn->prepare(
        "SELECT id
         FROM shops
         WHERE owner_user_id = ?
         LIMIT 1"
    );

    $ownerStmt->bind_param("i", $userId);
    $ownerStmt->execute();

    $ownerResult = $ownerStmt->get_result();

    if ($ownerResult->num_rows !== 1) {

        $ownerStmt->close();
        $conn->close();

        die("Owner account is not connected to a shop.");
    }

    $ownerStmt->close();
    $conn->close();

    header("Location: owner-dashboard.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| EMPLOYEE
|--------------------------------------------------------------------------
*/

if ($role === "employee") {

    /*
    | An employee must be approved and connected to a shop.
    */

    if (
        $accountStatus !== "approved" ||
        empty($shopId)
    ) {

        $conn->close();

        session_unset();
        session_destroy();

        header("Location: login.html?access=pending");
        exit;
    }

    $conn->close();

    header("Location: employee-dashboard.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Unknown / invalid role
|--------------------------------------------------------------------------
*/

$conn->close();

session_unset();
session_destroy();

header("Location: login.html");
exit;

?>