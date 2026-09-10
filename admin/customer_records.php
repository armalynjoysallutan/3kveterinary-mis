<?php

session_start();

if (!isset($_SESSION["admin_username"])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once "../config/database.php";

/* =====================================================
   PET REFERENCE DATA
   Species and Breeds come from System Variables
===================================================== */

$customerSpeciesList = [];

$customerSpeciesQuery = "
    SELECT species_id, species
    FROM pet_species
    WHERE status = 'Active'
    ORDER BY species ASC
";

$customerSpeciesResult = mysqli_query($conn, $customerSpeciesQuery);

if ($customerSpeciesResult) {
    while ($speciesRow = mysqli_fetch_assoc($customerSpeciesResult)) {
        $customerSpeciesList[] = $speciesRow;
    }
}


$customerBreedList = [];

$customerBreedQuery = "
    SELECT breed_id, species_id, breed
    FROM pet_breeds
    WHERE status = 'Active'
    ORDER BY breed ASC
";

$customerBreedResult = mysqli_query($conn, $customerBreedQuery);

if ($customerBreedResult) {
    while ($breedRow = mysqli_fetch_assoc($customerBreedResult)) {
        $customerBreedList[] = $breedRow;
    }
}

?>

<script>
    const customerFilterSpecies = <?= json_encode(
        $customerSpeciesList,
        JSON_HEX_TAG |
        JSON_HEX_APOS |
        JSON_HEX_AMP |
        JSON_HEX_QUOT
    ) ?>;

    const customerFilterBreeds = <?= json_encode(
        $customerBreedList,
        JSON_HEX_TAG |
        JSON_HEX_APOS |
        JSON_HEX_AMP |
        JSON_HEX_QUOT
    ) ?>;

   
</script>


<?php

/* =====================================================
   GET CUSTOMER RECORDS

   A customer appears in Customer Records when:
   - they have a Completed appointment
   OR
   - they have a Paid billing record

   Customer and pet information comes from:
   customers + pets

   Last visit comes from:
   completed appointments
   ===================================================== */

$sql = "
    SELECT
        c.customer_id,
        c.owner_name,
        c.contact_number,
        c.email,
        c.address,
        c.record_status,
        c.created_at,

        COUNT(DISTINCT p.pet_id) AS pet_count,

        MAX(
            CASE
                WHEN a.status = 'Completed'
                THEN a.appointment_date
                ELSE NULL
            END
        ) AS last_visit

    FROM customers c

    INNER JOIN pets p
        ON p.customer_id = c.customer_id

    LEFT JOIN appointments a
        ON a.customer_id = c.customer_id
        AND a.pet_id = p.pet_id
        AND a.is_archived = 0

    WHERE c.record_status = 'Active'

    GROUP BY
        c.customer_id,
        c.owner_name,
        c.contact_number,
        c.email,
        c.address,
        c.record_status,
        c.created_at

    ORDER BY
        c.owner_name ASC
";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Customer Records query failed: " . mysqli_error($conn));
}

$customers = [];

while ($row = mysqli_fetch_assoc($result)) {
    $customers[] = $row;
}


/* =====================================================
   GET PETS FOR EACH CUSTOMER
   ===================================================== */

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
        p.date_of_birth

    FROM pets p

    ORDER BY
        p.pet_name ASC
";

$petResult = mysqli_query($conn, $petSql);

if (!$petResult) {
    die("Pet query failed: " . mysqli_error($conn));
}

$petsByCustomer = [];

while ($pet = mysqli_fetch_assoc($petResult)) {

    $customerId = (int) $pet["customer_id"];

    if (!isset($petsByCustomer[$customerId])) {
        $petsByCustomer[$customerId] = [];
    }

    $petsByCustomer[$customerId][] = $pet;
}


/* =====================================================
   GET BREEDS ACTUALLY USED BY PETS
   Includes custom breeds entered by customers
   ===================================================== */

$customerCustomBreedList = [];
$customBreedSeen = [];

foreach ($petsByCustomer as $customerPets) {

    foreach ($customerPets as $pet) {

        $speciesName = trim($pet["species"] ?? "");
        $breedName = trim($pet["breed"] ?? "");

        if ($speciesName === "" || $breedName === "") {
            continue;
        }

        /* Do not show generic "Others" */
        if (strcasecmp($breedName, "Others") === 0) {
            continue;
        }

        /*
         * Prevent duplicate breeds under the same species.
         * Example:
         * Cat + Persian
         * Cat + Persian
         *
         * will only appear once.
         */
        $breedKey =
            strtolower($speciesName) . "|" .
            strtolower($breedName);

        if (isset($customBreedSeen[$breedKey])) {
            continue;
        }

        $customBreedSeen[$breedKey] = true;

        $customerCustomBreedList[] = [
            "species" => $speciesName,
            "breed" => $breedName
        ];
    }
}


/* Sort custom breeds alphabetically */
usort(
    $customerCustomBreedList,
    function ($a, $b) {
        return strcasecmp(
            $a["breed"],
            $b["breed"]
        );
    }
);

?>

