document.addEventListener("DOMContentLoaded", function () {
    "use strict";

        /* =========================================================
       SYSTEM MESSAGE MODAL
       ========================================================= */

    const systemMessageModal =
        document.getElementById("systemMessageModal");

    const systemMessageTitle =
        document.getElementById("systemMessageTitle");

    const systemMessageText =
        document.getElementById("systemMessageText");

    const systemMessageIcon =
        document.getElementById("systemMessageIcon");

    const systemMessageOk =
        document.getElementById("systemMessageOk");

    const systemMessageBackdrop =
        document.getElementById("systemMessageBackdrop");


    function showSystemMessage(
        message,
        type = "info",
        title = ""
    ) {

        if (
            !systemMessageModal ||
            !systemMessageTitle ||
            !systemMessageText ||
            !systemMessageIcon
        ) {
            console.error(message);
            return;
        }

        const settings = {
            success: {
                title: "Success",
                icon: "fa-circle-check"
            },

            error: {
                title: "Error",
                icon: "fa-circle-xmark"
            },

            warning: {
                title: "Warning",
                icon: "fa-triangle-exclamation"
            },

            info: {
                title: "Notice",
                icon: "fa-circle-info"
            }
        };

        const selected =
            settings[type] || settings.info;

        systemMessageTitle.textContent =
            title || selected.title;

        systemMessageText.textContent =
            message;

        systemMessageIcon.className =
            "system-message-icon " + type;

        systemMessageIcon.innerHTML = `
            <i class="fa-solid ${selected.icon}"></i>
        `;

        systemMessageModal.classList.add("open");
        systemMessageModal.setAttribute(
            "aria-hidden",
            "false"
        );

        document.body.classList.add("modal-open");
    }


    function closeSystemMessage() {

        if (!systemMessageModal) return;

        systemMessageModal.classList.remove("open");

        systemMessageModal.setAttribute(
            "aria-hidden",
            "true"
        );

        const otherOpenModal =
            document.querySelector(
                ".modal.open, " +
                ".modal.active, " +
                "#petRecordModal.open, " +
                "#medicalRecordModal.open, " +
                "#addServiceModal.active, " +
                "#systemMessageModal.open"
            );

        if (!otherOpenModal) {
            document.body.classList.remove(
                "modal-open"
            );
        }
    }


    if (systemMessageOk) {
        systemMessageOk.addEventListener(
            "click",
            closeSystemMessage
        );
    }


    if (systemMessageBackdrop) {
        systemMessageBackdrop.addEventListener(
            "click",
            closeSystemMessage
        );
    }


    document.addEventListener("keydown", function (event) {

        if (event.key !== "Escape") return;

        if (
            systemMessageModal &&
            systemMessageModal.classList.contains("open")
        ) {
            closeSystemMessage();
        }

    });

    /*
     * CUSTOMER RECORDS / VIEW PET RECORD
     * ---------------------------------------------------------
     * Important:
     * view_pet_record.php is loaded dynamically inside
     * #petRecordModalContent. Therefore all controls inside it
     * MUST use event delegation.
     *
     * Do NOT stop the whole script when one optional element
     * is missing. The old script returned at the top, which
     * prevented the Add New Record / Add Service handlers from
     * being registered.
     */

    const SERVICE_CATALOG_URL = "../process/get_service_catalog.php";

    let serviceCatalog = [];
    let catalogLoaded = false;
    let catalogLoading = false;

    /* =========================================================
       HELPERS
       ========================================================= */

    function getPetRecordModal() {
        return document.getElementById("petRecordModal");
    }

    function getPetRecordModalContent() {
        return document.getElementById("petRecordModalContent");
    }

    function getMedicalRecordModal() {
        return document.getElementById("medicalRecordModal");
    }

    function getAddServiceModal() {
        return document.getElementById("addServiceModal");
    }

    function openModal(modal) {
        if (!modal) return;
        modal.classList.add("open");
        modal.classList.add("active");
        modal.setAttribute("aria-hidden", "false");
        document.body.classList.add("modal-open");
    }

    function closeModal(modal) {
        if (!modal) return;

        modal.classList.remove("open");
        modal.classList.remove("active");
        modal.setAttribute("aria-hidden", "true");

        // Restore the page scrollbar when no modal remains open.
        const anyOpenModal = document.querySelector(
            ".modal.open, .modal.active, " +
            "#petRecordModal.open, #medicalRecordModal.open, #addServiceModal.active"
        );

        if (!anyOpenModal) {
            document.body.classList.remove("modal-open");
            document.body.style.overflow = "";
        }
    }

    function escapeHtml(value) {
        return String(value ?? "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function money(value) {
        const amount = Number(value);
        if (!Number.isFinite(amount)) return "₱0.00";

        return "₱" + amount.toLocaleString("en-PH", {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    /* =========================================================
       VIEW PET RECORD
       ========================================================= */

    document.addEventListener("click", function (event) {
        const button = event.target.closest(".view-pet-record");

        if (!button) return;

        event.preventDefault();
        event.stopPropagation();

        const petId = button.getAttribute("data-pet-id");

        if (!petId) {
            console.error("Pet ID is missing.");
            return;
        }

        const modal = getPetRecordModal();
        const content = getPetRecordModalContent();

        if (!modal || !content) {
            console.error(
                "Customer Records: #petRecordModal or #petRecordModalContent is missing."
            );
            return;
        }

        openModal(modal);

        content.innerHTML = `
            <div class="pet-record-loading">
                <i class="fa-solid fa-spinner fa-spin"></i>
                <span>Loading pet record...</span>
            </div>
        `;

        fetch(
            "view_pet_record.php?pet_id=" +
            encodeURIComponent(petId) +
            "&modal=1",
            {
                method: "GET",
                cache: "no-store"
            }
        )
            .then(function (response) {
                if (!response.ok) {
                    throw new Error(
                        "Unable to load pet record. HTTP " +
                        response.status
                    );
                }

                return response.text();
            })
            .then(function (html) {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, "text/html");

                const recordPage =
                    doc.querySelector(".view-record-page");

                if (!recordPage) {
                    throw new Error(
                        "Pet record content not found in response."
                    );
                }

                content.innerHTML = recordPage.innerHTML;

                // IMPORTANT: view_pet_record.php places #addServiceModal
                // outside .view-record-page. Because only .view-record-page
                // is copied into the Customer Records modal, the Add Service
                // modal would otherwise never exist in the live DOM.
                const sourceAddServiceModal =
                    doc.querySelector("#addServiceModal");

                // Remove an old injected copy first, then append the modal
                // from the fetched page into the live pet-record modal.
                const oldAddServiceModal =
                    document.getElementById("addServiceModal");

                if (oldAddServiceModal) {
                    oldAddServiceModal.remove();
                }

                if (sourceAddServiceModal) {
                    const addServiceModalClone =
                        sourceAddServiceModal.cloneNode(true);

                    // Keep it outside the inner scrolling content so its
                    // overlay can work normally.
                    const petModal = getPetRecordModal();

                    if (petModal) {
                        petModal.appendChild(addServiceModalClone);
                    } else {
                        document.body.appendChild(addServiceModalClone);
                    }
                }

                // Remove an inner "back" toolbar if the loaded page has one.
                const toolbar =
                    content.querySelector(".view-record-toolbar");

                if (toolbar) {
                    toolbar.remove();
                }

                // Initialize the dynamically inserted controls.
                initializeMedicalRecordForm();
                loadServiceCatalog();
            })
            .catch(function (error) {
                console.error("View Pet Record Error:", error);

                content.innerHTML = `
                    <div class="pet-record-error">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <strong>Unable to load pet record.</strong>
                        <span>Please try again.</span>
                    </div>
                `;
            });
    });

    /* =========================================================
       CLOSE VIEW PET RECORD
       ========================================================= */

    document.addEventListener("click", function (event) {
        const petModal = getPetRecordModal();

        if (!petModal) return;

        if (
            event.target.closest("#closePetRecordModal") ||
            event.target.closest("#petRecordModalBackdrop")
        ) {
            closeModal(petModal);
            return;
        }
    });


    /* =========================================================
       VIEW INDIVIDUAL MEDICAL RECORD
       ---------------------------------------------------------
       The Medical Record History table is loaded dynamically
       inside #petRecordModalContent, so this MUST use
       event delegation.
       ========================================================= */

    document.addEventListener("click", async function (event) {
        const button = event.target.closest(
            ".record-action-btn[data-record-id]:not(.billing-action-btn)"
        );

        if (!button) return;

        event.preventDefault();
        event.stopPropagation();

        const recordId = button.getAttribute("data-record-id");

        if (!recordId) {
            console.error("Medical Record ID is missing.");
            return;
        }

        let modal = document.getElementById(
            "medicalRecordDetailsModal"
        );

        if (!modal) {
            modal = document.createElement("div");
            modal.id = "medicalRecordDetailsModal";
            modal.setAttribute("aria-hidden", "true");

            modal.innerHTML = `
                <div class="medical-record-details-backdrop"></div>

                <div class="medical-record-details-dialog">
                    <div class="medical-record-details-header">
                        <div>
                            <h2>Medical Record Details</h2>
                            <p>Complete visit and clinical record</p>
                        </div>

                        <button
                            type="button"
                            class="medical-record-details-close"
                            aria-label="Close"
                        >
                            &times;
                        </button>
                    </div>

                    <div
                        class="medical-record-details-body"
                        id="medicalRecordDetailsBody"
                    >
                        <div class="medical-record-details-loading">
                            <i class="fa-solid fa-spinner fa-spin"></i>
                            <span>Loading record...</span>
                        </div>
                    </div>
                </div>
            `;

            document.body.appendChild(modal);

            const style = document.createElement("style");
            style.id = "medicalRecordDetailsModalStyles";
            style.textContent = `
                #medicalRecordDetailsModal {
                    position: fixed;
                    inset: 0;
                    z-index: 99999;
                    display: none;
                    align-items: center;
                    justify-content: center;
                    padding: 18px;
                }

                #medicalRecordDetailsModal.open {
                    display: flex;
                }

                .medical-record-details-backdrop {
                    position: absolute;
                    inset: 0;
                    background: rgba(15, 23, 42, 0.55);
                }

                .medical-record-details-dialog {
                    position: relative;
                    z-index: 1;
                    width: min(620px, 92vw);
                    max-height: 78vh;
                    overflow: hidden;
                    border-radius: 12px;
                    background: #fff;
                    box-shadow: 0 18px 45px rgba(15, 23, 42, 0.24);
                }

                .medical-record-details-header {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    gap: 14px;
                    padding: 14px 18px;
                    border-bottom: 1px solid #e5e7eb;
                }

                .medical-record-details-header h2 {
                    margin: 0;
                    color: #172033;
                    font-size: 17px;
                    font-weight: 800;
                }

                .medical-record-details-header p {
                    margin: 3px 0 0;
                    color: #94a3b8;
                    font-size: 10px;
                }

                .medical-record-details-close {
                    width: 32px;
                    height: 32px;
                    border: 0;
                    border-radius: 8px;
                    background: #f1f5f9;
                    color: #475569;
                    font-size: 20px;
                    line-height: 1;
                    cursor: pointer;
                }

                .medical-record-details-body {
                    max-height: calc(78vh - 65px);
                    overflow-y: auto;
                    padding: 16px 18px 18px;
                }

                .medical-record-details-grid {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 9px;
                }

                .medical-record-detail-card {
                    border: 1px solid #e2e8f0;
                    border-radius: 8px;
                    padding: 10px 12px;
                    background: #f8fafc;
                }

                .medical-record-detail-card.full {
                    grid-column: 1 / -1;
                }

                .medical-record-detail-label {
                    display: block;
                    margin-bottom: 4px;
                    color: #64748b;
                    font-size: 8px;
                    font-weight: 800;
                    text-transform: uppercase;
                }

                .medical-record-detail-value {
                    color: #1e293b;
                    font-size: 11px;
                    font-weight: 600;
                    white-space: pre-wrap;
                    overflow-wrap: anywhere;
                }

                .medical-record-details-section-title {
                    margin: 14px 0 7px;
                    color: #172033;
                    font-size: 11px;
                    font-weight: 800;
                }

                .medical-record-service-row {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    gap: 10px;
                    padding: 9px 11px;
                    border: 1px solid #e2e8f0;
                    border-radius: 8px;
                    background: #fff;
                }

                .medical-record-service-row + .medical-record-service-row {
                    margin-top: 6px;
                }

                .medical-record-service-name {
                    color: #1e293b;
                    font-size: 11px;
                    font-weight: 700;
                }

                .medical-record-service-meta {
                    margin-top: 2px;
                    color: #64748b;
                    font-size: 9px;
                }

                .medical-record-service-amount {
                    color: #1e293b;
                    font-size: 10px;
                    font-weight: 800;
                    white-space: nowrap;
                }

                .medical-record-source-badge {
                    display: inline-flex;
                    align-items: center;
                    margin-left: 4px;
                    padding: 2px 5px;
                    border-radius: 999px;
                    background: #eef8ff;
                    color: #1597d4;
                    font-size: 7px;
                    font-weight: 800;
                }

                .medical-record-details-loading,
                .medical-record-details-error,
                .medical-record-details-empty {
                    display: flex;
                    min-height: 120px;
                    align-items: center;
                    justify-content: center;
                    flex-direction: column;
                    gap: 7px;
                    color: #64748b;
                    text-align: center;
                    font-size: 11px;
                }

                @media (max-width: 650px) {
                    #medicalRecordDetailsModal {
                        padding: 10px;
                    }

                    .medical-record-details-dialog {
                        width: 94vw;
                        max-height: 84vh;
                    }

                    .medical-record-details-grid {
                        grid-template-columns: 1fr 1fr;
                    }

                    .medical-record-detail-card.full {
                        grid-column: 1 / -1;
                    }
                }
            `;

            document.head.appendChild(style);

            const close = () => {
                modal.classList.remove("open");
                modal.setAttribute("aria-hidden", "true");
            };

            modal.querySelector(
                ".medical-record-details-close"
            ).addEventListener("click", close);

            modal.querySelector(
                ".medical-record-details-backdrop"
            ).addEventListener("click", close);
        }

        const body = document.getElementById(
            "medicalRecordDetailsBody"
        );

        if (!body) return;

        modal.classList.add("open");
        modal.setAttribute("aria-hidden", "false");

        body.innerHTML = `
            <div class="medical-record-details-loading">
                <i class="fa-solid fa-spinner fa-spin"></i>
                <span>Loading record...</span>
            </div>
        `;

        try {
            const response = await fetch(
                "../process/get_medical_record_details.php?medical_record_id=" +
                encodeURIComponent(recordId),
                {
                    method: "GET",
                    cache: "no-store"
                }
            );

            if (!response.ok) {
                throw new Error(
                    "HTTP " + response.status
                );
            }

            const result = await response.json();

            if (!result.success || !result.record) {
                throw new Error(
                    result.message ||
                    "Medical record was not found."
                );
            }

            const record = result.record;
            const services = Array.isArray(result.services)
                ? result.services
                : [];

            const value = (v, fallback = "—") => {
                const text = String(v ?? "").trim();
                return text === "" ? fallback : escapeHtml(text);
            };

            const serviceHtml = services.length
                ? services.map(function (service) {
                    const source =
                        service.service_source ||
                        "Additional";

                    const amount =
                        Number(service.amount || 0);

                    return `
                        <div class="medical-record-service-row">
                            <div>
                                <div class="medical-record-service-name">
                                    ${escapeHtml(
                                        service.service_name || "Service"
                                    )}
                                    <span class="medical-record-source-badge">
                                        ${escapeHtml(source)}
                                    </span>
                                </div>

                                <div class="medical-record-service-meta">
                                    ${escapeHtml(
                                        service.service_category || "Service"
                                    )}
                                    ·
                                    ${Number(service.quantity || 1)}
                                    qty
                                    ${
                                        service.pet_weight !== null &&
                                        service.pet_weight !== undefined &&
                                        service.pet_weight !== ""
                                            ? " · " +
                                              escapeHtml(
                                                  service.pet_weight
                                              ) +
                                              " kg"
                                            : ""
                                    }
                                </div>
                            </div>

                            <div class="medical-record-service-amount">
                                ${money(amount)}
                            </div>
                        </div>
                    `;
                }).join("")
                : `
                    <div class="medical-record-details-empty">
                        No services recorded for this medical record.
                    </div>
                `;

            body.innerHTML = `
                <div class="medical-record-details-grid">

                    <div class="medical-record-detail-card">
                        <span class="medical-record-detail-label">
                            Visit Date
                        </span>
                        <div class="medical-record-detail-value">
                            ${value(record.record_date)}
                        </div>
                    </div>

                    <div class="medical-record-detail-card">
                        <span class="medical-record-detail-label">
                            Pet Weight
                        </span>
                        <div class="medical-record-detail-value">
                            ${
                                record.weight !== null &&
                                record.weight !== undefined &&
                                record.weight !== ""
                                    ? escapeHtml(record.weight) + " kg"
                                    : "—"
                            }
                        </div>
                    </div>

                    <div class="medical-record-detail-card">
                        <span class="medical-record-detail-label">
                            Temperature
                        </span>
                        <div class="medical-record-detail-value">
                            ${
                                record.temperature !== null &&
                                record.temperature !== undefined &&
                                record.temperature !== ""
                                    ? escapeHtml(record.temperature) + " °C"
                                    : "—"
                            }
                        </div>
                    </div>

                    <div class="medical-record-detail-card">
                        <span class="medical-record-detail-label">
                            Amount Paid
                        </span>
                        <div class="medical-record-detail-value">
                            ${money(record.amount_paid || 0)}
                        </div>
                    </div>

                    <div class="medical-record-detail-card full">
                        <span class="medical-record-detail-label">
                            Diagnosis (DX)
                        </span>
                        <div class="medical-record-detail-value">
                            ${value(record.diagnosis)}
                        </div>
                    </div>

                    <div class="medical-record-detail-card full">
                        <span class="medical-record-detail-label">
                            Treatment / Procedure (TX)
                        </span>
                        <div class="medical-record-detail-value">
                            ${value(record.treatment)}
                        </div>
                    </div>

                    <div class="medical-record-detail-card full">
                        <span class="medical-record-detail-label">
                            Vaccination (VX)
                        </span>
                        <div class="medical-record-detail-value">
                            ${value(record.vaccination)}
                        </div>
                    </div>

                    <div class="medical-record-detail-card">
                        <span class="medical-record-detail-label">
                            Next Visit
                        </span>
                        <div class="medical-record-detail-value">
                            ${value(record.next_visit)}
                        </div>
                    </div>

                    <div class="medical-record-detail-card">
                        <span class="medical-record-detail-label">
                            No. of Days of Return
                        </span>
                        <div class="medical-record-detail-value">
                            ${value(record.no_days_return)}
                        </div>
                    </div>

                </div>

                <div class="medical-record-details-section-title">
                    Services Performed
                </div>

                ${serviceHtml}
            `;
        } catch (error) {
            console.error(
                "View Medical Record Error:",
                error
            );

            body.innerHTML = `
                <div class="medical-record-details-error">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <strong>
                        Unable to load this medical record.
                    </strong>
                    <span>
                        ${escapeHtml(error.message)}
                    </span>
                </div>
            `;
        }
    });

    /* =========================================================
       ADD NEW MEDICAL RECORD
       ========================================================= */

    document.addEventListener("click", function (event) {
        const button = event.target.closest("button");

        if (!button) return;

        const text = button.textContent
            .trim()
            .replace(/\s+/g, " ")
            .toLowerCase();

        const isAddRecordButton =
            button.id === "addMedicalRecordBtn" ||
            button.id === "addNewRecord" ||
            button.classList.contains("add-new-record") ||
            text.includes("add new record");

        if (!isAddRecordButton) return;

        event.preventDefault();
        event.stopPropagation();

        const modal = getMedicalRecordModal();

        if (!modal) {
            console.error(
                "Add New Medical Record: #medicalRecordModal was not found."
            );
            return;
        }

        openModal(modal);

        initializeMedicalRecordForm();
        loadServiceCatalog();
    });

    /* =========================================================
       CLOSE MEDICAL RECORD MODAL
       ========================================================= */

    document.addEventListener("click", function (event) {
        const closeButton =
            event.target.closest("#closeMedicalRecordModal");

        const cancelButton =
            event.target.closest(
                "#cancelMedicalRecord, #cancelMedicalRecordBtn"
            );

        const backdrop =
            event.target.closest("#medicalRecordModalBackdrop");

        if (!closeButton && !cancelButton && !backdrop) return;

        event.preventDefault();
        event.stopPropagation();

        closeModal(getMedicalRecordModal());
    });

    /* =========================================================
       ESC KEY
       ========================================================= */

    document.addEventListener("keydown", function (event) {
        if (event.key !== "Escape") return;

        const serviceModal = getAddServiceModal();
        if (
            serviceModal &&
            (
                serviceModal.classList.contains("open") ||
                serviceModal.classList.contains("active")
            )
        ) {
            closeModal(serviceModal);
            return;
        }

        const medicalModal = getMedicalRecordModal();
        if (
            medicalModal &&
            (
                medicalModal.classList.contains("open") ||
                medicalModal.classList.contains("active")
            )
        ) {
            closeModal(medicalModal);
            return;
        }

        const petModal = getPetRecordModal();
        if (
            petModal &&
            (
                petModal.classList.contains("open") ||
                petModal.classList.contains("active")
            )
        ) {
            closeModal(petModal);
        }
    });

    /* =========================================================
       MEDICAL FORM INITIALIZATION
       ========================================================= */

    function initializeMedicalRecordForm() {
        const form = document.getElementById("medicalRecordForm");

        if (!form) return;

        const recordDate =
            form.querySelector('input[name="record_date"]');

        if (recordDate && !recordDate.value) {
            const now = new Date();
            const localDate =
                new Date(
                    now.getTime() -
                    now.getTimezoneOffset() * 60000
                )
                    .toISOString()
                    .split("T")[0];

            recordDate.value = localDate;
        }

        calculateDaysOfReturn();
    }

    /* =========================================================
       NEXT VISIT -> DAYS OF RETURN
       ========================================================= */

    document.addEventListener("change", function (event) {
        if (event.target.id !== "nextVisit") return;
        calculateDaysOfReturn();
    });

    function calculateDaysOfReturn() {
        const nextVisit =
            document.getElementById("nextVisit");

        const daysField =
            document.getElementById("noDaysReturn");

        const form =
            document.getElementById("medicalRecordForm");

        if (!nextVisit || !daysField || !form) return;

        if (!nextVisit.value) {
            daysField.value = "";
            return;
        }

        const recordDateField =
            form.querySelector('input[name="record_date"]');

        const recordDate =
            recordDateField?.value ||
            new Date().toISOString().split("T")[0];

        const start =
            new Date(recordDate + "T00:00:00");

        const end =
            new Date(nextVisit.value + "T00:00:00");

        const difference =
            Math.round(
                (end - start) /
                (1000 * 60 * 60 * 24)
            );

        daysField.value =
            difference >= 0
                ? difference
                : "";
    }

    /* =========================================================
       ADD SERVICE MODAL
       ========================================================= */

    document.addEventListener("click", function (event) {
        const openButton =
            event.target.closest("#openAddServiceModal");

        if (!openButton) return;

        event.preventDefault();
        event.stopPropagation();

        const modal = getAddServiceModal();

        if (!modal) {
            console.error(
                "Add Service: #addServiceModal was not found."
            );
            return;
        }

        // Prefill the Add Service weight from the medical record.
        const recordWeight =
            document.querySelector(
                '#medicalRecordForm input[name="weight"]'
            );

        const serviceWeight =
            document.getElementById("servicePetWeight");

        if (
            recordWeight &&
            serviceWeight &&
            recordWeight.value &&
            !serviceWeight.value
        ) {
            serviceWeight.value = recordWeight.value;
        }

        resetAddServiceForm();
        openModal(modal);
        loadServiceCatalog();
    });

    document.addEventListener("click", function (event) {
        const closeButton =
            event.target.closest(
                "#closeAddServiceModal, #cancelAddService"
            );

        if (!closeButton) return;

        event.preventDefault();
        event.stopPropagation();

        closeModal(getAddServiceModal());
    });

    document.addEventListener("click", function (event) {
        const modal = getAddServiceModal();

        if (!modal) return;

        if (event.target === modal) {
            closeModal(modal);
        }
    });

    /* =========================================================
       SERVICE CATALOG
       ========================================================= */

    async function loadServiceCatalog() {
        if (catalogLoaded) {
            populateServiceCategories();
            return;
        }

        if (catalogLoading) return;

        catalogLoading = true;

        const categorySelect =
            document.getElementById("serviceCategory");

        if (categorySelect) {
            categorySelect.disabled = true;
        }

        try {
            const response =
                await fetch(
                    SERVICE_CATALOG_URL,
                    {
                        method: "GET",
                        cache: "no-store",
                        headers: {
                            "Accept": "application/json"
                        }
                    }
                );

            if (!response.ok) {
                throw new Error(
                    "Service catalog request failed: HTTP " +
                    response.status
                );
            }

            const data = await response.json();

            if (!data.success || !Array.isArray(data.services)) {
                throw new Error(
                    data.message ||
                    "Invalid service catalog response."
                );
            }

            serviceCatalog = data.services;
            catalogLoaded = true;

            populateServiceCategories();
        }
        catch (error) {
            console.error(
                "Unable to load service catalog:",
                error
            );

            /*
             * If another page already exposed billingServices,
             * use it as a fallback.
             */
            if (Array.isArray(window.billingServices)) {
                serviceCatalog =
                    window.billingServices.map(function (service) {
                        return {
                            service_id: service.service_id,
                            category_id: service.category_id,
                            category_name: service.category_name,
                            service_name: service.service_name,
                            pricing_type: service.pricing_type,
                            fixed_price: service.fixed_price,
                            base_min_weight: service.base_min_weight,
                            base_max_weight: service.base_max_weight,
                            base_price: service.base_price,
                            weight_increment: service.weight_increment,
                            price_increment: service.price_increment
                        };
                    });

                catalogLoaded = true;
                populateServiceCategories();
            }
        }
        finally {
            catalogLoading = false;

            if (categorySelect) {
                categorySelect.disabled = false;
            }
        }
    }

    function populateServiceCategories() {
        const categorySelect =
            document.getElementById("serviceCategory");

        const serviceSelect =
            document.getElementById("serviceName");

        if (!categorySelect) return;

        const currentCategory =
            categorySelect.value;

        categorySelect.innerHTML = "";

        const defaultOption =
            document.createElement("option");

        defaultOption.value = "";
        defaultOption.textContent = "Select Category";

        categorySelect.appendChild(defaultOption);

        const categories = [];
        const categoryKeys = new Set();

        serviceCatalog.forEach(function (service) {
            const category =
                String(service.category_name || "").trim();

            if (!category) return;

            const key = category.toLowerCase();

            if (categoryKeys.has(key)) return;

            categoryKeys.add(key);
            categories.push(category);
        });

        categories.forEach(function (category) {
            const option =
                document.createElement("option");

            option.value = category;
            option.textContent = category;

            categorySelect.appendChild(option);
        });

        if (
            currentCategory &&
            categories.some(function (category) {
                return (
                    category.toLowerCase() ===
                    currentCategory.toLowerCase()
                );
            })
        ) {
            categorySelect.value = currentCategory;
        }

        if (serviceSelect) {
            populateServicesForCategory();
        }
    }

    /* =========================================================
       CATEGORY -> SERVICE
       ========================================================= */

    document.addEventListener("change", function (event) {
        if (event.target.id !== "serviceCategory") return;

        populateServicesForCategory();
    });

    function populateServicesForCategory() {
        const categorySelect =
            document.getElementById("serviceCategory");

        const serviceSelect =
            document.getElementById("serviceName");

        const priceInput =
            document.getElementById("serviceUnitPrice");

        if (!categorySelect || !serviceSelect) return;

        const category =
            categorySelect.value;

        serviceSelect.innerHTML = "";

        const defaultOption =
            document.createElement("option");

        defaultOption.value = "";

        if (!category) {
            defaultOption.textContent =
                "Select Category First";
            serviceSelect.disabled = true;

            serviceSelect.appendChild(defaultOption);

            if (priceInput) {
                priceInput.value = "0.00";
            }

            return;
        }

        defaultOption.textContent =
            "Select Service";

        serviceSelect.appendChild(defaultOption);

        const services =
            serviceCatalog.filter(function (service) {
                return (
                    String(service.category_name || "")
                        .toLowerCase() ===
                    category.toLowerCase()
                );
            });

        services.forEach(function (service) {
            const option =
                document.createElement("option");

            option.value =
                String(service.service_id);

            option.textContent =
                service.service_name;

            option.dataset.serviceId =
                String(service.service_id);

            option.dataset.serviceName =
                service.service_name || "";

            option.dataset.pricingType =
                service.pricing_type || "";

            option.dataset.fixedPrice =
                service.fixed_price ?? "";

            option.dataset.baseMinWeight =
                service.base_min_weight ?? "";

            option.dataset.baseMaxWeight =
                service.base_max_weight ?? "";

            option.dataset.basePrice =
                service.base_price ?? "";

            option.dataset.weightIncrement =
                service.weight_increment ?? "";

            option.dataset.priceIncrement =
                service.price_increment ?? "";

            serviceSelect.appendChild(option);
        });

        serviceSelect.disabled =
            services.length === 0;

        if (services.length === 0) {
            defaultOption.textContent =
                "No services available";
        }

        if (priceInput) {
            priceInput.value = "0.00";
        }
    }

    /* =========================================================
       SERVICE CHANGE -> PRICE
       ========================================================= */

    document.addEventListener("change", function (event) {
        if (event.target.id !== "serviceName") return;

        updateServicePrice();
    });

    document.addEventListener("input", function (event) {
        if (
            event.target.id === "servicePetWeight" ||
            event.target.id === "serviceQuantity"
        ) {
            updateServicePrice();
        }
    });

    function getSelectedService() {
        const serviceSelect =
            document.getElementById("serviceName");

        if (!serviceSelect || !serviceSelect.value) {
            return null;
        }

        const selectedOption =
            serviceSelect.options[
                serviceSelect.selectedIndex
            ];

        if (!selectedOption) return null;

        return {
            service_id:
                Number(selectedOption.dataset.serviceId),

            service_name:
                selectedOption.dataset.serviceName ||
                selectedOption.textContent,

            pricing_type:
                selectedOption.dataset.pricingType || "",

            fixed_price:
                selectedOption.dataset.fixedPrice,

            base_min_weight:
                selectedOption.dataset.baseMinWeight,

            base_max_weight:
                selectedOption.dataset.baseMaxWeight,

            base_price:
                selectedOption.dataset.basePrice,

            weight_increment:
                selectedOption.dataset.weightIncrement,

            price_increment:
                selectedOption.dataset.priceIncrement
        };
    }

    function calculateServicePrice(service, weight) {
        if (!service) return 0;

        const pricingType =
            String(service.pricing_type || "")
                .toLowerCase();

        const fixedPrice =
            Number(service.fixed_price);

        // Fixed-price service.
        if (
            pricingType === "fixed" &&
            Number.isFinite(fixedPrice)
        ) {
            return fixedPrice;
        }

        const baseMin =
            Number(service.base_min_weight);

        const baseMax =
            Number(service.base_max_weight);

        const basePrice =
            Number(service.base_price);

        const weightIncrement =
            Number(service.weight_increment);

        const priceIncrement =
            Number(service.price_increment);

        /*
         * Variable/manual pricing:
         * use the active pricing rule when one exists.
         */
        if (
            Number.isFinite(baseMin) &&
            Number.isFinite(baseMax) &&
            Number.isFinite(basePrice) &&
            Number.isFinite(weightIncrement) &&
            weightIncrement > 0 &&
            Number.isFinite(priceIncrement)
        ) {
            if (weight <= baseMax) {
                return basePrice;
            }

            const increments =
                Math.ceil(
                    (weight - baseMax) /
                    weightIncrement
                );

            return (
                basePrice +
                increments * priceIncrement
            );
        }

        // No automatic price available.
        return 0;
    }

    function updateServicePrice() {
        const service =
            getSelectedService();

        const weightInput =
            document.getElementById("servicePetWeight");

        const priceInput =
            document.getElementById("serviceUnitPrice");

        if (!priceInput) return;

        if (!service) {
            priceInput.value = "0.00";
            return;
        }

        const weight =
            parseFloat(weightInput?.value);

        /*
         * Fixed services do not require weight for price
         * calculation, but the Add Service form still requires
         * pet weight because it is part of the medical record.
         */
        const safeWeight =
            Number.isFinite(weight) && weight > 0
                ? weight
                : 0;

        const price =
            calculateServicePrice(
                service,
                safeWeight
            );

        priceInput.value =
            Number(price).toFixed(2);
    }

    /* =========================================================
       RESET ADD SERVICE FORM
       ========================================================= */

    function resetAddServiceForm() {
        const category =
            document.getElementById("serviceCategory");

        const service =
            document.getElementById("serviceName");

        const weight =
            document.getElementById("servicePetWeight");

        const quantity =
            document.getElementById("serviceQuantity");

        const price =
            document.getElementById("serviceUnitPrice");

        if (category) {
            category.value = "";
        }

        if (service) {
            service.innerHTML =
                '<option value="">Select Category First</option>';
            service.disabled = true;
        }

        if (weight) {
            const recordWeight =
                document.querySelector(
                    '#medicalRecordForm input[name="weight"]'
                );

            weight.value =
                recordWeight?.value || "";
        }

        if (quantity) {
            quantity.value = "1";
        }

        if (price) {
            price.value = "0.00";
        }
    }

    /* =========================================================
       CONFIRM ADD SERVICE
       ========================================================= */

    document.addEventListener("click", function (event) {
        const button =
            event.target.closest("#confirmAddService");

        if (!button) return;

        event.preventDefault();
        event.stopPropagation();

        const categorySelect =
            document.getElementById("serviceCategory");

        const serviceSelect =
            document.getElementById("serviceName");

        const weightInput =
            document.getElementById("servicePetWeight");

        const quantityInput =
            document.getElementById("serviceQuantity");

        const priceInput =
            document.getElementById("serviceUnitPrice");

        const selectedServices =
            document.getElementById("selectedServices");

        const form =
            document.getElementById("medicalRecordForm");

        if (
            !categorySelect ||
            !serviceSelect ||
            !weightInput ||
            !quantityInput ||
            !priceInput ||
            !selectedServices
        ) {
            console.error(
                "Add Service: required form elements are missing."
            );
            return;
        }

        const category =
            categorySelect.value;

        const service =
            getSelectedService();

        const weight =
            parseFloat(weightInput.value);

        const quantity =
            parseFloat(quantityInput.value);

        const price =
            parseFloat(priceInput.value);

        if (!category) {
            showSystemMessage(
                "Please select a service category.",
                "warning"
            );
            
            categorySelect.focus();
            return;
        }

        if (!service) {
            alert("Please select a service.");
            serviceSelect.focus();
            return;
        }

        if (!Number.isFinite(weight) || weight <= 0) {
            alert("Please enter the pet weight.");
            weightInput.focus();
            return;
        }

        if (!Number.isFinite(quantity) || quantity <= 0) {
            alert("Please enter a valid quantity.");
            quantityInput.focus();
            return;
        }

        if (!Number.isFinite(price) || price < 0) {
            alert("Unable to determine the service price.");
            return;
        }

        addSelectedService({
            service_id: service.service_id,
            category: category,
            service_name: service.service_name,
            pet_weight: weight,
            quantity: quantity,
            unit_price: price,
            amount: quantity * price
        });

        closeModal(getAddServiceModal());
        resetAddServiceForm();
    });

    /* =========================================================
       SELECTED SERVICES
       ========================================================= */

    function getSelectedServicesContainer() {
        return document.getElementById("selectedServices");
    }

    function addSelectedService(serviceData) {
        const container =
            getSelectedServicesContainer();

        const form =
            document.getElementById("medicalRecordForm");

        if (!container) return;

        const empty =
            container.querySelector(".no-services");

        if (empty) {
            empty.remove();
        }

        const item =
            document.createElement("div");

        item.className =
            "selected-service-item additional-service-item";

        item.dataset.serviceId =
            String(serviceData.service_id);

        item.innerHTML = `
            <div class="selected-service-info">
                <strong>${escapeHtml(serviceData.service_name)}</strong>
                <span>
                    ${escapeHtml(serviceData.category)}
                    · ${Number(serviceData.pet_weight).toFixed(2)} kg
                    · Qty ${Number(serviceData.quantity)}
                </span>
            </div>

            <div class="selected-service-action">
                <span class="service-source-badge">
                    Additional
                </span>

                <button
                    type="button"
                    class="remove-selected-service"
                    title="Remove service"
                >
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        `;

        /*
         * Keep service data in hidden fields so the medical-record
         * form can submit them later.
         */
        if (form) {
            const hidden = document.createElement("input");

            hidden.type = "hidden";
            hidden.name = "services[]";

            hidden.value =
                JSON.stringify(serviceData);

            hidden.dataset.serviceId =
                String(serviceData.service_id);

            item.appendChild(hidden);
        }

        container.appendChild(item);
    }

    document.addEventListener("click", function (event) {
        const removeButton =
            event.target.closest(
                ".remove-selected-service"
            );

        if (!removeButton) return;

        event.preventDefault();

        const item =
            removeButton.closest(
                ".selected-service-item"
            );

        if (!item) return;

        const hidden =
            item.querySelector(
                'input[name="services[]"]'
            );

        if (hidden) {
            hidden.remove();
        }

        item.remove();

        const container =
            getSelectedServicesContainer();

        if (
            container &&
            !container.querySelector(
                ".selected-service-item"
            )
        ) {
            container.innerHTML = `
                <div class="no-services">
                    No additional services added.
                </div>
            `;
        }
    });

    /* =========================================================
       PREVENT FORM SUBMIT WHEN CLICKING ADD SERVICE
       ========================================================= */

    document.addEventListener("click", function (event) {
        const addServiceButton =
            event.target.closest(
                "#openAddServiceModal, #confirmAddService"
            );

        if (addServiceButton) {
            event.preventDefault();
        }
    });

    console.log(
        "customer_records.js loaded successfully."
    );

    /* =========================================================
   SAVE MEDICAL RECORD
   Handles dynamically loaded View Pet Record modal
========================================================= */

document.addEventListener("submit", async function (event) {

    if (event.target.id !== "medicalRecordForm") {
        return;
    }

    event.preventDefault();

    const form = event.target;

    const weight =
        form.querySelector('[name="weight"]');

    const temperature =
        form.querySelector('[name="temperature"]');

    const nextVisit =
        form.querySelector('[name="next_visit"]');

    /* REQUIRED: WEIGHT */
    if (
        !weight ||
        !weight.value ||
        parseFloat(weight.value) <= 0
    ) {
        alert("Please enter the pet's weight.");
        weight?.focus();
        return;
    }

    /* REQUIRED: TEMPERATURE */
    if (
        !temperature ||
        !temperature.value ||
        parseFloat(temperature.value) <= 0
    ) {
        alert("Please enter the pet's temperature.");
        temperature?.focus();
        return;
    }

    /* REQUIRED: NEXT VISIT */
    if (
        !nextVisit ||
        !nextVisit.value
    ) {
        alert("Please select the next visit date.");
        nextVisit?.focus();
        return;
    }

    const formData =
        new FormData(form);

    try {

        const response = await fetch(
            "../process/save_medical_record.php",
            {
                method: "POST",
                body: formData
            }
        );

        const result =
            await response.json();

        console.log(
            "Save Medical Record Response:",
            result
        );

        if (!result.success) {

            alert(
                result.message ||
                "Unable to save medical record."
            );

            return;
        }

        alert(
            "Medical record saved successfully!"
        );

        /* Close Add New Record modal */
        const modal =
            document.getElementById(
                "medicalRecordModal"
            );

        if (modal) {
            modal.classList.remove("open");
        }

        document.body.classList.remove(
            "modal-open"
        );

        /*
         * Reload the View Pet Record content
         * so the newly saved record appears.
         */
        const petRecordModalContent =
            document.getElementById(
                "petRecordModalContent"
            );

        const viewButton =
            document.querySelector(
                ".view-pet-record.active"
            );

        if (
            petRecordModalContent &&
            viewButton
        ) {

            const petId =
                viewButton.getAttribute(
                    "data-pet-id"
                );

            if (petId) {

                const recordResponse =
                    await fetch(
                        "view_pet_record.php?pet_id=" +
                        encodeURIComponent(petId) +
                        "&modal=1"
                    );

                const html =
                    await recordResponse.text();

                const parser =
                    new DOMParser();

                const doc =
                    parser.parseFromString(
                        html,
                        "text/html"
                    );

                const recordPage =
                    doc.querySelector(
                        ".view-record-page"
                    );

                if (recordPage) {

                    petRecordModalContent.innerHTML =
                        recordPage.innerHTML;

                }
            }
        }

    }
    catch (error) {

        console.error(
            "Medical record save error:",
            error
        );

        alert(
            "Something went wrong while saving the medical record."
        );

    }

});

    /* =========================================================
       COMPACT REGISTERED PETS
       ---------------------------------------------------------
       - Only the first 3 pets remain visible in the customer card.
       - Additional pets open in a dedicated modal.
       - Existing .view-pet-record / .vaccination-history / .edit-pet
         classes are preserved so existing handlers continue to work.
       ========================================================= */

    document.addEventListener("click", function (event) {
        const moreButton = event.target.closest(".pet-more-btn");

        if (moreButton) {
            event.preventDefault();
            event.stopPropagation();

            const wrap = moreButton.closest(".pet-more-wrap");
            if (!wrap) return;

            const menu = wrap.querySelector(".pet-more-menu");
            if (!menu) return;

            document.querySelectorAll(".pet-more-wrap.menu-open").forEach(function (otherWrap) {
                if (otherWrap !== wrap) {
                    otherWrap.classList.remove("menu-open");

                    const otherMenu = otherWrap.querySelector(".pet-more-menu");
                    if (otherMenu) {
                        otherMenu.style.top = "";
                        otherMenu.style.left = "";
                        otherMenu.style.display = "";
                        otherMenu.style.visibility = "";
                    }

                    const otherButton = otherWrap.querySelector(".pet-more-btn");
                    otherButton?.setAttribute("aria-expanded", "false");
                }
            });

            const willOpen = !wrap.classList.contains("menu-open");

            if (!willOpen) {
                wrap.classList.remove("menu-open");
                menu.style.top = "";
                menu.style.left = "";
                menu.style.display = "";
                menu.style.visibility = "";
                moreButton.setAttribute("aria-expanded", "false");
                return;
            }

            /*
             * Smart floating position:
             * 1. Measure the menu.
             * 2. Try below, above, right, and left of the 3-dots button.
             * 3. Prefer the position that does not cover another pet row.
             * 4. Keep the menu inside the visible browser window.
             */
            const buttonRect = moreButton.getBoundingClientRect();

            menu.style.visibility = "hidden";
            menu.style.display = "block";

            const menuRect = menu.getBoundingClientRect();
            const menuWidth = menuRect.width;
            const menuHeight = menuRect.height;

            const gap = 6;
            const padding = 10;

            const viewportWidth = window.innerWidth;
            const viewportHeight = window.innerHeight;

            const candidates = [
                {
                    name: "below",
                    top: buttonRect.bottom + gap,
                    left: buttonRect.right - menuWidth
                },
                {
                    name: "above",
                    top: buttonRect.top - menuHeight - gap,
                    left: buttonRect.right - menuWidth
                },
                {
                    name: "right",
                    top: buttonRect.top,
                    left: buttonRect.right + gap
                },
                {
                    name: "left",
                    top: buttonRect.top,
                    left: buttonRect.left - menuWidth - gap
                },
                {
                    name: "below-left",
                    top: buttonRect.bottom + gap,
                    left: buttonRect.left
                },
                {
                    name: "above-left",
                    top: buttonRect.top - menuHeight - gap,
                    left: buttonRect.left
                }
            ];

            function clampPosition(candidate) {
                return {
                    top: Math.max(
                        padding,
                        Math.min(
                            candidate.top,
                            viewportHeight - menuHeight - padding
                        )
                    ),
                    left: Math.max(
                        padding,
                        Math.min(
                            candidate.left,
                            viewportWidth - menuWidth - padding
                        )
                    )
                };
            }

            function overlapArea(a, b) {
                const x = Math.max(
                    0,
                    Math.min(a.right, b.right) - Math.max(a.left, b.left)
                );

                const y = Math.max(
                    0,
                    Math.min(a.bottom, b.bottom) - Math.max(a.top, b.top)
                );

                return x * y;
            }

            const otherPetWraps = Array.from(
                document.querySelectorAll(".pet-more-wrap")
            ).filter(function (otherWrap) {
                return otherWrap !== wrap;
            });

            const scoredCandidates = candidates.map(function (candidate) {
                const position = clampPosition(candidate);

                const menuBox = {
                    left: position.left,
                    top: position.top,
                    right: position.left + menuWidth,
                    bottom: position.top + menuHeight
                };

                let collision = 0;

                otherPetWraps.forEach(function (otherWrap) {
                    const rect = otherWrap.getBoundingClientRect();

                    if (rect.width <= 0 || rect.height <= 0) return;

                    collision += overlapArea(menuBox, {
                        left: rect.left,
                        top: rect.top,
                        right: rect.right,
                        bottom: rect.bottom
                    });
                });

                /*
                 * Strongly prefer candidates that stay in the viewport.
                 * clampPosition() keeps them visible, so collision with
                 * another pet row becomes the main deciding factor.
                 */
                return {
                    ...candidate,
                    top: position.top,
                    left: position.left,
                    collision: collision
                };
            });

            scoredCandidates.sort(function (a, b) {
                return a.collision - b.collision;
            });

            const best = scoredCandidates[0];

            menu.style.top = `${best.top}px`;
            menu.style.left = `${best.left}px`;
            menu.style.visibility = "visible";

            wrap.classList.add("menu-open");
            moreButton.setAttribute("aria-expanded", "true");

            return;
        }

        if (!event.target.closest(".pet-more-wrap")) {
            document.querySelectorAll(".pet-more-wrap.menu-open").forEach(function (wrap) {
                wrap.classList.remove("menu-open");
                wrap.querySelector(".pet-more-btn")?.setAttribute("aria-expanded", "false");
            });
        }
    }, true);

    document.addEventListener("click", function (event) {
        const openButton = event.target.closest(".view-all-pets-btn");

        if (openButton) {
            event.preventDefault();
            event.stopPropagation();

            const modalId = openButton.getAttribute("data-pets-modal");
            const modal = modalId ? document.getElementById(modalId) : null;

            if (!modal) {
                console.error("Registered Pets modal not found:", modalId);
                return;
            }

            modal.classList.add("open");
            modal.setAttribute("aria-hidden", "false");
            document.body.classList.add("modal-open");
            return;
        }

        const closeButton = event.target.closest(".all-pets-modal-close");
        const backdrop = event.target.closest(".all-pets-modal-backdrop");

        if (closeButton || backdrop) {
            const modal = event.target.closest(".all-pets-modal");
            if (!modal) return;

            modal.classList.remove("open");
            modal.setAttribute("aria-hidden", "true");

            const anyAllPetsOpen = document.querySelector(".all-pets-modal.open");
            const anyOtherModalOpen = document.querySelector(
                ".modal.open, .modal.active, #petRecordModal.open, #medicalRecordModal.open, #addServiceModal.active"
            );

            if (!anyAllPetsOpen && !anyOtherModalOpen) {
                document.body.classList.remove("modal-open");
                document.body.style.overflow = "";
            }
        }
    });

    document.addEventListener("keydown", function (event) {
        if (event.key !== "Escape") return;

        const modal = document.querySelector(".all-pets-modal.open");
        if (!modal) return;

        modal.classList.remove("open");
        modal.setAttribute("aria-hidden", "true");

        const anyOtherModalOpen = document.querySelector(
            ".modal.open, .modal.active, #petRecordModal.open, #medicalRecordModal.open, #addServiceModal.active"
        );

        if (!anyOtherModalOpen) {
            document.body.classList.remove("modal-open");
            document.body.style.overflow = "";
        }
    });

});

/* =========================================================
   CUSTOMER MULTI-SELECT
   ========================================================= */

(function () {
    "use strict";

    const selectAllCustomers =
        document.getElementById("selectAllCustomers");

    const archiveSelectedCustomersBtn =
        document.getElementById("archiveSelectedCustomersBtn");

    const selectedCustomerCount =
        document.getElementById("selectedCustomerCount");

    if (
        !selectAllCustomers ||
        !archiveSelectedCustomersBtn ||
        !selectedCustomerCount
    ) {
        return;
    }

    function getCustomerCheckboxes() {
        return Array.from(
            document.querySelectorAll(".customer-select")
        );
    }

    function getSelectedCustomers() {
        return getCustomerCheckboxes().filter(
            checkbox => checkbox.checked
        );
    }

    function updateCustomerSelection() {

        const checkboxes =
            getCustomerCheckboxes();

        const selected =
            getSelectedCustomers();

        const count =
            selected.length;

        /* Update number */
        selectedCustomerCount.textContent =
            count;

        /* Enable / disable archive button */
        archiveSelectedCustomersBtn.disabled =
            count === 0;

        /* Update Select All */
        const allSelected =
            checkboxes.length > 0 &&
            selected.length === checkboxes.length;

        selectAllCustomers.checked =
            allSelected;

        /* Show partial selection */
        selectAllCustomers.indeterminate =
            selected.length > 0 &&
            !allSelected;
    }

    /* =====================================================
       INDIVIDUAL CUSTOMER CHECKBOX
       ===================================================== */

    document.addEventListener(
        "change",
        function (event) {

            if (
                !event.target.classList.contains(
                    "customer-select"
                )
            ) {
                return;
            }

            updateCustomerSelection();
        }
    );

    /* =====================================================
       SELECT ALL CUSTOMERS
       ===================================================== */

    selectAllCustomers.addEventListener(
        "change",
        function () {

            const checkboxes =
                getCustomerCheckboxes();

            checkboxes.forEach(
                function (checkbox) {
                    checkbox.checked =
                        selectAllCustomers.checked;
                }
            );

            updateCustomerSelection();
        }
    );

    /* Initial state */
    updateCustomerSelection();

})();

/* =========================================================
   MULTIPLE CUSTOMER ARCHIVE
   ========================================================= */

(function () {
    "use strict";

    function initMultipleCustomerArchive() {

        const selectAllCustomers =
            document.getElementById("selectAllCustomers");

        const archiveSelectedBtn =
            document.getElementById("archiveSelectedCustomersBtn");

        const selectedCustomerCount =
            document.getElementById("selectedCustomerCount");

        if (!selectAllCustomers || !archiveSelectedBtn) {
            console.warn(
                "Multiple Customer Archive: required elements not found."
            );
            return;
        }

        /* -----------------------------------------------------
           GET CUSTOMER CHECKBOXES
           ----------------------------------------------------- */

        function getCustomerCheckboxes() {
            return Array.from(
                document.querySelectorAll(
                    "#customerList .customer-select"
                )
            );
        }

        /* -----------------------------------------------------
           GET SELECTED CUSTOMERS
           ----------------------------------------------------- */

        function getSelectedCustomers() {
            return getCustomerCheckboxes().filter(function (checkbox) {
                return checkbox.checked;
            });
        }

        /* -----------------------------------------------------
           UPDATE UI
           ----------------------------------------------------- */

        function updateSelectionUI() {

            const checkboxes =
                getCustomerCheckboxes();

            const selected =
                getSelectedCustomers();

            const count =
                selected.length;

            if (selectedCustomerCount) {
                selectedCustomerCount.textContent =
                    String(count);
            }

            archiveSelectedBtn.disabled =
                count === 0;

            if (checkboxes.length === 0) {

                selectAllCustomers.checked = false;
                selectAllCustomers.indeterminate = false;

                return;
            }

            const allSelected =
                selected.length === checkboxes.length;

            const someSelected =
                selected.length > 0 &&
                selected.length < checkboxes.length;

            selectAllCustomers.checked =
                allSelected;

            selectAllCustomers.indeterminate =
                someSelected;
        }

        /* -----------------------------------------------------
           SELECT ALL
           ----------------------------------------------------- */

        selectAllCustomers.addEventListener(
            "change",
            function () {

                const checked =
                    selectAllCustomers.checked;

                getCustomerCheckboxes().forEach(
                    function (checkbox) {
                        checkbox.checked = checked;
                    }
                );

                updateSelectionUI();
            }
        );

        /* -----------------------------------------------------
           INDIVIDUAL CUSTOMER CHECKBOX
           ----------------------------------------------------- */

        document.addEventListener(
            "change",
            function (event) {

                if (
                    !event.target.classList.contains(
                        "customer-select"
                    )
                ) {
                    return;
                }

                updateSelectionUI();
            }
        );

        /* -----------------------------------------------------
           MULTIPLE ARCHIVE BUTTON
           ----------------------------------------------------- */

        archiveSelectedBtn.addEventListener(
            "click",
            function () {

                const selected =
                    getSelectedCustomers();

                if (!selected.length) {
                    return;
                }

                const customerIds =
                    selected.map(function (checkbox) {
                        return Number(checkbox.value);
                    });

                openMultipleArchiveModal(
                    customerIds
                );
            }
        );

        /* -----------------------------------------------------
           MULTIPLE ARCHIVE MODAL
           ----------------------------------------------------- */

        function openMultipleArchiveModal(customerIds) {

            const modal =
                document.getElementById(
                    "customerArchiveModal"
                );

            const nameEl =
                document.getElementById(
                    "customerArchiveName"
                );

            const reasonEl =
                document.getElementById(
                    "customerArchiveReason"
                );

            const errorEl =
                document.getElementById(
                    "customerArchiveError"
                );

            const confirmBtn =
                document.getElementById(
                    "customerArchiveConfirm"
                );

            const cancelBtn =
                document.getElementById(
                    "customerArchiveCancel"
                );

            const backdrop =
                document.getElementById(
                    "customerArchiveBackdrop"
                );

            if (
                !modal ||
                !reasonEl ||
                !confirmBtn
            ) {
                console.error(
                    "Multiple archive modal elements are missing."
                );
                return;
            }

            /* Store selected IDs */
            modal.dataset.customerIds =
                JSON.stringify(customerIds);

            /* Change modal text */
            const title =
                document.getElementById(
                    "customerArchiveTitle"
                );

            const description =
                modal.querySelector(
                    ".customer-archive-dialog-body p"
                );

            if (title) {
                title.textContent =
                    "Archive Customers?";
            }

            if (nameEl) {
                nameEl.textContent =
                    customerIds.length +
                    " customer records selected";
            }

            if (description) {
                description.textContent =
                    "The selected customer records and all associated pets will be archived.";
            }

            reasonEl.value = "";

            if (errorEl) {
                errorEl.textContent = "";
                errorEl.style.display = "none";
            }

            modal.classList.add("open");
            modal.setAttribute(
                "aria-hidden",
                "false"
            );

            document.body.classList.add(
                "modal-open"
            );

            setTimeout(function () {
                reasonEl.focus();
            }, 50);

            /* -------------------------------------------------
               CONFIRM MULTIPLE ARCHIVE
               ------------------------------------------------- */

            const oldConfirmHandler =
                confirmBtn._multipleArchiveHandler;

            if (oldConfirmHandler) {
                confirmBtn.removeEventListener(
                    "click",
                    oldConfirmHandler
                );
            }

            const multipleArchiveHandler =
                async function () {

                    const reason =
                        reasonEl.value.trim();

                    if (!reason) {

                        if (errorEl) {
                            errorEl.textContent =
                                "Please provide a reason for archiving these customers.";
                            errorEl.style.display =
                                "block";
                        }

                        reasonEl.focus();
                        return;
                    }

                    if (reason.length > 255) {

                        if (errorEl) {
                            errorEl.textContent =
                                "Archive reason must not exceed 255 characters.";
                            errorEl.style.display =
                                "block";
                        }

                        reasonEl.focus();
                        return;
                    }

                    confirmBtn.disabled = true;

                    confirmBtn.innerHTML =
                        '<i class="fa-solid fa-spinner fa-spin"></i> Archiving...';

                    const formData =
                        new FormData();

                    formData.append(
                        "action",
                        "archive_customer"
                    );

                    customerIds.forEach(
                        function (id) {

                            formData.append(
                                "customer_ids[]",
                                String(id)
                            );

                        }
                    );

                    formData.append(
                        "archive_reason",
                        reason
                    );

                    try {

                        const response =
                            await fetch(
                                "../process/archive_customers.php",
                                {
                                    method: "POST",
                                    body: formData,
                                    cache: "no-store"
                                }
                            );

                        const result =
                            await response.json();

                        if (
                            !response.ok ||
                            !result.success
                        ) {
                            throw new Error(
                                result.message ||
                                "Unable to archive the selected customer records."
                            );
                        }

                        modal.classList.remove(
                            "open"
                        );

                        modal.setAttribute(
                            "aria-hidden",
                            "true"
                        );

                        document.body.classList.remove(
                            "modal-open"
                        );

                        window.location.reload();

                    } catch (error) {

                        console.error(
                            "Multiple Customer Archive Error:",
                            error
                        );

                        if (errorEl) {
                            errorEl.textContent =
                                error.message ||
                                "Unable to archive the selected customer records.";
                            errorEl.style.display =
                                "block";
                        }

                    } finally {

                        confirmBtn.disabled = false;

                        confirmBtn.innerHTML =
                            '<i class="fa-solid fa-box-archive"></i> Archive Customer';
                    }
                };

            confirmBtn._multipleArchiveHandler =
                multipleArchiveHandler;

            confirmBtn.addEventListener(
                "click",
                multipleArchiveHandler
            );

            /* -------------------------------------------------
               CANCEL
               ------------------------------------------------- */

            const closeMultipleModal =
                function () {

                    modal.classList.remove(
                        "open"
                    );

                    modal.setAttribute(
                        "aria-hidden",
                        "true"
                    );

                    document.body.classList.remove(
                        "modal-open"
                    );

                    if (oldConfirmHandler) {
                        confirmBtn.removeEventListener(
                            "click",
                            oldConfirmHandler
                        );
                    }

                };

            if (cancelBtn) {
                cancelBtn.onclick =
                    closeMultipleModal;
            }

            if (backdrop) {
                backdrop.onclick =
                    closeMultipleModal;
            }
        }

        /* Initial state */
        updateSelectionUI();
    }

    if (
        document.readyState ===
        "loading"
    ) {

        document.addEventListener(
            "DOMContentLoaded",
            initMultipleCustomerArchive
        );

    } else {

        initMultipleCustomerArchive();
    }

})();

/* =========================================================
   OPEN CUSTOMER ARCHIVE
   ========================================================= */

document.addEventListener("DOMContentLoaded", function () {

    const archivedBtn =
        document.getElementById("archivedBtn");

    if (!archivedBtn) {
        return;
    }

    archivedBtn.addEventListener("click", function () {

        window.location.href = "customer_archive.php";

    });

});