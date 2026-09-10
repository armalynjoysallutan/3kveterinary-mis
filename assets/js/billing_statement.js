
/* =====================================================
   CUSTOM BILLING MESSAGE MODAL
   Replaces browser alert() / confirm()
===================================================== */

function showBillingMessage(
    message,
    type = "info",
    title = "Message",
    mode = "ok"
) {
    return new Promise(function (resolve) {

        const oldModal =
            document.getElementById("billingMessageModal");

        if (oldModal) {
            oldModal.remove();
        }

        const iconMap = {
            success: "fa-circle-check",
            warning: "fa-triangle-exclamation",
            error: "fa-circle-xmark",
            info: "fa-circle-info"
        };

        const modal = document.createElement("div");
        modal.id = "billingMessageModal";
        modal.className = "billing-message-overlay";

        const box = document.createElement("div");
        box.className = "billing-message-box";
        box.setAttribute("role", "dialog");
        box.setAttribute("aria-modal", "true");

        const icon = document.createElement("div");
        icon.className =
            "billing-message-icon " +
            (iconMap[type] ? type : "info");

        const iconElement = document.createElement("i");
        iconElement.className =
            "fa-solid " +
            (iconMap[type] || iconMap.info);

        icon.appendChild(iconElement);

        const heading = document.createElement("h3");
        heading.textContent = title;

        const text = document.createElement("p");
        text.textContent = message;

        const actions = document.createElement("div");
        actions.className = "billing-message-actions";

        if (mode === "confirm") {

            const cancelBtn =
                document.createElement("button");

            cancelBtn.type = "button";
            cancelBtn.className =
                "billing-message-btn cancel";
            cancelBtn.textContent = "Cancel";

            const confirmBtn =
                document.createElement("button");

            confirmBtn.type = "button";
            confirmBtn.className =
                "billing-message-btn confirm";
            confirmBtn.textContent = "Confirm";

            actions.appendChild(cancelBtn);
            actions.appendChild(confirmBtn);

            cancelBtn.addEventListener(
                "click",
                function () {
                    close(false);
                }
            );

            confirmBtn.addEventListener(
                "click",
                function () {
                    close(true);
                }
            );

        } else {

            const okBtn =
                document.createElement("button");

            okBtn.type = "button";
            okBtn.className =
                "billing-message-btn confirm";
            okBtn.textContent = "OK";

            actions.appendChild(okBtn);

            okBtn.addEventListener(
                "click",
                function () {
                    close(true);
                }
            );
        }

        box.appendChild(icon);
        box.appendChild(heading);
        box.appendChild(text);
        box.appendChild(actions);

        modal.appendChild(box);
        document.body.appendChild(modal);

        function close(value) {

            modal.classList.remove("show");

            setTimeout(function () {

                if (modal.parentNode) {
                    modal.remove();
                }

                resolve(value);

            }, 180);
        }

        requestAnimationFrame(function () {
            modal.classList.add("show");
        });

    });
}


function showBillingAlert(
    message,
    type = "info",
    title = "Message"
) {
    return showBillingMessage(
        message,
        type,
        title,
        "ok"
    );
}


function showBillingConfirm(
    message,
    title = "Confirm Payment"
) {
    return showBillingMessage(
        message,
        "warning",
        title,
        "confirm"
    );
}


