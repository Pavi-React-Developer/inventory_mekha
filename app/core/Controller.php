<?php
// app/core/Controller.php

namespace App\Core;

abstract class Controller {
    protected function render(string $viewPath, array $data = []): void {
        extract($data);

        // Standard context available in views
        $currentUser = Auth::user();
        $currentConcern = Auth::concern();
        $isSuperAdmin = Auth::isSuperAdmin();
        $csrfToken = Auth::csrfToken();

        // Flash messages
        $flashSuccess = $_SESSION['flash_success'] ?? null;
        $flashError   = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);

        // Load modules for sidebar
        $sidebarModules = [];
        if (Auth::check()) {
            if ($isSuperAdmin) {
                $sidebarModules = DB::fetchAll(
                    "SELECT * FROM modules WHERE is_super_admin_only = 1 ORDER BY sort_order ASC"
                );
            } else {
                $cid = Auth::concernId();
                $sidebarModules = DB::fetchAll(
                    "SELECT m.* FROM concern_modules cm 
                     JOIN modules m ON cm.module_id = m.id 
                     WHERE cm.concern_id = ? AND cm.enabled = 1 
                     ORDER BY m.sort_order ASC",
                    [$cid]
                );
            }
        }

        // Determine view full path
        $contentView = __DIR__ . "/../views/{$viewPath}.php";

        if (!file_exists($contentView)) {
            die("View not found: " . htmlspecialchars($viewPath));
        }

        // Layout wrapping
        $layout = __DIR__ . "/../views/layouts/main.php";
        if (isset($noLayout) && $noLayout) {
            require $contentView;
        } else {
            require $layout;
        }
    }

    protected function renderJson(array $data, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    protected function redirect(string $path): void {
        header('Location: ' . $this->url($path));
        exit;
    }

    public static function url(string $path = ''): string {
        $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
        // If script is in public/, handle neatly
        return $base . '/' . ltrim($path, '/');
    }

    protected function flash(string $type, string $message): void {
        Auth::startSession();
        $_SESSION["flash_{$type}"] = $message;
    }

    protected function validateCsrf(): void {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!Auth::verifyCsrfToken($token)) {
            http_response_code(419);
            die("CSRF token validation failed. Please refresh and try again.");
        }
    }
}
