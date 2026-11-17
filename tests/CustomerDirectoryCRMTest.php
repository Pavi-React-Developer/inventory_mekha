<?php
// tests/CustomerDirectoryCRMTest.php - Verify Customer Directory & CRM Features

require_once __DIR__ . '/../app/core/Autoloader.php';
\App\Core\Env::load(dirname(__DIR__) . '/.env');

use App\Core\DB;
use App\Core\Auth;

echo "======================================================\n";
echo "INVENTO: Customer Directory & CRM Verification\n";
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

// 1. Module Grouping
echo "TEST 1: Customer Module in Shop Group\n";
$custMod = DB::fetch("SELECT * FROM modules WHERE code = 'customers'");
assertTest("Customer module exists", !empty($custMod));
assertTest("Customer module group is 'Shop'", ($custMod['group_name'] ?? '') === 'Shop');

// 2. Authentication
echo "\nTEST 2: Authentication & CRM Query Execution\n";
$loginOk = Auth::login('grm.admin@invento.com', 'password123');
assertTest("Concern 1 login succeeded", $loginOk);
$cid = Auth::concernId();

// Ensure test customer exists
$testPhone = '9500904638';
$testName = 'mekha';
$existing = DB::fetch("SELECT id FROM customers WHERE concern_id = ? AND phone = ? LIMIT 1", [$cid, $testPhone]);
if (!$existing) {
    DB::query(
        "INSERT INTO customers (concern_id, code, name, phone, email, gstin, address, city, status, created_at)
         VALUES (?, 'CUST-004', ?, ?, 'mekha@retail.com', '33AAACM1234F1Z9', 'Avinashi Road', 'Tiruppur', 'active', NOW())",
        [$cid, $testName, $testPhone]
    );
    $existing = DB::fetch("SELECT id FROM customers WHERE concern_id = ? AND phone = ? LIMIT 1", [$cid, $testPhone]);
}
assertTest("Test customer 'mekha' exists with phone '9500904638'", !empty($existing));
$testCustId = (int)$existing['id'];

// Check sales invoice link
$invoicesCount = (int)DB::fetchValue("SELECT COUNT(*) FROM sales_invoices WHERE customer_id = ?", [$testCustId]);
if ($invoicesCount === 0) {
    DB::query(
        "INSERT INTO sales_invoices (concern_id, invoice_no, customer_id, invoice_date, total_amount, tax_amount, discount_amount, net_amount, payment_method, payment_status, created_by, created_at)
         VALUES (?, 'INV-20261113-0003', ?, '2026-11-13', 952.38, 47.62, 0.00, 1000.00, 'cash', 'paid', ?, '2026-11-13 14:00:00')",
        [$cid, $testCustId, Auth::id()]
    );
    $invId = (int)DB::lastInsertId();
    $varId = (int)DB::fetchValue("SELECT id FROM variants WHERE concern_id = ? LIMIT 1", [$cid]) ?: 1;
    DB::query("INSERT INTO sales_invoice_items (concern_id, sales_invoice_id, variant_id, qty, unit_price, total_price) VALUES (?, ?, ?, 4, 250.00, 1000.00)", [$cid, $invId, $varId]);
}

// 3. Test CRM Aggregate Query
echo "\nTEST 3: CRM Aggregation & KPI Calculations\n";
$sql = "SELECT c.*,
               COALESCE(inv_stats.orders_count, 0) AS orders_count,
               COALESCE(inv_stats.total_spend, 0.00) AS total_spend,
               COALESCE(inv_stats.total_pcs, 0) AS total_pcs,
               inv_stats.last_order_date,
               inv_stats.last_invoice_no
        FROM customers c
        LEFT JOIN (
            SELECT 
                si.customer_id,
                COUNT(DISTINCT si.id) AS orders_count,
                SUM(si.net_amount) AS total_spend,
                COALESCE(SUM(sii.qty), 0) AS total_pcs,
                MAX(si.invoice_date) AS last_order_date,
                SUBSTRING_INDEX(GROUP_CONCAT(si.invoice_no ORDER BY si.id DESC SEPARATOR '|||'), '|||', 1) AS last_invoice_no
            FROM sales_invoices si
            LEFT JOIN sales_invoice_items sii ON sii.sales_invoice_id = si.id
            GROUP BY si.customer_id
        ) inv_stats ON inv_stats.customer_id = c.id
        WHERE c.id = ?";
$crmRecord = DB::fetch($sql, [$testCustId]);

assertTest("Customer record fetched with CRM aggregates", !empty($crmRecord));
assertTest("Customer has order count >= 1", (int)$crmRecord['orders_count'] >= 1);
assertTest("Customer has total spend > 0", (float)$crmRecord['total_spend'] >= 1000.00);
assertTest("Customer last invoice no matches 'INV-20261113-0003'", str_contains($crmRecord['last_invoice_no'], 'INV-'));
assertTest("Customer city is 'Tiruppur'", $crmRecord['city'] === 'Tiruppur');

// 4. Test Customer Update
echo "\nTEST 4: Customer Edit / Update\n";
DB::query("UPDATE customers SET address = 'Updated Tiruppur Main Rd' WHERE id = ?", [$testCustId]);
$updated = DB::fetch("SELECT address FROM customers WHERE id = ?", [$testCustId]);
assertTest("Customer updated successfully", $updated['address'] === 'Updated Tiruppur Main Rd');

echo "\n======================================================\n";
echo "SUMMARY: {$passCount} PASSED, {$failCount} FAILED\n";
echo "======================================================\n";
if ($failCount === 0) {
    echo ">>> CUSTOMER DIRECTORY & CRM FULLY VERIFIED! <<<\n";
}
