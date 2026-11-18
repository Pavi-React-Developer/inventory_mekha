<?php
// app/controllers/LabelController.php - Rebuilt Label Generation (4 Sections: Purchase Raw, Purchase FG, Production, Return)

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;
use App\Core\Auth;
use App\Models\Variant;
use App\Services\QrService;
use App\Services\AuditLog;
use App\Services\StockService;
use App\Services\RollRegistryService;
use Exception;

class LabelController extends Controller {
    /**
     * Display the 4-Section Label Generation Hub
     */
    public function index(): void {
        $cid = Auth::concernId();
        $variantModel = new Variant();
        $allVariants = $variantModel->allWithDetails();

        $activeBillId = (int)($_GET['bill_id'] ?? 0);
        $activeTab    = trim($_GET['tab'] ?? 'purchase_raw');
        if (!in_array($activeTab, ['purchase_raw', 'purchase_fg', 'production', 'return'], true)) {
            $activeTab = 'purchase_raw';
        }

        $activeBill = null;
        if ($activeBillId > 0) {
            $bSql = "SELECT pb.*, s.name AS supplier_name, s.company_name AS supplier_company 
                     FROM purchase_bills pb 
                     LEFT JOIN suppliers s ON pb.supplier_id = s.id 
                     WHERE pb.id = ?";
            $bParams = [$activeBillId];
            if ($cid) {
                $bSql .= " AND pb.concern_id = ?";
                $bParams[] = $cid;
            }
            $activeBill = DB::fetch($bSql, $bParams);
        }

        $activeSubTab = trim($_GET['subtab'] ?? '');

        // 1a. Purchase Fabric Rolls (Rolls only)
        $rollSql = "SELECT pbi.*, pb.bill_no, pb.bill_date, pb.supplier_invoice_no, pb.inward_status AS bill_inward_status,
                           COALESCE(pbi.inward_status, pb.inward_status) AS inward_status,
                           s.name AS supplier_name, s.company_name AS supplier_company
                    FROM purchase_bill_items pbi
                    JOIN purchase_bills pb ON pbi.purchase_bill_id = pb.id
                    LEFT JOIN suppliers s ON pb.supplier_id = s.id
                    WHERE pbi.section_type = 'rolls'";
        $rollParams = [];
        if ($cid) {
            $rollSql .= " AND pbi.concern_id = ?";
            $rollParams[] = $cid;
        }
        if ($activeBillId > 0) {
            $rollSql .= " ORDER BY (pb.id = {$activeBillId}) DESC, pbi.id DESC LIMIT 100";
        } else {
            $rollSql .= " ORDER BY pbi.id DESC LIMIT 100";
        }
        $purchaseRollItems = DB::fetchAll($rollSql, $rollParams);

        // 1b. Purchase Other Garment Supplies & Trims (Trims, Accessories, Threads)
        $otherSql = "SELECT pbi.*, pb.bill_no, pb.bill_date, pb.supplier_invoice_no, pb.inward_status AS bill_inward_status,
                            COALESCE(pbi.inward_status, pb.inward_status) AS inward_status,
                            s.name AS supplier_name, s.company_name AS supplier_company
                     FROM purchase_bill_items pbi
                     JOIN purchase_bills pb ON pbi.purchase_bill_id = pb.id
                     LEFT JOIN suppliers s ON pb.supplier_id = s.id
                     WHERE pbi.section_type = 'garment_other'";
        $otherParams = [];
        if ($cid) {
            $otherSql .= " AND pbi.concern_id = ?";
            $otherParams[] = $cid;
        }
        if ($activeBillId > 0) {
            $otherSql .= " ORDER BY (pb.id = {$activeBillId}) DESC, pbi.id DESC LIMIT 100";
        } else {
            $otherSql .= " ORDER BY pbi.id DESC LIMIT 100";
        }
        $purchaseOtherItems = DB::fetchAll($otherSql, $otherParams);

        // Determine default subtab
        if (!in_array($activeSubTab, ['rolls', 'garment_other'], true)) {
            $activeSubTab = (!empty($purchaseRollItems) || empty($purchaseOtherItems)) ? 'rolls' : 'garment_other';
        }

        $purchaseRawItems = array_merge($purchaseRollItems, $purchaseOtherItems);

        // 2. Purchase Finished Goods (Ready-made garments from vendors, mapped to catalog)
        $fgSql = "SELECT pbi.*, pb.bill_no, pb.bill_date, pb.supplier_invoice_no, pb.inward_status AS bill_inward_status,
                         COALESCE(pbi.inward_status, pb.inward_status) AS inward_status,
                         s.name AS supplier_name, s.company_name AS supplier_company,
                         v.sku_code, v.name AS variant_name, v.size AS variant_size, 
                         v.price AS variant_price, v.mrp AS variant_mrp,
                         v.category_id, v.subcategory_id,
                         c.name AS category_name, sc.name AS subcategory_name, b.name AS brand_name
                  FROM purchase_bill_items pbi
                  JOIN purchase_bills pb ON pbi.purchase_bill_id = pb.id
                  LEFT JOIN suppliers s ON pb.supplier_id = s.id
                  LEFT JOIN variants v ON pbi.variant_id = v.id
                  LEFT JOIN categories c ON v.category_id = c.id
                  LEFT JOIN subcategories sc ON v.subcategory_id = sc.id
                  LEFT JOIN brands b ON v.brand_id = b.id
                  WHERE pbi.section_type = 'finished_goods'";
        $fgParams = [];
        if ($cid) {
            $fgSql .= " AND pbi.concern_id = ?";
            $fgParams[] = $cid;
        }
        if ($activeBillId > 0) {
            $fgSql .= " ORDER BY (pb.id = {$activeBillId}) DESC, pbi.id DESC LIMIT 100";
        } else {
            $fgSql .= " ORDER BY pbi.id DESC LIMIT 100";
        }
        $purchaseFgItems = DB::fetchAll($fgSql, $fgParams);

        // 3. Production Labels (After-Stitching assembled garments)
        $prodSql = "SELECT sl.*, 
                           v.sku_code, v.name AS variant_name, v.size AS variant_size, 
                           v.price AS variant_price, v.mrp AS variant_mrp,
                           v.category_id, v.subcategory_id,
                           c.name AS category_name, sc.name AS subcategory_name, b.name AS brand_name,
                           pp.program_no
                    FROM stitching_logs sl
                    JOIN variants v ON sl.produced_variant_id = v.id
                    LEFT JOIN production_programs pp ON sl.program_id = pp.id
                    LEFT JOIN categories c ON v.category_id = c.id
                    LEFT JOIN subcategories sc ON v.subcategory_id = sc.id
                    LEFT JOIN brands b ON v.brand_id = b.id";
        $prodParams = [];
        if ($cid) {
            $prodSql .= " WHERE sl.concern_id = ?";
            $prodParams[] = $cid;
        }
        $prodSql .= " ORDER BY sl.id DESC LIMIT 100";
        $productionItems = DB::fetchAll($prodSql, $prodParams);

        // 4. Return Labels (Customer Returns & Exchanges)
        $retSql = "SELECT re.*, 
                          cust.name AS customer_name, cust.phone AS customer_phone,
                          si.invoice_no,
                          rv.sku_code AS ret_sku, rv.name AS ret_variant_name, rv.size AS ret_size, 
                          rv.mrp AS ret_mrp, rv.price AS ret_price,
                          rv.category_id AS ret_cat_id, rv.subcategory_id AS ret_subcat_id,
                          rc.name AS ret_cat_name,
                          ev.sku_code AS exc_sku, ev.name AS exc_variant_name, ev.size AS exc_size, 
                          ev.mrp AS exc_mrp, ev.price AS exc_price,
                          ev.category_id AS exc_cat_id, ev.subcategory_id AS exc_subcat_id,
                          ec.name AS exc_cat_name
                   FROM returns_exchanges re
                   LEFT JOIN customers cust ON re.customer_id = cust.id
                   LEFT JOIN sales_invoices si ON re.sales_invoice_id = si.id
                   LEFT JOIN variants rv ON re.returned_variant_id = rv.id
                   LEFT JOIN categories rc ON rv.category_id = rc.id
                   LEFT JOIN variants ev ON re.exchange_variant_id = ev.id
                   LEFT JOIN categories ec ON ev.category_id = ec.id";
        $retParams = [];
        if ($cid) {
            $retSql .= " WHERE re.concern_id = ?";
            $retParams[] = $cid;
        }
        $retSql .= " ORDER BY re.id DESC LIMIT 100";
        $returnItems = DB::fetchAll($retSql, $retParams);

        // 5. Recent Label Jobs (Audit Trail)
        $jobSql = "SELECT lj.*, u.name AS user_name, con.name AS concern_name, con.code AS concern_code,
                          v.name AS variant_name, v.sku_code, v.category_id, v.subcategory_id
                   FROM label_jobs lj
                   LEFT JOIN variants v ON lj.variant_id = v.id
                   LEFT JOIN concerns con ON lj.concern_id = con.id
                   LEFT JOIN users u ON lj.printed_by = u.id";
        $jobParams = [];
        if ($cid) {
            $jobSql .= " WHERE lj.concern_id = ?";
            $jobParams[] = $cid;
        }
        $jobSql .= " ORDER BY lj.id DESC LIMIT 15";
        $recentJobs = DB::fetchAll($jobSql, $jobParams);

        $this->render('concern/label_generation', [
            'pageTitle'          => 'Barcode & QR Label Generation',
            'currentModule'      => 'label_generation',
            'purchaseRollItems'  => $purchaseRollItems,
            'purchaseOtherItems' => $purchaseOtherItems,
            'purchaseRawItems'   => $purchaseRawItems,
            'purchaseFgItems'    => $purchaseFgItems,
            'productionItems'    => $productionItems,
            'returnItems'        => $returnItems,
            'allVariants'        => $allVariants,
            'recentJobs'         => $recentJobs,
            'selectedCid'        => $cid,
            'activeBillId'       => $activeBillId,
            'activeBill'         => $activeBill,
            'activeTab'          => $activeTab,
            'activeSubTab'       => $activeSubTab,
        ]);
    }

