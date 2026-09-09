<?php

session_start();

if (isset($_SESSION['account_id']) && isset($_SESSION['role'])) {

    if ($_SESSION['role'] === 'Customer') {
        header("Location: ./about.php");
        exit();
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>3K Pet Solution - Sign Up</title>

    <link
        rel="stylesheet"
        href="./css/signup.css"
    >

</head>


<body>

    <div class="signup-container">


        <!-- LOGO -->

        <div class="logo-section">

            <img
                src="../assets/images/logo.png"
                alt="3K Pet Solution Logo"
            >

            <h1>3K Pet Solution</h1>

            <p>
                Veterinary Management Information System
            </p>

        </div>


        <!-- FORM TITLE -->

        <div class="form-title">

            <h2>Create Account</h2>

            <p>
                Register as a customer to get started.
            </p>

        </div>


        <!-- ERROR MESSAGE -->

        <?php if (isset($_SESSION['signup_error'])): ?>

            <div class="alert alert-error">

                <?php

                echo htmlspecialchars(
                    $_SESSION['signup_error']
                );

                unset($_SESSION['signup_error']);

                ?>

            </div>

        <?php endif; ?>


        <!-- SUCCESS MESSAGE -->

        <?php if (isset($_SESSION['signup_success'])): ?>

            <div class="alert alert-success">

                <?php

                echo htmlspecialchars(
                    $_SESSION['signup_success']
                );

                unset($_SESSION['signup_success']);

                ?>

            </div>

        <?php endif; ?>


        <!-- SIGN UP FORM -->

        <form
            action="../process/customer_signup_process.php"
            method="POST"
            id="signupForm"
        >


            <!-- FIRST NAME + LAST NAME -->

            <div class="form-row">


                <div class="form-group">

                    <label for="first_name">

                        First Name

                        <span class="required">*</span>

                    </label>


                    <input
                        type="text"
                        id="first_name"
                        name="first_name"
                        placeholder="Enter first name"
                        autocomplete="given-name"
                        maxlength="50"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="last_name">

                        Last Name

                        <span class="required">*</span>

                    </label>


                    <input
                        type="text"
                        id="last_name"
                        name="last_name"
                        placeholder="Enter last name"
                        autocomplete="family-name"
                        maxlength="50"
                        required
                    >

                </div>


            </div>


            <!-- PHONE -->

            <div class="form-group">

                <label for="phone">

                    Phone Number

                    <span class="required">*</span>

                </label>


                <input
                    type="tel"
                    id="phone"
                    name="phone"
                    placeholder="09XXXXXXXXX"
                    autocomplete="tel"
                    inputmode="numeric"
                    maxlength="11"
                    pattern="09[0-9]{9}"
                    required
                >

            </div>


            <!-- EMAIL -->

            <div class="form-group">

                <label for="email">

                    Email Address

                    <span class="required">*</span>

                </label>


                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="Enter email address"
                    autocomplete="email"
                    maxlength="100"
                    required
                >

            </div>


            <!-- PASSWORD -->

            <div class="form-group">

                <label for="password">

                    Password

                    <span class="required">*</span>

                </label>


                <div class="password-wrapper">

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter password"
                        autocomplete="new-password"
                        required
                    >


                    <button
                        type="button"
                        class="toggle-password"
                        data-target="password"
                        aria-label="Show password"
                    >
                        👁
                    </button>

                </div>

            </div>


            <!-- CONFIRM PASSWORD -->

            <div class="form-group">

                <label for="confirm_password">

                    Confirm Password

                    <span class="required">*</span>

                </label>


                <div class="password-wrapper">

                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        placeholder="Confirm password"
                        autocomplete="new-password"
                        required
                    >


                    <button
                        type="button"
                        class="toggle-password"
                        data-target="confirm_password"
                        aria-label="Show password"
                    >
                        👁
                    </button>

                </div>

            </div>


            <!-- TERMS -->

            <div class="terms">

                <input
                    type="checkbox"
                    id="terms"
                    name="terms"
                    value="1"
                    required
                >


                <label for="terms">

                    I agree to the

                    <a
                        href="#"
                        onclick="return false;"
                    >
                        Terms of Service
                    </a>

                    and

                    <a
                        href="#"
                        onclick="return false;"
                    >
                        Privacy Policy
                    </a>.

                </label>

            </div>


            <!-- SUBMIT -->

            <button
                type="submit"
                class="signup-btn"
            >
                Create Account
            </button>


        </form>


        <!-- LOGIN LINK -->

        <div class="login-link">

            Already have an account?

            <a href="./login.php">
                Login
            </a>

        </div>


    </div>


    <!-- CUSTOMER SIGNUP JS -->

    <script src="./js/signup.js"></script>

</body>

</html>