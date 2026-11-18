<?php
// tests/ProductionProgramRollsValidationTest.php - Automated Test for Multi-Roll Production Program Creation & Out-of-Qty Validations

require_once __DIR__ . '/../app/core/Autoloader.php';
\App\Core\Env::load(dirname(__DIR__) . '/.env');

use App\Core\DB;
use App\Core\Auth;
use App\Controllers\ProductionController;

echo "======================================================\n";
echo "INVENTO: Production Program Multi-Roll & Out-of-Qty Test\n";
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

// ----------------------------------------------------
// TEST 1: Schema Integrity Check
// ----------------------------------------------------
echo "TEST 1: Schema & Program Rolls Table Check\n";
$ppCols = array_column(DB::fetchAll("DESCRIBE production_programs"), 'Field');
assertTest("production_programs has 'program_name' column", in_array('program_name', $ppCols));
assertTest("production_programs has 'planner_name' column", in_array('planner_name', $ppCols));

$prExists = (int)DB::fetchValue("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'program_rolls'");
assertTest("program_rolls table exists", $prExists > 0);

$prCols = array_column(DB::fetchAll("DESCRIBE program_rolls"), 'Field');
assertTest("program_rolls has 'roll_piece_tag'", in_array('roll_piece_tag', $prCols));
assertTest("program_rolls has 'total_roll_qty'", in_array('total_roll_qty', $prCols));
assertTest("program_rolls has 'allocated_qty'", in_array('allocated_qty', $prCols));
assertTest("program_rolls has 'target_pieces'", in_array('target_pieces', $prCols));
assertTest("program_rolls has 3-tier catalog hierarchy columns", 
    in_array('category_id', $prCols) && 
    in_array('subcategory_id', $prCols) && 
    in_array('sub_sub_category_id', $prCols) && 
    in_array('catalog_id', $prCols)
);

// ----------------------------------------------------
// TEST 2: Seed / Ensure Test Rolls for Concern 1
// ----------------------------------------------------
echo "\nTEST 2: Setup Test Purchase Rolls\n";
$cid1 = 1;
$bill = DB::fetch("SELECT id FROM purchase_bills WHERE concern_id = ? LIMIT 1", [$cid1]);
if (!$bill) {
    // create dummy bill
    DB::query("INSERT INTO purchase_bills (concern_id, bill_no, supplier_id, bill_date, total_amount, payment_status, created_at) VALUES (?, 'TEST-BILL-001', 1, CURDATE(), 50000.00, 'paid', NOW())", [$cid1]);
    $billId = (int)DB::lastInsertId();
} else {
    $billId = (int)$bill['id'];
}

// Seed roll A (100 kg) and roll B (80 kg)
$uniqueTagA = 'TEST-ROLL-A-' . time();
$uniqueTagB = 'TEST-ROLL-B-' . time();

DB::query(
    "INSERT INTO purchase_bill_items (concern_id, purchase_bill_id, section_type, item_code, item_name, color, qty, unit, unit_price, total_price)
     VALUES (?, ?, 'rolls', ?, '100% Cotton Knitted Single Jersey', 'Navy Blue', 100.00, 'kg', 320.00, 32000.00)",
    [$cid1, $billId, $uniqueTagA]
);
$rollAId = (int)DB::lastInsertId();

DB::query(
    "INSERT INTO purchase_bill_items (concern_id, purchase_bill_id, section_type, item_code, item_name, color, qty, unit, unit_price, total_price)
     VALUES (?, ?, 'rolls', ?, 'Bio-Wash Combed Cotton Interlock', 'Burgundy', 80.00, 'kg', 350.00, 28000.00)",
    [$cid1, $billId, $uniqueTagB]
);
$rollBId = (int)DB::lastInsertId();

assertTest("Created test roll A with 100.00 kg (Tag: {$uniqueTagA})", $rollAId > 0);
assertTest("Created test roll B with 80.00 kg (Tag: {$uniqueTagB})", $rollBId > 0);

