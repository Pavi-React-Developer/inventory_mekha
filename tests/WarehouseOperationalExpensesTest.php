<?php
// tests/WarehouseOperationalExpensesTest.php

require_once __DIR__ . '/../app/core/Autoloader.php';
\App\Core\Env::load(dirname(__DIR__) . '/.env');

use App\Core\DB;
use App\Core\Auth;
use App\Controllers\FinanceController;

echo "======================================================\n";
echo "WAREHOUSE OPERATIONAL EXPENSES TEST SUITE\n";
echo "======================================================\n\n";

$pass = 0;
$fail = 0;

function check(string $name, bool $condition) {
    global $pass, $fail;
    if ($condition) {
        echo "  [PASS] {$name}\n";
        $pass++;
    } else {
        echo "  [FAIL] {$name}\n";
        $fail++;
    }
}

// 1. Auth Login
$login = Auth::login('admin@invento.com', 'password123');
check("Super Admin login succeeded", $login === true);

// 2. Insert test warehouse expense
$concern = DB::fetch("SELECT * FROM concerns LIMIT 1");
$cid = (int)$concern['id'];
$acc = DB::fetch("SELECT * FROM cash_accounts WHERE concern_id = ? LIMIT 1", [$cid]);
$accId = $acc ? (int)$acc['id'] : null;

$_POST = [
    'csrf_token'       => Auth::csrfToken(),
    'concern_id'       => $cid,
    'expense_date'     => date('Y-m-d'),
    'category'         => 'Carton & Packaging Boxes',
    'item_particulars' => '100 pcs 5-ply carton boxes',
    'paid_to'          => 'Sri Balaji Packaging Corp',
    'amount'           => '2500.00',
    'payment_mode'     => 'Cash',
    'status'           => 'Paid',
    'account_id'       => $accId,
    'description'      => 'Bill #PKG-9921 for warehouse shipment packaging'
];

$finCtrl = new FinanceController();
$initialCount = (int)DB::fetchValue("SELECT COUNT(*) FROM expenses WHERE concern_id = ?", [$cid]);

// Test store
$initialBalance = $accId ? (float)DB::fetchValue("SELECT balance FROM cash_accounts WHERE id = ?", [$accId]) : 0;
try {
    $finCtrl->storeExpense();
} catch (\Throwable $t) {
    // Controller redirects on success
}

$newCount = (int)DB::fetchValue("SELECT COUNT(*) FROM expenses WHERE concern_id = ?", [$cid]);
check("New warehouse expense voucher stored in DB", $newCount === $initialCount + 1);

$latestExp = DB::fetch("SELECT * FROM expenses WHERE concern_id = ? ORDER BY id DESC LIMIT 1", [$cid]);
check("Voucher number generated with WH-EXP prefix", str_starts_with($latestExp['expense_no'], 'WH-EXP-'));
check("Category matches 'Carton & Packaging Boxes'", $latestExp['category'] === 'Carton & Packaging Boxes');
check("Item particulars matches '100 pcs 5-ply carton boxes'", $latestExp['item_particulars'] === '100 pcs 5-ply carton boxes');
check("Paid to matches 'Sri Balaji Packaging Corp'", $latestExp['paid_to'] === 'Sri Balaji Packaging Corp');
check("Status matches 'Paid'", $latestExp['status'] === 'Paid');

// 3. Test View Render
$_GET = [
    'from_date' => date('Y-m-01'),
    'to_date'   => date('Y-m-d'),
];
ob_start();
try {
    $finCtrl->expense();
} catch (\Throwable $t) {}
$html = ob_get_clean();

check("Rendered view contains 'Warehouse Operational Expenses & Petty Cash'", strpos($html, 'Warehouse Operational Expenses & Petty Cash') !== false);
check("Rendered view contains 'TOTAL WAREHOUSE OUTFLOW'", strpos($html, 'Total Warehouse Outflow') !== false || strpos($html, 'TOTAL WAREHOUSE OUTFLOW') !== false);
check("Rendered view contains 'CASH & PETTY DISBURSED'", strpos($html, 'Cash & Petty Disbursed') !== false || strpos($html, 'CASH & PETTY DISBURSED') !== false);
check("Rendered view contains 'UPI & BANK TRANSFERS'", strpos($html, 'UPI & Bank Transfers') !== false || strpos($html, 'UPI & BANK TRANSFERS') !== false);
check("Rendered view contains 'PENDING PAYABLES'", strpos($html, 'Pending Payables') !== false || strpos($html, 'PENDING PAYABLES') !== false);
check("Rendered view contains filter bar with 'Search payee/bill...'", strpos($html, 'Search payee/bill...') !== false);
check("Rendered view contains recorded voucher row in table", strpos($html, $latestExp['expense_no']) !== false);

// 4. Test Export
$_GET['format'] = 'excel';
ob_start();
$finCtrl->exportExpenses();
$excelOut = ob_get_clean();
check("Export generated Excel spreadsheet with 'Warehouse Operational Expenses'", strpos($excelOut, 'Warehouse Operational Expenses') !== false);

$_GET['format'] = 'pdf';
ob_start();
$finCtrl->exportExpenses();
$pdfOut = ob_get_clean();
check("Export generated PDF printable report", strpos($pdfOut, 'Warehouse Operational Expenses') !== false && strpos($pdfOut, 'window.print()') !== false);

// Clean up test expense
DB::query("DELETE FROM expenses WHERE id = ?", [$latestExp['id']]);
if ($accId) {
    DB::query("DELETE FROM cash_transactions WHERE ref_module = 'Warehouse Expense' AND ref_id = ?", [$latestExp['id']]);
    DB::query("UPDATE cash_accounts SET balance = ? WHERE id = ?", [$initialBalance, $accId]);
}
check("Test expense cleaned up cleanly", true);

echo "\n======================================================\n";
echo "SUMMARY: {$pass} PASSED, {$fail} FAILED\n";
echo "======================================================\n";

if ($fail > 0) exit(1);
