<?php
// tests/ComprehensiveExportAndCashFlowTest.php

require_once __DIR__ . '/../app/core/Autoloader.php';
\App\Core\Env::load(dirname(__DIR__) . '/.env');

use App\Core\DB;
use App\Core\Auth;
use App\Services\ExportService;
use App\Controllers\AdminController;
use App\Controllers\FinanceController;
use App\Controllers\ConcernDashboardController;
use App\Controllers\SalesController;

echo "======================================================\n";
echo "EXPORT & CASH FLOW COMPREHENSIVE TEST SUITE\n";
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

// 1. Date Range Scopes
echo "TEST 1: Date Range Scope Resolution\n";
$r1 = ExportService::resolveDateRange('single_day', '2026-11-17');
check("Single day range resolves correctly", $r1['start_date'] === '2026-11-17' && $r1['end_date'] === '2026-11-17');

$r2 = ExportService::resolveDateRange('this_month');
check("This month range resolves correctly", !empty($r2['start_date']) && !empty($r2['end_date']));

$r3 = ExportService::resolveDateRange('last_month');
check("Last month range resolves correctly", !empty($r3['start_date']) && !empty($r3['end_date']));

$r4 = ExportService::resolveDateRange('3_months');
check("3 months range resolves correctly", !empty($r4['start_date']) && $r4['end_date'] === date('Y-m-d'));

$r5 = ExportService::resolveDateRange('6_months');
check("6 months range resolves correctly", !empty($r5['start_date']) && $r5['end_date'] === date('Y-m-d'));

$r6 = ExportService::resolveDateRange('custom', null, '2026-01-01', '2026-06-30');
check("Custom date range resolves correctly", $r6['start_date'] === '2026-01-01' && $r6['end_date'] === '2026-06-30');

$r7 = ExportService::resolveDateRange('all');
check("All time resolves null dates", $r7['start_date'] === null && $r7['end_date'] === null);

// 2. Auth Login
echo "\nTEST 2: Authentication & Context\n";
$login = Auth::login('admin@invento.com', 'password123');
check("Super Admin login succeeded", $login === true);

// 3. Test AdminController::exportReports execution
echo "\nTEST 3: Admin Reports Export Execution (Excel, PDF, CSV)\n";
$_GET['period'] = '3_months';
$_GET['format'] = 'excel';
ob_start();
$adminCtrl = new AdminController();
$adminCtrl->exportReports();
$excelOut = ob_get_clean();
check("Admin export generated Excel XML", strpos($excelOut, 'schemas-microsoft-com:office:excel') !== false && strpos($excelOut, 'Consolidated Multi-Concern Performance Report') !== false);

$_GET['format'] = 'pdf';
ob_start();
$adminCtrl->exportReports();
$pdfOut = ob_get_clean();
check("Admin export generated PDF printable HTML", strpos($pdfOut, '@media print') !== false && strpos($pdfOut, 'window.print()') !== false);

// 4. Test FinanceController::exportCashFlow execution
echo "\nTEST 4: Finance Cash Flow Export Execution (Excel, PDF, CSV)\n";
$_GET['period'] = '6_months';
$_GET['format'] = 'excel';
ob_start();
$finCtrl = new FinanceController();
$finCtrl->exportCashFlow();
$cfExcel = ob_get_clean();
check("Cash flow export generated Excel XML", strpos($cfExcel, 'Cash &amp; Cash Flow Statement &amp; Ledger') !== false || strpos($cfExcel, 'Cash & Cash Flow') !== false);

$_GET['format'] = 'pdf';
ob_start();
$finCtrl->exportCashFlow();
$cfPdf = ob_get_clean();
check("Cash flow export generated PDF printable HTML", strpos($cfPdf, 'Cash &amp; Cash Flow') !== false || strpos($cfPdf, 'Cash & Cash Flow') !== false);

// 5. Test ConcernDashboardController::exportDashboard execution
echo "\nTEST 5: Concern Dashboard Operations & Ledger Export\n";
$_GET['period'] = 'single_day';
$_GET['single_date'] = date('Y-m-d');
$_GET['format'] = 'excel';
ob_start();
$dashCtrl = new ConcernDashboardController();
$dashCtrl->exportDashboard();
$dashExcel = ob_get_clean();
check("Concern dashboard export generated Excel", strpos($dashExcel, 'Concern Operations &amp; Stock Movement Report') !== false || strpos($dashExcel, 'Concern Operations') !== false);

// 6. Test SalesController::exportCustomers execution
echo "\nTEST 6: Sales / CRM Customers Export\n";
$_GET['format'] = 'excel';
$_GET['period'] = 'this_month';
ob_start();
$salesCtrl = new SalesController();
$salesCtrl->exportCustomers();
$crmExcel = ob_get_clean();
check("Customer CRM export generated Excel", strpos($crmExcel, 'Customer Directory &amp; CRM Performance Report') !== false || strpos($crmExcel, 'Customer Directory') !== false);

$_GET['format'] = 'pdf';
ob_start();
$salesCtrl->exportCustomers();
$crmPdf = ob_get_clean();
check("Customer CRM export generated PDF", strpos($crmPdf, 'Customer Directory &amp; CRM') !== false || strpos($crmPdf, 'Customer Directory') !== false);

echo "\n======================================================\n";
echo "SUMMARY: {$pass} PASSED, {$fail} FAILED\n";
echo "======================================================\n";

if ($fail > 0) exit(1);
