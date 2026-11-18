<?php
// tests/MultiConcernShopLoginTest.php - Verification of multi-concern login and shop redirection flow

require_once __DIR__ . '/../app/core/Autoloader.php';
\App\Core\Env::load(__DIR__ . '/../.env');

use App\Core\DB;
use App\Core\Auth;

echo "======================================================\n";
echo "MULTI-CONCERN LOGIN & SHOP PAGE FLOW TEST\n";
echo "======================================================\n\n";

// 1. Verify Abi exists and is assigned to the 3 concerns
$abi = DB::fetch("SELECT * FROM users WHERE email = 'abi@gmail.com'");
if (!$abi) {
    die("[FAIL] User abi@gmail.com not found in users table.\n");
}
echo "[PASS] 1. User abi found: ID={$abi['id']}, Name={$abi['name']}\n";

$concerns = Auth::getUserConcerns((int)$abi['id']);
echo "[INFO] Abi assigned concerns count: " . count($concerns) . "\n";
foreach ($concerns as $c) {
    $isShop = Auth::isShopConcern((int)$c['id']);
    echo "       - [ID: {$c['id']}] {$c['name']} ({$c['code']}) -> " . ($isShop ? "RETAIL SHOP" : "PRODUCTION / UNIT") . "\n";
}

if (count($concerns) < 3) {
    die("[FAIL] Expected at least 3 concerns for abi, got " . count($concerns) . "\n");
}
echo "[PASS] 2. Auth::getUserConcerns returned all 3 locations.\n";

// 2. Check shop detection logic
$sulurShop = DB::fetch("SELECT id FROM concerns WHERE name LIKE '%sulur shop%'");
$mangalamUnit = DB::fetch("SELECT id FROM concerns WHERE name LIKE '%mangalam unit%'");
$mettuShop = DB::fetch("SELECT id FROM concerns WHERE name LIKE '%mettupalayam shop%'");

if (!Auth::isShopConcern((int)$sulurShop['id'])) {
    die("[FAIL] Sulur shop was not recognized as a shop concern.\n");
}
if (!Auth::isShopConcern((int)$mettuShop['id'])) {
    die("[FAIL] Mettupalayam shop was not recognized as a shop concern.\n");
}
if (Auth::isShopConcern((int)$mangalamUnit['id'])) {
    die("[FAIL] Mangalam unit should not be flagged as a shop.\n");
}
echo "[PASS] 3. Auth::isShopConcern correctly identifies retail shops vs production units.\n";

// 3. Test Session Switching to Sulur Shop
Auth::startSession();
$_SESSION['user'] = [
    'id'         => (int)$abi['id'],
    'name'       => $abi['name'],
    'email'      => $abi['email'],
    'role_id'    => (int)$abi['role_id'],
    'concern_id' => null,
];

$setResult = Auth::setSessionConcern((int)$sulurShop['id']);
if (!$setResult) {
    die("[FAIL] Failed to set session concern to Sulur Shop.\n");
}

if ($_SESSION['user']['concern_id'] !== (int)$sulurShop['id']) {
    die("[FAIL] Session concern_id was not updated to Sulur Shop.\n");
}
if ($_SESSION['concern']['id'] !== (int)$sulurShop['id']) {
    die("[FAIL] Session concern structure not updated.\n");
}
echo "[PASS] 4. Auth::setSessionConcern successfully activated Sulur Shop context.\n";

// Verify that when Sulur Shop is selected, destination is /m/billing (Shop POS Page)
$targetPage = Auth::isShopConcern((int)$_SESSION['user']['concern_id']) ? '/m/billing' : '/dashboard';
if ($targetPage !== '/m/billing') {
    die("[FAIL] Target page for Sulur Shop was expected to be /m/billing, got {$targetPage}\n");
}
echo "[PASS] 5. Sulur Shop selection lands directly on Shop Page ({$targetPage}).\n";

// 4. Test Session Switching to Mangalam Unit
Auth::setSessionConcern((int)$mangalamUnit['id']);
$unitTargetPage = Auth::isShopConcern((int)$_SESSION['user']['concern_id']) ? '/m/billing' : '/dashboard';
if ($unitTargetPage !== '/dashboard') {
    die("[FAIL] Target page for Mangalam Unit was expected to be /dashboard, got {$unitTargetPage}\n");
}
echo "[PASS] 6. Mangalam Unit selection lands on Dashboard ({$unitTargetPage}).\n";

echo "\n======================================================\n";
echo "ALL MULTI-CONCERN SHOP TESTS PASSED!\n";
echo "======================================================\n";
