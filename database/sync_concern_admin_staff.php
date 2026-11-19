<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/core/Autoloader.php';
\App\Core\Env::load(__DIR__ . '/../.env');

use App\Core\DB;

$admins = DB::fetchAll(
    "SELECT u.id, u.concern_id, u.name, u.email, u.phone, u.password_hash, u.status, c.address
     FROM users u
     LEFT JOIN staff s ON u.id = s.user_id
     LEFT JOIN concerns c ON u.concern_id = c.id
     WHERE s.id IS NULL AND u.concern_id IS NOT NULL"
);

echo "Found " . count($admins) . " unlinked concern admins.\n";

foreach ($admins as $adm) {
    $cid = (int)$adm['concern_id'];
    $uid = (int)$adm['id'];
    $staffCode = 'STF-' . str_pad((string)$cid, 3, '0', STR_PAD_LEFT) . '-001';

    $codeCount = (int)DB::fetchValue('SELECT COUNT(*) FROM staff WHERE concern_id = ? AND staff_code = ?', [$cid, $staffCode]);
    if ($codeCount > 0) {
        $staffCode = 'STF-' . str_pad((string)$cid, 3, '0', STR_PAD_LEFT) . '-' . str_pad((string)($codeCount + 1), 3, '0', STR_PAD_LEFT);
    }

    // Also check if email exists in staff
    $existingStaffWithEmail = DB::fetch("SELECT id FROM staff WHERE email = ?", [$adm['email']]);
    if ($existingStaffWithEmail) {
        DB::query("UPDATE staff SET user_id = ? WHERE id = ?", [$uid, $existingStaffWithEmail['id']]);
        echo "Linked existing staff id {$existingStaffWithEmail['id']} to user {$uid} ({$adm['email']})\n";
        continue;
    }

    DB::query(
        "INSERT INTO staff (concern_id, user_id, staff_code, name, email, password_hash, phone, designation, department, salary_type, base_salary, address, status, joining_date, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, 'Concern Administrator', 'Management', 'monthly', 0.00, ?, ?, CURDATE(), NOW())",
        [$cid, $uid, $staffCode, $adm['name'], $adm['email'], $adm['password_hash'], $adm['phone'], $adm['address'], $adm['status'] ?: 'active']
    );

    // Also ensure user_concerns mapping exists
    DB::query("INSERT IGNORE INTO user_concerns (user_id, concern_id) VALUES (?, ?)", [$uid, $cid]);

    // Also ensure user_permissions are seeded for concern's active modules
    $activeMods = DB::fetchAll("SELECT module_id FROM concern_modules WHERE concern_id = ? AND enabled = 1", [$cid]);
    $stmtUp = DB::pdo()->prepare(
        "INSERT INTO user_permissions (user_id, module_id, can_view, can_add, can_edit, can_delete)
         VALUES (?, ?, 1, 1, 1, 1)
         ON DUPLICATE KEY UPDATE can_view = 1, can_add = 1, can_edit = 1, can_delete = 1"
    );
    foreach ($activeMods as $am) {
        $stmtUp->execute([$uid, (int)$am['module_id']]);
    }

    echo "Created staff {$staffCode} and mapped permissions for user {$uid} ({$adm['email']})\n";
}

echo "Sync complete.\n";
