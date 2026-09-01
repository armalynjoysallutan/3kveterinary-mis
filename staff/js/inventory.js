document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("inventoryModal");
    const addBtn = document.getElementById("addInventoryItemBtn");
    const emptyAddBtn = document.getElementById("emptyAddInventoryBtn");
    const closeBtn = document.getElementById("closeInventoryModal");
    const cancelBtn = document.getElementById("cancelInventoryModal");
    const form = document.getElementById("inventoryItemForm");
    const searchInput = document.getElementById("inventorySearch");
    const categoryTabs = document.querySelectorAll(".inventory-category-tab");
    let activeCategory = "";
    const tableBody = document.getElementById("inventoryTableBody");
    const stockHistoryBtn = document.getElementById("stockHistoryBtn");

    function openModal() {
        if (!modal) return;
        modal.classList.add("show");
        document.body.classList.add("inventory-modal-open");

        const firstInput = document.getElementById("itemCode");
        firstInput?.focus();
    }

    function closeModal() {
        if (!modal) return;
        modal.classList.remove("show");
        document.body.classList.remove("inventory-modal-open");
    }

    addBtn?.addEventListener("click", openModal);
    emptyAddBtn?.addEventListener("click", openModal);
    closeBtn?.addEventListener("click", closeModal);
    cancelBtn?.addEventListener("click", closeModal);

    modal?.addEventListener("click", function (event) {
        if (event.target === modal) {
            closeModal();
        }
    });

    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape") {
            if (document.getElementById("stockOutModal")?.classList.contains("show")) {
                closeStockOutModal();
                return;
            }
            if (document.getElementById("stockInModal")?.classList.contains("show")) {
                closeStockInModal();
                return;
            }
            if (modal?.classList.contains("show")) {
                closeModal();
            }
        }
    });

    function filterRows() {
        const query = (searchInput?.value || "").trim().toLowerCase();
        document.querySelectorAll(".inventory-row").forEach(function (row) {
            const searchText = row.dataset.search || "";
            const rowCategory = row.dataset.category || "";
            row.style.display = (!query || searchText.includes(query)) && (!activeCategory || rowCategory === activeCategory) ? "" : "none";
        });
    }

    searchInput?.addEventListener("input", filterRows);

    categoryTabs.forEach(function (tab) {
        tab.addEventListener("click", function () {
            activeCategory = tab.dataset.category || "";
            categoryTabs.forEach(function (otherTab) {
                const active = otherTab === tab;
                otherTab.classList.toggle("active", active);
                otherTab.setAttribute("aria-selected", active ? "true" : "false");
            });
            filterRows();
        });
    });

    document.addEventListener("click", function (event) {
        const actionButton = event.target.closest(".inventory-more-btn");

        document.querySelectorAll(".inventory-action-menu.show").forEach(function (menu) {
            if (!actionButton || !actionButton.parentElement.contains(menu)) {
                menu.classList.remove("show");
                menu.previousElementSibling?.setAttribute("aria-expanded", "false");
            }
        });

        if (!actionButton) return;

        event.preventDefault();
        event.stopPropagation();

        const menu = actionButton.nextElementSibling;
        if (!menu) return;

        const willOpen = !menu.classList.contains("show");
        menu.classList.toggle("show", willOpen);
        actionButton.setAttribute("aria-expanded", willOpen ? "true" : "false");
    });

    /* =========================================================
       STOCK IN MODAL
       ========================================================= */
    let stockInModal = null;

    function createStockInModal() {
        if (document.getElementById("stockInModal")) {
            stockInModal = document.getElementById("stockInModal");
            return stockInModal;
        }

        const modal = document.createElement("div");
        modal.className = "inventory-modal-overlay";
        modal.id = "stockInModal";
        modal.innerHTML = `
            <div class="inventory-modal" role="dialog" aria-modal="true" aria-labelledby="stockInModalTitle">
                <div class="inventory-modal-header">
                    <div>
                        <h2 id="stockInModalTitle">Stock In</h2>
                        <p>Record a new stock delivery for this inventory item.</p>
                    </div>
                    <button type="button" class="modal-close-btn" id="closeStockInModal" aria-label="Close">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <form id="stockInForm" autocomplete="off">
                    <input type="hidden" id="stockInItemId" name="item_id">

                    <div class="inventory-form-note" style="margin-bottom:18px;">
                        <i class="fa-solid fa-box"></i>
                        <div>
                            <strong id="stockInItemName">Selected item</strong><br>
                            <span id="stockInItemMeta">Item ID</span>
                        </div>
                    </div>

                    <div class="inventory-form-grid">
                        <div class="inventory-form-group">
                            <label for="stockInBatchNumber">Batch Number <span>*</span></label>
                            <input type="text" id="stockInBatchNumber" name="batch_number" maxlength="100" placeholder="e.g. BAT-2026-001" required>
                        </div>

                        <div class="inventory-form-group">
                            <label for="stockInQuantity">Quantity <span>*</span></label>
                            <input type="number" id="stockInQuantity" name="quantity" min="0.01" step="0.01" placeholder="0" required>
                        </div>

                        <div class="inventory-form-group">
                            <label for="stockInExpiration">Expiration Date</label>
                            <input type="date" id="stockInExpiration" name="expiration_date">
                        </div>

                        <div class="inventory-form-group">
                            <label for="stockInDateReceived">Date Received <span>*</span></label>
                            <input type="date" id="stockInDateReceived" name="date_received" required>
                        </div>

                        <div class="inventory-form-group">
                            <label for="stockInReference">Reference Number</label>
                            <input type="text" id="stockInReference" name="reference_number" maxlength="100" placeholder="e.g. DR-2026-001">
                        </div>

                        <div class="inventory-form-group">
                            <label for="stockInRemarks">Remarks</label>
                            <input type="text" id="stockInRemarks" name="remarks" maxlength="255" placeholder="Optional remarks">
                        </div>
                    </div>

                    <div class="inventory-form-note">
                        <i class="fa-solid fa-circle-info"></i>
                        <span>Stock quantity will be added to the selected batch. A separate Stock In transaction will also be recorded.</span>
                    </div>

                    <div class="inventory-modal-footer">
                        <button type="button" class="modal-cancel-btn" id="cancelStockInModal">Cancel</button>
                        <button type="submit" class="modal-save-btn" id="saveStockInBtn">
                            <i class="fa-solid fa-arrow-down"></i>
                            Save Stock In
                        </button>
                    </div>
                </form>
            </div>
        `;

        document.body.appendChild(modal);
        stockInModal = modal;

        const close = () => closeStockInModal();
        modal.querySelector("#closeStockInModal")?.addEventListener("click", close);
        modal.querySelector("#cancelStockInModal")?.addEventListener("click", close);
        modal.addEventListener("click", function (event) {
            if (event.target === modal) close();
        });
        modal.querySelector("#stockInForm")?.addEventListener("submit", submitStockIn);

        return modal;
    }

    function openStockInModal(itemId, actionButton) {
        const modal = createStockInModal();
        const row = actionButton?.closest("tr");
        const cells = row?.querySelectorAll("td") || [];

        const itemName = cells[1]?.textContent.trim() || "Inventory Item";
        const itemCode = cells[0]?.textContent.trim() || `Item #${itemId}`;
        const unit = cells[3]?.textContent.trim() || "";

        modal.querySelector("#stockInForm").reset();
        modal.querySelector("#stockInItemId").value = itemId;
        modal.querySelector("#stockInItemName").textContent = itemName;
        modal.querySelector("#stockInItemMeta").textContent = `${itemCode}${unit ? ` • ${unit}` : ""}`;

        const today = new Date();
        const localDate = new Date(today.getTime() - today.getTimezoneOffset() * 60000)
            .toISOString().slice(0, 10);
        modal.querySelector("#stockInDateReceived").value = localDate;

        modal.classList.add("show");
        document.body.classList.add("inventory-modal-open");
        modal.querySelector("#stockInBatchNumber")?.focus();
    }

    function closeStockInModal() {
        const modal = document.getElementById("stockInModal");
        if (!modal) return;
        modal.classList.remove("show");
        document.body.classList.remove("inventory-modal-open");
    }

    async function submitStockIn(event) {
        event.preventDefault();

        const form = event.currentTarget;
        const saveButton = document.getElementById("saveStockInBtn");
        if (!saveButton) return;

        const formData = new FormData(form);
        const quantity = parseFloat(formData.get("quantity") || "0");

        if (quantity <= 0) {
            alert("Quantity must be greater than 0.");
            return;
        }

        const expiration = formData.get("expiration_date");
        const received = formData.get("date_received");
        if (expiration && received && expiration < received) {
            alert("Expiration date cannot be earlier than the date received.");
            return;
        }

        saveButton.disabled = true;
        saveButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';

        try {
            const response = await fetch("save_inventory_stock_in.php", {
                method: "POST",
                body: formData
            });

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.message || "Unable to record Stock In.");
            }

            alert(data.message || "Stock In recorded successfully.");
            closeStockInModal();
            window.location.reload();
        } catch (error) {
            alert(error.message || "Something went wrong while recording Stock In.");
            saveButton.disabled = false;
            saveButton.innerHTML = '<i class="fa-solid fa-arrow-down"></i> Save Stock In';
        }
    }

    /* =========================================================
       STOCK OUT MODAL
       ========================================================= */
    let stockOutModal = null;

    function createStockOutModal() {
        if (document.getElementById("stockOutModal")) {
            stockOutModal = document.getElementById("stockOutModal");
            return stockOutModal;
        }

        const modal = document.createElement("div");
        modal.className = "inventory-modal-overlay";
        modal.id = "stockOutModal";
        modal.innerHTML = `
            <div class="inventory-modal" role="dialog" aria-modal="true" aria-labelledby="stockOutModalTitle">
                <div class="inventory-modal-header">
                    <div>
                        <h2 id="stockOutModalTitle">Stock Out</h2>
                        <p>Record stock released, used, damaged or removed from inventory.</p>
                    </div>
                    <button type="button" class="modal-close-btn" id="closeStockOutModal" aria-label="Close">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <form id="stockOutForm" autocomplete="off">
                    <input type="hidden" id="stockOutItemId" name="item_id">

                    <div class="inventory-form-note" style="margin-bottom:18px;">
                        <i class="fa-solid fa-box-open"></i>
                        <div>
                            <strong id="stockOutItemName">Selected item</strong><br>
                            <span id="stockOutItemMeta">Item ID</span>
                        </div>
                    </div>

                    <div class="inventory-form-grid">
                        <div class="inventory-form-group">
                            <label for="stockOutBatch">Batch Number <span>*</span></label>
                            <select id="stockOutBatch" name="stock_id" required>
                                <option value="">Loading available batches...</option>
                            </select>
                            <small id="stockOutBatchHint" style="display:block;margin-top:6px;color:#718096;"></small>
                        </div>

                        <div class="inventory-form-group">
                            <label for="stockOutQuantity">Quantity <span>*</span></label>
                            <input type="number" id="stockOutQuantity" name="quantity" min="0.01" step="0.01" placeholder="0" required disabled>
                            <small id="stockOutAvailable" style="display:block;margin-top:6px;color:#718096;">Available: —</small>
                        </div>

                        <div class="inventory-form-group">
                            <label for="stockOutDate">Stock Out Date <span>*</span></label>
                            <input type="date" id="stockOutDate" name="stock_out_date" required>
                        </div>

                        <div class="inventory-form-group">
                            <label for="stockOutReason">Reason <span>*</span></label>
                            <select id="stockOutReason" name="reason" required>
                                <option value="">Select reason</option>
                                <option value="Used for Treatment/Procedure">Used for Treatment/Procedure</option>
                                <option value="Dispensed/Sold">Dispensed/Sold</option>
                                <option value="Expired">Expired</option>
                                <option value="Damaged">Damaged</option>
                                <option value="Lost/Missing">Lost/Missing</option>
                                <option value="Returned to Supplier">Returned to Supplier</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <div class="inventory-form-group">
                            <label for="stockOutReference">Reference Number</label>
                            <input type="text" id="stockOutReference" name="reference_number" maxlength="100" placeholder="e.g. USE-2026-001">
                        </div>

                        <div class="inventory-form-group">
                            <label for="stockOutRemarks">Remarks</label>
                            <input type="text" id="stockOutRemarks" name="remarks" maxlength="255" placeholder="Optional remarks">
                        </div>
                    </div>

                    <div class="inventory-form-note">
                        <i class="fa-solid fa-circle-info"></i>
                        <span>The selected batch quantity will be reduced and a separate Stock Out transaction will be recorded.</span>
                    </div>

                    <div class="inventory-modal-footer">
                        <button type="button" class="modal-cancel-btn" id="cancelStockOutModal">Cancel</button>
                        <button type="submit" class="modal-save-btn" id="saveStockOutBtn" disabled>
                            <i class="fa-solid fa-arrow-up"></i>
                            Save Stock Out
                        </button>
                    </div>
                </form>
            </div>
        `;

        document.body.appendChild(modal);
        stockOutModal = modal;

        modal.querySelector("#closeStockOutModal")?.addEventListener("click", closeStockOutModal);
        modal.querySelector("#cancelStockOutModal")?.addEventListener("click", closeStockOutModal);
        modal.addEventListener("click", function (event) {
            if (event.target === modal) closeStockOutModal();
        });

        modal.querySelector("#stockOutBatch")?.addEventListener("change", updateStockOutBatchInfo);
        modal.querySelector("#stockOutForm")?.addEventListener("submit", submitStockOut);

        return modal;
    }

    function getLocalDate() {
        const today = new Date();
        return new Date(today.getTime() - today.getTimezoneOffset() * 60000)
            .toISOString().slice(0, 10);
    }

    async function openStockOutModal(itemId, actionButton) {
        const modal = createStockOutModal();
        const row = actionButton?.closest("tr");
        const cells = row?.querySelectorAll("td") || [];

        const itemName = cells[1]?.textContent.trim() || "Inventory Item";
        const itemCode = cells[0]?.textContent.trim() || `Item #${itemId}`;
        const unit = cells[3]?.textContent.trim() || "";

        const form = modal.querySelector("#stockOutForm");
        const batchSelect = modal.querySelector("#stockOutBatch");
        const quantityInput = modal.querySelector("#stockOutQuantity");
        const saveButton = modal.querySelector("#saveStockOutBtn");

        form.reset();
        modal.querySelector("#stockOutItemId").value = itemId;
        modal.querySelector("#stockOutItemName").textContent = itemName;
        modal.querySelector("#stockOutItemMeta").textContent = `${itemCode}${unit ? ` • ${unit}` : ""}`;
        modal.querySelector("#stockOutDate").value = getLocalDate();

        batchSelect.disabled = true;
        quantityInput.disabled = true;
        quantityInput.value = "";
        quantityInput.removeAttribute("max");
        saveButton.disabled = true;
        modal.querySelector("#stockOutAvailable").textContent = "Available: —";
        modal.querySelector("#stockOutBatchHint").textContent = "";
        batchSelect.innerHTML = '<option value="">Loading available batches...</option>';

        modal.classList.add("show");
        document.body.classList.add("inventory-modal-open");

        try {
            const response = await fetch(`get_inventory_stock_batches.php?item_id=${encodeURIComponent(itemId)}`, {
                headers: { "Accept": "application/json" }
            });
            const data = await response.json();

            if (!data.success) {
                throw new Error(data.message || "Unable to load available batches.");
            }

            if (!data.batches.length) {
                batchSelect.innerHTML = '<option value="">No available stock</option>';
                modal.querySelector("#stockOutBatchHint").textContent = "This item has no batch with available quantity.";
                return;
            }

            batchSelect.innerHTML = '<option value="">Select batch</option>';
            data.batches.forEach(function (batch) {
                const option = document.createElement("option");
                option.value = batch.stock_id;
                option.dataset.quantity = batch.quantity;
                option.dataset.expiration = batch.expiration_date || "";
                option.textContent = `${batch.batch_number} — ${formatQuantity(batch.quantity)} available${batch.expiration_date ? ` • Exp: ${formatDate(batch.expiration_date)}` : ""}`;
                batchSelect.appendChild(option);
            });

            batchSelect.disabled = false;
            batchSelect.focus();
        } catch (error) {
            batchSelect.innerHTML = '<option value="">Unable to load batches</option>';
            modal.querySelector("#stockOutBatchHint").textContent = error.message || "Unable to load available batches.";
        }
    }

    function formatQuantity(value) {
        const number = Number(value || 0);
        return Number.isInteger(number) ? number.toString() : number.toFixed(2).replace(/0+$/, '').replace(/\.$/, '');
    }

    function formatDate(value) {
        if (!value) return "—";
        const parts = value.split("-");
        if (parts.length !== 3) return value;
        return new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]))
            .toLocaleDateString(undefined, { month: "short", day: "2-digit", year: "numeric" });
    }

    function updateStockOutBatchInfo() {
        const modal = document.getElementById("stockOutModal");
        if (!modal) return;

        const select = modal.querySelector("#stockOutBatch");
        const option = select.options[select.selectedIndex];
        const quantityInput = modal.querySelector("#stockOutQuantity");
        const availableText = modal.querySelector("#stockOutAvailable");
        const hint = modal.querySelector("#stockOutBatchHint");
        const saveButton = modal.querySelector("#saveStockOutBtn");
        const stockId = option?.value || "";
        const available = Number(option?.dataset.quantity || 0);
        const expiration = option?.dataset.expiration || "";


        if (!stockId) {
            quantityInput.value = "";
            quantityInput.disabled = true;
            quantityInput.removeAttribute("max");
            availableText.textContent = "Available: —";
            hint.textContent = "";
            saveButton.disabled = true;
            return;
        }

        quantityInput.disabled = false;
        quantityInput.max = available;
        quantityInput.value = "";
        availableText.textContent = `Available: ${formatQuantity(available)}`;
        hint.textContent = expiration ? `Expiration: ${formatDate(expiration)}` : "No expiration date recorded for this batch.";
        saveButton.disabled = false;
        quantityInput.focus();
    }

    function closeStockOutModal() {
        const modal = document.getElementById("stockOutModal");
        if (!modal) return;
        modal.classList.remove("show");
        document.body.classList.remove("inventory-modal-open");
    }

    async function submitStockOut(event) {
        event.preventDefault();

        const form = event.currentTarget;
        const saveButton = document.getElementById("saveStockOutBtn");
        const quantityInput = document.getElementById("stockOutQuantity");
        const batchSelect = document.getElementById("stockOutBatch");
        const quantity = parseFloat(quantityInput?.value || "0");
        const available = parseFloat(batchSelect?.options[batchSelect.selectedIndex]?.dataset.quantity || "0");

        if (!batchSelect?.value) {
            alert("Please select a batch.");
            return;
        }

        if (quantity <= 0) {
            alert("Quantity must be greater than 0.");
            quantityInput?.focus();
            return;
        }

        if (quantity > available) {
            alert(`Stock Out quantity cannot exceed the available quantity of ${formatQuantity(available)}.`);
            quantityInput?.focus();
            return;
        }

        saveButton.disabled = true;
        saveButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';

        try {
            const formData = new FormData(form);
            const response = await fetch("save_inventory_stock_out.php", {
                method: "POST",
                body: formData,
                headers: { "Accept": "application/json" }
            });

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.message || "Unable to record Stock Out.");
            }

            alert(data.message || "Stock Out recorded successfully.");
            closeStockOutModal();
            window.location.reload();
        } catch (error) {
            alert(error.message || "Something went wrong while recording Stock Out.");
            saveButton.disabled = false;
            saveButton.innerHTML = '<i class="fa-solid fa-arrow-up"></i> Save Stock Out';
        }
    }

    tableBody?.addEventListener("click", function (event) {
        const action = event.target.closest(".inventory-action-menu button");
        if (!action) return;

        const actionType = action.dataset.action;
        const itemId = action.dataset.id;

        document.querySelectorAll(".inventory-action-menu.show").forEach(function (menu) {
            menu.classList.remove("show");
        });

        if (actionType === "stock-in") {
            openStockInModal(itemId, action);
            return;
        }

        if (actionType === "stock-out") {
            openStockOutModal(itemId, action);
            return;
        }

        if (actionType === "view") {
            openViewItemModal(itemId);
            return;
        }

        if (actionType === "edit") {
            openEditItemModal(itemId);
            return;
        }

        console.log("Inventory action:", actionType, "Item:", itemId);
    });

    stockHistoryBtn?.addEventListener("click", function () {
    window.location.href = "stock_history.php";
    });

    document.getElementById("archiveInventoryBtn")?.addEventListener("click", function () {
        window.location.href = "inventory_archive.php";
    });

    form?.addEventListener("submit", async function (event) {
        event.preventDefault();

        const saveButton = document.getElementById("saveInventoryItemBtn");
        const formData = new FormData(form);

        saveButton.disabled = true;
        saveButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';

        try {
            const response = await fetch("save_inventory_item.php", {
                method: "POST",
                body: formData
            });

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.message || "Unable to save inventory item.");
            }

            alert(data.message || "Inventory item saved successfully.");
            window.location.reload();

        } catch (error) {
            alert(error.message || "Something went wrong while saving the item.");
            saveButton.disabled = false;
            saveButton.innerHTML = '<i class="fa-solid fa-check"></i> Save Item';
        }
    });

    /* =========================================================
   INVENTORY MULTI-SELECT
   ========================================================= */

