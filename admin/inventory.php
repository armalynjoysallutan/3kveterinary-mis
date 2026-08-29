<?php
session_start();

if (!isset($_SESSION["admin_username"])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once __DIR__ . '/../config/database.php';

$totalItems = 0;
$inStock = 0;
$reorderSoon = 0;
$outOfStock = 0;

$countSql = "
    SELECT
        COUNT(*) AS total_items,
        COALESCE(SUM(CASE WHEN COALESCE(s.total_stock, 0) > 0 THEN 1 ELSE 0 END), 0) AS in_stock,
        COALESCE(SUM(CASE
            WHEN COALESCE(s.total_stock, 0) > 0
             AND COALESCE(s.total_stock, 0) <= i.reorder_level
            THEN 1 ELSE 0 END), 0) AS reorder_soon,
        COALESCE(SUM(CASE WHEN COALESCE(s.total_stock, 0) <= 0 THEN 1 ELSE 0 END), 0) AS out_of_stock
    FROM inventory_items i
    LEFT JOIN (
        SELECT item_id, SUM(quantity) AS total_stock
        FROM inventory_stock
        GROUP BY item_id
    ) s ON s.item_id = i.item_id
    WHERE i.status = 'Active'
";

$countResult = mysqli_query($conn, $countSql);
if ($countResult) {
    $countRow = mysqli_fetch_assoc($countResult);
    $totalItems = (int)($countRow["total_items"] ?? 0);
    $inStock = (int)($countRow["in_stock"] ?? 0);
    $reorderSoon = (int)($countRow["reorder_soon"] ?? 0);
    $outOfStock = (int)($countRow["out_of_stock"] ?? 0);
}

$categories = [];
$categoryResult = mysqli_query(
    $conn,
    "SELECT category_id, category_name
     FROM inventory_categories
     WHERE status = 'Active'
     ORDER BY category_name ASC"
);
if ($categoryResult) {
    while ($row = mysqli_fetch_assoc($categoryResult)) {
        $categories[] = $row;
    }
}

$units = [];
$unitResult = mysqli_query(
    $conn,
    "SELECT unit_id, unit_name, abbreviation
     FROM inventory_units
     WHERE status = 'Active'
     ORDER BY unit_name ASC"
);
if ($unitResult) {
    while ($row = mysqli_fetch_assoc($unitResult)) {
        $units[] = $row;
    }
}

$suppliers = [];
$supplierResult = mysqli_query(
    $conn,
    "SELECT supplier_id, supplier_name
     FROM inventory_suppliers
     WHERE status = 'Active'
     ORDER BY supplier_name ASC"
);
if ($supplierResult) {
    while ($row = mysqli_fetch_assoc($supplierResult)) {
        $suppliers[] = $row;
    }
}

$inventoryRows = [];

$listSql = "
    SELECT
        i.item_id,
        i.category_id,
        i.item_code,
        i.item_name,
        i.reorder_level,
        i.unit_cost,
        i.retail_price,
        i.status,
        c.category_name,
        u.unit_name,
        u.abbreviation,
        s.supplier_name,
        COALESCE(st.total_stock, 0) AS total_stock,
        st.nearest_expiry,
        (
            SELECT st2.batch_number
            FROM inventory_stock st2
            WHERE st2.item_id = i.item_id
              AND st2.quantity > 0
            ORDER BY
                CASE WHEN st2.expiration_date IS NULL THEN 1 ELSE 0 END ASC,
                st2.expiration_date ASC,
                st2.stock_id ASC
            LIMIT 1
        ) AS current_batch,
        (
            SELECT st3.expiration_date
            FROM inventory_stock st3
            WHERE st3.item_id = i.item_id
              AND st3.quantity > 0
            ORDER BY
                CASE WHEN st3.expiration_date IS NULL THEN 1 ELSE 0 END ASC,
                st3.expiration_date ASC,
                st3.stock_id ASC
            LIMIT 1
        ) AS current_expiry
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
                    WHEN quantity > 0 AND expiration_date IS NOT NULL
                    THEN expiration_date
                    ELSE NULL
                END
            ) AS nearest_expiry
        FROM inventory_stock
        GROUP BY item_id
    ) st ON st.item_id = i.item_id
    WHERE i.status = 'Active'
    ORDER BY c.category_name ASC, i.item_name ASC
