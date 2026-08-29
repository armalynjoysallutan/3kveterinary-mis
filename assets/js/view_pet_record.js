/* =========================================================
   SYSTEM MESSAGE MODAL
========================================================= */

window.showSystemMessage = function (message, title = "System Message") {

    const modal =
        document.getElementById("systemMessageModal");

    const messageText =
        document.getElementById("systemMessageText");

    const messageTitle =
        document.getElementById("systemMessageTitle");

    const okButton =
        document.getElementById("systemMessageOk");

    if (!modal || !messageText || !okButton) {
        console.error("System message modal not found.");
        return;
    }

    messageText.textContent = message;

    if (messageTitle) {
        messageTitle.textContent = title;
    }

    modal.classList.add("open");
    modal.setAttribute("aria-hidden", "false");

    document.body.classList.add("modal-open");

    setTimeout(function () {
        okButton.focus();
    }, 50);
};


window.closeSystemMessage = function () {

    const modal =
        document.getElementById("systemMessageModal");

    if (!modal) {
        return;
    }

    modal.classList.remove("open");
    modal.setAttribute("aria-hidden", "true");

    document.body.classList.remove("modal-open");
};


document.addEventListener("DOMContentLoaded", function () {

    const modal =
        document.getElementById("systemMessageModal");

    const backdrop =
        document.getElementById("systemMessageBackdrop");

    const okButton =
        document.getElementById("systemMessageOk");

    if (okButton) {
        okButton.addEventListener(
            "click",
            function () {
                window.closeSystemMessage();
            }
        );
    }

    if (backdrop) {
        backdrop.addEventListener(
            "click",
            function () {
                window.closeSystemMessage();
            }
        );
    }

    document.addEventListener(
        "keydown",
        function (event) {

            if (
                event.key === "Escape" &&
                modal &&
                modal.classList.contains("open")
            ) {
                window.closeSystemMessage();
            }

        }
    );

});

