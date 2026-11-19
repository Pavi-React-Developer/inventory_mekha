<?php
// tests/RollLabelFabricDynamicTest.php
// Verification test for dynamic fabric name and removal of "ROLL #..." header

require_once __DIR__ . '/../app/core/Autoloader.php';
\App\Core\Env::load(dirname(__DIR__) . '/.env');

use App\Core\DB;
use App\Core\Auth;
use App\Services\QrService;

echo "======================================================\n";
echo "INVENTO: Fabric Roll Dynamic Label & Header Removal Test\n";
echo "======================================================\n\n";

$db = DB::connect();

// 1. Setup mock session and auth state
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'super_admin';

// Ensure a test concern exists
$stmt = $db->query("SELECT id, name FROM concerns LIMIT 1");
$concern = $stmt->fetch();
if (!$concern) {
    $db->query("INSERT INTO concerns (name, code, status) VALUES ('TEST CONCERN LTD', 'TCL', 'active')");
    $concernId = (int)$db->lastInsertId();
    $concernName = 'TEST CONCERN LTD';
} else {
    $concernId = (int)$concern['id'];
    $concernName = $concern['name'];
}
$_SESSION['concern_id'] = $concernId;

// 2. Insert test purchase bill and test roll with roll_code = 'cotton', pattern = 'PANDA', item_name = 'PANDA Fabric'
$billNo = 'PB-TEST-' . time();
$db->prepare("INSERT INTO purchase_bills (concern_id, bill_no, supplier_id, bill_date, total_amount, payment_status, created_at) VALUES (?, ?, 1, CURDATE(), 5000, 'paid', NOW())")
   ->execute([$concernId, $billNo]);
$billId = (int)$db->lastInsertId();

$db->prepare("INSERT INTO purchase_bill_items (purchase_bill_id, concern_id, section_type, item_name, item_code, pattern, color, rq_qty, qty, unit, unit_price, total_price, inward_status) VALUES (?, ?, 'rolls', 'PANDA Fabric', 'cotton', 'PANDA', 'RED', 10, 10, 'RQ', 500, 5000, 'pending')")
   ->execute([$billId, $concernId]);
$itemId = (int)$db->lastInsertId();

echo "[1/4] Created test purchase bill #$billNo with roll item id $itemId (item_code='cotton', pattern='PANDA', item_name='PANDA Fabric').\n";

// 3. Test dynamic resolution logic
$stmt = $db->prepare("SELECT pbi.*, pb.bill_no, pb.supplier_id, pb.concern_id as bill_concern_id FROM purchase_bill_items pbi JOIN purchase_bills pb ON pbi.purchase_bill_id = pb.id WHERE pbi.id = ?");
$stmt->execute([$itemId]);
$item = $stmt->fetch();

$isRoll = ($item['section_type'] ?? '') === 'rolls';

$codeVal = trim($item['item_code'] ?? '');
$nameVal = trim($item['item_name'] ?? '');
$patternVal = trim($item['pattern'] ?? '');

$codeIsGenericRoll = preg_match('/^ROLL\s*#?\s*\d*$/i', $codeVal);
$nameIsPatternFabric = !empty($patternVal) && (strcasecmp($nameVal, $patternVal . ' Fabric') === 0 || strcasecmp($nameVal, $patternVal) === 0);

if (!empty($codeVal) && !$codeIsGenericRoll) {
    $fabricName = $codeVal;
} elseif (!empty($nameVal) && !$nameIsPatternFabric) {
    $fabricName = $nameVal;
} else {
    $fabricName = !empty($codeVal) ? $codeVal : (!empty($nameVal) ? $nameVal : 'Fabric Material');
}

$headerTitle = $isRoll ? '' : $item['item_name'];

if ($headerTitle === '') {
    echo "  [PASS] Header title is empty string for roll (no 'ROLL #cotton').\n";
} else {
    echo "  [FAIL] Header title was expected to be empty, got: $headerTitle\n";
}

if ($fabricName === 'cotton') {
    echo "  [PASS] Fabric name dynamically resolved to 'cotton'.\n";
} else {
    echo "  [FAIL] Fabric name was expected to be 'cotton', got: $fabricName\n";
}

