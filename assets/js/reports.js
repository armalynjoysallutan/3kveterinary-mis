/* =========================================================
   REPORTS MODULE JAVASCRIPT
   File: admin/reports.js
   Purpose: Render the Reports page charts
   ========================================================= */

document.addEventListener('DOMContentLoaded', function () {
    /* =====================================================
       1. GET REPORT DATA
       ===================================================== */
    const dataElement = document.getElementById('reportsData');

    if (!dataElement || typeof Chart === 'undefined') {
        return;
    }

    let data;

    try {
        data = JSON.parse(dataElement.textContent);
    } catch (error) {
        console.error('Unable to read reports data.', error);
        return;
    }

    /* =====================================================
       2. GET CHART CANVASES
       ===================================================== */
    const recordsCanvas = document.getElementById('recordsChart');
    const salesCanvas = document.getElementById('salesChart');
    const inventoryCanvas = document.getElementById('inventoryChart');

    /* =====================================================
       3. RECORDS CHART
       ===================================================== */
    if (recordsCanvas) {
        new Chart(recordsCanvas, {
            type: 'line',

            data: {
                labels: data.records.labels,

                datasets: [
                    {
                        data: data.records.values,
                        borderColor: '#2563eb',
                        backgroundColor: 'rgba(37, 99, 235, .08)',
                        fill: true,
                        tension: 0.35,
                        borderWidth: 2,
                        pointRadius: 2
                    }
                ]
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
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 8
                            }
                        }
                    },

                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                            font: {
                                size: 8
                            }
                        },
                        grid: {
                            color: '#edf1f6'
                        }
                    }
                }
            }
        });
    }

    /* =====================================================
       4. SALES CHART
       ===================================================== */
    if (salesCanvas) {
        new Chart(salesCanvas, {
            type: 'line',

            data: {
                labels: data.sales.labels,

                datasets: [
                    {
                        data: data.sales.values,
                        borderColor: '#16a36a',
                        backgroundColor: 'rgba(22, 163, 106, .08)',
                        fill: true,
                        tension: 0.35,
                        borderWidth: 2,
                        pointRadius: 2
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
                                return ' ₱' + Number(context.raw || 0).toLocaleString(
                                    'en-PH',
                                    {
                                        minimumFractionDigits: 2
                                    }
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
                            font: {
                                size: 8
                            }
                        }
                    },

                    y: {
                        beginAtZero: true,
                        ticks: {
                            font: {
                                size: 8
                            },
                            callback: function (value) {
                                return '₱' + Number(value).toLocaleString(
                                    'en-PH',
                                    {
                                        notation: 'compact'
                                    }
                                );
                            }
                        },
                        grid: {
                            color: '#edf1f6'
                        }
                    }
                }
            }
        });
    }

    /* =====================================================
       5. INVENTORY CHART
       ===================================================== */
    if (inventoryCanvas) {
        new Chart(inventoryCanvas, {
            type: 'doughnut',

            data: {
                labels: [
                    'In Stock',
                    'Low Stock',
                    'Out of Stock',
                    'Expiring Soon'
                ],

                datasets: [
                    {
                        data: data.inventory,
                        backgroundColor: [
                            '#16a36a',
                            '#f59e0b',
                            '#ef4444',
                            '#8b5cf6'
                        ],
                        borderWidth: 0
                    }
                ]
            },

            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',

                plugins: {
                    legend: {
                        position: 'bottom',

                        labels: {
                            boxWidth: 8,
                            boxHeight: 8,
                            padding: 8,

                            font: {
                                size: 8
                            }
                        }
                    }
                }
            }
        });
    }
});
