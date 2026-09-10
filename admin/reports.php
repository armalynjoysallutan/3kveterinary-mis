<?php
/* =========================================================
   REPORTS MODULE
   File: admin/reports.php
   Purpose: Filter-based report generator
   Main categories: RECORDS, SALES, INVENTORY
   ========================================================= */

session_start();

if (!isset($_SESSION['admin_username'])) {
    header('Location: ../auth/login.php');
    exit();
}

require_once __DIR__ . '/../config/database.php';

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function money($value) {
    return '₱' . number_format((float)$value, 2);
}

function fetchRows($conn, $sql, $types = '', $params = []) {
    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        die('Reports query failed: ' . mysqli_error($conn));
    }

    if ($types !== '') {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    if (!mysqli_stmt_execute($stmt)) {
        die('Reports query failed: ' . mysqli_stmt_error($stmt));
    }

    $result = mysqli_stmt_get_result($stmt);
    $rows = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }

    mysqli_stmt_close($stmt);
    return $rows;
}

/* =========================================================
   FILTER VALUES
   ========================================================= */

$category = trim($_GET['category'] ?? '');
$report = trim($_GET['report'] ?? '');

$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? '');
$status = trim($_GET['status'] ?? 'All');
$species = trim($_GET['species'] ?? 'All');
$transactionType = trim($_GET['transaction_type'] ?? 'All');
$inventoryCategory = (int)($_GET['inventory_category'] ?? 0);

$reportMap = [
    'records' => [
        'all_records' => 'All Reports',
        'customer_records' => 'Customer Records',
        'pet_records' => 'Pet Records',
        'appointment_records' => 'Appointment Records',
        'medical_records' => 'Medical Records',
    ],
    'sales' => [
        'all_sales' => 'All Reports',
        'sales_summary' => 'Sales Summary',
        'sales_details' => 'Sales Details',
    ],
    'inventory' => [
        'all_inventory' => 'All Reports',
        'current_inventory' => 'Current Inventory',
        'low_stock' => 'Low Stock',
        'expiration' => 'Expiration',
    ],
];

if (!isset($reportMap[$category])) {
    $category = '';
    $report = '';
} elseif (!isset($reportMap[$category][$report])) {
    $report = '';
}

$reportTitle = $reportMap[$category][$report] ?? 'Select a Report';

$reportDescriptions = [
    'all_records' => 'This report provides a consolidated view of the veterinary clinic\'s customer, pet, appointment, and medical records. It is intended to provide a single reference for reviewing the clinic\'s overall records.',
    'customer_records' => 'This report presents the registered customer records of the veterinary clinic, including owner information, contact details, number of pets, registration date, and record status. It is used to review and monitor the clinic\'s customer information.',
    'pet_records' => 'This report presents the registered pet records of the veterinary clinic, including owner, species, breed, gender, date of birth, registration date, and record status. It is used to review and monitor the clinic\'s patient information.',
    'appointment_records' => 'This report presents the clinic\'s appointment records, including appointment date and time, owner, pet, service, appointment type, source, and status. It is used to review and monitor scheduled and recorded appointment transactions.',
    'medical_records' => 'This report presents the medical records of pets based on the selected criteria. It includes consultation information such as weight, temperature, diagnosis, treatment, vaccination, amount paid, and next visit. It is used to review the medical history and care provided to patients.',
    'all_sales' => 'This report provides a consolidated view of the veterinary clinic\'s sales summary and detailed sales transactions. It is intended to provide a single reference for reviewing sales activity and recorded revenue.',
    'sales_summary' => 'This report summarizes the veterinary clinic\'s sales transactions by date. It presents transaction counts, gross sales, paid sales, and unpaid sales to provide an overview of sales activity during the selected period.',
    'sales_details' => 'This report presents the detailed sales transactions recorded by the veterinary clinic. It provides the billing date, billing ID, transaction type, customer or buyer, pet, total amount, and payment status for each transaction, allowing individual sales to be reviewed and verified.',
    'all_inventory' => 'This report provides a consolidated view of the veterinary clinic\'s current inventory, low-stock items, and expiration information. It is intended to provide a single reference for reviewing inventory status and identifying items that may require attention.',
    'current_inventory' => 'This report presents the current inventory of active items, including item code, category, available stock, reorder level, cost, retail price, and nearest expiration date. It is used to monitor the clinic\'s available inventory.',
    'low_stock' => 'This report identifies active inventory items whose current stock has reached or fallen below the established reorder level. It is used to help clinic personnel identify items that may require replenishment.',
    'expiration' => 'This report presents active inventory items with recorded expiration information. It is used to monitor expiration dates and help clinic personnel identify items that may require timely inventory management action.',
];

$reportDescription = $reportDescriptions[$report] ?? '';

/* =========================================================
   INVENTORY CATEGORIES
   ========================================================= */

$categories = [];

$categoryResult = mysqli_query(
    $conn,
    "SELECT category_id, category_name
     FROM inventory_categories
     ORDER BY category_name ASC"
);

if ($categoryResult) {
    while ($row = mysqli_fetch_assoc($categoryResult)) {
        $categories[] = $row;
    }
}

/* =========================================================
   REPORT DATA
   ========================================================= */

$rows = [];
$summaryCount = 0;
$summaryTotal = 0;

