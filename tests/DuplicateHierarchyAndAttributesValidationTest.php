<?php
// tests/DuplicateHierarchyAndAttributesValidationTest.php

require_once __DIR__ . '/../app/core/Autoloader.php';
\App\Core\Env::load(dirname(__DIR__) . '/.env');

use App\Core\DB;

echo "======================================================================\n";
echo "TEST SUITE: Category, Subcategory, Sub-Subcategory & Attribute Validations\n";
echo "======================================================================\n\n";

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

function runPhpSnippet(string $code): ?array {
    $bootstrap = dirname(__DIR__) . '/app/core/Autoloader.php';
    $envPath = dirname(__DIR__) . '/.env';
    
    $fullScript = "<?php
require_once '$bootstrap';
\App\Core\Env::load('$envPath');
use App\Core\DB;
use App\Core\Auth;
use App\Controllers\ProductionController;
use App\Controllers\CatalogMappingController;

if (session_status() === PHP_SESSION_NONE) { session_start(); }
\$_SESSION['user_id'] = 1;
\$_SESSION['concern_id'] = 1;
\$_SESSION['role'] = 'super_admin';
\$_SESSION['csrf_token'] = 'test_token';
\$_SERVER['REQUEST_METHOD'] = 'POST';

$code
";
    $tmpFile = tempnam(sys_get_temp_dir(), 'test_val_') . '.php';
    file_put_contents($tmpFile, $fullScript);
    $output = shell_exec("php " . escapeshellarg($tmpFile));
    @unlink($tmpFile);

    // Find JSON in output
    if (preg_match('/\{.*\}/s', $output, $m)) {
        return json_decode($m[0], true);
    }
    return ['raw_output' => $output];
}

// Ensure test baseline exists in database
$existingCat = DB::fetch("SELECT * FROM categories WHERE LOWER(TRIM(name)) = 'women'");
if (!$existingCat) {
    DB::query("INSERT INTO categories (concern_id, name, code, default_hsn, default_gst_rate, status) VALUES (1, 'Women', 'W', '6109', 5.00, 'active')");
    $existingCat = DB::fetch("SELECT * FROM categories WHERE LOWER(TRIM(name)) = 'women'");
}
$catId = (int)$existingCat['id'];

$existingSub = DB::fetch("SELECT * FROM subcategories WHERE category_id = ? AND LOWER(TRIM(name)) = 'maternity'", [$catId]);
if (!$existingSub) {
    DB::query("INSERT INTO subcategories (concern_id, category_id, name, code, status) VALUES (1, ?, 'Maternity', 'M', 'active')", [$catId]);
    $existingSub = DB::fetch("SELECT * FROM subcategories WHERE category_id = ? AND LOWER(TRIM(name)) = 'maternity'", [$catId]);
}
$subId = (int)$existingSub['id'];

$existingSubSub = DB::fetch("SELECT * FROM sub_subcategories WHERE subcategory_id = ? AND LOWER(TRIM(name)) = 'normal sleeve'", [$subId]);
if (!$existingSubSub) {
    DB::query("INSERT INTO sub_subcategories (concern_id, subcategory_id, name, code, status) VALUES (1, ?, 'Normal sleeve', 'NS', 'active')", [$subId]);
    $existingSubSub = DB::fetch("SELECT * FROM sub_subcategories WHERE subcategory_id = ? AND LOWER(TRIM(name)) = 'normal sleeve'", [$subId]);
}

$existingAttr = DB::fetch("SELECT * FROM product_attributes WHERE attribute_type = 'size' AND LOWER(TRIM(attribute_value)) = '4xl'");
if (!$existingAttr) {
    DB::query("INSERT INTO product_attributes (concern_id, attribute_type, attribute_name, attribute_value, sort_order) VALUES (1, 'size', 'Size', '4XL', 10)");
}

// ----------------------------------------------------
// TEST 1: ProductionController::quickAddCategoryAjax duplication check
// ----------------------------------------------------
echo "--- 1. Testing ProductionController::quickAddCategoryAjax Duplication ---\n";

