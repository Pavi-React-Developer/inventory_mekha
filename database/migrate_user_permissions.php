<?php
// database/migrate_user_permissions.php - User-level Permissions Override & Schema Enhancements

require_once __DIR__ . '/../app/core/Autoloader.php';
\App\Core\Env::load(dirname(__DIR__) . '/.env');

use App\Core\DB;

echo "=== Running Migration: User Permissions & Staff Schema Enhancements ===\n\n";

try {
    // 1. Create user_permissions table
    echo "1. Creating 'user_permissions' table...\n";
    DB::query("CREATE TABLE IF NOT EXISTS `user_permissions` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `module_id` INT NOT NULL,
        `can_view` TINYINT(1) NULL DEFAULT NULL COMMENT 'NULL=inherit role, 1=explicit grant, 0=explicit deny',
        `can_add` TINYINT(1) NULL DEFAULT NULL COMMENT 'NULL=inherit role, 1=explicit grant, 0=explicit deny',
        `can_edit` TINYINT(1) NULL DEFAULT NULL COMMENT 'NULL=inherit role, 1=explicit grant, 0=explicit deny',
        `can_delete` TINYINT(1) NULL DEFAULT NULL COMMENT 'NULL=inherit role, 1=explicit grant, 0=explicit deny',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY `uk_user_module` (`user_id`, `module_id`),
        INDEX `idx_up_user` (`user_id`),
        INDEX `idx_up_module` (`module_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "   [OK] 'user_permissions' table ready.\n";

    // 2. Add performance & search indexes to staff and users
    echo "2. Checking indexes on 'staff' and 'users'...\n";
    
    // Check indexes on staff table
    $staffIndexes = array_column(DB::fetchAll("SHOW INDEX FROM `staff`"), 'Key_name');
    if (!in_array('idx_staff_concern_phone', $staffIndexes)) {
        DB::query("ALTER TABLE `staff` ADD INDEX `idx_staff_concern_phone` (`concern_id`, `phone`)");
        echo "   [OK] Added index idx_staff_concern_phone to staff.\n";
    }
    if (!in_array('idx_staff_concern_email', $staffIndexes)) {
        DB::query("ALTER TABLE `staff` ADD INDEX `idx_staff_concern_email` (`concern_id`, `email`)");
        echo "   [OK] Added index idx_staff_concern_email to staff.\n";
    }
    if (!in_array('idx_staff_user', $staffIndexes)) {
        DB::query("ALTER TABLE `staff` ADD INDEX `idx_staff_user` (`user_id`)");
        echo "   [OK] Added index idx_staff_user to staff.\n";
    }

    // Check indexes on users table
    $usersIndexes = array_column(DB::fetchAll("SHOW INDEX FROM `users`"), 'Key_name');
    if (!in_array('idx_users_phone', $usersIndexes)) {
        DB::query("ALTER TABLE `users` ADD INDEX `idx_users_phone` (`phone`)");
        echo "   [OK] Added index idx_users_phone to users.\n";
    }

    // 3. Ensure role_permissions unique key on (role_id, module_id)
    $rpIndexes = array_column(DB::fetchAll("SHOW INDEX FROM `role_permissions`"), 'Key_name');
    if (!in_array('uk_role_module', $rpIndexes)) {
        // Clean any duplicates before adding unique key
        DB::query("DELETE rp1 FROM role_permissions rp1 
                   INNER JOIN role_permissions rp2 
                   WHERE rp1.id > rp2.id AND rp1.role_id = rp2.role_id AND rp1.module_id = rp2.module_id");
        DB::query("ALTER TABLE `role_permissions` ADD UNIQUE KEY `uk_role_module` (`role_id`, `module_id`)");
        echo "   [OK] Added unique constraint uk_role_module to role_permissions.\n";
    }

    echo "\n=== Migration Completed Successfully! ===\n";

} catch (Exception $e) {
    echo "\n[ERROR] Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
