<?php
// database/migrate_staff_password.php

require_once __DIR__ . '/../app/core/Autoloader.php';
\App\Core\Env::load(dirname(__DIR__) . '/.env');

use App\Core\DB;

$cols = array_column(DB::fetchAll("DESCRIBE `staff`"), 'Field');

if (!in_array('password_hash', $cols)) {
    DB::query("ALTER TABLE `staff` ADD COLUMN `password_hash` VARCHAR(255) NULL AFTER `email`");
    echo "  + Added password_hash to staff\n";
}

if (!in_array('user_id', $cols)) {
    DB::query("ALTER TABLE `staff` ADD COLUMN `user_id` INT NULL AFTER `concern_id`");
    echo "  + Added user_id to staff\n";
}

echo "Staff table updated successfully.\n";
