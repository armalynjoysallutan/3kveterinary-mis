document.addEventListener("DOMContentLoaded", function () {

    const variableGroups =
        document.querySelectorAll(".variable-group");

    const variableDetails =
        document.getElementById("variableDetails");

    const defaultState =
        document.getElementById("defaultVariableState");

    const billingContent =
        document.getElementById("billingContent");

    const appointmentsContent =
        document.getElementById("appointmentsContent");

    const petRecordsContent =
        document.getElementById("petRecordsContent");

    const inventoryContent =
        document.getElementById("inventoryContent");


    /* ==========================================
       VARIABLE GROUP SELECTION
    ========================================== */

    variableGroups.forEach(function (group) {

        group.addEventListener("click", function () {

            variableGroups.forEach(function (item) {

                item.classList.remove("active");

            });


            group.classList.add("active");


            const selectedGroup =
                group.dataset.group;


            console.log(
                "Selected variable group:",
                selectedGroup
            );

            /* ======================================
             APPOINTMENTS
            ====================================== */

            if (selectedGroup === "appointments") {

                variableDetails.classList.remove(
                  "billing-selected"
                );

                defaultState.style.display = "none";

                if (billingContent) {
                    billingContent.style.display = "none";
                }

                if (petRecordsContent) {
                    petRecordsContent.style.display = "none";
                }

                if (inventoryContent) {
                    inventoryContent.style.display = "none";
                }

                if (appointmentsContent) {
                    appointmentsContent.style.display = "block";
                }

                return;
            }

            /* ======================================
   PET RECORDS
====================================== */

if (selectedGroup === "pet-records") {

    variableDetails.classList.remove(
        "billing-selected"
    );

    defaultState.style.display = "none";

    if (billingContent) {
        billingContent.style.display = "none";
    }

    if (appointmentsContent) {
        appointmentsContent.style.display = "none";
    }

    if (inventoryContent) {
        inventoryContent.style.display = "none";
    }

    if (petRecordsContent) {
        petRecordsContent.style.display = "block";
    }

    return;
}

            /* ======================================
               BILLING
            ====================================== */

            if (selectedGroup === "billing") {

                variableDetails.classList.add(
                    "billing-selected"
                );

                defaultState.style.display = "none";

                if (appointmentsContent) {
                    appointmentsContent.style.display = "none";
                }

                if (petRecordsContent) {
                    petRecordsContent.style.display = "none";
                }

                if (inventoryContent) {
                    inventoryContent.style.display = "none";
                }

                if (billingContent) {
                    billingContent.style.display = "block";
                }

                

                return;

            }



            /* ======================================
               INVENTORY
            ====================================== */

            if (selectedGroup === "inventory") {

                variableDetails.classList.add(
                    "billing-selected"
                );

                defaultState.style.display = "none";

                if (appointmentsContent) {
                    appointmentsContent.style.display = "none";
                }

                if (petRecordsContent) {
                    petRecordsContent.style.display = "none";
                }

                if (billingContent) {
                    billingContent.style.display = "none";
                }

                if (inventoryContent) {
                    inventoryContent.style.display = "block";
                }

                return;
            }


            /* ======================================
               OTHER GROUPS
            ====================================== */

            variableDetails.classList.remove(
                "billing-selected"
            );

            if(billingContent) {
                billingContent.style.display = "none";
            }

            if (appointmentsContent) {
                appointmentsContent.style.display = "none";
            }

            if (petRecordsContent) {
                petRecordsContent.style.display = "none";
            }

            if (inventoryContent) {
                inventoryContent.style.display = "none";
            }

            defaultState.style.display = "flex";
            

        });

    });


    /* ==========================================
   VARIABLE TABS
   BILLING + APPOINTMENTS
========================================== */

document.querySelectorAll(".billing-variable-content")
    .forEach(function (section) {

        const tabs =
            section.querySelectorAll(".variable-tab");

        const contents =
            section.querySelectorAll(".billing-tab-content");


        tabs.forEach(function (tab) {

            tab.addEventListener("click", function () {

                const target =
                    tab.dataset.tab;


                /* Remove active ONLY
                   from tabs in this section */
                tabs.forEach(function (item) {

                    item.classList.remove("active");

                });


                /* Hide contents ONLY
                   from this section */
                contents.forEach(function (content) {

                    content.classList.remove("active");

                });


                /* Activate clicked tab */
                tab.classList.add("active");


                /* Show selected content */
                const targetContent =
                    section.querySelector(
                        "#" + target
                    );


                if (targetContent) {

                    targetContent.classList.add("active");

                }

            });

        });

    });

    /* ==========================================
       ADD NEW
    ========================================== */

    const addVariableBtn =
        document.getElementById("addVariableBtn");


    if (addVariableBtn) {

        addVariableBtn.addEventListener(
            "click",
            function () {

                console.log(
                    "Add New variable clicked."
                );

            }
        );

    }

});

/* =========================================================
   SERVICES - ADD / EDIT UI
========================================================= */

