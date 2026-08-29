document.addEventListener("DOMContentLoaded", function () {

    const searchInput =
        document.getElementById(
            "inventoryArchiveSearch"
        );

    const categoryFilter =
        document.getElementById(
            "inventoryArchiveCategoryFilter"
        );

    const supplierFilter =
        document.getElementById(
            "inventoryArchiveSupplierFilter"
        );

    const stockFilter =
        document.getElementById(
            "inventoryArchiveStockFilter"
        );

    const applyButton =
        document.getElementById(
            "applyInventoryArchiveFilters"
        );

    const resetButton =
        document.getElementById(
            "resetInventoryArchiveFilters"
        );

    const rows = Array.from(
        document.querySelectorAll(
            ".archive-inventory-row"
        )
    );


    function applyFilters() {

        const searchValue =
            (searchInput?.value || "")
                .trim()
                .toLowerCase();

        const categoryValue =
            (
                categoryFilter?.value ||
                "all"
            ).toLowerCase();

        const supplierValue =
            (
                supplierFilter?.value ||
                "all"
            ).toLowerCase();

        const stockValue =
            (
                stockFilter?.value ||
                "all"
            ).toLowerCase();


        rows.forEach(function (row) {

            const search =
                (
                    row.dataset.search ||
                    ""
                ).toLowerCase();

            const category =
                (
                    row.dataset.category ||
                    ""
                ).toLowerCase();

            const supplier =
                (
                    row.dataset.supplier ||
                    ""
                ).toLowerCase();

            const stockStatus =
                (
                    row.dataset.stockStatus ||
                    ""
                ).toLowerCase();


            const matchesSearch =
                searchValue === "" ||
                search.includes(searchValue);


            const matchesCategory =
                categoryValue === "all" ||
                category === categoryValue;


            const matchesSupplier =
                supplierValue === "all" ||
                supplier === supplierValue;


            const matchesStock =
                stockValue === "all" ||
                stockStatus === stockValue;


            row.style.display =
                matchesSearch &&
                matchesCategory &&
                matchesSupplier &&
                matchesStock
                    ? ""
                    : "none";

        });

    }


    if (applyButton) {

        applyButton.addEventListener(
            "click",
            applyFilters
        );

    }


    if (searchInput) {

        searchInput.addEventListener(
            "input",
            applyFilters
        );

    }


    if (resetButton) {

        resetButton.addEventListener(
            "click",
            function () {

                if (searchInput) {
                    searchInput.value = "";
                }

                if (categoryFilter) {
                    categoryFilter.value = "all";
                }

                if (supplierFilter) {
                    supplierFilter.value = "all";
                }

                if (stockFilter) {
                    stockFilter.value = "all";
                }

                applyFilters();

            }
        );

    }


/* =========================================================
 * RESTORE INVENTORY ITEM
 * ========================================================= */

const inventoryRestoreModal =
    document.getElementById("inventoryRestoreModal");

const inventoryRestoreBackdrop =
    document.getElementById("inventoryRestoreBackdrop");

const inventoryRestoreCancel =
    document.getElementById("inventoryRestoreCancel");

const inventoryRestoreConfirm =
    document.getElementById("inventoryRestoreConfirm");


let selectedRestoreItemId = null;


/* =========================================================
 * OPEN RESTORE MODAL
 * ========================================================= */

document
    .querySelectorAll(".archive-inventory-restore-btn")
    .forEach(function (button) {

        button.addEventListener(
            "click",
            function () {

                selectedRestoreItemId =
                    this.dataset.id;

                if (!selectedRestoreItemId) {
                    return;
                }

                if (inventoryRestoreModal) {

                    inventoryRestoreModal.classList.add(
                        "show"
                    );

                    inventoryRestoreModal.setAttribute(
                        "aria-hidden",
                        "false"
                    );
                }

            }
        );

    });


/* =========================================================
 * CLOSE RESTORE MODAL
 * ========================================================= */

function closeInventoryRestoreModal() {

    if (!inventoryRestoreModal) {
        return;
    }

    inventoryRestoreModal.classList.remove(
        "show"
    );

    inventoryRestoreModal.setAttribute(
        "aria-hidden",
        "true"
    );

    selectedRestoreItemId = null;
}


/* =========================================================
 * CANCEL
 * ========================================================= */

if (inventoryRestoreCancel) {

    inventoryRestoreCancel.addEventListener(
        "click",
        function () {

            closeInventoryRestoreModal();

        }
    );

}


/* =========================================================
 * CLICK BACKDROP
 * ========================================================= */

if (inventoryRestoreBackdrop) {

    inventoryRestoreBackdrop.addEventListener(
        "click",
        function () {

            closeInventoryRestoreModal();

        }
    );

}


/* =========================================================
 * CONFIRM RESTORE
 * ========================================================= */

if (inventoryRestoreConfirm) {

    inventoryRestoreConfirm.addEventListener(
        "click",
        async function () {

            if (!selectedRestoreItemId) {
                return;
            }


            const itemId =
                selectedRestoreItemId;


            /* ---------------------------------------------
             * DISABLE BUTTON
             * --------------------------------------------- */

            inventoryRestoreConfirm.disabled =
                true;

            inventoryRestoreConfirm.innerHTML =
                '<i class="fa-solid fa-spinner fa-spin"></i> Restoring...';


            /* ---------------------------------------------
             * PREPARE REQUEST
             * --------------------------------------------- */

            const formData =
                new FormData();

            formData.append(
                "action",
                "restore_inventory"
            );

            formData.append(
                "item_id",
                itemId
            );


            try {

                const response =
                    await fetch(
                        "inventory_archive.php",
                        {
                            method: "POST",
                            body: formData,

                            headers: {
                                "Accept":
                                    "application/json"
                            }
                        }
                    );


                const result =
                    await response.json();


                /* -----------------------------------------
                 * CHECK RESPONSE
                 * ----------------------------------------- */

                if (
                    !response.ok ||
                    !result.success
                ) {

                    throw new Error(
                        result.message ||
                        "Unable to restore inventory item."
                    );

                }


                /* -----------------------------------------
                 * SUCCESS
                 * ----------------------------------------- */

                closeInventoryRestoreModal();

                window.location.reload();


            } catch (error) {

                console.error(
                    "Inventory Restore Error:",
                    error
                );


                alert(
                    error.message ||
                    "Unable to restore inventory item."
                );


                inventoryRestoreConfirm.disabled =
                    false;

                inventoryRestoreConfirm.innerHTML =
                    '<i class="fa-solid fa-rotate-left"></i> Restore';

            }

        }
    );

}
});