/* =========================================================
   SALES REPORT MODULE
   File: assets/js/sales_report.js

   Handles:
   - Sales Trend Chart
   - Sales by Item Type Chart
   - Top 10 Services & Items Chart
   - Transaction Status Chart
   - Chart cleanup / reinitialization
   ========================================================= */

document.addEventListener("DOMContentLoaded", function () {

    /* =====================================================
       1. CHECK REPORT DATA
       ===================================================== */

    if (
        typeof window.salesReportData === "undefined" ||
        !window.salesReportData
    ) {
        console.warn("Sales Report data was not found.");
        return;
    }


    const reportData = window.salesReportData;


    /* =====================================================
       2. STORE CHART INSTANCES
       ===================================================== */

    let salesTrendChart = null;
    let salesTypeChart = null;
    let topItemsChart = null;
    let statusChart = null;


    /* =====================================================
       3. CHART COLORS
       ===================================================== */

    const colors = {
        blue: "#2563eb",
        blueLight: "rgba(37, 99, 235, 0.10)",

        green: "#16a36a",
        greenLight: "rgba(22, 163, 106, 0.10)",

        orange: "#f59e0b",
        red: "#ef4444",

        purple: "#7c5cff",

        grid: "#edf1f6",
        text: "#64748b"
    };


    /* =====================================================
       4. COMMON CHART OPTIONS
       ===================================================== */

    const commonFont = {
        family: "Arial, sans-serif",
        size: 10
    };


    /* =====================================================
       5. SALES TREND
       ===================================================== */

    function createSalesTrendChart() {

        const canvas =
            document.getElementById("salesTrendChart");

        if (!canvas) {
            console.warn(
                "salesTrendChart canvas was not found."
            );
            return;
        }


        if (salesTrendChart) {
            salesTrendChart.destroy();
        }


        const labels =
            reportData.salesTrend?.labels || [];

        const values =
            reportData.salesTrend?.values || [];


        salesTrendChart = new Chart(
            canvas,
            {
                type: "line",

                data: {
                    labels: labels,

                    datasets: [
                        {
                            label: "Sales",

                            data: values,

                            borderColor: colors.blue,

                            backgroundColor:
                                colors.blueLight,

                            borderWidth: 2,

                            fill: true,

                            tension: 0.35,

                            pointRadius: 3,

                            pointHoverRadius: 5,

                            pointBackgroundColor:
                                colors.blue,

                            pointBorderWidth: 0
                        }
                    ]
                },

                options: {
                    responsive: true,

                    maintainAspectRatio: false,

                    interaction: {
                        mode: "index",
                        intersect: false
                    },

                    plugins: {
                        legend: {
                            display: false
                        },

                        tooltip: {
                            callbacks: {
                                label: function (context) {

                                    const value =
                                        Number(
                                            context.raw || 0
                                        );

                                    return (
                                        " ₱" +
                                        value.toLocaleString(
                                            "en-PH",
                                            {
                                                minimumFractionDigits: 2,
                                                maximumFractionDigits: 2
                                            }
                                        )
                                    );
                                }
                            }
                        }
                    },

                    scales: {

                        x: {
                            grid: {
                                display: false
                            },

                            ticks: {
                                color: colors.text,
                                font: commonFont
                            }
                        },

                        y: {
                            beginAtZero: true,

                            grid: {
                                color: colors.grid
                            },

                            ticks: {
                                color: colors.text,
                                font: commonFont,

                                callback: function (value) {
                                    return "₱" +
                                        Number(value)
                                            .toLocaleString(
                                                "en-PH"
                                            );
                                }
                            }
                        }
                    }
                }
            }
        );
    }


    /* =====================================================
       6. SALES BY ITEM TYPE
       ===================================================== */

    function createSalesTypeChart() {

        const canvas =
            document.getElementById("salesTypeChart");

        if (!canvas) {
            console.warn(
                "salesTypeChart canvas was not found."
            );
            return;
        }


        if (salesTypeChart) {
            salesTypeChart.destroy();
        }


        const labels =
            reportData.salesByType?.labels || [];

        const values =
            reportData.salesByType?.values || [];


        if (!labels.length) {

            salesTypeChart = new Chart(
                canvas,
                {
                    type: "doughnut",

                    data: {
                        labels: ["No Data"],

                        datasets: [
                            {
                                data: [1],

                                backgroundColor: [
                                    "#e5e7eb"
                                ],

                                borderWidth: 0
                            }
                        ]
                    },

                    options: {
                        responsive: true,
                        maintainAspectRatio: false,

                        plugins: {
                            legend: {
                                display: false
                            },

                            tooltip: {
                                enabled: false
                            }
                        }
                    }
                }
            );

            return;
        }


        const palette = [
            "#2563eb",
            "#16a36a",
            "#f59e0b",
            "#7c5cff",
            "#ef4444",
            "#06b6d4",
            "#ec4899",
            "#64748b"
        ];


        salesTypeChart = new Chart(
            canvas,
            {
                type: "doughnut",

                data: {
                    labels: labels,

                    datasets: [
                        {
                            data: values,

                            backgroundColor:
                                labels.map(
                                    function (_, index) {
                                        return palette[
                                            index %
                                            palette.length
                                        ];
                                    }
                                ),

                            borderWidth: 2,

                            borderColor: "#ffffff"
                        }
                    ]
                },

                options: {
                    responsive: true,

                    maintainAspectRatio: false,

                    cutout: "64%",

                    plugins: {

                        legend: {
                            position: "bottom",

                            labels: {
                                color: colors.text,

                                font: {
                                    family:
                                        "Arial, sans-serif",
                                    size: 9
                                },

                                boxWidth: 10,
                                boxHeight: 10,

                                padding: 10
                            }
                        },

                        tooltip: {

                            callbacks: {

                                label: function (context) {

                                    const value =
                                        Number(
                                            context.raw || 0
                                        );

                                    return (
                                        " " +
                                        context.label +
                                        ": ₱" +
                                        value.toLocaleString(
                                            "en-PH",
                                            {
                                                minimumFractionDigits: 2,
                                                maximumFractionDigits: 2
                                            }
                                        )
                                    );
                                }
                            }
                        }
                    }
                }
            }
        );
    }


    /* =====================================================
       7. TOP 10 ITEMS
       ===================================================== */

    function createTopItemsChart() {

        const canvas =
            document.getElementById("topItemsChart");

        if (!canvas) {
            console.warn(
                "topItemsChart canvas was not found."
            );
            return;
        }


        if (topItemsChart) {
            topItemsChart.destroy();
        }


        const labels =
            reportData.topItems?.labels || [];

        const values =
            reportData.topItems?.values || [];


        topItemsChart = new Chart(
            canvas,
            {
                type: "bar",

                data: {
                    labels: labels,

                    datasets: [
                        {
                            label: "Revenue",

                            data: values,

                            backgroundColor:
                                colors.green,

                            borderRadius: 5,

                            borderSkipped: false
                        }
                    ]
                },

                options: {
                    indexAxis: "y",

                    responsive: true,

                    maintainAspectRatio: false,

                    plugins: {

                        legend: {
                            display: false
                        },

                        tooltip: {

                            callbacks: {

                                label: function (context) {

                                    const value =
                                        Number(
                                            context.raw || 0
                                        );

                                    return (
                                        " ₱" +
                                        value.toLocaleString(
                                            "en-PH",
                                            {
                                                minimumFractionDigits: 2,
                                                maximumFractionDigits: 2
                                            }
                                        )
                                    );
                                }
                            }
                        }
                    },

                    scales: {

                        x: {
                            beginAtZero: true,

                            grid: {
                                color: colors.grid
                            },

                            ticks: {
                                color: colors.text,

                                font: commonFont,

                                callback: function (value) {
                                    return "₱" +
                                        Number(value)
                                            .toLocaleString(
                                                "en-PH"
                                            );
                                }
                            }
                        },

                        y: {

                            grid: {
                                display: false
                            },

                            ticks: {
                                color: colors.text,

                                font: {
                                    family:
                                        "Arial, sans-serif",
                                    size: 9
                                }
                            }
                        }
                    }
                }
            }
        );
    }


    /* =====================================================
       8. TRANSACTION STATUS
       ===================================================== */

    function createStatusChart() {

        const canvas =
            document.getElementById("statusChart");

        if (!canvas) {
            console.warn(
                "statusChart canvas was not found."
            );
            return;
        }


        if (statusChart) {
            statusChart.destroy();
        }


        const labels =
            reportData.statusDistribution?.labels || [];

        const values =
            reportData.statusDistribution?.values || [];


        if (!labels.length) {

            statusChart = new Chart(
                canvas,
                {
                    type: "doughnut",

                    data: {
                        labels: ["No Data"],

                        datasets: [
                            {
                                data: [1],

                                backgroundColor: [
                                    "#e5e7eb"
                                ],

                                borderWidth: 0
                            }
                        ]
                    },

                    options: {
                        responsive: true,
                        maintainAspectRatio: false,

                        plugins: {
                            legend: {
                                display: false
                            },

                            tooltip: {
                                enabled: false
                            }
                        }
                    }
                }
            );

            return;
        }


        const backgroundColors =
            labels.map(function (label) {

                if (
                    String(label).toLowerCase()
                        === "paid"
                ) {
                    return colors.green;
                }

                if (
                    String(label).toLowerCase()
                        === "pending"
                ) {
                    return colors.orange;
                }

                return colors.blue;
            });


        statusChart = new Chart(
            canvas,
            {
                type: "doughnut",

                data: {
                    labels: labels,

                    datasets: [
                        {
                            data: values,

                            backgroundColor:
                                backgroundColors,

                            borderColor: "#ffffff",

                            borderWidth: 2
                        }
                    ]
                },

                options: {
                    responsive: true,

                    maintainAspectRatio: false,

                    cutout: "64%",

                    plugins: {

                        legend: {
                            position: "bottom",

                            labels: {
                                color: colors.text,

                                font: {
                                    family:
                                        "Arial, sans-serif",
                                    size: 9
                                },

                                boxWidth: 10,
                                boxHeight: 10,

                                padding: 10
                            }
                        },

                        tooltip: {

                            callbacks: {

                                label: function (context) {

                                    const value =
                                        Number(
                                            context.raw || 0
                                        );

                                    return (
                                        " " +
                                        context.label +
                                        ": " +
                                        value +
                                        " transaction" +
                                        (
                                            value === 1
                                                ? ""
                                                : "s"
                                        )
                                    );
                                }
                            }
                        }
                    }
                }
            }
        );
    }


    /* =====================================================
       9. CREATE ALL CHARTS
       ===================================================== */

    function initializeSalesCharts() {

        createSalesTrendChart();

        createSalesTypeChart();

        createTopItemsChart();

        createStatusChart();
    }


    /* =====================================================
       10. DATE FILTER VALIDATION
       ===================================================== */

    const filterForm =
        document.querySelector(
            ".sales-filter-form"
        );

    const dateFrom =
        document.getElementById("date_from");

    const dateTo =
        document.getElementById("date_to");


    if (filterForm) {

        filterForm.addEventListener(
            "submit",
            function (event) {

                if (
                    dateFrom &&
                    dateTo &&
                    dateFrom.value &&
                    dateTo.value
                ) {

                    if (
                        dateFrom.value >
                        dateTo.value
                    ) {

                        event.preventDefault();

                        alert(
                            "Date From cannot be later than Date To."
                        );

                        dateFrom.focus();

                        return;
                    }
                }
            }
        );
    }


    /* =====================================================
       11. RESET FILTER
       ===================================================== */

    const resetButton =
        document.querySelector(
            ".reset-filter-button"
        );


    if (resetButton) {

        resetButton.addEventListener(
            "click",
            function () {

                /*
                 * The reset button already points to:
                 * sales_report.php
                 *
                 * No additional action is required.
                 */
            }
        );
    }


    /* =====================================================
       12. PRINT REPORT
       ===================================================== */

    const printButton =
        document.querySelector(
            ".print-button"
        );


    if (printButton) {

        printButton.addEventListener(
            "click",
            function () {

                window.print();

            }
        );
    }


    /* =====================================================
       13. INITIALIZE
       ===================================================== */

    initializeSalesCharts();


    /* =====================================================
       14. HANDLE WINDOW RESIZE
       ===================================================== */

    let resizeTimer = null;

    window.addEventListener(
        "resize",
        function () {

            clearTimeout(resizeTimer);

            resizeTimer = setTimeout(
                function () {

                    if (salesTrendChart) {
                        salesTrendChart.resize();
                    }

                    if (salesTypeChart) {
                        salesTypeChart.resize();
                    }

                    if (topItemsChart) {
                        topItemsChart.resize();
                    }

                    if (statusChart) {
                        statusChart.resize();
                    }

                },
                150
            );
        }
    );

});