if ($report === 'customer_records') {

    $where = [];
    $types = '';
    $params = [];

    if ($dateFrom !== '') {
        $where[] = 'DATE(c.created_at) >= ?';
        $types .= 's';
        $params[] = $dateFrom;
    }

    if ($dateTo !== '') {
        $where[] = 'DATE(c.created_at) <= ?';
        $types .= 's';
        $params[] = $dateTo;
    }

    if (in_array($status, ['Active', 'Archived'], true)) {
        $where[] = 'c.record_status = ?';
        $types .= 's';
        $params[] = $status;
    }

    $sql = "
        SELECT
            c.customer_id,
            c.owner_name,
            c.contact_number,
            c.email,
            c.record_status,
            c.created_at,
            COUNT(p.pet_id) AS pet_count
        FROM customers c
        LEFT JOIN pets p ON p.customer_id = c.customer_id
        " . ($where ? 'WHERE ' . implode(' AND ', $where) : '') . "
        GROUP BY
            c.customer_id,
            c.owner_name,
            c.contact_number,
            c.email,
            c.record_status,
            c.created_at
        ORDER BY c.created_at DESC
    ";

    $rows = fetchRows($conn, $sql, $types, $params);
    $summaryCount = count($rows);

} elseif ($report === 'pet_records') {

    $where = [];
    $types = '';
    $params = [];

    if ($dateFrom !== '') {
        $where[] = 'DATE(p.created_at) >= ?';
        $types .= 's';
        $params[] = $dateFrom;
    }

    if ($dateTo !== '') {
        $where[] = 'DATE(p.created_at) <= ?';
        $types .= 's';
        $params[] = $dateTo;
    }

    if ($species !== '' && $species !== 'All') {
        $where[] = 'LOWER(p.species) = LOWER(?)';
        $types .= 's';
        $params[] = $species;
    }

    if (in_array($status, ['Active', 'Archived'], true)) {
        $where[] = 'c.record_status = ?';
        $types .= 's';
        $params[] = $status;
    }

    $sql = "
        SELECT
            p.pet_id,
            p.pet_name,
            c.owner_name,
            p.species,
            p.breed,
            p.gender,
            p.date_of_birth,
            p.created_at,
            c.record_status
        FROM pets p
        INNER JOIN customers c ON c.customer_id = p.customer_id
        " . ($where ? 'WHERE ' . implode(' AND ', $where) : '') . "
        ORDER BY p.created_at DESC
    ";

    $rows = fetchRows($conn, $sql, $types, $params);
    $summaryCount = count($rows);

} elseif ($report === 'appointment_records') {

    $where = [];
    $types = '';
    $params = [];

    if ($dateFrom !== '') {
        $where[] = 'a.appointment_date >= ?';
        $types .= 's';
        $params[] = $dateFrom;
    }

    if ($dateTo !== '') {
        $where[] = 'a.appointment_date <= ?';
        $types .= 's';
        $params[] = $dateTo;
    }

    if ($status !== '' && $status !== 'All') {
        $where[] = 'a.status = ?';
        $types .= 's';
        $params[] = $status;
    }

    $sql = "
        SELECT
            a.appointment_id,
            a.appointment_date,
            a.appointment_time,
            c.owner_name,
            p.pet_name,
            a.service,
            a.appointment_type,
            a.status,
            a.appointment_source
        FROM appointments a
        INNER JOIN customers c ON c.customer_id = a.customer_id
        INNER JOIN pets p ON p.pet_id = a.pet_id
        " . ($where ? 'WHERE ' . implode(' AND ', $where) : '') . "
        ORDER BY a.appointment_date DESC, a.appointment_time DESC
    ";

    $rows = fetchRows($conn, $sql, $types, $params);
    $summaryCount = count($rows);

} elseif ($report === 'medical_records') {

    $where = [];
    $types = '';
    $params = [];

    if ($dateFrom !== '') {
        $where[] = 'm.record_date >= ?';
        $types .= 's';
        $params[] = $dateFrom;
    }

    if ($dateTo !== '') {
        $where[] = 'm.record_date <= ?';
        $types .= 's';
        $params[] = $dateTo;
    }

    if ($species !== '' && $species !== 'All') {
        $where[] = 'LOWER(p.species) = LOWER(?)';
        $types .= 's';
        $params[] = $species;
    }

    $sql = "
        SELECT
            m.medical_record_id,
            m.record_date,
            p.pet_name,
            p.species,
            c.owner_name,
            m.weight,
            m.temperature,
            m.diagnosis,
            m.treatment,
            m.vaccination,
            m.amount_paid,
            m.next_visit
        FROM medical_records m
        INNER JOIN pets p ON p.pet_id = m.pet_id
        INNER JOIN customers c ON c.customer_id = p.customer_id
        " . ($where ? 'WHERE ' . implode(' AND ', $where) : '') . "
        ORDER BY m.record_date DESC, m.medical_record_id DESC
    ";

    $rows = fetchRows($conn, $sql, $types, $params);
    $summaryCount = count($rows);

} elseif ($report === 'sales_summary') {

    $where = [];
    $types = '';
    $params = [];

    if ($dateFrom !== '') {
        $where[] = 'DATE(b.created_at) >= ?';
        $types .= 's';
        $params[] = $dateFrom;
    }

    if ($dateTo !== '') {
        $where[] = 'DATE(b.created_at) <= ?';
        $types .= 's';
        $params[] = $dateTo;
    }

    if ($status !== '' && $status !== 'All') {
        $where[] = 'b.payment_status = ?';
        $types .= 's';
        $params[] = $status;
    }

    if ($transactionType === 'Appointment Billing') {
        $where[] = 'b.appointment_id IS NOT NULL';
    } elseif ($transactionType === 'Purchase / Walk-in Sale') {
        $where[] = 'b.appointment_id IS NULL';
    }

    $sql = "
        SELECT
            DATE(b.created_at) AS sale_date,
            COUNT(*) AS transaction_count,
            SUM(b.total_amount) AS gross_sales,
            SUM(CASE WHEN b.payment_status = 'Paid' THEN b.total_amount ELSE 0 END) AS paid_sales,
            SUM(CASE WHEN b.payment_status <> 'Paid' THEN b.total_amount ELSE 0 END) AS unpaid_sales
        FROM billing b
        " . ($where ? 'WHERE ' . implode(' AND ', $where) : '') . "
        GROUP BY DATE(b.created_at)
        ORDER BY sale_date DESC
    ";

    $rows = fetchRows($conn, $sql, $types, $params);
    $summaryCount = count($rows);

    foreach ($rows as $row) {
        $summaryTotal += (float)$row['paid_sales'];
    }

} elseif ($report === 'sales_details') {

    $where = [];
    $types = '';
    $params = [];

    if ($dateFrom !== '') {
        $where[] = 'DATE(b.created_at) >= ?';
        $types .= 's';
        $params[] = $dateFrom;
    }

    if ($dateTo !== '') {
        $where[] = 'DATE(b.created_at) <= ?';
        $types .= 's';
        $params[] = $dateTo;
    }

    if ($status !== '' && $status !== 'All') {
        $where[] = 'b.payment_status = ?';
        $types .= 's';
        $params[] = $status;
    }

    if ($transactionType === 'Appointment Billing') {
        $where[] = 'b.appointment_id IS NOT NULL';
    } elseif ($transactionType === 'Purchase / Walk-in Sale') {
        $where[] = 'b.appointment_id IS NULL';
    }

    $sql = "
        SELECT
            b.billing_id,
            b.created_at,
            b.appointment_id,
            CASE
                WHEN b.appointment_id IS NULL THEN 'Purchase / Walk-in Sale'
                ELSE 'Appointment Billing'
            END AS transaction_type,
            COALESCE(
                NULLIF(c.owner_name, ''),
                NULLIF(b.buyer_name, ''),
                'Walk-in Customer'
            ) AS buyer_name,
            COALESCE(p.pet_name, '—') AS pet_name,
            b.total_amount,
            b.payment_status
        FROM billing b
        LEFT JOIN customers c ON c.customer_id = b.customer_id
        LEFT JOIN pets p ON p.pet_id = b.pet_id
        " . ($where ? 'WHERE ' . implode(' AND ', $where) : '') . "
        ORDER BY b.created_at DESC
    ";

    $rows = fetchRows($conn, $sql, $types, $params);
    $summaryCount = count($rows);

    foreach ($rows as $row) {
        if (($row['payment_status'] ?? '') === 'Paid') {
            $summaryTotal += (float)$row['total_amount'];
        }
    }

} elseif (in_array($report, ['current_inventory', 'low_stock', 'expiration'], true)) {

    $where = ["i.status = 'Active'"];
    $types = '';
    $params = [];

    if ($inventoryCategory > 0) {
        $where[] = 'i.category_id = ?';
        $types .= 'i';
        $params[] = $inventoryCategory;
    }

    $stockSubquery = "
        SELECT
            item_id,
            SUM(quantity) AS total_stock,
            MIN(
                CASE
                    WHEN quantity > 0 AND expiration_date IS NOT NULL
                    THEN expiration_date
                END
            ) AS nearest_expiry
        FROM inventory_stock
        GROUP BY item_id
    ";

    if ($report === 'low_stock') {
        $where[] = 'i.reorder_level > 0';
        $where[] = 'COALESCE(st.total_stock, 0) <= i.reorder_level';
    }

    if ($report === 'expiration') {
        $where[] = 'st.nearest_expiry IS NOT NULL';

        if ($dateFrom !== '') {
            $where[] = 'st.nearest_expiry >= ?';
            $types .= 's';
            $params[] = $dateFrom;
        }

        if ($dateTo !== '') {
            $where[] = 'st.nearest_expiry <= ?';
            $types .= 's';
            $params[] = $dateTo;
        }
    }

    $orderBy = 'i.item_name ASC';

    if ($report === 'low_stock') {
        $orderBy = 'COALESCE(st.total_stock, 0) ASC, i.item_name ASC';
    } elseif ($report === 'expiration') {
        $orderBy = 'st.nearest_expiry ASC, i.item_name ASC';
    }

    $sql = "
        SELECT
            i.item_id,
            i.item_code,
            i.item_name,
            c.category_name,
            i.reorder_level,
            COALESCE(st.total_stock, 0) AS total_stock,
            i.unit_cost,
            i.retail_price,
            st.nearest_expiry
        FROM inventory_items i
        INNER JOIN inventory_categories c ON c.category_id = i.category_id
        LEFT JOIN ($stockSubquery) st ON st.item_id = i.item_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY $orderBy
    ";

    $rows = fetchRows($conn, $sql, $types, $params);
    $summaryCount = count($rows);
}


