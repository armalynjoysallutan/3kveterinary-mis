<?php

session_start();

if (!isset($_SESSION["admin_username"])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once "../config/database.php";
require_once "../config/audit_log.php";


/* =========================================================
   RESTORE ARCHIVED CUSTOMER(S)
   - Restores the customer record to Active.
   - Associated pets remain linked through customer_id, so
     they automatically become available again in Customer Records.
   - Supports single restore and multiple restore.
   ========================================================= */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"])) {

    $action = $_POST["action"];

    if ($action === "restore_customer") {

        $customerIds = [];

        if (isset($_POST["customer_ids"]) && is_array($_POST["customer_ids"])) {
            foreach ($_POST["customer_ids"] as $id) {
                $id = (int) $id;
                if ($id > 0) {
                    $customerIds[] = $id;
                }
            }
        }

        if (isset($_POST["customer_id"])) {
            $id = (int) $_POST["customer_id"];
            if ($id > 0) {
                $customerIds[] = $id;
            }
        }

        $customerIds = array_values(array_unique($customerIds));

        header("Content-Type: application/json; charset=UTF-8");

        if (empty($customerIds)) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "No customer record was selected."
            ]);
            exit();
        }

        mysqli_begin_transaction($conn);

        try {
            $restoreSql = "
                UPDATE customers
                SET
                    record_status = 'Active',
                    archived_at = NULL,
                    archive_reason = NULL,
                    updated_at = CURRENT_TIMESTAMP()
                WHERE customer_id = ?
                  AND record_status = 'Archived'
            ";

            $stmt = mysqli_prepare($conn, $restoreSql);

            if (!$stmt) {
                throw new Exception(mysqli_error($conn));
            }

            $restoredCount = 0;

            foreach ($customerIds as $customerId) {
                mysqli_stmt_bind_param($stmt, "i", $customerId);

                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception(mysqli_stmt_error($stmt));
                }

                $restoredCount += mysqli_stmt_affected_rows($stmt);
            }

            mysqli_stmt_close($stmt);
            mysqli_commit($conn);

            /* =====================================================
   AUDIT LOG
   ===================================================== */

foreach ($customerIds as $customerId) {

    logAudit(
        $conn,
        "Customer Records",
        "Restored",
        "Restored customer record",
        (string) $customerId
    );
}

            echo json_encode([
                "success" => true,
                "restored" => $restoredCount,
                "message" => $restoredCount === 1
                    ? "Customer record restored successfully."
                    : $restoredCount . " customer records restored successfully."
            ]);
            exit();

        } catch (Throwable $e) {
            mysqli_rollback($conn);

            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => "Unable to restore the selected customer record(s)."
            ]);
            exit();
        }
    }
}

/* =========================================================
   CUSTOMER RECORDS ARCHIVE

   Archived customers are identified by:
   customers.record_status = 'Archived'

   Pets remain connected through:
   pets.customer_id = customers.customer_id

   Restore will be handled by the archive JS/PHP process.
   ========================================================= */


/* =========================================================
   GET ARCHIVED CUSTOMERS
   ========================================================= */

$sql = "
    SELECT
        c.customer_id,
        c.owner_name,
        c.contact_number,
        c.email,
        c.address,
        c.record_status,
        c.created_at,
        c.archived_at,
        c.archive_reason,

        COUNT(DISTINCT p.pet_id) AS pet_count,

        MAX(
            CASE
                WHEN a.status = 'Completed'
                THEN a.appointment_date
                ELSE NULL
            END
        ) AS last_visit

    FROM customers c

    LEFT JOIN pets p
        ON p.customer_id = c.customer_id

    LEFT JOIN appointments a
        ON a.customer_id = c.customer_id
        AND a.is_archived = 0

    WHERE c.record_status = 'Archived'

    GROUP BY
        c.customer_id,
        c.owner_name,
        c.contact_number,
        c.email,
        c.address,
        c.record_status,
        c.created_at,
        c.archived_at,
        c.archive_reason

    ORDER BY
        c.archived_at DESC,
        c.owner_name ASC
";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die(
        "Customer Archive query failed: "
        . mysqli_error($conn)
    );
}

$customers = [];

while ($row = mysqli_fetch_assoc($result)) {
    $customers[] = $row;
}


/* =========================================================
   GET PETS FOR ARCHIVED CUSTOMERS
   ========================================================= */

$petSql = "
    SELECT
        p.pet_id,
        p.customer_id,
        p.pet_name,
        p.species,
        p.breed,
        p.gender

    FROM pets p

    INNER JOIN customers c
        ON c.customer_id = p.customer_id

    WHERE c.record_status = 'Archived'

    ORDER BY
        p.pet_name ASC
