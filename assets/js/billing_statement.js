
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

        const serviceCategory =
            document.getElementById(
                "serviceCategory"
            );

        const serviceItem =
            document.getElementById(
                "serviceItem"
            );

        const serviceQuantity =
            document.getElementById(
                "serviceQuantity"
            );

        const servicePrice =
            document.getElementById(
                "servicePrice"
            );

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

                    serviceModal.classList.add(
                        "show"
                    );

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
   DATABASE-DRIVEN CATEGORY + SERVICE
====================================================== */


/*
|--------------------------------------------------------------------------
| BUILD CATEGORY LIST
|--------------------------------------------------------------------------
*/

function loadBillingCategories() {

    if (!serviceCategory) {
        return;
    }


    /*
    |--------------------------------------------------------------------------
    | CLEAR CURRENT HARDCODED OPTIONS
    |--------------------------------------------------------------------------
    */

    serviceCategory.innerHTML = "";


    /*
    |--------------------------------------------------------------------------
    | DEFAULT OPTION
    |--------------------------------------------------------------------------
    */

    const defaultCategory =
        document.createElement("option");


    defaultCategory.value = "";

    defaultCategory.textContent =
        "Select Category";


    serviceCategory.appendChild(
        defaultCategory
    );


    /*
    |--------------------------------------------------------------------------
    | GET CATEGORIES FROM SERVICES
    |--------------------------------------------------------------------------
    */

    const categories = [];


    if (
        Array.isArray(
            window.billingServices
        )
    ) {

        window.billingServices.forEach(
            function (service) {

                if (
                    service.category_name &&
                    !categories.includes(
                        service.category_name
                    )
                ) {

                    categories.push(
                        service.category_name
                    );

                }

            }
        );

    }


    /*
    |--------------------------------------------------------------------------
    | ADD SERVICE CATEGORIES
    |--------------------------------------------------------------------------
    */

    categories.forEach(
        function (category) {

            const option =
                document.createElement(
                    "option"
                );


            option.value =
                category;


            option.textContent =
                category;


            serviceCategory.appendChild(
                option
            );

        }
    );


    /*
    |--------------------------------------------------------------------------
    | ADD MEDICATION CATEGORY
    |--------------------------------------------------------------------------
    */

    if (
        Array.isArray(
            window.billingMedications
        ) &&
        window.billingMedications.length > 0
    ) {

        const medicationOption =
            document.createElement(
                "option"
            );


        medicationOption.value =
            "Medication";


        medicationOption.textContent =
            "Medication";


        serviceCategory.appendChild(
            medicationOption
        );

    }

}


/*
|--------------------------------------------------------------------------
| PET WEIGHT FIELD
|--------------------------------------------------------------------------
*/

function getPetWeightGroup() {

    return document.getElementById(
        "billingPetWeightGroup"
    );

}


function createPetWeightField() {

    const existing =
        getPetWeightGroup();


    if (existing) {
        return existing;
    }


    const group =
        document.createElement(
            "div"
        );


    group.className =
        "form-group";


    group.id =
        "billingPetWeightGroup";


    group.innerHTML = `

        <label>
            Pet Weight
            <span class="required">*</span>
        </label>

        <div
            style="
                display:flex;
                align-items:center;
                gap:8px;
            "
        >

            <input
                type="number"
                id="billingPetWeight"
                min="0"
                step="0.01"
                placeholder="Enter pet weight"
            >

            <span>
                kg
            </span>

        </div>

    `;


    /*
    |--------------------------------------------------------------------------
    | INSERT BEFORE QUANTITY / PRICE ROW
    |--------------------------------------------------------------------------
    */

    const quantityRow =
        serviceQuantity
            ?.closest(
                ".service-form-row"
            );


    if (quantityRow) {

        quantityRow.parentNode.insertBefore(
            group,
            quantityRow
        );

    }


    return group;

}


function showPetWeightField() {

    const group =
        createPetWeightField();


    group.style.display =
        "block";

}


