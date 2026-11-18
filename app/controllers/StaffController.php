<?php
// app/controllers/StaffController.php - Staff Management Module Controller

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;
use App\Core\Auth;
use App\Services\NumberSeries;
use App\Services\ExportService;
use Exception;

class StaffController extends Controller {

    public function index(): void {
        $allConcerns = DB::fetchAll(
            "SELECT c.id, c.name, c.code, c.status,
                    (SELECT COUNT(*) FROM staff s WHERE s.concern_id = c.id) AS staff_count
             FROM concerns c
             WHERE c.status = 'active'
             ORDER BY c.name ASC"
        );

        // Concern selection logic
        $selectedCid = null;
        if (!empty($_GET['concern_id'])) {
            $selectedCid = (int)$_GET['concern_id'];
            $_SESSION['staff_selected_concern_id'] = $selectedCid;
        } elseif (!empty($_SESSION['staff_selected_concern_id'])) {
            $selectedCid = (int)$_SESSION['staff_selected_concern_id'];
        } else {
            $selectedCid = Auth::concernId();
        }

        // If force choose or if no concern selected yet
        $showConcernModal = false;
        if (isset($_GET['choose']) || empty($selectedCid)) {
            $showConcernModal = true;
        }

        $activeConcern = null;
        $staffMembers = [];

        if ($selectedCid) {
            $activeConcern = DB::fetch("SELECT id, name, code FROM concerns WHERE id = ?", [$selectedCid]);
            if ($activeConcern) {
                $staffMembers = DB::fetchAll(
                    "SELECT s.*, c.name AS concern_name, c.code AS concern_code
                     FROM staff s
                     JOIN concerns c ON s.concern_id = c.id
                     WHERE s.concern_id = ?
                     ORDER BY s.id DESC",
                    [$selectedCid]
                );
            } else {
                $selectedCid = null;
                $showConcernModal = true;
            }
        }

        $this->render('concern/staff', [
            'pageTitle'        => 'Staff Management & Register',
            'currentModule'    => 'staff',
            'allConcerns'      => $allConcerns,
            'selectedCid'      => $selectedCid,
            'activeConcern'    => $activeConcern,
            'staffMembers'     => $staffMembers,
            'showConcernModal' => $showConcernModal,
        ]);
    }

    public function store(): void {
        $this->validateCsrf();
        $cid = (int)($_POST['concern_id'] ?? 0);
        if (!$cid) {
            $cid = Auth::concernId() ?: (int)($_SESSION['staff_selected_concern_id'] ?? 0);
        }

        if (!$cid) {
            $this->flash('error', 'Please choose a concern for this staff member.');
            $this->redirect('/m/staff?choose=1');
        }

        $name        = trim($_POST['name'] ?? '');
        $email       = trim($_POST['email'] ?? '');
        $password    = trim($_POST['password'] ?? '');
        $phone       = trim($_POST['phone'] ?? '');
        $designation = trim($_POST['designation'] ?? '');
        $status      = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
        $department  = trim($_POST['department'] ?? 'General');

        if (!$name) {
            $this->flash('error', 'Staff member name is required.');
            $this->redirect('/m/staff?concern_id=' . $cid);
        }

        try {
            $passwordHash = $password ? password_hash($password, PASSWORD_BCRYPT) : null;
            $staffCode = trim($_POST['staff_code'] ?? '');
            if (!$staffCode) {
                $staffCode = NumberSeries::getNext($cid, 'STAFF');
            }

            $userId = null;
            // If email and password provided, also create/sync login user account
            if ($email && $password) {
                $existingUser = DB::fetch("SELECT id FROM users WHERE email = ?", [$email]);
                if ($existingUser) {
                    $userId = (int)$existingUser['id'];
                    DB::query(
                        "UPDATE users SET name = ?, concern_id = ?, password_hash = ?, phone = ?, status = ? WHERE id = ?",
                        [$name, $cid, $passwordHash, $phone, $status, $userId]
                    );
                } else {
                    $roleId = (int)DB::fetchValue("SELECT id FROM roles WHERE concern_id = ? LIMIT 1", [$cid]);
                    if (!$roleId) {
                        $roleId = (int)DB::fetchValue("SELECT id FROM roles WHERE id != 1 LIMIT 1") ?: 3;
                    }
                    DB::query(
                        "INSERT INTO users (concern_id, role_id, name, email, password_hash, phone, status, created_at)
                         VALUES (?, ?, ?, ?, ?, ?, ?, NOW())",
                        [$cid, $roleId, $name, $email, $passwordHash, $phone, $status]
                    );
                    $userId = (int)DB::lastInsertId();
                }
            }

            DB::query(
                "INSERT INTO staff (concern_id, user_id, staff_code, name, email, password_hash, phone, designation, department, status, created_by, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
                [$cid, $userId, $staffCode, $name, $email, $passwordHash, $phone, $designation, $department, $status, Auth::id()]
            );

            $this->flash('success', "Staff member '{$name}' [{$staffCode}] registered successfully.");
        } catch (Exception $e) {
            $this->flash('error', 'Failed to register staff: ' . $e->getMessage());
        }

        $this->redirect('/m/staff?concern_id=' . $cid);
    }