";

$petResult = mysqli_query($conn, $petSql);

if (!$petResult) {
    die(
        "Archived pet query failed: "
        . mysqli_error($conn)
    );
}

$petsByCustomer = [];

while ($pet = mysqli_fetch_assoc($petResult)) {

    $customerId = (int) $pet["customer_id"];

    if (!isset($petsByCustomer[$customerId])) {
        $petsByCustomer[$customerId] = [];
    }

    $petsByCustomer[$customerId][] = $pet;
}


/* =========================================================
   SUMMARY DATA
   ========================================================= */

$totalArchivedCustomers = count($customers);

$totalArchivedPets = 0;

foreach ($petsByCustomer as $customerPets) {
    $totalArchivedPets += count($customerPets);
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
        Customer Records Archive | Veterinary MIS
    </title>


    <!-- SHARED CSS -->
    <link
        rel="stylesheet"
        href="../assets/css/layout.css"
    >


    <!-- CUSTOMER RECORDS ARCHIVE CSS -->
    <link
        rel="stylesheet"
        href="../assets/css/customer_archive.css"
    >


    <!-- FONT AWESOME -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
    >

</head>


<body>

<div class="container">


    <!-- =====================================================
         SIDEBAR
         ===================================================== -->

    <?php include 'partials/sidebar.php'; ?>


    <div
        class="sidebar-overlay"
        id="sidebarOverlay"
    ></div>


    <!-- =====================================================
         MAIN CONTENT
         ===================================================== -->

    <main
        class="content"
        id="mainContent"
    >


        <!-- TOPBAR -->

        <?php

        $pageTitle = "Customer Records Archive";
        $showAdminInfo = false;

        include "partials/topbar.php";

        ?>


        <div class="page-content">


            <div class="customer-archive-page">


                <!-- =================================================
                     BREADCRUMB
                     ================================================= -->

                <div class="archive-breadcrumb">

                    <a href="archived.php">
                        Archive
                    </a>

                    <i class="fa-solid fa-chevron-right"></i>

                    <span>
                        Customer Records Archive
                    </span>

                </div>


                <!-- =================================================
                     PAGE HEADER
                     ================================================= -->

                <div class="archive-header">

                    <div>

                        <h2>
                            Customer Records Archive
                        </h2>

                        <p>
                            View and restore archived customer records.
                        </p>

                    </div>


                    <button
                        type="button"
                        class="back-records-btn"
                        onclick="window.location.href='archived.php'"
                    >

                        <i class="fa-solid fa-arrow-left"></i>

                        Back to Archive

                    </button>

                </div>


                <!-- =================================================
                     SUMMARY CARDS
                     ================================================= -->

                <div class="archive-summary-grid">


                    <div class="archive-summary-card">

                        <div class="archive-summary-icon">

                            <i class="fa-solid fa-users"></i>

                        </div>

                        <div>

                            <span>
                                Archived Customers
                            </span>

                            <strong>
                                <?= $totalArchivedCustomers ?>
                            </strong>

                        </div>

                    </div>


                    <div class="archive-summary-card">

                        <div class="archive-summary-icon">

                            <i class="fa-solid fa-paw"></i>

                        </div>

                        <div>

                            <span>
                                Associated Pets
                            </span>

                            <strong>
                                <?= $totalArchivedPets ?>
                            </strong>

                        </div>

                    </div>


                </div>


                <!-- =================================================
                     TOOLBAR
                     ================================================= -->

                <div class="archive-toolbar">


                    <!-- SEARCH -->

                    <div class="archive-search">

                        <i class="fa-solid fa-magnifying-glass"></i>

                        <input
                            type="text"
                            id="archiveSearch"
                            placeholder="Search customer, owner ID or email..."
                        >

                    </div>


                    <!-- SELECT ALL -->

                    <label class="archive-select-all">

                        <input
                            type="checkbox"
                            id="selectAllCustomers"
                        >

                        <span>
                            Select All
                        </span>

                    </label>


                    <!-- RESTORE SELECTED -->

                    <button
                        type="button"
                        class="restore-selected-btn"
                        id="restoreSelectedBtn"
                        disabled
                    >

                        <i class="fa-solid fa-rotate-left"></i>

                        Restore Selected

                        <span
                            class="selected-count"
                            id="selectedCount"
                        >
                            0
                        </span>

                    </button>


                </div>


                <!-- =================================================
                     ARCHIVED CUSTOMER TABLE
                     ================================================= -->

                <div class="archive-table-wrapper">

                    <table
                        class="archive-table"
                        id="customerArchiveTable"
                    >

                        <thead>

                            <tr>

                                <th class="checkbox-column">

                                    <input
                                        type="checkbox"
                                        id="tableSelectAll"
                                    >

                                </th>

                                <th>
                                    CUSTOMER
                                </th>

                                <th>
                                    OWNER ID
                                </th>

                                <th>
                                    PETS
                                </th>

                                <th>
                                    LAST VISIT
                                </th>

                                <th>
                                    ARCHIVED DATE
                                </th>

                                <th>
                                    ARCHIVE REASON
                                </th>

                                <th>
                                    ACTION
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                            <?php if (empty($customers)): ?>

                                <tr class="archive-empty-row">

                                    <td
                                        colspan="8"
                                    >

                                        <div class="archive-empty">

                                            <i class="fa-solid fa-box-open"></i>

                                            <h3>
                                                No Archived Customer Records
                                            </h3>

                                            <p>
                                                Archived customer records
                                                will appear here.
                                            </p>

                                        </div>

                                    </td>

                                </tr>


                            <?php else: ?>


                                <?php foreach ($customers as $customer): ?>

                                    <?php

                                    $customerId =
                                        (int) $customer["customer_id"];

                                    $customerPets =
                                        $petsByCustomer[$customerId]
                                        ?? [];

                                    $initial =
                                        strtoupper(
                                            substr(
                                                trim(
                                                    $customer["owner_name"]
                                                ),
                                                0,
                                                1
                                            )
                                        );

                                    ?>


                                    <tr
                                        class="archive-customer-row"
                                        data-customer-id="<?= $customerId ?>"
                                        data-search="<?= htmlspecialchars(
                                            strtolower(
                                                $customer["owner_name"]
                                                . " "
                                                . "CUS-"
                                                . str_pad(
                                                    $customerId,
                                                    3,
                                                    "0",
                                                    STR_PAD_LEFT
                                                )
                                                . " "
                                                . ($customer["email"] ?? "")
                                                . " "
                                                . ($customer["contact_number"] ?? "")
                                            )
                                        ) ?>"
                                    >


                                        <!-- CHECKBOX -->

                                        <td class="checkbox-column">

                                            <input
                                                type="checkbox"
                                                class="customer-select"
                                                value="<?= $customerId ?>"
                                            >

                                        </td>


                                        <!-- CUSTOMER -->

                                        <td>

                                            <div class="archive-customer-info">

                                                <div class="archive-avatar">

                                                    <?= htmlspecialchars($initial) ?>

                                                </div>


                                                <div>

                                                    <strong>

                                                        <?= htmlspecialchars(
                                                            $customer["owner_name"]
                                                        ) ?>

                                                    </strong>

                                                    <?php if (!empty($customer["contact_number"])): ?>

                                                        <span>

                                                            <i class="fa-solid fa-phone"></i>

                                                            <?= htmlspecialchars(
                                                                $customer["contact_number"]
                                                            ) ?>

                                                        </span>

                                                    <?php endif; ?>

                                                </div>

                                            </div>

                                        </td>


                                        <!-- OWNER ID -->

                                        <td>

                                            <span class="owner-id">

                                                CUS-<?= str_pad(
                                                    $customerId,
                                                    3,
                                                    "0",
                                                    STR_PAD_LEFT
                                                ) ?>

                                            </span>

                                        </td>


                                        <!-- PETS -->

                                        <td>

                                            <span class="pet-count">

                                                <i class="fa-solid fa-paw"></i>

                                                <?= count($customerPets) ?>

                                            </span>

                                        </td>


                                        <!-- LAST VISIT -->

                                        <td>

                                            <?php if (
                                                !empty(
                                                    $customer["last_visit"]
                                                )
                                            ): ?>

                                                <?= date(
                                                    "M d, Y",
                                                    strtotime(
                                                        $customer["last_visit"]
                                                    )
                                                ) ?>

                                            <?php else: ?>

                                                <span class="no-data">
                                                    No completed visit
                                                </span>

                                            <?php endif; ?>

                                        </td>


                                        <!-- ARCHIVED DATE -->

                                        <td>

                                            <?php if (
                                                !empty(
                                                    $customer["archived_at"]
                                                )
                                            ): ?>

                                                <div class="archive-date">

                                                    <strong>

                                                        <?= date(
                                                            "M d, Y",
                                                            strtotime(
                                                                $customer["archived_at"]
                                                            )
                                                        ) ?>

                                                    </strong>

                                                    <span>

                                                        <?= date(
                                                            "h:i A",
                                                            strtotime(
                                                                $customer["archived_at"]
                                                            )
                                                        ) ?>

                                                    </span>

                                                </div>

                                            <?php else: ?>

                                                <span class="no-data">
                                                    —
                                                </span>

                                            <?php endif; ?>

                                        </td>


                                        <!-- ARCHIVE REASON -->

                                        <td>

                                            <span class="archive-reason">

                                                <?= htmlspecialchars(
                                                    $customer["archive_reason"]
                                                    ?: "No reason specified"
                                                ) ?>

                                            </span>

                                        </td>


                                        <!-- ACTION -->

                                        <td>


                                            <button
                                                type="button"
                                                class="view-archived-pets-btn"
                                                data-customer-id="<?= $customerId ?>"
                                            >
                                                <i class="fa-solid fa-eye"></i>
                                                View Pets

                                            </button>

                                            <button
                                                type="button"
                                                class="restore-customer-btn"
                                                data-customer-id="<?= $customerId ?>"
                                                data-customer-name="<?= htmlspecialchars(
                                                    $customer["owner_name"],
                                                    ENT_QUOTES
                                                ) ?>"
                                            >

                                                <i class="fa-solid fa-rotate-left"></i>

                                                Restore

                                            </button>

                                        </td>


                                    </tr>


                                <?php endforeach; ?>


                            <?php endif; ?>


                        </tbody>

                    </table>

                </div>


                <!-- =================================================
                     FOOTER
                     ================================================= -->

                <div class="archive-table-footer">

                    <span>

                        Showing

                        <strong id="visibleArchiveCount">
                            <?= $totalArchivedCustomers ?>
                        </strong>

                        of

                        <strong>
                            <?= $totalArchivedCustomers ?>
                        </strong>

                        archived records

                    </span>


                    <div class="archive-pagination">

                        <button
                            type="button"
                            disabled
                        >

                            <i class="fa-solid fa-chevron-left"></i>

                        </button>


                        <button
                            type="button"
                            class="active"
                        >
                            1
                        </button>


                        <button
                            type="button"
                            disabled
                        >

                            <i class="fa-solid fa-chevron-right"></i>

                        </button>

                    </div>

                </div>


            </div>

        </div>


    </main>

