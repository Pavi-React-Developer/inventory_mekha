<?php
// database/migrate_inter_concern_transfer.php
// Adds columns to outwards and inwards to support inter-concern / production-unit product transfers

require_once __DIR__ . '/../app/core/Autoloader.php';
\App\Core\Env::load(dirname(__DIR__) . '/.env');

use App\Core\DB;

echo "Starting Inter-Concern Transfer Migration...\n";

// 1. Update outwards table
$outCols = array_column(DB::fetchAll("DESCRIBE `outwards`"), 'Field');

if (!in_array('transfer_type', $outCols)) {
    DB::query("ALTER TABLE `outwards` ADD COLUMN `transfer_type` ENUM('customer', 'concern') NOT NULL DEFAULT 'customer' AFTER `customer_id`");
    echo "  + Added transfer_type to outwards\n";
}

if (!in_array('destination_concern_id', $outCols)) {
    DB::query("ALTER TABLE `outwards` ADD COLUMN `destination_concern_id` INT NULL AFTER `transfer_type`");
    echo "  + Added destination_concern_id to outwards\n";
}

DB::query("ALTER TABLE `outwards` MODIFY COLUMN `customer_id` INT NULL");
DB::query("ALTER TABLE `outwards` MODIFY COLUMN `status` VARCHAR(50) NOT NULL DEFAULT 'prepared'");
echo "  + Made customer_id nullable and flexible status on outwards\n";

// 2. Update inwards table
$inCols = array_column(DB::fetchAll("DESCRIBE `inwards`"), 'Field');

if (!in_array('outward_id', $inCols)) {
    DB::query("ALTER TABLE `inwards` ADD COLUMN `outward_id` INT NULL AFTER `purchase_bill_id`");
    echo "  + Added outward_id to inwards\n";
}

if (!in_array('from_concern_id', $inCols)) {
    DB::query("ALTER TABLE `inwards` ADD COLUMN `from_concern_id` INT NULL AFTER `outward_id`");
    echo "  + Added from_concern_id to inwards\n";
}

if (!in_array('inward_type', $inCols)) {
    DB::query("ALTER TABLE `inwards` ADD COLUMN `inward_type` ENUM('purchase', 'transfer') NOT NULL DEFAULT 'purchase' AFTER `from_concern_id`");
    echo "  + Added inward_type to inwards\n";
}

echo "Inter-Concern Transfer Migration Completed.\n";