<script>
    const customerFilterCustomBreeds = <?= json_encode(
        $customerCustomBreedList,
        JSON_HEX_TAG |
        JSON_HEX_APOS |
        JSON_HEX_AMP |
        JSON_HEX_QUOT
    ) ?>;
</script>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Customer Records | Veterinary MIS
    </title>

    <!-- SHARED CSS -->
    <link rel="stylesheet" href="../assets/css/layout.css">
    



    <!-- CUSTOMER RECORDS CSS -->
    <link rel="stylesheet" href="../assets/css/customer_records.css">
    <link rel="stylesheet" href="../assets/css/view_pet_record.css">

    <!-- FONT AWESOME -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
    >


    <style>
        /* =========================================================
           CUSTOMER RECORDS - COMPACT PET LIST
           Keeps the customer card short when an owner has many pets.
           Existing pet action classes are preserved.
           ========================================================= */
        .customer-pets-section {
            position: relative;
        }

        .pet-actions,
        .all-pet-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
        }

        .pet-more-wrap {
            position: relative;
            z-index: 10030;
            pointer-events: auto !important;
        }

        .pet-more-btn {
            width: 38px;
            min-width: 38px;
            height: 38px;
            padding: 0 !important;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }

        .pet-more-menu {
            position: fixed;
            z-index: 10020;
            min-width: 190px;
            padding: 6px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: #fff;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.14);
            display: none;
}

        .pet-more-wrap.menu-open .pet-more-menu {
            display: block;
        }

        .pet-menu-item {
            width: 100%;
            border: 0;
            background: transparent;
            border-radius: 7px;
            padding: 9px 10px;
            display: flex;
            align-items: center;
            gap: 9px;
            text-align: left;
            color: #334155;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
        }

        .pet-menu-item:hover {
            background: #f1f8fd;
            color: #159fe0;
        }

        .pet-menu-item i {
            width: 16px;
            color: #159fe0;
        }

        .view-all-pets-btn {
            width: 100%;
            margin-top: 10px;
            padding: 10px 14px;
            border: 1px dashed #b9dff2;
            border-radius: 9px;
            background: #f5fbff;
            color: #159fe0;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.2s ease;
        }

        .view-all-pets-btn:hover {
            background: #eaf7fe;
            border-color: #159fe0;
        }

        .view-all-pets-btn i {
            margin-right: 6px;
        }

        .view-all-pets-btn span {
            margin-left: 3px;
            color: #64748b;
            font-weight: 600;
        }

        .all-pets-modal {
            position: fixed;
            inset: 0;
            z-index: 99990;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .all-pets-modal.open {
            display: flex;
        }

        .all-pets-modal-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, 0.52);
        }

        .all-pets-modal-dialog {
            position: relative;
            z-index: 1;
            width: min(760px, 94vw);
            max-height: 82vh;
            overflow: hidden;
            border-radius: 16px;
            background: #fff;
            box-shadow: 0 20px 60px rgba(15, 23, 42, 0.25);
        }

        .all-pets-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 18px 20px;
            border-bottom: 1px solid #e5e7eb;
        }

        .all-pets-modal-header h3 {
            margin: 0;
            color: #172033;
            font-size: 20px;
            font-weight: 800;
        }

        .all-pets-modal-header p {
            margin: 4px 0 0;
            color: #94a3b8;
            font-size: 11px;
        }

        .all-pets-modal-close {
            width: 36px;
            height: 36px;
            border: 1px solid #e2e8f0;
            border-radius: 9px;
            background: #fff;
            color: #64748b;
            cursor: pointer;
            font-size: 17px;
        }

        .all-pets-modal-close:hover {
            background: #f8fafc;
            color: #159fe0;
        }

        .all-pets-modal-body {
            max-height: calc(82vh - 84px);
            overflow-y: auto;
            padding: 12px 16px 16px;
        }

        .all-pet-row {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 8px;
            border-bottom: 1px solid #eef2f7;
        }

        .all-pet-row:last-child {
            border-bottom: 0;
        }

        .all-pet-row .pet-avatar {
            flex: 0 0 auto;
        }

        .all-pet-row .pet-info {
            flex: 1 1 auto;
            min-width: 0;
        }

        .all-pet-actions {
            flex: 0 0 auto;
        }

        @media (max-width: 760px) {
            .all-pet-row {
                align-items: flex-start;
                flex-wrap: wrap;
            }

            .all-pet-actions {
                width: 100%;
                justify-content: flex-end;
                padding-left: 48px;
            }
        }
    </style>

</head>

<body>

