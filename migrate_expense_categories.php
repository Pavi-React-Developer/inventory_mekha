<?php
// migrate_expense_categories.php
require_once __DIR__ . '/app/core/Autoloader.php';
\App\Core\Env::load(__DIR__ . '/.env');
use App\Core\DB;

try {
    DB::query("CREATE TABLE IF NOT EXISTS `expense_categories` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `concern_id` INT NULL,
        `name` VARCHAR(150) NOT NULL,
        `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_exp_cat_concern` (`concern_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Table expense_categories created or already exists.\n";
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
