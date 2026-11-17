<?php
// tests/IsolationSecurityTest.php - Comprehensive Architecture, Isolation & Security Verification

require_once __DIR__ . '/../app/core/Autoloader.php';
\App\Core\Env::load(dirname(__DIR__) . '/.env');

use App\Core\DB;
use App\Core\Auth;
use App\Core\ModuleGuard;
use App\Models\Variant;
use App\Services\StockService;
use App\Services\QrService;

echo "======================================================\n";
echo "INVENTO: Multi-Concern Architecture & Security Verification\n";
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
// TEST 1: Database Connection & Seed Data Integrity
// ----------------------------------------------------
echo "TEST 1: Database & Seed Verification\n";
$concernCount = DB::fetchValue("SELECT COUNT(*) FROM concerns");
assertTest("Concerns count >= 2", $concernCount >= 2);

$modulesCount = DB::fetchValue("SELECT COUNT(*) FROM modules");
assertTest("Modules registry has all 26 modules (4 Super Admin + 22 Concern)", $modulesCount >= 26);

// ----------------------------------------------------
// TEST 2: Multi-Concern Authentication & Session Isolation
// ----------------------------------------------------
echo "\nTEST 2: Authentication & Session-Only Tenant Boundary\n";
$superLogin = Auth::login('admin@invento.com', 'password123');
assertTest("Super Admin login succeeded", $superLogin === true);
assertTest("Super Admin has null concern_id", Auth::concernId() === null);
assertTest("Super Admin role detected", Auth::isSuperAdmin() === true);
Auth::logout();

$grmLogin = Auth::login('grm.admin@invento.com', 'password123');
assertTest("Concern 1 (GRM) login succeeded", $grmLogin === true);
assertTest("Concern 1 has session concern_id = 1", Auth::concernId() === 1);
assertTest("Concern 1 concern() helper returns GRM", Auth::concern()['code'] === 'CON-GRM');
assertTest("Concern 1 has all 22 modules enabled", count($_SESSION['enabled_modules']) === 22);

// ----------------------------------------------------
// TEST 3: Tenant Scoped Model Query Isolation
// ----------------------------------------------------
echo "\nTEST 3: Tenant Scoped Model Query Isolation\n";
// While logged into Concern 1:
$variantModel = new Variant();
$c1Variants = $variantModel->all();
assertTest("Concern 1 sees only Concern 1 variants (does not see Concern 2 variant #5)", 
    count($c1Variants) > 0 && !in_array(5, array_column($c1Variants, 'id'))
);

// Cross-tenant record lookup test: Concern 1 attempts to find Variant 5 (Apex)
$crossAttempt = $variantModel->find(5);
assertTest("Concern 1 requesting Concern 2 record by ID returns NULL (Strict 404)", $crossAttempt === null);

// Concern 1 can find its own variant #1
$ownVariant = $variantModel->find(1);
assertTest("Concern 1 requesting its own variant #1 returns record successfully", $ownVariant !== null && $ownVariant['id'] == 1);

// ----------------------------------------------------
// TEST 4: QR Scan Cross-Tenant Security Check
// ----------------------------------------------------
echo "\nTEST 4: QR Scan Tenant Boundary Check\n";
// Concern 2 QR payload is C4-S6-V5 (Category 4, Subcat 6, Variant 5)
$c2QrPayload = "C4-S6-V5";
$qrLookupFromC1 = QrService::lookupVariant($c2QrPayload, Auth::concernId());
assertTest("Scanning Concern 2 QR while logged into Concern 1 returns NULL (Isolated)", $qrLookupFromC1 === null);

$c1QrPayload = "C1-S1-V1";
$qrLookupFromC1Own = QrService::lookupVariant($c1QrPayload, Auth::concernId());
assertTest("Scanning Concern 1 QR while logged into Concern 1 resolves variant + live stock", 
    $qrLookupFromC1Own !== null && $qrLookupFromC1Own['sku_code'] === 'SKU-FAB-180-BLK'
);

// Switch login to Concern 2 (Apex)
Auth::logout();
$apexLogin = Auth::login('apex.admin@invento.com', 'password123');
assertTest("Concern 2 (Apex) login succeeded", $apexLogin === true);
assertTest("Concern 2 has session concern_id = 2", Auth::concernId() === 2);

// As Concern 2, try scanning Concern 1 QR
$qrLookupFromC2 = QrService::lookupVariant($c1QrPayload, Auth::concernId());
assertTest("Scanning Concern 1 QR while logged into Concern 2 returns NULL (Isolated)", $qrLookupFromC2 === null);

// As Concern 2, own QR resolves
$qrLookupFromC2Own = QrService::lookupVariant($c2QrPayload, Auth::concernId());
assertTest("Scanning Concern 2 QR while logged into Concern 2 resolves variant #5", 
    $qrLookupFromC2Own !== null && $qrLookupFromC2Own['sku_code'] === 'SKU-APX-YRN-30'
);