<div class="container">

    <!-- SIDEBAR -->
    <?php include 'partials/sidebar.php'; ?>

    <div
        class="sidebar-overlay"
        id="sidebarOverlay"
    ></div>


    <!-- MAIN CONTENT -->
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

            <div class="customer-records-content">


                <!-- =================================================
                     PAGE DESCRIPTION
                     ================================================= -->

                <div class="customer-records-header">

                    <div>

                        <h2>
                            Customer Records
                        </h2>

                        <p>
                            Manage all pet owners, records and their accounts.
                        </p>

                    </div>

                </div>


                <!-- =================================================
                     TOOLBAR
                     ================================================= -->

                <div class="customer-toolbar">


                    <!-- SEARCH -->

                    <div class="customer-search">

                        <i class="fa-solid fa-magnifying-glass"></i>

                        <input
                            type="text"
                            id="customerSearch"
                            placeholder="Search by name, phone, email or customer ID..."
                        >

                    </div>


                    <!-- DATE RANGE -->

                    <button
                        type="button"
                        class="customer-toolbar-btn"
                        id="dateRangeBtn"
                    >

                        <i class="fa-regular fa-calendar"></i>

                        Date Range

                    </button>


                    <!-- FILTER -->

                    <button
                        type="button"
                        class="customer-toolbar-btn"
                        id="filterBtn"
                    >

                        <i class="fa-solid fa-sliders"></i>

                        Filters

                    </button>


                    <!-- ADD CUSTOMER -->

                    <button
                        type="button"
                        class="customer-add-btn"
                        id="addCustomerBtn"
                    >

                        <i class="fa-solid fa-plus"></i>

                        Add Customer

                    </button>


                    <!-- ARCHIVED -->

                    <button
                        type="button"
                        class="customer-archived-btn"
                        id="archivedBtn"
                    >

                        Archived

                    </button>

            
                </div>

                <!-- =====================================================
                CUSTOMER RECORDS FILTER PANEL
                ===================================================== -->
                <div class="customer-filter-panel" id="customerFilterPanel" style="display: none;">

    
                    <div class="customer-filter-section" id="petFilterSection">

                        <div class="customer-filter-group">
                            <label for="customerFilterSpecies">
                                Species
                            </label>

                            <select id="customerFilterSpecies">
                                <option value="">All Species</option>

                                <?php foreach ($customerSpeciesList as $species): ?>
                                    <option
                                        value="<?= htmlspecialchars(strtolower(trim($species["species"]))) ?>"
                                        data-species-id="<?= (int) $species["species_id"] ?>"
                                    >
                                        <?= htmlspecialchars($species["species"]) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    
                       
                        <div class="customer-filter-group">
                            <label for="customerFilterBreed">
                                Breed
                            </label>

                            <select id="customerFilterBreed" disabled>
                                <option value="">All Breeds</option>
                            </select>
                        </div>


                        <div class="customer-filter-group">
                            <label for="customerFilterGender">
                                Sex
                            </label>

                            <select id="customerFilterGender">
                                <option value="">All Sex</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                            </select>
                        </div>

                    </div>


                    <!-- DATE RANGE FILTERS -->
                    <div class="customer-filter-section" id="dateFilterSection">

                        <div class="customer-filter-group">
                            <label for="customerFilterDateType">
                                Date Type
                            </label>

                            <select id="customerFilterDateType">
                                <option value="registered">Registered Since</option>
                                <option value="last_visit">Last Visit</option>
                            </select>
                        </div>


                        <div class="customer-filter-group">
                            <label for="customerFilterFrom">
                                From
                            </label>

                            <input
                                type="date"
                                id="customerFilterFrom"
                            >
                        </div>


                        <div class="customer-filter-group">
                            <label for="customerFilterTo">
                                To
                            </label>

                            <input
                                type="date"
                                id="customerFilterTo"
                            >
                        </div>

                    </div>


                    <!-- FILTER ACTIONS -->
                    <div class="customer-filter-actions">

                        <button
                            type="button"
                            id="applyCustomerFilters"
                            class="customer-filter-apply"
                        >
                            <i class="fa-solid fa-filter"></i>
                            Apply
                       </button>

                       <button
                           type="button"
                           id="clearCustomerFilters"
                           class="customer-filter-clear"
                        >
                           Clear
                        </button>

                    </div>

                </div>

                <!-- =====================================================
                     MULTIPLE CUSTOMER ARCHIVE
                    ===================================================== -->
                <div class="customer-bulk-actions"> 
                    <label class="customer-select-all">
                        <input
                            type="checkbox"
                            id="selectAllCustomers"
                        >
                        <span>Select All</span>   
                    </label>  
                    
                    <button
                        type="button"
                        class="customer-multi-archive-btn"
                        id="archiveSelectedCustomersBtn"
                        disabled
                    >
                        <i class="fa-solid fa-box-archive"></i>
                        Archived Selected
                        <span id="selectedCustomerCount">0</span>

                    </button>    
                </div>





               


                <!-- =================================================
                     CUSTOMER LIST
                     ================================================= -->

                <div
                    class="customer-list"
                    id="customerList"
                >


                    <?php if (empty($customers)): ?>

                        <div class="customer-empty">

                            <i class="fa-solid fa-users"></i>

                            <h3>
                                No Customer Records Found
                            </h3>

                            <p>
                                Customers with completed appointments
                                or paid billing records will appear here.
                            </p>

                        </div>


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


                            <?php
    /*
     * FILTER DATA
     * A customer can have multiple pets,
     * so we store all their pet species, breeds, and genders.
     */
    $filterSpecies = [];
$filterBreeds = [];
$filterGenders = [];
$filterPets = [];