";

$listResult = mysqli_query($conn, $listSql);
if ($listResult) {
    while ($row = mysqli_fetch_assoc($listResult)) {
        $inventoryRows[] = $row;
    }
}

function inventoryStatus($stock, $reorderLevel) {
    $stock = (float)$stock;
    $reorderLevel = (float)$reorderLevel;

    if ($stock <= 0) {
        return ["Out of Stock", "out-of-stock"];
    }

    if ($reorderLevel > 0 && $stock <= $reorderLevel) {
        return ["Low Stock", "low-stock"];
    }

    return ["In Stock", "in-stock"];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory | Veterinary MIS</title>

    <link rel="stylesheet" href="../assets/css/layout.css">
    <link rel="stylesheet" href="../assets/css/inventory.css">
    <link rel="stylesheet" href="../assets/css/appointments.css">
   

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
    >
</head>

<body>
<div class="container">

    <?php include 'partials/sidebar.php'; ?>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <main class="content" id="mainContent">

        <?php
        $pageTitle = "Inventory";
        $showAdminInfo = false;
        include "partials/topbar.php";
        ?>

        <div class="page-content">
            <div class="inventory-content">

                <div class="toolbar-card">
                    <div class="toolbar-left">
                        <div class="search-box">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input
                                type="text"
                                id="inventorySearch"
                                placeholder="Search Item ID, Item Name or Batch No..."
                                autocomplete="off"
                            >
                        </div>


                    </div>

                    <div class="toolbar-right">
                        <button class="add-btn" type="button" id="addInventoryItemBtn">
                            <i class="fa-solid fa-plus"></i>
                            Add Item
                        </button>

                        <button class="history-btn" type="button" id="stockHistoryBtn"> 
                            <i class="fa-solid fa-clock-rotate-left"></i>
                            Stock History
                        </button >   


                        <button class="archive-btn" type="button" id="archiveInventoryBtn">
                            <i class="fa-solid fa-box-archive"></i>
                            Archived
                        </button>
                    </div>
                </div>

                <div class="inventory-category-tabs" id="inventoryCategoryTabs" role="tablist" aria-label="Inventory categories">
                    <button type="button" class="inventory-category-tab active" data-category="" role="tab" aria-selected="true">All Items</button>
                    <?php $preferredCategoryOrder = ['Clinic Supplies', 'Medicine', 'Supplements', 'Pet Food', 'Vaccines', 'Test Kits', 'Others']; ?>
                    <?php foreach ($preferredCategoryOrder as $preferredName): ?>
                        <?php foreach ($categories as $category): ?>
                            <?php if (strcasecmp($category['category_name'], $preferredName) === 0): ?>
                                <button type="button" class="inventory-category-tab" data-category="<?= (int)$category['category_id'] ?>" role="tab" aria-selected="false">
                                    <?= htmlspecialchars($category['category_name']) ?>
                                </button>
                            <?php break; endif; ?>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </div>

                <!-- INVENTORY BULK ARCHIVE -->

                <div class="inventory-bulk-actions">

                    <label class="inventory-select-all">
                        <input
                             type="checkbox"
                             id="selectAllInventory"
                    </label>

                    <button
                        type="button"
                        class="inventory-multi-archive-btn"
                        id="archiveSelectedInventoryBtn"
                        disabled
                    >
                        <i class="fa-solid fa-box archive"></i>
                        Archive Selected
                        <span id="selectedInventoryCount">0</span>
                    </button>    

                </div>

                <div class="inventory-summary">

                    <div class="inventory-stat-card">
                        <div class="inventory-stat-info">
                            <span>Total Items</span>
                            <strong><?= $totalItems ?></strong>
                            <small>Total active inventory items</small>
                        </div>
                        <div class="inventory-stat-icon total">
                            <i class="fa-solid fa-box"></i>
                        </div>
                    </div>

                    <div class="inventory-stat-card">
                        <div class="inventory-stat-info">
                            <span>In Stock</span>
                            <strong><?= $inStock ?></strong>
                            <small>Items currently in stock</small>
                        </div>
                        <div class="inventory-stat-icon stock">
                            <i class="fa-solid fa-boxes-stacked"></i>
                        </div>
                    </div>

                    <div class="inventory-stat-card">
                        <div class="inventory-stat-info">
                            <span>Re-order Soon</span>
                            <strong><?= $reorderSoon ?></strong>
                            <small>Items at or below reorder level</small>
                        </div>
                        <div class="inventory-stat-icon reorder">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </div>
                    </div>

                    <div class="inventory-stat-card">
                        <div class="inventory-stat-info">
                            <span>Out of Stock</span>
                            <strong><?= $outOfStock ?></strong>
                            <small>Items needing immediate restock</small>
                        </div>
                        <div class="inventory-stat-icon out">
                            <i class="fa-solid fa-circle-xmark"></i>
                        </div>
                    </div>

                </div>

                <div class="inventory-list-card">

                    <div class="section-header">
                        <div>
                            <h3>Inventory List</h3>
                            <p>Manage stocked items, batches and pricing.</p>
                        </div>
                    </div>

                    <div class="table-wrapper">
                        <table class="inventory-table">
                            <thead>
                                <tr>
                                    <th class="inventory-checkbox-column">
                                        <input
                                            type="checkbox"
                                            id="tableSelectAllInventory"
                                            aria-label="Select all inventory items"
                                    </th>
                                    <th>ITEM ID</th>
                                    <th>ITEM NAME</th>
                                    <th>CATEGORY</th>
                                    <th>UNIT</th>
                                    <th>STOCK</th>
                                    <th>UNIT COST</th>
                                    <th>RETAIL PRICE</th>
                                    <th>BATCH NO.</th>
                                    <th>EXPIRY DATE</th>
                                    <th>STATUS</th>
                                    <th>ACTIONS</th>
                                </tr>
                            </thead>

                            <tbody id="inventoryTableBody">

                            <?php if (empty($inventoryRows)): ?>

                                <tr>
                                    <td colspan="12">
                                        <div class="inventory-empty">
                                            <i class="fa-solid fa-box-open"></i>
                                            <h4>No Inventory Items</h4>
                                            <p>Add your first inventory item to begin managing stock.</p>
                                            <button type="button" class="empty-add-btn" id="emptyAddInventoryBtn">
                                                <i class="fa-solid fa-plus"></i>
                                                Add Item
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                            <?php else: ?>

<?php foreach ($inventoryRows as $item): ?>
                                    <?php [$statusText, $statusClass] = inventoryStatus($item["total_stock"], $item["reorder_level"]); ?>
                                    <?php $searchText = strtolower($item["item_code"] . " " . $item["item_name"] . " " . $item["category_name"]); ?>
                                    <tr class="inventory-row" data-search="<?= htmlspecialchars($searchText) ?>" data-category="<?= (int)$item["category_id"] ?>">
                                        <td class="inventory-checkbox-column">
                                            <input
                                                type="checkbox"
                                                class="inventory-select"
                                                value="<?= (int)$item["item_id"] ?>"
                                            >    
                                        </td>

                                        <td>
                                            <span class="inventory-item-code">
                                                <?= htmlspecialchars($item["item_code"]) ?>
                                            </span>
                                        </td>

                                        
                                        <td><strong><?= htmlspecialchars($item["item_name"]) ?></strong></td>
                                        <td><?= htmlspecialchars($item["category_name"]) ?></td>
                                        <td><?= htmlspecialchars($item["abbreviation"] ?: ($item["unit_name"] ?? "—")) ?></td>
                                        <td><?= number_format((float)$item["total_stock"], 0) ?></td>
                                        <td>₱<?= number_format((float)$item["unit_cost"], 2) ?></td>
                                        <td>₱<?= number_format((float)$item["retail_price"], 2) ?></td>
                                        <td><?= !empty($item["current_batch"]) ? htmlspecialchars($item["current_batch"]) : "—" ?></td>
                                        <td>
                                            <?php if (!empty($item["current_expiry"])): ?>
                                                <?= htmlspecialchars(date("M d, Y", strtotime($item["current_expiry"]))) ?>
                                            <?php else: ?>
                                                —
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="inventory-status <?= $statusClass ?>"><?= htmlspecialchars($statusText) ?></span></td>
                                        <td>
                                            <button type="button" class="inventory-more-btn" data-id="<?= (int)$item["item_id"] ?>" aria-expanded="false" aria-label="Inventory actions"><i class="fa-solid fa-ellipsis"></i></button>
                                            <div class="inventory-action-menu">
                                                <button type="button" data-action="view" data-id="<?= (int)$item["item_id"] ?>"><i class="fa-regular fa-eye"></i> View</button>
                                                <button type="button" data-action="stock-in" data-id="<?= (int)$item["item_id"] ?>"><i class="fa-solid fa-arrow-down"></i> Stock In</button>
                                                <button type="button" data-action="stock-out" data-id="<?= (int)$item["item_id"] ?>"><i class="fa-solid fa-arrow-up"></i> Stock Out</button>
                                                <button type="button" data-action="edit" data-id="<?= (int)$item["item_id"] ?>"><i class="fa-solid fa-pen"></i> Edit</button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>

                            <?php endif; ?>

                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

    </main>
</div>

<!-- ADD ITEM MODAL -->
<div class="inventory-modal-overlay" id="inventoryModal">
    <div class="inventory-modal" role="dialog" aria-modal="true" aria-labelledby="inventoryModalTitle">

        <div class="inventory-modal-header">
            <div>
                <h2 id="inventoryModalTitle">Add Inventory Item</h2>
                <p>Add an item to the inventory master list.</p>
            </div>

            <button type="button" class="modal-close-btn" id="closeInventoryModal" aria-label="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form id="inventoryItemForm" autocomplete="off">

            <div class="inventory-form-grid">

                <div class="inventory-form-group">
                    <label for="itemCode">Item ID <span>*</span></label>
                    <input type="text" id="itemCode" name="item_code" placeholder="e.g. FOOD-001" required>
                </div>

                <div class="inventory-form-group">
                    <label for="itemName">Item Name <span>*</span></label>
                    <input type="text" id="itemName" name="item_name" placeholder="Enter item name" required>
                </div>

                <div class="inventory-form-group">
                    <label for="itemCategory">Category <span>*</span></label>
                    <select id="itemCategory" name="category_id" required>
                        <option value="">Select category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= (int)$category["category_id"] ?>">
                                <?= htmlspecialchars($category["category_name"]) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="inventory-form-group">
                    <label for="itemUnit">Unit</label>
                    <select id="itemUnit" name="unit_id">
                        <option value="">Select unit</option>
                        <?php foreach ($units as $unit): ?>
                            <option value="<?= (int)$unit["unit_id"] ?>">
                                <?= htmlspecialchars($unit["unit_name"]) ?>
                                (<?= htmlspecialchars($unit["abbreviation"]) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="inventory-form-group">
                    <label for="itemSupplier">Supplier</label>
                    <select id="itemSupplier" name="supplier_id">
                        <option value="">Select supplier</option>
                        <?php foreach ($suppliers as $supplier): ?>
                            <option value="<?= (int)$supplier["supplier_id"] ?>">
                                <?= htmlspecialchars($supplier["supplier_name"]) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="inventory-form-group">
                    <label for="reorderLevel">Re-order Level</label>
                    <input type="number" id="reorderLevel" name="reorder_level" min="0" step="0.01" value="0">
                </div>

                <div class="inventory-form-group">
                    <label for="unitCost">Unit Cost</label>
                    <input type="number" id="unitCost" name="unit_cost" min="0" step="0.01" placeholder="0.00">
                </div>

                <div class="inventory-form-group">
                    <label for="retailPrice">Retail Price</label>
                    <input type="number" id="retailPrice" name="retail_price" min="0" step="0.01" placeholder="0.00">
                </div>

            </div>

            <div class="inventory-form-note">
                <i class="fa-solid fa-circle-info"></i>
                Batch number, expiration date and actual stock will be recorded through Stock In.
            </div>

            <div class="inventory-modal-footer">
                <button type="button" class="modal-cancel-btn" id="cancelInventoryModal">
                    Cancel
                </button>

                <button type="submit" class="modal-save-btn" id="saveInventoryItemBtn">
                    <i class="fa-solid fa-check"></i>
                    Save Item
                </button>
            </div>

        </form>
    </div>
</div>

<!-- =========================================================
     VIEW INVENTORY ITEM MODAL
========================================================= -->

<div
    class="inventory-modal-overlay"
    id="viewInventoryModal"
>

    <div
        class="inventory-modal inventory-view-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="viewInventoryModalTitle"
    >

        <!-- HEADER -->

        <div class="inventory-modal-header">

            <div>

                <h2 id="viewInventoryModalTitle">
                    View Item Details
                </h2>

                <p>
                    Complete information about this inventory item.
                </p>

            </div>

            <button
                type="button"
                class="modal-close-btn"
                id="closeViewInventoryModal"
                aria-label="Close"
            >

                <i class="fa-solid fa-xmark"></i>

            </button>

        </div>


        <!-- BODY -->

        <div class="inventory-view-body">

            <!-- ITEM INFORMATION -->

            <div class="inventory-view-section">

                <div class="inventory-view-section-title">

                    <i class="fa-solid fa-box"></i>

                    <span>Item Information</span>

                </div>


                <div class="inventory-view-grid">

                    <div class="inventory-view-field">

                        <span class="inventory-view-label">
                            Item ID
                        </span>

                        <strong id="viewItemCode">
                            —
                        </strong>

                    </div>


                    <div class="inventory-view-field">

                        <span class="inventory-view-label">
                            Item Name
                        </span>

                        <strong id="viewItemName">
                            —
                        </strong>

                    </div>


                    <div class="inventory-view-field">

                        <span class="inventory-view-label">
                            Category
                        </span>

                        <strong id="viewItemCategory">
                            —
                        </strong>

                    </div>


                    <div class="inventory-view-field">

                        <span class="inventory-view-label">
                            Subcategory
                        </span>

                        <strong id="viewItemSubcategory">
                            —
                        </strong>

                    </div>


                    <div class="inventory-view-field">

                        <span class="inventory-view-label">
                            Unit
                        </span>

                        <strong id="viewItemUnit">
                            —
                        </strong>

                    </div>


                    <div class="inventory-view-field">

                        <span class="inventory-view-label">
                            Supplier
                        </span>

                        <strong id="viewItemSupplier">
                            —
                        </strong>

                    </div>

                </div>

            </div>


            <!-- PRICING -->

            <div class="inventory-view-section">

                <div class="inventory-view-section-title">

                    <i class="fa-solid fa-peso-sign"></i>

                    <span>Pricing</span>

                </div>


                <div class="inventory-view-grid">

                    <div class="inventory-view-field">

                        <span class="inventory-view-label">
                            Unit Cost
                        </span>

                        <strong id="viewUnitCost">
                            —
                        </strong>

                    </div>


                    <div class="inventory-view-field">

                        <span class="inventory-view-label">
                            Retail Price
                        </span>

                        <strong id="viewRetailPrice">
                            —
                        </strong>

                    </div>


                    <div class="inventory-view-field">

                        <span class="inventory-view-label">
                            Re-order Level
                        </span>

                        <strong id="viewReorderLevel">
                            —
                        </strong>

                    </div>


                    <div class="inventory-view-field">

                        <span class="inventory-view-label">
                            Current Stock
                        </span>

                        <strong id="viewCurrentStock">
                            —
                        </strong>

                    </div>

                </div>

            </div>


            <!-- BATCH INFORMATION -->

            <div class="inventory-view-section">

                <div class="inventory-view-section-title">

                    <i class="fa-solid fa-layer-group"></i>

                    <span>Batch Information</span>

                </div>


                <div
                    class="inventory-view-batch-wrapper"
                    id="viewBatchWrapper"
                >

                    <div class="inventory-view-loading">

                        <i class="fa-solid fa-spinner fa-spin"></i>

                        Loading batch information...

                    </div>

                </div>

            </div>


        </div>


        <!-- FOOTER -->

        <div class="inventory-modal-footer">

            <button
                type="button"
                class="modal-cancel-btn"
                id="cancelViewInventoryModal"
            >
                Close
            </button>


            <button
                type="button"
                class="modal-save-btn"
                id="viewItemHistoryBtn"
            >

                <i class="fa-solid fa-clock-rotate-left"></i>

                View Stock History

            </button>

        </div>

    </div>

</div>

<script src="../assets/js/layout.js"></script>
<script src="../assets/js/inventory.js"></script>

<!-- =========================================================
     INVENTORY ARCHIVE CONFIRMATION MODAL
     ========================================================= -->

<div
    class="inventory-archive-modal"
    id="inventoryArchiveModal"
    aria-hidden="true"
>

    <div
        class="inventory-archive-modal-overlay"
        id="inventoryArchiveBackdrop"
    ></div>


    <div
        class="inventory-archive-modal-content"
        role="dialog"
        aria-modal="true"
        aria-labelledby="inventoryArchiveTitle"
    >

        <!-- HEADER -->

        <div class="inventory-archive-modal-header">

            <div>

                <h3 id="inventoryArchiveTitle">
                    Archive Inventory
                </h3>

                <p>
                    Are you sure you want to archive the selected inventory items?
                </p>

            </div>


            <button
                type="button"
                class="inventory-archive-modal-close"
                id="inventoryArchiveClose"
                aria-label="Close"
            >
                <i class="fa-solid fa-xmark"></i>
            </button>

        </div>


        <!-- BODY -->

        <div class="inventory-archive-modal-body">

            <div
                class="inventory-archive-selected-name"
                id="inventoryArchiveSelectedCount"
            >
                0 inventory items selected
            </div>


            <label for="inventoryArchiveReason">
                Reason for archiving
            </label>


            <select id="inventoryArchiveReason">

                <option value="">
                    Select archive reason
                </option>

                <option value="Expired">
                    Expired
                </option>

                <option value="Damaged">
                    Damaged
                </option>

                <option value="Discontinued">
                    Discontinued
                </option>

                <option value="No longer stocked">
                    No longer stocked
                </option>

                <option value="Other">
                    Other
                </option>

            </select>


            <div
                id="inventoryArchiveError"
                class="inventory-archive-error"
                style="display: none;"
            ></div>

        </div>


        <!-- FOOTER -->

        <div class="inventory-archive-modal-footer">

            <button
                type="button"
                class="inventory-archive-cancel-btn"
                id="inventoryArchiveCancel"
            >
                Cancel
            </button>


            <button
                type="button"
                class="inventory-archive-confirm-btn"
                id="inventoryArchiveConfirm"
            >
                <i class="fa-solid fa-box-archive"></i>
                Archive Inventory
            </button>

        </div>

    </div>

</div>





</body>
</html>
