<?php
require_once __DIR__ . '/../app/core/Autoloader.php';
\App\Core\Env::load(dirname(__DIR__) . '/.env');
use App\Core\DB;

$existingCols = DB::fetchAll('DESCRIBE expenses');
$fields = array_column($existingCols, 'Field');

if (!in_array('paid_to', $fields)) {
    DB::query("ALTER TABLE expenses ADD COLUMN paid_to VARCHAR(255) NULL AFTER category");
    echo "Added paid_to column.\n";
}
if (!in_array('item_particulars', $fields)) {
    DB::query("ALTER TABLE expenses ADD COLUMN item_particulars VARCHAR(255) NULL AFTER paid_to");
    echo "Added item_particulars column.\n";
}
if (!in_array('status', $fields)) {
    DB::query("ALTER TABLE expenses ADD COLUMN status VARCHAR(50) NOT NULL DEFAULT 'Paid' AFTER payment_mode");
    echo "Added status column.\n";
}

echo "Expenses table schema verified.\n";
