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

    <link rel="stylesheet" href="../assets/css/layout.css">
    <link rel="stylesheet" href="../assets/css/appointments.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>

</head>
<body>

<div class="container">

    <!-- SIDEBAR -->
    <?php include 'partials/sidebar.php'; ?>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- MAIN CONTENT -->
    <main class="content" id="mainContent">

        <?php
        $pageTitle = "Appointments";
        $showAdminInfo = false;
        include "partials/topbar.php";
        ?>

       <div class="page-content">
         <div class="appointments-content"> 

            <!-- SEARCH + ACTIONS -->
            <div class="toolbar-card">

                <div class="toolbar-left">

                    <div class="search-box">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" placeholder="Search appointment...">
                    </div>

                </div>

                <div class="toolbar-right">

                    <button class="add-btn">
                        <i class="fa-solid fa-plus"></i>
                        Add Appointment
                    </button>

                    <button class="archive-btn">
                        <i class="fa-solid fa-box-archive"></i>
                        Archived
                    </button>

                </div>

            </div>

            <!-- WEEKLY CALENDAR -->
            <div class="calendar-card">

                <div class="calendar-header">

                    <div class="calendar-title">
                        <h3>📅 Weekly Calendar</h3>
                    </div>

                    <div class="calendar-controls">

                        <h4 id="currentMonth"></h4>

                        <div class="calendar-buttons">
                            <button id="prevBtn">◀ Previous</button>
                            <button id="todayBtn">Today</button>
                            <button id="nextBtn">Next ▶</button>
                        </div>

                    </div>

                </div>

                <div id="calendar"></div>

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
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>

<script src="../assets/js/layout.js"></script>
<script src="../assets/js/appointments.js"></script>

</body>
</html>