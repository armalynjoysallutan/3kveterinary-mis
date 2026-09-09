<?php

session_start();


// =========================================================
// SAVE CURRENT ROLE BEFORE CLEARING SESSION
// =========================================================

$role = $_SESSION["role"] ?? "";


// =========================================================
// DESTROY ALL SESSION DATA
// =========================================================

$_SESSION = [];


// Delete session cookie if cookies are being used

if (ini_get("session.use_cookies")) {

    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        "",
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );

}


session_destroy();


// =========================================================
// PREVENT BROWSER CACHE
// =========================================================

header(
    "Cache-Control: no-store, no-cache, must-revalidate, max-age=0"
);

header(
    "Cache-Control: post-check=0, pre-check=0",
    false
);

header("Pragma: no-cache");

header("Expires: 0");


// =========================================================
// ROLE-BASED REDIRECT
// =========================================================

// CUSTOMER

if ($role === "Customer") {

    header(
        "Location: ../customer/about.php"
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
// FALLBACK
// =========================================================

header(
    "Location: ../customer/about.php"
);

exit();

?>