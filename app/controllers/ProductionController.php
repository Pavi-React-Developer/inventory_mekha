<?php
// app/controllers/ProductionController.php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;
use App\Core\Auth;
use App\Services\StockService;
use App\Services\NumberSeries;
use App\Services\AuditLog;
use Exception;

class ProductionController extends Controller {

    // ========================================================
    // 1. PRODUCTION CATALOG (MANUFACTURED GARMENT STYLES)
    // ========================================================

    /**
     * Display Production Catalog list view matching Image 1
     */
    public function catalogProgram(): void {
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
                           con.name AS concern_name, con.code AS concern_code
                    FROM production_programs pp
                    LEFT JOIN catalogs c ON pp.catalog_id = c.id
                    JOIN variants v ON pp.target_variant_id = v.id
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
            // Auto build whole code if not supplied
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
            // Count existing styles to formulate style code
            $nextSeq = (int)DB::fetchValue("SELECT COUNT(*) FROM catalogs WHERE concern_id = ?", [$cid]) + 1;
            $styleCode = sprintf("%s-STY-%04d", $conPrefix, $nextSeq);

            // Double check uniqueness
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

                // Check if SKU exists
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

        // Validate
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

        // Resolve names
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

        // Check if any variants have stock movements in ledger
        $hasStockLedger = (int)DB::fetchValue(
            "SELECT COUNT(*) FROM stock_ledger sl 
             JOIN variants v ON sl.variant_id = v.id 
             WHERE v.catalog_id = ?",
            [$id]
        );

        if ($hasStockLedger > 0) {
            // Cannot hard delete due to immutable accounting ledger. Archive instead.
            DB::query("UPDATE catalogs SET status = 'inactive' WHERE id = ?", [$id]);
            DB::query("UPDATE variants SET status = 'inactive' WHERE catalog_id = ?", [$id]);
            $this->flash('warning', "Style '{$style['style_code']}' has stock transaction history and has been archived as Inactive.");
        } else {
            // Safe to cleanly remove
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
    // 2. PRODUCTION PROGRAMS SCHEDULING (PRESERVED)
    // ========================================================

    public function programsList(): void {
        $cid = Auth::concernId();
        $progSql = "SELECT pp.*, c.name AS catalog_name, v.name AS target_variant_name, v.sku_code,
                           con.name AS concern_name, con.code AS concern_code
                    FROM production_programs pp
                    LEFT JOIN catalogs c ON pp.catalog_id = c.id
                    JOIN variants v ON pp.target_variant_id = v.id
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

    public function storeProgram(): void {
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

        $this->redirect('/production/catalog');
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
            $sql .= " WHERE sl.concern_id = ?";
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

            // Single stock gateway: QC passed units move directly into Stocks!
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
