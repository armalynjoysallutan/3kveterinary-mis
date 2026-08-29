<?php
session_start();

if (!isset($_SESSION["admin_username"])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once __DIR__ . '/../config/database.php';


/* =========================================================
   GET PETS FOR VACCINATION CERTIFICATES

   CUSTOMER RECORDS
        customers
             ↓
           pets
             ↓
      medical_records

   NOTE:
   QR certificate table will be connected in the next step.
   ========================================================= */

$vaccinationRows = [];

$sql = "
    SELECT
        p.pet_id,
        p.pet_name,
        p.species,
        p.breed,

        c.customer_id,
        c.owner_name,
        c.record_status,

        vc.certificate_id,
        vc.qr_token,
        vc.status AS qr_status,
        vc.generated_at,

        MAX(
            CASE
                WHEN mr.vaccination IS NOT NULL
                     AND TRIM(mr.vaccination) <> ''
                THEN mr.record_date
                ELSE NULL
            END
        ) AS last_vaccination

    FROM pets p

    INNER JOIN customers c
        ON c.customer_id = p.customer_id

    LEFT JOIN medical_records mr
        ON mr.pet_id = p.pet_id

    LEFT JOIN vaccination_certificates vc
        ON vc.pet_id = p.pet_id

    WHERE c.record_status = 'Active'

    GROUP BY
        p.pet_id,
        p.pet_name,
        p.species,
        p.breed,
        c.customer_id,
        c.owner_name,
        c.record_status,
        vc.certificate_id,
        vc.qr_token,
        vc.status,
        vc.generated_at

    ORDER BY
        p.pet_name ASC
";


$result = mysqli_query($conn, $sql);

if (!$result) {
    die(
        "Vaccination Certificate query failed: "
        . mysqli_error($conn)
    );
}


while ($row = mysqli_fetch_assoc($result)) {
    $vaccinationRows[] = $row;
}


/* =========================================================
   HELPER
   DATABASE PET ID
   1 → PET-001
   2 → PET-002
   ========================================================= */

function vaccinationPetId($petId)
{
    return 'PET-' . str_pad(
        (int) $petId,
        3,
        '0',
        STR_PAD_LEFT
    );
}


/* =========================================================
   HELPER
   FORMAT LAST VACCINATION DATE
   ========================================================= */

function vaccinationDate($date)
{
    if (empty($date)) {
        return '—';
    }

    return date(
        'M d, Y',
        strtotime($date)
    );
}


/* =========================================================
   ESCAPE OUTPUT
   ========================================================= */

function vaccinationEscape($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

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
        Vaccination Certificate | Veterinary MIS
    </title>


    <!-- =====================================================
         SHARED LAYOUT
    ====================================================== -->

    <link
        rel="stylesheet"
        href="../assets/css/layout.css"
    >


    <!-- =====================================================
         APPOINTMENT STYLE / SHARED COMPONENTS
    ====================================================== -->

    <link
        rel="stylesheet"
        href="../assets/css/appointments.css"
    >


    <!-- =====================================================
         VACCINATION CERTIFICATE STYLE
    ====================================================== -->

    <link
        rel="stylesheet"
        href="../assets/css/vaccination_certificates.css"
    >


    <!-- =====================================================
         FONT AWESOME
    ====================================================== -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
    >

</head>


<body>


<div class="container">


    <!-- =====================================================
         SIDEBAR
    ====================================================== -->

    <?php include 'partials/sidebar.php'; ?>


    <div
        class="sidebar-overlay"
        id="sidebarOverlay"
    ></div>


    <!-- =====================================================
         MAIN CONTENT
    ====================================================== -->

    <main
        class="content"
        id="mainContent"
    >


        <!-- =====================================================
             TOPBAR
        ====================================================== -->

        <?php

        $pageTitle = "Vaccination Certificate";
        $showAdminInfo = false;

        include "partials/topbar.php";

        ?>


        <div class="page-content">

            <div class="vaccination-certificate-content">


                <!-- =================================================
                     SEARCH + ACTIONS
                ================================================== -->

                <div class="toolbar-card">

                    <div class="toolbar-left">

                        <div class="search-box">

                            <i
                                class="fa-solid fa-magnifying-glass"
                            ></i>

                            <input
                                type="text"
                                id="vaccinationSearch"
                                placeholder="Search Pet ID, Pet Name or Owner..."
                                autocomplete="off"
                            >

                        </div>

                    </div>


                    <div class="toolbar-right">

                        <button
                            type="button"
                            class="add-btn"
                            id="generateCertificateBtn"
                        >

                            <i
                                class="fa-solid fa-qrcode"
                            ></i>

                            Generate QR Certificate

                        </button>

                    </div>

                </div>


                <!-- =================================================
                     PAGE INTRO
                ================================================== -->

                <div class="vaccination-info-card">

                    <div class="vaccination-info-icon">

                        <i
                            class="fa-solid fa-syringe"
                        ></i>

                    </div>


                    <div>

                        <h3>
                            Vaccination Certificates
                        </h3>

                        <p>
                            Generate a unique QR code for each pet
                            to provide access to its vaccination
                            and medical records.
                        </p>

                    </div>

                </div>


                <!-- =================================================
                     CERTIFICATE LIST
                ================================================== -->

                <div class="card vaccination-list-card">


                    <div class="section-header">

                        <div>

                            <h3>
                                Vaccination Certificate List
                            </h3>

                            <p>
                                Manage QR codes for pet vaccination certificates.
                            </p>

                        </div>

                    </div>


                    <div class="table-wrapper">


                        <table class="vaccination-table">


                            <thead>

                                <tr>

                                    <th>
                                        PET ID
                                    </th>

                                    <th>
                                        PET
                                    </th>

                                    <th>
                                        OWNER
                                    </th>

                                    <th>
                                        SPECIES / BREED
                                    </th>

                                    <th>
                                        LAST VACCINATION
                                    </th>

                                    <th>
                                        QR STATUS
                                    </th>

                                    <th>
                                        ACTIONS
                                    </th>

                                </tr>

                            </thead>


                            <tbody
                                id="vaccinationTableBody"
                            >


                                <?php if (empty($vaccinationRows)): ?>


                                    <tr>

                                        <td
                                            colspan="7"
                                            style="text-align:center;"
                                        >

                                            No active pets found.

                                        </td>

                                    </tr>


                                <?php else: ?>


                                    <?php foreach ($vaccinationRows as $row): ?>


                                        <?php

                                        $petId =
                                            (int) $row["pet_id"];

                                        $displayPetId =
                                            vaccinationPetId($petId);

                                        $petName =
                                            $row["pet_name"] ?? '';

                                        $ownerName =
                                            $row["owner_name"] ?? '';

                                        $species =
                                            $row["species"] ?? '';

                                        $breed =
                                            $row["breed"] ?? '';

                                        $lastVaccination =
                                            vaccinationDate(
                                                $row["last_vaccination"]
                                                    ?? null
                                            );

                                        $searchData =
                                            strtolower(
                                                implode(
                                                    ' ',
                                                    [
                                                        $displayPetId,
                                                        $petName,
                                                        $ownerName,
                                                        $species,
                                                        $breed
                                                    ]
                                                )
                                            );

                                        ?>


                                        <tr
                                            class="vaccination-row"
                                            data-search="<?= vaccinationEscape($searchData) ?>"
                                        >


                                            <!-- PET ID -->

                                            <td>

                                                <span class="pet-id">

                                                    <?= vaccinationEscape(
                                                        $displayPetId
                                                    ) ?>

                                                </span>

                                            </td>


                                            <!-- PET -->

                                            <td>

                                                <strong>

                                                    <?= vaccinationEscape(
                                                        $petName
                                                    ) ?>

                                                </strong>

                                            </td>


                                            <!-- OWNER -->

                                            <td>

                                                <?= vaccinationEscape(
                                                    $ownerName
                                                ) ?>

                                            </td>


                                            <!-- SPECIES / BREED -->

                                            <td>

                                                <strong>

                                                    <?= vaccinationEscape(
                                                        ucfirst($species)
                                                    ) ?>

                                                </strong>


                                                <?php if (!empty($breed)): ?>

                                                    <br>

                                                    <small>

                                                        <?= vaccinationEscape(
                                                            $breed
                                                        ) ?>

                                                    </small>

                                                <?php endif; ?>

                                            </td>


                                            <!-- LAST VACCINATION -->

                                            <td>

                                                <?= vaccinationEscape(
                                                    $lastVaccination
                                                ) ?>

                                            </td>


                                            <!-- QR STATUS -->

                                            <td>

                                                <?php
                                                $qrStatus =
                                                    $row["qr_status"]
                                                        ?? "Not Generated";

                                                $isGenerated =
                                                    $qrStatus === "Generated";
                                                ?>

                                                <?php if ($isGenerated): ?>

                                                    <span
                                                        class="qr-status generated"
                                                    >

                                                        <i
                                                            class="fa-solid fa-circle-check"
                                                        ></i>

                                                        Generated

                                                    </span>

                                                <?php else: ?>

                                                    <span
                                                        class="qr-status not-generated"
                                                    >

                                                        <i
                                                            class="fa-solid fa-circle-minus"
                                                        ></i>

                                                        Not Generated

                                                    </span>

                                                <?php endif; ?>

                                            </td>


                                            <!-- ACTIONS -->

                                            <td>

                                                <div
                                                    class="certificate-actions"
                                                >

                                                    <?php if ($isGenerated): ?>

                                                        <button
                                                            type="button"
                                                            class="action-btn view"
                                                            data-action="view"
                                                            data-pet-id="<?= vaccinationEscape($displayPetId) ?>"
                                                            data-db-pet-id="<?= $petId ?>"
                                                            data-pet-name="<?= vaccinationEscape($petName) ?>"
                                                            data-owner="<?= vaccinationEscape($ownerName) ?>"
                                                            data-qr-token="<?= vaccinationEscape($row["qr_token"] ?? "") ?>"
                                                            title="View QR Certificate"
                                                        >

                                                            <i
                                                                class="fa-regular fa-eye"
                                                            ></i>

                                                        </button>

                                                        <button
                                                            type="button"
                                                            class="action-btn print"
                                                            data-action="print"
                                                            data-pet-id="<?= vaccinationEscape($displayPetId) ?>"
                                                            data-db-pet-id="<?= $petId ?>"
                                                            data-pet-name="<?= vaccinationEscape($petName) ?>"
                                                            data-owner="<?= vaccinationEscape($ownerName) ?>"
                                                            data-qr-token="<?= vaccinationEscape($row["qr_token"] ?? "") ?>"
                                                            title="Print QR Code"
                                                        >

                                                            <i
                                                                class="fa-solid fa-print"
                                                            ></i>

                                                        </button>

                                                    <?php else: ?>

                                                        <button
                                                            type="button"
                                                            class="action-btn generate"
                                                            data-action="generate"
                                                            data-pet-id="<?= vaccinationEscape($displayPetId) ?>"
                                                            data-db-pet-id="<?= $petId ?>"
                                                            data-pet-name="<?= vaccinationEscape($petName) ?>"
                                                            data-owner="<?= vaccinationEscape($ownerName) ?>"
                                                            title="Generate QR Certificate"
                                                        >

                                                            <i
                                                                class="fa-solid fa-qrcode"
                                                            ></i>

                                                        </button>

                                                    <?php endif; ?>

                                                </div>

                                            </td>


                                        </tr>


                                    <?php endforeach; ?>


                                <?php endif; ?>


                            </tbody>


                        </table>


                        <!-- =================================================
                             EMPTY SEARCH RESULT
                        ================================================== -->

                        <div
                            class="vaccination-empty"
                            id="vaccinationEmpty"
                            style="display:none;"
                        >

                            <i
                                class="fa-solid fa-syringe"
                            ></i>

                            <h4>
                                No vaccination certificates found
                            </h4>

                            <p>
                                Try searching using another pet,
                                owner, or Pet ID.
                            </p>

                        </div>


                    </div>

                </div>


            </div>

        </div>


    </main>


</div>



<!-- =========================================================
     VIEW QR CERTIFICATE MODAL
========================================================= -->

<div
    class="vaccination-modal-overlay"
    id="viewCertificateModal"
>


    <div
        class="vaccination-modal"
        role="dialog"
        aria-modal="true"
    >


        <!-- =================================================
             MODAL HEADER
        ================================================== -->

        <div class="vaccination-modal-header">


            <div>

                <h2>
                    Vaccination QR Certificate
                </h2>

                <p>
                    QR code assigned to this pet.
                </p>

            </div>


            <button
                type="button"
                class="modal-close-btn"
                id="closeCertificateModal"
            >

                <i
                    class="fa-solid fa-xmark"
                ></i>

            </button>


        </div>



        <!-- =================================================
             MODAL BODY
        ================================================== -->

        <div class="vaccination-modal-body">


            <div class="certificate-preview">


                <div class="clinic-name">
                    3K PET SOLUTION
                </div>


                <div class="clinic-subtitle">
                    ANIMAL CLINIC
                </div>


                <h3>
                    Vaccination Record QR
                </h3>


                <!-- =================================================
                     QR PLACEHOLDER

                     JS WILL REPLACE THIS WHEN QR IS GENERATED
                ================================================== -->

                <div
                    class="qr-placeholder"
                    id="qrPlaceholder"
                >

                    <i
                        class="fa-solid fa-qrcode"
                    ></i>

                </div>


                <!-- =================================================
                     PET INFORMATION
                ================================================== -->

                <div class="certificate-pet-info">


                    <div>

                        <span>
                            PET ID
                        </span>

                        <strong
                            id="modalPetId"
                        >
                            —
                        </strong>

                    </div>


                    <div>

                        <span>
                            PET NAME
                        </span>

                        <strong
                            id="modalPetName"
                        >
                            —
                        </strong>

                    </div>


                    <div>

                        <span>
                            OWNER
                        </span>

                        <strong
                            id="modalOwner"
                        >
                            —
                        </strong>

                    </div>


                </div>


                <p class="qr-instruction">

                    Scan this QR code to view the pet's
                    vaccination and medical records.

                </p>


            </div>


        </div>



        <!-- =================================================
             MODAL FOOTER
        ================================================== -->

        <div class="vaccination-modal-footer">


            <button
                type="button"
                class="modal-cancel-btn"
                id="cancelCertificateModal"
            >

                Close

            </button>


            <button
                type="button"
                class="modal-save-btn"
                id="printCertificateBtn"
            >

                <i
                    class="fa-solid fa-print"
                ></i>

                Print QR Code

            </button>


        </div>


    </div>


</div>



<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script
    src="../assets/js/layout.js"
></script>


<script
    src="../assets/js/vaccination_certificates.js"
></script>


</body>

</html>