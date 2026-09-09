document.addEventListener("DOMContentLoaded", function () {


    // ==========================================
    // SHOW / HIDE PASSWORD
    // ==========================================

    const toggleButtons =
        document.querySelectorAll(".toggle-password");

    const eyeOpen = `
        <svg
            xmlns="http://www.w3.org/2000/svg"
            width="19"
            height="19"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
            aria-hidden="true"
        >
            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"></path>
            <circle cx="12" cy="12" r="3"></circle>
        </svg>
    `;

    const eyeClosed = `
        <svg
            xmlns="http://www.w3.org/2000/svg"
            width="19"
            height="19"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
            aria-hidden="true"
        >
            <path d="M3 3l18 18"></path>
            <path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"></path>
            <path d="M9.9 4.2A10.8 10.8 0 0 1 12 4c6.5 0 10 8 10 8a17.7 17.7 0 0 1-3.2 4.4"></path>
            <path d="M6.6 6.6C3.8 8.6 2 12 2 12s3.5 8 10 8c1.5 0 2.8-.3 4-.8"></path>
        </svg>
    `;

    toggleButtons.forEach(function (button) {

        // Initial icon
        button.innerHTML = eyeOpen;

        button.addEventListener("click", function () {

            const targetId =
                button.getAttribute("data-target");

            const input =
                document.getElementById(targetId);

            if (!input) {
                return;
            }

            if (input.type === "password") {

                input.type = "text";

                button.innerHTML = eyeClosed;

                button.setAttribute(
                    "aria-label",
                    "Hide password"
                );

            } else {

                input.type = "password";

                button.innerHTML = eyeOpen;

                button.setAttribute(
                    "aria-label",
                    "Show password"
                );

            }

        });

    });


    // ==========================================
    // PHONE NUMBER
    // PHILIPPINE MOBILE NUMBER
    // ==========================================

    const phoneInput =
        document.getElementById("phone");


    if (phoneInput) {

        phoneInput.addEventListener(
            "input",
            function () {

                // Numbers only
                this.value =
                    this.value
                        .replace(/[^0-9]/g, "")
                        .slice(0, 11);

            }
        );


        phoneInput.addEventListener(
            "blur",
            function () {

                if (
                    this.value !== "" &&
                    !/^09[0-9]{9}$/.test(this.value)
                ) {

                    this.setCustomValidity(
                        "Please enter a valid 11-digit Philippine mobile number starting with 09."
                    );

                } else {

                    this.setCustomValidity("");

                }

            }
        );


        phoneInput.addEventListener(
            "input",
            function () {

                this.setCustomValidity("");

            }
        );

    }


    // ==========================================
    // PASSWORD MATCH
    // ==========================================

    const passwordInput =
        document.getElementById("password");

    const confirmPasswordInput =
        document.getElementById("confirm_password");


    function validatePasswords() {

        if (
            confirmPasswordInput.value !== "" &&
            passwordInput.value !==
            confirmPasswordInput.value
        ) {

            confirmPasswordInput.setCustomValidity(
                "Passwords do not match."
            );

        } else {

            confirmPasswordInput.setCustomValidity("");

        }

    }


    if (passwordInput && confirmPasswordInput) {

        passwordInput.addEventListener(
            "input",
            validatePasswords
        );

        confirmPasswordInput.addEventListener(
            "input",
            validatePasswords
        );

    }

});