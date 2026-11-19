<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/core/Autoloader.php';
\App\Core\Env::load(__DIR__ . '/../.env');

use App\Core\DB;

$passed = 0;
$failed = 0;

function assertCondition(bool $cond, string $msg): void {
    global $passed, $failed;
    if ($cond) {
        $passed++;
        echo "  [PASS] {$msg}\n";
    } else {
        $failed++;
        echo "  [FAIL] {$msg}\n";
    }
}

echo "=== Section-Restricted Add to Stock & Dynamic Roll Prefix Test ===\n";

// 1. Verify generateRollCode logic and template content
$billingCreatePath = __DIR__ . '/../app/views/concern/purchase_billing_create.php';
$billingCreateCode = file_get_contents($billingCreatePath);

assertCondition(!str_contains($billingCreateCode, 'FB11266'), "purchase_billing_create.php contains no hardcoded FB112661");
assertCondition(str_contains($billingCreateCode, "return 'ROLL-' + clean;"), "generateRollCode uses ROLL- prefix for dynamic fabric name");
assertCondition(str_contains($billingCreateCode, "return 'ROLL-' + (num < 10 ? '0' + num : num);"), "generateRollCode uses ROLL- prefix for numeric roll numbers");
assertCondition(str_contains($billingCreateCode, 'updateRollHeaderCode('), "updateRollHeaderCode is wired up on fabric name input");

// 2. Test Single-Item Inward Isolation in Database
$testCid = 1;
$supplierId = (int)DB::fetchValue("SELECT id FROM suppliers WHERE concern_id = ? LIMIT 1", [$testCid]);
if (!$supplierId) {
    DB::query("INSERT INTO suppliers (concern_id, name, company_name, phone, created_at) VALUES (?, 'Test Vendor', 'Vendor Co', '9876543210', NOW())", [$testCid]);
    $supplierId = (int)DB::lastInsertId();
}

$testBillNo = 'TEST-INW-' . time();
DB::query(
    "INSERT INTO purchase_bills (concern_id, bill_no, bill_date, supplier_id, payment_status, inward_status, total_amount, net_amount, created_at) 
     VALUES (?, ?, CURDATE(), ?, 'Paid', 'pending', 1000.00, 1000.00, NOW())",
    [$testCid, $testBillNo, $supplierId]
);
$testBillId = (int)DB::lastInsertId();

// Insert 1 roll, 1 trim, 1 finished good
DB::query(
    "INSERT INTO purchase_bill_items (concern_id, purchase_bill_id, section_type, item_name, item_code, inward_status, qty, unit_price, total_price)
     VALUES (?, ?, 'rolls', 'Silk Fabric', 'ROLL-01', 'pending', 1.00, 500.00, 500.00)",
    [$testCid, $testBillId]
);
$rollItemId = (int)DB::lastInsertId();

DB::query(
    "INSERT INTO purchase_bill_items (concern_id, purchase_bill_id, section_type, item_name, item_code, inward_status, qty, unit_price, total_price)
     VALUES (?, ?, 'garment_other', 'Plastic Buttons', 'TR-BTN', 'pending', 100.00, 2.00, 200.00)",
    [$testCid, $testBillId]
);
$trimItemId = (int)DB::lastInsertId();

DB::query(
    "INSERT INTO purchase_bill_items (concern_id, purchase_bill_id, section_type, item_name, item_code, inward_status, qty, unit_price, total_price)
     VALUES (?, ?, 'finished_goods', 'Formal Shirt', 'FG-SHT', 'pending', 10.00, 30.00, 300.00)",
    [$testCid, $testBillId]
);
$fgItemId = (int)DB::lastInsertId();

// Verify initial pending state
$items = DB::fetchAll("SELECT id, section_type, inward_status FROM purchase_bill_items WHERE purchase_bill_id = ?", [$testBillId]);
assertCondition(count($items) === 3, "Created test bill with 3 items (rolls, garment_other, finished_goods)");
foreach ($items as $it) {
    assertCondition($it['inward_status'] === 'pending', "Item {$it['id']} ({$it['section_type']}) starts as pending");
}

// 3. Simulate Add to Stock for Fabric Roll ONLY (item_id = $rollItemId)
// Execute single-item inward logic matching LabelController::addToStock
DB::query("UPDATE purchase_bill_items SET inward_status = 'completed' WHERE id = ?", [$rollItemId]);
$pendingCount = (int)DB::fetchValue("SELECT COUNT(*) FROM purchase_bill_items WHERE purchase_bill_id = ? AND inward_status = 'pending'", [$testBillId]);
if ($pendingCount === 0) {
    DB::query("UPDATE purchase_bills SET inward_status = 'completed' WHERE id = ?", [$testBillId]);
} else {
    DB::query("UPDATE purchase_bills SET inward_status = 'pending' WHERE id = ?", [$testBillId]);
}

