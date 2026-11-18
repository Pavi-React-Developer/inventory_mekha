<?php
// database/migrate_catalog_mapping_module.php - Migration to create catalog_mapping module & tables

require_once __DIR__ . '/../app/core/Autoloader.php';
\App\Core\Env::load(__DIR__ . '/../.env');

use App\Core\DB;

echo "Migrating Catalog Mapping Module Schema...\n";

// 1. Ensure catalog_id on purchase_bill_items
$pbiCols = array_column(DB::fetchAll("DESCRIBE `purchase_bill_items`"), 'Field');
if (!in_array('catalog_id', $pbiCols)) {
    DB::query("ALTER TABLE `purchase_bill_items` ADD COLUMN `catalog_id` INT NULL AFTER `variant_id`");
    echo "  + Added catalog_id column to purchase_bill_items\n";
}

// 2. Create catalog_mappings table
DB::query("CREATE TABLE IF NOT EXISTS `catalog_mappings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `concern_id` INT NOT NULL,
    `purchase_bill_item_id` INT NOT NULL,
    `catalog_id` INT NOT NULL,
    `variant_id` INT NULL,
    `category_id` INT NULL,
    `subcategory_id` INT NULL,
    `sub_sub_category_id` INT NULL,
    `selected_sizes` TEXT NULL,
    `selected_color` VARCHAR(100) NULL,
    `status` ENUM('mapped', 'unmapped') NOT NULL DEFAULT 'mapped',
    `notes` TEXT NULL,
    `created_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_cmap_concern` (`concern_id`),
    INDEX `idx_cmap_pbi` (`purchase_bill_item_id`),
    INDEX `idx_cmap_catalog` (`catalog_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo "  + Checked/Created catalog_mappings table\n";

// 3. Create product_attributes table
DB::query("CREATE TABLE IF NOT EXISTS `product_attributes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `concern_id` INT NOT NULL,
    `attribute_type` VARCHAR(50) NOT NULL DEFAULT 'size',
    `attribute_name` VARCHAR(100) NOT NULL,
    `attribute_value` VARCHAR(100) NOT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_pattr_concern` (`concern_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo "  + Checked/Created product_attributes table\n";

// 4. Register module in `modules` table
$existMod = DB::fetch("SELECT id FROM `modules` WHERE `code` = 'catalog_mapping'");
if (!$existMod) {
    DB::query(
        "INSERT INTO `modules` (`code`, `name`, `icon`, `sort_order`, `group_name`, `is_super_admin_only`)
         VALUES ('catalog_mapping', 'Catalog Mapping', 'bi-diagram-3-fill', 30.5, 'Production', 0)"
    );
    $modId = (int)DB::lastInsertId();
    echo "  + Registered 'catalog_mapping' module (ID: {$modId}) in Production group\n";
} else {
    $modId = (int)$existMod['id'];
    DB::query("UPDATE `modules` SET `sort_order` = 30.5, `group_name` = 'Production', `icon` = 'bi-diagram-3-fill' WHERE id = ?", [$modId]);
    echo "  + 'catalog_mapping' module already exists (ID: {$modId})\n";
}

// 5. Enable in `concern_modules` for all active concerns
$concerns = DB::fetchAll("SELECT id FROM concerns");
foreach ($concerns as $con) {
    $cId = (int)$con['id'];
    $cmExist = DB::fetch("SELECT id FROM `concern_modules` WHERE `concern_id` = ? AND `module_id` = ?", [$cId, $modId]);
    if (!$cmExist) {
        DB::query("INSERT INTO `concern_modules` (`concern_id`, `module_id`, `enabled`) VALUES (?, ?, 1)", [$cId, $modId]);
    }
}
echo "  + Enabled catalog_mapping in concern_modules for all concerns\n";

// 6. Assign permissions in `role_permissions` for admin roles
$adminRoles = DB::fetchAll("SELECT id FROM roles WHERE id IN (1, 2, 3) OR name LIKE '%Admin%'");
foreach ($adminRoles as $r) {
    $rId = (int)$r['id'];
    $permExist = DB::fetch("SELECT id FROM `role_permissions` WHERE `role_id` = ? AND `module_id` = ?", [$rId, $modId]);
    if (!$permExist) {
        DB::query("INSERT INTO `role_permissions` (`role_id`, `module_id`, `can_view`, `can_add`, `can_edit`, `can_delete`) VALUES (?, ?, 1, 1, 1, 1)", [$rId, $modId]);
    }
}
echo "  + Granted permissions to Admin roles\n";

// 7. Seed standard size attributes if empty
$attrCount = (int)DB::fetchValue("SELECT COUNT(*) FROM product_attributes");
if ($attrCount === 0) {
    $standardSizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL', 'Free Size'];
    foreach ($standardSizes as $order => $size) {
        DB::query(
            "INSERT INTO product_attributes (concern_id, attribute_type, attribute_name, attribute_value, sort_order) VALUES (1, 'size', 'Standard Size', ?, ?)",
            [$size, $order + 1]
        );
    }
    echo "  + Seeded default size attributes\n";
}

echo "Migration Complete!\n";
