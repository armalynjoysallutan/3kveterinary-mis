<?php
session_start();

if (!isset($_SESSION["admin_username"])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once "../config/database.php";

$petId = isset($_GET["pet_id"]) 
    ? (int) $_GET["pet_id"] 
    : 0;

$appointmentId = isset($_GET["appointment_id"])
    ? (int) $_GET["appointment_id"]
    : 0;

$isModal = isset($_GET["modal"]) && $_GET["modal"] === "1";    

if ($petId <= 0) {
    die("Invalid pet ID.");
}

/* =========================================================
   PET + OWNER INFORMATION
   ========================================================= */
$petSql = "
    SELECT
        p.pet_id,
        p.customer_id,
        p.pet_name,
        p.species,
        p.breed,
        p.color,
        p.gender,
        p.weight,
        p.date_of_birth,

        c.owner_name,
        c.address,
        c.contact_number,
        c.email

    FROM pets p

    INNER JOIN customers c
        ON c.customer_id = p.customer_id

    WHERE p.pet_id = ?

    LIMIT 1
";

$petStmt = mysqli_prepare($conn, $petSql);

if (!$petStmt) {
    die("Pet query failed: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($petStmt, "i", $petId);
mysqli_stmt_execute($petStmt);

$petResult = mysqli_stmt_get_result($petStmt);
$pet = mysqli_fetch_assoc($petResult);

mysqli_stmt_close($petStmt);

if (!$pet) {
    die("Pet record not found.");
}

/* =========================================================
   SELECTED APPOINTMENT
   If this page was opened from an appointment, use that
   exact appointment instead of guessing the latest one.
   ========================================================= */
$selectedAppointment = null;

if ($appointmentId > 0) {
    $selectedSql = "
        SELECT
            appointment_id,
            pet_id,
            service_category,
            service,
            appointment_date,
            reason
        FROM appointments
        WHERE appointment_id = ?
          AND pet_id = ?
          AND is_archived = 0
        LIMIT 1
    ";

    $selectedStmt = mysqli_prepare($conn, $selectedSql);

    if ($selectedStmt) {
        mysqli_stmt_bind_param($selectedStmt, "ii", $appointmentId, $petId);
        mysqli_stmt_execute($selectedStmt);

        $selectedResult = mysqli_stmt_get_result($selectedStmt);
        $selectedAppointment = mysqli_fetch_assoc($selectedResult);

        mysqli_stmt_close($selectedStmt);
    }
}

/* =========================================================
   MEDICAL RECORD HISTORY
   ========================================================= */
$history = [];

$historySql = "
    SELECT
        mr.medical_record_id,
        mr.record_date,
        mr.weight,
        mr.temperature,
        mr.diagnosis,
        mr.treatment,
        mr.vaccination,
        mr.amount_paid,
        mr.next_visit,
        mr.no_days_return,

        a.service_category,
        a.service,
        a.reason,

        b.payment_status,
        b.billing_id

    FROM medical_records mr

    LEFT JOIN appointments a
        ON a.appointment_id = mr.appointment_id

    LEFT JOIN billing b
        ON b.billing_id = mr.billing_id

    WHERE mr.pet_id = ?

    ORDER BY
        mr.record_date DESC,
        mr.medical_record_id DESC
";

$historyStmt = mysqli_prepare($conn, $historySql);

if ($historyStmt) {
    mysqli_stmt_bind_param($historyStmt, "i", $petId);
    mysqli_stmt_execute($historyStmt);

    $historyResult = mysqli_stmt_get_result($historyStmt);

    while ($row = mysqli_fetch_assoc($historyResult)) {
        $history[] = $row;
    }

    mysqli_stmt_close($historyStmt);
}

/* =========================================================
   LATEST COMPLETED APPOINTMENT
   Used when creating a new medical record so vaccination
   information can be carried from the appointment.
   ========================================================= */
$latestAppointment = null;

$latestSql = "
    SELECT
        appointment_id,
        service_category,
        service,
        appointment_date,
        reason
    FROM appointments
    WHERE pet_id = ?
      AND status = 'Completed'
      AND is_archived = 0
    ORDER BY appointment_date DESC, appointment_time DESC
    LIMIT 1
";

$latestStmt = mysqli_prepare($conn, $latestSql);

if ($latestStmt) {
    mysqli_stmt_bind_param($latestStmt, "i", $petId);
    mysqli_stmt_execute($latestStmt);

    $latestResult = mysqli_stmt_get_result($latestStmt);
    $latestAppointment = mysqli_fetch_assoc($latestResult);

    mysqli_stmt_close($latestStmt);
}

// Use the appointment passed from Appointment module when available.
$activeAppointment = $selectedAppointment ?: $latestAppointment;

/* =========================================================
   NEXT SCHEDULED VISIT
   ========================================================= */
$nextVisit = null;

$nextSql = "
    SELECT
        appointment_id,
        appointment_date,
        appointment_time,
        service,
        service_category
    FROM appointments
    WHERE pet_id = ?
      AND is_archived = 0
      AND appointment_date >= CURDATE()
      AND status IN ('Pending', 'Confirmed', 'Arrived')
    ORDER BY appointment_date ASC, appointment_time ASC
    LIMIT 1
";

$nextStmt = mysqli_prepare($conn, $nextSql);

if ($nextStmt) {
    mysqli_stmt_bind_param($nextStmt, "i", $petId);
    mysqli_stmt_execute($nextStmt);

    $nextResult = mysqli_stmt_get_result($nextStmt);
    $nextVisit = mysqli_fetch_assoc($nextResult);

    mysqli_stmt_close($nextStmt);
}

$initial = strtoupper(substr(trim($pet["pet_name"]), 0, 1));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        View Pet Record | Veterinary MIS
    </title>

    <link
        rel="stylesheet"
        href="../assets/css/layout.css"
    >

    <link
        rel="stylesheet"
        href="../assets/css/customer_records.css"
    >

    <link
        rel="stylesheet"
        href="../assets/css/view_pet_record.css"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
    >
</head>
    <style>
        .medical-record-actions {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .medical-record-actions .record-action-btn {
            width: 34px;
            height: 34px;
            min-width: 34px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }

        .medical-record-actions .billing-action-btn {
            border-color: #22a7e8;
            color: #1597d4;
        }

        .medical-record-actions .billing-action-btn:hover {
            background: #eef8ff;
        }

        .medical-record-actions .create-billing-btn.is-loading {
            opacity: .65;
            pointer-events: none;
        }
    </style>


<body>

<div class="container">

    <?php include "partials/sidebar.php"; ?>

    <div
        class="sidebar-overlay"
        id="sidebarOverlay"
    ></div>

    <main
        class="content"
        id="mainContent"
    >

        <?php
        $pageTitle = "Customer Records";
        $showAdminInfo = false;

        include "partials/topbar.php";
        ?>

        <div class="page-content">

            <div class="view-record-page">

                <div class="view-record-toolbar">

                    <a
                        href="customer_records.php"
                        class="back-record-btn"
                    >
                        <i class="fa-solid fa-arrow-left"></i>
                        Back to Customer Records
                    </a>

                </div>


                <!-- =========================================
                     OWNER + PET INFORMATION
                     ========================================= -->

                <div class="record-info-grid">

                    <section class="record-info-card">

                        <div class="record-section-title">
                            <i class="fa-regular fa-user"></i>
                            Owner Information
                        </div>

                        <div class="record-info-body">

                            <div class="record-field">
                                <span>Name of Owner</span>
                                <strong>
                                    <?= htmlspecialchars($pet["owner_name"]) ?>
                                </strong>
                            </div>

                            <div class="record-field">
                                <span>Address</span>
                                <strong>
                                    <?= htmlspecialchars(
                                        $pet["address"] ?: "Not provided"
                                    ) ?>
                                </strong>
                            </div>

                            <div class="record-field">
                                <span>Email Address</span>
                                <strong>
                                    <?= htmlspecialchars(
                                        $pet["email"] ?: "Not provided"
                                    ) ?>
                                </strong>
                            </div>

                            <div class="record-field">
                                <span>Phone/CP #</span>
                                <strong>
                                    <?= htmlspecialchars(
                                        $pet["contact_number"] ?: "Not provided"
                                    ) ?>
                                </strong>
                            </div>

                        </div>

                    </section>


                    <section class="record-info-card">

                        <div class="record-section-title">
                            <i class="fa-solid fa-paw"></i>
                            Pet Information
                        </div>

                        <div class="record-pet-body">

                            <div class="record-pet-avatar">
                                <?= htmlspecialchars($initial) ?>
                            </div>

                            <div class="record-pet-details">

                                <div class="record-field">
                                    <span>Name of Pet</span>
                                    <strong>
                                        <?= htmlspecialchars($pet["pet_name"]) ?>
                                    </strong>
                                </div>

                                <div class="record-field">
                                    <span>Breed</span>
                                    <strong>
                                        <?= htmlspecialchars(
                                            $pet["breed"] ?: "Not specified"
                                        ) ?>
                                    </strong>
                                </div>

                                <div class="record-field">
                                    <span>Species</span>
                                    <strong>
                                        <?= htmlspecialchars(
                                            ucfirst($pet["species"])
                                        ) ?>
                                    </strong>
                                </div>

                                <div class="record-field">
                                    <span>Sex</span>
                                    <strong>
                                        <?= htmlspecialchars(
                                            $pet["gender"] ?: "Not specified"
                                        ) ?>
                                    </strong>
                                </div>

                                <div class="record-field">
                                    <span>Color</span>
                                    <strong>
                                        <?= htmlspecialchars(
                                            $pet["color"] ?: "Not specified"
                                        ) ?>
                                    </strong>
                                </div>

                                <div class="record-field">
                                    <span>Date of Birth</span>
                                    <strong>
                                        <?= !empty($pet["date_of_birth"])
                                            ? date("m/Y", strtotime($pet["date_of_birth"]))
                                            : "Not specified"
                                        ?>
                                    </strong>
                                </div>

                            </div>

                        </div>

                    </section>

                </div>


                <!-- =========================================
                     MEDICAL RECORD HISTORY
                     ========================================= -->

                <section class="medical-history-card">

                    <div class="medical-history-header">

                        <div>
                            <h2>
                                <i class="fa-regular fa-file-lines"></i>
                                Medical Record History
                            </h2>

                            <p>
                                Visit history, clinical findings and billing information.
                            </p>
                        </div>

                        <button
                            type="button"
                            class="add-medical-record-btn"
                            id="addMedicalRecordBtn"
                            data-pet-id="<?= $petId ?>"
                            data-appointment-id="<?= (int) ($activeAppointment["appointment_id"] ?? 0) ?>"
                            data-vaccination="<?= htmlspecialchars(
                                (
                                    isset($activeAppointment["service_category"])
                                    && strtolower($activeAppointment["service_category"]) === "vaccination"
                                )
                                ? ($activeAppointment["service"] ?? "")
                                : ""
                            ) ?>"
                        >
                            <i class="fa-solid fa-plus"></i>
                            Add New Record
                        </button>

                    

                    </div>


                    <div class="medical-history-table-wrap">

                        <table class="medical-history-table">

                            <thead>

                                <tr>
                                    <th>DATE</th>
                                    <th>WT.(KG)</th>
                                    <th>TEMP</th>
                                    <th>DX</th>
                                    <th>TX</th>
                                    <th>VX</th>
                                    <th>NEXT VISIT</th>
                                    <th>NO. DAYS OF RETURN</th>
                                    <th>ACTIONS</th>
                                </tr>

                            </thead>

                            <tbody>

                            <?php if (empty($history)): ?>

                                <tr>
                                    <td
                                        colspan="10"
                                        class="medical-empty"
                                    >
                                        <i class="fa-regular fa-file-lines"></i>

                                        <strong>
                                            No medical records yet
                                        </strong>

                                        <span>
                                            Add the pet's first medical record using
                                            the button above.
                                        </span>
                                    </td>
                                </tr>

                            <?php else: ?>

                                <?php foreach ($history as $record): ?>

                                    <tr>

                                        <td>
                                            <?= date(
                                                "m/d/Y",
                                                strtotime($record["record_date"])
                                            ) ?>
                                        </td>

                                        <td>
                                            <?= $record["weight"] !== null
                                                ? number_format(
                                                    (float) $record["weight"],
                                                    2
                                                ) . " kg"
                                                : "—"
                                            ?>
                                        </td>

                                        <td>
                                            <?= $record["temperature"] !== null
                                                ? number_format(
                                                    (float) $record["temperature"],
                                                    1
                                                ) . " °C"
                                                : "—"
                                            ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars(
                                                $record["diagnosis"] ?: "—"
                                            ) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars(
                                                $record["treatment"] ?: "—" 
                                            ) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars(
                                                $record["vaccination"] ?: "—"
                                            ) ?>
                                        </td>

                                        <td>
                                            <?= !empty($record["next_visit"])
                                                ? date(
                                                    "m/d/Y",
                                                    strtotime($record["next_visit"])
                                                )
                                                : "—"
                                            ?>
                                        </td>

                                        <td>
                                            <?= $record["no_days_return"] !== null
                                                ? (int) $record["no_days_return"] . " days"
                                                : "—"
                                            ?>
                                        </td>

                                        <td>

                                            <div class="medical-record-actions">

                                                <button
                                                    type="button"
                                                    class="record-action-btn"
                                                    title="View Record"
                                                    data-record-id="<?= (int) $record["medical_record_id"] ?>"
                                                >
                                                    <i class="fa-regular fa-eye"></i>
                                                </button>

                                                <?php if (!empty($record["billing_id"])): ?>

                                                    <a
                                                        href="billing_statement.php?billing_id=<?= (int) $record["billing_id"] ?>"
                                                        class="record-action-btn billing-action-btn"
                                                        title="View Billing"
                                                    >
                                                        <i class="fa-solid fa-file-invoice-dollar"></i>
                                                    </a>

                                                <?php else: ?>

                                                    <button
                                                        type="button"
                                                        class="record-action-btn billing-action-btn create-billing-btn"
                                                        title="Create Billing"
                                                        data-record-id="<?= (int) $record["medical_record_id"] ?>"
                                                        onclick="event.preventDefault(); event.stopImmediatePropagation(); createBillingFromMedicalRecord(this);"
                                                    >
                                                        <i class="fa-solid fa-file-invoice-dollar"></i>
                                                    </button>

                                                <?php endif; ?>

                                            </div>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            <?php endif; ?>

                            </tbody>

                        </table>

                    </div>

                </section>

                <!-- =========================================================
     ADD NEW MEDICAL RECORD MODAL
     ========================================================= -->

<div
    class="medical-record-modal"
    id="medicalRecordModal"
    aria-hidden="true"
>

    <div
        class="medical-record-modal-backdrop"
        id="medicalRecordModalBackdrop"
    ></div>

    <div class="medical-record-modal-dialog">

        <div class="medical-record-modal-header">

            <div>
                <h2>Add New Medical Record</h2>
                <p>Enter the pet's current visit findings.</p>
            </div>

            <button
                type="button"
                class="medical-modal-close"
                id="closeMedicalRecordModal"
            >
                <i class="fa-solid fa-xmark"></i>
            </button>

        </div>


        <form
            id="medicalRecordForm"
            autocomplete="off"
        >

            <input
                type="hidden"
                name="pet_id"
                value="<?= $petId ?>"
            >

            <input
                type="hidden"
                name="appointment_id"
                id="medicalAppointmentId"
                value="<?= (int) ($activeAppointment["appointment_id"] ?? 0) ?>"
            >


            <div class="medical-form-grid">

                <!-- VISIT DATE -->
                <div class="medical-form-group">

                    <label>
                        Visit Date
                    </label>

                    <input
                        type="date"
                        name="record_date"
                        id="medicalRecordDate"
                        value="<?= date('Y-m-d') ?>"
                        readonly
                    >

                </div>


                <!-- WEIGHT -->
                <div class="medical-form-group">

                    <label>
                        Weight (kg)
                        <span>*</span>
                    </label>

                    <input
                        type="number"
                        name="weight"
                        step="0.01"
                        min="0"
                        placeholder="Enter weight"
                        required
                    >

                </div>


                <!-- TEMPERATURE -->
                <div class="medical-form-group">

                    <label>
                        Temperature (°C)
                        <span>*</span>
                    </label>

                    <input
                        type="number"
                        name="temperature"
                        step="0.1"
                        min="0"
                        placeholder="e.g. 38.5"
                        required
                    >

                </div>


                <!-- AMOUNT PAID -->
                <div class="medical-form-group">

                    <label>
                        Amount Paid
                    </label>

                    <input
                        type="text"
                        value="Automatically linked from Billing"
                        readonly
                    >

                    <small>
                        Amount paid comes from the completed billing record.
                    </small>

                </div>


                <!-- DIAGNOSIS - OPTIONAL -->
                <div class="medical-form-group full">

                    <label>
                        Diagnosis (DX)
                    </label>

                    <textarea
                        name="diagnosis"
                        rows="3"
                        placeholder="Enter diagnosis..."
                    ></textarea>

                </div>


                <!-- TREATMENT - OPTIONAL -->
                <div class="medical-form-group full">

                    <label>
                        Treatment / Procedure (TX)
                    </label>

                    <textarea
                        name="treatment"
                        rows="3"
                        placeholder="Enter treatment / procedure..."
                    ></textarea>

                </div>


                <!-- SERVICES PERFORMED -->
                <div class="medical-form-group full">

                    <label>
                        Services Performed
                    </label>

                    <div
    id="selectedServices"
    class="selected-services"
>

    <?php if (
        !empty($activeAppointment) &&
        !empty($activeAppointment["service"])
    ): ?>

        <div
            class="selected-service-item"
            data-source="appointment"
        >

            <div class="selected-service-info">

                <strong>
                    <?= htmlspecialchars(
                        $activeAppointment["service"]
                    ) ?>
                </strong>

                <small>
                    <?= htmlspecialchars(
                        $activeAppointment["service_category"]
                    ) ?>
                </small>

            </div>

            <span class="service-source-badge">
                Appointment
            </span>

        </div>

    <?php else: ?>

        <div class="no-services">
            No services performed yet.
        </div>

    <?php endif; ?>

</div>

                    <button
                        type="button"
                        id="openAddServiceModal"
                        class="add-service-inside-btn"
                    >
                        <i class="fa-solid fa-plus"></i>
                        Add Service
                    </button>

                </div>


                <!-- VACCINATION -->
                <div class="medical-form-group full">

                    <label>
                        Vaccination (VX)
                    </label>

                    <input
                        type="text"
                        name="vaccination"
                        id="medicalVaccination"
                        value="<?= htmlspecialchars(
                            (
                                isset($activeAppointment["service_category"])
                                && strtolower($activeAppointment["service_category"]) === "vaccination"
                            )
                            ? ($activeAppointment["service"] ?? "")
                            : ""
                        ) ?>"
                        placeholder="Automatically filled when applicable"
                        readonly
                    >

                    <small>
                        Vaccination information comes from the completed appointment.
                    </small>

                </div>


                <!-- NEXT VISIT - REQUIRED -->
                <div class="medical-form-group">

                    <label>
                        Next Visit
                        <span>*</span>
                    </label>

                    <input
                        type="date"
                        name="next_visit"
                        id="nextVisit"
                        required
                    >

                </div>


                <!-- DAYS OF RETURN -->
                <div class="medical-form-group">

                    <label>
                        No. of Days of Return
                    </label>

                    <input
                        type="number"
                        name="no_days_return"
                        id="noDaysReturn"
                        min="0"
                        readonly
                        placeholder="Automatically calculated"
                    >

                    <small>
                        Automatically calculated from Visit Date and Next Visit.
                    </small>

                </div>

            </div>


            <!-- BUTTONS -->
            <div class="medical-record-modal-actions">

                <button
                    type="button"
                    class="medical-cancel-btn"
                    id="cancelMedicalRecord"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="medical-record-save-btn"
                    id="saveMedicalRecord"
                >
                    <i class="fa-solid fa-check"></i>
                    Save Record
                </button>

            </div>

        </form>

    </div>

</div>


            </div>

        </div>

    </main>

</div>


<!-- =========================
     ADD SERVICE MODAL
     ========================= -->

<div
    id="addServiceModal"
    class="service-modal-overlay"
    aria-hidden="true"
>

    <div class="service-modal">

        <!-- MODAL HEADER -->
        <div class="service-modal-header">

            <div>
                <h2>Add Service</h2>

                <p>
                    Add an additional service to this medical record.
                </p>
            </div>

            <button
                type="button"
                class="service-modal-close"
                id="closeAddServiceModal"
            >
                &times;
            </button>

        </div>


        <!-- MODAL BODY -->
        <div class="service-modal-body">

            <!-- SERVICE CATEGORY -->
            <div class="service-form-group">

                <label for="serviceCategory">
                    Service Category
                </label>

                <select
                    id="serviceCategory"
                >

                    <option value="">
                        Select Category
                    </option>

                    <?php foreach ($serviceCategories as $category): ?>

                        <option
                            value="<?= htmlspecialchars($category) ?>"
                        >
                            <?= htmlspecialchars($category) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- SERVICE -->
            <div class="service-form-group">

                <label for="serviceName">
                    Service
                </label>

                <select
                    id="serviceName"
                    disabled
                >

                    <option value="">
                        Select Category First
                    </option>

                    <?php foreach ($serviceCatalog as $serviceRow): ?>

                        <option
                            value="<?= (int) $serviceRow["service_id"] ?>"
                            data-category="<?= htmlspecialchars($serviceRow["category_name"]) ?>"
                            data-service-name="<?= htmlspecialchars($serviceRow["service_name"]) ?>"
                            data-pricing-type="<?= htmlspecialchars($serviceRow["pricing_type"]) ?>"
                            data-fixed-price="<?= htmlspecialchars($serviceRow["fixed_price"] ?? "") ?>"
                        >
                            <?= htmlspecialchars($serviceRow["service_name"]) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- PET WEIGHT + QUANTITY -->
            <div class="service-form-row">

                <div class="service-form-group">

                    <label for="servicePetWeight">
                        Pet Weight
                        <span>*</span>
                    </label>

                    <div class="input-with-unit">

                        <input
                            type="number"
                            id="servicePetWeight"
                            step="0.01"
                            min="0"
                            placeholder="Enter pet weight"
                        >

                        <span>kg</span>

                    </div>

                </div>


                <div class="service-form-group">

                    <label for="serviceQuantity">
                        Quantity
                    </label>

                    <input
                        type="number"
                        id="serviceQuantity"
                        value="1"
                        min="1"
                        step="1"
                    >

                </div>

            </div>

            <!-- NEXT VISIT -->
            <div class="service-form-row">

                <div class="service-form-group">
                    <label for="serviceNextVisit">
                        Next Visit
                        <span>*</span>
                    </label>
                </div>

                <input
                    type="date"
                    id="serviceNextVisit"
                >
            </div>

            <div class="service-form-group">

                <label for="serviceNoDaysReturn">
                
                    Days of Return
                </label>
                
                <input
                    type="number"
                    id="serviceNoDaysReturn"
                    min="0"
                    readonly
                    placeholder="Automatically calculated"
                >    
            </div>
            
        </div>


        <!-- MODAL FOOTER -->
        <div class="service-modal-footer">

            <button
                type="button"
                class="service-btn service-btn-cancel"
                id="cancelAddService"
            >
                Cancel
            </button>

            <button
                type="button"
                class="service-btn service-btn-primary"
                id="confirmAddService"
            >
                <i class="fa-solid fa-plus"></i>
                Add Service
            </button>

        </div>

    </div>

</div>

<script src="../assets/js/layout.js"></script>

<script src="../assets/js/customer_records.js"></script>

<script src="../assets/js/view_pet_record.js"></script>

<!-- =========================================================
     SYSTEM MESSAGE MODAL
========================================================= -->

<div
    id="systemMessageModal"
    class="system-message-modal"
    aria-hidden="true"
>
    <div
        class="system-message-backdrop"
        id="systemMessageBackdrop"
    ></div>

    <div
        class="system-message-dialog"
        role="dialog"
        aria-modal="true"
        aria-labelledby="systemMessageTitle"
    >

        <div class="system-message-header">
            <div class="system-message-icon">
                <i class="fa-solid fa-circle-exclamation"></i>
            </div>

            <div>
                <h3 id="systemMessageTitle">
                    System Message
                </h3>
            </div>
        </div>

        <div class="system-message-body">
            <p id="systemMessageText">
                Please select a service category.
            </p>
        </div>

        <div class="system-message-footer">
            <button
                type="button"
                id="systemMessageOk"
                class="system-message-ok-btn"
            >
                OK
            </button>
        </div>

    </div>
</div>

<!-- =========================================================
     SYSTEM CONFIRMATION MODAL
========================================================= -->

<div
    id="systemConfirmModal"
    class="system-confirm-modal"
    aria-hidden="true"
>
    <div
        class="system-confirm-backdrop"
        id="systemConfirmBackdrop"
    ></div>

    <div
        class="system-confirm-dialog"
        role="dialog"
        aria-modal="true"
        aria-labelledby="systemConfirmTitle"
    >

        <div class="system-confirm-icon">
            <i class="fa-solid fa-circle-question"></i>
        </div>

        <h3 id="systemConfirmTitle">
            Confirm Action
        </h3>

        <p id="systemConfirmText">
            Are you sure?
        </p>

        <div class="system-confirm-actions">

            <button
                type="button"
                id="systemConfirmCancel"
                class="system-confirm-cancel"
            >
                Cancel
            </button>

            <button
                type="button"
                id="systemConfirmOk"
                class="system-confirm-ok"
            >
                Confirm
            </button>

        </div>

    </div>
</div>

<script>

function showSystemConfirm(message) {

    return new Promise(function (resolve) {

        const modal =
            document.getElementById("systemConfirmModal");

        const messageText =
            document.getElementById("systemConfirmText");

        const confirmButton =
            document.getElementById("systemConfirmOk");

        const cancelButton =
            document.getElementById("systemConfirmCancel");

        const backdrop =
            document.getElementById("systemConfirmBackdrop");

        if (
            !modal ||
            !messageText ||
            !confirmButton ||
            !cancelButton
        ) {
            resolve(false);
            return;
        }

        messageText.textContent = message;

        modal.classList.add("open");
        modal.setAttribute("aria-hidden", "false");

        function cleanup(result) {

            modal.classList.remove("open");

            modal.setAttribute(
                "aria-hidden",
                "true"
            );

            confirmButton.removeEventListener(
                "click",
                handleConfirm
            );

            cancelButton.removeEventListener(
                "click",
                handleCancel
            );

            if (backdrop) {
                backdrop.removeEventListener(
                    "click",
                    handleCancel
                );
            }

            document.removeEventListener(
                "keydown",
                handleEscape
            );

            resolve(result);
        }

        function handleConfirm() {
            cleanup(true);
        }

        function handleCancel() {
            cleanup(false);
        }

        function handleEscape(event) {

            if (event.key === "Escape") {
                cleanup(false);
            }

        }

        confirmButton.addEventListener(
            "click",
            handleConfirm
        );

        cancelButton.addEventListener(
            "click",
            handleCancel
        );

        if (backdrop) {
            backdrop.addEventListener(
                "click",
                handleCancel
            );
        }

        document.addEventListener(
            "keydown",
            handleEscape
        );

    });
}

async function createBillingFromMedicalRecord(button) {
    const medicalRecordId = button.dataset.recordId;

    if (!medicalRecordId) {
        window.showSystemMessage(
            "Medical record ID is missing."
        );
        return;
    }

    const confirmed = await showSystemConfirm(
    "Are you sure you want to create billing for this medical record?"
    );

    if (!confirmed) {
        return;
    }

   

    const originalHtml = button.innerHTML;
    button.disabled = true;
    button.classList.add("is-loading");
    button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

    try {
        const response = await fetch(
            "../process/create_billing_from_medical_record.php",
            {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"
                },
                body: new URLSearchParams({
                    medical_record_id: medicalRecordId
                })
            }
        );

        const raw = await response.text();
        let result;

        try {
            result = JSON.parse(raw);
        } catch (e) {
            console.error("Create Billing raw response:", raw);
            throw new Error("Server returned an invalid response.");
        }

        if (!result.success) {
            throw new Error(result.message || "Unable to create billing.");
        }

        if (!result.billing_id) {
            throw new Error("Billing was created but no billing ID was returned.");
        }

        window.location.href =
            "billing_statement.php?billing_id=" +
            encodeURIComponent(result.billing_id);

    } catch (error) {
        console.error("Create Billing Error:", error);
        window.showSystemMessage(
            error.message || 
            "Something went wrong while creating the billing."
        );
        button.disabled = false;
        button.classList.remove("is-loading");
        button.innerHTML = originalHtml;
    }
}
</script>

</body>
</html>