foreach ($customerPets as $filterPet) {

    $petSpecies =
        strtolower(trim($filterPet["species"] ?? ""));

    $petBreed =
        strtolower(trim($filterPet["breed"] ?? ""));

    $petGender =
        strtolower(trim($filterPet["gender"] ?? ""));

    if ($petSpecies !== "") {
        $filterSpecies[] = $petSpecies;
    }

    if ($petBreed !== "") {
        $filterBreeds[] = $petBreed;
    }

    if ($petGender !== "") {
        $filterGenders[] = $petGender;
    }

    /*
     * Store the complete combination of each pet.
     * This allows Species + Breed + Sex
     * to match the SAME pet.
     */
    $filterPets[] = [
        "species" => $petSpecies,
        "breed" => $petBreed,
        "gender" => $petGender
    ];
}

$filterSpecies =
    array_values(array_unique($filterSpecies));

$filterBreeds =
    array_values(array_unique($filterBreeds));

$filterGenders =
    array_values(array_unique($filterGenders));
?>

<div
    class="customer-card"
    data-customer-id="<?= $customerId ?>"
    data-search="
        <?= htmlspecialchars(
            strtolower(
                $customer["owner_name"]
                . " "
                . $customer["contact_number"]
                . " "
                . ($customer["email"] ?? "")
                . " CUS-"
                . str_pad(
                    $customerId,
                    3,
                    "0",
                    STR_PAD_LEFT
                )
            )
        ) ?>
    "
    data-species="<?= htmlspecialchars(implode("|", $filterSpecies)) ?>"
    data-breed="<?= htmlspecialchars(implode("|", $filterBreeds)) ?>"
    data-gender="<?= htmlspecialchars(implode("|", $filterGenders)) ?>"
    data-pets="<?= htmlspecialchars(json_encode($filterPets), ENT_QUOTES, 'UTF-8') ?>"
    data-created-at="<?= htmlspecialchars(date("Y-m-d", strtotime($customer["created_at"]))) ?>"
    data-last-visit="<?= !empty($customer["last_visit"]) ? htmlspecialchars(date("Y-m-d", strtotime($customer["last_visit"]))) : "" ?>"