function hidePetWeightField() {
    // Pet weight is required for every visit.
    // It is never hidden because the current weight
    // must be recorded on every checkup.
    const group = getPetWeightGroup();

    if (group) {
        group.style.display = "block";
    }
}

/*
|--------------------------------------------------------------------------
| CALCULATE WEIGHT-BASED PRICE
|--------------------------------------------------------------------------
*/

function calculateWeightBasedPrice(
    service,
    weight
) {

    const baseMin =
        parseFloat(
            service.base_min_weight ??
            service.baseMinWeight
        );

    const baseMax =
        parseFloat(
            service.base_max_weight ??
            service.baseMaxWeight
        );

    const basePrice =
        parseFloat(
            service.base_price ??
            service.basePrice
        );

    const weightIncrement =
        parseFloat(
            service.weight_increment ??
            service.weightIncrement
        );

    const priceIncrement =
        parseFloat(
            service.price_increment ??
            service.priceIncrement
        );


    console.log(
        "WEIGHT PRICING DEBUG:",
        {
            weight: weight,
            baseMin: baseMin,
            baseMax: baseMax,
            basePrice: basePrice,
            weightIncrement: weightIncrement,
            priceIncrement: priceIncrement
        }
    );


    if (
        Number.isNaN(weight) ||
        weight < 0
    ) {

        return null;

    }


    if (
        Number.isNaN(baseMin) ||
        Number.isNaN(baseMax) ||
        Number.isNaN(basePrice) ||
        Number.isNaN(weightIncrement) ||
        Number.isNaN(priceIncrement)
    ) {

        console.error(
            "Incomplete pricing rule:",
            service
        );

        return null;

    }


    /*
    |--------------------------------------------------------------------------
    | BASE WEIGHT RANGE
    |--------------------------------------------------------------------------
    */

    if (
        weight >= baseMin &&
        weight <= baseMax
    ) {

        return basePrice;

    }


    /*
    |--------------------------------------------------------------------------
    | ABOVE BASE MAXIMUM
    |--------------------------------------------------------------------------
    */

    if (
        weight > baseMax
    ) {

        const additionalWeight =
            weight - baseMax;


        const additionalIncrements =
            Math.ceil(
                additionalWeight /
                weightIncrement
            );


        return (
            basePrice +
            (
                additionalIncrements *
                priceIncrement
            )
        );

    }


    /*
    |--------------------------------------------------------------------------
    | BELOW STARTING WEIGHT
    |--------------------------------------------------------------------------
    */

    return basePrice;

}

/*
|--------------------------------------------------------------------------
| FIND SELECTED SERVICE
|--------------------------------------------------------------------------
*/

function getSelectedBillingService() {

    const category =
        serviceCategory?.value;


    const serviceName =
        serviceItem?.value;


    if (
        !category ||
        !serviceName
    ) {

        return null;

    }


    /*
    |--------------------------------------------------------------------------
    | MEDICATION
    |--------------------------------------------------------------------------
    */

    if (
        category ===
        "Medication"
    ) {

        if (
            !Array.isArray(
                window.billingMedications
            )
        ) {

            return null;

        }


        return (
            window.billingMedications
                .find(
                    function (medication) {

                        return (
                            medication.medication_name ===
                            serviceName
                        );

                    }
                )
        );

    }


    /*
    |--------------------------------------------------------------------------
    | SERVICES
    |--------------------------------------------------------------------------
    */

    if (
        !Array.isArray(
            window.billingServices
        )
    ) {

        return null;

    }


    return (
        window.billingServices
            .find(
                function (service) {

                    return (
                        service.category_name ===
                            category
                        &&
                        service.service_name ===
                            serviceName
                    );

                }
            )
    );

}


/* =====================================================
   CATEGORY CHANGE
====================================================== */

