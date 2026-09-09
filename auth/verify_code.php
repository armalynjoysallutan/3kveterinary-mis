<?php

session_start();


// =========================================================
// CHECK EMAIL SESSION
// =========================================================

if (!isset($_SESSION["email"])) {

    header("Location: forgot_password.php");
    exit();

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

    <title>
        Verify Code
    </title>


    <link
        rel="stylesheet"
        href="../assets/css/login.css"
    >

</head>


<body>


<div class="container">


    <div class="logo">

        <img
            src="../assets/images/logo.png"
            alt="3K Pet Solution Logo"
        >

    </div>



    <div class="login-card">


        <h1>
            Verify Code
        </h1>


        <p>
            Enter the 6-digit verification code sent to your email.
        </p>



        <form
            action="../process/verify_code_process.php"
            method="POST"
            id="verifyCodeForm"
        >


            <div class="otp-container">


                <input
                    type="tel"
                    maxlength="1"
                    class="otp-input"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                >


                <input
                    type="tel"
                    maxlength="1"
                    class="otp-input"
                    inputmode="numeric"
                >


                <input
                    type="tel"
                    maxlength="1"
                    class="otp-input"
                    inputmode="numeric"
                >


                <input
                    type="tel"
                    maxlength="1"
                    class="otp-input"
                    inputmode="numeric"
                >


                <input
                    type="tel"
                    maxlength="1"
                    class="otp-input"
                    inputmode="numeric"
                >


                <input
                    type="tel"
                    maxlength="1"
                    class="otp-input"
                    inputmode="numeric"
                >


            </div>



            <!-- =================================================
                 HIDDEN COMPLETE VERIFICATION CODE
            ================================================== -->

            <input
                type="hidden"
                name="verification_code"
                id="verification_code"
            >



            <button
                type="submit"
            >
                Verify
            </button>


        </form>


    </div>


</div>



<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {


        const inputs =
            document.querySelectorAll(
                ".otp-input"
            );


        const hiddenCode =
            document.getElementById(
                "verification_code"
            );


        const form =
            document.getElementById(
                "verifyCodeForm"
            );



        /*
        ========================================================
        UPDATE HIDDEN CODE
        ========================================================
        */

        function updateCode() {

            let code = "";


            inputs.forEach(function (input) {

                code += input.value;

            });


            hiddenCode.value = code;


        }



        /*
        ========================================================
        OTP INPUT BEHAVIOR
        ========================================================
        */

        inputs.forEach(function (input, index) {


            input.addEventListener(
                "input",
                function () {


                    /*
                    Only allow numbers
                    */

                    this.value =
                        this.value.replace(
                            /[^0-9]/g,
                            ""
                        );


                    updateCode();


                    /*
                    Move to next box
                    */

                    if (
                        this.value !== "" &&
                        index < inputs.length - 1
                    ) {

                        inputs[
                            index + 1
                        ].focus();

                    }

                }
            );



            /*
            ====================================================
            BACKSPACE
            ====================================================
            */

            input.addEventListener(
                "keydown",
                function (event) {


                    if (
                        event.key === "Backspace" &&
                        this.value === "" &&
                        index > 0
                    ) {

                        inputs[
                            index - 1
                        ].focus();

                    }


                }
            );


            /*
            ====================================================
            PASTE 6-DIGIT CODE
            ====================================================
            */

            input.addEventListener(
                "paste",
                function (event) {


                    event.preventDefault();


                    const pastedCode =
                        (
                            event.clipboardData ||
                            window.clipboardData
                        )
                        .getData("text")
                        .replace(
                            /[^0-9]/g,
                            ""
                        )
                        .substring(
                            0,
                            6
                        );


                    pastedCode
                        .split("")
                        .forEach(
                            function (
                                digit,
                                digitIndex
                            ) {

                                if (
                                    inputs[digitIndex]
                                ) {

                                    inputs[
                                        digitIndex
                                    ].value =
                                        digit;

                                }

                            }
                        );


                    updateCode();


                    /*
                    Focus last filled box
                    */

                    const lastIndex =
                        Math.min(
                            pastedCode.length - 1,
                            inputs.length - 1
                        );


                    if (
                        lastIndex >= 0
                    ) {

                        inputs[
                            lastIndex
                        ].focus();

                    }


                }
            );


        });



        /*
        ========================================================
        FORM SUBMIT VALIDATION
        ========================================================
        */

        form.addEventListener(
            "submit",
            function (event) {


                updateCode();


                const code =
                    hiddenCode.value.trim();


                if (
                    code.length !== 6
                ) {

                    event.preventDefault();


                    alert(
                        "Please enter the complete 6-digit verification code."
                    );


                    return;

                }


            }
        );


    }
);

</script>


</body>

</html>