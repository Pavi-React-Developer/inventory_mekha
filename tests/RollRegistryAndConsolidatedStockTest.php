<?php
// tests/RollRegistryAndConsolidatedStockTest.php

require_once __DIR__ . '/../app/core/Autoloader.php';
\App\Core\Env::load(dirname(__DIR__) . '/.env');

use App\Core\DB;
use App\Core\Auth;
use App\Services\RollRegistryService;
use App\Controllers\PurchaseController;
use App\Controllers\PurchaseStockController;

echo "======================================================\n";
echo "INVENTO: Roll Color, Design & Qty Accumulation Test\n";
echo "======================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertCheck(string $title, bool $condition, string $detail = '') {
    global $passCount, $failCount;
    if ($condition) {
        echo "  [PASS] {$title}\n";
        $passCount++;
    } else {
        echo "  [FAIL] {$title}" . ($detail ? " - {$detail}" : "") . "\n";
        $failCount++;
    }
}

// -----------------------------------------------------------
// TEST GROUP 1: Database Table & Master Registry
// -----------------------------------------------------------
echo "--- Group 1: Database Table & Master Registry ---\n";

RollRegistryService::initTable();

$tableExists = DB::fetchValue("SHOW TABLES LIKE 'fabric_roll_specs'");
assertCheck("Table 'fabric_roll_specs' exists in database", $tableExists === 'fabric_roll_specs');

// Sync existing rolls
RollRegistryService::syncSpecs(1);

$savedSpecs = RollRegistryService::getSavedSpecs(1);
assertCheck("Saved specs can be fetched for Concern 1", is_array($savedSpecs));

$savedColors = RollRegistryService::getSavedColors(1);
assertCheck("Saved colors can be fetched for future use", is_array($savedColors));

$savedDesigns = RollRegistryService::getSavedDesigns(1);
assertCheck("Saved designs can be fetched for future use", is_array($savedDesigns));

// -----------------------------------------------------------
// TEST GROUP 2: Duplicate Register Entry Removal & Qty Accumulation
// -----------------------------------------------------------
echo "\n--- Group 2: Duplicate Entry Removal & Qty Accumulation ---\n";

// Clear test specific records if any
DB::query("DELETE FROM purchase_bill_items WHERE item_code LIKE 'TEST-ROLL-%'");
DB::query("DELETE FROM fabric_roll_specs WHERE concern_id = 1 AND color = 'BLACK' AND design_name = 'FLORAL 01'");
$supplierId = (int)DB::fetchValue("SELECT id FROM suppliers WHERE concern_id = 1 ORDER BY id LIMIT 1");
$testBillNo = 'TEST-ROLL-STOCK-' . time();
DB::query(
    "INSERT INTO purchase_bills (concern_id, bill_no, supplier_id, bill_date, total_amount, tax_amount, net_amount, payment_status, inward_status)
     VALUES (1, ?, ?, CURDATE(), 0, 0, 0, 'unpaid', 'pending')",
    [$testBillNo, $supplierId]
);
$testBillId = (int)DB::lastInsertId();

// 1. Insert First Bill Item: 10 Rolls of BLACK / FLORAL 01
DB::query(
    "INSERT INTO purchase_bill_items (concern_id, purchase_bill_id, section_type, item_name, item_code, color, pattern, qty, net_weight, unit, unit_price, total_price, inward_status)
     VALUES (1, ?, 'rolls', 'FLORAL 01 Fabric', 'TEST-ROLL-01', 'BLACK', 'FLORAL 01', 10.0, 20.0, 'Rolls', 500.0, 5000.0, 'pending')",
    [$testBillId]
);

// 2. Insert Second Duplicate Bill Item (e.g. from same or another bill): 5 Rolls of BLACK / FLORAL 01
DB::query(
    "INSERT INTO purchase_bill_items (concern_id, purchase_bill_id, section_type, item_name, item_code, color, pattern, qty, net_weight, unit, unit_price, total_price, inward_status)
     VALUES (1, ?, 'rolls', 'FLORAL 01 Fabric', 'TEST-ROLL-02', 'BLACK', 'FLORAL 01', 5.0, 10.0, 'Rolls', 500.0, 2500.0, 'pending')",
    [$testBillId]
);

// Pending purchase items must not count as stock until the inward action completes.
RollRegistryService::syncSpecs(1);

