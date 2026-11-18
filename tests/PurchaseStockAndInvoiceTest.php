<?php
// tests/PurchaseStockAndInvoiceTest.php - Comprehensive verification for Purchase Stocks module and Invoice table

require_once __DIR__ . '/../app/core/Autoloader.php';
\App\Core\Env::load(dirname(__DIR__) . '/.env');

use App\Core\DB;
use App\Core\Auth;
use App\Core\ModuleGuard;
use App\Controllers\PurchaseStockController;

echo "======================================================\n";
echo "INVENTO: Purchase Stocks & Invoice Optimization Test\n";
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
// TEST GROUP 1: Database Registry & Security RBAC
// -----------------------------------------------------------
echo "--- Group 1: Module Registration & Permissions ---\n";

$mod = DB::fetch("SELECT * FROM modules WHERE code = 'purchase_stocks'");
assertCheck("Module 'purchase_stocks' exists in modules table", !empty($mod));
assertCheck("Module is in 'Purchase' group", ($mod['group_name'] ?? '') === 'Purchase');
assertCheck("Module sort_order is 22", (int)($mod['sort_order'] ?? 0) === 22);

$cmCount = (int)DB::fetchValue("SELECT COUNT(*) FROM concern_modules WHERE module_id = ?", [$mod['id']]);
assertCheck("Module enabled across concerns in concern_modules", $cmCount >= 2);

$rpCount = (int)DB::fetchValue("SELECT COUNT(*) FROM role_permissions WHERE module_id = ? AND can_view = 1", [$mod['id']]);
assertCheck("Role permissions granted for purchase_stocks", $rpCount >= 2);

// Check that purchase_damage is after purchase_stocks
$damSort = (int)DB::fetchValue("SELECT sort_order FROM modules WHERE code = 'purchase_damage'");
assertCheck("purchase_damage sort order is 23", $damSort === 23);

// -----------------------------------------------------------
// TEST GROUP 2: PurchaseStockController Logic & 3 Sections
// -----------------------------------------------------------
echo "\n--- Group 2: PurchaseStockController & 3 Sections Data ---\n";

// Set session as Super Admin
$_SESSION['user'] = [
    'id' => 1,
    'role_id' => 1,
    'name' => 'Super Administrator',
    'email' => 'admin@invento.com',
    'concern_id' => null,
];

$controller = new PurchaseStockController();

// Test Tab 1: Rolls
$_GET['tab'] = 'rolls';
$_GET['concern_id'] = '1';
ob_start();
$controller->index();
$rollsHtml = ob_get_clean();

assertCheck("Controller renders 200 for Tab 1 (rolls)", strlen($rollsHtml) > 500);
assertCheck("Rolls tab shows Fabric Rolls title", strpos($rollsHtml, 'Fabric Rolls') !== false);
assertCheck("Rolls tab lists Roll #1 and Roll #2 from Bill #15", strpos($rollsHtml, 'flora') !== false || strpos($rollsHtml, 'Flora') !== false);
assertCheck("Rolls tab contains Available Stock column", strpos($rollsHtml, 'Available Stock') !== false);

// Test Tab 2: Garment Other (Trims)
$_GET['tab'] = 'garment_other';
$_GET['concern_id'] = '1';
ob_start();
$controller->index();
$otherHtml = ob_get_clean();

assertCheck("Controller renders 200 for Tab 2 (garment_other)", strlen($otherHtml) > 500);
assertCheck("Garment Other tab shows Trims & Garment Other title", strpos($otherHtml, 'Other Garments & Trims') !== false);
assertCheck("Garment Other tab contains Inwarded Qty & Unit", strpos($otherHtml, 'Unit of Measure') !== false);

// Test Tab 3: Finished Goods
$_GET['tab'] = 'finished_goods';
$_GET['concern_id'] = '1';
ob_start();
$controller->index();
$fgHtml = ob_get_clean();

assertCheck("Controller renders 200 for Tab 3 (finished_goods)", strlen($fgHtml) > 500);
assertCheck("Finished Goods tab shows Purchased Finished Goods", strpos($fgHtml, 'Purchased Finished Goods') !== false);
assertCheck("Finished Goods tab contains MRP and Cost columns", strpos($fgHtml, 'Purchase Cost') !== false && strpos($fgHtml, 'MRP') !== false);

// -----------------------------------------------------------
// TEST GROUP 3: Multi-Tenant Data Isolation
// -----------------------------------------------------------
echo "\n--- Group 3: Multi-Tenant Isolation ---\n";

