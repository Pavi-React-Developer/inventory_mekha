<?php
require_once __DIR__ . '/../app/core/Autoloader.php';
\App\Core\Env::load(dirname(__DIR__) . '/.env');

use App\Core\DB;
use App\Core\Auth;
use App\Controllers\ProductionController;

// Simulate logged in user
$user = DB::fetch("SELECT * FROM users WHERE email = 'admin@invento.com'");
if ($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role_id'] = $user['role_id'];
    $_SESSION['user_name'] = $user['name'];
}

echo "Testing stitching() method...\n";
ob_start();
$ctl = new ProductionController();
$ctl->stitching();
$html = ob_get_clean();

echo "stitching() executed successfully without errors!\n";
echo "Rendered HTML length: " . strlen($html) . " bytes\n";

if (strpos($html, 'Stitching & Assembly Tracking') !== false) {
    echo "SUCCESS: Page content rendered properly!\n";
    exit(0);
} else {
    echo "FAILED: Expected content not found.\n";
    exit(1);
}
