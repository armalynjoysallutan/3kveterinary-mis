<?php

session_start();

header("Content-Type: application/json");

if (
    !(
        isset($_SESSION["admin_id"]) ||
        isset($_SESSION["admin_username"]) ||
        (
            isset($_SESSION["account_id"]) &&
            isset($_SESSION["role"]) &&
            $_SESSION["role"] === "Staff"
        )
    )
) {
    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "Unauthorized access."
    ]);

    exit();
}

require_once "../config/database.php";


/* =========================================================
   DEFAULT VALUES
   ========================================================= */

$outOfStock = 0;
$expiredItems = 0;
$totalStock = 0;
$revenueMtd = 0;
$registeredClients = 0;
$totalPatients = 0;
$newBookings = 0;


/* =========================================================
   1. INVENTORY
   ---------------------------------------------------------
   Uses the same stock computation as the Inventory module.
   Only Active inventory items are included.
   ========================================================= */

$inventorySql = "
    SELECT
        COALESCE(
            SUM(
                CASE
                    WHEN COALESCE(s.total_stock, 0) <= 0
                    THEN 1
                    ELSE 0
                END
            ),
            0
        ) AS out_of_stock,

        COALESCE(
            SUM(COALESCE(s.total_stock, 0)),
            0
        ) AS total_stock

    FROM inventory_items i

    LEFT JOIN (
        SELECT
            item_id,
            SUM(quantity) AS total_stock
        FROM inventory_stock
        GROUP BY item_id
    ) s
        ON s.item_id = i.item_id

    WHERE i.status = 'Active'
";


$inventoryResult = mysqli_query($conn, $inventorySql);


if ($inventoryResult) {

    $inventoryRow =
        mysqli_fetch_assoc($inventoryResult);

    $outOfStock =
        (int) ($inventoryRow["out_of_stock"] ?? 0);

    $totalStock =
        (int) ($inventoryRow["total_stock"] ?? 0);
}


/* =========================================================
   2. EXPIRED ITEMS
   ---------------------------------------------------------
   Counts Active inventory items that have at least one
   expired batch with remaining stock.
   ========================================================= */

$expiredSql = "
    SELECT COUNT(DISTINCT i.item_id) AS expired_items

    FROM inventory_items i

    INNER JOIN inventory_stock s
        ON s.item_id = i.item_id

    WHERE i.status = 'Active'

      AND s.quantity > 0

      AND s.expiration_date IS NOT NULL

      AND s.expiration_date < CURDATE()
";


$expiredResult = mysqli_query(
    $conn,
    $expiredSql
);


if ($expiredResult) {

    $expiredRow =
        mysqli_fetch_assoc($expiredResult);

    $expiredItems =
        (int) ($expiredRow["expired_items"] ?? 0);
}


/* =========================================================
   3. REVENUE — MONTH TO DATE
   ---------------------------------------------------------
   Paid billing records created during the current month.
   ========================================================= */

$revenueSql = "
    SELECT
        COALESCE(
            SUM(total_amount),
            0
        ) AS revenue_mtd

    FROM billing

    WHERE payment_status = 'Paid'

      AND created_at >=
          DATE_FORMAT(
              CURDATE(),
              '%Y-%m-01'
          )

      AND created_at <
          DATE_ADD(
              DATE_FORMAT(
                  CURDATE(),
                  '%Y-%m-01'
              ),
              INTERVAL 1 MONTH
          )
";


$revenueResult = mysqli_query(
    $conn,
    $revenueSql
);


if ($revenueResult) {

    $revenueRow =
        mysqli_fetch_assoc($revenueResult);

    $revenueMtd =
        (float) ($revenueRow["revenue_mtd"] ?? 0);
}


/* =========================================================
   4. REGISTERED CLIENTS
   ---------------------------------------------------------
   Follows the same eligibility rule as Customer Records:

   Active customer
   AND
   Completed appointment
   OR
   Paid billing
   ========================================================= */

$clientsSql = "
    SELECT
        COUNT(*) AS registered_clients

    FROM customers c

    WHERE c.record_status = 'Active'

      AND (
            EXISTS (
                SELECT 1

                FROM appointments a

                WHERE a.customer_id = c.customer_id

                  AND a.status = 'Completed'

                  AND a.is_archived = 0
            )

            OR

            EXISTS (
                SELECT 1

                FROM billing b

                WHERE b.customer_id = c.customer_id

                  AND b.payment_status = 'Paid'
            )
      )
";


$clientsResult = mysqli_query(
    $conn,
    $clientsSql
);


if ($clientsResult) {

    $clientsRow =
        mysqli_fetch_assoc($clientsResult);

    $registeredClients =
        (int) ($clientsRow["registered_clients"] ?? 0);
}


/* =========================================================
   5. TOTAL PATIENTS
   ---------------------------------------------------------
   Counts pets belonging to Active customers.
   Archived customer records are excluded from the active
   Dashboard patient count.
   ========================================================= */

$patientsSql = "
    SELECT
        COUNT(DISTINCT p.pet_id) AS total_patients

    FROM pets p

    INNER JOIN customers c
        ON c.customer_id = p.customer_id

    WHERE c.record_status = 'Active'
";


$patientsResult = mysqli_query(
    $conn,
    $patientsSql
);


if ($patientsResult) {

    $patientsRow =
        mysqli_fetch_assoc($patientsResult);

    $totalPatients =
        (int) ($patientsRow["total_patients"] ?? 0);
}


/* =========================================================
   6. NEW BOOKINGS
   ---------------------------------------------------------
   Appointments created during the current week.

   Uses appointments.created_at, which is automatically
   populated by the database.
   ========================================================= */

$newBookingsSql = "
    SELECT
        COUNT(*) AS new_bookings

    FROM appointments

    WHERE created_at >=
          DATE_SUB(
              CURDATE(),
              INTERVAL WEEKDAY(CURDATE()) DAY
          )

      AND created_at <
          DATE_ADD(
              DATE_SUB(
                  CURDATE(),
                  INTERVAL WEEKDAY(CURDATE()) DAY
              ),
              INTERVAL 7 DAY
          )
        AND status = 'Confirmed'

        AND is_archived = 0      
";


$newBookingsResult = mysqli_query(
    $conn,
    $newBookingsSql
);


if ($newBookingsResult) {

    $newBookingsRow =
        mysqli_fetch_assoc($newBookingsResult);

    $newBookings =
        (int) ($newBookingsRow["new_bookings"] ?? 0);
}


/* =========================================================
   RESPONSE
   ========================================================= */

echo json_encode([

    "success" => true,

    "stats" => [

        "out_of_stock" =>
            $outOfStock,

        "expired_items" =>
            $expiredItems,

        "revenue_mtd" =>
            round($revenueMtd, 2),

        "total_stock" =>
            $totalStock,

        "registered_clients" =>
            $registeredClients,

        "total_patients" =>
            $totalPatients,

        "new_bookings" =>
            $newBookings

    ]

]);

?>