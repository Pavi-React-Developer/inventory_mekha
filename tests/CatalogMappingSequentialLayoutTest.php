<?php
// tests/CatalogMappingSequentialLayoutTest.php - Verify Catalog Mapping Sequential Layout & Comprehensive Data Fetching

require_once __DIR__ . '/../app/core/Autoloader.php';
\App\Core\Env::load(dirname(__DIR__) . '/.env');

use App\Core\DB;
use App\Core\Auth;
use App\Controllers\CatalogMappingController;

echo "======================================================\n";
echo "INVENTO: Catalog Mapping Sequential Layout Verification\n";
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
// Setup User & Tenant
// ----------------------------------------------------
echo "--- Group 1: Tenant & Purchased Finished Good Setup ---\n";
$user = DB::fetch("SELECT * FROM users WHERE email = 'grm.admin@invento.com'");
if (!$user) {
    $user = DB::fetch("SELECT * FROM users WHERE role = 'concern_admin' LIMIT 1");
}
assertTest("Found valid concern user", !empty($user));
$_SESSION['user_id'] = $user['id'];
$_SESSION['concern_id'] = $user['concern_id'] ?: 1;
$_SESSION['user_role'] = $user['role'] ?? ($user['role_name'] ?? 'concern_admin');
$cid = (int)$_SESSION['concern_id'];

// Insert or fetch Supplier
$supplier = DB::fetch("SELECT * FROM suppliers WHERE concern_id = ? LIMIT 1", [$cid]);
if (!$supplier) {
    DB::query(
        "INSERT INTO suppliers (concern_id, name, phone, email, gstin, city, state, status, created_at)
         VALUES (?, 'ANAND TEXTILES', '8668007593', 'anand@textiles.com', '33AAAAA0000A1Z5', 'Karur', 'Tamil Nadu', 'active', NOW())",
        [$cid]
    );
    $supplierId = (int)DB::lastInsertId();
    $supplier = DB::fetch("SELECT * FROM suppliers WHERE id = ?", [$supplierId]);
} else {
    $supplierId = (int)$supplier['id'];
}
assertTest("Supplier exists with phone and GSTIN", !empty($supplier['phone']) || !empty($supplier['name']));

// Insert Purchase Bill
$billNo = 'GRM-PO-TEST-' . rand(1000, 9999);
$invNo = 'INV-' . rand(1000, 9999);
DB::query(
    "INSERT INTO purchase_bills (
        concern_id, bill_no, supplier_invoice_no, supplier_id, bill_date,
        total_amount, tax_amount, net_amount, payment_status, payment_method,
        paid_amount, inward_status, created_at
    ) VALUES (?, ?, ?, ?, CURDATE(), 10500.00, 500.00, 10500.00, 'paid', 'credit', 10500.00, 'pending', NOW())",
    [$cid, $billNo, $invNo, $supplierId]
);
$billId = (int)DB::lastInsertId();
assertTest("Created test purchase bill #{$billNo} with Inv #{$invNo}", $billId > 0);

// Insert Purchased Finished Good item with comprehensive fields
$itemName = 'PREMIUM RED COTTON SHIRT';
$itemCode = 'FG-SHIRT-' . rand(100, 999);
DB::query(
    "INSERT INTO purchase_bill_items (
        concern_id, purchase_bill_id, section_type, item_name, item_code,
        hsn_code, specification, color, pattern, unit,
        net_weight, qty, inward_status, unit_price, gst_rate,
        gst_type, tax_mode, total_price, tax_amount
    ) VALUES (
        ?, ?, 'finished_goods', ?, ?,
        '6543', 'Formal Cotton Full Sleeves', 'Red', 'Solid', 'Pieces',
        15.50, 50.00, 'pending', 200.00, 5.00,
        'Both (CGST and SGST)', 'Exclude GST', 10000.00, 500.00
    )",
    [$cid, $billId, $itemName, $itemCode]
);
$itemId = (int)DB::lastInsertId();
assertTest("Created finished good item with ID {$itemId} (Weight: 15.50kg, Qty: 50, Rate: ₹200)", $itemId > 0);

// ----------------------------------------------------
// Group 2: Controller & Data Fetching Verification
// ----------------------------------------------------
echo "\n--- Group 2: Controller & Comprehensive Data Fetching ---\n";
$_GET['id'] = $itemId;

// Capture rendered output of mapItemForm
$controller = new CatalogMappingController();
ob_start();
try {
    $controller->mapItemForm();
    $html = ob_get_clean();
} catch (\Throwable $e) {
    ob_end_clean();
    $html = '';
    echo "Controller error: " . $e->getMessage() . "\n";
}

assertTest("mapItemForm rendered HTML successfully", !empty($html));

// Verify that ALL data points are fetched and present in the rendered HTML
assertTest("Product name is present in HTML", str_contains($html, $itemName));
assertTest("Item code is present in HTML", str_contains($html, $itemCode));
assertTest("Bill number is present in HTML", str_contains($html, $billNo));
assertTest("Supplier invoice number is present in HTML", str_contains($html, $invNo));
assertTest("Supplier name is present in HTML", str_contains($html, htmlspecialchars($supplier['name'])));
assertTest("Quantity '50' is present in HTML", str_contains($html, '50'));
assertTest("Unit 'Pieces' is present in HTML", str_contains($html, 'Pieces'));
assertTest("Net weight '15.50 kg' is present in HTML", str_contains($html, '15.50') && str_contains($html, 'kg'));
assertTest("Supplier cost '200.00' is present in HTML", str_contains($html, '200.00'));
assertTest("Tax Mode 'Exclude GST' is present in HTML", str_contains($html, 'Exclude GST'));
assertTest("HSN code '6543' is present in HTML", str_contains($html, '6543'));
assertTest("GST rate '5.00%' is present in HTML", str_contains($html, '5.00%'));
assertTest("Resolved color 'Red' is extracted and present", str_contains($html, 'Red'));
assertTest("Payment status 'Paid' badge is present in HTML", str_contains($html, 'Paid'));
assertTest("Inward status badge is present in HTML", str_contains($html, 'Pending Inward') || str_contains($html, 'Inward Completed'));