const selectAllInventory =
    document.getElementById("selectAllInventory");

const tableSelectAllInventory =
    document.getElementById("tableSelectAllInventory");

const inventoryCheckboxes =
    document.querySelectorAll(".inventory-select");

const archiveSelectedInventoryBtn =
    document.getElementById("archiveSelectedInventoryBtn");

const selectedInventoryCount =
    document.getElementById("selectedInventoryCount");


function updateInventorySelection() {

    const checkboxes =
        document.querySelectorAll(".inventory-select");

    const selected =
        document.querySelectorAll(
            ".inventory-select:checked"
        );

    const total =
        checkboxes.length;

    const selectedCount =
        selected.length;


    /* Update selected count */

    if (selectedInventoryCount) {

        selectedInventoryCount.textContent =
            selectedCount;

    }


    /* Enable / disable archive button */

    if (archiveSelectedInventoryBtn) {

        archiveSelectedInventoryBtn.disabled =
            selectedCount === 0;

    }


    /* Update Select All states */

    const allSelected =
        total > 0 &&
        selectedCount === total;


    const partiallySelected =
        selectedCount > 0 &&
        selectedCount < total;


    if (selectAllInventory) {

        selectAllInventory.checked =
            allSelected;

        selectAllInventory.indeterminate =
            partiallySelected;

    }


    if (tableSelectAllInventory) {

        tableSelectAllInventory.checked =
            allSelected;

        tableSelectAllInventory.indeterminate =
            partiallySelected;

    }

}

