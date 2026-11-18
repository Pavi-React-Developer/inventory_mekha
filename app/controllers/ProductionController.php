<?php
// app/controllers/ProductionController.php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;
use App\Core\Auth;
use App\Services\StockService;
use App\Services\AuditLog;
use Exception;

class ProductionController extends Controller {

    private static bool $schemaEnsured = false;

    /**
     * Self-healing schema guard ensuring all 3-tier hierarchy & catalog & program tables exist
     */
    private function ensureCatalogSchema(): void {
        if (self::$schemaEnsured) return;
        try {
            // 1. Ensure sub_subcategories exists
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

            // 2. Ensure catalogs columns
            $catlgCols = array_column(DB::fetchAll('DESCRIBE `catalogs`'), 'Field');
            if (!in_array('style_code', $catlgCols)) {
                DB::query('ALTER TABLE `catalogs` ADD COLUMN style_code VARCHAR(50) NULL AFTER code');
            }
            if (!in_array('short_code', $catlgCols)) {
                DB::query('ALTER TABLE `catalogs` ADD COLUMN short_code VARCHAR(50) NULL AFTER style_code');
            }
            if (!in_array('category_id', $catlgCols)) {
                DB::query('ALTER TABLE `catalogs` ADD COLUMN category_id INT NULL AFTER short_code');
            }
            if (!in_array('subcategory_id', $catlgCols)) {
                DB::query('ALTER TABLE `catalogs` ADD COLUMN subcategory_id INT NULL AFTER category_id');
            }
            if (!in_array('sub_sub_category_id', $catlgCols)) {
                DB::query('ALTER TABLE `catalogs` ADD COLUMN sub_sub_category_id INT NULL AFTER subcategory_id');
            }
            if (!in_array('main_category_code', $catlgCols)) {
                DB::query('ALTER TABLE `catalogs` ADD COLUMN main_category_code VARCHAR(20) NULL AFTER sub_sub_category_id');
            }
            if (!in_array('sub_category_code', $catlgCols)) {
                DB::query('ALTER TABLE `catalogs` ADD COLUMN sub_category_code VARCHAR(20) NULL AFTER main_category_code');
            }
            if (!in_array('sub_sub_category_code', $catlgCols)) {
                DB::query('ALTER TABLE `catalogs` ADD COLUMN sub_sub_category_code VARCHAR(20) NULL AFTER sub_category_code');
            }
            if (!in_array('hierarchy_path', $catlgCols)) {
                DB::query('ALTER TABLE `catalogs` ADD COLUMN hierarchy_path VARCHAR(255) NULL AFTER sub_sub_category_code');
            }
            if (!in_array('hsn_code', $catlgCols)) {
                DB::query('ALTER TABLE `catalogs` ADD COLUMN hsn_code VARCHAR(50) NULL AFTER hierarchy_path');
            }
            if (!in_array('gst_rate', $catlgCols)) {
                DB::query('ALTER TABLE `catalogs` ADD COLUMN gst_rate DECIMAL(5,2) NOT NULL DEFAULT 5.00 AFTER hsn_code');
            }
            if (!in_array('sizes', $catlgCols)) {
                DB::query('ALTER TABLE `catalogs` ADD COLUMN sizes TEXT NULL AFTER gst_rate');
            }
            if (!in_array('status', $catlgCols)) {
                DB::query("ALTER TABLE `catalogs` ADD COLUMN status ENUM('active', 'inactive', 'in_house') NOT NULL DEFAULT 'in_house' AFTER sizes");
            }

            // 3. Ensure categories columns
            $catCols = array_column(DB::fetchAll('DESCRIBE `categories`'), 'Field');
            if (!in_array('code', $catCols)) {
                DB::query('ALTER TABLE `categories` ADD COLUMN code VARCHAR(20) NULL AFTER name');
            }
            if (!in_array('default_hsn', $catCols)) {
                DB::query('ALTER TABLE `categories` ADD COLUMN default_hsn VARCHAR(50) NULL AFTER code');
            }
            if (!in_array('default_gst_rate', $catCols)) {
                DB::query('ALTER TABLE `categories` ADD COLUMN default_gst_rate DECIMAL(5,2) NOT NULL DEFAULT 5.00 AFTER default_hsn');
            }

            // 4. Ensure subcategories columns
            $subCols = array_column(DB::fetchAll('DESCRIBE `subcategories`'), 'Field');
            if (!in_array('code', $subCols)) {
                DB::query('ALTER TABLE `subcategories` ADD COLUMN code VARCHAR(20) NULL AFTER name');
            }

            // 5. Ensure variants table columns
            $varCols = array_column(DB::fetchAll('DESCRIBE `variants`'), 'Field');
            if (!in_array('catalog_id', $varCols)) {
                DB::query('ALTER TABLE `variants` ADD COLUMN catalog_id INT NULL AFTER concern_id');
                DB::query('ALTER TABLE `variants` ADD INDEX idx_var_catalog (catalog_id)');
            }
            DB::query('ALTER TABLE `variants` MODIFY COLUMN subcategory_id INT NULL');

            // 6. Ensure production_programs columns & program_rolls table
            $progCols = array_column(DB::fetchAll('DESCRIBE `production_programs`'), 'Field');
            if (!in_array('program_name', $progCols)) {
                DB::query('ALTER TABLE `production_programs` ADD COLUMN program_name VARCHAR(255) NULL AFTER program_no');
            }
            if (!in_array('planner_name', $progCols)) {
                DB::query('ALTER TABLE `production_programs` ADD COLUMN planner_name VARCHAR(150) NULL AFTER program_name');
            }
            if (!in_array('updated_at', $progCols)) {
                DB::query('ALTER TABLE `production_programs` ADD COLUMN updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP');
            }
            DB::query('ALTER TABLE `production_programs` MODIFY COLUMN target_variant_id INT NULL');
            DB::query('ALTER TABLE `production_programs` MODIFY COLUMN planned_qty DECIMAL(12,2) NOT NULL DEFAULT 0.00');

            DB::query("CREATE TABLE IF NOT EXISTS `program_rolls` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `concern_id` INT NOT NULL,
                `program_id` INT NOT NULL,
                `purchase_bill_item_id` INT NULL,
                `roll_piece_tag` VARCHAR(100) NULL,
                `fabric_name` VARCHAR(255) NULL,
                `color` VARCHAR(100) NULL,
                `unit` VARCHAR(50) NOT NULL DEFAULT 'kg',
                `total_roll_qty` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                `allocated_qty` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                `target_pieces` INT NOT NULL DEFAULT 0,
                `category_id` INT NULL,
                `subcategory_id` INT NULL,
                `sub_sub_category_id` INT NULL,
                `catalog_id` INT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_prgroll_concern` (`concern_id`),
                INDEX `idx_prgroll_program` (`program_id`),
                INDEX `idx_prgroll_rollitem` (`purchase_bill_item_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            self::$schemaEnsured = true;
        } catch (\Throwable $e) {
            error_log("Schema ensure error: " . $e->getMessage());
        }
    }

    // ========================================================
    // 1. PRODUCTION CATALOG (MANUFACTURED GARMENT STYLES)
    // ========================================================

    /**
     * Display Production Catalog list view matching Image 1
     */
    public function catalogProgram(): void {
        $this->ensureCatalogSchema();
        $cid = Auth::concernId();

        // Filters
        $search = trim($_GET['search'] ?? '');
        $catFilter = !empty($_GET['category_id']) ? (int)$_GET['category_id'] : null;
        $subcatFilter = !empty($_GET['subcategory_id']) ? (int)$_GET['subcategory_id'] : null;

        // Build Catalog query
        $sql = "SELECT c.*, 
                       cat.name AS category_name, cat.code AS category_code_ref,
                       sub.name AS subcategory_name, sub.code AS subcategory_code_ref,
                       subsub.name AS subsub_name,
                       con.name AS concern_name, con.code AS concern_code,
                       (SELECT COUNT(*) FROM variants v WHERE v.catalog_id = c.id AND v.status = 'active') AS active_variants_count
                FROM catalogs c
                LEFT JOIN categories cat ON c.category_id = cat.id
                LEFT JOIN subcategories sub ON c.subcategory_id = sub.id
                LEFT JOIN sub_subcategories subsub ON c.sub_sub_category_id = subsub.id
                LEFT JOIN concerns con ON c.concern_id = con.id
                WHERE 1=1";
        
        $params = [];
        if ($cid) {
            $sql .= " AND c.concern_id = ?";
            $params[] = $cid;
        }

        if (!empty($search)) {
            $sql .= " AND (c.style_code LIKE ? OR c.short_code LIKE ? OR c.name LIKE ? OR c.hierarchy_path LIKE ? OR c.hsn_code LIKE ?)";
            $term = "%{$search}%";
            $params = array_merge($params, [$term, $term, $term, $term, $term]);
        }

        if ($catFilter) {
            $sql .= " AND c.category_id = ?";
            $params[] = $catFilter;
        }

        if ($subcatFilter) {
            $sql .= " AND c.subcategory_id = ?";
            $params[] = $subcatFilter;
        }

        $sql .= " ORDER BY c.id DESC";
        $styles = DB::fetchAll($sql, $params);

        // Fetch categories and subcategories for filter dropdowns
        if ($cid) {
            $categories = DB::fetchAll("SELECT * FROM categories WHERE concern_id = ? AND status = 'active' ORDER BY name ASC", [$cid]);
            $subcategories = DB::fetchAll("SELECT * FROM subcategories WHERE concern_id = ? AND status = 'active' ORDER BY name ASC", [$cid]);
            $variants = DB::fetchAll("SELECT id, sku_code, name FROM variants WHERE concern_id = ? AND status = 'active'", [$cid]);
            $catalogs = DB::fetchAll("SELECT id, name, style_code FROM catalogs WHERE concern_id = ? ORDER BY id DESC", [$cid]);
        } else {
            $categories = DB::fetchAll("SELECT * FROM categories WHERE status = 'active' ORDER BY name ASC");
            $subcategories = DB::fetchAll("SELECT * FROM subcategories WHERE status = 'active' ORDER BY name ASC");
            $variants = DB::fetchAll("SELECT id, sku_code, name FROM variants WHERE status = 'active'");
            $catalogs = DB::fetchAll("SELECT id, name, style_code FROM catalogs ORDER BY id DESC");
        }

        // Fetch Production Programs register for the tab
        $progSql = "SELECT pp.*, c.name AS catalog_name, v.name AS target_variant_name, v.sku_code,
                           con.name AS concern_name, con.code AS concern_code,
                           (SELECT COUNT(*) FROM program_rolls pr WHERE pr.program_id = pp.id) AS rolls_count
                    FROM production_programs pp
                    LEFT JOIN catalogs c ON pp.catalog_id = c.id
                    LEFT JOIN variants v ON pp.target_variant_id = v.id
                    LEFT JOIN concerns con ON pp.concern_id = con.id";
        $progParams = [];
        if ($cid) {
            $progSql .= " WHERE pp.concern_id = ?";
            $progParams[] = $cid;
        }
        $progSql .= " ORDER BY pp.id DESC";
        $programs = DB::fetchAll($progSql, $progParams);

        $this->render('concern/catalog_program', [
            'pageTitle'     => 'Production Catalog',
            'currentModule' => 'catalog_program',
            'styles'        => $styles,
            'programs'      => $programs,
            'categories'    => $categories,
            'subcategories' => $subcategories,
            'variants'      => $variants,
            'catalogs'      => $catalogs,
            'selectedCid'   => $cid,
            'filters'       => [
                'search'         => $search,
                'category_id'    => $catFilter,
                'subcategory_id' => $subcatFilter,
            ],
        ]);
    }

    /**
     * Create Product Style Form matching Image 2
     */
    public function createStyle(): void {
        $this->ensureCatalogSchema();
        $cid = Auth::concernId();

        if ($cid) {
            $categories = DB::fetchAll("SELECT * FROM categories WHERE concern_id = ? AND status = 'active' ORDER BY name ASC", [$cid]);
            $hsnList = DB::fetchAll("SELECT * FROM hsn_masters WHERE (concern_id = ? OR concern_id IS NULL) ORDER BY hsn_code ASC", [$cid]);
        } else {
            $categories = DB::fetchAll("SELECT * FROM categories WHERE status = 'active' ORDER BY name ASC");
            $hsnList = DB::fetchAll("SELECT * FROM hsn_masters ORDER BY hsn_code ASC");
        }

        $this->render('concern/catalog_create', [
            'pageTitle'     => 'Add Product Style',
            'currentModule' => 'catalog_program',
            'categories'    => $categories,
            'hsnList'       => $hsnList,
            'selectedCid'   => $cid,
            'isEdit'        => false,
            'style'         => null,
        ]);
    }

    /**
     * Edit Product Style Form matching Image 2
     */
    public function editStyle(): void {
        $this->ensureCatalogSchema();
        $cid = Auth::concernId();
        $id = (int)($_GET['id'] ?? 0);

        if (!$id) {
            $this->flash('error', 'Invalid Product Style ID.');
            $this->redirect('/production/catalog');
        }

        $sql = "SELECT * FROM catalogs WHERE id = ?";
        $params = [$id];
        if ($cid) {
            $sql .= " AND concern_id = ?";
            $params[] = $cid;
        }
        $style = DB::fetch($sql, $params);

        if (!$style) {
            $this->flash('error', 'Product Style not found or access denied.');
            $this->redirect('/production/catalog');
        }

        if ($cid) {
            $categories = DB::fetchAll("SELECT * FROM categories WHERE concern_id = ? AND status = 'active' ORDER BY name ASC", [$cid]);
            $hsnList = DB::fetchAll("SELECT * FROM hsn_masters WHERE (concern_id = ? OR concern_id IS NULL) ORDER BY hsn_code ASC", [$cid]);
            $subcategories = $style['category_id'] ? DB::fetchAll("SELECT * FROM subcategories WHERE category_id = ? AND concern_id = ? ORDER BY name ASC", [$style['category_id'], $cid]) : [];
            $subSubcategories = $style['subcategory_id'] ? DB::fetchAll("SELECT * FROM sub_subcategories WHERE subcategory_id = ? AND concern_id = ? ORDER BY name ASC", [$style['subcategory_id'], $cid]) : [];
        } else {
            $categories = DB::fetchAll("SELECT * FROM categories WHERE status = 'active' ORDER BY name ASC");
            $hsnList = DB::fetchAll("SELECT * FROM hsn_masters ORDER BY hsn_code ASC");
            $subcategories = $style['category_id'] ? DB::fetchAll("SELECT * FROM subcategories WHERE category_id = ? ORDER BY name ASC", [$style['category_id']]) : [];
            $subSubcategories = $style['subcategory_id'] ? DB::fetchAll("SELECT * FROM sub_subcategories WHERE subcategory_id = ? ORDER BY name ASC", [$style['subcategory_id']]) : [];
        }

        $this->render('concern/catalog_create', [
            'pageTitle'        => 'Edit Product Style',
            'currentModule'    => 'catalog_program',
            'categories'       => $categories,
            'subcategories'    => $subcategories,
            'subSubcategories' => $subSubcategories,
            'hsnList'          => $hsnList,
            'selectedCid'      => $cid,
            'isEdit'           => true,
            'style'            => $style,
        ]);
    }

    /**
     * Store new Product Style with comprehensive field validations
     */
    public function storeStyle(): void {
        $this->ensureCatalogSchema();
        $this->validateCsrf();
        $cid = Auth::concernId() ?: (int)($_POST['concern_id'] ?? 1);

        // 1. Validate Category Hierarchy
        $categoryId = (int)($_POST['category_id'] ?? 0);
        if ($categoryId <= 0) {
            $this->flash('error', 'Main Category is required. Please select a valid Category.');
            $this->redirect('/production/catalog/create');
        }

        $cat = DB::fetch("SELECT * FROM categories WHERE id = ?" . ($cid ? " AND concern_id = ?" : ""), $cid ? [$categoryId, $cid] : [$categoryId]);
        if (!$cat) {
            $this->flash('error', 'Selected Main Category does not exist or does not belong to your concern.');
            $this->redirect('/production/catalog/create');
        }

        $subcategoryId = !empty($_POST['subcategory_id']) ? (int)$_POST['subcategory_id'] : null;
        $subSubcategoryId = !empty($_POST['sub_sub_category_id']) ? (int)$_POST['sub_sub_category_id'] : null;

        // Category Codes Validation
        $mainCatCode = strtoupper(trim($_POST['main_category_code'] ?? ''));
        $subCatCode = strtoupper(trim($_POST['sub_category_code'] ?? ''));
        $subSubCatCode = strtoupper(trim($_POST['sub_sub_category_code'] ?? ''));
        $wholeCode = strtoupper(trim($_POST['whole_code'] ?? ''));

        if (empty($mainCatCode)) {
            $this->flash('error', 'Main Category short code is required.');
            $this->redirect('/production/catalog/create');
        }

        if (empty($wholeCode)) {
            $wholeCode = $mainCatCode . $subCatCode . $subSubCatCode;
        }

        if (strlen($wholeCode) < 1 || strlen($wholeCode) > 30) {
            $this->flash('error', 'Whole code for QR code must be between 1 and 30 characters.');
            $this->redirect('/production/catalog/create');
        }

        // 2. Validate HSN & GST Tax Classification
        $hsnCode = trim($_POST['hsn_code'] ?? '');
        $gstRate = isset($_POST['gst_rate']) ? (float)$_POST['gst_rate'] : 5.00;

        if (empty($hsnCode)) {
            $this->flash('error', 'HSN Code is required.');
            $this->redirect('/production/catalog/create');
        }

        if ($gstRate < 0 || $gstRate > 100) {
            $this->flash('error', 'GST Rate must be between 0.00% and 100.00%.');
            $this->redirect('/production/catalog/create');
        }

        // 3. Validate Product Attributes (Sizes)
        $sizes = $_POST['sizes'] ?? [];
        if (!is_array($sizes) || empty($sizes)) {
            $this->flash('error', 'At least one size attribute must be selected.');
            $this->redirect('/production/catalog/create');
        }

        $cleanSizes = [];
        foreach ($sizes as $s) {
            $val = trim($s);
            if ($val !== '' && !in_array($val, $cleanSizes)) {
                $cleanSizes[] = $val;
            }
        }

        if (empty($cleanSizes)) {
            $this->flash('error', 'Please select at least one valid size.');
            $this->redirect('/production/catalog/create');
        }

        // Resolve Hierarchy Path & Product Style Name
        $pathParts = [$cat['name']];
        if ($subcategoryId) {
            $sub = DB::fetch("SELECT name FROM subcategories WHERE id = ?", [$subcategoryId]);
            if ($sub) $pathParts[] = $sub['name'];
        }
        if ($subSubcategoryId) {
            $subsub = DB::fetch("SELECT name FROM sub_subcategories WHERE id = ?", [$subSubcategoryId]);
            if ($subsub) $pathParts[] = $subsub['name'];
        }
        $hierarchyPath = implode(' > ', $pathParts);
        $styleName = trim($_POST['name'] ?? '') ?: implode(' - ', $pathParts);

        // Generate Style Code e.g. GRM-STY-0001
        $con = DB::fetch("SELECT code FROM concerns WHERE id = ?", [$cid]);
        $conPrefix = 'STY';
        if ($con && !empty($con['code'])) {
            $cleanConCode = preg_replace('/[^A-Za-z0-9]/', '', str_replace(['CON-', 'CON'], '', strtoupper($con['code'])));
            $conPrefix = $cleanConCode ?: 'STY';
        }

        DB::beginTransaction();
        try {
            $nextSeq = (int)DB::fetchValue("SELECT COUNT(*) FROM catalogs WHERE concern_id = ?", [$cid]) + 1;
            $styleCode = sprintf("%s-STY-%04d", $conPrefix, $nextSeq);

            $dup = DB::fetch("SELECT id FROM catalogs WHERE concern_id = ? AND style_code = ?", [$cid, $styleCode]);
            if ($dup) {
                $styleCode = sprintf("%s-STY-%04d", $conPrefix, $nextSeq + rand(10, 99));
            }

            // Insert into catalogs
            DB::query(
                "INSERT INTO catalogs (
                    concern_id, code, style_code, short_code, 
                    category_id, subcategory_id, sub_sub_category_id,
                    main_category_code, sub_category_code, sub_sub_category_code,
                    hierarchy_path, hsn_code, gst_rate, sizes, status, name, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'in_house', ?, NOW())",
                [
                    $cid,
                    $styleCode,
                    $styleCode,
                    $wholeCode,
                    $categoryId,
                    $subcategoryId,
                    $subSubcategoryId,
                    $mainCatCode,
                    $subCatCode,
                    $subSubCatCode,
                    $hierarchyPath,
                    $hsnCode,
                    $gstRate,
                    json_encode($cleanSizes),
                    $styleName,
                ]
            );
            $catalogId = (int)DB::lastInsertId();

            // Create Variants for each selected size
            foreach ($cleanSizes as $size) {
                $sku = "{$styleCode}-{$size}";
                $varName = "{$styleName} - {$size}";

                $existVar = DB::fetch("SELECT id FROM variants WHERE concern_id = ? AND sku_code = ?", [$cid, $sku]);
                if (!$existVar) {
                    DB::query(
                        "INSERT INTO variants (
                            concern_id, catalog_id, sku_code, name, 
                            category_id, subcategory_id, size, 
                            price, mrp, creation_date, status, created_at
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, 0.00, 0.00, CURDATE(), 'active', NOW())",
                        [
                            $cid,
                            $catalogId,
                            $sku,
                            $varName,
                            $categoryId,
                            $subcategoryId ?: null,
                            $size,
                        ]
                    );
                } else {
                    DB::query("UPDATE variants SET catalog_id = ?, status = 'active' WHERE id = ?", [$catalogId, $existVar['id']]);
                }
            }

            // Audit Log
            AuditLog::log(
                'CREATE',
                'catalogs',
                $catalogId,
                null,
                [
                    'style_code'     => $styleCode,
                    'name'           => $styleName,
                    'hierarchy_path' => $hierarchyPath,
                    'hsn_code'       => $hsnCode,
                    'gst_rate'       => $gstRate,
                    'sizes'          => $cleanSizes,
                ]
            );

            DB::commit();
            $this->flash('success', "Product Style '{$styleCode}' ({$styleName}) saved successfully with " . count($cleanSizes) . " variants.");
        } catch (Exception $e) {
            DB::rollBack();
            $this->flash('error', 'Failed to save product style: ' . $e->getMessage());
            $this->redirect('/production/catalog/create');
        }