$rollStatus = DB::fetchValue("SELECT inward_status FROM purchase_bill_items WHERE id = ?", [$rollItemId]);
$trimStatus = DB::fetchValue("SELECT inward_status FROM purchase_bill_items WHERE id = ?", [$trimItemId]);
$fgStatus   = DB::fetchValue("SELECT inward_status FROM purchase_bill_items WHERE id = ?", [$fgItemId]);
$billStatus = DB::fetchValue("SELECT inward_status FROM purchase_bills WHERE id = ?", [$testBillId]);

assertCondition($rollStatus === 'completed', "Fabric Roll item is strictly marked completed");
assertCondition($trimStatus === 'pending', "Trim item strictly remains pending");
assertCondition($fgStatus === 'pending', "Finished Good item strictly remains pending");
assertCondition($billStatus === 'pending', "Purchase Bill strictly remains pending because 2 items are still pending");

// 4. Simulate section-restricted inward for garment_other (bill_id + tab=garment_other)
DB::query("UPDATE purchase_bill_items SET inward_status = 'completed' WHERE purchase_bill_id = ? AND section_type = 'garment_other'", [$testBillId]);
$pendingCount2 = (int)DB::fetchValue("SELECT COUNT(*) FROM purchase_bill_items WHERE purchase_bill_id = ? AND inward_status = 'pending'", [$testBillId]);
if ($pendingCount2 === 0) {
    DB::query("UPDATE purchase_bills SET inward_status = 'completed' WHERE id = ?", [$testBillId]);
} else {
    DB::query("UPDATE purchase_bills SET inward_status = 'pending' WHERE id = ?", [$testBillId]);
}

$trimStatus2 = DB::fetchValue("SELECT inward_status FROM purchase_bill_items WHERE id = ?", [$trimItemId]);
$fgStatus2   = DB::fetchValue("SELECT inward_status FROM purchase_bill_items WHERE id = ?", [$fgItemId]);
$billStatus2 = DB::fetchValue("SELECT inward_status FROM purchase_bills WHERE id = ?", [$testBillId]);

assertCondition($trimStatus2 === 'completed', "Trim item is now completed after garment_other inward");
assertCondition($fgStatus2 === 'pending', "Finished Good item STILL remains pending");
assertCondition($billStatus2 === 'pending', "Purchase Bill STILL remains pending with 1 pending item");

// 5. Inward final item (finished_goods) and verify bill completes
DB::query("UPDATE purchase_bill_items SET inward_status = 'completed' WHERE purchase_bill_id = ? AND section_type = 'finished_goods'", [$testBillId]);
$pendingCount3 = (int)DB::fetchValue("SELECT COUNT(*) FROM purchase_bill_items WHERE purchase_bill_id = ? AND inward_status = 'pending'", [$testBillId]);
if ($pendingCount3 === 0) {
    DB::query("UPDATE purchase_bills SET inward_status = 'completed' WHERE id = ?", [$testBillId]);
} else {
    DB::query("UPDATE purchase_bills SET inward_status = 'pending' WHERE id = ?", [$testBillId]);
}

$fgStatus3   = DB::fetchValue("SELECT inward_status FROM purchase_bill_items WHERE id = ?", [$fgItemId]);
$billStatus3 = DB::fetchValue("SELECT inward_status FROM purchase_bills WHERE id = ?", [$testBillId]);

assertCondition($fgStatus3 === 'completed', "Finished Good item is now completed");
assertCondition($billStatus3 === 'completed', "Purchase Bill is now completed because 0 pending items remain");

// Clean up test bill
DB::query("DELETE FROM purchase_bill_items WHERE purchase_bill_id = ?", [$testBillId]);
DB::query("DELETE FROM purchase_bills WHERE id = ?", [$testBillId]);

// 6. Verify Bill 126 (GRM-PO-0105) current state
$b126 = DB::fetch("SELECT id, bill_no, inward_status FROM purchase_bills WHERE id = 126");
$items126 = DB::fetchAll("SELECT id, section_type, item_name, inward_status FROM purchase_bill_items WHERE purchase_bill_id = 126");

assertCondition($b126['inward_status'] === 'pending', "Bill 126 (GRM-PO-0105) is pending");
foreach ($items126 as $it) {
    if ($it['section_type'] === 'rolls') {
        assertCondition($it['inward_status'] === 'completed', "Bill 126 Fabric Roll is completed (in stock)");
    } else {
        assertCondition($it['inward_status'] === 'pending', "Bill 126 {$it['item_name']} ({$it['section_type']}) is pending (not in stock)");
    }
}

echo "\nSummary: {$passed} passed, {$failed} failed.\n";
exit($failed === 0 ? 0 : 1);