/* =========================================================
   INVENTORY ARCHIVE MODAL
   ========================================================= */



const inventoryArchiveModal =
    document.getElementById("inventoryArchiveModal");

const inventoryArchiveBackdrop =
    document.getElementById("inventoryArchiveBackdrop");

const inventoryArchiveClose =
    document.getElementById("inventoryArchiveClose");

const inventoryArchiveCancel =
    document.getElementById("inventoryArchiveCancel");

const inventoryArchiveSelectedCount =
    document.getElementById("inventoryArchiveSelectedCount");


/* =========================================================
   OPEN ARCHIVE MODAL
   ========================================================= */

if (archiveSelectedInventoryBtn) {

    archiveSelectedInventoryBtn.addEventListener(
        "click",
        function () {

            const selectedInventory =
                document.querySelectorAll(
                    ".inventory-select:checked"
                );

            const selectedCount =
                selectedInventory.length;


            /* Safety check */

            if (selectedCount === 0) {
                return;
            }


            /* Update selected count */

            if (inventoryArchiveSelectedCount) {

                inventoryArchiveSelectedCount.textContent =
                    selectedCount +
                    " inventory item" +
                    (selectedCount === 1 ? "" : "s") +
                    " selected";

            }


            /* Open modal */

            if (inventoryArchiveModal) {

                inventoryArchiveModal.classList.add("open");

                inventoryArchiveModal.setAttribute(
                    "aria-hidden",
                    "false"
                );

            }

        }
    );

}


