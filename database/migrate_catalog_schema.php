<?php
// database/migrate_catalog_schema.php
require_once __DIR__ . '/../app/core/Autoloader.php';
\App\Core\Env::load(dirname(__DIR__) . '/.env');
use App\Core\DB;

// 1. Update categories table
$catCols = array_column(DB::fetchAll('DESCRIBE categories'), 'Field');
if (!in_array('code', $catCols)) {
    DB::query('ALTER TABLE categories ADD COLUMN code VARCHAR(20) NULL AFTER name');
}
if (!in_array('default_hsn', $catCols)) {
    DB::query('ALTER TABLE categories ADD COLUMN default_hsn VARCHAR(50) NULL AFTER code');
}
if (!in_array('default_gst_rate', $catCols)) {
    DB::query('ALTER TABLE categories ADD COLUMN default_gst_rate DECIMAL(5,2) NOT NULL DEFAULT 5.00 AFTER default_hsn');
}

// 2. Update subcategories table
$subCols = array_column(DB::fetchAll('DESCRIBE subcategories'), 'Field');
if (!in_array('code', $subCols)) {
    DB::query('ALTER TABLE subcategories ADD COLUMN code VARCHAR(20) NULL AFTER name');
}

// 3. Create sub_subcategories table
DB::query("CREATE TABLE IF NOT EXISTS `sub_subcategories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `concern_id` INT NOT NULL,
    `subcategory_id` INT NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `code` VARCHAR(20) NULL,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_subsubcat_concern` (`concern_id`),
    INDEX `idx_subsubcat_subcat` (`subcategory_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// 4. Update catalogs table
$catlgCols = array_column(DB::fetchAll('DESCRIBE catalogs'), 'Field');
if (!in_array('style_code', $catlgCols)) {
    DB::query('ALTER TABLE catalogs ADD COLUMN style_code VARCHAR(50) NULL AFTER code');
}
if (!in_array('short_code', $catlgCols)) {
    DB::query('ALTER TABLE catalogs ADD COLUMN short_code VARCHAR(50) NULL AFTER style_code');
}
if (!in_array('category_id', $catlgCols)) {
    DB::query('ALTER TABLE catalogs ADD COLUMN category_id INT NULL AFTER short_code');
}
if (!in_array('subcategory_id', $catlgCols)) {
    DB::query('ALTER TABLE catalogs ADD COLUMN subcategory_id INT NULL AFTER category_id');
}
if (!in_array('sub_sub_category_id', $catlgCols)) {
    DB::query('ALTER TABLE catalogs ADD COLUMN sub_sub_category_id INT NULL AFTER subcategory_id');
}
if (!in_array('main_category_code', $catlgCols)) {
    DB::query('ALTER TABLE catalogs ADD COLUMN main_category_code VARCHAR(20) NULL AFTER sub_sub_category_id');
}
if (!in_array('sub_category_code', $catlgCols)) {
    DB::query('ALTER TABLE catalogs ADD COLUMN sub_category_code VARCHAR(20) NULL AFTER main_category_code');
}
if (!in_array('sub_sub_category_code', $catlgCols)) {
    DB::query('ALTER TABLE catalogs ADD COLUMN sub_sub_category_code VARCHAR(20) NULL AFTER sub_category_code');
}
if (!in_array('hierarchy_path', $catlgCols)) {
    DB::query('ALTER TABLE catalogs ADD COLUMN hierarchy_path VARCHAR(255) NULL AFTER sub_sub_category_code');
}
if (!in_array('hsn_code', $catlgCols)) {
    DB::query('ALTER TABLE catalogs ADD COLUMN hsn_code VARCHAR(50) NULL AFTER hierarchy_path');
}
if (!in_array('gst_rate', $catlgCols)) {
    DB::query('ALTER TABLE catalogs ADD COLUMN gst_rate DECIMAL(5,2) NOT NULL DEFAULT 5.00 AFTER hsn_code');
}
if (!in_array('sizes', $catlgCols)) {
    DB::query('ALTER TABLE catalogs ADD COLUMN sizes TEXT NULL AFTER gst_rate');
}
if (!in_array('status', $catlgCols)) {
    DB::query("ALTER TABLE catalogs ADD COLUMN status ENUM('active', 'inactive', 'in_house') NOT NULL DEFAULT 'in_house' AFTER sizes");
}
if (!in_array('updated_at', $catlgCols)) {
    DB::query('ALTER TABLE catalogs ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at');
}

// 5. Update variants table
$varCols = array_column(DB::fetchAll('DESCRIBE variants'), 'Field');
if (!in_array('catalog_id', $varCols)) {
    DB::query('ALTER TABLE variants ADD COLUMN catalog_id INT NULL AFTER concern_id');
    DB::query('ALTER TABLE variants ADD INDEX idx_var_catalog (catalog_id)');
}

// 6. Seed standard HSN codes if table is empty
$hsnCount = (int)DB::fetchValue("SELECT COUNT(*) FROM hsn_masters");
if ($hsnCount === 0) {
    $defaultHsns = [
        ['hsn_code' => '6109', 'description' => 'T-shirts, singlets and other vests, knitted or crocheted', 'gst_rate' => 5.00],
        ['hsn_code' => '107',  'description' => 'Kids & Baby Garments and Dresses', 'gst_rate' => 5.00],
        ['hsn_code' => '6203', 'description' => 'Men trousers, bib and brace overalls, breeches and shorts', 'gst_rate' => 5.00],
        ['hsn_code' => '6204', 'description' => 'Women suits, ensembles, jackets, dresses, skirts', 'gst_rate' => 5.00],
        ['hsn_code' => '6111', 'description' => 'Babies garments and clothing accessories, knitted or crocheted', 'gst_rate' => 5.00],
        ['hsn_code' => '5208', 'description' => 'Woven fabrics of cotton, containing 85% or more by weight', 'gst_rate' => 5.00],
        ['hsn_code' => '6105', 'description' => 'Men or boys shirts, knitted or crocheted', 'gst_rate' => 5.00],
        ['hsn_code' => '6106', 'description' => 'Women or girls blouses, shirts and shirt-blouses, knitted', 'gst_rate' => 5.00],
    ];
    foreach ($defaultHsns as $h) {
        DB::query("INSERT INTO hsn_masters (concern_id, hsn_code, description, gst_rate) VALUES (NULL, ?, ?, ?)", [
            $h['hsn_code'], $h['description'], $h['gst_rate']
        ]);
    }
}

// 7. Seed sample categories for Concern 1 if not present to match the image
$kidsCat = DB::fetch("SELECT id FROM categories WHERE name = 'KIDS' AND concern_id = 1");
if (!$kidsCat) {
    DB::query("INSERT INTO categories (concern_id, name, code, default_hsn, default_gst_rate, status) VALUES (1, 'KIDS', 'K', '107', 5.00, 'active')");
    $kidsCatId = (int)DB::lastInsertId();
} else {
    $kidsCatId = (int)$kidsCat['id'];
    DB::query("UPDATE categories SET code = 'K', default_hsn = '107', default_gst_rate = 5.00 WHERE id = ?", [$kidsCatId]);
}

$babySub = DB::fetch("SELECT id FROM subcategories WHERE name = 'BABY' AND category_id = ?", [$kidsCatId]);
if (!$babySub) {
    DB::query("INSERT INTO subcategories (concern_id, category_id, name, code, status) VALUES (1, ?, 'BABY', 'B', 'active')", [$kidsCatId]);
    $babySubId = (int)DB::lastInsertId();
} else {
    $babySubId = (int)$babySub['id'];
    DB::query("UPDATE subcategories SET code = 'B' WHERE id = ?", [$babySubId]);
}

$dressSubSub = DB::fetch("SELECT id FROM sub_subcategories WHERE name = 'DRESS' AND subcategory_id = ?", [$babySubId]);
if (!$dressSubSub) {
    DB::query("INSERT INTO sub_subcategories (concern_id, subcategory_id, name, code, status) VALUES (1, ?, 'DRESS', 'D', 'active')", [$babySubId]);
}

// Also check and update existing categories codes
DB::query("UPDATE categories SET code = 'RF', default_hsn = '5208', default_gst_rate = 5.00 WHERE name = 'Raw Fabric' AND (code IS NULL OR code = '')");
DB::query("UPDATE categories SET code = 'FG', default_hsn = '6109', default_gst_rate = 5.00 WHERE name = 'Apparel Finished Goods' AND (code IS NULL OR code = '')");
DB::query("UPDATE categories SET code = 'AT', default_hsn = '6109', default_gst_rate = 12.00 WHERE name = 'Accessories & Trims' AND (code IS NULL OR code = '')");

echo "Catalog schema migration & seed completed successfully!\n";
