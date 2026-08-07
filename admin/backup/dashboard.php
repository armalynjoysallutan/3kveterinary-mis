<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../auth/login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Dashboard</title>

<link rel="stylesheet" href="../assets/css/dashboard.css">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">

</head>

<body>

<div class="container">

    <aside class="sidebar">

    <div class="logo">

        <img src="../assets/images/logo.png" alt="Logo">

    </div>

    <ul class="menu">

        <li class="active">
            <a href="dashboard.php">
                <i class="fa-solid fa-table-columns"></i>
                <span>Dashboard</span>
            </a>
        </li>

        <li>
            <a href="appointments.php">
                <i class="fa-regular fa-calendar"></i>
                <span>Appointments</span>
            </a>
        </li>

        <li>
            <a href="customer_records.php">
                <i class="fa-regular fa-clipboard"></i>
                <span>Customer Records</span>
            </a>
        </li>

        <li>
            <a href="billing.php">
                <i class="fa-solid fa-file-invoice-dollar"></i>
                <span>Billing</span>
            </a>
        </li>

        <li>
            <a href="inventory.php">
                <i class="fa-solid fa-box"></i>
                <span>Inventory</span>
            </a>
        </li>

        <li>
            <a href="vaccination_certificates.php">
                <i class="fa-solid fa-shield-dog"></i>
                <span>Vaccination Certificates</span>
            </a>
        </li>

        <li>
            <a href="reports.php">
                <i class="fa-regular fa-file-lines"></i>
                <span>Reports</span>
            </a>
        </li>

        <li>
            <a href="archived.php">
                <i class="fa-solid fa-box-archive"></i>
                <span>Archived</span>
            </a>
        </li>

        <li>
            <a href="settings.php">
                <i class="fa-solid fa-gear"></i>
                <span>Settings</span>
            </a>
        </li>

    </ul>

    <div class="sidebar-footer">

        <div class="user">

            <img src="../assets/images/default-user.png" alt="User">

            <div>

                <strong><?php echo $_SESSION["admin_username"]; ?></strong>

                <small>Administrator</small>

            </div>

        </div>

        <a href="../process/logout.php">

            <i class="fa-solid fa-right-from-bracket"></i>

        </a>

    </div>

</aside>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

    <main class="content">

    <div class="topbar"> 

        <div class="topbar-left">
            <button id="menu-toggle" class="menu-toggle">
                <i class="fa-solid fa-bars"></i>
            </button>
            <h2>Dashboard</h2>
        </div>

        <div class="admin-info">

            Welcome,
            <strong><?php echo $_SESSION['admin_username']; ?></strong>

        </div>

    </div>

    <div class="dashboard-content">

    <div class="dashboard-grid">

        <!-- LEFT -->
        <div class="left-section">

            <div class="card calendar-card">

                <div class="card-header">

                    <div>
                        <h3>📅 Event Calendar</h3>

                        <p class="calendar-subtitle">
                            View delivery days and important events.
                        </p>

                        <h4 id="currentMonth"></h4>

                    </div>

                    <div class="calendar-buttons">
                        <button id="todayBtn">Today</button>
                        <button id="prevBtn">Previous</button>
                        <button id="nextBtn">Next</button>
                    </div>

                </div>

                <div id="calendar"></div>

                <div class="calendar-legend">

                    <div class="legend-item">
                        <span class="legend-color delivery"></span>
                        <span>Appointments</span>
                    </div>

                    <div class="legend-item">
                        <span class="legend-color restock"></span>
                        <span>Delivery Day</span>
                    </div>

                    <div class="legend-item">
                        <span class="legend-color other"></span>
                        <span>Order/Restock</span>
                    </div>
                    
                    <div class="legend-item">
                         <span class="legend-color regular"></span>
                         <span>Other Event</span>
                    </div>

                </div>

                <div class="upcoming-events-card">

                    <h4>Upcoming Events</h4>

                    <div class="upcoming-events-list">

                        <div class="upcoming-item">
                            <div class="event-date">Jul 03, 2026 (Fri)</div>
                            <div class="event-title">Bantay - Vaccination</div>
                        </div>

                        <div class="upcoming-item">
                            <div class="event-date">Jul 04, 2026 (Sat)</div>
                            <div class="event-title">Snow - Consultation</div>
                        </div>

                        <div class="upcoming-item">
                            <div class="event-date">Jul 05, 2026 (Sun)</div>
                            <div class="event-title">Tiger - Checkup</div>
                        </div>

                    </div>

                </div>

            </div>

            <div class="card weekly-card">

                <h3>Weekly Appointments</h3>

                <canvas id="weeklyChart"></canvas>

            </div>

        </div>

        <!-- RIGHT -->

        <div class="stats">

            <div class="card stat-card">

                <div class="stat-top">

                    <div>

                        <h4>Out of Stock</h4>

                        <h2>20</h2>

                        <small class="green-text">
                            +10 new this week
                        </small>

                    </div>

                    <div class="stat-icon danger">

                        <i class="fa-solid fa-circle-exclamation"></i>

                    </div>
                </div>
            </div>

            <div class="card stat-card">

                <div class="stat-top">

                    <div>

                        <h4>Expired Items</h4>

                        <h2>5</h2>

                        <small class="red-text">

                            +3 new this week
                        </small>

                    </div>

                    <div class="stat-icon danger">

                        <i class="fa-regular fa-triangle-exclamation"></i>
                    
                    </div>
                </div>
            </div>

            <div class="card stat-card">

                <div class="stat-top">

                    <div>

                        <h4>Revenue (MTD)</h4>

                        <h2>₱7,200</h2>

                        <small class="green-text">
                            +18% from last month
                        </small>
                    </div>
                    
                    <div class="stat-icon warning">

                        <i class="fa-solid fa-peso-sign"></i>
                    
                    </div>
                </div>
            </div>

            <div class="card stat-card">

                <div class="stat-top">

                    <div>

                        <h4>Total Stock</h4>

                        <h2>620</h2>

                        <small class="green-text">
                            +50 new this week
                        </small>
                    </div>

                    <div class="stat-icon success">

                        <i class="fa-solid fa-box"></i>
                    </div>
                </div>
            </div>

            <div class="double-card">

                <div class="card stat-card">

                    <div class="stat-top">

                        <div>

                            <h4>Registered Clients</h4>

                            <h2>198</h2>

                            <small class="green-text">
                                +5 new this month
                            </small>

                        </div>
                        
                        <div class="stat-icon primary">

                            <i class="fa-solid fa-users"></i>

                        </div>
                    </div>
                </div>

                <div class="card stat-card">

                    <div class="stat-top">

                        <div>

                            <h4>Total Patients</h4>

                            <h2>342</h2>

                            <small class="green-text">
                                +8 new this week
                            </small>

                        </div>

                        <div class="stat-icon purple">

                            <i class="fa-solid fa-paw"></i>

                        </div>
                    </div>
                </div>

            </div>

            <div class="card stat-card">

                <div class="stat-top">

                    <div>

                        <h4>New Bookings</h4>

                        <h2>5</h2>

                    </div>

                    <a href="#" class="view-link">View</a>

                </div>
            </div>

        </div>

    </div>

