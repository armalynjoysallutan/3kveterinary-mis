// ========================================
// BILLING SEARCH
// ========================================

const billingSearch =
    document.getElementById("billingSearch");


if (billingSearch) {

    billingSearch.addEventListener(
        "input",
        function () {

            const searchValue =
                this.value
                    .trim()
                    .toLowerCase();


            const rows =
                document.querySelectorAll(
                    ".billing-row"
                );


            rows.forEach(function (row) {

                const searchText =
                    row.dataset.search || "";


                if (
                    searchText.includes(searchValue)
                ) {

                    row.style.display = "";

                } else {

                    row.style.display = "none";

                }

            });

        }
    );

}


// ========================================
// ADD BILLING
// ========================================
const addBillingBtn =
    document.getElementById("addBillingBtn");

if (addBillingBtn) {
    addBillingBtn.addEventListener(
        "click",
        function () {

            window.location.href =
                "purchase_billing.php";

        }
    );
}

// ========================================
// VIEW BILLING STATEMENT
// ========================================

document.addEventListener("click", function (e) {

    const button = e.target.closest(".view-billing-btn");

    if (!button) {
        return;
    }

    const billingId = button.dataset.id;

    if (!billingId) {
        console.error("Billing ID not found.");
        return;
    }

    window.location.href =
        "billing_statement.php?billing_id=" + encodeURIComponent(billingId);

});

// ========================================
// BILLING ARCHIVE
// ========================================

const archiveBillingBtn =
    document.getElementById("archiveBillingBtn");

if (archiveBillingBtn) {

    archiveBillingBtn.addEventListener(
        "click",
        function () {

            window.location.href =
                "billing_archive.php";

        }
    );

}