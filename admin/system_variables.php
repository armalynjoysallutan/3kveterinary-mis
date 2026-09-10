<?php

session_start();

if (!isset($_SESSION["admin_username"])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once __DIR__ . '/../config/database.php';

/*
|--------------------------------------------------------------------------
| PET SPECIES
|--------------------------------------------------------------------------
| Keep the clinic's default species available here.
| These are used as reference values in pet registration.
*/



/*
|--------------------------------------------------------------------------
| INVENTORY SYSTEM VARIABLES ACTIONS
|--------------------------------------------------------------------------
| Reference data used by the future Inventory module.
| Billing -> medications is intentionally kept separate.
*/
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["inventory_variable_action"])) {

    header("Content-Type: application/json; charset=UTF-8");

    $action = trim($_POST["inventory_variable_action"]);

    function inventory_variable_response($success, $message, $extra = []) {
        echo json_encode(array_merge([
            "success" => $success,
            "message" => $message
        ], $extra));
        exit();
    }

        /* ==========================================================
       WEBSITE PRODUCTS
       Pet Food + Supplements only
       Pet Food requires Dog/Cat classification.
    ========================================================== */

    if ($action === "get_website_product_items") {

        $items = [];

        $query = "
            SELECT
                i.item_id,
                i.item_code,
                i.item_name,
                i.category_id,
                c.category_name,
                i.retail_price,
                i.status
            FROM inventory_items i
            INNER JOIN inventory_categories c
                ON c.category_id = i.category_id
            WHERE i.status = 'Active'
              AND LOWER(TRIM(c.category_name)) IN ('pet food', 'supplements', 'others')
            ORDER BY c.category_name ASC, i.item_name ASC
        ";

        $result = mysqli_query($conn, $query);

        if (!$result) {
            inventory_variable_response(false, mysqli_error($conn));
        }

        while ($row = mysqli_fetch_assoc($result)) {
            $items[] = $row;
        }

        inventory_variable_response(
            true,
            "Website product inventory items loaded successfully.",
            ["items" => $items]
        );
    }


    if ($action === "add_website_product") {

        $itemId = (int)($_POST["item_id"] ?? 0);
        $petType = trim($_POST["pet_type"] ?? "");
        $description = trim($_POST["description"] ?? "");
        $status = trim($_POST["status"] ?? "Visible");

        if ($itemId <= 0) {
            inventory_variable_response(false, "Please select an inventory item.");
        }

        if (!in_array($status, ["Visible", "Hidden"], true)) {
            $status = "Visible";
        }

        /*
         * Verify that the selected item is an Active
         * Pet Food or Supplements inventory item.
         */
        $itemCheck = mysqli_prepare($conn, "
            SELECT
                i.item_id,
                c.category_name
            FROM inventory_items i
            INNER JOIN inventory_categories c
                ON c.category_id = i.category_id
            WHERE i.item_id = ?
              AND i.status = 'Active'
              AND LOWER(TRIM(c.category_name)) IN ('pet food', 'supplements', 'others')
            LIMIT 1
        ");

        if (!$itemCheck) {
            inventory_variable_response(false, mysqli_error($conn));
        }

        mysqli_stmt_bind_param($itemCheck, "i", $itemId);
        mysqli_stmt_execute($itemCheck);

        $itemResult = mysqli_stmt_get_result($itemCheck);

        if (!$itemResult || mysqli_num_rows($itemResult) === 0) {
            mysqli_stmt_close($itemCheck);

            inventory_variable_response(
                false,
                "The selected item is not an active Pet Food, Supplements, or Others inventory item."
            );
        }

        $itemData = mysqli_fetch_assoc($itemResult);
        mysqli_stmt_close($itemCheck);

        $categoryName = strtolower(trim($itemData["category_name"]));

        /*
 * Handle website product image upload.
 */

$imagePath = null;

if (
    isset($_FILES["image"]) &&
    $_FILES["image"]["error"] !== UPLOAD_ERR_NO_FILE
) {

    if ($_FILES["image"]["error"] !== UPLOAD_ERR_OK) {

        inventory_variable_response(
            false,
            "There was a problem uploading the product image."
        );

    }

    // Maximum file size: 5 MB
    $maxFileSize = 5 * 1024 * 1024;

    if ($_FILES["image"]["size"] > $maxFileSize) {

        inventory_variable_response(
            false,
            "Product image must not exceed 5 MB."
        );

    }

    $tmpFile = $_FILES["image"]["tmp_name"];

    // Verify that the uploaded file is actually an image
    $imageInfo = getimagesize($tmpFile);

    if ($imageInfo === false) {

        inventory_variable_response(
            false,
            "Please upload a valid image file."
        );

    }

    $allowedMimeTypes = [
        "image/jpeg" => "jpg",
        "image/png"  => "png",
        "image/webp" => "webp"
    ];

    $mimeType = $imageInfo["mime"] ?? "";

    if (!isset($allowedMimeTypes[$mimeType])) {

        inventory_variable_response(
            false,
            "Only JPG, PNG, and WebP images are allowed."
        );

    }

    $extension = $allowedMimeTypes[$mimeType];

    /*
     * Physical upload folder.
     * system_variables.php is inside /admin,
     * so ../assets points to /assets.
     */
    $uploadDirectory =
        __DIR__ .
        "/../assets/uploads/website_products/";

    if (!is_dir($uploadDirectory)) {

        if (!mkdir($uploadDirectory, 0755, true)) {

            inventory_variable_response(
                false,
                "Unable to create the product image upload folder."
            );

        }

    }

    /*
     * Generate a unique filename.
     */
    $uniqueFileName =
        "website_product_" .
        $itemId .
        "_" .
        bin2hex(random_bytes(8)) .
        "." .
        $extension;

    $destination =
        $uploadDirectory .
        $uniqueFileName;

    if (!move_uploaded_file(
        $tmpFile,
        $destination
    )) {

        inventory_variable_response(
            false,
            "Unable to save the product image."
        );

    }

    /*
     * Path stored in the database.
     */
    $imagePath =
        "assets/uploads/website_products/" .
        $uniqueFileName;
}

        /*
         * Pet Food must be classified as Dog or Cat.
         * Supplements do not require pet_type.
         */
        if ($categoryName === "pet food") {

            if (!in_array($petType, ["Dog", "Cat"], true)) {
                inventory_variable_response(
                    false,
                    "Please select whether this Pet Food product is for Dog or Cat."
                );
            }

        } else {
            $petType = null;
        }


        /*
         * Prevent the same inventory item from being
         * added as a website product more than once.
         */
        $duplicateCheck = mysqli_prepare($conn, "
            SELECT website_product_id
            FROM website_products
            WHERE item_id = ?
            LIMIT 1
        ");

        if (!$duplicateCheck) {
            inventory_variable_response(false, mysqli_error($conn));
        }

        mysqli_stmt_bind_param($duplicateCheck, "i", $itemId);
        mysqli_stmt_execute($duplicateCheck);

        $duplicateResult = mysqli_stmt_get_result($duplicateCheck);

        if ($duplicateResult && mysqli_num_rows($duplicateResult) > 0) {
            mysqli_stmt_close($duplicateCheck);

            inventory_variable_response(
                false,
                "This inventory item is already added as a website product."
            );
        }

        mysqli_stmt_close($duplicateCheck);


        /*
         * Insert website product.
         */
        $stmt = mysqli_prepare($conn, "
            INSERT INTO website_products
                (item_id, pet_type, description,image_path, status)
            VALUES
                (?, ?, ?, ?, ?)
        ");

        if (!$stmt) {
            inventory_variable_response(false, mysqli_error($conn));
        }

        mysqli_stmt_bind_param(
            $stmt,
            "issss",
            $itemId,
            $petType,
            $description,
            $imagePath,
            $status
        );

        if (!mysqli_stmt_execute($stmt)) {

            $message = mysqli_error($conn);
            mysqli_stmt_close($stmt);

            inventory_variable_response(false, $message);
        }

        $newId = mysqli_insert_id($conn);

        mysqli_stmt_close($stmt);

        inventory_variable_response(
            true,
            "Website product added successfully.",
            ["id" => $newId]
        );
    }

        /* ==========================================================
       WEBSITE PRODUCTS
       EDIT WEBSITE PRODUCT
    ========================================================== */

    if ($action === "get_website_product") {

        $websiteProductId =
            (int)($_POST["website_product_id"] ?? 0);

        if ($websiteProductId <= 0) {
            inventory_variable_response(
                false,
                "Invalid website product ID."
            );
        }

        $stmt = mysqli_prepare($conn, "
            SELECT
                wp.website_product_id,
                wp.item_id,
                wp.pet_type,
                wp.description,
                wp.image_path,
                wp.status,
                i.item_name,
                c.category_name
            FROM website_products wp
            INNER JOIN inventory_items i
                ON i.item_id = wp.item_id
            INNER JOIN inventory_categories c
                ON c.category_id = i.category_id
            WHERE wp.website_product_id = ?
            LIMIT 1
        ");

        if (!$stmt) {
            inventory_variable_response(
                false,
                mysqli_error($conn)
            );
        }

        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $websiteProductId
        );

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        if (!$result || mysqli_num_rows($result) === 0) {

            mysqli_stmt_close($stmt);

            inventory_variable_response(
                false,
                "Website product not found."
            );
        }

        $product = mysqli_fetch_assoc($result);

        mysqli_stmt_close($stmt);

        inventory_variable_response(
            true,
            "Website product loaded successfully.",
            [
                "product" => $product
            ]
        );
    }


    /* ==========================================================
       WEBSITE PRODUCTS
       UPDATE WEBSITE PRODUCT
    ========================================================== */

    if ($action === "edit_website_product") {

        $websiteProductId =
            (int)($_POST["website_product_id"] ?? 0);

        $petType =
            trim($_POST["pet_type"] ?? "");

        $description =
            trim($_POST["description"] ?? "");

        $status =
            trim($_POST["status"] ?? "Visible");


        if ($websiteProductId <= 0) {

            inventory_variable_response(
                false,
                "Invalid website product ID."
            );
        }


        if (!in_array(
            $status,
            ["Visible", "Hidden"],
            true
        )) {

            $status = "Visible";
        }


        /*
         * Get the existing website product
         * together with its inventory category.
         */

        $productCheck = mysqli_prepare($conn, "
            SELECT
                wp.website_product_id,
                wp.item_id,
                wp.pet_type,
                wp.description,
                wp.image_path,
                wp.status,
                i.item_name,
                c.category_name
            FROM website_products wp
            INNER JOIN inventory_items i
                ON i.item_id = wp.item_id
            INNER JOIN inventory_categories c
                ON c.category_id = i.category_id
            WHERE wp.website_product_id = ?
            LIMIT 1
        ");


        if (!$productCheck) {

            inventory_variable_response(
                false,
                mysqli_error($conn)
            );
        }


        mysqli_stmt_bind_param(
            $productCheck,
            "i",
            $websiteProductId
        );

        mysqli_stmt_execute($productCheck);

        $productResult =
            mysqli_stmt_get_result($productCheck);


        if (
            !$productResult ||
            mysqli_num_rows($productResult) === 0
        ) {

            mysqli_stmt_close($productCheck);

            inventory_variable_response(
                false,
                "Website product not found."
            );
        }


        $existingProduct =
            mysqli_fetch_assoc($productResult);

        mysqli_stmt_close($productCheck);


        $itemId =
            (int)$existingProduct["item_id"];

        $categoryName =
            strtolower(
                trim($existingProduct["category_name"])
            );

        $oldImagePath =
            $existingProduct["image_path"];


        /*
         * Only Pet Food requires Dog/Cat.
         * Supplements and Others use NULL.
         */

        if ($categoryName === "pet food") {

            if (!in_array(
                $petType,
                ["Dog", "Cat"],
                true
            )) {

                inventory_variable_response(
                    false,
                    "Please select whether this Pet Food product is for Dog or Cat."
                );
            }

        } else {

            $petType = null;
        }


        /*
         * Keep the existing image unless
         * a new image was uploaded.
         */

        $newImagePath = $oldImagePath;

        $newUploadedFile = null;


        if (
            isset($_FILES["image"]) &&
            $_FILES["image"]["error"] !== UPLOAD_ERR_NO_FILE
        ) {

            if (
                $_FILES["image"]["error"] !==
                UPLOAD_ERR_OK
            ) {

                inventory_variable_response(
                    false,
                    "There was a problem uploading the product image."
                );
            }


            // Maximum file size: 5 MB

            $maxFileSize =
                5 * 1024 * 1024;


            if (
                $_FILES["image"]["size"] >
                $maxFileSize
            ) {

                inventory_variable_response(
                    false,
                    "Product image must not exceed 5 MB."
                );
            }


            $tmpFile =
                $_FILES["image"]["tmp_name"];


            /*
             * Verify that the uploaded file
             * is actually an image.
             */

            $imageInfo =
                getimagesize($tmpFile);


            if ($imageInfo === false) {

                inventory_variable_response(
                    false,
                    "Please upload a valid image file."
                );
            }


            $allowedMimeTypes = [
                "image/jpeg" => "jpg",
                "image/png"  => "png",
                "image/webp" => "webp"
            ];


            $mimeType =
                $imageInfo["mime"] ?? "";


            if (
                !isset(
                    $allowedMimeTypes[$mimeType]
                )
            ) {

                inventory_variable_response(
                    false,
                    "Only JPG, PNG, and WebP images are allowed."
                );
            }


            $extension =
                $allowedMimeTypes[$mimeType];


            /*
             * Physical upload folder.
             */

            $uploadDirectory =
                __DIR__ .
                "/../assets/uploads/website_products/";


            if (!is_dir($uploadDirectory)) {

                if (
                    !mkdir(
                        $uploadDirectory,
                        0755,
                        true
                    )
                ) {

                    inventory_variable_response(
                        false,
                        "Unable to create the product image upload folder."
                    );
                }
            }


            /*
             * Generate a unique filename.
             */

            $uniqueFileName =
                "website_product_" .
                $itemId .
                "_" .
                bin2hex(
                    random_bytes(8)
                ) .
                "." .
                $extension;


            $destination =
                $uploadDirectory .
                $uniqueFileName;


            if (
                !move_uploaded_file(
                    $tmpFile,
                    $destination
                )
            ) {

                inventory_variable_response(
                    false,
                    "Unable to save the product image."
                );
            }


            $newImagePath =
                "assets/uploads/website_products/" .
                $uniqueFileName;


            /*
             * Remember the newly uploaded physical
             * file so it can be removed if the
             * database update fails.
             */

            $newUploadedFile =
                $destination;
        }


        /*
         * Update the website product.
         */

        $stmt = mysqli_prepare($conn, "
            UPDATE website_products
            SET
                pet_type = ?,
                description = ?,
                image_path = ?,
                status = ?
            WHERE website_product_id = ?
        ");


        if (!$stmt) {

            if (
                $newUploadedFile &&
                file_exists($newUploadedFile)
            ) {
                unlink($newUploadedFile);
            }

            inventory_variable_response(
                false,
                mysqli_error($conn)
            );
        }


        mysqli_stmt_bind_param(
            $stmt,
            "ssssi",
            $petType,
            $description,
            $newImagePath,
            $status,
            $websiteProductId
        );


        if (!mysqli_stmt_execute($stmt)) {

            $message =
                mysqli_error($conn);

            mysqli_stmt_close($stmt);


            /*
             * Remove newly uploaded image
             * if database update failed.
             */

            if (
                $newUploadedFile &&
                file_exists($newUploadedFile)
            ) {

                unlink($newUploadedFile);
            }


            inventory_variable_response(
                false,
                $message
            );
        }


        mysqli_stmt_close($stmt);


        /*
         * If a new image replaced the old one,
         * remove the old physical file.
         */

        if (
            $newUploadedFile &&
            $oldImagePath
        ) {

            $oldImageFile =
                __DIR__ .
                "/../" .
                $oldImagePath;


            if (
                file_exists($oldImageFile)
            ) {

                unlink($oldImageFile);
            }
        }


        inventory_variable_response(
            true,
            "Website product updated successfully."
        );
    }


    /* ==========================================================
       WEBSITE PRODUCTS
       DELETE WEBSITE PRODUCT
    ========================================================== */

    if ($action === "delete_website_product") {

        $websiteProductId =
            (int)($_POST["website_product_id"] ?? 0);


        if ($websiteProductId <= 0) {

            inventory_variable_response(
                false,
                "Invalid website product ID."
            );
        }


        /*
         * Get the image path first so we can
         * remove the physical file after
         * deleting the database record.
         */

        $productCheck = mysqli_prepare($conn, "
            SELECT
                image_path
            FROM website_products
            WHERE website_product_id = ?
            LIMIT 1
        ");


        if (!$productCheck) {

            inventory_variable_response(
                false,
                mysqli_error($conn)
            );
        }


        mysqli_stmt_bind_param(
            $productCheck,
            "i",
            $websiteProductId
        );

        mysqli_stmt_execute(
            $productCheck
        );


        $productResult =
            mysqli_stmt_get_result(
                $productCheck
            );


        if (
            !$productResult ||
            mysqli_num_rows($productResult) === 0
        ) {

            mysqli_stmt_close(
                $productCheck
            );

            inventory_variable_response(
                false,
                "Website product not found."
            );
        }


        $product =
            mysqli_fetch_assoc(
                $productResult
            );

        mysqli_stmt_close(
            $productCheck
        );


        $imagePath =
            $product["image_path"];


        /*
         * Delete only the website_products record.
         * The inventory_items record is NOT deleted.
         */

        $stmt = mysqli_prepare($conn, "
            DELETE FROM website_products
            WHERE website_product_id = ?
        ");


        if (!$stmt) {

            inventory_variable_response(
                false,
                mysqli_error($conn)
            );
        }


        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $websiteProductId
        );


        if (!mysqli_stmt_execute($stmt)) {

            $message =
                mysqli_error($conn);

            mysqli_stmt_close($stmt);


            inventory_variable_response(
                false,
                $message
            );
        }


        mysqli_stmt_close($stmt);


        /*
         * Delete the uploaded image file,
         * if one exists.
         */

        if ($imagePath) {

            $imageFile =
                __DIR__ .
                "/../" .
                $imagePath;


            if (
                file_exists($imageFile)
            ) {

                unlink($imageFile);
            }
        }


        inventory_variable_response(
            true,
            "Website product deleted successfully."
        );
    }

    if ($action === "add_category") {
        $name = trim($_POST["category_name"] ?? "");
        $description = trim($_POST["description"] ?? "");
        $status = trim($_POST["status"] ?? "Active");

        if ($name === "") inventory_variable_response(false, "Category name is required.");
        if (!in_array($status, ["Active", "Inactive"], true)) $status = "Active";

        $check = mysqli_prepare($conn,
            "SELECT category_id FROM inventory_categories
             WHERE LOWER(TRIM(category_name)) = LOWER(TRIM(?)) LIMIT 1");
        if (!$check) inventory_variable_response(false, mysqli_error($conn));
        mysqli_stmt_bind_param($check, "s", $name);
        mysqli_stmt_execute($check);
        $result = mysqli_stmt_get_result($check);
        if ($result && mysqli_num_rows($result) > 0) {
            mysqli_stmt_close($check);
            inventory_variable_response(false, "This inventory category already exists.");
        }
        mysqli_stmt_close($check);

        $stmt = mysqli_prepare($conn,
            "INSERT INTO inventory_categories (category_name, description, status)
             VALUES (?, ?, ?)");
        if (!$stmt) inventory_variable_response(false, mysqli_error($conn));
        mysqli_stmt_bind_param($stmt, "sss", $name, $description, $status);
        if (!mysqli_stmt_execute($stmt)) {
            $message = mysqli_error($conn);
            mysqli_stmt_close($stmt);
            inventory_variable_response(false, $message);
        }
        $newId = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);
        inventory_variable_response(true, "Inventory category added successfully.", ["id" => $newId]);
    }

    if ($action === "edit_category") {
        $id = (int)($_POST["category_id"] ?? 0);
        $name = trim($_POST["category_name"] ?? "");
        $description = trim($_POST["description"] ?? "");
        $status = trim($_POST["status"] ?? "Active");

        if ($id <= 0) inventory_variable_response(false, "Invalid category ID.");
        if ($name === "") inventory_variable_response(false, "Category name is required.");
        if (!in_array($status, ["Active", "Inactive"], true)) $status = "Active";

        $check = mysqli_prepare($conn,
            "SELECT category_id FROM inventory_categories
             WHERE LOWER(TRIM(category_name)) = LOWER(TRIM(?))
               AND category_id <> ? LIMIT 1");
        if (!$check) inventory_variable_response(false, mysqli_error($conn));
        mysqli_stmt_bind_param($check, "si", $name, $id);
        mysqli_stmt_execute($check);
        $result = mysqli_stmt_get_result($check);
        if ($result && mysqli_num_rows($result) > 0) {
            mysqli_stmt_close($check);
            inventory_variable_response(false, "This inventory category already exists.");
        }
        mysqli_stmt_close($check);

        $stmt = mysqli_prepare($conn,
            "UPDATE inventory_categories
             SET category_name = ?, description = ?, status = ?
             WHERE category_id = ?");
        if (!$stmt) inventory_variable_response(false, mysqli_error($conn));
        mysqli_stmt_bind_param($stmt, "sssi", $name, $description, $status, $id);
        if (!mysqli_stmt_execute($stmt)) {
            $message = mysqli_error($conn);
            mysqli_stmt_close($stmt);
            inventory_variable_response(false, $message);
        }
        mysqli_stmt_close($stmt);
        inventory_variable_response(true, "Inventory category updated successfully.");
    }

    if ($action === "delete_category") {
        $id = (int)($_POST["category_id"] ?? 0);
        if ($id <= 0) inventory_variable_response(false, "Invalid category ID.");

        $stmt = mysqli_prepare($conn,
            "DELETE FROM inventory_categories WHERE category_id = ?");
        if (!$stmt) inventory_variable_response(false, mysqli_error($conn));
        mysqli_stmt_bind_param($stmt, "i", $id);

        if (!mysqli_stmt_execute($stmt)) {
            $errorCode = mysqli_errno($conn);
            $message = mysqli_error($conn);
            mysqli_stmt_close($stmt);

            if ($errorCode === 1451) {
                inventory_variable_response(false,
                    "This category cannot be deleted because it is already being used.");
            }

            inventory_variable_response(false, $message);
        }

        mysqli_stmt_close($stmt);
        inventory_variable_response(true, "Inventory category deleted successfully.");
    }

    /* ==========================================================
       INVENTORY SUBCATEGORIES
       Subcategories are linked to Inventory Categories.
    ========================================================== */

    if ($action === "add_subcategory") {
        $categoryId = (int)($_POST["category_id"] ?? 0);
        $name = trim($_POST["subcategory_name"] ?? "");
        $description = trim($_POST["description"] ?? "");
        $status = trim($_POST["status"] ?? "Active");

        if ($categoryId <= 0) inventory_variable_response(false, "Please select an inventory category.");
        if ($name === "") inventory_variable_response(false, "Subcategory name is required.");
        if (!in_array($status, ["Active", "Inactive"], true)) $status = "Active";

        $categoryCheck = mysqli_prepare($conn,
            "SELECT category_id FROM inventory_categories WHERE category_id = ? LIMIT 1");
        if (!$categoryCheck) inventory_variable_response(false, mysqli_error($conn));
        mysqli_stmt_bind_param($categoryCheck, "i", $categoryId);
        mysqli_stmt_execute($categoryCheck);
        $categoryResult = mysqli_stmt_get_result($categoryCheck);
        if (!$categoryResult || mysqli_num_rows($categoryResult) === 0) {
            mysqli_stmt_close($categoryCheck);
            inventory_variable_response(false, "Selected inventory category does not exist.");
        }
        mysqli_stmt_close($categoryCheck);

        $check = mysqli_prepare($conn,
            "SELECT subcategory_id FROM inventory_subcategories
             WHERE category_id = ?
               AND LOWER(TRIM(subcategory_name)) = LOWER(TRIM(?))
             LIMIT 1");
        if (!$check) inventory_variable_response(false, mysqli_error($conn));
        mysqli_stmt_bind_param($check, "is", $categoryId, $name);
        mysqli_stmt_execute($check);
        $result = mysqli_stmt_get_result($check);
        if ($result && mysqli_num_rows($result) > 0) {
            mysqli_stmt_close($check);
            inventory_variable_response(false, "This subcategory already exists under the selected category.");
        }
        mysqli_stmt_close($check);

        $stmt = mysqli_prepare($conn,
            "INSERT INTO inventory_subcategories
             (category_id, subcategory_name, description, status)
             VALUES (?, ?, ?, ?)");
        if (!$stmt) inventory_variable_response(false, mysqli_error($conn));
        mysqli_stmt_bind_param($stmt, "isss", $categoryId, $name, $description, $status);
        if (!mysqli_stmt_execute($stmt)) {
            $message = mysqli_error($conn);
            mysqli_stmt_close($stmt);
            inventory_variable_response(false, $message);
        }
        $newId = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);
        inventory_variable_response(true, "Inventory subcategory added successfully.", ["id" => $newId]);
    }

    if ($action === "edit_subcategory") {
        $id = (int)($_POST["subcategory_id"] ?? 0);
        $categoryId = (int)($_POST["category_id"] ?? 0);
        $name = trim($_POST["subcategory_name"] ?? "");
        $description = trim($_POST["description"] ?? "");
        $status = trim($_POST["status"] ?? "Active");

        if ($id <= 0) inventory_variable_response(false, "Invalid subcategory ID.");
        if ($categoryId <= 0) inventory_variable_response(false, "Please select an inventory category.");
        if ($name === "") inventory_variable_response(false, "Subcategory name is required.");
        if (!in_array($status, ["Active", "Inactive"], true)) $status = "Active";

        $check = mysqli_prepare($conn,
            "SELECT subcategory_id FROM inventory_subcategories
             WHERE category_id = ?
               AND LOWER(TRIM(subcategory_name)) = LOWER(TRIM(?))
               AND subcategory_id <> ?
             LIMIT 1");
        if (!$check) inventory_variable_response(false, mysqli_error($conn));
        mysqli_stmt_bind_param($check, "isi", $categoryId, $name, $id);
        mysqli_stmt_execute($check);
        $result = mysqli_stmt_get_result($check);
        if ($result && mysqli_num_rows($result) > 0) {
            mysqli_stmt_close($check);
            inventory_variable_response(false, "This subcategory already exists under the selected category.");
        }
        mysqli_stmt_close($check);

        $stmt = mysqli_prepare($conn,
            "UPDATE inventory_subcategories
             SET category_id = ?, subcategory_name = ?, description = ?, status = ?
             WHERE subcategory_id = ?");
        if (!$stmt) inventory_variable_response(false, mysqli_error($conn));
        mysqli_stmt_bind_param($stmt, "isssi", $categoryId, $name, $description, $status, $id);
        if (!mysqli_stmt_execute($stmt)) {
            $message = mysqli_error($conn);
            mysqli_stmt_close($stmt);
            inventory_variable_response(false, $message);
        }
        mysqli_stmt_close($stmt);
        inventory_variable_response(true, "Inventory subcategory updated successfully.");
    }

    if ($action === "delete_subcategory") {
        $id = (int)($_POST["subcategory_id"] ?? 0);
        if ($id <= 0) inventory_variable_response(false, "Invalid subcategory ID.");

        $stmt = mysqli_prepare($conn,
            "DELETE FROM inventory_subcategories WHERE subcategory_id = ?");
        if (!$stmt) inventory_variable_response(false, mysqli_error($conn));
        mysqli_stmt_bind_param($stmt, "i", $id);

        if (!mysqli_stmt_execute($stmt)) {
            $errorCode = mysqli_errno($conn);
            $message = mysqli_error($conn);
            mysqli_stmt_close($stmt);
            if ($errorCode === 1451) {
                inventory_variable_response(false,
                    "This subcategory cannot be deleted because it is already being used.");
            }
            inventory_variable_response(false, $message);
        }

        mysqli_stmt_close($stmt);
        inventory_variable_response(true, "Inventory subcategory deleted successfully.");
    }

    if ($action === "add_unit") {
        $name = trim($_POST["unit_name"] ?? "");
        $abbreviation = trim($_POST["abbreviation"] ?? "");
        $status = trim($_POST["status"] ?? "Active");

        if ($name === "") inventory_variable_response(false, "Unit name is required.");
        if ($abbreviation === "") inventory_variable_response(false, "Unit abbreviation is required.");
        if (!in_array($status, ["Active", "Inactive"], true)) $status = "Active";

        $check = mysqli_prepare($conn,
            "SELECT unit_id FROM inventory_units
             WHERE LOWER(TRIM(unit_name)) = LOWER(TRIM(?))
                OR LOWER(TRIM(abbreviation)) = LOWER(TRIM(?))
             LIMIT 1");
        if (!$check) inventory_variable_response(false, mysqli_error($conn));
        mysqli_stmt_bind_param($check, "ss", $name, $abbreviation);
        mysqli_stmt_execute($check);
        $result = mysqli_stmt_get_result($check);
        if ($result && mysqli_num_rows($result) > 0) {
            mysqli_stmt_close($check);
            inventory_variable_response(false, "This unit name or abbreviation already exists.");
        }
        mysqli_stmt_close($check);

        $stmt = mysqli_prepare($conn,
            "INSERT INTO inventory_units (unit_name, abbreviation, status)
             VALUES (?, ?, ?)");
        if (!$stmt) inventory_variable_response(false, mysqli_error($conn));
        mysqli_stmt_bind_param($stmt, "sss", $name, $abbreviation, $status);
        if (!mysqli_stmt_execute($stmt)) {
            $message = mysqli_error($conn);
            mysqli_stmt_close($stmt);
            inventory_variable_response(false, $message);
        }
        $newId = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);
        inventory_variable_response(true, "Inventory unit added successfully.", ["id" => $newId]);
    }

    if ($action === "edit_unit") {
        $id = (int)($_POST["unit_id"] ?? 0);
        $name = trim($_POST["unit_name"] ?? "");
        $abbreviation = trim($_POST["abbreviation"] ?? "");
        $status = trim($_POST["status"] ?? "Active");

        if ($id <= 0) inventory_variable_response(false, "Invalid unit ID.");
        if ($name === "") inventory_variable_response(false, "Unit name is required.");
        if ($abbreviation === "") inventory_variable_response(false, "Unit abbreviation is required.");
        if (!in_array($status, ["Active", "Inactive"], true)) $status = "Active";

        $check = mysqli_prepare($conn,
            "SELECT unit_id FROM inventory_units
             WHERE (LOWER(TRIM(unit_name)) = LOWER(TRIM(?))
                OR LOWER(TRIM(abbreviation)) = LOWER(TRIM(?)))
               AND unit_id <> ? LIMIT 1");
        if (!$check) inventory_variable_response(false, mysqli_error($conn));
        mysqli_stmt_bind_param($check, "ssi", $name, $abbreviation, $id);
        mysqli_stmt_execute($check);
        $result = mysqli_stmt_get_result($check);
        if ($result && mysqli_num_rows($result) > 0) {
            mysqli_stmt_close($check);
            inventory_variable_response(false, "This unit name or abbreviation already exists.");
        }
        mysqli_stmt_close($check);

        $stmt = mysqli_prepare($conn,
            "UPDATE inventory_units
             SET unit_name = ?, abbreviation = ?, status = ?
             WHERE unit_id = ?");
        if (!$stmt) inventory_variable_response(false, mysqli_error($conn));
        mysqli_stmt_bind_param($stmt, "sssi", $name, $abbreviation, $status, $id);
        if (!mysqli_stmt_execute($stmt)) {
            $message = mysqli_error($conn);
            mysqli_stmt_close($stmt);
            inventory_variable_response(false, $message);
        }
        mysqli_stmt_close($stmt);
        inventory_variable_response(true, "Inventory unit updated successfully.");
    }

    if ($action === "delete_unit") {
        $id = (int)($_POST["unit_id"] ?? 0);
        if ($id <= 0) inventory_variable_response(false, "Invalid unit ID.");

        $stmt = mysqli_prepare($conn,
            "DELETE FROM inventory_units WHERE unit_id = ?");
        if (!$stmt) inventory_variable_response(false, mysqli_error($conn));
        mysqli_stmt_bind_param($stmt, "i", $id);

        if (!mysqli_stmt_execute($stmt)) {
            $errorCode = mysqli_errno($conn);
            $message = mysqli_error($conn);
            mysqli_stmt_close($stmt);

            if ($errorCode === 1451) {
                inventory_variable_response(false,
                    "This unit cannot be deleted because it is already being used.");
            }

            inventory_variable_response(false, $message);
        }

        mysqli_stmt_close($stmt);
        inventory_variable_response(true, "Inventory unit deleted successfully.");
    }

    if ($action === "add_supplier") {
        $name = trim($_POST["supplier_name"] ?? "");
        $contactPerson = trim($_POST["contact_person"] ?? "");
        $contactNumber = trim($_POST["contact_number"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $address = trim($_POST["address"] ?? "");
        $status = trim($_POST["status"] ?? "Active");

        if ($name === "") inventory_variable_response(false, "Supplier name is required.");
        if (!in_array($status, ["Active", "Inactive"], true)) $status = "Active";

        if ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            inventory_variable_response(false, "Please enter a valid supplier email.");
        }

        $check = mysqli_prepare($conn,
            "SELECT supplier_id FROM inventory_suppliers
             WHERE LOWER(TRIM(supplier_name)) = LOWER(TRIM(?)) LIMIT 1");
        if (!$check) inventory_variable_response(false, mysqli_error($conn));
        mysqli_stmt_bind_param($check, "s", $name);
        mysqli_stmt_execute($check);
        $result = mysqli_stmt_get_result($check);
        if ($result && mysqli_num_rows($result) > 0) {
            mysqli_stmt_close($check);
            inventory_variable_response(false, "This supplier already exists.");
        }
        mysqli_stmt_close($check);

        $stmt = mysqli_prepare($conn,
            "INSERT INTO inventory_suppliers
             (supplier_name, contact_person, contact_number, email, address, status)
             VALUES (?, ?, ?, ?, ?, ?)");
        if (!$stmt) inventory_variable_response(false, mysqli_error($conn));
        mysqli_stmt_bind_param(
            $stmt,
            "ssssss",
            $name,
            $contactPerson,
            $contactNumber,
            $email,
            $address,
            $status
        );
        if (!mysqli_stmt_execute($stmt)) {
            $message = mysqli_error($conn);
            mysqli_stmt_close($stmt);
            inventory_variable_response(false, $message);
        }
        $newId = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);
        inventory_variable_response(true, "Supplier added successfully.", ["id" => $newId]);
    }

    if ($action === "edit_supplier") {
        $id = (int)($_POST["supplier_id"] ?? 0);
        $name = trim($_POST["supplier_name"] ?? "");
        $contactPerson = trim($_POST["contact_person"] ?? "");
        $contactNumber = trim($_POST["contact_number"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $address = trim($_POST["address"] ?? "");
        $status = trim($_POST["status"] ?? "Active");

        if ($id <= 0) inventory_variable_response(false, "Invalid supplier ID.");
        if ($name === "") inventory_variable_response(false, "Supplier name is required.");
        if (!in_array($status, ["Active", "Inactive"], true)) $status = "Active";

        if ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            inventory_variable_response(false, "Please enter a valid supplier email.");
        }

        $check = mysqli_prepare($conn,
            "SELECT supplier_id FROM inventory_suppliers
             WHERE LOWER(TRIM(supplier_name)) = LOWER(TRIM(?))
               AND supplier_id <> ? LIMIT 1");
        if (!$check) inventory_variable_response(false, mysqli_error($conn));
        mysqli_stmt_bind_param($check, "si", $name, $id);
        mysqli_stmt_execute($check);
        $result = mysqli_stmt_get_result($check);
        if ($result && mysqli_num_rows($result) > 0) {
            mysqli_stmt_close($check);
            inventory_variable_response(false, "This supplier already exists.");
        }
        mysqli_stmt_close($check);

        $stmt = mysqli_prepare($conn,
            "UPDATE inventory_suppliers
             SET supplier_name = ?, contact_person = ?, contact_number = ?,
                 email = ?, address = ?, status = ?
             WHERE supplier_id = ?");
        if (!$stmt) inventory_variable_response(false, mysqli_error($conn));
        mysqli_stmt_bind_param(
            $stmt,
            "ssssssi",
            $name,
            $contactPerson,
            $contactNumber,
            $email,
            $address,
            $status,
            $id
        );
        if (!mysqli_stmt_execute($stmt)) {
            $message = mysqli_error($conn);
            mysqli_stmt_close($stmt);
            inventory_variable_response(false, $message);
        }
        mysqli_stmt_close($stmt);
        inventory_variable_response(true, "Supplier updated successfully.");
    }

    if ($action === "delete_supplier") {
        $id = (int)($_POST["supplier_id"] ?? 0);
        if ($id <= 0) inventory_variable_response(false, "Invalid supplier ID.");

        $stmt = mysqli_prepare($conn,
            "DELETE FROM inventory_suppliers WHERE supplier_id = ?");
        if (!$stmt) inventory_variable_response(false, mysqli_error($conn));
        mysqli_stmt_bind_param($stmt, "i", $id);

        if (!mysqli_stmt_execute($stmt)) {
            $errorCode = mysqli_errno($conn);
            $message = mysqli_error($conn);
            mysqli_stmt_close($stmt);

            if ($errorCode === 1451) {
                inventory_variable_response(false,
                    "This supplier cannot be deleted because it is already being used.");
            }

            inventory_variable_response(false, $message);
        }

        mysqli_stmt_close($stmt);
        inventory_variable_response(true, "Supplier deleted successfully.");
    }

    inventory_variable_response(false, "Invalid inventory variable action.");
}

/*
|--------------------------------------------------------------------------
| PET RECORDS ACTIONS
|--------------------------------------------------------------------------
| Species and Breeds use this same system_variables.php endpoint.
*/

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["pet_reference_action"])) {

    header("Content-Type: application/json; charset=UTF-8");

    $action = trim($_POST["pet_reference_action"]);

    function pet_reference_response($success, $message, $extra = []) {
        echo json_encode(array_merge([
            "success" => $success,
            "message" => $message
        ], $extra));
        exit();
    }

    if ($action === "add_species") {
        $species = trim($_POST["species"] ?? "");
        $status = trim($_POST["status"] ?? "Active");

        if ($species === "") pet_reference_response(false, "Species name is required.");
        if (!in_array($status, ["Active", "Inactive"], true)) $status = "Active";

        $check = mysqli_prepare($conn,
            "SELECT species_id FROM pet_species
             WHERE LOWER(TRIM(species)) = LOWER(TRIM(?)) LIMIT 1");
        if (!$check) pet_reference_response(false, mysqli_error($conn));
        mysqli_stmt_bind_param($check, "s", $species);
        mysqli_stmt_execute($check);
        $result = mysqli_stmt_get_result($check);
        if ($result && mysqli_num_rows($result) > 0) {
            mysqli_stmt_close($check);
            pet_reference_response(false, "This species already exists.");
        }
        mysqli_stmt_close($check);

        $stmt = mysqli_prepare($conn,
            "INSERT INTO pet_species (species, status) VALUES (?, ?)");
        if (!$stmt) pet_reference_response(false, mysqli_error($conn));
        mysqli_stmt_bind_param($stmt, "ss", $species, $status);
        if (!mysqli_stmt_execute($stmt)) {
            $message = mysqli_error($conn);
            mysqli_stmt_close($stmt);
            pet_reference_response(false, $message);
        }
        $newId = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);
        pet_reference_response(true, "Species added successfully.", ["id" => $newId]);
    }

    if ($action === "edit_species") {
        $speciesId = (int)($_POST["species_id"] ?? 0);
        $species = trim($_POST["species"] ?? "");
        $status = trim($_POST["status"] ?? "Active");

        if ($speciesId <= 0) pet_reference_response(false, "Invalid species ID.");
        if ($species === "") pet_reference_response(false, "Species name is required.");
        if (!in_array($status, ["Active", "Inactive"], true)) $status = "Active";

        $check = mysqli_prepare($conn,
            "SELECT species_id FROM pet_species
             WHERE LOWER(TRIM(species)) = LOWER(TRIM(?))
               AND species_id <> ? LIMIT 1");
        if (!$check) pet_reference_response(false, mysqli_error($conn));
        mysqli_stmt_bind_param($check, "si", $species, $speciesId);
        mysqli_stmt_execute($check);
        $result = mysqli_stmt_get_result($check);
        if ($result && mysqli_num_rows($result) > 0) {
            mysqli_stmt_close($check);
            pet_reference_response(false, "This species already exists.");
        }
        mysqli_stmt_close($check);

        $stmt = mysqli_prepare($conn,
            "UPDATE pet_species SET species = ?, status = ? WHERE species_id = ?");
        if (!$stmt) pet_reference_response(false, mysqli_error($conn));
        mysqli_stmt_bind_param($stmt, "ssi", $species, $status, $speciesId);
        if (!mysqli_stmt_execute($stmt)) {
            $message = mysqli_error($conn);
            mysqli_stmt_close($stmt);
            pet_reference_response(false, $message);
        }
        mysqli_stmt_close($stmt);
        pet_reference_response(true, "Species updated successfully.");
    }

    if ($action === "delete_species") {
        $speciesId = (int)($_POST["species_id"] ?? 0);
        if ($speciesId <= 0) pet_reference_response(false, "Invalid species ID.");

        $stmt = mysqli_prepare($conn,
            "DELETE FROM pet_species WHERE species_id = ?");
        if (!$stmt) pet_reference_response(false, mysqli_error($conn));
        mysqli_stmt_bind_param($stmt, "i", $speciesId);

        if (!mysqli_stmt_execute($stmt)) {
            $errorCode = mysqli_errno($conn);
            $message = mysqli_error($conn);
            mysqli_stmt_close($stmt);

            if ($errorCode === 1451) {
                pet_reference_response(false,
                    "This species cannot be deleted because it is still being used by one or more breeds.");
            }
            pet_reference_response(false, $message);
        }
        mysqli_stmt_close($stmt);
        pet_reference_response(true, "Species deleted successfully.");
    }

    if ($action === "add_breed") {
        $speciesId = (int)($_POST["species_id"] ?? 0);
        $breed = trim($_POST["breed"] ?? "");
        $status = trim($_POST["status"] ?? "Active");

        if ($speciesId <= 0) pet_reference_response(false, "Please select a species.");
        if ($breed === "") pet_reference_response(false, "Breed name is required.");
        if (!in_array($status, ["Active", "Inactive"], true)) $status = "Active";

        $speciesCheck = mysqli_prepare($conn,
            "SELECT species_id FROM pet_species WHERE species_id = ? LIMIT 1");
        if (!$speciesCheck) pet_reference_response(false, mysqli_error($conn));
        mysqli_stmt_bind_param($speciesCheck, "i", $speciesId);
        mysqli_stmt_execute($speciesCheck);
        $speciesResult = mysqli_stmt_get_result($speciesCheck);
        if (!$speciesResult || mysqli_num_rows($speciesResult) === 0) {
            mysqli_stmt_close($speciesCheck);
            pet_reference_response(false, "Selected species does not exist.");
        }
        mysqli_stmt_close($speciesCheck);

        $check = mysqli_prepare($conn,
            "SELECT breed_id FROM pet_breeds
             WHERE species_id = ?
               AND LOWER(TRIM(breed)) = LOWER(TRIM(?)) LIMIT 1");
        if (!$check) pet_reference_response(false, mysqli_error($conn));
        mysqli_stmt_bind_param($check, "is", $speciesId, $breed);
        mysqli_stmt_execute($check);
        $result = mysqli_stmt_get_result($check);
        if ($result && mysqli_num_rows($result) > 0) {
            mysqli_stmt_close($check);
            pet_reference_response(false, "This breed already exists for the selected species.");
        }
        mysqli_stmt_close($check);

        $stmt = mysqli_prepare($conn,
            "INSERT INTO pet_breeds (species_id, breed, status) VALUES (?, ?, ?)");
        if (!$stmt) pet_reference_response(false, mysqli_error($conn));
        mysqli_stmt_bind_param($stmt, "iss", $speciesId, $breed, $status);
        if (!mysqli_stmt_execute($stmt)) {
            $message = mysqli_error($conn);
            mysqli_stmt_close($stmt);
            pet_reference_response(false, $message);
        }
        $newId = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);
        pet_reference_response(true, "Breed added successfully.", ["id" => $newId]);
    }

    if ($action === "edit_breed") {
        $breedId = (int)($_POST["breed_id"] ?? 0);
        $speciesId = (int)($_POST["species_id"] ?? 0);
        $breed = trim($_POST["breed"] ?? "");
        $status = trim($_POST["status"] ?? "Active");

        if ($breedId <= 0) pet_reference_response(false, "Invalid breed ID.");
        if ($speciesId <= 0) pet_reference_response(false, "Please select a species.");
        if ($breed === "") pet_reference_response(false, "Breed name is required.");
        if (!in_array($status, ["Active", "Inactive"], true)) $status = "Active";

        $check = mysqli_prepare($conn,
            "SELECT breed_id FROM pet_breeds
             WHERE species_id = ?
               AND LOWER(TRIM(breed)) = LOWER(TRIM(?))
               AND breed_id <> ? LIMIT 1");
        if (!$check) pet_reference_response(false, mysqli_error($conn));
        mysqli_stmt_bind_param($check, "isi", $speciesId, $breed, $breedId);
        mysqli_stmt_execute($check);
        $result = mysqli_stmt_get_result($check);
        if ($result && mysqli_num_rows($result) > 0) {
            mysqli_stmt_close($check);
            pet_reference_response(false, "This breed already exists for the selected species.");
        }
        mysqli_stmt_close($check);

        $stmt = mysqli_prepare($conn,
            "UPDATE pet_breeds
             SET species_id = ?, breed = ?, status = ?
             WHERE breed_id = ?");
        if (!$stmt) pet_reference_response(false, mysqli_error($conn));
        mysqli_stmt_bind_param($stmt, "issi", $speciesId, $breed, $status, $breedId);
        if (!mysqli_stmt_execute($stmt)) {
            $message = mysqli_error($conn);
            mysqli_stmt_close($stmt);
            pet_reference_response(false, $message);
        }
        mysqli_stmt_close($stmt);
        pet_reference_response(true, "Breed updated successfully.");
    }

    if ($action === "delete_breed") {
        $breedId = (int)($_POST["breed_id"] ?? 0);
        if ($breedId <= 0) pet_reference_response(false, "Invalid breed ID.");

        $stmt = mysqli_prepare($conn,
            "DELETE FROM pet_breeds WHERE breed_id = ?");
        if (!$stmt) pet_reference_response(false, mysqli_error($conn));
        mysqli_stmt_bind_param($stmt, "i", $breedId);

        if (!mysqli_stmt_execute($stmt)) {
            $message = mysqli_error($conn);
            mysqli_stmt_close($stmt);
            pet_reference_response(false, $message);
        }
        mysqli_stmt_close($stmt);
        pet_reference_response(true, "Breed deleted successfully.");
    }

    pet_reference_response(false, "Invalid pet record action.");
}

$speciesList = [];

$speciesQuery = "
    SELECT
        species_id,
        species,
        status
    FROM pet_species
    ORDER BY species_id ASC
";

$speciesResult = mysqli_query($conn, $speciesQuery);

if ($speciesResult) {
    while ($speciesRow = mysqli_fetch_assoc($speciesResult)) {
        $speciesList[] = $speciesRow;
    }
}


$inventoryCategoriesList = [];
$inventoryCategoriesQuery = "
    SELECT category_id, category_name, description, status
    FROM inventory_categories
    ORDER BY category_name ASC
";
$inventoryCategoriesResult = mysqli_query($conn, $inventoryCategoriesQuery);
if ($inventoryCategoriesResult) {
    while ($inventoryCategoryRow = mysqli_fetch_assoc($inventoryCategoriesResult)) {
        $inventoryCategoriesList[] = $inventoryCategoryRow;
    }
}

$inventorySubcategoriesList = [];
$inventorySubcategoriesQuery = "
    SELECT
        s.subcategory_id,
        s.category_id,
        s.subcategory_name,
        s.description,
        s.status,
        c.category_name
    FROM inventory_subcategories s
    INNER JOIN inventory_categories c
        ON c.category_id = s.category_id
    ORDER BY c.category_name ASC, s.subcategory_name ASC
";
$inventorySubcategoriesResult = mysqli_query($conn, $inventorySubcategoriesQuery);
if ($inventorySubcategoriesResult) {
    while ($inventorySubcategoryRow = mysqli_fetch_assoc($inventorySubcategoriesResult)) {
        $inventorySubcategoriesList[] = $inventorySubcategoryRow;
    }
}

$inventoryUnitsList = [];
$inventoryUnitsQuery = "
    SELECT unit_id, unit_name, abbreviation, status
    FROM inventory_units
    ORDER BY unit_name ASC
";
$inventoryUnitsResult = mysqli_query($conn, $inventoryUnitsQuery);
if ($inventoryUnitsResult) {
    while ($inventoryUnitRow = mysqli_fetch_assoc($inventoryUnitsResult)) {
        $inventoryUnitsList[] = $inventoryUnitRow;
    }
}

$inventorySuppliersList = [];
$inventorySuppliersQuery = "
    SELECT supplier_id, supplier_name, contact_person, contact_number,
           email, address, status
    FROM inventory_suppliers
    ORDER BY supplier_name ASC
";
$inventorySuppliersResult = mysqli_query($conn, $inventorySuppliersQuery);
if ($inventorySuppliersResult) {
    while ($inventorySupplierRow = mysqli_fetch_assoc($inventorySuppliersResult)) {
        $inventorySuppliersList[] = $inventorySupplierRow;
    }
}

/* ==========================================================
   WEBSITE PRODUCT INVENTORY ITEMS
   Active Pet Food, Supplements and Others only.
========================================================== */

$websiteProductItems = [];

$websiteProductItemsQuery = "
    SELECT
        i.item_id,
        i.item_code,
        i.item_name,
        i.category_id,
        c.category_name,
        i.status
    FROM inventory_items i
    INNER JOIN inventory_categories c
        ON c.category_id = i.category_id
    WHERE i.status = 'Active'
      AND LOWER(TRIM(c.category_name))
          IN ('pet food', 'supplements', 'others')
    ORDER BY
        c.category_name ASC,
        i.item_name ASC
";

$websiteProductItemsResult = mysqli_query(
    $conn,
    $websiteProductItemsQuery
);

if ($websiteProductItemsResult) {

    while (
        $websiteProductItemRow =
        mysqli_fetch_assoc($websiteProductItemsResult)
    ) {

        $websiteProductItems[] =
            $websiteProductItemRow;

    }

}


/* ==========================================================
   WEBSITE PRODUCTS
   Products currently configured for the customer website.
========================================================== */

$websiteProductsList = [];

$websiteProductsQuery = "
    SELECT
        wp.website_product_id,
        wp.item_id,
        wp.pet_type,
        wp.description,
        wp.image_path,
        wp.status,
        i.item_name,
        i.item_code,
        c.category_name
    FROM website_products wp
    INNER JOIN inventory_items i
        ON i.item_id = wp.item_id
    INNER JOIN inventory_categories c
        ON c.category_id = i.category_id
    ORDER BY
        c.category_name ASC,
        wp.pet_type ASC,
        i.item_name ASC
";

$websiteProductsResult = mysqli_query($conn, $websiteProductsQuery);

if ($websiteProductsResult) {
    while ($websiteProductRow = mysqli_fetch_assoc($websiteProductsResult)) {
        $websiteProductsList[] = $websiteProductRow;
    }
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

    <title>System Variables | Veterinary MIS</title>

    <!-- SAME SYSTEM CSS AS APPOINTMENTS -->
    <link
        rel="stylesheet"
        href="../assets/css/layout.css"
    >

    <link
        rel="stylesheet"
        href="../assets/css/appointments.css"
    >

    <!-- SYSTEM VARIABLES CSS -->
    <link
        rel="stylesheet"
        href="../assets/css/system_variables.css"
    >

    <!-- FONT AWESOME -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
    >


    <!-- ADDITIONAL SYSTEM VARIABLE STYLES -->
    <style>
        .custom-variable-note {
            margin-top: 18px;
            padding: 14px 16px;
            border-radius: 10px;
            background: #f8fafc;
            color: #64748b;
            font-size: 14px;
        }

        .schedule-settings-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }

        .schedule-setting-card {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 18px;
            background: #fff;
        }

        .schedule-setting-card h4 {
            margin: 0 0 8px;
            font-size: 16px;
            color: #1f2937;
        }

        .schedule-setting-card p {
            margin: 0;
            color: #64748b;
            line-height: 1.5;
            font-size: 14px;
        }

        .custom-variable-content {
            display: none;
        }

        .custom-variable-content.active {
            display: block;
        }

        @media (max-width: 900px) {
            .schedule-settings-grid {
                grid-template-columns: 1fr;
            }
        }

        /* =========================================================
   WEBSITE PRODUCT MODAL
========================================================= */

.variable-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.45);
    display: none;
    align-items: center;
    justify-content: center;
    padding: 24px;
    z-index: 9999;
}

.variable-modal-overlay.show {
    display: flex !important;
}

.variable-modal {
    width: 100%;
    max-width: 620px;
    max-height: 90vh;
    overflow-y: auto;
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 20px 50px rgba(15, 23, 42, 0.20);
}

.variable-modal-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 20px;
    padding: 22px 24px;
    border-bottom: 1px solid #e5e7eb;
}

.variable-modal-header h3 {
    margin: 0;
    font-size: 21px;
    font-weight: 700;
    color: #111827;
}

.variable-modal-header p {
    margin: 6px 0 0;
    color: #6b7280;
    font-size: 14px;
    line-height: 1.5;
}

.variable-modal-close {
    width: 36px;
    height: 36px;
    border: none;
    border-radius: 8px;
    background: #f3f4f6;
    color: #6b7280;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 16px;
}

.variable-modal-close:hover {
    background: #e5e7eb;
    color: #111827;
}

#addWebsiteProductForm,
#editWebsiteProductForm {
    padding: 24px;
}

.variable-modal-field {
    margin-bottom: 18px;
}

.variable-modal-field label {
    display: block;
    margin-bottom: 7px;
    font-size: 14px;
    font-weight: 600;
    color: #374151;
}

.variable-modal-field label span {
    color: #ef4444;
}

.variable-modal-field select,
.variable-modal-field textarea,
.variable-modal-field input[type="file"] {
    width: 100%;
    box-sizing: border-box;
    font-family: inherit;
}

.variable-modal-field select,
.variable-modal-field textarea {
    border: 1px solid #d1d5db;
    border-radius: 8px;
    background: #ffffff;
    color: #111827;
    font-size: 14px;
    padding: 10px 12px;
    outline: none;
}

.variable-modal-field select {
    height: 42px;
}

.variable-modal-field textarea {
    min-height: 110px;
    resize: vertical;
}

.variable-modal-field select:focus,
.variable-modal-field textarea:focus {
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.10);
}

.variable-modal-field input[type="file"] {
    padding: 9px 0;
    font-size: 14px;
    color: #374151;
}

.variable-modal-field small {
    display: block;
    margin-top: 6px;
    color: #9ca3af;
    font-size: 12px;
    line-height: 1.4;
}

.variable-modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding-top: 6px;
}

.variable-modal-cancel,
.variable-modal-submit {
    border: none;
    border-radius: 8px;
    padding: 10px 16px;
    font-family: inherit;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
}

.variable-modal-cancel {
    background: #f3f4f6;
    color: #374151;
}

.variable-modal-cancel:hover {
    background: #e5e7eb;
}

.variable-modal-submit {
    background: #4f46e5;
    color: #ffffff;
}

.variable-modal-submit:hover {
    background: #4338ca;
}
    </style>

</head>


<body>


<div class="container">


    <!-- SIDEBAR -->
    <?php include "partials/sidebar.php"; ?>


    <div
        class="sidebar-overlay"
        id="sidebarOverlay"
    ></div>


    <!-- MAIN CONTENT -->
    <main
        class="content"
        id="mainContent"
    >


        <!-- TOPBAR -->
        <?php

        $pageTitle = "System Variables";
        $showAdminInfo = false;

        include "partials/topbar.php";

        ?>


        <div class="system-variables-page">


            <!-- ==========================================
                 BREADCRUMB
            =========================================== -->

            <div class="system-breadcrumb">

                <a href="settings.php">
                    Settings
                </a>

                <i class="fa-solid fa-chevron-right"></i>

                <span>
                    System Variables
                </span>

            </div>


            <!-- ==========================================
                 PAGE HEADER CARD
            =========================================== -->

            <div class="system-header-card">


                <div class="system-header-info">

                    <h1>
                        System Variables
                    </h1>

                    <p>
                        Manage reference data and system
                        settings used across different modules.
                    </p>

                </div>


                <button
                    type="button"
                    class="system-add-btn"
                    id="addVariableBtn"
                >

                    <i class="fa-solid fa-plus"></i>

                    Add New

                </button>


            </div>


            <!-- ==========================================
                 MAIN SYSTEM VARIABLES AREA
            =========================================== -->

            <div class="system-variables-layout">


                <!-- ======================================
                     LEFT PANEL
                ======================================= -->

                <div class="variable-groups-card">


                    <div class="variable-groups-title">

                        <span>
                            VARIABLE GROUPS
                        </span>

                    </div>


                    <!-- APPOINTMENTS -->

                    <button
                        type="button"
                        class="variable-group"
                        data-group="appointments"
                    >

                        <div class="variable-group-icon blue">

                            <i class="fa-regular fa-calendar"></i>

                        </div>


                        <div class="variable-group-info">

                            <strong>
                                Appointments
                            </strong>

                            <small>
                                Manage appointment related
                                dropdowns and reasons.
                            </small>

                        </div>


                        <i class="fa-solid fa-chevron-right variable-arrow"></i>

                    </button>


                    <!-- PET RECORDS -->

                    <button
                        type="button"
                        class="variable-group"
                        data-group="pet-records"
                    >

                        <div class="variable-group-icon green">

                            <i class="fa-solid fa-paw"></i>

                        </div>


                        <div class="variable-group-info">

                            <strong>
                                Pet Records
                            </strong>

                            <small>
                                Manage pet species, breeds,
                                colors and other references.
                            </small>

                        </div>


                        <i class="fa-solid fa-chevron-right variable-arrow"></i>

                    </button>


                    <!-- BILLING -->

                    <button
                        type="button"
                        class="variable-group"
                        data-group="billing"
                    >

                        <div class="variable-group-icon orange">

                            <i class="fa-solid fa-file-invoice-dollar"></i>

                        </div>


                        <div class="variable-group-info">

                            <strong>
                                Services
                            </strong>

                            <small>
                                Manage services, pricing
                                rules, test kits and units.
                            </small>

                        </div>


                        <i class="fa-solid fa-chevron-right variable-arrow"></i>

                    </button>


                    <!-- INVENTORY -->

                    <button
                        type="button"
                        class="variable-group"
                        data-group="inventory"
                    >

                        <div class="variable-group-icon purple">

                            <i class="fa-solid fa-box"></i>

                        </div>


                        <div class="variable-group-info">

                            <strong>
                                Inventory
                            </strong>

                            <small>
                                Manage item categories,
                                units and other references.
                            </small>

                        </div>


                        <i class="fa-solid fa-chevron-right variable-arrow"></i>

                    </button>


                    <!-- VACCINATION -->

                    <button
                        type="button"
                        class="variable-group"
                        data-group="vaccination"
                    >

                        <div class="variable-group-icon pink">

                            <i class="fa-solid fa-syringe"></i>

                        </div>


                        <div class="variable-group-info">

                            <strong>
                                Vax Certificate
                            </strong>

                            <small>
                                Manage vaccine types and
                                related references.
                            </small>

                        </div>


                        <i class="fa-solid fa-chevron-right variable-arrow"></i>

                    </button>


                    <!-- ARCHIVE -->

                    <button
                        type="button"
                        class="variable-group"
                        data-group="archive"
                    >

                        <div class="variable-group-icon brown">

                            <i class="fa-solid fa-box-archive"></i>

                        </div>


                        <div class="variable-group-info">

                            <strong>
                                Archive
                            </strong>

                            <small>
                                Manage archive reasons
                                and other archive settings.
                            </small>

                        </div>


                        <i class="fa-solid fa-chevron-right variable-arrow"></i>

                    </button>


                    <!-- INFORMATION NOTE -->

                    <div class="variable-info-note">

                        <i class="fa-solid fa-circle-info"></i>

                        <p>
                            These variables are used as
                            dropdown values and references
                            in different modules of the system.
                        </p>

                    </div>


                </div>


                <!-- ======================================
     RIGHT PANEL
======================================= -->

<div
    class="variable-details-card"
    id="variableDetails"
>


    <!-- =========================================================
         APPOINTMENTS VARIABLE CONTENT
         Added without changing the existing Billing content.
    ========================================================== -->
    <div
        class="billing-variable-content custom-variable-content"
        id="appointmentsContent"
        style="display:none;"
    >

        <div class="variable-detail-header">

            <div class="variable-detail-title">

                <div class="detail-main-icon">
                    <i class="fa-regular fa-calendar"></i>
                </div>

                <div>
                    <h2>Appointments</h2>

                    <p>
                        Manage appointment types, statuses and
                        scheduling settings.
                    </p>
                </div>

            </div>

            <div class="detail-header-actions">

                <button
                    type="button"
                    class="group-action edit"
                    id="editAppointmentsGroup"
                >
                    <i class="fa-solid fa-pen"></i>
                    Edit Group
                </button>

                <button
                    type="button"
                    class="group-action deactivate"
                    id="deactivateAppointmentsGroup"
                >
                    <i class="fa-regular fa-trash-can"></i>
                    Deactivate Group
                </button>

            </div>

        </div>


        <div class="variable-tabs">

            <button
                type="button"
                class="variable-tab active"
                data-tab="appointment-types"
            >
                Appointment Types
            </button>

            <button
                type="button"
                class="variable-tab"
                data-tab="appointment-status"
            >
                Appointment Status
            </button>

            <button
                type="button"
                class="variable-tab"
                data-tab="appointment-schedule"
            >
                Appointment Schedule
            </button>

        </div>


        <!-- APPOINTMENT TYPES -->
        <div
            class="billing-tab-content active"
            id="appointment-types"
        >

            <div class="billing-section-header">

                <div>
                    <h3>Appointment Types</h3>

                    <p>
                        Available appointment types used when
                        creating an appointment.
                    </p>
                </div>

                <button
                    type="button"
                    class="add-variable-btn"
                    id="addAppointmentTypeBtn"
                >
                    <i class="fa-solid fa-plus"></i>
                    Add Type
                </button>

            </div>

            <div class="variable-table-wrapper">

                <table class="variable-table">

                    <thead>
                        <tr>
                            <th>Appointment Type</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                     <tbody>

<?php

$appointmentTypesQuery = "
    SELECT
        appointment_type_id,
        appointment_type,
        status
    FROM appointment_types
    ORDER BY appointment_type_id ASC
";

$appointmentTypesResult = mysqli_query(
    $conn,
    $appointmentTypesQuery
);

if (
    $appointmentTypesResult &&
    mysqli_num_rows($appointmentTypesResult) > 0
) {

    while (
        $appointmentType =
        mysqli_fetch_assoc($appointmentTypesResult)
    ) {

        $appointmentTypeId =
            (int)$appointmentType['appointment_type_id'];

        $appointmentTypeName =
            htmlspecialchars(
                $appointmentType['appointment_type']
            );

        $appointmentTypeStatus =
            htmlspecialchars(
                $appointmentType['status']
            );

?>

<tr>

    <td>
        <strong>
            <?= $appointmentTypeName ?>
        </strong>
    </td>

    <td>
        <span
            class="status <?= $appointmentTypeStatus === 'Active' ? 'active' : '' ?>"
        >
            <?= $appointmentTypeStatus ?>
        </span>
    </td>

    <td class="table-actions">

        <button
            type="button"
            class="icon-action edit"
            title="Edit Appointment Type"
            data-id="<?= $appointmentTypeId ?>"
            data-type="<?= $appointmentTypeName ?>"
        >
            <i class="fa-solid fa-pen"></i>
        </button>

        <button
            type="button"
            class="icon-action delete"
            title="Delete Appointment Type"
            data-id="<?= $appointmentTypeId ?>"
            data-type="<?= $appointmentTypeName ?>"
        >
            <i class="fa-regular fa-trash-can"></i>
        </button>

    </td>

</tr>

<?php

    }

} else {

?>

<tr>

    <td
        colspan="3"
        style="text-align: center; padding: 30px;"
    >
        <span style="color: #6b7280;">
            No appointment types added yet.
        </span>
    </td>

</tr>

<?php

}

?>

</tbody>

                </table>

            </div>

        </div>


        <!-- APPOINTMENT STATUS -->
        <div
            class="billing-tab-content"
            id="appointment-status"
        >

            <div class="billing-section-header">

                <div>
                    <h3>Appointment Status</h3>

                    <p>
                        Status values used throughout the
                        appointment workflow.
                    </p>
                </div>

                <button
                    type="button"
                    class="add-variable-btn"
                    id="addAppointmentStatusBtn"
                >
                    <i class="fa-solid fa-plus"></i>
                    Add Status
                </button>

            </div>

            <div class="variable-table-wrapper">

                <table class="variable-table">

                    <thead>
                        <tr>
                            <th>Appointment Status</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                        <tr>
                            <td><strong>Pending</strong></td>
                            <td><span class="status active">Active</span></td>
                            <td class="table-actions">
                                <button
                                    type="button"
                                    class="icon-action edit"
                                    title="Edit Appointment Status"
                                    data-status="Pending"
                                >
                                    <i class="fa-solid fa-pen"></i>
                                </button>

                                <button
                                    type="button"
                                    class="icon-action delete"
                                    title="Delete Appointment Status"
                                    data-status="Pending"
                                >
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>
                            </td>
                        </tr>

                        <tr>
                            <td><strong>Confirmed</strong></td>
                            <td><span class="status active">Active</span></td>
                            <td class="table-actions">
                                <button
                                    type="button"
                                    class="icon-action edit"
                                    title="Edit Appointment Status"
                                    data-status="Confirmed"
                                >
                                    <i class="fa-solid fa-pen"></i>
                                </button>

                                <button
                                    type="button"
                                    class="icon-action delete"
                                    title="Delete Appointment Status"
                                    data-status="Confirmed"
                                >
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>
                            </td>
                        </tr>

                        <tr>
                            <td><strong>Arrived</strong></td>
                            <td><span class="status active">Active</span></td>
                            <td class="table-actions">
                                <button
                                    type="button"
                                    class="icon-action edit"
                                    title="Edit Appointment Status"
                                    data-status="Arrived"
                                >
                                    <i class="fa-solid fa-pen"></i>
                                </button>

                                <button
                                    type="button"
                                    class="icon-action delete"
                                    title="Delete Appointment Status"
                                    data-status="Arrived"
                                >
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>
                            </td>
                        </tr>

                        <tr>
                            <td><strong>Completed</strong></td>
                            <td><span class="status active">Active</span></td>
                            <td class="table-actions">
                                <button
                                    type="button"
                                    class="icon-action edit"
                                    title="Edit Appointment Status"
                                    data-status="Completed"
                                >
                                    <i class="fa-solid fa-pen"></i>
                                </button>

                                <button
                                    type="button"
                                    class="icon-action delete"
                                    title="Delete Appointment Status"
                                    data-status="Completed"
                                >
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>
                            </td>
                        </tr>

                        <tr>
                            <td><strong>Cancelled</strong></td>
                            <td><span class="status active">Active</span></td>
                            <td class="table-actions">
                                <button
                                    type="button"
                                    class="icon-action edit"
                                    title="Edit Appointment Status"
                                    data-status="Cancelled"
                                >
                                    <i class="fa-solid fa-pen"></i>
                                </button>

                                <button
                                    type="button"
                                    class="icon-action delete"
                                    title="Delete Appointment Status"
                                    data-status="Cancelled"
                                >
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>
                            </td>
                        </tr>

                    </tbody>

                </table>

            </div>

        </div>


        <!-- APPOINTMENT SCHEDULE -->
        <div
            class="billing-tab-content"
            id="appointment-schedule"
        >

            <div class="billing-section-header">

                <div>
                    <h3>Appointment Schedule</h3>

                    <p>
                        Manage the clinic's available appointment
                        schedule settings.
                    </p>
                </div>

            </div>

            <div class="schedule-settings-grid">

                <div class="schedule-setting-card">
                    <h4>Available Days</h4>
                    <p>
                        Configure the days when appointments
                        can be scheduled.
                    </p>
                </div>

                <div class="schedule-setting-card">
                    <h4>Available Time Slots</h4>
                    <p>
                        Configure the time slots that can be
                        selected during appointment booking.
                    </p>
                </div>

                <div class="schedule-setting-card">
                    <h4>Clinic Hours</h4>
                    <p>
                        Configure the clinic's opening and
                        closing hours.
                    </p>
                </div>

            </div>

            <div class="custom-variable-note">
                <i class="fa-solid fa-circle-info"></i>
                Schedule values can be configured here and
                used by the appointment booking form.
            </div>

        </div>

    </div>


    <!-- =========================================================
         PET RECORDS VARIABLE CONTENT
         Uses the same structure as Billing.
    ========================================================== -->
    <div
        class="billing-variable-content custom-variable-content"
        id="petRecordsContent"
        style="display:none;"
    >

        <div class="variable-detail-header">

            <div class="variable-detail-title">

                <div class="detail-main-icon">
                    <i class="fa-solid fa-paw"></i>
                </div>

                <div>
                    <h2>Pet Records</h2>
                    <p>
                        Manage pet species and breeds used in pet registration.
                    </p>
                </div>

            </div>

            <div class="detail-header-actions">

                <button
                    type="button"
                    class="group-action edit"
                    id="editPetRecordsGroup"
                >
                    <i class="fa-solid fa-pen"></i>
                    Edit Group
                </button>

                <button
                    type="button"
                    class="group-action deactivate"
                    id="deactivatePetRecordsGroup"
                >
                    <i class="fa-regular fa-trash-can"></i>
                    Deactivate Group
                </button>

            </div>

        </div>

        <!-- PET RECORDS TABS - SAME STRUCTURE AS BILLING -->
        <div class="variable-tabs">

            <button
                type="button"
                class="variable-tab active"
                data-tab="pet-species"
            >
                Species
            </button>

            <button
                type="button"
                class="variable-tab"
                data-tab="pet-breeds"
            >
                Breeds
            </button>

        </div>

        <!-- SPECIES -->
        <div
            class="billing-tab-content active"
            id="pet-species"
        >

            <div class="billing-section-header">
                <div>
                    <h3>Species</h3>
                    <p>
                        Species available when registering a pet.
                    </p>
                </div>

                <button
                    type="button"
                    class="add-variable-btn"
                    id="addPetSpeciesBtn"
                >
                    <i class="fa-solid fa-plus"></i>
                    Add Species
                </button>
            </div>

            <div class="variable-table-wrapper">

                <table class="variable-table">
                    <thead>
                        <tr>
                            <th>Species</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($speciesList as $species): ?>

                            <tr>

                                <td>
                                    <strong>
                                        <?= htmlspecialchars($species["species"]) ?>
                                    </strong>
                                </td>

                                <td>
                                    <span class="status <?= strtolower($species["status"]) === 'active' ? 'active' : '' ?>">
                                        <?= htmlspecialchars($species["status"]) ?>
                                    </span>
                                </td>

                                <td class="table-actions">
                                    <button
                                        type="button"
                                        class="icon-action edit"
                                        title="Edit Species"
                                        data-id="<?= (int)$species["species_id"] ?>"
                                        data-species="<?= htmlspecialchars($species["species"], ENT_QUOTES) ?>"
                                    >
                                        <i class="fa-solid fa-pen"></i>
                                    </button>

                                    <button
                                        type="button"
                                        class="icon-action delete"
                                        title="Delete Species"
                                        data-id="<?= (int)$species["species_id"] ?>"
                                        data-species="<?= htmlspecialchars($species["species"], ENT_QUOTES) ?>"
                                    >
                                        <i class="fa-regular fa-trash-can"></i>
                                    </button>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>
                </table>

            </div>

        </div>

        <!-- BREEDS -->
        <div
            class="billing-tab-content"
            id="pet-breeds"
        >

            <div class="billing-section-header">
                <div>
                    <h3>Breeds</h3>
                    <p>
                        Breeds grouped by species for use in pet registration.
                    </p>
                </div>

                <button
                    type="button"
                    class="add-variable-btn"
                    id="addPetBreedBtn"
                >
                    <i class="fa-solid fa-plus"></i>
                    Add Breed
                </button>
            </div>

            <div class="variable-table-wrapper">

                <table class="variable-table">
                    <thead>
                        <tr>
                            <th>Breed</th>
                            <th>Species</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php

                        $breedsQuery = "
                            SELECT
                                pb.breed_id,
                                pb.species_id,
                                pb.breed,
                                pb.status,
                                ps.species
                            FROM pet_breeds pb
                            INNER JOIN pet_species ps
                                ON pb.species_id = ps.species_id
                            ORDER BY pb.species_id ASC, pb.breed ASC
                        ";

                        $breedsResult = mysqli_query($conn, $breedsQuery);

                        if ($breedsResult && mysqli_num_rows($breedsResult) > 0):
                            while ($breedRow = mysqli_fetch_assoc($breedsResult)):

                                $breedId = (int)$breedRow['breed_id'];
                                $speciesId = (int)$breedRow['species_id'];
                                $breedName = htmlspecialchars($breedRow['breed'], ENT_QUOTES, 'UTF-8');
                                $breedSpecies = htmlspecialchars($breedRow['species'], ENT_QUOTES, 'UTF-8');
                                $breedStatus = htmlspecialchars($breedRow['status'], ENT_QUOTES, 'UTF-8');

                        ?>

                            <tr>

                                <td>
                                    <strong><?= $breedName ?></strong>
                                </td>

                                <td>
                                    <?= $breedSpecies ?>
                                </td>

                                <td>
                                    <span class="status <?= strtolower($breedRow['status']) === 'active' ? 'active' : '' ?>">
                                        <?= $breedStatus ?>
                                    </span>
                                </td>

                                <td class="table-actions">

                                    <button
                                        type="button"
                                        class="icon-action edit"
                                        title="Edit Breed"
                                        data-id="<?= $breedId ?>"
                                        data-breed="<?= $breedName ?>"
                                        data-species-id="<?= $speciesId ?>"
                                    >
                                        <i class="fa-solid fa-pen"></i>
                                    </button>

                                    <button
                                        type="button"
                                        class="icon-action delete"
                                        title="Delete Breed"
                                        data-id="<?= $breedId ?>"
                                        data-breed="<?= $breedName ?>"
                                    >
                                        <i class="fa-regular fa-trash-can"></i>
                                    </button>

                                </td>

                            </tr>

                        <?php
                            endwhile;
                        else:
                        ?>

                            <tr>
                                <td colspan="4" style="text-align: center; padding: 30px;">
                                    <span style="color: #6b7280;">
                                        No breeds added yet.
                                    </span>
                                </td>
                            </tr>

                        <?php endif; ?>

                    </tbody>
                </table>

            </div>

        </div>

    </div>


    <!-- =========================================================
         INVENTORY VARIABLE CONTENT
         Reference data for the Inventory module.
    ========================================================== -->
    <div
        class="billing-variable-content custom-variable-content inventory-variable-content"
        id="inventoryContent"
        style="display:none;"
    >

        <div class="variable-detail-header">

            <div class="variable-detail-title">

                <div class="detail-main-icon inventory">
                    <i class="fa-solid fa-box"></i>
                </div>

                <div>
                    <h2>Inventory</h2>
                    <p>
                        Manage inventory categories, units and suppliers used by the inventory module.
                    </p>
                </div>

            </div>

        </div>

        <div class="variable-tabs">

            <button
                type="button"
                class="variable-tab active"
                data-tab="inventory-categories"
            >
                Item Categories
            </button>

            <button
                type="button"
                class="variable-tab"
                data-tab="inventory-subcategories"
            >
                Subcategories
            </button>

            <button
                type="button"
                class="variable-tab"
                data-tab="inventory-units"
            >
                Units
            </button>

            <button
                type="button"
                class="variable-tab"
                data-tab="inventory-suppliers"
            >
                Suppliers
            </button>

            <button
                type="button"
                class="variable-tab"
                data-tab="inventory-website-products"
            >
                Website Products
            </button>    

        </div>

        <!-- INVENTORY CATEGORIES -->
        <div
            class="billing-tab-content active"
            id="inventory-categories"
        >

            <div class="billing-section-header">

                <div>
                    <h3>Item Categories</h3>
                    <p>
                        Categories used when adding and filtering inventory items.
                    </p>
                </div>

                <button
                    type="button"
                    class="add-variable-btn"
                    id="addInventoryCategoryBtn"
                >
                    <i class="fa-solid fa-plus"></i>
                    Add Category
                </button>

            </div>

            <div class="variable-table-wrapper">

                <table class="variable-table">

                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if (count($inventoryCategoriesList) > 0): ?>

                        <?php foreach ($inventoryCategoriesList as $category): ?>

                            <tr>

                                <td>
                                    <strong>
                                        <?= htmlspecialchars($category["category_name"]) ?>
                                    </strong>
                                </td>

                                <td>
                                    <?= htmlspecialchars($category["description"] ?? "") ?>
                                </td>

                                <td>
                                    <span class="status <?= strtolower($category["status"]) === "active" ? "active" : "" ?>">
                                        <?= htmlspecialchars($category["status"]) ?>
                                    </span>
                                </td>

                                <td class="table-actions">

                                    <button
                                        type="button"
                                        class="icon-action edit inventory-category-edit"
                                        title="Edit Category"
                                        data-id="<?= (int)$category["category_id"] ?>"
                                        data-name="<?= htmlspecialchars($category["category_name"], ENT_QUOTES) ?>"
                                        data-description="<?= htmlspecialchars($category["description"] ?? "", ENT_QUOTES) ?>"
                                        data-status="<?= htmlspecialchars($category["status"], ENT_QUOTES) ?>"
                                    >
                                        <i class="fa-solid fa-pen"></i>
                                    </button>

                                    <button
                                        type="button"
                                        class="icon-action delete inventory-category-delete"
                                        title="Delete Category"
                                        data-id="<?= (int)$category["category_id"] ?>"
                                        data-name="<?= htmlspecialchars($category["category_name"], ENT_QUOTES) ?>"
                                    >
                                        <i class="fa-regular fa-trash-can"></i>
                                    </button>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="4" style="text-align:center; padding:30px;">
                                <span style="color:#6b7280;">
                                    No inventory categories added yet.
                                </span>
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

        <!-- INVENTORY SUBCATEGORIES -->
        <div
            class="billing-tab-content"
            id="inventory-subcategories"
        >

            <div class="billing-section-header">
                <div>
                    <h3>Subcategories</h3>
                    <p>
                        Manage subcategories under each inventory category.
                    </p>
                </div>

                <button
                    type="button"
                    class="add-variable-btn"
                    id="addInventorySubcategoryBtn"
                >
                    <i class="fa-solid fa-plus"></i>
                    Add Subcategory
                </button>
            </div>

            <div class="variable-table-wrapper">
                <table class="variable-table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Subcategory</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if (count($inventorySubcategoriesList) > 0): ?>
                        <?php foreach ($inventorySubcategoriesList as $subcategory): ?>
                            <tr>
                                <td>
                                    <strong>
                                        <?= htmlspecialchars($subcategory["category_name"]) ?>
                                    </strong>
                                </td>

                                <td>
                                    <?= htmlspecialchars($subcategory["subcategory_name"]) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($subcategory["description"] ?? "") ?>
                                </td>

                                <td>
                                    <span class="status <?= strtolower($subcategory["status"]) === "active" ? "active" : "" ?>">
                                        <?= htmlspecialchars($subcategory["status"]) ?>
                                    </span>
                                </td>

                                <td class="table-actions">
                                    <button
                                        type="button"
                                        class="icon-action edit inventory-subcategory-edit"
                                        title="Edit Subcategory"
                                        data-id="<?= (int)$subcategory["subcategory_id"] ?>"
                                        data-category-id="<?= (int)$subcategory["category_id"] ?>"
                                        data-name="<?= htmlspecialchars($subcategory["subcategory_name"], ENT_QUOTES) ?>"
                                        data-description="<?= htmlspecialchars($subcategory["description"] ?? "", ENT_QUOTES) ?>"
                                        data-status="<?= htmlspecialchars($subcategory["status"], ENT_QUOTES) ?>"
                                    >
                                        <i class="fa-solid fa-pen"></i>
                                    </button>

                                    <button
                                        type="button"
                                        class="icon-action delete inventory-subcategory-delete"
                                        title="Delete Subcategory"
                                        data-id="<?= (int)$subcategory["subcategory_id"] ?>"
                                        data-name="<?= htmlspecialchars($subcategory["subcategory_name"], ENT_QUOTES) ?>"
                                    >
                                        <i class="fa-regular fa-trash-can"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align:center; padding:30px;">
                                <span style="color:#6b7280;">
                                    No inventory subcategories added yet.
                                </span>
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>

        <!-- INVENTORY UNITS -->
        <div
            class="billing-tab-content"
            id="inventory-units"
        >

            <div class="billing-section-header">

                <div>
                    <h3>Units</h3>
                    <p>
                        Units used for inventory quantities and item measurements.
                    </p>
                </div>

                <button
                    type="button"
                    class="add-variable-btn"
                    id="addInventoryUnitBtn"
                >
                    <i class="fa-solid fa-plus"></i>
                    Add Unit
                </button>

            </div>

            <div class="variable-table-wrapper">

                <table class="variable-table">

                    <thead>
                        <tr>
                            <th>Unit</th>
                            <th>Abbreviation</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if (count($inventoryUnitsList) > 0): ?>

                        <?php foreach ($inventoryUnitsList as $unit): ?>

                            <tr>

                                <td>
                                    <strong>
                                        <?= htmlspecialchars($unit["unit_name"]) ?>
                                    </strong>
                                </td>

                                <td>
                                    <?= htmlspecialchars($unit["abbreviation"]) ?>
                                </td>

                                <td>
                                    <span class="status <?= strtolower($unit["status"]) === "active" ? "active" : "" ?>">
                                        <?= htmlspecialchars($unit["status"]) ?>
                                    </span>
                                </td>

                                <td class="table-actions">

                                    <button
                                        type="button"
                                        class="icon-action edit inventory-unit-edit"
                                        title="Edit Unit"
                                        data-id="<?= (int)$unit["unit_id"] ?>"
                                        data-name="<?= htmlspecialchars($unit["unit_name"], ENT_QUOTES) ?>"
                                        data-abbreviation="<?= htmlspecialchars($unit["abbreviation"], ENT_QUOTES) ?>"
                                        data-status="<?= htmlspecialchars($unit["status"], ENT_QUOTES) ?>"
                                    >
                                        <i class="fa-solid fa-pen"></i>
                                    </button>

                                    <button
                                        type="button"
                                        class="icon-action delete inventory-unit-delete"
                                        title="Delete Unit"
                                        data-id="<?= (int)$unit["unit_id"] ?>"
                                        data-name="<?= htmlspecialchars($unit["unit_name"], ENT_QUOTES) ?>"
                                    >
                                        <i class="fa-regular fa-trash-can"></i>
                                    </button>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="4" style="text-align:center; padding:30px;">
                                <span style="color:#6b7280;">
                                    No inventory units added yet.
                                </span>
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

        <!-- INVENTORY SUPPLIERS -->
        <div
            class="billing-tab-content"
            id="inventory-suppliers"
        >

            <div class="billing-section-header">

                <div>
                    <h3>Suppliers</h3>
                    <p>
                        Manage suppliers used when receiving inventory stock.
                    </p>
                </div>

                <button
                    type="button"
                    class="add-variable-btn"
                    id="addInventorySupplierBtn"
                >
                    <i class="fa-solid fa-plus"></i>
                    Add Supplier
                </button>

            </div>

            <div class="variable-table-wrapper">

                <table class="variable-table">

                    <thead>
                        <tr>
                            <th>Supplier</th>
                            <th>Contact Person</th>
                            <th>Contact Number</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if (count($inventorySuppliersList) > 0): ?>

                        <?php foreach ($inventorySuppliersList as $supplier): ?>

                            <tr>

                                <td>
                                    <strong>
                                        <?= htmlspecialchars($supplier["supplier_name"]) ?>
                                    </strong>
                                </td>

                                <td>
                                    <?= htmlspecialchars($supplier["contact_person"] ?? "") ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($supplier["contact_number"] ?? "") ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($supplier["email"] ?? "") ?>
                                </td>

                                <td>
                                    <span class="status <?= strtolower($supplier["status"]) === "active" ? "active" : "" ?>">
                                        <?= htmlspecialchars($supplier["status"]) ?>
                                    </span>
                                </td>

                                <td class="table-actions">

                                    <button
                                        type="button"
                                        class="icon-action edit inventory-supplier-edit"
                                        title="Edit Supplier"
                                        data-id="<?= (int)$supplier["supplier_id"] ?>"
                                        data-name="<?= htmlspecialchars($supplier["supplier_name"], ENT_QUOTES) ?>"
                                        data-contact-person="<?= htmlspecialchars($supplier["contact_person"] ?? "", ENT_QUOTES) ?>"
                                        data-contact-number="<?= htmlspecialchars($supplier["contact_number"] ?? "", ENT_QUOTES) ?>"
                                        data-email="<?= htmlspecialchars($supplier["email"] ?? "", ENT_QUOTES) ?>"
                                        data-address="<?= htmlspecialchars($supplier["address"] ?? "", ENT_QUOTES) ?>"
                                        data-status="<?= htmlspecialchars($supplier["status"], ENT_QUOTES) ?>"
                                    >
                                        <i class="fa-solid fa-pen"></i>
                                    </button>

                                    <button
                                        type="button"
                                        class="icon-action delete inventory-supplier-delete"
                                        title="Delete Supplier"
                                        data-id="<?= (int)$supplier["supplier_id"] ?>"
                                        data-name="<?= htmlspecialchars($supplier["supplier_name"], ENT_QUOTES) ?>"
                                    >
                                        <i class="fa-regular fa-trash-can"></i>
                                    </button>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="6" style="text-align:center; padding:30px;">
                                <span style="color:#6b7280;">
                                    No suppliers added yet.
                                </span>
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

            <div class="inventory-variable-note">
                <i class="fa-solid fa-circle-info"></i>
                <span>
                    Supplier records will be available for Stock In transactions in the Inventory module.
                </span>
            </div>

        </div>

    
    
            <!-- INVENTORY WEBSITE PRODUCTS -->
        <div
            class="billing-tab-content"
            id="inventory-website-products"
        >

            <div class="billing-section-header">

                <div>
                    <h3>
                        Website Products
                    </h3>

                    <p>
                        Manage pet food products displayed on the customer website.
                    </p>
                </div>

                <button
                    type="button"
                    class="add-variable-btn"
                    id="addWebsiteProductBtn"
                >
                    <i class="fa-solid fa-plus"></i>
                    Add Product
                </button>

            </div>


            <div class="variable-table-wrapper">

                <table class="variable-table">

                    <thead>
                        <tr>

                            <th>
                                Product
                            </th>

                            <th>
                                Description
                            </th>

                            <th>
                                Image
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Actions
                            </th>

                        </tr>
                    </thead>


                    <tbody>

<?php if (count($websiteProductsList) > 0): ?>

    <?php foreach ($websiteProductsList as $websiteProduct): ?>

        <?php
            $websiteProductId =
                (int)$websiteProduct['website_product_id'];

            $productName =
                htmlspecialchars(
                    $websiteProduct['item_name']
                );

            $categoryName =
                htmlspecialchars(
                    $websiteProduct['category_name']
                );

            $petType =
                $websiteProduct['pet_type'] !== null
                    ? htmlspecialchars($websiteProduct['pet_type'])
                    : '—';

            $description =
                htmlspecialchars(
                    $websiteProduct['description'] ?? ''
                );

            $imagePath =
                htmlspecialchars(
                    $websiteProduct['image_path'] ?? ''
                );

            $productStatus =
                htmlspecialchars(
                    $websiteProduct['status']
                );
        ?>

        <tr>

            <!-- PRODUCT -->
            <td>
                <strong>
                    <?= $productName ?>
                </strong>

                <small
                    style="
                        display:block;
                        margin-top:4px;
                        color:#6b7280;
                    "
                >
                    <?= $categoryName ?>
                    <?php if ($websiteProduct['pet_type'] !== null): ?>
                        · <?= $petType ?>
                    <?php endif; ?>
                </small>
            </td>


            <!-- DESCRIPTION -->
            <td>
                <?= $description !== ''
                    ? $description
                    : '<span style="color:#9ca3af;">No description</span>'
                ?>
            </td>


            <!-- IMAGE -->
            <td>

                <?php if ($imagePath !== ''): ?>

                    <img
                        src="../<?= $imagePath ?>"
                        alt="<?= $productName ?>"
                        style="
                            width:55px;
                            height:55px;
                            object-fit:cover;
                            border-radius:8px;
                            border:1px solid #e5e7eb;
                        "
                    >

                <?php else: ?>

                    <span style="color:#9ca3af;">
                        No image
                    </span>

                <?php endif; ?>

            </td>


            <!-- STATUS -->
            <td>

                <span
                    class="status <?= $productStatus === 'Visible' ? 'active' : '' ?>"
                >
                    <?= $productStatus ?>
                </span>

            </td>


            <!-- ACTIONS -->
            <td class="table-actions">

                <button
                    type="button"
                    class="icon-action edit"
                    title="Edit Website Product"
                    data-id="<?= $websiteProductId ?>"
                >
                    <i class="fa-solid fa-pen"></i>
                </button>


                <button
                    type="button"
                    class="icon-action delete"
                    title="Delete Website Product"
                    data-id="<?= $websiteProductId ?>"
                >
                    <i class="fa-regular fa-trash-can"></i>
                </button>

            </td>

        </tr>

    <?php endforeach; ?>

<?php else: ?>

    <tr>

        <td
            colspan="5"
            style="
                text-align:center;
                padding:30px;
            "
        >
            <span style="color:#6b7280;">
                No website products added yet.
            </span>
        </td>

    </tr>

<?php endif; ?>

</tbody>

                </table>

            </div>


            <div class="inventory-variable-note">

                <i class="fa-solid fa-circle-info"></i>

                <span>
                    Active Pet Food, Supplements, Others inventory items can be added as website products.
                    Pet Food products must be classified as Dog or Cat.
                </span>

            </div>

        </div>
    </div>    
    <!-- BILLING CONTENT -->

    <div
        class="billing-variable-content"
        id="billingContent"
    >

        <!-- BILLING HEADER -->

        <div class="variable-detail-header">

            <div class="variable-detail-title">

                <div class="detail-main-icon billing">

                    <i class="fa-solid fa-file-invoice-dollar"></i>

                </div>

                <div>

                    <h2>
                        Billing
                    </h2>

                    <p>
                        Manage services, pricing rules,
                        test kits and units.
                    </p>

                </div>

            </div>


            <div class="detail-header-actions">

                <button
                    type="button"
                    class="group-action edit"
                    id="editBillingGroup"
                >

                    <i class="fa-solid fa-pen"></i>

                    Edit Group

                </button>


                <button
                    type="button"
                    class="group-action deactivate"
                    id="deactivateBillingGroup"
                >

                    <i class="fa-regular fa-trash-can"></i>

                    Deactivate Group

                </button>

            </div>

        </div>


        <!-- BILLING TABS -->
<div class="variable-tabs">

    <button
        type="button"
        class="variable-tab active"
        data-tab="service-categories"
    >
        Service Categories
    </button>

    <button
        type="button"
        class="variable-tab"
        data-tab="services"
    >
        Services
    </button>

    <button
        type="button"
        class="variable-tab"
        data-tab="test-kits"
    >
        Test Kits
    </button>

    <button
        type="button"
        class="variable-tab"
        data-tab="medications"
    >
        Medications
    </button>

    <button
        type="button"
        class="variable-tab"
        data-tab="pricing-rules"
    >
        Pricing Rules
    </button>

</div>


        <!-- ==================================
             SERVICE CATEGORIES
        =================================== -->

        <div
            class="billing-tab-content active"
            id="service-categories"
        >

            <div class="billing-section-header">

                <div>

                    <h3>
                        Service Categories
                    </h3>

                    <p>
                        Categories used when creating
                        appointments and billing records.
                    </p>

                </div>


                <button
                    type="button"
                    class="add-variable-btn"
                >

                    <i class="fa-solid fa-plus"></i>

                    Add Category

                </button>

            </div>


            <div class="variable-table-wrapper">

                <table class="variable-table">

                    <thead>

                        <tr>

                            <th>
                                Category
                            </th>

                            <th>
                                Description
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Actions
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        <tr>
                            <td><strong>Particulars</strong></td>
                            <td>General veterinary services.</td>
                            <td><span class="status active">Active</span></td>
                            <td class="table-actions">
                                <button class="icon-action edit"><i class="fa-solid fa-pen"></i></button>
                                <button class="icon-action delete"><i class="fa-regular fa-trash-can"></i></button>
                            </td>
                        </tr>

                        <tr>
                            <td><strong>Vaccination</strong></td>
                            <td>Vaccination services and vaccines.</td>
                            <td><span class="status active">Active</span></td>
                            <td class="table-actions">
                                <button class="icon-action edit"><i class="fa-solid fa-pen"></i></button>
                                <button class="icon-action delete"><i class="fa-regular fa-trash-can"></i></button>
                            </td>
                        </tr>

                        <tr>
                            <td><strong>Deworming</strong></td>
                            <td>Deworming services based on pet weight.</td>
                            <td><span class="status active">Active</span></td>
                            <td class="table-actions">
                                <button class="icon-action edit"><i class="fa-solid fa-pen"></i></button>
                                <button class="icon-action delete"><i class="fa-regular fa-trash-can"></i></button>
                            </td>
                        </tr>

                        <tr>
                            <td><strong>Laboratory</strong></td>
                            <td>Laboratory and diagnostic examinations.</td>
                            <td><span class="status active">Active</span></td>
                            <td class="table-actions">
                                <button class="icon-action edit"><i class="fa-solid fa-pen"></i></button>
                                <button class="icon-action delete"><i class="fa-regular fa-trash-can"></i></button>
                            </td>
                        </tr>

                        <tr>
                            <td><strong>Specialties</strong></td>
                            <td>Specialized veterinary procedures and treatments.</td>
                            <td><span class="status active">Active</span></td>
                            <td class="table-actions">
                                <button class="icon-action edit"><i class="fa-solid fa-pen"></i></button>
                                <button class="icon-action delete"><i class="fa-regular fa-trash-can"></i></button>
                            </td>
                        </tr>

                    </tbody>

                </table>

            </div>

        </div>


        <!-- ==================================
             SERVICES
        =================================== -->

        <div
            class="billing-tab-content"
            id="services"
        >

            <div class="billing-section-header">

                <div>

                    <h3>
                        Services
                    </h3>

                    <p>
                        Manage billable veterinary services
                        and their pricing rules.
                    </p>

                </div>


                <button
                    type="button"
                    class="add-variable-btn"
                >

                    <i class="fa-solid fa-plus"></i>

                    Add Service

                </button>

            </div>


            <div class="variable-table-wrapper">

                <table class="variable-table">

                    <thead>

                        <tr>

                            <th>Category</th>

                            <th>Service</th>

                            <th>Pricing</th>

                            <th>Price</th>

                            <th>Status</th>

                            <th>Actions</th>

                        </tr>

                    </thead>


                    <tbody>

<?php

$servicesQuery = "
    SELECT
        s.service_id,
        sc.category_name,
        s.service_name,
        s.pricing_type,
        s.fixed_price,
        s.status,

        (
            SELECT pr.base_price
            FROM pricing_rules pr
            WHERE pr.service_id = s.service_id
              AND pr.status = 'Active'
            ORDER BY pr.pricing_rule_id ASC
            LIMIT 1
        ) AS rule_base_price

    FROM services s

    INNER JOIN service_categories sc
        ON s.category_id = sc.category_id

    ORDER BY
        sc.category_name ASC,
        s.service_name ASC
";

$servicesResult = mysqli_query(
    $conn,
    $servicesQuery
);

if (
    $servicesResult &&
    mysqli_num_rows($servicesResult) > 0
) {

    while (
        $service =
        mysqli_fetch_assoc($servicesResult)
    ) {

        $serviceId =
            (int)$service['service_id'];

        $category =
            htmlspecialchars(
                $service['category_name']
            );

        $serviceName =
            htmlspecialchars(
                $service['service_name']
            );

        $pricingType =
            htmlspecialchars(
                $service['pricing_type']
            );

        $status =
            htmlspecialchars(
                $service['status']
            );


        /* =========================
           DISPLAY PRICE
        ========================= */

        $displayPrice = "—";


        if (
            $service['pricing_type'] === 'Fixed'
            &&
            $service['fixed_price'] !== null
        ) {

            $displayPrice =
                "₱" .
                number_format(
                    (float)$service['fixed_price'],
                    2
                );

        }

        elseif (
            $service['pricing_type'] === 'Weight-Based'
            &&
            $service['rule_base_price'] !== null
        ) {

            $displayPrice =
                "₱" .
                number_format(
                    (float)$service['rule_base_price'],
                    2
                ) .
                "+";

        }

?>

<tr
    data-service-id="<?= $serviceId ?>"
>

    <td>
        <?= $category ?>
    </td>


    <td>
        <strong>
            <?= $serviceName ?>
        </strong>
    </td>


    <td>
        <?= $pricingType ?>
    </td>


    <td>
        <?= $displayPrice ?>
    </td>


    <td>

        <span
            class="status <?= $status === 'Active' ? 'active' : '' ?>"
        >
            <?= $status ?>
        </span>

    </td>


    <td class="table-actions">

        <button
            type="button"
            class="icon-action edit"
            title="Edit Service"
            data-id="<?= $serviceId ?>"
        >
            <i class="fa-solid fa-pen"></i>
        </button>


        <button
            type="button"
            class="icon-action delete"
            title="Delete Service"
            data-id="<?= $serviceId ?>"
        >
            <i class="fa-regular fa-trash-can"></i>
        </button>

    </td>

</tr>

<?php

    }

} else {

?>

<tr>

    <td
        colspan="6"
        style="text-align: center;"
    >
        No services found.
    </td>

</tr>

<?php

}

?>

</tbody>

                </table>

            </div>

        </div>


        <!-- ==================================
             TEST KITS
        =================================== -->

        <div
            class="billing-tab-content"
            id="test-kits"
        >

            <div class="billing-section-header">

                <div>

                    <h3>
                        Test Kits
                    </h3>

                    <p>
                        Test kits available under
                        Laboratory.
                    </p>

                </div>


                <button
                    type="button"
                    class="add-variable-btn"
                >

                    <i class="fa-solid fa-plus"></i>

                    Add Test Kit

                </button>

            </div>


            <div class="variable-table-wrapper">

                <table class="variable-table">

                    <thead>

                        <tr>

                            <th>
                                Test Kit
                            </th>

                            <th>
                                Category
                            </th>

                            <th>
                                Unit Price
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Actions
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                        <tr>

                            <td>
                                <strong>
                                    Canine Parvo Test Kit
                                </strong>
                            </td>

                            <td>
                                Laboratory
                            </td>

                            <td>
                                ₱1,500.00
                            </td>

                            <td>
                                <span class="status active">
                                    Active
                                </span>
                            </td>

                            <td class="table-actions">

                                <button class="icon-action edit">
                                    <i class="fa-solid fa-pen"></i>
                                </button>

                                <button class="icon-action delete">
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>

                            </td>

                        </tr>


                        <tr>

                            <td>
                                <strong>
                                    Canine Distemper Test Kit
                                </strong>
                            </td>

                            <td>
                                Laboratory
                            </td>

                            <td>
                                ₱950.00
                            </td>

                            <td>
                                <span class="status active">
                                    Active
                                </span>
                            </td>

                            <td class="table-actions">

                                <button class="icon-action edit">
                                    <i class="fa-solid fa-pen"></i>
                                </button>

                                <button class="icon-action delete">
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>

                            </td>

                        </tr>


                        <tr>

                            <td>
                                <strong>
                                    FIV/FeLV Test Kit
                                </strong>
                            </td>

                            <td>
                                Laboratory
                            </td>

                            <td>
                                ₱1,100.00
                            </td>

                            <td>
                                <span class="status active">
                                    Active
                                </span>
                            </td>

                            <td class="table-actions">

                                <button class="icon-action edit">
                                    <i class="fa-solid fa-pen"></i>
                                </button>

                                <button class="icon-action delete">
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>

                            </td>

                        </tr>


                    </tbody>

                </table>

            </div>


            <div class="billing-note">

                <i class="fa-solid fa-circle-info"></i>

                <span>
                    Test kits are displayed only after
                    <strong>Test Kit</strong> is selected
                    under Laboratory.
                </span>

            </div>

        </div>
    <!-- ==================================
     MEDICATIONS
=================================== -->

<div
    class="billing-tab-content"
    id="medications"
>

    <div class="billing-section-header">

        <div>
            <h3>
                Medications
            </h3>

            <p>
                Manage medications and their unit prices
                available for billing.
            </p>
        </div>

        <button
            type="button"
            class="add-variable-btn"
            id="addMedicationBtn"
        >
            <i class="fa-solid fa-plus"></i>
            Add Medication
        </button>

    </div>


    <div class="variable-table-wrapper">

        <table class="variable-table">

            <thead>
                <tr>

                    <th>
                        Medication
                    </th>

                    <th>
                        Unit Price
                    </th>

                    <th>
                        Status
                    </th>

                    <th>
                        Actions
                    </th>

                </tr>
            </thead>


            <tbody>

<?php

$medicationsQuery = "
    SELECT
        medication_id,
        medication_name,
        unit_price,
        status
    FROM medications
    ORDER BY medication_name ASC
";

$medicationsResult = mysqli_query(
    $conn,
    $medicationsQuery
);

if (
    $medicationsResult &&
    mysqli_num_rows($medicationsResult) > 0
) {

    while (
        $medication =
        mysqli_fetch_assoc($medicationsResult)
    ) {

        $medicationId =
            (int)$medication['medication_id'];

        $medicationName =
            htmlspecialchars(
                $medication['medication_name']
            );

        $unitPrice =
            number_format(
                (float)$medication['unit_price'],
                2
            );

        $status =
            htmlspecialchars(
                $medication['status']
            );

?>

<tr
    data-medication-id="<?= $medicationId ?>"
>

    <td>
        <strong>
            <?= $medicationName ?>
        </strong>
    </td>

    <td>
        ₱<?= $unitPrice ?>
    </td>

    <td>
        <span
            class="status <?= $status === 'Active' ? 'active' : '' ?>"
        >
            <?= $status ?>
        </span>
    </td>

    <td class="table-actions">

        <button
            type="button"
            class="icon-action edit"
            title="Edit Medication"
            data-id="<?= $medicationId ?>"
        >
            <i class="fa-solid fa-pen"></i>
        </button>

        <button
            type="button"
            class="icon-action delete"
            title="Delete Medication"
            data-id="<?= $medicationId ?>"
        >
            <i class="fa-regular fa-trash-can"></i>
        </button>

    </td>

</tr>

<?php

    }

} else {

?>

<tr>

    <td
        colspan="4"
        style="text-align: center; padding: 30px;"
    >
        <span style="color: #6b7280;">
            No medications added yet.
        </span>
    </td>

</tr>

<?php

}

?>

            </tbody>

        </table>

    </div>


    <div class="billing-note">

        <i class="fa-solid fa-circle-info"></i>

        <span>
            Medication prices are displayed here and can be
            selected during billing.
        </span>

    </div>

</div>

        <!-- ==================================
     PRICING RULES
     =================================== -->

<div
    class="billing-tab-content"
    id="pricing-rules"
>

    <div class="billing-section-header">

        <div>
            <h3>Pricing Rules</h3>

            <p>
                Configure how prices are calculated
                for billable services.
            </p>
        </div>

        <button
            type="button"
            class="add-variable-btn"
            id="addPricingRuleBtn"
        >
            <i class="fa-solid fa-plus"></i>
            Add Pricing Rule
        </button>

    </div>


    <!-- PRICING TYPE CARDS -->

    <div class="pricing-rule-grid">

        <div class="pricing-rule-card">

            <div class="pricing-rule-icon fixed">
                <i class="fa-solid fa-tag"></i>
            </div>

            <div>
                <h4>Fixed</h4>

                <p>
                    Uses one fixed price regardless
                    of pet weight.
                </p>
            </div>

        </div>


        <div class="pricing-rule-card">

            <div class="pricing-rule-icon weight">
                <i class="fa-solid fa-weight-scale"></i>
            </div>

            <div>
                <h4>Weight-Based</h4>

                <p>
                    Price changes according to
                    the pet's weight range.
                </p>
            </div>

        </div>


        <div class="pricing-rule-card">

            <div class="pricing-rule-icon manual">
                <i class="fa-solid fa-pen-to-square"></i>
            </div>

            <div>
                <h4>Manual / Variable</h4>

                <p>
                    Staff enters the applicable
                    amount during billing.
                </p>
            </div>

        </div>

    </div>


    <!-- DATABASE PRICING RULES -->

    <div class="weight-pricing-section">

        <div class="pricing-section-title">

            <div>
                <h4>Current Weight-Based Rules</h4>

                <p>
                    Prices are calculated automatically
                    using the configured weight increments.
                </p>
            </div>

        </div>


        <div class="variable-table-wrapper">

            <table class="variable-table">

                <thead>

                    <tr>

                        <th>Service</th>

                        <th>Base Weight</th>

                        <th>Base Price</th>

                        <th>Every</th>

                        <th>Price Increase</th>

                        <th>Status</th>

                        <th>Actions</th>

                    </tr>

                </thead>


                <tbody id="pricingRulesTableBody">

                    <?php

                    $pricingRulesQuery = "
                        SELECT
                            pr.pricing_rule_id,
                            pr.service_id,
                            pr.base_min_weight,
                            pr.base_max_weight,
                            pr.base_price,
                            pr.weight_increment,
                            pr.price_increment,
                            pr.status,
                            s.service_name

                        FROM pricing_rules pr

                        INNER JOIN services s
                            ON pr.service_id = s.service_id

                        WHERE pr.status = 'Active'

                        ORDER BY s.service_name ASC
                    ";

                    $pricingRulesResult =
                        mysqli_query(
                            $conn,
                            $pricingRulesQuery
                        );


                    if (
                        $pricingRulesResult &&
                        mysqli_num_rows(
                            $pricingRulesResult
                        ) > 0
                    ) {

                        while (
                            $rule =
                            mysqli_fetch_assoc(
                                $pricingRulesResult
                            )
                        ) {

                            $baseMin =
                                number_format(
                                    $rule['base_min_weight'],
                                    0
                                );

                            $baseMax =
                                number_format(
                                    $rule['base_max_weight'],
                                    0
                                );

                            $basePrice =
                                number_format(
                                    $rule['base_price'],
                                    2
                                );

                            $weightIncrement =
                                number_format(
                                    $rule['weight_increment'],
                                    0
                                );

                            $priceIncrement =
                                number_format(
                                    $rule['price_increment'],
                                    2
                                );

                    ?>

                    <tr
                        data-rule-id="<?=
                            (int)$rule[
                                'pricing_rule_id'
                            ]
                        ?>"
                    >

                        <td>
                            <strong>
                                <?= htmlspecialchars(
                                    $rule[
                                        'service_name'
                                    ]
                                ) ?>
                            </strong>
                        </td>


                        <td>
                            <?= $baseMin ?>
                            – <?= $baseMax ?> kg
                        </td>


                        <td>
                            ₱<?= $basePrice ?>
                        </td>


                        <td>
                            +<?= $weightIncrement ?> kg
                        </td>


                        <td>
                            +₱<?= $priceIncrement ?>
                        </td>


                        <td>

                            <span class="status active">
                                <?= htmlspecialchars(
                                    $rule['status']
                                ) ?>
                            </span>

                        </td>


                        <td class="table-actions">

                            <button
                                type="button"
                                class="icon-action edit pricing-rule-edit"
                                data-id="<?=
                                    (int)$rule[
                                        'pricing_rule_id'
                                    ]
                                ?>"
                            >
                                <i class="fa-solid fa-pen"></i>
                            </button>


                            <button
                                type="button"
                                class="icon-action delete pricing-rule-delete"
                                data-id="<?=
                                    (int)$rule[
                                        'pricing_rule_id'
                                    ]
                                ?>"
                            >
                                <i class="fa-regular fa-trash-can"></i>
                            </button>

                        </td>

                    </tr>

                    <?php

                        }

                    } else {

                    ?>

                    <tr>

                        <td
                            colspan="7"
                            style="text-align:center;"
                        >
                            No active pricing rules found.
                        </td>

                    </tr>

                    <?php

                    }

                    ?>

                </tbody>

            </table>

        </div>


        <div class="billing-note">

            <i class="fa-solid fa-circle-info"></i>

            <span>
                Weight-based prices are calculated using
                the base price and configured increments.
            </span>

        </div>

    </div>

</div>

    </div>

    <!-- ADD WEBSITE PRODUCT MODAL -->
<div
    class="variable-modal-overlay"
    id="websiteProductModal"
    style="display: none;"
>
    <div class="variable-modal">

        <!-- MODAL HEADER -->
        <div class="variable-modal-header">

            <div>
                <h3>Add Website Product</h3>
                <p>
                    Add a pet food or supplement product to the customer website.
                </p>
            </div>

            <button
                type="button"
                class="variable-modal-close"
                id="closeWebsiteProductModal"
                aria-label="Close"
            >
                <i class="fa-solid fa-xmark"></i>
            </button>

        </div>

        <!-- MODAL BODY -->
        <form
            id="addWebsiteProductForm"
            method="POST"
            enctype="multipart/form-data"
        >

            <input
                type="hidden"
                name="inventory_variable_action"
                value="add_website_product"
            >

            <!-- PRODUCT -->
            <div class="variable-modal-field">

                <label for="websiteProductItem">
                    Product
                    <span>*</span>
                </label>

                <select
                    id="websiteProductItem"
                    name="item_id"
                    required
                >
                    <option value="">
                        Select Product
                    </option>

                    <?php foreach ($websiteProductItems as $item): ?>

                        <option
                            value="<?= (int)$item["item_id"] ?>"
                            data-category="<?= htmlspecialchars($item["category_name"], ENT_QUOTES) ?>"
                        >
                            <?= htmlspecialchars($item["item_name"]) ?>
                            — <?= htmlspecialchars($item["category_name"]) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

                <small>
                    Only active Pet Food and Supplements inventory items are available.
                </small>

            </div>


            <!-- PET TYPE -->
            <div
                class="variable-modal-field"
                id="websiteProductPetTypeField"
            >

                <label for="websiteProductPetType">
                    Pet Type
                    <span>*</span>
                </label>

                <select
                    id="websiteProductPetType"
                    name="pet_type"
                >
                    <option value="">
                        Select Pet Type
                    </option>

                    <option value="Dog">
                        Dog
                    </option>

                    <option value="Cat">
                        Cat
                    </option>
                </select>

                <small>
                    Required for Pet Food products only.
                </small>

            </div>


            <!-- DESCRIPTION -->
            <div class="variable-modal-field">

                <label for="websiteProductDescription">
                    Description
                </label>

                <textarea
                    id="websiteProductDescription"
                    name="description"
                    rows="4"
                    placeholder="Enter product description..."
                ></textarea>

            </div>


            <!-- IMAGE -->
            <div class="variable-modal-field">

                <label for="websiteProductImage">
                    Product Image
                </label>

                <input
                    type="file"
                    id="websiteProductImage"
                    name="image"
                    accept="image/*"
                >

                <small>
                    Upload an image for the product displayed on the customer website.
                </small>

            </div>


            <!-- STATUS -->
            <div class="variable-modal-field">

                <label for="websiteProductStatus">
                    Status
                    <span>*</span>
                </label>

                <select
                    id="websiteProductStatus"
                    name="status"
                    required
                >
                    <option value="Hidden">
                        Hidden
                    </option>

                    <option value="Visible">
                        Visible
                    </option>
                </select>

            </div>


            <!-- MODAL FOOTER -->
            <div class="variable-modal-footer">

                <button
                    type="button"
                    class="variable-modal-cancel"
                    id="cancelWebsiteProductModal"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="variable-modal-submit"
                    id="saveWebsiteProductBtn"
                >
                    <i class="fa-solid fa-check"></i>
                    Add Product
                </button>

            </div>

        </form>

    </div>
</div>


<!-- DEFAULT EMPTY STATE -->
<div
    class="variable-empty-state"
    id="defaultVariableState"
>  

    
        <div class="empty-state-icon">

            <i class="fa-solid fa-layer-group"></i>

        </div>

        <h2>
            Select a variable group
        </h2>

        <p>
            Choose a group from the left panel
            to view and manage its variables.
        </p>

    </div>


</div>

            </div>


        </div>


    </main>

</div>


<!-- LAYOUT JS -->
<script src="../assets/js/layout.js"></script>

<!-- INVENTORY CATEGORIES DATA FOR SYSTEM VARIABLES JS -->
<script>
    window.inventoryCategories = <?= json_encode(
        $inventoryCategoriesList,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    ) ?>;
</script>

<!-- SYSTEM VARIABLES JS -->
<script src="../assets/js/system_variables.js"></script>


</body>

</html>