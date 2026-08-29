document.addEventListener("DOMContentLoaded", function () {

    /* =========================================================
       QR CODE LIBRARY
       ========================================================= */

    function loadQRCodeLibrary(callback) {

        if (typeof QRCode !== "undefined") {
            callback();
            return;
        }

        const script = document.createElement("script");

        script.src =
            "https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js";

        script.onload = function () {
            callback();
        };

        script.onerror = function () {
            alert(
                "Unable to load the QR Code generator. Please check your internet connection."
            );
        };

        document.head.appendChild(script);
    }


    /* =========================================================
       QR URL
       ========================================================= */

    function getQRUrl(petId) {

        /*
         * FOR TESTING:
         * This creates a link based on the current system URL.
         *
         * Example:
         * http://localhost/veterinary-mis/pet_record.php?pet_id=PET-001
         *
         * IMPORTANT:
         * When the system is deployed/accessed through Wi-Fi,
         * replace this with the actual clinic system URL/IP.
         */

        const baseUrl =
            window.location.origin +
            "/veterinary-mis/pet_record.php";

        return (
            baseUrl +
            "?pet_id=" +
            encodeURIComponent(petId)
        );
    }


    /* =========================================================
       SEARCH
       ========================================================= */

    const searchInput =
        document.getElementById("vaccinationSearch");

    const rows =
        document.querySelectorAll(".vaccination-row");

    const emptyMessage =
        document.getElementById("vaccinationEmpty");


    if (searchInput) {

        searchInput.addEventListener("input", function () {

            const searchValue =
                this.value.trim().toLowerCase();

            let visibleRows = 0;

            rows.forEach(function (row) {

                const searchText =
                    row.dataset.search || "";

                const matched =
                    searchText.includes(searchValue);

                row.style.display =
                    matched ? "" : "none";

                if (matched) {
                    visibleRows++;
                }

            });


            if (emptyMessage) {

                emptyMessage.style.display =
                    visibleRows === 0
                        ? "block"
                        : "none";

            }

        });

    }


    /* =========================================================
       MODAL ELEMENTS
       ========================================================= */

    const modal =
        document.getElementById("viewCertificateModal");

    const closeBtn =
        document.getElementById("closeCertificateModal");

    const cancelBtn =
        document.getElementById("cancelCertificateModal");


    const modalPetId =
        document.getElementById("modalPetId");

    const modalPetName =
        document.getElementById("modalPetName");

    const modalOwner =
        document.getElementById("modalOwner");


    const qrPlaceholder =
        document.getElementById("qrPlaceholder");


    const printBtn =
        document.getElementById("printCertificateBtn");


    /* =========================================================
       CURRENT PET DATA
       ========================================================= */

    let currentPet = {
        id: "",
        name: "",
        owner: "",
        qrToken: "",
        qrUrl: ""
    };


    /* =========================================================
       GENERATE QR
       ========================================================= */

    function generateQR(petIdOrUrl) {

        if (!qrPlaceholder) {
            return;
        }

        loadQRCodeLibrary(function () {

            qrPlaceholder.innerHTML = "";

            const qrUrl =
                String(petIdOrUrl).includes("://")
                    ? String(petIdOrUrl)
                    : getQRUrl(petIdOrUrl);


            new QRCode(qrPlaceholder, {

                text: qrUrl,

                width: 150,

                height: 150,

                colorDark: "#111827",

                colorLight: "#ffffff",

                correctLevel:
                    QRCode.CorrectLevel.H

            });

        });

    }


    /* =========================================================
       OPEN VIEW MODAL
       ========================================================= */

    function openModal(button) {

        if (!modal) {
            return;
        }


        const petId =
            button.dataset.petId || "—";

        const petName =
            button.dataset.petName || "—";

        const owner =
            button.dataset.owner || "—";

        const savedQrToken =
            button.dataset.qrToken || "";

        const savedQrUrl =
            button.dataset.qrUrl || "";


        currentPet = {

            id: petId,

            name: petName,

            owner: owner

        };


        if (modalPetId) {

            modalPetId.textContent =
                petId;

        }


        if (modalPetName) {

            modalPetName.textContent =
                petName;

        }


        if (modalOwner) {

            modalOwner.textContent =
                owner;

        }


        modal.classList.add("show");

        document.body.style.overflow =
            "hidden";


        generateQR(
            savedQrUrl ||
            savedQrToken ||
            petId
        );

    }


    /* =========================================================
       CLOSE MODAL
       ========================================================= */

    function closeModal() {

        if (!modal) {
            return;
        }


        modal.classList.remove("show");

        document.body.style.overflow =
            "";

    }


    if (closeBtn) {

        closeBtn.addEventListener(
            "click",
            closeModal
        );

    }


    if (cancelBtn) {

        cancelBtn.addEventListener(
            "click",
            closeModal
        );

    }


    if (modal) {

        modal.addEventListener(
            "click",
            function (event) {

                if (
                    event.target === modal
                ) {

                    closeModal();

                }

            }
        );

    }


    /* =========================================================
       ESCAPE KEY
       ========================================================= */

    document.addEventListener(
        "keydown",
        function (event) {

            if (
                event.key === "Escape"
            ) {

                closeModal();

            }

        }
    );


    /* =========================================================
       ACTION BUTTONS
       ========================================================= */

    document.addEventListener(
        "click",
        async function (event) {

            const button =
                event.target.closest(
                    "[data-action]"
                );


            if (!button) {
                return;
            }


            const action =
                button.dataset.action;


            /* -------------------------------------------------
               VIEW
               ------------------------------------------------- */

            if (action === "view") {

                openModal(button);

                return;

            }


            /* -------------------------------------------------
               GENERATE
               ------------------------------------------------- */

            if (action === "generate") {

                const displayPetId =
                    button.dataset.petId || "";

                const dbPetId =
                    button.dataset.dbPetId ||
                    displayPetId;

                const petName =
                    button.dataset.petName ||
                    "this pet";

                if (!dbPetId) {
                    alert("Pet ID is missing.");
                    return;
                }

                const row =
                    button.closest(".vaccination-row");

                const owner =
                    button.dataset.owner ||
                    (row
                        ? row.children[2]?.textContent.trim()
                        : "—");

                if (button.disabled) {
                    return;
                }

                const originalHTML = button.innerHTML;

                button.disabled = true;
                button.innerHTML =
                    '<i class="fa-solid fa-spinner fa-spin"></i>';

                try {

                    const formData = new FormData();
                    formData.append("pet_id", dbPetId);

                    const response = await fetch(
                        "generate_vaccination_qr.php",
                        {
                            method: "POST",
                            body: formData
                        }
                    );

                    const data = await response.json();

                    if (!data.success) {
                        throw new Error(
                            data.message ||
                            "Unable to generate QR certificate."
                        );
                    }

                    const qrToken = data.qr_token || "";
                    const qrUrl =
                        data.qr_url || getQRUrl(qrToken);

                    currentPet = {
                        id: displayPetId,
                        name: petName,
                        owner: owner,
                        qrToken: qrToken,
                        qrUrl: qrUrl
                    };

                    if (modalPetId) {
                        modalPetId.textContent = displayPetId;
                    }

                    if (modalPetName) {
                        modalPetName.textContent = petName;
                    }

                    if (modalOwner) {
                        modalOwner.textContent = owner || "—";
                    }

                    if (modal) {
                        modal.classList.add("show");
                        document.body.style.overflow = "hidden";
                    }

                    generateQR(qrUrl);

                    const status = row
                        ? row.querySelector(".qr-status")
                        : null;

                    if (status) {
                        status.classList.remove("not-generated");
                        status.classList.add("generated");
                        status.innerHTML = `
                            <i class="fa-solid fa-circle-check"></i>
                            Generated
                        `;
                    }

                    const actionContainer = row
                        ? row.querySelector(".certificate-actions")
                        : null;

                    if (
                        actionContainer &&
                        !actionContainer.querySelector('[data-action="view"]')
                    ) {

                        actionContainer.innerHTML = `

                            <button
                                type="button"
                                class="action-btn view"
                                data-action="view"
                                data-pet-id="${escapeHTML(displayPetId)}"
                                data-db-pet-id="${escapeHTML(dbPetId)}"
                                data-pet-name="${escapeHTML(petName)}"
                                data-owner="${escapeHTML(owner)}"
                                data-qr-token="${escapeHTML(qrToken)}"
                                data-qr-url="${escapeHTML(qrUrl)}"
                                title="View QR Code"
                            >
                                <i class="fa-regular fa-eye"></i>
                            </button>

                            <button
                                type="button"
                                class="action-btn print"
                                data-action="print"
                                data-pet-id="${escapeHTML(displayPetId)}"
                                data-db-pet-id="${escapeHTML(dbPetId)}"
                                data-pet-name="${escapeHTML(petName)}"
                                data-owner="${escapeHTML(owner)}"
                                data-qr-token="${escapeHTML(qrToken)}"
                                data-qr-url="${escapeHTML(qrUrl)}"
                                title="Print QR Code"
                            >
                                <i class="fa-solid fa-print"></i>
                            </button>

                        `;
                    }

                } catch (error) {

                    console.error("Vaccination QR Error:", error);

                    alert(
                        error.message ||
                        "Unable to generate QR certificate."
                    );

                    button.innerHTML = originalHTML;
                    button.disabled = false;
                    return;
                }

                button.disabled = false;
                return;
            }


            /* -------------------------------------------------
               PRINT
               ------------------------------------------------- */

            if (action === "print") {

                const petId =
                    button.dataset.petId || "";

                const petName =
                    button.dataset.petName ||
                    "Pet";


                const owner =
                    button.dataset.owner ||
                    "—";


                if (!petId) {

                    alert(
                        "Pet ID is missing."
                    );

                    return;

                }


                printQRCode(
                    petId,
                    petName,
                    owner,
                    button.dataset.qrUrl ||
                    button.dataset.qrToken ||
                    ""
                );


                return;

            }

        }
    );


    /* =========================================================
       TOP GENERATE BUTTON
       ========================================================= */

    const generateCertificateBtn =
        document.getElementById(
            "generateCertificateBtn"
        );


    if (generateCertificateBtn) {

        generateCertificateBtn.addEventListener(
            "click",
            function () {

                /*
                 * Find pets that are currently
                 * displayed in the table.
                 */

                const visibleRows =
                    Array.from(rows)
                        .filter(function (row) {

                            return (
                                row.style.display !==
                                "none"
                            );

                        });


                if (
                    visibleRows.length === 0
                ) {

                    alert(
                        "No pet found. Please search for a pet first."
                    );

                    return;

                }


                /*
                 * If there is only one visible pet,
                 * generate directly.
                 */

                if (
                    visibleRows.length === 1
                ) {

                    const generateButton =
                        visibleRows[0]
                            .querySelector(
                                '[data-action="generate"]'
                            );


                    if (generateButton) {

                        generateButton.click();

                        return;

                    }


                    const viewButton =
                        visibleRows[0]
                            .querySelector(
                                '[data-action="view"]'
                            );


                    if (viewButton) {

                        viewButton.click();

                        return;

                    }

                }


                /*
                 * Otherwise ask which pet.
                 */

                let petList =
                    "Select a Pet:\n\n";


                visibleRows.forEach(
                    function (row, index) {

                        const petId =
                            row.querySelector(
                                ".pet-id"
                            )?.textContent
                                .trim() || "";

                        const petName =
                            row.children[1]
                                ?.textContent
                                .trim() || "";

                        petList +=
                            `${index + 1}. ${petId} - ${petName}\n`;

                    }
                );


                const selection =
                    prompt(
                        petList +
                        "\nEnter the number of the pet:"
                    );


                if (!selection) {
                    return;
                }


                const selectedIndex =
                    parseInt(selection, 10) - 1;


                if (
                    isNaN(selectedIndex) ||
                    !visibleRows[selectedIndex]
                ) {

                    alert(
                        "Invalid pet selection."
                    );

                    return;

                }


                const selectedRow =
                    visibleRows[selectedIndex];


                const generateButton =
                    selectedRow.querySelector(
                        '[data-action="generate"]'
                    );


                if (generateButton) {

                    generateButton.click();

                    return;

                }


                const viewButton =
                    selectedRow.querySelector(
                        '[data-action="view"]'
                    );


                if (viewButton) {

                    viewButton.click();

                }

            }
        );

    }


    /* =========================================================
       PRINT QR CODE
       ========================================================= */

    function printQRCode(
        petId,
        petName,
        owner,
        savedQrUrl
    ) {

        loadQRCodeLibrary(function () {

            /*
             * Create temporary QR container.
             */

            const tempQR =
                document.createElement("div");


            tempQR.style.position =
                "absolute";

            tempQR.style.left =
                "-99999px";

            tempQR.style.top =
                "0";


            document.body.appendChild(
                tempQR
            );


            const qrUrl =
                savedQrUrl ||
                getQRUrl(petId);


            new QRCode(tempQR, {

                text: qrUrl,

                width: 220,

                height: 220,

                colorDark: "#111827",

                colorLight: "#ffffff",

                correctLevel:
                    QRCode.CorrectLevel.H

            });


            /*
             * Wait for QR image/canvas.
             */

            setTimeout(
                function () {

                    const qrImage =
                        tempQR.querySelector(
                            "img"
                        );

                    const qrCanvas =
                        tempQR.querySelector(
                            "canvas"
                        );


                    let qrHTML = "";


                    if (qrImage) {

                        qrHTML =
                            `<img
                                src="${qrImage.src}"
                                alt="QR Code"
                            >`;

                    } else if (qrCanvas) {

                        qrHTML =
                            `<img
                                src="${qrCanvas.toDataURL("image/png")}"
                                alt="QR Code"
                            >`;

                    }


                    if (!qrHTML) {

                        document.body.removeChild(
                            tempQR
                        );

                        alert(
                            "Unable to generate QR code for printing."
                        );

                        return;

                    }


                    /*
                     * Open print window.
                     */

                    const printWindow =
                        window.open(
                            "",
                            "_blank",
                            "width=600,height=700"
                        );


                    if (!printWindow) {

                        document.body.removeChild(
                            tempQR
                        );

                        alert(
                            "Please allow pop-ups to print the QR code."
                        );

                        return;

                    }


                    printWindow.document.open();


                    printWindow.document.write(`
                        <!DOCTYPE html>

                        <html>

                        <head>

                            <title>
                                Vaccination QR - ${petName}
                            </title>

                            <style>

                                * {
                                    box-sizing: border-box;
                                }

                                body {
                                    margin: 0;
                                    padding: 30px;
                                    font-family:
                                        Arial,
                                        sans-serif;
                                    background: #ffffff;
                                    color: #1e293b;
                                    text-align: center;
                                }

                                .certificate {
                                    width: 100%;
                                    max-width: 420px;
                                    margin: 0 auto;
                                    padding: 28px;
                                    border: 1px solid #dbe2eb;
                                    border-radius: 14px;
                                }

                                .clinic {
                                    font-size: 20px;
                                    font-weight: 800;
                                    margin-bottom: 3px;
                                }

                                .subtitle {
                                    font-size: 11px;
                                    color: #64748b;
                                    letter-spacing: 1px;
                                    margin-bottom: 22px;
                                }

                                h2 {
                                    margin: 0 0 18px;
                                    font-size: 17px;
                                }

                                .qr {
                                    margin: 0 auto 20px;
                                }

                                .qr img {
                                    width: 220px;
                                    height: 220px;
                                    display: block;
                                    margin: auto;
                                }

                                .pet-info {
                                    text-align: left;
                                    border: 1px solid #e2e8f0;
                                    border-radius: 9px;
                                    overflow: hidden;
                                    margin-top: 18px;
                                }

                                .info-row {
                                    display: flex;
                                    justify-content: space-between;
                                    gap: 20px;
                                    padding: 10px 12px;
                                    border-bottom: 1px solid #edf1f5;
                                    font-size: 12px;
                                }

                                .info-row:last-child {
                                    border-bottom: none;
                                }

                                .label {
                                    color: #64748b;
                                    font-weight: 700;
                                }

                                .value {
                                    font-weight: 700;
                                    text-align: right;
                                }

                                .instruction {
                                    margin-top: 20px;
                                    font-size: 11px;
                                    line-height: 1.5;
                                    color: #64748b;
                                }

                                .url {
                                    margin-top: 12px;
                                    font-size: 8px;
                                    color: #94a3b8;
                                    word-break: break-all;
                                }

                                @media print {

                                    body {
                                        padding: 0;
                                    }

                                    .certificate {
                                        border: none;
                                    }

                                }

                            </style>

                        </head>

                        <body>

                            <div class="certificate">

                                <div class="clinic">
                                    3K PET SOLUTION
                                </div>

                                <div class="subtitle">
                                    ANIMAL CLINIC
                                </div>

                                <h2>
                                    Vaccination Record QR
                                </h2>

                                <div class="qr">
                                    ${qrHTML}
                                </div>

                                <div class="pet-info">

                                    <div class="info-row">

                                        <span class="label">
                                            PET ID
                                        </span>

                                        <span class="value">
                                            ${escapeHTML(petId)}
                                        </span>

                                    </div>

                                    <div class="info-row">

                                        <span class="label">
                                            PET NAME
                                        </span>

                                        <span class="value">
                                            ${escapeHTML(petName)}
                                        </span>

                                    </div>

                                    <div class="info-row">

                                        <span class="label">
                                            OWNER
                                        </span>

                                        <span class="value">
                                            ${escapeHTML(owner)}
                                        </span>

                                    </div>

                                </div>

                                <div class="instruction">

                                    Scan this QR code to view
                                    the pet's vaccination and
                                    medical records.

                                </div>

                                <div class="url">

                                    ${escapeHTML(qrUrl)}

                                </div>

                            </div>

                            <script>

                                window.onload = function () {

                                    window.print();

                                };

                            <\/script>

                        </body>

                        </html>
                    `);


                    printWindow.document.close();


                    /*
                     * Remove temporary QR.
                     */

                    document.body.removeChild(
                        tempQR
                    );

                },
                300
            );

        });

    }


    /* =========================================================
       ESCAPE HTML
       ========================================================= */

    function escapeHTML(value) {

        return String(value)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");

    }


});