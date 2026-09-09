<?php

session_start();

require_once("../config/database.php");


// ==========================================
// ONLY POST REQUESTS ARE ALLOWED
// ==========================================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../customer/signup.php");
    exit();
}


// ==========================================
// GET FORM DATA
// ==========================================

$first_name = trim($_POST["first_name"] ?? "");
$last_name = trim($_POST["last_name"] ?? "");
$phone = trim($_POST["phone"] ?? "");
$email = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";
$confirm_password = $_POST["confirm_password"] ?? "";
$terms = isset($_POST["terms"]);


// ==========================================
// VALIDATION
// ==========================================

if (
    $first_name === "" ||
    $last_name === "" ||
    $phone === "" ||
    $email === "" ||
    $password === "" ||
    $confirm_password === ""
) {
    $_SESSION["signup_error"] = "Please complete all required fields.";
    header("Location: ../customer/signup.php");
    exit();
}


if (!$terms) {
    $_SESSION["signup_error"] = "You must agree to the Terms of Service and Privacy Policy.";
    header("Location: ../customer/signup.php");
    exit();
}


if (!preg_match("/^[a-zA-ZÀ-ÿ\s'-]+$/", $first_name)) {
    $_SESSION["signup_error"] = "Please enter a valid first name.";
    header("Location: ../customer/signup.php");
    exit();
}


if (!preg_match("/^[a-zA-ZÀ-ÿ\s'-]+$/", $last_name)) {
    $_SESSION["signup_error"] = "Please enter a valid last name.";
    header("Location: ../customer/signup.php");
    exit();
}

// ==========================================
// PHILIPPINE PHONE NUMBER VALIDATION
// ==========================================

if (!preg_match("/^09[0-9]{9}$/", $phone)) {

    $_SESSION["signup_error"] =
        "Please enter a valid 11-digit Philippine mobile number starting with 09.";

    header("Location: ../customer/signup.php");
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION["signup_error"] = "Please enter a valid email address.";
    header("Location: ../customer/signup.php");
    exit();
}


if ($password !== $confirm_password) {
    $_SESSION["signup_error"] = "Passwords do not match.";
    header("Location: ../customer/signup.php");
    exit();
}


if (strlen($password) < 8) {
    $_SESSION["signup_error"] = "Password must be at least 8 characters.";
    header("Location: ../customer/signup.php");
    exit();
}


// ==========================================
// CHECK IF EMAIL ALREADY EXISTS
// ==========================================

$check_email = $conn->prepare(
    "SELECT account_id
     FROM accounts
     WHERE email = ?
     LIMIT 1"
);

$check_email->bind_param("s", $email);
$check_email->execute();

$email_result = $check_email->get_result();


if ($email_result->num_rows > 0) {

    $check_email->close();

    $_SESSION["signup_error"] = "An account with this email address already exists.";
    header("Location: ../customer/signup.php");
    exit();
}

$check_email->close();


// ==========================================
// CREATE CUSTOMER USERNAME
// ==========================================
//
// Customer login will use EMAIL,
// but accounts.username is required and UNIQUE.
//
// We generate a username automatically.
// Example:
// kathrenzy.santos
// kathrenzy.santos2
//
// ==========================================

$base_username =
    strtolower(
        preg_replace(
            "/[^a-zA-Z0-9]/",
            "",
            $first_name . "." . $last_name
        )
    );

if ($base_username === "") {
    $base_username = "customer";
}


$username = $base_username;
$counter = 1;


while (true) {

    $check_username = $conn->prepare(
        "SELECT account_id
         FROM accounts
         WHERE username = ?
         LIMIT 1"
    );

    $check_username->bind_param("s", $username);
    $check_username->execute();

    $username_result = $check_username->get_result();

    $check_username->close();

    if ($username_result->num_rows === 0) {
        break;
    }

    $counter++;
    $username = $base_username . $counter;
}


// ==========================================
// PREPARE DATA
// ==========================================

$owner_name = $first_name . " " . $last_name;

$hashed_password = password_hash(
    $password,
    PASSWORD_DEFAULT
);

$role = "Customer";
$status = "Active";


// ==========================================
// START TRANSACTION
// ==========================================

$conn->begin_transaction();


try {

    // ======================================
    // CREATE ACCOUNT
    // ======================================

    $account_stmt = $conn->prepare(
        "INSERT INTO accounts
        (
            username,
            email,
            password,
            role,
            status
        )
        VALUES (?, ?, ?, ?, ?)"
    );

    $account_stmt->bind_param(
        "sssss",
        $username,
        $email,
        $hashed_password,
        $role,
        $status
    );

    if (!$account_stmt->execute()) {
        throw new Exception("Unable to create account.");
    }

    $account_id = $conn->insert_id;

    $account_stmt->close();


    // ======================================
    // CREATE CUSTOMER RECORD
    // ======================================

    $customer_stmt = $conn->prepare(
        "INSERT INTO customers
        (
            owner_name,
            contact_number,
            email,
            record_status
        )
        VALUES (?, ?, ?, 'Active')"
    );

    $customer_stmt->bind_param(
        "sss",
        $owner_name,
        $phone,
        $email
    );

    if (!$customer_stmt->execute()) {
        throw new Exception("Unable to create customer record.");
    }

    $customer_id = $conn->insert_id;

    $customer_stmt->close();


    // ======================================
    // SAVE EVERYTHING
    // ======================================

    $conn->commit();


    // ======================================
    // CREATE CUSTOMER SESSION
    // ======================================

    $_SESSION["account_id"] = $account_id;
    $_SESSION["customer_id"] = $customer_id;
    $_SESSION["username"] = $username;
    $_SESSION["email"] = $email;
    $_SESSION["role"] = "Customer";

    $_SESSION["customer_first_name"] = $first_name;
    $_SESSION["customer_last_name"] = $last_name;
    $_SESSION["customer_name"] = $owner_name;


    // ======================================
    // REDIRECT TO CUSTOMER LANDING PAGE
    // ======================================

    header("Location: ../customer/about.php");
    exit();


} catch (Exception $e) {

    // ======================================
    // ROLLBACK IF SOMETHING FAILED
    // ======================================

    $conn->rollback();

    $_SESSION["signup_error"] =
        "Registration failed. Please try again.";

    header("Location: ../customer/signup.php");
    exit();
}

?>