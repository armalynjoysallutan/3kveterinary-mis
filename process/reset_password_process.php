<?php

session_start();

require_once "../config/database.php";


// =========================================================
// VERIFY RECOVERY SESSION
// =========================================================

if (
    !isset($_SESSION["verified"]) ||
    !isset($_SESSION["email"])
) {

    header(
        "Location: ../auth/forgot_password.php"
    );

    exit();

}


// =========================================================
// ONLY POST REQUESTS
// =========================================================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header(
        "Location: ../auth/reset_password.php"
    );

    exit();

}


// =========================================================
// GET PASSWORD DATA
// =========================================================

$password =
    trim($_POST["password"] ?? "");

$confirm =
    trim($_POST["confirm_password"] ?? "");


// =========================================================
// VALIDATE PASSWORD
// =========================================================

if ($password === "" || $confirm === "") {

    $_SESSION["error"] =
        "Please enter your new password.";

    header(
        "Location: ../auth/reset_password.php"
    );

    exit();

}


if ($password !== $confirm) {

    $_SESSION["error"] =
        "Passwords do not match.";

    header(
        "Location: ../auth/reset_password.php"
    );

    exit();

}


// =========================================================
// PASSWORD LENGTH
// =========================================================

if (strlen($password) < 8) {

    $_SESSION["error"] =
        "Password must be at least 8 characters.";

    header(
        "Location: ../auth/reset_password.php"
    );

    exit();

}


// =========================================================
// GET EMAIL
// =========================================================

$email = $_SESSION["email"];


// =========================================================
// GET ACCOUNT ROLE BEFORE UPDATING PASSWORD
// =========================================================

$account_stmt = mysqli_prepare(
    $conn,
    "SELECT role
     FROM accounts
     WHERE email = ?
     AND status = 'Active'
     LIMIT 1"
);


mysqli_stmt_bind_param(
    $account_stmt,
    "s",
    $email
);


mysqli_stmt_execute(
    $account_stmt
);


$account_result =
    mysqli_stmt_get_result(
        $account_stmt
    );


if ($account_result->num_rows !== 1) {

    mysqli_stmt_close(
        $account_stmt
    );

    unset($_SESSION["verified"]);
    unset($_SESSION["email"]);

    $_SESSION["error"] =
        "Account not found.";

    header(
        "Location: ../auth/forgot_password.php"
    );

    exit();

}


$account =
    mysqli_fetch_assoc(
        $account_result
    );


$role =
    $account["role"];


mysqli_stmt_close(
    $account_stmt
);


// =========================================================
// HASH NEW PASSWORD
// =========================================================

$hashedPassword =
    password_hash(
        $password,
        PASSWORD_DEFAULT
    );


// =========================================================
// UPDATE PASSWORD
// =========================================================

$stmt = mysqli_prepare(
    $conn,
    "UPDATE accounts
     SET password = ?
     WHERE email = ?"
);


mysqli_stmt_bind_param(
    $stmt,
    "ss",
    $hashedPassword,
    $email
);


if (!mysqli_stmt_execute($stmt)) {

    mysqli_stmt_close($stmt);

    $_SESSION["error"] =
        "Unable to change password.";

    header(
        "Location: ../auth/reset_password.php"
    );

    exit();

}


mysqli_stmt_close($stmt);


// =========================================================
// DELETE USED VERIFICATION CODE
// =========================================================

$delete = mysqli_prepare(
    $conn,
    "DELETE FROM password_resets
     WHERE email = ?"
);


mysqli_stmt_bind_param(
    $delete,
    "s",
    $email
);


mysqli_stmt_execute($delete);

mysqli_stmt_close($delete);


// =========================================================
// CLEAR RECOVERY SESSION
// =========================================================

unset($_SESSION["verified"]);
unset($_SESSION["email"]);


// =========================================================
// SUCCESS MESSAGE
// =========================================================

$_SESSION["success"] =
    "Password changed successfully.";


// =========================================================
// ROLE-BASED REDIRECT
// =========================================================

if ($role === "Customer") {

    header(
        "Location: ../customer/login.php"
    );

    exit();

}


// =========================================================
// ADMIN / STAFF
// =========================================================

if (
    $role === "Admin" ||
    $role === "Staff"
) {

    header(
        "Location: ../auth/login.php"
    );

    exit();

}


// =========================================================
// UNKNOWN ROLE
// =========================================================

header(
    "Location: ../auth/login.php"
);

exit();

?>