    /**
     * AJAX endpoint to map an unmapped finished goods purchase item to a catalog variant
     */
    public function mapVariant(): void {
        $this->validateCsrf();
        $itemId    = (int)($_POST['item_id'] ?? 0);
        $variantId = (int)($_POST['variant_id'] ?? 0);
        $cid       = Auth::concernId();

        if (!$itemId || !$variantId) {
            $this->renderJson(['success' => false, 'message' => 'Invalid item or variant selected.'], 400);
            return;
        }

        // Verify variant belongs to tenant
        $variant = null;
        if ($cid) {
            $variant = DB::fetch("SELECT * FROM variants WHERE id = ? AND concern_id = ?", [$variantId, $cid]);
        } else {
            $variant = DB::fetch("SELECT * FROM variants WHERE id = ?", [$variantId]);
        }

        if (!$variant) {
            $this->renderJson(['success' => false, 'message' => 'Variant not found or access denied.'], 404);
            return;
        }

        $sql = "UPDATE purchase_bill_items SET variant_id = ? WHERE id = ?";
        $params = [$variantId, $itemId];
        if ($cid) {
            $sql .= " AND concern_id = ?";
            $params[] = $cid;
        }
        DB::query($sql, $params);

        $this->renderJson([
            'success'      => true,
            'message'      => "Item mapped to '{$variant['name']}' ({$variant['sku_code']}) successfully!",
            'variant_name' => $variant['name'],
            'sku_code'     => $variant['sku_code'],
            'mrp'          => $variant['mrp'],
            'size'         => $variant['size'],
            'qr'           => QrService::generatePayload((int)$variant['category_id'], (int)$variant['subcategory_id'], (int)$variant['id'])
        ]);
    }

