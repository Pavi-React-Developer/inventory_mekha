<?php
// database/migrate_all_tables.php
require_once __DIR__ . '/../app/core/Autoloader.php';
\App\Core\Env::load(dirname(__DIR__) . '/.env');

use App\Core\DB;

echo "========================================================\n";
echo "MIGRATING LOCAL DATABASE SCHEMA: All Missing Columns\n";
echo "========================================================\n\n";

// 1. PROGRAM_ROLLS
echo "1. Checking `program_rolls`...\n";
$cols = array_column(DB::fetchAll("SHOW COLUMNS FROM `program_rolls`"), 'Field');
if (!in_array('roll_weight', $cols)) {
    DB::query("ALTER TABLE `program_rolls` ADD COLUMN `roll_weight` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `allocated_qty`");
    echo "  + Added `roll_weight` to `program_rolls`\n";
} else {
    echo "  . `roll_weight` already exists\n";
}

if (!in_array('roll_price', $cols)) {
    DB::query("ALTER TABLE `program_rolls` ADD COLUMN `roll_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `roll_weight`");
    echo "  + Added `roll_price` to `program_rolls`\n";
} else {
    echo "  . `roll_price` already exists\n";
}

// 2. OUTWARDS
echo "\n2. Checking `outwards`...\n";
$cols = array_column(DB::fetchAll("SHOW COLUMNS FROM `outwards`"), 'Field');
if (!in_array('transfer_type', $cols)) {
    DB::query("ALTER TABLE `outwards` ADD COLUMN `transfer_type` ENUM('customer', 'concern') NOT NULL DEFAULT 'customer' AFTER `customer_id`");
    echo "  + Added `transfer_type` to `outwards`\n";
} else {
    echo "  . `transfer_type` already exists\n";
}

if (!in_array('destination_concern_id', $cols)) {
    DB::query("ALTER TABLE `outwards` ADD COLUMN `destination_concern_id` INT NULL AFTER `transfer_type`");
    echo "  + Added `destination_concern_id` to `outwards`\n";
} else {
    echo "  . `destination_concern_id` already exists\n";
}

DB::query("ALTER TABLE `outwards` MODIFY COLUMN `customer_id` INT NULL");
DB::query("ALTER TABLE `outwards` MODIFY COLUMN `status` VARCHAR(50) NOT NULL DEFAULT 'prepared'");
echo "  + Ensured `customer_id` is nullable and flexible `status` on `outwards`\n";

// 3. INWARDS
echo "\n3. Checking `inwards`...\n";
$cols = array_column(DB::fetchAll("SHOW COLUMNS FROM `inwards`"), 'Field');
if (!in_array('outward_id', $cols)) {
    DB::query("ALTER TABLE `inwards` ADD COLUMN `outward_id` INT NULL AFTER `purchase_bill_id`");
    echo "  + Added `outward_id` to `inwards`\n";
} else {
    echo "  . `outward_id` already exists\n";
}

if (!in_array('from_concern_id', $cols)) {
    DB::query("ALTER TABLE `inwards` ADD COLUMN `from_concern_id` INT NULL AFTER `outward_id`");
    echo "  + Added `from_concern_id` to `inwards`\n";
} else {
    echo "  . `from_concern_id` already exists\n";
}

if (!in_array('inward_type', $cols)) {
    DB::query("ALTER TABLE `inwards` ADD COLUMN `inward_type` ENUM('purchase', 'transfer') NOT NULL DEFAULT 'purchase' AFTER `from_concern_id`");
    echo "  + Added `inward_type` to `inwards`\n";
} else {
    echo "  . `inward_type` already exists\n";
}

// 4. CASHIER_SHIFTS
echo "\n4. Checking `cashier_shifts`...\n";
$cols = array_column(DB::fetchAll("SHOW COLUMNS FROM `cashier_shifts`"), 'Field');
if (!in_array('variance_reason', $cols)) {
    DB::query("ALTER TABLE `cashier_shifts` ADD COLUMN `variance_reason` VARCHAR(255) NULL AFTER `variance`");
    echo "  + Added `variance_reason` to `cashier_shifts`\n";
} else {
    echo "  . `variance_reason` already exists\n";
}

// 5. PURCHASE_BILL_ITEMS
echo "\n5. Checking `purchase_bill_items`...\n";
$cols = array_column(DB::fetchAll("SHOW COLUMNS FROM `purchase_bill_items`"), 'Field');
if (!in_array('inward_status', $cols)) {
    DB::query("ALTER TABLE `purchase_bill_items` ADD COLUMN `inward_status` VARCHAR(30) NOT NULL DEFAULT 'pending'");
    echo "  + Added `inward_status` to `purchase_bill_items`\n";
} else {
    echo "  . `inward_status` already exists\n";
}

if (!in_array('inward_at', $cols)) {
    DB::query("ALTER TABLE `purchase_bill_items` ADD COLUMN `inward_at` DATETIME NULL");
    echo "  + Added `inward_at` to `purchase_bill_items`\n";
} else {
    echo "  . `inward_at` already exists\n";
}

// 6. STITCHING_ITEMS
echo "\n6. Checking `stitching_items`...\n";
$cols = array_column(DB::fetchAll("SHOW COLUMNS FROM `stitching_items`"), 'Field');
if (!in_array('inward_status', $cols)) {
    DB::query("ALTER TABLE `stitching_items` ADD COLUMN `inward_status` VARCHAR(30) DEFAULT 'pending'");
    echo "  + Added `inward_status` to `stitching_items`\n";
} else {
    echo "  . `inward_status` already exists\n";
}

if (!in_array('inward_at', $cols)) {
    DB::query("ALTER TABLE `stitching_items` ADD COLUMN `inward_at` DATETIME NULL");
    echo "  + Added `inward_at` to `stitching_items`\n";
} else {
    echo "  . `inward_at` already exists\n";
}

// 7. STITCHING_LOGS
echo "\n7. Checking `stitching_logs`...\n";
$cols = array_column(DB::fetchAll("SHOW COLUMNS FROM `stitching_logs`"), 'Field');
if (!in_array('inward_status', $cols)) {
    DB::query("ALTER TABLE `stitching_logs` ADD COLUMN `inward_status` VARCHAR(30) DEFAULT 'pending'");
    echo "  + Added `inward_status` to `stitching_logs`\n";
} else {
    echo "  . `inward_status` already exists\n";
}

echo "\n========================================================\n";
echo "SUCCESS: All tables in local DB migrated successfully!\n";
echo "========================================================\n";
