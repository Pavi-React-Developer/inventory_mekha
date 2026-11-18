<?php
// database/migrate_program_rolls_schema.php
require_once __DIR__ . '/../app/core/Autoloader.php';
\App\Core\Env::load(dirname(__DIR__) . '/.env');
use App\Core\DB;

echo "Starting Production Program Multi-Roll Migration...\n";

// 1. Update production_programs table
$progCols = array_column(DB::fetchAll('DESCRIBE `production_programs`'), 'Field');

if (!in_array('program_name', $progCols)) {
    DB::query('ALTER TABLE `production_programs` ADD COLUMN program_name VARCHAR(255) NULL AFTER program_no');
    echo "Added program_name to production_programs.\n";
}

if (!in_array('planner_name', $progCols)) {
    DB::query('ALTER TABLE `production_programs` ADD COLUMN planner_name VARCHAR(150) NULL AFTER program_name');
    echo "Added planner_name to production_programs.\n";
}

// Make target_variant_id nullable in production_programs since programs can now target rolls with per-roll styles
DB::query('ALTER TABLE `production_programs` MODIFY COLUMN target_variant_id INT NULL');
DB::query('ALTER TABLE `production_programs` MODIFY COLUMN planned_qty DECIMAL(12,2) NOT NULL DEFAULT 0.00');

// 2. Create program_rolls table
DB::query("CREATE TABLE IF NOT EXISTS `program_rolls` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `concern_id` INT NOT NULL,
    `program_id` INT NOT NULL,
    `purchase_bill_item_id` INT NULL,
    `roll_piece_tag` VARCHAR(100) NULL,
    `fabric_name` VARCHAR(255) NULL,
    `color` VARCHAR(100) NULL,
    `unit` VARCHAR(50) NOT NULL DEFAULT 'kg',
    `total_roll_qty` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `allocated_qty` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `target_pieces` INT NOT NULL DEFAULT 0,
    `category_id` INT NULL,
    `subcategory_id` INT NULL,
    `sub_sub_category_id` INT NULL,
    `catalog_id` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_prgroll_concern` (`concern_id`),
    INDEX `idx_prgroll_program` (`program_id`),
    INDEX `idx_prgroll_rollitem` (`purchase_bill_item_id`),
    CONSTRAINT `fk_prgroll_concern` FOREIGN KEY (`concern_id`) REFERENCES `concerns`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_prgroll_program` FOREIGN KEY (`program_id`) REFERENCES `production_programs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

echo "Created/verified program_rolls table.\n";

// 3. Seed sample purchased rolls in purchase_bills & purchase_bill_items if none exist
$rollItemsCount = (int)DB::fetchValue("SELECT COUNT(*) FROM purchase_bill_items WHERE section_type = 'rolls' AND concern_id = 1");
if ($rollItemsCount === 0) {
    // Ensure sample purchase bill exists
    $bill = DB::fetch("SELECT id FROM purchase_bills WHERE concern_id = 1 LIMIT 1");
    if (!$bill) {
        $supplier = DB::fetch("SELECT id FROM suppliers WHERE concern_id = 1 LIMIT 1");
        $supId = $supplier ? $supplier['id'] : 1;
        DB::query(
            "INSERT INTO purchase_bills (concern_id, bill_no, supplier_invoice_no, supplier_id, bill_date, payment_status, total_amount, created_at)
             VALUES (1, 'PB-20261101-001', 'INV-SUN-8842', ?, CURDATE() - INTERVAL 10 DAY, 'Paid', 125000.00, NOW())",
            [$supId]
        );
        $billId = (int)DB::lastInsertId();
    } else {
        $billId = (int)$bill['id'];
    }

    // Insert 3 realistic purchased rolls
    $sampleRolls = [
        [
            'item_code' => 'FB1126141',
            'item_name' => '100% Combed Cotton Single Jersey 180 GSM',
            'color' => 'Navy Blue',
            'net_weight' => 120.00,
            'qty' => 120.00,
            'unit' => 'kg',
            'unit_price' => 280.00,
            'total_price' => 33600.00,
        ],
        [
            'item_code' => 'FB1126142',
            'item_name' => 'Poly Cotton Rib 2x2 High Elastic Fabric',
            'color' => 'Jet Black',
            'net_weight' => 95.50,
            'qty' => 95.50,
            'unit' => 'kg',
            'unit_price' => 310.00,
            'total_price' => 29605.00,
        ],
        [
            'item_code' => 'FB1126143',
            'item_name' => 'Bio-Washed Knitted Single Jersey Cotton',
            'color' => 'Crimson Red',
            'net_weight' => 150.00,
            'qty' => 150.00,
            'unit' => 'kg',
            'unit_price' => 295.00,
            'total_price' => 44250.00,
        ]
    ];

    foreach ($sampleRolls as $r) {
        DB::query(
            "INSERT INTO purchase_bill_items (
                concern_id, purchase_bill_id, section_type, item_name, item_code, 
                color, net_weight, qty, rq_qty, unit, unit_price, total_price, inward_status
            ) VALUES (
                1, ?, 'rolls', ?, ?, ?, ?, ?, 1, ?, ?, ?, 'completed'
            )",
            [
                $billId, $r['item_name'], $r['item_code'],
                $r['color'], $r['net_weight'], $r['qty'],
                $r['unit'], $r['unit_price'], $r['total_price']
            ]
        );
    }
    echo "Seeded 3 sample purchased fabric rolls with piece tags FB1126141, FB1126142, FB1126143.\n";
}

echo "Production Program Multi-Roll Migration completed successfully!\n";
