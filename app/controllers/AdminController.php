<?php
// app/controllers/AdminController.php - Super Admin Features

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;
use App\Core\Auth;
use App\Models\Concern;
use App\Models\Asset;
use App\Services\AuditLog;
use Exception;

class AdminController extends Controller {
    public function switchConcern(): void {
        $concernId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        Auth::switchConcern($concernId > 0 ? $concernId : null);
        
        $this->flash('success', $concernId > 0 ? 'Switched active workspace view to concern.' : 'Switched to Consolidated View (All Concerns).');
        
        $referer = $_SERVER['HTTP_REFERER'] ?? self::url('/admin/dashboard');
        header('Location: ' . $referer);
        exit;
    }

    public function dashboard(): void {
        $concernsCount = DB::fetchValue("SELECT COUNT(*) FROM concerns WHERE status = 'active'");
        $usersCount = DB::fetchValue("SELECT COUNT(*) FROM users WHERE status = 'active'");
        $assetsCount = DB::fetchValue("SELECT COUNT(*) FROM assets WHERE status = 'active'");
        $assetsValuation = DB::fetchValue("SELECT COALESCE(SUM(current_value), 0) FROM assets");
        
        $concerns = DB::fetchAll(
            "SELECT c.*, 
                    (SELECT COUNT(*) FROM concern_modules cm WHERE cm.concern_id = c.id AND cm.enabled = 1) AS active_modules_count,
                    (SELECT COUNT(*) FROM users u WHERE u.concern_id = c.id) AS users_count
             FROM concerns c 
             ORDER BY c.id DESC"
        );

        $recentAudits = DB::fetchAll(
            "SELECT a.*, u.name AS user_name, c.name AS concern_name 
             FROM audit_log a
             LEFT JOIN users u ON a.user_id = u.id
             LEFT JOIN concerns c ON a.concern_id = c.id
             ORDER BY a.id DESC LIMIT 10"
        );

        $this->render('admin/dashboard', [
            'pageTitle'       => 'Super Admin Dashboard',
            'currentModule'   => 'admin_dashboard',
            'concernsCount'   => (int)$concernsCount,
            'usersCount'      => (int)$usersCount,
            'assetsCount'     => (int)$assetsCount,
            'assetsValuation' => (float)$assetsValuation,
            'concerns'        => $concerns,
            'recentAudits'    => $recentAudits,
        ]);
    }

    public function concerns(): void {
        $concerns = DB::fetchAll(
            "SELECT c.*, 
                    (SELECT COUNT(*) FROM concern_modules cm WHERE cm.concern_id = c.id AND cm.enabled = 1) AS active_modules_count,
                    (SELECT COUNT(*) FROM users u WHERE u.concern_id = c.id) AS users_count,
                    (SELECT COUNT(*) FROM variants v WHERE v.concern_id = c.id) AS variants_count
             FROM concerns c 
             ORDER BY c.id DESC"
        );

        $allModules = DB::fetchAll("SELECT * FROM modules WHERE is_super_admin_only = 0 ORDER BY sort_order ASC");

        $this->render('admin/concerns', [
            'pageTitle'     => 'Concern Management',
            'currentModule' => 'admin_concerns',
            'concerns'      => $concerns,
            'allModules'    => $allModules,
        ]);
    }

