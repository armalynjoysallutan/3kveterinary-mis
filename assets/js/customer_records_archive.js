/* =========================================================
   CUSTOMER RECORDS ARCHIVE
   Restore single / multiple customer records
   ========================================================= */

(function () {
    "use strict";

    document.addEventListener("DOMContentLoaded", function () {
        const searchInput = document.getElementById("archiveSearch");
        const selectAllCustomers = document.getElementById("selectAllCustomers");
        const tableSelectAll = document.getElementById("tableSelectAll");
        const restoreSelectedBtn = document.getElementById("restoreSelectedBtn");
        const selectedCount = document.getElementById("selectedCount");
        const visibleArchiveCount = document.getElementById("visibleArchiveCount");

        const modal = document.getElementById("restoreModal");
        const modalBackdrop = document.getElementById("restoreModalBackdrop");
        const modalTitle = document.getElementById("restoreModalTitle");
        const modalMessage = document.getElementById("restoreModalMessage");
        const cancelBtn = document.getElementById("restoreCancelBtn");
        const confirmBtn = document.getElementById("restoreConfirmBtn");

        const rows = Array.from(
            document.querySelectorAll(".archive-customer-row")
        );

        let pendingCustomerIds = [];

        function getRowCheckboxes(visibleOnly = false) {
            return rows
                .filter(function (row) {
                    return !visibleOnly || row.style.display !== "none";
                })
                .map(function (row) {
                    return row.querySelector(".customer-select");
                })
                .filter(Boolean);
        }

        function getSelectedCheckboxes() {
            return Array.from(
                document.querySelectorAll(".customer-select:checked")
            );
        }

        function updateSelectionUI() {
            const selected = getSelectedCheckboxes();
            const visibleCheckboxes = getRowCheckboxes(true);
            const visibleSelected = visibleCheckboxes.filter(function (cb) {
                return cb.checked;
            });

            const count = selected.length;

            if (selectedCount) {
                selectedCount.textContent = String(count);
            }

            if (restoreSelectedBtn) {
                restoreSelectedBtn.disabled = count === 0;
            }

            const allVisibleSelected =
                visibleCheckboxes.length > 0 &&
                visibleSelected.length === visibleCheckboxes.length;

            if (selectAllCustomers) {
                selectAllCustomers.checked = allVisibleSelected;
                selectAllCustomers.indeterminate =
                    visibleSelected.length > 0 && !allVisibleSelected;
            }

            if (tableSelectAll) {
                tableSelectAll.checked = allVisibleSelected;
                tableSelectAll.indeterminate =
                    visibleSelected.length > 0 && !allVisibleSelected;
            }
        }

        function filterRows() {
            const query = (searchInput?.value || "").trim().toLowerCase();
            let visibleCount = 0;

            rows.forEach(function (row) {
                const searchable = (
                    row.getAttribute("data-search") || ""
                ).toLowerCase();

                const show = !query || searchable.includes(query);
                row.style.display = show ? "" : "none";

                if (show) {
                    visibleCount++;
                }
            });

            if (visibleArchiveCount) {
                visibleArchiveCount.textContent = String(visibleCount);
            }

            updateSelectionUI();
        }

        function openRestoreModal(customerIds, customerName = "") {
            if (!modal) return;

            pendingCustomerIds = customerIds.map(Number).filter(function (id) {
                return id > 0;
            });

            if (!pendingCustomerIds.length) return;

            const multiple = pendingCustomerIds.length > 1;

            if (modalTitle) {
                modalTitle.textContent = multiple
                    ? "Restore Customers?"
                    : "Restore Customer?";
            }

            if (modalMessage) {
                modalMessage.textContent = multiple
                    ? "The selected " + pendingCustomerIds.length +
                      " customer records and all associated pets will be restored to Customer Records."
                    : "“" + customerName +
                      "” and all associated pets will be restored to Customer Records.";
            }

            modal.classList.add("open");
            modal.setAttribute("aria-hidden", "false");
            document.body.classList.add("modal-open");
        }

        function closeRestoreModal() {
            if (!modal) return;

            modal.classList.remove("open");
            modal.setAttribute("aria-hidden", "true");
            pendingCustomerIds = [];
            document.body.classList.remove("modal-open");
        }

        async function restoreCustomers(customerIds) {
            if (!customerIds.length) return;

            if (confirmBtn) {
                confirmBtn.disabled = true;
                confirmBtn.innerHTML =
                    '<i class="fa-solid fa-spinner fa-spin"></i> Restoring...';
            }

            const formData = new FormData();
            formData.append("action", "restore_customer");

            customerIds.forEach(function (id) {
                formData.append("customer_ids[]", String(id));
            });

            try {
                const response = await fetch(window.location.href, {
                    method: "POST",
                    body: formData,
                    cache: "no-store"
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    throw new Error(
                        data.message || "Unable to restore the selected record(s)."
                    );
                }

                closeRestoreModal();
                window.location.reload();

            } catch (error) {
                console.error("Customer Archive Restore Error:", error);

                if (modalMessage) {
                    modalMessage.textContent =
                        error.message ||
                        "Unable to restore the selected record(s). Please try again.";
                }

                if (confirmBtn) {
                    confirmBtn.disabled = false;
                    confirmBtn.innerHTML =
                        '<i class="fa-solid fa-rotate-left"></i> Restore';
                }
            }
        }

        function setVisibleSelection(checked) {
            getRowCheckboxes(true).forEach(function (checkbox) {
                checkbox.checked = checked;
            });

            updateSelectionUI();
        }

        if (searchInput) {
            searchInput.addEventListener("input", filterRows);
        }

        if (selectAllCustomers) {
            selectAllCustomers.addEventListener("change", function () {
                setVisibleSelection(selectAllCustomers.checked);
            });
        }

        if (tableSelectAll) {
            tableSelectAll.addEventListener("change", function () {
                setVisibleSelection(tableSelectAll.checked);
            });
        }

        document.addEventListener("change", function (event) {
            if (event.target.classList.contains("customer-select")) {
                updateSelectionUI();
            }
        });

        document.addEventListener("click", function (event) {
            const restoreButton = event.target.closest(".restore-customer-btn");

            if (restoreButton) {
                event.preventDefault();

                const customerId = Number(
                    restoreButton.getAttribute("data-customer-id")
                );

                const customerName =
                    restoreButton.getAttribute("data-customer-name") ||
                    "this customer";

                openRestoreModal([customerId], customerName);
            }
        });

                /* =========================================================
           VIEW ARCHIVED CUSTOMER PETS
           ========================================================= */

        const archivePetsModal =
            document.getElementById("archivePetsModal");

        const archivePetsModalBackdrop =
            document.getElementById("archivePetsModalBackdrop");

        const archivePetsCloseBtn =
            document.getElementById("archivePetsCloseBtn");

        const archivePetsModalTitle =
            document.getElementById("archivePetsModalTitle");

        const archivePetsList =
            document.getElementById("archivePetsList");

        const archivedCustomerPets =
            window.archivedCustomerPets || {};


        function escapeHtml(value) {
            return String(value)
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }


        function closeArchivedPetsModal() {

            if (!archivePetsModal) return;

            archivePetsModal.classList.remove("open");

            archivePetsModal.setAttribute(
                "aria-hidden",
                "true"
            );

            document.body.classList.remove("modal-open");
        }


        function openArchivedPetsModal(
            customerId,
            customerName
        ) {

            if (
                !archivePetsModal ||
                !archivePetsList
            ) {
                return;
            }

            const pets =
                archivedCustomerPets[String(customerId)] ||
                archivedCustomerPets[customerId] ||
                [];


            if (archivePetsModalTitle) {

                archivePetsModalTitle.textContent =
                    customerName
                        ? customerName + " — Pets"
                        : "Associated Pets";
            }


            if (!pets.length) {

                archivePetsList.innerHTML = `
                    <div class="archive-pets-empty">

                        <i class="fa-solid fa-paw"></i>

                        <h4>
                            No associated pets
                        </h4>

                        <p>
                            This archived customer has no
                            associated pet records.
                        </p>

                    </div>
                `;

            } else {

                archivePetsList.innerHTML =
                    pets.map(function (pet) {

                        const petId =
                            Number(pet.pet_id);

                        const petName =
                            escapeHtml(
                                pet.pet_name ||
                                "Unnamed Pet"
                            );

                        const species =
                            escapeHtml(
                                pet.species || "—"
                            );

                        const breed =
                            escapeHtml(
                                pet.breed || "—"
                            );

                        const gender =
                            escapeHtml(
                                pet.gender || "—"
                            );


                        return `
                            <div class="archive-pet-card">

                                <div class="archive-pet-icon">
                                    <i class="fa-solid fa-paw"></i>
                                </div>


                                <div class="archive-pet-info">

                                    <strong>
                                        ${petName}
                                    </strong>

                                    <span>
                                        ${species} • ${breed}
                                    </span>

                                    <small>
                                        Gender: ${gender}
                                    </small>

                                </div>


                                <a
                                    class="archive-view-medical-btn"
                                    href="view_pet_record.php?pet_id=${petId}&archived=1"
                                >
                                    <i class="fa-solid fa-file-medical"></i>

                                    View Medical Records
                                </a>

                            </div>
                        `;

                    }).join("");
            }


            archivePetsModal.classList.add("open");

            archivePetsModal.setAttribute(
                "aria-hidden",
                "false"
            );

            document.body.classList.add("modal-open");
        }


        document.addEventListener("click", function (event) {

    const viewButton = event.target.closest(
        ".view-archived-pets-btn"
    );

    if (!viewButton) {
        return;
    }

    event.preventDefault();
    event.stopPropagation();

    const customerId = Number(
        viewButton.getAttribute("data-customer-id")
    );

    if (!customerId) {
        console.error(
            "View Pets Error: Missing customer ID."
        );
        return;
    }

    const row = viewButton.closest(
        ".archive-customer-row"
    );

    const customerName =
        row?.querySelector(
            ".archive-customer-info strong"
        )?.textContent.trim() || "Archived Customer";

    openArchivedPetsModal(
        customerId,
        customerName
    );
});

        if (archivePetsCloseBtn) {

            archivePetsCloseBtn.addEventListener(
                "click",
                closeArchivedPetsModal
            );
        }


        if (archivePetsModalBackdrop) {

            archivePetsModalBackdrop.addEventListener(
                "click",
                closeArchivedPetsModal
            );
        }


        document.addEventListener(
            "keydown",
            function (event) {

                if (
                    event.key === "Escape" &&
                    archivePetsModal?.classList.contains("open")
                ) {

                    closeArchivedPetsModal();
                }
            }
        );

        if (restoreSelectedBtn) {
            restoreSelectedBtn.addEventListener("click", function () {
                const ids = getSelectedCheckboxes().map(function (checkbox) {
                    return Number(checkbox.value);
                });

                if (!ids.length) return;

                openRestoreModal(ids);
            });
        }

        if (cancelBtn) {
            cancelBtn.addEventListener("click", closeRestoreModal);
        }

        if (modalBackdrop) {
            modalBackdrop.addEventListener("click", closeRestoreModal);
        }

        if (confirmBtn) {
            confirmBtn.addEventListener("click", function () {
                if (!pendingCustomerIds.length) return;
                restoreCustomers(pendingCustomerIds.slice());
            });
        }

        document.addEventListener("keydown", function (event) {
            if (event.key === "Escape" && modal?.classList.contains("open")) {
                closeRestoreModal();
            }
        });

        filterRows();
        updateSelectionUI();
    });
})();