if ($report === 'all_records') {
    $allCustomerRows = fetchRows($conn, "SELECT c.customer_id,c.owner_name,c.contact_number,c.email,c.record_status,c.created_at,COUNT(p.pet_id) AS pet_count FROM customers c LEFT JOIN pets p ON p.customer_id=c.customer_id" . (($status !== 'All' && in_array($status,['Active','Archived'],true)) ? " WHERE c.record_status='" . mysqli_real_escape_string($conn,$status) . "'" : '') . " GROUP BY c.customer_id,c.owner_name,c.contact_number,c.email,c.record_status,c.created_at ORDER BY c.created_at DESC");
    $allPetRows = fetchRows($conn, "SELECT p.pet_id,p.pet_name,c.owner_name,p.species,p.breed,p.gender,p.date_of_birth,p.created_at,c.record_status FROM pets p INNER JOIN customers c ON c.customer_id=p.customer_id" . (($species !== 'All' && $species !== '') ? " WHERE LOWER(p.species)=LOWER('" . mysqli_real_escape_string($conn,$species) . "')" : '') . " ORDER BY p.created_at DESC");
    $allAppointmentRows = fetchRows($conn, "SELECT a.appointment_id,a.appointment_date,a.appointment_time,c.owner_name,p.pet_name,a.service,a.appointment_type,a.status,a.appointment_source FROM appointments a INNER JOIN customers c ON c.customer_id=a.customer_id INNER JOIN pets p ON p.pet_id=a.pet_id" . (($status !== 'All' && $status !== '') ? " WHERE a.status='" . mysqli_real_escape_string($conn,$status) . "'" : '') . " ORDER BY a.appointment_date DESC,a.appointment_time DESC");
    $allMedicalRows = fetchRows($conn, "SELECT m.medical_record_id,m.record_date,p.pet_name,p.species,c.owner_name,m.weight,m.temperature,m.diagnosis,m.treatment,m.vaccination,m.amount_paid,m.next_visit FROM medical_records m INNER JOIN pets p ON p.pet_id=m.pet_id INNER JOIN customers c ON c.customer_id=p.customer_id" . (($species !== 'All' && $species !== '') ? " WHERE LOWER(p.species)=LOWER('" . mysqli_real_escape_string($conn,$species) . "')" : '') . " ORDER BY m.record_date DESC,m.medical_record_id DESC");
    $rows=array_merge($allCustomerRows,$allPetRows,$allAppointmentRows,$allMedicalRows); $summaryCount=count($rows);
} elseif ($report === 'all_sales') {
    $allSalesSummaryRows=fetchRows($conn,"SELECT DATE(b.created_at) AS sale_date,COUNT(*) AS transaction_count,SUM(b.total_amount) AS gross_sales,SUM(CASE WHEN b.payment_status='Paid' THEN b.total_amount ELSE 0 END) AS paid_sales,SUM(CASE WHEN b.payment_status<>'Paid' THEN b.total_amount ELSE 0 END) AS unpaid_sales FROM billing b GROUP BY DATE(b.created_at) ORDER BY sale_date DESC");
    $allSalesDetailRows=fetchRows($conn,"SELECT b.billing_id,b.created_at,CASE WHEN b.appointment_id IS NULL THEN 'Purchase / Walk-in Sale' ELSE 'Appointment Billing' END AS transaction_type,COALESCE(NULLIF(c.owner_name,''),NULLIF(b.buyer_name,''),'Walk-in Customer') AS buyer_name,COALESCE(p.pet_name,'—') AS pet_name,b.total_amount,b.payment_status FROM billing b LEFT JOIN customers c ON c.customer_id=b.customer_id LEFT JOIN pets p ON p.pet_id=b.pet_id ORDER BY b.created_at DESC");
    foreach($allSalesSummaryRows as $r){$summaryTotal+=(float)$r['paid_sales'];} $rows=$allSalesDetailRows; $summaryCount=count($rows);
} elseif ($report === 'all_inventory') {
    $allCurrentInventoryRows=fetchRows($conn,"SELECT i.item_id,i.item_code,i.item_name,c.category_name,i.reorder_level,COALESCE(st.total_stock,0) AS total_stock,i.unit_cost,i.retail_price,st.nearest_expiry FROM inventory_items i INNER JOIN inventory_categories c ON c.category_id=i.category_id LEFT JOIN (SELECT item_id,SUM(quantity) AS total_stock,MIN(CASE WHEN quantity>0 AND expiration_date IS NOT NULL THEN expiration_date END) AS nearest_expiry FROM inventory_stock GROUP BY item_id) st ON st.item_id=i.item_id WHERE i.status='Active' ORDER BY i.item_name ASC");
    $allLowStockRows=fetchRows($conn,"SELECT i.item_id,i.item_code,i.item_name,c.category_name,i.reorder_level,COALESCE(st.total_stock,0) AS total_stock,i.unit_cost,i.retail_price FROM inventory_items i INNER JOIN inventory_categories c ON c.category_id=i.category_id LEFT JOIN (SELECT item_id,SUM(quantity) AS total_stock FROM inventory_stock GROUP BY item_id) st ON st.item_id=i.item_id WHERE i.status='Active' AND i.reorder_level>0 AND COALESCE(st.total_stock,0)<=i.reorder_level ORDER BY COALESCE(st.total_stock,0) ASC,i.item_name ASC");
    $allExpirationRows=fetchRows($conn,"SELECT i.item_id,i.item_code,i.item_name,c.category_name,COALESCE(st.total_stock,0) AS total_stock,i.retail_price,st.nearest_expiry FROM inventory_items i INNER JOIN inventory_categories c ON c.category_id=i.category_id LEFT JOIN (SELECT item_id,SUM(quantity) AS total_stock,MIN(CASE WHEN quantity>0 AND expiration_date IS NOT NULL THEN expiration_date END) AS nearest_expiry FROM inventory_stock GROUP BY item_id) st ON st.item_id=i.item_id WHERE i.status='Active' AND st.nearest_expiry IS NOT NULL ORDER BY st.nearest_expiry ASC,i.item_name ASC");
    $rows=array_merge($allCurrentInventoryRows,$allLowStockRows,$allExpirationRows); $summaryCount=count($rows);
}

