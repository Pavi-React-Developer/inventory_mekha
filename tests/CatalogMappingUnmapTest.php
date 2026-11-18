<?php
// tests/CatalogMappingUnmapTest.php
require_once __DIR__ . '/../app/core/Env.php';
\App\Core\Env::load(__DIR__ . '/../.env');
require_once __DIR__ . '/../app/core/DB.php';
require_once __DIR__ . '/../app/core/Auth.php';
require_once __DIR__ . '/../app/services/AuditLog.php';

use App\Core\DB;
use App\Core\Auth;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Log in as Super Admin for testing
$_SESSION['user'] = [
    'id' => 1,
    'concern_id' => 1,
    'name' => 'Super Administrator',
    'email' => 'superadmin@inventomekha.com',
    'is_super_admin' => 1
];

echo "======================================================\n";
echo "INVENTO: Catalog Mapping & Unmap Verification Test\n";
echo "======================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertCondition($desc, $cond) {
    global $passCount, $failCount;
    if ($cond) {
        echo "  [PASS] {$desc}\n";
        $passCount++;
    } else {
        echo "  [FAIL] {$desc}\n";
        $failCount++;
    }
}

// 1. Create a dummy bill and finished good item
$billId = DB::fetchValue("SELECT id FROM purchase_bills LIMIT 1");
if (!$billId) {
    DB::query("INSERT INTO purchase_bills (concern_id, bill_no, supplier_id, bill_date) VALUES (1, 'BILL-UNMAP-TEST', 1, '2026-11-18')");
    $billId = DB::lastInsertId();
}

$testTag = 'FG-UNMAP-TEST-' . time();
DB::query("INSERT INTO purchase_bill_items (
    concern_id, purchase_bill_id, section_type, item_name, item_code, qty, unit, unit_price, total_price
) VALUES (1, ?, 'finished_goods', 'TEST UNMAP SHIRT', ?, 25.00, 'Pieces', 200.00, 5000.00)", [$billId, $testTag]);
$testItemId = (int)DB::lastInsertId();

assertCondition("Created test finished good item (ID: {$testItemId})", $testItemId > 0);

// Check initial status: should be unmapped
$item = DB::fetch("SELECT * FROM purchase_bill_items WHERE id = ?", [$testItemId]);
assertCondition("Initial item has catalog_id = NULL", $item['catalog_id'] === null);
assertCondition("Initial item has variant_id = NULL", $item['variant_id'] === null);

// 2. Map the item to a catalog style and variant
$catId = DB::fetchValue("SELECT id FROM catalogs WHERE concern_id = 1 LIMIT 1");
$varId = DB::fetchValue("SELECT id FROM variants WHERE concern_id = 1 LIMIT 1");

DB::query("UPDATE purchase_bill_items SET catalog_id = ?, variant_id = ? WHERE id = ?", [$catId, $varId, $testItemId]);
DB::query("INSERT INTO catalog_mappings (concern_id, purchase_bill_item_id, catalog_id, variant_id, status, created_at)
           VALUES (1, ?, ?, ?, 'mapped', NOW())", [$testItemId, $catId, $varId]);
$mappingId = (int)DB::lastInsertId();

assertCondition("Mapped item to catalog #{$catId} and variant #{$varId} (Mapping ID: {$mappingId})", $mappingId > 0);

// Verify item is mapped in DB
$mappedItem = DB::fetch("SELECT * FROM purchase_bill_items WHERE id = ?", [$testItemId]);
assertCondition("Mapped item has catalog_id #{$catId}", (int)$mappedItem['catalog_id'] === (int)$catId);
assertCondition("Mapped item has variant_id #{$varId}", (int)$mappedItem['variant_id'] === (int)$varId);

// 3. Perform Unmap (mimicking unmapItem logic)
DB::beginTransaction();
DB::query("DELETE FROM catalog_mappings WHERE purchase_bill_item_id = ?", [$testItemId]);
DB::query("UPDATE purchase_bill_items SET catalog_id = NULL, variant_id = NULL WHERE id = ?", [$testItemId]);
App\Services\AuditLog::log('DELETE', 'catalog_mappings', $testItemId, null, ['action' => 'unmap']);
DB::commit();

// 4. Verify DB state after Unmap
$unmappedItem = DB::fetch("SELECT * FROM purchase_bill_items WHERE id = ?", [$testItemId]);
assertCondition("After unmap: catalog_id is NULL", $unmappedItem['catalog_id'] === null);
assertCondition("After unmap: variant_id is NULL", $unmappedItem['variant_id'] === null);

$mappingExists = DB::fetch("SELECT id FROM catalog_mappings WHERE purchase_bill_item_id = ?", [$testItemId]);
assertCondition("After unmap: catalog_mappings record is deleted", empty($mappingExists));

// 5. Verify Controller query logic calculates is_mapped = false
$sql = "SELECT pbi.id, pbi.catalog_id, pbi.variant_id,
               cm.id AS mapping_id, cm.status AS mapping_status,
               c.name AS catalog_name
        FROM purchase_bill_items pbi
        LEFT JOIN catalog_mappings cm ON cm.purchase_bill_item_id = pbi.id
        LEFT JOIN catalogs c ON COALESCE(cm.catalog_id, pbi.catalog_id) = c.id
        WHERE pbi.id = ?";
$row = DB::fetch($sql, [$testItemId]);

$isMapped = (!empty($row['mapping_id']) && $row['mapping_status'] === 'mapped') 
            || (!empty($row['catalog_id']) && !empty($row['variant_id']));
if (empty($row['mapping_id']) && empty($row['catalog_id'])) {
    $isMapped = false;
}

assertCondition("Controller evaluation: is_mapped is strictly FALSE", $isMapped === false);

// 6. Test Unmapped filter query
$unmappedFilterRows = DB::fetchAll("SELECT pbi.id FROM purchase_bill_items pbi 
                                    LEFT JOIN catalog_mappings cm ON cm.purchase_bill_item_id = pbi.id 
                                    WHERE pbi.id = ? 
                                      AND (cm.id IS NULL OR cm.status = 'unmapped') 
                                      AND (pbi.catalog_id IS NULL AND pbi.variant_id IS NULL)", [$testItemId]);
assertCondition("Unmapped filter SQL correctly captures this item", count($unmappedFilterRows) === 1);

// Clean up test record
DB::query("DELETE FROM purchase_bill_items WHERE id = ?", [$testItemId]);
echo "\n======================================================\n";
echo "TEST RESULTS: Total = " . ($passCount + $failCount) . " | Passed = {$passCount} | Failed = {$failCount}\n";
echo "======================================================\n";
if ($failCount === 0) {
    echo ">>> ALL CATALOG UNMAP TESTS PASSED! <<<\n";
}
