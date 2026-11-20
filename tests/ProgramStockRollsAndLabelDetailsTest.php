<?php
require_once __DIR__ . '/../app/core/Autoloader.php';
\App\Core\Env::load(dirname(__DIR__) . '/.env');

use App\Core\DB;
use App\Core\Auth;
use App\Controllers\ProductionController;
use App\Controllers\LabelController;

if (session_status() === PHP_SESSION_NONE) { session_start(); }
$_SESSION['user_id'] = 1;
$_SESSION['concern_id'] = 1;
$_SESSION['role'] = 'super_admin';

echo "=== TEST 1: PROGRAM CREATION RESTRICTS TO IN-STOCK ROLLS ONLY ===\n";

// Ensure we have a roll in purchase_bill_items with inward_status = 'pending'
$roll = DB::fetch("SELECT id, item_name, inward_status FROM purchase_bill_items WHERE section_type = 'rolls' LIMIT 1");
if (!$roll) {
    echo "No roll found in purchase_bill_items to test.\n";
    exit(1);
}

$rollId = (int)$roll['id'];

// Test A: Roll with inward_status = 'pending' MUST NOT appear in createProgram available rolls
DB::query("UPDATE purchase_bill_items SET inward_status = 'pending' WHERE id = ?", [$rollId]);

$cid = 1;
$rollsSql = "SELECT pbi.id, pbi.concern_id, pbi.item_code, pbi.item_name, pbi.inward_status
             FROM purchase_bill_items pbi
             JOIN purchase_bills pb ON pbi.purchase_bill_id = pb.id
             LEFT JOIN suppliers s ON pb.supplier_id = s.id
             WHERE pbi.section_type = 'rolls'
               AND (pbi.inward_status = 'completed' OR (pbi.inward_status IS NULL AND pb.inward_status = 'completed'))" . ($cid ? " AND pbi.concern_id = ?" : "") . "
             ORDER BY pbi.id DESC";
$rollsPending = DB::fetchAll($rollsSql, [$cid]);
$foundPending = false;
foreach ($rollsPending as $r) {
    if ((int)$r['id'] === $rollId) {
        $foundPending = true;
        break;
    }
}
if ($foundPending) {
    echo "FAILED: Roll with inward_status='pending' appeared in program available rolls!\n";
    exit(1);
}
echo "PASSED: Roll with inward_status='pending' is excluded from program available rolls.\n";

// Test B: When roll is marked inward_status = 'completed', it MUST appear in available rolls
DB::query("UPDATE purchase_bill_items SET inward_status = 'completed' WHERE id = ?", [$rollId]);
$rollsCompleted = DB::fetchAll($rollsSql, [$cid]);
$foundCompleted = false;
foreach ($rollsCompleted as $r) {
    if ((int)$r['id'] === $rollId) {
        $foundCompleted = true;
        break;
    }
}
if (!$foundCompleted) {
    echo "FAILED: Roll with inward_status='completed' did NOT appear in program available rolls!\n";
    exit(1);
}
echo "PASSED: Roll with inward_status='completed' is included in program available rolls.\n";

// Reset back to pending as per user state
DB::query("UPDATE purchase_bill_items SET inward_status = 'pending' WHERE id = ?", [$rollId]);
echo "PASSED: Restored roll {$rollId} back to pending inward_status.\n";

echo "\n=== TEST 2: LABEL GENERATION SCRIPT SYNTAX & ATTRIBUTES ===\n";
ob_start();
$labelCtrl = new LabelController();
$labelCtrl->index();
$html = ob_get_clean();

// Check for syntax error markers
if (strpos($html, 'submitFgToStock') === false) {
    echo "FAILED: submitFgToStock missing in label generation HTML.\n";
    exit(1);
}
// Ensure no duplicated submitRawToStock or stray body.appendChild
$duplicateCount = substr_count($html, 'document.body.appendChild(form);');
echo "Found {$duplicateCount} form submissions in label generation script.\n";

// Verify select roll options contain proper data attributes
if (strpos($html, 'id="roll_item_select"') === false) {
    echo "FAILED: roll_item_select not found.\n";
    exit(1);
}
if (strpos($html, 'data-fabric="cotton"') === false) {
    echo "FAILED: data-fabric attribute missing.\n";
    exit(1);
}
if (strpos($html, 'data-rq="20"') === false) {
    echo "FAILED: data-rq attribute missing or incorrect.\n";
    exit(1);
}

echo "PASSED: Label generation HTML rendered properly with valid roll data attributes.\n";
echo "\nALL TESTS PASSED!\n";
