/* =========================================================
   INVENTORY REPORT
   File: assets/js/inventory_report.js
   ========================================================= */

document.addEventListener("DOMContentLoaded", function () {

    /* =====================================================
       REPORT DATA
       ===================================================== */

    const reportData = window.inventoryReportData || {};

    const stockStatus = reportData.stockStatus || {
        labels: [],
        values: []
    };

    const categoryItems = reportData.categoryItems || {
        labels: [],
        values: []
    };

    const categoryStock = reportData.categoryStock || {
        labels: [],
        values: []
    };


    /* =====================================================
       CHART DEFAULTS
       ===================================================== */

    if (typeof Chart !== "undefined") {

        Chart.defaults.font.family =
            "Arial, Helvetica, sans-serif";

        Chart.defaults.font.size = 10;

        Chart.defaults.color = "#64748b";
    }


    /* =====================================================
       STOCK STATUS CHART
       ===================================================== */

    const statusCanvas =
        document.getElementById(
            "inventoryStatusChart"
        );

    if (
        statusCanvas &&
        typeof Chart !== "undefined"
    ) {

        new Chart(statusCanvas, {

            type: "doughnut",

            data: {

                labels: stockStatus.labels,

                datasets: [
                    {
                        data: stockStatus.values,

                        backgroundColor: [
                            "#16a36a",
                            "#f59e0b",
                            "#ef4444"
                        ],

                        borderWidth: 0,

                        hoverOffset: 5
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

                            usePointStyle: true,

                            pointStyle: "circle",

                            padding: 18,

                            font: {
                                size: 10
                            }
                        }
                    },

                    tooltip: {

                        callbacks: {

                            label: function (context) {

                                const value =
                                    context.raw || 0;

                                return (
                                    " " +
                                    context.label +
                                    ": " +
                                    value
                                );
                            }
                        }
                    }
                }
            }
        });
    }


    /* =====================================================
       ITEMS BY CATEGORY
       ===================================================== */

    const categoryCanvas =
        document.getElementById(
            "inventoryCategoryChart"
        );

    if (
        categoryCanvas &&
        typeof Chart !== "undefined"
    ) {

        new Chart(categoryCanvas, {

            type: "bar",

            data: {

                labels: categoryItems.labels,

                datasets: [
                    {
                        label: "Items",

                        data: categoryItems.values,

                        backgroundColor: "#7c5cff",

                        borderRadius: 5,

                        borderSkipped: false,

                        maxBarThickness: 32
                    }
                ]
            },

            options: {

                responsive: true,

                maintainAspectRatio: false,

                indexAxis: "y",

                plugins: {

                    legend: {
                        display: false
                    },

                    tooltip: {

                        callbacks: {

                            label: function (context) {

                                return (
                                    " Items: " +
                                    (context.raw || 0)
                                );
                            }
                        }
                    }
                },

                scales: {

                    x: {

                        beginAtZero: true,

                        ticks: {

                            precision: 0,

                            font: {
                                size: 9
                            }
                        },

                        grid: {
                            color: "#edf1f6"
                        }
                    },

                    y: {

                        ticks: {

                            font: {
                                size: 9
                            }
                        },

                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }


    /* =====================================================
       STOCK QUANTITY BY CATEGORY
       ===================================================== */

    const quantityCanvas =
        document.getElementById(
            "inventoryQuantityChart"
        );

    if (
        quantityCanvas &&
        typeof Chart !== "undefined"
    ) {

        new Chart(quantityCanvas, {

            type: "bar",

            data: {

                labels: categoryStock.labels,

                datasets: [
                    {
                        label: "Stock Quantity",

                        data: categoryStock.values,

                        backgroundColor: "#16a36a",

                        borderRadius: 5,

                        borderSkipped: false,

                        maxBarThickness: 40
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

                        callbacks: {

                            label: function (context) {

                                return (
                                    " Stock: " +
                                    (context.raw || 0)
                                );
                            }
                        }
                    }
                },

                scales: {

                    x: {

                        ticks: {

                            font: {
                                size: 9
                            }
                        },

                        grid: {
                            display: false
                        }
                    },

                    y: {

                        beginAtZero: true,

                        ticks: {

                            precision: 0,

                            font: {
                                size: 9
                            }
                        },

                        grid: {
                            color: "#edf1f6"
                        }
                    }
                }
            }
        });
    }


    /* =====================================================
       PRINT REPORT
       ===================================================== */

    const printButton =
        document.getElementById(
            "printInventoryReport"
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
       FILTER UX
       ===================================================== */

    const filterForm =
        document.querySelector(
            ".inventory-report-filters"
        );

    if (filterForm) {

        filterForm.addEventListener(
            "submit",
            function () {

                const selects =
                    filterForm.querySelectorAll(
                        "select"
                    );

                selects.forEach(function (select) {

                    if (!select.value) {

                        select.removeAttribute(
                            "name"
                        );
                    }

                });

            }
        );
    }


    /* =====================================================
       TABLE ROW HOVER
       ===================================================== */

    const reportTables =
        document.querySelectorAll(
            ".inventory-report-table tbody tr"
        );

    reportTables.forEach(function (row) {

        row.addEventListener(
            "mouseenter",
            function () {

                if (
                    !row.classList.contains(
                        "empty-report"
                    )
                ) {

                    row.style.transition =
                        "background-color .15s ease";
                }

            }
        );

    });

});