/* =========================================================
   CLOSE ARCHIVE MODAL
   ========================================================= */

function closeInventoryArchiveModal() {

    if (!inventoryArchiveModal) {
        return;
    }

    inventoryArchiveModal.classList.remove("open");

    inventoryArchiveModal.setAttribute(
        "aria-hidden",
        "true"
    );

}


/* Close button */

if (inventoryArchiveClose) {

    inventoryArchiveClose.addEventListener(
        "click",
        closeInventoryArchiveModal
    );

}


/* Cancel button */

if (inventoryArchiveCancel) {

    inventoryArchiveCancel.addEventListener(
        "click",
        closeInventoryArchiveModal
    );

}


/* Click outside modal */

if (inventoryArchiveBackdrop) {

    inventoryArchiveBackdrop.addEventListener(
        "click",
        closeInventoryArchiveModal
    );

}

/* =========================================================
   CONFIRM INVENTORY ARCHIVE
   ========================================================= */

const inventoryArchiveConfirm =
    document.getElementById("inventoryArchiveConfirm");

const inventoryArchiveReason =
    document.getElementById("inventoryArchiveReason");

const inventoryArchiveError =
    document.getElementById("inventoryArchiveError");


if (inventoryArchiveConfirm) {

    inventoryArchiveConfirm.addEventListener(
        "click",
        async function () {

            /* ---------------------------------------------
               VALIDATE REASON
               --------------------------------------------- */

            const reason =
                inventoryArchiveReason
                    ? inventoryArchiveReason.value.trim()
                    : "";

            if (reason === "") {

                if (inventoryArchiveError) {

                    inventoryArchiveError.textContent =
                        "Please select a reason for archiving the selected inventory items.";

                    inventoryArchiveError.style.display =
                        "block";
                }

                if (inventoryArchiveReason) {
                    inventoryArchiveReason.focus();
                }

                return;
            }


            /* ---------------------------------------------
               GET SELECTED INVENTORY ITEMS
               --------------------------------------------- */

            const selectedInventory =
                document.querySelectorAll(
                    ".inventory-select:checked"
                );


            const itemIds =
                Array.from(selectedInventory).map(
                    function (checkbox) {
                        return checkbox.value;
                    }
                );


            if (itemIds.length === 0) {

                if (inventoryArchiveError) {

                    inventoryArchiveError.textContent =
                        "No inventory items were selected.";

                    inventoryArchiveError.style.display =
                        "block";
                }

                return;
            }


            /* ---------------------------------------------
               DISABLE BUTTON WHILE PROCESSING
               --------------------------------------------- */

            inventoryArchiveConfirm.disabled = true;

            inventoryArchiveConfirm.innerHTML =
                '<i class="fa-solid fa-spinner fa-spin"></i> Archiving...';


            /* ---------------------------------------------
               PREPARE FORM DATA
               --------------------------------------------- */

            const formData =
                new FormData();


            itemIds.forEach(
                function (itemId) {

                    formData.append(
                        "item_ids[]",
                        itemId
                    );

                }
            );


            formData.append(
                "archive_reason",
                reason
            );


            /* ---------------------------------------------
               SEND TO BACKEND
               --------------------------------------------- */

            try {

                const response =
                    await fetch(
                        "../process/archive_inventory_items.php",
                        {
                            method: "POST",
                            body: formData,

                            headers: {
                                "Accept": "application/json"
                            }
                        }
                    );


                const result =
                    await response.json();


                /* -----------------------------------------
                   CHECK RESPONSE
                   ----------------------------------------- */

                if (
                    !response.ok ||
                    !result.success
                ) {

                    throw new Error(
                        result.message ||
                        "Unable to archive the selected inventory items."
                    );
                }


                /* -----------------------------------------
                   SUCCESS
                   ----------------------------------------- */

                closeInventoryArchiveModal();

                window.location.reload();


            } catch (error) {

                console.error(
                    "Inventory Archive Error:",
                    error
                );


                if (inventoryArchiveError) {

                    inventoryArchiveError.textContent =
                        error.message ||
                        "Unable to archive the selected inventory items.";

                    inventoryArchiveError.style.display =
                        "block";
                }


                inventoryArchiveConfirm.disabled =
                    false;

                inventoryArchiveConfirm.innerHTML =
                    '<i class="fa-solid fa-box-archive"></i> Archive Inventory';

            }

        }
    );

}




