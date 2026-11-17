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

        $name     = trim($_POST['name'] ?? '');
        $code     = strtoupper(trim($_POST['code'] ?? ''));
        $gstin    = trim($_POST['gstin'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $address  = trim($_POST['address'] ?? '');

        // Admin User Details
        $adminName  = trim($_POST['admin_name'] ?? '');
        $adminEmail = trim($_POST['admin_email'] ?? '');
        $adminPass  = $_POST['admin_password'] ?? '';

        // Picked Modules
        $pickedModuleIds = $_POST['modules'] ?? [];

        if (empty($name) || empty($code) || empty($adminEmail) || empty($adminPass)) {
            $this->flash('error', 'Please fill in all mandatory fields (Name, Code, Admin Email, Password).');
            $this->redirect('/admin/concerns');
        }

        DB::beginTransaction();
        try {
            // 1. Create Concern
            DB::query(
                "INSERT INTO concerns (code, name, address, gstin, phone, email, status) VALUES (?, ?, ?, ?, ?, ?, 'active')",
                [$code, $name, $address, $gstin, $phone, $email]
            );
            $concernId = (int)DB::lastInsertId();

            // 2. Assign Chosen Modules
            $allConcernModules = DB::fetchAll("SELECT id FROM modules WHERE is_super_admin_only = 0");
            $insertCm = DB::pdo()->prepare("INSERT INTO concern_modules (concern_id, module_id, enabled) VALUES (?, ?, ?)");
            foreach ($allConcernModules as $m) {
                $enabled = in_array((string)$m['id'], $pickedModuleIds) ? 1 : 0;
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
                [$concernId, $roleId, $adminName ?: "{$name} Admin", $adminEmail, $passwordHash, $phone]
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
        $concernStats = DB::fetchAll(
            "SELECT c.id, c.code, c.name,
                    (SELECT COUNT(*) FROM variants v WHERE v.concern_id = c.id) AS total_variants,
                    (SELECT COALESCE(SUM(s.qty), 0) FROM stocks s WHERE s.concern_id = c.id) AS total_stock_qty,
                    (SELECT COALESCE(SUM(s.qty * v.price), 0) FROM stocks s JOIN variants v ON s.variant_id = v.id WHERE s.concern_id = c.id) AS stock_valuation,
                    (SELECT COALESCE(SUM(net_amount), 0) FROM sales_invoices si WHERE si.concern_id = c.id) AS total_sales,
                    (SELECT COALESCE(SUM(net_amount), 0) FROM purchase_bills pb WHERE pb.concern_id = c.id) AS total_purchases
             FROM concerns c
             ORDER BY c.id ASC"
        );

        $this->render('admin/reports', [
            'pageTitle'     => 'Consolidated Multi-Concern Reports',
            'currentModule' => 'admin_reports',
            'concernStats'  => $concernStats,
        ]);
    }
}
