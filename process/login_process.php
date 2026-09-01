<?php

session_start();

include("../config/database.php");
require_once("../config/audit_log.php");


$username = mysqli_real_escape_string(
    $conn,
    $_POST['username']
);

$password = $_POST['password'];


/* =========================================================
   FIND ACTIVE ACCOUNT
========================================================= */

$sql = "SELECT *
        FROM accounts
        WHERE username='$username'
        AND status='Active'
        LIMIT 1";

$result = mysqli_query($conn, $sql);


if(mysqli_num_rows($result) == 1){

    $user = mysqli_fetch_assoc($result);


    /* =====================================================
       VERIFY PASSWORD
    ===================================================== */

    if(password_verify($password, $user['password'])){


        /* =================================================
           GENERIC SESSION
        ================================================= */

        $_SESSION['account_id'] =
            $user['account_id'];

        $_SESSION['username'] =
            $user['username'];

        $_SESSION['role'] =
            $user['role'];


        /* =================================================
           PRESERVE EXISTING ADMIN SESSION
           FOR ADMIN COMPATIBILITY
        ================================================= */

        if($user['role'] === 'Admin'){

            $_SESSION['admin_id'] =
                $user['account_id'];

            $_SESSION['admin_username'] =
                $user['username'];

            $_SESSION['admin_role'] =
                $user['role'];
        }


        /* =================================================
           AUDIT LOG
        ================================================= */

        logAudit(
            $conn,
            "Authentication",
            "Login",
            "User logged into the system.",
            null
        );


        /* =================================================
           ROLE-BASED REDIRECT
        ================================================= */

        if($user['role'] === 'Admin'){

            header(
                "Location: ../admin/dashboard.php"
            );

            exit();

        }


        if($user['role'] === 'Staff'){

            header(
                "Location: ../staff/dashboard.php"
            );

            exit();

        }


        if($user['role'] === 'Customer'){

            header(
                "Location: ../customer/dashboard.php"
            );

            exit();

        }


        /* =================================================
           UNKNOWN ROLE
        ================================================= */

        $_SESSION["error"] =
            "Your account role is not configured.";

        header(
            "Location: ../auth/login.php"
        );

        exit();


    }else{

        $_SESSION["error"] =
            "Invalid username or password.";

        header(
            "Location: ../auth/login.php"
        );

        exit();

    }


}else{

    $_SESSION["error"] =
        "Invalid username or password.";

    header(
        "Location: ../auth/login.php"
    );

    exit();

}

?>