/* =========================================================
   SELECT ALL
   ========================================================= */

function selectAllInventoryItems(checked) {

    document
        .querySelectorAll(".inventory-select")
        .forEach(function (checkbox) {

            checkbox.checked =
                checked;

        });

    updateInventorySelection();

}


/* Main Select All */

if (selectAllInventory) {

    selectAllInventory.addEventListener(
        "change",
        function () {

            selectAllInventoryItems(
                this.checked
            );

        }
    );

}


/* Table Select All */

if (tableSelectAllInventory) {

    tableSelectAllInventory.addEventListener(
        "change",
        function () {

            selectAllInventoryItems(
                this.checked
            );

        }
    );

}


/* Individual checkboxes */

document.addEventListener("change", function (event) {

    const checkbox = event.target.closest(".inventory-select");

    if (!checkbox) {
        return;
    }

    updateInventorySelection();
    
});


/* Initial state */

updateInventorySelection();
});

// =========================================================
// VIEW ITEM
// =========================================================

const viewInventoryModal =
    document.getElementById("viewInventoryModal");

const closeViewInventoryModal =
    document.getElementById("closeViewInventoryModal");

const cancelViewInventoryModal =
    document.getElementById("cancelViewInventoryModal");

const viewItemHistoryBtn =
    document.getElementById("viewItemHistoryBtn");


