document.addEventListener("DOMContentLoaded", function () {

    console.log("Appointments Archive JS Loaded");


    /* =====================================================
       ELEMENTS
       ===================================================== */

    const searchInput =
        document.getElementById("archiveSearch");

    const tableBody =
        document.getElementById("archiveTableBody");

    const noSearchResult =
        document.getElementById("archiveNoSearchResult");


    /* =====================================================
       SEARCH ARCHIVED APPOINTMENTS
       ===================================================== */

    if (searchInput && tableBody) {

        searchInput.addEventListener(
            "input",
            function () {

                const searchValue =
                    searchInput.value
                        .trim()
                        .toLowerCase();

                const rows =
                    tableBody.querySelectorAll(
                        ".archive-appointment-row"
                    );

                let visibleCount = 0;


                rows.forEach(function (row) {

                    const searchableText =
                        row.dataset.search ||
                        row.textContent.toLowerCase();


                    if (
                        searchValue === "" ||
                        searchableText.includes(searchValue)
                    ) {

                        row.style.display = "";

                        visibleCount++;

                    } else {

                        row.style.display = "none";

                    }

                });


                /* =========================================
                   NO SEARCH RESULT
                ========================================== */

                if (noSearchResult) {

                    if (
                        searchValue !== "" &&
                        visibleCount === 0
                    ) {

                        noSearchResult.style.display = "";

                    } else {

                        noSearchResult.style.display = "none";

                    }

                }

            }
        );

    }


    /* =====================================================
       RESTORE APPOINTMENT
       ===================================================== */

    document.addEventListener(
        "click",
        function (event) {

            const button =
                event.target.closest(
                    ".archive-restore-btn"
                );


            if (!button) {
                return;
            }


            const appointmentId =
                button.dataset.id;


            if (!appointmentId) {
                return;
            }


            /* =========================================
               CONFIRMATION
            ========================================== */

            const confirmed = confirm(
                "Are you sure you want to restore this appointment?"
            );


            if (!confirmed) {
                return;
            }


            /* =========================================
               CREATE FORM
            ========================================== */

            const form =
                document.createElement("form");

            form.method = "POST";
            form.action = "appointments_archive.php";


            /* ACTION */

            const actionInput =
                document.createElement("input");

            actionInput.type = "hidden";
            actionInput.name = "action";
            actionInput.value =
                "restore_appointment";


            /* APPOINTMENT ID */

            const idInput =
                document.createElement("input");

            idInput.type = "hidden";
            idInput.name = "appointment_id";
            idInput.value = appointmentId;


            form.appendChild(actionInput);
            form.appendChild(idInput);

            document.body.appendChild(form);

            form.submit();

        }
    );

});