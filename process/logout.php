<?php

session_start();

require_once "../config/database.php";
require_once "../config/audit_log.php";


// ========================================
// AUDIT LOG — LOGOUT
// ========================================

if (isset($_SESSION["admin_id"])) {

    logAudit(
        $conn,
        "Authentication",
        "Logout",
        "User logged out of the system.",
        null
    );

}


// ========================================
// REMOVE ALL SESSION VARIABLES
// ========================================

$_SESSION = [];


// ========================================
// DESTROY SESSION
// ========================================

session_destroy();


// ========================================
// PREVENT BROWSER CACHE
// ========================================

header(
    "Cache-Control: no-store, no-cache, must-revalidate"
);

header(
    "Pragma: no-cache"
);


// ========================================
// REDIRECT TO LOGIN
// ========================================

header(
    "Location: ../auth/login.php"
);

exit();

?>