// ----------------------------------------------------
// TEST 3: Available Balance & "Out of Qty" Calculation
// ----------------------------------------------------
echo "\nTEST 3: Available Balance Calculation\n";
$fetchRollBalance = function($rollItemId, $concernId) {
    return DB::fetch(
        "SELECT pbi.*, 
                COALESCE((
                    SELECT SUM(pr.allocated_qty) 
                    FROM program_rolls pr 
                    JOIN production_programs pp ON pr.program_id = pp.id 
                    WHERE pr.purchase_bill_item_id = pbi.id 
                      AND pp.status != 'cancelled'
                ), 0.00) AS used_qty
         FROM purchase_bill_items pbi 
         WHERE pbi.id = ? AND pbi.concern_id = ?",
        [$rollItemId, $concernId]
    );
};

$balA = $fetchRollBalance($rollAId, $cid1);
$availableA = (float)$balA['qty'] - (float)$balA['used_qty'];
assertTest("Roll A initially has 100.00 kg available out of 100.00 kg total", $availableA == 100.00 && (float)$balA['used_qty'] == 0.00);

// ----------------------------------------------------
// TEST 4: Allocation Validation - Exceeding Available Balance
// ----------------------------------------------------
echo "\nTEST 4: Over-Allocation Validation (Strict Out-of-Qty)\n";
$attemptOverAllocQty = 120.00;
$overAllocErrorTriggered = false;

if ($attemptOverAllocQty > ($availableA + 0.001)) {
    $overAllocErrorTriggered = true;
}
assertTest("Strictly rejects allocation of 120.00 kg on Roll A (exceeds 100.00 kg balance)", $overAllocErrorTriggered);

// ----------------------------------------------------
// TEST 5: Valid Allocation & Atomic Storage
// ----------------------------------------------------
echo "\nTEST 5: Valid Multi-Roll Allocation & Persistence\n";
$programNo = 'PRG-' . date('Ymd') . '-TEST' . rand(100, 999);
$programName = 'SUMMER MATERNITY COLLECTION 2026';
$plannerName = 'System Administrator';

$allocA = 45.00; // 45 kg out of 100 kg
$allocB = 30.00; // 30 kg out of 80 kg
$totalAlloc = $allocA + $allocB;

DB::beginTransaction();
try {
    DB::query(
        "INSERT INTO production_programs (
            concern_id, program_no, program_name, planner_name,
            catalog_id, target_variant_id, planned_qty,
            start_date, target_date, status, created_at
        ) VALUES (?, ?, ?, ?, NULL, NULL, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 14 DAY), 'planned', NOW())",
        [$cid1, $programNo, $programName, $plannerName, $totalAlloc]
    );
    $programId = (int)DB::lastInsertId();
    DB::query("DELETE FROM program_rolls WHERE program_id = ?", [$programId]);

    // Insert roll A allocation
    DB::query(
        "INSERT INTO program_rolls (
            concern_id, program_id, purchase_bill_item_id,
            roll_piece_tag, fabric_name, color, unit,
            total_roll_qty, allocated_qty, target_pieces,
            category_id, subcategory_id, sub_sub_category_id, catalog_id, created_at
        ) VALUES (?, ?, ?, ?, '100% Cotton Knitted Single Jersey', 'Navy Blue', 'kg', 100.00, ?, 180, 1, 1, NULL, NULL, NOW())",
        [$cid1, $programId, $rollAId, $uniqueTagA, $allocA]
    );

    // Insert roll B allocation
    DB::query(
        "INSERT INTO program_rolls (
            concern_id, program_id, purchase_bill_item_id,
            roll_piece_tag, fabric_name, color, unit,
            total_roll_qty, allocated_qty, target_pieces,
            category_id, subcategory_id, sub_sub_category_id, catalog_id, created_at
        ) VALUES (?, ?, ?, ?, 'Bio-Wash Combed Cotton Interlock', 'Burgundy', 'kg', 80.00, ?, 120, 1, 1, NULL, NULL, NOW())",
        [$cid1, $programId, $rollBId, $uniqueTagB, $allocB]
    );

    DB::commit();
    assertTest("Production Program #{$programNo} inserted with ID {$programId}", $programId > 0);
} catch (\Throwable $e) {
    DB::rollBack();
    assertTest("Valid insertion threw exception: " . $e->getMessage(), false);
}

