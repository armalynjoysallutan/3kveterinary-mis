document.addEventListener("DOMContentLoaded", function () {

    console.log("Appointments JS Loaded");

    // Keep Appointment Details Modal outside other modals
     const detailsModalRoot =
         document.getElementById("appointmentDetailsModal");

    if(detailsModalRoot){
         document.body.appendChild(detailsModalRoot);
    }
// ===========================
// LOAD APPOINTMENT LIST
// WITH SEARCH SUPPORT
// ===========================

function loadAppointmentList(){

    const tableBody =
        document.getElementById("appointmentTableBody");

    if(!tableBody) return;


    fetch("../process/get_appointments.php")

        .then(response => response.json())

        .then(result => {

            console.log(
                "Appointment List:",
                result
            );


            if(!result.success){

                console.error(
                    result.message
                );

                return;

            }


            // ==================================
            // SAVE ALL APPOINTMENTS
            // ==================================

            window.appointmentsData =
                result.data;


            // ==================================
            // GET SEARCH VALUE
            // ==================================

            const searchInput =
                document.getElementById(
                    "appointmentSearch"
                );


            const searchValue =
                searchInput
                    ? searchInput.value
                        .trim()
                        .toLowerCase()
                    : "";


            // ==================================
            // FILTER APPOINTMENTS
            // ==================================

            const filteredAppointments =
                result.data.filter(
                    function(appointment){

                        if(searchValue === ""){
                            return true;
                        }


                        const appointmentId =
                            String(
                                appointment.appointment_id || ""
                            ).toLowerCase();


                        const owner =
                            String(
                                appointment.owner_name || ""
                            ).toLowerCase();


                        const pet =
                            String(
                                appointment.pet_name || ""
                            ).toLowerCase();


                        const service =
                            String(
                                appointment.service || ""
                            ).toLowerCase();


                        const status =
                            String(
                                appointment.status || ""
                            ).toLowerCase();


                        return (
                            appointmentId.includes(searchValue) ||
                            owner.includes(searchValue) ||
                            pet.includes(searchValue) ||
                            service.includes(searchValue) ||
                            status.includes(searchValue)
                        );

                    }
                );


            // ==================================
            // CLEAR TABLE
            // ==================================

            tableBody.innerHTML = "";


            // ==================================
            // NO RESULTS
            // ==================================

            if(filteredAppointments.length === 0){

                tableBody.innerHTML = `
                    <tr>
                        <td
                            colspan="8"
                            style="text-align:center;"
                        >
                            No appointments found.
                        </td>
                    </tr>
                `;

                return;

            }


            // ==================================
            // DISPLAY APPOINTMENTS
            // ==================================

            filteredAppointments.forEach(
                function(appointment){

                    const date =
                        new Date(
                            appointment.appointment_date +
                            "T" +
                            appointment.appointment_time
                        );


                    const formattedDate =
                        date.toLocaleDateString(
                            "en-US",
                            {
                                month: "short",
                                day: "numeric",
                                year: "numeric"
                            }
                        );


                    const formattedTime =
                        date.toLocaleTimeString(
                            "en-US",
                            {
                                hour: "numeric",
                                minute: "2-digit"
                            }
                        );


                    const reference =
                        "APT-" +
                        String(
                            appointment.appointment_id
                        ).padStart(4, "0");


                    const row =
                        document.createElement("tr");


                    // ==================================
                    // ACTIONS
                    // ==================================

                    let actionsHTML = "";


                    if(
                        appointment.status ===
                        "Pending"
                    ){

                        actionsHTML = `
                            <div class="action-group">

                                <button
                                    class="link-btn confirm-btn"
                                    data-id="${appointment.appointment_id}">
                                    Confirm
                                </button>

                                <div class="action-wrapper">

                                    <button
                                        class="action-menu-btn"
                                        data-id="${appointment.appointment_id}">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>

                                    <div class="action-dropdown">

                                        <button
                                            class="view-details-btn"
                                            data-id="${appointment.appointment_id}">
                                            View Details
                                        </button>

                                        <button
                                            class="reschedule-btn"
                                            data-id="${appointment.appointment_id}">
                                            Reschedule
                                        </button>

                                        <button
                                            class="send-confirmation-btn"
                                            data-id="${appointment.appointment_id}">
                                            Send Email Confirmation
                                        </button>

                                        <button
                                            class="cancel-appointment-btn"
                                            data-id="${appointment.appointment_id}">
                                            Cancel
                                        </button>

                                    </div>

                                </div>

                            </div>
                        `;

                    }


                    else if(
                        appointment.status ===
                        "Confirmed"
                    ){

                        actionsHTML = `
                            <div class="action-wrapper">

                                <button
                                    class="action-menu-btn"
                                    data-id="${appointment.appointment_id}">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>

                                <div class="action-dropdown">

                                    <button
                                        class="view-details-btn"
                                        data-id="${appointment.appointment_id}">
                                        View Details
                                    </button>

                                    <button
                                        class="arrived-btn"
                                        data-id="${appointment.appointment_id}">
                                        Mark as Arrived
                                    </button>

                                    <button
                                        class="cancel-appointment-btn"
                                        data-id="${appointment.appointment_id}">
                                        Cancel
                                    </button>

                                </div>

                            </div>
                        `;

                    }


                    else if(
                        appointment.status ===
                        "Arrived"
                    ){

                        actionsHTML = `
                            <div class="action-wrapper">

                                <button
                                    class="action-menu-btn"
                                    data-id="${appointment.appointment_id}">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>

                                <div class="action-dropdown">

                                    <button
                                        class="view-details-btn"
                                        data-id="${appointment.appointment_id}">
                                        View Details
                                    </button>

                                    <button
                                        class="complete-btn"
                                        data-id="${appointment.appointment_id}">
                                        Complete
                                    </button>

                                </div>

                            </div>
                        `;

                    }


                    else if(
                        appointment.status ===
                        "Completed"
                    ){

                        actionsHTML = `
                            <button
                                class="link-btn bill-btn"
                                data-id="${appointment.appointment_id}">
                                Bill
                            </button>
                        `;

                    }


                    else if(
                        appointment.status ===
                        "Cancelled"
                    ){

                        actionsHTML = `
                            <button
                                class="link-btn view-details-btn"
                                data-id="${appointment.appointment_id}">
                                View Details
                            </button>
                        `;

                    }


                    // ==================================
                    // ROW CONTENT
                    // ==================================

                    row.innerHTML = `

                        <td>
                            ${reference}
                        </td>

                        <td>
                            <div class="date-main">
                                ${formattedDate}
                            </div>

                            <small>
                                ${formattedTime}
                            </small>
                        </td>

                        <td>
                            <strong>
                                ${appointment.pet_name}
                            </strong>

                            <br>

                            <small>
                                ${appointment.species}
                            </small>
                        </td>

                        <td>
                            ${appointment.owner_name}
                        </td>

                        <td>
                            ${appointment.service}
                        </td>

                        <td>
                            <span
                                class="status-badge ${appointment.status.toLowerCase()}">
                                ${appointment.status}
                            </span>
                        </td>

                        <td>
                            ${actionsHTML}
                        </td>

                    `;


                    tableBody.appendChild(row);

                }
            );

        })

        .catch(error => {

            console.error(
                "Error loading appointment list:",
                error
            );

        });

}
loadAppointmentList();

// ===========================
// APPOINTMENT SEARCH
// ===========================

const appointmentSearch =
    document.getElementById(
        "appointmentSearch"
    );


if(appointmentSearch){

    appointmentSearch.addEventListener(
        "input",
        function(){

            loadAppointmentList();

        }
    );

}


    const calendarEl = document.getElementById("calendar");

    if (!calendarEl) return;

    const calendar = new FullCalendar.Calendar(calendarEl, {

    initialView: "dayGridWeek",

    headerToolbar: false,

    height: "400px",

    dayMaxEvents: 4,

    moreLinkText: function(num) {
        return "+" + num + " more";
    },

    events: "../process/get_calendar_appointments.php",

    eventClick: function(info) {

        const appointmentId = info.event.id;

        console.log(
            "Calendar appointment clicked:",
            appointmentId
        );

        // Create temporary View Details button
        // so we can reuse the existing View Details logic

        const viewButton =
            document.createElement("button");

        viewButton.className =
            "view-details-btn";

        viewButton.dataset.id =
            appointmentId;

        viewButton.style.display = "none";

        document.body.appendChild(viewButton);

        // Trigger existing View Details logic
        viewButton.click();

        // Remove temporary button
        viewButton.remove();

    }

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

    document.getElementById("nextBtn").addEventListener("click", function () {

    calendar.next();
    updateMonth();

});


// ===========================
// ADD APPOINTMENT MODAL
// ===========================

const openModal = document.getElementById("openAppointmentModal");
const modal = document.getElementById("addAppointmentModal");
const closeModal = document.getElementById("closeAddAppointment");

if(openModal && modal){

    openModal.addEventListener("click", function(){

        modal.classList.add("show");

    });

}

if(closeModal){

    closeModal.addEventListener("click", function(){

        modal.classList.remove("show");

    });

}

modal.addEventListener("click", function(e){

    if(e.target === modal){

        modal.classList.remove("show");

    }

});

// ===========================
// CANCEL APPOINTMENT MODAL
// ===========================

const cancelAppointmentModal =
    document.getElementById(
        "cancelAppointmentModal"
    );

const closeCancelAppointment =
    document.getElementById(
        "closeCancelAppointment"
    );

const keepAppointment =
    document.getElementById(
        "keepAppointment"
    );

const confirmCancelAppointment =
    document.getElementById(
        "confirmCancelAppointment"
    );

const cancellationReason =
    document.getElementById(
        "cancellationReason"
    );

const otherCancellationContainer =
    document.getElementById(
        "otherCancellationContainer"
    );

const otherCancellationReason =
    document.getElementById(
        "otherCancellationReason"
    );


let selectedCancellationAppointmentId = null;



// ===========================
// OPEN CANCEL MODAL
// ===========================

document.addEventListener("click", function(e){

    const cancelButton =
        e.target.closest(
            ".cancel-appointment-btn"
        );

    if(!cancelButton){
        return;
    }


    selectedCancellationAppointmentId =
        cancelButton.dataset.id;


    cancellationReason.value = "";

    otherCancellationReason.value = "";

    otherCancellationContainer.style.display =
        "none";


    cancelAppointmentModal.classList.add(
        "show"
    );

});



// ===========================
// SHOW OTHER REASON
// ===========================

if(cancellationReason){

    cancellationReason.addEventListener(
        "change",
        function(){

            if(this.value === "Other"){

                otherCancellationContainer.style.display =
                    "block";

            }else{

                otherCancellationContainer.style.display =
                    "none";

                otherCancellationReason.value =
                    "";

            }

        }
    );

}



// ===========================
// CLOSE CANCEL MODAL
// ===========================

if(closeCancelAppointment){

    closeCancelAppointment.addEventListener(
        "click",
        function(){

            cancelAppointmentModal.classList.remove(
                "show"
            );

        }
    );

}



// ===========================
// KEEP APPOINTMENT
// ===========================

if(keepAppointment){

    keepAppointment.addEventListener(
        "click",
        function(){

            cancelAppointmentModal.classList.remove(
                "show"
            );

        }
    );

}



// ===========================
// CONFIRM CANCELLATION
// ===========================

if(confirmCancelAppointment){

    confirmCancelAppointment.addEventListener(
        "click",
        function(){

            let finalReason =
                cancellationReason.value;


            if(finalReason === ""){

                alert(
                    "Please select a reason for cancellation."
                );

                return;

            }


            if(
                finalReason === "Other"
            ){

                finalReason =
                    otherCancellationReason.value.trim();


                if(finalReason === ""){

                    alert(
                        "Please specify the cancellation reason."
                    );

                    return;

                }

            }


            if(
                !selectedCancellationAppointmentId
            ){

                alert(
                    "Invalid appointment."
                );

                return;

            }


            confirmCancelAppointment.disabled =
                true;


            fetch(
                "../process/cancel_appointment.php",
                {

                    method: "POST",

                    body: new URLSearchParams({

                        appointmentId:
                            selectedCancellationAppointmentId,

                        cancellationReason:
                            finalReason

                    })

                }

            )

            .then(response =>
                response.json()
            )

            .then(result => {

                console.log(
                    "Cancel Result:",
                    result
                );


                if(result.success){

                    cancelAppointmentModal.classList.remove(
                        "show"
                    );


                    selectedCancellationAppointmentId =
                        null;


                    loadAppointmentList();


                    alert(
                        "Appointment cancelled successfully."
                    );


                }else{

                    alert(
                        result.message ||
                        "Failed to cancel appointment."
                    );

                }

            })

            .catch(error => {

                console.error(
                    "Cancel Error:",
                    error
                );


                alert(
                    "Something went wrong while cancelling the appointment."
                );

            })

            .finally(() => {

                confirmCancelAppointment.disabled =
                    false;

            });

        }
    );

}



// ===========================
// CLOSE MODAL OUTSIDE
// ===========================

if(cancelAppointmentModal){

    cancelAppointmentModal.addEventListener(
        "click",
        function(e){

            if(
                e.target ===
                cancelAppointmentModal
            ){

                cancelAppointmentModal.classList.remove(
                    "show"
                );

            }

        }
    );

}

// ===========================
// DYNAMIC SERVICES
// ===========================

const services = {

    particulars: [
        "Consultation",
        "Medication / Treatment",
        "Confinement / Boarding"
    ],

    deworming: [
        "Tablet",
        "Paste"
    ],

    vaccination: [
        "9 in 1",
        "Anti Rabies",
        "Kennel Cough",
        "QuadCat"
    ],

    laboratory: [
        "Blood Chemistry",
        "Smear Test",
        "Ultrasound",
        "Test Kit"
    ],

    specialties: [
        "Surgical Procedures",
        "Dental Hygiene",
        "Skin Disease Treatment"
    ]

};

const category = document.getElementById("serviceCategory");
const service = document.getElementById("service");

category.addEventListener("change", function () {

    service.innerHTML =
        '<option value="">Select Service</option>';

    const selected = this.value;

    if (services[selected]) {

        services[selected].forEach(function(item){

            const option = document.createElement("option");

            option.value = item;

            option.textContent = item;

            service.appendChild(option);

        });

    }

});

// ===========================
// SERVICE AVAILABILITY
// ===========================

const availabilityCard =
document.getElementById("availabilityCard");

service.addEventListener("change", function(){

    const selected = this.value;

    const vetRequired = [

        "Consultation",
        "Medication / Treatment",
        "Confinement / Boarding",

        "Blood Chemistry",
        "Smear Test",
        "Ultrasound",
        "Test Kit",

        "Surgical Procedures",
        "Dental Hygiene",
        "Skin Disease Treatment"

    ];

    if(vetRequired.includes(selected)){

        availabilityCard.innerHTML = `

            <div class="availability-icon">
                👨‍⚕️
            </div>

            <div class="availability-content">

                <h5>Veterinarian Required</h5>

                <p>

                Monday–Friday<br>

                10:00 AM – 1:00 PM<br><br>

                Saturday<br>

                10:00 AM – 12:00 PM

                </p>

            </div>

        `;

    }else{

        availabilityCard.innerHTML = `

            <div class="availability-icon">
                👩‍⚕️
            </div>

            <div class="availability-content">

                <h5>Veterinary Staff Service</h5>

                <p>

                Monday–Friday<br>

                8:00 AM – 4:00 PM<br><br>

                Saturday<br>

                8:00 AM – 12:00 PM

                </p>

            </div>

        `;

    }

});

// ===========================
// ASSIGNED VETERINARIAN
// ===========================

const vetServices = [

    "Consultation",
    "Medication / Treatment",
    "Confinement / Boarding",

    "Blood Chemistry",
    "Smear Test",
    "Ultrasound",
    "Test Kit",

    "Surgical Procedures",
    "Dental Hygiene",
    "Skin Disease Treatment"

];

const veterinarian = document.getElementById("veterinarian");

service.addEventListener("change", function(){

    if(vetServices.includes(this.value)){

        veterinarian.disabled = false;

    }else{

        veterinarian.disabled = true;
        veterinarian.value = "";

    }

});

// ===========================
// SMART TIME SLOTS
// ===========================

const serviceCategory =
document.getElementById("serviceCategory");

const appointmentDate =
document.getElementById("appointmentDate");

// ===========================
// DISABLE PAST DATES
// ===========================

const today = new Date();

today.setHours(0,0,0,0);

appointmentDate.min =
today.toISOString().split("T")[0];

const appointmentTime =
document.getElementById("appointmentTime");

// ===========================
// SMART TIME SLOTS
// MAX 3 APPOINTMENTS PER SLOT
// ===========================

const MAX_APPOINTMENTS_PER_SLOT = 3;

async function generateAvailableTimeSlots(){

    // ===========================
    // CHECK REQUIRED FIELDS
    // ===========================

    if(
        serviceCategory.value === "" ||
        appointmentDate.value === ""
    ){

        appointmentTime.disabled = true;

        appointmentTime.innerHTML =
            '<option value="">Select service and date first</option>';

        return;
    }


    appointmentTime.disabled = true;

    appointmentTime.innerHTML =
        '<option value="">Loading available times...</option>';


    // ===========================
    // GET SELECTED DATE
    // ===========================

    const selectedDate =
        new Date(
            appointmentDate.value + "T00:00:00"
        );

    const day =
        selectedDate.getDay();


    let startHour;
    let endHour;


    // ===========================
    // SUNDAY
    // ===========================

    if(day === 0){

        appointmentTime.disabled = true;

        appointmentTime.innerHTML =
            '<option value="">Clinic Closed (Sunday)</option>';

        return;
    }


    // ===========================
    // SATURDAY
    // ===========================

    if(day === 6){

        if(
            serviceCategory.value === "vaccination" ||
            serviceCategory.value === "deworming"
        ){

            startHour = 8;
            endHour = 12;

        }else{

            startHour = 10;
            endHour = 12;

        }

    }


    // ===========================
    // MONDAY - FRIDAY
    // ===========================

    else{

        if(
            serviceCategory.value === "vaccination" ||
            serviceCategory.value === "deworming"
        ){

            startHour = 8;
            endHour = 16;

        }else{

            startHour = 10;
            endHour = 13;

        }

    }


    // ===========================
    // LOAD APPOINTMENTS
    // ===========================

    let existingAppointments = [];

    try{

        const response =
            await fetch(
                "../process/get_appointments.php"
            );

        const result =
            await response.json();


        if(result.success){

            existingAppointments =
                result.data || [];

        }

    }catch(error){

        console.error(
            "Failed to load appointments:",
            error
        );

        appointmentTime.disabled = false;

        appointmentTime.innerHTML =
            '<option value="">Unable to load availability</option>';

        return;
    }


    // ===========================
    // CREATE TIME SLOTS
    // ===========================

    appointmentTime.innerHTML =
        '<option value="">Select Time</option>';


    for(
        let hour = startHour;
        hour <= endHour;
        hour++
    ){

        for(
            let minute = 0;
            minute < 60;
            minute += 30
        ){

            if(
                hour === endHour &&
                minute > 0
            ){

                break;
            }


            // ===========================
            // TIME VALUE
            // ===========================

            let displayHour =
                hour % 12 || 12;

            let ampm =
                hour >= 12 ? "PM" : "AM";

            let displayMinute =
                minute
                    .toString()
                    .padStart(2, "0");


            let displayText =
                `${displayHour}:${displayMinute} ${ampm}`;


            // ===========================
            // DATABASE TIME FORMAT
            // ===========================

            let databaseTime =
                `${hour
                    .toString()
                    .padStart(2, "0")}:${displayMinute}:00`;


            // ===========================
            // COUNT ACTIVE APPOINTMENTS
            // ===========================

            const bookedCount =
                existingAppointments.filter(
                    function(appointment){

                        const sameDate =
                            String(
                                appointment.appointment_date
                            ) ===
                            String(
                                appointmentDate.value
                            );


                        const appointmentTimeValue =
                            String(
                                appointment.appointment_time
                            ).substring(0, 8);


                        const sameTime =
                            appointmentTimeValue ===
                            databaseTime;


                        const activeStatus =
                            [
                                "Pending",
                                "Confirmed",
                                "Arrived"
                            ].includes(
                                appointment.status
                            );


                        return (
                            sameDate &&
                            sameTime &&
                            activeStatus
                        );

                    }
                ).length;


            // ===========================
            // CREATE OPTION
            // ===========================

            const option =
                document.createElement("option");


            option.value =
                displayText;


            // ===========================
            // SLOT STATUS
            // ===========================

            if(
                bookedCount >=
                MAX_APPOINTMENTS_PER_SLOT
            ){

                option.textContent =
                    `${displayText} — FULL`;

                option.disabled = true;

            }else{

                const remaining =
                    MAX_APPOINTMENTS_PER_SLOT -
                    bookedCount;


                option.textContent =
                    `${displayText} — ${remaining} slot${
                        remaining !== 1 ? "s" : ""
                    } left`;

            }


            appointmentTime.appendChild(option);

        }

    }


    appointmentTime.disabled = false;

}

serviceCategory.addEventListener(
    "change",
    generateAvailableTimeSlots
);

appointmentDate.addEventListener(
    "change",
    generateAvailableTimeSlots
);

// ===========================
// SUCCESS MODAL
// ===========================

const appointmentSuccessModal =
    document.getElementById(
        "appointmentSuccessModal"
    );

const successModalDone =
    document.getElementById(
        "successModalDone"
    );


function showAppointmentSuccess(){

    if(appointmentSuccessModal){

        appointmentSuccessModal.classList.add(
            "show"
        );

    }

}


function closeAppointmentSuccess(){

    if(appointmentSuccessModal){

        appointmentSuccessModal.classList.remove(
            "show"
        );

    }

}

if(successModalDone){

    successModalDone.addEventListener(
        "click",
        function(){

            closeAppointmentSuccess();
            
            window.location.reload();

        }
    );

}

// ===========================
// SAVE APPOINTMENT
// ===========================

const saveAppointment =
    document.getElementById("saveAppointment");

if(saveAppointment){

    saveAppointment.addEventListener("click", async function(){

        // ===========================
        // RUN VALIDATION
        // ===========================

        const isValid = validateAppointmentForm();

        if(!isValid){

            return;

        }
        console.log("Validation passed. Preparing to save...");


        // ===========================
        // COLLECT FORM DATA
        // ===========================

        const formData = new FormData();

        // Appointment Information
        formData.append(
            "serviceCategory",
            document.getElementById("serviceCategory").value
        );

        formData.append(
            "service",
            document.getElementById("service").value
        );

        formData.append(
            "appointmentDate",
            document.getElementById("appointmentDate").value
        );

        formData.append(
            "appointmentTime",
            document.getElementById("appointmentTime").value
        );

        formData.append(
            "appointmentType",
            document.getElementById("appointmentType").value
        );

        formData.append(
            "reason",
            document.getElementById("reason").value
        );

        // ===========================
        // CLIENT TYPE / EXISTING IDS
       // ===========================

        if(existingRadio.checked){

            formData.append(
                "customer_id",
                searchOwner.dataset.customerId
            );

            formData.append(
                "pet_id",
                addingNewPetForExistingClient
                    ? ""
                    : existingPet.value
            );

            formData.append (
                "newPetForExisting",
                addingNewPetForExistingClient
                    ? "1"
                    :"0"
            );       

        }else{

            formData.append(
                "customer_id",
                ""
           );

            formData.append(
                "pet_id",
                ""
            );

            formData.append(
                "newPetForExisting",
                "0"

            );

        }


        // Owner Information
        formData.append(
            "ownerName",
            document.getElementById("ownerName").value
        );

        formData.append(
            "contactNumber",
            document.getElementById("contactNumber").value
        );

        formData.append(
            "email",
            document.getElementById("email").value
        );

        formData.append(
            "address",
            document.getElementById("address").value
        );


        // Pet Information
        formData.append(
            "petName",
            document.getElementById("petName").value
        );

        formData.append(
            "species",
            document.getElementById("species").value
        );

        formData.append(
            "breed",
            document.getElementById("breed").value
        );

        formData.append(
            "otherBreed",
            document.getElementById("otherBreed").value
        );

        formData.append(
            "color",
            document.getElementById("color").value
        );

        formData.append(
            "gender",
            document.getElementById("gender").value
        );

        formData.append(
            "weight",
            document.getElementById("weight").value
        );

        formData.append(
            "estimatedAge",
            document.getElementById("estimatedAge").value
        );


        // ===========================
        // SEND TO PHP
        // ===========================

        try{
            console.log("Sending appointment to save_appointment.php...");
            const response = await fetch(
                "../process/save_appointment.php",
                {
                    method: "POST",
                    body: formData
                }
            );

            const result = await response.json();

            console.log(result);


            if(result.success){

                resetAppointmentForm();

               // Close Add Appointment modal
               modal.classList.remove("show");

               // Show custom success modal
               showAppointmentSuccess();

            }else{

                alert(result.message);

            }

        }catch(error){

            console.error("Save Appointment Error:", error);

            alert(
                "Something went wrong while saving the appointment."
            );

        }

    });

}

// ===========================
// CLIENT TYPE
// ===========================

const existingRadio =
document.getElementById("existingClient");

const newRadio =
document.getElementById("newClient");

const existingSection =
document.getElementById("existingClientSection");

const ownerSection =
document.getElementById("ownerSection");

const petSection =
document.getElementById("petSection");

function toggleClientType(){

    const layout =
        document.getElementById(
            "appointmentClientLayout"
        );

    if(!layout){
        console.error(
            "Appointment client layout not found."
        );
        return;
    }


    if(existingRadio.checked){

        // ===========================
        // EXISTING CLIENT
        // ===========================

        existingSection.style.display = "block";

        ownerSection.style.display = "none";

        petSection.style.display = "block";

        layout.classList.add(
            "existing-client"
        );


        // ===========================
        // CLEAR NEW CLIENT DATA
        // ===========================

        // Owner
        document.getElementById(
            "ownerName"
        ).value = "";

        document.getElementById(
            "contactNumber"
        ).value = "";

        document.getElementById(
            "email"
        ).value = "";

        document.getElementById(
            "address"
        ).value = "";


        // Pet
        document.getElementById(
            "petName"
        ).value = "";

        document.getElementById(
            "species"
        ).value = "";

        document.getElementById(
            "breed"
        ).value = "";

        document.getElementById(
            "color"
        ).value = "";

        document.getElementById(
            "gender"
        ).value = "Male";

        document.getElementById(
            "weight"
        ).value = "";

        document.getElementById(
            "estimatedAge"
        ).value = "";


        // Appointment
        document.getElementById(
            "serviceCategory"
        ).value = "";

        document.getElementById(
            "service"
        ).value = "";

        document.getElementById(
            "appointmentDate"
        ).value = "";

        document.getElementById(
            "appointmentTime"
        ).innerHTML =
            '<option value="">Select a date first</option>';

        document.getElementById(
            "appointmentType"
        ).value = "";

        document.getElementById(
            "reason"
        ).value = "";


    }else{

        // ===========================
        // NEW CLIENT
        // ===========================

        existingSection.style.display = "none";

        ownerSection.style.display = "block";

        petSection.style.display = "block";

        layout.classList.remove(
            "existing-client"
        );


        // ===========================
        // MAKE PET FIELDS EDITABLE
        // ===========================

        document.getElementById(
            "petName"
        ).readOnly = false;

        document.getElementById(
            "species"
        ).disabled = false;

        document.getElementById(
            "breed"
        ).disabled = false;

        document.getElementById(
            "color"
        ).readOnly = false;

        document.getElementById(
            "gender"
        ).disabled = false;

        document.getElementById(
            "weight"
        ).readOnly = false;

        document.getElementById(
            "estimatedAge"
        ).readOnly = false;


        // ===========================
        // CLEAR EXISTING CLIENT DATA
        // ===========================

        searchOwner.value = "";

        searchOwner.dataset.customerId = "";

        existingPet.innerHTML =
            '<option value="">Select Pet</option>';

        existingClientPets = [];

        document.getElementById(
            "petName"
        ).value = "";

        document.getElementById(
            "species"
        ).value = "";

        document.getElementById(
            "breed"
        ).value = "";

        document.getElementById(
            "color"
        ).value = "";

        document.getElementById(
            "gender"
        ).value = "Male";

        document.getElementById(
            "weight"
        ).value = "";

        document.getElementById(
            "estimatedAge"
        ).value = "";

        // ===========================
        // CLEAR APPOINTMENT DATA
        // ===========================

        document.getElementById(
            "serviceCategory"
        ).value = "";

        document.getElementById(
            "service"
        ).value = "";

        document.getElementById(
            "appointmentDate"
        ).value = "";

        document.getElementById(
            "appointmentTime"
        ).innerHTML =
            '<option value="">Select a date first</option>';

        document.getElementById(
         "appointmentType"
        ).value = "";

        document.getElementById(
            "reason"
        ).value = "";

        

    }

}

existingRadio.addEventListener(
    "change",
    toggleClientType
);

newRadio.addEventListener(
    "change",
    toggleClientType
);

toggleClientType();

// ===========================
// LOAD EXISTING CLIENTS
// ===========================

let existingClients = [];

fetch("../process/get_existing_clients.php")
    .then(response => response.json())
    .then(result => {

        console.log("Existing Clients:", result);

        if(result.success){

            existingClients = result.data;

        }

    })
    .catch(error => {

        console.error(
            "Failed to load existing clients:",
            error
        );

    });

// ===========================
// ADD NEW PET FOR EXISTING CLIENT
// ===========================

let addingNewPetForExistingClient = false;

const addNewPetBtn =
    document.getElementById("addNewPetBtn");

const existingPetSelect =
    document.getElementById("existingPet");


function setPetFieldsEditable(editable){

    document.getElementById("petName").readOnly =
        !editable;

    document.getElementById("species").disabled =
        !editable;

    document.getElementById("breed").disabled =
        !editable;

    document.getElementById("color").readOnly =
        !editable;

    document.getElementById("gender").disabled =
        !editable;

    document.getElementById("weight").readOnly =
        !editable;

    document.getElementById("estimatedAge").readOnly =
        !editable;

}


function clearPetFields(){

    document.getElementById("petName").value = "";

    document.getElementById("species").value = "";

    document.getElementById("breed").innerHTML =
        '<option value="">Select Species first</option>';

    document.getElementById("breed").disabled = true;

    document.getElementById("color").value = "";

    document.getElementById("gender").value = "Male";

    document.getElementById("weight").value = "";

    document.getElementById("estimatedAge").value = "";

    document.getElementById(
        "otherBreedContainer"
    ).style.display = "none";

    document.getElementById(
        "otherBreed"
    ).value = "";

}


if(addNewPetBtn){

    addNewPetBtn.addEventListener(
        "click",
        function(){

            // ===========================
            // MAKE SURE OWNER IS SELECTED
            // ===========================

            const customerId =
                searchOwner.dataset.customerId;


            if(!customerId){

                alert(
                    "Please select an existing client first."
                );

                return;

            }


            // ===========================
            // CANCEL NEW PET MODE
            // ===========================

            if(addingNewPetForExistingClient){

                addingNewPetForExistingClient =
                    false;

                existingPetSelect.disabled = false;

                addNewPetBtn.classList.remove(
                    "cancel-mode"
                );

                addNewPetBtn.innerHTML =
                    '<i class="fa-solid fa-plus"></i> Add New Pet';


                clearPetFields();

                return;

            }


            // ===========================
            // ENABLE NEW PET MODE
            // ===========================

            addingNewPetForExistingClient =
                true;


            existingPetSelect.value = "";

            existingPetSelect.disabled = true;


            clearPetFields();


            setPetFieldsEditable(true);


            addNewPetBtn.classList.add(
                "cancel-mode"
            );


            addNewPetBtn.innerHTML =
                '<i class="fa-solid fa-xmark"></i> Cancel Add New Pet';

        }
    );

}    




// ===========================
// VALIDATION FUNCTIONS
// ===========================

function showError(input, message){

    input.classList.add("input-error");

    let error = input.parentElement.querySelector(".error-message");

    if(!error){

        error = document.createElement("small");

        error.className = "error-message";

        input.parentElement.appendChild(error);

    }

    error.textContent = message;
    

}

function clearError(input){

    input.classList.remove("input-error");

    const error = input.parentElement.querySelector(".error-message");

    if(error){

        error.remove();

    }
}


function validateAppointmentForm(){

    let hasError = false;

    const isExistingClient =
        existingRadio.checked;


    // ===========================
    // HELPER
    // ===========================

    function validateField(field){

        if(!field) return;

        field.classList.remove("input-error");

        const oldError =
            field.parentElement.querySelector(
                ".error-message"
            );

        if(oldError){
            oldError.remove();
        }


        if(field.value.trim() === ""){

            hasError = true;

            field.classList.add(
                "input-error"
            );

            const errorMessage =
                document.createElement("small");

            errorMessage.className =
                "error-message";

            errorMessage.textContent =
                "This field is required.";

            field.parentElement.appendChild(
                errorMessage
            );
        }
    }


    // ===========================
    // APPOINTMENT INFORMATION
    // ===========================

    const appointmentFields = [

        document.getElementById(
            "serviceCategory"
        ),

        document.getElementById(
            "service"
        ),

        document.getElementById(
            "appointmentDate"
        ),

        document.getElementById(
            "appointmentTime"
        ),

        document.getElementById(
            "appointmentType"
        )

    ];

    appointmentFields.forEach(function(field){

        validateField(field);

    });


    // ===========================
// EXISTING CLIENT
// ===========================

if(isExistingClient){

    // ===========================
    // OWNER MUST BE SELECTED
    // ===========================

    if(
        !searchOwner.value.trim() ||
        !searchOwner.dataset.customerId
    ){

        hasError = true;

        searchOwner.classList.add(
            "input-error"
        );

        const errorMessage =
            document.createElement("small");

        errorMessage.className =
            "error-message";

        errorMessage.textContent =
            "Please select an existing client.";

        searchOwner.parentElement.appendChild(
            errorMessage
        );

    }


    // ===========================
    // NEW PET
    // ===========================

    if(
        addingNewPetForExistingClient
    ){

        const newPetFields = [

            document.getElementById(
                "petName"
            ),

            document.getElementById(
                "species"
            ),

            document.getElementById(
                "breed"
            ),

            document.getElementById(
                "color"
            ),

            document.getElementById(
                "gender"
            ),

            document.getElementById(
                "weight"
            ),

            document.getElementById(
                "estimatedAge"
            )

        ];


        newPetFields.forEach(
            function(field){

                validateField(field);

            }
        );


    }

    // ===========================
    // EXISTING PET
    // ===========================

    else{

        if(!existingPet.value){

            hasError = true;

            existingPet.classList.add(
                "input-error"
            );

            const errorMessage =
                document.createElement("small");

            errorMessage.className =
                "error-message";

            errorMessage.textContent =
                "Please select a pet.";

            existingPet.parentElement.appendChild(
                errorMessage
            );

        }

    }

}


    // ===========================
    // NEW CLIENT
    // ===========================

    else{

        const newClientFields = [

            document.getElementById(
                "ownerName"
            ),

            document.getElementById(
                "contactNumber"
            ),

            document.getElementById(
                "address"
            ),

            document.getElementById(
                "petName"
            ),

            document.getElementById(
                "species"
            ),

            document.getElementById(
                "breed"
            ),

            document.getElementById(
                "color"
            ),

            document.getElementById(
                "gender"
            ),

            document.getElementById(
                "weight"
            ),

            document.getElementById(
                "estimatedAge"
            )

        ];


        newClientFields.forEach(function(field){

            validateField(field);

        });


        // ===========================
        // CONTACT NUMBER
        // ===========================

        const contactNumber =
            document.getElementById(
                "contactNumber"
            );

        if(
            contactNumber.value.trim() !== "" &&
            !/^09\d{9}$/.test(
                contactNumber.value.trim()
            )
        ){

            hasError = true;

            contactNumber.classList.add(
                "input-error"
            );

            const errorMessage =
                document.createElement("small");

            errorMessage.className =
                "error-message";

            errorMessage.textContent =
                "Enter a valid 11-digit mobile number.";

            contactNumber.parentElement.appendChild(
                errorMessage
            );
        }

    }


    // ===========================
    // RESULT
    // ===========================

    if(hasError){

        console.log(
            "Validation failed."
        );

        return false;
    }


    console.log(
        "Validation passed."
    );

    return true;

}
// ===========================
// REMOVE ERROR WHEN USER INPUTS
// ===========================

const requiredFields = document.querySelectorAll(
    "#serviceCategory, #service, #appointmentDate, #appointmentTime, #appointmentType, #reason, #ownerName, #contactNumber, #address, #petName, #species, #breed, #color, #gender, #weight, #estimatedAge, #otherBreed"
);

requiredFields.forEach(function(field){

    field.addEventListener("input", function(){

        if(this.value.trim() !== ""){

            this.classList.remove("input-error");

            const errorMessage =
                this.parentElement.querySelector(".error-message");

            if(errorMessage){
                errorMessage.remove();
            }

        }

    });

    field.addEventListener("change", function(){

        if(this.value.trim() !== ""){

            this.classList.remove("input-error");

            const errorMessage =
                this.parentElement.querySelector(".error-message");

            if(errorMessage){
                errorMessage.remove();
            }

        }

    });

});
// ===========================
// CONTACT NUMBER
// ===========================

const contactNumber =
    document.getElementById("contactNumber");

if(contactNumber){

    contactNumber.addEventListener("input", function(){

        this.value = this.value.replace(/\D/g, "");

    });

}
// ===========================
// SPECIES → BREED
// ===========================

const species = document.getElementById("species");
const breed = document.getElementById("breed");

const otherBreedContainer =
    document.getElementById("otherBreedContainer");

const otherBreed =
    document.getElementById("otherBreed");

const dogBreeds = [
    "Labrador Retriever",
    "Golden Retriever",
    "German Shepherd",
    "Shih Tzu",
    "Pomeranian",
    "Poodle",
    "Beagle",
    "Bulldog",
    "Chihuahua",
    "Siberian Husky",
    "Mixed Breed",
    "Others"
];

const catBreeds = [
    "Persian",
    "Siamese",
    "Maine Coon",
    "Ragdoll",
    "British Shorthair",
    "Bengal",
    "Scottish Fold",
    "American Shorthair",
    "Sphynx",
    "Mixed Breed",
    "Others"
];

species.addEventListener("change", function(){

    breed.innerHTML = "";

    otherBreedContainer.style.display = "none";
    otherBreed.value = "";

    if(this.value === ""){

        breed.disabled = true;

        breed.innerHTML =
            '<option value="">Select species first</option>';

        return;
    }

    breed.disabled = false;

    breed.innerHTML =
        '<option value="">Select Breed</option>';

    let breeds = [];

    if(this.value === "dog"){
        breeds = dogBreeds;
    }

    if(this.value === "cat"){
        breeds = catBreeds;
    }

    breeds.forEach(function(item){

        const option =
            document.createElement("option");

        option.value = item;
        option.textContent = item;

        breed.appendChild(option);

    });

});
// ===========================
// OTHER BREED
// ===========================

breed.addEventListener("change", function(){

    if(this.value === "Others"){

        otherBreedContainer.style.display = "block";

        otherBreed.focus();

    }else{

        otherBreedContainer.style.display = "none";

        otherBreed.value = "";

    }
}); 
// ===========================
// CONFIRM APPOINTMENT
// ===========================

document.addEventListener("click", function(e){

    const confirmButton =
        e.target.closest(".confirm-btn");

    if(!confirmButton){
        return;
    }

    const appointmentId =
        confirmButton.dataset.id;


    // ==================================
    // UPDATE STATUS TO CONFIRMED
    // ==================================

    fetch("../process/update_appointment_status.php", {

        method: "POST",

        body: new URLSearchParams({

            appointmentId: appointmentId,
            status: "Confirmed"

        })

    })

    .then(response => response.json())

    .then(result => {

        console.log(
            "Confirm Result:",
            result
        );


        if(!result.success){

            alert(
                result.message ||
                "Failed to confirm appointment."
            );

            return;
        }


        // ==================================
        // SEND EMAIL CONFIRMATION
        // ==================================

        return fetch(
            "../process/send_appointment_confirmation.php",
            {

                method: "POST",

                body: new URLSearchParams({

                    appointmentId: appointmentId

                })

            }

        )

        .then(response => response.json())

        .then(emailResult => {

            console.log(
                "Email Result:",
                emailResult
            );


            // ==================================
            // REFRESH APPOINTMENT LIST
            // ==================================

            loadAppointmentList();

            if (calendar){
                calendar.refetchEvents();
            }


            // ==================================
            // EMAIL RESULT
            // ==================================

            if(emailResult.email_sent){

                alert(
                    "Appointment confirmed successfully.\n\n" +
                    "Confirmation email has been sent."
                );

            }

            else if(
                emailResult.success &&
                emailResult.email_sent === false
            ){

                alert(
                    "Appointment confirmed successfully.\n\n" +
                    "No email address is registered for this customer."
                );

            }

            else{

                alert(
                    "Appointment confirmed successfully.\n\n" +
                    "However, the confirmation email could not be sent."
                );

            }

        });

    })

    .catch(error => {

        console.error(
            "Confirm Error:",
            error
        );

        alert(
            "Something went wrong while confirming the appointment."
        );

    });

});

// ===========================
// COMPLETE APPOINTMENT
// ===========================

document.addEventListener("click", function(e){

    if(e.target.classList.contains("complete-btn")){

        const appointmentId =
            e.target.dataset.id;

        fetch("../process/update_appointment_status.php", {

            method: "POST",

            body: new URLSearchParams({
                appointmentId: appointmentId,
                status: "Completed"
            })

        })

        .then(response => response.json())

        .then(result => {

            console.log("Complete Result:", result);

            if(result.success){

                loadAppointmentList();

            }

        })

        .catch(error => {

            console.error(
                "Complete Error:",
                error
            );

        });

    }

});

// ===========================
// MARK APPOINTMENT AS ARRIVED
// ===========================

document.addEventListener("click", function(e){

    const arrivedButton =
        e.target.closest(".arrived-btn");

    if(!arrivedButton){
        return;
    }

    const appointmentId =
        arrivedButton.dataset.id;

    console.log(
        "Mark as Arrived clicked:",
        appointmentId
    );

    fetch("../process/update_appointment_status.php", {

        method: "POST",

        body: new URLSearchParams({

            appointmentId: appointmentId,
            status: "Arrived"

        })

    })

    .then(response => response.json())

    .then(result => {

        console.log(
            "Arrived Result:",
            result
        );

        if(result.success){

            loadAppointmentList();

        }else{

            alert(
                result.message ||
                "Failed to mark appointment as arrived."
            );

        }

    })

    .catch(error => {

        console.error(
            "Arrived Error:",
            error
        );

        alert(
            "Something went wrong while updating the appointment."
        );

    });

});
// ===========================
// ACTION DROPDOWN
// ===========================

document.addEventListener("click", function(e){

    const menuButton =
        e.target.closest(".action-menu-btn");

    const dropdown =
        e.target.closest(".action-dropdown");


    // Open clicked menu
    if(menuButton){

        e.stopPropagation();

        const wrapper =
            menuButton.closest(".action-wrapper");

        const currentDropdown =
            wrapper.querySelector(".action-dropdown");


        // Close other menus
        document
            .querySelectorAll(".action-dropdown.show")
            .forEach(function(menu){

                if(menu !== currentDropdown){

                    menu.classList.remove("show");

                }

            });


        currentDropdown.classList.toggle("show");

        return;
    }


    // Click inside dropdown
    if(dropdown){

        return;

    }


    // Click outside
    document
        .querySelectorAll(".action-dropdown.show")
        .forEach(function(menu){

            menu.classList.remove("show");

        });

});
// ===========================
// VIEW APPOINTMENT DETAILS
// ===========================

document.addEventListener("click", function(e){

    const viewButton =
        e.target.closest(".view-details-btn");

    console.log("View Details clicked:", viewButton);    

    if(!viewButton){
        return;
    }

    const appointmentId =
        viewButton.dataset.id;

    const appointment =
        window.appointmentsData.find(function(item){

            return String(item.appointment_id) ===
                   String(appointmentId);

        });
    console.log("Selected Appointment:", appointment);
    if(!appointment){

        console.error("Appointment not found.");

        return;

    }

    document.getElementById("detailsReference").textContent =
        "APT-" +
        String(appointment.appointment_id).padStart(4, "0");

    document.getElementById("detailsDate").textContent =
        appointment.appointment_date;

    const timeObject = new Date(
    "1970-01-01T" + appointment.appointment_time
    );

    const formattedDetailsTime =
    timeObject.toLocaleTimeString("en-US", {
        hour: "numeric",
        minute: "2-digit"
    });

    document.getElementById("detailsTime").textContent =
        formattedDetailsTime;

    document.getElementById("detailsStatus").textContent =
        appointment.status;

    document.getElementById("detailsPet").textContent =
        appointment.pet_name;

    document.getElementById("detailsSpecies").textContent =
        appointment.species;

    document.getElementById("detailsOwner").textContent =
        appointment.owner_name;

    document.getElementById("detailsService").textContent =
        appointment.service;

    document.getElementById("detailsType").textContent =
        appointment.appointment_type;

    document.getElementById("detailsReason").textContent =
        appointment.reason || "—";

    const modal =
        document.getElementById("appointmentDetailsModal");


    const cancellationDetails =
        modal
            ? modal.querySelector("#cancellationDetails")
            : null;


    const detailsCancellationReason =
        modal
            ? modal.querySelector("#detailsCancellationReason")
            : null;


    if (
        cancellationDetails &&
        detailsCancellationReason
    ) {

        if (appointment.status === "Cancelled") {

            cancellationDetails.style.display = "block";

            detailsCancellationReason.textContent =
                appointment.cancellation_reason || "—";

        } else {

            cancellationDetails.style.display = "none";

            detailsCancellationReason.textContent =
                "—";

        }

    }

    console.log("Modal found:", modal);

    if(modal){

        modal.classList.add("show");

    }

});

// ===========================
// CLOSE APPOINTMENT DETAILS
// ===========================

const closeAppointmentDetails =
    document.getElementById("closeAppointmentDetails");

if(closeAppointmentDetails){

    closeAppointmentDetails.addEventListener("click", function(){

        const modal =
            document.getElementById("appointmentDetailsModal");

        if(modal){

            modal.classList.remove("show");

        }

    });

}
// ===========================
// CLOSE DETAILS MODAL OUTSIDE
// ===========================

const appointmentDetailsModal =
    document.getElementById("appointmentDetailsModal");

if(appointmentDetailsModal){

    appointmentDetailsModal.addEventListener("click", function(e){

        if(e.target === appointmentDetailsModal){

            appointmentDetailsModal.classList.remove("show");

        }

    });

}
// ===========================
// SEND CONFIRMATION EMAIL
// ===========================

document.addEventListener("click", function(e){

    const sendEmailButton =
        e.target.closest(".send-confirmation-btn");

    if(!sendEmailButton){
        return;
    }


    const appointmentId =
        sendEmailButton.dataset.id;


    fetch(
        "../process/send_appointment_confirmation.php",
        {

            method: "POST",

            body: new URLSearchParams({

                appointmentId: appointmentId

            })

        }

    )

    .then(response => response.json())

    .then(result => {

        console.log(
            "Send Confirmation Result:",
            result
        );


        if(result.email_sent){

            alert(
                "Confirmation email sent successfully."
            );

        }

        else if(
            result.success &&
            result.email_sent === false
        ){

            alert(
                "This customer does not have a registered email address."
            );

        }

        else{

            alert(
                result.message ||
                "Failed to send confirmation email."
            );

        }

    })

    .catch(error => {

        console.error(
            "Send Confirmation Error:",
            error
        );

        alert(
            "Something went wrong while sending the confirmation email."
        );

    });

});

// ===========================
// SEARCH EXISTING CLIENTS
// ===========================

const searchOwner =
    document.getElementById("searchOwner");

const ownerSearchResults =
    document.getElementById("ownerSearchResults");

searchOwner.addEventListener("input", function(){

    const searchValue =
        this.value.trim().toLowerCase();

    ownerSearchResults.innerHTML = "";

    if(searchValue === ""){
        return;
    }

    const matches =
        existingClients.filter(function(client){

            return client.owner_name
                .toLowerCase()
                .includes(searchValue);

        });

    matches.forEach(function(client){

        const result =
            document.createElement("div");

        result.className =
            "owner-search-item";

        result.textContent =
            client.owner_name;

        result.dataset.customerId =
            client.customer_id;

        ownerSearchResults.appendChild(result);

    });

});

// ===========================
// SELECT EXISTING CLIENT
// ===========================

ownerSearchResults.addEventListener("click", function(e){

    const selected =
        e.target.closest(".owner-search-item");

    if(!selected){
        return;
    }

    const customerId =
        selected.dataset.customerId;

    const client =
        existingClients.find(function(item){

            return String(item.customer_id) ===
                   String(customerId);

        });

    if(!client){
        return;
    }

    searchOwner.value =
        client.owner_name;

    searchOwner.dataset.customerId =
        client.customer_id;

    ownerSearchResults.innerHTML = "";

    console.log(
        "Selected Client:",
        client
    );

    // ===========================
// LOAD CLIENT PETS
// ===========================

const existingPet =
    document.getElementById("existingPet");

existingPet.innerHTML =
    '<option value="">Loading pets...</option>';

fetch(
    "../process/get_client_pets.php?customer_id=" +
    encodeURIComponent(client.customer_id)
)
    .then(response => response.json())
    .then(result => {

        console.log(
            "Client Pets:",
            result
        );

        existingPet.innerHTML =
            '<option value="">Select Pet</option>';

        if(!result.success){
            return;
        }

        if(result.data.length === 0){

            existingPet.innerHTML =
                '<option value="">No pets found</option>';

            return;
        }
        existingClientPets = result.data;
        result.data.forEach(function(pet){

            const option =
                document.createElement("option");

            option.value =
                pet.pet_id;

            option.textContent =
                pet.pet_name +
                " (" +
                pet.species +
                ")";

            existingPet.appendChild(option);

        });

    })
    .catch(error => {

        console.error(
            "Failed to load client pets:",
            error
        );

        existingPet.innerHTML =
            '<option value="">Unable to load pets</option>';

    });

});

// ===========================
// SELECT EXISTING PET
// ===========================

let existingClientPets = [];


existingPet.addEventListener(
    "change",
    function(){
        if(addingNewPetForExistingClient){
            return;
        }



    const petId = this.value;

    if(!petId){
        return;
    }

    const selectedPet =
        existingClientPets.find(function(pet){

            return String(pet.pet_id) ===
                   String(petId);

        });

    console.log(
        "Selected Existing Pet:",
        selectedPet
    );

    if(!selectedPet){
        return;
    }


    // ===========================
    // AUTO-FILL PET INFORMATION
    // ===========================

    document.getElementById("petName").value =
        selectedPet.pet_name || "";

    document.getElementById("species").value =
        selectedPet.species || "";

    document.getElementById("color").value =
        selectedPet.color || "";

    document.getElementById("gender").value =
        selectedPet.gender || "";

    document.getElementById("weight").value =
        selectedPet.weight || "";

    document.getElementById("estimatedAge").value =
        selectedPet.estimated_age || "";


    // ===========================
    // BREED
    // ===========================

    const breed =
    document.getElementById("breed");

const savedBreed =
    selectedPet.breed || "";

if(savedBreed){

    // Check if breed already exists
    let breedOption =
        Array.from(breed.options).find(function(option){

            return option.value === savedBreed ||
                   option.textContent === savedBreed;

        });


    // If breed is not in the dropdown,
    // temporarily add the saved database value
    if(!breedOption){

        breedOption =
            document.createElement("option");

        breedOption.value =
            savedBreed;

        breedOption.textContent =
            savedBreed;

        breed.appendChild(breedOption);

    }


    breed.value = savedBreed;

}

breed.disabled = true;

    // ===========================
    // MAKE EXISTING PET READ-ONLY
    // ===========================

    document.getElementById("petName").readOnly = true;

    document.getElementById("species").disabled = true;

    document.getElementById("breed").disabled = true;

    document.getElementById("color").readOnly = true;

    document.getElementById("gender").disabled = true;

    document.getElementById("weight").readOnly = true;

    document.getElementById("estimatedAge").readOnly = true;

});


// ===========================
// RESET APPOINTMENT FORM
// ===========================

function resetAppointmentForm(){

    // ===========================
    // APPOINTMENT INFORMATION
    // ===========================

    document.getElementById(
        "serviceCategory"
    ).value = "";

    document.getElementById(
        "service"
    ).value = "";

    document.getElementById(
        "appointmentDate"
    ).value = "";

    document.getElementById(
        "appointmentTime"
    ).innerHTML =
        '<option value="">Select a date first</option>';

    document.getElementById(
        "appointmentType"
    ).value = "";

    document.getElementById(
        "reason"
    ).value = "";


    // ===========================
    // OWNER INFORMATION
    // ===========================

    document.getElementById(
        "ownerName"
    ).value = "";

    document.getElementById(
        "contactNumber"
    ).value = "";

    document.getElementById(
        "email"
    ).value = "";

    document.getElementById(
        "address"
    ).value = "";


    // ===========================
    // EXISTING CLIENT
    // ===========================

    searchOwner.value = "";

    searchOwner.dataset.customerId = "";

    existingPet.innerHTML =
        '<option value="">Select Pet</option>';

    existingClientPets = [];


    // ===========================
    // PET INFORMATION
    // ===========================

    document.getElementById(
        "petName"
    ).value = "";

    document.getElementById(
        "species"
    ).value = "";

    document.getElementById(
        "breed"
    ).value = "";

    document.getElementById(
        "color"
    ).value = "";

    document.getElementById(
        "gender"
    ).value = "Male";

    document.getElementById(
        "weight"
    ).value = "";

    document.getElementById(
        "estimatedAge"
    ).value = "";


    // ===========================
    // CLEAR VALIDATION ERRORS
    // ===========================

    document
        .querySelectorAll(
            ".input-error"
        )
        .forEach(function(field){

            field.classList.remove(
                "input-error"
            );

        });


    document
        .querySelectorAll(
            ".error-message"
        )
        .forEach(function(error){

            error.remove();

        });


    // ===========================
    // RETURN TO EXISTING CLIENT
    // ===========================

    existingRadio.checked = true;

    newRadio.checked = false;

    toggleClientType();

}


});