    /**
     * Generate & render printable label sheet for any of the 4 sections
     */
    public function generate(): void {
        $this->validateCsrf();

        $labelType  = trim($_POST['label_type'] ?? 'variant');
        $qty        = max(1, min(500, (int)($_POST['qty'] ?? 12)));
        $itemId     = (int)($_POST['item_id'] ?? 0);
        $variantId  = !empty($_POST['variant_id']) ? (int)$_POST['variant_id'] : null;
        $itemTarget = trim($_POST['item_target'] ?? 'returned'); // for return label: returned or exchange
        $cid        = Auth::concernId();

        $concern = Auth::concern();
        if (!$concern && $cid) {
            $concern = DB::fetch("SELECT * FROM concerns WHERE id = ?", [$cid]);
        }
        $concernName = $concern['name'] ?? 'INVENTO ERP';

        $labelData = [
            'concern_name'   => $concernName,
            'ref_no'         => 'REF-001',
            'header_title'   => 'Garment Product',
            'label_1_name'   => 'Fabric',
            'label_1_val'    => 'Standard',
            'label_2_name'   => 'Color',
            'label_2_val'    => 'STANDARD',
            'label_3_name'   => 'Design',
            'label_3_val'    => 'STANDARD',
            'design'         => 'STANDARD',
            'label_4_name'   => 'Price',
            'label_4_val'    => '₹0.00',
            'price'          => 0.00,
            'price_mrp'      => 0.00,
            'tag_code'       => 'GAR-S-01',
            'section_title'  => 'PRODUCT LABEL',
            'section_badge'  => 'STANDARD',
            'item_title'     => 'Garment Product',
            'item_code'      => 'GAR-S-01',
            'brand_name'     => $concernName,
            'category_name'  => 'General',
            'cost_or_ref'    => '',
            'qr_payload'     => 'GAR-S-01',
            'extra_details'  => [],
        ];

        // ----------------------------------------------------
        // 1. PURCHASE LABEL (Rolls & Other Garment Things / Trims)
        // ----------------------------------------------------
        if (in_array($labelType, ['purchase_raw', 'purchase_rolls', 'purchase_other'], true)) {
            $item = DB::fetch(
                "SELECT pbi.*, pb.bill_no, pb.bill_date, s.name AS supplier_name, s.company_name AS supplier_company
                 FROM purchase_bill_items pbi
                 JOIN purchase_bills pb ON pbi.purchase_bill_id = pb.id
                 LEFT JOIN suppliers s ON pb.supplier_id = s.id
                 WHERE pbi.id = ?" . ($cid ? " AND pbi.concern_id = ?" : ""),
                $cid ? [$itemId, $cid] : [$itemId]
            );

            if (!$item) {
                $this->flash('error', 'Purchase item record not found.');
                $this->redirect('/m/label_generation');
                return;
            }

            $cid = $cid ?: (int)$item['concern_id'];
            $isRoll = ($item['section_type'] === 'rolls');
            $designVal = trim($item['pattern'] ?: ($item['specification'] ?: ($isRoll ? 'PLAIN' : 'STANDARD')));
            $priceVal = (float)($item['unit_price'] ?: ($item['mrp'] ?: 0));

            $tagCode = QrService::generateTagCode(
                $item['item_name'] ?: ($isRoll ? 'Fabric' : 'Trims'),
                $item['color'] ?? '',
                $designVal,
                (string)$item['id']
            );
            $qrText = $tagCode;

            $headerTitle = $isRoll ? ('ROLL #' . ($item['item_code'] ?: $item['id'])) : $item['item_name'];

            $labelData = [
                'concern_name'   => $concernName,
                'ref_no'         => $item['bill_no'] ?: ('PO-' . $item['purchase_bill_id']),
                'header_title'   => $headerTitle,
                'label_1_name'   => $isRoll ? 'Fabric' : 'Item',
                'label_1_val'    => $item['item_name'] ?: ($isRoll ? 'Fabric Material' : 'Garment Supply'),
                'label_2_name'   => 'Color',
                'label_2_val'    => $item['color'] ?: 'STANDARD',
                'label_3_name'   => 'Design',
                'label_3_val'    => $designVal,
                'design'         => $designVal,
                'label_4_name'   => 'Price',
                'label_4_val'    => '₹' . number_format($priceVal, 2),
                'price'          => $priceVal,
                'price_mrp'      => $priceVal,
                'tag_code'       => $tagCode,
                'qr_payload'     => $qrText,
                'section_title'  => $isRoll ? 'FABRIC ROLL LABEL' : 'TRIMS & ACCESSORIES',
                'section_badge'  => $isRoll ? 'RAW MATERIAL' : 'PURCHASE TRIM',
                'item_title'     => $headerTitle,
                'item_code'      => $tagCode,
                'brand_name'     => $concernName,
                'category_name'  => $isRoll ? 'Fabric Rolls' : 'Garment Other Things',
                'cost_or_ref'    => 'Bill: ' . $item['bill_no'],
                'bill_id'        => (int)$item['purchase_bill_id'],
                'item_id'        => (int)$item['id'],
                'stock_tab'      => $isRoll ? 'rolls' : 'garment_other',
                'inward_status'  => $item['inward_status'] ?? 'pending',
                'extra_details'  => [
                    'Design'   => $designVal,
                    'Price'    => '₹' . number_format($priceVal, 2),
                    'Bill No'  => $item['bill_no'],
                    'Date'     => $item['bill_date'] ? date('d-m-Y', strtotime($item['bill_date'])) : date('d-m-Y')
                ]
            ];
        }

        // ----------------------------------------------------
        // 2. PURCHASE FINISHED GOODS (Mapped to Catalog)
        // ----------------------------------------------------
        elseif ($labelType === 'purchase_fg') {
            $item = DB::fetch(
                "SELECT pbi.*, pb.bill_no, pb.bill_date, s.name AS supplier_name, s.company_name AS supplier_company,
                        v.sku_code, v.name AS variant_name, v.size AS variant_size, v.price AS variant_price, v.mrp AS variant_mrp,
                        v.category_id, v.subcategory_id,
                        c.name AS category_name, sc.name AS subcategory_name, b.name AS brand_name
                 FROM purchase_bill_items pbi
                 JOIN purchase_bills pb ON pbi.purchase_bill_id = pb.id
                 LEFT JOIN suppliers s ON pb.supplier_id = s.id
                 LEFT JOIN variants v ON pbi.variant_id = v.id
                 LEFT JOIN categories c ON v.category_id = c.id
                 LEFT JOIN subcategories sc ON v.subcategory_id = sc.id
                 LEFT JOIN brands b ON v.brand_id = b.id
                 WHERE pbi.id = ?" . ($cid ? " AND pbi.concern_id = ?" : ""),
                $cid ? [$itemId, $cid] : [$itemId]
            );

            if (!$item) {
                $this->flash('error', 'Purchase finished good record not found.');
                $this->redirect('/m/label_generation');
                return;
            }

            $cid = $cid ?: (int)$item['concern_id'];
            $varId = (int)($item['variant_id'] ?: ($variantId ?: 0));

            // If not mapped, map now if variantId is provided
            if (!$item['variant_id'] && $variantId) {
                DB::query("UPDATE purchase_bill_items SET variant_id = ? WHERE id = ?", [$variantId, $item['id']]);
                $varModel = new Variant();
                $v = $varModel->findWithDetails($variantId);
                if ($v) {
                    $item['variant_id']       = $v['id'];
                    $item['sku_code']         = $v['sku_code'];
                    $item['variant_name']     = $v['name'];
                    $item['variant_size']     = $v['size'];
                    $item['variant_mrp']      = $v['mrp'];
                    $item['category_id']      = $v['category_id'];
                    $item['subcategory_id']   = $v['subcategory_id'];
                    $item['category_name']    = $v['category_name'] ?? 'Apparel';
                    $item['brand_name']       = $v['brand_name'] ?? 'INVENTO';
                }
            }

            $prodName = $item['variant_name'] ?: ($item['item_name'] ?: 'Finished Garment');
            $tagCode = QrService::generateTagCode(
                $prodName,
                $item['color'] ?? '',
                $item['specification'] ?? '',
                (string)$item['id']
            );
            $qrText = $tagCode;
            $mrp = (float)($item['variant_mrp'] ?: ($item['mrp'] ?: $item['unit_price']));
            $designVal = trim($item['pattern'] ?: ($item['specification'] ?: ($item['variant_size'] ?: 'STANDARD')));

            $labelData = [
                'concern_name'   => $concernName,
                'ref_no'         => $item['bill_no'] ?: ('PO-' . $item['purchase_bill_id']),
                'header_title'   => $prodName,
                'label_1_name'   => 'Product',
                'label_1_val'    => $item['category_name'] ?: 'Ready-Made Garment',
                'label_2_name'   => 'Color',
                'label_2_val'    => $item['color'] ?: 'STANDARD',
                'label_3_name'   => 'Design',
                'label_3_val'    => $designVal,
                'design'         => $designVal,
                'label_4_name'   => 'Price',
                'label_4_val'    => '₹' . number_format($mrp, 2),
                'price'          => $mrp,
                'price_mrp'      => $mrp,
                'tag_code'       => $tagCode,
                'qr_payload'     => $qrText,
                'section_title'  => 'PURCHASE FINISHED GOODS',
                'section_badge'  => 'READY-MADE GARMENT',
                'item_title'     => $prodName,
                'item_code'      => $tagCode,
                'brand_name'     => $concernName,
                'category_name'  => $item['category_name'] ?: 'Apparel',
                'cost_or_ref'    => 'Vendor Bill: ' . $item['bill_no'],
                'bill_id'        => (int)$item['purchase_bill_id'],
                'item_id'        => (int)$item['id'],
                'stock_tab'      => 'finished_goods',
                'inward_status'  => $item['inward_status'] ?? 'pending',
                'extra_details'  => [
                    'Design'     => $designVal,
                    'Price'      => '₹' . number_format($mrp, 2),
                    'Bill No'    => $item['bill_no'],
                    'Supplier'   => $item['supplier_name'],
                    'Catalog SKU'=> $item['sku_code'] ?: 'Not Mapped',
                ]
            ];
        }

        // ----------------------------------------------------
        // 3. PRODUCTION LABELS (After Stitching Products)
        // ----------------------------------------------------
        elseif ($labelType === 'production') {
            $item = DB::fetch(
                "SELECT sl.*, 
                        v.sku_code, v.name AS variant_name, v.size AS variant_size, v.price AS variant_price, v.mrp AS variant_mrp,
                        v.category_id, v.subcategory_id,
                        c.name AS category_name, sc.name AS subcategory_name, b.name AS brand_name,
                        pp.program_no
                 FROM stitching_logs sl
                 JOIN variants v ON sl.produced_variant_id = v.id
                 LEFT JOIN production_programs pp ON sl.program_id = pp.id
                 LEFT JOIN categories c ON v.category_id = c.id
                 LEFT JOIN subcategories sc ON v.subcategory_id = sc.id
                 LEFT JOIN brands b ON v.brand_id = b.id
                 WHERE sl.id = ?" . ($cid ? " AND sl.concern_id = ?" : ""),
                $cid ? [$itemId, $cid] : [$itemId]
            );

            if (!$item) {
                // Check if user selected variant directly for production
                if ($variantId) {
                    $varModel = new Variant();
                    $v = $varModel->findWithDetails($variantId);
                    if ($v) {
                        $item = [
                            'id'                  => 0,
                            'concern_id'          => $v['concern_id'],
                            'log_no'              => 'STITCH-VAR-' . $v['id'],
                            'produced_variant_id' => $v['id'],
                            'sku_code'            => $v['sku_code'],
                            'variant_name'        => $v['name'],
                            'variant_size'        => $v['size'],
                            'variant_mrp'         => $v['mrp'],
                            'category_id'         => $v['category_id'],
                            'subcategory_id'      => $v['subcategory_id'],
                            'category_name'       => $v['category_name'] ?? 'In-House Production',
                            'brand_name'          => $v['brand_name'] ?? 'INVENTO Apparel',
                            'program_no'          => 'In-House Production'
                        ];
                    }
                }
            }

            if (!$item) {
                $this->flash('error', 'Production stitching batch record not found.');
                $this->redirect('/m/label_generation');
                return;
            }

            $cid = $cid ?: (int)$item['concern_id'];
            $variantId = (int)$item['produced_variant_id'];
            $prodName = $item['variant_name'] ?: 'In-House Garment';
            $tagCode = QrService::generateTagCode(
                $prodName,
                $item['color'] ?? '',
                !empty($item['pattern']) ? $item['pattern'] : ($item['program_no'] ?? ''),
                (string)$variantId
            );
            $qrText = $tagCode;

            $designVal = trim(!empty($item['pattern']) ? $item['pattern'] : ($item['program_no'] ?: 'STANDARD'));
            $priceVal = (float)($item['variant_mrp'] ?: ($item['variant_price'] ?? 0));

            $labelData = [
                'concern_name'   => $concernName,
                'ref_no'         => $item['program_no'] ?: ('STITCH-' . ($item['log_no'] ?? '1')),
                'header_title'   => $prodName,
                'label_1_name'   => 'Program',
                'label_1_val'    => $item['program_no'] ?: 'In-House Production',
                'label_2_name'   => 'Color',
                'label_2_val'    => $item['color'] ?? 'STANDARD',
                'label_3_name'   => 'Design',
                'label_3_val'    => $designVal,
                'design'         => $designVal,
                'label_4_name'   => 'Price',
                'label_4_val'    => '₹' . number_format($priceVal, 2),
                'price'          => $priceVal,
                'price_mrp'      => $priceVal,
                'tag_code'       => $tagCode,
                'qr_payload'     => $qrText,
                'section_title'  => 'PRODUCTION LABEL',
                'section_badge'  => 'IN-HOUSE STITCHED',
                'item_title'     => $prodName,
                'item_code'      => $tagCode,
                'brand_name'     => $concernName,
                'category_name'  => $item['category_name'] ?: 'Garments',
                'cost_or_ref'    => 'Log: ' . $item['log_no'],
                'extra_details'  => [
                    'Design'     => $designVal,
                    'Price'      => '₹' . number_format($priceVal, 2),
                    'Batch Log'  => $item['log_no'],
                    'Program'    => $item['program_no'] ?: 'Standard Production',
                    'Assembled'  => date('d-m-Y')
                ]
            ];
        }

        // ----------------------------------------------------
        // 4. RETURN LABELS (Customer Returns & Exchanges)
        // ----------------------------------------------------
        elseif ($labelType === 'return') {
            $item = DB::fetch(
                "SELECT re.*, 
                        cust.name AS customer_name, cust.phone AS customer_phone,
                        si.invoice_no,
                        rv.id AS ret_var_id, rv.sku_code AS ret_sku, rv.name AS ret_variant_name, rv.size AS ret_size, 
                        rv.mrp AS ret_mrp, rv.category_id AS ret_cat_id, rv.subcategory_id AS ret_subcat_id,
                        rc.name AS ret_cat_name, rb.name AS ret_brand_name,
                        ev.id AS exc_var_id, ev.sku_code AS exc_sku, ev.name AS exc_variant_name, ev.size AS exc_size, 
                        ev.mrp AS exc_mrp, ev.category_id AS exc_cat_id, ev.subcategory_id AS exc_subcat_id,
                        ec.name AS exc_cat_name, eb.name AS exc_brand_name
                 FROM returns_exchanges re
                 LEFT JOIN customers cust ON re.customer_id = cust.id
                 LEFT JOIN sales_invoices si ON re.sales_invoice_id = si.id
                 LEFT JOIN variants rv ON re.returned_variant_id = rv.id
                 LEFT JOIN categories rc ON rv.category_id = rc.id
                 LEFT JOIN brands rb ON rv.brand_id = rb.id
                 LEFT JOIN variants ev ON re.exchange_variant_id = ev.id
                 LEFT JOIN categories ec ON ev.category_id = ec.id
                 LEFT JOIN brands eb ON ev.brand_id = eb.id
                 WHERE re.id = ?" . ($cid ? " AND re.concern_id = ?" : ""),
                $cid ? [$itemId, $cid] : [$itemId]
            );

            if (!$item) {
                $this->flash('error', 'Return / Exchange record not found.');
                $this->redirect('/m/label_generation');
                return;
            }

            $cid = $cid ?: (int)$item['concern_id'];

            // Choose whether user wanted the Returned Item tag or the Exchange Item tag
            $isExchange = ($itemTarget === 'exchange' && !empty($item['exc_var_id']));
            if ($isExchange) {
                $variantId = (int)$item['exc_var_id'];
                $varName   = $item['exc_variant_name'];
                $sku       = $item['exc_sku'];
                $size      = $item['exc_size'];
                $mrp       = (float)$item['exc_mrp'];
                $catName   = $item['exc_cat_name'];
                $brand     = $item['exc_brand_name'];
                $catId     = (int)$item['exc_cat_id'];
                $subcatId  = (int)$item['exc_subcat_id'];
                $badge     = 'EXCHANGE DISPATCH';
            } else {
                $variantId = (int)$item['ret_var_id'];
                $varName   = $item['ret_variant_name'];
                $sku       = $item['ret_sku'];
                $size      = $item['ret_size'];
                $mrp       = (float)$item['ret_mrp'];
                $catName   = $item['ret_cat_name'];
                $brand     = $item['ret_brand_name'];
                $catId     = (int)$item['ret_cat_id'];
                $subcatId  = (int)$item['ret_subcat_id'];
                $badge     = 'RESTOCK / RETURN';
            }

            $prodName = $varName ?: 'Returned Product';
            $tagCode = QrService::generateTagCode(
                $prodName,
                '',
                'RET',
                (string)$variantId
            );
            $qrText = $tagCode;

            $designVal = trim($item['pattern'] ?? 'RESTOCK');
            $priceVal = (float)($mrp ?: 0);

            $labelData = [
                'concern_name'   => $concernName,
                'ref_no'         => $item['return_no'] ?: 'RET-0001',
                'header_title'   => $prodName,
                'label_1_name'   => 'Customer',
                'label_1_val'    => $item['customer_name'] ?: 'Retail Customer',
                'label_2_name'   => 'Color',
                'label_2_val'    => 'STANDARD',
                'label_3_name'   => 'Design',
                'label_3_val'    => $designVal,
                'design'         => $designVal,
                'label_4_name'   => 'Price',
                'label_4_val'    => '₹' . number_format($priceVal, 2),
                'price'          => $priceVal,
                'price_mrp'      => $priceVal,
                'tag_code'       => $tagCode,
                'qr_payload'     => $qrText,
                'section_title'  => 'RETURN / RESTOCK LABEL',
                'section_badge'  => $badge,
                'item_title'     => $prodName,
                'item_code'      => $tagCode,
                'brand_name'     => $concernName,
                'category_name'  => $catName ?: 'Apparel',
                'cost_or_ref'    => 'Return #' . $item['return_no'],
                'extra_details'  => [
                    'Design'     => $designVal,
                    'Price'      => '₹' . number_format($priceVal, 2),
                    'Return No'  => $item['return_no'],
                    'Invoice No' => $item['invoice_no'] ?: 'Direct Return',
                    'Date'       => date('d-m-Y', strtotime($item['return_date'] ?: date('Y-m-d'))),
                    'Customer'   => $item['customer_name']
                ]
            ];
        }

        // ----------------------------------------------------
        // 5. DIRECT VARIANT FALLBACK
        // ----------------------------------------------------
        else {
            $variantId = (int)($_POST['variant_id'] ?? 0);
            $varModel = new Variant();
            $v = $varModel->findWithDetails($variantId);

            if (!$v) {
                $this->flash('error', 'Selected variant was not found.');
                $this->redirect('/m/label_generation');
                return;
            }

            $cid = $cid ?: (int)$v['concern_id'];
            $designVal = $v['subcategory_name'] ?: 'STANDARD';
            $priceVal = (float)$v['mrp'];

            $tagCode = QrService::generateTagCode(
                $v['name'],
                '',
                $designVal,
                (string)$v['id']
            );
            $qrText = $tagCode;

            $labelData = [
                'concern_name'   => $concernName,
                'ref_no'         => $v['sku_code'],
                'header_title'   => $v['name'],
                'label_1_name'   => 'Category',
                'label_1_val'    => $v['category_name'] ?? 'Apparel',
                'label_2_name'   => 'Color',
                'label_2_val'    => 'STANDARD',
                'label_3_name'   => 'Design',
                'label_3_val'    => $designVal,
                'design'         => $designVal,
                'label_4_name'   => 'Price',
                'label_4_val'    => '₹' . number_format($priceVal, 2),
                'price'          => $priceVal,
                'price_mrp'      => $priceVal,
                'tag_code'       => $tagCode,
                'qr_payload'     => $qrText,
                'section_title'  => 'PRODUCT CATALOG LABEL',
                'section_badge'  => 'CATALOG VARIANT',
                'item_title'     => $v['name'],
                'item_code'      => $tagCode,
                'brand_name'     => $concernName,
                'category_name'  => $v['category_name'] ?? 'Apparel',
                'cost_or_ref'    => 'Catalog',
                'extra_details'  => [
                    'Design'     => $designVal,
                    'Price'      => '₹' . number_format($priceVal, 2),
                    'SKU'        => $v['sku_code'],
                ]
            ];
        }

        // Save print job for audit
        DB::query(
            "INSERT INTO label_jobs (concern_id, label_type, ref_id, variant_id, item_title, item_code, qr_payload, qty, printed_by, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
            [
                $cid,
                $labelType,
                $itemId ?: null,
                $variantId ?: null,
                $labelData['item_title'],
                $labelData['item_code'],
                $labelData['qr_payload'],
                $qty,
                Auth::id()
            ]
        );
        $jobId = (int)DB::lastInsertId();

        AuditLog::log('GENERATE_LABELS', 'label_jobs', $jobId, null, [
            'label_type' => $labelType,
            'item_title' => $labelData['item_title'],
            'item_code'  => $labelData['item_code'],
            'qty'        => $qty,
            'qr_payload' => $labelData['qr_payload']
        ]);

        // Render printable sheet
        $this->render('concern/label_print_sheet', [
            'pageTitle'   => "Print Labels - {$labelData['item_code']} ({$qty} Copies)",
            'noLayout'    => true,
            'labelData'   => $labelData,
            'qty'         => $qty,
            'qrCodeText'  => $labelData['qr_payload'],
            'jobId'       => $jobId,
            'labelType'   => $labelType,
        ]);
    }