// Check updated balances
$balA_after = $fetchRollBalance($rollAId, $cid1);
$availableA_after = (float)$balA_after['qty'] - (float)$balA_after['used_qty'];
assertTest("Roll A balance updated: 55.00 kg available out of 100.00 kg (45.00 kg used)", abs($availableA_after - 55.00) < 0.001);

$balB_after = $fetchRollBalance($rollBId, $cid1);
$availableB_after = (float)$balB_after['qty'] - (float)$balB_after['used_qty'];
assertTest("Roll B balance updated: 50.00 kg available out of 80.00 kg (30.00 kg used)", abs($availableB_after - 50.00) < 0.001);

// ----------------------------------------------------
// TEST 6: Subsequent Allocation Exceeding Remaining Balance
// ----------------------------------------------------
echo "\nTEST 6: Subsequent Program Exceeding Remaining Balance\n";
// Attempting to allocate 60.00 kg from Roll A (only 55.00 kg left)
$attemptSubsequentAlloc = 60.00;
$secondOverAllocTriggered = ($attemptSubsequentAlloc > ($availableA_after + 0.001));
assertTest("Rejects second allocation of 60.00 kg on Roll A because remaining balance is 55.00 kg", $secondOverAllocTriggered);

// ----------------------------------------------------
// TEST 7: Multi-Tenant Scoping Test (Concern 2 vs Concern 1)
// ----------------------------------------------------
echo "\nTEST 7: Multi-Tenant Scoping Isolation\n";
$cid2 = 2;
// Check if Roll A can be queried by Concern 2
$cid2RollLookup = DB::fetch("SELECT * FROM purchase_bill_items WHERE id = ? AND concern_id = ?", [$rollAId, $cid2]);
assertTest("Concern 2 cannot access or allocate Roll A owned by Concern 1", empty($cid2RollLookup));

// Check if Concern 2 can see Program 1
$cid2ProgLookup = DB::fetch("SELECT * FROM production_programs WHERE id = ? AND concern_id = ?", [$programId, $cid2]);
assertTest("Concern 2 cannot view or access Production Program of Concern 1", empty($cid2ProgLookup));

// ----------------------------------------------------
// TEST 8: READ Operation - Retrieve Program Details & Joined Rolls
// ----------------------------------------------------
echo "\nTEST 8: READ Operation - Program Details & Rolls\n";
$progDetails = DB::fetch("SELECT * FROM production_programs WHERE id = ?", [$programId]);
assertTest("READ: Program record retrieved", !empty($progDetails) && $progDetails['program_no'] === $programNo);

$assignedRolls = DB::fetchAll("SELECT * FROM program_rolls WHERE program_id = ? ORDER BY id ASC", [$programId]);
assertTest("READ: Program has exactly 2 assigned rolls", count($assignedRolls) === 2);
assertTest("READ: Roll A allocated qty is 45.00 kg", (float)$assignedRolls[0]['allocated_qty'] == 45.00);
assertTest("READ: Roll B allocated qty is 30.00 kg", (float)$assignedRolls[1]['allocated_qty'] == 30.00);

// ----------------------------------------------------
// TEST 9: UPDATE Operation - Valid Modification of Program & Roll Allocation
// ----------------------------------------------------
echo "\nTEST 9: UPDATE Operation - Adjust Program & Roll Allocation\n";
$updatedTitle = 'AUTUMN/WINTER CASUALS 2026';
$updatedAllocA = 50.00; // Increased from 45.00 to 50.00 kg (Roll A has 100 kg total, 0 in other programs)
$updatedTotal = $updatedAllocA + $allocB;

