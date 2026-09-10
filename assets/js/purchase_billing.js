document.addEventListener("DOMContentLoaded", function () {

    // ========================================
    // ELEMENTS
    // ========================================

    const customerSelect =
        document.getElementById("purchaseCustomer");

    const buyerNameInput =
        document.getElementById("purchaseBuyerName");

    const petSelect =
        document.getElementById("purchasePet");

    // Modal
    const modal =
        document.getElementById("purchaseItemModal");

    const openModalBtn =
        document.getElementById("openPurchaseItemModal");

    const closeModalBtn =
        document.getElementById("closePurchaseItemModal");

    const cancelModalBtn =
        document.getElementById("cancelPurchaseItemBtn");

    // Inventory
    const categorySelect =
        document.getElementById("purchaseItemCategory");

    const searchInput =
        document.getElementById("purchaseItemSearch");

    const searchResults =
        document.getElementById("purchaseItemSearchResults");

    // Current selected item
    const currentItemInfo =
        document.getElementById("purchaseSelectedItemInfo");

    const currentItemName =
        document.getElementById("purchaseSelectedItemName");

    const currentItemStock =
        document.getElementById("purchaseSelectedItemStock");

    const currentItemUnit = 
        document.getElementById("purchaseSelectedItemUnit");  

    const quantityInput =
        document.getElementById("purchaseItemQuantity");

    const priceInput =
        document.getElementById("purchaseItemPrice");

    // Selected items
    const selectedItemsContainer =
        document.getElementById( "purchasePendingItemsList");

    const addItemBtn =
        document.getElementById("addPurchaseItemBtn");

    const saveItemsBtn =
        document.getElementById("savePurchaseItemsBtn"); 
    
    const pendingItemsSection =
        document.getElementById("purchasePendingItemsSection");

    const pendingItemsTotal = 
        document.getElementById("purchasePendingItemsTotal");  
 
    // Main table
    const itemsBody =
        document.getElementById("purchaseItemsBody");

    const itemCount =
        document.getElementById("purchaseItemCount");

    const totalDisplay =
        document.getElementById("purchaseTotal");

    const createBillingBtn =
        document.getElementById(
            "createPurchaseBillingBtn"
        );


    // ========================================
    // DATA
    // ========================================

    const customers =
        Array.isArray(window.purchaseCustomers)
            ? window.purchaseCustomers
            : [];

    const pets =
        Array.isArray(window.purchasePets)
            ? window.purchasePets
            : [];

    const inventoryItems =
        Array.isArray(window.purchaseInventoryItems)
            ? window.purchaseInventoryItems
            : [];


    // ========================================
    // STATE
    // ========================================

    /*
        Temporary items inside the modal.

        These are NOT yet in the main billing
        table until the user clicks "Add Items".
    */

    const selectedItems = new Map();


    /*
        Final items already added to the
        purchase billing table.
    */

    const purchaseItems = new Map();


    /*
        Currently selected inventory item.
    */

    let currentItemId = null;


    // ========================================
    // FORMAT CURRENCY
    // ========================================

    function formatCurrency(value) {

        const number =
            Number(value) || 0;

        return "₱" + number.toLocaleString(
            "en-PH",
            {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }
        );

    }


    // ========================================
    // ESCAPE HTML
    // ========================================

    function escapeHtml(value) {

        return String(value ?? "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");

    }


    // ========================================
    // GET INVENTORY ITEM
    // ========================================

    function getInventoryItem(itemId) {

        return inventoryItems.find(function (item) {

            return String(item.item_id) ===
                String(itemId);

        }) || null;

    }


    // ========================================
    // CUSTOMER → PET
    // ========================================

    function loadCustomerPets() {

        if (!petSelect) {
            return;
        }


        const customerId =
            customerSelect?.value || "";


        petSelect.innerHTML = `
            <option value="">
                No Pet / Purchase Only
            </option>
        `;


        if (!customerId) {

            petSelect.disabled = true;

            return;

        }


        const customerPets =
            pets.filter(function (pet) {

                return String(pet.customer_id) ===
                    String(customerId);

            });


        customerPets.forEach(function (pet) {

            const option =
                document.createElement("option");

            option.value =
                pet.pet_id;

            option.textContent =
                pet.pet_name;

            petSelect.appendChild(option);

        });


        petSelect.disabled =
            customerPets.length === 0;

    }


    if (customerSelect) {

        customerSelect.addEventListener(
            "change",
            function () {

                loadCustomerPets();


                /*
                    Automatically use the selected
                    customer's name as Buyer Name
                    if Buyer Name is still empty.
                */

                if (
                    customerSelect.value &&
                    buyerNameInput &&
                    !buyerNameInput.value.trim()
                ) {

                    const customer =
                        customers.find(function (item) {

                            return String(
                                item.customer_id
                            ) ===
                            String(
                                customerSelect.value
                            );

                        });


                    if (customer) {

                        buyerNameInput.value =
                            customer.owner_name || "";

                    }

                }

            }
        );

    }


    // ========================================
    // OPEN MODAL
    // ========================================

    function openModal() {

        if (!modal) {
            return;
        }


        modal.style.display = "flex";
        document.body.style.overflow = "hidden";


        /*
            Reset only the CURRENT item.

            Do NOT clear selectedItems.

            This means selected products remain
            when switching categories.
        */

        resetCurrentItem();

        renderSearchResults();

        renderSelectedItems();

    }


    if (openModalBtn) {

        openModalBtn.addEventListener(
            "click",
            openModal
        );

    }


    // ========================================
    // CLOSE MODAL
    // ========================================

    function closeModal() {

        if (!modal) {
            return;
        }


        modal.style.display = "none";

        document.body.style.overflow = "";

        resetCurrentItem();

    }


    if (closeModalBtn) {

        closeModalBtn.addEventListener(
            "click",
            closeModal
        );

    }


    if (cancelModalBtn) {

        cancelModalBtn.addEventListener(
            "click",
            closeModal
        );

    }


    // ========================================
    // CLICK OUTSIDE MODAL
    // ========================================

    if (modal) {

        modal.addEventListener(
            "click",
            function (event) {

                if (event.target === modal) {

                    closeModal();

                }

            }
        );

    }


    // ========================================
    // RESET CURRENT ITEM
    // ========================================

    function resetCurrentItem() {

        currentItemId = null;


        if (currentItemInfo) {

            currentItemInfo.style.display =
                "none";

        }


        if (currentItemName) {

            currentItemName.textContent = "";

        }


        if (currentItemStock) {

            currentItemStock.textContent = "";

        }

        if (currentItemUnit) {
            currentItemUnit.textContent = "Unit: —";
        }    


        if (quantityInput) {

            quantityInput.value = 1;

            quantityInput.disabled = true;

        }


        if (priceInput) {

            priceInput.value = "₱0.00";

        }
        if (currentItemUnit) {
            currentItemUnit.textContent = "—";
        }    

    }


    // ========================================
    // CATEGORY CHANGE
    // ========================================

    if (categorySelect) {

        categorySelect.addEventListener(
            "change",
            function () {

                resetCurrentItem();


                if (searchInput) {

                    searchInput.value = "";

                }


                renderSearchResults();

            }
        );

    }


    // ========================================
    // SEARCH
    // ========================================

    if (searchInput) {

        searchInput.addEventListener(
            "input",
            function () {

                renderSearchResults();

            }
        );

    }


    // ========================================
    // RENDER SEARCH RESULTS
    // ========================================

    function renderSearchResults() {

        if (!searchResults) {
            return;
        }


        const category =
            categorySelect?.value || "";


        const search =
            searchInput?.value
                ?.trim()
                .toLowerCase() || "";


        if (!category) {

            searchResults.innerHTML = `
                <div class="item-search-empty">
                    Select an item category first.
                </div>
            `;

            return;

        }


        const filteredItems =
            inventoryItems.filter(function (item) {

                const itemCategory =
                    String(
                        item.category_name || ""
                    ).toLowerCase();


                const itemName =
                    String(
                        item.item_name || ""
                    ).toLowerCase();


                const itemCode =
                    String(
                        item.item_code || ""
                    ).toLowerCase();


                const categoryMatches =
                    itemCategory ===
                    category.toLowerCase();


                const searchMatches =
                    !search ||
                    itemName.includes(search) ||
                    itemCode.includes(search);


                return (
                    categoryMatches &&
                    searchMatches
                );

            });


        if (filteredItems.length === 0) {

            searchResults.innerHTML = `
                <div class="item-search-empty">
                    No items found.
                </div>
            `;

            return;

        }


        searchResults.innerHTML =
            filteredItems.map(function (item) {

                const stock =
                    Number(
                        item.current_stock
                    ) || 0;


                const price =
                    Number(
                        item.retail_price
                    ) || 0;


                const isSelected =
                    selectedItems.has(
                        String(item.item_id)
                    );


                return `
                    <div
                        class="inventory-search-item purchase-inventory-result"
                        data-item-id="${escapeHtml(item.item_id)}"
                        style="
                            ${stock <= 0
                                ? "opacity:0.5;cursor:not-allowed;"
                                : ""
                            }
                        "
                    >

                        <div class="inventory-item-main">

                            <strong>
                                ${escapeHtml(
                                    item.item_name
                                )}
                            </strong>

                            <small>
                                Code:
                                ${escapeHtml(
                                    item.item_code || "N/A"
                                )}
                            </small>

                        </div>


                        <div class="inventory-item-meta">

                            <span>
                                Stock: ${stock}
                            </span>

                            <strong>
                                ${formatCurrency(price)}
                            </strong>

                        </div>

                    </div>
                `;

            }).join("");


        /*
            Highlight selected items.
        */

        filteredItems.forEach(function (item) {

            if (
                selectedItems.has(
                    String(item.item_id)
                )
            ) {

                const element =
                    searchResults.querySelector(
                        `[data-item-id="${item.item_id}"]`
                    );


                if (element) {

                    element.style.background =
                        "#f0f0f0";

                }

            }

        });


        // Attach click events

        const resultElements =
            searchResults.querySelectorAll(
                ".purchase-inventory-result"
            );


        resultElements.forEach(function (element) {

            element.addEventListener(
                "click",
                function () {

                    const itemId =
                        element.dataset.itemId;


                    selectInventoryItem(
                        itemId
                    );

                }
            );

        });

    }


    // ========================================
    // SELECT INVENTORY ITEM
    // ========================================

    function selectInventoryItem(itemId) {

        const item =
            getInventoryItem(itemId);


        if (!item) {
            return;
        }


        const stock =
            Number(item.current_stock) || 0;


        if (stock <= 0) {

            alert(
                "This item is currently out of stock."
            );

            return;

        }


        currentItemId =
            String(item.item_id);


        /*
            If already selected, show its
            existing quantity.
        */

        const existing =
            selectedItems.get(
                currentItemId
            );


        if (existing) {

            quantityInput.value =
                existing.quantity;

        } else {

            quantityInput.value = 1;

        }


        quantityInput.disabled = false;


        priceInput.value =
            formatCurrency(
                item.retail_price
            );


        currentItemName.textContent =
            item.item_name;


        currentItemStock.textContent =
            "Available stock: " + stock;

        if (currentItemUnit) {
            currentItemUnit.textContent =
                "Unit: " +
                (
                    item.abbreviation ||
                    item.unit_name ||
                    "—"
                );    
               
        }

        currentItemInfo.style.display =
            "block";

    }


    // ========================================
    // QUANTITY VALIDATION
    // ========================================

    if (quantityInput) {

        quantityInput.addEventListener(
            "input",
            function () {

                if (!currentItemId) {
                    return;
                }


                const item =
                    getInventoryItem(
                        currentItemId
                    );


                if (!item) {
                    return;
                }


                const stock =
                    Number(item.current_stock) || 0;


                let quantity =
                    parseInt(
                        quantityInput.value,
                        10
                    );


                if (
                    isNaN(quantity) ||
                    quantity < 1
                ) {

                    quantity = 1;

                }


                if (quantity > stock) {

                    quantity = stock;

                }


                quantityInput.value =
                    quantity;

            }
        );

    }


    // ========================================
    // ADD CURRENT ITEM TO SELECTED LIST
    // ========================================

    function addCurrentItemToSelected() {

        if (!currentItemId) {

            alert(
                "Please select an item first."
            );

            return false;

        }


        const item =
            getInventoryItem(
                currentItemId
            );


        if (!item) {

            alert(
                "Selected item could not be found."
            );

            return false;

        }


        const stock =
            Number(item.current_stock) || 0;


        let quantity =
            parseInt(
                quantityInput.value,
                10
            );


        if (
            isNaN(quantity) ||
            quantity < 1
        ) {

            quantity = 1;

        }


        if (quantity > stock) {

            alert(
                "Quantity cannot exceed available stock: " +
                stock
            );

            return false;

        }


        /*
            Same item:
            UPDATE quantity.

            Do not create duplicate rows.
        */

        selectedItems.set(
            String(item.item_id),
            {
                item_id:
                    item.item_id,

                item_code:
                    item.item_code,

                item_name:
                    item.item_name,

                category_name:
                    item.category_name,

                retail_price:
                    Number(
                        item.retail_price
                    ) || 0,

                current_stock:
                    stock,

                quantity:
                    quantity
            }
        );


        resetCurrentItem();

        renderSelectedItems();

        renderSearchResults();

        return true;

    }


    // ========================================
// ADD ITEM TO TEMPORARY LIST
// ========================================

if (addItemBtn) {

    addItemBtn.addEventListener(
        "click",
        function () {

            const added =
                addCurrentItemToSelected();

            if (!added) {
                return;
            }

        }
    );

}


// ========================================
// FINAL ADD ITEMS TO PURCHASE TABLE
// ========================================

if (saveItemsBtn) {

    saveItemsBtn.addEventListener(
        "click",
        function () {

            /*
             * If there is still a currently
             * selected item, add it first.
             */

            if (currentItemId) {

                const added =
                    addCurrentItemToSelected();

                if (!added) {
                    return;
                }

            }


            /*
             * Make sure at least one item
             * is ready to be transferred.
             */

            if (selectedItems.size === 0) {

                alert(
                    "Please select at least one item."
                );

                return;

            }


            /*
             * Transfer temporary items
             * into the main Purchase Items table.
             */

            selectedItems.forEach(
                function (item) {

                    const itemId =
                        String(item.item_id);


                    /*
                     * If the product already exists
                     * in the main table, add the
                     * quantity instead of creating
                     * a duplicate row.
                     */

                    if (
                        purchaseItems.has(itemId)
                    ) {

                        const existing =
                            purchaseItems.get(
                                itemId
                            );


                        existing.quantity =
                            Number(
                                existing.quantity
                            ) +
                            Number(
                                item.quantity
                            );


                    } else {

                        purchaseItems.set(
                            itemId,
                            {
                                item_id:
                                    item.item_id,

                                item_code:
                                    item.item_code,

                                item_name:
                                    item.item_name,

                                category_name:
                                    item.category_name,

                                retail_price:
                                    Number(
                                        item.retail_price
                                    ) || 0,

                                current_stock:
                                    Number(
                                        item.current_stock
                                    ) || 0,

                                quantity:
                                    Number(
                                        item.quantity
                                    ) || 1
                            }
                        );

                    }

                }
            );


            /*
             * Clear temporary list.
             */

            selectedItems.clear();


            renderPurchaseTable();

            renderSelectedItems();


            /*
             * Close modal.
             */

            closeModal();

        }
    );

}

    // ========================================
    // RENDER SELECTED ITEMS
    // ========================================

    function renderSelectedItems() {

        if (!selectedItemsContainer) {
            return;
        }

        if (pendingItemsSection) {
            pendingItemsSection.style.display =
                selectedItems.size > 0 ? "block" : "none";
        }
        
        if (saveItemsBtn) {
            saveItemsBtn.style.display =
                selectedItems.size > 0 ? "inline-flex" : "none";
        }        

        if (selectedItems.size === 0) {

            selectedItemsContainer.innerHTML = `
                <div class="item-search-empty">
                    No items selected.
                </div>
            `;

            if (pendingItemsTotal) {
                pendingItemsTotal.textContent =
                    formatCurrency(0);

            }
            return;

        }


        selectedItemsContainer.innerHTML =
            Array.from(
                selectedItems.values()
            ).map(function (item) {

                const amount =
                    Number(item.quantity) *
                    Number(item.retail_price);


                return `
                    <div
                        class="selected-purchase-item"
                        data-item-id="${escapeHtml(item.item_id)}"
                        style="
                            display:flex;
                            align-items:center;
                            justify-content:space-between;
                            gap:12px;
                            padding:10px 0;
                            border-bottom:1px solid #eee;
                        "
                    >

                        <div>

                            <div class="selected-item-name">

                                ${escapeHtml(
                                    item.item_name
                                )}

                            </div>


                            <div class="selected-item-stock">

                                ${escapeHtml(
                                    item.category_name
                                )}

                                · Qty:
                                ${item.quantity}

                                ·
                                ${formatCurrency(
                                    item.retail_price
                                )}

                            </div>

                        </div>


                        <div
                            style="
                                display:flex;
                                align-items:center;
                                gap:10px;
                            "
                        >

                            <strong>
                                ${formatCurrency(amount)}
                            </strong>


                            <button
                                type="button"
                                class="remove-selected-purchase-item"
                                data-item-id="${escapeHtml(item.item_id)}"
                                title="Remove item"
                                style="
                                    border:none;
                                    background:transparent;
                                    color:#c0392b;
                                    cursor:pointer;
                                    font-size:16px;
                                "
                            >
                                <i class="fa-solid fa-xmark"></i>
                            </button>

                        </div>

                    </div>
                `;

            }).join("");

            let pendingTotal = 0;

            selectedItems.forEach(function (item) {
                pendingTotal +=
                    Number(item.quantity || 0) *
                    Number(item.retail_price || 0);
            });
            
            if (pendingItemsTotal) {
                pendingItemsTotal.textContent =
                    formatCurrency(pendingTotal);
                }

        


        const removeButtons =
            selectedItemsContainer.querySelectorAll(
                ".remove-selected-purchase-item"
            );


        removeButtons.forEach(function (button) {

            button.addEventListener(
                "click",
                function () {

                    const itemId =
                        String(
                            button.dataset.itemId
                        );


                    selectedItems.delete(
                        itemId
                    );


                    renderSelectedItems();

                    renderSearchResults();

                }
            );

        });

    }


    // ========================================
    // RENDER MAIN PURCHASE TABLE
    // ========================================

    function renderPurchaseTable() {

        if (!itemsBody) {
            return;
        }


        if (purchaseItems.size === 0) {

            itemsBody.innerHTML = `
                <tr>

                    <td
                        colspan="6"
                        class="no-items"
                    >
                        No purchase items added yet.
                    </td>

                </tr>
            `;

            updateSummary();

            return;

        }


        itemsBody.innerHTML =
            Array.from(
                purchaseItems.values()
            ).map(function (item) {

                const amount =
                    Number(item.quantity) *
                    Number(item.retail_price);


                return `
                    <tr
                        data-item-id="${escapeHtml(
                            item.item_id
                        )}"
                    >

                        <td>

                            <strong>
                                ${escapeHtml(
                                    item.item_name
                                )}
                            </strong>

                            <div
                                style="
                                    font-size:12px;
                                    opacity:.7;
                                    margin-top:3px;
                                "
                            >
                                ${escapeHtml(
                                    item.item_code || "N/A"
                                )}
                            </div>

                        </td>


                        <td>

                            <span class="item-type">
                                ${escapeHtml(
                                    item.category_name
                                )}
                            </span>

                        </td>


                        <td>

                            <input
                                type="number"
                                class="purchase-table-quantity"
                                data-item-id="${escapeHtml(
                                    item.item_id
                                )}"
                                value="${item.quantity}"
                                min="1"
                                max="${item.current_stock}"
                                step="1"
                                style="
                                    width:70px;
                                    padding:6px;
                                "
                            >

                        </td>


                        <td>

                            ${formatCurrency(
                                item.retail_price
                            )}

                        </td>


                        <td>

                            <strong>
                                ${formatCurrency(amount)}
                            </strong>

                        </td>


                        <td>

                            <button
                                type="button"
                                class="remove-purchase-item"
                                data-item-id="${escapeHtml(
                                    item.item_id
                                )}"
                              
                            >

                                <i class="fa-solid fa-trash"></i>

                            </button>

                        </td>

                    </tr>
                `;

            }).join("");


        // ====================================
        // TABLE QUANTITY
        // ====================================

        const quantityFields =
            itemsBody.querySelectorAll(
                ".purchase-table-quantity"
            );


        quantityFields.forEach(function (input) {

            input.addEventListener(
                "change",
                function () {

                    const itemId =
                        String(
                            input.dataset.itemId
                        );


                    const item =
                        purchaseItems.get(
                            itemId
                        );


                    if (!item) {
                        return;
                    }


                    const stock =
                        Number(
                            item.current_stock
                        ) || 0;


                    let quantity =
                        parseInt(
                            input.value,
                            10
                        );


                    if (
                        isNaN(quantity) ||
                        quantity < 1
                    ) {

                        quantity = 1;

                    }


                    if (quantity > stock) {

                        alert(
                            "Quantity cannot exceed available stock: " +
                            stock
                        );

                        quantity = stock;

                    }


                    item.quantity =
                        quantity;


                    renderPurchaseTable();

                }
            );

        });


        // ====================================
        // REMOVE TABLE ITEM
        // ====================================

        const removeButtons =
            itemsBody.querySelectorAll(
                ".remove-purchase-item"
            );


        removeButtons.forEach(function (button) {

            button.addEventListener(
                "click",
                function () {

                    const itemId =
                        String(
                            button.dataset.itemId
                        );


                    purchaseItems.delete(
                        itemId
                    );


                    renderPurchaseTable();

                }
            );

        });


        updateSummary();

    }


    // ========================================
    // SUMMARY
    // ========================================

    function updateSummary() {

        let total = 0;

        let count = 0;


        purchaseItems.forEach(function (item) {

            const quantity =
                Number(item.quantity) || 0;


            const price =
                Number(item.retail_price) || 0;


            count += quantity;


            total +=
                quantity * price;

        });


        if (itemCount) {

            itemCount.textContent =
                count;

        }


        if (totalDisplay) {

            totalDisplay.textContent =
                formatCurrency(total);

        }

    }


    // ========================================
// CREATE PURCHASE BILLING
// ========================================

if (createBillingBtn) {

    createBillingBtn.addEventListener(
        "click",
        async function () {

            if (purchaseItems.size === 0) {

                alert(
                    "Please add at least one purchase item."
                );

                return;
            }


            /*
             * Get buyer information
             */

            const customerId =
                customerSelect?.value || "";

            const buyerName =
                buyerNameInput?.value.trim() || "";

            const petId =
                petSelect?.value || "";


            /*
             * Convert purchase items
             * into a normal array.
             */

            const items =
                Array.from(
                    purchaseItems.values()
                ).map(function (item) {

                    return {
                        item_id:
                            Number(item.item_id),

                        quantity:
                            Number(item.quantity)
                    };

                });


            /*
             * Confirm before creating billing.
             */

            const confirmed =
                confirm(
                    "Create this purchase billing?\n\n" +
                    "Total Amount: " +
                    formatCurrency(
                        Array.from(
                            purchaseItems.values()
                        ).reduce(
                            function (total, item) {

                                return total +
                                    (
                                        Number(item.quantity) *
                                        Number(item.retail_price)
                                    );

                            },
                            0
                        )
                    )
                );


            if (!confirmed) {
                return;
            }


            /*
             * Prevent double-click
             */

            createBillingBtn.disabled = true;

            const originalButtonText =
                createBillingBtn.innerHTML;

            createBillingBtn.innerHTML =
                '<i class="fa-solid fa-spinner fa-spin"></i> Creating...';


            try {

                const formData =
                    new FormData();


                formData.append(
                    "customer_id",
                    customerId
                );


                formData.append(
                    "buyer_name",
                    buyerName
                );


                formData.append(
                    "pet_id",
                    petId
                );


                formData.append(
                    "items",
                    JSON.stringify(items)
                );


                const response =
                    await fetch(
                        "../process/create_purchase_billing.php",
                        {
                            method: "POST",
                            body: formData
                        }
                    );


                const result =
                    await response.json();


                if (!result.success) {

                    throw new Error(
                        result.message ||
                        "Failed to create purchase billing."
                    );
                }


                /*
                 * Billing successfully created.
                 *
                 * Go directly to the
                 * Billing Statement.
                 */

                window.location.href =
                    "billing_statement.php?billing_id=" +
                    encodeURIComponent(
                        result.billing_id
                    );


            } catch (error) {

                console.error(
                    "Create Purchase Billing Error:",
                    error
                );


                alert(
                    error.message ||
                    "An error occurred while creating purchase billing."
                );


                createBillingBtn.disabled =
                    false;

                createBillingBtn.innerHTML =
                    originalButtonText;
            }

        }
    );

}


    // ========================================
    // INITIAL LOAD
    // ========================================

    loadCustomerPets();

    renderSearchResults();

    renderSelectedItems();

    renderPurchaseTable();

});