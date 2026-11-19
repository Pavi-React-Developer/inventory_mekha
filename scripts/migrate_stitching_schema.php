<?php
require_once __DIR__ . '/../app/core/Autoloader.php';
\App\Core\Env::load(dirname(__DIR__) . '/.env');

use App\Core\DB;

echo "--- Running Stitching Schema Migration ---\n";

// 1. stitching_masters (Tailors)
DB::query("
CREATE TABLE IF NOT EXISTS stitching_masters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    concern_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    phone VARCHAR(30) NULL,
    employee_code VARCHAR(50) NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_sm_concern (concern_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
echo "[OK] stitching_masters table created/verified.\n";

// 2. Enhance stitching_logs
$existingCols = array_column(DB::fetchAll("DESCRIBE stitching_logs"), 'Field');

$colsToAdd = [
    'batch_no' => "VARCHAR(50) NULL AFTER log_no",
    'stitching_name' => "VARCHAR(255) NULL AFTER batch_no",
    'status' => "ENUM('in_progress', 'planned', 'completed') DEFAULT 'in_progress' AFTER stitching_name",
    'cutting_id' => "INT NULL AFTER status",
    'tailor_id' => "INT NULL AFTER cutting_id",
    'tailor_name' => "VARCHAR(150) NULL AFTER tailor_id",
    'cut_pieces' => "INT NOT NULL DEFAULT 0 AFTER input_pieces",
    'cut_weight' => "DECIMAL(12,2) DEFAULT 0.00 AFTER stitched_pieces",
    'stitch_weight' => "DECIMAL(12,2) DEFAULT 0.00 AFTER cut_weight",
    'wastage_weight' => "DECIMAL(12,2) DEFAULT 0.00 AFTER wastage_pieces",
    'sold_wastage_qty' => "DECIMAL(12,2) DEFAULT 0.00 AFTER wastage_weight",
    'updated_at' => "TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP AFTER created_at",
];

foreach ($colsToAdd as $col => $def) {
    if (!in_array($col, $existingCols)) {
        DB::query("ALTER TABLE stitching_logs ADD COLUMN $col $def");
        echo "[ADD] Column '$col' added to stitching_logs.\n";
    } else {
        echo "[EXISTS] Column '$col' already in stitching_logs.\n";
    }
}

// Make produced_variant_id NULLable if needed
DB::query("ALTER TABLE stitching_logs MODIFY COLUMN produced_variant_id INT NULL");
echo "[OK] produced_variant_id modified to NULLable.\n";

// 3. stitching_items table
DB::query("
CREATE TABLE IF NOT EXISTS stitching_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stitching_id INT NOT NULL,
    cutting_item_id INT NULL,
    catalog_id INT NULL,
    variant_id INT NULL,
    size VARCHAR(50) NULL,
    color VARCHAR(100) NULL,
    sku_code VARCHAR(100) NULL,
    variant_name VARCHAR(255) NULL,
    cut_qty INT NOT NULL DEFAULT 0,
    stitching_qty INT NOT NULL DEFAULT 0,
    per_pc_weight DECIMAL(8,3) DEFAULT 0.000,
    total_weight DECIMAL(12,2) DEFAULT 0.00,
    base_price DECIMAL(12,2) DEFAULT 0.00,
    discount_price DECIMAL(12,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_si_stitching (stitching_id),
    KEY idx_si_variant (variant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
echo "[OK] stitching_items table created/verified.\n";

// 4. stitching_wastage_sales table
DB::query("
CREATE TABLE IF NOT EXISTS stitching_wastage_sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    concern_id INT NOT NULL,
    sale_no VARCHAR(50) NOT NULL,
    stitching_id INT NOT NULL,
    buyer_name VARCHAR(150) NOT NULL,
    buyer_phone VARCHAR(20) NOT NULL,
    buyer_company VARCHAR(200) NULL,
    wastage_category VARCHAR(100) NOT NULL,
    unit_type VARCHAR(20) DEFAULT 'KG',
    quantity DECIMAL(12,2) NOT NULL,
    selling_rate DECIMAL(12,2) NOT NULL,
    total_price DECIMAL(12,2) NOT NULL,
    amount_received DECIMAL(12,2) DEFAULT 0.00,
    sale_date DATE NOT NULL,
    payment_status ENUM('paid', 'partial', 'pending') DEFAULT 'paid',
    notes TEXT NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_sws_concern (concern_id),
    KEY idx_sws_stitching (stitching_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
echo "[OK] stitching_wastage_sales table created/verified.\n";

echo "--- Stitching Schema Migration Completed Successfully ---\n";