// Verify ONLY 1 row exists in fabric_roll_specs for BLACK + FLORAL 01
$count = (int)DB::fetchValue(
    "SELECT COUNT(*) FROM fabric_roll_specs WHERE concern_id = 1 AND color = 'BLACK' AND design_name = 'FLORAL 01'"
);
assertCheck("Pending roll items are excluded from stock registry", $count === 0);

DB::query("UPDATE purchase_bill_items SET inward_status = 'completed' WHERE item_code LIKE 'TEST-ROLL-%'");
RollRegistryService::syncSpecs(1);
$count = (int)DB::fetchValue(
    "SELECT COUNT(*) FROM fabric_roll_specs WHERE concern_id = 1 AND color = 'BLACK' AND design_name = 'FLORAL 01'"
);
assertCheck("Duplicate register entry removed: Exactly 1 row in fabric_roll_specs for BLACK / FLORAL 01", $count === 1);

$finalRow = DB::fetch(
    "SELECT * FROM fabric_roll_specs WHERE concern_id = 1 AND color = 'BLACK' AND design_name = 'FLORAL 01'"
);
assertCheck("New quantity accumulated with previous count: total qty is 15.00 (10 + 5)", (float)$finalRow['qty'] == 15.0);
assertCheck("New weight accumulated with previous weight: total net_weight is 30.00 kg (20 + 10)", (float)$finalRow['net_weight'] == 30.0);
assertCheck("Total valuation accumulated to ₹7,500.00", (float)$finalRow['total_value'] == 7500.0);

// -----------------------------------------------------------
// TEST GROUP 3: Purchase Billing Create View Autocomplete & Fetch
// -----------------------------------------------------------
echo "\n--- Group 3: Purchase Billing Create Autocomplete & Live Feedback ---\n";

$_SESSION['user'] = [
    'id' => 1,
    'role_id' => 1,
    'name' => 'Super Administrator',
    'email' => 'admin@invento.com',
    'concern_id' => null,
];

$purchaseController = new PurchaseController();
ob_start();
$purchaseController->createBill();
$createHtml = ob_get_clean();

assertCheck("purchase_billing_create renders successfully", strlen($createHtml) > 500);
assertCheck("Contains <datalist id='rollColorList'> for colors", strpos($createHtml, 'id="rollColorList"') !== false);
assertCheck("Contains <datalist id='rollPatternList'> for designs", strpos($createHtml, 'id="rollPatternList"') !== false);
assertCheck("Color input has list='rollColorList'", strpos($createHtml, 'list="rollColorList"') !== false);
assertCheck("Pattern input has list='rollPatternList'", strpos($createHtml, 'list="rollPatternList"') !== false);
assertCheck("Quick-select dropdown for saved color & design present", strpos($createHtml, 'roll-quick-select') !== false);
assertCheck("Live roll stock notice div present", strpos($createHtml, 'id="rollStockNotice_1"') !== false);
assertCheck("JavaScript contains onRollColorOrPatternChange()", strpos($createHtml, 'onRollColorOrPatternChange') !== false);

// -----------------------------------------------------------
// TEST GROUP 4: Purchase Stocks Reflection & Report Export
// -----------------------------------------------------------
echo "\n--- Group 4: Purchase Stocks Reflection & Report Export ---\n";

$stockController = new PurchaseStockController();

// 1. Consolidated View
$_GET = ['tab' => 'rolls', 'concern_id' => '1', 'view' => 'consolidated'];
ob_start();
$stockController->index();
$stockHtml = ob_get_clean();

assertCheck("Purchase Stocks renders consolidated table", strpos($stockHtml, 'viewRollConsolidated') !== false);
assertCheck("Consolidated table displays 'FLORAL 01'", strpos($stockHtml, 'FLORAL 01') !== false);
assertCheck("Consolidated table displays 'BLACK'", strpos($stockHtml, 'BLACK') !== false);
assertCheck("Consolidated table displays accumulated 15 Rolls", strpos($stockHtml, '15') !== false);
assertCheck("View mode switcher button group present", strpos($stockHtml, 'btnRollConsolidated') !== false);
assertCheck("Consolidated stock table uses compact row styling", strpos($stockHtml, 'table-sm align-middle') !== false);
assertCheck("Consolidated stock rows provide the exact fabric-label preview", strpos($stockHtml, 'id="rollQrModal"') !== false && strpos($stockHtml, 'onclick="showRollQr(this)"') !== false && strpos($stockHtml, 'id="rollQrFabric"') !== false && strpos($stockHtml, 'id="rollQrCodeText"') !== false);
assertCheck("Consolidated stock rows provide an edit-only action", strpos($stockHtml, 'onclick="editConsolidatedRoll(this)"') !== false && strpos($stockHtml, 'id="editConsolidatedRollModal"') !== false);
assertCheck("Edit action is prefilled with the row's current values", strpos($stockHtml, 'data-design="FLORAL 01"') !== false && strpos($stockHtml, 'data-qty="15.00"') !== false);

