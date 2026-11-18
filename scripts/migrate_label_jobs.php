<?php
require_once __DIR__ . '/../app/core/Autoloader.php';
\App\Core\Env::load(dirname(__DIR__) . '/.env');
use App\Core\DB;

$cols = array_column(DB::fetchAll("DESCRIBE label_jobs"), 'Field');

if (!in_array('label_type', $cols)) {
    DB::query("ALTER TABLE label_jobs ADD COLUMN label_type VARCHAR(50) NOT NULL DEFAULT 'production' AFTER concern_id");
    echo "Added label_type\n";
}
if (!in_array('ref_id', $cols)) {
    DB::query("ALTER TABLE label_jobs ADD COLUMN ref_id INT NULL AFTER label_type");
    echo "Added ref_id\n";
}
if (!in_array('item_title', $cols)) {
    DB::query("ALTER TABLE label_jobs ADD COLUMN item_title VARCHAR(255) NULL AFTER variant_id");
    echo "Added item_title\n";
}
if (!in_array('item_code', $cols)) {
    DB::query("ALTER TABLE label_jobs ADD COLUMN item_code VARCHAR(100) NULL AFTER item_title");
    echo "Added item_code\n";
}
if (!in_array('qr_payload', $cols)) {
    DB::query("ALTER TABLE label_jobs ADD COLUMN qr_payload VARCHAR(255) NULL AFTER item_code");
    echo "Added qr_payload\n";
}

// Make variant_id nullable
DB::query("ALTER TABLE label_jobs MODIFY COLUMN variant_id INT NULL");
echo "Modified variant_id to nullable\n";

echo "Label jobs table migration completed.\n";