>


                                <!-- =====================================
                                     CUSTOMER INFORMATION
                                     ===================================== -->

                                <div class="customer-info-section">
                                    
                                    <div class="customer-select-box">
                                        <input
                                            type="checkbox"
                                            class="customer-select"
                                            value="<?= $customerId ?>"
                                            
                                        >    

                                    </div>

                                     
                                    <div class="customer-avatar">

                                        <?= htmlspecialchars($initial) ?>

                                    </div>


                                    <div class="customer-info">

                                        <div class="customer-name-row">

                                            <strong>
                                                <?= htmlspecialchars(
                                                    $customer["owner_name"]
                                                ) ?>
                                            </strong>

                                            <span class="status-badge active">
                                                Active
                                            </span>

                                        </div>


                                        <span class="customer-id">

                                            Owner ID:
                                            CUS-<?= str_pad(
                                                $customerId,
                                                3,
                                                "0",
                                                STR_PAD_LEFT
                                            ) ?>

                                        </span>


                                        <div class="customer-contact">

                                            <span>

                                                <i class="fa-solid fa-phone"></i>

                                                <?= htmlspecialchars(
                                                    $customer["contact_number"]
                                                ) ?>

                                            </span>


                                            <?php if (!empty($customer["email"])): ?>

                                                <span>

                                                    <i class="fa-regular fa-envelope"></i>

                                                    <?= htmlspecialchars(
                                                        $customer["email"]
                                                    ) ?>

                                                </span>

                                            <?php endif; ?>

                                        </div>

                                    </div>

                                </div>


                                <!-- =====================================
                                     REGISTERED INFORMATION
                                     ===================================== -->

                                <div class="customer-date-section">

                                    <div>

                                        <span class="info-label">

                                            <i class="fa-regular fa-calendar"></i>

                                            Registered Since:

                                        </span>

                                        <strong>

                                            <?= date(
                                                "M d, Y",
                                                strtotime(
                                                    $customer["created_at"]
                                                )
                                            ) ?>

                                        </strong>

                                    </div>


                                    <div>

                                        <span class="info-label">

                                            <i class="fa-regular fa-clock"></i>

                                            Last Visit:

                                        </span>

                                        <strong>

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

                                                No completed visit

                                            <?php endif; ?>

                                        </strong>

                                    </div>

                                </div>


                                <!-- =====================================
                                     PETS
                                     ===================================== -->

                                <div class="customer-pets-section">


                                    <div class="registered-pets-title">

                                        Registered Pets
                                        (<span class="filtered-pet-count"><?= count($customerPets) ?></span>)
                                        </span>)
                                        

                                    </div>


                                    <?php if (empty($customerPets)): ?>

                                        <div class="no-pets">
                                            No registered pets.
                                        </div>

                                    <?php else: ?>


                                        <?php foreach ($customerPets as $petIndex => $pet): ?>

                                            <?php if ($petIndex >= 3) { continue; } ?>

                                            <div 
                                                class="pet-row"
                                                data-pet-species="<?= htmlspecialchars(strtolower(trim($pet["species"] ?? ""))) ?>"
                                                data-pet-breed="<?= htmlspecialchars(strtolower(trim($pet["breed"] ?? ""))) ?>"
                                                data-pet-gender="<?= htmlspecialchars(strtolower(trim($pet["gender"] ?? ""))) ?>"
                                            >


                                                <!-- PET AVATAR -->

                                                <div class="pet-avatar">

                                                    <i class="fa-solid fa-paw"></i>

                                                </div>


                                                <!-- PET INFO -->

                                                <div class="pet-info">

                                                    <strong>
                                                        <?= htmlspecialchars(
                                                            $pet["pet_name"]
                                                        ) ?>
                                                    </strong>

                                                    <span>

                                                        <?= htmlspecialchars(
                                                            ucfirst(
                                                                $pet["species"]
                                                            )
                                                        ) ?>

                                                        <?= !empty($pet["gender"])
                                                            ? " • "
                                                            . htmlspecialchars(
                                                                ucfirst(
                                                                    $pet["gender"]
                                                                )
                                                            )
                                                            : ""
                                                        ?>

                                                    </span>


                                                    <span class="pet-breed">

                                                        <?= htmlspecialchars(
                                                            $pet["breed"]
                                                            ?: "Breed not specified"
                                                        ) ?>

                                                    </span>

                                                </div>


                                                <!-- PET ACTIONS -->

                                                <div class="pet-actions">

                                                    <button
                                                        type="button"
                                                        class="pet-action view-pet-record"
                                                        data-pet-id="<?= (int) $pet["pet_id"] ?>"
                                                    >
                                                        View Pet Record
                                                    </button>

                                                    <div class="pet-more-wrap">
                                                        <button
                                                            type="button"
                                                            class="pet-action pet-more-btn"
                                                            aria-label="More actions"
                                                            aria-expanded="false"
                                                        >
                                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                                        </button>

                                                        <div class="pet-more-menu">
                                                            <button
                                                                type="button"
                                                                class="pet-menu-item vaccination-history"
                                                                data-pet-id="<?= (int) $pet["pet_id"] ?>"
                                                            >
                                                                <i class="fa-solid fa-syringe"></i>
                                                                Vaccination History
                                                            </button>

                                                            <button
                                                                type="button"
                                                                class="pet-menu-item edit-pet"
                                                                data-pet-id="<?= (int) $pet["pet_id"] ?>"
                                                            >
                                                                <i class="fa-solid fa-pen"></i>
                                                                Edit Info
                                                            </button>
                                                        </div>
                                                    </div>

                                                </div>

                                            </div>

                                        <?php endforeach; ?>

                                    <?php endif; ?>

                                    <?php if (count($customerPets) > 3): ?>
                                        <button
                                            type="button"
                                            class="view-all-pets-btn"
                                            data-pets-modal="petsModal-<?= $customerId ?>"
                                        >
                                            <i class="fa-solid fa-paw"></i>
                                            View All Pets
                                            <span>(<?= count($customerPets) ?>)</span>
                                        </button>
                                    <?php endif; ?>

                                </div>


                            </div>

                            <?php if (count($customerPets) > 3): ?>
                                <div
                                    class="all-pets-modal"
                                    id="petsModal-<?= $customerId ?>"
                                    aria-hidden="true"
                                >
                                    <div class="all-pets-modal-backdrop"></div>

                                    <div class="all-pets-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="allPetsTitle-<?= $customerId ?>">
                                        <div class="all-pets-modal-header">
                                            <div>
                                                <h3 id="allPetsTitle-<?= $customerId ?>">
                                                    Registered Pets
                                                </h3>
                                                <p>
                                                    <?= htmlspecialchars($customer["owner_name"]) ?> · <?= count($customerPets) ?> pets
                                                </p>
                                            </div>

                                            <button
                                                type="button"
                                                class="all-pets-modal-close"
                                                aria-label="Close"
                                            >
                                                <i class="fa-solid fa-xmark"></i>
                                            </button>
                                        </div>

                                        <div class="all-pets-modal-body">
                                            <?php foreach ($customerPets as $pet): ?>
                                                <div class="all-pet-row">
                                                    <div class="pet-avatar">
                                                        <i class="fa-solid fa-paw"></i>
                                                    </div>

                                                    <div class="pet-info">
                                                        <strong>
                                                            <?= htmlspecialchars($pet["pet_name"]) ?>
                                                        </strong>

                                                        <span>
                                                            <?= htmlspecialchars(ucfirst($pet["species"])) ?>
                                                            <?= !empty($pet["gender"])
                                                                ? " • " . htmlspecialchars(ucfirst($pet["gender"]))
                                                                : "" ?>
                                                        </span>

                                                        <span class="pet-breed">
                                                            <?= htmlspecialchars($pet["breed"] ?: "Breed not specified") ?>
                                                        </span>
                                                    </div>

                                                    <div class="all-pet-actions">
                                                        <button
                                                            type="button"
                                                            class="pet-action view-pet-record"
                                                            data-pet-id="<?= (int) $pet["pet_id"] ?>"
                                                        >
                                                            View Pet Record
                                                        </button>

                                                        <div class="pet-more-wrap">
                                                            <button
                                                                type="button"
                                                                class="pet-action pet-more-btn"
                                                                aria-label="More actions"
                                                                aria-expanded="false"
                                                            >
                                                                <i class="fa-solid fa-ellipsis-vertical"></i>
                                                            </button>

                                                            <div class="pet-more-menu">
                                                                <button
                                                                    type="button"
                                                                    class="pet-menu-item vaccination-history"
                                                                    data-pet-id="<?= (int) $pet["pet_id"] ?>"
                                                                >
                                                                    <i class="fa-solid fa-syringe"></i>
                                                                    Vaccination History
                                                                </button>

                                                                <button
                                                                    type="button"
                                                                    class="pet-menu-item edit-pet"
                                                                    data-pet-id="<?= (int) $pet["pet_id"] ?>"
                                                                >
                                                                    <i class="fa-solid fa-pen"></i>
                                                                    Edit Info
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                        <?php endforeach; ?>


                    <?php endif; ?>


                </div>


                <!-- =================================================
                     FOOTER
                     ================================================= -->

                <div class="customer-list-footer">

                    <span>

                        Showing
                        <strong id="visibleCustomerCount">
                            <?= count($customers) ?>
                        </strong>
                        of
                        <strong>
                            <?= count($customers) ?>
                        </strong>
                        records

                    </span>


                    <button
                        type="button"
                        class="next-page-btn"
                        disabled
                    >

                        <i class="fa-solid fa-chevron-right"></i>

                    </button>

                </div>


            </div>

        </div>

    </main>

