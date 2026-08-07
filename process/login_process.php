<?php

session_start();

include("../config/database.php");

$username = mysqli_real_escape_string($conn, $_POST['username']);
$password = $_POST['password'];

$sql = "SELECT * FROM accounts
        WHERE username='$username'
        AND role='Admin'
        AND status='Active'
        LIMIT 1";

$result = mysqli_query($conn, $sql);

if(mysqli_num_rows($result) == 1){

    $user = mysqli_fetch_assoc($result);

    if(password_verify($password, $user['password'])){

        $_SESSION['admin_id'] = $user['account_id'];
        $_SESSION['admin_username'] = $user['username'];
        $_SESSION['admin_role'] = $user['role'];

        header("Location: ../admin/dashboard.php");
        exit();

    }else{

        $_SESSION["error"] = "Invalid username or password.";
        
        header("Location: ../auth/login.php");
        exit();

    }

}else{

    $_SESSION["error"] = "Invalid username or password.";

    header("Location: ../auth/login.php");
    exit();

}

?>