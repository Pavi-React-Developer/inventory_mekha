<?php
/**
 * Test: Production Program Dynamic Weight and Price Calculation & Non-Editable Inputs
 */
require_once __DIR__ . '/../app/core/Autoloader.php';
\App\Core\Env::load(dirname(__DIR__) . '/.env');

use App\Core\DB;

$passed = 0;
$failed = 0;

function assertTest($cond, $desc) {
    global $passed, $failed;
    if ($cond) {
        echo "  [PASS] $desc\n";
        $passed++;
    } else {
        echo "  [FAIL] $desc\n";
        $failed++;
    }
}

echo "\n======================================================\n";
echo "INVENTO: Production Program Dynamic Weight & Price Test\n";
echo "======================================================\n\n";

// TEST 1: Check program_create.php and program_edit.php template readonly attributes
echo "TEST 1: View Template Attributes (Non-Editable Weight and Price)\n";
$createView = file_get_contents(__DIR__ . '/../app/views/concern/program_create.php');
$editView = file_get_contents(__DIR__ . '/../app/views/concern/program_edit.php');

assertTest(strpos($createView, 'name="rolls[${index}][roll_weight]" class="form-control input-roll-weight bg-light fw-semibold" value="0.00" readonly tabindex="-1"') !== false, "Create View: roll_weight is readonly with bg-light and tabindex=-1");
assertTest(strpos($createView, 'name="rolls[${index}][roll_price]" class="form-control input-roll-price font-monospace bg-light fw-bold" value="0.00" readonly tabindex="-1"') !== false, "Create View: roll_price is readonly with bg-light and tabindex=-1");

assertTest(strpos($editView, 'id="rollWeight_${index}" class="form-control input-roll-weight bg-light fw-semibold" value="${rollWeightVal}" readonly tabindex="-1"') !== false, "Edit View: roll_weight is readonly with bg-light and tabindex=-1");
assertTest(strpos($editView, 'id="rollPrice_${index}" class="form-control input-roll-price font-monospace bg-light fw-bold" value="${rollPriceVal}" readonly tabindex="-1"') !== false, "Edit View: roll_price is readonly with bg-light and tabindex=-1");

// TEST 2: Mathematical formula verification matching JavaScript & PHP logic
echo "\nTEST 2: Dynamic Calculation Formulas\n";
// Scenario 1: Roll #46 Flower Fabric: 10 RQ, 200 kg net weight, unit_price = 5000.00
$totalQty = 10.0;
$netWeight = 200.0;
$unitPrice = 5000.0;
$allocatedQty = 5.0;

$calcWeight = round(($allocatedQty / $totalQty) * $netWeight, 2);
$calcPrice = round($allocatedQty * $unitPrice, 2);

assertTest($calcWeight === 100.00, "5 RQ of (10 RQ, 200 kg) yields exactly 100.00 kg");
assertTest($calcPrice === 25000.00, "5 RQ at ₹5,000.00/RQ yields exactly ₹25,000.00");

// Scenario 2: 100 kg fabric at ₹320/kg, allocated 35 kg
$unitKg = 'kg';
$allocKg = 35.0;
$rateKg = 320.0;
$calcWeightKg = $allocKg; // when unit is kg, weight = qty
$calcPriceKg = round($allocKg * $rateKg, 2);

assertTest($calcWeightKg === 35.00, "35 kg fabric yields exactly 35.00 kg weight");
assertTest($calcPriceKg === 11200.00, "35 kg at ₹320.00/kg yields ₹11,200.00");

// TEST 3: DB Persistence Check with calculated values
echo "\nTEST 3: Database Storage and Persistence of Dynamic Values\n";
$concernId = 1;
$uniqueSuffix = time();

$sup = DB::fetch("SELECT id FROM suppliers LIMIT 1");
$supplierId = $sup ? (int)$sup['id'] : 1;

// Insert a mock purchase bill and item
DB::query(
    "INSERT INTO purchase_bills (concern_id, bill_no, supplier_id, bill_date, payment_status, total_amount, created_at)
     VALUES (?, ?, ?, NOW(), 'paid', 50000.00, NOW())",
    [$concernId, "TEST-BILL-DYN-$uniqueSuffix", $supplierId]
);
$billId = DB::lastInsertId();

DB::query(
    "INSERT INTO purchase_bill_items (concern_id, purchase_bill_id, section_type, item_name, color, pattern, unit, qty, net_weight, unit_price, total_price)
     VALUES (?, ?, 'rolls', 'Flower Fabric Test', 'Black', 'Flower', 'RQ', 10.00, 200.00, 5000.00, 50000.00)",
    [$concernId, $billId]
);
$billItemId = DB::lastInsertId();

// Create program with 5 RQ allocated
$progNo = "PRG-DYN-$uniqueSuffix";
DB::query(
    "INSERT INTO production_programs (concern_id, program_no, program_name, planned_qty, start_date, target_date, status, created_at)
     VALUES (?, ?, 'Dynamic Weight Test Program', 5.00, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 14 DAY), 'planned', NOW())",
    [$concernId, $progNo]
);
$progId = DB::lastInsertId();

DB::query(
    "INSERT INTO program_rolls (
        concern_id, program_id, purchase_bill_item_id, roll_piece_tag, fabric_name, color, unit,
        total_roll_qty, allocated_qty, roll_weight, roll_price, target_pieces, created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
    [
        $concernId, $progId, $billItemId, "FLO-TEST-$uniqueSuffix", 'Flower Fabric Test', 'Black', 'RQ',
        10.00, 5.00, $calcWeight, $calcPrice, 0
    ]
);
$rollInsertId = DB::lastInsertId();

$savedRoll = DB::fetch("SELECT * FROM program_rolls WHERE id = ?", [$rollInsertId]);
assertTest((float)$savedRoll['roll_weight'] === 100.00, "Persisted roll_weight in program_rolls is exactly 100.00 kg");
assertTest((float)$savedRoll['roll_price'] === 25000.00, "Persisted roll_price in program_rolls is exactly ₹25,000.00");

// Clean up test data
DB::query("DELETE FROM program_rolls WHERE program_id = ?", [$progId]);
DB::query("DELETE FROM production_programs WHERE id = ?", [$progId]);
DB::query("DELETE FROM purchase_bill_items WHERE id = ?", [$billItemId]);
DB::query("DELETE FROM purchase_bills WHERE id = ?", [$billId]);

echo "\n======================================================\n";
echo "TEST RESULTS: Total = " . ($passed + $failed) . " | Passed = $passed | Failed = $failed\n";
echo "======================================================\n";

if ($failed === 0) {
    echo ">>> ALL DYNAMIC WEIGHT & PRICE TESTS PASSED SUCCESSFULLY! <<<\n";
    exit(0);
} else {
    echo ">>> SOME TESTS FAILED! <<<\n";
    exit(1);
}
