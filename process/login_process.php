<?php

session_start();

require_once("../config/database.php");


// =========================================================
// ONLY POST REQUESTS ARE ALLOWED
// =========================================================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: ../auth/login.php");
    exit();

}


// =========================================================
// GET FORM DATA
// =========================================================

$username = trim($_POST["username"] ?? "");
$password = $_POST["password"] ?? "";


// =========================================================
// DETERMINE LOGIN TYPE
//
// Customer login page:
//     /customer/login.php
//
// Admin / Staff login page:
//     /auth/login.php
// =========================================================

$is_customer_login = false;

if (
    isset($_SERVER["HTTP_REFERER"]) &&
    strpos($_SERVER["HTTP_REFERER"], "/customer/") !== false
) {

    $is_customer_login = true;

}


// =========================================================
// VALIDATION
// =========================================================

if ($username === "" || $password === "") {

    if ($is_customer_login) {

        $_SESSION["login_error"] =
            "Please enter your email address and password.";

        header("Location: ../customer/login.php");

    } else {

        $_SESSION["error"] =
            "Please enter your username/email and password.";

        $_SESSION["login_error"] =
            "Please enter your username and password.";

        header("Location: ../auth/login.php");

    }

    exit();

}


// =========================================================
// FIND ACCOUNT
//
// IMPORTANT:
// The login page determines which roles are allowed.
//
// Customer Login:
//     Customer ONLY
//
// Admin/Staff Login:
//     Admin OR Staff ONLY
// =========================================================

if ($is_customer_login) {

    $stmt = $conn->prepare(
        "SELECT
            account_id,
            username,
            email,
            password,
            role,
            status
         FROM accounts
         WHERE email = ?
         AND role = 'Customer'
         AND status = 'Active'
         LIMIT 1"
    );

    $stmt->bind_param(
        "s",
        $username
    );

} else {

    $stmt = $conn->prepare(
        "SELECT
            account_id,
            username,
            email,
            password,
            role,
            status
         FROM accounts
         WHERE username = ?
         AND role IN ('Admin', 'Staff')
         AND status = 'Active'
         LIMIT 1"
    );

    $stmt->bind_param(
        "s",
        $username
    );

}


$stmt->execute();

$result = $stmt->get_result();


// =========================================================
// ACCOUNT NOT FOUND
// =========================================================

if ($result->num_rows !== 1) {

    $stmt->close();


    if ($is_customer_login) {

        $_SESSION["login_error"] =
            "Invalid email address or password.";

        header("Location: ../customer/login.php");

    } else {

        $_SESSION["login_error"] =
            "Invalid username or password.";

        header("Location: ../auth/login.php");

    }

    exit();

}


$user = $result->fetch_assoc();

$stmt->close();


// =========================================================
// VERIFY PASSWORD
// =========================================================

if (!password_verify($password, $user["password"])) {


    if ($is_customer_login) {

        $_SESSION["login_error"] =
            "Invalid email address or password.";

        header("Location: ../customer/login.php");

    } else {

        $_SESSION["login_error"] =
            "Invalid username or password.";

        header("Location: ../auth/login.php");

    }

    exit();

}


// =========================================================
// REGENERATE SESSION
// =========================================================

session_regenerate_id(true);


// =========================================================
// COMMON SESSION DATA
// =========================================================

$_SESSION["account_id"] = $user["account_id"];
$_SESSION["username"]   = $user["username"];
$_SESSION["email"]      = $user["email"];
$_SESSION["role"]       = $user["role"];


// =========================================================
// ADMIN LOGIN
// =========================================================

if ($user["role"] === "Admin") {

    $_SESSION["admin_id"] =
        $user["account_id"];

    $_SESSION["admin_username"] =
        $user["username"];

    $_SESSION["admin_role"] =
        $user["role"];


    header(
        "Location: ../admin/dashboard.php"
    );

    exit();

}


// =========================================================
// STAFF LOGIN
// =========================================================

if ($user["role"] === "Staff") {

    $_SESSION["staff_id"] =
        $user["account_id"];

    $_SESSION["staff_username"] =
        $user["username"];

    $_SESSION["staff_role"] =
        $user["role"];


    header(
        "Location: ../staff/dashboard.php"
    );

    exit();

}


// =========================================================
// CUSTOMER LOGIN
// =========================================================

if ($user["role"] === "Customer") {


    // =====================================================
    // FIND CUSTOMER RECORD
    // =====================================================

    $customer_stmt = $conn->prepare(
        "SELECT
            customer_id,
            owner_name,
            contact_number,
            email
         FROM customers
         WHERE email = ?
         AND record_status = 'Active'
         LIMIT 1"
    );


    $customer_stmt->bind_param(
        "s",
        $user["email"]
    );


    $customer_stmt->execute();

    $customer_result =
        $customer_stmt->get_result();


    // =====================================================
    // CUSTOMER RECORD NOT FOUND
    // =====================================================

    if ($customer_result->num_rows !== 1) {

        $customer_stmt->close();

        session_unset();

        $_SESSION["login_error"] =
            "Customer record not found. Please contact the clinic.";

        header(
            "Location: ../customer/login.php"
        );

        exit();

    }


    $customer =
        $customer_result->fetch_assoc();

    $customer_stmt->close();


    // =====================================================
    // CUSTOMER SESSION
    // =====================================================

    $_SESSION["customer_id"] =
        $customer["customer_id"];

    $_SESSION["customer_name"] =
        $customer["owner_name"];


    // =====================================================
    // SPLIT FIRST AND LAST NAME
    // =====================================================

    $name_parts = explode(
        " ",
        trim($customer["owner_name"])
    );


    $_SESSION["customer_first_name"] =
        $name_parts[0] ?? "";


    $_SESSION["customer_last_name"] =
        count($name_parts) > 1
            ? implode(
                " ",
                array_slice(
                    $name_parts,
                    1
                )
            )
            : "";


    // =====================================================
    // REDIRECT CUSTOMER
    // =====================================================

    header(
        "Location: ../customer/about.php"
    );

    exit();

}


// =========================================================
// UNKNOWN ROLE
// =========================================================

$_SESSION["error"] =
    "Your account role is not configured.";

header(
    "Location: ../auth/login.php"
);

exit();

?>