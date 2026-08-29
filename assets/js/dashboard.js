document.addEventListener("DOMContentLoaded", function () {

    /* =========================
       CALENDAR MODAL ELEMENTS
    ========================= */
    const calendarModal = document.getElementById("calendarModal");
    const closeCalendarModal = document.getElementById("closeCalendarModal");

    /* =========================
       CALENDAR
    ========================= */
    const calendarEl = document.getElementById("calendar");
    const currentMonth = document.getElementById("currentMonth");

    function openCalendarModal(dateStr) {
        const modalDate = document.getElementById("calendarModalDate");
        const modalBody = document.getElementById("calendarModalBody");

        if (modalDate) {
            const clickedDate = new Date(dateStr + "T00:00:00");
            modalDate.textContent = clickedDate.toLocaleDateString("en-US", {
                year: "numeric",
                month: "long",
                day: "numeric"
            });
        }

        if (modalBody) {
            modalBody.innerHTML = `<div class="modal-empty">Loading events...</div>`;
        }

        fetch(`../process/get_day_events.php?date=${dateStr}`)
            .then(response => response.json())
            .then(data => {
                let appointmentsHTML = "";
                let deliveryHTML = "";
                let restockHTML = "";
                let otherHTML = "";

                /* =========================
                   APPOINTMENTS
                ========================= */
                if (data.appointments && data.appointments.length > 0) {
                    data.appointments.forEach(item => {
                        const rawTime = item.appointment_time;
                        const timeObj = new Date(`1970-01-01T${rawTime}`);
                        const formattedTime = timeObj.toLocaleTimeString("en-US", {
                            hour: "numeric",
                            minute: "2-digit",
                            hour12: true
                        });

                        appointmentsHTML += `
                            <div class="modal-event-card">
                                <div class="modal-event-time">${formattedTime}</div>
                                <div class="modal-event-details">
                                    <strong>${item.pet_name} - ${item.service}</strong>
                                    <small>Owner: ${item.owner_name}</small>
                                    <small>Status: ${item.status}</small>
                                </div>
                            </div>
                        `;
                    });
                } else {
                    appointmentsHTML = `
                        <div class="modal-empty">
                            No appointments for this day.
                        </div>
                    `;
                }

                /* =========================
                   DELIVERY EVENTS
                ========================= */
                if (data.delivery_events && data.delivery_events.length > 0) {
                    data.delivery_events.forEach(item => {
                        let formattedTime = "No time set";

                        if (item.event_time) {
                            const timeObj = new Date(`1970-01-01T${item.event_time}`);
                            formattedTime = timeObj.toLocaleTimeString("en-US", {
                                hour: "numeric",
                                minute: "2-digit",
                                hour12: true
                            });
                        }

                        deliveryHTML += `
                            <div class="modal-event-card">
                                <div class="modal-event-time">${formattedTime}</div>
                                <div class="modal-event-details">
                                    <strong>${item.event_title}</strong>
                                    <small>${item.notes ? item.notes : "No notes available."}</small>
                                </div>
                            </div>
                        `;
                    });
                } else {
                    deliveryHTML = `
                        <div class="modal-empty">
                            No delivery events for this day.
                        </div>
                    `;
                }

                /* =========================
                   RESTOCK EVENTS
                ========================= */
                if (data.restock_events && data.restock_events.length > 0) {
                    data.restock_events.forEach(item => {
                        let formattedTime = "No time set";

                        if (item.event_time) {
                            const timeObj = new Date(`1970-01-01T${item.event_time}`);
                            formattedTime = timeObj.toLocaleTimeString("en-US", {
                                hour: "numeric",
                                minute: "2-digit",
                                hour12: true
                            });
                        }

                        restockHTML += `
                            <div class="modal-event-card">
                                <div class="modal-event-time">${formattedTime}</div>
                                <div class="modal-event-details">
                                    <strong>${item.event_title}</strong>
                                    <small>${item.notes ? item.notes : "No notes available."}</small>
                                </div>
                            </div>
                        `;
                    });
                } else {
                    restockHTML = `
                        <div class="modal-empty">
                            No order/restock events for this day.
                        </div>
                    `;
                }

                /* =========================
                   OTHER EVENTS
                ========================= */
                if (data.other_events && data.other_events.length > 0) {
                    data.other_events.forEach(item => {
                        let formattedTime = "No time set";

                        if (item.event_time) {
                            const timeObj = new Date(`1970-01-01T${item.event_time}`);
                            formattedTime = timeObj.toLocaleTimeString("en-US", {
                                hour: "numeric",
                                minute: "2-digit",
                                hour12: true
                            });
                        }

                        otherHTML += `
                            <div class="modal-event-card">
                                <div class="modal-event-time">${formattedTime}</div>
                                <div class="modal-event-details">
                                    <strong>${item.event_title}</strong>
                                    <small>${item.notes ? item.notes : "No notes available."}</small>
                                </div>
                            </div>
                        `;
                    });
                } else {
                    otherHTML = `
                        <div class="modal-empty">
                            No other events for this day.
                        </div>
                    `;
                }

                /* =========================
                   RENDER MODAL BODY
                ========================= */
                modalBody.innerHTML = `
                    <div class="modal-section">
                        <div class="modal-section-title">
                            <span class="modal-dot appointments"></span>
                            <span>Appointments</span>
                        </div>
                        <div class="modal-event-list">
                            ${appointmentsHTML}
                        </div>
                    </div>

                    <div class="modal-section">
                        <div class="modal-section-title">
                            <span class="modal-dot delivery"></span>
                            <span>Delivery Day</span>
                        </div>
                        <div class="modal-event-list">
                            ${deliveryHTML}
                        </div>
                    </div>

                    <div class="modal-section">
                        <div class="modal-section-title">
                            <span class="modal-dot restock"></span>
                            <span>Order / Restock</span>
                        </div>
                        <div class="modal-event-list">
                            ${restockHTML}
                        </div>
                    </div>

                    <div class="modal-section">
                        <div class="modal-section-title">
                            <span class="modal-dot other"></span>
                            <span>Other Event</span>
                        </div>
                        <div class="modal-event-list">
                            ${otherHTML}
                        </div>
                    </div>
                `;

                if (calendarModal) {
                    calendarModal.classList.add("show");
                }
            })
            .catch(error => {
                console.error("Error loading day events:", error);

                if (modalBody) {
                    modalBody.innerHTML = `
                        <div class="modal-empty">
                            Failed to load events for this day.
                        </div>
                    `;
                }

                if (calendarModal) {
                    calendarModal.classList.add("show");
                }
            });
    }

    const calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: "dayGridMonth",
    headerToolbar: false,
    height: "auto",

    events: "../process/get_dashboard_calendar_events.php",

    dayMaxEvents: true,
    displayEventTime: false,

    eventDisplay: "block",

    eventContent: function(arg) {

        const eventType =
            arg.event.extendedProps.event_type || "";

        let markerClass = "appointment";

        if (eventType === "Delivery Day") {
            markerClass = "delivery";
        } else if (eventType === "Order/Restock") {
            markerClass = "restock";
        } else if (eventType === "Other Event") {
            markerClass = "other";
        }

        return {
            html: `
                <span
                    class="dashboard-calendar-marker ${markerClass}"
                    title="${arg.event.title}"
                ></span>
            `
        };
    },

    dateClick: function(info) {
        openCalendarModal(info.dateStr);
    },

    moreLinkClick: function(info) {
        const clickedDate =
            info.date.toISOString().split("T")[0];

        openCalendarModal(clickedDate);

        return "none";
    }
});

    calendar.render();

    function updateMonth() {
        currentMonth.textContent = calendar.view.title.toUpperCase();
    }

    updateMonth();

    document.getElementById("todayBtn").addEventListener("click", function () {
        calendar.today();
        updateMonth();
    });

    document.getElementById("prevBtn").addEventListener("click", function () {
        calendar.prev();
        updateMonth();
    });

    document.getElementById("nextBtn").addEventListener("click", function () {
        calendar.next();
        updateMonth();
    });

    /* =========================
       WEEKLY CHART
    ========================= */
    fetch("../process/get_weekly_appointments.php")
        .then(response => response.json())
        .then(data => {
            const ctx = document.getElementById("weeklyChart");

            new Chart(ctx, {
                type: "bar",
                data: {
                    labels: ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"],
                    datasets: [{
                        data: data,
                        backgroundColor: "#18AEF5",
                        borderRadius: 6,
                        barPercentage: 0.55,
                        categoryPercentage: 0.75
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        duration: 1200,
                        easing: "easeOutQuart"
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            },
                            grid: {
                                display: false
                            },
                            border: {
                                display: false
                            }
                        }
                    }
                }
            });
        })
        .catch(error => console.error("Error loading weekly appointments:", error));

    /* =========================
       OVERLAY SIDEBAR TOGGLE
    ========================= */
    const menuToggle = document.getElementById("menu-toggle");
    const container = document.querySelector(".container");
    const sidebarOverlay = document.getElementById("sidebarOverlay");

    if (menuToggle && container) {
        menuToggle.addEventListener("click", function () {
            container.classList.toggle("sidebar-open");
        });
    }

    if (sidebarOverlay && container) {
        sidebarOverlay.addEventListener("click", function () {
            container.classList.remove("sidebar-open");
        });
    }

    /* =========================
       CALENDAR MODAL CLOSE
    ========================= */
    if (closeCalendarModal && calendarModal) {
        closeCalendarModal.addEventListener("click", function () {
            calendarModal.classList.remove("show");
        });
    }

    if (calendarModal) {
        calendarModal.addEventListener("click", function (e) {
            if (e.target === calendarModal) {
                calendarModal.classList.remove("show");
            }
        });
    }

        /* =========================
       DASHBOARD STATISTICS
    ========================= */

    fetch("../process/get_dashboard_stats.php")
        .then(response => {
            if (!response.ok) {
                throw new Error("Failed to load dashboard statistics.");
            }

            return response.json();
        })
        .then(data => {

            if (!data.success || !data.stats) {
                throw new Error(
                    data.message || "Invalid dashboard statistics response."
                );
            }

            const stats = data.stats;


            /* =========================
               OUT OF STOCK
            ========================= */

            const outOfStockEl =
                document.getElementById("dashboardOutOfStock");

            if (outOfStockEl) {
                outOfStockEl.textContent =
                    Number(stats.out_of_stock || 0).toLocaleString();
            }


            /* =========================
               EXPIRED ITEMS
            ========================= */

            const expiredItemsEl =
                document.getElementById("dashboardExpiredItems");

            if (expiredItemsEl) {
                expiredItemsEl.textContent =
                    Number(stats.expired_items || 0).toLocaleString();
            }


            /* =========================
               REVENUE (MTD)
            ========================= */

            const revenueEl =
                document.getElementById("dashboardRevenue");

            if (revenueEl) {

                const revenue =
                    Number(stats.revenue_mtd || 0);

                revenueEl.textContent =
                    "₱" +
                    revenue.toLocaleString("en-PH", {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
            }


            /* =========================
               TOTAL STOCK
            ========================= */

            const totalStockEl =
                document.getElementById("dashboardTotalStock");

            if (totalStockEl) {
                totalStockEl.textContent =
                    Number(stats.total_stock || 0).toLocaleString();
            }


            /* =========================
               REGISTERED CLIENTS
            ========================= */

            const registeredClientsEl =
                document.getElementById(
                    "dashboardRegisteredClients"
                );

            if (registeredClientsEl) {
                registeredClientsEl.textContent =
                    Number(
                        stats.registered_clients || 0
                    ).toLocaleString();
            }


            /* =========================
               TOTAL PATIENTS
            ========================= */

            const totalPatientsEl =
                document.getElementById(
                    "dashboardTotalPatients"
                );

            if (totalPatientsEl) {
                totalPatientsEl.textContent =
                    Number(
                        stats.total_patients || 0
                    ).toLocaleString();
            }


            /* =========================
               NEW BOOKINGS
            ========================= */

            const newBookingsEl =
                document.getElementById(
                    "dashboardNewBookings"
                );

            if (newBookingsEl) {
                newBookingsEl.textContent =
                    Number(
                        stats.new_bookings || 0
                    ).toLocaleString();
            }

        })
        .catch(error => {
            console.error(
                "Error loading dashboard statistics:",
                error
            );
        });

        /* =========================
       UPCOMING EVENTS
    ========================= */

    const upcomingEventsList =
        document.getElementById("upcomingEventsList");

    if (upcomingEventsList) {

        fetch("../process/get_dashboard_calendar_events.php")
            .then(response => {

                if (!response.ok) {
                    throw new Error(
                        "Failed to load upcoming events."
                    );
                }

                return response.json();
            })

            .then(events => {

                if (!Array.isArray(events)) {
                    throw new Error(
                        "Invalid upcoming events response."
                    );
                }

                const today = new Date();

                today.setHours(0, 0, 0, 0);


                /* =========================
                   FILTER FUTURE EVENTS
                ========================= */

                const upcomingEvents = events
                    .filter(event => {

                        if (!event.start) {
                            return false;
                        }

                        const datePart =
                            event.start.substring(0, 10);

                        const eventDate =
                            new Date(
                                datePart + "T00:00:00"
                            );

                        return eventDate >= today;
                    })


                    /* =========================
                       SORT NEAREST FIRST
                    ========================= */

                    .sort((a, b) => {

                        return new Date(a.start) -
                               new Date(b.start);

                    })


                    /* =========================
                       SHOW ONLY NEXT 3
                    ========================= */

                    .slice(0, 3);


                /* =========================
                   NO UPCOMING EVENTS
                ========================= */

                if (upcomingEvents.length === 0) {

                    upcomingEventsList.innerHTML = `
                        <div class="upcoming-item">
                            <div class="event-date">
                                No upcoming events
                            </div>

                            <div class="event-title">
                                There are no upcoming confirmed
                                appointments or events.
                            </div>
                        </div>
                    `;

                    return;
                }


                /* =========================
                   RENDER UPCOMING EVENTS
                ========================= */

                upcomingEventsList.innerHTML =
                    upcomingEvents.map(event => {

                        const datePart =
                            event.start.substring(0, 10);

                        const dateObj =
                            new Date(
                                datePart + "T00:00:00"
                            );

                        const formattedDate =
                            dateObj.toLocaleDateString(
                                "en-US",
                                {
                                    month: "short",
                                    day: "2-digit",
                                    year: "numeric",
                                    weekday: "short"
                                }
                            );

                        return `
                            <div class="upcoming-item">

                                <div class="event-date">
                                    ${formattedDate}
                                </div>

                                <div class="event-title">
                                    ${event.title}
                                </div>

                            </div>
                        `;

                    }).join("");

            })

            .catch(error => {

                console.error(
                    "Error loading upcoming events:",
                    error
                );

                upcomingEventsList.innerHTML = `
                    <div class="upcoming-item">

                        <div class="event-date">
                            Unable to load events
                        </div>

                        <div class="event-title">
                            Please refresh the Dashboard.
                        </div>

                    </div>
                `;
            });
    }    

});