document.addEventListener("DOMContentLoaded", function () {

    const servicesTab = document.getElementById("services");

    if (!servicesTab) {
        return;
    }

    const categories = [
        "Particulars",
        "Vaccination",
        "Deworming",
        "Laboratory",
        "Specialties"
    ];

    function formatAmount(value) {

        const number = parseFloat(value);

        if (Number.isNaN(number)) {
            return "0.00";
        }

        return number.toLocaleString("en-PH", {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function escapeHtml(value) {

        return String(value)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function createServiceModal(mode, row) {

        const existing =
            document.getElementById("serviceModalOverlay");

        if (existing) {
            existing.remove();
        }

        let currentCategory = "";
        let currentService = "";
        let currentPricing = "";
        let currentPrice = "";

        if (row) {

            const cells =
                row.querySelectorAll("td");

            currentCategory =
                cells[0]?.textContent.trim() || "";

            currentService =
                cells[1]?.textContent.trim() || "";

            currentPricing =
                cells[2]?.textContent.trim() || "";

            currentPrice =
                cells[3]?.textContent.trim() || "";

            if (
                currentPricing.toLowerCase() === "manual" ||
                currentPricing.toLowerCase() === "variable"
            ) {
                currentPricing = "Manual / Variable";
            }

            currentPrice = currentPrice
                .replace("₱", "")
                .replace(/,/g, "")
                .replace("+", "")
                .trim();

            if (currentPrice === "—") {
                currentPrice = "";
            }
        }

        const overlay =
            document.createElement("div");

        overlay.className =
            "service-modal-overlay";

        overlay.id =
            "serviceModalOverlay";

        overlay.innerHTML = `

            <div
                class="service-modal"
                role="dialog"
                aria-modal="true"
            >

                <div class="service-modal-header">

                    <div class="service-modal-title">

                        <div class="service-modal-title-icon">
                            <i class="fa-solid fa-paw"></i>
                        </div>

                        <div>

                            <h3>
                                ${
                                    mode === "edit"
                                        ? "Edit Service"
                                        : "Add Service"
                                }
                            </h3>

                            <p>
                                ${
                                    mode === "edit"
                                        ? "Update the selected veterinary service."
                                        : "Add a billable veterinary service."
                                }
                            </p>

                        </div>

                    </div>

                    <button
                        type="button"
                        class="service-modal-close"
                        id="closeServiceModal"
                    >
                        <i class="fa-solid fa-xmark"></i>
                    </button>

                </div>


                <form id="serviceForm">

                    <div class="service-modal-body">

                        <div class="service-form-group">

                            <label for="serviceCategory">
                                Service Category
                                <span class="required">*</span>
                            </label>

                            <select
                                id="serviceCategory"
                                class="service-form-select"
                                required
                            >

                                <option value="">
                                    Select category
                                </option>

                                ${categories.map(category => `
                                    <option
                                        value="${category}"
                                        ${
                                            category === currentCategory
                                                ? "selected"
                                                : ""
                                        }
                                    >
                                        ${category}
                                    </option>
                                `).join("")}

                            </select>

                        </div>


                        <div class="service-form-group">

                            <label for="serviceName">
                                Service Name
                                <span class="required">*</span>
                            </label>

                            <input
                                type="text"
                                id="serviceName"
                                class="service-form-input"
                                placeholder="e.g. Consultation"
                                value="${escapeHtml(currentService)}"
                                required
                            >

                        </div>


                        <div class="service-form-group">

                            <label for="servicePricing">
                                Pricing Type
                                <span class="required">*</span>
                            </label>

                            <select
                                id="servicePricing"
                                class="service-form-select"
                                required
                            >

                                <option value="">
                                    Select pricing type
                                </option>

                                <option
                                    value="Fixed"
                                    ${
                                        currentPricing === "Fixed"
                                            ? "selected"
                                            : ""
                                    }
                                >
                                    Fixed
                                </option>

                                <option
                                    value="Weight-Based"
                                    ${
                                        currentPricing === "Weight-Based"
                                            ? "selected"
                                            : ""
                                    }
                                >
                                    Weight-Based
                                </option>

                                <option
                                    value="Manual / Variable"
                                    ${
                                        currentPricing === "Manual / Variable"
                                            ? "selected"
                                            : ""
                                    }
                                >
                                    Manual / Variable
                                </option>

                            </select>

                            <p class="service-form-help">
                                Fixed uses one set price.
                                Weight-Based uses the Pricing Rules tab.
                                Manual / Variable is entered during billing.
                            </p>

                        </div>


                        <div
                            class="service-form-group"
                            id="servicePriceField"
                        >

                            <label for="servicePrice">
                                Price
                                <span class="required">*</span>
                            </label>

                            <div class="service-price-wrap">

                                <span class="service-price-symbol">
                                    ₱
                                </span>

                                <input
                                    type="number"
                                    id="servicePrice"
                                    class="service-form-input service-price-input"
                                    min="0"
                                    step="0.01"
                                    placeholder="0.00"
                                    value="${currentPrice}"
                                >

                            </div>

                            <p class="service-form-help">
                                Enter the fixed price for this service.
                            </p>

                        </div>


                        <div class="service-form-group">

                            <label for="serviceStatus">
                                Status
                            </label>

                            <select
                                id="serviceStatus"
                                class="service-form-select"
                            >

                                <option value="Active">
                                    Active
                                </option>

                                <option value="Inactive">
                                    Inactive
                                </option>

                            </select>

                        </div>


                        <div
                            class="service-modal-message"
                            id="serviceModalMessage"
                        ></div>

                    </div>


                    <div class="service-modal-footer">

                        <button
                            type="button"
                            class="service-modal-btn cancel"
                            id="cancelServiceModal"
                        >
                            Cancel
                        </button>

                        <button
                            type="submit"
                            class="service-modal-btn save"
                        >
                            ${
                                mode === "edit"
                                    ? "Save Changes"
                                    : "Save Service"
                            }
                        </button>

                    </div>

                </form>

            </div>
        `;

        document.body.appendChild(overlay);


        const pricingSelect =
            document.getElementById("servicePricing");

        const priceField =
            document.getElementById("servicePriceField");

        const priceInput =
            document.getElementById("servicePrice");


        function updatePriceVisibility() {

            if (pricingSelect.value === "Fixed") {

                priceField.style.display = "block";

                priceInput.required = true;

            } else {

                priceField.style.display = "none";

                priceInput.required = false;

            }
        }


        pricingSelect.addEventListener(
            "change",
            updatePriceVisibility
        );

        updatePriceVisibility();


        function closeModal() {
            overlay.remove();
        }


        document
            .getElementById("closeServiceModal")
            .addEventListener(
                "click",
                closeModal
            );


        document
            .getElementById("cancelServiceModal")
            .addEventListener(
                "click",
                closeModal
            );


        overlay.addEventListener(
            "click",
            function (event) {

                if (event.target === overlay) {
                    closeModal();
                }

            }
        );


        document
            .getElementById("serviceForm")
            .addEventListener(
                "submit",
                function (event) {

                    event.preventDefault();

                    const category =
                        document
                            .getElementById("serviceCategory")
                            .value
                            .trim();

                    const service =
                        document
                            .getElementById("serviceName")
                            .value
                            .trim();

                    const pricing =
                        document
                            .getElementById("servicePricing")
                            .value;

                    const price =
                        document
                            .getElementById("servicePrice")
                            .value
                            .trim();

                    const status =
                        document
                            .getElementById("serviceStatus")
                            .value;


                    const message =
                        document
                            .getElementById("serviceModalMessage");


                    message.style.display = "none";


                    if (
                        !category ||
                        !service ||
                        !pricing
                    ) {

                        message.textContent =
                            "Please complete all required fields.";

                        message.style.display =
                            "block";

                        return;
                    }


                    if (
                        pricing === "Fixed" &&
                        !price
                    ) {

                        message.textContent =
                            "Please enter the fixed price.";

                        message.style.display =
                            "block";

                        return;
                    }


                    /*
|--------------------------------------------------------------------------
| SAVE SERVICE TO DATABASE
|--------------------------------------------------------------------------
*/

if (mode === "edit" && row) {

    const serviceId =
        row.dataset.serviceId;

    if (!serviceId) {

        message.textContent =
            "Service ID not found.";

        message.style.display =
            "block";

        return;
    }


    const saveButton =
        document.querySelector(
            "#serviceForm .service-modal-btn.save"
        );


    saveButton.disabled = true;

    saveButton.textContent =
        "Saving...";


    const formData =
        new FormData();


    formData.append(
        "service_id",
        serviceId
    );


    formData.append(
        "category_name",
        category
    );


    formData.append(
        "service_name",
        service
    );


    formData.append(
        "pricing_type",
        pricing
    );


    formData.append(
        "fixed_price",
        pricing === "Fixed"
            ? price
            : ""
    );


    formData.append(
        "status",
        status
    );


    fetch(
        "update_service.php",
        {
            method: "POST",
            body: formData
        }
    )

    .then(function (response) {

        return response.json();

    })

    .then(function (data) {

        if (!data.success) {

            throw new Error(
                data.message ||
                "Failed to update service."
            );

        }


        alert(
            data.message
        );


        closeModal();


        window.location.reload();

    })

    .catch(function (error) {

        message.textContent =
            error.message;

        message.style.display =
            "block";


        saveButton.disabled =
            false;

        saveButton.textContent =
            "Save Changes";

    });


    return;
}


/*
|--------------------------------------------------------------------------
| ADD SERVICE
|--------------------------------------------------------------------------
*/

const saveButton =
    document.querySelector(
        "#serviceForm .service-modal-btn.save"
    );

saveButton.disabled = true;

saveButton.textContent =
    "Saving...";


const formData =
    new FormData();

formData.append(
    "category_name",
    category
);

formData.append(
    "service_name",
    service
);

formData.append(
    "pricing_type",
    pricing
);

formData.append(
    "fixed_price",
    pricing === "Fixed"
        ? price
        : ""
);

formData.append(
    "status",
    status
);


fetch(
    "save_service.php",
    {
        method: "POST",
        body: formData
    }
)

.then(function (response) {

    return response.json();

})

.then(function (data) {

    if (!data.success) {

        throw new Error(
            data.message ||
            "Failed to save service."
        );

    }


    alert(
        data.message
    );


    closeModal();


    /*
    |--------------------------------------------------------------------------
    | Reload page so PHP loads the newly saved service
    |--------------------------------------------------------------------------
    */

    window.location.reload();

})

.catch(function (error) {

    message.textContent =
        error.message;

    message.style.display =
        "block";


    saveButton.disabled =
        false;

    saveButton.textContent =
        "Save Service";

});

                }
            );


        overlay.classList.add("show");

    }


    /*
     * + ADD SERVICE
     */

    servicesTab
        .querySelectorAll(".add-variable-btn")
        .forEach(function (button) {

            button.addEventListener(
                "click",
                function () {

                    createServiceModal(
                        "add",
                        null
                    );

                }
            );

        });


    /*
     * EDIT SERVICE
     */

    function attachEditButtons() {

        const rows =
            servicesTab.querySelectorAll(
                ".variable-table tbody tr"
            );


        rows.forEach(function (row) {

            const editButton =
                row.querySelector(
                    ".icon-action.edit"
                );


            if (
                editButton &&
                !editButton.dataset.bound
            ) {

                editButton.dataset.bound =
                    "true";


                editButton.addEventListener(
                    "click",
                    function () {

                        createServiceModal(
                            "edit",
                            row
                        );

                    }
                );

            }

        });

    }


    attachEditButtons();


    /*
     * BIND EDIT BUTTONS
     * FOR NEWLY ADDED ROWS
     */

    const serviceTableBody =
        servicesTab.querySelector("tbody");


    if (serviceTableBody) {

        const observer =
            new MutationObserver(
                function () {

                    attachEditButtons();

                }
            );


        observer.observe(
            serviceTableBody,
            {
                childList: true
            }
        );

    }

});
/* =========================================================
   PRICING RULES - ADD
========================================================= */

document.addEventListener("DOMContentLoaded", function () {

    const addPricingRuleBtn =
        document.getElementById("addPricingRuleBtn");

    if (!addPricingRuleBtn) {
        return;
    }


    function escapeHtml(value) {

        return String(value)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");

    }


    function formatAmount(value) {

        const number = parseFloat(value);

        if (Number.isNaN(number)) {
            return "0.00";
        }

        return number.toLocaleString("en-PH", {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });

    }


    function createPricingRuleModal() {

        const existing =
            document.getElementById(
                "pricingRuleModalOverlay"
            );

        if (existing) {
            existing.remove();
        }


        const overlay =
            document.createElement("div");

        overlay.className =
            "service-modal-overlay";

        overlay.id =
            "pricingRuleModalOverlay";


        overlay.innerHTML = `

            <div
                class="service-modal"
                role="dialog"
                aria-modal="true"
            >

                <div class="service-modal-header">

                    <div class="service-modal-title">

                        <div class="service-modal-title-icon">
                            <i class="fa-solid fa-weight-scale"></i>
                        </div>

                        <div>

                            <h3>
                                Add Pricing Rule
                            </h3>

                            <p>
                                Configure weight-based pricing
                                for a veterinary service.
                            </p>

                        </div>

                    </div>


                    <button
                        type="button"
                        class="service-modal-close"
                        id="closePricingRuleModal"
                    >
                        <i class="fa-solid fa-xmark"></i>
                    </button>

                </div>


                <form id="pricingRuleForm">

                    <div class="service-modal-body">


                        <!-- SERVICE -->

                        <div class="service-form-group">

                            <label for="pricingRuleService">

                                Service

                                <span class="required">
                                    *
                                </span>

                            </label>


                            <select
                                id="pricingRuleService"
                                class="service-form-select"
                                required
                            >

                                <option value="">
                                    Loading services...
                                </option>

                            </select>


                            <p class="service-form-help">
                                Only Weight-Based services
                                can have pricing rules.
                            </p>

                        </div>


                        <!-- BASE MIN -->

                        <div class="service-form-group">

                            <label for="baseMinWeight">

                                Starting Weight

                                <span class="required">
                                    *
                                </span>

                            </label>


                            <div class="pricing-input-row">

                                <input
                                    type="number"
                                    id="baseMinWeight"
                                    class="service-form-input"
                                    min="0"
                                    step="0.1"
                                    value="0"
                                    required
                                >

                                <span class="pricing-unit">
                                    kg
                                </span>

                            </div>

                        </div>


                        <!-- BASE MAX -->

                        <div class="service-form-group">

                            <label for="baseMaxWeight">

                                Base Maximum Weight

                                <span class="required">
                                    *
                                </span>

                            </label>


                            <div class="pricing-input-row">

                                <input
                                    type="number"
                                    id="baseMaxWeight"
                                    class="service-form-input"
                                    min="0"
                                    step="0.1"
                                    value="5"
                                    required
                                >

                                <span class="pricing-unit">
                                    kg
                                </span>

                            </div>


                            <p class="service-form-help">
                                Example: 0–5 kg.
                            </p>

                        </div>


                        <!-- BASE PRICE -->

                        <div class="service-form-group">

                            <label for="basePrice">

                                Base Price

                                <span class="required">
                                    *
                                </span>

                            </label>


                            <div class="service-price-wrap">

                                <span class="service-price-symbol">
                                    ₱
                                </span>

                                <input
                                    type="number"
                                    id="basePrice"
                                    class="service-form-input service-price-input"
                                    min="0"
                                    step="0.01"
                                    placeholder="0.00"
                                    required
                                >

                            </div>

                        </div>


                        <!-- WEIGHT INCREMENT -->

                        <div class="service-form-group">

                            <label for="weightIncrement">

                                Every Additional Weight

                                <span class="required">
                                    *
                                </span>

                            </label>


                            <div class="pricing-input-row">

                                <input
                                    type="number"
                                    id="weightIncrement"
                                    class="service-form-input"
                                    min="0.1"
                                    step="0.1"
                                    value="5"
                                    required
                                >

                                <span class="pricing-unit">
                                    kg
                                </span>

                            </div>


                            <p class="service-form-help">
                                Example: every additional 5 kg.
                            </p>

                        </div>


                        <!-- PRICE INCREMENT -->

                        <div class="service-form-group">

                            <label for="priceIncrement">

                                Price Increase

                                <span class="required">
                                    *
                                </span>

                            </label>


                            <div class="service-price-wrap">

                                <span class="service-price-symbol">
                                    ₱
                                </span>

                                <input
                                    type="number"
                                    id="priceIncrement"
                                    class="service-form-input service-price-input"
                                    min="0"
                                    step="0.01"
                                    placeholder="0.00"
                                    required
                                >

                            </div>


                            <p class="service-form-help">
                                Amount added for every additional
                                weight increment.
                            </p>

                        </div>


                        <div
                            class="service-modal-message"
                            id="pricingRuleModalMessage"
                        ></div>


                    </div>


                    <div class="service-modal-footer">

                        <button
                            type="button"
                            class="service-modal-btn cancel"
                            id="cancelPricingRuleModal"
                        >
                            Cancel
                        </button>


                        <button
                            type="submit"
                            class="service-modal-btn save"
                        >
                            Save Pricing Rule
                        </button>

                    </div>

                </form>

            </div>

        `;


        document.body.appendChild(overlay);


        overlay.classList.add("show");


        const serviceSelect =
            document.getElementById(
                "pricingRuleService"
            );


        const message =
            document.getElementById(
                "pricingRuleModalMessage"
            );


        /*
        |--------------------------------------------------------------------------
        | LOAD WEIGHT-BASED SERVICES
        |--------------------------------------------------------------------------
        */

        fetch("get_weight_based_services.php")

            .then(function (response) {
                return response.json();
            })

            .then(function (data) {

                serviceSelect.innerHTML =
                    `<option value="">
                        Select service
                    </option>`;


                if (
                    !data.success ||
                    !data.services ||
                    data.services.length === 0
                ) {

                    serviceSelect.innerHTML =
                        `<option value="">
                            No available Weight-Based services
                        </option>`;

                    return;
                }


                data.services.forEach(
                    function (service) {

                        const option =
                            document.createElement(
                                "option"
                            );

                        option.value =
                            service.service_id;

                        option.textContent =
                            service.service_name;

                        serviceSelect.appendChild(
                            option
                        );

                    }
                );

            })

            .catch(function () {

                serviceSelect.innerHTML =
                    `<option value="">
                        Unable to load services
                    </option>`;

            });


        /*
        |--------------------------------------------------------------------------
        | CLOSE MODAL
        |--------------------------------------------------------------------------
        */

        function closeModal() {
            overlay.remove();
        }


        document
            .getElementById(
                "closePricingRuleModal"
            )
            .addEventListener(
                "click",
                closeModal
            );


        document
            .getElementById(
                "cancelPricingRuleModal"
            )
            .addEventListener(
                "click",
                closeModal
            );


        overlay.addEventListener(
            "click",
            function (event) {

                if (
                    event.target === overlay
                ) {
                    closeModal();
                }

            }
        );


        /*
        |--------------------------------------------------------------------------
        | SAVE
        |--------------------------------------------------------------------------
        */

        document
            .getElementById(
                "pricingRuleForm"
            )
            .addEventListener(
                "submit",
                function (event) {

                    event.preventDefault();


                    message.style.display =
                        "none";


                    const serviceId =
                        serviceSelect.value;


                    const baseMinWeight =
                        document
                            .getElementById(
                                "baseMinWeight"
                            )
                            .value;


                    const baseMaxWeight =
                        document
                            .getElementById(
                                "baseMaxWeight"
                            )
                            .value;


                    const basePrice =
                        document
                            .getElementById(
                                "basePrice"
                            )
                            .value;


                    const weightIncrement =
                        document
                            .getElementById(
                                "weightIncrement"
                            )
                            .value;


                    const priceIncrement =
                        document
                            .getElementById(
                                "priceIncrement"
                            )
                            .value;


                    if (!serviceId) {

                        message.textContent =
                            "Please select a service.";

                        message.style.display =
                            "block";

                        return;
                    }


                    if (
                        parseFloat(baseMaxWeight) <=
                        parseFloat(baseMinWeight)
                    ) {

                        message.textContent =
                            "Base maximum weight must be greater than the starting weight.";

                        message.style.display =
                            "block";

                        return;
                    }


                    if (
                        parseFloat(basePrice) < 0 ||
                        parseFloat(weightIncrement) <= 0 ||
                        parseFloat(priceIncrement) < 0
                    ) {

                        message.textContent =
                            "Please enter valid pricing values.";

                        message.style.display =
                            "block";

                        return;
                    }


                    const formData =
                        new FormData();


                    formData.append(
                        "service_id",
                        serviceId
                    );

                    formData.append(
                        "base_min_weight",
                        baseMinWeight
                    );

                    formData.append(
                        "base_max_weight",
                        baseMaxWeight
                    );

                    formData.append(
                        "base_price",
                        basePrice
                    );

                    formData.append(
                        "weight_increment",
                        weightIncrement
                    );

                    formData.append(
                        "price_increment",
                        priceIncrement
                    );


                    const saveButton =
                        document.querySelector(
                            "#pricingRuleForm .service-modal-btn.save"
                        );


                    saveButton.disabled = true;

                    saveButton.textContent =
                        "Saving...";


                    fetch(
                        "save_pricing_rule.php",
                        {
                            method: "POST",
                            body: formData
                        }
                    )

                    .then(function (response) {
                        return response.json();
                    })

                    .then(function (data) {

                        if (!data.success) {

                            throw new Error(
                                data.message ||
                                "Failed to save pricing rule."
                            );

                        }


                        alert(
                            data.message
                        );


                        closeModal();


                        /*
                        |--------------------------------------------------
                        | Refresh page so PHP reloads the database records
                        |--------------------------------------------------
                        */

                        window.location.reload();

                    })

                    .catch(function (error) {

                        message.textContent =
                            error.message;

                        message.style.display =
                            "block";


                        saveButton.disabled =
                            false;

                        saveButton.textContent =
                            "Save Pricing Rule";

                    });

                }
            );

    }


    addPricingRuleBtn.addEventListener(
        "click",
        createPricingRuleModal
    );

});

/* =========================================================
   PRICING RULES - EDIT
========================================================= */

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const editButtons =
            document.querySelectorAll(
                ".pricing-rule-edit"
            );


        if (!editButtons.length) {
            return;
        }


        function createEditPricingRuleModal(
            rule
        ) {

            const existing =
                document.getElementById(
                    "pricingRuleEditModalOverlay"
                );


            if (existing) {
                existing.remove();
            }


            const overlay =
                document.createElement("div");


            overlay.className =
                "service-modal-overlay";


            overlay.id =
                "pricingRuleEditModalOverlay";


            overlay.innerHTML = `

                <div
                    class="service-modal"
                    role="dialog"
                    aria-modal="true"
                >

                    <div class="service-modal-header">

                        <div class="service-modal-title">

                            <div
                                class="service-modal-title-icon"
                            >
                                <i
                                    class="fa-solid fa-pen"
                                ></i>
                            </div>

                            <div>

                                <h3>
                                    Edit Pricing Rule
                                </h3>

                                <p>
                                    Update weight-based
                                    pricing for this service.
                                </p>

                            </div>

                        </div>


                        <button
                            type="button"
                            class="service-modal-close"
                            id="closeEditPricingRuleModal"
                        >
                            <i
                                class="fa-solid fa-xmark"
                            ></i>
                        </button>

                    </div>


                    <form
                        id="editPricingRuleForm"
                    >

                        <div class="service-modal-body">


                            <!-- SERVICE -->

                            <div class="service-form-group">

                                <label>
                                    Service
                                </label>

                                <input
                                    type="text"
                                    class="service-form-input"
                                    value="${rule.service_name}"
                                    readonly
                                >

                                <p
                                    class="service-form-help"
                                >
                                    Service cannot be changed
                                    once a pricing rule exists.
                                </p>

                            </div>


                            <!-- STARTING WEIGHT -->

                            <div class="service-form-group">

                                <label
                                    for="editBaseMinWeight"
                                >
                                    Starting Weight
                                    <span class="required">
                                        *
                                    </span>
                                </label>


                                <div
                                    class="pricing-input-row"
                                >

                                    <input
                                        type="number"
                                        id="editBaseMinWeight"
                                        class="service-form-input"
                                        min="0"
                                        step="0.1"
                                        value="${rule.base_min_weight}"
                                        required
                                    >

                                    <span
                                        class="pricing-unit"
                                    >
                                        kg
                                    </span>

                                </div>

                            </div>


                            <!-- MAXIMUM WEIGHT -->

                            <div class="service-form-group">

                                <label
                                    for="editBaseMaxWeight"
                                >
                                    Base Maximum Weight
                                    <span class="required">
                                        *
                                    </span>
                                </label>


                                <div
                                    class="pricing-input-row"
                                >

                                    <input
                                        type="number"
                                        id="editBaseMaxWeight"
                                        class="service-form-input"
                                        min="0"
                                        step="0.1"
                                        value="${rule.base_max_weight}"
                                        required
                                    >

                                    <span
                                        class="pricing-unit"
                                    >
                                        kg
                                    </span>

                                </div>


                                <p
                                    class="service-form-help"
                                >
                                    Example: 0–5 kg.
                                </p>

                            </div>


                            <!-- BASE PRICE -->

                            <div class="service-form-group">

                                <label
                                    for="editBasePrice"
                                >
                                    Base Price
                                    <span class="required">
                                        *
                                    </span>
                                </label>


                                <div
                                    class="service-price-wrap"
                                >

                                    <span
                                        class="service-price-symbol"
                                    >
                                        ₱
                                    </span>

                                    <input
                                        type="number"
                                        id="editBasePrice"
                                        class="service-form-input service-price-input"
                                        min="0"
                                        step="0.01"
                                        value="${rule.base_price}"
                                        required
                                    >

                                </div>

                            </div>


                            <!-- WEIGHT INCREMENT -->

                            <div class="service-form-group">

                                <label
                                    for="editWeightIncrement"
                                >
                                    Every Additional Weight
                                    <span class="required">
                                        *
                                    </span>
                                </label>


                                <div
                                    class="pricing-input-row"
                                >

                                    <input
                                        type="number"
                                        id="editWeightIncrement"
                                        class="service-form-input"
                                        min="0.1"
                                        step="0.1"
                                        value="${rule.weight_increment}"
                                        required
                                    >

                                    <span
                                        class="pricing-unit"
                                    >
                                        kg
                                    </span>

                                </div>


                                <p
                                    class="service-form-help"
                                >
                                    Example: every additional
                                    5 kg.
                                </p>

                            </div>


                            <!-- PRICE INCREMENT -->

                            <div class="service-form-group">

                                <label
                                    for="editPriceIncrement"
                                >
                                    Price Increase
                                    <span class="required">
                                        *
                                    </span>
                                </label>


                                <div
                                    class="service-price-wrap"
                                >

                                    <span
                                        class="service-price-symbol"
                                    >
                                        ₱
                                    </span>

                                    <input
                                        type="number"
                                        id="editPriceIncrement"
                                        class="service-form-input service-price-input"
                                        min="0"
                                        step="0.01"
                                        value="${rule.price_increment}"
                                        required
                                    >

                                </div>


                                <p
                                    class="service-form-help"
                                >
                                    Amount added for every
                                    additional weight increment.
                                </p>

                            </div>


                            <div
                                class="service-modal-message"
                                id="editPricingRuleMessage"
                            ></div>


                        </div>


                        <div class="service-modal-footer">

                            <button
                                type="button"
                                class="service-modal-btn cancel"
                                id="cancelEditPricingRuleModal"
                            >
                                Cancel
                            </button>


                            <button
                                type="submit"
                                class="service-modal-btn save"
                            >
                                Save Changes
                            </button>

                        </div>

                    </form>

                </div>

            `;


            document.body.appendChild(
                overlay
            );


            overlay.classList.add("show");


            /*
            |--------------------------------------------------------------------------
            | CLOSE
            |--------------------------------------------------------------------------
            */

            function closeModal() {
                overlay.remove();
            }


            document
                .getElementById(
                    "closeEditPricingRuleModal"
                )
                .addEventListener(
                    "click",
                    closeModal
                );


            document
                .getElementById(
                    "cancelEditPricingRuleModal"
                )
                .addEventListener(
                    "click",
                    closeModal
                );


            /*
            |--------------------------------------------------------------------------
            | SUBMIT
            |--------------------------------------------------------------------------
            */

            document
                .getElementById(
                    "editPricingRuleForm"
                )
                .addEventListener(
                    "submit",
                    function (event) {

                        event.preventDefault();


                        const message =
                            document.getElementById(
                                "editPricingRuleMessage"
                            );


                        const baseMinWeight =
                            document.getElementById(
                                "editBaseMinWeight"
                            ).value;


                        const baseMaxWeight =
                            document.getElementById(
                                "editBaseMaxWeight"
                            ).value;


                        const basePrice =
                            document.getElementById(
                                "editBasePrice"
                            ).value;


                        const weightIncrement =
                            document.getElementById(
                                "editWeightIncrement"
                            ).value;


                        const priceIncrement =
                            document.getElementById(
                                "editPriceIncrement"
                            ).value;


                        if (
                            parseFloat(
                                baseMaxWeight
                            ) <=
                            parseFloat(
                                baseMinWeight
                            )
                        ) {

                            message.textContent =
                                "Base maximum weight must be greater than starting weight.";

                            message.style.display =
                                "block";

                            return;
                        }


                        const formData =
                            new FormData();


                        formData.append(
                            "pricing_rule_id",
                            rule.pricing_rule_id
                        );


                        formData.append(
                            "base_min_weight",
                            baseMinWeight
                        );


                        formData.append(
                            "base_max_weight",
                            baseMaxWeight
                        );


                        formData.append(
                            "base_price",
                            basePrice
                        );


                        formData.append(
                            "weight_increment",
                            weightIncrement
                        );


                        formData.append(
                            "price_increment",
                            priceIncrement
                        );


                        const saveButton =
                            document.querySelector(
                                "#editPricingRuleForm .service-modal-btn.save"
                            );


                        saveButton.disabled =
                            true;


                        saveButton.textContent =
                            "Saving...";


                        fetch(
                            "update_pricing_rule.php",
                            {
                                method: "POST",
                                body: formData
                            }
                        )

                        .then(
                            function (response) {
                                return response.json();
                            }
                        )

                        .then(
                            function (data) {

                                if (
                                    !data.success
                                ) {

                                    throw new Error(
                                        data.message
                                    );

                                }


                                alert(
                                    data.message
                                );


                                closeModal();


                                window.location.reload();

                            }
                        )

                        .catch(
                            function (error) {

                                message.textContent =
                                    error.message;


                                message.style.display =
                                    "block";


                                saveButton.disabled =
                                    false;


                                saveButton.textContent =
                                    "Save Changes";

                            }
                        );

                    }
                );

        }


        /*
        |--------------------------------------------------------------------------
        | EDIT BUTTONS
        |--------------------------------------------------------------------------
        */

        editButtons.forEach(
            function (button) {

                button.addEventListener(
                    "click",
                    function () {

                        const row =
                            button.closest("tr");


                        if (!row) {
                            return;
                        }


                        const ruleId =
                            row.dataset.ruleId;


                        if (!ruleId) {

                            alert(
                                "Pricing rule ID not found."
                            );

                            return;
                        }


                        fetch(
                            "get_pricing_rule.php?id="
                            + encodeURIComponent(
                                ruleId
                            )
                        )

                        .then(
                            function (response) {
                                return response.json();
                            }
                        )

                        .then(
                            function (data) {

                                if (
                                    !data.success
                                ) {

                                    throw new Error(
                                        data.message
                                    );

                                }


                                createEditPricingRuleModal(
                                    data.rule
                                );

                            }
                        )

                        .catch(
                            function (error) {

                                alert(
                                    error.message
                                );

                            }
                        );

                    }
                );

            }
        );

    }
);

/* =========================================================
   MEDICATIONS - ADD
========================================================= */

document.addEventListener("DOMContentLoaded", function () {

    const addMedicationButton =
        document.getElementById("addMedicationBtn");

    if (!addMedicationButton) {
        return;
    }

    function createMedicationModal() {

        const existing =
            document.getElementById("medicationModalOverlay");

        if (existing) {
            existing.remove();
        }

        const overlay =
            document.createElement("div");

        overlay.className =
            "service-modal-overlay";

        overlay.id =
            "medicationModalOverlay";

        overlay.innerHTML = `

            <div
                class="service-modal"
                role="dialog"
                aria-modal="true"
            >

                <div class="service-modal-header">

                    <div class="service-modal-title">

                        <div class="service-modal-title-icon">
                            <i class="fa-solid fa-pills"></i>
                        </div>

                        <div>

                            <h3>
                                Add Medication
                            </h3>

                            <p>
                                Add a medication available for billing.
                            </p>

                        </div>

                    </div>

                    <button
                        type="button"
                        class="service-modal-close"
                        id="closeMedicationModal"
                    >
                        <i class="fa-solid fa-xmark"></i>
                    </button>

                </div>


                <form id="medicationForm">

                    <div class="service-modal-body">

                        <div class="service-form-group">

                            <label for="medicationName">
                                Medication Name
                                <span class="required">*</span>
                            </label>

                            <input
                                type="text"
                                id="medicationName"
                                class="service-form-input"
                                placeholder="e.g. Amoxicillin"
                                required
                            >

                        </div>


                        <div class="service-form-group">

                            <label for="medicationPrice">
                                Unit Price (₱)
                                <span class="required">*</span>
                            </label>

                            <input
                                type="number"
                                id="medicationPrice"
                                class="service-form-input"
                                min="0"
                                step="0.01"
                                placeholder="0.00"
                                required
                            >

                            <p class="service-form-help">
                                Enter the price per unit of medication.
                            </p>

                        </div>


                        <div class="service-form-group">

                            <label for="medicationStatus">
                                Status
                            </label>

                            <select
                                id="medicationStatus"
                                class="service-form-select"
                            >

                                <option value="Active">
                                    Active
                                </option>

                                <option value="Inactive">
                                    Inactive
                                </option>

                            </select>

                        </div>


                        <div
                            class="service-modal-message"
                            id="medicationModalMessage"
                        ></div>

                    </div>


                    <div class="service-modal-footer">

                        <button
                            type="button"
                            class="service-modal-btn cancel"
                            id="cancelMedicationModal"
                        >
                            Cancel
                        </button>

                        <button
                            type="submit"
                            class="service-modal-btn save"
                        >
                            Save Medication
                        </button>

                    </div>

                </form>

            </div>

        `;

        document.body.appendChild(overlay);

        const closeButton =
            document.getElementById("closeMedicationModal");

        const cancelButton =
            document.getElementById("cancelMedicationModal");

        function closeMedicationModal() {

            overlay.classList.remove("show");

            setTimeout(function () {
                overlay.remove();
            }, 200);

        }

        closeButton.addEventListener(
            "click",
            closeMedicationModal
        );

        cancelButton.addEventListener(
            "click",
            closeMedicationModal
        );

        overlay.addEventListener(
            "click",
            function (event) {

                if (event.target === overlay) {
                    closeMedicationModal();
                }

            }
        );

        document
    .getElementById("medicationForm")
    .addEventListener(
        "submit",
        function (event) {

            event.preventDefault();


            const nameInput =
                document.getElementById(
                    "medicationName"
                );

            const priceInput =
                document.getElementById(
                    "medicationPrice"
                );

            const statusInput =
                document.getElementById(
                    "medicationStatus"
                );

            const message =
                document.getElementById(
                    "medicationModalMessage"
                );

            const saveButton =
                document.querySelector(
                    "#medicationForm .service-modal-btn.save"
                );


            const medicationName =
                nameInput.value.trim();

            const unitPrice =
                priceInput.value.trim();

            const status =
                statusInput.value;


            message.style.display =
                "none";


            /*
            |--------------------------------------------------------------------------
            | VALIDATION
            |--------------------------------------------------------------------------
            */

            if (!medicationName) {

                message.textContent =
                    "Please enter the medication name.";

                message.style.display =
                    "block";

                nameInput.focus();

                return;
            }


            if (
                unitPrice === "" ||
                parseFloat(unitPrice) < 0
            ) {

                message.textContent =
                    "Please enter a valid unit price.";

                message.style.display =
                    "block";

                priceInput.focus();

                return;
            }


            /*
            |--------------------------------------------------------------------------
            | PREVENT DOUBLE CLICK
            |--------------------------------------------------------------------------
            */

            saveButton.disabled =
                true;

            saveButton.textContent =
                "Saving...";


            /*
            |--------------------------------------------------------------------------
            | FORM DATA
            |--------------------------------------------------------------------------
            */

            const formData =
                new FormData();


            formData.append(
                "medication_name",
                medicationName
            );


            formData.append(
                "unit_price",
                unitPrice
            );


            formData.append(
                "status",
                status
            );


            /*
            |--------------------------------------------------------------------------
            | SAVE TO DATABASE
            |--------------------------------------------------------------------------
            */

            fetch(
                "save_medication.php",
                {
                    method: "POST",
                    body: formData
                }
            )

            .then(
                function (response) {

                    return response.json();

                }
            )

            .then(
                function (data) {

                    if (!data.success) {

                        throw new Error(
                            data.message ||
                            "Failed to save medication."
                        );

                    }


                    alert(
                        data.message
                    );


                    overlay.remove();


                    /*
                    |--------------------------------------------------------------------------
                    | RELOAD
                    |--------------------------------------------------------------------------
                    */

                    window.location.reload();

                }
            )

            .catch(
                function (error) {

                    message.textContent =
                        error.message;

                    message.style.display =
                        "block";


                    saveButton.disabled =
                        false;

                    saveButton.textContent =
                        "Save Medication";

                }
            );

        }
    );

        requestAnimationFrame(function () {
            overlay.classList.add("show");
        });

    }

    addMedicationButton.addEventListener(
        "click",
        function () {
            createMedicationModal();
        }
    );

});

/* =========================================================
   MEDICATIONS - EDIT
========================================================= */

document.addEventListener("DOMContentLoaded", function () {

    const medicationsTab =
        document.getElementById("medications");

    if (!medicationsTab) {
        return;
    }


    /*
    |--------------------------------------------------------------------------
    | EDIT MEDICATION MODAL
    |--------------------------------------------------------------------------
    */

    function createEditMedicationModal(row) {

        const existing =
            document.getElementById(
                "medicationEditModalOverlay"
            );

        if (existing) {
            existing.remove();
        }


        const medicationId =
            row.dataset.medicationId;


        if (!medicationId) {

            alert(
                "Medication ID not found."
            );

            return;
        }


        const cells =
            row.querySelectorAll("td");


        const currentName =
            cells[0]?.textContent
                .trim() || "";


        let currentPrice =
            cells[1]?.textContent
                .trim() || "";


        const currentStatus =
            cells[2]?.textContent
                .trim() || "Active";


        /*
        |--------------------------------------------------------------------------
        | CLEAN PRICE
        |--------------------------------------------------------------------------
        */

        currentPrice =
            currentPrice
                .replace("₱", "")
                .replace(/,/g, "")
                .trim();


        if (
            currentPrice === "—"
        ) {
            currentPrice = "";
        }


        /*
        |--------------------------------------------------------------------------
        | CREATE OVERLAY
        |--------------------------------------------------------------------------
        */

        const overlay =
            document.createElement("div");


        overlay.className =
            "service-modal-overlay";


        overlay.id =
            "medicationEditModalOverlay";


        overlay.innerHTML = `

            <div
                class="service-modal"
                role="dialog"
                aria-modal="true"
            >

                <div class="service-modal-header">

                    <div class="service-modal-title">

                        <div class="service-modal-title-icon">

                            <i class="fa-solid fa-pills"></i>

                        </div>


                        <div>

                            <h3>
                                Edit Medication
                            </h3>

                            <p>
                                Update the selected medication.
                            </p>

                        </div>

                    </div>


                    <button
                        type="button"
                        class="service-modal-close"
                        id="closeMedicationEditModal"
                    >

                        <i class="fa-solid fa-xmark"></i>

                    </button>

                </div>


                <form id="medicationEditForm">

                    <div class="service-modal-body">


                        <!-- MEDICATION NAME -->

                        <div class="service-form-group">

                            <label for="editMedicationName">

                                Medication Name

                                <span class="required">
                                    *
                                </span>

                            </label>


                            <input
                                type="text"
                                id="editMedicationName"
                                class="service-form-input"
                                value="${escapeMedicationHtml(currentName)}"
                                required
                            >

                        </div>


                        <!-- UNIT PRICE -->

                        <div class="service-form-group">

                            <label for="editMedicationPrice">

                                Unit Price (₱)

                                <span class="required">
                                    *
                                </span>

                            </label>


                            <input
                                type="number"
                                id="editMedicationPrice"
                                class="service-form-input"
                                min="0"
                                step="0.01"
                                value="${escapeMedicationHtml(currentPrice)}"
                                required
                            >


                            <p class="service-form-help">

                                Enter the price per unit of medication.

                            </p>

                        </div>


                        <!-- STATUS -->

                        <div class="service-form-group">

                            <label for="editMedicationStatus">

                                Status

                            </label>


                            <select
                                id="editMedicationStatus"
                                class="service-form-select"
                            >

                                <option
                                    value="Active"
                                    ${
                                        currentStatus
                                            .toLowerCase() ===
                                        "active"
                                            ? "selected"
                                            : ""
                                    }
                                >
                                    Active
                                </option>


                                <option
                                    value="Inactive"
                                    ${
                                        currentStatus
                                            .toLowerCase() ===
                                        "inactive"
                                            ? "selected"
                                            : ""
                                    }
                                >
                                    Inactive
                                </option>

                            </select>

                        </div>


                        <div
                            class="service-modal-message"
                            id="medicationEditModalMessage"
                        ></div>


                    </div>


                    <div class="service-modal-footer">

                        <button
                            type="button"
                            class="service-modal-btn cancel"
                            id="cancelMedicationEditModal"
                        >
                            Cancel
                        </button>


                        <button
                            type="submit"
                            class="service-modal-btn save"
                        >
                            Save Changes
                        </button>

                    </div>

                </form>

            </div>

        `;


        document.body.appendChild(
            overlay
        );


        /*
        |--------------------------------------------------------------------------
        | SHOW MODAL
        |--------------------------------------------------------------------------
        */

        requestAnimationFrame(
            function () {

                overlay.classList.add(
                    "show"
                );

            }
        );


        /*
        |--------------------------------------------------------------------------
        | CLOSE MODAL
        |--------------------------------------------------------------------------
        */

        function closeModal() {

            overlay.classList.remove(
                "show"
            );


            setTimeout(
                function () {

                    overlay.remove();

                },
                200
            );

        }


        document
            .getElementById(
                "closeMedicationEditModal"
            )
            .addEventListener(
                "click",
                closeModal
            );


        document
            .getElementById(
                "cancelMedicationEditModal"
            )
            .addEventListener(
                "click",
                closeModal
            );


        overlay.addEventListener(
            "click",
            function (event) {

                if (
                    event.target === overlay
                ) {

                    closeModal();

                }

            }
        );


        /*
        |--------------------------------------------------------------------------
        | SAVE CHANGES
        |--------------------------------------------------------------------------
        */

        document
            .getElementById(
                "medicationEditForm"
            )
            .addEventListener(
                "submit",
                function (event) {

                    event.preventDefault();


                    const nameInput =
                        document.getElementById(
                            "editMedicationName"
                        );


                    const priceInput =
                        document.getElementById(
                            "editMedicationPrice"
                        );


                    const statusInput =
                        document.getElementById(
                            "editMedicationStatus"
                        );


                    const message =
                        document.getElementById(
                            "medicationEditModalMessage"
                        );


                    const saveButton =
                        document.querySelector(
                            "#medicationEditForm .service-modal-btn.save"
                        );


                    const medicationName =
                        nameInput.value.trim();


                    const unitPrice =
                        priceInput.value.trim();


                    const status =
                        statusInput.value;


                    message.style.display =
                        "none";


                    /*
                    |--------------------------------------------------------------------------
                    | VALIDATION
                    |--------------------------------------------------------------------------
                    */

                    if (!medicationName) {

                        message.textContent =
                            "Please enter the medication name.";

                        message.style.display =
                            "block";

                        nameInput.focus();

                        return;

                    }


                    if (
                        unitPrice === "" ||
                        isNaN(
                            parseFloat(
                                unitPrice
                            )
                        ) ||
                        parseFloat(
                            unitPrice
                        ) < 0
                    ) {

                        message.textContent =
                            "Please enter a valid unit price.";

                        message.style.display =
                            "block";

                        priceInput.focus();

                        return;

                    }


                    /*
                    |--------------------------------------------------------------------------
                    | PREVENT DOUBLE CLICK
                    |--------------------------------------------------------------------------
                    */

                    saveButton.disabled =
                        true;


                    saveButton.textContent =
                        "Saving...";


                    /*
                    |--------------------------------------------------------------------------
                    | FORM DATA
                    |--------------------------------------------------------------------------
                    */

                    const formData =
                        new FormData();


                    formData.append(
                        "medication_id",
                        medicationId
                    );


                    formData.append(
                        "medication_name",
                        medicationName
                    );


                    formData.append(
                        "unit_price",
                        unitPrice
                    );


                    formData.append(
                        "status",
                        status
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | UPDATE DATABASE
                    |--------------------------------------------------------------------------
                    */

                    fetch(
                        "update_medication.php",
                        {
                            method: "POST",
                            body: formData
                        }
                    )

                    .then(
                        function (response) {

                            return response.json();

                        }
                    )

                    .then(
                        function (data) {

                            if (
                                !data.success
                            ) {

                                throw new Error(
                                    data.message ||
                                    "Failed to update medication."
                                );

                            }


                            alert(
                                data.message
                            );


                            closeModal();


                            /*
                            |--------------------------------------------------------------------------
                            | RELOAD PAGE
                            |--------------------------------------------------------------------------
                            */

                            window.location.reload();

                        }
                    )

                    .catch(
                        function (error) {

                            message.textContent =
                                error.message;


                            message.style.display =
                                "block";


                            saveButton.disabled =
                                false;


                            saveButton.textContent =
                                "Save Changes";

                        }
                    );

                }
            );

    }


    /*
    |--------------------------------------------------------------------------
    | ESCAPE HTML
    |--------------------------------------------------------------------------
    */

    function escapeMedicationHtml(
        value
    ) {

        return String(value)
            .replace(
                /&/g,
                "&amp;"
            )
            .replace(
                /</g,
                "&lt;"
            )
            .replace(
                />/g,
                "&gt;"
            )
            .replace(
                /"/g,
                "&quot;"
            )
            .replace(
                /'/g,
                "&#039;"
            );

    }


    /*
    |--------------------------------------------------------------------------
    | ATTACH EDIT BUTTONS
    |--------------------------------------------------------------------------
    */

    const medicationRows =
        medicationsTab.querySelectorAll(
            "tbody tr"
        );


    medicationRows.forEach(
        function (row) {

            const editButton =
                row.querySelector(
                    ".icon-action.edit"
                );


            if (
                editButton &&
                !editButton.dataset.medicationEditBound
            ) {

                editButton.dataset.medicationEditBound =
                    "true";


                editButton.addEventListener(
                    "click",
                    function () {

                        createEditMedicationModal(
                            row
                        );

                    }
                );

            }

        }
    );

});



/* ==========================================================
   APPOINTMENT TYPES - EDIT MODAL
   Uses the same modal classes already used by Services.
   This block is isolated so it does not alter Billing logic.
========================================================== */

document.addEventListener("DOMContentLoaded", function () {

    const appointmentTypesSection =
        document.getElementById("appointment-types");

    if (!appointmentTypesSection) {
        return;
    }


    function escapeAppointmentType(value) {

        return String(value || "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");

    }


    function closeAppointmentTypeModal() {

        const overlay =
            document.getElementById(
                "appointmentTypeModalOverlay"
            );

        if (overlay) {
            overlay.remove();
        }

    }


    function createAppointmentTypeEditModal(button) {

        closeAppointmentTypeModal();


        const currentType =
            button.dataset.type || "";


        const row =
            button.closest("tr");


        let currentStatus = "Active";

        if (row) {

            const statusElement =
                row.querySelector(".status");

            if (statusElement) {
                currentStatus =
                    statusElement.textContent.trim();
            }

        }


        const overlay =
            document.createElement("div");

        overlay.className =
            "service-modal-overlay";

        overlay.id =
            "appointmentTypeModalOverlay";


        overlay.innerHTML = `

            <div
                class="service-modal"
                role="dialog"
                aria-modal="true"
                aria-labelledby="appointmentTypeModalTitle"
            >

                <div class="service-modal-header">

                    <div class="service-modal-title">

                        <div class="service-modal-title-icon">
                            <i class="fa-solid fa-calendar-check"></i>
                        </div>

                        <div>

                            <h3 id="appointmentTypeModalTitle">
                                Edit Appointment Type
                            </h3>

                            <p>
                                Update the selected appointment type.
                            </p>

                        </div>

                    </div>


                    <button
                        type="button"
                        class="service-modal-close"
                        id="closeAppointmentTypeModal"
                        aria-label="Close"
                    >
                        <i class="fa-solid fa-xmark"></i>
                    </button>

                </div>


                <form id="appointmentTypeEditForm">

                    <div class="service-modal-body">

                        <div class="service-form-group">

                            <label for="appointmentTypeEditName">
                                Appointment Type
                                <span class="required">*</span>
                            </label>

                            <input
                                type="text"
                                id="appointmentTypeEditName"
                                class="service-form-input"
                                value="${escapeAppointmentType(currentType)}"
                                required
                                autocomplete="off"
                            >

                        </div>


                        <div class="service-form-group">

                            <label for="appointmentTypeEditStatus">
                                Status
                            </label>

                            <select
                                id="appointmentTypeEditStatus"
                                class="service-form-select"
                            >

                                <option
                                    value="Active"
                                    ${currentStatus === "Active" ? "selected" : ""}
                                >
                                    Active
                                </option>

                                <option
                                    value="Inactive"
                                    ${currentStatus === "Inactive" ? "selected" : ""}
                                >
                                    Inactive
                                </option>

                            </select>

                        </div>


                        <div
                            class="service-modal-message"
                            id="appointmentTypeEditMessage"
                        ></div>

                    </div>


                    <div class="service-modal-footer">

                        <button
                            type="button"
                            class="service-modal-btn cancel"
                            id="cancelAppointmentTypeModal"
                        >
                            Cancel
                        </button>

                        <button
                            type="submit"
                            class="service-modal-btn save"
                        >
                            Save Changes
                        </button>

                    </div>

                </form>

            </div>

        `;


        document.body.appendChild(overlay);


        const form =
            document.getElementById(
                "appointmentTypeEditForm"
            );

        const nameInput =
            document.getElementById(
                "appointmentTypeEditName"
            );

        const statusInput =
            document.getElementById(
                "appointmentTypeEditStatus"
            );

        const message =
            document.getElementById(
                "appointmentTypeEditMessage"
            );


        function closeModal() {
            closeAppointmentTypeModal();
        }


        const closeButton =
            document.getElementById(
                "closeAppointmentTypeModal"
            );

        const cancelButton =
            document.getElementById(
                "cancelAppointmentTypeModal"
            );


        if (closeButton) {

            closeButton.addEventListener(
                "click",
                closeModal
            );

        }


        if (cancelButton) {

            cancelButton.addEventListener(
                "click",
                closeModal
            );

        }


        overlay.addEventListener(
            "click",
            function (event) {

                if (event.target === overlay) {
                    closeModal();
                }

            }
        );


        document.addEventListener(
            "keydown",
            function handleEscape(event) {

                if (
                    event.key === "Escape" &&
                    document.getElementById(
                        "appointmentTypeModalOverlay"
                    )
                ) {

                    closeModal();

                    document.removeEventListener(
                        "keydown",
                        handleEscape
                    );

                }

            }
        );


        form.addEventListener(
            "submit",
            function (event) {

                event.preventDefault();


                const newType =
                    nameInput.value.trim();

                const newStatus =
                    statusInput.value;


                if (!newType) {

                    message.textContent =
                        "Appointment type is required.";

                    message.style.display =
                        "block";

                    nameInput.focus();

                    return;

                }


                /*
                 * At this stage the Appointment Types table
                 * in the supplied PHP is static and its edit
                 * buttons only contain data-type values.
                 *
                 * Therefore we update the visible row here
                 * without inventing a database endpoint.
                 *
                 * Database saving will be connected once the
                 * appointment-variable PHP endpoint is present.
                 */

                if (row) {

                    const typeCell =
                        row.querySelector(
                            "td:first-child"
                        );

                    const statusCell =
                        row.querySelector(
                            ".status"
                        );


                    if (typeCell) {

                        typeCell.innerHTML =
                            "<strong>" +
                            escapeAppointmentType(
                                newType
                            ) +
                            "</strong>";

                    }


                    if (statusCell) {

                        statusCell.textContent =
                            newStatus;

                        statusCell.classList.remove(
                            "active",
                            "inactive"
                        );

                        statusCell.classList.add(
                            newStatus.toLowerCase()
                        );

                    }


                    button.dataset.type =
                        newType;

                }


                closeModal();

            }
        );


        overlay.classList.add("show");


        setTimeout(
            function () {

                nameInput.focus();

                nameInput.select();

            },
            0
        );

    }


    /*
     * EVENT DELEGATION
     *
     * This is intentionally scoped only to
     * #appointment-types so the edit buttons
     * inside Billing/Services are untouched.
     */

    appointmentTypesSection.addEventListener(
        "click",
        function (event) {

            const editButton =
                event.target.closest(
                    ".icon-action.edit"
                );


            if (
                !editButton ||
                !appointmentTypesSection.contains(
                    editButton
                )
            ) {
                return;
            }


            event.preventDefault();
            event.stopPropagation();


            createAppointmentTypeEditModal(
                editButton
            );

        }
    );

});


/* ==========================================================
   APPOINTMENT STATUS - EDIT MODAL
   ADDED ONLY - existing code above is untouched.
========================================================== */

document.addEventListener("DOMContentLoaded", function () {

    const appointmentStatusSection =
        document.getElementById("appointment-status");

    if (!appointmentStatusSection) {
        return;
    }


    function closeAppointmentStatusModal() {

        const overlay =
            document.getElementById(
                "appointmentStatusModalOverlay"
            );

        if (overlay) {
            overlay.remove();
        }

    }


    function createAppointmentStatusEditModal(button) {

        closeAppointmentStatusModal();


        const currentStatusName =
            button.dataset.status || "";


        const row =
            button.closest("tr");


        if (!row) {
            return;
        }


        const statusBadge =
            row.querySelector(".status");


        const currentState =
            statusBadge
                ? statusBadge.textContent.trim()
                : "Active";


        const overlay =
            document.createElement("div");


        overlay.className =
            "service-modal-overlay";


        overlay.id =
            "appointmentStatusModalOverlay";


        overlay.innerHTML = `

            <div
                class="service-modal"
                role="dialog"
                aria-modal="true"
            >

                <div class="service-modal-header">

                    <div class="service-modal-title">

                        <div class="service-modal-title-icon">
                            <i class="fa-regular fa-calendar"></i>
                        </div>

                        <div>

                            <h3>
                                Edit Appointment Status
                            </h3>

                            <p>
                                Update the status availability used in appointments.
                            </p>

                        </div>

                    </div>


                    <button
                        type="button"
                        class="service-modal-close"
                        id="closeAppointmentStatusModal"
                    >
                        <i class="fa-solid fa-xmark"></i>
                    </button>

                </div>


                <form id="appointmentStatusEditForm">

                    <div class="service-modal-body">

                        <div class="service-form-group">

                            <label>
                                Appointment Status
                            </label>

                            <input
                                type="text"
                                class="service-form-input"
                                value="${currentStatusName}"
                                readonly
                            >

                        </div>


                        <div class="service-form-group">

                            <label for="appointmentStatusEditState">
                                Status
                            </label>

                            <select
                                id="appointmentStatusEditState"
                                class="service-form-select"
                            >

                                <option
                                    value="Active"
                                    ${currentState === "Active" ? "selected" : ""}
                                >
                                    Active
                                </option>

                                <option
                                    value="Inactive"
                                    ${currentState === "Inactive" ? "selected" : ""}
                                >
                                    Inactive
                                </option>

                            </select>

                        </div>


                        <div
                            class="service-modal-message"
                            id="appointmentStatusEditMessage"
                        ></div>

                    </div>


                    <div class="service-modal-footer">

                        <button
                            type="button"
                            class="service-modal-btn cancel"
                            id="cancelAppointmentStatusModal"
                        >
                            Cancel
                        </button>

                        <button
                            type="submit"
                            class="service-modal-btn save"
                        >
                            Save Changes
                        </button>

                    </div>

                </form>

            </div>

        `;


        document.body.appendChild(overlay);


        const form =
            document.getElementById(
                "appointmentStatusEditForm"
            );


        const stateInput =
            document.getElementById(
                "appointmentStatusEditState"
            );


        function closeModal() {
            closeAppointmentStatusModal();
        }


        document
            .getElementById(
                "closeAppointmentStatusModal"
            )
            .addEventListener(
                "click",
                closeModal
            );


        document
            .getElementById(
                "cancelAppointmentStatusModal"
            )
            .addEventListener(
                "click",
                closeModal
            );


        overlay.addEventListener(
            "click",
            function (event) {

                if (event.target === overlay) {
                    closeModal();
                }

            }
        );


        form.addEventListener(
            "submit",
            function (event) {

                event.preventDefault();


                const newState =
                    stateInput.value;


                if (statusBadge) {

                    statusBadge.textContent =
                        newState;


                    statusBadge.classList.remove(
                        "active",
                        "inactive"
                    );


                    statusBadge.classList.add(
                        newState.toLowerCase()
                    );

                }


                closeModal();

            }
        );


        overlay.classList.add("show");

    }


    appointmentStatusSection.addEventListener(
        "click",
        function (event) {

            const editButton =
                event.target.closest(
                    ".appointment-status-edit-added"
                );


            if (!editButton) {
                return;
            }


            event.preventDefault();
            event.stopPropagation();


            createAppointmentStatusEditModal(
                editButton
            );

        }
    );


    /*
     * Add a dedicated class to the existing status edit
     * buttons. No PHP/table code is modified.
     */

    appointmentStatusSection
        .querySelectorAll(
            ".icon-action.edit"
        )
        .forEach(function (button) {

            button.classList.add(
                "appointment-status-edit-added"
            );

        });

});


/* ==========================================================
   APPOINTMENT SCHEDULE - EDIT MODALS
   ADDED ONLY - existing PHP schedule cards are untouched.
========================================================== */

document.addEventListener("DOMContentLoaded", function () {

    const scheduleSection =
        document.getElementById(
            "appointment-schedule"
        );


    if (!scheduleSection) {
        return;
    }


    const scheduleCards =
        scheduleSection.querySelectorAll(
            ".schedule-setting-card"
        );


    if (scheduleCards.length < 3) {
        return;
    }


    /*
     * Add Edit buttons to the existing three cards.
     * This does not replace or remove any existing markup.
     */

    const scheduleDefinitions = [
        {
            key: "days",
            title: "Available Days",
            icon: "fa-calendar-days"
        },
        {
            key: "slots",
            title: "Available Time Slots",
            icon: "fa-clock"
        },
        {
            key: "hours",
            title: "Clinic Hours",
            icon: "fa-business-time"
        }
    ];


    scheduleCards.forEach(function (card, index) {

        const definition =
            scheduleDefinitions[index];


        if (!definition) {
            return;
        }


        card.dataset.scheduleKey =
            definition.key;


        const existingButton =
            card.querySelector(
                ".appointment-schedule-edit"
            );


        if (existingButton) {
            return;
        }


        const button =
            document.createElement("button");


        button.type = "button";

        button.className =
            "group-action edit appointment-schedule-edit";


        button.innerHTML = `
            <i class="fa-solid fa-pen"></i>
            Edit
        `;


        card.appendChild(button);

    });


    function closeScheduleModal() {

        const overlay =
            document.getElementById(
                "appointmentScheduleModalOverlay"
            );

        if (overlay) {
            overlay.remove();
        }

    }


    function createScheduleModal(key) {

        closeScheduleModal();


        let title = "";
        let description = "";
        let body = "";


        if (key === "days") {

            title =
                "Available Days";

            description =
                "Select the days when the clinic accepts appointments.";


            body = `

                <div class="service-form-group">

                    <label>
                        Available Days
                        <span class="required">*</span>
                    </label>

                    <div
                        style="
                            display:grid;
                            grid-template-columns:repeat(2,minmax(0,1fr));
                            gap:10px;
                        "
                    >

                        <label>
                            <input type="checkbox" value="Monday">
                            Monday
                        </label>

                        <label>
                            <input type="checkbox" value="Tuesday">
                            Tuesday
                        </label>

                        <label>
                            <input type="checkbox" value="Wednesday">
                            Wednesday
                        </label>

                        <label>
                            <input type="checkbox" value="Thursday">
                            Thursday
                        </label>

                        <label>
                            <input type="checkbox" value="Friday">
                            Friday
                        </label>

                        <label>
                            <input type="checkbox" value="Saturday">
                            Saturday
                        </label>

                        <label>
                            <input type="checkbox" value="Sunday">
                            Sunday
                        </label>

                    </div>

                </div>

            `;

        }


        if (key === "slots") {

            title =
                "Available Time Slots";

            description =
                "Set the appointment time interval used during booking.";


            body = `

                <div class="service-form-group">

                    <label for="scheduleSlotDuration">
                        Time Slot Duration
                        <span class="required">*</span>
                    </label>

                    <select
                        id="scheduleSlotDuration"
                        class="service-form-select"
                    >

                        <option value="15">
                            Every 15 minutes
                        </option>

                        <option value="30" selected>
                            Every 30 minutes
                        </option>

                        <option value="60">
                            Every 1 hour
                        </option>

                    </select>

                </div>


                <div class="service-form-group">

                    <label for="scheduleFirstSlot">
                        First Available Slot
                        <span class="required">*</span>
                    </label>

                    <input
                        type="time"
                        id="scheduleFirstSlot"
                        class="service-form-input"
                        value="09:00"
                    >

                </div>

            `;

        }


        if (key === "hours") {

            title =
                "Clinic Hours";

            description =
                "Set the clinic's opening and closing hours.";


            body = `

                <div class="service-form-group">

                    <label for="clinicOpeningTime">
                        Opening Time
                        <span class="required">*</span>
                    </label>

                    <input
                        type="time"
                        id="clinicOpeningTime"
                        class="service-form-input"
                        value="09:00"
                    >

                </div>


                <div class="service-form-group">

                    <label for="clinicClosingTime">
                        Closing Time
                        <span class="required">*</span>
                    </label>

                    <input
                        type="time"
                        id="clinicClosingTime"
                        class="service-form-input"
                        value="17:00"
                    >

                </div>

            `;

        }


        const overlay =
            document.createElement("div");


        overlay.className =
            "service-modal-overlay";


        overlay.id =
            "appointmentScheduleModalOverlay";


        overlay.innerHTML = `

            <div
                class="service-modal"
                role="dialog"
                aria-modal="true"
            >

                <div class="service-modal-header">

                    <div class="service-modal-title">

                        <div class="service-modal-title-icon">

                            <i
                                class="fa-solid ${
                                    scheduleDefinitions.find(
                                        item => item.key === key
                                    )?.icon || "fa-calendar"
                                }"
                            ></i>

                        </div>

                        <div>

                            <h3>
                                Edit ${title}
                            </h3>

                            <p>
                                ${description}
                            </p>

                        </div>

                    </div>


                    <button
                        type="button"
                        class="service-modal-close"
                        id="closeAppointmentScheduleModal"
                    >
                        <i class="fa-solid fa-xmark"></i>
                    </button>

                </div>


                <form
                    id="appointmentScheduleEditForm"
                >

                    <div class="service-modal-body">

                        ${body}

                        <div
                            class="service-modal-message"
                            id="appointmentScheduleEditMessage"
                        ></div>

                    </div>


                    <div class="service-modal-footer">

                        <button
                            type="button"
                            class="service-modal-btn cancel"
                            id="cancelAppointmentScheduleModal"
                        >
                            Cancel
                        </button>


                        <button
                            type="submit"
                            class="service-modal-btn save"
                        >
                            Save Changes
                        </button>

                    </div>

                </form>

            </div>

        `;


        document.body.appendChild(overlay);


        const form =
            document.getElementById(
                "appointmentScheduleEditForm"
            );


        const message =
            document.getElementById(
                "appointmentScheduleEditMessage"
            );


        function closeModal() {
            closeScheduleModal();
        }


        document
            .getElementById(
                "closeAppointmentScheduleModal"
            )
            .addEventListener(
                "click",
                closeModal
            );


        document
            .getElementById(
                "cancelAppointmentScheduleModal"
            )
            .addEventListener(
                "click",
                closeModal
            );


        overlay.addEventListener(
            "click",
            function (event) {

                if (event.target === overlay) {
                    closeModal();
                }

            }
        );


        form.addEventListener(
            "submit",
            function (event) {

                event.preventDefault();


                if (key === "days") {

                    const selectedDays =
                        Array.from(
                            form.querySelectorAll(
                                'input[type="checkbox"]:checked'
                            )
                        );


                    if (
                        selectedDays.length === 0
                    ) {

                        message.textContent =
                            "Please select at least one available day.";

                        message.style.display =
                            "block";

                        return;

                    }

                }


                if (key === "hours") {

                    const opening =
                        document.getElementById(
                            "clinicOpeningTime"
                        ).value;


                    const closing =
                        document.getElementById(
                            "clinicClosingTime"
                        ).value;


                    if (
                        !opening ||
                        !closing ||
                        opening >= closing
                    ) {

                        message.textContent =
                            "Closing time must be later than opening time.";

                        message.style.display =
                            "block";

                        return;

                    }

                }


                closeModal();

            }
        );


        overlay.classList.add("show");

    }


    scheduleSection.addEventListener(
        "click",
        function (event) {

            const editButton =
                event.target.closest(
                    ".appointment-schedule-edit"
                );


            if (!editButton) {
                return;
            }


            event.preventDefault();
            event.stopPropagation();


            const card =
                editButton.closest(
                    ".schedule-setting-card"
                );


            if (!card) {
                return;
            }


            createScheduleModal(
                card.dataset.scheduleKey
            );

        }
    );

});

/* ==========================================================
   APPOINTMENT VARIABLES - ADD TYPE / ADD STATUS
   ADDED ONLY.
   Existing Appointment Type/Status Edit code is untouched.
   Existing hardcoded rows are untouched.
========================================================== */

document.addEventListener("DOMContentLoaded", function () {

    const appointmentTypesSection =
        document.getElementById("appointment-types");

    const appointmentStatusSection =
        document.getElementById("appointment-status");

    /* ----------------------------------------------------------
       SHARED HELPERS
    ---------------------------------------------------------- */

    function escapeAppointmentVariable(value) {

        return String(value || "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");

    }


    function closeAddAppointmentVariableModal() {

        const overlay =
            document.getElementById(
                "addAppointmentVariableModalOverlay"
            );

        if (overlay) {
            overlay.remove();
        }

    }


    function showAppointmentVariableMessage(
        messageElement,
        message,
        isError
    ) {

        if (!messageElement) {
            return;
        }

        messageElement.textContent = message;
        messageElement.style.display = "block";

        if (isError) {
            messageElement.classList.add("error");
        } else {
            messageElement.classList.remove("error");
        }

    }


    /* ==========================================================
       ADD APPOINTMENT TYPE
    ========================================================== */

    if (appointmentTypesSection) {

        const addTypeButton =
            document.getElementById(
                "addAppointmentTypeBtn"
            );


        if (addTypeButton) {

            addTypeButton.addEventListener(
                "click",
                function (event) {

                    event.preventDefault();
                    event.stopPropagation();

                    closeAddAppointmentVariableModal();


                    const overlay =
                        document.createElement("div");

                    overlay.className =
                        "service-modal-overlay";

                    overlay.id =
                        "addAppointmentVariableModalOverlay";


                    overlay.innerHTML = `

                        <div
                            class="service-modal"
                            role="dialog"
                            aria-modal="true"
                            aria-labelledby="addAppointmentTypeModalTitle"
                        >

                            <div class="service-modal-header">

                                <div class="service-modal-title">

                                    <div class="service-modal-title-icon">
                                        <i class="fa-regular fa-calendar-plus"></i>
                                    </div>

                                    <div>

                                        <h3 id="addAppointmentTypeModalTitle">
                                            Add Appointment Type
                                        </h3>

                                        <p>
                                            Add a new appointment type.
                                        </p>

                                    </div>

                                </div>


                                <button
                                    type="button"
                                    class="service-modal-close"
                                    id="closeAddAppointmentTypeModal"
                                >
                                    <i class="fa-solid fa-xmark"></i>
                                </button>

                            </div>


                            <form id="addAppointmentTypeForm">

                                <div class="service-modal-body">

                                    <div class="service-form-group">

                                        <label for="addAppointmentTypeName">
                                            Appointment Type
                                            <span class="required">*</span>
                                        </label>

                                        <input
                                            type="text"
                                            id="addAppointmentTypeName"
                                            class="service-form-input"
                                            placeholder="e.g. Emergency"
                                            maxlength="50"
                                            required
                                            autocomplete="off"
                                        >

                                    </div>


                                    <div class="service-form-group">

                                        <label for="addAppointmentTypeStatus">
                                            Status
                                        </label>

                                        <select
                                            id="addAppointmentTypeStatus"
                                            class="service-form-select"
                                        >

                                            <option value="Active">
                                                Active
                                            </option>

                                            <option value="Inactive">
                                                Inactive
                                            </option>

                                        </select>

                                    </div>


                                    <div
                                        class="service-modal-message"
                                        id="addAppointmentTypeMessage"
                                    ></div>

                                </div>


                                <div class="service-modal-footer">

                                    <button
                                        type="button"
                                        class="service-modal-btn cancel"
                                        id="cancelAddAppointmentTypeModal"
                                    >
                                        Cancel
                                    </button>


                                    <button
                                        type="submit"
                                        class="service-modal-btn save"
                                        id="saveAppointmentTypeBtn"
                                    >
                                        Save Type
                                    </button>

                                </div>

                            </form>

                        </div>

                    `;


                    document.body.appendChild(overlay);


                    const form =
                        document.getElementById(
                            "addAppointmentTypeForm"
                        );

                    const nameInput =
                        document.getElementById(
                            "addAppointmentTypeName"
                        );

                    const statusInput =
                        document.getElementById(
                            "addAppointmentTypeStatus"
                        );

                    const message =
                        document.getElementById(
                            "addAppointmentTypeMessage"
                        );

                    const saveButton =
                        document.getElementById(
                            "saveAppointmentTypeBtn"
                        );


                    function closeModal() {
                        closeAddAppointmentVariableModal();
                    }


                    document
                        .getElementById(
                            "closeAddAppointmentTypeModal"
                        )
                        .addEventListener(
                            "click",
                            closeModal
                        );


                    document
                        .getElementById(
                            "cancelAddAppointmentTypeModal"
                        )
                        .addEventListener(
                            "click",
                            closeModal
                        );


                    overlay.addEventListener(
                        "click",
                        function (clickEvent) {

                            if (
                                clickEvent.target === overlay
                            ) {
                                closeModal();
                            }

                        }
                    );


                    form.addEventListener(
                        "submit",
                        function (submitEvent) {

                            submitEvent.preventDefault();

                            const appointmentType =
                                nameInput.value.trim();

                            const status =
                                statusInput.value;


                            message.style.display =
                                "none";


                            if (!appointmentType) {

                                showAppointmentVariableMessage(
                                    message,
                                    "Appointment type is required.",
                                    true
                                );

                                nameInput.focus();

                                return;
                            }


                            saveButton.disabled = true;

                            saveButton.textContent =
                                "Saving...";


                            const formData =
                                new FormData();

                            formData.append(
                                "appointment_type",
                                appointmentType
                            );

                            formData.append(
                                "status",
                                status
                            );


                            fetch(
                                "save_appointment_type.php",
                                {
                                    method: "POST",
                                    body: formData
                                }
                            )

                            .then(function (response) {
                                return response.json();
                            })

                            .then(function (data) {

                                if (!data.success) {

                                    throw new Error(
                                        data.message ||
                                        "Failed to save appointment type."
                                    );

                                }


                                closeModal();

                                window.location.reload();

                            })

                            .catch(function (error) {

                                showAppointmentVariableMessage(
                                    message,
                                    error.message ||
                                    "Unable to save appointment type.",
                                    true
                                );

                                saveButton.disabled = false;

                                saveButton.textContent =
                                    "Save Type";

                            });

                        }
                    );


                    overlay.classList.add("show");


                    setTimeout(
                        function () {

                            nameInput.focus();

                        },
                        0
                    );

                }
            );

        }

    }


    /* ==========================================================
       ADD APPOINTMENT STATUS
    ========================================================== */

    if (appointmentStatusSection) {

        const addStatusButton =
            document.getElementById(
                "addAppointmentStatusBtn"
            );


        if (addStatusButton) {

            addStatusButton.addEventListener(
                "click",
                function (event) {

                    event.preventDefault();
                    event.stopPropagation();

                    closeAddAppointmentVariableModal();


                    const overlay =
                        document.createElement("div");

                    overlay.className =
                        "service-modal-overlay";

                    overlay.id =
                        "addAppointmentVariableModalOverlay";


                    overlay.innerHTML = `

                        <div
                            class="service-modal"
                            role="dialog"
                            aria-modal="true"
                            aria-labelledby="addAppointmentStatusModalTitle"
                        >

                            <div class="service-modal-header">

                                <div class="service-modal-title">

                                    <div class="service-modal-title-icon">
                                        <i class="fa-regular fa-calendar-check"></i>
                                    </div>

                                    <div>

                                        <h3 id="addAppointmentStatusModalTitle">
                                            Add Appointment Status
                                        </h3>

                                        <p>
                                            Add a new appointment status.
                                        </p>

                                    </div>

                                </div>


                                <button
                                    type="button"
                                    class="service-modal-close"
                                    id="closeAddAppointmentStatusModal"
                                >
                                    <i class="fa-solid fa-xmark"></i>
                                </button>

                            </div>


                            <form id="addAppointmentStatusForm">

                                <div class="service-modal-body">

                                    <div class="service-form-group">

                                        <label for="addAppointmentStatusName">
                                            Appointment Status
                                            <span class="required">*</span>
                                        </label>

                                        <input
                                            type="text"
                                            id="addAppointmentStatusName"
                                            class="service-form-input"
                                            placeholder="e.g. No Show"
                                            maxlength="50"
                                            required
                                            autocomplete="off"
                                        >

                                    </div>


                                    <div class="service-form-group">

                                        <label for="addAppointmentStatusState">
                                            Status
                                        </label>

                                        <select
                                            id="addAppointmentStatusState"
                                            class="service-form-select"
                                        >

                                            <option value="Active">
                                                Active
                                            </option>

                                            <option value="Inactive">
                                                Inactive
                                            </option>

                                        </select>

                                    </div>


                                    <div
                                        class="service-modal-message"
                                        id="addAppointmentStatusMessage"
                                    ></div>

                                </div>


                                <div class="service-modal-footer">

                                    <button
                                        type="button"
                                        class="service-modal-btn cancel"
                                        id="cancelAddAppointmentStatusModal"
                                    >
                                        Cancel
                                    </button>


                                    <button
                                        type="submit"
                                        class="service-modal-btn save"
                                        id="saveAppointmentStatusBtn"
                                    >
                                        Save Status
                                    </button>

                                </div>

                            </form>

                        </div>

                    `;


                    document.body.appendChild(overlay);


                    const form =
                        document.getElementById(
                            "addAppointmentStatusForm"
                        );

                    const nameInput =
                        document.getElementById(
                            "addAppointmentStatusName"
                        );

                    const statusInput =
                        document.getElementById(
                            "addAppointmentStatusState"
                        );

                    const message =
                        document.getElementById(
                            "addAppointmentStatusMessage"
                        );

                    const saveButton =
                        document.getElementById(
                            "saveAppointmentStatusBtn"
                        );


                    function closeModal() {
                        closeAddAppointmentVariableModal();
                    }


                    document
                        .getElementById(
                            "closeAddAppointmentStatusModal"
                        )
                        .addEventListener(
                            "click",
                            closeModal
                        );


                    document
                        .getElementById(
                            "cancelAddAppointmentStatusModal"
                        )
                        .addEventListener(
                            "click",
                            closeModal
                        );


                    overlay.addEventListener(
                        "click",
                        function (clickEvent) {

                            if (
                                clickEvent.target === overlay
                            ) {
                                closeModal();
                            }

                        }
                    );


                    form.addEventListener(
                        "submit",
                        function (submitEvent) {

                            submitEvent.preventDefault();

                            const appointmentStatus =
                                nameInput.value.trim();

                            const status =
                                statusInput.value;


                            message.style.display =
                                "none";


                            if (!appointmentStatus) {

                                showAppointmentVariableMessage(
                                    message,
                                    "Appointment status is required.",
                                    true
                                );

                                nameInput.focus();

                                return;
                            }


                            saveButton.disabled = true;

                            saveButton.textContent =
                                "Saving...";


                            const formData =
                                new FormData();

                            formData.append(
                                "appointment_status",
                                appointmentStatus
                            );

                            formData.append(
                                "status",
                                status
                            );


                            fetch(
                                "save_appointment_status.php",
                                {
                                    method: "POST",
                                    body: formData
                                }
                            )

                            .then(function (response) {
                                return response.json();
                            })

                            .then(function (data) {

                                if (!data.success) {

                                    throw new Error(
                                        data.message ||
                                        "Failed to save appointment status."
                                    );

                                }


                                closeModal();

                                window.location.reload();

                            })

                            .catch(function (error) {

                                showAppointmentVariableMessage(
                                    message,
                                    error.message ||
                                    "Unable to save appointment status.",
                                    true
                                );

                                saveButton.disabled = false;

                                saveButton.textContent =
                                    "Save Status";

                            });

                        }
                    );


                    overlay.classList.add("show");


                    setTimeout(
                        function () {

                            nameInput.focus();

                        },
                        0
                    );

                }
            );

        }

    }

});
/* ==========================================================
   APPOINTMENT SCHEDULE - DATABASE CONNECTION
   ADDED ONLY
   Connects the existing Schedule modal to MySQL.
========================================================== */

document.addEventListener("DOMContentLoaded", function () {

    let appointmentScheduleData = [];

    /*
    |--------------------------------------------------------------------------
    | LOAD SCHEDULE DATA
    |--------------------------------------------------------------------------
    */

    function loadAppointmentSchedule() {

        return fetch("get_appointment_schedule.php")
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {

                if (!data.success) {
                    console.error(
                        "Unable to load appointment schedule:",
                        data.message
                    );

                    return;
                }

                appointmentScheduleData =
                    data.schedules || [];

            })
            .catch(function (error) {

                console.error(
                    "Error loading appointment schedule:",
                    error
                );

            });

    }


    /*
    |--------------------------------------------------------------------------
    | GET CURRENT SCHEDULE VALUES
    |--------------------------------------------------------------------------
    */

    function getScheduleValues() {

        const schedules =
            appointmentScheduleData || [];

        return {

            availableDays:
                schedules
                    .filter(function (schedule) {
                        return Number(schedule.is_available) === 1;
                    })
                    .map(function (schedule) {
                        return schedule.day_of_week;
                    }),

            slotInterval:
                schedules.length > 0 &&
                schedules[0].slot_interval
                    ? Number(schedules[0].slot_interval)
                    : 30,

            openingTime:
                schedules.length > 0 &&
                schedules[0].opening_time
                    ? String(
                        schedules[0].opening_time
                    ).substring(0, 5)
                    : "09:00",

            closingTime:
                schedules.length > 0 &&
                schedules[0].closing_time
                    ? String(
                        schedules[0].closing_time
                    ).substring(0, 5)
                    : "17:00"

        };

    }


    /*
    |--------------------------------------------------------------------------
    | LOAD DATABASE VALUES INTO EXISTING MODAL
    |--------------------------------------------------------------------------
    */

    function populateScheduleModal(key) {

        const values =
            getScheduleValues();


        /*
        |--------------------------------------------------------------------------
        | AVAILABLE DAYS
        |--------------------------------------------------------------------------
        */

        if (key === "days") {

            const checkboxes =
                document.querySelectorAll(
                    "#appointmentScheduleModalOverlay input[type='checkbox']"
                );

            checkboxes.forEach(function (checkbox) {

                checkbox.checked =
                    values.availableDays.includes(
                        checkbox.value
                    );

            });

        }


        /*
        |--------------------------------------------------------------------------
        | AVAILABLE TIME SLOTS
        |--------------------------------------------------------------------------
        */

        if (key === "slots") {

            const slotSelect =
                document.getElementById(
                    "scheduleSlotDuration"
                );

            if (slotSelect) {

                slotSelect.value =
                    String(
                        values.slotInterval
                    );

            }


            const firstSlot =
                document.getElementById(
                    "scheduleFirstSlot"
                );

            if (firstSlot) {

                /*
                 * First Available Slot currently
                 * represents the beginning of the
                 * appointment schedule.
                 */

                firstSlot.value =
                    values.openingTime;

            }

        }


        /*
        |--------------------------------------------------------------------------
        | CLINIC HOURS
        |--------------------------------------------------------------------------
        */

        if (key === "hours") {

            const opening =
                document.getElementById(
                    "clinicOpeningTime"
                );

            const closing =
                document.getElementById(
                    "clinicClosingTime"
                );


            if (opening) {

                opening.value =
                    values.openingTime;

            }


            if (closing) {

                closing.value =
                    values.closingTime;

            }

        }

    }


    /*
    |--------------------------------------------------------------------------
    | INTERCEPT SCHEDULE EDIT CLICK
    |--------------------------------------------------------------------------
    |
    | This runs before the existing Schedule click handler.
    | We do NOT replace the existing modal.
    |
    */

    document.addEventListener(
        "click",
        function (event) {

            const editButton =
                event.target.closest(
                    ".appointment-schedule-edit"
                );

            if (!editButton) {
                return;
            }


            const card =
                editButton.closest(
                    ".schedule-setting-card"
                );

            if (!card) {
                return;
            }


            const key =
                card.dataset.scheduleKey;

            if (!key) {
                return;
            }


            /*
             * Load fresh database values.
             */

            loadAppointmentSchedule()
                .then(function () {

                    /*
                     * Existing code already creates
                     * the modal synchronously.
                     *
                     * Wait a little so the modal exists.
                     */

                    setTimeout(function () {

                        populateScheduleModal(
                            key
                        );

                    }, 50);

                });

        },
        true
    );


    /*
    |--------------------------------------------------------------------------
    | INTERCEPT EXISTING SCHEDULE FORM SUBMIT
    |--------------------------------------------------------------------------
    |
    | Capture mode makes this run BEFORE the old
    | submit listener inside createScheduleModal().
    |
    */

    document.addEventListener(
        "submit",
        function (event) {

            const form =
                event.target;

            if (
                !form ||
                form.id !==
                    "appointmentScheduleEditForm"
            ) {

                return;

            }


            /*
             * Stop the old submit handler.
             */

            event.preventDefault();
            event.stopImmediatePropagation();


            /*
             * Get current schedule key.
             */

            const overlay =
                document.getElementById(
                    "appointmentScheduleModalOverlay"
                );

            if (!overlay) {
                return;
            }


            /*
             * Determine which schedule card
             * opened the modal.
             */

            let key = null;

           const scheduleForm =
    document.getElementById(
        "appointmentScheduleEditForm"
    );


if (scheduleForm) {

    if (
        scheduleForm.querySelector(
            "#scheduleSlotDuration"
        )
    ) {

        key = "slots";

    } else if (
        scheduleForm.querySelector(
            "#clinicOpeningTime"
        )
    ) {

        key = "hours";

    } else if (
        scheduleForm.querySelector(
            'input[type="checkbox"]'
        )
    ) {

        key = "days";

    }

}


            /*
             * Fallback:
             *
             * Detect which form fields exist.
             */

            if (!key) {

                if (
                    form.querySelector(
                        "#scheduleSlotDuration"
                    )
                ) {

                    key = "slots";

                } else if (
                    form.querySelector(
                        "#clinicOpeningTime"
                    )
                ) {

                    key = "hours";

                } else if (
                    form.querySelector(
                        'input[type="checkbox"]'
                    )
                ) {

                    key = "days";

                }

            }


            if (!key) {

                console.error(
                    "Unable to determine schedule type."
                );

                return;

            }


            const message =
                document.getElementById(
                    "appointmentScheduleEditMessage"
                );


            /*
             |--------------------------------------------------------------------------
             | AVAILABLE DAYS
             |--------------------------------------------------------------------------
             */

            if (key === "days") {

                const selectedDays =
                    Array.from(
                        form.querySelectorAll(
                            'input[type="checkbox"]:checked'
                        )
                    ).map(function (checkbox) {

                        return checkbox.value;

                    });


                if (
                    selectedDays.length === 0
                ) {

                    if (message) {

                        message.textContent =
                            "Please select at least one available day.";

                        message.style.display =
                            "block";

                    }

                    return;

                }


                const formData =
                    new FormData();

                formData.append(
                    "key",
                    "days"
                );

                formData.append(
                    "selected_days",
                    JSON.stringify(
                        selectedDays
                    )
                );


                saveAppointmentSchedule(
                    formData,
                    message
                );

                return;

            }


            /*
             |--------------------------------------------------------------------------
             | AVAILABLE TIME SLOTS
             |--------------------------------------------------------------------------
             */

            if (key === "slots") {

                const slotSelect =
                    document.getElementById(
                        "scheduleSlotDuration"
                    );

                const firstSlot =
                    document.getElementById(
                        "scheduleFirstSlot"
                    );


                if (!slotSelect) {
                    return;
                }


                const formData =
                    new FormData();

                formData.append(
                    "key",
                    "slots"
                );

                formData.append(
                    "slot_interval",
                    slotSelect.value
                );


                /*
                 * The current database structure uses
                 * opening_time / closing_time for clinic
                 * hours.
                 *
                 * First Available Slot is retained
                 * in the modal UI for now.
                 */

                if (firstSlot) {

                    formData.append(
                        "first_slot",
                        firstSlot.value
                    );

                }


                saveAppointmentSchedule(
                    formData,
                    message
                );

                return;

            }


            /*
             |--------------------------------------------------------------------------
             | CLINIC HOURS
             |--------------------------------------------------------------------------
             */

            if (key === "hours") {

                const opening =
                    document.getElementById(
                        "clinicOpeningTime"
                    );

                const closing =
                    document.getElementById(
                        "clinicClosingTime"
                    );


                if (
                    !opening ||
                    !closing
                ) {

                    return;

                }


                if (
                    !opening.value ||
                    !closing.value
                ) {

                    if (message) {

                        message.textContent =
                            "Opening and closing time are required.";

                        message.style.display =
                            "block";

                    }

                    return;

                }


                if (
                    opening.value >=
                    closing.value
                ) {

                    if (message) {

                        message.textContent =
                            "Closing time must be later than opening time.";

                        message.style.display =
                            "block";

                    }

                    return;

                }


                const formData =
                    new FormData();

                formData.append(
                    "key",
                    "hours"
                );

                formData.append(
                    "opening_time",
                    opening.value
                );

                formData.append(
                    "closing_time",
                    closing.value
                );


                saveAppointmentSchedule(
                    formData,
                    message
                );

            }

        },
        true
    );


    /*
    |--------------------------------------------------------------------------
    | SAVE SCHEDULE
    |--------------------------------------------------------------------------
    */

    function saveAppointmentSchedule(
        formData,
        message
    ) {

        if (message) {

            message.textContent =
                "Saving changes...";

            message.style.display =
                "block";

        }


        fetch(
            "save_appointment_schedule.php",
            {
                method: "POST",
                body: formData
            }
        )

            .then(function (response) {

                return response.json();

            })

            .then(function (data) {

                if (!data.success) {

                    if (message) {

                        message.textContent =
                            data.message ||
                            "Unable to save changes.";

                    }

                    return;

                }


                /*
                 * Reload latest values.
                 */

                return loadAppointmentSchedule()
                    .then(function () {

                        if (message) {

                            message.textContent =
                                data.message ||
                                "Schedule updated successfully.";

                        }


                        /*
                         * Close the existing modal
                         * after successful save.
                         */

                        setTimeout(
                            function () {

                                const overlay =
                                    document.getElementById(
                                        "appointmentScheduleModalOverlay"
                                    );

                                if (overlay) {

                                    overlay.remove();

                                }

                            },
                            500
                        );

                    });

            })

            .catch(function (error) {

                console.error(
                    "Schedule save error:",
                    error
                );


                if (message) {

                    message.textContent =
                        "An error occurred while saving the schedule.";

                }

            });

    }


    /*
    |--------------------------------------------------------------------------
    | INITIAL LOAD
    |--------------------------------------------------------------------------
    */

    loadAppointmentSchedule();

});


/* =========================================================
   PET RECORDS - SPECIES & BREEDS
   Add / Edit / Delete + Modal UI
   Uses system_variables.php as the same backend endpoint.
========================================================= */

document.addEventListener("DOMContentLoaded", function () {

    const petRecordsContent = document.getElementById("petRecordsContent");

    if (!petRecordsContent) return;

    function escapeHtml(value) {
        return String(value ?? "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function removeModal(id) {
        const existing = document.getElementById(id);
        if (existing) existing.remove();
    }

    function showMessageModal(type, title, message) {
        const id = "petRecordMessageModalOverlay";
        removeModal(id);

        const icon = type === "success"
            ? "fa-circle-check"
            : "fa-circle-exclamation";

        const overlay = document.createElement("div");
        overlay.className = "service-modal-overlay";
        overlay.id = id;

        overlay.innerHTML = `
            <div class="service-modal" role="dialog" aria-modal="true">
                <div class="service-modal-header">
                    <div class="service-modal-title">
                        <div class="service-modal-title-icon">
                            <i class="fa-solid ${icon}"></i>
                        </div>
                        <div>
                            <h3>${escapeHtml(title)}</h3>
                            <p>${escapeHtml(message)}</p>
                        </div>
                    </div>
                    <button type="button" class="service-modal-close" data-close-message>
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="service-modal-body">
                    <div class="service-modal-message" style="display:block;">
                        ${escapeHtml(message)}
                    </div>
                </div>

                <div class="service-modal-footer">
                    <button type="button" class="service-modal-btn save" data-close-message>
                        OK
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(overlay);
        overlay.classList.add("show");

        overlay.querySelectorAll("[data-close-message]").forEach(function (button) {
            button.addEventListener("click", function () {
                overlay.remove();
            });
        });

        overlay.addEventListener("click", function (event) {
            if (event.target === overlay) overlay.remove();
        });
    }

    function showConfirmModal(title, message, confirmText, onConfirm) {
        const id = "petRecordConfirmModalOverlay";
        removeModal(id);

        const overlay = document.createElement("div");
        overlay.className = "service-modal-overlay";
        overlay.id = id;

        overlay.innerHTML = `
    <div class="service-modal" role="dialog" aria-modal="true">
        <div class="service-modal-header">
            <div class="service-modal-title">
                <div class="service-modal-title-icon">
                    <i class="fa-solid fa-circle-question"></i>
                </div>
                <div>
                    <h3>${escapeHtml(title)}</h3>
                    <p>Please confirm this action.</p>
                </div>
            </div>

            <button type="button" class="service-modal-close" data-cancel-confirm>
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="service-modal-body">
            <div class="service-modal-message" style="display:block;">
                ${escapeHtml(message)}
            </div>
        </div>

        <div class="service-modal-footer">
            <button type="button" class="service-modal-btn cancel" data-cancel-confirm>
                Cancel
            </button>

            <button type="button" class="service-modal-btn save" data-confirm-action>
                ${escapeHtml(confirmText)}
            </button>
        </div>
    </div>
`;

        document.body.appendChild(overlay);
        overlay.classList.add("show");

        overlay.querySelectorAll("[data-cancel-confirm]").forEach(function (button) {
            button.addEventListener("click", function () {
                overlay.remove();
            });
        });

        overlay.querySelector("[data-confirm-action]").addEventListener("click", function () {
            overlay.remove();
            onConfirm();
        });

        overlay.addEventListener("click", function (event) {
            if (event.target === overlay) overlay.remove();
        });
    }

    window.showConfirmModal = showConfirmModal;
    window.showMessageModal = showMessageModal;
    


    function getSpeciesOptions(selectedId) {
        let options = "";

        petRecordsContent
            .querySelectorAll("#pet-species .icon-action.edit")
            .forEach(function (button) {
                const id = button.dataset.id || "";
                const name = button.dataset.species || "";

                options += `
                    <option value="${escapeHtml(id)}"
                        ${String(id) === String(selectedId) ? "selected" : ""}>
                        ${escapeHtml(name)}
                    </option>
                `;
            });

        return options;
    }

    function createSpeciesModal(mode, button) {
        removeModal("petSpeciesModalOverlay");

        const isEdit = mode === "edit";
        const speciesId = isEdit ? button.dataset.id : "";
        const speciesName = isEdit ? button.dataset.species : "";
        const status = isEdit
            ? (button.closest("tr")?.querySelector(".status")?.textContent.trim() || "Active")
            : "Active";

        const overlay = document.createElement("div");
        overlay.className = "service-modal-overlay";
        overlay.id = "petSpeciesModalOverlay";

        overlay.innerHTML = `
            <div class="service-modal" role="dialog" aria-modal="true">
                <div class="service-modal-header">
                    <div class="service-modal-title">
                        <div class="service-modal-title-icon">
                            <i class="fa-solid fa-paw"></i>
                        </div>
                        <div>
                            <h3>${isEdit ? "Edit Species" : "Add Species"}</h3>
                            <p>${isEdit
                                ? "Update the selected pet species."
                                : "Add a pet species used in registration."}</p>
                        </div>
                    </div>
                    <button type="button" class="service-modal-close" data-close-species>
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <form id="petSpeciesForm">
                    <div class="service-modal-body">
                        <div class="service-form-group">
                            <label for="petSpeciesName">
                                Species Name <span class="required">*</span>
                            </label>
                            <input type="text" id="petSpeciesName"
                                class="service-form-input"
                                placeholder="e.g. Dog"
                                value="${escapeHtml(speciesName)}"
                                required>
                        </div>

                        <div class="service-form-group">
                            <label for="petSpeciesStatus">Status</label>
                            <select id="petSpeciesStatus" class="service-form-select">
                                <option value="Active" ${status === "Active" ? "selected" : ""}>Active</option>
                                <option value="Inactive" ${status === "Inactive" ? "selected" : ""}>Inactive</option>
                            </select>
                        </div>

                        <div class="service-modal-message" id="petSpeciesModalMessage"></div>
                    </div>

                    <div class="service-modal-footer">
                        <button type="button" class="service-modal-btn cancel" data-close-species>
                            Cancel
                        </button>
                        <button type="submit" class="service-modal-btn save">
                            ${isEdit ? "Save Changes" : "Save Species"}
                        </button>
                    </div>
                </form>
            </div>
        `;

        document.body.appendChild(overlay);
        overlay.classList.add("show");

        function closeModal() {
            overlay.remove();
        }

        overlay.querySelectorAll("[data-close-species]").forEach(function (button) {
            button.addEventListener("click", closeModal);
        });

        overlay.addEventListener("click", function (event) {
            if (event.target === overlay) closeModal();
        });

        document.getElementById("petSpeciesForm").addEventListener("submit", function (event) {
            event.preventDefault();

            const nameInput = document.getElementById("petSpeciesName");
            const statusInput = document.getElementById("petSpeciesStatus");
            const message = document.getElementById("petSpeciesModalMessage");
            const saveButton = overlay.querySelector(".service-modal-btn.save");

            const species = nameInput.value.trim();
            const statusValue = statusInput.value;

            message.style.display = "none";

            if (!species) {
                message.textContent = "Species name is required.";
                message.style.display = "block";
                return;
            }

            const formData = new FormData();
            formData.append("pet_reference_action", isEdit ? "edit_species" : "add_species");
            formData.append("species", species);
            formData.append("status", statusValue);

            if (isEdit) formData.append("species_id", speciesId);

            saveButton.disabled = true;
            saveButton.textContent = "Saving...";

            fetch("system_variables.php", {
                method: "POST",
                body: formData
            })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                if (!data.success) {
                    throw new Error(data.message || "Unable to save species.");
                }

                closeModal();

                showMessageModal(
                    "success",
                    isEdit ? "Species Updated" : "Species Added",
                    data.message
                );

                setTimeout(function () {
                    window.location.reload();
                }, 900);
            })
            .catch(function (error) {
                message.textContent = error.message;
                message.style.display = "block";
                saveButton.disabled = false;
                saveButton.textContent = isEdit ? "Save Changes" : "Save Species";
            });
        });
    }

    function createBreedModal(mode, button) {
        removeModal("petBreedModalOverlay");

        const isEdit = mode === "edit";
        const breedId = isEdit ? button.dataset.id : "";
        const breedName = isEdit ? button.dataset.breed : "";
        const selectedSpeciesId = isEdit ? button.dataset.speciesId : "";

        const status = isEdit
            ? (button.closest("tr")?.querySelector(".status")?.textContent.trim() || "Active")
            : "Active";

        const speciesOptions = getSpeciesOptions(selectedSpeciesId);

        if (!speciesOptions) {
            showMessageModal(
                "error",
                "No Species Available",
                "Please add at least one species before adding a breed."
            );
            return;
        }

        const overlay = document.createElement("div");
        overlay.className = "service-modal-overlay";
        overlay.id = "petBreedModalOverlay";

        overlay.innerHTML = `
            <div class="service-modal" role="dialog" aria-modal="true">
                <div class="service-modal-header">
                    <div class="service-modal-title">
                        <div class="service-modal-title-icon">
                            <i class="fa-solid fa-paw"></i>
                        </div>
                        <div>
                            <h3>${isEdit ? "Edit Breed" : "Add Breed"}</h3>
                            <p>${isEdit
                                ? "Update the selected pet breed."
                                : "Add a breed and assign it to a species."}</p>
                        </div>
                    </div>
                    <button type="button" class="service-modal-close" data-close-breed>
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <form id="petBreedForm">
                    <div class="service-modal-body">
                        <div class="service-form-group">
                            <label for="petBreedSpecies">
                                Species <span class="required">*</span>
                            </label>
                            <select id="petBreedSpecies" class="service-form-select" required>
                                <option value="">Select species</option>
                                ${speciesOptions}
                            </select>
                        </div>

                        <div class="service-form-group">
                            <label for="petBreedName">
                                Breed Name <span class="required">*</span>
                            </label>
                            <input type="text" id="petBreedName"
                                class="service-form-input"
                                placeholder="e.g. Labrador Retriever"
                                value="${escapeHtml(breedName)}"
                                required>
                        </div>

                        <div class="service-form-group">
                            <label for="petBreedStatus">Status</label>
                            <select id="petBreedStatus" class="service-form-select">
                                <option value="Active" ${status === "Active" ? "selected" : ""}>Active</option>
                                <option value="Inactive" ${status === "Inactive" ? "selected" : ""}>Inactive</option>
                            </select>
                        </div>

                        <div class="service-modal-message" id="petBreedModalMessage"></div>
                    </div>

                    <div class="service-modal-footer">
                        <button type="button" class="service-modal-btn cancel" data-close-breed>
                            Cancel
                        </button>
                        <button type="submit" class="service-modal-btn save">
                            ${isEdit ? "Save Changes" : "Save Breed"}
                        </button>
                    </div>
                </form>
            </div>
        `;

        document.body.appendChild(overlay);
        overlay.classList.add("show");

        function closeModal() {
            overlay.remove();
        }

        overlay.querySelectorAll("[data-close-breed]").forEach(function (button) {
            button.addEventListener("click", closeModal);
        });

        overlay.addEventListener("click", function (event) {
            if (event.target === overlay) closeModal();
        });

        document.getElementById("petBreedForm").addEventListener("submit", function (event) {
            event.preventDefault();

            const speciesInput = document.getElementById("petBreedSpecies");
            const nameInput = document.getElementById("petBreedName");
            const statusInput = document.getElementById("petBreedStatus");
            const message = document.getElementById("petBreedModalMessage");
            const saveButton = overlay.querySelector(".service-modal-btn.save");

            const speciesId = speciesInput.value;
            const breed = nameInput.value.trim();
            const statusValue = statusInput.value;

            message.style.display = "none";

            if (!speciesId) {
                message.textContent = "Please select a species.";
                message.style.display = "block";
                return;
            }

            if (!breed) {
                message.textContent = "Breed name is required.";
                message.style.display = "block";
                return;
            }

            const formData = new FormData();
            formData.append("pet_reference_action", isEdit ? "edit_breed" : "add_breed");
            formData.append("species_id", speciesId);
            formData.append("breed", breed);
            formData.append("status", statusValue);

            if (isEdit) formData.append("breed_id", breedId);

            saveButton.disabled = true;
            saveButton.textContent = "Saving...";

            fetch("system_variables.php", {
                method: "POST",
                body: formData
            })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                if (!data.success) {
                    throw new Error(data.message || "Unable to save breed.");
                }

                closeModal();

                showMessageModal(
                    "success",
                    isEdit ? "Breed Updated" : "Breed Added",
                    data.message
                );

                setTimeout(function () {
                    window.location.reload();
                }, 900);
            })
            .catch(function (error) {
                message.textContent = error.message;
                message.style.display = "block";
                saveButton.disabled = false;
                saveButton.textContent = isEdit ? "Save Changes" : "Save Breed";
            });
        });
    }

    function deleteSpecies(button) {
        const id = button.dataset.id;
        const name = button.dataset.species;

        showConfirmModal(
            "Delete Species?",
            `Are you sure you want to delete "${name}"? This action cannot be undone.`,
            "Delete",
            function () {
                const formData = new FormData();
                formData.append("pet_reference_action", "delete_species");
                formData.append("species_id", id);

                fetch("system_variables.php", {
                    method: "POST",
                    body: formData
                })
                .then(function (response) {
                    return response.json();
                })
                .then(function (data) {
                    if (!data.success) throw new Error(data.message || "Unable to delete species.");

                    showMessageModal("success", "Species Deleted", data.message);

                    setTimeout(function () {
                        window.location.reload();
                    }, 900);
                })
                .catch(function (error) {
                    showMessageModal("error", "Unable to Delete Species", error.message);
                });
            }
        );
    }

    function deleteBreed(button) {
        const id = button.dataset.id;
        const name = button.dataset.breed;

        showConfirmModal(
            "Delete Breed?",
            `Are you sure you want to delete "${name}"? This action cannot be undone.`,
            "Delete",
            function () {
                const formData = new FormData();
                formData.append("pet_reference_action", "delete_breed");
                formData.append("breed_id", id);

                fetch("system_variables.php", {
                    method: "POST",
                    body: formData
                })
                .then(function (response) {
                    return response.json();
                })
                .then(function (data) {
                    if (!data.success) throw new Error(data.message || "Unable to delete breed.");

                    showMessageModal("success", "Breed Deleted", data.message);

                    setTimeout(function () {
                        window.location.reload();
                    }, 900);
                })
                .catch(function (error) {
                    showMessageModal("error", "Unable to Delete Breed", error.message);
                });
            }
        );
    }

    const addSpeciesButton = document.getElementById("addPetSpeciesBtn");
    if (addSpeciesButton) {
        addSpeciesButton.addEventListener("click", function () {
            createSpeciesModal("add", null);
        });
    }

    const addBreedButton = document.getElementById("addPetBreedBtn");
    if (addBreedButton) {
        addBreedButton.addEventListener("click", function () {
            createBreedModal("add", null);
        });
    }

    petRecordsContent
        .querySelectorAll("#pet-species .icon-action.edit")
        .forEach(function (button) {
            button.addEventListener("click", function () {
                createSpeciesModal("edit", button);
            });
        });

    petRecordsContent
        .querySelectorAll("#pet-species .icon-action.delete")
        .forEach(function (button) {
            button.addEventListener("click", function () {
                deleteSpecies(button);
            });
        });

    petRecordsContent
        .querySelectorAll("#pet-breeds .icon-action.edit")
        .forEach(function (button) {
            button.addEventListener("click", function () {
                createBreedModal("edit", button);
            });
        });

    petRecordsContent
        .querySelectorAll("#pet-breeds .icon-action.delete")
        .forEach(function (button) {
            button.addEventListener("click", function () {
                deleteBreed(button);
            });
        });

});


/* ==========================================================
   INVENTORY SYSTEM VARIABLES
   Categories / Units / Suppliers
   Isolated from existing Billing and Pet Records handlers.
========================================================== */

document.addEventListener("DOMContentLoaded", function () {

    const inventoryContent =
        document.getElementById("inventoryContent");

    if (!inventoryContent) {
        return;
    }

    function inventoryEscape(value) {
        return String(value ?? "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function inventoryCloseModal(id) {
        const overlay = document.getElementById(id);
        if (overlay) {
            overlay.remove();
        }
    }

    function inventoryMessage(type, title, message) {

        const id = "inventoryMessageModalOverlay";
        inventoryCloseModal(id);

        const icon =
            type === "success"
                ? "fa-circle-check"
                : "fa-circle-exclamation";

        const iconClass =
            type === "success"
                ? "success"
                : "error";

        const overlay = document.createElement("div");
        overlay.className = "service-modal-overlay";
        overlay.id = id;

        overlay.innerHTML = `
            <div class="service-modal" role="dialog" aria-modal="true">

                <div class="service-modal-header">

                    <div class="service-modal-title">

                        <div class="service-modal-title-icon ${iconClass}">
                            <i class="fa-solid ${icon}"></i>
                        </div>

                        <div>
                            <h3>${inventoryEscape(title)}</h3>
                            <p>Inventory system variables</p>
                        </div>

                    </div>

                    <button
                        type="button"
                        class="service-modal-close"
                        data-inventory-message-close
                        aria-label="Close"
                    >
                        <i class="fa-solid fa-xmark"></i>
                    </button>

                </div>

                <div class="service-modal-body">

                    <div class="inventory-message-text">
                        ${inventoryEscape(message)}
                    </div>

                </div>

                <div class="service-modal-footer">

                    <button
                        type="button"
                        class="service-modal-btn save"
                        data-inventory-message-close
                    >
                        OK
                    </button>

                </div>

            </div>
        `;

        document.body.appendChild(overlay);

        overlay.querySelectorAll("[data-inventory-message-close]")
            .forEach(function (button) {
                button.addEventListener("click", function () {
                    inventoryCloseModal(id);
                });
            });
    }

    function inventoryConfirm(title, message, confirmText, onConfirm) {

        const id = "inventoryConfirmModalOverlay";
        inventoryCloseModal(id);

        const overlay = document.createElement("div");
        overlay.className = "service-modal-overlay";
        overlay.id = id;

        overlay.innerHTML = `
            <div class="service-modal" role="dialog" aria-modal="true">

                <div class="service-modal-header">

                    <div class="service-modal-title">

                        <div class="service-modal-title-icon warning">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </div>

                        <div>
                            <h3>${inventoryEscape(title)}</h3>
                            <p>Inventory system variables</p>
                        </div>

                    </div>

                    <button
                        type="button"
                        class="service-modal-close"
                        data-inventory-cancel
                        aria-label="Close"
                    >
                        <i class="fa-solid fa-xmark"></i>
                    </button>

                </div>

                <div class="service-modal-body">

                    <div class="inventory-message-text">
                        ${inventoryEscape(message)}
                    </div>

                </div>

                <div class="service-modal-footer">

                    <button
                        type="button"
                        class="service-modal-btn cancel"
                        data-inventory-cancel
                    >
                        Cancel
                    </button>

                    <button
                        type="button"
                        class="service-modal-btn save"
                        data-inventory-confirm
                    >
                        ${inventoryEscape(confirmText)}
                    </button>

                </div>

            </div>
        `;

        document.body.appendChild(overlay);

        overlay.querySelectorAll("[data-inventory-cancel]")
            .forEach(function (button) {
                button.addEventListener("click", function () {
                    inventoryCloseModal(id);
                });
            });

        overlay.querySelector("[data-inventory-confirm]")
            .addEventListener("click", function () {
                inventoryCloseModal(id);
                onConfirm();
            });
    }

    function inventorySubmit(formData) {

        return fetch("system_variables.php", {
            method: "POST",
            body: formData
        })
        .then(function (response) {
            return response.json();
        });
    }

    function createInventoryModal(type, mode, button) {

        const overlayId =
            "inventory" +
            type.charAt(0).toUpperCase() +
            type.slice(1) +
            "ModalOverlay";

        inventoryCloseModal(overlayId);

        const isEdit = mode === "edit";

        let values = {
            id: "",
            name: "",
            description: "",
            categoryId: "",
            abbreviation: "",
            contactPerson: "",
            contactNumber: "",
            email: "",
            address: "",
            status: "Active"
        };

        if (isEdit && button) {
            values.id = button.dataset.id || "";
            values.name = button.dataset.name || "";
            values.description = button.dataset.description || "";
            values.categoryId = button.dataset.categoryId || "";
            values.abbreviation = button.dataset.abbreviation || "";
            values.contactPerson = button.dataset.contactPerson || "";
            values.contactNumber = button.dataset.contactNumber || "";
            values.email = button.dataset.email || "";
            values.address = button.dataset.address || "";
            values.status = button.dataset.status || "Active";
        }

        let title = "";
        let subtitle = "";
        let icon = "";
        let body = "";

        if (type === "category") {

            title = isEdit ? "Edit Item Category" : "Add Item Category";
            subtitle = isEdit
                ? "Update the selected inventory category."
                : "Add a category that can be used for inventory items.";
            icon = "fa-layer-group";

            body = `
                <div class="service-form-group">
                    <label for="inventoryCategoryName">
                        Category Name
                        <span class="required">*</span>
                    </label>

                    <input
                        type="text"
                        id="inventoryCategoryName"
                        class="service-form-input"
                        value="${inventoryEscape(values.name)}"
                        required
                        autocomplete="off"
                    >
                </div>

                <div class="service-form-group">
                    <label for="inventoryCategoryDescription">
                        Description
                    </label>

                    <textarea
                        id="inventoryCategoryDescription"
                        class="service-form-input inventory-textarea"
                        rows="3"
                    >${inventoryEscape(values.description)}</textarea>
                </div>

                <div class="service-form-group">
                    <label for="inventoryCategoryStatus">Status</label>

                    <select
                        id="inventoryCategoryStatus"
                        class="service-form-select"
                    >
                        <option value="Active" ${values.status === "Active" ? "selected" : ""}>Active</option>
                        <option value="Inactive" ${values.status === "Inactive" ? "selected" : ""}>Inactive</option>
                    </select>
                </div>
            `;

        } else if (type === "subcategory") {

            title = isEdit ? "Edit Subcategory" : "Add Subcategory";
            subtitle = isEdit
                ? "Update the selected inventory subcategory."
                : "Add a subcategory under an inventory category.";
            icon = "fa-layer-group";

            const categoryOptions = Array.isArray(window.inventoryCategories)
                ? window.inventoryCategories.map(function (category) {
                    return `
                        <option
                            value="${inventoryEscape(category.category_id)}"
                            ${String(category.category_id) === String(values.categoryId) ? "selected" : ""}
                        >
                            ${inventoryEscape(category.category_name)}
                        </option>
                    `;
                }).join("")
                : "";

            body = `
                <div class="service-form-group">
                    <label for="inventorySubcategoryCategory">
                        Category
                        <span class="required">*</span>
                    </label>

                    <select
                        id="inventorySubcategoryCategory"
                        class="service-form-select"
                        required
                    >
                        <option value="">Select category</option>
                        ${categoryOptions}
                    </select>
                </div>

                <div class="service-form-group">
                    <label for="inventorySubcategoryName">
                        Subcategory Name
                        <span class="required">*</span>
                    </label>

                    <input
                        type="text"
                        id="inventorySubcategoryName"
                        class="service-form-input"
                        value="${inventoryEscape(values.name)}"
                        placeholder="e.g. Antibiotic"
                        required
                        autocomplete="off"
                    >
                </div>

                <div class="service-form-group">
                    <label for="inventorySubcategoryDescription">
                        Description
                    </label>

                    <textarea
                        id="inventorySubcategoryDescription"
                        class="service-form-input inventory-textarea"
                        rows="3"
                        placeholder="Describe this subcategory"
                    >${inventoryEscape(values.description)}</textarea>
                </div>

                <div class="service-form-group">
                    <label for="inventorySubcategoryStatus">Status</label>

                    <select
                        id="inventorySubcategoryStatus"
                        class="service-form-select"
                    >
                        <option value="Active" ${values.status === "Active" ? "selected" : ""}>Active</option>
                        <option value="Inactive" ${values.status === "Inactive" ? "selected" : ""}>Inactive</option>
                    </select>
                </div>
            `;

        } else if (type === "unit") {

            title = isEdit ? "Edit Unit" : "Add Unit";
            subtitle = isEdit
                ? "Update the selected inventory unit."
                : "Add a unit used for inventory quantities.";
            icon = "fa-scale-balanced";

            body = `
                <div class="inventory-form-grid">

                    <div class="service-form-group">
                        <label for="inventoryUnitName">
                            Unit Name
                            <span class="required">*</span>
                        </label>

                        <input
                            type="text"
                            id="inventoryUnitName"
                            class="service-form-input"
                            value="${inventoryEscape(values.name)}"
                            placeholder="e.g. Piece"
                            required
                            autocomplete="off"
                        >
                    </div>

                    <div class="service-form-group">
                        <label for="inventoryUnitAbbreviation">
                            Abbreviation
                            <span class="required">*</span>
                        </label>

                        <input
                            type="text"
                            id="inventoryUnitAbbreviation"
                            class="service-form-input"
                            value="${inventoryEscape(values.abbreviation)}"
                            placeholder="e.g. pc"
                            required
                            autocomplete="off"
                        >
                    </div>

                </div>

                <div class="service-form-group">
                    <label for="inventoryUnitStatus">Status</label>

                    <select
                        id="inventoryUnitStatus"
                        class="service-form-select"
                    >
                        <option value="Active" ${values.status === "Active" ? "selected" : ""}>Active</option>
                        <option value="Inactive" ${values.status === "Inactive" ? "selected" : ""}>Inactive</option>
                    </select>
                </div>
            `;

        } else {

            title = isEdit ? "Edit Supplier" : "Add Supplier";
            subtitle = isEdit
                ? "Update the selected supplier."
                : "Add a supplier for future Stock In transactions.";
            icon = "fa-truck";

            body = `
                <div class="service-form-group">
                    <label for="inventorySupplierName">
                        Supplier Name
                        <span class="required">*</span>
                    </label>

                    <input
                        type="text"
                        id="inventorySupplierName"
                        class="service-form-input"
                        value="${inventoryEscape(values.name)}"
                        required
                        autocomplete="off"
                    >
                </div>

                <div class="inventory-form-grid">

                    <div class="service-form-group">
                        <label for="inventorySupplierContactPerson">Contact Person</label>

                        <input
                            type="text"
                            id="inventorySupplierContactPerson"
                            class="service-form-input"
                            value="${inventoryEscape(values.contactPerson)}"
                            autocomplete="off"
                        >
                    </div>

                    <div class="service-form-group">
                        <label for="inventorySupplierContactNumber">Contact Number</label>

                        <input
                            type="text"
                            id="inventorySupplierContactNumber"
                            class="service-form-input"
                            value="${inventoryEscape(values.contactNumber)}"
                            autocomplete="off"
                        >
                    </div>

                </div>

                <div class="service-form-group">
                    <label for="inventorySupplierEmail">Email</label>

                    <input
                        type="email"
                        id="inventorySupplierEmail"
                        class="service-form-input"
                        value="${inventoryEscape(values.email)}"
                        autocomplete="off"
                    >
                </div>

                <div class="service-form-group">
                    <label for="inventorySupplierAddress">Address</label>

                    <textarea
                        id="inventorySupplierAddress"
                        class="service-form-input inventory-textarea"
                        rows="3"
                    >${inventoryEscape(values.address)}</textarea>
                </div>

                <div class="service-form-group">
                    <label for="inventorySupplierStatus">Status</label>

                    <select
                        id="inventorySupplierStatus"
                        class="service-form-select"
                    >
                        <option value="Active" ${values.status === "Active" ? "selected" : ""}>Active</option>
                        <option value="Inactive" ${values.status === "Inactive" ? "selected" : ""}>Inactive</option>
                    </select>
                </div>
            `;
        }

        const overlay = document.createElement("div");
        overlay.className = "service-modal-overlay";
        overlay.id = overlayId;

        overlay.innerHTML = `
            <div
                class="service-modal"
                role="dialog"
                aria-modal="true"
            >

                <div class="service-modal-header">

                    <div class="service-modal-title">

                        <div class="service-modal-title-icon">
                            <i class="fa-solid ${icon}"></i>
                        </div>

                        <div>
                            <h3>${inventoryEscape(title)}</h3>
                            <p>${inventoryEscape(subtitle)}</p>
                        </div>

                    </div>

                    <button
                        type="button"
                        class="service-modal-close"
                        data-inventory-modal-close
                        aria-label="Close"
                    >
                        <i class="fa-solid fa-xmark"></i>
                    </button>

                </div>

                <form id="${overlayId}Form">

                    <div class="service-modal-body">

                        ${body}

                        <div
                            class="service-modal-message"
                            id="${overlayId}Message"
                        ></div>

                    </div>

                    <div class="service-modal-footer">

                        <button
                            type="button"
                            class="service-modal-btn cancel"
                            data-inventory-modal-close
                        >
                            Cancel
                        </button>

                        <button
                            type="submit"
                            class="service-modal-btn save"
                        >
                            ${isEdit ? "Save Changes" : "Add"}
                        </button>

                    </div>

                </form>

            </div>
        `;

        document.body.appendChild(overlay);

        overlay.querySelectorAll("[data-inventory-modal-close]")
            .forEach(function (button) {
                button.addEventListener("click", function () {
                    inventoryCloseModal(overlayId);
                });
            });

        const form =
            document.getElementById(overlayId + "Form");

        const message =
            document.getElementById(overlayId + "Message");

        form.addEventListener("submit", function (event) {

            event.preventDefault();

            const formData = new FormData();

            formData.append(
                "inventory_variable_action",
                isEdit
                    ? "edit_" + type
                    : "add_" + type
            );

            if (isEdit) {
                let idField = "";

                if (type === "category") {
                    idField = "category_id";
                } else if (type === "subcategory") {
                    idField = "subcategory_id";
                } else if (type === "unit") {
                    idField = "unit_id";
                } else {
                    idField = "supplier_id";
                }

                formData.append(idField, values.id);
            }

            if (type === "category") {

                formData.append(
                    "category_name",
                    document.getElementById("inventoryCategoryName").value.trim()
                );

                formData.append(
                    "description",
                    document.getElementById("inventoryCategoryDescription").value.trim()
                );

                formData.append(
                    "status",
                    document.getElementById("inventoryCategoryStatus").value
                );

            } else if (type === "subcategory") {

                formData.append(
                    "category_id",
                    document.getElementById("inventorySubcategoryCategory").value
                );

                formData.append(
                    "subcategory_name",
                    document.getElementById("inventorySubcategoryName").value.trim()
                );

                formData.append(
                    "description",
                    document.getElementById("inventorySubcategoryDescription").value.trim()
                );

                formData.append(
                    "status",
                    document.getElementById("inventorySubcategoryStatus").value
                );

            } else if (type === "unit") {

                formData.append(
                    "unit_name",
                    document.getElementById("inventoryUnitName").value.trim()
                );

                formData.append(
                    "abbreviation",
                    document.getElementById("inventoryUnitAbbreviation").value.trim()
                );

                formData.append(
                    "status",
                    document.getElementById("inventoryUnitStatus").value
                );

            } else {

                formData.append(
                    "supplier_name",
                    document.getElementById("inventorySupplierName").value.trim()
                );

                formData.append(
                    "contact_person",
                    document.getElementById("inventorySupplierContactPerson").value.trim()
                );

                formData.append(
                    "contact_number",
                    document.getElementById("inventorySupplierContactNumber").value.trim()
                );

                formData.append(
                    "email",
                    document.getElementById("inventorySupplierEmail").value.trim()
                );

                formData.append(
                    "address",
                    document.getElementById("inventorySupplierAddress").value.trim()
                );

                formData.append(
                    "status",
                    document.getElementById("inventorySupplierStatus").value
                );
            }

            message.textContent = "";
            message.className = "service-modal-message";

            inventorySubmit(formData)
                .then(function (data) {

                    if (!data.success) {
                        throw new Error(
                            data.message ||
                            "Unable to save the inventory variable."
                        );
                    }

                    inventoryCloseModal(overlayId);

                    inventoryMessage(
                        "success",
                        isEdit ? "Updated Successfully" : "Added Successfully",
                        data.message
                    );

                    setTimeout(function () {
                        window.location.reload();
                    }, 900);

                })
                .catch(function (error) {

                    message.textContent = error.message;
                    message.className =
                        "service-modal-message error";

                });

        });

    }

    function deleteInventoryVariable(type, button) {

        const id = button.dataset.id || "";
        const name = button.dataset.name || "";

        inventoryConfirm(
            "Delete " +
                (type === "category"
                    ? "Category"
                    : type === "subcategory"
                        ? "Subcategory"
                        : type === "unit"
                            ? "Unit"
                            : "Supplier") +
                "?",
            `Are you sure you want to delete "${name}"? This action cannot be undone.`,
            "Delete",
            function () {

                const formData = new FormData();

                formData.append(
                    "inventory_variable_action",
                    "delete_" + type
                );

                let idField = "";

                if (type === "category") {
                    idField = "category_id";
                } else if (type === "subcategory") {
                    idField = "subcategory_id";
                } else if (type === "unit") {
                    idField = "unit_id";
                } else {
                    idField = "supplier_id";
                }

                formData.append(idField, id);

                inventorySubmit(formData)
                    .then(function (data) {

                        if (!data.success) {
                            throw new Error(
                                data.message ||
                                "Unable to delete the inventory variable."
                            );
                        }

                        inventoryMessage(
                            "success",
                            "Deleted Successfully",
                            data.message
                        );

                        setTimeout(function () {
                            window.location.reload();
                        }, 900);

                    })
                    .catch(function (error) {

                        inventoryMessage(
                            "error",
                            "Unable to Delete",
                            error.message
                        );

                    });

            }
        );
    }

    const addCategoryButton =
        document.getElementById("addInventoryCategoryBtn");

    if (addCategoryButton) {
        addCategoryButton.addEventListener("click", function () {
            createInventoryModal("category", "add", null);
        });
    }

    const addSubcategoryButton =
        document.getElementById("addInventorySubcategoryBtn");

    if (addSubcategoryButton) {
        addSubcategoryButton.addEventListener("click", function () {
            createInventoryModal("subcategory", "add", null);
        });
    }

    inventoryContent
        .querySelectorAll(".inventory-subcategory-edit")
        .forEach(function (button) {
            button.addEventListener("click", function () {
                createInventoryModal("subcategory", "edit", button);
            });
        });

    inventoryContent
        .querySelectorAll(".inventory-subcategory-delete")
        .forEach(function (button) {
            button.addEventListener("click", function () {
                deleteInventoryVariable("subcategory", button);
            });
        });

    const addUnitButton =
        document.getElementById("addInventoryUnitBtn");

    if (addUnitButton) {
        addUnitButton.addEventListener("click", function () {
            createInventoryModal("unit", "add", null);
        });
    }

    const addSupplierButton =
        document.getElementById("addInventorySupplierBtn");

    if (addSupplierButton) {
        addSupplierButton.addEventListener("click", function () {
            createInventoryModal("supplier", "add", null);
        });
    }

    inventoryContent
        .querySelectorAll(".inventory-category-edit")
        .forEach(function (button) {
            button.addEventListener("click", function () {
                createInventoryModal("category", "edit", button);
            });
        });

    inventoryContent
        .querySelectorAll(".inventory-category-delete")
        .forEach(function (button) {
            button.addEventListener("click", function () {
                deleteInventoryVariable("category", button);
            });
        });

    inventoryContent
        .querySelectorAll(".inventory-unit-edit")
        .forEach(function (button) {
            button.addEventListener("click", function () {
                createInventoryModal("unit", "edit", button);
            });
        });

    inventoryContent
        .querySelectorAll(".inventory-unit-delete")
        .forEach(function (button) {
            button.addEventListener("click", function () {
                deleteInventoryVariable("unit", button);
            });
        });

    inventoryContent
        .querySelectorAll(".inventory-supplier-edit")
        .forEach(function (button) {
            button.addEventListener("click", function () {
                createInventoryModal("supplier", "edit", button);
            });
        });

    inventoryContent
        .querySelectorAll(".inventory-supplier-delete")
        .forEach(function (button) {
            button.addEventListener("click", function () {
                deleteInventoryVariable("supplier", button);
            });
        });

});

/* =========================================================
   WEBSITE PRODUCTS - ADD MODAL
========================================================= */

document.addEventListener("DOMContentLoaded", function () {

    const addWebsiteProductBtn =
        document.getElementById("addWebsiteProductBtn");

    const websiteProductModal =
        document.getElementById("websiteProductModal");

    const closeWebsiteProductModal =
        document.getElementById("closeWebsiteProductModal");

    const cancelWebsiteProductModal =
        document.getElementById("cancelWebsiteProductModal");

    const websiteProductItem =
        document.getElementById("websiteProductItem");

    const websiteProductPetTypeField =
        document.getElementById("websiteProductPetTypeField");

    const websiteProductPetType =
        document.getElementById("websiteProductPetType");    


    /*
    |--------------------------------------------------------------------------
    | CHECK REQUIRED ELEMENTS
    |--------------------------------------------------------------------------
    */

    if (
        !addWebsiteProductBtn ||
        !websiteProductModal
    ) {
        return;
    }

    /* =========================================================
      PET TYPE BEHAVIOR
       ========================================================= */

 function updateWebsiteProductPetType() {

    if (!websiteProductItem) {
        return;
    }

    const selectedOption =
        websiteProductItem.options[
            websiteProductItem.selectedIndex
        ];

    if (!selectedOption || !selectedOption.value) {

        websiteProductPetTypeField.style.display = "block";
        websiteProductPetType.required = false;
        websiteProductPetType.value = "";

        return;
    }

    const category =
        (
            selectedOption.dataset.category || ""
        ).trim().toLowerCase();


    if (category === "pet food") {

        websiteProductPetTypeField.style.display = "block";

        websiteProductPetType.required = true;

    } else {

        websiteProductPetTypeField.style.display = "none";

        websiteProductPetType.required = false;
        websiteProductPetType.value = "";

    }

}


    /*
    |--------------------------------------------------------------------------
    | OPEN MODAL
    |--------------------------------------------------------------------------
    */

    function openWebsiteProductModal() {

        websiteProductModal.style.display = "flex";

        requestAnimationFrame(function () {
            websiteProductModal.classList.add("show");
        });

    }


    /*
    |--------------------------------------------------------------------------
    | CLOSE MODAL
    |--------------------------------------------------------------------------
    */

    function closeModal() {

        websiteProductModal.classList.remove("show");

        setTimeout(function () {
            websiteProductModal.style.display = "none";
        }, 200);

    }


    /*
    |--------------------------------------------------------------------------
    | ADD PRODUCT BUTTON
    |--------------------------------------------------------------------------
    */

    addWebsiteProductBtn.addEventListener(
        "click",
        function () {

            openWebsiteProductModal();

        }
    );


    /*
    |--------------------------------------------------------------------------
    | X BUTTON
    |--------------------------------------------------------------------------
    */

    if (closeWebsiteProductModal) {

        closeWebsiteProductModal.addEventListener(
            "click",
            closeModal
        );

    }


    /*
    |--------------------------------------------------------------------------
    | CANCEL BUTTON
    |--------------------------------------------------------------------------
    */

    if (cancelWebsiteProductModal) {

        cancelWebsiteProductModal.addEventListener(
            "click",
            closeModal
        );

    }


    /*
    |--------------------------------------------------------------------------
    | CLICK OUTSIDE MODAL
    |--------------------------------------------------------------------------
    */

    websiteProductModal.addEventListener(
        "click",
        function (event) {

            if (
                event.target === websiteProductModal
            ) {

                closeModal();

            }

        }
    );

    if (websiteProductItem) {
        websiteProductItem.addEventListener(
            "change",
            updateWebsiteProductPetType
        );
        
        updateWebsiteProductPetType();
    }    



});

/* =========================================================
   WEBSITE PRODUCTS - SAVE PRODUCT
========================================================= */

document.addEventListener("DOMContentLoaded", function () {

    const addWebsiteProductForm =
        document.getElementById("addWebsiteProductForm");

    const websiteProductModal =
        document.getElementById("websiteProductModal");

    const saveWebsiteProductBtn =
        document.getElementById("saveWebsiteProductBtn");


    if (!addWebsiteProductForm) {
        return;
    }


    addWebsiteProductForm.addEventListener(
        "submit",
        async function (event) {

            event.preventDefault();


            if (saveWebsiteProductBtn) {

                saveWebsiteProductBtn.disabled = true;

                saveWebsiteProductBtn.innerHTML =
                    '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';

            }


            const formData =
                new FormData(addWebsiteProductForm);


            try {

                const response = await fetch(
                    window.location.href,
                    {
                        method: "POST",
                        body: formData
                    }
                );


                const result =
                    await response.json();


                if (!result.success) {

                    alert(
                        result.message ||
                        "Unable to add website product."
                    );

                    return;

                }


                alert(
                    result.message ||
                    "Website product added successfully."
                );


                if (websiteProductModal) {

                    websiteProductModal.classList.remove("show");

                    setTimeout(function () {

                        websiteProductModal.style.display =
                            "none";

                    }, 200);

                }


                /*
                 * Reload the page so the newly added
                 * product appears in the Website Products table.
                 */

                window.location.reload();


            } catch (error) {

                console.error(
                    "Website Product Error:",
                    error
                );

                alert(
                    "Something went wrong while adding the website product."
                );


            } finally {

                if (saveWebsiteProductBtn) {

                    saveWebsiteProductBtn.disabled = false;

                    saveWebsiteProductBtn.innerHTML =
                        '<i class="fa-solid fa-check"></i> Add Product';

                }

            }

        }
    );

});

/* =========================================================
   WEBSITE PRODUCTS - EDIT PRODUCT
========================================================= */

document.addEventListener("DOMContentLoaded", function () {

    const editButtons = document.querySelectorAll(
        '.icon-action.edit[title="Edit Website Product"]'
    );

    editButtons.forEach(function (button) {

        button.addEventListener("click", async function () {

            const productId = button.dataset.id;

            if (!productId) {
                alert("Website product ID is missing.");
                return;
            }

            try {

                const formData = new FormData();

                formData.append(
                    "inventory_variable_action",
                    "get_website_product"
                );

                formData.append(
                    "website_product_id",
                    productId
                );

                const response = await fetch(
                    window.location.href,
                    {
                        method: "POST",
                        body: formData
                    }
                );

                const result = await response.json();

                if (!result.success) {
                    alert(
                        result.message ||
                        "Unable to load website product."
                    );
                    return;
                }

                const product = result.product;

                /* Remove old edit modal if there is one */
                const oldModal = document.getElementById(
                    "editWebsiteProductModal"
                );

                if (oldModal) {
                    oldModal.remove();
                }

                const isPetFood =
                    String(product.category_name || "")
                        .toLowerCase() === "pet food";

                const petTypeDisplay =
                    isPetFood ? "block" : "none";

                const petTypeRequired =
                    isPetFood ? "required" : "";

                const currentImage =
                    product.image_path
                        ? '<img src="../' +
                          escapeWebsiteProductHtml(product.image_path) +
                          '" style="width:80px;height:80px;object-fit:cover;border-radius:8px;border:1px solid #e5e7eb;">'
                        : '<span style="color:#9ca3af;">No image</span>';

                const overlay = document.createElement("div");

                overlay.id = "editWebsiteProductModal";
                overlay.className = "variable-modal-overlay";

                overlay.innerHTML =
                    '<div class="variable-modal">' +

                        '<div class="variable-modal-header">' +

                            '<div>' +
                                '<h3>Edit Website Product</h3>' +
                                '<p>Update the website product information.</p>' +
                            '</div>' +

                            '<button type="button" ' +
                                'class="variable-modal-close" ' +
                                'id="closeEditWebsiteProductModal">' +
                                '<i class="fa-solid fa-xmark"></i>' +
                            '</button>' +

                        '</div>' +

                        '<form id="editWebsiteProductForm" enctype="multipart/form-data">' +

                            '<input type="hidden" ' +
                                'name="inventory_variable_action" ' +
                                'value="edit_website_product">' +

                            '<input type="hidden" ' +
                                'name="website_product_id" ' +
                                'value="' + productId + '">' +

                            '<div class="variable-modal-field">' +
                                '<label>Product</label>' +
                                '<input type="text" value="' +
                                    escapeWebsiteProductHtml(product.item_name || "") +
                                    '" readonly>' +
                                '<small>Product name comes from the inventory item.</small>' +
                            '</div>' +

                            '<div class="variable-modal-field">' +
                                '<label>Category</label>' +
                                '<input type="text" value="' +
                                    escapeWebsiteProductHtml(product.category_name || "") +
                                    '" readonly>' +
                            '</div>' +

                            '<div class="variable-modal-field" ' +
                                'id="editWebsiteProductPetTypeField" ' +
                                'style="display:' + petTypeDisplay + ';">' +

                                '<label>Pet Type</label>' +

                                '<select name="pet_type" ' +
                                    'id="editWebsiteProductPetType" ' +
                                    petTypeRequired + '>' +

                                    '<option value="">Select Pet Type</option>' +

                                    '<option value="Dog" ' +
                                        (product.pet_type === "Dog" ? "selected" : "") +
                                    '>Dog</option>' +

                                    '<option value="Cat" ' +
                                        (product.pet_type === "Cat" ? "selected" : "") +
                                    '>Cat</option>' +

                                '</select>' +

                            '</div>' +

                            '<div class="variable-modal-field">' +
                                '<label>Description</label>' +
                                '<textarea name="description" rows="4" ' +
                                    'placeholder="Enter product description">' +
                                    escapeWebsiteProductHtml(product.description || "") +
                                '</textarea>' +
                            '</div>' +

                            '<div class="variable-modal-field">' +
                                '<label>Current Image</label>' +
                                '<div style="margin:8px 0 12px;">' +
                                    currentImage +
                                '</div>' +

                                '<label>Replace Image</label>' +
                                '<input type="file" name="image" ' +
                                    'accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">' +

                                '<small>Leave empty to keep the current image. Maximum file size: 5MB.</small>' +
                            '</div>' +

                            '<div class="variable-modal-field">' +
                                '<label>Status</label>' +

                                '<select name="status" required>' +

                                    '<option value="Visible" ' +
                                        (product.status === "Visible" ? "selected" : "") +
                                    '>Visible</option>' +

                                    '<option value="Hidden" ' +
                                        (product.status === "Hidden" ? "selected" : "") +
                                    '>Hidden</option>' +

                                '</select>' +

                            '</div>' +

                            '<div class="variable-modal-footer">' +

                                '<button type="button" ' +
                                    'class="variable-modal-cancel" ' +
                                    'id="cancelEditWebsiteProductModal">' +
                                    'Cancel' +
                                '</button>' +

                                '<button type="submit" ' +
                                    'class="variable-modal-submit" ' +
                                    'id="saveEditWebsiteProductBtn">' +
                                    '<i class="fa-solid fa-check"></i> Save Changes' +
                                '</button>' +

                            '</div>' +

                        '</form>' +

                    '</div>';

                document.body.appendChild(overlay);

                requestAnimationFrame(function () {
                    overlay.classList.add("show");
                });


                /* CLOSE MODAL */

                function closeEditModal() {

                    overlay.classList.remove("show");

                    setTimeout(function () {
                        overlay.remove();
                    }, 200);

                }

                document.getElementById(
                    "closeEditWebsiteProductModal"
                ).addEventListener(
                    "click",
                    closeEditModal
                );

                document.getElementById(
                    "cancelEditWebsiteProductModal"
                ).addEventListener(
                    "click",
                    closeEditModal
                );

                overlay.addEventListener(
                    "click",
                    function (event) {

                        if (event.target === overlay) {
                            closeEditModal();
                        }

                    }
                );


                /* SAVE CHANGES */

                const editForm = document.getElementById(
                    "editWebsiteProductForm"
                );

                const saveButton = document.getElementById(
                    "saveEditWebsiteProductBtn"
                );

                editForm.addEventListener(
                    "submit",
                    async function (event) {

                        event.preventDefault();

                        saveButton.disabled = true;

                        saveButton.innerHTML =
                            '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';

                        const saveData = new FormData(editForm);

                        try {

                            const saveResponse = await fetch(
                                window.location.href,
                                {
                                    method: "POST",
                                    body: saveData
                                }
                            );

                            const saveResult =
                                await saveResponse.json();

                            if (!saveResult.success) {

                                alert(
                                    saveResult.message ||
                                    "Unable to update website product."
                                );

                                return;
                            }

                            alert(
                                saveResult.message ||
                                "Website product updated successfully."
                            );

                            window.location.reload();

                        } catch (error) {

                            console.error(
                                "Edit Website Product Error:",
                                error
                            );

                            alert(
                                "Something went wrong while updating the website product."
                            );

                        } finally {

                            saveButton.disabled = false;

                            saveButton.innerHTML =
                                '<i class="fa-solid fa-check"></i> Save Changes';

                        }

                    }
                );

            } catch (error) {

                console.error(
                    "Get Website Product Error:",
                    error
                );

                alert(
                    "Something went wrong while loading the website product."
                );

            }

        });

    });

});


/* =========================================================
   WEBSITE PRODUCTS - HTML ESCAPE
========================================================= */

function escapeWebsiteProductHtml(value) {

    return String(value ?? "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");

}

/* =========================================================
   WEBSITE PRODUCTS - DELETE
========================================================= */

document.addEventListener("DOMContentLoaded", function () {

    const deleteButtons = document.querySelectorAll(
        '.icon-action.delete[title="Delete Website Product"]'
    );

    deleteButtons.forEach(function (button) {

        button.addEventListener("click", function () {

            const productId = button.dataset.id;

            if (!productId) {
                showMessageModal(
                    "error",
                    "Unable to Delete Website Product",
                    "Website product ID is missing."
                );
                return;
            }

            const row = button.closest("tr");

            let productName = "this website product";

            if (row) {
                const firstCell = row.querySelector("td");

                if (firstCell) {
                    const strong =
                        firstCell.querySelector("strong");

                    if (strong) {    
                        productName =
                            strong.textContent.trim() ||
                            "this website product";

                    } else {
                        productName =
                        firstCell.textContent.trim() ||
                        "this website product";
                    }            
                }
            }

            window.showConfirmModal(
                "Delete Website Product?",
                `Are you sure you want to delete "${productName}"? This action cannot be undone.`,
                "Delete",
                async function () {

                    button.disabled = true;

                    const formData = new FormData();

                    formData.append(
                        "inventory_variable_action",
                        "delete_website_product"
                    );

                    formData.append(
                        "website_product_id",
                        productId
                    );

                    try {

                        const response = await fetch(
                            window.location.href,
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
                                "Unable to delete website product."
                            );
                        }

                        showMessageModal(
                            "success",
                            "Website Product Deleted",
                            result.message ||
                            "Website product deleted successfully."
                        );

                        setTimeout(function () {
                            window.location.reload();
                        }, 900);

                    } catch (error) {

                        console.error(
                            "Delete Website Product Error:",
                            error
                        );

                        window.showMessageModal(
                            "error",
                            "Unable to Delete Website Product",
                            error.message ||
                            "Something went wrong while deleting the website product."
                        );

                        button.disabled = false;
                    }

                }
            );

        });

    });

});