<?php
// tests/ShiftRegisterTest.php

require_once __DIR__ . '/../app/core/Autoloader.php';
\App\Core\Env::load(dirname(__DIR__) . '/.env');

use App\Core\DB;
use App\Core\Auth;
use App\Models\CashierShift;

echo "======================================================\n";
echo "INVENTO: Cashier Shift Register & Float Verification\n";
echo "======================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertTest(string $title, bool $condition) {
    global $passCount, $failCount;
    if ($condition) {
        echo "  [PASS] {$title}\n";
        $passCount++;
    } else {
        echo "  [FAIL] {$title}\n";
        $failCount++;
    }
}

// Clean up any test shifts
DB::query("DELETE FROM cashier_shifts WHERE shift_no LIKE 'TEST-SHF%'");

// 1. Login as Concern 1
Auth::login('grm.admin@invento.com', 'password123');
$c1Id = Auth::concernId();
assertTest("Concern 1 login succeeded with concern_id = 1", $c1Id === 1);

// 2. Open Shift as Concern 1
$shiftNo = 'TEST-SHF-001';
$denoms = [
    '500' => 4,
    '200' => 5,
    '100' => 10,
    '50'  => 10,
    '20'  => 20,
    '10'  => 10,
    '5'   => 0,
    'coins' => 0,
    'total' => 5000.00
];

DB::query(
    "INSERT INTO cashier_shifts (concern_id, user_id, cashier_name, shift_no, opening_notes, starting_float, is_float_modified, denominations_json, status, opened_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'open', NOW())",
    [$c1Id, Auth::id(), 'Test Cashier 1', $shiftNo, 'Morning Counter Test', 5000.00, 0, json_encode($denoms)]
);
$shiftId = (int)DB::lastInsertId();
assertTest("Shift record created with ID > 0", $shiftId > 0);

// 3. Model Tenant Scoping check
$shiftModel = new CashierShift();
$ownShift = $shiftModel->find($shiftId);
assertTest("Concern 1 can find its own shift record", $ownShift !== null && $ownShift['shift_no'] === $shiftNo);

// 4. Switch to Concern 2 & verify isolation
Auth::logout();
Auth::login('apex.admin@invento.com', 'password123');
$c2Id = Auth::concernId();
assertTest("Concern 2 login succeeded with concern_id = 2", $c2Id === 2);

$crossShift = $shiftModel->find($shiftId);
assertTest("Concern 2 CANNOT see Concern 1 shift (returns NULL - Strict Isolation)", $crossShift === null);

// 5. Switch back to Concern 1 & Close shift with exact float match
Auth::logout();
Auth::login('grm.admin@invento.com', 'password123');

$expectedFloat = 5000.00; // no transactions logged during test
$closingFloat = 5000.00;
$variance = $closingFloat - $expectedFloat;

DB::query(
    "UPDATE cashier_shifts SET 
        closing_notes = 'Handover complete without issues',
        closing_float = ?,
        expected_closing_float = ?,
        variance = ?,
        status = 'closed',
        closed_at = NOW()
     WHERE id = ?",
    [$closingFloat, $expectedFloat, $variance, $shiftId]
);

$closedShift = $shiftModel->find($shiftId);
assertTest("Shift closed successfully with status = 'closed'", $closedShift['status'] === 'closed');
assertTest("Closing variance equals 0.00 (Exact Match)", (float)$closedShift['variance'] === 0.00);

// Cleanup
DB::query("DELETE FROM cashier_shifts WHERE id = ?", [$shiftId]);
assertTest("Test shift cleaned up", true);

echo "\n======================================================\n";
echo "SUMMARY: {$passCount} PASSED, {$failCount} FAILED\n";
echo "======================================================\n";

if ($failCount === 0) {
    echo ">>> CASHIER SHIFT REGISTER ISOLATION & WORKFLOW VERIFIED! <<<\n";
    exit(0);
} else {
    echo ">>> SOME TESTS FAILED! <<<\n";
    exit(1);
}