$reportInterpretation = '';
if ($report === 'all_records') {
    $reportInterpretation = 'The consolidated report contains ' . number_format(count($allCustomerRows ?? [])) . ' customer records, ' . number_format(count($allPetRows ?? [])) . ' pet records, ' . number_format(count($allAppointmentRows ?? [])) . ' appointment records, and ' . number_format(count($allMedicalRows ?? [])) . ' medical records. These sections provide a combined view of the clinic\'s records based on the selected reporting criteria.';
} elseif ($report === 'customer_records') {
    $reportInterpretation = 'The report contains ' . number_format($summaryCount) . ' customer record(s) based on the selected reporting criteria. The listed records can be used to review registered owners and their current record status.';
} elseif ($report === 'pet_records') {
    $reportInterpretation = 'The report contains ' . number_format($summaryCount) . ' pet record(s) based on the selected reporting criteria. The listed records provide a reference for the clinic\'s registered patients.';
} elseif ($report === 'appointment_records') {
    $reportInterpretation = 'The report contains ' . number_format($summaryCount) . ' appointment record(s) based on the selected reporting criteria. These records provide a reference for reviewing appointment activity and status.';
} elseif ($report === 'medical_records') {
    $reportInterpretation = 'The report contains ' . number_format($summaryCount) . ' medical record(s) based on the selected reporting criteria. These records provide a reference for reviewing consultations and the medical care recorded for patients.';
} elseif ($report === 'all_sales') {
    $reportInterpretation = 'The consolidated report contains ' . number_format(count($allSalesDetailRows ?? [])) . ' sales transaction(s) across ' . number_format(count($allSalesSummaryRows ?? [])) . ' sales date(s), with recorded paid sales of ' . money($summaryTotal) . '. The two sections provide both an overall summary and a detailed transaction reference.';
} elseif ($report === 'sales_summary') {
    $reportInterpretation = 'The report summarizes ' . number_format($summaryCount) . ' sales date(s), with total recorded paid sales of ' . money($summaryTotal) . ' based on the selected reporting criteria. The summary can be used to review sales activity across the selected period.';
} elseif ($report === 'sales_details') {
    $reportInterpretation = 'The report contains ' . number_format($summaryCount) . ' sales transaction(s), with total recorded paid sales of ' . money($summaryTotal) . ' based on the selected reporting criteria. The detailed entries can be used to review individual billing transactions.';
} elseif ($report === 'all_inventory') {
    $reportInterpretation = 'The consolidated report contains ' . number_format(count($allCurrentInventoryRows ?? [])) . ' current inventory item(s), ' . number_format(count($allLowStockRows ?? [])) . ' low-stock item(s), and ' . number_format(count($allExpirationRows ?? [])) . ' item(s) with expiration information. These sections provide a combined view of inventory status.';
} elseif ($report === 'current_inventory') {
    $reportInterpretation = 'The report contains ' . number_format($summaryCount) . ' active inventory item(s) based on the selected criteria. The listed stock, reorder level, pricing, and expiration information can be used to review current inventory status.';
} elseif ($report === 'low_stock') {
    $reportInterpretation = 'The report identifies ' . number_format($summaryCount) . ' low-stock item(s) based on the selected criteria. These items have reached or fallen below their established reorder level and may require replenishment.';
} elseif ($report === 'expiration') {
    $reportInterpretation = 'The report contains ' . number_format($summaryCount) . ' inventory item(s) with recorded expiration information based on the selected criteria. The listed dates can be used to monitor items that may require timely action.';
}

if ($report !== '' && $summaryCount === 0) {
    $reportInterpretation = 'No records were found based on the selected reporting criteria. The filters may be adjusted and the report generated again to review other records.';
}


$hasResults = $report !== '';

$categoryLabel = $category === 'records'
    ? 'RECORDS'
    : ($category === 'sales' ? 'SALES' : ($category === 'inventory' ? 'INVENTORY' : ''));

$filterParts = [];

if ($dateFrom !== '') {
    $filterParts[] = 'From: ' . date('m/d/Y', strtotime($dateFrom));
}
if ($dateTo !== '') {
    $filterParts[] = 'To: ' . date('m/d/Y', strtotime($dateTo));
}
if ($status !== 'All' && $status !== '') {
    $filterParts[] = 'Status: ' . $status;
}
if ($species !== 'All' && $species !== '') {
    $filterParts[] = 'Species: ' . $species;
}
if ($transactionType !== 'All' && $transactionType !== '') {
    $filterParts[] = 'Type: ' . $transactionType;
}

$selectedInventoryCategory = 'All Categories';
foreach ($categories as $cat) {
    if ((int)$cat['category_id'] === $inventoryCategory) {
        $selectedInventoryCategory = $cat['category_name'];
        break;
    }
}
if ($inventoryCategory > 0) {
    $filterParts[] = 'Category: ' . $selectedInventoryCategory;
}

$filterText = $filterParts ? implode(' • ', $filterParts) : 'All available records';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports | Veterinary MIS</title>

    <link rel="stylesheet" href="../assets/css/layout.css">
    <link rel="stylesheet" href="../assets/css/reports.css">

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
    >
</head>