    public function update(): void {
        $this->validateCsrf();
        $id = (int)($_POST['id'] ?? 0);
        $staff = DB::fetch("SELECT * FROM staff WHERE id = ?", [$id]);
        if (!$staff) {
            $this->flash('error', 'Staff record not found.');
            $this->redirect('/m/staff');
        }

        $cid         = (int)($_POST['concern_id'] ?? $staff['concern_id']);
        $name        = trim($_POST['name'] ?? $staff['name']);
        $email       = trim($_POST['email'] ?? $staff['email']);
        $password    = trim($_POST['password'] ?? '');
        $phone       = trim($_POST['phone'] ?? $staff['phone']);
        $designation = trim($_POST['designation'] ?? $staff['designation']);
        $status      = ($_POST['status'] ?? $staff['status']) === 'inactive' ? 'inactive' : 'active';
        $department  = trim($_POST['department'] ?? $staff['department']);

        try {
            $passwordHash = $password ? password_hash($password, PASSWORD_BCRYPT) : $staff['password_hash'];

            // Sync with users login table if applicable
            if (!empty($staff['user_id'])) {
                if ($password) {
                    DB::query(
                        "UPDATE users SET name = ?, email = ?, concern_id = ?, password_hash = ?, phone = ?, status = ? WHERE id = ?",
                        [$name, $email, $cid, $passwordHash, $phone, $status, $staff['user_id']]
                    );
                } else {
                    DB::query(
                        "UPDATE users SET name = ?, email = ?, concern_id = ?, phone = ?, status = ? WHERE id = ?",
                        [$name, $email, $cid, $phone, $status, $staff['user_id']]
                    );
                }
            } elseif ($email && $password) {
                $roleId = (int)DB::fetchValue("SELECT id FROM roles WHERE concern_id = ? LIMIT 1", [$cid]) ?: 3;
                DB::query(
                    "INSERT INTO users (concern_id, role_id, name, email, password_hash, phone, status, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, NOW())",
                    [$cid, $roleId, $name, $email, $passwordHash, $phone, $status]
                );
                $userId = (int)DB::lastInsertId();
                DB::query("UPDATE staff SET user_id = ? WHERE id = ?", [$userId, $id]);
            }

            DB::query(
                "UPDATE staff SET concern_id = ?, name = ?, email = ?, password_hash = ?, phone = ?, designation = ?, department = ?, status = ?
                 WHERE id = ?",
                [$cid, $name, $email, $passwordHash, $phone, $designation, $department, $status, $id]
            );

            $this->flash('success', "Staff member '{$name}' updated successfully.");
        } catch (Exception $e) {
            $this->flash('error', 'Failed to update staff: ' . $e->getMessage());
        }

        $this->redirect('/m/staff?concern_id=' . $cid);
    }

    public function delete(): void {
        $this->validateCsrf();
        $id = (int)($_POST['id'] ?? 0);
        $staff = DB::fetch("SELECT * FROM staff WHERE id = ?", [$id]);
        if (!$staff) {
            $this->flash('error', 'Staff record not found.');
            $this->redirect('/m/staff');
        }

        $cid = (int)$staff['concern_id'];
        try {
            DB::query("DELETE FROM staff WHERE id = ?", [$id]);
            $this->flash('success', "Staff record '{$staff['name']}' [{$staff['staff_code']}] deleted successfully.");
        } catch (Exception $e) {
            $this->flash('error', 'Failed to delete staff: ' . $e->getMessage());
        }

        $this->redirect('/m/staff?concern_id=' . $cid);
    }

    public function export(): void {
        $selectedCid = !empty($_GET['concern_id']) ? (int)$_GET['concern_id'] : Auth::concernId();
        $format = strtolower($_GET['format'] ?? 'excel');

        $sql = "SELECT s.*, c.name AS concern_name, c.code AS concern_code
                FROM staff s
                JOIN concerns c ON s.concern_id = c.id";
        $params = [];
        if ($selectedCid) {
            $sql .= " WHERE s.concern_id = ?";
            $params[] = $selectedCid;
        }
        $sql .= " ORDER BY s.id DESC";
        $staffList = DB::fetchAll($sql, $params);

        $headers = [
            'Staff Code', 'Name', 'Department', 'Designation', 'Phone / Mobile', 'Email', 
            'Salary Type', 'Base Salary (INR)', 'Status', 'Joining Date', 'Concern'
        ];

        $rows = [];
        $totSalary = 0;
        foreach ($staffList as $stf) {
            $sal = (float)($stf['base_salary'] ?? 0);
            $totSalary += $sal;
            $rows[] = [
                $stf['staff_code'],
                $stf['name'],
                $stf['department'] ?: 'General',
                $stf['designation'] ?: '-',
                $stf['phone'] ?: '-',
                $stf['email'] ?: '-',
                ucfirst($stf['salary_type'] ?: 'Monthly'),
                number_format($sal, 2),
                strtoupper($stf['status'] ?? 'ACTIVE'),
                $stf['joining_date'] ? date('d M Y', strtotime($stf['joining_date'])) : '-',
                $stf['concern_name'] ?? 'Consolidated'
            ];
        }

        $totals = ['Total Staff: ' . count($staffList), '', '', '', '', '', 'Total Payroll:', number_format($totSalary, 2), '', '', ''];

        $metadata = [
            'Report Title' => 'Staff Directory Register Report',
            'Generated At' => date('d M Y, h:i A'),
            'Total Staff'  => count($staffList),
            'Concern'      => $selectedCid ? ($staffList[0]['concern_name'] ?? 'Concern #' . $selectedCid) : 'All Concerns'
        ];

        $filename = 'staff_register_' . date('Ymd_His');

        if ($format === 'pdf') {
            ExportService::renderPdfView('Staff Directory Register Report', $metadata, $headers, $rows, $totals);
        } elseif ($format === 'csv') {
            ExportService::downloadCsv($filename, $headers, $rows);
        } else {
            ExportService::downloadExcel($filename, 'Staff Directory Register Report', $metadata, $headers, $rows, $totals);
        }
    }
}