    public function scanPage(): void {
        $this->render('concern/scan', [
            'pageTitle'     => 'Live QR / Barcode Scanner',
            'currentModule' => 'stocks',
        ]);
    }

    /**
     * AJAX endpoint to lookup scanned QR code
     */
    public function lookup(): void {
        $cid = Auth::concernId();
        $code = trim($_GET['code'] ?? $_POST['code'] ?? '');

        if (empty($code)) {
            $this->renderJson(['success' => false, 'message' => 'Empty barcode/QR code'], 400);
        }

        // 1. If Super Admin has not filtered to a single concern, detect variant concern
        if (Auth::isSuperAdmin() && !$cid) {
            $parsed = QrService::parsePayload($code);
            if ($parsed) {
                $varCid = DB::fetchValue("SELECT concern_id FROM variants WHERE id = ?", [$parsed['variant_id']]);
                if ($varCid) {
                    $cid = (int)$varCid;
                }
            }
        }

        // 2. Check if this is a Purchase Roll QR: e.g. PUR-ROLL-{id}-...
        if (str_starts_with($code, 'PUR-ROLL-') || str_starts_with($code, 'PUR-TRIM-')) {
            $parts = explode('-', $code);
            $pbiId = isset($parts[2]) ? (int)$parts[2] : 0;
            if ($pbiId) {
                $item = DB::fetch(
                    "SELECT pbi.*, pb.bill_no, pb.bill_date, s.name AS supplier_name, s.company_name AS supplier_company
                     FROM purchase_bill_items pbi
                     JOIN purchase_bills pb ON pbi.purchase_bill_id = pb.id
                     LEFT JOIN suppliers s ON pb.supplier_id = s.id
                     WHERE pbi.id = ?" . ($cid ? " AND pbi.concern_id = ?" : ""),
                    $cid ? [$pbiId, $cid] : [$pbiId]
                );

                if ($item) {
                    $this->renderJson([
                        'success' => true,
                        'data'    => [
                            'name'          => $item['item_name'],
                            'sku_code'      => $item['item_code'],
                            'category_name' => ($item['section_type'] === 'rolls' ? 'Fabric Roll' : 'Trims & Accessories'),
                            'current_stock' => $item['qty'],
                            'price'         => $item['unit_price'],
                            'mrp'           => $item['mrp'] ?: $item['unit_price'],
                            'size'          => number_format((float)$item['qty'], 2) . ' ' . $item['unit'],
                            'brand_name'    => $item['supplier_company'] ?: $item['supplier_name'],
                            'attributes_parsed' => [
                                'Section'     => strtoupper($item['section_type']),
                                'Bill No'     => $item['bill_no'],
                                'Color'       => $item['color'] ?: '-',
                                'Net Weight'  => $item['net_weight'] ? $item['net_weight'] . ' kg' : '-',
                                'Gross Weight'=> $item['gross_weight'] ? $item['gross_weight'] . ' kg' : '-',
                            ]
                        ]
                    ]);
                    return;
                }
            }
        }

        // 2b. Check if this is a simplified tag code lookup from label_jobs or purchase_bill_items
        $job = DB::fetch(
            "SELECT * FROM label_jobs WHERE qr_payload = ?" . ($cid ? " AND concern_id = ?" : "") . " ORDER BY id DESC LIMIT 1",
            $cid ? [$code, $cid] : [$code]
        );
        if ($job) {
            if ($job['label_type'] === 'purchase_raw' && !empty($job['ref_id'])) {
                $pbi = DB::fetch(
                    "SELECT pbi.*, pb.bill_no, pb.bill_date, s.name AS supplier_name, s.company_name AS supplier_company
                     FROM purchase_bill_items pbi
                     JOIN purchase_bills pb ON pbi.purchase_bill_id = pb.id
                     LEFT JOIN suppliers s ON pb.supplier_id = s.id
                     WHERE pbi.id = ?" . ($cid ? " AND pbi.concern_id = ?" : ""),
                    $cid ? [$job['ref_id'], $cid] : [$job['ref_id']]
                );
                if ($pbi) {
                    $this->renderJson([
                        'success' => true,
                        'data'    => [
                            'name'          => $pbi['item_name'],
                            'sku_code'      => $job['item_code'],
                            'category_name' => ($pbi['section_type'] === 'rolls' ? 'Fabric Roll' : 'Trims & Accessories'),
                            'current_stock' => $pbi['qty'],
                            'price'         => $pbi['unit_price'],
                            'mrp'           => $pbi['mrp'] ?: $pbi['unit_price'],
                            'size'          => number_format((float)$pbi['qty'], 2) . ' ' . $pbi['unit'],
                            'brand_name'    => $pbi['supplier_company'] ?: $pbi['supplier_name'],
                            'attributes_parsed' => [
                                'Section'     => strtoupper($pbi['section_type']),
                                'Bill No'     => $pbi['bill_no'],
                                'Color'       => $pbi['color'] ?: '-',
                                'Net Weight'  => $pbi['net_weight'] ? $pbi['net_weight'] . ' kg' : '-',
                                'Gross Weight'=> $pbi['gross_weight'] ? $pbi['gross_weight'] . ' kg' : '-',
                                'Code'        => $job['item_code'],
                            ]
                        ]
                    ]);
                    return;
                }
            } elseif (!empty($job['variant_id'])) {
                $var = (new Variant())->findWithDetails((int)$job['variant_id']);
                if ($var && (!$cid || (int)$var['concern_id'] === $cid)) {
                    $this->renderJson([
                        'success' => true,
                        'data'    => $var
                    ]);
                    return;
                }
            }
        }

        // 3. Standard Variant QR Lookup
        if (!$cid) {
            $this->renderJson(['success' => false, 'message' => 'Variant not found.'], 404);
        }

        $variant = QrService::lookupVariant($code, $cid);

        if (!$variant) {
            $this->renderJson([
                'success' => false,
                'message' => "QR Code '{$code}' not found or belongs to another concern under tenant isolation."
            ], 404);
        }

        $this->renderJson([
            'success' => true,
            'data'    => $variant,
        ]);
    }