</div>

    </main>

</div>

<!-- CALENDAR DAY MODAL -->
<div class="calendar-modal" id="calendarModal">
    <div class="calendar-modal-content">

        <div class="calendar-modal-header">
            <div>
                <h3 id="calendarModalDate">July 2, 2026</h3>
                <p id="modalDateSubtitle">All reminders and events for this day</p>
            </div>

            <button class="calendar-modal-close" id="closeCalendarModal">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="calendar-modal-body" id="calendarModalBody">

            <!-- APPOINTMENTS -->
            <div class="modal-section">
                <div class="modal-section-title">
                    <span class="modal-dot appointments"></span>
                    <span>Appointments</span>
                </div>

                <div class="modal-event-list">
                    <div class="modal-event-card">
                        <div class="modal-event-time">9:00 AM</div>
                        <div class="modal-event-details">
                            <strong>Max - Vaccination</strong>
                            <small>Owner: Juan Dela Cruz</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- DELIVERY DAY -->
            <div class="modal-section">
                <div class="modal-section-title">
                    <span class="modal-dot delivery"></span>
                    <span>Delivery Day</span>
                </div>

                <div class="modal-event-list">
                    <div class="modal-event-card">
                        <div class="modal-event-time">10:00 AM</div>
                        <div class="modal-event-details">
                            <strong>Pet Essentials Delivery</strong>
                            <small>Supplier delivery scheduled</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ORDER / RESTOCK -->
            <div class="modal-section">
                <div class="modal-section-title">
                    <span class="modal-dot restock"></span>
                    <span>Order / Restock</span>
                </div>

                <div class="modal-event-list">
                    <div class="modal-event-card">
                        <div class="modal-event-time">2:00 PM</div>
                        <div class="modal-event-details">
                            <strong>Restock Deworming Tablets</strong>
                            <small>Prepare supplier order</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- OTHER EVENT -->
            <div class="modal-section">
                <div class="modal-section-title">
                    <span class="modal-dot other"></span>
                    <span>Other Event</span>
                </div>

                <div class="modal-event-list">
                    <div class="modal-event-card">
                        <div class="modal-event-time">4:00 PM</div>
                        <div class="modal-event-details">
                            <strong>Clinic Staff Meeting</strong>
                            <small>Monthly operations check-in</small>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="../assets/js/dashboard.js"></script>

</body>
</html>