<body>
<div class="container">

    <?php include __DIR__ . '/partials/sidebar.php'; ?>

    <main class="content">

        <?php
        $pageTitle = "Reports";
        $showAdminInfo = false;
        include __DIR__ . '/partials/topbar.php';
        ?>

        <div class="page-content reports-page">

            <div class="reports-head">
                <h1>Reports</h1>
                <p>Generate filtered reports for records, sales, and inventory.</p>
            </div>

            <section class="report-filter-card">

                <div class="report-filter-header">
                    <div>
                        <span class="section-kicker">REPORT GENERATOR</span>
                        <h2>Report Filters</h2>
                        <p>Choose a main category, select its report, then apply the available filters.</p>
                    </div>
                </div>

                <form method="GET" action="reports.php" id="reportFilterForm">

                    <div class="filter-grid">

                        <div class="field">
                            <label for="category">Report Category</label>
                            <select name="category" id="category" required>
                                <option value="">Select Category</option>
                                <option value="records" <?= $category === 'records' ? 'selected' : '' ?>>
                                    Records
                                </option>
                                <option value="sales" <?= $category === 'sales' ? 'selected' : '' ?>>
                                    Sales
                                </option>
                                <option value="inventory" <?= $category === 'inventory' ? 'selected' : '' ?>>
                                    Inventory
                                </option>
                            </select>
                        </div>

                        <div class="field">
                            <label for="report">Report</label>
                            <select name="report" id="report" required disabled>
                                <option value="">Select Report</option>
                            </select>
                        </div>

                        <div class="field date-field">
                            <label for="date_from">Date From</label>
                            <input type="date" name="date_from" id="date_from" value="<?= e($dateFrom) ?>">
                        </div>

                        <div class="field date-field">
                            <label for="date_to">Date To</label>
                            <input type="date" name="date_to" id="date_to" value="<?= e($dateTo) ?>">
                        </div>

                        <div class="field filter-status">
                            <label for="status">Status</label>
                            <select name="status" id="status">
                                <option value="All" <?= $status === 'All' ? 'selected' : '' ?>>All</option>
                                <option value="Active" <?= $status === 'Active' ? 'selected' : '' ?>>Active</option>
                                <option value="Archived" <?= $status === 'Archived' ? 'selected' : '' ?>>Archived</option>
                                <option value="Pending" <?= $status === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="Confirmed" <?= $status === 'Confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                <option value="Arrived" <?= $status === 'Arrived' ? 'selected' : '' ?>>Arrived</option>
                                <option value="Completed" <?= $status === 'Completed' ? 'selected' : '' ?>>Completed</option>
                                <option value="Cancelled" <?= $status === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                <option value="Paid" <?= $status === 'Paid' ? 'selected' : '' ?>>Paid</option>
                            </select>
                        </div>

                        <div class="field filter-species">
                            <label for="species">Species</label>
                            <select name="species" id="species">
                                <option value="All" <?= $species === 'All' ? 'selected' : '' ?>>All Species</option>
                                <option value="Dog" <?= $species === 'Dog' ? 'selected' : '' ?>>Dog</option>
                                <option value="Cat" <?= $species === 'Cat' ? 'selected' : '' ?>>Cat</option>
                            </select>
                        </div>

                        <div class="field filter-transaction">
                            <label for="transaction_type">Transaction Type</label>
                            <select name="transaction_type" id="transaction_type">
                                <option value="All" <?= $transactionType === 'All' ? 'selected' : '' ?>>All Transactions</option>
                                <option value="Appointment Billing" <?= $transactionType === 'Appointment Billing' ? 'selected' : '' ?>>
                                    Appointment Billing
                                </option>
                                <option value="Purchase / Walk-in Sale" <?= $transactionType === 'Purchase / Walk-in Sale' ? 'selected' : '' ?>>
                                    Purchase / Walk-in Sale
                                </option>
                            </select>
                        </div>

                        <div class="field filter-inventory-category">
                            <label for="inventory_category">Inventory Category</label>
                            <select name="inventory_category" id="inventory_category">
                                <option value="0">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option
                                        value="<?= (int)$cat['category_id'] ?>"
                                        <?= $inventoryCategory === (int)$cat['category_id'] ? 'selected' : '' ?>
                                    >
                                        <?= e($cat['category_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                    </div>

                    <div class="filter-actions">
                        <a href="reports.php" class="clear-btn">
                            <i class="fa-solid fa-rotate-left"></i>
                            Clear
                        </a>

                        <button type="submit" class="generate-btn">
                            <i class="fa-solid fa-filter"></i>
                            Generate Report
                        </button>
                    </div>

                </form>
            </section>

            <?php if ($hasResults): ?>

                <section class="report-results-card" id="reportResults">

                    <div class="results-header">
                        <div>
                            <span class="results-kicker"><?= e($categoryLabel) ?></span>
                            <h2><?= e($reportTitle) ?></h2>
                            <p><?= e($filterText) ?></p>
                        </div>

                        <button type="button" class="print-btn" id="printReportBtn">
                            <i class="fa-solid fa-print"></i>
                            Print Report
                        </button>
                    </div>

                    <div class="result-summary">
                        <div>
                            <span>RESULTS</span>
                            <strong><?= number_format($summaryCount) ?></strong>
                        </div>

                        <?php if (in_array($report, ['sales_summary', 'sales_details'], true)): ?>
                            <div>
                                <span>PAID SALES</span>
                                <strong><?= money($summaryTotal) ?></strong>
                            </div>
                        <?php endif; ?>

                        <div class="generated-date">
                            <span>GENERATED</span>
                            <strong><?= e(date('m/d/Y h:i A')) ?></strong>
                        </div>
                    </div>

                    <div class="report-description">
                        <div class="report-section-label">REPORT DESCRIPTION</div>
                        <p><?= e($reportDescription) ?></p>
                    </div>

                    <div class="report-interpretation">
                        <div class="report-section-label">REPORT SUMMARY / INTERPRETATION</div>
                        <p><?= e($reportInterpretation) ?></p>
                    </div>

                    <div class="table-wrap">

                        <?php if (!$rows): ?>

                            <div class="empty-report">
                                <i class="fa-regular fa-folder-open"></i>
                                <strong>No records found</strong>
                                <span>Try changing the selected filters.</span>
                            </div>

                        <?php elseif ($report === 'all_records'): ?>
<div class="all-report-section"><div class="all-report-section-header"><div><h3>Customer Records</h3><span>Owner/customer information</span></div><strong><?= count($allCustomerRows) ?> records</strong></div><div class="table-wrap"><table class="report-table"><thead><tr><th>CUSTOMER ID</th><th>OWNER NAME</th><th>CONTACT</th><th>EMAIL</th><th>PETS</th><th>DATE REGISTERED</th><th>STATUS</th></tr></thead><tbody><?php foreach($allCustomerRows as $row): ?><tr><td>CUST-<?= str_pad((int)$row['customer_id'],3,'0',STR_PAD_LEFT) ?></td><td><?= e($row['owner_name']) ?></td><td><?= e($row['contact_number']) ?></td><td><?= e($row['email'] ?: '—') ?></td><td><?= number_format((int)$row['pet_count']) ?></td><td><?= e(date('m/d/Y',strtotime($row['created_at']))) ?></td><td><span class="pill <?= strtolower($row['record_status']) ?>"><?= e($row['record_status']) ?></span></td></tr><?php endforeach; if(!$allCustomerRows): ?><tr><td colspan="7">No customer records found.</td></tr><?php endif; ?></tbody></table></div></div>
<div class="all-report-section"><div class="all-report-section-header"><div><h3>Pet Records</h3><span>Registered pet information</span></div><strong><?= count($allPetRows) ?> records</strong></div><div class="table-wrap"><table class="report-table"><thead><tr><th>PET ID</th><th>PET NAME</th><th>OWNER</th><th>SPECIES</th><th>BREED</th><th>SEX</th><th>DATE OF BIRTH</th><th>STATUS</th></tr></thead><tbody><?php foreach($allPetRows as $row): ?><tr><td>PET-<?= str_pad((int)$row['pet_id'],3,'0',STR_PAD_LEFT) ?></td><td><?= e($row['pet_name']) ?></td><td><?= e($row['owner_name']) ?></td><td><?= e(ucfirst($row['species'])) ?></td><td><?= e($row['breed'] ?: '—') ?></td><td><?= e($row['gender'] ?: '—') ?></td><td><?= !empty($row['date_of_birth']) ? e(date('m/d/Y',strtotime($row['date_of_birth']))) : '—' ?></td><td><?= e($row['record_status']) ?></td></tr><?php endforeach; if(!$allPetRows): ?><tr><td colspan="8">No pet records found.</td></tr><?php endif; ?></tbody></table></div></div>
<div class="all-report-section"><div class="all-report-section-header"><div><h3>Appointment Records</h3><span>Appointment and scheduling information</span></div><strong><?= count($allAppointmentRows) ?> records</strong></div><div class="table-wrap"><table class="report-table"><thead><tr><th>APPOINTMENT ID</th><th>DATE</th><th>TIME</th><th>OWNER</th><th>PET</th><th>SERVICE</th><th>TYPE</th><th>SOURCE</th><th>STATUS</th></tr></thead><tbody><?php foreach($allAppointmentRows as $row): ?><tr><td>APT-<?= str_pad((int)$row['appointment_id'],3,'0',STR_PAD_LEFT) ?></td><td><?= e(date('m/d/Y',strtotime($row['appointment_date']))) ?></td><td><?= e(date('h:i A',strtotime($row['appointment_time']))) ?></td><td><?= e($row['owner_name']) ?></td><td><?= e($row['pet_name']) ?></td><td><?= e($row['service']) ?></td><td><?= e($row['appointment_type'] ?: '—') ?></td><td><?= e($row['appointment_source']) ?></td><td><?= e($row['status']) ?></td></tr><?php endforeach; if(!$allAppointmentRows): ?><tr><td colspan="9">No appointment records found.</td></tr><?php endif; ?></tbody></table></div></div>
<div class="all-report-section"><div class="all-report-section-header"><div><h3>Medical Records</h3><span>Consultation and treatment records</span></div><strong><?= count($allMedicalRows) ?> records</strong></div><div class="table-wrap"><table class="report-table"><thead><tr><th>RECORD ID</th><th>DATE</th><th>OWNER</th><th>PET</th><th>SPECIES</th><th>WEIGHT</th><th>TEMP.</th><th>DIAGNOSIS</th><th>TREATMENT</th><th>VACCINATION</th><th>AMOUNT PAID</th><th>NEXT VISIT</th></tr></thead><tbody><?php foreach($allMedicalRows as $row): ?><tr><td>MR-<?= str_pad((int)$row['medical_record_id'],3,'0',STR_PAD_LEFT) ?></td><td><?= e(date('m/d/Y',strtotime($row['record_date']))) ?></td><td><?= e($row['owner_name']) ?></td><td><?= e($row['pet_name']) ?></td><td><?= e(ucfirst($row['species'])) ?></td><td><?= $row['weight']!==null?e($row['weight']).' kg':'—' ?></td><td><?= $row['temperature']!==null?e($row['temperature']).' °C':'—' ?></td><td><?= e($row['diagnosis'] ?: '—') ?></td><td><?= e($row['treatment'] ?: '—') ?></td><td><?= e($row['vaccination'] ?: '—') ?></td><td><?= money($row['amount_paid']) ?></td><td><?= !empty($row['next_visit'])?e(date('m/d/Y',strtotime($row['next_visit']))):'—' ?></td></tr><?php endforeach; if(!$allMedicalRows): ?><tr><td colspan="12">No medical records found.</td></tr><?php endif; ?></tbody></table></div></div>
<?php elseif ($report === 'all_sales'): ?>
<div class="all-report-section"><div class="all-report-section-header"><div><h3>Sales Summary</h3><span>Sales grouped by date</span></div><strong><?= count($allSalesSummaryRows) ?> days</strong></div><div class="table-wrap"><table class="report-table"><thead><tr><th>DATE</th><th>TRANSACTIONS</th><th>GROSS SALES</th><th>PAID SALES</th><th>UNPAID SALES</th></tr></thead><tbody><?php foreach($allSalesSummaryRows as $row): ?><tr><td><?= e(date('m/d/Y',strtotime($row['sale_date']))) ?></td><td><?= number_format((int)$row['transaction_count']) ?></td><td><?= money($row['gross_sales']) ?></td><td><?= money($row['paid_sales']) ?></td><td><?= money($row['unpaid_sales']) ?></td></tr><?php endforeach; if(!$allSalesSummaryRows): ?><tr><td colspan="5">No sales summary found.</td></tr><?php endif; ?></tbody></table></div></div>
<div class="all-report-section"><div class="all-report-section-header"><div><h3>Sales Details</h3><span>Billing transactions included in the summary</span></div><strong><?= count($allSalesDetailRows) ?> records</strong></div><div class="table-wrap"><table class="report-table"><thead><tr><th>DATE</th><th>BILLING ID</th><th>TYPE</th><th>CUSTOMER / BUYER</th><th>PET</th><th>TOTAL AMOUNT</th><th>STATUS</th></tr></thead><tbody><?php foreach($allSalesDetailRows as $row): ?><tr><td><?= e(date('m/d/Y h:i A',strtotime($row['created_at']))) ?></td><td>BILL-<?= str_pad((int)$row['billing_id'],3,'0',STR_PAD_LEFT) ?></td><td><?= e($row['transaction_type']) ?></td><td><?= e($row['buyer_name']) ?></td><td><?= e($row['pet_name']) ?></td><td><?= money($row['total_amount']) ?></td><td><?= e($row['payment_status']) ?></td></tr><?php endforeach; if(!$allSalesDetailRows): ?><tr><td colspan="7">No sales detail found.</td></tr><?php endif; ?></tbody></table></div></div>
<?php elseif ($report === 'all_inventory'): ?>
<div class="all-report-section"><div class="all-report-section-header"><div><h3>Current Inventory</h3><span>Active items and current stock</span></div><strong><?= count($allCurrentInventoryRows) ?> items</strong></div><div class="table-wrap"><table class="report-table"><thead><tr><th>ITEM CODE</th><th>ITEM NAME</th><th>CATEGORY</th><th>CURRENT STOCK</th><th>REORDER LEVEL</th><th>UNIT COST</th><th>RETAIL PRICE</th><th>NEAREST EXPIRY</th></tr></thead><tbody><?php foreach($allCurrentInventoryRows as $row): ?><tr><td><?= e($row['item_code']) ?></td><td><?= e($row['item_name']) ?></td><td><?= e($row['category_name']) ?></td><td><?= number_format((float)$row['total_stock'],2) ?></td><td><?= number_format((float)$row['reorder_level'],2) ?></td><td><?= money($row['unit_cost']) ?></td><td><?= money($row['retail_price']) ?></td><td><?= !empty($row['nearest_expiry'])?e(date('m/d/Y',strtotime($row['nearest_expiry']))):'—' ?></td></tr><?php endforeach; if(!$allCurrentInventoryRows): ?><tr><td colspan="8">No inventory records found.</td></tr><?php endif; ?></tbody></table></div></div>
<div class="all-report-section"><div class="all-report-section-header"><div><h3>Low Stock</h3><span>Active items at or below reorder level</span></div><strong><?= count($allLowStockRows) ?> items</strong></div><div class="table-wrap"><table class="report-table"><thead><tr><th>ITEM CODE</th><th>ITEM NAME</th><th>CATEGORY</th><th>CURRENT STOCK</th><th>REORDER LEVEL</th><th>UNIT COST</th><th>RETAIL PRICE</th></tr></thead><tbody><?php foreach($allLowStockRows as $row): ?><tr><td><?= e($row['item_code']) ?></td><td><?= e($row['item_name']) ?></td><td><?= e($row['category_name']) ?></td><td><?= number_format((float)$row['total_stock'],2) ?></td><td><?= number_format((float)$row['reorder_level'],2) ?></td><td><?= money($row['unit_cost']) ?></td><td><?= money($row['retail_price']) ?></td></tr><?php endforeach; if(!$allLowStockRows): ?><tr><td colspan="7">No low-stock items found.</td></tr><?php endif; ?></tbody></table></div></div>
<div class="all-report-section"><div class="all-report-section-header"><div><h3>Expiration</h3><span>Inventory batches with expiration dates</span></div><strong><?= count($allExpirationRows) ?> records</strong></div><div class="table-wrap"><table class="report-table"><thead><tr><th>ITEM CODE</th><th>ITEM NAME</th><th>CATEGORY</th><th>CURRENT STOCK</th><th>NEAREST EXPIRY</th><th>RETAIL PRICE</th></tr></thead><tbody><?php foreach($allExpirationRows as $row): ?><tr><td><?= e($row['item_code']) ?></td><td><?= e($row['item_name']) ?></td><td><?= e($row['category_name']) ?></td><td><?= number_format((float)$row['total_stock'],2) ?></td><td><?= !empty($row['nearest_expiry'])?e(date('m/d/Y',strtotime($row['nearest_expiry']))):'—' ?></td><td><?= money($row['retail_price']) ?></td></tr><?php endforeach; if(!$allExpirationRows): ?><tr><td colspan="6">No expiration records found.</td></tr><?php endif; ?></tbody></table></div></div>
<?php elseif ($report === 'customer_records'): ?>

                            <table class="report-table">
                                <thead>
                                <tr>
                                    <th>CUSTOMER ID</th>
                                    <th>OWNER NAME</th>
                                    <th>CONTACT</th>
                                    <th>EMAIL</th>
                                    <th>PETS</th>
                                    <th>DATE REGISTERED</th>
                                    <th>STATUS</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($rows as $row): ?>
                                    <tr>
                                        <td>CUST-<?= str_pad((int)$row['customer_id'], 3, '0', STR_PAD_LEFT) ?></td>
                                        <td><?= e($row['owner_name']) ?></td>
                                        <td><?= e($row['contact_number']) ?></td>
                                        <td><?= e($row['email'] ?: '—') ?></td>
                                        <td><?= number_format((int)$row['pet_count']) ?></td>
                                        <td><?= e(date('m/d/Y', strtotime($row['created_at']))) ?></td>
                                        <td><span class="pill <?= strtolower($row['record_status']) ?>"><?= e($row['record_status']) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>

                        <?php elseif ($report === 'pet_records'): ?>

                            <table class="report-table">
                                <thead>
                                <tr>
                                    <th>PET ID</th>
                                    <th>PET NAME</th>
                                    <th>OWNER</th>
                                    <th>SPECIES</th>
                                    <th>BREED</th>
                                    <th>SEX</th>
                                    <th>DATE OF BIRTH</th>
                                    <th>DATE REGISTERED</th>
                                    <th>STATUS</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($rows as $row): ?>
                                    <tr>
                                        <td>PET-<?= str_pad((int)$row['pet_id'], 3, '0', STR_PAD_LEFT) ?></td>
                                        <td><?= e($row['pet_name']) ?></td>
                                        <td><?= e($row['owner_name']) ?></td>
                                        <td><?= e(ucfirst($row['species'])) ?></td>
                                        <td><?= e($row['breed'] ?: '—') ?></td>
                                        <td><?= e($row['gender'] ?: '—') ?></td>
                                        <td><?= !empty($row['date_of_birth']) ? e(date('m/d/Y', strtotime($row['date_of_birth']))) : '—' ?></td>
                                        <td><?= e(date('m/d/Y', strtotime($row['created_at']))) ?></td>
                                        <td><span class="pill <?= strtolower($row['record_status']) ?>"><?= e($row['record_status']) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>

                        <?php elseif ($report === 'appointment_records'): ?>

                            <table class="report-table">
                                <thead>
                                <tr>
                                    <th>APPOINTMENT ID</th>
                                    <th>DATE</th>
                                    <th>TIME</th>
                                    <th>OWNER</th>
                                    <th>PET</th>
                                    <th>SERVICE</th>
                                    <th>TYPE</th>
                                    <th>SOURCE</th>
                                    <th>STATUS</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($rows as $row): ?>
                                    <tr>
                                        <td>APT-<?= str_pad((int)$row['appointment_id'], 3, '0', STR_PAD_LEFT) ?></td>
                                        <td><?= e(date('m/d/Y', strtotime($row['appointment_date']))) ?></td>
                                        <td><?= e(date('h:i A', strtotime($row['appointment_time']))) ?></td>
                                        <td><?= e($row['owner_name']) ?></td>
                                        <td><?= e($row['pet_name']) ?></td>
                                        <td><?= e($row['service']) ?></td>
                                        <td><?= e($row['appointment_type'] ?: '—') ?></td>
                                        <td><?= e($row['appointment_source']) ?></td>
                                        <td><span class="pill status-pill"><?= e($row['status']) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>

                        <?php elseif ($report === 'medical_records'): ?>

                            <table class="report-table">
                                <thead>
                                <tr>
                                    <th>RECORD ID</th>
                                    <th>DATE</th>
                                    <th>OWNER</th>
                                    <th>PET</th>
                                    <th>SPECIES</th>
                                    <th>WEIGHT</th>
                                    <th>TEMP.</th>
                                    <th>DIAGNOSIS</th>
                                    <th>TREATMENT</th>
                                    <th>VACCINATION</th>
                                    <th>AMOUNT PAID</th>
                                    <th>NEXT VISIT</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($rows as $row): ?>
                                    <tr>
                                        <td>MR-<?= str_pad((int)$row['medical_record_id'], 3, '0', STR_PAD_LEFT) ?></td>
                                        <td><?= e(date('m/d/Y', strtotime($row['record_date']))) ?></td>
                                        <td><?= e($row['owner_name']) ?></td>
                                        <td><?= e($row['pet_name']) ?></td>
                                        <td><?= e(ucfirst($row['species'])) ?></td>
                                        <td><?= $row['weight'] !== null ? e($row['weight']) . ' kg' : '—' ?></td>
                                        <td><?= $row['temperature'] !== null ? e($row['temperature']) . ' °C' : '—' ?></td>
                                        <td><?= e($row['diagnosis'] ?: '—') ?></td>
                                        <td><?= e($row['treatment'] ?: '—') ?></td>
                                        <td><?= e($row['vaccination'] ?: '—') ?></td>
                                        <td><?= money($row['amount_paid']) ?></td>
                                        <td><?= !empty($row['next_visit']) ? e(date('m/d/Y', strtotime($row['next_visit']))) : '—' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>

                        <?php elseif ($report === 'sales_summary'): ?>

                            <table class="report-table">
                                <thead>
                                <tr>
                                    <th>DATE</th>
                                    <th>TRANSACTIONS</th>
                                    <th>GROSS SALES</th>
                                    <th>PAID SALES</th>
                                    <th>UNPAID SALES</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($rows as $row): ?>
                                    <tr>
                                        <td><?= e(date('m/d/Y', strtotime($row['sale_date']))) ?></td>
                                        <td><?= number_format((int)$row['transaction_count']) ?></td>
                                        <td><?= money($row['gross_sales']) ?></td>
                                        <td><?= money($row['paid_sales']) ?></td>
                                        <td><?= money($row['unpaid_sales']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>

                        <?php elseif ($report === 'sales_details'): ?>

                            <table class="report-table">
                                <thead>
                                <tr>
                                    <th>DATE</th>
                                    <th>BILLING ID</th>
                                    <th>TYPE</th>
                                    <th>CUSTOMER / BUYER</th>
                                    <th>PET</th>
                                    <th>TOTAL AMOUNT</th>
                                    <th>STATUS</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($rows as $row): ?>
                                    <tr>
                                        <td><?= e(date('m/d/Y h:i A', strtotime($row['created_at']))) ?></td>
                                        <td>BILL-<?= str_pad((int)$row['billing_id'], 3, '0', STR_PAD_LEFT) ?></td>
                                        <td><?= e($row['transaction_type']) ?></td>
                                        <td><?= e($row['buyer_name']) ?></td>
                                        <td><?= e($row['pet_name']) ?></td>
                                        <td><?= money($row['total_amount']) ?></td>
                                        <td><span class="pill <?= strtolower($row['payment_status']) ?>"><?= e($row['payment_status']) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>

                        <?php elseif ($report === 'current_inventory'): ?>

                            <table class="report-table">
                                <thead>
                                <tr>
                                    <th>ITEM CODE</th>
                                    <th>ITEM NAME</th>
                                    <th>CATEGORY</th>
                                    <th>CURRENT STOCK</th>
                                    <th>REORDER LEVEL</th>
                                    <th>UNIT COST</th>
                                    <th>RETAIL PRICE</th>
                                    <th>NEAREST EXPIRY</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($rows as $row): ?>
                                    <tr>
                                        <td><?= e($row['item_code']) ?></td>
                                        <td><?= e($row['item_name']) ?></td>
                                        <td><?= e($row['category_name']) ?></td>
                                        <td><?= number_format((float)$row['total_stock'], 2) ?></td>
                                        <td><?= number_format((float)$row['reorder_level'], 2) ?></td>
                                        <td><?= money($row['unit_cost']) ?></td>
                                        <td><?= money($row['retail_price']) ?></td>
                                        <td><?= !empty($row['nearest_expiry']) ? e(date('m/d/Y', strtotime($row['nearest_expiry']))) : '—' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>

                        <?php elseif ($report === 'low_stock'): ?>

                            <table class="report-table">
                                <thead>
                                <tr>
                                    <th>ITEM CODE</th>
                                    <th>ITEM NAME</th>
                                    <th>CATEGORY</th>
                                    <th>CURRENT STOCK</th>
                                    <th>REORDER LEVEL</th>
                                    <th>UNIT COST</th>
                                    <th>RETAIL PRICE</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($rows as $row): ?>
                                    <tr>
                                        <td><?= e($row['item_code']) ?></td>
                                        <td><?= e($row['item_name']) ?></td>
                                        <td><?= e($row['category_name']) ?></td>
                                        <td class="low-stock-value"><?= number_format((float)$row['total_stock'], 2) ?></td>
                                        <td><?= number_format((float)$row['reorder_level'], 2) ?></td>
                                        <td><?= money($row['unit_cost']) ?></td>
                                        <td><?= money($row['retail_price']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>

                        <?php elseif ($report === 'expiration'): ?>

                            <table class="report-table">
                                <thead>
                                <tr>
                                    <th>ITEM CODE</th>
                                    <th>ITEM NAME</th>
                                    <th>CATEGORY</th>
                                    <th>CURRENT STOCK</th>
                                    <th>NEAREST EXPIRY</th>
                                    <th>RETAIL PRICE</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($rows as $row): ?>
                                    <tr>
                                        <td><?= e($row['item_code']) ?></td>
                                        <td><?= e($row['item_name']) ?></td>
                                        <td><?= e($row['category_name']) ?></td>
                                        <td><?= number_format((float)$row['total_stock'], 2) ?></td>
                                        <td><?= !empty($row['nearest_expiry']) ? e(date('m/d/Y', strtotime($row['nearest_expiry']))) : '—' ?></td>
                                        <td><?= money($row['retail_price']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>

                        <?php endif; ?>

                    </div>
                </section>

            <?php else: ?>

                <section class="report-empty-state">
                    <div class="empty-icon">
                        <i class="fa-solid fa-file-circle-check"></i>
                    </div>
                    <h2>Select a Report</h2>
                    <p>Choose one of the three report categories and generate the report you need.</p>
                </section>

            <?php endif; ?>

            <div class="footer">
                Veterinary MIS • Reports
            </div>

        </div>
    </main>
</div>

<script>
window.reportCategory = <?= json_encode($category) ?>;
window.reportValue = <?= json_encode($report) ?>;
</script>
<script src="../assets/js/reports.js"></script>
</body>
</html>