// =========================================================
// OPEN VIEW MODAL
// =========================================================

function openViewItemModal(itemId) {

    if (!viewInventoryModal) {
        console.error("View Item modal not found.");
        return;
    }

    // Save item ID
    viewInventoryModal.dataset.itemId = itemId;


    // Reset fields
    setViewText("viewItemCode", "Loading...");
    setViewText("viewItemName", "Loading...");
    setViewText("viewItemCategory", "Loading...");
    setViewText("viewItemSubcategory", "Loading...");
    setViewText("viewItemUnit", "Loading...");
    setViewText("viewItemSupplier", "Loading...");

    setViewText("viewUnitCost", "Loading...");
    setViewText("viewRetailPrice", "Loading...");
    setViewText("viewReorderLevel", "Loading...");
    setViewText("viewCurrentStock", "Loading...");


    const batchWrapper =
        document.getElementById("viewBatchWrapper");

    if (batchWrapper) {

        batchWrapper.innerHTML = `
            <div class="inventory-view-loading">
                <i class="fa-solid fa-spinner fa-spin"></i>
                Loading batch information...
            </div>
        `;

    }


    // Show modal
    viewInventoryModal.classList.add("show");

    document.body.classList.add("modal-open");


    // =====================================================
    // GET ITEM
    // =====================================================

    fetch(
        "get_inventory_item.php?item_id=" +
        encodeURIComponent(itemId)
    )

    .then(response => {

        if (!response.ok) {
            throw new Error(
                "Unable to connect to server."
            );
        }

        return response.json();

    })

    .then(data => {

        if (!data.success) {

            throw new Error(
                data.message ||
                "Unable to load item."
            );

        }


        const item = data.item || {};


        // =================================================
        // ITEM INFORMATION
        // =================================================

        setViewText(
            "viewItemCode",
            item.item_code
        );

        setViewText(
            "viewItemName",
            item.item_name
        );

        setViewText(
            "viewItemCategory",
            item.category_name
        );

        setViewText(
            "viewItemSubcategory",
            item.subcategory_name
        );

        setViewText(
            "viewItemUnit",
            item.unit_display
        );

        setViewText(
            "viewItemSupplier",
            item.supplier_name
        );


        // =================================================
        // PRICING / STOCK
        // =================================================

        setViewText(
            "viewUnitCost",
            formatPeso(item.unit_cost)
        );

        setViewText(
            "viewRetailPrice",
            formatPeso(item.retail_price)
        );

        setViewText(
            "viewReorderLevel",
            item.reorder_level
        );

        setViewText(
            "viewCurrentStock",
            item.total_stock
        );


        // =================================================
        // BATCHES
        // =================================================

        renderViewBatches(
            data.batches || []
        );

    })

    .catch(error => {

        console.error(
            "View Item Error:",
            error
        );


        setViewText(
            "viewItemCode",
            "Unable to load"
        );

        setViewText(
            "viewItemName",
            "Unable to load"
        );

        setViewText(
            "viewItemCategory",
            "Unable to load"
        );

        setViewText(
            "viewItemSubcategory",
            "Unable to load"
        );

        setViewText(
            "viewItemUnit",
            "Unable to load"
        );

        setViewText(
            "viewItemSupplier",
            "Unable to load"
        );


        const batchWrapper =
            document.getElementById(
                "viewBatchWrapper"
            );


        if (batchWrapper) {

            batchWrapper.innerHTML = `

                <div class="inventory-view-empty">

                    <i class="fa-solid fa-circle-exclamation"></i>

                    Unable to load batch information.

                </div>

            `;

        }


        alert(
            error.message ||
            "Unable to load item details."
        );

    });

}


// =========================================================
// SET TEXT
// =========================================================

function setViewText(id, value) {

    const element =
        document.getElementById(id);


    if (!element) {
        return;
    }


    if (
        value === null ||
        value === undefined ||
        value === ""
    ) {

        element.textContent = "—";

        return;

    }


    element.textContent = value;

}


// =========================================================
// PESO FORMAT
// =========================================================

function formatPeso(value) {

    const number =
        Number(value);


    if (
        value === null ||
        value === undefined ||
        value === "" ||
        Number.isNaN(number)
    ) {

        return "—";

    }


    return "₱" +
        number.toLocaleString(
            "en-PH",
            {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }
        );

}


// =========================================================
// RENDER BATCHES
// =========================================================

