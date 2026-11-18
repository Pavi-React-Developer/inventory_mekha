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
            'currentSubModule' => 'all',
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

            if ($userId && $cid) {
                DB::query("INSERT IGNORE INTO user_concerns (user_id, concern_id) VALUES (?, ?)", [$userId, $cid]);
            }

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

            $effectiveUserId = !empty($staff['user_id']) ? (int)$staff['user_id'] : ($userId ?? null);
            if ($effectiveUserId && $cid) {
                DB::query("INSERT IGNORE INTO user_concerns (user_id, concern_id) VALUES (?, ?)", [$effectiveUserId, $cid]);
            }

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

    public function roles(): void {
        $allConcerns = DB::fetchAll("SELECT id, name, code, status FROM concerns WHERE status = 'active' ORDER BY name ASC");
        $allRoles = DB::fetchAll("SELECT id, name, description, concern_id FROM roles ORDER BY (concern_id IS NULL) DESC, id ASC");

        $concernFilter = isset($_GET['concern_id']) && $_GET['concern_id'] !== '' ? (int)$_GET['concern_id'] : null;
        $search = trim($_GET['search'] ?? '');

        $sql = "SELECT s.*, c.name AS concern_name, c.code AS concern_code,
                       u.email AS user_email, u.role_id, u.status AS user_status,
                       r.name AS role_name
                FROM staff s
                JOIN concerns c ON s.concern_id = c.id
                LEFT JOIN users u ON s.user_id = u.id
                LEFT JOIN roles r ON u.role_id = r.id
                WHERE 1=1";
        $params = [];

        if ($concernFilter) {
            $sql .= " AND s.concern_id = ?";
            $params[] = $concernFilter;
        }

        if ($search !== '') {
            $sql .= " AND (s.name LIKE ? OR s.staff_code LIKE ? OR s.phone LIKE ? OR s.email LIKE ? OR s.designation LIKE ?)";
            $term = "%{$search}%";
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        $sql .= " ORDER BY s.id DESC";
        $staffMembers = DB::fetchAll($sql, $params);

        // Map user concerns for users
        $userConcernsMap = [];
        $userIds = array_values(array_filter(array_unique(array_column($staffMembers, 'user_id'))));
        if (!empty($userIds)) {
            $inClause = implode(',', array_map('intval', $userIds));
            $ucRows = DB::fetchAll("SELECT uc.user_id, uc.concern_id, c.name AS concern_name, c.code AS concern_code
                                    FROM user_concerns uc
                                    JOIN concerns c ON uc.concern_id = c.id
                                    WHERE uc.user_id IN ({$inClause})");
            foreach ($ucRows as $row) {
                $userConcernsMap[$row['user_id']][] = [
                    'id'   => (int)$row['concern_id'],
                    'name' => $row['concern_name'],
                    'code' => $row['concern_code']
                ];
            }
        }

        // Operational modules for CRUD permissions matrix
        $modules = DB::fetchAll(
            "SELECT * FROM modules WHERE is_super_admin_only = 0 ORDER BY group_name ASC, sort_order ASC, id ASC"
        );

        $activeTab = ($_GET['tab'] ?? 'staff') === 'matrix' ? 'matrix' : 'staff';
        $selectedRoleId = (int)($_GET['matrix_role_id'] ?? 0);
        if (!$selectedRoleId && !empty($allRoles)) {
            $selectedRoleId = (int)$allRoles[0]['id'];
        }

        $matrixPerms = [];
        if ($selectedRoleId) {
            $rpRows = DB::fetchAll("SELECT * FROM role_permissions WHERE role_id = ?", [$selectedRoleId]);
            foreach ($rpRows as $rp) {
                $matrixPerms[$rp['module_id']] = [
                    'can_view'   => (int)$rp['can_view'],
                    'can_add'    => (int)$rp['can_add'],
                    'can_edit'   => (int)$rp['can_edit'],
                    'can_delete' => (int)$rp['can_delete'],
                ];
            }
        }

        $this->render('concern/staff_roles', [
            'pageTitle'        => 'Staff Role & Access Assignment',
            'currentModule'    => 'staff',
            'currentSubModule' => 'roles',
            'allConcerns'      => $allConcerns,
            'allRoles'         => $allRoles,
            'modules'          => $modules,
            'staffMembers'     => $staffMembers,
            'userConcernsMap'  => $userConcernsMap,
            'concernFilter'    => $concernFilter,
            'search'           => $search,
            'activeTab'        => $activeTab,
            'selectedRoleId'   => $selectedRoleId,
            'matrixPerms'      => $matrixPerms,
        ]);
    }

    public function assignRole(): void {
        $this->validateCsrf();

        $staffId = (int)($_POST['staff_id'] ?? 0);
        $roleId = (int)($_POST['role_id'] ?? 0);
        $concernIds = array_map('intval', (array)($_POST['concern_ids'] ?? []));
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        $staff = DB::fetch("SELECT * FROM staff WHERE id = ?", [$staffId]);
        if (!$staff) {
            $this->flash('error', 'Staff record not found.');
            $this->redirect('/m/staff/roles');
        }

        try {
            $userId = !empty($staff['user_id']) ? (int)$staff['user_id'] : null;

            if (!$userId) {
                if (!$email) {
                    $email = $staff['email'] ?: ('staff_' . strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $staff['staff_code'])) . '@invento.com');
                }
                $existingUser = DB::fetch("SELECT id FROM users WHERE email = ?", [$email]);
                if ($existingUser) {
                    $userId = (int)$existingUser['id'];
                    $updateSql = "UPDATE users SET role_id = ?, status = 'active'";
                    $uParams = [$roleId ?: 10];
                    if ($password) {
                        $updateSql .= ", password_hash = ?";
                        $uParams[] = password_hash($password, PASSWORD_BCRYPT);
                    }
                    $updateSql .= " WHERE id = ?";
                    $uParams[] = $userId;
                    DB::query($updateSql, $uParams);
                } else {
                    $pass = $password ?: 'password123';
                    $passwordHash = password_hash($pass, PASSWORD_BCRYPT);
                    DB::query(
                        "INSERT INTO users (concern_id, role_id, name, email, password_hash, phone, status, created_at)
                         VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())",
                        [$staff['concern_id'], $roleId ?: 10, $staff['name'], $email, $passwordHash, $staff['phone']]
                    );
                    $userId = (int)DB::lastInsertId();
                }

                DB::query("UPDATE staff SET user_id = ?, email = ? WHERE id = ?", [$userId, $email, $staffId]);
            } else {
                $updateSql = "UPDATE users SET role_id = ?";
                $uParams = [$roleId];
                if ($email) {
                    $updateSql .= ", email = ?";
                    $uParams[] = $email;
                }
                if ($password) {
                    $updateSql .= ", password_hash = ?";
                    $uParams[] = password_hash($password, PASSWORD_BCRYPT);
                }
                $updateSql .= " WHERE id = ?";
                $uParams[] = $userId;
                DB::query($updateSql, $uParams);

                if ($email) {
                    DB::query("UPDATE staff SET email = ? WHERE id = ?", [$email, $staffId]);
                }
            }

            if ($userId) {
                // If custom permissions submitted for this staff member
                if (!empty($_POST['is_custom_perms']) && !empty($_POST['custom_perms'])) {
                    $customRoleName = "Custom: " . $staff['name'];
                    $customRoleId = (int)DB::fetchValue("SELECT id FROM roles WHERE name = ? AND concern_id = ?", [$customRoleName, $staff['concern_id']]);
                    if (!$customRoleId) {
                        DB::query(
                            "INSERT INTO roles (concern_id, name, description, created_at) VALUES (?, ?, ?, NOW())",
                            [$staff['concern_id'], $customRoleName, "Custom granular permissions for " . $staff['name']]
                        );
                        $customRoleId = (int)DB::lastInsertId();
                    }
                    $roleId = $customRoleId;
                    DB::query("UPDATE users SET role_id = ? WHERE id = ?", [$roleId, $userId]);

                    $allMods = DB::fetchAll("SELECT id FROM modules WHERE is_super_admin_only = 0");
                    $stmtRp = DB::pdo()->prepare(
                        "INSERT INTO role_permissions (role_id, module_id, can_view, can_add, can_edit, can_delete)
                         VALUES (?, ?, ?, ?, ?, ?)
                         ON DUPLICATE KEY UPDATE can_view = VALUES(can_view), can_add = VALUES(can_add), can_edit = VALUES(can_edit), can_delete = VALUES(can_delete)"
                    );
                    $customPerms = (array)$_POST['custom_perms'];
                    foreach ($allMods as $m) {
                        $mid = (int)$m['id'];
                        $v = !empty($customPerms[$mid]['view']) ? 1 : 0;
                        $a = !empty($customPerms[$mid]['add']) ? 1 : 0;
                        $e = !empty($customPerms[$mid]['edit']) ? 1 : 0;
                        $d = !empty($customPerms[$mid]['delete']) ? 1 : 0;
                        $stmtRp->execute([$roleId, $mid, $v, $a, $e, $d]);
                    }
                }

                DB::query("DELETE FROM user_concerns WHERE user_id = ?", [$userId]);

                if (empty($concernIds)) {
                    $concernIds = [(int)$staff['concern_id']];
                }

                $stmt = DB::pdo()->prepare("INSERT IGNORE INTO user_concerns (user_id, concern_id) VALUES (?, ?)");
                foreach ($concernIds as $cid) {
                    if ($cid > 0) {
                        $stmt->execute([$userId, $cid]);
                    }
                }
            }

            $this->flash('success', "Role and concern access updated successfully for '{$staff['name']}'.");
        } catch (Exception $e) {
            $this->flash('error', 'Failed to assign role: ' . $e->getMessage());
        }

        $this->redirect('/m/staff/roles');
    }

    public function apiGetRolePermissions(): void {
        $roleId = (int)($_GET['role_id'] ?? 0);
        $perms = [];
        if ($roleId) {
            $rows = DB::fetchAll("SELECT module_id, can_view, can_add, can_edit, can_delete FROM role_permissions WHERE role_id = ?", [$roleId]);
            foreach ($rows as $r) {
                $perms[(int)$r['module_id']] = [
                    'view'   => (int)$r['can_view'],
                    'add'    => (int)$r['can_add'],
                    'edit'   => (int)$r['can_edit'],
                    'delete' => (int)$r['can_delete'],
                ];
            }
        }
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }
        echo json_encode(['success' => true, 'role_id' => $roleId, 'permissions' => $perms]);
        exit;
    }

    public function saveRolePermissions(): void {
        $this->validateCsrf();
        $roleId = (int)($_POST['role_id'] ?? 0);
        $permissions = $_POST['perms'] ?? [];

        if (!$roleId) {
            $this->flash('error', 'Please select a valid role to configure.');
            $this->redirect('/m/staff/roles?tab=matrix');
        }

        try {
            $modules = DB::fetchAll("SELECT id FROM modules WHERE is_super_admin_only = 0");
            $stmt = DB::pdo()->prepare(
                "INSERT INTO role_permissions (role_id, module_id, can_view, can_add, can_edit, can_delete)
                 VALUES (?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE can_view = VALUES(can_view), can_add = VALUES(can_add), can_edit = VALUES(can_edit), can_delete = VALUES(can_delete)"
            );

            foreach ($modules as $m) {
                $mid = (int)$m['id'];
                $canView = !empty($permissions[$mid]['view']) ? 1 : 0;
                $canAdd = !empty($permissions[$mid]['add']) ? 1 : 0;
                $canEdit = !empty($permissions[$mid]['edit']) ? 1 : 0;
                $canDelete = !empty($permissions[$mid]['delete']) ? 1 : 0;

                $stmt->execute([$roleId, $mid, $canView, $canAdd, $canEdit, $canDelete]);
            }

            $roleName = DB::fetchValue("SELECT name FROM roles WHERE id = ?", [$roleId]) ?: "Role #{$roleId}";
            $this->flash('success', "CRUD permissions updated successfully for '{$roleName}'.");
        } catch (Exception $e) {
            $this->flash('error', 'Failed to update permissions: ' . $e->getMessage());
        }

        $this->redirect('/m/staff/roles?tab=matrix&matrix_role_id=' . $roleId);
    }

    public function createRole(): void {
        $this->validateCsrf();
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $concernId = !empty($_POST['concern_id']) ? (int)$_POST['concern_id'] : null;

        if (!$name) {
            $this->flash('error', 'Role name is required.');
            $this->redirect('/m/staff/roles?tab=matrix');
        }

        try {
            DB::query(
                "INSERT INTO roles (concern_id, name, description, created_at) VALUES (?, ?, ?, NOW())",
                [$concernId, $name, $description]
            );
            $newRoleId = (int)DB::lastInsertId();

            // Default all modules to view=1, add=0, edit=0, delete=0
            $modules = DB::fetchAll("SELECT id FROM modules WHERE is_super_admin_only = 0");
            $stmt = DB::pdo()->prepare(
                "INSERT INTO role_permissions (role_id, module_id, can_view, can_add, can_edit, can_delete) VALUES (?, ?, 1, 0, 0, 0)"
            );
            foreach ($modules as $m) {
                $stmt->execute([$newRoleId, (int)$m['id']]);
            }

            $this->flash('success', "Role '{$name}' created successfully. Now configure its CRUD permissions below.");
            $this->redirect('/m/staff/roles?tab=matrix&matrix_role_id=' . $newRoleId);
        } catch (Exception $e) {
            $this->flash('error', 'Failed to create role: ' . $e->getMessage());
            $this->redirect('/m/staff/roles?tab=matrix');
        }
    }
}
