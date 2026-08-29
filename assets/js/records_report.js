/* =========================================================
   RECORDS REPORT
   File: assets/js/records_report.js
   ========================================================= */

document.addEventListener("DOMContentLoaded", function () {

    const data = window.recordsReportData || {};

    /* =========================================================
       CHART DEFAULTS
       ========================================================= */
    Chart.defaults.font.family = "Arial, sans-serif";
    Chart.defaults.color = "#64748b";

    function createLineChart(canvasId, chartData, label, borderColor, fillColor) {
        const canvas = document.getElementById(canvasId);

        if (!canvas || typeof Chart === "undefined") {
            return;
        }

        new Chart(canvas, {
            type: "line",
            data: {
                labels: chartData.labels || [],
                datasets: [{
                    label: label,
                    data: chartData.values || [],
                    borderColor: borderColor,
                    backgroundColor: fillColor,
                    borderWidth: 2,
                    tension: 0.35,
                    fill: true,
                    pointRadius: 3,
                    pointHoverRadius: 5
                }]
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
                                return " " + context.parsed.y + " registrations";
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        },
                        grid: {
                            color: "#edf1f6"
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    function createBarChart(canvasId, chartData) {
        const canvas = document.getElementById(canvasId);

        if (!canvas || typeof Chart === "undefined") {
            return;
        }

        new Chart(canvas, {
            type: "bar",
            data: {
                labels: chartData.labels || [],
                datasets: [{
                    label: "Number of Pets",
                    data: chartData.values || [],
                    borderRadius: 5,
                    borderSkipped: false,
                    backgroundColor: "#7c5cff"
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        },
                        grid: {
                            color: "#edf1f6"
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            maxRotation: 35,
                            minRotation: 0
                        }
                    }
                }
            }
        });
    }

    function createStatusChart(canvasId, chartData) {
        const canvas = document.getElementById(canvasId);

        if (!canvas || typeof Chart === "undefined") {
            return;
        }

        new Chart(canvas, {
            type: "doughnut",
            data: {
                labels: chartData.labels || [],
                datasets: [{
                    data: chartData.values || [],
                    backgroundColor: ["#16a36a", "#ef4444"],
                    borderWidth: 0,
                    hoverOffset: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: "68%",
                plugins: {
                    legend: {
                        position: "bottom",
                        labels: {
                            usePointStyle: true,
                            padding: 16,
                            font: {
                                size: 10
                            }
                        }
                    }
                }
            }
        });
    }

    /* =========================================================
       CREATE CHARTS
       ========================================================= */
    createLineChart(
        "petsTrendChart",
        data.petTrend || { labels: [], values: [] },
        "Pets Registered",
        "#635bff",
        "rgba(99, 91, 255, 0.10)"
    );

    createBarChart(
        "breedChart",
        data.breed || { labels: [], values: [] }
    );

    createLineChart(
        "customersTrendChart",
        data.customerTrend || { labels: [], values: [] },
        "Customers Registered",
        "#16a36a",
        "rgba(22, 163, 106, 0.10)"
    );

    createStatusChart(
        "statusChart",
        data.status || { labels: [], values: [] }
    );

    /* =========================================================
       FILTERS
       Note: The report is analytical, so filters affect the
       detailed filter state and can be extended to server-side
       filtering later without changing the page structure.
       ========================================================= */
    const dateFilter = document.getElementById("dateFilter");
    const breedFilter = document.getElementById("breedFilter");
    const statusFilter = document.getElementById("statusFilter");
    const resetButton = document.getElementById("resetRecordFilters");
    const applyButton = document.getElementById("applyRecordFilters");

    if (applyButton) {
        applyButton.addEventListener("click", function () {
            const params = new URLSearchParams();

            if (dateFilter && dateFilter.value !== "all") {
                params.set("date", dateFilter.value);
            }

            if (breedFilter && breedFilter.value !== "all") {
                params.set("breed", breedFilter.value);
            }

            if (statusFilter && statusFilter.value !== "all") {
                params.set("status", statusFilter.value);
            }

            const query = params.toString();
            const target = window.location.pathname + (query ? "?" + query : "");

            window.location.href = target;
        });
    }

    if (resetButton) {
        resetButton.addEventListener("click", function () {
            if (dateFilter) dateFilter.value = "all";
            if (breedFilter) breedFilter.value = "all";
            if (statusFilter) statusFilter.value = "all";

            window.location.href = window.location.pathname;
        });
    }

    /* =========================================================
       PRINT REPORT
       ========================================================= */
    const printButton = document.getElementById("printRecordsReport");

    if (printButton) {
        printButton.addEventListener("click", function () {
            window.print();
        });
    }
});
