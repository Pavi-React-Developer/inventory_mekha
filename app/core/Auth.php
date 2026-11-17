<?php
// app/core/Auth.php

namespace App\Core;

class Auth {
    public static function startSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            $config = require __DIR__ . '/../../config/app.php';
            if (!headers_sent()) {
                session_name($config['session']['name'] ?? 'INVENTO_SESSID');
                session_set_cookie_params([
                    'lifetime' => $config['session']['lifetime'] ?? 86400,
                    'path'     => $config['session']['path'] ?? '/',
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);
                session_start();
            } else {
                @session_start();
            }
        }
    }

    public static function check(): bool {
        self::startSession();
        return isset($_SESSION['user']) && !empty($_SESSION['user']['id']);
    }

    public static function user(): ?array {
        self::startSession();
        return $_SESSION['user'] ?? null;
    }

    public static function id(): ?int {
        return self::user()['id'] ?? null;
    }

    public static function isSuperAdmin(): bool {
        $u = self::user();
        return $u !== null && $u['concern_id'] === null;
    }

    /**
     * Session tenant identifier.
     * For concern users: fixed to their own concern_id.
     * For super admin: can be switched to a specific concern or null (all concerns).
     */
    public static function concernId(): ?int {
        $u = self::user();
        if (!$u) {
            return null;
        }
        if ($u['concern_id'] !== null) {
            return (int)$u['concern_id'];
        }
        // Super admin selected concern
        return !empty($_SESSION['super_admin_concern_id']) ? (int)$_SESSION['super_admin_concern_id'] : null;
    }

    public static function superAdminSelectedConcernId(): ?int {
        return !empty($_SESSION['super_admin_concern_id']) ? (int)$_SESSION['super_admin_concern_id'] : null;
    }

    public static function switchConcern(?int $concernId): void {
        self::startSession();
        if (!self::isSuperAdmin()) {
            return;
        }
        if ($concernId && $concernId > 0) {
            $_SESSION['super_admin_concern_id'] = (int)$concernId;
        } else {
            $_SESSION['super_admin_concern_id'] = null;
        }
    }

    public static function allConcerns(): array {
        return DB::fetchAll("SELECT id, name, code, status FROM concerns WHERE status = 'active' ORDER BY name ASC");
    }

    public static function concern(): ?array {
        if (!self::isSuperAdmin()) {
            return $_SESSION['concern'] ?? null;
        }
        $selectedId = self::concernId();
        if ($selectedId) {
            return DB::fetch("SELECT id, name, code, logo FROM concerns WHERE id = ?", [$selectedId]);
        }
        return null;
    }

    public static function login(string $email, string $password): bool {
        self::startSession();
        
        $sql = "SELECT u.*, r.name AS role_name, c.name AS concern_name, c.code AS concern_code, c.status AS concern_status, c.logo AS concern_logo
                FROM users u
                LEFT JOIN roles r ON u.role_id = r.id
                LEFT JOIN concerns c ON u.concern_id = c.id
                WHERE u.email = ? AND u.status = 'active'
                LIMIT 1";
        
        $user = DB::fetch($sql, [$email]);
        if (!$user) {
            return false;
        }

        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }

        // If user belongs to a concern, ensure concern is active
        if ($user['concern_id'] !== null && $user['concern_status'] !== 'active') {
            return false;
        }

        // Prevent session fixation
        if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
            @session_regenerate_id(true);
        }

        $_SESSION['user'] = [
            'id'           => (int)$user['id'],
            'name'         => $user['name'],
            'email'        => $user['email'],
            'concern_id'   => $user['concern_id'] !== null ? (int)$user['concern_id'] : null,
            'role_id'      => (int)$user['role_id'],
            'role_name'    => $user['role_name'],
            'phone'        => $user['phone'],
        ];

        if ($user['concern_id'] !== null) {
            $_SESSION['concern'] = [
                'id'     => (int)$user['concern_id'],
                'name'   => $user['concern_name'],
                'code'   => $user['concern_code'],
                'logo'   => $user['concern_logo'],
            ];

            // Cache enabled modules for fast checking
            $mods = DB::fetchAll(
                "SELECT m.code, m.id FROM concern_modules cm 
                 JOIN modules m ON cm.module_id = m.id 
                 WHERE cm.concern_id = ? AND cm.enabled = 1",
                [(int)$user['concern_id']]
            );
            $_SESSION['enabled_modules'] = array_column($mods, 'code');

            // Cache permissions
            $perms = DB::fetchAll(
                "SELECT m.code, rp.can_view, rp.can_add, rp.can_edit, rp.can_delete 
                 FROM role_permissions rp
                 JOIN modules m ON rp.module_id = m.id
                 WHERE rp.role_id = ?",
                [(int)$user['role_id']]
            );
            $pMap = [];
            foreach ($perms as $p) {
                $pMap[$p['code']] = [
                    'view'   => (bool)$p['can_view'],
                    'add'    => (bool)$p['can_add'],
                    'edit'   => (bool)$p['can_edit'],
                    'delete' => (bool)$p['can_delete'],
                ];
            }
            $_SESSION['role_permissions'] = $pMap;
        } else {
            $_SESSION['concern'] = null;
            $_SESSION['enabled_modules'] = [];
            $_SESSION['role_permissions'] = [];
        }

        // Update last login
        DB::query("UPDATE users SET last_login = NOW() WHERE id = ?", [$user['id']]);

        // Audit log
        \App\Services\AuditLog::log('LOGIN', 'users', $user['id'], null, ['ip' => $_SERVER['REMOTE_ADDR'] ?? '']);

        return true;
    }

    public static function logout(): void {
        self::startSession();
        if (isset($_SESSION['user']['id'])) {
            \App\Services\AuditLog::log('LOGOUT', 'users', $_SESSION['user']['id'], null, null);
        }
        $_SESSION = [];
        if (!headers_sent() && ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_destroy();
        }
    }

    public static function hasModule(string $moduleCode): bool {
        if (self::isSuperAdmin()) {
            return true;
        }
        $mods = $_SESSION['enabled_modules'] ?? [];
        return in_array($moduleCode, $mods, true);
    }

    public static function can(string $moduleCode, string $action = 'view'): bool {
        if (self::isSuperAdmin()) {
            return true;
        }
        if (!self::hasModule($moduleCode)) {
            return false;
        }
        $perms = $_SESSION['role_permissions'] ?? [];
        if (!isset($perms[$moduleCode])) {
            // Default allow view if module enabled unless explicitly restricted
            return $action === 'view';
        }
        return !empty($perms[$moduleCode][$action]);
    }

    public static function csrfToken(): string {
        self::startSession();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verifyCsrfToken(?string $token): bool {
        self::startSession();
        if (empty($token) || empty($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}
