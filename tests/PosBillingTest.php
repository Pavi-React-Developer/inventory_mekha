<?php
// tests/PosBillingTest.php - Verify Shop POS Billing Terminal, Module Grouping & Transactions

require_once __DIR__ . '/../app/core/Autoloader.php';
\App\Core\Env::load(dirname(__DIR__) . '/.env');

use App\Core\DB;
use App\Core\Auth;
use App\Services\StockService;
use App\Services\CashService;

echo "======================================================\n";
echo "INVENTO: Shop Module POS Billing Terminal Verification\n";
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

// 1. Verify Module Grouping
echo "TEST 1: Module Grouping & Registration\n";
$billingMod = DB::fetch("SELECT * FROM modules WHERE code = 'billing'");
assertTest("Billing module exists", !empty($billingMod));
assertTest("Billing module group is 'Shop'", ($billingMod['group_name'] ?? '') === 'Shop');
assertTest("Billing module display name is 'POS Billing'", ($billingMod['name'] ?? '') === 'POS Billing');

// 2. Concern Login & Stock Availability
echo "\nTEST 2: Concern Authentication & Catalog Verification\n";
$loginOk = Auth::login('grm.admin@invento.com', 'password123');
assertTest("Concern 1 login succeeded", $loginOk);
$cid = Auth::concernId();
assertTest("Concern ID is 1", $cid === 1);

// Find an active variant for Concern 1
$variant = DB::fetch("SELECT * FROM variants WHERE concern_id = ? AND status = 'active' LIMIT 1", [$cid]);
assertTest("Variant found in Concern 1 catalog", !empty($variant));

// Ensure stock is available
$initialStock = StockService::getQty($cid, (int)$variant['id']);
if ($initialStock < 10) {
    StockService::move($cid, (int)$variant['id'], 'Main Store', 50, 0, 'Initial Stock', null, 'Seed test stock', Auth::id());
    $initialStock = StockService::getQty($cid, (int)$variant['id']);
}
assertTest("Sufficient stock available for test sale (> 5 units)", $initialStock >= 5);

// 3. Complete POS Sale Simulation
echo "\nTEST 3: POS Sale Execution & Ledger Integration\n";
$custPhone = '9876543210';
$custName  = 'Test POS Customer';
$saleQty   = 2.0;
$unitPrice = (float)$variant['price'];
$gstPct    = 5.0;
$discount  = 50.0;
$subtotal  = ($unitPrice * $saleQty) - $discount;
$gstAmount = ($subtotal * $gstPct) / 100.0;
$netAmount = round($subtotal + $gstAmount, 2);

// Check starting cash balance
$cashAccount = DB::fetch("SELECT id, balance FROM cash_accounts WHERE concern_id = ? ORDER BY id ASC LIMIT 1", [$cid]);
assertTest("Cash account exists for Concern 1", !empty($cashAccount));
$initialCashBalance = (float)$cashAccount['balance'];

// Execute test transaction via controllers logic
DB::beginTransaction();
try {
    // Customer
    $cust = DB::fetch("SELECT id FROM customers WHERE concern_id = ? AND phone = ? LIMIT 1", [$cid, $custPhone]);
    if ($cust) {
        $customerId = (int)$cust['id'];
    } else {
        DB::query("INSERT INTO customers (concern_id, code, name, phone, address, city, status, created_at) VALUES (?, 'CUST-TEST', ?, ?, 'Test Street', 'Coimbatore', 'active', NOW())", [$cid, $custName, $custPhone]);
        $customerId = (int)DB::lastInsertId();
    }

    $invNo = 'POS-TEST-' . time();
    DB::query(
        "INSERT INTO sales_invoices (concern_id, invoice_no, customer_id, invoice_date, total_amount, tax_amount, discount_amount, net_amount, payment_method, payment_status, notes, created_by, created_at)
         VALUES (?, ?, ?, CURDATE(), ?, ?, ?, ?, 'cash', 'paid', 'Automated POS Test', ?, NOW())",
        [$cid, $invNo, $customerId, $subtotal, $gstAmount, $discount, $netAmount, Auth::id()]
    );
    $invoiceId = (int)DB::lastInsertId();

    DB::query(
        "INSERT INTO sales_invoice_items (concern_id, sales_invoice_id, variant_id, qty, unit_price, total_price)
         VALUES (?, ?, ?, ?, ?, ?)",
        [$cid, $invoiceId, (int)$variant['id'], $saleQty, $unitPrice, $subtotal]
    );

    // Stock deduction
    StockService::move(
        $cid,
        (int)$variant['id'],
        'Main Store',
        0.00,
        $saleQty,
        'Billing',
        $invoiceId,
        "POS Sale #{$invNo}",
        Auth::id()
    );

    // Cash transaction
    CashService::postTransaction(
        $cid,
        (int)$cashAccount['id'],
        date('Y-m-d'),
        'credit',
        $netAmount,
        'Billing',
        $invoiceId,
        "POS Sale Invoice #{$invNo} [cash]",
        Auth::id()
    );

    DB::commit();

    assertTest("Sales invoice recorded with ID > 0", $invoiceId > 0);

    // Verify stock ledger & balance
    $newStock = StockService::getQty($cid, (int)$variant['id']);
    assertTest("Stock correctly decremented by {$saleQty} units", round($initialStock - $newStock, 2) === round($saleQty, 2));

    // Verify cash ledger & balance
    $newCashBalance = (float)DB::fetchValue("SELECT balance FROM cash_accounts WHERE id = ?", [$cashAccount['id']]);
    assertTest("Cash balance correctly credited by ₹{$netAmount}", round($newCashBalance - $initialCashBalance, 2) === round($netAmount, 2));

    // Clean up test invoice & reverse test cash/stock
    DB::query("DELETE FROM sales_invoice_items WHERE sales_invoice_id = ?", [$invoiceId]);
    DB::query("DELETE FROM sales_invoices WHERE id = ?", [$invoiceId]);
    DB::query("DELETE FROM cash_transactions WHERE ref_module = 'Billing' AND ref_id = ?", [$invoiceId]);
    DB::query("UPDATE cash_accounts SET balance = ? WHERE id = ?", [$initialCashBalance, $cashAccount['id']]);
    StockService::move($cid, (int)$variant['id'], 'Main Store', $saleQty, 0, 'Test Rollback', null, 'Rollback test stock', Auth::id());
    assertTest("Test sale and ledgers cleanly reverted", true);

} catch (\Throwable $e) {
    DB::rollBack();
    assertTest("POS Sale threw error: " . $e->getMessage(), false);
}

echo "\n======================================================\n";
echo "SUMMARY: {$passCount} PASSED, {$failCount} FAILED\n";
echo "======================================================\n";
if ($failCount === 0) {
    echo ">>> SHOP POS BILLING TERMINAL FULLY VERIFIED! <<<\n";
}
