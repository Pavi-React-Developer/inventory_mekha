<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\DB;
use App\Core\ModuleGuard;
use App\Services\RollRegistryService;

class PurchaseStockController extends Controller {

    public function index(): void {
        ModuleGuard::enforce('purchase_stocks', 'view');

        $isSuperAdmin = Auth::isSuperAdmin();
        $userCid = Auth::concernId();
        $selectedCid = $userCid;

        if ($isSuperAdmin) {
            if (isset($_GET['concern_id']) && $_GET['concern_id'] !== '') {
                $selectedCid = (int)$_GET['concern_id'];
            } else {
                $selectedCid = null; // Consolidated
            }
        }

        $activeTab = trim($_GET['tab'] ?? 'rolls');
        if (!in_array($activeTab, ['rolls', 'garment_other', 'finished_goods'], true)) {
            $activeTab = 'rolls';
        }

        $search = trim($_GET['q'] ?? '');
        $supplierFilter = (int)($_GET['supplier_id'] ?? 0);
        $statusFilter = trim($_GET['status'] ?? '');

        // Base where condition builder
        $buildWhere = function(string $secType) use ($selectedCid, $search, $supplierFilter, $statusFilter) {
            $where = ["pbi.section_type = ?"];
            $params = [$secType];

            if ($selectedCid) {
                $where[] = "pbi.concern_id = ?";
                $params[] = $selectedCid;
            }

            if (!empty($search)) {
                $like = "%{$search}%";
                $where[] = "(pbi.item_code LIKE ? OR pbi.item_name LIKE ? OR pbi.pattern LIKE ? OR pbi.color LIKE ? OR pbi.specification LIKE ? OR pb.bill_no LIKE ? OR s.name LIKE ? OR s.company_name LIKE ?)";
                $params = array_merge($params, [$like, $like, $like, $like, $like, $like, $like, $like]);
            }

            if ($supplierFilter > 0) {
                $where[] = "pb.supplier_id = ?";
                $params[] = $supplierFilter;
            }

            if (!empty($statusFilter)) {
                if ($statusFilter === 'in_stock') {
                    $where[] = "pb.inward_status = 'completed'";
                } elseif ($statusFilter === 'pending') {
                    $where[] = "pb.inward_status = 'pending'";
                }
            }

            return ['sql' => implode(' AND ', $where), 'params' => $params];
        };

        // 1. Fetch Fabric Rolls (Individual batches)
        $rollsWhere = $buildWhere('rolls');
        $rolls = DB::fetchAll(
            "SELECT pbi.*, pb.bill_no, pb.bill_date, pb.supplier_invoice_no, pb.inward_status, pb.payment_status,
                    s.name AS supplier_name, s.company_name AS supplier_company,
                    c.name AS concern_name, c.code AS concern_code
             FROM purchase_bill_items pbi
             JOIN purchase_bills pb ON pbi.purchase_bill_id = pb.id
             LEFT JOIN suppliers s ON pb.supplier_id = s.id
             LEFT JOIN concerns c ON pbi.concern_id = c.id
             WHERE {$rollsWhere['sql']}
             ORDER BY pbi.id DESC",
            $rollsWhere['params']
        );

        // Fetch Consolidated Color & Design Roll Stock Registry (Removes duplicate register entries & adds counts)
        RollRegistryService::syncSpecs($selectedCid);
        $consolidatedRolls = RollRegistryService::getConsolidatedStock($selectedCid, $search);
        $rollViewMode = trim($_GET['view'] ?? 'consolidated');

        // 2. Fetch Other Garment Supplies & Trims
        $otherWhere = $buildWhere('garment_other');
        $garmentOther = DB::fetchAll(
            "SELECT pbi.*, pb.bill_no, pb.bill_date, pb.supplier_invoice_no, pb.inward_status, pb.payment_status,
                    s.name AS supplier_name, s.company_name AS supplier_company,
                    c.name AS concern_name, c.code AS concern_code
             FROM purchase_bill_items pbi
             JOIN purchase_bills pb ON pbi.purchase_bill_id = pb.id
             LEFT JOIN suppliers s ON pb.supplier_id = s.id
             LEFT JOIN concerns c ON pbi.concern_id = c.id
             WHERE {$otherWhere['sql']}
             ORDER BY pbi.id DESC",
            $otherWhere['params']
        );

        // 3. Fetch Finished Goods
        $finishedWhere = $buildWhere('finished_goods');
        $finishedGoods = DB::fetchAll(
            "SELECT pbi.*, pb.bill_no, pb.bill_date, pb.supplier_invoice_no, pb.inward_status, pb.payment_status,
                    s.name AS supplier_name, s.company_name AS supplier_company,
                    c.name AS concern_name, c.code AS concern_code,
                    v.sku_code AS variant_sku, v.name AS variant_name, v.size AS variant_size,
                    (SELECT st.qty FROM stocks st WHERE st.variant_id = pbi.variant_id AND st.concern_id = pbi.concern_id AND st.location = 'Main Store' LIMIT 1) AS live_variant_stock
             FROM purchase_bill_items pbi
             JOIN purchase_bills pb ON pbi.purchase_bill_id = pb.id
             LEFT JOIN suppliers s ON pb.supplier_id = s.id
             LEFT JOIN concerns c ON pbi.concern_id = c.id
             LEFT JOIN variants v ON pbi.variant_id = v.id
             WHERE {$finishedWhere['sql']}
             ORDER BY pbi.id DESC",
            $finishedWhere['params']
        );

        // Calculate KPI Metrics across current concern scope (unfiltered by search for accurate totals)
        $metricWhere = $selectedCid ? "WHERE pbi.concern_id = ?" : "";
        $metricParams = $selectedCid ? [$selectedCid] : [];

        $allMetrics = DB::fetchAll(
            "SELECT pbi.section_type, 
                    SUM(pbi.qty) AS total_qty, 
                    SUM(COALESCE(pbi.net_weight, 0)) AS total_weight,
                    SUM(pbi.total_price) AS total_val,
                    SUM(COALESCE(pbi.mrp, 0) * pbi.qty) AS total_mrp_val,
                    COUNT(pbi.id) AS item_count
             FROM purchase_bill_items pbi
             {$metricWhere}
             GROUP BY pbi.section_type",
            $metricParams
        );

        $metrics = [
            'rolls_qty'        => 0.0,
            'rolls_weight'     => 0.0,
            'rolls_value'      => 0.0,
            'rolls_count'      => 0,
            'other_qty'        => 0.0,
            'other_count'      => 0,
            'other_value'      => 0.0,
            'finished_qty'     => 0.0,
            'finished_count'   => 0,
            'finished_value'   => 0.0,
            'finished_mrp_val' => 0.0,
            'grand_value'      => 0.0,
        ];

        foreach ($allMetrics as $m) {
            $t = $m['section_type'];
            if ($t === 'rolls') {
                $metrics['rolls_qty']    = (float)$m['total_qty'];
                $metrics['rolls_weight'] = (float)$m['total_weight'];
                $metrics['rolls_value']  = (float)$m['total_val'];
                $metrics['rolls_count']  = (int)$m['item_count'];
            } elseif ($t === 'garment_other') {
                $metrics['other_qty']   = (float)$m['total_qty'];
                $metrics['other_value'] = (float)$m['total_val'];
                $metrics['other_count'] = (int)$m['item_count'];
            } elseif ($t === 'finished_goods') {
                $metrics['finished_qty']     = (float)$m['total_qty'];
                $metrics['finished_value']   = (float)$m['total_val'];
                $metrics['finished_mrp_val'] = (float)$m['total_mrp_val'];
                $metrics['finished_count']   = (int)$m['item_count'];
            }
            $metrics['grand_value'] += (float)$m['total_val'];
        }

        // Supplier filter list
        $supSql = "SELECT id, name, company_name FROM suppliers";
        $supParams = [];
        if ($selectedCid) {
            $supSql .= " WHERE concern_id = ?";
            $supParams[] = $selectedCid;
        }
        $supSql .= " ORDER BY name ASC";
        $suppliers = DB::fetchAll($supSql, $supParams);

        $allConcerns = $isSuperAdmin ? DB::fetchAll("SELECT id, name, code FROM concerns ORDER BY name ASC") : [];

        $this->render('concern/purchase_stocks', [
            'pageTitle'         => 'Purchase Stocks & Inventory',
            'currentModule'     => 'purchase_stocks',
            'activeTab'         => $activeTab,
            'rolls'             => $rolls,
            'consolidatedRolls' => $consolidatedRolls,
            'rollViewMode'      => $rollViewMode,
            'garmentOther'      => $garmentOther,
            'finishedGoods'     => $finishedGoods,
            'metrics'           => $metrics,
            'suppliers'         => $suppliers,
            'allConcerns'       => $allConcerns,
            'selectedCid'       => $selectedCid,
            'search'            => $search,
            'supplierFilter'    => $supplierFilter,
            'statusFilter'      => $statusFilter,
        ]);
    }

