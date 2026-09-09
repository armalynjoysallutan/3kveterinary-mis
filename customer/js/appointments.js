/* =========================================================
   APPOINTMENT CLIENT TYPE SWITCHING
========================================================= */

document.addEventListener("DOMContentLoaded", function () {

    const clientTypeCards =
        document.querySelectorAll(".client-type-card");

    const existingClientState =
        document.getElementById("existingClientState");

    const newClientState =
        document.getElementById("newClientState");


    if (
        !clientTypeCards.length ||
        !existingClientState ||
        !newClientState
    ) {
        return;
    }


    clientTypeCards.forEach(function (card) {

        card.addEventListener("click", function () {

            const selectedType =
                card.getAttribute("data-client-type");


            /* =========================================
               REMOVE SELECTED STATE
            ========================================= */

            clientTypeCards.forEach(function (item) {

                item.classList.remove("selected");

                item.setAttribute(
                    "aria-pressed",
                    "false"
                );

            });


            /* =========================================
               SELECT CLICKED CARD
            ========================================= */

            card.classList.add("selected");

            card.setAttribute(
                "aria-pressed",
                "true"
            );


            /* =========================================
               SHOW CORRESPONDING FORM
            ========================================= */

            if (selectedType === "existing") {

                existingClientState.classList.remove("hidden");

                newClientState.classList.add("hidden");

            }


            if (selectedType === "new") {

                newClientState.classList.remove("hidden");

                existingClientState.classList.add("hidden");

            }

        });

    });

});