serviceCategory?.addEventListener(
    "change",
    function () {

        const category =
            this.value;


        serviceItem.innerHTML =
            "";


        servicePrice.value =
            "";


        hidePetWeightField();


        /*
        |--------------------------------------------------------------------------
        | NO CATEGORY
        |--------------------------------------------------------------------------
        */

        if (!category) {

            serviceItem.disabled =
                true;


            const option =
                document.createElement(
                    "option"
                );


            option.value =
                "";


            option.textContent =
                "Select Category First";


            serviceItem.appendChild(
                option
            );


            return;

        }


        /*
        |--------------------------------------------------------------------------
        | ENABLE SERVICE
        |--------------------------------------------------------------------------
        */

        serviceItem.disabled =
            false;


        const defaultOption =
            document.createElement(
                "option"
            );


        defaultOption.value =
            "";


        defaultOption.textContent =
            "Select Service";


        serviceItem.appendChild(
            defaultOption
        );


        /*
        |--------------------------------------------------------------------------
        | GET SERVICES
        |--------------------------------------------------------------------------
        */

        let services = [];


        /*
        |--------------------------------------------------------------------------
        | MEDICATIONS
        |--------------------------------------------------------------------------
        */

        if (
            category ===
            "Medication"
        ) {

            if (
                Array.isArray(
                    window.billingMedications
                )
            ) {

                services =
                    window.billingMedications.map(
                        function (medication) {

                            return {

                                name:
                                    medication.medication_name,

                                pricingType:
                                    "Fixed",

                                fixedPrice:
                                    medication.unit_price

                            };

                        }
                    );

            }

        }


        /*
        |--------------------------------------------------------------------------
        | NORMAL SERVICES
        |--------------------------------------------------------------------------
        */

        else {

            services =
                window.billingServices
                    ?.filter(
                        function (service) {

                            return (
                                service.category_name ===
                                category
                            );

                        }
                    )
                    .map(
                        function (service) {

                            return {

                                name:
                                    service.service_name,

                                pricingType:
                                    service.pricing_type,

                                fixedPrice:
                                    service.fixed_price,

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

                            };

                        }
                    ) || [];

        }


        /*
        |--------------------------------------------------------------------------
        | BUILD SERVICE OPTIONS
        |--------------------------------------------------------------------------
        */

        services.forEach(
            function (service) {

                const option =
                    document.createElement(
                        "option"
                    );


                option.value =
                    service.name;


                option.textContent =
                    service.name;


                option.dataset.pricingType =
                    service.pricingType;


                if (
                    service.fixedPrice !== null &&
                    service.fixedPrice !== undefined
                ) {

                    option.dataset.price =
                        service.fixedPrice;

                }


                serviceItem.appendChild(
                    option
                );

            }
        );

    }
);


/* =====================================================
   SERVICE CHANGE
====================================================== */

serviceItem?.addEventListener(
    "change",
    function () {

        const service =
            getSelectedBillingService();


        servicePrice.value =
            "";


        hidePetWeightField();


        if (!service) {

            return;

        }


        /*
        |--------------------------------------------------------------------------
        | MEDICATION
        |--------------------------------------------------------------------------
        */

        if (
            serviceCategory.value ===
            "Medication"
        ) {

            servicePrice.value =
                parseFloat(
                    service.unit_price
                ).toFixed(2);


            servicePrice.readOnly =
                true;


            return;

        }


        /*
        |--------------------------------------------------------------------------
        | FIXED PRICE SERVICE
        |--------------------------------------------------------------------------
        */

        if (
            service.pricing_type ===
            "Fixed"
        ) {

            if (
                service.fixed_price !==
                null &&
                service.fixed_price !==
                undefined
            ) {

                servicePrice.value =
                    parseFloat(
                        service.fixed_price
                    ).toFixed(2);

            }


            servicePrice.readOnly =
                true;


            return;

        }


        /*
        |--------------------------------------------------------------------------
        | WEIGHT-BASED SERVICE
        |--------------------------------------------------------------------------
        */

        if (
            service.pricing_type ===
            "Weight-Based"
        ) {

            showPetWeightField();


            servicePrice.readOnly =
                true;


            servicePrice.value =
                "";


            const weightInput =
                document.getElementById(
                    "billingPetWeight"
                );


            weightInput?.addEventListener(
    "input",
    function () {

        const weight =
            parseFloat(
                this.value
            );


        const calculatedPrice =
            calculateWeightBasedPrice(
                service,
                weight
            );


        if (
            calculatedPrice === null
        ) {

            servicePrice.value =
                "";

            return;

        }


        servicePrice.value =
            Number(
                calculatedPrice
            ).toFixed(2);

    }
);

            return;

        }


        /*
        |--------------------------------------------------------------------------
        | DEFAULT / VARIABLE
        |--------------------------------------------------------------------------
        */

        servicePrice.readOnly =
            false;

    }
);


