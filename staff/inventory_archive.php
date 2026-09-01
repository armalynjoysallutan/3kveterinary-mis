<?php

session_start();

if (
    !isset($_SESSION["account_id"]) ||
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "Staff"
) {
    header("Location: ../auth/login.php");
    exit();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/audit_log.php';

/* =========================================================
   RESTORE INVENTORY ITEM
   ========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST"
    && isset($_POST["action"])
    && $_POST["action"] === "restore_inventory"
) {

    header("Content-Type: application/json");

    $itemId = isset($_POST["item_id"])
        ? (int) $_POST["item_id"]
        : 0;

    if ($itemId <= 0) {
        echo json_encode([
            "success" => false,
            "message" => "Invalid inventory item."
        ]);
        exit();
    }

    // =========================================================
// GET ITEM DETAILS FOR AUDIT LOG
// =========================================================

$auditStmt = mysqli_prepare(
    $conn,
    "
    SELECT
        item_code,
        item_name
    FROM inventory_items
    WHERE item_id = ?
      AND status = 'Archived'
    "
);

$auditItem = null;

if ($auditStmt) {

    mysqli_stmt_bind_param(
        $auditStmt,
        "i",
        $itemId
    );

    mysqli_stmt_execute(
        $auditStmt
    );

    $auditResult =
        mysqli_stmt_get_result(
            $auditStmt
        );

    $auditItem =
        mysqli_fetch_assoc(
            $auditResult
        );

    mysqli_stmt_close(
        $auditStmt
    );
}

    $stmt = mysqli_prepare(
        $conn,
        "
        UPDATE inventory_items
        SET
            status = 'Active',
            archived_at = NULL,
            archive_reason = NULL,
            updated_at = CURRENT_TIMESTAMP
        WHERE item_id = ?
          AND status = 'Archived'
        "
    );

    if (!$stmt) {
        echo json_encode([
            "success" => false,
            "message" => "Unable to prepare restore request."
        ]);
        exit();
    }

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $itemId
    );

    if (!mysqli_stmt_execute($stmt)) {
        echo json_encode([
            "success" => false,
            "message" => "Unable to restore inventory item."
        ]);
        mysqli_stmt_close($stmt);
        exit();
    }

    $affectedRows = mysqli_stmt_affected_rows($stmt);

    mysqli_stmt_close($stmt);

    if ($affectedRows <= 0) {
        echo json_encode([
            "success" => false,
            "message" => "Inventory item was not found or is no longer archived."
        ]);
        exit();
    }

    // =========================================================
// AUDIT LOG — RESTORE
// =========================================================

if ($auditItem) {

    logAudit(
        $conn,
        "Inventory",
        "Restored",
        "Inventory item \"" .
            $auditItem["item_name"] .
            "\" was restored from archive.",
        $auditItem["item_code"]
    );

}

    echo json_encode([
        "success" => true,
        "message" => "Inventory item restored successfully."
    ]);

    exit();
}


/* =========================================================
   INVENTORY ARCHIVE
   Archived inventory items are identified by:
   inventory_items.status = 'Archived'
   ========================================================= */

$archivedInventory = [];

$sql = "
    SELECT
        i.item_id,
        i.item_code,
        i.item_name,
        i.category_id,
        i.subcategory_id,
        i.unit_id,
        i.supplier_id,
        i.reorder_level,
        i.unit_cost,
        i.retail_price,
        i.status,
        i.created_at,
        i.updated_at,

        c.category_name,

        u.unit_name,

        s.supplier_name,

        COALESCE(st.total_stock, 0) AS total_stock,

        st.nearest_expiry

    FROM inventory_items i

    INNER JOIN inventory_categories c
        ON c.category_id = i.category_id

    LEFT JOIN inventory_units u
        ON u.unit_id = i.unit_id

    LEFT JOIN inventory_suppliers s
        ON s.supplier_id = i.supplier_id

    LEFT JOIN (
        SELECT
            item_id,

            SUM(quantity) AS total_stock,

            MIN(
                CASE
                    WHEN quantity > 0
                     AND expiration_date IS NOT NULL
                    THEN expiration_date
                    ELSE NULL
                END
            ) AS nearest_expiry

        FROM inventory_stock

        GROUP BY item_id

    ) st
        ON st.item_id = i.item_id

    WHERE i.status = 'Archived'

    ORDER BY
        i.updated_at DESC,
        i.item_name ASC
";


$result = mysqli_query($conn, $sql);

if (!$result) {
    die(
        "Unable to load archived inventory items: " .
        mysqli_error($conn)
    );
}


while ($row = mysqli_fetch_assoc($result)) {
    $archivedInventory[] = $row;
}


$archivedCount = count($archivedInventory);


/* =========================================================
   SUMMARY METRICS
   ========================================================= */

$totalRemainingStock = 0;

$uniqueCategories = [];
$uniqueSuppliers = [];

foreach ($archivedInventory as $row) {

    $totalRemainingStock += (float)($row["total_stock"] ?? 0);

    if (!empty($row["category_id"])) {
        $uniqueCategories[$row["category_id"]] = true;
    }

    if (!empty($row["supplier_id"])) {
        $uniqueSuppliers[$row["supplier_id"]] = true;
    }
}


$categoryCount = count($uniqueCategories);
$supplierCount = count($uniqueSuppliers);


/* =========================================================
   FILTER OPTIONS
   ========================================================= */

$categoryOptions = [];
$supplierOptions = [];

foreach ($archivedInventory as $row) {

    if (!empty($row["category_name"])) {

        $key = strtolower(
            trim($row["category_name"])
        );

        $categoryOptions[$key] =
            $row["category_name"];
    }


    if (!empty($row["supplier_name"])) {

        $key = strtolower(
            trim($row["supplier_name"])
        );

        $supplierOptions[$key] =
            $row["supplier_name"];
    }
}

natcasesort($categoryOptions);
natcasesort($supplierOptions);


/* =========================================================
   INVENTORY STATUS
   ========================================================= */

function archivedInventoryStockStatus(
    $stock,
    $reorderLevel
) {

    $stock = (float)$stock;
    $reorderLevel = (float)$reorderLevel;


    if ($stock <= 0) {

        return [
            "Out of Stock",
            "out-of-stock"
        ];
    }


    if (
        $reorderLevel > 0 &&
        $stock <= $reorderLevel
    ) {

        return [
            "Low Stock",
            "low-stock"
        ];
    }


    return [
        "In Stock",
        "in-stock"
    ];
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
        Inventory Archive | Veterinary MIS
    </title>


    <link
        rel="stylesheet"
        href="../assets/css/layout.css"
    >

    <link
        rel="stylesheet"
        href="../assets/css/inventory.css"
    >

    <link
        rel="stylesheet"
        href="../assets/css/inventory_archive.css"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
    >

</head>


<body>


<div class="layout">


    <?php include __DIR__ . '/partials/sidebar.php'; ?>


    <main
        class="content"
        id="mainContent"
    >


        <?php

        $pageTitle = 'Inventory Archive';

        $showAdminInfo = true;

        include __DIR__ . '/partials/topbar.php';

        ?>


        <div
            class="page-content inventory-archive-page"
        >


            <div
                class="inventory-archive-content"
            >


                <!-- =================================================
                     PAGE HEADER
                     ================================================= -->

                <div class="archive-report-header">


                    <div
                        class="archive-report-header-left"
                    >

                        <div
                            class="archive-report-breadcrumb"
                        >

                            <a href="archived.php">
                                Archive
                            </a>

                            <i
                                class="fa-solid fa-chevron-right"
                            ></i>

                            <span>
                                Inventory Archive
                            </span>

                        </div>


                        <h1>
                            Inventory Archive
                        </h1>


                        <p>
                            View and manage archived inventory items.
                        </p>

                    </div>


                    <div
                        class="archive-report-actions"
                    >

                        <a
                            href="inventory.php"
                            class="archive-report-back-btn"
                        >

                            <i
                                class="fa-solid fa-arrow-left"
                            ></i>

                            Back to Inventory

                        </a>

                    </div>


                </div>


                <!-- =================================================
                     SUMMARY METRICS
                     ================================================= -->

                <section class="archive-metrics">


                    <!-- ARCHIVED ITEMS -->

                    <article
                        class="archive-metric-card"
                    >

                        <div
                            class="metric-icon teal"
                        >

                            <i
                                class="fa-solid fa-boxes-stacked"
                            ></i>

                        </div>


                        <div>

                            <span>
                                ARCHIVED ITEMS
                            </span>

                            <strong>
                                <?= number_format($archivedCount) ?>
                            </strong>

                            <small>
                                Total inactive inventory items
                            </small>

                        </div>

                    </article>


                    <!-- REMAINING STOCK -->

                    <article
                        class="archive-metric-card"
                    >

                        <div
                            class="metric-icon blue"
                        >

                            <i
                                class="fa-solid fa-cubes"
                            ></i>

                        </div>


                        <div>

                            <span>
                                REMAINING STOCK
                            </span>

                            <strong>
                                <?= number_format(
                                    $totalRemainingStock,
                                    2
                                ) ?>
                            </strong>

                            <small>
                                Stock associated with archived items
                            </small>

                        </div>

                    </article>


                    <!-- CATEGORIES -->

                    <article
                        class="archive-metric-card"
                    >

                        <div
                            class="metric-icon green"
                        >

                            <i
                                class="fa-solid fa-layer-group"
                            ></i>

                        </div>


                        <div>

                            <span>
                                CATEGORIES
                            </span>

                            <strong>
                                <?= number_format(
                                    $categoryCount
                                ) ?>
                            </strong>

                            <small>
                                Categories represented in archive
                            </small>

                        </div>

                    </article>


                    <!-- SUPPLIERS -->

                    <article
                        class="archive-metric-card"
                    >

                        <div
                            class="metric-icon orange"
                        >

                            <i
                                class="fa-solid fa-truck"
                            ></i>

                        </div>


                        <div>

                            <span>
                                SUPPLIERS
                            </span>

                            <strong>
                                <?= number_format(
                                    $supplierCount
                                ) ?>
                            </strong>

                            <small>
                                Suppliers represented in archive
                            </small>

                        </div>

                    </article>


                </section>


                <!-- =================================================
                     FILTERS
                     ================================================= -->

                <section
                    class="report-card filters-card"
                >


                    <div
                        class="report-card-header compact-header"
                    >

                        <div>

                            <h2>
                                Filters
                            </h2>

                            <p>
                                Refine the archived inventory list using the available filters.
                            </p>

                        </div>

                    </div>


                    <div
                        class="archive-filters"
                    >


                        <!-- SEARCH -->

                        <div
                            class="filter-field search-filter-field"
                        >

                            <label
                                for="inventoryArchiveSearch"
                            >
                                Search
                            </label>


                            <div
                                class="archive-search-box"
                            >

                                <i
                                    class="fa-solid fa-magnifying-glass"
                                ></i>


                                <input
                                    type="text"
                                    id="inventoryArchiveSearch"
                                    placeholder="Search item, item code, category, supplier..."
                                    autocomplete="off"
                                >

                            </div>

                        </div>


                        <!-- CATEGORY -->

                        <div
                            class="filter-field"
                        >

                            <label
                                for="inventoryArchiveCategoryFilter"
                            >
                                Category
                            </label>


                            <select
                                id="inventoryArchiveCategoryFilter"
                            >

                                <option value="all">
                                    All Categories
                                </option>


                                <?php foreach (
                                    $categoryOptions
                                    as $categoryValue =>
                                    $categoryLabel
                                ): ?>

                                    <option
                                        value="<?= htmlspecialchars(
                                            $categoryValue,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                    >

                                        <?= htmlspecialchars(
                                            $categoryLabel,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <!-- SUPPLIER -->

                        <div
                            class="filter-field"
                        >

                            <label
                                for="inventoryArchiveSupplierFilter"
                            >
                                Supplier
                            </label>


                            <select
                                id="inventoryArchiveSupplierFilter"
                            >

                                <option value="all">
                                    All Suppliers
                                </option>


                                <?php foreach (
                                    $supplierOptions
                                    as $supplierValue =>
                                    $supplierLabel
                                ): ?>

                                    <option
                                        value="<?= htmlspecialchars(
                                            $supplierValue,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                    >

                                        <?= htmlspecialchars(
                                            $supplierLabel,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <!-- STOCK STATUS -->

                        <div
                            class="filter-field"
                        >

                            <label
                                for="inventoryArchiveStockFilter"
                            >
                                Stock Status
                            </label>


                            <select
                                id="inventoryArchiveStockFilter"
                            >

                                <option value="all">
                                    All Stock Status
                                </option>

                                <option value="in-stock">
                                    In Stock
                                </option>

                                <option value="low-stock">
                                    Low Stock
                                </option>

                                <option value="out-of-stock">
                                    Out of Stock
                                </option>

                            </select>

                        </div>


                        <!-- ACTIONS -->

                        <div
                            class="filter-actions"
                        >

                            <button
                                type="button"
                                class="reset-filter-btn"
                                id="resetInventoryArchiveFilters"
                            >
                                Reset
                            </button>


                            <button
                                type="button"
                                class="apply-filter-btn"
                                id="applyInventoryArchiveFilters"
                            >
                                Apply
                            </button>

                        </div>


                    </div>

                </section>


                <!-- =================================================
                     ARCHIVED INVENTORY LIST
                     ================================================= -->

                <section
                    class="report-card archive-list-card"
                >


                    <div
                        class="report-card-header archive-list-header"
                    >

                        <div>

                            <h2>
                                Archived Inventory
                            </h2>

                            <p>
                                Inactive inventory items are shown here.
                            </p>

                        </div>


                        <span
                            class="report-badge"
                        >

                            <i
                                class="fa-solid fa-box-archive"
                            ></i>

                            <?= number_format(
                                $archivedCount
                            ) ?>

                            Archived

                        </span>

                    </div>


                    <div
                        class="archive-table-area"
                    >


                        <table
                            class="archive-inventory-table"
                        >


                            <thead>

                                <tr>

                                    <th>
                                        ITEM
                                    </th>

                                    <th>
                                        CATEGORY
                                    </th>

                                    <th>
                                        UNIT
                                    </th>

                                    <th>
                                        SUPPLIER
                                    </th>

                                    <th>
                                        STOCK
                                    </th>

                                    <th>
                                        REORDER LEVEL
                                    </th>

                                    <th>
                                        UNIT COST
                                    </th>

                                    <th>
                                        RETAIL PRICE
                                    </th>

                                    <th>
                                        STATUS
                                    </th>

                                    <th>
                                        ACTION
                                    </th>

                                </tr>

                            </thead>


                            <tbody
                                id="inventoryArchiveTableBody"
                            >


                            <?php if (
                                $archivedCount === 0
                            ): ?>


                                <tr
                                    class="inventory-archive-empty-row"
                                >

                                    <td colspan="10">

                                        <div
                                            class="archive-empty-state"
                                        >

                                            <i
                                                class="fa-solid fa-box-open"
                                            ></i>

                                            <strong>
                                                No Archived Inventory
                                            </strong>

                                            <span>
                                                Archived inventory items will appear here.
                                            </span>

                                        </div>

                                    </td>

                                </tr>


                            <?php else: ?>


                                <?php foreach (
                                    $archivedInventory
                                    as $row
                                ): ?>


                                    <?php

                                    $itemId =
                                        (int)$row["item_id"];

                                    $itemCode =
                                        trim(
                                            (string)(
                                                $row["item_code"]
                                                ?? ""
                                            )
                                        );

                                    $itemName =
                                        trim(
                                            (string)(
                                                $row["item_name"]
                                                ?? ""
                                            )
                                        );

                                    $categoryName =
                                        trim(
                                            (string)(
                                                $row["category_name"]
                                                ?? ""
                                            )
                                        );

                                    $unitName =
                                        trim(
                                            (string)(
                                                $row["unit_name"]
                                                ?? ""
                                            )
                                        );

                                    $supplierName =
                                        trim(
                                            (string)(
                                                $row["supplier_name"]
                                                ?? ""
                                            )
                                        );


                                    $stock =
                                        (float)(
                                            $row["total_stock"]
                                            ?? 0
                                        );

                                    $reorderLevel =
                                        (float)(
                                            $row["reorder_level"]
                                            ?? 0
                                        );


                                    [
                                        $stockStatus,
                                        $stockStatusClass
                                    ] =
                                        archivedInventoryStockStatus(
                                            $stock,
                                            $reorderLevel
                                        );


                                    $searchText =
                                        strtolower(
                                            $itemCode .
                                            " " .
                                            $itemName .
                                            " " .
                                            $categoryName .
                                            " " .
                                            $supplierName .
                                            " " .
                                            $unitName
                                        );


                                    $categoryKey =
                                        strtolower(
                                            $categoryName
                                        );


                                    $supplierKey =
                                        strtolower(
                                            $supplierName
                                        );


                                    ?>

                                    <tr
                                        class="archive-inventory-row"

                                        data-search="<?= htmlspecialchars(
                                            $searchText,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"

                                        data-category="<?= htmlspecialchars(
                                            $categoryKey,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"

                                        data-supplier="<?= htmlspecialchars(
                                            $supplierKey,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"

                                        data-stock-status="<?= htmlspecialchars(
                                            $stockStatusClass,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                    >


                                        <!-- ITEM -->

                                        <td>

                                            <strong
                                                class="inventory-item-name"
                                            >

                                                <?= htmlspecialchars(
                                                    $itemName
                                                ) ?>

                                            </strong>


                                            <?php if (
                                                $itemCode !== ""
                                            ): ?>

                                                <small>
                                                    <?= htmlspecialchars(
                                                        $itemCode
                                                    ) ?>
                                                </small>

                                            <?php endif; ?>

                                        </td>


                                        <!-- CATEGORY -->

                                        <td>

                                            <?= htmlspecialchars(
                                                $categoryName !== ""
                                                    ? $categoryName
                                                    : "—"
                                            ) ?>

                                        </td>


                                        <!-- UNIT -->

                                        <td>

                                            <?= htmlspecialchars(
                                                $unitName !== ""
                                                    ? $unitName
                                                    : "—"
                                            ) ?>

                                        </td>


                                        <!-- SUPPLIER -->

                                        <td>

                                            <?= htmlspecialchars(
                                                $supplierName !== ""
                                                    ? $supplierName
                                                    : "—"
                                            ) ?>

                                        </td>


                                        <!-- STOCK -->

                                        <td>

                                            <strong>
                                                <?= number_format(
                                                    $stock,
                                                    2
                                                ) ?>
                                            </strong>

                                        </td>


                                        <!-- REORDER -->

                                        <td>

                                            <?= number_format(
                                                $reorderLevel,
                                                2
                                            ) ?>

                                        </td>


                                        <!-- UNIT COST -->

                                        <td>

                                            ₱<?= number_format(
                                                (float)(
                                                    $row["unit_cost"]
                                                    ?? 0
                                                ),
                                                2
                                            ) ?>

                                        </td>


                                        <!-- RETAIL -->

                                        <td>

                                            ₱<?= number_format(
                                                (float)(
                                                    $row["retail_price"]
                                                    ?? 0
                                                ),
                                                2
                                            ) ?>

                                        </td>


                                        <!-- STATUS -->

                                        <td>

                                            <span
                                                class="inventory-stock-status <?= htmlspecialchars(
                                                    $stockStatusClass
                                                ) ?>"
                                            >

                                                <?= htmlspecialchars(
                                                    $stockStatus
                                                ) ?>

                                            </span>

                                        </td>


                                        <!-- ACTION -->

                                        <td>

                                            <button
                                                type="button"
                                                class="archive-inventory-view-btn"
                                                data-id="<?= $itemId ?>"
                                            >

                                                View

                                            </button>


                                            <button
                                                type="button"
                                                class="archive-inventory-restore-btn"
                                                data-id="<?= $itemId ?>"
                                            >

                                                <i
                                                    class="fa-solid fa-rotate-left"
                                                ></i>

                                                Restore

                                            </button>

                                        </td>


                                    </tr>


                                <?php endforeach; ?>


                            <?php endif; ?>


                            </tbody>


                        </table>


                    </div>


                </section>


                <!-- =================================================
                     INSIGHTS
                     ================================================= -->

                <section class="insight-grid">


                    <article
                        class="insight-card teal-insight"
                    >

                        <div class="insight-icon">

                            <i
                                class="fa-solid fa-box-archive"
                            ></i>

                        </div>


                        <div>

                            <h3>
                                Archived Inventory
                            </h3>

                            <p>
                                Inventory items marked inactive are kept here for historical reference and future restoration.
                            </p>

                        </div>

                    </article>


                    <article
                        class="insight-card blue-insight"
                    >

                        <div class="insight-icon">

                            <i
                                class="fa-solid fa-clock-rotate-left"
                            ></i>

                        </div>


                        <div>

                            <h3>
                                Historical Inventory
                            </h3>

                            <p>
                                Archived items remain available for reviewing their category, supplier, pricing, and associated stock information.
                            </p>

                        </div>

                    </article>


                </section>


                <div class="report-footer">

                    Inventory Archive
                    •
                    Veterinary Management Information System

                </div>


            </div>

        </div>


    </main>

</div>

<!-- RESTORE CONFIRMATION MODAL -->
<div
    class="inventory-restore-modal"
    id="inventoryRestoreModal"
    aria-hidden="true"
>

    <div
        class="inventory-restore-backdrop"
        id="inventoryRestoreBackdrop"
    ></div>


    <div
        class="inventory-restore-dialog"
        role="dialog"
        aria-modal="true"
        aria-labelledby="inventoryRestoreTitle"
    >

        <div class="inventory-restore-header">

            <div class="inventory-restore-icon">
                <i class="fa-solid fa-rotate-left"></i>
            </div>

            <div>

                <h2 id="inventoryRestoreTitle">
                    Restore Inventory Item
                </h2>

                <p>
                    Are you sure you want to restore this inventory item?
                </p>

            </div>

        </div>


        <div class="inventory-restore-footer">

            <button
                type="button"
                class="inventory-restore-cancel-btn"
                id="inventoryRestoreCancel"
            >
                Cancel
            </button>

            <button
                type="button"
                class="inventory-restore-confirm-btn"
                id="inventoryRestoreConfirm"
            >
                <i class="fa-solid fa-rotate-left"></i>
                Restore
            </button>

        </div>

    </div>

</div>


<script
    src="../assets/js/layout.js"
></script>


<script
    src="../assets/js/inventory_archive.js"
></script>


</body>

</html>