document.addEventListener(
    "DOMContentLoaded",
    function () {
        console.log("BILLING JS LOADED");


        /* =====================================================
           ELEMENTS
        ====================================================== */

        const serviceModal =
            document.getElementById(
                "serviceModal"
            );

        const openAddServiceBtn =
            document.getElementById(
                "openAddServiceBtn"
            );

        const closeServiceModalBtn =
            document.getElementById(
                "closeServiceModal"
            );

        const cancelServiceBtn =
            document.getElementById(
                "cancelServiceBtn"
            );

        const itemCategory =
            document.getElementById(
                "itemCategory"
            );

        const itemSearch =
            document.getElementById(
                "itemSearch"
            );

        const itemSearchResults =
            document.getElementById(
                "itemSearchResults"
            );

        const selectedItemInfo =
            document.getElementById(
                "selectedItemInfo"
            );

        const selectedItemName =
            document.getElementById(
                "selectedItemName"
            );
        const selectedItemStock =
            document.getElementById(
                "selectedItemStock"
            );
            
        const selectedItemUnit =
            document.getElementById(
                "selectedItemUnit"
            );    
            
        const itemQuantity =
            document.getElementById(
                "itemQuantity"
            );
            
        const itemPrice =
            document.getElementById(
                "itemPrice"
            );
            
        let selectedInventoryItem = null;    
            
        const addServiceBtn =
            document.getElementById(
                "addServiceBtn"
            );

        const amountPaid =
            document.getElementById(
                "amountPaid"
            );

        const changeAmount =
            document.getElementById(
                "changeAmount"
            );

        const grandTotal =
            document.getElementById(
                "grandTotal"
            );

        const confirmPaymentBtn =
            document.getElementById(
                "confirmPaymentBtn"
            );


        /* =====================================================
   DATABASE SERVICE CATALOG
====================================================== */

const serviceCatalog = {};


// =====================================================
// LOAD SERVICES FROM DATABASE
// =====================================================

if (
    Array.isArray(
        window.billingServices
    )
) {

    window.billingServices.forEach(
        function (service) {

            const category =
                service.category_name;


            if (
                !serviceCatalog[category]
            ) {

                serviceCatalog[category] =
                    [];

            }


            serviceCatalog[category].push({

                id:
                    service.service_id,

                name:
                    service.service_name,

                pricingType:
                    service.pricing_type,

                fixedPrice:
                    service.fixed_price,

                pricingRuleId:
                    service.pricing_rule_id,

                baseMinWeight:
                    service.base_min_weight,

                baseMaxWeight:
                    service.base_max_weight,

                basePrice:
                    service.base_price,

                weightIncrement:
                    service.weight_increment,

                priceIncrement:
                    service.price_increment

            });

        }
    );

}


// =====================================================
// LOAD MEDICATIONS FROM DATABASE
// =====================================================

if (
    Array.isArray(
        window.billingMedications
    )
) {

    serviceCatalog["Medication"] = [];


    window.billingMedications.forEach(
        function (medication) {

            serviceCatalog["Medication"].push({

                id:
                    medication.medication_id,

                name:
                    medication.medication_name,

                pricingType:
                    "Fixed",

                fixedPrice:
                    medication.unit_price

            });

        }
    );

}

        /* =====================================================
           OPEN ADD SERVICE MODAL
        ====================================================== */

        if (openAddServiceBtn) {

            openAddServiceBtn.addEventListener(
                "click",
                function () {

                    serviceModal.classList.add("show");
                    document.body.style.overflow = "hidden";

                }
            );

        }


        /* =====================================================
           CLOSE MODAL
        ====================================================== */

        function closeServiceModal() {

            if (serviceModal) {

                serviceModal.classList.remove(
                    "show"
                );

            }
            document.body.style.overflow = "";

        }


        closeServiceModalBtn?.addEventListener(
            "click",
            closeServiceModal
        );


        cancelServiceBtn?.addEventListener(
            "click",
            closeServiceModal
        );


        serviceModal?.addEventListener(
            "click",
            function (e) {

                if (
                    e.target ===
                    serviceModal
                ) {

                    closeServiceModal();

                }

            }
        );



/* =====================================================
   ITEM CATEGORY + SEARCH
===================================================== */

function renderInventorySearchResults() {

    if (!itemSearchResults) {
        return;
    }

    const category =
        itemCategory?.value?.trim() || "";

    const search =
        itemSearch?.value
            ?.trim()
            .toLowerCase() || "";

    selectedInventoryItem = null;

    if (selectedItemInfo) {
        selectedItemInfo.style.display =
            "none";
    }

    if (!category) {

        itemSearchResults.innerHTML = `
            <div class="item-search-empty">
                Select an item category first.
            </div>
        `;

        return;
    }


    const inventoryItems =
        Array.isArray(
            window.billingInventoryItems
        )
            ? window.billingInventoryItems
            : [];


    const filteredItems =
        inventoryItems.filter(
            function (item) {

                const itemCategoryName =
                    (
                        item.category_name || ""
                    )
                    .toLowerCase();

                const itemName =
                    (
                        item.item_name || ""
                    )
                    .toLowerCase();

                return (
                    itemCategoryName ===
                        category.toLowerCase()
                    &&
                    (
                        !search ||
                        itemName.includes(search)
                    )
                );

            }
        );


    if (
        filteredItems.length === 0
    ) {

        itemSearchResults.innerHTML = `
            <div class="item-search-empty">
                No matching items found.
            </div>
        `;

        return;
    }


    itemSearchResults.innerHTML =
        filteredItems
            .map(
                function (item) {

                    const stock =
                        Number(
                            item.current_stock
                        ) || 0;

                    const price =
                        Number(
                            item.retail_price
                        ) || 0;

                    return `
                        <div
                            class="inventory-search-item"
                            data-item-id="${item.item_id}"
                        >

                            <div class="inventory-item-main">

                                <strong>
                                    ${item.item_name}
                                </strong>

                                <small>
                                    ${item.item_code}
                                </small>

                            </div>

                            <div class="inventory-item-meta">

                                <span>
                                    Stock:
                                    ${stock}
                                </span>

                                <span>
                                    Unit:
                                    ${item.abbreviation || item.unit_name || "--"}
                                </span>

                                <strong>
                                    ₱${price.toFixed(2)}
                                </strong>

                            </div>

                        </div>
                    `;

                }
            )
            .join("");

}


/* =====================================================
   ITEM CATEGORY CHANGE
===================================================== */

itemCategory?.addEventListener(
    "change",
    function () {
        console.log("CATEGORY CHANGED!");
        console.log("SELECTED CATEGORY:", itemCategory.value);

        if (itemSearch) {
            itemSearch.value = "";
        }

        if (itemPrice) {
            itemPrice.value = "";
        }

        if (itemQuantity) {
            itemQuantity.value = 1;
        }

        selectedInventoryItem = null;

        if (selectedItemInfo) {
            selectedItemInfo.style.display =
                "none";
        }

        renderInventorySearchResults();

    }
);


/* =====================================================
   ITEM SEARCH INPUT
===================================================== */

itemSearch?.addEventListener(
    "input",
    function () {

        renderInventorySearchResults();

    }
);


/* =====================================================
   SELECT INVENTORY ITEM
===================================================== */

itemSearchResults?.addEventListener(
    "click",
    function (event) {

        const itemElement =
            event.target.closest(
                ".inventory-search-item"
            );

        if (!itemElement) {
            return;
        }


        const itemId =
            Number(
                itemElement.dataset.itemId
            );


        const inventoryItems =
            Array.isArray(
                window.billingInventoryItems
            )
                ? window.billingInventoryItems
                : [];


        const item =
            inventoryItems.find(
                function (inventoryItem) {

                    return (
                        Number(
                            inventoryItem.item_id
                        ) === itemId
                    );

                }
            );


        if (!item) {
            return;
        }


        selectedInventoryItem =
            item;


        if (selectedItemName) {

            selectedItemName.textContent =
                item.item_name;

        }


        if (selectedItemStock) {

            selectedItemStock.textContent =
                Number(
                    item.current_stock
                ) || 0;

        }

        if (selectedItemUnit) {
            selectedItemUnit.textContent =
            item.abbreviation ||
            item.unit_name ||
            "—";
        }    


        if (itemPrice) {

            itemPrice.value =
                Number(
                    item.retail_price
                ).toFixed(2);

        }


        if (selectedItemInfo) {

            selectedItemInfo.style.display =
                "block";

        }

    }
);


/* =====================================================
   MULTIPLE ITEMS - TEMPORARY LIST
===================================================== */

let pendingBillingItems = [];

const pendingItemsSection =
    document.getElementById(
        "pendingItemsSection"
    );

const pendingItemsList =
    document.getElementById(
        "pendingItemsList"
    );

const pendingItemsTotal =
    document.getElementById(
        "pendingItemsTotal"
    );

const savePendingItemsBtn =
    document.getElementById(
        "savePendingItemsBtn"
    );


/* =====================================================
   RENDER PENDING ITEMS
===================================================== */

function renderPendingBillingItems() {

    if (!pendingItemsList) {
        return;
    }

    if (pendingBillingItems.length === 0) {

        pendingItemsList.innerHTML = "";

        if (pendingItemsSection) {
            pendingItemsSection.style.display =
                "none";
        }

        if (savePendingItemsBtn) {
            savePendingItemsBtn.style.display =
                "none";
        }

        if (pendingItemsTotal) {
            pendingItemsTotal.textContent =
                "₱0.00";
        }

        return;
    }


    if (pendingItemsSection) {
        pendingItemsSection.style.display =
            "block";
    }

    if (savePendingItemsBtn) {
        savePendingItemsBtn.style.display =
            "inline-flex";
    }


    pendingItemsList.innerHTML =
        pendingBillingItems
            .map(function (item, index) {

                const amount =
                    item.quantity *
                    item.unit_price;


                return `
                    <div class="pending-item-row">

                        <div class="pending-item-details">

                            <strong>
                                ${item.item_name}
                            </strong>

                            <small>
                                ${item.item_category}
                            </small>

                            <span>
                                ${item.quantity}
                                ×
                                ₱${item.unit_price.toFixed(2)}
                            </span>

                        </div>


                        <div class="pending-item-right">

                            <strong>
                                ₱${amount.toFixed(2)}
                            </strong>

                            <button
                                type="button"
                                class="remove-pending-item"
                                data-index="${index}"
                                title="Remove item"
                            >
                                <i class="fa-solid fa-trash"></i>
                            </button>

                        </div>

                    </div>
                `;

            })
            .join("");


    const total =
        pendingBillingItems.reduce(
            function (sum, item) {

                return (
                    sum +
                    (
                        item.quantity *
                        item.unit_price
                    )
                );

            },
            0
        );


    if (pendingItemsTotal) {

        pendingItemsTotal.textContent =
            "₱" +
            total.toLocaleString(
                "en-PH",
                {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }
            );

    }

}


/* =====================================================
   REMOVE PENDING ITEM
===================================================== */

pendingItemsList?.addEventListener(
    "click",
    function (event) {

        const removeBtn =
            event.target.closest(
                ".remove-pending-item"
            );

        if (!removeBtn) {
            return;
        }


        const index =
            Number(
                removeBtn.dataset.index
            );


        if (
            Number.isNaN(index)
        ) {
            return;
        }


        pendingBillingItems.splice(
            index,
            1
        );


        renderPendingBillingItems();

    }
);


/* =====================================================
   ADD ITEM TO TEMPORARY LIST
===================================================== */

addServiceBtn?.addEventListener(
    "click",
    function () {

        if (!selectedInventoryItem) {

            alert(
                "Please select an item."
            );

            return;
        }


        const quantity =
            Number(
                itemQuantity?.value
            ) || 0;


        const unitPrice =
            Number(
                itemPrice?.value
            ) || 0;


        const stock =
            Number(
                selectedInventoryItem.current_stock
            ) || 0;


        if (quantity <= 0) {

            alert(
                "Please enter a valid quantity."
            );

            return;
        }


        if (quantity > stock) {

            alert(
                "Quantity exceeds available stock."
            );

            return;
        }


        if (unitPrice < 0) {

            alert(
                "Invalid unit price."
            );

            return;
        }


        const itemId =
            Number(
                selectedInventoryItem.item_id
            );


        /*
         * Check whether this product
         * is already in the temporary list.
         */

        const existingItem =
            pendingBillingItems.find(
                function (item) {

                    return (
                        Number(
                            item.item_id
                        ) === itemId
                    );

                }
            );


        if (existingItem) {

            const newQuantity =
                existingItem.quantity +
                quantity;


            if (newQuantity > stock) {

                alert(
                    "The total quantity exceeds available stock."
                );

                return;
            }


            existingItem.quantity =
                newQuantity;


            existingItem.amount =
                newQuantity *
                existingItem.unit_price;

        } else {

            pendingBillingItems.push({

                item_id:
                    itemId,

                item_code:
                    selectedInventoryItem.item_code || "",

                item_name:
                    selectedInventoryItem.item_name || "",

                item_category:
                    selectedInventoryItem.category_name || "",

                quantity:
                    quantity,

                unit_price:
                    unitPrice,

                amount:
                    quantity *
                    unitPrice

            });

        }


        renderPendingBillingItems();


        /*
         * Reset only the product
         * selection fields.
         *
         * The modal stays open so the
         * user can immediately select
         * another product.
         */

        if (itemSearch) {
            itemSearch.value = "";
        }

        if (itemPrice) {
            itemPrice.value = "";
        }

        if (itemQuantity) {
            itemQuantity.value = 1;
        }

        selectedInventoryItem = null;


        if (selectedItemInfo) {

            selectedItemInfo.style.display =
                "none";

        }


        renderInventorySearchResults();

    }
);


/* =====================================================
   FINAL SAVE - ADD ALL ITEMS
===================================================== */

savePendingItemsBtn?.addEventListener(
    "click",
    async function () {

        if (
            pendingBillingItems.length === 0
        ) {

            alert(
                "Please add at least one item."
            );

            return;
        }


        const billingId =
            window.currentBillingId;


        if (!billingId) {

            alert(
                "Billing ID is missing."
            );

            return;
        }


        savePendingItemsBtn.disabled =
            true;


        savePendingItemsBtn.innerHTML =
            '<i class="fa-solid fa-spinner fa-spin"></i> Adding...';


        try {

            /*
             * Add every temporary item
             * to the current billing.
             */

            for (
                const item
                of pendingBillingItems
            ) {

                const response =
                    await fetch(
                        "../process/add_billing_item.php",
                        {
                            method: "POST",

                            headers: {
                                "Content-Type":
                                    "application/x-www-form-urlencoded"
                            },

                            body:
                                new URLSearchParams({

                                    billing_id:
                                        String(
                                            billingId
                                        ),

                                    item_type:
                                        "Product",

                                    item_id:
                                        String(
                                            item.item_id
                                        ),

                                    item_name:
                                        item.item_name,

                                    quantity:
                                        String(
                                            item.quantity
                                        ),

                                    unit_price:
                                        String(
                                            item.unit_price
                                        )

                                })

                        }
                    );


                const result =
                    await response.json();


                if (!result.success) {

                    throw new Error(
                        result.message ||
                        "Unable to add item."
                    );

                }

            }


            pendingBillingItems = [];


            await showBillingAlert(
                "All selected items were added successfully.",
                "success",
                "Items Added"
            );


            location.reload();


        } catch (error) {

            console.error(
                "ADD MULTIPLE ITEMS ERROR:",
                error
            );


            await showBillingAlert(
                error.message ||
                "Unable to add items. Please try again.",
                "error",
                "Add Items Failed"
            );


        } finally {

            savePendingItemsBtn.disabled =
                false;

            savePendingItemsBtn.innerHTML =
                "Add Items";

        }

    }
);

/* =====================================================
   PRODUCT QUANTITY UPDATE
===================================================== */

document
    .querySelectorAll(".billing-product-quantity")
    .forEach(function (input) {

        input.addEventListener(
            "change",
            async function () {

                const billingItemId =
                    this.dataset.itemId;

                let quantity =
                    Number(this.value);


                if (
                    !billingItemId ||
                    !Number.isFinite(quantity) ||
                    quantity < 1
                ) {

                    this.value = 1;

                    return;
                }


                try {

                    const response =
                        await fetch(
                            "../process/update_billing_item.php",
                            {
                                method: "POST",

                                headers: {
                                    "Content-Type":
                                        "application/x-www-form-urlencoded"
                                },

                                body:
                                    new URLSearchParams({

                                        billing_item_id:
                                            billingItemId,

                                        quantity:
                                            String(quantity)

                                    })
                            }
                        );


                    const result =
                        await response.json();


                    if (!result.success) {

                        await showBillingAlert(
                            result.message ||
                                "Unable to update quantity.",
                            "error",
                            "Quantity Update Failed"
                        );

                        location.reload();

                        return;
                    }


                    location.reload();


                } catch (error) {

                    console.error(
                        "QUANTITY UPDATE ERROR:",
                        error
                    );


                    await showBillingAlert(
                        "Something went wrong while updating the quantity.",
                        "error",
                        "Quantity Update Failed"
                    );

                    location.reload();

                }

            }
        );

    });

        /* =====================================================
           GET GRAND TOTAL
        ====================================================== */

        function getGrandTotal() {

            if (!grandTotal) {

                return 0;

            }


            return parseFloat(

                grandTotal.textContent
                    .replace("₱", "")
                    .replace(/,/g, "")

            ) || 0;

        }


        /* =====================================================
           AMOUNT PAID → CHANGE
        ====================================================== */

        amountPaid?.addEventListener(
            "input",
            function () {

                const paid =
                    parseFloat(
                        this.value
                    ) || 0;


                const total =
                    getGrandTotal();


                const change =
                    paid - total;


                if (changeAmount) {

                    changeAmount.textContent =
                        "₱" +
                        Math.max(
                            change,
                            0
                        ).toLocaleString(
                            "en-PH",
                            {
                                minimumFractionDigits:
                                    2,

                                maximumFractionDigits:
                                    2
                            }
                        );

                }

            }
        );


        /* =====================================================
           CONFIRM PAYMENT
        ====================================================== */

        confirmPaymentBtn?.addEventListener(
            "click",
            async function () {

                const billingId =
                    this.dataset.id;


                const paid =
                    parseFloat(
                        amountPaid?.value
                    ) || 0;


                const total =
                    getGrandTotal();


                if (paid < total) {

                    await showBillingAlert(
                        "The amount paid is not enough to complete this payment.",
                        "warning",
                        "Insufficient Payment"
                    );

                    return;

                }


                const confirmed =
                    await showBillingConfirm(
                        "Are you sure you want to confirm this payment?",
                        "Confirm Payment"
                    );

                if (!confirmed) {
                    return;
                }


                try {

                    const response =
                        await fetch(
                            "../process/confirm_billing.php",
                            {

                                method:
                                    "POST",

                                headers: {

                                    "Content-Type":
                                        "application/x-www-form-urlencoded"

                                },

                                body:
                                    new URLSearchParams({

                                        billing_id:
                                            billingId,

                                        amount_paid:
                                            paid

                                    })

                            }
                        );


                    const result =
                        await response.json();


                    if (!result.success) {

                        await showBillingAlert(
                            result.message ||
                            "Unable to confirm payment.",
                            "error",
                            "Payment Failed"
                        );

                        return;

                    }


                    await showBillingAlert(
                        "Payment has been confirmed successfully.",
                        "success",
                        "Payment Successful"
                    );


                    window.location.href =
                        "billing.php";


                } catch (error) {

                    console.error(
                        error
                    );


                    await showBillingAlert(
                        "Something went wrong while confirming payment.",
                        "error",
                        "Payment Failed"
                    );

                }

            }
        );


        /* =========================================================
   PRINT BILLING RECEIPT
========================================================= */

const printReceiptBtn =
    document.getElementById(
        "printReceiptBtn"
    );


if (printReceiptBtn) {

    printReceiptBtn.addEventListener(
        "click",
        function () {

            window.print();

        }
    );

}



/* =========================================================
   DOWNLOAD BILLING STATEMENT
========================================================= */

const downloadBillingBtn =
    document.getElementById(
        "downloadBillingBtn"
    );


if (downloadBillingBtn) {

    downloadBillingBtn.addEventListener(
        "click",
        async function () {

            const button = this;

            const billingId =
                button.dataset.id;

            const billingNumber =
                button.dataset.number ||
                ("BILL-" + billingId);


            if (!billingId) {

                showBillingAlert(
                    "Billing ID is missing.",
                    "error",
                    "Download Error"
                );

                return;

            }


            try {

                button.disabled = true;

                button.innerHTML =
                    '<i class="fa-solid fa-spinner fa-spin"></i> Downloading...';


                /*
                 * Clone the current billing statement.
                 * Remove buttons/scripts so the downloaded
                 * document is a clean billing document.
                 */

                const documentClone =
                    document.documentElement.cloneNode(
                        true
                    );


                documentClone
                    .querySelectorAll(
                        "script"
                    )
                    .forEach(
                        script =>
                            script.remove()
                    );


                documentClone
                    .querySelectorAll(
                        ".billing-document-actions"
                    )
                    .forEach(
                        element =>
                            element.remove()
                    );


                documentClone
                    .querySelectorAll(
                        ".sidebar, .topbar, nav"
                    )
                    .forEach(
                        element =>
                            element.remove()
                    );


                /*
                 * Create downloadable HTML.
                 */

                const htmlContent =
                    "<!DOCTYPE html>\n" +
                    documentClone.outerHTML;


                const blob =
                    new Blob(
                        [htmlContent],
                        {
                            type:
                                "text/html;charset=utf-8"
                        }
                    );


                const url =
                    URL.createObjectURL(
                        blob
                    );


                const link =
                    document.createElement(
                        "a"
                    );


                link.href = url;


                link.download =
                    billingNumber +
                    "-Billing-Statement.html";


                document.body.appendChild(
                    link
                );


                link.click();


                link.remove();


                URL.revokeObjectURL(
                    url
                );


                /*
                 * Tell the server that the
                 * billing document was downloaded.
                 */

                const response =
                    await fetch(
                        "../process/mark_billing_downloaded.php",
                        {
                            method: "POST",

                            headers: {
                                "Content-Type":
                                    "application/x-www-form-urlencoded"
                            },

                            body:
                                new URLSearchParams({
                                    billing_id:
                                        billingId
                                })
                        }
                    );


                const result =
                    await response.json();


                if (!result.success) {

                    throw new Error(
                        result.message ||
                        "Unable to archive billing."
                    );

                }


                await showBillingAlert(

                    "Billing statement downloaded successfully and has been archived.",

                    "success",

                    "Download Complete"

                );


                /*
                 * Return to Billing.
                 */

                window.location.href =
                    "billing.php";


            } catch (error) {

                console.error(
                    "Billing download error:",
                    error
                );


                showBillingAlert(

                    error.message ||
                    "Something went wrong while downloading the billing statement.",

                    "error",

                    "Download Error"

                );


                button.disabled =
                    false;


                button.innerHTML =
                    '<i class="fa-solid fa-download"></i> Download';

            }

        }
    );

}

    }


);