<?php
// database/migrate_user_concerns.php - Create and populate user_concerns mapping table

require_once __DIR__ . '/../app/core/Autoloader.php';
\App\Core\Env::load(__DIR__ . '/../.env');

use App\Core\DB;

echo "=== MIGRATING USER CONCERNS ===\n";

DB::query("
    CREATE TABLE IF NOT EXISTS user_concerns (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        concern_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_user_concern (user_id, concern_id),
        INDEX idx_user (user_id),
        INDEX idx_concern (concern_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
echo "1. Table user_concerns verified.\n";

// Populate from staff table
DB::query("
    INSERT IGNORE INTO user_concerns (user_id, concern_id)
    SELECT DISTINCT s.user_id, s.concern_id
    FROM staff s
    WHERE s.user_id IS NOT NULL AND s.status = 'active'
");
echo "2. Populated from active staff members.\n";

// Populate from users table
DB::query("
    INSERT IGNORE INTO user_concerns (user_id, concern_id)
    SELECT id, concern_id
    FROM users
    WHERE concern_id IS NOT NULL AND status = 'active'
");
echo "3. Populated from active users table.\n";

// Display current assignments
$records = DB::fetchAll("
    SELECT uc.id, uc.user_id, u.name AS user_name, u.email, uc.concern_id, c.name AS concern_name, c.code AS concern_code
    FROM user_concerns uc
    JOIN users u ON uc.user_id = u.id
    JOIN concerns c ON uc.concern_id = c.id
    ORDER BY u.id, c.name
");

echo "\nCURRENT USER CONCERN ASSIGNMENTS:\n";
foreach ($records as $r) {
    echo "  User #{$r['user_id']} ({$r['user_name']} - {$r['email']}) -> Concern #{$r['concern_id']} [{$r['concern_code']}] {$r['concern_name']}\n";
}

echo "=== MIGRATION COMPLETE ===\n";
