<?php
// database/seed_sample_catalog.php
require_once __DIR__ . '/../app/core/Autoloader.php';
\App\Core\Env::load(dirname(__DIR__) . '/.env');
use App\Core\DB;

$existing = DB::fetch("SELECT id FROM catalogs WHERE style_code = 'GRM-STY-0001' AND concern_id = 1");
if (!$existing) {
    $kids = DB::fetch("SELECT id FROM categories WHERE name = 'KIDS' AND concern_id = 1");
    $kidsId = $kids ? $kids['id'] : 1;
    $baby = DB::fetch("SELECT id FROM subcategories WHERE name = 'BABY' AND category_id = ?", [$kidsId]);
    $babyId = $baby ? $baby['id'] : null;
    $dress = $babyId ? DB::fetch("SELECT id FROM sub_subcategories WHERE name = 'DRESS' AND subcategory_id = ?", [$babyId]) : null;
    $dressId = $dress ? $dress['id'] : null;

    DB::query(
        "INSERT INTO catalogs (
            concern_id, code, style_code, short_code,
            category_id, subcategory_id, sub_sub_category_id,
            main_category_code, sub_category_code, sub_sub_category_code,
            hierarchy_path, hsn_code, gst_rate, sizes, status, name, created_at
        ) VALUES (
            1, 'GRM-STY-0001', 'GRM-STY-0001', 'KBD',
            ?, ?, ?,
            'K', 'B', 'D',
            'KIDS > BABY > DRESS', '107', 5.00, ?, 'in_house', 'KIDS - BABY - DRESS', NOW()
        )",
        [$kidsId, $babyId, $dressId, json_encode(['S', 'M', 'L'])]
    );
    $catId = (int)DB::lastInsertId();

    foreach (['S', 'M', 'L'] as $size) {
        $sku = "GRM-STY-0001-{$size}";
        $v = DB::fetch("SELECT id FROM variants WHERE sku_code = ? AND concern_id = 1", [$sku]);
        if (!$v) {
            DB::query(
                "INSERT INTO variants (concern_id, catalog_id, sku_code, name, category_id, subcategory_id, size, price, mrp, creation_date, status, created_at)
                 VALUES (1, ?, ?, ?, ?, ?, ?, 250.00, 399.00, CURDATE(), 'active', NOW())",
                [$catId, $sku, "KIDS - BABY - DRESS - {$size}", $kidsId, $babyId ?? 0, $size]
            );
        } else {
            DB::query("UPDATE variants SET catalog_id = ?, status = 'active' WHERE id = ?", [$catId, $v['id']]);
        }
    }
    echo "Seeded sample style GRM-STY-0001 successfully!\n";
} else {
    echo "Sample style already exists.\n";
}