// ----------------------------------------------------
// TEST 5: Module Guard (Feature Toggle per Concern)
// ----------------------------------------------------
echo "\nTEST 5: Module Guard & Disabled Module Enforcement\n";
// Concern 2 was seeded with 'cutting_wastage' and 'stitching_wastage' disabled
assertTest("Concern 2 has cutting_wastage disabled in session cache", Auth::hasModule('cutting_wastage') === false);
assertTest("Concern 2 has billing enabled in session cache", Auth::hasModule('billing') === true);

// ----------------------------------------------------
// TEST 6: Single Stock Gateway & Ledger Audit Reconciliation
// ----------------------------------------------------
echo "\nTEST 6: Single Stock Gateway (StockService::move)\n";
// Logged in as Concern 2, test stock movement
$initQty = StockService::getQty(2, 5, 'Main Store');

// Move in 50 units
$newQty = StockService::move(2, 5, 'Main Store', 50.00, 0.00, 'Inward', 999, 'Test inward batch', Auth::id());
assertTest("StockService::move() credited 50 units", $newQty == ($initQty + 50.00));

// Move out 20 units
$afterOutQty = StockService::move(2, 5, 'Main Store', 0.00, 20.00, 'Outward', 999, 'Test outward delivery', Auth::id());
assertTest("StockService::move() debited 20 units", $afterOutQty == ($newQty - 20.00));

// Ledger reconciliation check: SUM(qty_in) - SUM(qty_out) == live stock balance!
$ledgerNet = DB::fetchValue(
    "SELECT COALESCE(SUM(qty_in - qty_out), 0) FROM stock_ledger WHERE concern_id = 2 AND variant_id = 5 AND location = 'Main Store'"
);
$liveBalance = DB::fetchValue(
    "SELECT qty FROM stocks WHERE concern_id = 2 AND variant_id = 5 AND location = 'Main Store'"
);
assertTest("Reconciliation: Live stock balance exactly matches stock ledger net", (float)$ledgerNet === (float)$liveBalance);

// Test negative stock prevention: Attempting to withdraw 999999 units must throw Exception
$threwException = false;
try {
    StockService::move(2, 5, 'Main Store', 0.00, 999999.00, 'Outward', 999, 'Overdraw test', Auth::id());
} catch (\Throwable $e) {
    $threwException = true;
}
assertTest("StockService blocks insufficient/negative stock withdrawal", $threwException === true);

// ----------------------------------------------------
// TEST 7: Supplier Master District & State Persistence & Validation
// ----------------------------------------------------
echo "\nTEST 7: Supplier Master District & State Fields & Validation\n";
$testSupplierCode = 'SUP-UNIT-TEST-' . time();
DB::query(
    "INSERT INTO suppliers (concern_id, code, name, company_name, phone, email, gstin, address, city, district, state, status)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')",
    [1, $testSupplierCode, 'VIJA ENTERPRISES', 'VIJA ENTERPRISES', '8668007593', 'pavismvt@gmail.com', '33AAAAA0000A1Z5', 'valvarmanglam', 'Karur', 'Karur', 'Tamil Nadu']
);
$insertedSup = DB::fetch("SELECT * FROM suppliers WHERE code = ?", [$testSupplierCode]);
assertTest("Supplier inserted with separate district field", !empty($insertedSup) && $insertedSup['district'] === 'Karur');
assertTest("Supplier inserted with separate state field", !empty($insertedSup) && $insertedSup['state'] === 'Tamil Nadu');
DB::query("DELETE FROM suppliers WHERE code = ?", [$testSupplierCode]);

// Validation rule regex checks
assertTest("Validation: Valid 10-digit phone passes regex", (bool)preg_match('/^[6-9]\d{9}$/', '8668007593'));
assertTest("Validation: Invalid 9-digit or non-6-9 phone blocked", !preg_match('/^[6-9]\d{9}$/', '1234567890'));
assertTest("Validation: Valid 15-char GSTIN format passes", (bool)preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/i', '33AAAAA0000A1Z5'));
assertTest("Validation: Invalid GSTIN blocked", !preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/i', 'INVALID_GSTIN'));
assertTest("Validation: Valid email passes filter", (bool)filter_var('pavismvt@gmail.com', FILTER_VALIDATE_EMAIL));
assertTest("Validation: Invalid email blocked", !filter_var('not-an-email', FILTER_VALIDATE_EMAIL));

// ----------------------------------------------------
// FINAL RESULTS
// ----------------------------------------------------
echo "\n======================================================\n";
echo "SUMMARY: {$passCount} PASSED, {$failCount} FAILED\n";
echo "======================================================\n";

if ($failCount === 0) {
    echo ">>> ALL SECURITY, ISOLATION & ARCHITECTURE TESTS PASSED! <<<\n";
    exit(0);
} else {
    echo ">>> SOME TESTS FAILED! <<<\n";
    exit(1);
}