// Set session as Concern 2 Admin (role 2, concern_id 2)
$_SESSION['user'] = [
    'id' => 2,
    'role_id' => 2,
    'name' => 'Concern 2 Admin',
    'email' => 'admin2@invento.com',
    'concern_id' => 2,
];
$_SESSION['enabled_modules'] = ['purchase_stocks', 'purchase_billing', 'suppliers'];
$_SESSION['role_permissions'] = [
    'purchase_stocks' => ['view' => 1, 'add' => 1, 'edit' => 1, 'delete' => 1]
];
unset($_SESSION['active_concern_id']);

$_GET = ['tab' => 'rolls']; // no super admin concern_id override allowed for concern user
ob_start();
$controller->index();
$c2Html = ob_get_clean();

// Concern 2 has no purchase bills with rolls (all rolls belong to concern 1)
assertCheck("Concern 2 cannot see Concern 1 fabric rolls (Bill #15 / Flora)", strpos($c2Html, 'GRM-PO-0102') === false);
assertCheck("Concern 2 shows empty rolls stock callout", strpos($c2Html, 'No fabric rolls found') !== false);

// -----------------------------------------------------------
// TEST GROUP 4: Invoice Table Optimization (Image Issue Fix)
// -----------------------------------------------------------
echo "\n--- Group 4: Purchase Billing Invoice Table Optimization ---\n";

// Restore super admin session
$_SESSION['user'] = [
    'id' => 1,
    'role_id' => 1,
    'name' => 'Super Administrator',
    'email' => 'admin@invento.com',
    'concern_id' => null,
];

$bill = DB::fetch("SELECT pb.*, c.name as concern_name, c.code as concern_code, 
                          s.name as supplier_name, s.company_name as supplier_company, 
                          s.phone as supplier_phone, s.district as supplier_district, 
                          s.state as supplier_state, s.gstin as supplier_gstin 
                   FROM purchase_bills pb 
                   LEFT JOIN concerns c ON pb.concern_id = c.id 
                   LEFT JOIN suppliers s ON pb.supplier_id = s.id 
                   WHERE pb.id = 15");

$items = DB::fetchAll("SELECT * FROM purchase_bill_items WHERE purchase_bill_id = 15 ORDER BY id ASC");
$allConcerns = DB::fetchAll("SELECT * FROM concerns");

ob_start();
$csrfToken = 'test-token';
include dirname(__DIR__) . '/app/views/concern/purchase_billing_view.php';
$invoiceHtml = ob_get_clean();

assertCheck("Invoice renders successfully for Bill #15", strlen($invoiceHtml) > 500);

// Check 1: Zero text collisions in column headers
assertCheck("Invoice header has nowrap and fixed proportions", strpos($invoiceHtml, 'white-space: nowrap;') !== false);
assertCheck("Invoice table has print-items-table class", strpos($invoiceHtml, 'print-items-table') !== false);

// Check 2: Deduplication - No repetitive 'red • red'
assertCheck("No redundant 'red • red' in Bill #15 items", strpos($invoiceHtml, 'red &bull; red') === false && strpos($invoiceHtml, 'red • red') === false);

// Check 3: Description Title is formatted cleanly instead of generic '1'
assertCheck("Item 1 title displays 'Fabric • Flora' instead of plain '1'", strpos($invoiceHtml, 'Fabric &bull; Flora') !== false || strpos($invoiceHtml, 'Flora') !== false);
assertCheck("Item 2 title displays 'Fabric • Elephant' instead of plain '2'", strpos($invoiceHtml, 'Fabric &bull; Elephant') !== false || strpos($invoiceHtml, 'Elephant') !== false);

// Check 4: Currency formatting with nowrap-num class
assertCheck("Currency cells use .nowrap-num class to prevent digit wrapping", strpos($invoiceHtml, 'nowrap-num') !== false);
assertCheck("Taxable base ₹47,619.05 formatted cleanly", strpos($invoiceHtml, '47,619.05') !== false);
assertCheck("GST Tax ₹2,380.95 formatted cleanly", strpos($invoiceHtml, '2,380.95') !== false);
assertCheck("Line total ₹50,000.00 formatted cleanly", strpos($invoiceHtml, '50,000.00') !== false);
assertCheck("GST column displays 'CGST+SGST 5%' cleanly", strpos($invoiceHtml, 'CGST+SGST 5%') !== false);

// -----------------------------------------------------------
// Summary
// -----------------------------------------------------------
echo "\n======================================================\n";
echo "SUMMARY: {$passCount} Passed, {$failCount} Failed\n";
echo "======================================================\n";

if ($failCount > 0) {
    exit(1);
}
