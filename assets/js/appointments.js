document.addEventListener("DOMContentLoaded", function () {

    console.log("Appointments JS Loaded");

    const calendarEl = document.getElementById("calendar");

    if (!calendarEl) return;

    const calendar = new FullCalendar.Calendar(calendarEl, {

    initialView: "dayGridWeek",

    headerToolbar: false,

    height: "auto",

    events: "../process/get_calendar_appointments.php"

});

    calendar.render();

    function updateMonth() {

    const currentMonth = document.getElementById("currentMonth");

    currentMonth.textContent = calendar.view.title.toUpperCase();

}

updateMonth();

    // ===========================
    // BUTTONS
    // ===========================

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

});