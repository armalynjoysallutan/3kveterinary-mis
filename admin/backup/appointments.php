<?php
session_start();

if (!isset($_SESSION["admin_username"])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once __DIR__ . '/../config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments | Veterinary MIS</title>

    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/appointments.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>

</head>
<body>

<div class="appointment-layout" id="appointmentLayout">

    <!-- SIDEBAR -->
    <aside class="sidebar appointment-sidebar" id="sidebar">
        <div class="logo">
            <img src="../assets/images/logo.png" alt="Logo">
        </div>

        <ul class="menu">
            <li>
                <a href="dashboard.php" class="nav-link">
                    <i class="fa-solid fa-table-columns nav-icon"></i>
                    <span class="menu-text">Dashboard</span>
                </a>
            </li>

            <li class="active">
                <a href="appointments.php" class="nav-link">
                    <i class="fa-regular fa-calendar nav-icon"></i>
                    <span class="menu-text">Appointments</span>
                </a>
            </li>

            <li>
                <a href="customer_records.php" class="nav-link">
                    <i class="fa-regular fa-address-book nav-icon"></i>
                    <span class="menu-text">Customer Records</span>
                </a>
            </li>

            <li>
                <a href="billing.php" class="nav-link">
                    <i class="fa-solid fa-file-invoice-dollar nav-icon"></i>
                    <span class="menu-text">Billing</span>
                </a>
            </li>

            <li>
                <a href="inventory.php" class="nav-link">
                    <i class="fa-solid fa-box nav-icon"></i>
                    <span class="menu-text">Inventory</span>
                </a>
            </li>

            <li>
                <a href="vaccination_certificates.php" class="nav-link">
                    <i class="fa-solid fa-shield-dog nav-icon"></i>
                    <span class="menu-text">Vaccination Certificates</span>
                </a>
            </li>

            <li>
                <a href="reports.php" class="nav-link">
                    <i class="fa-regular fa-file-lines nav-icon"></i>
                    <span class="menu-text">Reports</span>
                </a>
            </li>
        </ul>

        <div class="sidebar-footer">
            <div class="user">
                <img src="../assets/images/admin-avatar.png" alt="Admin">
                <div>
                    <strong>admin</strong>
                    <small>Administrator</small>
                </div>
            </div>
            <a href="../process/logout.php"><i class="fa-solid fa-right-from-bracket"></i></a>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="content appointment-content" id="mainContent">

        <!-- TOPBAR -->
        <div class="topbar">
            <div class="topbar-left">
                <button class="menu-toggle" id="menuToggle" type="button">
                    <i class="fa-solid fa-bars"></i>
                </button>

                <div class="page-heading">
                    
                    <h1>Appointments</h1>

                </div>
            </div>
        </div>

        <div class="appointments-content">

            <!-- SEARCH + ACTIONS -->
            <div class="appointment-toolbar">
                <div class="search-box">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" placeholder="Search by reference, pet, or owner name...">
                </div>

                <div class="toolbar-actions">
                    <button class="btn-primary">
                        <i class="fa-solid fa-plus"></i> Add Appointment
                    </button>
                    <a href="archived.php" class="btn-secondary">Archived</a>
                </div>
            </div>

            <!-- WEEKLY CALENDAR -->
            <div class="calendar-card">
                <div class="calendar-card-header">
                    <div>
                        <h2>Weekly Calendar</h2>
                        <p id="appointmentCurrentWeek">JULY 2026</p>
                    </div>

                    <div class="calendar-nav">
                        <button id="appointmentPrev">Previous</button>
                        <button id="appointmentToday">Today</button>
                        <button id="appointmentNext">Next</button>
                    </div>
                </div>

                <div id="appointmentCalendar"></div>
            </div>

            <!-- APPOINTMENT LIST -->
            <div class="card appointment-list-card">
                <div class="section-header">
                    <h3>Appointment Lists</h3>
                </div>

                <div class="table-wrapper">
                    <table class="appointment-table">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Date & Time</th>
                                <th>Pet</th>
                                <th>Owner</th>
                                <th>Type / Service</th>
                                <th>Next Visit</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody id="appointmentTableBody">
                            <tr>
                                <td>APT-0001</td>
                                <td>
                                    <div class="date-main">Jul 8, 2026</div>
                                    <small>9:00 AM</small>
                                </td>
                                <td>
                                    <strong>Max</strong><br>
                                    <small>Dog</small>
                                </td>
                                <td>Juan Dela Cruz</td>
                                <td>Vaccination</td>
                                <td>Jul 15, 2026</td>
                                <td><span class="status-badge pending">Pending</span></td>
                                <td>
                                    <div class="action-group">
                                        <button class="link-btn">Edit</button>
                                        <button class="link-btn">Bill</button>
                                        <button class="action-menu-btn">
                                            <i class="fa-solid fa-ellipsis"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td>APT-0002</td>
                                <td>
                                    <div class="date-main">Jul 8, 2026</div>
                                    <small>10:30 AM</small>
                                </td>
                                <td>
                                    <strong>Luna</strong><br>
                                    <small>Cat</small>
                                </td>
                                <td>Maria Santos</td>
                                <td>Consultation</td>
                                <td>—</td>
                                <td><span class="status-badge confirmed">Confirmed</span></td>
                                <td>
                                    <div class="action-group">
                                        <button class="link-btn">Edit</button>
                                        <button class="link-btn">Bill</button>
                                        <button class="action-menu-btn">
                                            <i class="fa-solid fa-ellipsis"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td>APT-0003</td>
                                <td>
                                    <div class="date-main">Jul 9, 2026</div>
                                    <small>1:00 PM</small>
                                </td>
                                <td>
                                    <strong>Bantay</strong><br>
                                    <small>Dog</small>
                                </td>
                                <td>Paolo Ramos</td>
                                <td>Deworming</td>
                                <td>Jul 23, 2026</td>
                                <td><span class="status-badge completed">Completed</span></td>
                                <td>
                                    <div class="action-group">
                                        <button class="link-btn">Edit</button>
                                        <button class="link-btn">Bill</button>
                                        <button class="action-menu-btn">
                                            <i class="fa-solid fa-ellipsis"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>
</div>

<!-- ADD APPOINTMENT MODAL PLACEHOLDER -->
<div class="modal-overlay" id="addAppointmentModal">
    <div class="modal-box large-modal">
        <div class="modal-header">
            <h3>Create Appointment</h3>
            <button class="modal-close" id="closeAddAppointment">&times;</button>
        </div>

        <div class="modal-body">
            <p class="modal-note">
                Appointment form will go here next.
            </p>
        </div>
    </div>
</div>

<!-- JS -->
<script src="../assets/js/appointments.js"></script>

</body>
</html>