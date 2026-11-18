<?php
// app/controllers/CatalogMappingController.php - Catalog Mapping Module for Finished Goods, 3-Tier Categories & Attributes

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;
use App\Core\Auth;
use App\Services\AuditLog;
use App\Services\StockService;
use Exception;

class CatalogMappingController extends Controller {

    private static bool $schemaEnsured = false;

    /**
     * Self-healing schema guard ensuring catalog_mappings and product_attributes exist
     */
    protected function ensureSchema(): void {
        if (self::$schemaEnsured) return;

        try {
            // 1. Ensure catalog_id on purchase_bill_items
            $pbiCols = array_column(DB::fetchAll("DESCRIBE `purchase_bill_items`"), 'Field');
            if (!in_array('catalog_id', $pbiCols)) {
                DB::query("ALTER TABLE `purchase_bill_items` ADD COLUMN `catalog_id` INT NULL AFTER `variant_id`");
            }

            // 2. Ensure catalog_mappings table
            DB::query("CREATE TABLE IF NOT EXISTS `catalog_mappings` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `concern_id` INT NOT NULL,
                `purchase_bill_item_id` INT NOT NULL,
                `catalog_id` INT NOT NULL,
                `variant_id` INT NULL,
                `category_id` INT NULL,
                `subcategory_id` INT NULL,
                `sub_sub_category_id` INT NULL,
                `selected_sizes` TEXT NULL,
                `selected_color` VARCHAR(100) NULL,
                `status` ENUM('mapped', 'unmapped') NOT NULL DEFAULT 'mapped',
                `notes` TEXT NULL,
                `created_by` INT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_cmap_concern` (`concern_id`),
                INDEX `idx_cmap_pbi` (`purchase_bill_item_id`),
                INDEX `idx_cmap_catalog` (`catalog_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            // 3. Ensure product_attributes table
            DB::query("CREATE TABLE IF NOT EXISTS `product_attributes` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `concern_id` INT NOT NULL,
                `attribute_type` VARCHAR(50) NOT NULL DEFAULT 'size',
                `attribute_name` VARCHAR(100) NOT NULL,
                `attribute_value` VARCHAR(100) NOT NULL,
                `sort_order` INT NOT NULL DEFAULT 0,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_pattr_concern` (`concern_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            // 4. Ensure module registered in `modules`
            $existMod = DB::fetch("SELECT id FROM `modules` WHERE `code` = 'catalog_mapping'");
            if (!$existMod) {
                DB::query(
                    "INSERT INTO `modules` (`code`, `name`, `icon`, `sort_order`, `group_name`, `is_super_admin_only`)
                     VALUES ('catalog_mapping', 'Catalog Mapping', 'bi-diagram-3-fill', 30.5, 'Production', 0)"
                );
            }

            self::$schemaEnsured = true;
        } catch (\Throwable $e) {
            error_log("CatalogMapping schema check error: " . $e->getMessage());
        }
    }

    /**
     * Main View: List Purchased Finished Goods & 3-Tier Hierarchy / Attributes
     */
    public function index(): void {
        $this->ensureSchema();
        $cid = Auth::concernId();

        $statusFilter = trim($_GET['status'] ?? 'all');
        $search = trim($_GET['search'] ?? '');
        $catFilter = !empty($_GET['category_id']) ? (int)$_GET['category_id'] : null;

        // Query Finished Goods from Purchase Billing
        $sql = "SELECT pbi.*, 
                       pb.bill_no, pb.bill_date, pb.payment_status,
                       s.name AS supplier_name,
                       cm.id AS mapping_id, cm.status AS mapping_status, cm.selected_sizes, cm.selected_color,
                       c.name AS catalog_name, c.style_code AS catalog_style_code, c.short_code AS catalog_short_code,
                       c.hierarchy_path, c.hsn_code AS catalog_hsn, c.gst_rate AS catalog_gst_rate,
                       cat.name AS category_name, sub.name AS subcategory_name, subsub.name AS subsub_name,
                       v.sku_code AS variant_sku, v.name AS variant_name
                FROM purchase_bill_items pbi
                JOIN purchase_bills pb ON pbi.purchase_bill_id = pb.id
                LEFT JOIN suppliers s ON pb.supplier_id = s.id
                LEFT JOIN catalog_mappings cm ON cm.purchase_bill_item_id = pbi.id
                LEFT JOIN catalogs c ON COALESCE(cm.catalog_id, pbi.catalog_id) = c.id
                LEFT JOIN categories cat ON c.category_id = cat.id
                LEFT JOIN subcategories sub ON c.subcategory_id = sub.id
                LEFT JOIN sub_subcategories subsub ON c.sub_sub_category_id = subsub.id
                LEFT JOIN variants v ON pbi.variant_id = v.id
                WHERE pbi.section_type = 'finished_goods'";

        $params = [];
        if ($cid) {
            $sql .= " AND pbi.concern_id = ?";
            $params[] = $cid;
        }

        if ($statusFilter === 'unmapped') {
            $sql .= " AND (cm.id IS NULL OR cm.status = 'unmapped' OR (pbi.catalog_id IS NULL AND pbi.variant_id IS NULL))";
        } elseif ($statusFilter === 'mapped') {
            $sql .= " AND (cm.id IS NOT NULL AND cm.status = 'mapped' OR (pbi.catalog_id IS NOT NULL OR pbi.variant_id IS NOT NULL))";
        }

        if (!empty($search)) {
            $sql .= " AND (pbi.item_name LIKE ? OR pbi.item_code LIKE ? OR pb.bill_no LIKE ? OR s.name LIKE ? OR c.name LIKE ? OR c.style_code LIKE ?)";
            $term = "%{$search}%";
            $params = array_merge($params, [$term, $term, $term, $term, $term, $term]);
        }

        if ($catFilter) {
            $sql .= " AND (c.category_id = ? OR cm.category_id = ?)";
            $params[] = $catFilter;
            $params[] = $catFilter;
        }

        $sql .= " ORDER BY pbi.id DESC";
        $items = DB::fetchAll($sql, $params);

        // Fetch Metrics
        $totalItems = count($items);
        $mappedCount = 0;
        $unmappedCount = 0;
        $totalPieces = 0;

        foreach ($items as &$it) {
            $isMapped = (!empty($it['mapping_id']) && $it['mapping_status'] === 'mapped') || (!empty($it['catalog_name']) || !empty($it['variant_id']));
            $it['is_mapped'] = $isMapped;
            if ($isMapped) {
                $mappedCount++;
            } else {
                $unmappedCount++;
            }
            $totalPieces += (float)($it['qty'] ?? 0);
        }
        unset($it);

        // Load 3-Tier Hierarchy for Tab 2 and Dropdowns
        if ($cid) {
            $categories = DB::fetchAll("SELECT * FROM categories WHERE concern_id = ? ORDER BY name ASC", [$cid]);
            $subcategories = DB::fetchAll("SELECT s.*, c.name AS category_name FROM subcategories s JOIN categories c ON s.category_id = c.id WHERE s.concern_id = ? ORDER BY s.name ASC", [$cid]);
            $subSubcategories = DB::fetchAll("SELECT ss.*, s.name AS subcategory_name, c.name AS category_name FROM sub_subcategories ss LEFT JOIN subcategories s ON ss.subcategory_id = s.id LEFT JOIN categories c ON s.category_id = c.id WHERE ss.concern_id = ? ORDER BY ss.name ASC", [$cid]);
            $attributes = DB::fetchAll("SELECT * FROM product_attributes WHERE concern_id = ? ORDER BY attribute_type ASC, sort_order ASC", [$cid]);
            $catalogs = DB::fetchAll("SELECT id, style_code, short_code, name FROM catalogs WHERE concern_id = ? AND status != 'inactive' ORDER BY id DESC", [$cid]);
        } else {
            $categories = DB::fetchAll("SELECT * FROM categories ORDER BY name ASC");
            $subcategories = DB::fetchAll("SELECT s.*, c.name AS category_name FROM subcategories s JOIN categories c ON s.category_id = c.id ORDER BY s.name ASC");
            $subSubcategories = DB::fetchAll("SELECT ss.*, s.name AS subcategory_name, c.name AS category_name FROM sub_subcategories ss LEFT JOIN subcategories s ON ss.subcategory_id = s.id LEFT JOIN categories c ON s.category_id = c.id ORDER BY ss.name ASC");
            $attributes = DB::fetchAll("SELECT * FROM product_attributes ORDER BY attribute_type ASC, sort_order ASC");
            $catalogs = DB::fetchAll("SELECT id, style_code, short_code, name FROM catalogs ORDER BY id DESC");
        }

        $activeTab = $_GET['tab'] ?? 'mappings';

        $this->render('concern/catalog_mapping', [
            'pageTitle'        => 'Catalog Mapping',
            'currentModule'    => 'catalog_mapping',
            'items'            => $items,
            'categories'       => $categories,
            'subcategories'    => $subcategories,
            'subSubcategories' => $subSubcategories,
            'attributes'       => $attributes,
            'catalogs'         => $catalogs,
            'metrics'          => [
                'total'    => $totalItems,
                'mapped'   => $mappedCount,
                'unmapped' => $unmappedCount,
                'pieces'   => $totalPieces,
            ],
            'filters'          => [
                'status'      => $statusFilter,
                'search'      => $search,
                'category_id' => $catFilter,
            ],
            'activeTab'        => $activeTab,
            'selectedCid'      => $cid,
        ]);
    }

    /**
     * Map Finished Good Item Form
     */
    public function mapItemForm(): void {
        $this->ensureSchema();
        $cid = Auth::concernId() ?: 1;
        $itemId = (int)($_GET['id'] ?? 0);

        if (!$itemId) {
            $this->flash('error', 'Invalid Finished Good Item ID.');
            $this->redirect('/production/catalog-mapping');
        }

        $sql = "SELECT pbi.*, 
                       pb.bill_no, pb.bill_date, pb.supplier_id,
                       s.name AS supplier_name,
                       cm.id AS mapping_id, cm.catalog_id AS mapped_catalog_id, cm.selected_sizes, cm.selected_color,
                       c.name AS catalog_name, c.style_code AS catalog_style_code, c.short_code AS catalog_short_code,
                       c.hierarchy_path, c.category_id AS mapped_cat_id, c.subcategory_id AS mapped_subcat_id, c.sub_sub_category_id AS mapped_subsub_id
                FROM purchase_bill_items pbi
                JOIN purchase_bills pb ON pbi.purchase_bill_id = pb.id
                LEFT JOIN suppliers s ON pb.supplier_id = s.id
                LEFT JOIN catalog_mappings cm ON cm.purchase_bill_item_id = pbi.id
                LEFT JOIN catalogs c ON COALESCE(cm.catalog_id, pbi.catalog_id) = c.id
                WHERE pbi.id = ?" . ($cid ? " AND pbi.concern_id = ?" : "");
        $item = DB::fetch($sql, $cid ? [$itemId, $cid] : [$itemId]);

        if (!$item) {
            $this->flash('error', 'Finished good item not found or access denied.');
            $this->redirect('/production/catalog-mapping');
        }

        // Generate suggested style code: GRM-STY-XXXX
        $styleCount = (int)DB::fetchValue("SELECT COUNT(*) FROM catalogs WHERE concern_id = ?", [$cid]) + 1;
        $conCode = Auth::concern()['code'] ?? 'GRM';
        $suggestedStyleCode = sprintf("%s-STY-%04d", $conCode, $styleCount);

        // Fetch Categories & Attributes
        $categories = DB::fetchAll("SELECT * FROM categories WHERE concern_id = ? AND status = 'active' ORDER BY name ASC", [$cid]);
        $subcategories = DB::fetchAll("SELECT * FROM subcategories WHERE concern_id = ? AND status = 'active' ORDER BY name ASC", [$cid]);
        $subSubcategories = DB::fetchAll("SELECT * FROM sub_subcategories WHERE concern_id = ? ORDER BY name ASC", [$cid]);
        $attributes = DB::fetchAll("SELECT * FROM product_attributes WHERE concern_id = ? ORDER BY attribute_type ASC, sort_order ASC", [$cid]);
        $catalogs = DB::fetchAll("SELECT id, style_code, short_code, name, hierarchy_path FROM catalogs WHERE concern_id = ? AND status != 'inactive' ORDER BY id DESC", [$cid]);

        // Default sizes available
        $defaultSizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL', 'Free Size'];
        $customSizes = array_column(
            array_filter($attributes, fn($a) => $a['attribute_type'] === 'size'),
            'attribute_value'
        );
        $allSizes = array_unique(array_merge($defaultSizes, $customSizes));

        $this->render('concern/catalog_mapping_form', [
            'pageTitle'          => 'Map Finished Good to Catalog: ' . $item['item_name'],
            'currentModule'      => 'catalog_mapping',
            'item'               => $item,
            'suggestedStyleCode' => $suggestedStyleCode,
            'categories'         => $categories,
            'subcategories'      => $subcategories,
            'subSubcategories'   => $subSubcategories,
            'catalogs'           => $catalogs,
            'sizes'              => $allSizes,
            'selectedCid'        => $cid,
        ]);
    }

    /**
     * Save Finished Good Catalog Mapping & Auto-create Catalog / Size Variants
     */
    public function saveMapping(): void {
        $this->ensureSchema();
        $this->validateCsrf();

        $itemId = (int)($_POST['purchase_bill_item_id'] ?? 0);
        $mappingMode = $_POST['mapping_mode'] ?? 'new_style'; // 'new_style' or 'existing_style'

        if (!$itemId) {
            $this->flash('error', 'Invalid Purchased Item ID.');
            $this->redirect('/production/catalog-mapping');
        }

        $userCid = Auth::concernId();
        $item = DB::fetch("SELECT * FROM purchase_bill_items WHERE id = ?", [$itemId]);
        if (!$item) {
            $this->flash('error', 'Finished good item not found.');
            $this->redirect('/production/catalog-mapping');
        }
        if ($userCid && (int)$item['concern_id'] !== (int)$userCid) {
            $this->flash('error', 'Finished good item not found or unauthorized.');
            $this->redirect('/production/catalog-mapping');
        }
        $cid = (int)($item['concern_id'] ?: ($userCid ?: 1));

        DB::beginTransaction();
        try {
            $catalogId = null;
            $primaryVariantId = null;
            $cleanSizes = [];
            $color = trim($_POST['color'] ?? ($item['color'] ?? ''));

            if ($mappingMode === 'existing_style') {
                $catalogId = (int)($_POST['existing_catalog_id'] ?? 0);
                if (!$catalogId) {
                    throw new Exception('Please select an existing catalog style to map.');
                }
                $catRecord = DB::fetch("SELECT * FROM catalogs WHERE id = ? AND concern_id = ?", [$catalogId, $cid]);
                if (!$catRecord) {
                    throw new Exception('Selected catalog style not found.');
                }

                // Pick primary variant
                $existVar = DB::fetch("SELECT id FROM variants WHERE catalog_id = ? AND status = 'active' LIMIT 1", [$catalogId]);
                $primaryVariantId = $existVar ? (int)$existVar['id'] : null;

                $cleanSizes = !empty($catRecord['sizes']) ? json_decode($catRecord['sizes'], true) : [];
            } else {
                // New Style Mapping
                $styleCode = strtoupper(trim($_POST['style_code'] ?? ''));
                $styleName = trim($_POST['style_name'] ?? '');
                $categoryId = (int)($_POST['category_id'] ?? 0);
                $subcategoryId = !empty($_POST['subcategory_id']) ? (int)$_POST['subcategory_id'] : null;
                $subSubcategoryId = !empty($_POST['sub_sub_category_id']) ? (int)$_POST['sub_sub_category_id'] : null;

                if (empty($styleCode)) {
                    throw new Exception('Product Style Code is required.');
                }
                if (empty($styleName)) {
                    throw new Exception('Product Style Name is required.');
                }
                if ($categoryId <= 0) {
                    throw new Exception('Main Category is required.');
                }

                $mainCatCode = strtoupper(trim($_POST['main_category_code'] ?? ''));
                $subCatCode = strtoupper(trim($_POST['sub_category_code'] ?? ''));
                $subSubCatCode = strtoupper(trim($_POST['sub_sub_category_code'] ?? ''));
                $wholeCode = strtoupper(trim($_POST['whole_code'] ?? ''));
                if (empty($wholeCode)) {
                    $wholeCode = $mainCatCode . $subCatCode . $subSubCatCode;
                }

                $hsnCode = trim($_POST['hsn_code'] ?? ($item['hsn_code'] ?? '6109'));
                $gstRate = isset($_POST['gst_rate']) ? (float)$_POST['gst_rate'] : (float)($item['gst_rate'] ?? 5.00);
                $mrp = (float)($_POST['mrp'] ?? 0.00);
                $sellingPrice = (float)($_POST['selling_price'] ?? 0.00);

                // Sizes
                $rawSizes = $_POST['sizes'] ?? [];
                if (is_array($rawSizes)) {
                    foreach ($rawSizes as $s) {
                        $val = trim($s);
                        if ($val !== '' && !in_array($val, $cleanSizes)) {
                            $cleanSizes[] = $val;
                        }
                    }
                }
                if (empty($cleanSizes)) {
                    $cleanSizes = ['Free Size'];
                }

                // Construct hierarchy path
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
                $hierarchyPath = implode(' > ', array_filter($pathParts));

                // Insert or update catalogs
                $existStyle = DB::fetch("SELECT id FROM catalogs WHERE concern_id = ? AND style_code = ?", [$cid, $styleCode]);
                if ($existStyle) {
                    $catalogId = (int)$existStyle['id'];
                    DB::query(
                        "UPDATE catalogs SET
                            name = ?, category_id = ?, subcategory_id = ?, sub_sub_category_id = ?,
                            short_code = ?, main_category_code = ?, sub_category_code = ?, sub_sub_category_code = ?,
                            hierarchy_path = ?, hsn_code = ?, gst_rate = ?, sizes = ?, status = 'in_house', updated_at = NOW()
                         WHERE id = ?",
                        [
                            $styleName, $categoryId, $subcategoryId, $subSubcategoryId,
                            $wholeCode, $mainCatCode, $subCatCode, $subSubCatCode,
                            $hierarchyPath, $hsnCode, $gstRate, json_encode($cleanSizes),
                            $catalogId
                        ]
                    );
                } else {
                    DB::query(
                        "INSERT INTO catalogs (
                            concern_id, code, style_code, short_code, name,
                            category_id, subcategory_id, sub_sub_category_id,
                            main_category_code, sub_category_code, sub_sub_category_code,
                            hierarchy_path, hsn_code, gst_rate, sizes, status, created_at
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'in_house', NOW())",
                        [
                            $cid, $styleCode, $styleCode, $wholeCode, $styleName,
                            $categoryId, $subcategoryId, $subSubcategoryId,
                            $mainCatCode, $subCatCode, $subSubCatCode,
                            $hierarchyPath, $hsnCode, $gstRate, json_encode($cleanSizes)
                        ]
                    );
                    $catalogId = (int)DB::lastInsertId();
                }

                // Generate / synchronize size variants
                $attrJson = json_encode(array_filter(['color' => $color]));
                foreach ($cleanSizes as $size) {
                    $sku = "{$styleCode}-{$size}";
                    $varName = "{$styleName} - {$size}";
                    $existVar = DB::fetch("SELECT id FROM variants WHERE concern_id = ? AND catalog_id = ? AND size = ?", [$cid, $catalogId, $size]);
                    if (!$existVar) {
                        DB::query(
                            "INSERT INTO variants (
                                concern_id, catalog_id, sku_code, name,
                                category_id, subcategory_id, size, attributes,
                                price, mrp, creation_date, status, created_at
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), 'active', NOW())",
                            [
                                $cid, $catalogId, $sku, $varName,
                                $categoryId, $subcategoryId, $size, $attrJson,
                                $sellingPrice, $mrp
                            ]
                        );
                        $newVarId = (int)DB::lastInsertId();
                        if (!$primaryVariantId) $primaryVariantId = $newVarId;
                    } else {
                        DB::query("UPDATE variants SET price = ?, mrp = ?, attributes = ?, status = 'active' WHERE id = ?", [$sellingPrice, $mrp, $attrJson, $existVar['id']]);
                        if (!$primaryVariantId) $primaryVariantId = (int)$existVar['id'];
                    }
                }
            }

            // Update purchase_bill_items
            DB::query("UPDATE purchase_bill_items SET catalog_id = ?, variant_id = ? WHERE id = ?", [$catalogId, $primaryVariantId, $itemId]);

            // Save or update catalog_mappings table
            $existMap = DB::fetch("SELECT id FROM catalog_mappings WHERE purchase_bill_item_id = ?", [$itemId]);
            if ($existMap) {
                DB::query(
                    "UPDATE catalog_mappings SET
                        catalog_id = ?, variant_id = ?, category_id = ?, subcategory_id = ?, sub_sub_category_id = ?,
                        selected_sizes = ?, selected_color = ?, status = 'mapped', updated_at = NOW()
                     WHERE id = ?",
                    [
                        $catalogId, $primaryVariantId,
                        $_POST['category_id'] ?? null, $_POST['subcategory_id'] ?? null, $_POST['sub_sub_category_id'] ?? null,
                        json_encode($cleanSizes), $color,
                        $existMap['id']
                    ]
                );
            } else {
                DB::query(
                    "INSERT INTO catalog_mappings (
                        concern_id, purchase_bill_item_id, catalog_id, variant_id,
                        category_id, subcategory_id, sub_sub_category_id,
                        selected_sizes, selected_color, status, created_by, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'mapped', ?, NOW())",
                    [
                        $cid, $itemId, $catalogId, $primaryVariantId,
                        $_POST['category_id'] ?? null, $_POST['subcategory_id'] ?? null, $_POST['sub_sub_category_id'] ?? null,
                        json_encode($cleanSizes), $color,
                        Auth::id()
                    ]
                );
            }