</div>

<!-- =========================================================
     ARCHIVED CUSTOMER PETS MODAL
     ========================================================= -->

<div
    class="archive-pets-modal"
    id="archivePetsModal"
    aria-hidden="true"
>
    <div
        class="archive-pets-modal-backdrop"
        id="archivePetsModalBackdrop"
    ></div>

    <div
        class="archive-pets-modal-dialog"
        role="dialog"
        aria-modal="true"
        aria-labelledby="archivePetsModalTitle"
    >

        <div class="archive-pets-modal-header">

            <div>
                <span class="archive-pets-modal-label">
                    Archived Customer
                </span>

                <h3 id="archivePetsModalTitle">
                    Associated Pets
                </h3>
            </div>

            <button
                type="button"
                class="archive-pets-close-btn"
                id="archivePetsCloseBtn"
                aria-label="Close"
            >
                <i class="fa-solid fa-xmark"></i>
            </button>

        </div>

        <div
            class="archive-pets-list"
            id="archivePetsList"
        ></div>

    </div>

</div>


<!-- =========================================================
     RESTORE CONFIRMATION MODAL
     ========================================================= -->

<div
    class="restore-modal"
    id="restoreModal"
    aria-hidden="true"
>

    <div
        class="restore-modal-backdrop"
        id="restoreModalBackdrop"
    ></div>


    <div
        class="restore-modal-dialog"
        role="dialog"
        aria-modal="true"
        aria-labelledby="restoreModalTitle"
    >

        <div class="restore-modal-icon">

            <i class="fa-solid fa-rotate-left"></i>

        </div>


        <h3 id="restoreModalTitle">
            Restore Customer?
        </h3>


        <p id="restoreModalMessage">

            This customer and all associated pets
            will be restored to Customer Records.

        </p>


        <div class="restore-modal-actions">

            <button
                type="button"
                class="restore-cancel-btn"
                id="restoreCancelBtn"
            >
                Cancel
            </button>


            <button
                type="button"
                class="restore-confirm-btn"
                id="restoreConfirmBtn"
            >

                <i class="fa-solid fa-rotate-left"></i>

                Restore

            </button>

        </div>

    </div>

</div>


<!-- =========================================================
     SHARED JS
     ========================================================= -->

<script src="../assets/js/layout.js"></script>

<script>
    window.archivedCustomerPets = <?= json_encode(
        $petsByCustomer,
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    ) ?>;
</script>


<!-- =========================================================
     CUSTOMER RECORDS ARCHIVE JS
     ========================================================= -->

<script src="../assets/js/customer_records_archive.js"></script>


</body>

</html>