    public function storeConcern(): void {
        $this->validateCsrf();

        $errors = [];
        $readField = static function (string $field, bool $trim = true) use (&$errors): string {
            $value = $_POST[$field] ?? '';
            if (!is_string($value)) {
                $errors[] = 'Invalid form value for ' . str_replace('_', ' ', $field) . '.';
                return '';
            }
            return $trim ? trim($value) : $value;
        };

        $name = $readField('name');
        $gstin = strtoupper($readField('gstin'));
        $phone = $readField('phone');
        $address = $readField('address');
        $adminName = $readField('admin_name');
        $adminEmail = strtolower($readField('admin_email'));
        $adminPass = $readField('admin_password', false);

        $textLength = static function (string $value): int {
            return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
        };

        if ($textLength($name) < 2 || $textLength($name) > 150 || preg_match('/\p{L}/u', $name) !== 1) {
            $errors[] = 'Concern name must be 2 to 150 characters and contain a letter.';
        }
        if ($gstin !== '' && !preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][A-Z0-9]Z[A-Z0-9]$/D', $gstin)) {
            $errors[] = 'Enter a valid 15-character GSTIN or leave the field blank.';
        }
        if ($phone !== '' && !preg_match('/^\+?[0-9]{10,15}$/D', $phone)) {
            $errors[] = 'Phone must contain 10 to 15 digits, with an optional leading +.';
        }
        if ($textLength($address) > 1000) {
            $errors[] = 'Registered address cannot exceed 1000 characters.';
        }
        if ($textLength($adminName) < 2 || $textLength($adminName) > 100 || preg_match('/\p{L}/u', $adminName) !== 1) {
            $errors[] = 'Admin name must be 2 to 100 characters and contain a letter.';
        }
        if ($adminEmail === '' || strlen($adminEmail) > 120 || filter_var($adminEmail, FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = 'Enter a valid admin login email address.';
        }
        if (strlen($adminPass) < 8 || strlen($adminPass) > 72 || !preg_match('/[A-Za-z]/', $adminPass) || !preg_match('/[0-9]/', $adminPass)) {
            $errors[] = 'Admin password must be 8 to 72 characters and include at least one letter and one number.';
        }

        $allConcernModules = DB::fetchAll("SELECT id FROM modules WHERE is_super_admin_only = 0");
        $allowedModuleIds = array_fill_keys(array_map('strval', array_column($allConcernModules, 'id')), true);
        $rawModuleIds = $_POST['modules'] ?? [];
        $pickedModuleIds = [];
        if (!is_array($rawModuleIds)) {
            $errors[] = 'Select valid modules for the concern.';
        } else {
            foreach ($rawModuleIds as $moduleId) {
                if ((!is_string($moduleId) && !is_int($moduleId))
                    || !preg_match('/^[1-9][0-9]*$/D', (string)$moduleId)
                    || !isset($allowedModuleIds[(string)$moduleId])) {
                    $errors[] = 'The selected module list contains an invalid module.';
                    break;
                }
                $pickedModuleIds[] = (int)$moduleId;
            }
            $pickedModuleIds = array_values(array_unique($pickedModuleIds));
        }
        if (!$pickedModuleIds) {
            $errors[] = 'Select at least one module for the concern.';
        }

        if ($adminEmail !== '' && DB::fetch("SELECT id FROM users WHERE email = ? LIMIT 1", [$adminEmail])) {
            $errors[] = 'That admin login email is already in use.';
        }

        if ($errors) {
            $this->flash('error', implode(' ', array_unique($errors)));
            $this->redirect('/admin/concerns');
            return;
        }

        DB::beginTransaction();
        try {
            $code = $this->generateConcernCode($name);

            // 1. Create Concern
            DB::query(
                "INSERT INTO concerns (code, name, address, gstin, phone, email, status) VALUES (?, ?, ?, ?, ?, ?, 'active')",
                [
                    $code,
                    $name,
                    $address !== '' ? $address : null,
                    $gstin !== '' ? $gstin : null,
                    $phone !== '' ? $phone : null,
                    null,
                ]
            );
            $concernId = (int)DB::lastInsertId();

            // 2. Assign Chosen Modules
            $insertCm = DB::pdo()->prepare("INSERT INTO concern_modules (concern_id, module_id, enabled) VALUES (?, ?, ?)");
            foreach ($allConcernModules as $m) {
                $enabled = in_array((int)$m['id'], $pickedModuleIds, true) ? 1 : 0;
                $insertCm->execute([$concernId, $m['id'], $enabled]);
            }

            // 3. Create Default Concern Admin Role
            DB::query(
                "INSERT INTO roles (concern_id, name, description) VALUES (?, 'Concern Administrator', 'Full concern access')",
                [$concernId]
            );
            $roleId = (int)DB::lastInsertId();

            // 4. Assign permissions to role for enabled modules
            $insertRp = DB::pdo()->prepare("INSERT INTO role_permissions (role_id, module_id, can_view, can_add, can_edit, can_delete) VALUES (?, ?, 1, 1, 1, 1)");
            foreach ($pickedModuleIds as $modId) {
                $insertRp->execute([$roleId, (int)$modId]);
            }

            // 5. Create Admin User
            $passwordHash = password_hash($adminPass, PASSWORD_BCRYPT);
            DB::query(
                "INSERT INTO users (concern_id, role_id, name, email, password_hash, phone, status) VALUES (?, ?, ?, ?, ?, ?, 'active')",
                [$concernId, $roleId, $adminName, $adminEmail, $passwordHash, $phone !== '' ? $phone : null]
            );

            // 6. Seed Default Document Series
            $prefix = strtoupper(substr($code, 0, 3));
            $docTypes = ['INVOICE', 'PO', 'BATCH', 'INWARD', 'OUTWARD', 'REQ', 'DAMAGE'];
            $insertDs = DB::pdo()->prepare("INSERT INTO doc_series (concern_id, doc_type, prefix, next_no) VALUES (?, ?, ?, 101)");
            foreach ($docTypes as $dt) {
                $insertDs->execute([$concernId, $dt, "{$prefix}-{$dt}-"]);
            }

            // 7. Seed Default Cash Accounts
            DB::query(
                "INSERT INTO cash_accounts (concern_id, name, account_type, balance) VALUES (?, 'Main Cash Drawer', 'cash', 0.00)",
                [$concernId]
            );

            // 8. Seed Default Master Categories
            DB::query("INSERT INTO categories (concern_id, name) VALUES (?, 'General Raw Materials')", [$concernId]);
            $catId = (int)DB::lastInsertId();
            DB::query("INSERT INTO subcategories (concern_id, category_id, name) VALUES (?, ?, 'Default')", [$concernId, $catId]);

            DB::commit();

            AuditLog::log('CREATE_CONCERN', 'concerns', $concernId, null, ['name' => $name, 'code' => $code]);

            $this->flash('success', "Concern '{$name}' ({$code}) successfully created with " . count($pickedModuleIds) . " active modules!");
            $this->redirect('/admin/concerns');
        } catch (Exception $e) {
            DB::rollBack();
            $this->flash('error', 'Failed to create concern: ' . $e->getMessage());
            $this->redirect('/admin/concerns');
        }
    }