function renderViewBatches(batches) {

    const wrapper =
        document.getElementById(
            "viewBatchWrapper"
        );


    if (!wrapper) {
        return;
    }


    if (
        !Array.isArray(batches) ||
        batches.length === 0
    ) {

        wrapper.innerHTML = `

            <div class="inventory-view-empty">

                <i class="fa-solid fa-box-open"></i>

                No batch records found for this item.

            </div>

        `;

        return;

    }


    let rows = "";


    batches.forEach(batch => {

        let statusClass =
            batch.status_class ||
            "available";


        rows += `

            <tr>

                <td>
                    ${escapeViewHtml(
                        batch.batch_number || "—"
                    )}
                </td>

                <td>
                    ${escapeViewHtml(
                        batch.expiration_date || "—"
                    )}
                </td>

                <td>
                    ${escapeViewHtml(
                        batch.quantity ?? "0"
                    )}
                </td>

                <td>

                    <span
                        class="inventory-batch-status ${statusClass}"
                    >

                        ${escapeViewHtml(
                            batch.status ||
                            "Available"
                        )}

                    </span>

                </td>

            </tr>

        `;

    });


    wrapper.innerHTML = `

        <table class="inventory-view-batch-table">

            <thead>

                <tr>

                    <th>
                        BATCH NO.
                    </th>

                    <th>
                        EXPIRATION
                    </th>

                    <th>
                        QUANTITY
                    </th>

                    <th>
                        STATUS
                    </th>

                </tr>

            </thead>

            <tbody>

                ${rows}

            </tbody>

        </table>

    `;

}


// =========================================================
// ESCAPE HTML
// =========================================================

function escapeViewHtml(value) {

    return String(value)

        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");

}


// =========================================================
// CLOSE VIEW MODAL
// =========================================================

function closeViewItemModal() {

    if (!viewInventoryModal) {
        return;
    }


    viewInventoryModal.classList.remove(
        "show"
    );


    document.body.classList.remove(
        "modal-open"
    );

}


if (closeViewInventoryModal) {

    closeViewInventoryModal.addEventListener(
        "click",
        closeViewItemModal
    );

}


if (cancelViewInventoryModal) {

    cancelViewInventoryModal.addEventListener(
        "click",
        closeViewItemModal
    );

}


// Close when clicking outside modal
if (viewInventoryModal) {

    viewInventoryModal.addEventListener(
        "click",
        function(event) {

            if (
                event.target ===
                viewInventoryModal
            ) {

                closeViewItemModal();

            }

        }
    );

}


// =========================================================
// VIEW STOCK HISTORY
// =========================================================

if (viewItemHistoryBtn) {

    viewItemHistoryBtn.addEventListener(
        "click",
        function() {

            if (!viewInventoryModal) {
                return;
            }


            const itemId =
                viewInventoryModal.dataset.itemId;


            if (!itemId) {
                return;
            }


            window.location.href =
                "stock_history.php?item_id=" +
                encodeURIComponent(itemId);

        }
    );

}

// =========================================================
// EDIT INVENTORY ITEM
// =========================================================

let editInventoryModal = null;


/* =========================================================
   CREATE EDIT MODAL
   ========================================================= */

function createEditInventoryModal() {

    if (document.getElementById("editInventoryModal")) {
        editInventoryModal =
            document.getElementById("editInventoryModal");

        return editInventoryModal;
    }


    const modal = document.createElement("div");

    modal.className = "inventory-modal-overlay";
    modal.id = "editInventoryModal";


    modal.innerHTML = `
        <div
            class="inventory-modal"
            role="dialog"
            aria-modal="true"
            aria-labelledby="editInventoryModalTitle"
        >

            <!-- HEADER -->

            <div class="inventory-modal-header">

                <div>

                    <h2 id="editInventoryModalTitle">
                        Edit Inventory Item
                    </h2>

                    <p>
                        Update the information of this inventory item.
                    </p>

                </div>


                <button
                    type="button"
                    class="modal-close-btn"
                    id="closeEditInventoryModal"
                    aria-label="Close"
                >
                    <i class="fa-solid fa-xmark"></i>
                </button>

            </div>


            <!-- FORM -->

            <form
                id="editInventoryForm"
                autocomplete="off"
            >

                <input
                    type="hidden"
                    id="editItemId"
                    name="item_id"
                >


                <div class="inventory-form-grid">


                    <!-- ITEM ID -->

                    <div class="inventory-form-group">

                        <label for="editItemCode">
                            Item ID <span>*</span>
                        </label>

                        <input
                            type="text"
                            id="editItemCode"
                            name="item_code"
                            placeholder="e.g. FOOD-001"
                            required
                        >

                    </div>


                    <!-- ITEM NAME -->

                    <div class="inventory-form-group">

                        <label for="editItemName">
                            Item Name <span>*</span>
                        </label>

                        <input
                            type="text"
                            id="editItemName"
                            name="item_name"
                            placeholder="Enter item name"
                            required
                        >

                    </div>


                    <!-- CATEGORY -->

                    <div class="inventory-form-group">

                        <label for="editItemCategory">
                            Category <span>*</span>
                        </label>

                        <select
                            id="editItemCategory"
                            name="category_id"
                            required
                        >
                            <option value="">
                                Select category
                            </option>
                        </select>

                    </div>


                    <!-- UNIT -->

                    <div class="inventory-form-group">

                        <label for="editItemUnit">
                            Unit
                        </label>

                        <select
                            id="editItemUnit"
                            name="unit_id"
                        >
                            <option value="">
                                Select unit
                            </option>
                        </select>

                    </div>


                    <!-- SUPPLIER -->

                    <div class="inventory-form-group">

                        <label for="editItemSupplier">
                            Supplier
                        </label>

                        <select
                            id="editItemSupplier"
                            name="supplier_id"
                        >
                            <option value="">
                                Select supplier
                            </option>
                        </select>

                    </div>


                    <!-- REORDER -->

                    <div class="inventory-form-group">

                        <label for="editReorderLevel">
                            Re-order Level
                        </label>

                        <input
                            type="number"
                            id="editReorderLevel"
                            name="reorder_level"
                            min="0"
                            step="0.01"
                        >

                    </div>


                    <!-- UNIT COST -->

                    <div class="inventory-form-group">

                        <label for="editUnitCost">
                            Unit Cost
                        </label>

                        <input
                            type="number"
                            id="editUnitCost"
                            name="unit_cost"
                            min="0"
                            step="0.01"
                        >

                    </div>


                    <!-- RETAIL PRICE -->

                    <div class="inventory-form-group">

                        <label for="editRetailPrice">
                            Retail Price
                        </label>

                        <input
                            type="number"
                            id="editRetailPrice"
                            name="retail_price"
                            min="0"
                            step="0.01"
                        >

                    </div>

                </div>


                <div class="inventory-form-note">

                    <i class="fa-solid fa-circle-info"></i>

                    <span>
                        Stock quantity, batch number and expiration
                        date are managed through Stock In and Stock Out.
                    </span>

                </div>


                <!-- FOOTER -->

                <div class="inventory-modal-footer">

                    <button
                        type="button"
                        class="modal-cancel-btn"
                        id="cancelEditInventoryModal"
                    >
                        Cancel
                    </button>


                    <button
                        type="submit"
                        class="modal-save-btn"
                        id="saveEditInventoryBtn"
                    >

                        <i class="fa-solid fa-check"></i>

                        Save Changes

                    </button>

                </div>

            </form>

        </div>
    `;


    document.body.appendChild(modal);

    editInventoryModal = modal;


    /* CLOSE */

    modal
        .querySelector("#closeEditInventoryModal")
        ?.addEventListener(
            "click",
            closeEditInventoryModal
        );


    modal
        .querySelector("#cancelEditInventoryModal")
        ?.addEventListener(
            "click",
            closeEditInventoryModal
        );


    modal.addEventListener(
        "click",
        function(event) {

            if (event.target === modal) {

                closeEditInventoryModal();

            }

        }
    );


    modal
        .querySelector("#editInventoryForm")
        ?.addEventListener(
            "submit",
            submitEditInventory
        );


    return modal;
}


