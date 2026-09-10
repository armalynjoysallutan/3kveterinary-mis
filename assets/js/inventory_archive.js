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

/* =========================================================
   INVENTORY MULTIPLE SELECT + RESTORE
========================================================= */

document.addEventListener("DOMContentLoaded", function () {

    const selectAllInventory =
        document.getElementById("selectAllInventory");

    const restoreSelectedInventory =
        document.getElementById("restoreSelectedInventory");

    const selectedCount =
        document.getElementById("inventorySelectedCount");


    function getInventoryCheckboxes() {

        return Array.from(
            document.querySelectorAll(
                ".inventory-select-checkbox"
            )
        );

    }


    function getSelectedInventoryIds() {

        return getInventoryCheckboxes()

            .filter(function (checkbox) {

                return checkbox.checked;

            })

            .map(function (checkbox) {

                return checkbox.value;

            });

    }


    function updateInventorySelection() {

        const checkboxes =
            getInventoryCheckboxes();

        const selectedIds =
            getSelectedInventoryIds();


        /* SELECTED COUNT */

        if (selectedCount) {

            selectedCount.textContent =
                selectedIds.length +
                (
                    selectedIds.length === 1
                        ? " selected"
                        : " selected"
                );

        }


        /* RESTORE BUTTON */

        if (restoreSelectedInventory) {

            restoreSelectedInventory.disabled =
                selectedIds.length === 0;

        }


        /* SELECT ALL STATE */

        if (selectAllInventory) {

            const visibleCheckboxes =
                checkboxes.filter(function (checkbox) {

                    return checkbox
                        .closest("tr")
                        ?.style.display !== "none";

                });


            const checkedVisible =
                visibleCheckboxes.filter(function (checkbox) {

                    return checkbox.checked;

                });


            selectAllInventory.checked =
                visibleCheckboxes.length > 0 &&
                checkedVisible.length ===
                    visibleCheckboxes.length;


            selectAllInventory.indeterminate =
                checkedVisible.length > 0 &&
                checkedVisible.length <
                    visibleCheckboxes.length;

        }


        /* HIGHLIGHT SELECTED ROW */

        checkboxes.forEach(function (checkbox) {

            const row =
                checkbox.closest("tr");

            if (!row) return;


            row.classList.toggle(
                "inventory-row-selected",
                checkbox.checked
            );

        });

    }


    /* =====================================================
       SELECT ALL
    ===================================================== */

    if (selectAllInventory) {

        selectAllInventory.addEventListener(
            "change",
            function () {

                const checkboxes =
                    getInventoryCheckboxes();


                checkboxes.forEach(function (checkbox) {

                    const row =
                        checkbox.closest("tr");


                    if (
                        row &&
                        row.style.display !== "none"
                    ) {

                        checkbox.checked =
                            selectAllInventory.checked;

                    }

                });


                updateInventorySelection();

            }
        );

    }


    /* =====================================================
       INDIVIDUAL CHECKBOX
    ===================================================== */

    document.addEventListener(
        "change",
        function (e) {

            if (
                e.target.classList.contains(
                    "inventory-select-checkbox"
                )
            ) {

                updateInventorySelection();

            }

        }
    );


    /* =====================================================
       RESTORE SELECTED
    ===================================================== */

    if (restoreSelectedInventory) {

        restoreSelectedInventory.addEventListener(
            "click",
            function () {

                const selectedIds =
                    getSelectedInventoryIds();


                if (selectedIds.length === 0) {

                    return;

                }


                const itemWord =
                    selectedIds.length === 1
                        ? "inventory item"
                        : "inventory items";


                const confirmed =
                    confirm(
                        `Are you sure you want to restore ${selectedIds.length} ${itemWord}?`
                    );


                if (!confirmed) {

                    return;

                }


                restoreSelectedInventory.disabled =
                    true;


                restoreSelectedInventory.innerHTML =
                    '<i class="fa-solid fa-spinner fa-spin"></i> Restoring...';


                /* RESTORE ONE BY ONE */

                Promise.all(

                    selectedIds.map(function (itemId) {

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


                        return fetch(
                            "inventory_archive.php",
                            {
                                method: "POST",
                                body: formData
                            }
                        )
                        .then(function (response) {

                            return response.json();

                        });

                    })

                )

                .then(function (results) {

                    const failed =
                        results.filter(function (result) {

                            return !result.success;

                        });


                    if (failed.length > 0) {

                        alert(
                            "Some inventory items could not be restored."
                        );

                    } else {

                        alert(
                            selectedIds.length +
                            " inventory item" +
                            (
                                selectedIds.length === 1
                                    ? ""
                                    : "s"
                            ) +
                            " restored successfully."
                        );

                    }


                    /* REFRESH */

                    window.location.reload();

                })

                .catch(function (error) {

                    console.error(
                        "Bulk inventory restore error:",
                        error
                    );


                    alert(
                        "Unable to restore the selected inventory items."
                    );


                    restoreSelectedInventory.disabled =
                        false;


                    restoreSelectedInventory.innerHTML =
                        '<i class="fa-solid fa-rotate-left"></i> Restore Selected';

                });

            }
        );

    }


    /* INITIAL STATE */

    updateInventorySelection();

});