</div>

<!-- =========================================================
     VIEW PET RECORD MODAL
     ========================================================= -->

<div class="pet-record-modal" id="petRecordModal">

    <div
        class="pet-record-modal-backdrop"
        id="petRecordModalBackdrop"
    ></div>

    <div class="pet-record-modal-dialog">

        <div class="pet-record-modal-header">

            <div>
                <h2>View Pet Record</h2>
                <p>Pet information and medical record history</p>
            </div>

            <button
                type="button"
                class="pet-record-modal-close"
                id="closePetRecordModal"
            >
                <i class="fa-solid fa-xmark"></i>
            </button>

        </div>

        <div
            class="pet-record-modal-content"
            id="petRecordModalContent"
        >
            <div class="pet-record-loading">

                <i class="fa-solid fa-spinner fa-spin"></i>

                <span>
                    Loading pet record...
                </span>

            </div>
        </div>

    </div>

</div>

<!-- =========================================================
     ADD CUSTOMER MODAL
     ========================================================= -->

<div
    class="modal-overlay"
    id="addCustomerModal"
    aria-hidden="true"
>
    <div
        class="modal-box large-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="addCustomerTitle"
    >

        <!-- MODAL HEADER -->
        <div class="modal-header">

            <h3 id="addCustomerTitle">
                Add Customer
            </h3>

            <button
                type="button"
                class="modal-close"
                id="closeAddCustomer"
                aria-label="Close"
            >
                &times;
            </button>

        </div>


        <!-- MODAL BODY -->
        <div class="modal-body">

            <!-- ===========================
                 OWNER INFORMATION
            =========================== -->

            <div class="form-section">

                <h4>Owner Information</h4>

                <div class="form-grid">

                    <!-- LAST NAME -->
                    <div class="form-group">

                        <label for="addOwnerLastName">
                            Last Name
                            <span class="required">*</span>
                        </label>

                        <input
                            type="text"
                            id="addOwnerLastName"
                            name="owner_last_name"
                            placeholder="Enter last name"
                            autocomplete="family-name"
                        >

                    </div>


                    <!-- FIRST NAME -->
                    <div class="form-group">

                        <label for="addOwnerFirstName">
                            First Name
                            <span class="required">*</span>
                        </label>

                        <input
                            type="text"
                            id="addOwnerFirstName"
                            name="owner_first_name"
                            placeholder="Enter first name"
                            autocomplete="given-name"
                        >

                    </div>


                    <!-- MIDDLE NAME -->
                    <div class="form-group">

                        <label for="addOwnerMiddleName">
                            Middle Name
                        </label>

                        <input
                            type="text"
                            id="addOwnerMiddleName"
                            name="owner_middle_name"
                            placeholder="Enter middle name"
                            autocomplete="additional-name"
                        >

                    </div>


                    <!-- CONTACT NUMBER -->
                    <div class="form-group">

                        <label for="addContactNumber">
                            Contact Number
                            <span class="required">*</span>
                        </label>

                        <input
                            type="text"
                            id="addContactNumber"
                            name="contact_number"
                            maxlength="11"
                            inputmode="numeric"
                            placeholder="e.g. 09123456789"
                        >

                    </div>


                    <!-- EMAIL -->
                    <div class="form-group">

                        <label for="addEmail">
                            Email Address
                        </label>

                        <input
                            type="email"
                            id="addEmail"
                            name="email"
                            placeholder="Enter email address"
                            autocomplete="email"
                        >

                    </div>


                    <!-- ADDRESS -->
                    <div class="form-group">

                        <label for="addAddress">
                            Address
                        </label>

                        <input
                            type="text"
                            id="addAddress"
                            name="address"
                            placeholder="Enter address"
                            autocomplete="street-address"
                        >

                    </div>

                </div>

            </div>


            <!-- ===========================
                 PET INFORMATION
            =========================== -->

            <div class="form-section">

                <h4>Pet Information</h4>

                <div class="form-grid">

                    <!-- PET NAME -->
                    <div class="form-group">

                        <label for="addPetName">
                            Pet Name
                            <span class="required">*</span>
                        </label>

                        <input
                            type="text"
                            id="addPetName"
                            name="pet_name"
                            placeholder="Enter pet name"
                        >

                    </div>


                    <!-- SPECIES -->
                    <div class="form-group">

                        <label for="addSpecies">
                            Species
                            <span class="required">*</span>
                        </label>

                        <select
                            id="addSpecies"
                            name="species_id"
                        >
                            <option value="">
                                Select Species
                            </option>

                            <?php foreach ($customerSpeciesList as $species): ?>

                                <option
                                    value="<?= (int) $species["species_id"] ?>"
                                    
                                >
                                   <?= htmlspecialchars($species["species"]) ?>
                                </option>

                            <?php endforeach; ?>
                        </select>        


                            
                           
                    </div>


                    <!-- BREED -->
                    <div class="form-group">

                        <label for="addBreed">
                            Breed
                        </label>

                        <select
                            id="addBreed"
                            name="breed_id"
                            disabled
                        >
                            <option value="">
                                Select Breed
                            </option>

                            <?php foreach ($customerBreedList as $breed): ?>
                                <option
                                    value="<?= (int)$breed["breed_id"] ?>"
                                    data-species-id="<?= (int)$breed["species_id"] ?>"
                                    data-breed-name="<?= htmlspecialchars($breed["breed"], ENT_QUOTES) ?>"
                                >
                                    <?= htmlspecialchars($breed["breed"]) ?>
                                </option>
                            <?php endforeach; ?>

                            <option
                                value="Others"
                                data-other-breed="true"
                            >
                                Others
                            </option>    
                            
                        </select>    

                        <div 
                            id="addOtherBreedGroup"
                            style="display: none; margin-top: 12px;"
                        >

                            <label for="addOtherBreed">
                                Specify Breed
                                <span class="required">*</span>
                            </label>

                            <input
                                type="text"
                                id="addOtherBreed"
                                name="other_breed"
                            >
                        </div>    

                    </div>


                    <!-- COLOR -->
                    <div class="form-group">

                        <label for="addColor">
                            Color
                        </label>

                        <input
                            type="text"
                            id="addColor"
                            name="color"
                            placeholder="Enter pet's color"
                        >

                    </div>


                    <!-- GENDER -->
                    <div class="form-group">

                        <label for="addGender">
                            Sex
                        </label>

                        <select
                            id="addGender"
                            name="gender"
                        >
                            <option value="">
                                Select Sex
                            </option>

                            <option value="male">
                                Male
                            </option>

                            <option value="female">
                                Female
                            </option>
                        </select>

                    </div>


                    <!-- WEIGHT -->
                    <div class="form-group">

                        <label for="addWeight">
                            Weight (kg)
                        </label>

                        <input
                            type="number"
                            id="addWeight"
                            name="weight"
                            min="0"
                            step="0.01"
                            placeholder="Enter weight"
                        >

                    </div>


                    <!-- DATE OF BIRTH -->
                    <div class="form-group">

                        <label>
                            Date of Birth
                        </label>


                        <input
                            type="month"
                            id="addDateOfBirth"
                            name="date_of_birth"
                            value=""
                        >    
                    </div>

                </div>

            </div>

        </div>


        <!-- MODAL FOOTER -->
        <div class="modal-footer">

            <button
                type="button"
                class="cancel-btn"
                id="cancelAddCustomer"
            >
                Cancel
            </button>

            <button
                type="button"
                class="save-btn"
                id="saveCustomer"
            >
                Save Customer
            </button>

        </div>

    </div>