$res1 = runPhpSnippet('
    $_POST = ["name" => "women", "code" => "W"];
    $c = new ProductionController();
    $c->quickAddCategoryAjax();
');
assertTest("Duplicate Category 'women' rejected", isset($res1['success']) && $res1['success'] === false);
assertTest("Error message contains 'already exists'", strpos($res1['message'] ?? '', 'already exists') !== false);

$res1Upper = runPhpSnippet('
    $_POST = ["name" => "WOMEN", "code" => "W"];
    $c = new ProductionController();
    $c->quickAddCategoryAjax();
');
assertTest("Duplicate Category 'WOMEN' rejected", isset($res1Upper['success']) && $res1Upper['success'] === false);

$res1Trim = runPhpSnippet('
    $_POST = ["name" => "  Women  ", "code" => "W"];
    $c = new ProductionController();
    $c->quickAddCategoryAjax();
');
assertTest("Duplicate Category '  Women  ' rejected", isset($res1Trim['success']) && $res1Trim['success'] === false);

// ----------------------------------------------------
// TEST 2: ProductionController::quickAddSubcategoryAjax duplication check
// ----------------------------------------------------
echo "\n--- 2. Testing ProductionController::quickAddSubcategoryAjax Duplication ---\n";

$res2 = runPhpSnippet('
    $_POST = ["category_id" => ' . $catId . ', "name" => "maternity", "code" => "M"];
    $c = new ProductionController();
    $c->quickAddSubcategoryAjax();
');
assertTest("Duplicate Subcategory 'maternity' under Category '{$existingCat['name']}' rejected", isset($res2['success']) && $res2['success'] === false);
assertTest("Subcategory error message contains 'already exists'", strpos($res2['message'] ?? '', 'already exists') !== false);

$res2Upper = runPhpSnippet('
    $_POST = ["category_id" => ' . $catId . ', "name" => "MATERNITY", "code" => "M"];
    $c = new ProductionController();
    $c->quickAddSubcategoryAjax();
');
assertTest("Duplicate Subcategory 'MATERNITY' rejected", isset($res2Upper['success']) && $res2Upper['success'] === false);

// ----------------------------------------------------
// TEST 3: ProductionController::quickAddSubSubcategoryAjax duplication check
// ----------------------------------------------------
echo "\n--- 3. Testing ProductionController::quickAddSubSubcategoryAjax Duplication ---\n";

$res3 = runPhpSnippet('
    $_POST = ["subcategory_id" => ' . $subId . ', "name" => "normal sleeve", "code" => "NS"];
    $c = new ProductionController();
    $c->quickAddSubSubcategoryAjax();
');
assertTest("Duplicate Sub-Subcategory 'normal sleeve' rejected", isset($res3['success']) && $res3['success'] === false);
assertTest("Sub-Subcategory error contains 'already exists'", strpos($res3['message'] ?? '', 'already exists') !== false);

$res3Upper = runPhpSnippet('
    $_POST = ["subcategory_id" => ' . $subId . ', "name" => "NORMAL SLEEVE", "code" => "NS"];
    $c = new ProductionController();
    $c->quickAddSubSubcategoryAjax();
');
assertTest("Duplicate Sub-Subcategory 'NORMAL SLEEVE' rejected", isset($res3Upper['success']) && $res3Upper['success'] === false);

// ----------------------------------------------------
// TEST 4: ProductionController::quickAddAttributeAjax duplication check
// ----------------------------------------------------
echo "\n--- 4. Testing ProductionController::quickAddAttributeAjax Duplication ---\n";

$res4 = runPhpSnippet('
    $_POST = ["attribute_type" => "size", "attribute_value" => "4xl", "attribute_name" => "Size"];
    $c = new ProductionController();
    $c->quickAddAttributeAjax();
');
assertTest("Duplicate Attribute '4xl' for 'size' rejected", isset($res4['success']) && $res4['success'] === false);
assertTest("Attribute error contains 'already exists'", strpos($res4['message'] ?? '', 'already exists') !== false);

$res4Upper = runPhpSnippet('
    $_POST = ["attribute_type" => "size", "attribute_value" => "4XL", "attribute_name" => "Size"];
    $c = new ProductionController();
    $c->quickAddAttributeAjax();
');
assertTest("Duplicate Attribute '4XL' rejected", isset($res4Upper['success']) && $res4Upper['success'] === false);

// ----------------------------------------------------
// TEST 5: CatalogMappingController::quickCreateCategory duplication check
// ----------------------------------------------------
echo "\n--- 5. Testing CatalogMappingController::quickCreateCategory Duplication ---\n";

$res5 = runPhpSnippet('
    $_SERVER["HTTP_X_REQUESTED_WITH"] = "xmlhttprequest";
    $_POST = [
        "csrf_token" => "test_token",
        "tier" => "category",
        "name" => "women"
    ];
    $c = new CatalogMappingController();
    $c->quickCreateCategory();
');
assertTest("CatalogMappingController rejects duplicate category 'women'", isset($res5['success']) && $res5['success'] === false);
assertTest("Error contains 'already exists'", strpos($res5['message'] ?? '', 'already exists') !== false);

$res5Sub = runPhpSnippet('
    $_SERVER["HTTP_X_REQUESTED_WITH"] = "xmlhttprequest";
    $_POST = [
        "csrf_token" => "test_token",
        "tier" => "subcategory",
        "category_id" => ' . $catId . ',
        "name" => "maternity"
    ];
    $c = new CatalogMappingController();
    $c->quickCreateCategory();
');
assertTest("CatalogMappingController rejects duplicate subcategory 'maternity'", isset($res5Sub['success']) && $res5Sub['success'] === false);
assertTest("Subcategory error contains 'already exists'", strpos($res5Sub['message'] ?? '', 'already exists') !== false);

$res5SubSub = runPhpSnippet('
    $_SERVER["HTTP_X_REQUESTED_WITH"] = "xmlhttprequest";
    $_POST = [
        "csrf_token" => "test_token",
        "tier" => "subsubcategory",
        "subcategory_id" => ' . $subId . ',
        "name" => "normal sleeve"
    ];
    $c = new CatalogMappingController();
    $c->quickCreateCategory();
');
assertTest("CatalogMappingController rejects duplicate sub-subcategory 'normal sleeve'", isset($res5SubSub['success']) && $res5SubSub['success'] === false);
assertTest("Sub-Subcategory error contains 'already exists'", strpos($res5SubSub['message'] ?? '', 'already exists') !== false);

// ----------------------------------------------------
// TEST 6: CatalogMappingController::quickCreateAttribute duplication check
// ----------------------------------------------------
echo "\n--- 6. Testing CatalogMappingController::quickCreateAttribute Duplication ---\n";

$res6 = runPhpSnippet('
    $_SERVER["HTTP_X_REQUESTED_WITH"] = "xmlhttprequest";
    $_POST = [
        "csrf_token" => "test_token",
        "attribute_type" => "size",
        "attribute_value" => "4xl",
        "attribute_name" => "Size"
    ];
    $c = new CatalogMappingController();
    $c->quickCreateAttribute();
');
assertTest("CatalogMappingController rejects duplicate attribute '4xl'", isset($res6['success']) && $res6['success'] === false);
assertTest("Attribute error contains 'already exists'", strpos($res6['message'] ?? '', 'already exists') !== false);

// ----------------------------------------------------
// TEST 7: Successful creation of unique items
// ----------------------------------------------------
echo "\n--- 7. Testing Successful Creation of Unique Items ---\n";

$uniqueCatName = 'UniqueCat' . time();
$res7 = runPhpSnippet('
    $_POST = ["name" => "' . $uniqueCatName . '", "code" => "UC"];
    $c = new ProductionController();
    $c->quickAddCategoryAjax();
');
assertTest("Unique Category creation succeeds", isset($res7['success']) && $res7['success'] === true);
$createdCatId = (int)($res7['id'] ?? 0);

if ($createdCatId > 0) {
    $uniqueSubName = 'UniqueSub' . time();
    $res7Sub = runPhpSnippet('
        $_POST = ["category_id" => ' . $createdCatId . ', "name" => "' . $uniqueSubName . '", "code" => "US"];
        $c = new ProductionController();
        $c->quickAddSubcategoryAjax();
    ');
    assertTest("Unique Subcategory creation succeeds", isset($res7Sub['success']) && $res7Sub['success'] === true);
    $createdSubId = (int)($res7Sub['id'] ?? 0);

    if ($createdSubId > 0) {
        $uniqueSubSubName = 'UniqueSubSub' . time();
        $res7SubSub = runPhpSnippet('
            $_POST = ["subcategory_id" => ' . $createdSubId . ', "name" => "' . $uniqueSubSubName . '", "code" => "USS"];
            $c = new ProductionController();
            $c->quickAddSubSubcategoryAjax();
        ');
        assertTest("Unique Sub-Subcategory creation succeeds", isset($res7SubSub['success']) && $res7SubSub['success'] === true);
        $createdSubSubId = (int)($res7SubSub['id'] ?? 0);

        if ($createdSubSubId > 0) {
            DB::query("DELETE FROM sub_subcategories WHERE id = ?", [$createdSubSubId]);
        }
        DB::query("DELETE FROM subcategories WHERE id = ?", [$createdSubId]);
    }
    DB::query("DELETE FROM categories WHERE id = ?", [$createdCatId]);
}

$uniqueAttrVal = 'ATTR_' . time();
$res7Attr = runPhpSnippet('
    $_POST = ["attribute_type" => "size", "attribute_value" => "' . $uniqueAttrVal . '", "attribute_name" => "Size"];
    $c = new ProductionController();
    $c->quickAddAttributeAjax();
');
assertTest("Unique Attribute creation succeeds", isset($res7Attr['success']) && $res7Attr['success'] === true);
$createdAttrId = (int)($res7Attr['id'] ?? 0);
if ($createdAttrId > 0) {
    DB::query("DELETE FROM product_attributes WHERE id = ?", [$createdAttrId]);
}

echo "\n======================================================================\n";
echo "SUMMARY: {$passCount} Passed, {$failCount} Failed\n";
echo "======================================================================\n";

if ($failCount > 0) {
    exit(1);
}
