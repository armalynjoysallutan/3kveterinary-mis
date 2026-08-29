<?php

/**
 * =========================================================
 * AUDIT LOG HELPER
 * =========================================================
 *
 * Centralized function for recording system activities.
 *
 */


function logAudit(
    $conn,
    $module,
    $action,
    $description = null,
    $referenceNo = null
) {

    /*
     * Get the currently logged-in administrator.
     */

    $adminId =
        $_SESSION["admin_id"]
        ?? null;

    $adminUsername =
        $_SESSION["admin_username"]
        ?? null;

    $adminRole =
        $_SESSION["admin_role"]
        ?? null;


    /*
     * Insert audit record.
     */

    $sql = "
        INSERT INTO audit_logs (
            user_id,
            username,
            role,
            module,
            action,
            description,
            reference_no
        )
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ";


    $stmt =
        mysqli_prepare(
            $conn,
            $sql
        );


    /*
     * Prevent audit logging from
     * breaking the main system action.
     */

    if (!$stmt) {

        error_log(
            "Audit log prepare failed: "
            . mysqli_error($conn)
        );

        return false;
    }


    mysqli_stmt_bind_param(
        $stmt,
        "issssss",
        $adminId,
        $adminUsername,
        $adminRole,
        $module,
        $action,
        $description,
        $referenceNo
    );


    $success =
        mysqli_stmt_execute($stmt);


    if (!$success) {

        error_log(
            "Audit log insert failed: "
            . mysqli_stmt_error($stmt)
        );

    }


    mysqli_stmt_close($stmt);


    return $success;
}