    private function generateConcernCode(string $name): string {
        $words = preg_split('/[^A-Z0-9]+/', strtoupper($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $token = '';
        foreach (array_slice($words, 0, 3) as $word) {
            $token .= $word[0];
        }
        if (strlen($token) < 2) {
            $token = substr(implode('', $words), 0, 3);
        }
        if ($token === '') {
            $token = 'NEW';
        }

        $baseCode = 'CON-' . $token;
        $code = $baseCode;
        $suffix = 2;
        while (DB::fetch("SELECT id FROM concerns WHERE code = ? LIMIT 1", [$code])) {
            $code = $baseCode . '-' . $suffix;
            $suffix++;
        }
        return $code;
    }

    public function toggleModule(): void {
        $this->validateCsrf();

        $concernId = (int)($_POST['concern_id'] ?? 0);
        $moduleId  = (int)($_POST['module_id'] ?? 0);
        $enabled   = (int)($_POST['enabled'] ?? 0);

        if (!$concernId || !$moduleId) {
            $this->renderJson(['success' => false, 'message' => 'Invalid parameters'], 400);
        }

        DB::query(
            "INSERT INTO concern_modules (concern_id, module_id, enabled) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE enabled = VALUES(enabled)",
            [$concernId, $moduleId, $enabled]
        );

        AuditLog::log('TOGGLE_MODULE', 'concern_modules', $concernId, null, ['module_id' => $moduleId, 'enabled' => $enabled]);

        $this->renderJson(['success' => true, 'message' => 'Module status updated successfully!']);
    }

    public function assets(): void {
        $assets = DB::fetchAll(
            "SELECT a.*, c.name AS concern_name 
             FROM assets a
             LEFT JOIN concerns c ON a.concern_id = c.id
             ORDER BY a.id DESC"
        );

        $concerns = DB::fetchAll("SELECT id, name FROM concerns WHERE status = 'active'");

        $this->render('admin/assets', [
            'pageTitle'     => 'Asset Management',
            'currentModule' => 'admin_assets',
            'assets'        => $assets,
            'concerns'      => $concerns,
        ]);
    }

    public function storeAsset(): void {
        $this->validateCsrf();

        $code      = trim($_POST['asset_code'] ?? '');
        $name      = trim($_POST['name'] ?? '');
        $category  = trim($_POST['category'] ?? '');
        $cost      = (float)($_POST['purchase_cost'] ?? 0);
        $value     = (float)($_POST['current_value'] ?? $cost);
        $date      = $_POST['purchase_date'] ?: date('Y-m-d');
        $location  = trim($_POST['location'] ?? '');
        $concernId = !empty($_POST['concern_id']) ? (int)$_POST['concern_id'] : null;
        $notes     = trim($_POST['notes'] ?? '');

        if (empty($code) || empty($name)) {
            $this->flash('error', 'Asset Code and Name are required.');
            $this->redirect('/admin/assets');
        }

        try {
            DB::query(
                "INSERT INTO assets (concern_id, asset_code, name, category, purchase_date, purchase_cost, current_value, location, status, notes)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', ?)",
                [$concernId, $code, $name, $category, $date, $cost, $value, $location, $notes]
            );

            $this->flash('success', "Asset '{$name}' ({$code}) recorded successfully.");
        } catch (Exception $e) {
            $this->flash('error', 'Failed to save asset: ' . $e->getMessage());
        }

        $this->redirect('/admin/assets');
    }

    public function reports(): void {
        $period     = trim($_GET['period'] ?? 'all');
        $singleDate = trim($_GET['single_date'] ?? '');
        $fromDate   = trim($_GET['from_date'] ?? '');
        $toDate     = trim($_GET['to_date'] ?? '');

        $range = \App\Services\ExportService::resolveDateRange($period, $singleDate, $fromDate, $toDate);

        $salesDateFilter = "";
        $purchaseDateFilter = "";

        if (!empty($range['start_date']) && !empty($range['end_date'])) {
            $startDateEsc = addslashes($range['start_date']);
            $endDateEsc = addslashes($range['end_date']);
            $salesDateFilter = " AND si.invoice_date >= '{$startDateEsc}' AND si.invoice_date <= '{$endDateEsc}'";
            $purchaseDateFilter = " AND pb.bill_date >= '{$startDateEsc}' AND pb.bill_date <= '{$endDateEsc}'";
        }

        $concernStats = DB::fetchAll(
            "SELECT c.id, c.code, c.name,
                    (SELECT COUNT(*) FROM variants v WHERE v.concern_id = c.id) AS total_variants,
                    (SELECT COALESCE(SUM(s.qty), 0) FROM stocks s WHERE s.concern_id = c.id) AS total_stock_qty,
                    (SELECT COALESCE(SUM(s.qty * v.price), 0) FROM stocks s JOIN variants v ON s.variant_id = v.id WHERE s.concern_id = c.id) AS stock_valuation,
                    (SELECT COALESCE(SUM(net_amount), 0) FROM sales_invoices si WHERE si.concern_id = c.id {$salesDateFilter}) AS total_sales,
                    (SELECT COALESCE(SUM(net_amount), 0) FROM purchase_bills pb WHERE pb.concern_id = c.id {$purchaseDateFilter}) AS total_purchases
             FROM concerns c
             ORDER BY c.id ASC"
        );

        $this->render('admin/reports', [
            'pageTitle'     => 'Consolidated Multi-Concern Reports',
            'currentModule' => 'admin_reports',
            'concernStats'  => $concernStats,
            'period'        => $period,
            'singleDate'    => $singleDate,
            'fromDate'      => $fromDate,
            'toDate'        => $toDate,
            'rangeLabel'    => $range['label'],
        ]);
    }

    public function exportReports(): void {
        $format     = strtolower(trim($_GET['format'] ?? 'excel'));
        $period     = trim($_GET['period'] ?? 'all');
        $singleDate = trim($_GET['single_date'] ?? '');
        $fromDate   = trim($_GET['from_date'] ?? '');
        $toDate     = trim($_GET['to_date'] ?? '');

        $range = \App\Services\ExportService::resolveDateRange($period, $singleDate, $fromDate, $toDate);

        $salesDateFilter = "";
        $purchaseDateFilter = "";

        if (!empty($range['start_date']) && !empty($range['end_date'])) {
            $startDateEsc = addslashes($range['start_date']);
            $endDateEsc = addslashes($range['end_date']);
            $salesDateFilter = " AND si.invoice_date >= '{$startDateEsc}' AND si.invoice_date <= '{$endDateEsc}'";
            $purchaseDateFilter = " AND pb.bill_date >= '{$startDateEsc}' AND pb.bill_date <= '{$endDateEsc}'";
        }

        $stats = DB::fetchAll(
            "SELECT c.id, c.code, c.name,
                    (SELECT COUNT(*) FROM variants v WHERE v.concern_id = c.id) AS total_variants,
                    (SELECT COALESCE(SUM(s.qty), 0) FROM stocks s WHERE s.concern_id = c.id) AS total_stock_qty,
                    (SELECT COALESCE(SUM(s.qty * v.price), 0) FROM stocks s JOIN variants v ON s.variant_id = v.id WHERE s.concern_id = c.id) AS stock_valuation,
                    (SELECT COALESCE(SUM(net_amount), 0) FROM sales_invoices si WHERE si.concern_id = c.id {$salesDateFilter}) AS total_sales,
                    (SELECT COALESCE(SUM(net_amount), 0) FROM purchase_bills pb WHERE pb.concern_id = c.id {$purchaseDateFilter}) AS total_purchases
             FROM concerns c
             ORDER BY c.id ASC"
        );

        $headers = [
            '#', 'Concern Code', 'Concern Name', 'Variants Cataloged', 'Stock Qty In Hand',
            'Live Inventory Valuation (₹)', 'Gross Purchases in Period (₹)', 'Gross Sales in Period (₹)'
        ];

        $exportData = [];
        $grandStockQty = 0;
        $grandValuation = 0.00;
        $grandPurchases = 0.00;
        $grandSales = 0.00;
        $sl = 1;

        foreach ($stats as $cs) {
            $stockQty = (float)$cs['total_stock_qty'];
            $val = (float)$cs['stock_valuation'];
            $purch = (float)$cs['total_purchases'];
            $sales = (float)$cs['total_sales'];

            $grandStockQty += $stockQty;
            $grandValuation += $val;
            $grandPurchases += $purch;
            $grandSales += $sales;

            $exportData[] = [
                $sl++,
                $cs['code'],
                $cs['name'],
                number_format($cs['total_variants']) . ' items',
                number_format($stockQty, 2),
                '₹' . number_format($val, 2),
                '₹' . number_format($purch, 2),
                '₹' . number_format($sales, 2)
            ];
        }

        $totalsRow = [
            'TOTAL CONSOLIDATED', '', count($exportData) . ' Concerns', '', number_format($grandStockQty, 2),
            '₹' . number_format($grandValuation, 2), '₹' . number_format($grandPurchases, 2), '₹' . number_format($grandSales, 2)
        ];

        $metadata = [
            'Scope'        => 'Consolidated All Registered Concerns',
            'Period Scope' => $range['label'],
            'Generated By' => Auth::user()['name'] ?? 'Super Administrator',
        ];

        $cleanPeriodName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $range['period']);
        $filename = 'Consolidated_Performance_Report_' . $cleanPeriodName . '_' . date('Ymd_His');

        if ($format === 'excel' || $format === 'xls' || $format === 'xlsx') {
            \App\Services\ExportService::downloadExcel($filename, 'Consolidated Multi-Concern Performance Report', $metadata, $headers, $exportData, $totalsRow);
        } elseif ($format === 'pdf') {
            \App\Services\ExportService::renderPdfView('Consolidated Multi-Concern Performance Report', $metadata, $headers, $exportData, $totalsRow);
        } else {
            \App\Services\ExportService::downloadCsv($filename, $headers, $exportData, $totalsRow);
        }
    }
}
