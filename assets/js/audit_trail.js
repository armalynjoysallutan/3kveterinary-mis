document.addEventListener("DOMContentLoaded", function () {

    // =========================================================
    // ELEMENTS
    // =========================================================

    const auditTableBody =
        document.getElementById("auditTableBody");

    const auditLogCount =
        document.getElementById("auditLogCount");

    const auditPaginationInfo =
        document.getElementById("auditPaginationInfo");

    const auditPagination =
        document.getElementById("auditPagination");

    const auditSearch =
        document.getElementById("auditSearch");

    const auditDateFrom =
        document.getElementById("auditDateFrom");

    const auditDateTo =
        document.getElementById("auditDateTo");

    const auditUserFilter =
        document.getElementById("auditUserFilter");

    const auditModuleFilter =
        document.getElementById("auditModuleFilter");

    const auditActionFilter =
        document.getElementById("auditActionFilter");

    const auditApplyBtn =
        document.getElementById("auditApplyBtn");

    const auditResetBtn =
        document.getElementById("auditResetBtn");


    // =========================================================
    // DATA
    // =========================================================

    let allAuditLogs = [];

    let filteredAuditLogs = [];

    let currentPage = 1;

    const logsPerPage = 10;


    // =========================================================
    // ESCAPE HTML
    // Prevents database text from being interpreted as HTML.
    // =========================================================

    function escapeHtml(value) {

        if (value === null || value === undefined) {
            return "";
        }

        return String(value)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");

    }


    // =========================================================
    // FORMAT DATE & TIME
    // =========================================================

    function formatDateTime(dateValue) {

        if (!dateValue) {
            return "-";
        }

        const date =
            new Date(
                dateValue.replace(" ", "T")
            );

        if (Number.isNaN(date.getTime())) {
            return escapeHtml(dateValue);
        }

        return date.toLocaleString(
            "en-PH",
            {
                year: "numeric",
                month: "short",
                day: "2-digit",
                hour: "2-digit",
                minute: "2-digit",
                hour12: true
            }
        );

    }


    // =========================================================
    // ACTION CLASS
    // =========================================================

    function getActionClass(action) {

        const normalized =
            String(action || "")
                .toLowerCase()
                .trim();

        if (normalized === "created") {
            return "created";
        }

        if (normalized === "updated") {
            return "updated";
        }

        if (normalized === "archived") {
            return "archived";
        }

        if (normalized === "restored") {
            return "restored";
        }

        if (normalized === "deleted") {
            return "deleted";
        }

        if (
            normalized === "login" ||
            normalized === "logout"
        ) {
            return normalized;
        }

        return "";

    }


    // =========================================================
    // LOAD AUDIT LOGS
    // =========================================================

    async function loadAuditLogs() {

        try {

            const response =
                await fetch(
                    "../process/get_audit_logs.php",
                    {
                        method: "GET",
                        headers: {
                            "Accept":
                                "application/json"
                        }
                    }
                );


            if (!response.ok) {
                throw new Error(
                    "Failed to load audit logs."
                );
            }


            const data =
                await response.json();


            if (!data.success) {
                throw new Error(
                    data.message ||
                    "Unable to retrieve audit logs."
                );
            }


            allAuditLogs =
                Array.isArray(data.logs)
                    ? data.logs
                    : [];


            filteredAuditLogs =
                [...allAuditLogs];


            populateUserFilter();


            currentPage = 1;

            renderAuditLogs();

        } catch (error) {

            console.error(
                "Audit Trail Error:",
                error
            );


            if (auditTableBody) {

                auditTableBody.innerHTML = `
                    <tr class="audit-empty-row">
                        <td colspan="8">
                            <div class="audit-empty-state">
                                <div class="audit-empty-icon">
                                    <i class="fa-solid fa-triangle-exclamation"></i>
                                </div>

                                <h3>
                                    Unable to load audit logs
                                </h3>

                                <p>
                                    Please refresh the page and try again.
                                </p>
                            </div>
                        </td>
                    </tr>
                `;

            }

        }

    }


    // =========================================================
    // POPULATE USER FILTER
    // =========================================================

    function populateUserFilter() {

        if (!auditUserFilter) {
            return;
        }


        const currentValue =
            auditUserFilter.value;


        const users =
            [
                ...new Set(
                    allAuditLogs
                        .map(function (log) {
                            return log.username;
                        })
                        .filter(function (username) {
                            return username;
                        })
                )
            ];


        auditUserFilter.innerHTML = `
            <option value="">
                All Users
            </option>
        `;


        users.forEach(function (username) {

            const option =
                document.createElement("option");

            option.value =
                username;

            option.textContent =
                username;

            auditUserFilter.appendChild(
                option
            );

        });


        if (
            users.includes(currentValue)
        ) {
            auditUserFilter.value =
                currentValue;
        }

    }


    // =========================================================
    // APPLY FILTERS
    // =========================================================

    function applyFilters() {

        const searchValue =
            (
                auditSearch?.value || ""
            )
                .trim()
                .toLowerCase();


        const dateFrom =
            auditDateFrom?.value || "";


        const dateTo =
            auditDateTo?.value || "";


        const selectedUser =
            auditUserFilter?.value || "";


        const selectedModule =
            auditModuleFilter?.value || "";


        const selectedAction =
            auditActionFilter?.value || "";


        filteredAuditLogs =
            allAuditLogs.filter(
                function (log) {


                    // SEARCH
                    if (searchValue) {

                        const searchText =
                            [
                                log.username,
                                log.role,
                                log.module,
                                log.action,
                                log.description,
                                log.reference_no
                            ]
                                .filter(Boolean)
                                .join(" ")
                                .toLowerCase();


                        if (
                            !searchText.includes(
                                searchValue
                            )
                        ) {
                            return false;
                        }

                    }


                    // USER
                    if (
                        selectedUser &&
                        log.username !== selectedUser
                    ) {
                        return false;
                    }


                    // MODULE
                    if (
                        selectedModule &&
                        log.module !== selectedModule
                    ) {
                        return false;
                    }


                    // ACTION
                    if (
                        selectedAction &&
                        log.action !== selectedAction
                    ) {
                        return false;
                    }


                    // DATE FROM
                    if (dateFrom) {

                        const logDate =
                            String(
                                log.created_at || ""
                            ).substring(0, 10);


                        if (logDate < dateFrom) {
                            return false;
                        }

                    }


                    // DATE TO
                    if (dateTo) {

                        const logDate =
                            String(
                                log.created_at || ""
                            ).substring(0, 10);


                        if (logDate > dateTo) {
                            return false;
                        }

                    }


                    return true;

                }
            );


        currentPage = 1;

        renderAuditLogs();

    }


    // =========================================================
    // RENDER TABLE
    // =========================================================

    function renderAuditLogs() {

        if (!auditTableBody) {
            return;
        }


        const totalLogs =
            filteredAuditLogs.length;


        if (auditLogCount) {

            auditLogCount.textContent =
                totalLogs +
                " Log" +
                (
                    totalLogs === 1
                        ? ""
                        : "s"
                );

        }


        // EMPTY STATE
        if (totalLogs === 0) {

            auditTableBody.innerHTML = `
                <tr class="audit-empty-row">
                    <td colspan="8">
                        <div class="audit-empty-state">

                            <div class="audit-empty-icon">
                                <i class="fa-solid fa-clock-rotate-left"></i>
                            </div>

                            <h3>
                                No audit logs found
                            </h3>

                            <p>
                                No system activities match
                                the selected filters.
                            </p>

                        </div>
                    </td>
                </tr>
            `;


            renderPagination();

            return;

        }


        const totalPages =
            Math.ceil(
                totalLogs / logsPerPage
            );


        if (
            currentPage > totalPages
        ) {
            currentPage = totalPages;
        }


        const startIndex =
            (
                currentPage - 1
            ) * logsPerPage;


        const endIndex =
            Math.min(
                startIndex + logsPerPage,
                totalLogs
            );


        const pageLogs =
            filteredAuditLogs.slice(
                startIndex,
                endIndex
            );


        auditTableBody.innerHTML =
            pageLogs
                .map(function (log) {

                    const actionClass =
                        getActionClass(
                            log.action
                        );


                    return `
                        <tr class="audit-row">

                            <td>
                                <span class="audit-date">
                                    ${formatDateTime(
                                        log.created_at
                                    )}
                                </span>
                            </td>


                            <td>
                                <span class="audit-user">
                                    ${escapeHtml(
                                        log.username || "-"
                                    )}
                                </span>
                            </td>


                            <td>
                                <span class="audit-role">
                                    ${escapeHtml(
                                        log.role || "-"
                                    )}
                                </span>
                            </td>


                            <td>
                                <span class="audit-module">
                                    ${escapeHtml(
                                        log.module || "-"
                                    )}
                                </span>
                            </td>


                            <td>
                                <span
                                    class="audit-action ${actionClass}"
                                >
                                    ${escapeHtml(
                                        log.action || "-"
                                    )}
                                </span>
                            </td>


                            <td>
                                <span class="audit-description">
                                    ${escapeHtml(
                                        log.description || "-"
                                    )}
                                </span>
                            </td>


                            <td>
                                <span class="audit-reference">
                                    ${escapeHtml(
                                        log.reference_no || "-"
                                    )}
                                </span>
                            </td>


                            <td>
                                <button
                                    type="button"
                                    class="audit-view-btn"
                                    title="View Audit Log"
                                    data-id="${escapeHtml(
                                        log.id
                                    )}"
                                >
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </td>

                        </tr>
                    `;

                })
                .join("");


        renderPagination();

    }


    // =========================================================
    // PAGINATION
    // =========================================================

    function renderPagination() {

        if (!auditPagination) {
            return;
        }


        const totalLogs =
            filteredAuditLogs.length;


        const totalPages =
            Math.max(
                1,
                Math.ceil(
                    totalLogs / logsPerPage
                )
            );


        if (totalLogs === 0) {

            if (auditPaginationInfo) {

                auditPaginationInfo.textContent =
                    "Showing 0 to 0 of 0 logs";

            }


            auditPagination.innerHTML = `
                <button
                    type="button"
                    disabled
                >
                    <i class="fa-solid fa-chevron-left"></i>
                </button>

                <button
                    type="button"
                    class="active"
                >
                    1
                </button>

                <button
                    type="button"
                    disabled
                >
                    <i class="fa-solid fa-chevron-right"></i>
                </button>
            `;

            return;

        }


        const start =
            (
                currentPage - 1
            ) * logsPerPage + 1;


        const end =
            Math.min(
                currentPage * logsPerPage,
                totalLogs
            );


        if (auditPaginationInfo) {

            auditPaginationInfo.textContent =
                `Showing ${start} to ${end} of ${totalLogs} logs`;

        }


        let html = "";


        // PREVIOUS
        html += `
            <button
                type="button"
                data-page="${currentPage - 1}"
                ${currentPage === 1 ? "disabled" : ""}
            >
                <i class="fa-solid fa-chevron-left"></i>
            </button>
        `;


        // PAGE NUMBERS
        for (
            let page = 1;
            page <= totalPages;
            page++
        ) {

            html += `
                <button
                    type="button"
                    data-page="${page}"
                    class="${
                        page === currentPage
                            ? "active"
                            : ""
                    }"
                >
                    ${page}
                </button>
            `;

        }


        // NEXT
        html += `
            <button
                type="button"
                data-page="${currentPage + 1}"
                ${
                    currentPage === totalPages
                        ? "disabled"
                        : ""
                }
            >
                <i class="fa-solid fa-chevron-right"></i>
            </button>
        `;


        auditPagination.innerHTML =
            html;

    }


    // =========================================================
    // PAGINATION CLICK
    // =========================================================

    if (auditPagination) {

        auditPagination.addEventListener(
            "click",
            function (event) {

                const button =
                    event.target.closest(
                        "button[data-page]"
                    );


                if (!button) {
                    return;
                }


                if (
                    button.disabled
                ) {
                    return;
                }


                const page =
                    Number(
                        button.dataset.page
                    );


                if (
                    Number.isNaN(page) ||
                    page < 1
                ) {
                    return;
                }


                currentPage = page;

                renderAuditLogs();

            }
        );

    }


    // =========================================================
    // APPLY BUTTON
    // =========================================================

    if (auditApplyBtn) {

        auditApplyBtn.addEventListener(
            "click",
            function () {

                applyFilters();

            }
        );

    }


    // =========================================================
    // RESET BUTTON
    // =========================================================

    if (auditResetBtn) {

        auditResetBtn.addEventListener(
            "click",
            function () {

                if (auditSearch) {
                    auditSearch.value = "";
                }

                if (auditDateFrom) {
                    auditDateFrom.value = "";
                }

                if (auditDateTo) {
                    auditDateTo.value = "";
                }

                if (auditUserFilter) {
                    auditUserFilter.value = "";
                }

                if (auditModuleFilter) {
                    auditModuleFilter.value = "";
                }

                if (auditActionFilter) {
                    auditActionFilter.value = "";
                }


                filteredAuditLogs =
                    [...allAuditLogs];

                currentPage = 1;

                renderAuditLogs();

            }
        );

    }


    // =========================================================
    // SEARCH ON ENTER
    // =========================================================

    if (auditSearch) {

        auditSearch.addEventListener(
            "keydown",
            function (event) {

                if (
                    event.key === "Enter"
                ) {

                    applyFilters();

                }

            }
        );

    }


    // =========================================================
    // EXPORT BUTTON
    // =========================================================

    const auditExportBtn =
        document.getElementById(
            "auditExportBtn"
        );


    if (auditExportBtn) {

        auditExportBtn.addEventListener(
            "click",
            function () {

                if (
                    filteredAuditLogs.length === 0
                ) {

                    alert(
                        "There are no audit logs to export."
                    );

                    return;
                }


                let csv =
                    "Date & Time,User,Role,Module,Action,Description,Reference No.\n";


                filteredAuditLogs.forEach(
                    function (log) {

                        const row = [
                            log.created_at || "",
                            log.username || "",
                            log.role || "",
                            log.module || "",
                            log.action || "",
                            log.description || "",
                            log.reference_no || ""
                        ];


                        csv +=
                            row
                                .map(function (value) {

                                    return `"${String(value)
                                        .replace(/"/g, '""')}"`;

                                })
                                .join(",") +
                            "\n";

                    }
                );


                const blob =
                    new Blob(
                        [csv],
                        {
                            type:
                                "text/csv;charset=utf-8;"
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
                    "audit_trail.csv";


                document.body.appendChild(
                    link
                );


                link.click();

                link.remove();

                URL.revokeObjectURL(
                    url
                );

            }
        );

    }


    // =========================================================
    // INITIAL LOAD
    // =========================================================

    loadAuditLogs();

});