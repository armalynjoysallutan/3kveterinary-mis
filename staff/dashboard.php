<?php
session_start();

if (
    !isset($_SESSION['account_id']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'Staff'
) {
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

    <?php include 'partials/sidebar.php'; ?>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

    <main class="content">

  <div class="topbar-wrapper">

    <div class="topbar">

        <div class="topbar-left">

            <button id="menu-toggle" class="menu-toggle">
                <i class="fa-solid fa-bars"></i>
            </button>

            <h2>Dashboard</h2>

        </div>

        <div class="admin-info">
            Welcome,
            <strong><?php echo htmlspecialchars($_SESSION['username'] ?? 'Staff'); ?></strong>
        </div>

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
                            View confirmed appointments and important events.
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

                    <div 
                       class="upcoming-events-list"
                       id="upcomingEventsList"

                    >
                       <div class="upcoming-item">
                           <div class="event-date">Loading...</div>
                           <div class="event-title">Loading upcoming events...</div>
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

                        <h2 id="dashboardOutOfStock">0</h2>

                        <small class="green-text">
                            Expired batches with stock
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

                        <h2 id="dashboardExpiredItems">0</h2>

                        <small class="red-text">

                            Items currently unavailable
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

                        <h4>Total Sales</h4>

                        <h2 id="dashboardRevenue">₱0.00</h2>

                        <small class="green-text">
                            Paid billing this month
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

                        <h2 id="dashboardTotalStock">0</h2>

                        <small class="green-text">
                            Current stock quantity
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

                            <h4>Registered Customers</h4>

                            <h2 id="dashboardRegisteredClients">0</h2>

                            <small class="green-text">
                                Active registered customers
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

                            <h2 id="dashboardTotalPatients">0</h2>

                            <small class="green-text">
                                Pets of active clients
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

                        <h2 id="dashboardNewBookings">0</h2>

                    </div>

                    <a href="appointments.php" class="view-link">View</a>

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
                <h3 id="calendarModalDate"></h3>

                <p id="modalDateSubtitle">
                    All reminders and events for this day
                </p>
            </div>

            <button
                type="button"
                class="calendar-modal-close"
                id="closeCalendarModal"
            >
                <i class="fa-solid fa-xmark"></i>
            </button>

        </div>

        <div
            class="calendar-modal-body"
            id="calendarModalBody"
        >

            <div class="modal-loading">
                Loading events...
            </div>

        </div>

    </div>

    <!-- ADD CALENDAR EVENT MODAL -->
    <div class="calendar-event-form-modal" id="calendarEventFormModal">

        <div class="calendar-event-form-content">

            <div class="calendar-event-form-header">

                <div> 
                    <h3 id="calendarEventFormTitle">Add Event</h3>
                    <p id="calendarEventFormDate"></p>
                </div>

                <button
                    type="button"
                    class="calendar-modal-close"
                    id= "closeCalendarEventForm"
                >
                    <i class="fa-solid fa-xmark"></i>    
                </button>
            </div>

            <form id="calendarEventForm">
               <input
                   type="hidden"
                   id="calendarEventType"
                   name="event_type"

                >

                <input
                    type="hidden"
                    id="calendarEventDate"
                    name="event_date"   
                >
                
                <div class="calendar-form-group">
                    <label for="calendarEventTitle">
                        Event Title
                    </label>

                    <input
                        type="text"
                        id="calendarEventTitle"
                        name="event_title"
                        maxlength="255"
                        placeholder="Enter event title"
                        required
                    >  

                </div>

                <div class="calendar-form-group">
                    <label for="calendarEventTime">
                        Time
                    </label>

                    <input
                        type="time"
                        id="calendarEventTime"
                        name="event_time"
                    >   
                        
                </div>

                <div class="calendar-form-group">
                    <label for="calendarEventNotes">
                        Notes
                    </label>

                    <textarea
                        id="calendarEventNotes"
                        name="notes"
                        rows="4"
                        placeholder="Add notes (optional)"
                    ></textarea>    

                </div>

                <div class="calendar-form-actions">
                    <button
                        type="button"
                        class="calendar-form-cancel"
                        id="cancelCalendarEvent"
                    >
                        Cancel
                    </button>
                    
                    <button
                        type="submit"
                        class="calendar-form-save"
                        id="saveCalendarEvent"
                    >
                        Save Event
                    </button>    
            
                </div>

            </form>

        </div>


</div>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="../assets/js/dashboard.js"></script>

</body>
</html>