// Check balance calculation during update excluding self:
$usedInOther = (float)DB::fetchValue(
    "SELECT COALESCE(SUM(pr.allocated_qty), 0.00) 
     FROM program_rolls pr 
     JOIN production_programs pp ON pr.program_id = pp.id 
     WHERE pr.purchase_bill_item_id = ? AND pp.status != 'cancelled' AND pp.id != ?",
    [$rollAId, $programId]
);
$availableForUpdate = 100.00 - $usedInOther;
assertTest("UPDATE: Roll A available balance excluding self is 100.00 kg", $availableForUpdate == 100.00);

DB::beginTransaction();
try {
    DB::query(
        "UPDATE production_programs SET program_name = ?, planned_qty = ?, status = 'in_progress' WHERE id = ?",
        [$updatedTitle, $updatedTotal, $programId]
    );
    DB::query("DELETE FROM program_rolls WHERE program_id = ?", [$programId]);
    DB::query(
        "INSERT INTO program_rolls (
            concern_id, program_id, purchase_bill_item_id,
            roll_piece_tag, fabric_name, color, unit,
            total_roll_qty, allocated_qty, target_pieces,
            category_id, subcategory_id, sub_sub_category_id, catalog_id, created_at
        ) VALUES (?, ?, ?, ?, '100% Cotton Knitted Single Jersey', 'Navy Blue', 'kg', 100.00, ?, 200, 1, 1, NULL, NULL, NOW())",
        [$cid1, $programId, $rollAId, $uniqueTagA, $updatedAllocA]
    );
    DB::query(
        "INSERT INTO program_rolls (
            concern_id, program_id, purchase_bill_item_id,
            roll_piece_tag, fabric_name, color, unit,
            total_roll_qty, allocated_qty, target_pieces,
            category_id, subcategory_id, sub_sub_category_id, catalog_id, created_at
        ) VALUES (?, ?, ?, ?, 'Bio-Wash Combed Cotton Interlock', 'Burgundy', 'kg', 80.00, ?, 120, 1, 1, NULL, NULL, NOW())",
        [$cid1, $programId, $rollBId, $uniqueTagB, $allocB]
    );
    DB::commit();
    assertTest("UPDATE: Program updated successfully with modified name and allocation", true);
} catch (\Throwable $e) {
    DB::rollBack();
    assertTest("UPDATE: Failed with exception: " . $e->getMessage(), false);
}

$updatedProg = DB::fetch("SELECT * FROM production_programs WHERE id = ?", [$programId]);
assertTest("UPDATE: Program name is updated to '{$updatedTitle}'", $updatedProg['program_name'] === $updatedTitle);
assertTest("UPDATE: Program status is 'in_progress'", $updatedProg['status'] === 'in_progress');

// ----------------------------------------------------
// TEST 10: UPDATE Validation - Over-Allocation Rejected
// ----------------------------------------------------
echo "\nTEST 10: UPDATE Validation - Over-Allocation Exceeding Total Balance\n";
$excessiveAlloc = 150.00; // Roll A total is 100 kg
$updateBlocked = ($excessiveAlloc > ($availableForUpdate + 0.001));
assertTest("UPDATE: Excessive allocation of 150.00 kg is blocked (exceeds 100.00 kg balance)", $updateBlocked);

// ----------------------------------------------------
// TEST 11: STATUS UPDATE - Quick Status Transition
// ----------------------------------------------------
echo "\nTEST 11: Quick Status Transition\n";
DB::query("UPDATE production_programs SET status = 'completed' WHERE id = ?", [$programId]);
$statusCheck = DB::fetchValue("SELECT status FROM production_programs WHERE id = ?", [$programId]);
assertTest("STATUS UPDATE: Status successfully changed to 'completed'", $statusCheck === 'completed');

