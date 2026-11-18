<?php
// database/migrate_staff_module.php - Migration to create staff table and register staff module

require_once __DIR__ . '/../app/core/Autoloader.php';
\App\Core\Env::load(dirname(__DIR__) . '/.env');

use App\Core\DB;

echo "Starting Staff Module Migration...\n";

// 1. Create staff table
DB::query("CREATE TABLE IF NOT EXISTS `staff` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `concern_id` INT NOT NULL,
    `staff_code` VARCHAR(50) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(30) NULL,
    `email` VARCHAR(120) NULL,
    `designation` VARCHAR(100) NULL,
    `department` VARCHAR(100) NULL,
    `salary_type` ENUM('monthly', 'daily', 'piece_rate', 'hourly') NOT NULL DEFAULT 'monthly',
    `base_salary` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `address` TEXT NULL,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `joining_date` DATE NULL,
    `created_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_staff_concern` (`concern_id`),
    CONSTRAINT `fk_staff_concern` FOREIGN KEY (`concern_id`) REFERENCES `concerns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo "  + Created staff table\n";

// 2. Register in modules table
$mod = DB::fetch("SELECT id FROM modules WHERE code = 'staff'");
if (!$mod) {
    DB::query(
        "INSERT INTO `modules` (`code`, `name`, `group_name`, `icon`, `sort_order`, `is_super_admin_only`)
         VALUES ('staff', 'Staff', 'Staff', 'bi-people-fill', 90, 0)"
    );
    $moduleId = (int)DB::lastInsertId();
    echo "  + Registered 'staff' module (ID: {$moduleId})\n";
} else {
    $moduleId = (int)$mod['id'];
    echo "  + 'staff' module already registered (ID: {$moduleId})\n";
}

// 3. Enable in concern_modules for all active concerns
DB::query(
    "INSERT IGNORE INTO `concern_modules` (`concern_id`, `module_id`, `enabled`, `created_at`)
     SELECT c.id, ?, 1, NOW() FROM concerns c WHERE c.status = 'active'",
    [$moduleId]
);
echo "  + Enabled staff module in concern_modules\n";

// 4. Register doc_series for STAFF code generation
$concerns = DB::fetchAll("SELECT id, code FROM concerns");
foreach ($concerns as $c) {
    $existing = DB::fetch("SELECT doc_type FROM doc_series WHERE concern_id = ? AND doc_type = 'STAFF'", [$c['id']]);
    if (!$existing) {
        $prefix = ($c['code'] ?: 'CON') . '-STF-';
        DB::query(
            "INSERT INTO doc_series (concern_id, doc_type, prefix, next_no) VALUES (?, 'STAFF', ?, 1)",
            [$c['id'], $prefix]
        );
    }
}
echo "  + Registered doc_series for STAFF\n";

// 5. Seed initial staff members if table is empty
$count = (int)DB::fetchValue("SELECT COUNT(*) FROM staff");
if ($count === 0 && !empty($concerns)) {
    // Add sample staff for concern 1
    $c1 = $concerns[0]['id'];
    DB::query(
        "INSERT INTO staff (concern_id, staff_code, name, phone, designation, department, salary_type, base_salary, status, joining_date, created_at)
         VALUES (?, 'STF-001', 'Ramesh Kumar', '9842100011', 'Cutting Master', 'Cutting', 'monthly', 22000.00, 'active', '2024-01-15', NOW())",
        [$c1]
    );
    DB::query(
        "INSERT INTO staff (concern_id, staff_code, name, phone, designation, department, salary_type, base_salary, status, joining_date, created_at)
         VALUES (?, 'STF-002', 'Senthil Nathan', '9842100012', 'Senior Tailor', 'Stitching', 'piece_rate', 18000.00, 'active', '2024-03-01', NOW())",
        [$c1]
    );
    DB::query(
        "INSERT INTO staff (concern_id, staff_code, name, phone, designation, department, salary_type, base_salary, status, joining_date, created_at)
         VALUES (?, 'STF-003', 'Priya Murugan', '9842100013', 'QC Inspector', 'Quality Check', 'monthly', 16500.00, 'active', '2024-05-10', NOW())",
        [$c1]
    );

    if (isset($concerns[1])) {
        $c2 = $concerns[1]['id'];
        DB::query(
            "INSERT INTO staff (concern_id, staff_code, name, phone, designation, department, salary_type, base_salary, status, joining_date, created_at)
             VALUES (?, 'STF-004', 'Karthik Raja', '9842100021', 'Warehouse Supervisor', 'Store & Inventory', 'monthly', 25000.00, 'active', '2023-11-20', NOW())",
            [$c2]
        );
        DB::query(
            "INSERT INTO staff (concern_id, staff_code, name, phone, designation, department, salary_type, base_salary, status, joining_date, created_at)
             VALUES (?, 'STF-005', 'Anitha Devi', '9842100022', 'Accounts Assistant', 'Accounts & Finance', 'monthly', 20000.00, 'active', '2024-02-15', NOW())",
            [$c2]
        );
    }
    echo "  + Seeded sample staff members\n";
}

echo "Staff Module Migration Finished Successfully.\n";
