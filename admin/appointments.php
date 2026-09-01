<?php
session_start();

if (!isset($_SESSION["admin_username"])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once __DIR__ . '/../config/database.php';

/* =====================================================
   ACTIVE SERVICE CATALOG FOR APPOINTMENT FORM
   -----------------------------------------------------
   Service Categories and Services are managed from
   System Variables. The appointment form reads only
   Active records from the database.
   ===================================================== */
$appointmentServiceCatalog = [];

$serviceCatalogSql = "
    SELECT
        sc.category_id,
        sc.category_name,
        s.service_id,
        s.service_name,
        s.pricing_type,
        s.fixed_price
    FROM service_categories sc
    LEFT JOIN services s
        ON s.category_id = sc.category_id
        AND s.status = 'Active'
    WHERE sc.status = 'Active'
    ORDER BY
        sc.category_name ASC,
        s.service_name ASC
";

$serviceCatalogResult = mysqli_query($conn, $serviceCatalogSql);

if ($serviceCatalogResult) {

    while ($row = mysqli_fetch_assoc($serviceCatalogResult)) {

        $categoryId = (int)$row['category_id'];

        if (!isset($appointmentServiceCatalog[$categoryId])) {
            $appointmentServiceCatalog[$categoryId] = [
                'category_id' => $categoryId,
                'category_name' => $row['category_name'],
                'services' => []
            ];
        }

        if ($row['service_id'] !== null) {
            $appointmentServiceCatalog[$categoryId]['services'][] = [
                'service_id' => (int)$row['service_id'],
                'service_name' => $row['service_name'],
                'pricing_type' => $row['pricing_type'],
                'fixed_price' => $row['fixed_price'] !== null
                    ? (float)$row['fixed_price']
                    : null
            ];
        }
    }
}

$appointmentServiceCatalog = array_values($appointmentServiceCatalog);

$appointmentServiceCatalogJson = json_encode(
    $appointmentServiceCatalog,
    JSON_HEX_TAG |
    JSON_HEX_AMP |
    JSON_HEX_APOS |
    JSON_HEX_QUOT
);

if ($appointmentServiceCatalogJson === false) {
    $appointmentServiceCatalogJson = '[]';
}

/* =====================================================
   ACTIVE PET SPECIES + BREEDS FOR APPOINTMENT FORM
   -----------------------------------------------------
   Species and breeds are managed from System Variables.
   Only Active records are shown in the appointment form.
   'Others' remains a manual fallback and is NOT stored
   as a reference breed in the database.
   ===================================================== */
$appointmentSpeciesList = [];
$appointmentBreedCatalog = [];

$petReferenceSql = "
    SELECT
        ps.species_id,
        ps.species,
        pb.breed_id,
        pb.breed
    FROM pet_species ps
    LEFT JOIN pet_breeds pb
        ON pb.species_id = ps.species_id
        AND pb.status = 'Active'
    WHERE ps.status = 'Active'
    ORDER BY
        ps.species ASC,
        pb.breed ASC
";

$petReferenceResult = mysqli_query($conn, $petReferenceSql);

if ($petReferenceResult) {
    while ($row = mysqli_fetch_assoc($petReferenceResult)) {

        $speciesId = (int)$row['species_id'];
        $speciesName = $row['species'];

        if (!isset($appointmentBreedCatalog[$speciesId])) {
            $appointmentSpeciesList[] = [
                'species_id' => $speciesId,
                'species' => $speciesName
            ];

            $appointmentBreedCatalog[$speciesId] = [
                'species_id' => $speciesId,
                'species' => $speciesName,
                'breeds' => []
            ];
        }

        if ($row['breed_id'] !== null) {
            $appointmentBreedCatalog[$speciesId]['breeds'][] = [
                'breed_id' => (int)$row['breed_id'],
                'breed' => $row['breed']
            ];
        }
    }
}

$appointmentBreedCatalog = array_values($appointmentBreedCatalog);

$appointmentSpeciesJson = json_encode(
    $appointmentSpeciesList,
    JSON_HEX_TAG |
    JSON_HEX_AMP |
    JSON_HEX_APOS |
    JSON_HEX_QUOT
);

if ($appointmentSpeciesJson === false) {
    $appointmentSpeciesJson = '[]';
}

$appointmentBreedCatalogJson = json_encode(
    $appointmentBreedCatalog,
    JSON_HEX_TAG |
    JSON_HEX_AMP |
    JSON_HEX_APOS |
    JSON_HEX_QUOT
);

if ($appointmentBreedCatalogJson === false) {
    $appointmentBreedCatalogJson = '[]';
}
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
                        <input 
                          type="text"
                          id="appointmentSearch"
                          placeholder="Search appointment...">
                    </div>

                </div>

                <div class="toolbar-right">

                    <button class="add-btn" id="openAppointmentModal">
                        <i class="fa-solid fa-plus"></i>
                        Add Appointment
                    </button>

                    <a href="appointments_archive.php" class="archive-btn">
                        <i class="fa-solid fa-box-archive"></i>
                        Archived
                    </a>

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

                <div class="appointment-status-filter">
                    <label for="appointmentStatusFilter">Status</label>

                    <select id="appointmentStatusFilter">
                        <option value="">All Status</option>
                        <option value="Pending">Pending</option>
                        <option value="Confirmed">Confirmed</option>
                        <option value="Arrived">Arrived</option>
                        <option value="Completed">Completed</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
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

<!-- ===========================
     ADD APPOINTMENT MODAL
=========================== -->

<div class="modal-overlay" id="addAppointmentModal">

    <div class="modal-box large-modal">

        <!-- MODAL HEADER -->
        <div class="modal-header">

            <h3>Create Appointment</h3>

            <button
                class="modal-close"
                id="closeAddAppointment"
                type="button">
                &times;
            </button>

        </div>


        <!-- ===========================
             CLIENT TYPE
        =========================== -->

        <div class="form-section">

            <h4>Client Type</h4>

            <div class="client-type">

                <!-- EXISTING CLIENT -->

                <label class="client-option">

                    <input
                        type="radio"
                        name="clientType"
                        id="existingClient"
                        checked>

                    <div class="client-card">

                        <i class="fa-solid fa-user-check"></i>

                        <div>

                            <strong>Existing Client</strong>

                            <small>
                                Select an existing owner and pet record.
                            </small>

                        </div>

                    </div>

                </label>


                <!-- NEW CLIENT -->

                <label class="client-option">

                    <input
                        type="radio"
                        name="clientType"
                        id="newClient">

                    <div class="client-card">

                        <i class="fa-solid fa-user-plus"></i>

                        <div>

                            <strong>New Client</strong>

                            <small>
                                Create a new owner and pet record.
                            </small>

                        </div>

                    </div>

                </label>

            </div>


            <!-- ===========================
                 EXISTING CLIENT
            =========================== -->

            <div
                id="existingClientSection"
                class="form-section"
                style="display:none;">

                <h4>Existing Client</h4>

                <div class="form-grid">

                    <!-- SEARCH OWNER -->

                    <div class="form-group">

                        <label>
                            Search Owner
                            <span class="required">*</span>
                        </label>

                        <input
                            type="text"
                            id="searchOwner"
                            placeholder="Search owner name...">

                        <div
                            id="ownerSearchResults"
                            class="owner-search-results">
                        </div>

                    </div>


                    <!-- SELECT PET -->

                    <div class="form-group">

                        <label>
                            Select Pet
                            <span class="required">*</span>
                        </label>

                        <select id="existingPet">

                            <option value="">
                                Select Pet
                            </option>

                        </select>

                        <button
                            type="button"
                            id="addNewPetBtn"
                            class="add-new-pet-btn">

                            <i class="fa-solid fa-plus"></i>
                            Add New Pet
                        </button>

                    </div>

                </div>

            </div>

        </div>


        <!-- ===========================
             APPOINTMENT / CLIENT LAYOUT
        =========================== -->

        <div
            class="appointment-client-layout"
            id="appointmentClientLayout">


            <!-- ===========================
                 APPOINTMENT INFORMATION
            =========================== -->

            <div
                class="form-section appointment-section">

                <h4>Appointment Information</h4>

                <div class="form-grid">


                    <!-- SERVICE CATEGORY -->

                    <div class="form-group">

                        <label>
                            Service Category
                            <span class="required">*</span>
                        </label>

                        <select id="serviceCategory">

                            <option value="">
                                Select Category
                            </option>

                        </select>

                        <small
                            class="error-message"
                            id="serviceCategoryError">
                        </small>

                    </div>


                    <!-- SERVICE -->

                    <div class="form-group">

                        <label>
                            Service
                            <span class="required">*</span>
                        </label>

                        <select id="service">

                            <option value="">
                                Select Service
                            </option>

                        </select>

                    </div>


                    <!-- PREFERRED DATE -->

                    <div class="form-group">

                        <label>
                            Preferred Date
                            <span class="required">*</span>
                        </label>

                        <input
                            type="date"
                            id="appointmentDate">

                    </div>


                    <!-- PREFERRED TIME -->

                    <div class="form-group">

                        <label>
                            Preferred Time
                            <span class="required">*</span>
                        </label>

                        <select id="appointmentTime">

                            <option value="">
                                Select a date first
                            </option>

                        </select>

                    </div>


                    <!-- APPOINTMENT TYPE -->

                    <div class="form-group">

                        <label>
                            Appointment Type
                            <span class="required">*</span>
                        </label>

                        <select id="appointmentType">

                            <option value="">
                                Select Type
                            </option>

                            <option value="initial">
                                Initial Consultation
                            </option>

                            <option value="followup">
                                Follow-up
                            </option>

                        </select>

                    </div>


                    <!-- SERVICE AVAILABILITY -->

                    <div class="form-group">

                        <label>
                            Service Availability
                        </label>

                        <div
                            class="availability-card"
                            id="availabilityCard">

                            <div class="availability-icon">

                                <i class="fa-solid fa-circle-info"></i>

                            </div>

                            <div class="availability-content">

                                <h5>
                                    Select a service first
                                </h5>

                                <p>
                                    Service schedule will appear here.
                                </p>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- ===========================
                     REASON
                =========================== -->

                <div
                    class="form-group"
                    style="margin-top:20px;">

                    <label>
                        Reason for Visit
                    </label>

                    <textarea
                        id="reason"
                        rows="4"
                        placeholder="Briefly describe the pet's condition..."></textarea>

                </div>

            </div>


            <!-- ===========================
                 OWNER INFORMATION
            =========================== -->

            <div
                class="form-section"
                id="ownerSection">

                <h4>Owner Information</h4>

                <div class="form-grid">


                    <!-- OWNER NAME -->

                    <div class="form-group">

                        <label>
                            Owner Name
                            <span class="required">*</span>
                        </label>

                        <input
                            type="text"
                            id="ownerName">

                    </div>


                    <!-- CONTACT NUMBER -->

                    <div class="form-group">

                        <label>
                            Contact Number
                            <span class="required">*</span>
                        </label>

                        <input
                            type="text"
                            id="contactNumber"
                            maxlength="11"
                            inputmode="numeric"
                            placeholder="e.g. 09123456789">

                    </div>


                    <!-- EMAIL -->

                    <div class="form-group">

                        <label>
                            Email Address
                        </label>

                        <input
                            type="email"
                            id="email">

                    </div>


                    <!-- ADDRESS -->

                    <div class="form-group">

                        <label>
                            Address
                            <span class="required">*</span>
                        </label>

                        <input
                            type="text"
                            id="address">

                    </div>

                </div>

            </div>


            <!-- ===========================
                 PET INFORMATION
            =========================== -->

            <div
                class="form-section"
                id="petSection">

                <h4>Pet Information</h4>

                <p
                 id="petSectionNotice"
                 class="pet-section-notice">
                 <i class="fa-solid fa-lock"></i>
                 Complete Owner Information first
               </p>

                <div class="form-grid">


                    <!-- PET NAME -->

                    <div class="form-group">

                        <label>
                            Pet Name
                            <span class="required">*</span>
                        </label>

                        <input
                            type="text"
                            id="petName">

                    </div>


                    <!-- SPECIES -->

                    <div class="form-group">

                        <label>
                            Species
                            <span class="required">*</span>
                        </label>

                        <select id="species">

                            <option value="">
                                Select Species
                            </option>

                            <?php foreach ($appointmentSpeciesList as $appointmentSpecies): ?>

                                <option
                                    value="<?= htmlspecialchars(strtolower($appointmentSpecies['species']), ENT_QUOTES, 'UTF-8') ?>"
                                >
                                    <?= htmlspecialchars($appointmentSpecies['species'], ENT_QUOTES, 'UTF-8') ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <!-- BREED -->

                    <div class="form-group">

                        <label>
                            Breed
                            <span class="required">*</span>
                        </label>

                        <select
                            id="breed"
                            disabled>

                            <option value="">
                                Select Species first
                            </option>

                        </select>


                        <div
                            id="otherBreedContainer"
                            style="display:none; margin-top:5px;">

                            <input
                                type="text"
                                id="otherBreed"
                                placeholder="Enter breed">

                        </div>

                    </div>


                    <!-- COLOR -->

                    <div class="form-group">

                        <label>
                            Color
                            <span class="required">*</span>
                        </label>

                        <input
                            type="text"
                            id="color"
                            placeholder="Enter pet's color">

                    </div>


                    <!-- GENDER -->

                    <div class="form-group">

                        <label>
                            Gender
                            <span class="required">*</span>
                        </label>

                        <select id="gender">

                            <option>
                                Male
                            </option>

                            <option>
                                Female
                            </option>

                        </select>

                    </div>


                    <!-- WEIGHT -->

                    <div class="form-group">

                        <label>
                            Weight (kg)
                            <span class="required">*</span>
                        </label>

                        <input
                            type="number"
                            id="weight">

                    </div>


                    <!-- ESTIMATED AGE -->

                    <div class="form-group">

                        <label>
                            Estimated Age
                            <span class="required">*</span>
                        </label>

                        <input
                            type="text"
                            id="estimatedAge"
                            placeholder="e.g. 2 Years or 8 Months">

                    </div>

                </div>

            </div>

        </div>


        <!-- ===========================
             MODAL FOOTER
        =========================== -->

        <div class="modal-footer">

            <button
                type="button"
                class="cancel-btn"
                id="cancelAppointment">

                Cancel

            </button>


            <button
                type="button"
                class="save-btn"
                id="saveAppointment">

                Book Appointment

            </button>

        </div>

    </div>

</div>


<!-- ===========================
     APPOINTMENT DETAILS MODAL
=========================== -->

<div
    class="details-modal-overlay"
    id="appointmentDetailsModal">

    <div class="details-modal">


        <!-- HEADER -->

        <div class="details-modal-header">

            <h3>
                Appointment Details
            </h3>

            <button
                type="button"
                class="details-modal-close"
                id="closeAppointmentDetails">

                &times;

            </button>

        </div>


        <!-- BODY -->

        <div class="details-modal-body">

            <div class="details-grid">


                <div class="detail-item">

                    <span>
                        Reference
                    </span>

                    <strong id="detailsReference">
                        —
                    </strong>

                </div>


                <div class="detail-item">

                    <span>
                        Date
                    </span>

                    <strong id="detailsDate">
                        —
                    </strong>

                </div>


                <div class="detail-item">

                    <span>
                        Time
                    </span>

                    <strong id="detailsTime">
                        —
                    </strong>

                </div>


                <div class="detail-item">

                    <span>
                        Status
                    </span>

                    <strong id="detailsStatus">
                        —
                    </strong>

                </div>


                <div class="detail-item">

                    <span>
                        Pet
                    </span>

                    <strong id="detailsPet">
                        —
                    </strong>

                </div>


                <div class="detail-item">

                    <span>
                        Species
                    </span>

                    <strong id="detailsSpecies">
                        —
                    </strong>

                </div>


                <div class="detail-item">

                    <span>
                        Owner
                    </span>

                    <strong id="detailsOwner">
                        —
                    </strong>

                </div>


                <div class="detail-item">

                    <span>
                        Service
                    </span>

                    <strong id="detailsService">
                        —
                    </strong>

                </div>


                <div class="detail-item">

                    <span>
                        Appointment Type
                    </span>

                    <strong id="detailsType">
                        —
                    </strong>

                </div>


                <div class="detail-item full-width">

                    <span>
                        Reason for Visit
                    </span>

                    <strong id="detailsReason">
                        —
                    </strong>

                </div>

                <div 
                    class= "details-item full-width cancellation-detail"
                    id="cancellationDetails"
                    style="display:none;"
                >
                    <span>
                        Cancellation Reason
                    </span> 
                    
                    <strong id="detailsCancellationReason">
                        —
                    </strong>
                </div>    

        

            </div>

        </div>

    </div>

</div>

<!-- ===========================
     CANCEL APPOINTMENT MODAL
=========================== -->

<div
    class="cancel-modal-overlay"
    id="cancelAppointmentModal"
>

    <div class="cancel-modal">

        <div class="cancel-modal-header">

            <div>
                <h3>Cancel Appointment</h3>

                <p>
                    Please provide a reason for cancelling this appointment.
                </p>
            </div>

            <button
                type="button"
                id="closeCancelAppointment"
                class="cancel-modal-close"
            >
                &times;
            </button>

        </div>


        <div class="cancel-modal-body">

            <label for="cancellationReason">
                Reason for Cancellation
                <span class="required">*</span>
            </label>

            <select
                id="cancellationReason"
            >

                <option value="">
                    Select reason
                </option>

                <option value="Veterinarian unavailable">
                    Veterinarian unavailable
                </option>

                <option value="Clinic schedule conflict">
                    Clinic schedule conflict
                </option>

                <option value="Service unavailable">
                    Service unavailable
                </option>

                <option value="Emergency / unforeseen circumstances">
                    Emergency / unforeseen circumstances
                </option>

                <option value="Other">
                    Other
                </option>

            </select>


            <div
                id="otherCancellationContainer"
                style="display:none;"
            >

                <label for="otherCancellationReason">
                    Please specify
                    <span class="required">*</span>
                </label>

                <textarea
                    id="otherCancellationReason"
                    rows="3"
                    placeholder="Enter cancellation reason..."
                ></textarea>

            </div>

        </div>


        <div class="cancel-modal-footer">

            <button
                type="button"
                id="keepAppointment"
                class="cancel-modal-secondary"
            >
                Keep Appointment
            </button>

            <button
                type="button"
                id="confirmCancelAppointment"
                class="cancel-modal-danger"
            >
                Cancel Appointment
            </button>

        </div>

    </div>

</div>

<!-- ===========================
     APPOINTMENT SUCCESS MODAL
=========================== -->

<div
    class="success-modal-overlay"
    id="appointmentSuccessModal">

    <div class="success-modal">

        <div class="success-icon">

            <i class="fa-solid fa-check"></i>

        </div>


        <h3>
            Appointment Booked Successfully!
        </h3>


        <p>
            The appointment has been successfully added
            to the appointment list.
        </p>


        <button
            type="button"
            id="successModalDone"
            class="success-modal-btn">

            Done

        </button>

    </div>

</div>

<!-- JS -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>

<script src="../assets/js/layout.js"></script>
<script>
    window.appointmentServiceCatalog = <?= $appointmentServiceCatalogJson ?>;
    window.appointmentSpeciesList = <?= $appointmentSpeciesJson ?>;
    window.appointmentBreedCatalog = <?= $appointmentBreedCatalogJson ?>;
</script>

<script src="../assets/js/appointments.js"></script>

</body>
</html>