</div>

<!-- =========================================================
     ADD CUSTOMER SUCCESS MODAL
     ========================================================= -->

<div
    class="success-modal-overlay"
    id="customerSuccessModal"
>

    <div class="success-modal">

        <div class="success-icon">
            <i class="fa-solid fa-check"></i>
        </div>

        <h3>
            Customer Added Successfully!
        </h3>

        <p>
            The customer and pet record have been
            successfully added to Customer Records.
        </p>

        <button
            type="button"
            id="customerSuccessDone"
            class="success-modal-btn"
        >
            Done
        </button>

    </div>

</div>

<!-- =========================================================
     ARCHIVE CUSTOMER MODAL
     ========================================================= -->

<div
    class="customer-archive-modal"
    id="customerArchiveModal"
    aria-hidden="true"
>

    <div
        class="customer-archive-backdrop"
        id="customerArchiveBackdrop"
    ></div>


    <div
        class="customer-archive-dialog"
        role="dialog"
        aria-modal="true"
        aria-labelledby="customerArchiveTitle"
    >

        <!-- HEADER -->
        <div class="customer-archive-dialog-header">

            <div class="customer-archive-icon">
                <i class="fa-solid fa-box-archive"></i>
            </div>

            <div>

                <h3 id="customerArchiveTitle">
                    Archive Customer?
                </h3>

                <div
                    class="archive-customer-name"
                    id="customerArchiveName"
                ></div>

            </div>

        </div>


        <!-- BODY -->
        <div class="customer-archive-dialog-body">

            <p>
                This customer record will be removed from active
                Customer Records and moved to the Customer Records
                Archive. Associated pets will remain linked to the
                customer and can be restored with the record.
            </p>


            <label for="customerArchiveReason">
                Reason for archiving
            </label>


            <textarea
                id="customerArchiveReason"
                maxlength="255"
                placeholder="Enter the reason for archiving this customer..."
            ></textarea>


            <div
                id="customerArchiveError"
                style="
                    display:none;
                    margin-top:7px;
                    color:#dc2626;
                    font-size:10px;
                    font-weight:700;
                "
            ></div>

        </div>


        <!-- ACTIONS -->
        <div class="customer-archive-dialog-actions">

            <button
                type="button"
                class="customer-archive-cancel"
                id="customerArchiveCancel"
            >
                Cancel
            </button>


            <button
                type="button"
                class="customer-archive-confirm"
                id="customerArchiveConfirm"
            >
                <i class="fa-solid fa-box-archive"></i>
                Archive Customer
            </button>

        </div>

    </div>

