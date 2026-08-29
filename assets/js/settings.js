/* =========================================
   SETTINGS PAGE
========================================= */

document.addEventListener("DOMContentLoaded", function () {

    const settingsButtons =
        document.querySelectorAll(".settings-open-btn");


    settingsButtons.forEach(function (button) {

        button.addEventListener("click", function () {

            const target =
                button.getAttribute("href");


            if (!target || target === "#") {

                return;

            }

        });

    });

});