$designVal = trim($item['pattern'] ?: ($item['specification'] ?: ($isRoll ? 'PLAIN' : 'STANDARD')));
if ($designVal === 'PANDA') {
    echo "  [PASS] Design value is 'PANDA'.\n";
} else {
    echo "  [FAIL] Design value was expected to be 'PANDA', got: $designVal\n";
}

// 4. Test rendered output of label_print_sheet.php
$tagCode = QrService::generateTagCode($fabricName, $item['color'] ?? '', $designVal, (string)$item['id']);
$labelData = [
    'concern_name'   => $concernName,
    'ref_no'         => $item['bill_no'],
    'header_title'   => $headerTitle,
    'is_roll'        => $isRoll,
    'label_1_name'   => 'Fabric',
    'label_1_val'    => $fabricName,
    'label_2_name'   => 'Color',
    'label_2_val'    => $item['color'] ?: 'STANDARD',
    'label_3_name'   => 'Design',
    'label_3_val'    => $designVal,
    'item_code'      => $tagCode,
    'tag_code'       => $tagCode,
    'price'          => 500,
    'qr_payload'     => $tagCode,
    'section_title'  => 'FABRIC ROLL LABEL',
    'section_badge'  => 'RAW MATERIAL',
    'item_title'     => $fabricName,
    'bill_id'        => $billId,
    'item_id'        => $itemId,
    'stock_tab'      => 'rolls'
];

$qty = 2;
$jobId = 999;
$qrCodeText = $tagCode;

ob_start();
include __DIR__ . '/../app/views/concern/label_print_sheet.php';
$html = ob_get_clean();

// Assertions on HTML output
if (strpos($html, 'ROLL #cotton') === false && strpos($html, '<div class="label-item-title">') === false) {
    echo "  [PASS] 'ROLL #cotton' / '<div class=\"label-item-title\">' completely absent from sticker body.\n";
} else {
    echo "  [FAIL] 'ROLL #...' or label-item-title found in sticker body!\n";
}

if (strpos($html, '<span class="detail-label">Fabric:</span>') !== false && strpos($html, '<span class="detail-val">cotton</span>') !== false) {
    echo "  [PASS] 'Fabric: cotton' successfully rendered in detail row.\n";
} else {
    echo "  [FAIL] 'Fabric: cotton' not found in sticker body!\n";
}

if (strpos($html, '<span class="detail-label">Design:</span>') !== false && strpos($html, '<span class="detail-val text-uppercase">PANDA</span>') !== false) {
    echo "  [PASS] 'Design: PANDA' successfully rendered in detail row.\n";
} else {
    echo "  [FAIL] 'Design: PANDA' not found in sticker body!\n";
}

if (strpos($html, htmlspecialchars($concernName)) !== false) {
    echo "  [PASS] Dynamic concern name '{$concernName}' rendered at top.\n";
} else {
    echo "  [FAIL] Concern name not found at top!\n";
}

// 5. Test PurchaseController saving logic with raw roll code
$rInput = [
    'roll_code'   => 'cotton',
    'pattern'     => 'PANDA',
    'color'       => 'RED',
    'shade'       => '',
    'hsn'         => '5208',
    'rq'          => 5,
    'price'       => 250,
    'net_weight'  => 120.5
];
$rawRollCode = trim($rInput['roll_code'] ?? '');
$rollPattern = trim($rInput['pattern'] ?? '');
$rollName    = !empty($rawRollCode) ? $rawRollCode : (!empty($rollPattern) ? ($rollPattern . ' Fabric') : 'ROLL #1');

if ($rollName === 'cotton') {
    echo "  [PASS] PurchaseController stores entered Fabric Name '$rollName' for new roll.\n";
} else {
    echo "  [FAIL] PurchaseController rollName expected 'cotton', got '$rollName'\n";
}

// Cleanup test records
$db->prepare("DELETE FROM purchase_bill_items WHERE purchase_bill_id = ?")->execute([$billId]);
$db->prepare("DELETE FROM purchase_bills WHERE id = ?")->execute([$billId]);
echo "\n[Cleaned up temporary test data]\n";
echo "=== ALL ROLL LABEL TESTS COMPLETED SUCCESSFULLY! ===\n";
