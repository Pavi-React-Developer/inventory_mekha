<?php
// tests/RollDesignViewAndCreateTest.php
session_start();

require_once __DIR__ . '/../app/core/Autoloader.php';
\App\Core\Env::load(dirname(__DIR__) . '/.env');

use App\Core\DB;
use App\Core\Auth;
use App\Core\Router;
use App\Services\RollRegistryService;
use App\Controllers\PurchaseController;

echo "======================================================\n";
echo "INVENTO: Roll Design Dropdown - View & Create Option Test\n";
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

// Ensure database table exists
RollRegistryService::initTable();

// Setup clean test concern
$testCid = 1;

// -----------------------------------------------------------
// TEST GROUP 1: Service - createOrUpdateSpec and getSavedSpecs
// -----------------------------------------------------------
echo "--- Group 1: Service - createOrUpdateSpec & getSavedSpecs ---\n";

$testPattern = "FLORAL TEST " . rand(100, 999);
$testColor = "CRIMSON " . rand(100, 999);
$testPrice = 450.50;
$testHsn = "5208";

$createRes = RollRegistryService::createOrUpdateSpec(
    $testCid,
    $testColor,
    $testPattern,
    $testPrice,
    $testHsn,
    0.0,
    0.0,
    "Test Mill"
);

assertCheck("createOrUpdateSpec succeeds", $createRes['success'] === true);
assertCheck("createOrUpdateSpec action is 'created'", ($createRes['action'] ?? '') === 'created');
assertCheck("Created spec has correct design_name", ($createRes['spec']['design_name'] ?? '') === strtoupper($testPattern));
assertCheck("Created spec has correct color", ($createRes['spec']['color'] ?? '') === strtoupper($testColor));
assertCheck("Created spec has correct price", (float)($createRes['spec']['last_unit_price'] ?? 0) === $testPrice);
assertCheck("Created spec has correct HSN", ($createRes['spec']['hsn_code'] ?? '') === $testHsn);

// Test update existing spec
$updateRes = RollRegistryService::createOrUpdateSpec(
    $testCid,
    $testColor,
    $testPattern,
    520.00,
    "5209"
);
assertCheck("createOrUpdateSpec updates existing on duplicate without error", $updateRes['success'] === true);
assertCheck("createOrUpdateSpec action is 'updated'", ($updateRes['action'] ?? '') === 'updated');
assertCheck("Updated price is reflected", (float)($updateRes['spec']['last_unit_price'] ?? 0) === 520.00);
assertCheck("Updated HSN is reflected", ($updateRes['spec']['hsn_code'] ?? '') === "5209");

// Test validation
$failRes = RollRegistryService::createOrUpdateSpec($testCid, '', '');
assertCheck("createOrUpdateSpec rejects empty color and design", $failRes['success'] === false);

// Test getSavedSpecs contains id and supplier
$savedSpecs = RollRegistryService::getSavedSpecs($testCid);
assertCheck("getSavedSpecs returns array", is_array($savedSpecs) && count($savedSpecs) > 0);
$foundCreated = false;
foreach ($savedSpecs as $s) {
    if ($s['design_name'] === strtoupper($testPattern) && $s['color'] === strtoupper($testColor)) {
        $foundCreated = true;
        assertCheck("Saved spec includes id", isset($s['id']));
        assertCheck("Saved spec includes last_supplier_name key", array_key_exists('last_supplier_name', $s));
        break;
    }
}
assertCheck("getSavedSpecs contains newly created test spec", $foundCreated);


// -----------------------------------------------------------
// TEST GROUP 2: Route & Controller Endpoint
// -----------------------------------------------------------
echo "\n--- Group 2: Route & Controller Endpoint ---\n";

$router = new Router();
require __DIR__ . '/../app/routes.php';

