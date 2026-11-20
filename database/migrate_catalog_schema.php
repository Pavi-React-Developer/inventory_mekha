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
    DB::query('ALTER TABLE categories ADD COLUMN default_gst_rate DECIMAL(5,2) NULL DEFAULT NULL AFTER default_hsn');
}
DB::query('ALTER TABLE categories MODIFY COLUMN default_gst_rate DECIMAL(5,2) NULL DEFAULT NULL');

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
    DB::query('ALTER TABLE catalogs ADD COLUMN gst_rate DECIMAL(5,2) NULL DEFAULT NULL AFTER hsn_code');
}
DB::query('ALTER TABLE catalogs MODIFY COLUMN gst_rate DECIMAL(5,2) NULL DEFAULT NULL');
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

// 6. Create hsn_masters table if not exists and seed standard HSN codes
require_once __DIR__ . '/hsn_gst_master.php';
ensureHsnGstMaster();

// 7. Seed sample categories removed - all categories are user-defined and dynamic
echo "Catalog schema migration completed successfully!\n";
