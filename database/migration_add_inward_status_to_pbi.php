<?php
require_once __DIR__ . '/../app/core/Autoloader.php';
App\Core\Env::load(__DIR__ . '/../.env');

use App\Core\DB;

try {
    $cols = DB::fetchAll("SHOW COLUMNS FROM purchase_bill_items LIKE 'inward_status'");
    if (empty($cols)) {
        DB::query("ALTER TABLE purchase_bill_items ADD COLUMN `inward_status` ENUM('pending', 'completed') NOT NULL DEFAULT 'pending' AFTER `qty`");
        echo "Added column inward_status to purchase_bill_items\n";
    } else {
        echo "Column inward_status already exists on purchase_bill_items\n";
    }

    // Sync existing inward_status from purchase_bills
    DB::query("UPDATE purchase_bill_items pbi JOIN purchase_bills pb ON pbi.purchase_bill_id = pb.id SET pbi.inward_status = pb.inward_status");
    echo "Synchronized existing inward_status from purchase_bills\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