/*
|--------------------------------------------------------------------------
| INITIALIZE CATEGORIES
|--------------------------------------------------------------------------
*/

createPetWeightField();

loadBillingCategories();

    /* =====================================================
   ADD SERVICE
===================================================== */

addServiceBtn?.addEventListener(
    "click",
    async function () {

        const category =
            serviceCategory.value;


        const service =
            serviceItem.value;


        const quantity =
            parseFloat(
                serviceQuantity.value
            );


        const price =
            parseFloat(
                servicePrice.value
            );


        const weightInput =
            document.getElementById(
                "billingPetWeight"
            );


        const petWeight =
            parseFloat(
                weightInput?.value
            );


        /* =========================
           VALIDATION
        ========================= */

        if (!category) {

            await showBillingAlert(
                "Please select a service category.",
                "warning",
                "Required Information"
            );

            return;

        }


        if (!service) {

            await showBillingAlert(
                "Please select a service.",
                "warning",
                "Required Information"
            );

            return;

        }


        /*
        |--------------------------------------------------------------------------
        | PET WEIGHT IS REQUIRED
        |--------------------------------------------------------------------------
        */

        if (
            isNaN(petWeight) ||
            petWeight <= 0
        ) {

            await showBillingAlert(
                "Please enter the pet weight.",
                "warning",
                "Pet Weight Required"
            );


            weightInput?.focus();


            return;

        }


        if (
            !quantity ||
            quantity <= 0
        ) {

            await showBillingAlert(
                "Please enter a valid quantity.",
                "warning",
                "Invalid Quantity"
            );

            return;

        }


        if (
            isNaN(price) ||
            price < 0
        ) {

            await showBillingAlert(
                "Please enter a valid price.",
                "warning",
                "Invalid Price"
            );

            return;

        }


        /* =========================
           GET BILLING ID
        ========================= */

        const billingId =
            confirmPaymentBtn?.dataset.id;


        if (!billingId) {

            await showBillingAlert(
                "Billing ID not found.",
                "error",
                "Billing Error"
            );

            return;

        }


        /* =========================
           CALCULATE AMOUNT
        ========================= */

        const amount =
            quantity * price;


        /* =========================
           DISABLE BUTTON
        ========================= */

        addServiceBtn.disabled =
            true;


        addServiceBtn.innerHTML =
            '<i class="fa-solid fa-spinner fa-spin"></i> Adding...';


        try {

            const response =
                await fetch(
                    "../process/add_billing_item.php",
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

                                item_type:
                                    category,

                                item_name:
                                    service,

                                quantity:
                                    quantity,

                                unit_price:
                                    price,

                                pet_weight:
                                    petWeight

                            })

                    }
                );


            const result =
                await response.json();


            if (
                !result.success
            ) {

                await showBillingAlert(
                    result.message ||
                    "Unable to add service.",
                    "error",
                    "Unable to Add Service"
                );

                return;

            }


            /* =========================
               SUCCESS
            ========================= */

            await showBillingAlert(
                service +
                " has been added to the bill.",
                "success",
                "Service Added"
            );


            /*
             * Reload the statement so:
             * - new item appears
             * - subtotal updates
             * - total updates
             * - pet weight is saved
             */

            window.location.reload();

        }


        catch (error) {

            console.error(
                "Add service error:",
                error
            );


            await showBillingAlert(
                "Something went wrong while adding the service.",
                "error",
                "Add Service Failed"
            );

        }


        finally {

            addServiceBtn.disabled =
                false;


            addServiceBtn.innerHTML =
                '<i class="fa-solid fa-plus"></i> Add to Bill';

        }

    }
);


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

    }
);