    public function export(): void {
        ModuleGuard::enforce('purchase_stocks', 'view');

        $isSuperAdmin = Auth::isSuperAdmin();
        $userCid = Auth::concernId();
        $selectedCid = $userCid;
        if ($isSuperAdmin && isset($_GET['concern_id']) && $_GET['concern_id'] !== '') {
            $selectedCid = (int)$_GET['concern_id'];
        }

        $tab = trim($_GET['tab'] ?? 'rolls');
        if (!in_array($tab, ['rolls', 'garment_other', 'finished_goods'], true)) {
            $tab = 'rolls';
        }

        $where = ["pbi.section_type = ?"];
        $params = [$tab];
        if ($selectedCid) {
            $where[] = "pbi.concern_id = ?";
            $params[] = $selectedCid;
        }

        $items = DB::fetchAll(
            "SELECT pbi.*, pb.bill_no, pb.bill_date, pb.supplier_invoice_no, pb.inward_status,
                    s.name AS supplier_name, s.company_name AS supplier_company,
                    c.name AS concern_name, c.code AS concern_code
             FROM purchase_bill_items pbi
             JOIN purchase_bills pb ON pbi.purchase_bill_id = pb.id
             LEFT JOIN suppliers s ON pb.supplier_id = s.id
             LEFT JOIN concerns c ON pbi.concern_id = c.id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY pbi.id DESC",
            $params
        );

        $filename = "purchase_stocks_{$tab}_" . date('Ymd_His') . ".csv";

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        // Add UTF-8 BOM for Excel compatibility
        fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

        if ($tab === 'rolls') {
            $isDetailed = (trim($_GET['mode'] ?? '') === 'detailed');
            if ($isDetailed) {
                fputcsv($out, ['Roll Code', 'Fabric Name', 'Color / Shade', 'Pattern', 'HSN', 'Gross Wt (kg)', 'Net Wt (kg)', 'Qty (Rolls)', 'Unit Price (INR)', 'Total Value (INR)', 'Bill No', 'Bill Date', 'Supplier', 'Inward Status']);
                foreach ($items as $r) {
                    fputcsv($out, [
                        $r['item_code'] ?: '-',
                        $r['item_name'] ?: '-',
                        $r['color'] ?: '-',
                        $r['pattern'] ?: '-',
                        $r['hsn_code'] ?: '-',
                        $r['gross_weight'] ?? 0,
                        $r['net_weight'] ?? 0,
                        $r['qty'],
                        $r['unit_price'],
                        $r['total_price'],
                        $r['bill_no'],
                        $r['bill_date'],
                        $r['supplier_name'] ?: '-',
                        strtoupper($r['inward_status'] ?? 'PENDING')
                    ]);
                }
            } else {
                // Consolidated Color & Design Stock Report (removes duplicates, accumulates counts)
                RollRegistryService::syncSpecs($selectedCid);
                $consRolls = RollRegistryService::getConsolidatedStock($selectedCid);
                fputcsv($out, ['Design Name / Pattern', 'Color / Shade', 'HSN Code', 'Total Available Qty (Rolls)', 'Total Fabric Weight (kg)', 'Unit Rate (INR)', 'Total Stock Valuation (INR)', 'Inward Batches Count', 'Latest Bill No', 'Latest Supplier', 'Stock Status']);
                foreach ($consRolls as $cr) {
                    fputcsv($out, [
                        $cr['design_name'] ?: '-',
                        $cr['color'] ?: '-',
                        $cr['hsn_code'] ?: '-',
                        $cr['qty'],
                        $cr['net_weight'],
                        $cr['last_unit_price'],
                        $cr['total_value'],
                        $cr['bills_count'],
                        $cr['last_bill_no'] ?: '-',
                        $cr['last_supplier_name'] ?: '-',
                        'IN STOCK'
                    ]);
                }
            }
        } elseif ($tab === 'garment_other') {
            fputcsv($out, ['Item Code', 'Item Name', 'Specification', 'Unit', 'Qty Inwarded', 'Unit Price (INR)', 'Total Value (INR)', 'Bill No', 'Bill Date', 'Supplier', 'Inward Status']);
            foreach ($items as $g) {
                fputcsv($out, [
                    $g['item_code'] ?: '-',
                    $g['item_name'] ?: '-',
                    $g['specification'] ?: '-',
                    $g['unit'] ?: 'Pcs',
                    $g['qty'],
                    $g['unit_price'],
                    $g['total_price'],
                    $g['bill_no'],
                    $g['bill_date'],
                    $g['supplier_name'] ?: '-',
                    strtoupper($g['inward_status'] ?? 'PENDING')
                ]);
            }
        } else {
            fputcsv($out, ['SKU / Code', 'Garment Product', 'Category', 'Specification / Size', 'Color', 'MRP (INR)', 'Purchase Price (INR)', 'Available Qty', 'Total Value (INR)', 'Bill No', 'Bill Date', 'Supplier', 'Inward Status']);
            foreach ($items as $f) {
                fputcsv($out, [
                    $f['item_code'] ?: '-',
                    $f['item_name'] ?: '-',
                    $f['category_name'] ?: '-',
                    $f['specification'] ?: '-',
                    $f['color'] ?: '-',
                    $f['mrp'] ?? 0,
                    $f['unit_price'],
                    $f['qty'],
                    $f['total_price'],
                    $f['bill_no'],
                    $f['bill_date'],
                    $f['supplier_name'] ?: '-',
                    strtoupper($f['inward_status'] ?? 'PENDING')
                ]);
            }
        }

        fclose($out);
        exit;
    }
}