    /**
     * Inward purchase items / purchase bill directly into Purchase Stocks
     * Transitions inward status from pending to completed, syncs stock ledger & roll specs,
     * and redirects user to the Purchase Stocks module.
     */
    public function addToStock(): void {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405);
            if (!headers_sent()) {
                header('Allow: POST');
            }
            $this->flash('error', 'Use the Label Generation Add to Stock form to inward items.');
            $this->redirect('/m/label_generation');
            return;
        }
        $this->validateCsrf();

        $cid = Auth::concernId();
        $billId = (int)($_POST['bill_id'] ?? ($_GET['bill_id'] ?? 0));
        $itemId = (int)($_POST['item_id'] ?? ($_GET['item_id'] ?? 0));
        $preferredTab = trim($_POST['tab'] ?? ($_GET['tab'] ?? ''));

        if (!$billId && !$itemId) {
            $this->flash('error', 'Invalid request: No purchase bill or item specified for stock inwarding.');
            $this->redirect('/m/label_generation');
            return;
        }

        try {
            DB::beginTransaction();

            $targetTab = 'rolls';
            $billNo = '';

            if ($billId > 0) {
                // Inward full bill
                $billSql = "SELECT * FROM purchase_bills WHERE id = ?";
                $billParams = [$billId];
                if ($cid) {
                    $billSql .= " AND concern_id = ?";
                    $billParams[] = $cid;
                }
                $bill = DB::fetch($billSql, $billParams);
                if (!$bill) {
                    throw new Exception("Purchase bill #{$billId} not found or access denied.");
                }

                $concernId = (int)$bill['concern_id'];
                $billNo = $bill['bill_no'];

                // Update bill inward_status to completed
                DB::query("UPDATE purchase_bills SET inward_status = 'completed' WHERE id = ?", [$billId]);
                DB::query("UPDATE purchase_bill_items SET inward_status = 'completed' WHERE purchase_bill_id = ?", [$billId]);

                $items = DB::fetchAll("SELECT * FROM purchase_bill_items WHERE purchase_bill_id = ?", [$billId]);
                $hasRolls = false;
                $hasOther = false;
                $hasFg = false;

                foreach ($items as $it) {
                    if ($it['section_type'] === 'rolls') {
                        $hasRolls = true;
                    } elseif ($it['section_type'] === 'garment_other') {
                        $hasOther = true;
                    } elseif ($it['section_type'] === 'finished_goods') {
                        $hasFg = true;
                        if (!empty($it['variant_id'])) {
                            // Check if already moved into stock ledger for this bill
                            $alreadyLedger = DB::fetchValue(
                                "SELECT id FROM stock_ledger WHERE concern_id = ? AND variant_id = ? AND ref_module = 'Purchase Inward' AND ref_id = ? LIMIT 1",
                                [$concernId, $it['variant_id'], $billId]
                            );
                            if (!$alreadyLedger) {
                                StockService::move(
                                    $concernId,
                                    (int)$it['variant_id'],
                                    'Main Store',
                                    (float)$it['qty'],
                                    0.00,
                                    'Purchase Inward',
                                    $billId,
                                    "Inward from Purchase Bill #{$billNo} via Label Generation Hub"
                                );
                            }
                        }
                    }
                }

                if ($hasRolls) {
                    RollRegistryService::syncSpecs($concernId);
                }

                if ($preferredTab && in_array($preferredTab, ['rolls', 'garment_other', 'finished_goods'], true)) {
                    $targetTab = $preferredTab;
                } elseif ($hasRolls) {
                    $targetTab = 'rolls';
                } elseif ($hasOther) {
                    $targetTab = 'garment_other';
                } elseif ($hasFg) {
                    $targetTab = 'finished_goods';
                }

                if (DB::pdo()->inTransaction()) {
                    DB::commit();
                }

                $this->flash('success', "Purchase Bill '{$billNo}' items successfully added to stock! Purchase inventory updated.");
                $this->redirect("/m/purchase_stocks?tab={$targetTab}");
                return;
            }

            if ($itemId > 0) {
                // Inward individual item
                $itemSql = "SELECT pbi.*, pb.bill_no, pb.concern_id AS b_cid 
                            FROM purchase_bill_items pbi 
                            JOIN purchase_bills pb ON pbi.purchase_bill_id = pb.id 
                            WHERE pbi.id = ?";
                $itemParams = [$itemId];
                if ($cid) {
                    $itemSql .= " AND pbi.concern_id = ?";
                    $itemParams[] = $cid;
                }
                $item = DB::fetch($itemSql, $itemParams);
                if (!$item) {
                    throw new Exception("Purchase item #{$itemId} not found or access denied.");
                }

                $concernId = (int)$item['concern_id'];
                $billId = (int)$item['purchase_bill_id'];
                $billNo = $item['bill_no'];

                DB::query("UPDATE purchase_bill_items SET inward_status = 'completed' WHERE id = ?", [$itemId]);

                // Check if all items in the bill are completed
                $pendingCount = (int)DB::fetchValue(
                    "SELECT COUNT(*) FROM purchase_bill_items WHERE purchase_bill_id = ? AND inward_status = 'pending'",
                    [$billId]
                );
                if ($pendingCount === 0) {
                    DB::query("UPDATE purchase_bills SET inward_status = 'completed' WHERE id = ?", [$billId]);
                }

                if ($item['section_type'] === 'rolls') {
                    RollRegistryService::syncSpecs($concernId);
                    $targetTab = 'rolls';
                } elseif ($item['section_type'] === 'garment_other') {
                    $targetTab = 'garment_other';
                } elseif ($item['section_type'] === 'finished_goods') {
                    $targetTab = 'finished_goods';
                    if (!empty($item['variant_id'])) {
                        $alreadyLedger = DB::fetchValue(
                            "SELECT id FROM stock_ledger WHERE concern_id = ? AND variant_id = ? AND ref_module = 'Purchase Inward' AND ref_id = ? LIMIT 1",
                            [$concernId, $item['variant_id'], $billId]
                        );
                        if (!$alreadyLedger) {
                            StockService::move(
                                $concernId,
                                (int)$item['variant_id'],
                                'Main Store',
                                (float)$item['qty'],
                                0.00,
                                'Purchase Inward',
                                $billId,
                                "Item '{$item['item_name']}' inward from Bill #{$billNo}"
                            );
                        }
                    }
                }

                if ($preferredTab && in_array($preferredTab, ['rolls', 'garment_other', 'finished_goods'], true)) {
                    $targetTab = $preferredTab;
                }

                if (DB::pdo()->inTransaction()) {
                    DB::commit();
                }

                $this->flash('success', "Item '{$item['item_name']}' from Bill '{$billNo}' added to stock successfully!");
                $this->redirect("/m/purchase_stocks?tab={$targetTab}");
                return;
            }

        } catch (Exception $e) {
            if (DB::pdo()->inTransaction()) {
                DB::rollBack();
            }
            $this->flash('error', 'Error adding item to stock: ' . $e->getMessage());
            $this->redirect('/m/label_generation' . ($billId ? "?bill_id={$billId}" : ''));
        }
    }
}