</div>

<!-- SHARED JS -->

<script src="../assets/js/layout.js"></script>

>
<!-- CUSTOMER RECORDS JS -->

<script src="../assets/js/customer_records.js"></script>



<!-- =========================================================
     CREATE BILLING FROM MEDICAL RECORD
     ---------------------------------------------------------
     This handler is intentionally placed in Customer Records
     because view_pet_record.php is loaded dynamically into
     #petRecordModalContent. Its <script> tags are not executed
     when that HTML is inserted with innerHTML.
     ========================================================= -->

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
            console.error(
                "System confirmation modal not found."
            );

            resolve(false);
            return;
        }

        messageText.textContent = message;

        modal.classList.add("open");
        modal.setAttribute(
            "aria-hidden",
            "false"
        );

        function closeModal(result) {

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
            closeModal(true);
        }

        function handleCancel() {
            closeModal(false);
        }

        function handleEscape(event) {

            if (event.key === "Escape") {
                closeModal(false);
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

document.addEventListener("click", async function (event) {

    const button =
        event.target.closest(".create-billing-btn");

    if (!button) {
        return;
    }

    event.preventDefault();
    event.stopPropagation();
    event.stopImmediatePropagation();

    const medicalRecordId =
        button.getAttribute("data-record-id");

    if (!medicalRecordId) {
        window.showSystemMessage(
            "Medical record ID is missing.",
            "warning"
        
        );
        return;
    }

    const confirmed = await showSystemConfirm(
        "Are you sure you want to create billing for this medical record?"
    );

    if (!confirmed) {
        return;
    }

    const originalHtml =
        button.innerHTML;

    button.disabled = true;
    button.classList.add("is-loading");

    button.innerHTML =
        '<i class="fa-solid fa-spinner fa-spin"></i>';

    try {

        const response = await fetch(
            "../process/create_billing_from_medical_record.php",
            {
                method: "POST",
                headers: {
                    "Content-Type":
                        "application/x-www-form-urlencoded; charset=UTF-8"
                },
                body: new URLSearchParams({
                    medical_record_id:
                        medicalRecordId
                })
            }
        );

        const result =
            await response.json();

        console.log(
            "Create Billing Response:",
            result
        );

        if (!result.success) {

            window.showSystemMessage(
                result.message ||
                "Unable to create billing.",
                "error"
            );

            return;
        }

        window.location.href =
            "billing_statement.php?billing_id=" +
            encodeURIComponent(
                result.billing_id
            );

    } catch (error) {

        console.error(
            "Create Billing Error:",
            error
        );

        window.showSystemMessage(
            "Something went wrong while creating the billing.",
            "error"
        );

    } finally {

        button.disabled = false;
        button.classList.remove("is-loading");
        button.innerHTML = originalHtml;

    }

}, true);
</script>

<!-- SYSTEM CONFIRMATION MODAL -->
<div
    class="system-confirm-modal"
    id="systemConfirmModal"
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



<!-- SYSTEM MESSAGE MODAL -->
<div
    class="system-message-modal"
    id="systemMessageModal"
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

        <div class="system-message-icon" id="systemMessageIcon">
            <i class="fa-solid fa-circle-info"></i>
        </div>

        <div class="system-message-content">

            <h3 id="systemMessageTitle">
                Notice
            </h3>

            <p id="systemMessageText">
                System message.
            </p>

        </div>

        <button
            type="button"
            class="system-message-ok"
            id="systemMessageOk"
        >
            OK
        </button>

    </div>
</div>

</body>

</html>