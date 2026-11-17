<?php
// app/controllers/SalesController.php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;
use App\Core\Auth;
use App\Services\StockService;
use App\Services\NumberSeries;
use App\Services\CashService;
use Exception;

class SalesController extends Controller {
    // 1. CUSTOMERS & CRM DIRECTORY
    public function customers(): void {
        $cid = Auth::concernId();
        if (!$cid && Auth::isSuperAdmin()) {
            $cid = Auth::superAdminSelectedConcernId();
        }

        $sql = "SELECT c.*, con.name AS concern_name, con.code AS concern_code,
                       COALESCE(inv_stats.orders_count, 0) AS orders_count,
                       COALESCE(inv_stats.total_spend, 0.00) AS total_spend,
                       COALESCE(inv_stats.total_pcs, 0) AS total_pcs,
                       inv_stats.last_order_date,
                       inv_stats.last_invoice_no
                FROM customers c
                LEFT JOIN concerns con ON c.concern_id = con.id
                LEFT JOIN (
                    SELECT 
                        si.customer_id,
                        COUNT(DISTINCT si.id) AS orders_count,
                        SUM(si.net_amount) AS total_spend,
                        COALESCE(SUM(sii.qty), 0) AS total_pcs,
                        MAX(si.invoice_date) AS last_order_date,
                        SUBSTRING_INDEX(GROUP_CONCAT(si.invoice_no ORDER BY si.id DESC SEPARATOR '|||'), '|||', 1) AS last_invoice_no
                    FROM sales_invoices si
                    LEFT JOIN sales_invoice_items sii ON sii.sales_invoice_id = si.id
                    GROUP BY si.customer_id
                ) inv_stats ON inv_stats.customer_id = c.id";
        $params = [];
        if ($cid) {
            $sql .= " WHERE c.concern_id = ?";
            $params[] = $cid;
        }
        $sql .= " ORDER BY c.id DESC";
        $customers = DB::fetchAll($sql, $params);

        // Aggregate CRM KPIs
        $totalCustomers = count($customers);
        $activeCustomers = 0;
        $inactiveCustomers = 0;
        $totalCustomerRevenue = 0.00;
        $payingCustomers = 0;
        $repeatBuyers = 0;
        $cities = [];

        foreach ($customers as $c) {
            if (($c['status'] ?? 'active') === 'active') {
                $activeCustomers++;
            } else {
                $inactiveCustomers++;
            }

            $spend = (float)($c['total_spend'] ?? 0);
            $orders = (int)($c['orders_count'] ?? 0);
            $totalCustomerRevenue += $spend;

            if ($orders > 0) {
                $payingCustomers++;
            }
            if ($orders >= 2) {
                $repeatBuyers++;
            }

            $city = trim($c['city'] ?? '');
            if (!empty($city) && !in_array($city, $cities)) {
                $cities[] = $city;
            }
        }
        sort($cities);

        $avgCustomerLtv = $payingCustomers > 0 ? ($totalCustomerRevenue / $payingCustomers) : 0.00;
        $repeatCustomerRate = $payingCustomers > 0 ? round(($repeatBuyers / $payingCustomers) * 100, 0) : 0;

        $this->render('concern/customers', [
            'pageTitle'            => 'Customer Directory & CRM',
            'currentModule'        => 'customers',
            'customers'            => $customers,
            'selectedCid'          => $cid,
            'totalCustomers'       => $totalCustomers,
            'activeCustomers'      => $activeCustomers,
            'inactiveCustomers'    => $inactiveCustomers,
            'totalCustomerRevenue' => $totalCustomerRevenue,
            'avgCustomerLtv'       => $avgCustomerLtv,
            'repeatCustomerRate'   => $repeatCustomerRate,
            'repeatBuyers'         => $repeatBuyers,
            'cities'               => $cities,
        ]);
    }

