const toggles = document.querySelectorAll(".toggle-password");

toggles.forEach(toggle => {

    toggle.addEventListener("click", () => {

        const input = document.getElementById(toggle.dataset.target);

        if (input.type === "password") {

            input.type = "text";

            toggle.classList.remove("fa-eye");
            toggle.classList.add("fa-eye-slash");

        } else {

            input.type = "password";

            toggle.classList.remove("fa-eye-slash");
            toggle.classList.add("fa-eye");

        }

    });

});