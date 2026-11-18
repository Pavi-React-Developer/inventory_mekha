<?php
require_once __DIR__ . '/../app/core/Autoloader.php';
\App\Core\Env::load(dirname(__DIR__) . '/.env');
use App\Core\DB;

DB::query("UPDATE modules SET group_name = 'Shop' WHERE code = 'customers'");
DB::query("UPDATE modules SET group_name = 'Shop', name = 'POS Billing' WHERE code = 'billing'");
DB::query("UPDATE modules SET name = 'Cash & Cash Flow' WHERE code = 'cash_flow'");

echo "Modules updated.\n";