document.addEventListener("DOMContentLoaded", function () {


    const addServiceModal = document.getElementById("addServiceModal");
    const closeAddServiceModal = document.getElementById("closeAddServiceModal");
    const cancelAddService = document.getElementById("cancelAddService");

    /*
     * + Add Service button INSIDE Add New Medical Record
     */
    document.addEventListener("click", function (event) {

        const addServiceButton =
            event.target.closest("#openAddServiceModal");

        if (!addServiceButton) {
            return;
        }

        if (!addServiceModal) {
            console.error("Add Service modal not found.");
            return;
        }

        addServiceModal.classList.add("active");

    });


    /*
     * Close using X
     */
    if (closeAddServiceModal) {

        closeAddServiceModal.addEventListener("click", function () {

            addServiceModal.classList.remove("active");

        });

    }


    /*
     * Close using Cancel
     */
    if (cancelAddService) {

        cancelAddService.addEventListener("click", function () {

            addServiceModal.classList.remove("active");

        });

    }


    /*
     * Close when clicking outside modal
     */
    if (addServiceModal) {

        addServiceModal.addEventListener("click", function (event) {

            if (event.target === addServiceModal) {

                addServiceModal.classList.remove("active");

            }

        });

    }


    /*
     * Close using ESC
     */
    document.addEventListener("keydown", function (event) {

        if (event.key === "Escape" && addServiceModal) {

            addServiceModal.classList.remove("active");

        }

    });

        /*
     * =========================================================
     * SERVICES PERFORMED
     * =========================================================
     */

    const medicalRecordServices = [];


    function escapeServiceText(value) {
        return String(value ?? "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }


    function renderMedicalRecordServices() {

        const container =
            document.getElementById("selectedServices");

        if (!container) {
            return;
        }

        container
            .querySelectorAll(".additional-service-item")
            .forEach(function (item) {
                item.remove();
            });

        if (medicalRecordServices.length === 0) {
            return;
        }

        medicalRecordServices.forEach(
            function (service, index) {

                const item =
                    document.createElement("div");

                item.className =
                    "selected-service-item additional-service-item";

                item.innerHTML = `
                    <div class="selected-service-info">

                        <strong>
                            ${escapeServiceText(
                                service.service_name
                            )}
                        </strong>

                        <small>
                            ${escapeServiceText(
                                service.service_category
                            )}
                        </small>

                    </div>

                    <div class="service-item-right">

                        <span class="service-source-badge">
                            Additional
                        </span>

                        <button
                            type="button"
                            class="remove-service-btn"
                            data-service-index="${index}"
                            title="Remove Service"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>

                    </div>
                `;

                container.appendChild(item);
            }
        );
    }


    /*
     * ADD SERVICE
     */

    const confirmAddService =
        document.getElementById("confirmAddService");

    if (confirmAddService) {

        confirmAddService.addEventListener(
            "click",
            function () {

                const category =
                    document.getElementById("serviceCategory");

                const service =
                    document.getElementById("serviceName");

                const petWeight =
                    document.getElementById("servicePetWeight");

                const quantity =
                    document.getElementById("serviceQuantity");

                const unitPrice =
                    document.getElementById("serviceUnitPrice");


                if (!category || !category.value) {

                    window.showSystemMessage(
                        "Please select a service category."
                    );
                    return;
                }


                if (!service || !service.value) {
                    window.showSystemMessage(
                        "Please select a service."
                    );
                    return;
                }


                if (
                    !petWeight ||
                    !petWeight.value ||
                    parseFloat(petWeight.value) <= 0
                ) {
                    window.showSystemMessage(
                        "Please enter the pet's weight."
                    );
                    petWeight?.focus();
                    return;
                }


                const selectedOption =
                    service.options[
                        service.selectedIndex
                    ];


                const serviceData = {

                    service_id:
                        selectedOption?.dataset.serviceId ||
                        service.value ||
                        null,

                    service_category:
                        selectedOption?.dataset.categoryName ||
                        category.options[
                            category.selectedIndex
                        ]?.textContent ||
                        category.value,

                    service_name:
                        selectedOption?.dataset.serviceName ||
                        selectedOption?.textContent ||
                        service.value,

                    pet_weight:
                        parseFloat(
                            petWeight.value
                        ),

                    quantity:
                        parseFloat(
                            quantity?.value || 1
                        ),

                    unit_price:
                        parseFloat(
                            unitPrice?.value || 0
                        ),

                    service_source:
                        "Additional"
                };


                medicalRecordServices.push(
                    serviceData
                );


                renderMedicalRecordServices();


                if (addServiceModal) {
                    addServiceModal.classList.remove(
                        "active"
                    );
                }


                category.value = "";


                if (service) {
                    service.innerHTML =
                        '<option value="">Select Category First</option>';

                    service.disabled = true;
                }


                if (petWeight) {
                    petWeight.value = "";
                }


                if (quantity) {
                    quantity.value = "1";
                }


                if (unitPrice) {
                    unitPrice.value = "0.00";
                }

            }
        );
    }


    /*
     * REMOVE ADDITIONAL SERVICE
     */

    document.addEventListener(
        "click",
        function (event) {

            const removeButton =
                event.target.closest(
                    ".remove-service-btn"
                );

            if (!removeButton) {
                return;
            }


            const index =
                parseInt(
                    removeButton.dataset.serviceIndex,
                    10
                );


            if (Number.isNaN(index)) {
                return;
            }


            medicalRecordServices.splice(
                index,
                1
            );


            renderMedicalRecordServices();

        }
    );


    /* =========================================================
   SAVE MEDICAL RECORD
   ========================================================= */

document.addEventListener("DOMContentLoaded", function () {

    const medicalRecordForm =
        document.getElementById("medicalRecordForm");

    if (!medicalRecordForm) {
        return;
    }

    medicalRecordForm.addEventListener(
        "submit",
        async function (event) {

            event.preventDefault();

            const submitButton =
                medicalRecordForm.querySelector(
                    'button[type="submit"]'
                );

            /* -----------------------------------------
               CHECK REQUIRED FIELDS
            ----------------------------------------- */

            const weight =
                medicalRecordForm.querySelector(
                    'input[name="weight"]'
                );

            const temperature =
                medicalRecordForm.querySelector(
                    'input[name="temperature"]'
                );

            const nextVisit =
                medicalRecordForm.querySelector(
                    'input[name="next_visit"]'
                );

            if (
                !weight ||
                !weight.value ||
                parseFloat(weight.value) <= 0
            ) {
                alert("Please enter the pet's weight.");
                weight?.focus();
                return;
            }

            if (
                !temperature ||
                !temperature.value ||
                parseFloat(temperature.value) <= 0
            ) {
                window.showSystemMessage("Please enter the pet's temperature.");
                temperature?.focus();
                return;
            }

            if (
                !nextVisit ||
                !nextVisit.value
            ) {
                window.showSystemMessage("Please select the next visit date.");
                nextVisit?.focus();
                return;
            }


            /* -----------------------------------------
               CALCULATE DAYS OF RETURN
            ----------------------------------------- */

            const daysField =
                document.getElementById(
                    "noDaysReturn"
                );

            const recordDate =
                medicalRecordForm.querySelector(
                    'input[name="record_date"]'
                );

            if (
                daysField &&
                nextVisit.value &&
                recordDate &&
                recordDate.value
            ) {

                const start =
                    new Date(
                        recordDate.value + "T00:00:00"
                    );

                const end =
                    new Date(
                        nextVisit.value + "T00:00:00"
                    );

                const difference =
                    Math.round(
                        (
                            end - start
                        ) /
                        (
                            1000 *
                            60 *
                            60 *
                            24
                        )
                    );

                daysField.value =
                    difference >= 0
                        ? difference
                        : "";
            }


            /* -----------------------------------------
               PREVENT DOUBLE SUBMIT
            ----------------------------------------- */

            if (submitButton) {
                submitButton.disabled = true;
                submitButton.dataset.originalText =
                    submitButton.innerHTML;

                submitButton.innerHTML =
                    "Saving...";
            }


            /* -----------------------------------------
               PREPARE FORM DATA
            ----------------------------------------- */

            const formData =
                new FormData(
                    medicalRecordForm
                );

            formData.append(
                "medical_record_services",
                JSON.stringify(
                    medicalRecordServices
                )
            );


            /* -----------------------------------------
               SEND TO PHP
            ----------------------------------------- */

            try {

                const response =
                    await fetch(
                        "../process/save_medical_record.php",
                        {
                            method: "POST",
                            body: formData
                        }
                    );

                const result =
                    await response.json();


                /* -------------------------------------
                   CHECK RESPONSE
                ------------------------------------- */

                if (!result.success) {

                    window.showSystemMessage(
                        result.message ||
                        "Unable to save medical record."
                    );

                    return;
                }


                /* -------------------------------------
                   SUCCESS
                ------------------------------------- */

                window.showSystemMessage(
                    "Medical record saved successfully!"
                );


                /* -------------------------------------
                   CLOSE MODAL
                ------------------------------------- */

                const modal =
                    document.getElementById(
                        "medicalRecordModal"
                    );

                if (modal) {

                    modal.classList.remove(
                        "open"
                    );
                }

                document.body.classList.remove(
                    "modal-open"
                );


                /* -------------------------------------
                   REFRESH PAGE
                ------------------------------------- */

                window.location.reload();

            }
            catch (error) {

                console.error(
                    "Save medical record error:",
                    error
                );

                alert(
                    "Something went wrong while saving the medical record."
                );

            }
            finally {

                if (submitButton) {

                    submitButton.disabled =
                        false;

                    if (
                        submitButton.dataset
                            .originalText
                    ) {

                        submitButton.innerHTML =
                            submitButton.dataset
                                .originalText;
                    }
                }
            }

        }
    );

});

});