/* =========================================================
   OPEN EDIT MODAL
   ========================================================= */

async function openEditItemModal(itemId) {

    const modal =
        createEditInventoryModal();


    const form =
        modal.querySelector("#editInventoryForm");


    form.reset();


    document.getElementById("editItemId").value =
        itemId;


    document.getElementById("editItemCode").value =
        "";


    document.getElementById("editItemName").value =
        "";


    document.getElementById("editReorderLevel").value =
        "0";


    document.getElementById("editUnitCost").value =
        "0";


    document.getElementById("editRetailPrice").value =
        "0";


    /* COPY OPTIONS FROM EXISTING ADD FORM */

    copySelectOptions(
        "itemCategory",
        "editItemCategory"
    );

    copySelectOptions(
        "itemUnit",
        "editItemUnit"
    );

    copySelectOptions(
        "itemSupplier",
        "editItemSupplier"
    );


    modal.classList.add("show");

    document.body.classList.add(
        "inventory-modal-open"
    );


    try {

        const response =
            await fetch(
                "edit_inventory_item.php?item_id=" +
                encodeURIComponent(itemId),
                {
                    headers: {
                        "Accept": "application/json"
                    }
                }
            );


        const data =
            await response.json();


        if (!data.success) {

            throw new Error(
                data.message ||
                "Unable to load inventory item."
            );

        }


        const item =
            data.item;


        document.getElementById(
            "editItemId"
        ).value =
            item.item_id;


        document.getElementById(
            "editItemCode"
        ).value =
            item.item_code || "";


        document.getElementById(
            "editItemName"
        ).value =
            item.item_name || "";


        document.getElementById(
            "editItemCategory"
        ).value =
            item.category_id || "";


        document.getElementById(
            "editItemUnit"
        ).value =
            item.unit_id || "";


        document.getElementById(
            "editItemSupplier"
        ).value =
            item.supplier_id || "";


        document.getElementById(
            "editReorderLevel"
        ).value =
            item.reorder_level ?? 0;


        document.getElementById(
            "editUnitCost"
        ).value =
            item.unit_cost ?? 0;


        document.getElementById(
            "editRetailPrice"
        ).value =
            item.retail_price ?? 0;


        document.getElementById(
            "editItemCode"
        )?.focus();


    } catch (error) {

        alert(
            error.message ||
            "Unable to load inventory item."
        );

        closeEditInventoryModal();

    }

}


/* =========================================================
   COPY SELECT OPTIONS
   ========================================================= */

function copySelectOptions(
    sourceId,
    targetId
) {

    const source =
        document.getElementById(sourceId);

    const target =
        document.getElementById(targetId);


    if (!source || !target) {
        return;
    }


    target.innerHTML =
        source.innerHTML;
}


/* =========================================================
   SUBMIT EDIT
   ========================================================= */

async function submitEditInventory(event) {

    event.preventDefault();


    const form =
        event.currentTarget;


    const saveButton =
        document.getElementById(
            "saveEditInventoryBtn"
        );


    if (!saveButton) {
        return;
    }


    const formData =
        new FormData(form);


    const itemId =
        formData.get("item_id");


    if (!itemId) {

        alert(
            "Invalid inventory item."
        );

        return;

    }


    saveButton.disabled = true;

    saveButton.innerHTML =
        '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';


    try {

        const response =
            await fetch(
                "edit_inventory_item.php",
                {
                    method: "POST",
                    body: formData,
                    headers: {
                        "Accept": "application/json"
                    }
                }
            );


        const data =
            await response.json();


        if (!data.success) {

            throw new Error(
                data.message ||
                "Unable to update inventory item."
            );

        }


        alert(
            data.message ||
            "Inventory item updated successfully."
        );


        closeEditInventoryModal();


        window.location.reload();


    } catch (error) {

        alert(
            error.message ||
            "Something went wrong while updating the item."
        );


        saveButton.disabled = false;

        saveButton.innerHTML =
            '<i class="fa-solid fa-check"></i> Save Changes';

    }

}


/* =========================================================
   CLOSE EDIT MODAL
   ========================================================= */

function closeEditInventoryModal() {

    const modal =
        document.getElementById(
            "editInventoryModal"
        );


    if (!modal) {
        return;
    }


    modal.classList.remove("show");


    document.body.classList.remove(
        "inventory-modal-open"
    );

}