            // Sync with physical inventory stocks using StockService::move
            if ($primaryVariantId && (float)$item['qty'] > 0) {
                try {
                    StockService::move(
                        $cid,
                        $primaryVariantId,
                        'Main Store',
                        (float)$item['qty'],
                        0.00,
                        'catalog_mapping',
                        $itemId,
                        "Finished Goods Inward via Catalog Mapping from Bill #{$item['purchase_bill_id']}",
                        Auth::id()
                    );
                } catch (\Throwable $stkEx) {
                    error_log("StockService notice: " . $stkEx->getMessage());
                }
            }

            // Audit Log
            AuditLog::log('CREATE', 'catalog_mappings', $itemId, null, [
                'purchase_bill_item_id' => $itemId,
                'catalog_id'            => $catalogId,
                'variant_id'            => $primaryVariantId,
                'sizes'                 => $cleanSizes,
            ]);

            DB::commit();
            $this->flash('success', "Finished good item '{$item['item_name']}' successfully mapped to catalog with " . count($cleanSizes) . " size variants.");
        } catch (Exception $e) {
            DB::rollBack();
            $this->flash('error', 'Mapping failed: ' . $e->getMessage());
            $this->redirect('/production/catalog-mapping/map?id=' . $itemId);
        }

        $this->redirect('/production/catalog-mapping');
    }

    /**
     * Unmap Finished Good Item
     */
    public function unmapItem(): void {
        $this->ensureSchema();
        $this->validateCsrf();
        $cid = Auth::concernId();
        $itemId = (int)($_POST['purchase_bill_item_id'] ?? 0);

        if (!$itemId) {
            $this->flash('error', 'Invalid item ID.');
            $this->redirect('/production/catalog-mapping');
        }

        $item = DB::fetch("SELECT * FROM purchase_bill_items WHERE id = ?" . ($cid ? " AND concern_id = ?" : ""), $cid ? [$itemId, $cid] : [$itemId]);
        if (!$item) {
            $this->flash('error', 'Item not found or access denied.');
            $this->redirect('/production/catalog-mapping');
        }

        DB::beginTransaction();
        try {
            DB::query("DELETE FROM catalog_mappings WHERE purchase_bill_item_id = ?", [$itemId]);
            DB::query("UPDATE purchase_bill_items SET catalog_id = NULL WHERE id = ?", [$itemId]);
            AuditLog::log('DELETE', 'catalog_mappings', $itemId, null, ['action' => 'unmap']);
            DB::commit();
            $this->flash('success', "Item '{$item['item_name']}' unmapped successfully.");
        } catch (Exception $e) {
            DB::rollBack();
            $this->flash('error', 'Unmap failed: ' . $e->getMessage());
        }

        $this->redirect('/production/catalog-mapping');
    }

    /**
     * Quick-add Category / Subcategory / Sub-Subcategory from Catalog Mapping
     */
    public function quickCreateCategory(): void {
        $this->ensureSchema();
        $this->validateCsrf();
        $cid = Auth::concernId() ?: 1;

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
                  || !empty($_POST['ajax'])
                  || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

        $tier = $_POST['tier'] ?? 'category'; // 'category', 'subcategory', 'subsubcategory'
        $name = trim($_POST['name'] ?? '');
        $code = strtoupper(trim($_POST['code'] ?? ''));

        if (empty($name)) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Name is required.']);
                exit;
            }
            $this->flash('error', 'Name is required.');
            $this->redirect('/production/catalog-mapping?tab=hierarchy');
        }

        try {
            $newId = 0;
            $hsn = null;
            $gst = null;
            $catId = null;
            $subcatId = null;

            if ($tier === 'category') {
                $hsn = trim($_POST['default_hsn'] ?? '6109');
                $gst = (float)($_POST['default_gst_rate'] ?? 5.00);
                if (empty($code)) $code = strtoupper(substr($name, 0, 2));
                DB::query(
                    "INSERT INTO categories (concern_id, name, code, default_hsn, default_gst_rate, status, created_at)
                     VALUES (?, ?, ?, ?, ?, 'active', NOW())",
                    [$cid, $name, $code, $hsn, $gst]
                );
                $newId = (int)DB::lastInsertId();
                $msg = "Main Category '{$name}' created successfully.";
            } elseif ($tier === 'subcategory') {
                $catId = (int)($_POST['category_id'] ?? 0);
                if (!$catId) throw new Exception('Parent Main Category is required.');
                if (empty($code)) $code = strtoupper(substr($name, 0, 2));
                DB::query(
                    "INSERT INTO subcategories (concern_id, category_id, name, code, status, created_at)
                     VALUES (?, ?, ?, ?, 'active', NOW())",
                    [$cid, $catId, $name, $code]
                );
                $newId = (int)DB::lastInsertId();
                $msg = "Subcategory '{$name}' created successfully.";
            } elseif ($tier === 'subsubcategory') {
                $subcatId = (int)($_POST['subcategory_id'] ?? 0);
                if (!$subcatId) throw new Exception('Parent Subcategory is required for Tier-3 category.');
                if (empty($code)) $code = strtoupper(substr($name, 0, 2));
                DB::query(
                    "INSERT INTO sub_subcategories (concern_id, subcategory_id, name, code, status, created_at)
                     VALUES (?, ?, ?, ?, 'active', NOW())",
                    [$cid, $subcatId, $name, $code]
                );
                $newId = (int)DB::lastInsertId();
                $msg = "Tier-3 Sub-Subcategory '{$name}' created successfully.";
            }

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => $msg,
                    'item' => [
                        'id' => $newId,
                        'name' => $name,
                        'code' => $code,
                        'tier' => $tier,
                        'category_id' => $catId,
                        'subcategory_id' => $subcatId,
                        'default_hsn' => $hsn ?? '6109',
                        'default_gst_rate' => $gst ?? 5.00
                    ]
                ]);
                exit;
            }

            $this->flash('success', $msg);
        } catch (Exception $e) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                exit;
            }
            $this->flash('error', 'Failed to create category: ' . $e->getMessage());
        }

        $returnUrl = $_POST['return_url'] ?? '/production/catalog-mapping?tab=hierarchy';
        $this->redirect($returnUrl);
    }

    /**
     * Quick-add Custom Product Attribute (e.g. Size, Color)
     */
    public function quickCreateAttribute(): void {
        $this->ensureSchema();
        $this->validateCsrf();
        $cid = Auth::concernId() ?: 1;

        $type = trim($_POST['attribute_type'] ?? 'size');
        $val = trim($_POST['attribute_value'] ?? '');
        $name = trim($_POST['attribute_name'] ?? ucfirst($type));

        if (empty($val)) {
            $this->flash('error', 'Attribute value cannot be empty.');
            $this->redirect('/production/catalog-mapping?tab=hierarchy');
        }

        try {
            DB::query(
                "INSERT INTO product_attributes (concern_id, attribute_type, attribute_name, attribute_value, sort_order)
                 VALUES (?, ?, ?, ?, (SELECT COALESCE(MAX(sort_order), 0) + 1 FROM product_attributes pa WHERE pa.concern_id = ?))",
                [$cid, $type, $name, $val, $cid]
            );
            $this->flash('success', "Attribute '{$val}' ({$name}) added successfully.");
        } catch (Exception $e) {
            $this->flash('error', 'Failed to add attribute: ' . $e->getMessage());
        }

        $returnUrl = $_POST['return_url'] ?? '/production/catalog-mapping?tab=hierarchy';
        $this->redirect($returnUrl);
    }

    /**
     * AJAX endpoint returning JSON details of a finished good item
     */
    public function apiItemDetails(): void {
        header('Content-Type: application/json');
        $this->ensureSchema();
        $cid = Auth::concernId();
        $id = (int)($_GET['id'] ?? 0);

        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Invalid ID.']);
            exit;
        }

        $sql = "SELECT pbi.*, pb.bill_no, pb.bill_date, s.name AS supplier_name,
                       c.name AS catalog_name, c.style_code, c.hierarchy_path, c.sizes
                FROM purchase_bill_items pbi
                JOIN purchase_bills pb ON pbi.purchase_bill_id = pb.id
                LEFT JOIN suppliers s ON pb.supplier_id = s.id
                LEFT JOIN catalogs c ON pbi.catalog_id = c.id
                WHERE pbi.id = ?" . ($cid ? " AND pbi.concern_id = ?" : "");
        $item = DB::fetch($sql, $cid ? [$id, $cid] : [$id]);

        if (!$item) {
            echo json_encode(['success' => false, 'message' => 'Item not found.']);
            exit;
        }

        echo json_encode(['success' => true, 'item' => $item]);
        exit;
    }
}
