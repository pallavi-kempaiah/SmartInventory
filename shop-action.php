<?php

session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION["user_id"])) {
    header("Location: login.html");
    exit;
}

require_once "db.php";

$userId = (int) $_SESSION["user_id"];
$action = $_POST["action"] ?? "";

if (!in_array($action, ["approve", "reject", "remove"], true)) {
    die("Invalid action.");
}


/*
|--------------------------------------------------------------------------
| Verify that the logged-in user is a shop owner
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare(
    "SELECT id, shop_name
     FROM shops
     WHERE owner_user_id = ?
     LIMIT 1"
);

$stmt->bind_param("i", $userId);
$stmt->execute();

$shopResult = $stmt->get_result();

if ($shopResult->num_rows !== 1) {
    $stmt->close();
    $conn->close();

    die("Access denied. Only the shop owner can perform this action.");
}

$shop = $shopResult->fetch_assoc();

$shopId = (int) $shop["id"];

$stmt->close();


/*
|--------------------------------------------------------------------------
| APPROVE EMPLOYEE
|--------------------------------------------------------------------------
*/

if ($action === "approve") {

    $requestId = (int) ($_POST["request_id"] ?? 0);

    if ($requestId <= 0) {
        die("Invalid request.");
    }

    $conn->begin_transaction();

    try {

        /*
         * Find the pending request and make sure
         * it belongs to the owner's shop.
         */

        $stmt = $conn->prepare(
            "SELECT employee_user_id
             FROM shop_join_requests
             WHERE id = ?
               AND shop_id = ?
               AND status = 'pending'
             LIMIT 1"
        );

        $stmt->bind_param("ii", $requestId, $shopId);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows !== 1) {
            throw new Exception("Join request not found.");
        }

        $request = $result->fetch_assoc();

        $employeeId = (int) $request["employee_user_id"];

        $stmt->close();


        /*
         * Approve employee account.
         */

        $stmt = $conn->prepare(
            "UPDATE users
             SET account_status = 'approved',
                 shop_id = ?
             WHERE id = ?
               AND role = 'employee'"
        );

        $stmt->bind_param("ii", $shopId, $employeeId);

        if (!$stmt->execute()) {
            throw new Exception("Unable to approve employee.");
        }

        $stmt->close();


        /*
         * Mark join request as approved.
         */

        $stmt = $conn->prepare(
            "UPDATE shop_join_requests
             SET status = 'approved'
             WHERE id = ?
               AND shop_id = ?"
        );

        $stmt->bind_param("ii", $requestId, $shopId);

        if (!$stmt->execute()) {
            throw new Exception("Unable to update request.");
        }

        $stmt->close();

        $conn->commit();

        $conn->close();

        header("Location: shop-management.php");
        exit;

    } catch (Exception $e) {

        $conn->rollback();
        $conn->close();

        die("Unable to approve the employee. Please try again.");
    }
}


/*
|--------------------------------------------------------------------------
| REJECT JOIN REQUEST
|--------------------------------------------------------------------------
*/

if ($action === "reject") {

    $requestId = (int) ($_POST["request_id"] ?? 0);

    if ($requestId <= 0) {
        die("Invalid request.");
    }

    $stmt = $conn->prepare(
        "UPDATE shop_join_requests
         SET status = 'rejected'
         WHERE id = ?
           AND shop_id = ?
           AND status = 'pending'"
    );

    $stmt->bind_param("ii", $requestId, $shopId);

    if ($stmt->execute()) {

        $stmt->close();
        $conn->close();

        header("Location: shop-management.php");
        exit;

    } else {

        $stmt->close();
        $conn->close();

        die("Unable to reject the request.");
    }
}


/*
|--------------------------------------------------------------------------
| REMOVE EMPLOYEE
|--------------------------------------------------------------------------
*/

if ($action === "remove") {

    $employeeId = (int) ($_POST["employee_id"] ?? 0);

    if ($employeeId <= 0) {
        die("Invalid employee.");
    }


    /*
     * Make sure this employee actually belongs
     * to the owner's shop.
     */

    $stmt = $conn->prepare(
        "SELECT id
         FROM users
         WHERE id = ?
           AND shop_id = ?
           AND role = 'employee'
           AND account_status = 'approved'
         LIMIT 1"
    );

    $stmt->bind_param("ii", $employeeId, $shopId);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows !== 1) {

        $stmt->close();
        $conn->close();

        die("Employee not found or does not belong to your shop.");
    }

    $stmt->close();


    /*
     * Remove employee from the shop.
     *
     * We keep their account instead of deleting it.
     */

    $stmt = $conn->prepare(
        "UPDATE users
         SET shop_id = NULL,
             account_status = 'pending'
         WHERE id = ?
           AND shop_id = ?
           AND role = 'employee'"
    );

    $stmt->bind_param("ii", $employeeId, $shopId);

    if (!$stmt->execute()) {

        $stmt->close();
        $conn->close();

        die("Unable to remove employee.");
    }

    $stmt->close();


    /*
     * Close any approved join request status.
     */

    $stmt = $conn->prepare(
        "UPDATE shop_join_requests
         SET status = 'rejected'
         WHERE employee_user_id = ?
           AND shop_id = ?
           AND status = 'approved'"
    );

    $stmt->bind_param("ii", $employeeId, $shopId);
    $stmt->execute();

    $stmt->close();
    $conn->close();

    header("Location: shop-management.php");
    exit;
}

?>