    public function storeCustomer(): void {
        $this->validateCsrf();
        $cid = Auth::concernId() ?: (int)($_POST['concern_id'] ?? 0);
        if (!$cid && Auth::isSuperAdmin()) {
            $cid = Auth::superAdminSelectedConcernId();
        }
        if (!$cid) {
            $cid = (int)DB::fetchValue("SELECT id FROM concerns WHERE status = 'active' ORDER BY id ASC LIMIT 1") ?: 1;
        }

        $name    = trim($_POST['name'] ?? '');
        $phone   = trim($_POST['phone'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $gstin   = trim($_POST['gstin'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $city    = trim($_POST['city'] ?? '');

        if (empty($name)) {
            $this->flash('error', 'Customer name is required.');
            $this->redirect('/m/customers');
        }

        $code = 'CUST-' . str_pad((string)(DB::fetchValue("SELECT COUNT(*) FROM customers WHERE concern_id = ?", [$cid]) + 1), 3, '0', STR_PAD_LEFT);

        DB::query(
            "INSERT INTO customers (concern_id, code, name, phone, email, gstin, address, city, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())",
            [$cid, $code, $name, $phone, $email, $gstin, $address, $city]
        );

        $this->flash('success', "Customer '{$name}' created with code {$code}.");
        $this->redirect('/m/customers');
    }

    public function updateCustomer(): void {
        $this->validateCsrf();
        $id = (int)($_POST['id'] ?? 0);
        $cid = Auth::concernId() ?: (int)($_POST['concern_id'] ?? 0);

        if (!$id) {
            $this->flash('error', 'Customer not found.');
            $this->redirect('/m/customers');
        }

        $name    = trim($_POST['name'] ?? '');
        $phone   = trim($_POST['phone'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $gstin   = trim($_POST['gstin'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $city    = trim($_POST['city'] ?? '');
        $status  = $_POST['status'] ?? 'active';

        if (empty($name)) {
            $this->flash('error', 'Customer name is required.');
            $this->redirect('/m/customers');
        }

        $sql = "UPDATE customers SET name = ?, phone = ?, email = ?, gstin = ?, address = ?, city = ?, status = ? WHERE id = ?";
        $params = [$name, $phone, $email, $gstin, $address, $city, $status, $id];
        if ($cid) {
            $sql .= " AND concern_id = ?";
            $params[] = $cid;
        }

        DB::query($sql, $params);
        $this->flash('success', "Customer '{$name}' updated successfully.");
        $this->redirect('/m/customers');
    }

    public function customerProfile(): void {
        $id = (int)($_GET['id'] ?? 0);
        $cid = Auth::concernId();

        $sql = "SELECT c.*, con.name AS concern_name 
                FROM customers c 
                LEFT JOIN concerns con ON c.concern_id = con.id 
                WHERE c.id = ?";
        $params = [$id];
        if ($cid) {
            $sql .= " AND c.concern_id = ?";
            $params[] = $cid;
        }
        $customer = DB::fetch($sql, $params);
        if (!$customer) {
            $this->renderJson(['success' => false, 'message' => 'Customer not found.'], 404);
            return;
        }

        $invoices = DB::fetchAll(
            "SELECT si.id, si.invoice_no, si.invoice_date, si.net_amount, si.payment_method, si.payment_status,
                    (SELECT COUNT(*) FROM sales_invoice_items sii WHERE sii.sales_invoice_id = si.id) AS items_count,
                    (SELECT COALESCE(SUM(sii.qty), 0) FROM sales_invoice_items sii WHERE sii.sales_invoice_id = si.id) AS pcs_count
             FROM sales_invoices si
             WHERE si.customer_id = ?
             ORDER BY si.id DESC",
            [$id]
        );

        $totalSpend = array_sum(array_column($invoices, 'net_amount'));
        $ordersCount = count($invoices);
        $totalPcs = array_sum(array_column($invoices, 'pcs_count'));

        $this->renderJson([
            'success'     => true,
            'customer'    => $customer,
            'orders_count'=> $ordersCount,
            'total_spend' => $totalSpend,
            'total_pcs'   => $totalPcs,
            'invoices'    => $invoices,
        ]);
    }

    public function exportCustomers(): void {
        $cid = Auth::concernId();
        if (!$cid && Auth::isSuperAdmin()) {
            $cid = Auth::superAdminSelectedConcernId();
        }

        $format     = strtolower(trim($_GET['format'] ?? 'csv'));
        $period     = trim($_GET['period'] ?? 'all');
        $singleDate = trim($_GET['single_date'] ?? '');
        $fromDate   = trim($_GET['from_date'] ?? '');
        $toDate     = trim($_GET['to_date'] ?? '');
        $filterCity = trim($_GET['city'] ?? '');
        $scope      = trim($_GET['scope'] ?? 'all');

        $range = \App\Services\ExportService::resolveDateRange($period, $singleDate, $fromDate, $toDate);

        // Concern display details
        $concernName = 'All Concerns (Consolidated)';
        if ($cid) {
            $con = DB::fetch("SELECT name FROM concerns WHERE id = ?", [$cid]);
            if ($con) $concernName = $con['name'];
        }

        // Build SQL with date filter
        $subWhere = "1=1";
        $subParams = [];
        if (!empty($range['start_date']) && !empty($range['end_date'])) {
            $subWhere .= " AND si.invoice_date >= ? AND si.invoice_date <= ?";
            $subParams[] = $range['start_date'];
            $subParams[] = $range['end_date'];
        }

        $sql = "SELECT c.id, c.code, c.name, c.phone, c.email, c.gstin, c.city, c.address, c.status,
                       COALESCE(inv_stats.orders_count, 0) AS orders_count,
                       COALESCE(inv_stats.total_spend, 0.00) AS total_spend,
                       COALESCE(inv_stats.total_pcs, 0) AS total_pcs,
                       inv_stats.last_order_date,
                       inv_stats.last_invoice_no
                FROM customers c
                LEFT JOIN (
                    SELECT 
                        si.customer_id,
                        COUNT(DISTINCT si.id) AS orders_count,
                        SUM(si.net_amount) AS total_spend,
                        COALESCE(SUM(sii.qty), 0) AS total_pcs,
                        MAX(si.invoice_date) AS last_order_date,
                        SUBSTRING_INDEX(GROUP_CONCAT(si.invoice_no ORDER BY si.id DESC SEPARATOR '|||'), '|||', 1) AS last_invoice_no
                    FROM sales_invoices si
                    LEFT JOIN sales_invoice_items sii ON sii.sales_invoice_id = si.id
                    WHERE {$subWhere}
                    GROUP BY si.customer_id
                ) inv_stats ON inv_stats.customer_id = c.id
                WHERE 1=1";
        $params = $subParams;

        if ($cid) {
            $sql .= " AND c.concern_id = ?";
            $params[] = $cid;
        }
        if (!empty($filterCity)) {
            $sql .= " AND c.city = ?";
            $params[] = $filterCity;
        }
        if ($scope === 'orders_only') {
            $sql .= " AND inv_stats.orders_count > 0";
        }
        $sql .= " ORDER BY c.id DESC";

        $rows = DB::fetchAll($sql, $params);

        $headers = [
            '#', 'Customer Code', 'Customer Name', 'Phone', 'City', 'GSTIN',
            'Orders in Period', 'Pieces Bought', 'Total Spend (₹)', 'Avg / Order (₹)', 'Last Order Date', 'Last Invoice #'
        ];

        $exportData = [];
        $totalOrders = 0;
        $totalPcs = 0;
        $grandSpend = 0.00;
        $sl = 1;

        foreach ($rows as $r) {
            $orders = (int)$r['orders_count'];
            $spend = (float)$r['total_spend'];
            $pcs = (int)$r['total_pcs'];
            $avg = $orders > 0 ? round($spend / $orders, 2) : 0.00;

            $totalOrders += $orders;
            $totalPcs += $pcs;
            $grandSpend += $spend;

            $exportData[] = [
                $sl++,
                $r['code'] ?: 'CUST-' . $r['id'],
                $r['name'],
                $r['phone'] ?: '-',
                $r['city'] ?: ($r['address'] ?: '-'),
                $r['gstin'] ?: 'Unregistered',
                $orders,
                $pcs . ' pcs',
                '₹' . number_format($spend, 2),
                '₹' . number_format($avg, 2),
                $r['last_order_date'] ? date('d M Y', strtotime($r['last_order_date'])) : '-',
                $r['last_invoice_no'] ?: '-'
            ];
        }

        $overallAvg = $totalOrders > 0 ? round($grandSpend / $totalOrders, 2) : 0.00;
        $totalsRow = [
            'TOTALS', '', count($exportData) . ' Customers', '', '', '',
            $totalOrders, $totalPcs . ' pcs', '₹' . number_format($grandSpend, 2), '₹' . number_format($overallAvg, 2), '', ''
        ];

        $metadata = [
            'Concern / Outlet' => $concernName,
            'Period Scope'    => $range['label'],
            'City Filter'     => !empty($filterCity) ? $filterCity : 'All Cities',
            'Customers Found' => count($exportData) . ' records',
        ];

        $cleanPeriodName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $range['period']);
        $filename = 'Customer_Directory_' . $cleanPeriodName . '_' . date('Ymd_His');

        if ($format === 'excel' || $format === 'xls' || $format === 'xlsx') {
            \App\Services\ExportService::downloadExcel($filename, 'Customer Directory & CRM Performance Report', $metadata, $headers, $exportData, $totalsRow);
        } elseif ($format === 'pdf') {
            \App\Services\ExportService::renderPdfView('Customer Directory & CRM Performance Report', $metadata, $headers, $exportData, $totalsRow);
        } else {
            \App\Services\ExportService::downloadCsv($filename, $headers, $exportData, $totalsRow);
        }
    }

    // 2. REQUEST
    public function request(): void {
        $cid = Auth::concernId();

        $sql = "SELECT cr.*, c.name AS customer_name, v.name AS variant_name, v.sku_code,
                       con.name AS concern_name, con.code AS concern_code,
                       COALESCE(stk.total_qty, 0) AS current_stock
                FROM customer_requests cr
                JOIN customers c ON cr.customer_id = c.id
                JOIN variants v ON cr.variant_id = v.id
                LEFT JOIN concerns con ON cr.concern_id = con.id
                LEFT JOIN (
                    SELECT variant_id, SUM(qty) AS total_qty FROM stocks GROUP BY variant_id
                ) stk ON v.id = stk.variant_id";
        $params = [];
        if ($cid) {
            $sql .= " WHERE cr.concern_id = ?";
            $params[] = $cid;
        }
        $sql .= " ORDER BY cr.id DESC";
        $requests = DB::fetchAll($sql, $params);

        if ($cid) {
            $customers = DB::fetchAll("SELECT id, name FROM customers WHERE concern_id = ? AND status = 'active'", [$cid]);
            $variants  = DB::fetchAll("SELECT id, sku_code, name, size FROM variants WHERE concern_id = ? AND status = 'active'", [$cid]);
        } else {
            $customers = DB::fetchAll("SELECT id, name FROM customers WHERE status = 'active'");
            $variants  = DB::fetchAll("SELECT id, sku_code, name, size FROM variants WHERE status = 'active'");
        }

        $this->render('concern/request', [
            'pageTitle'     => 'Customer Product Requests',
            'currentModule' => 'request',
            'requests'      => $requests,
            'customers'     => $customers,
            'variants'      => $variants,
            'selectedCid'   => $cid,
        ]);
    }

    public function storeRequest(): void {
        $this->validateCsrf();
        $cid = Auth::concernId() ?: (int)($_POST['concern_id'] ?? 0);

        if (!$cid) {
            $this->flash('error', 'Please choose a concern.');
            $this->redirect('/m/request');
        }

        $customerId   = (int)($_POST['customer_id'] ?? 0);
        $variantId    = (int)($_POST['variant_id'] ?? 0);
        $requestedQty = (float)($_POST['requested_qty'] ?? 0);
        $expectedDate = $_POST['expected_date'] ?: date('Y-m-d', strtotime('+7 days'));
        $notes        = trim($_POST['notes'] ?? '');

        if (!$customerId || !$variantId || $requestedQty <= 0) {
            $this->flash('error', 'Customer, Variant and Requested Quantity are required.');
            $this->redirect('/m/request');
        }

        $reqNo = NumberSeries::getNext($cid, 'REQ');

        DB::query(
            "INSERT INTO customer_requests (concern_id, request_no, customer_id, variant_id, requested_qty, expected_date, status, notes, created_at)
             VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, NOW())",
            [$cid, $reqNo, $customerId, $variantId, $requestedQty, $expectedDate, $notes]
        );

        $this->flash('success', "Order Request '{$reqNo}' registered successfully.");
        $this->redirect('/m/request');
    }

    // 3. BATCH
    public function batch(): void {
        $cid = Auth::concernId();

        $sql = "SELECT b.*, v.name AS variant_name, v.sku_code, v.size, v.weight_unit,
                       con.name AS concern_name, con.code AS concern_code
                FROM batches b
                JOIN variants v ON b.variant_id = v.id
                LEFT JOIN concerns con ON b.concern_id = con.id";
        $params = [];
        if ($cid) {
            $sql .= " WHERE b.concern_id = ?";
            $params[] = $cid;
        }
        $sql .= " ORDER BY b.id DESC";
        $batches = DB::fetchAll($sql, $params);

        if ($cid) {
            $variants = DB::fetchAll("SELECT id, sku_code, name, size FROM variants WHERE concern_id = ? AND status = 'active'", [$cid]);
        } else {
            $variants = DB::fetchAll("SELECT id, sku_code, name, size FROM variants WHERE status = 'active'");
        }

        $this->render('concern/batch', [
            'pageTitle'     => 'Batch Allocation & Lot Tracking',
            'currentModule' => 'batch',
            'batches'       => $batches,
            'variants'      => $variants,
            'selectedCid'   => $cid,
        ]);
    }

    public function storeBatch(): void {
        $this->validateCsrf();
        $cid = Auth::concernId() ?: (int)($_POST['concern_id'] ?? 0);

        if (!$cid) {
            $this->flash('error', 'Please choose a concern.');
            $this->redirect('/m/batch');
        }

        $variantId = (int)($_POST['variant_id'] ?? 0);
        $qty       = (float)($_POST['batch_qty'] ?? 0);
        $mfgDate   = $_POST['mfg_date'] ?: date('Y-m-d');
        $expDate   = !empty($_POST['exp_date']) ? $_POST['exp_date'] : null;

        if (!$variantId || $qty <= 0) {
            $this->flash('error', 'Variant and Batch Quantity are required.');
            $this->redirect('/m/batch');
        }

        $batchNo = NumberSeries::getNext($cid, 'BATCH');

        DB::query(
            "INSERT INTO batches (concern_id, batch_no, variant_id, batch_qty, mfg_date, exp_date, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())",
            [$cid, $batchNo, $variantId, $qty, $mfgDate, $expDate]
        );

        $this->flash('success', "Batch lot '{$batchNo}' initialized.");
        $this->redirect('/m/batch');
    }

    // 4. POS BILLING TERMINAL (Shop Module)
    public function billing(): void {
        $cid = Auth::concernId();
        if (!$cid && Auth::isSuperAdmin()) {
            $cid = Auth::superAdminSelectedConcernId();
        }

        // Concern display details
        $concernName = 'GRM Retail Flagship Store';
        if ($cid) {
            $con = DB::fetch("SELECT name, code FROM concerns WHERE id = ?", [$cid]);
            if ($con) $concernName = $con['name'];
        } elseif (Auth::isSuperAdmin()) {
            $con = DB::fetch("SELECT name, code FROM concerns WHERE status = 'active' ORDER BY id ASC LIMIT 1");
            if ($con) $concernName = $con['name'];
        }
        $staffName = Auth::user()['name'] ?? 'System Administrator';

        // Load Sales Invoices for History / Print
        $sql = "SELECT si.*, c.name AS customer_name, c.phone AS customer_phone, con.name AS concern_name, con.code AS concern_code,
                       (SELECT COUNT(*) FROM sales_invoice_items sii WHERE sii.sales_invoice_id = si.id) AS items_count
                FROM sales_invoices si
                JOIN customers c ON si.customer_id = c.id
                LEFT JOIN concerns con ON si.concern_id = con.id";
        $params = [];
        if ($cid) {
            $sql .= " WHERE si.concern_id = ?";
            $params[] = $cid;
        }
        $sql .= " ORDER BY si.id DESC LIMIT 50";
        $invoices = DB::fetchAll($sql, $params);

        if ($cid) {
            $customers = DB::fetchAll("SELECT id, name, phone, address, city FROM customers WHERE concern_id = ? AND status = 'active' ORDER BY id DESC", [$cid]);
            $variants  = DB::fetchAll(
                "SELECT v.id, v.sku_code, v.name, v.price, v.mrp, v.size, v.attributes, COALESCE(s.qty, 0) as available_qty
                 FROM variants v
                 LEFT JOIN stocks s ON v.id = s.variant_id AND s.concern_id = ?
                 WHERE v.concern_id = ? AND v.status = 'active'
                 ORDER BY v.name ASC",
                [$cid, $cid]
            );
        } else {
            $customers = DB::fetchAll("SELECT id, name, phone, address, city FROM customers WHERE status = 'active' ORDER BY id DESC LIMIT 50");
            $variants  = DB::fetchAll(
                "SELECT v.id, v.sku_code, v.name, v.price, v.mrp, v.size, v.attributes, COALESCE(s.qty, 0) as available_qty
                 FROM variants v
                 LEFT JOIN (SELECT variant_id, SUM(qty) as qty FROM stocks GROUP BY variant_id) s ON v.id = s.variant_id
                 WHERE v.status = 'active'
                 ORDER BY v.name ASC"
            );
        }

        // Print preview if requested
        $printInvoice = null;
        $printItems = [];
        $printId = !empty($_GET['print_id']) ? (int)$_GET['print_id'] : 0;
        if ($printId > 0) {
            $printInvoice = DB::fetch(
                "SELECT si.*, c.name AS customer_name, c.phone AS customer_phone, c.address AS customer_address, 
                        con.name AS concern_name, con.gstin AS concern_gstin, con.address AS concern_address, con.phone AS concern_phone
                 FROM sales_invoices si
                 JOIN customers c ON si.customer_id = c.id
                 LEFT JOIN concerns con ON si.concern_id = con.id
                 WHERE si.id = ?",
                [$printId]
            );
            if ($printInvoice) {
                $printItems = DB::fetchAll(
                    "SELECT sii.*, v.sku_code, v.name AS item_name, v.size
                     FROM sales_invoice_items sii
                     JOIN variants v ON sii.variant_id = v.id
                     WHERE sii.sales_invoice_id = ?",
                    [$printId]
                );
            }
        }

        $this->render('concern/billing', [
            'pageTitle'     => 'POS Billing Terminal',
            'currentModule' => 'billing',
            'concernName'   => $concernName,
            'staffName'     => $staffName,
            'invoices'      => $invoices,
            'customers'     => $customers,
            'variants'      => $variants,
            'selectedCid'   => $cid,
            'printInvoice'  => $printInvoice,
            'printItems'    => $printItems,
        ]);
    }

    public function variantLookup(): void {
        $query = trim($_GET['q'] ?? '');
        if (empty($query)) {
            $this->renderJson(['success' => false, 'message' => 'Empty search query.'], 400);
            return;
        }

        $cid = Auth::concernId() ?: Auth::superAdminSelectedConcernId();
        if (!$cid && Auth::isSuperAdmin()) {
            $cid = (int)DB::fetchValue("SELECT id FROM concerns WHERE status = 'active' ORDER BY id ASC LIMIT 1") ?: 1;
        }

        $variantId = null;
        // Check if QR format like C1-S1-V5
        if (preg_match('/V(\d+)$/i', $query, $matches)) {
            $variantId = (int)$matches[1];
        }

        $sql = "SELECT v.id, v.sku_code, v.name, v.price, v.mrp, v.size, v.attributes, COALESCE(s.qty, 0) AS available_qty
                FROM variants v
                LEFT JOIN stocks s ON v.id = s.variant_id AND s.concern_id = ?
                WHERE v.concern_id = ? AND v.status = 'active'";
        $params = [$cid, $cid];

        if ($variantId) {
            $sql .= " AND v.id = ?";
            $params[] = $variantId;
        } else {
            $sql .= " AND (v.sku_code = ? OR v.name = ? OR v.sku_code LIKE ? OR v.name LIKE ?)";
            $params[] = $query;
            $params[] = $query;
            $params[] = "%{$query}%";
            $params[] = "%{$query}%";
        }
        $sql .= " LIMIT 1";

        $variant = DB::fetch($sql, $params);
        if (!$variant) {
            $this->renderJson(['success' => false, 'message' => "Item '{$query}' not found."], 404);
            return;
        }

        $attrs = json_decode($variant['attributes'] ?? '{}', true) ?: [];
        $hsnCode = $attrs['hsn'] ?? '6109'; // Standard garment HSN code

        $this->renderJson([
            'success' => true,
            'variant' => [
                'id'            => (int)$variant['id'],
                'sku_code'      => $variant['sku_code'],
                'name'          => $variant['name'],
                'size'          => $variant['size'] ?: '-',
                'price'         => (float)$variant['price'],
                'mrp'           => (float)($variant['mrp'] > 0 ? $variant['mrp'] : $variant['price']),
                'available_qty' => (float)$variant['available_qty'],
                'hsn_code'      => $hsnCode,
                'gst_pct'       => 5.0,
            ]
        ]);
    }

    public function customerLookup(): void {
        $phone = trim($_GET['phone'] ?? '');
        if (empty($phone)) {
            $this->renderJson(['found' => false]);
            return;
        }

        $cid = Auth::concernId() ?: Auth::superAdminSelectedConcernId();
        $sql = "SELECT id, name, phone, address, city FROM customers WHERE phone = ?";
        $params = [$phone];
        if ($cid) {
            $sql .= " AND concern_id = ?";
            $params[] = $cid;
        }
        $sql .= " LIMIT 1";

        $cust = DB::fetch($sql, $params);
        if ($cust) {
            $this->renderJson(['found' => true, 'customer' => $cust]);
        } else {
            $this->renderJson(['found' => false]);
        }
    }

    public function completePosSale(): void {
        $this->validateCsrf();
        $cid = (int)($_POST['concern_id'] ?? 0) ?: Auth::concernId();
        if (!$cid && Auth::isSuperAdmin()) {
            $cid = Auth::superAdminSelectedConcernId();
        }
        if (!$cid) {
            $cid = (int)DB::fetchValue("SELECT id FROM concerns WHERE status = 'active' ORDER BY id ASC LIMIT 1") ?: 1;
        }

        $custMobile  = trim($_POST['customer_mobile'] ?? '');
        $custName    = trim($_POST['customer_name'] ?? 'Walk-in Customer');
        $custAddress = trim($_POST['customer_address'] ?? 'Counter Walk-in / Local Store');
        $gstType     = $_POST['gst_type'] ?? 'both'; // 'both', 'igst', 'exempt'
        $payMethod   = $_POST['payment_method'] ?? 'cash';
        $isOnline    = !empty($_POST['is_online']) ? 1 : 0;

        $cartItemsJson = $_POST['cart_items_json'] ?? '[]';
        $items = json_decode($cartItemsJson, true);

        if (empty($items) || !is_array($items)) {
            $this->flash('error', 'Cannot complete sale: Billing cart is empty.');
            $this->redirect('/m/billing');
        }

        DB::beginTransaction();
        try {
            // 1. Resolve or create Customer
            $customerId = null;
            if (!empty($custMobile)) {
                $existingCust = DB::fetch(
                    "SELECT id FROM customers WHERE concern_id = ? AND phone = ? LIMIT 1",
                    [$cid, $custMobile]
                );
                if ($existingCust) {
                    $customerId = (int)$existingCust['id'];
                }
            }
            if (!$customerId) {
                $custCode = 'CUST-' . rand(10000, 99999);
                DB::query(
                    "INSERT INTO customers (concern_id, code, name, phone, address, city, status, created_at)
                     VALUES (?, ?, ?, ?, ?, 'Retail Store', 'active', NOW())",
                    [$cid, $custCode, $custName, $custMobile, $custAddress]
                );
                $customerId = (int)DB::lastInsertId();
            }

            // 2. Calculate Totals
            $taxableSubtotal = 0.00;
            $taxTotal = 0.00;
            $discountTotal = 0.00;

            foreach ($items as $item) {
                $qty = max(1, (float)($item['qty'] ?? 1));
                $unitPrice = (float)($item['price'] ?? 0);
                $itemDiscount = (float)($item['discount'] ?? 0);
                $gstPct = $gstType === 'exempt' ? 0.00 : (float)($item['gst_pct'] ?? 5.0);

                $itemSubtotal = ($unitPrice * $qty) - $itemDiscount;
                if ($itemSubtotal < 0) $itemSubtotal = 0;

                $itemGst = ($itemSubtotal * $gstPct) / 100.0;

                $taxableSubtotal += $itemSubtotal;
                $taxTotal += $itemGst;
                $discountTotal += $itemDiscount;
            }

            $netPayable = round($taxableSubtotal + $taxTotal, 2);

            // 3. Generate Sequential Invoice Number
            $invoiceNo = NumberSeries::getNext($cid, 'INVOICE');
            $invDate = date('Y-m-d');
            $notes = "POS Terminal Sale. GST Type: {$gstType}" . ($isOnline ? " [Online Channel]" : "");

            DB::query(
                "INSERT INTO sales_invoices (concern_id, invoice_no, customer_id, invoice_date, total_amount, tax_amount, discount_amount, net_amount, payment_method, payment_status, notes, created_by, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'paid', ?, ?, NOW())",
                [$cid, $invoiceNo, $customerId, $invDate, $taxableSubtotal, $taxTotal, $discountTotal, $netPayable, $payMethod, $notes, Auth::id()]
            );
            $invoiceId = (int)DB::lastInsertId();

            // 4. Record items & atomically deduct stock via single stock gateway
            foreach ($items as $item) {
                $varId = (int)$item['id'];
                $qty = max(1, (float)($item['qty'] ?? 1));
                $unitPrice = (float)($item['price'] ?? 0);
                $itemTotal = ($unitPrice * $qty) - (float)($item['discount'] ?? 0);

                DB::query(
                    "INSERT INTO sales_invoice_items (concern_id, sales_invoice_id, variant_id, qty, unit_price, total_price)
                     VALUES (?, ?, ?, ?, ?, ?)",
                    [$cid, $invoiceId, $varId, $qty, $unitPrice, $itemTotal]
                );

                // Deduct stock via StockService::move
                StockService::move(
                    $cid,
                    $varId,
                    'Main Store',
                    0.00,
                    $qty,
                    'Billing',
                    $invoiceId,
                    "POS Sale #{$invoiceNo}",
                    Auth::id()
                );
            }

            // 5. Post to Cash Accounts / Cash Flow Ledger
            if ($netPayable > 0 && in_array($payMethod, ['cash', 'upi', 'card', 'split'])) {
                $cashAccount = DB::fetch(
                    "SELECT id FROM cash_accounts WHERE concern_id = ? AND account_type IN ('cash', 'petty_cash') ORDER BY id ASC LIMIT 1",
                    [$cid]
                ) ?: DB::fetch("SELECT id FROM cash_accounts WHERE concern_id = ? ORDER BY id ASC LIMIT 1", [$cid]);

                if ($cashAccount) {
                    CashService::postTransaction(
                        $cid,
                        (int)$cashAccount['id'],
                        $invDate,
                        'credit',
                        $netPayable,
                        'Billing',
                        $invoiceId,
                        "POS Sale Invoice #{$invoiceNo} [{$payMethod}]",
                        Auth::id()
                    );
                }
            }

            // 6. Audit Trail
            AuditLog::log('POS_SALE', 'sales_invoices', $invoiceId, null, [
                'invoice_no'  => $invoiceNo,
                'net_payable' => $netPayable,
                'items_count' => count($items),
                'cashier'     => Auth::user()['name'] ?? 'Staff',
            ]);

            DB::commit();

            $this->flash('success', "Sale Completed! Invoice #{$invoiceNo} generated for ₹" . number_format($netPayable, 2) . ".");
            $this->redirect("/m/billing?print_id={$invoiceId}");
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->flash('error', "POS Transaction failed: " . $e->getMessage());
            $this->redirect('/m/billing');
        }
    }

    public function storeInvoice(): void {
        $this->completePosSale();
    }

    // 5. OUTWARD
    public function outward(): void {
        $cid = Auth::concernId();

        $sql = "SELECT o.*, c.name AS customer_name, si.invoice_no, con.name AS concern_name, con.code AS concern_code,
                       (SELECT COUNT(*) FROM outward_items oi WHERE oi.outward_id = o.id) AS items_count,
                       (SELECT COALESCE(SUM(qty), 0) FROM outward_items oi WHERE oi.outward_id = o.id) AS total_qty
                FROM outwards o
                JOIN customers c ON o.customer_id = c.id
                LEFT JOIN sales_invoices si ON o.sales_invoice_id = si.id
                LEFT JOIN concerns con ON o.concern_id = con.id";
        $params = [];
        if ($cid) {
            $sql .= " WHERE o.concern_id = ?";
            $params[] = $cid;
        }
        $sql .= " ORDER BY o.id DESC";
        $outwards = DB::fetchAll($sql, $params);

        if ($cid) {
            $customers = DB::fetchAll("SELECT id, name FROM customers WHERE concern_id = ? AND status = 'active'", [$cid]);
            $invoices  = DB::fetchAll("SELECT id, invoice_no FROM sales_invoices WHERE concern_id = ?", [$cid]);
            $variants  = DB::fetchAll(
                "SELECT v.id, v.sku_code, v.name, v.size, COALESCE(s.qty, 0) AS stock_qty
                 FROM variants v
                 LEFT JOIN stocks s ON v.id = s.variant_id AND s.concern_id = ?
                 WHERE v.concern_id = ? AND v.status = 'active'",
                [$cid, $cid]
            );
        } else {
            $customers = DB::fetchAll("SELECT id, name FROM customers WHERE status = 'active'");
            $invoices  = DB::fetchAll("SELECT id, invoice_no FROM sales_invoices");
            $variants  = DB::fetchAll(
                "SELECT v.id, v.sku_code, v.name, v.size, COALESCE(s.qty, 0) AS stock_qty
                 FROM variants v
                 LEFT JOIN (SELECT variant_id, SUM(qty) as qty FROM stocks GROUP BY variant_id) s ON v.id = s.variant_id
                 WHERE v.status = 'active'"
            );
        }

        $this->render('concern/outward', [
            'pageTitle'     => 'Outward Gate Pass & Packing',
            'currentModule' => 'outward',
            'outwards'      => $outwards,
            'customers'     => $customers,
            'invoices'      => $invoices,
            'variants'      => $variants,
            'selectedCid'   => $cid,
        ]);
    }

    public function storeOutward(): void {
        $this->validateCsrf();
        $cid = Auth::concernId() ?: (int)($_POST['concern_id'] ?? 0);

        if (!$cid) {
            $this->flash('error', 'Please choose a concern.');
            $this->redirect('/m/outward');
        }

        $customerId = (int)($_POST['customer_id'] ?? 0);
        $invoiceId  = !empty($_POST['sales_invoice_id']) ? (int)$_POST['sales_invoice_id'] : null;
        $outwDate   = $_POST['outward_date'] ?: date('Y-m-d');
        $location   = trim($_POST['location'] ?? 'Main Store');
        $variantId  = (int)($_POST['variant_id'] ?? 0);
        $qty        = (float)($_POST['qty'] ?? 0);

        if (!$customerId || !$variantId || $qty <= 0) {
            $this->flash('error', 'Customer, Variant and valid Quantity are required.');
            $this->redirect('/m/outward');
        }

        DB::beginTransaction();
        try {
            $outwardNo = NumberSeries::getNext($cid, 'OUTWARD');

            DB::query(
                "INSERT INTO outwards (concern_id, outward_no, sales_invoice_id, customer_id, outward_date, location, status, created_by, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, 'prepared', ?, NOW())",
                [$cid, $outwardNo, $invoiceId, $customerId, $outwDate, $location, Auth::id()]
            );
            $outwardId = (int)DB::lastInsertId();

            DB::query(
                "INSERT INTO outward_items (concern_id, outward_id, variant_id, qty) VALUES (?, ?, ?, ?)",
                [$cid, $outwardId, $variantId, $qty]
            );

            // Single stock gateway: Outward deducts stock!
            StockService::move(
                $cid,
                $variantId,
                $location,
                0.00,
                $qty,
                'Outward',
                $outwardId,
                "Outward dispatch gate-pass {$outwardNo}",
                Auth::id()
            );

            DB::commit();

            $this->flash('success', "Outward consignment '{$outwardNo}' created and {$qty} units deducted from live stock.");
        } catch (Exception $e) {
            DB::rollBack();
            $this->flash('error', 'Failed to generate outward: ' . $e->getMessage());
        }

        $this->redirect('/m/outward');
    }

    // 6. DISPATCH
    public function dispatch(): void {
        $cid = Auth::concernId();

        $sql = "SELECT d.*, o.outward_no, c.name AS customer_name, con.name AS concern_name, con.code AS concern_code
                FROM dispatches d
                JOIN outwards o ON d.outward_id = o.id
                JOIN customers c ON o.customer_id = c.id
                LEFT JOIN concerns con ON d.concern_id = con.id";
        $params = [];
        if ($cid) {
            $sql .= " WHERE d.concern_id = ?";
            $params[] = $cid;
        }
        $sql .= " ORDER BY d.id DESC";
        $dispatches = DB::fetchAll($sql, $params);

        if ($cid) {
            $outwards = DB::fetchAll("SELECT id, outward_no FROM outwards WHERE concern_id = ? AND status = 'prepared'", [$cid]);
        } else {
            $outwards = DB::fetchAll("SELECT id, outward_no FROM outwards WHERE status = 'prepared'");
        }

        $this->render('concern/dispatch', [
            'pageTitle'     => 'Dispatch & Logistics Tracking',
            'currentModule' => 'dispatch',
            'dispatches'    => $dispatches,
            'outwards'      => $outwards,
            'selectedCid'   => $cid,
        ]);
    }

    public function storeDispatch(): void {
        $this->validateCsrf();
        $cid = Auth::concernId() ?: (int)($_POST['concern_id'] ?? 0);

        $outwardId    = (int)($_POST['outward_id'] ?? 0);
        $transporter  = trim($_POST['transporter_name'] ?? '');
        $vehicleNo    = trim($_POST['vehicle_no'] ?? '');
        $trackingNo   = trim($_POST['tracking_no'] ?? '');
        $dispatchDate = $_POST['dispatch_date'] ?: date('Y-m-d');
        $notes        = trim($_POST['notes'] ?? '');

        if (!$outwardId) {
            $this->flash('error', 'Please select an outward gate pass.');
            $this->redirect('/m/dispatch');
        }

        // If cid not set, read from outward
        if (!$cid) {
            $cid = (int)DB::fetchValue("SELECT concern_id FROM outwards WHERE id = ?", [$outwardId]);
        }

        DB::beginTransaction();
        try {
            $dispNo = 'DSP-' . date('Ymd') . '-' . rand(100, 999);

            DB::query(
                "INSERT INTO dispatches (concern_id, dispatch_no, outward_id, transporter_name, vehicle_no, tracking_no, dispatch_date, status, notes, created_by, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'in_transit', ?, ?, NOW())",
                [$cid, $dispNo, $outwardId, $transporter, $vehicleNo, $trackingNo, $dispatchDate, $notes, Auth::id()]
            );

            // Update outward status to dispatched
            DB::query("UPDATE outwards SET status = 'dispatched' WHERE id = ? AND concern_id = ?", [$outwardId, $cid]);

            DB::commit();

            $this->flash('success', "Dispatch record '{$dispNo}' logged. Shipment is in transit.");
        } catch (Exception $e) {
            DB::rollBack();
            $this->flash('error', 'Failed to record dispatch: ' . $e->getMessage());
        }

        $this->redirect('/m/dispatch');
    }

    // 7. RETURN & EXCHANGE
    public function returnExchange(): void {
        $cid = Auth::concernId();

        $sql = "SELECT re.*, c.name AS customer_name, v1.name AS returned_variant_name, v1.sku_code AS returned_sku,
                       v2.name AS exchange_variant_name, v2.sku_code AS exchange_sku,
                       con.name AS concern_name, con.code AS concern_code
                FROM returns_exchanges re
                JOIN customers c ON re.customer_id = c.id
                JOIN variants v1 ON re.returned_variant_id = v1.id
                LEFT JOIN variants v2 ON re.exchange_variant_id = v2.id
                LEFT JOIN concerns con ON re.concern_id = con.id";
        $params = [];
        if ($cid) {
            $sql .= " WHERE re.concern_id = ?";
            $params[] = $cid;
        }
        $sql .= " ORDER BY re.id DESC";
        $returns = DB::fetchAll($sql, $params);

        if ($cid) {
            $customers = DB::fetchAll("SELECT id, name FROM customers WHERE concern_id = ? AND status = 'active'", [$cid]);
            $variants  = DB::fetchAll("SELECT id, sku_code, name, size FROM variants WHERE concern_id = ? AND status = 'active'", [$cid]);
        } else {
            $customers = DB::fetchAll("SELECT id, name FROM customers WHERE status = 'active'");
            $variants  = DB::fetchAll("SELECT id, sku_code, name, size FROM variants WHERE status = 'active'");
        }

        $this->render('concern/return_exchange', [
            'pageTitle'     => 'Customer Returns & Exchanges',
            'currentModule' => 'return_exchange',
            'returns'       => $returns,
            'customers'     => $customers,
            'variants'      => $variants,
            'selectedCid'   => $cid,
        ]);
    }

    public function storeReturn(): void {
        $this->validateCsrf();
        $cid = Auth::concernId() ?: (int)($_POST['concern_id'] ?? 0);

        if (!$cid) {
            $this->flash('error', 'Please choose a concern.');
            $this->redirect('/m/return_exchange');
        }

        $type        = $_POST['type'] ?? 'return';
        $customerId  = (int)($_POST['customer_id'] ?? 0);
        $returnDate  = $_POST['return_date'] ?: date('Y-m-d');
        $retVarId    = (int)($_POST['returned_variant_id'] ?? 0);
        $retQty      = (float)($_POST['returned_qty'] ?? 0);
        $excVarId    = !empty($_POST['exchange_variant_id']) ? (int)$_POST['exchange_variant_id'] : null;
        $excQty      = !empty($_POST['exchange_qty']) ? (float)$_POST['exchange_qty'] : null;
        $reason      = trim($_POST['reason'] ?? '');

        if (!$customerId || !$retVarId || $retQty <= 0) {
            $this->flash('error', 'Customer, Returned Variant, and Quantity are required.');
            $this->redirect('/m/return_exchange');
        }

        DB::beginTransaction();
        try {
            $returnNo = 'RET-' . date('Ymd') . '-' . rand(100, 999);

            DB::query(
                "INSERT INTO returns_exchanges (concern_id, return_no, type, customer_id, return_date, returned_variant_id, returned_qty, exchange_variant_id, exchange_qty, reason, created_by, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
                [$cid, $returnNo, $type, $customerId, $returnDate, $retVarId, $retQty, $excVarId, $excQty, $reason, Auth::id()]
            );
            $retId = (int)DB::lastInsertId();

            // Return re-enters stocks
            StockService::move(
                $cid,
                $retVarId,
                'Main Store',
                $retQty,
                0.00,
                'Return & Exchange',
                $retId,
                "Customer return: {$reason}",
                Auth::id()
            );

            // If exchange, deduct exchange variant stock
            if ($type === 'exchange' && $excVarId && $excQty > 0) {
                StockService::move(
                    $cid,
                    $excVarId,
                    'Main Store',
                    0.00,
                    $excQty,
                    'Return & Exchange',
                    $retId,
                    "Exchange delivery for return {$returnNo}",
                    Auth::id()
                );
            }

            DB::commit();

            $this->flash('success', "Return/Exchange record '{$returnNo}' processed and inventory balances synchronized.");
        } catch (Exception $e) {
            DB::rollBack();
            $this->flash('error', 'Failed to process return: ' . $e->getMessage());
        }

        $this->redirect('/m/return_exchange');
    }
}