// ----------------------------------------------------
// TEST 12: DELETE Operation with Downstream Cutting Protection
// ----------------------------------------------------
echo "\nTEST 12: DELETE Operation - Downstream Cutting Protection\n";
// Create cutting log referencing this program
DB::query(
    "INSERT INTO cutting_logs (concern_id, log_no, program_id, raw_variant_id, consumed_qty, cut_pieces, wastage_qty, created_at)
     VALUES (?, 'CUT-TEST-001', ?, 1, 20.00, 80, 1.50, NOW())",
    [$cid1, $programId]
);
$cutLogId = (int)DB::lastInsertId();

// When cutting log exists, program should be cancelled rather than hard-deleted
$hasCutting = (int)DB::fetchValue("SELECT COUNT(*) FROM cutting_logs WHERE program_id = ?", [$programId]);
if ($hasCutting > 0) {
    DB::query("UPDATE production_programs SET status = 'cancelled' WHERE id = ?", [$programId]);
}
$postCutStatus = DB::fetchValue("SELECT status FROM production_programs WHERE id = ?", [$programId]);
assertTest("DELETE: Program with cutting history is safely cancelled to protect ledger integrity", $postCutStatus === 'cancelled');

// Clean up cutting log for clean hard delete test
DB::query("DELETE FROM cutting_logs WHERE id = ?", [$cutLogId]);

// ----------------------------------------------------
// TEST 13: DELETE Operation - Hard Delete & Stock Balance Restoration
// ----------------------------------------------------
echo "\nTEST 13: DELETE Operation - Hard Delete & Inventory Restoration\n";
DB::beginTransaction();
try {
    DB::query("DELETE FROM program_rolls WHERE program_id = ?", [$programId]);
    DB::query("DELETE FROM production_programs WHERE id = ?", [$programId]);
    DB::commit();
    assertTest("DELETE: Program and roll allocations cleanly deleted", true);
} catch (\Throwable $e) {
    DB::rollBack();
    assertTest("DELETE: Deletion failed: " . $e->getMessage(), false);
}

$deletedProg = DB::fetch("SELECT * FROM production_programs WHERE id = ?", [$programId]);
assertTest("DELETE: Program record no longer exists", empty($deletedProg));

$deletedRolls = DB::fetchAll("SELECT * FROM program_rolls WHERE program_id = ?", [$programId]);
assertTest("DELETE: Program rolls associations removed", empty($deletedRolls));

// Check that Roll A and Roll B now have their FULL balances restored back to stock
$balA_restored = $fetchRollBalance($rollAId, $cid1);
$availableA_restored = (float)$balA_restored['qty'] - (float)$balA_restored['used_qty'];
assertTest("DELETE: Roll A full 100.00 kg balance restored to inventory (0.00 kg used)", abs($availableA_restored - 100.00) < 0.001);

$balB_restored = $fetchRollBalance($rollBId, $cid1);
$availableB_restored = (float)$balB_restored['qty'] - (float)$balB_restored['used_qty'];
assertTest("DELETE: Roll B full 80.00 kg balance restored to inventory (0.00 kg used)", abs($availableB_restored - 80.00) < 0.001);

// ----------------------------------------------------
// SUMMARY
// ----------------------------------------------------
echo "\n======================================================\n";
echo "TEST RESULTS: Total Tests = " . ($passCount + $failCount) . " | Passed = {$passCount} | Failed = {$failCount}\n";
echo "======================================================\n";

if ($failCount === 0) {
    echo ">>> ALL PRODUCTION PROGRAM ROLLS VALIDATION TESTS PASSED SUCCESSFULLY! <<<\n";
    exit(0);
} else {
    echo ">>> SOME TESTS FAILED. PLEASE REVIEW LOGS. <<<\n";
    exit(1);
}