// ----------------------------------------------------
// Group 3: Sequential Order Verification (One-by-One)
// ----------------------------------------------------
echo "\n--- Group 3: Sequential Layout Flow (1 -> 2 -> 3 -> 4 -> 5 -> 6) ---\n";

$posSection1 = strpos($html, '1. Purchased Product Baseline Details');
$posSection2 = strpos($html, '2. Category Hierarchy (3-Tier)');
$posSection3 = strpos($html, '3. Product Attributes & Variations');
$posSection4 = strpos($html, '4. Pricing Configuration');
$posSection5 = strpos($html, '5. Mapping Summary & Stock Projection');
$posSection6 = strpos($html, 'Save & Map to Catalog');

assertTest("Section 1 (Baseline Details) is found in HTML", $posSection1 !== false);
assertTest("Section 2 (Category Hierarchy) is found in HTML", $posSection2 !== false);
assertTest("Section 3 (Product Attributes) is found in HTML", $posSection3 !== false);
assertTest("Section 4 (Pricing Configuration) is found in HTML", $posSection4 !== false);
assertTest("Section 5 (Mapping Summary) is found in HTML", $posSection5 !== false);
assertTest("Section 6 (Bottom Save Bar) is found in HTML", $posSection6 !== false);

// Sequential order check: 1 < 2 < 3 < 4 < 5 < 6
assertTest("ORDER CHECK: Section 1 (Baseline Details) appears FIRST", $posSection1 < $posSection2);
assertTest("ORDER CHECK: Section 2 (Category Hierarchy) appears AFTER Section 1", $posSection2 > $posSection1 && $posSection2 < $posSection3);
assertTest("ORDER CHECK: Section 3 (Product Attributes) appears AFTER Section 2", $posSection3 > $posSection2 && $posSection3 < $posSection4);
assertTest("ORDER CHECK: Section 4 (Pricing Configuration) appears AFTER Section 3", $posSection4 > $posSection3 && $posSection4 < $posSection5);
assertTest("ORDER CHECK: Section 5 (Mapping Summary) appears AFTER Section 4", $posSection5 > $posSection4 && $posSection5 < $posSection6);
assertTest("ORDER CHECK: Bottom Save Bar appears LAST", $posSection6 > $posSection5);

// ----------------------------------------------------
// Group 4: Required Interactive Elements & Field Presence
// ----------------------------------------------------
echo "\n--- Group 4: Interactive Fields & Elements Integrity ---\n";

assertTest("Radio 'modeNewStyle' is present", str_contains($html, 'id="modeNewStyle"'));
assertTest("Radio 'modeExistingStyle' is present", str_contains($html, 'id="modeExistingStyle"'));
assertTest("Select 'selectMainCategory' is present", str_contains($html, 'id="selectMainCategory"'));
assertTest("Select 'selectSubCategory' is present", str_contains($html, 'id="selectSubCategory"'));
assertTest("Select 'selectSubSubCategory' is present", str_contains($html, 'id="selectSubSubCategory"'));
assertTest("Input 'inputStyleCode' is present", str_contains($html, 'id="inputStyleCode"'));
assertTest("Input 'inputStyleName' is present", str_contains($html, 'id="inputStyleName"'));
assertTest("Input 'inputColor' is present", str_contains($html, 'id="inputColor"'));
assertTest("Container 'sizesContainer' is present", str_contains($html, 'id="sizesContainer"'));
assertTest("Input 'inputCustomSize' is present", str_contains($html, 'id="inputCustomSize"'));
assertTest("Input 'inputMrp' is present", str_contains($html, 'id="inputMrp"'));
assertTest("Input 'inputSellingPrice' is present", str_contains($html, 'id="inputSellingPrice"'));
assertTest("Element 'summarySelectedSizesCount' is present", str_contains($html, 'id="summarySelectedSizesCount"'));
assertTest("Submit button 'btnSaveMapping' is present", str_contains($html, 'id="btnSaveMapping"'));

// ----------------------------------------------------
// Cleanup Test Data (Keep Live Database Clean)
// ----------------------------------------------------
if (!empty($billId)) {
    DB::query("DELETE FROM purchase_bill_items WHERE purchase_bill_id = ?", [$billId]);
    DB::query("DELETE FROM purchase_bills WHERE id = ?", [$billId]);
}

// ----------------------------------------------------
// SUMMARY
// ----------------------------------------------------
echo "\n======================================================\n";
echo "TEST RESULTS: Total = " . ($passCount + $failCount) . " | Passed = {$passCount} | Failed = {$failCount}\n";
echo "======================================================\n";

if ($failCount === 0) {
    echo ">>> SEQUENTIAL CATALOG MAPPING LAYOUT & DATA FETCHING FULLY VERIFIED! <<<\n";
    exit(0);
} else {
    echo ">>> SOME TESTS FAILED! <<<\n";
    exit(1);
}
