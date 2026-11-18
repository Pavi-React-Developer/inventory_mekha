<?php
require_once __DIR__ . '/../app/core/Autoloader.php';
\App\Core\Env::load(dirname(__DIR__) . '/.env');

use App\Core\DB;

$pbCols = array_column(DB::fetchAll('DESCRIBE purchase_bills'), 'Field');
if (!in_array('supplier_invoice_no', $pbCols)) {
    DB::query('ALTER TABLE purchase_bills ADD COLUMN supplier_invoice_no VARCHAR(100) NULL AFTER bill_no');
    echo "Added supplier_invoice_no to purchase_bills\n";
}

$pbiCols = array_column(DB::fetchAll('DESCRIBE purchase_bill_items'), 'Field');
$neededCols = [
    'section_type' => "ENUM('rolls', 'garment_other', 'finished_goods') NOT NULL DEFAULT 'rolls'",
    'item_name' => 'VARCHAR(255) NULL',
    'item_code' => 'VARCHAR(100) NULL',
    'hsn_code' => 'VARCHAR(30) NULL',
    'specification' => 'VARCHAR(255) NULL',
    'color' => 'VARCHAR(100) NULL',
    'pattern' => 'VARCHAR(150) NULL',
    'design_name' => 'VARCHAR(150) NULL',
    'designs_count' => 'INT NOT NULL DEFAULT 1',
    'rq_qty' => 'INT NOT NULL DEFAULT 1',
    'unit' => "VARCHAR(50) NOT NULL DEFAULT 'kg'",
    'gross_weight' => 'DECIMAL(12,2) NULL',
    'net_weight' => 'DECIMAL(12,2) NULL',
    'inward_status' => "ENUM('pending', 'completed') NOT NULL DEFAULT 'pending'",
    'rolls_count' => 'INT NULL',
    'weight_per_roll' => 'DECIMAL(10,3) NULL',
    'fabric_type' => 'VARCHAR(100) NULL',
    'gsm' => 'INT NULL',
    'dia' => 'VARCHAR(50) NULL',
    'photo_path' => 'VARCHAR(255) NULL',
    'category_name' => 'VARCHAR(150) NULL',
    'product_type' => 'VARCHAR(100) NULL',
    'mrp' => 'DECIMAL(12,2) NULL',
    'selling_price' => 'DECIMAL(12,2) NULL'
];
foreach ($neededCols as $col => $def) {
    if (!in_array($col, $pbiCols)) {
        DB::query("ALTER TABLE purchase_bill_items ADD COLUMN {$col} {$def}");
        echo "Added {$col} to purchase_bill_items\n";
    }
}

// Ensure fabric_roll_specs table exists
DB::query("CREATE TABLE IF NOT EXISTS `fabric_roll_specs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `concern_id` INT NOT NULL,
    `color` VARCHAR(100) NOT NULL,
    `design_name` VARCHAR(150) NOT NULL,
    `fabric_type` VARCHAR(100) NULL,
    `gsm` INT NULL,
    `dia` VARCHAR(50) NULL,
    `total_rolls` INT NOT NULL DEFAULT 0,
    `total_weight` DECIMAL(12,3) NOT NULL DEFAULT 0.000,
    `latest_rate` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `latest_bill_no` VARCHAR(50) NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_concern_color_design` (`concern_id`, `color`, `design_name`),
    INDEX `idx_frs_concern` (`concern_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

echo "Fabric roll specs verified.\n";
