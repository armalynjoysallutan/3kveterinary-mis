<?php

session_start();

require_once "../config/database.php";
require_once "../config/audit_log.php";


$success = logAudit(
    $conn,
    "Audit Trail",
    "Created",
    "Test audit log entry.",
    "TEST-001"
);


if ($success) {

    echo "Audit log successfully saved.";

} else {

    echo "Failed to save audit log.";

}

?>