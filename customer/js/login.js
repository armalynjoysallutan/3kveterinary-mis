document.addEventListener("DOMContentLoaded", function () {

    const passwordInput = document.getElementById("password");
    const passwordToggle = document.getElementById("passwordToggle");
    const eyeIcon = document.getElementById("eyeIcon");

    if (!passwordToggle || !passwordInput || !eyeIcon) {
        return;
    }

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

    // Initial state
    eyeIcon.innerHTML = eyeOpen;

    passwordToggle.addEventListener("click", function () {

        if (passwordInput.type === "password") {

            passwordInput.type = "text";

            eyeIcon.innerHTML = eyeClosed;

            passwordToggle.setAttribute(
                "aria-label",
                "Hide password"
            );

        } else {

            passwordInput.type = "password";

            eyeIcon.innerHTML = eyeOpen;

            passwordToggle.setAttribute(
                "aria-label",
                "Show password"
            );

        }

    });

});