// Check route exists in routes list
$matchedSaveRoute = false;
$routes = $router->getRoutes();
foreach ($routes as $route) {
    if ($route['pattern'] === '#^/m/purchase_billing/save_roll_spec$#' && $route['method'] === 'POST') {
        $matchedSaveRoute = true;
        assertCheck("Route POST /m/purchase_billing/save_roll_spec is registered", true);
        assertCheck("Controller is PurchaseController", $route['handler'][0] === PurchaseController::class);
        assertCheck("Method is saveRollSpecAjax", $route['handler'][1] === 'saveRollSpecAjax');
        break;
    }
}
if (!$matchedSaveRoute) {
    assertCheck("Route POST /m/purchase_billing/save_roll_spec is registered", false);
}


// -----------------------------------------------------------
// TEST GROUP 3: View Template - Dropdown, View & Create Modals
// -----------------------------------------------------------
echo "\n--- Group 3: View Template UI & JavaScript ---\n";

$viewFile = dirname(__DIR__) . '/app/views/concern/purchase_billing_create.php';
$viewContent = file_get_contents($viewFile);

assertCheck("View contains quick select container", strpos($viewContent, 'roll-spec-selector-bar') !== false);
assertCheck("View contains '-- Choose from Saved Color & Design Registry --'", strpos($viewContent, '-- Choose from Saved Color & Design Registry --') !== false);
assertCheck("View dropdown contains '+ Create New Color & Design...' option", strpos($viewContent, '➕ + Create New Color & Design...') !== false);
assertCheck("View dropdown contains 'View All Saved Registry...' option", strpos($viewContent, '👁️ View All Saved Registry') !== false);
assertCheck("View contains View button with viewCurrentRollSpec()", strpos($viewContent, 'viewCurrentRollSpec') !== false);
assertCheck("View contains New button with openCreateDesignModal()", strpos($viewContent, 'openCreateDesignModal') !== false);

// Modals
assertCheck("View contains #modalCreateRollDesign", strpos($viewContent, 'id="modalCreateRollDesign"') !== false);
assertCheck("Create modal contains design pattern input", strpos($viewContent, 'id="newDesignPattern"') !== false);
assertCheck("Create modal contains color shade input", strpos($viewContent, 'id="newDesignColor"') !== false);
assertCheck("Create modal contains price input", strpos($viewContent, 'id="newDesignPrice"') !== false);
assertCheck("Create modal contains HSN input", strpos($viewContent, 'id="newDesignHsn"') !== false);

assertCheck("View contains #modalViewRollSpecs", strpos($viewContent, 'id="modalViewRollSpecs"') !== false);
assertCheck("View modal contains #viewSpecDetailCard", strpos($viewContent, 'id="viewSpecDetailCard"') !== false);
assertCheck("View modal contains registry table #tableRegistrySpecs", strpos($viewContent, 'id="tableRegistrySpecs"') !== false);
assertCheck("View modal contains search filter input #vSpecSearchInput", strpos($viewContent, 'id="vSpecSearchInput"') !== false);

// JS logic
assertCheck("JS contains saveAndApplyRollDesign()", strpos($viewContent, 'function saveAndApplyRollDesign()') !== false);
assertCheck("JS contains getSavedRollSpecsOptionsHtml()", strpos($viewContent, 'function getSavedRollSpecsOptionsHtml(') !== false);
assertCheck("JS contains applySavedRollSpec()", strpos($viewContent, 'function applySavedRollSpec(') !== false);
assertCheck("JS contains updateAllRollQuickSelects()", strpos($viewContent, 'function updateAllRollQuickSelects(') !== false);
assertCheck("JS contains selectSpecFromRegistry()", strpos($viewContent, 'function selectSpecFromRegistry(') !== false);
assertCheck("JS handles __NEW__ option selection", strpos($viewContent, "val === '__NEW__'") !== false);
assertCheck("JS handles __VIEW_ALL__ option selection", strpos($viewContent, "val === '__VIEW_ALL__'") !== false);

// -----------------------------------------------------------
// TEST SUMMARY
// -----------------------------------------------------------
echo "\n======================================================\n";
echo "TEST RESULTS: {$passCount} PASSED, {$failCount} FAILED\n";
echo "======================================================\n";

if ($failCount > 0) {
    exit(1);
}
exit(0);