        $this->redirect('/production/catalog');
    }

    /**
     * Update existing Product Style
     */
    public function updateStyle(): void {
        $this->ensureCatalogSchema();
        $this->validateCsrf();
        $cid = Auth::concernId();
        $id = (int)($_POST['id'] ?? 0);

        if (!$id) {
            $this->flash('error', 'Invalid Style ID.');
            $this->redirect('/production/catalog');
        }

        $style = DB::fetch("SELECT * FROM catalogs WHERE id = ?" . ($cid ? " AND concern_id = ?" : ""), $cid ? [$id, $cid] : [$id]);
        if (!$style) {
            $this->flash('error', 'Style not found or access denied.');
            $this->redirect('/production/catalog');
        }

        $categoryId = (int)($_POST['category_id'] ?? 0);
        if ($categoryId <= 0) {
            $this->flash('error', 'Main Category is required.');
            $this->redirect('/production/catalog/edit?id=' . $id);
        }

        $subcategoryId = !empty($_POST['subcategory_id']) ? (int)$_POST['subcategory_id'] : null;
        $subSubcategoryId = !empty($_POST['sub_sub_category_id']) ? (int)$_POST['sub_sub_category_id'] : null;

        $mainCatCode = strtoupper(trim($_POST['main_category_code'] ?? ''));
        $subCatCode = strtoupper(trim($_POST['sub_category_code'] ?? ''));
        $subSubCatCode = strtoupper(trim($_POST['sub_sub_category_code'] ?? ''));
        $wholeCode = strtoupper(trim($_POST['whole_code'] ?? ''));
        if (empty($wholeCode)) {
            $wholeCode = $mainCatCode . $subCatCode . $subSubCatCode;
        }

        $hsnCode = trim($_POST['hsn_code'] ?? '');
        $gstRate = isset($_POST['gst_rate']) ? (float)$_POST['gst_rate'] : 5.00;

        if (empty($hsnCode)) {
            $this->flash('error', 'HSN Code is required.');
            $this->redirect('/production/catalog/edit?id=' . $id);
        }

        $sizes = $_POST['sizes'] ?? [];
        $cleanSizes = [];
        if (is_array($sizes)) {
            foreach ($sizes as $s) {
                $val = trim($s);
                if ($val !== '' && !in_array($val, $cleanSizes)) {
                    $cleanSizes[] = $val;
                }
            }
        }

        if (empty($cleanSizes)) {
            $this->flash('error', 'Please select at least one size.');
            $this->redirect('/production/catalog/edit?id=' . $id);
        }

        $cat = DB::fetch("SELECT name FROM categories WHERE id = ?", [$categoryId]);
        $pathParts = [$cat['name'] ?? ''];
        if ($subcategoryId) {
            $sub = DB::fetch("SELECT name FROM subcategories WHERE id = ?", [$subcategoryId]);
            if ($sub) $pathParts[] = $sub['name'];
        }
        if ($subSubcategoryId) {
            $subsub = DB::fetch("SELECT name FROM sub_subcategories WHERE id = ?", [$subSubcategoryId]);
            if ($subsub) $pathParts[] = $subsub['name'];
        }
        $hierarchyPath = implode(' > ', $pathParts);
        $styleName = trim($_POST['name'] ?? '') ?: implode(' - ', $pathParts);

        DB::beginTransaction();
        try {
            DB::query(
                "UPDATE catalogs SET 
                    short_code = ?,
                    category_id = ?,
                    subcategory_id = ?,
                    sub_sub_category_id = ?,
                    main_category_code = ?,
                    sub_category_code = ?,
                    sub_sub_category_code = ?,
                    hierarchy_path = ?,
                    hsn_code = ?,
                    gst_rate = ?,
                    sizes = ?,
                    name = ?,
                    updated_at = NOW()
                 WHERE id = ?",
                [
                    $wholeCode,
                    $categoryId,
                    $subcategoryId,
                    $subSubcategoryId,
                    $mainCatCode,
                    $subCatCode,
                    $subSubCatCode,
                    $hierarchyPath,
                    $hsnCode,
                    $gstRate,
                    json_encode($cleanSizes),
                    $styleName,
                    $id
                ]
            );

            // Synchronize variants
            foreach ($cleanSizes as $size) {
                $sku = "{$style['style_code']}-{$size}";
                $varName = "{$styleName} - {$size}";
                $existVar = DB::fetch("SELECT id FROM variants WHERE concern_id = ? AND catalog_id = ? AND size = ?", [$style['concern_id'], $id, $size]);
                if (!$existVar) {
                    DB::query(
                        "INSERT INTO variants (
                            concern_id, catalog_id, sku_code, name, 
                            category_id, subcategory_id, size, 
                            price, mrp, creation_date, status, created_at
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, 0.00, 0.00, CURDATE(), 'active', NOW())",
                        [
                            $style['concern_id'],
                            $id,
                            $sku,
                            $varName,
                            $categoryId,
                            $subcategoryId ?: null,
                            $size,
                        ]
                    );
                } else {
                    DB::query("UPDATE variants SET status = 'active', name = ? WHERE id = ?", [$varName, $existVar['id']]);
                }
            }

            DB::commit();
            $this->flash('success', "Product Style '{$style['style_code']}' updated successfully.");
        } catch (Exception $e) {
            DB::rollBack();
            $this->flash('error', 'Failed to update style: ' . $e->getMessage());
        }

        $this->redirect('/production/catalog');
    }

    /**
     * Delete Product Style
     */
    public function deleteStyle(): void {
        $this->ensureCatalogSchema();
        $this->validateCsrf();
        $cid = Auth::concernId();
        $id = (int)($_POST['id'] ?? 0);

        if (!$id) {
            $this->flash('error', 'Invalid Style ID.');
            $this->redirect('/production/catalog');
        }

        $style = DB::fetch("SELECT * FROM catalogs WHERE id = ?" . ($cid ? " AND concern_id = ?" : ""), $cid ? [$id, $cid] : [$id]);
        if (!$style) {
            $this->flash('error', 'Product Style not found or access denied.');
            $this->redirect('/production/catalog');
        }

        $hasStockLedger = (int)DB::fetchValue(
            "SELECT COUNT(*) FROM stock_ledger sl 
             JOIN variants v ON sl.variant_id = v.id 
             WHERE v.catalog_id = ?",
            [$id]
        );

        if ($hasStockLedger > 0) {
            DB::query("UPDATE catalogs SET status = 'inactive' WHERE id = ?", [$id]);
            DB::query("UPDATE variants SET status = 'inactive' WHERE catalog_id = ?", [$id]);
            $this->flash('warning', "Style '{$style['style_code']}' has stock transaction history and has been archived as Inactive.");
        } else {
            DB::beginTransaction();
            try {
                DB::query("DELETE FROM variants WHERE catalog_id = ?", [$id]);
                DB::query("DELETE FROM catalogs WHERE id = ?", [$id]);
                DB::commit();
                $this->flash('success', "Product Style '{$style['style_code']}' and its variants deleted successfully.");
            } catch (Exception $e) {
                DB::rollBack();
                $this->flash('error', 'Delete failed: ' . $e->getMessage());
            }
        }

        $this->redirect('/production/catalog');
    }

    // ========================================================
    // AJAX ENDPOINTS FOR DYNAMIC 3-TIER HIERARCHY & HSN
    // ========================================================

    public function getSubcategoriesAjax(): void {
        header('Content-Type: application/json');
        $this->ensureCatalogSchema();
        $cid = Auth::concernId();
        $catId = (int)($_GET['category_id'] ?? 0);

        if (!$catId) {
            echo json_encode(['success' => false, 'data' => []]);
            exit;
        }

        $sql = "SELECT id, name, code FROM subcategories WHERE category_id = ? AND status = 'active'";
        $params = [$catId];
        if ($cid) {
            $sql .= " AND concern_id = ?";
            $params[] = $cid;
        }
        $sql .= " ORDER BY name ASC";

        $rows = DB::fetchAll($sql, $params);
        echo json_encode(['success' => true, 'data' => $rows]);
        exit;
    }

    public function getSubSubcategoriesAjax(): void {
        header('Content-Type: application/json');
        $this->ensureCatalogSchema();
        $cid = Auth::concernId();
        $subcatId = (int)($_GET['subcategory_id'] ?? 0);

        if (!$subcatId) {
            echo json_encode(['success' => false, 'data' => []]);
            exit;
        }

        $sql = "SELECT id, name, code FROM sub_subcategories WHERE subcategory_id = ? AND status = 'active'";
        $params = [$subcatId];
        if ($cid) {
            $sql .= " AND concern_id = ?";
            $params[] = $cid;
        }
        $sql .= " ORDER BY name ASC";

        $rows = DB::fetchAll($sql, $params);
        echo json_encode(['success' => true, 'data' => $rows]);
        exit;
    }

    public function quickAddCategoryAjax(): void {
        header('Content-Type: application/json');
        $cid = Auth::concernId() ?: 1;
        $name = trim($_POST['name'] ?? '');
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $hsn  = trim($_POST['default_hsn'] ?? '6109');
        $gst  = (float)($_POST['default_gst_rate'] ?? 5.00);

        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Category name is required.']);
            exit;
        }

        if (empty($code)) {
            $code = strtoupper(substr($name, 0, 2));
        }

        try {
            DB::query(
                "INSERT INTO categories (concern_id, name, code, default_hsn, default_gst_rate, status) VALUES (?, ?, ?, ?, ?, 'active')",
                [$cid, $name, $code, $hsn, $gst]
            );
            $id = (int)DB::lastInsertId();
            echo json_encode([
                'success' => true, 
                'id' => $id, 
                'name' => $name, 
                'code' => $code, 
                'default_hsn' => $hsn, 
                'default_gst_rate' => $gst
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function quickAddSubcategoryAjax(): void {
        header('Content-Type: application/json');
        $cid = Auth::concernId() ?: 1;
        $catId = (int)($_POST['category_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $code = strtoupper(trim($_POST['code'] ?? ''));

        if (!$catId || empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Parent Category and Subcategory name are required.']);
            exit;
        }

        if (empty($code)) {
            $code = strtoupper(substr($name, 0, 2));
        }

        try {
            DB::query(
                "INSERT INTO subcategories (concern_id, category_id, name, code, status) VALUES (?, ?, ?, ?, 'active')",
                [$cid, $catId, $name, $code]
            );
            $id = (int)DB::lastInsertId();
            echo json_encode(['success' => true, 'id' => $id, 'name' => $name, 'code' => $code]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function quickAddSubSubcategoryAjax(): void {
        header('Content-Type: application/json');
        $cid = Auth::concernId() ?: 1;
        $subcatId = (int)($_POST['subcategory_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $code = strtoupper(trim($_POST['code'] ?? ''));

        if (!$subcatId || empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Parent Subcategory and Sub-Subcategory name are required.']);
            exit;
        }

        if (empty($code)) {
            $code = strtoupper(substr($name, 0, 2));
        }

        try {
            DB::query(
                "INSERT INTO sub_subcategories (concern_id, subcategory_id, name, code, status) VALUES (?, ?, ?, ?, 'active')",
                [$cid, $subcatId, $name, $code]
            );
            $id = (int)DB::lastInsertId();
            echo json_encode(['success' => true, 'id' => $id, 'name' => $name, 'code' => $code]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function searchHsnAjax(): void {
        header('Content-Type: application/json');
        $cid = Auth::concernId();
        $q = trim($_GET['q'] ?? '');

        $sql = "SELECT hsn_code, description, gst_rate FROM hsn_masters WHERE 1=1";
        $params = [];
        if ($cid) {
            $sql .= " AND (concern_id = ? OR concern_id IS NULL)";
            $params[] = $cid;
        }
        if (!empty($q)) {
            $sql .= " AND (hsn_code LIKE ? OR description LIKE ?)";
            $params[] = "%{$q}%";
            $params[] = "%{$q}%";
        }
        $sql .= " ORDER BY hsn_code ASC LIMIT 15";

        $rows = DB::fetchAll($sql, $params);
        echo json_encode(['success' => true, 'data' => $rows]);
        exit;
    }

    public function quickAddHsnAjax(): void {
        header('Content-Type: application/json');
        $cid = Auth::concernId();
        $hsn = trim($_POST['hsn_code'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $gst = (float)($_POST['gst_rate'] ?? 5.00);

        if (empty($hsn)) {
            echo json_encode(['success' => false, 'message' => 'HSN Code is required.']);
            exit;
        }

        try {
            DB::query(
                "INSERT INTO hsn_masters (concern_id, hsn_code, description, gst_rate) VALUES (?, ?, ?, ?)",
                [$cid, $hsn, $desc, $gst]
            );
            echo json_encode(['success' => true, 'hsn_code' => $hsn, 'description' => $desc, 'gst_rate' => $gst]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function styleDetailsAjax(): void {
        header('Content-Type: application/json');
        $cid = Auth::concernId();
        $id = (int)($_GET['id'] ?? 0);

        $style = DB::fetch("SELECT * FROM catalogs WHERE id = ?" . ($cid ? " AND concern_id = ?" : ""), $cid ? [$id, $cid] : [$id]);
        if (!$style) {
            echo json_encode(['success' => false, 'message' => 'Style not found.']);
            exit;
        }

        $variants = DB::fetchAll("SELECT * FROM variants WHERE catalog_id = ?" . ($cid ? " AND concern_id = ?" : ""), $cid ? [$id, $cid] : [$id]);

        echo json_encode([
            'success'  => true,
            'style'    => $style,
            'variants' => $variants,
        ]);
        exit;
    }

    // ========================================================
    // 2. PRODUCTION PROGRAMS (MULTI-ROLL SCHEDULING - IMAGE 2)
    // ========================================================

    /**
     * Programs list view (tab)
     */
    public function programsList(): void {
        $this->ensureCatalogSchema();
        $cid = Auth::concernId();
        $progSql = "SELECT pp.*, c.name AS catalog_name, v.name AS target_variant_name, v.sku_code,
                           con.name AS concern_name, con.code AS concern_code,
                           (SELECT COUNT(*) FROM program_rolls pr WHERE pr.program_id = pp.id) AS rolls_count
                    FROM production_programs pp
                    LEFT JOIN catalogs c ON pp.catalog_id = c.id
                    LEFT JOIN variants v ON pp.target_variant_id = v.id
                    LEFT JOIN concerns con ON pp.concern_id = con.id";
        $progParams = [];
        if ($cid) {
            $progSql .= " WHERE pp.concern_id = ?";
            $progParams[] = $cid;
        }
        $progSql .= " ORDER BY pp.id DESC";
        $programs = DB::fetchAll($progSql, $progParams);

        if ($cid) {
            $catalogs = DB::fetchAll("SELECT * FROM catalogs WHERE concern_id = ?", [$cid]);
            $variants = DB::fetchAll("SELECT id, sku_code, name FROM variants WHERE concern_id = ? AND status = 'active'", [$cid]);
        } else {
            $catalogs = DB::fetchAll("SELECT * FROM catalogs");
            $variants = DB::fetchAll("SELECT id, sku_code, name FROM variants WHERE status = 'active'");
        }

        $this->render('concern/catalog_program', [
            'pageTitle'     => 'Production Programs',
            'currentModule' => 'catalog_program',
            'styles'        => [],
            'programs'      => $programs,
            'categories'    => [],
            'subcategories' => [],
            'variants'      => $variants,
            'catalogs'      => $catalogs,
            'selectedCid'   => $cid,
            'activeTab'     => 'programs',
        ]);
    }

    /**
     * Legacy single-variant program store (backward compatibility)
     */
    public function storeProgram(): void {
        $this->ensureCatalogSchema();
        $this->validateCsrf();
        $cid = Auth::concernId() ?: (int)($_POST['concern_id'] ?? 0);

        if (!$cid) {
            $this->flash('error', 'Please choose a concern.');
            $this->redirect('/production/catalog');
        }

        $targetVarId = (int)($_POST['target_variant_id'] ?? 0);
        $plannedQty  = (float)($_POST['planned_qty'] ?? 0);
        $startDate   = $_POST['start_date'] ?: date('Y-m-d');
        $targetDate  = $_POST['target_date'] ?: date('Y-m-d', strtotime('+14 days'));
        $catalogId   = !empty($_POST['catalog_id']) ? (int)$_POST['catalog_id'] : null;

        if (!$targetVarId || $plannedQty <= 0) {
            $this->flash('error', 'Target Variant and Planned Quantity are required.');
            $this->redirect('/production/catalog');
        }

        DB::beginTransaction();
        try {
            $progNo = 'PRG-' . date('Ymd') . '-' . rand(100, 999);

            DB::query(
                "INSERT INTO production_programs (concern_id, program_no, catalog_id, target_variant_id, planned_qty, start_date, target_date, status, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'in_progress', NOW())",
                [$cid, $progNo, $catalogId, $targetVarId, $plannedQty, $startDate, $targetDate]
            );

            DB::commit();
            $this->flash('success', "Production Program '{$progNo}' scheduled for {$plannedQty} units.");
        } catch (Exception $e) {
            DB::rollBack();
            $this->flash('error', 'Failed to schedule program: ' . $e->getMessage());
        }

        $this->redirect('/production/catalog?tab=programs');
    }

    /**
     * Create Production Program Form matching Image 2 (Excluding Section 4)
     */
    public function createProgram(): void {
        $this->ensureCatalogSchema();
        $cid = Auth::concernId() ?: 1;

        // Auto-generate program code: # PRG-YYYYMMDD-XXXX
        $datePrefix = date('Ymd');
        $progCount = (int)DB::fetchValue("SELECT COUNT(*) FROM production_programs WHERE concern_id = ?", [$cid]) + 1;
        $suggestedCode = sprintf("PRG-%s-%04d", $datePrefix, $progCount);

        // Planner Name
        $plannerName = Auth::user()['name'] ?? 'System Administrator';

        // Load Available Fabric Rolls from purchase_bill_items
        $rollsSql = "SELECT pbi.id, pbi.item_code, pbi.item_name, pbi.color, pbi.unit,
                            pbi.net_weight, pbi.qty AS total_qty,
                            pb.bill_no, s.name AS supplier_name,
                            COALESCE((
                                SELECT SUM(pr.allocated_qty) 
                                FROM program_rolls pr 
                                JOIN production_programs pp ON pr.program_id = pp.id 
                                WHERE pr.purchase_bill_item_id = pbi.id 
                                  AND pp.status != 'cancelled'
                            ), 0.00) AS allocated_qty
                     FROM purchase_bill_items pbi
                     JOIN purchase_bills pb ON pbi.purchase_bill_id = pb.id
                     LEFT JOIN suppliers s ON pb.supplier_id = s.id
                     WHERE pbi.section_type = 'rolls' AND pbi.concern_id = ?
                     ORDER BY pbi.id DESC";
        $rawRolls = DB::fetchAll($rollsSql, [$cid]);

        $availableRolls = [];
        foreach ($rawRolls as $r) {
            $total = (float)$r['total_qty'];
            $allocated = (float)$r['allocated_qty'];
            $remaining = max(0.00, $total - $allocated);
            $r['remaining_qty'] = $remaining;
            $r['out_of_label'] = sprintf("%.2f %s out of %.2f %s", $remaining, $r['unit'], $total, $r['unit']);
            $availableRolls[] = $r;
        }

        // Load Categories for 3-tier hierarchy
        $categories = DB::fetchAll("SELECT * FROM categories WHERE concern_id = ? AND status = 'active' ORDER BY name ASC", [$cid]);
        $catalogs = DB::fetchAll("SELECT id, style_code, short_code, name FROM catalogs WHERE concern_id = ? AND status != 'inactive' ORDER BY id DESC", [$cid]);

        $this->render('concern/program_create', [
            'pageTitle'      => 'Create Production Program',
            'currentModule'  => 'catalog_program',
            'suggestedCode'  => $suggestedCode,
            'plannerName'    => $plannerName,
            'availableRolls' => $availableRolls,
            'categories'     => $categories,
            'catalogs'       => $catalogs,
            'selectedCid'    => $cid,
        ]);
    }

    /**
     * AJAX endpoint returning available rolls with calculated "out of qty"
     */
    public function apiAvailableRolls(): void {
        header('Content-Type: application/json');
        $this->ensureCatalogSchema();
        $cid = Auth::concernId() ?: 1;

        $rollsSql = "SELECT pbi.id, pbi.item_code, pbi.item_name, pbi.color, pbi.unit,
                            pbi.net_weight, pbi.qty AS total_qty,
                            pb.bill_no, s.name AS supplier_name,
                            COALESCE((
                                SELECT SUM(pr.allocated_qty) 
                                FROM program_rolls pr 
                                JOIN production_programs pp ON pr.program_id = pp.id 
                                WHERE pr.purchase_bill_item_id = pbi.id 
                                  AND pp.status != 'cancelled'
                            ), 0.00) AS allocated_qty
                     FROM purchase_bill_items pbi
                     JOIN purchase_bills pb ON pbi.purchase_bill_id = pb.id
                     LEFT JOIN suppliers s ON pb.supplier_id = s.id
                     WHERE pbi.section_type = 'rolls' AND pbi.concern_id = ?
                     ORDER BY pbi.id DESC";
        $rawRolls = DB::fetchAll($rollsSql, [$cid]);

        $data = [];
        foreach ($rawRolls as $r) {
            $total = (float)$r['total_qty'];
            $allocated = (float)$r['allocated_qty'];
            $remaining = max(0.00, $total - $allocated);
            $r['remaining_qty'] = $remaining;
            $r['out_of_label'] = sprintf("%.2f %s out of %.2f %s", $remaining, $r['unit'], $total, $r['unit']);
            $data[] = $r;
        }

        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }

    /**
     * AJAX endpoint for roll barcode / QR tag scan lookup
     */
    public function apiRollLookup(): void {
        header('Content-Type: application/json');
        $this->ensureCatalogSchema();
        $cid = Auth::concernId() ?: 1;
        $tag = trim($_GET['tag'] ?? '');

        if (empty($tag)) {
            echo json_encode(['success' => false, 'message' => 'Piece tag or barcode required.']);
            exit;
        }

        $sql = "SELECT pbi.id, pbi.item_code, pbi.item_name, pbi.color, pbi.unit,
                       pbi.net_weight, pbi.qty AS total_qty,
                       pb.bill_no, s.name AS supplier_name,
                       COALESCE((
                           SELECT SUM(pr.allocated_qty) 
                           FROM program_rolls pr 
                           JOIN production_programs pp ON pr.program_id = pp.id 
                           WHERE pr.purchase_bill_item_id = pbi.id 
                             AND pp.status != 'cancelled'
                       ), 0.00) AS allocated_qty
                FROM purchase_bill_items pbi
                JOIN purchase_bills pb ON pbi.purchase_bill_id = pb.id
                LEFT JOIN suppliers s ON pb.supplier_id = s.id
                WHERE pbi.section_type = 'rolls' 
                  AND pbi.concern_id = ?
                  AND (pbi.item_code = ? OR pbi.item_name LIKE ?)
                LIMIT 1";
        $row = DB::fetch($sql, [$cid, $tag, "%{$tag}%"]);

        if (!$row) {
            echo json_encode(['success' => false, 'message' => "No purchased fabric roll found matching '{$tag}'."]);
            exit;
        }

        $total = (float)$row['total_qty'];
        $allocated = (float)$row['allocated_qty'];
        $remaining = max(0.00, $total - $allocated);
        $row['remaining_qty'] = $remaining;
        $row['out_of_label'] = sprintf("%.2f %s out of %.2f %s", $remaining, $row['unit'], $total, $row['unit']);

        echo json_encode(['success' => true, 'roll' => $row]);
        exit;
    }

    /**
     * Store Production Program with Multi-Roll Allocation & "Out of Qty" Validations (Image 2)
     */
    public function storeProgramV2(): void {
        $this->ensureCatalogSchema();
        $this->validateCsrf();
        $cid = Auth::concernId() ?: 1;

        // 1. Program Overview Validations
        $programName = trim($_POST['program_name'] ?? '');
        $programCode = trim($_POST['program_no'] ?? '');
        $plannerName = trim($_POST['planner_name'] ?? (Auth::user()['name'] ?? 'System Administrator'));

        if (empty($programName) || strlen($programName) < 3) {
            $this->flash('error', 'Program Name is required (minimum 3 characters).');
            $this->redirect('/production/programs/create');
        }

        if (empty($programCode)) {
            $progCount = (int)DB::fetchValue("SELECT COUNT(*) FROM production_programs WHERE concern_id = ?", [$cid]) + 1;
            $programCode = sprintf("PRG-%s-%04d", date('Ymd'), $progCount);
        }

        // 2. Timeline Validations
        $startDate = !empty($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-d');
        $targetDate = !empty($_POST['target_date']) ? $_POST['target_date'] : date('Y-m-d', strtotime('+14 days'));

        if (strtotime($targetDate) < strtotime($startDate)) {
            $this->flash('error', 'Target Completion Date cannot precede Programming Start Date.');
            $this->redirect('/production/programs/create');
        }

        // 3. Rolls Allocation Validations
        $rolls = $_POST['rolls'] ?? [];
        if (!is_array($rolls) || empty($rolls)) {
            $this->flash('error', 'Please assign at least one raw material fabric roll.');
            $this->redirect('/production/programs/create');
        }

        $validatedRolls = [];
        $totalPlannedQty = 0.00;

        foreach ($rolls as $index => $r) {
            $rollItemId = (int)($r['purchase_bill_item_id'] ?? 0);
            $allocatedQty = (float)($r['allocated_qty'] ?? 0);
            $targetPieces = (int)($r['target_pieces'] ?? 0);
            $catId = !empty($r['category_id']) ? (int)$r['category_id'] : null;
            $subcatId = !empty($r['subcategory_id']) ? (int)$r['subcategory_id'] : null;
            $subSubcatId = !empty($r['sub_sub_category_id']) ? (int)$r['sub_sub_category_id'] : null;
            $catalogId = !empty($r['catalog_id']) ? (int)$r['catalog_id'] : null;

            if ($allocatedQty <= 0) {
                $this->flash('error', "Roll #" . ($index + 1) . ": Allocated quantity must be greater than zero.");
                $this->redirect('/production/programs/create');
            }

            // Fetch roll and verify balance
            $rollItem = DB::fetch(
                "SELECT pbi.*, 
                        COALESCE((
                            SELECT SUM(pr.allocated_qty) 
                            FROM program_rolls pr 
                            JOIN production_programs pp ON pr.program_id = pp.id 
                            WHERE pr.purchase_bill_item_id = pbi.id 
                              AND pp.status != 'cancelled'
                        ), 0.00) AS used_qty
                 FROM purchase_bill_items pbi 
                 WHERE pbi.id = ? AND pbi.concern_id = ?",
                [$rollItemId, $cid]
            );

            if (!$rollItem) {
                $this->flash('error', "Roll #" . ($index + 1) . ": Invalid purchased roll selected.");
                $this->redirect('/production/programs/create');
            }

            $totalRollQty = (float)$rollItem['qty'];
            $usedQty = (float)$rollItem['used_qty'];
            $availableQty = max(0.00, $totalRollQty - $usedQty);

            // Strict Out-of-Qty validation!
            if ($allocatedQty > ($availableQty + 0.001)) {
                $tag = $rollItem['item_code'] ?: ('Item #' . $rollItem['id']);
                $this->flash('error', "Roll #" . ($index + 1) . " ({$tag}): Allocated quantity ({$allocatedQty} {$rollItem['unit']}) exceeds available balance ({$availableQty} {$rollItem['unit']} out of {$totalRollQty} {$rollItem['unit']}).");
                $this->redirect('/production/programs/create');
            }

            $totalPlannedQty += $allocatedQty;

            $validatedRolls[] = [
                'purchase_bill_item_id' => $rollItemId,
                'roll_piece_tag'        => $rollItem['item_code'] ?? '',
                'fabric_name'           => $rollItem['item_name'] ?? '',
                'color'                 => $rollItem['color'] ?? '',
                'unit'                  => $rollItem['unit'] ?? 'kg',
                'total_roll_qty'        => $totalRollQty,
                'allocated_qty'         => $allocatedQty,
                'target_pieces'         => $targetPieces,
                'category_id'           => $catId,
                'subcategory_id'        => $subcatId,
                'sub_sub_category_id'   => $subSubcatId,
                'catalog_id'            => $catalogId,
            ];
        }

        DB::beginTransaction();
        try {
            // Insert production_program
            DB::query(
                "INSERT INTO production_programs (
                    concern_id, program_no, program_name, planner_name,
                    catalog_id, target_variant_id, planned_qty,
                    start_date, target_date, status, created_at
                ) VALUES (?, ?, ?, ?, ?, NULL, ?, ?, ?, 'planned', NOW())",
                [
                    $cid,
                    $programCode,
                    $programName,
                    $plannerName,
                    $validatedRolls[0]['catalog_id'] ?? null,
                    $totalPlannedQty,
                    $startDate,
                    $targetDate
                ]
            );
            $programId = (int)DB::lastInsertId();

            // Insert program_rolls mappings
            foreach ($validatedRolls as $vr) {
                DB::query(
                    "INSERT INTO program_rolls (
                        concern_id, program_id, purchase_bill_item_id,
                        roll_piece_tag, fabric_name, color, unit,
                        total_roll_qty, allocated_qty, target_pieces,
                        category_id, subcategory_id, sub_sub_category_id, catalog_id,
                        created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
                    [
                        $cid,
                        $programId,
                        $vr['purchase_bill_item_id'],
                        $vr['roll_piece_tag'],
                        $vr['fabric_name'],
                        $vr['color'],
                        $vr['unit'],
                        $vr['total_roll_qty'],
                        $vr['allocated_qty'],
                        $vr['target_pieces'],
                        $vr['category_id'],
                        $vr['subcategory_id'],
                        $vr['sub_sub_category_id'],
                        $vr['catalog_id'],
                    ]
                );
            }

            // Audit Log
            AuditLog::log(
                'CREATE',
                'production_programs',
                $programId,
                null,
                [
                    'program_no'   => $programCode,
                    'program_name' => $programName,
                    'planner'      => $plannerName,
                    'total_qty'    => $totalPlannedQty,
                    'rolls_count'  => count($validatedRolls),
                ]
            );

            DB::commit();
            $this->flash('success', "Production Program '{$programCode}' ({$programName}) scheduled successfully with " . count($validatedRolls) . " fabric roll allocations.");
        } catch (Exception $e) {
            DB::rollBack();
            $this->flash('error', 'Failed to create program: ' . $e->getMessage());
            $this->redirect('/production/programs/create');
        }

        $this->redirect('/production/catalog?tab=programs');
    }

    /**
     * View Production Program details (Full-Page View)
     */
    public function viewProgram(): void {
        $this->ensureCatalogSchema();
        $cid = Auth::concernId();
        $id = (int)($_GET['id'] ?? 0);

        if (!$id) {
            $this->flash('error', 'Invalid program ID.');
            $this->redirect('/production/catalog?tab=programs');
        }

        $sql = "SELECT pp.*, c.name AS catalog_name, c.style_code AS catalog_code,
                       v.name AS target_variant_name, v.sku_code,
                       con.name AS concern_name, con.code AS concern_code
                FROM production_programs pp
                LEFT JOIN catalogs c ON pp.catalog_id = c.id
                LEFT JOIN variants v ON pp.target_variant_id = v.id
                LEFT JOIN concerns con ON pp.concern_id = con.id
                WHERE pp.id = ?";
        $params = [$id];
        if ($cid) {
            $sql .= " AND pp.concern_id = ?";
            $params[] = $cid;
        }

        $program = DB::fetch($sql, $params);
        if (!$program) {
            $this->flash('error', 'Program not found or access denied.');
            $this->redirect('/production/catalog?tab=programs');
        }

        $rolls = DB::fetchAll(
            "SELECT pr.*, 
                    pbi.item_code AS original_piece_tag, pbi.item_name AS roll_material, pbi.color AS roll_color, pbi.unit AS roll_unit, pbi.qty AS original_roll_qty,
                    cat.name AS category_name, sub.name AS subcategory_name, subsub.name AS subsub_name,
                    c.name AS catalog_style_name, c.style_code AS catalog_style_code
             FROM program_rolls pr
             LEFT JOIN purchase_bill_items pbi ON pr.purchase_bill_item_id = pbi.id
             LEFT JOIN categories cat ON pr.category_id = cat.id
             LEFT JOIN subcategories sub ON pr.subcategory_id = sub.id
             LEFT JOIN sub_subcategories subsub ON pr.sub_sub_category_id = subsub.id
             LEFT JOIN catalogs c ON pr.catalog_id = c.id
             WHERE pr.program_id = ?
             ORDER BY pr.id ASC",
            [$id]
        );

        $cuttingStats = DB::fetch(
            "SELECT COUNT(*) AS total_lots, COALESCE(SUM(consumed_qty), 0) AS total_consumed, COALESCE(SUM(cut_pieces), 0) AS total_cut_pieces 
             FROM cutting_logs WHERE program_id = ?",
            [$id]
        );

        $this->render('concern/program_view', [
            'pageTitle'     => 'Production Program: ' . $program['program_no'],
            'currentModule' => 'catalog_program',
            'program'       => $program,
            'rolls'         => $rolls,
            'cutting'       => $cuttingStats,
            'selectedCid'   => $cid,
        ]);
    }

    /**
     * AJAX endpoint for Production Program details (Used by Quick View Modal)
     */
    public function apiProgramDetails(): void {
        header('Content-Type: application/json');
        $this->ensureCatalogSchema();
        $cid = Auth::concernId();
        $id = (int)($_GET['id'] ?? 0);

        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Invalid program ID.']);
            exit;
        }

        $sql = "SELECT pp.*, c.name AS catalog_name, c.style_code AS catalog_code,
                       v.name AS target_variant_name, v.sku_code,
                       con.name AS concern_name, con.code AS concern_code
                FROM production_programs pp
                LEFT JOIN catalogs c ON pp.catalog_id = c.id
                LEFT JOIN variants v ON pp.target_variant_id = v.id
                LEFT JOIN concerns con ON pp.concern_id = con.id
                WHERE pp.id = ?";
        $params = [$id];
        if ($cid) {
            $sql .= " AND pp.concern_id = ?";
            $params[] = $cid;
        }

        $program = DB::fetch($sql, $params);
        if (!$program) {
            echo json_encode(['success' => false, 'message' => 'Program not found or access denied.']);
            exit;
        }

        $rolls = DB::fetchAll(
            "SELECT pr.*, 
                    pbi.item_code AS original_piece_tag, pbi.item_name AS roll_material, pbi.color AS roll_color, pbi.unit AS roll_unit, pbi.qty AS original_roll_qty,
                    cat.name AS category_name, sub.name AS subcategory_name, subsub.name AS subsub_name,
                    c.name AS catalog_style_name, c.style_code AS catalog_style_code
             FROM program_rolls pr
             LEFT JOIN purchase_bill_items pbi ON pr.purchase_bill_item_id = pbi.id
             LEFT JOIN categories cat ON pr.category_id = cat.id
             LEFT JOIN subcategories sub ON pr.subcategory_id = sub.id
             LEFT JOIN sub_subcategories subsub ON pr.sub_sub_category_id = subsub.id
             LEFT JOIN catalogs c ON pr.catalog_id = c.id
             WHERE pr.program_id = ?
             ORDER BY pr.id ASC",
            [$id]
        );

        $cuttingStats = DB::fetch(
            "SELECT COUNT(*) AS total_lots, COALESCE(SUM(consumed_qty), 0) AS total_consumed, COALESCE(SUM(cut_pieces), 0) AS total_cut_pieces 
             FROM cutting_logs WHERE program_id = ?",
            [$id]
        );

        echo json_encode([
            'success' => true,
            'program' => $program,
            'rolls'   => $rolls,
            'cutting' => $cuttingStats,
        ]);
        exit;
    }

    /**
     * Edit Production Program Form
     */
    public function editProgram(): void {
        $this->ensureCatalogSchema();
        $cid = Auth::concernId() ?: 1;
        $id = (int)($_GET['id'] ?? 0);

        if (!$id) {
            $this->flash('error', 'Invalid program ID.');
            $this->redirect('/production/catalog?tab=programs');
        }

        $program = DB::fetch(
            "SELECT * FROM production_programs WHERE id = ?" . ($cid ? " AND concern_id = ?" : ""),
            $cid ? [$id, $cid] : [$id]
        );
        if (!$program) {
            $this->flash('error', 'Program not found or access denied.');
            $this->redirect('/production/catalog?tab=programs');
        }

        $existingRolls = DB::fetchAll(
            "SELECT pr.*, 
                    pbi.item_code AS current_roll_code, pbi.item_name AS current_roll_name, pbi.color AS current_roll_color, pbi.unit AS current_unit, pbi.qty AS original_total_qty
             FROM program_rolls pr
             LEFT JOIN purchase_bill_items pbi ON pr.purchase_bill_item_id = pbi.id
             WHERE pr.program_id = ?
             ORDER BY pr.id ASC",
            [$id]
        );

        // Load Available Fabric Rolls for this concern, discounting allocations in OTHER programs
        $rollsSql = "SELECT pbi.id, pbi.item_code, pbi.item_name, pbi.color, pbi.unit,
                            pbi.net_weight, pbi.qty AS total_qty,
                            pb.bill_no, s.name AS supplier_name,
                            COALESCE((
                                SELECT SUM(pr.allocated_qty) 
                                FROM program_rolls pr 
                                JOIN production_programs pp ON pr.program_id = pp.id 
                                WHERE pr.purchase_bill_item_id = pbi.id 
                                  AND pp.status != 'cancelled'
                                  AND pp.id != ?
                            ), 0.00) AS allocated_in_other_programs
                     FROM purchase_bill_items pbi
                     JOIN purchase_bills pb ON pbi.purchase_bill_id = pb.id
                     LEFT JOIN suppliers s ON pb.supplier_id = s.id
                     WHERE pbi.section_type = 'rolls' AND pbi.concern_id = ?
                     ORDER BY pbi.id DESC";
        $rawRolls = DB::fetchAll($rollsSql, [$id, $cid]);

        $availableRolls = [];
        foreach ($rawRolls as $r) {
            $total = (float)$r['total_qty'];
            $allocatedOther = (float)$r['allocated_in_other_programs'];
            $remaining = max(0.00, $total - $allocatedOther);
            $r['remaining_qty'] = $remaining;
            $r['out_of_label'] = sprintf("%.2f %s out of %.2f %s", $remaining, $r['unit'], $total, $r['unit']);
            $availableRolls[] = $r;
        }

        $categories = DB::fetchAll("SELECT * FROM categories WHERE concern_id = ? AND status = 'active' ORDER BY name ASC", [$cid]);
        $subcategories = DB::fetchAll("SELECT * FROM subcategories WHERE concern_id = ? AND status = 'active' ORDER BY name ASC", [$cid]);
        $subSubcategories = DB::fetchAll("SELECT * FROM sub_subcategories WHERE concern_id = ? AND status = 'active' ORDER BY name ASC", [$cid]);
        $catalogs = DB::fetchAll("SELECT id, style_code, short_code, name FROM catalogs WHERE concern_id = ? AND status != 'inactive' ORDER BY id DESC", [$cid]);

        $this->render('concern/program_edit', [
            'pageTitle'        => 'Edit Production Program: ' . $program['program_no'],
            'currentModule'    => 'catalog_program',
            'program'          => $program,
            'existingRolls'    => $existingRolls,
            'availableRolls'   => $availableRolls,
            'categories'       => $categories,
            'subcategories'    => $subcategories,
            'subSubcategories' => $subSubcategories,
            'catalogs'         => $catalogs,
            'selectedCid'      => $cid,
        ]);
    }

    /**
     * Update Production Program
     */
    public function updateProgram(): void {
        $this->ensureCatalogSchema();
        $this->validateCsrf();
        $cid = Auth::concernId() ?: 1;
        $id = (int)($_POST['id'] ?? 0);

        if (!$id) {
            $this->flash('error', 'Invalid program ID.');
            $this->redirect('/production/catalog?tab=programs');
        }

        $program = DB::fetch(
            "SELECT * FROM production_programs WHERE id = ?" . ($cid ? " AND concern_id = ?" : ""),
            $cid ? [$id, $cid] : [$id]
        );
        if (!$program) {
            $this->flash('error', 'Program not found or access denied.');
            $this->redirect('/production/catalog?tab=programs');
        }

        $programName = trim($_POST['program_name'] ?? '');
        $status = $_POST['status'] ?? $program['status'];
        if (!in_array($status, ['planned', 'in_progress', 'completed', 'cancelled'])) {
            $status = 'planned';
        }

        if (empty($programName) || strlen($programName) < 3) {
            $this->flash('error', 'Program Name is required (minimum 3 characters).');
            $this->redirect('/production/programs/edit?id=' . $id);
        }

        $startDate = !empty($_POST['start_date']) ? $_POST['start_date'] : $program['start_date'];
        $targetDate = !empty($_POST['target_date']) ? $_POST['target_date'] : $program['target_date'];

        if (strtotime($targetDate) < strtotime($startDate)) {
            $this->flash('error', 'Target Completion Date cannot precede Programming Start Date.');
            $this->redirect('/production/programs/edit?id=' . $id);
        }

        $rolls = $_POST['rolls'] ?? [];
        if (!is_array($rolls) || empty($rolls)) {
            $this->flash('error', 'Please assign at least one raw material fabric roll.');
            $this->redirect('/production/programs/edit?id=' . $id);
        }

        $validatedRolls = [];
        $totalPlannedQty = 0.00;

        foreach ($rolls as $index => $r) {
            $rollItemId = (int)($r['purchase_bill_item_id'] ?? 0);
            $allocatedQty = (float)($r['allocated_qty'] ?? 0);
            $targetPieces = (int)($r['target_pieces'] ?? 0);
            $catId = !empty($r['category_id']) ? (int)$r['category_id'] : null;
            $subcatId = !empty($r['subcategory_id']) ? (int)$r['subcategory_id'] : null;
            $subSubcatId = !empty($r['sub_sub_category_id']) ? (int)$r['sub_sub_category_id'] : null;
            $catalogId = !empty($r['catalog_id']) ? (int)$r['catalog_id'] : null;

            if ($allocatedQty <= 0) {
                $this->flash('error', "Roll #" . ($index + 1) . ": Allocated quantity must be greater than zero.");
                $this->redirect('/production/programs/edit?id=' . $id);
            }

            // Fetch roll and verify balance (excluding this program's current allocation)
            $rollItem = DB::fetch(
                "SELECT pbi.*, 
                        COALESCE((
                            SELECT SUM(pr.allocated_qty) 
                            FROM program_rolls pr 
                            JOIN production_programs pp ON pr.program_id = pp.id 
                            WHERE pr.purchase_bill_item_id = pbi.id 
                              AND pp.status != 'cancelled'
                              AND pp.id != ?
                        ), 0.00) AS used_qty_other
                 FROM purchase_bill_items pbi 
                 WHERE pbi.id = ? AND pbi.concern_id = ?",
                [$id, $rollItemId, $cid]
            );

            if (!$rollItem) {
                $this->flash('error', "Roll #" . ($index + 1) . ": Invalid purchased roll selected.");
                $this->redirect('/production/programs/edit?id=' . $id);
            }

            $totalRollQty = (float)$rollItem['qty'];
            $usedQtyOther = (float)$rollItem['used_qty_other'];
            $availableQty = max(0.00, $totalRollQty - $usedQtyOther);

            if ($allocatedQty > ($availableQty + 0.001)) {
                $tag = $rollItem['item_code'] ?: ('Item #' . $rollItem['id']);
                $this->flash('error', "Roll #" . ($index + 1) . " ({$tag}): Allocated quantity ({$allocatedQty} {$rollItem['unit']}) exceeds available balance ({$availableQty} {$rollItem['unit']} out of {$totalRollQty} {$rollItem['unit']}).");
                $this->redirect('/production/programs/edit?id=' . $id);
            }

            $totalPlannedQty += $allocatedQty;

            $validatedRolls[] = [
                'purchase_bill_item_id' => $rollItemId,
                'roll_piece_tag'        => $rollItem['item_code'] ?? '',
                'fabric_name'           => $rollItem['item_name'] ?? '',
                'color'                 => $rollItem['color'] ?? '',
                'unit'                  => $rollItem['unit'] ?? 'kg',
                'total_roll_qty'        => $totalRollQty,
                'allocated_qty'         => $allocatedQty,
                'target_pieces'         => $targetPieces,
                'category_id'           => $catId,
                'subcategory_id'        => $subcatId,
                'sub_sub_category_id'   => $subSubcatId,
                'catalog_id'            => $catalogId,
            ];
        }

        DB::beginTransaction();
        try {
            DB::query(
                "UPDATE production_programs SET
                    program_name = ?,
                    catalog_id = ?,
                    planned_qty = ?,
                    start_date = ?,
                    target_date = ?,
                    status = ?,
                    updated_at = NOW()
                 WHERE id = ?",
                [
                    $programName,
                    $validatedRolls[0]['catalog_id'] ?? null,
                    $totalPlannedQty,
                    $startDate,
                    $targetDate,
                    $status,
                    $id
                ]
            );

            // Re-sync program rolls
            DB::query("DELETE FROM program_rolls WHERE program_id = ?", [$id]);

            foreach ($validatedRolls as $vr) {
                DB::query(
                    "INSERT INTO program_rolls (
                        concern_id, program_id, purchase_bill_item_id,
                        roll_piece_tag, fabric_name, color, unit,
                        total_roll_qty, allocated_qty, target_pieces,
                        category_id, subcategory_id, sub_sub_category_id, catalog_id,
                        created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
                    [
                        $cid,
                        $id,
                        $vr['purchase_bill_item_id'],
                        $vr['roll_piece_tag'],
                        $vr['fabric_name'],
                        $vr['color'],
                        $vr['unit'],
                        $vr['total_roll_qty'],
                        $vr['allocated_qty'],
                        $vr['target_pieces'],
                        $vr['category_id'],
                        $vr['subcategory_id'],
                        $vr['sub_sub_category_id'],
                        $vr['catalog_id'],
                    ]
                );
            }

            AuditLog::log('UPDATE', 'production_programs', $id, $program, [
                'program_name' => $programName,
                'status'       => $status,
                'planned_qty'  => $totalPlannedQty,
                'rolls_count'  => count($validatedRolls),
            ]);

            DB::commit();
            $this->flash('success', "Production Program '{$program['program_no']}' updated successfully.");
        } catch (Exception $e) {
            DB::rollBack();
            $this->flash('error', 'Failed to update program: ' . $e->getMessage());
            $this->redirect('/production/programs/edit?id=' . $id);
        }

        $this->redirect('/production/catalog?tab=programs');
    }

    /**
     * Delete or Cancel Production Program
     */
    public function deleteProgram(): void {
        $this->ensureCatalogSchema();
        $this->validateCsrf();
        $cid = Auth::concernId();
        $id = (int)($_POST['id'] ?? 0);

        if (!$id) {
            $this->flash('error', 'Invalid program ID.');
            $this->redirect('/production/catalog?tab=programs');
        }

        $program = DB::fetch("SELECT * FROM production_programs WHERE id = ?" . ($cid ? " AND concern_id = ?" : ""), $cid ? [$id, $cid] : [$id]);
        if (!$program) {
            $this->flash('error', 'Program not found or access denied.');
            $this->redirect('/production/catalog?tab=programs');
        }

        // Check if downstream logs exist
        $hasCutting = (int)DB::fetchValue("SELECT COUNT(*) FROM cutting_logs WHERE program_id = ?", [$id]);
        $hasStitching = (int)DB::fetchValue("SELECT COUNT(*) FROM stitching_logs WHERE program_id = ?", [$id]);

        if ($hasCutting > 0 || $hasStitching > 0) {
            // Cannot hard delete because cutting/stitching consumed goods. Mark cancelled.
            DB::query("UPDATE production_programs SET status = 'cancelled', updated_at = NOW() WHERE id = ?", [$id]);
            AuditLog::log('UPDATE', 'production_programs', $id, $program, ['status' => 'cancelled']);
            $this->flash('warning', "Program '{$program['program_no']}' has active cutting/production history. It has been marked as Cancelled rather than deleted to protect ledger integrity.");
        } else {
            DB::beginTransaction();
            try {
                DB::query("DELETE FROM program_rolls WHERE program_id = ?", [$id]);
                DB::query("DELETE FROM production_programs WHERE id = ?", [$id]);
                AuditLog::log('DELETE', 'production_programs', $id, $program, null);
                DB::commit();
                $this->flash('success', "Production Program '{$program['program_no']}' deleted successfully and fabric rolls released back to stock.");
            } catch (Exception $e) {
                DB::rollBack();
                $this->flash('error', 'Delete failed: ' . $e->getMessage());
            }
        }

        $this->redirect('/production/catalog?tab=programs');
    }

    /**
     * Quick status update for Production Program
     */
    public function updateProgramStatus(): void {
        $this->ensureCatalogSchema();
        $this->validateCsrf();
        $cid = Auth::concernId();
        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';

        if (!in_array($status, ['planned', 'in_progress', 'completed', 'cancelled'])) {
            $this->flash('error', 'Invalid status specified.');
            $this->redirect('/production/catalog?tab=programs');
        }

        $program = DB::fetch("SELECT * FROM production_programs WHERE id = ?" . ($cid ? " AND concern_id = ?" : ""), $cid ? [$id, $cid] : [$id]);
        if (!$program) {
            $this->flash('error', 'Program not found or access denied.');
            $this->redirect('/production/catalog?tab=programs');
        }

        DB::query("UPDATE production_programs SET status = ?, updated_at = NOW() WHERE id = ?", [$status, $id]);
        AuditLog::log('UPDATE', 'production_programs', $id, $program, ['status' => $status]);
        $this->flash('success', "Program '{$program['program_no']}' status changed to " . ucfirst(str_replace('_', ' ', $status)) . ".");
        $this->redirect('/production/catalog?tab=programs');
    }

    // ========================================================
    // 3. CUTTING & WASTAGE
    // ========================================================
    public function cutting(): void {
        $cid = Auth::concernId();

        $sql = "SELECT cl.*, pp.program_no, v.name AS raw_variant_name, v.sku_code,
                       con.name AS concern_name, con.code AS concern_code
                FROM cutting_logs cl
                LEFT JOIN production_programs pp ON cl.program_id = pp.id
                JOIN variants v ON cl.raw_variant_id = v.id
                LEFT JOIN concerns con ON cl.concern_id = con.id";
        $params = [];
        if ($cid) {
            $sql .= " WHERE cl.concern_id = ?";
            $params[] = $cid;
        }
        $sql .= " ORDER BY cl.id DESC";
        $logs = DB::fetchAll($sql, $params);

        if ($cid) {
            $programs = DB::fetchAll("SELECT id, program_no FROM production_programs WHERE concern_id = ? AND status != 'cancelled'", [$cid]);
            $rawVariants = DB::fetchAll("SELECT id, sku_code, name, weight_unit FROM variants WHERE concern_id = ? AND status = 'active'", [$cid]);
        } else {
            $programs = DB::fetchAll("SELECT id, program_no FROM production_programs WHERE status != 'cancelled'");
            $rawVariants = DB::fetchAll("SELECT id, sku_code, name, weight_unit FROM variants WHERE status = 'active'");
        }

        $this->render('concern/cutting_wastage', [
            'pageTitle'     => 'Fabric Cutting & Wastage Tracking',
            'currentModule' => 'cutting_wastage',
            'logs'          => $logs,
            'programs'      => $programs,
            'rawVariants'   => $rawVariants,
            'selectedCid'   => $cid,
        ]);
    }

    public function storeCutting(): void {
        $this->validateCsrf();
        $cid = Auth::concernId() ?: (int)($_POST['concern_id'] ?? 0);

        if (!$cid) {
            $this->flash('error', 'Please choose a concern.');
            $this->redirect('/m/cutting_wastage');
        }

        $progId      = !empty($_POST['program_id']) ? (int)$_POST['program_id'] : null;
        $rawVarId    = (int)($_POST['raw_variant_id'] ?? 0);
        $consumedQty = (float)($_POST['consumed_qty'] ?? 0);
        $cutPieces   = (int)($_POST['cut_pieces'] ?? 0);
        $wastageQty  = (float)($_POST['wastage_qty'] ?? 0);
        $notes       = trim($_POST['notes'] ?? '');

        if (!$rawVarId || $consumedQty <= 0 || $cutPieces <= 0) {
            $this->flash('error', 'Raw Fabric Variant, Consumed Quantity, and Cut Pieces are required.');
            $this->redirect('/m/cutting_wastage');
        }

        DB::beginTransaction();
        try {
            $lotNo = 'CUT-' . date('Ymd') . '-' . rand(100, 999);

            DB::query(
                "INSERT INTO cutting_logs (concern_id, lot_no, program_id, raw_variant_id, consumed_qty, cut_pieces, wastage_qty, notes, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())",
                [$cid, $lotNo, $progId, $rawVarId, $consumedQty, $cutPieces, $wastageQty, $notes]
            );
            $cutId = (int)DB::lastInsertId();

            // Single stock gateway: deduct consumed fabric from warehouse stock
            StockService::move(
                $cid,
                $rawVarId,
                'Main Store',
                0.00,
                $consumedQty,
                'Cutting',
                $cutId,
                "Cutting Lot [{$lotNo}]: Consumed {$consumedQty} units to produce {$cutPieces} cut pieces",
                Auth::id()
            );

            DB::commit();
            $this->flash('success', "Cutting Lot '{$lotNo}' recorded! Deducted {$consumedQty} fabric units from stock.");
        } catch (Exception $e) {
            DB::rollBack();
            $this->flash('error', 'Cutting deduction failed: ' . $e->getMessage());
        }

        $this->redirect('/m/cutting_wastage');
    }

    // ========================================================
    // 4. STITCHING & WASTAGE
    // ========================================================
    public function stitching(): void {
        $cid = Auth::concernId();

        $sql = "SELECT sl.*, pp.program_no, cl.lot_no,
                       con.name AS concern_name, con.code AS concern_code
                FROM stitching_logs sl
                LEFT JOIN production_programs pp ON sl.program_id = pp.id
                LEFT JOIN cutting_logs cl ON sl.cutting_lot_id = cl.id
                LEFT JOIN concerns con ON sl.concern_id = con.id";
        $params = [];
        if ($cid) {
            $sql .= " WHERE cl.concern_id = ?";
            $params[] = $cid;
        }
        $sql .= " ORDER BY sl.id DESC";
        $logs = DB::fetchAll($sql, $params);

        if ($cid) {
            $programs = DB::fetchAll("SELECT id, program_no FROM production_programs WHERE concern_id = ? AND status != 'cancelled'", [$cid]);
            $cuttingLots = DB::fetchAll("SELECT id, lot_no, cut_pieces FROM cutting_logs WHERE concern_id = ?", [$cid]);
        } else {
            $programs = DB::fetchAll("SELECT id, program_no FROM production_programs WHERE status != 'cancelled'");
            $cuttingLots = DB::fetchAll("SELECT id, lot_no, cut_pieces FROM cutting_logs");
        }

        $this->render('concern/stitching_wastage', [
            'pageTitle'     => 'Garment Stitching & Assembly Tracking',
            'currentModule' => 'stitching_wastage',
            'logs'          => $logs,
            'programs'      => $programs,
            'cuttingLots'   => $cuttingLots,
            'selectedCid'   => $cid,
        ]);
    }

    public function storeStitching(): void {
        $this->validateCsrf();
        $cid = Auth::concernId() ?: (int)($_POST['concern_id'] ?? 0);

        if (!$cid) {
            $this->flash('error', 'Please choose a concern.');
            $this->redirect('/m/stitching_wastage');
        }

        $cutLotId      = !empty($_POST['cutting_lot_id']) ? (int)$_POST['cutting_lot_id'] : null;
        $progId        = !empty($_POST['program_id']) ? (int)$_POST['program_id'] : null;
        $operator      = trim($_POST['operator_name'] ?? '');
        $receivedPieces= (int)($_POST['received_pieces'] ?? 0);
        $stitchedPieces= (int)($_POST['stitched_pieces'] ?? 0);
        $damagedPieces = (int)($_POST['damaged_pieces'] ?? 0);

        if ($receivedPieces <= 0 || $stitchedPieces <= 0) {
            $this->flash('error', 'Received Pieces and Stitched Pieces are required.');
            $this->redirect('/m/stitching_wastage');
        }

        DB::beginTransaction();
        try {
            $batchNo = 'STITCH-' . date('Ymd') . '-' . rand(100, 999);

            DB::query(
                "INSERT INTO stitching_logs (concern_id, batch_no, cutting_lot_id, program_id, operator_name, received_pieces, stitched_pieces, damaged_pieces, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())",
                [$cid, $batchNo, $cutLotId, $progId, $operator, $receivedPieces, $stitchedPieces, $damagedPieces]
            );

            DB::commit();
            $this->flash('success', "Stitching Batch '{$batchNo}' recorded! {$stitchedPieces} garments assembled.");
        } catch (Exception $e) {
            DB::rollBack();
            $this->flash('error', 'Stitching entry error: ' . $e->getMessage());
        }

        $this->redirect('/m/stitching_wastage');
    }

    // ========================================================
    // 5. QC STOCKS INSPECTION
    // ========================================================
    public function qc(): void {
        $cid = Auth::concernId();

        $sql = "SELECT qi.*, v.name AS variant_name, v.sku_code, u.name AS inspector_name,
                       con.name AS concern_name, con.code AS concern_code
                FROM qc_inspections qi
                JOIN variants v ON qi.variant_id = v.id
                LEFT JOIN users u ON qi.inspector_id = u.id
                LEFT JOIN concerns con ON qi.concern_id = con.id";
        $params = [];
        if ($cid) {
            $sql .= " WHERE qi.concern_id = ?";
            $params[] = $cid;
        }
        $sql .= " ORDER BY qi.id DESC";
        $inspections = DB::fetchAll($sql, $params);

        if ($cid) {
            $variants = DB::fetchAll("SELECT id, sku_code, name FROM variants WHERE concern_id = ? AND status = 'active'", [$cid]);
        } else {
            $variants = DB::fetchAll("SELECT id, sku_code, name FROM variants WHERE status = 'active'");
        }

        $this->render('concern/qc_stocks', [
            'pageTitle'     => 'Quality Control & Stock Approval',
            'currentModule' => 'qc_stocks',
            'inspections'   => $inspections,
            'variants'      => $variants,
            'selectedCid'   => $cid,
        ]);
    }

    public function storeQc(): void {
        $this->validateCsrf();
        $cid = Auth::concernId() ?: (int)($_POST['concern_id'] ?? 0);

        if (!$cid) {
            $this->flash('error', 'Please choose a concern.');
            $this->redirect('/m/qc_stocks');
        }

        $variantId    = (int)($_POST['variant_id'] ?? 0);
        $inspectedQty = (float)($_POST['inspected_qty'] ?? 0);
        $passedQty    = (float)($_POST['passed_qty'] ?? 0);
        $rejectedQty  = (float)($_POST['rejected_qty'] ?? 0);
        $location     = trim($_POST['location'] ?? 'Main Store');
        $defectNotes  = trim($_POST['defect_notes'] ?? '');

        if (!$variantId || $inspectedQty <= 0 || $passedQty < 0) {
            $this->flash('error', 'Variant, Inspected Qty, and Passed Qty are required.');
            $this->redirect('/m/qc_stocks');
        }

        DB::beginTransaction();
        try {
            $qcNo = 'QC-' . date('Ymd') . '-' . rand(100, 999);

            DB::query(
                "INSERT INTO qc_inspections (concern_id, qc_no, variant_id, inspected_qty, passed_qty, rejected_qty, location, defect_notes, inspector_id, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
                [$cid, $qcNo, $variantId, $inspectedQty, $passedQty, $rejectedQty, $location, $defectNotes, Auth::id()]
            );
            $qcId = (int)DB::lastInsertId();

            if ($passedQty > 0) {
                StockService::move(
                    $cid,
                    $variantId,
                    $location,
                    $passedQty,
                    0.00,
                    'QC Stocks',
                    $qcId,
                    "QC Inspection Passed [{$qcNo}]: {$passedQty} units approved",
                    Auth::id()
                );
            }

            DB::commit();

            $this->flash('success', "QC Inspection '{$qcNo}' approved! {$passedQty} units moved to live stock.");
        } catch (Exception $e) {
            DB::rollBack();
            $this->flash('error', 'QC processing error: ' . $e->getMessage());
        }

        $this->redirect('/m/qc_stocks');
    }
}