// 2. Export CSV Check
ob_start();
// Test export logic directly
RollRegistryService::syncSpecs(1);
$csvRows = RollRegistryService::getConsolidatedStock(1);
assertCheck("Consolidated stock records ready for export", count($csvRows) >= 1);

$foundBlackFloral = false;
foreach ($csvRows as $r) {
    if ($r['color'] === 'BLACK' && $r['design_name'] === 'FLORAL 01' && (float)$r['qty'] == 15.0) {
        $foundBlackFloral = true;
        break;
    }
}
assertCheck("Export dataset has merged entry for BLACK / FLORAL 01 with accumulated qty = 15.0", $foundBlackFloral);

// Edited consolidated values must survive registry rebuilds without changing purchase history.
$_SESSION['csrf_token'] = 'test-roll-stock-edit-token';
$_POST = [
    'csrf_token' => $_SESSION['csrf_token'],
    'concern_id' => 1,
    'source_color' => 'BLACK',
    'source_design_name' => 'FLORAL 01',
    'color' => 'BLACK',
    'design_name' => 'FLORAL 01 EDITED',
    'hsn_code' => '6543',
    'qty' => '17.00',
    'net_weight' => '32.00',
    'unit' => 'Rolls',
    'last_unit_price' => '510.00',
    'total_value' => '8670.00',
];
$editController = new class extends PurchaseStockController {
    public ?string $lastRedirect = null;
    public ?string $lastFlashType = null;

    public function redirect(string $url): void {
        $this->lastRedirect = $url;
    }

    public function flash(string $type, string $message): void {
        $this->lastFlashType = $type;
    }
};
$editController->updateConsolidatedRoll();
assertCheck("Consolidated edit endpoint accepts a valid CSRF-protected update", $editController->lastFlashType === 'success');
RollRegistryService::syncSpecs(1);
$editedRows = RollRegistryService::getConsolidatedStock(1);
$editedRow = null;
foreach ($editedRows as $r) {
    if ($r['source_color'] === 'BLACK' && $r['source_design_name'] === 'FLORAL 01') {
        $editedRow = $r;
        break;
    }
}
assertCheck("Edited consolidated values persist after stock synchronization", !empty($editedRow) && $editedRow['design_name'] === 'FLORAL 01 EDITED' && (float)$editedRow['qty'] === 17.0 && (float)$editedRow['net_weight'] === 32.0 && (float)$editedRow['total_value'] === 8670.0);
assertCheck("Edited consolidated values remain searchable", count(RollRegistryService::getConsolidatedStock(1, 'EDITED')) === 1);
$sourceQty = (float)DB::fetchValue("SELECT SUM(qty) FROM purchase_bill_items WHERE item_code LIKE 'TEST-ROLL-%'");
assertCheck("Editing consolidated values preserves source purchase quantities", $sourceQty === 15.0);

// Clean up test entry
DB::query("DELETE FROM fabric_roll_spec_overrides WHERE concern_id = 1 AND source_color = 'BLACK' AND source_design_name = 'FLORAL 01'");
DB::query("DELETE FROM purchase_bill_items WHERE item_code LIKE 'TEST-ROLL-%'");
DB::query("DELETE FROM purchase_bills WHERE id = ?", [$testBillId]);
DB::query("DELETE FROM fabric_roll_specs WHERE concern_id = 1 AND color = 'BLACK' AND design_name = 'FLORAL 01'");
RollRegistryService::syncSpecs(1);

// -----------------------------------------------------------
// SUMMARY
// -----------------------------------------------------------
echo "\n======================================================\n";
echo "SUMMARY: {$passCount} Passed, {$failCount} Failed\n";
echo "======================================================\n";

if ($failCount > 0) {
    exit(1);
}
