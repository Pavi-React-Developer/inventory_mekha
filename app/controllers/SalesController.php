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
    // 1. CUSTOMERS
    public function customers(): void {
        $cid = Auth::concernId();
        $customers = DB::fetchAll("SELECT * FROM customers WHERE concern_id = ? ORDER BY id DESC", [$cid]);

        $this->render('concern/customers', [
            'pageTitle'     => 'Customer Details & Directory',
            'currentModule' => 'customers',
            'customers'     => $customers,
        ]);
    }

    public function storeCustomer(): void {
        $this->validateCsrf();
        $cid = Auth::concernId();

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
            "INSERT INTO customers (concern_id, code, name, phone, email, gstin, address, city, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')",
            [$cid, $code, $name, $phone, $email, $gstin, $address, $city]
        );

        $this->flash('success', "Customer '{$name}' created with code {$code}.");
        $this->redirect('/m/customers');
    }

    // 2. REQUEST
    public function request(): void {
        $cid = Auth::concernId();

        $requests = DB::fetchAll(
            "SELECT cr.*, c.name AS customer_name, v.name AS variant_name, v.sku_code,
                    COALESCE(stk.total_qty, 0) AS current_stock
             FROM customer_requests cr
             JOIN customers c ON cr.customer_id = c.id
             JOIN variants v ON cr.variant_id = v.id
             LEFT JOIN (
                 SELECT variant_id, SUM(qty) AS total_qty FROM stocks WHERE concern_id = ? GROUP BY variant_id
             ) stk ON v.id = stk.variant_id
             WHERE cr.concern_id = ?
             ORDER BY cr.id DESC",
            [$cid, $cid]
        );

        $customers = DB::fetchAll("SELECT id, name FROM customers WHERE concern_id = ? AND status = 'active'", [$cid]);
        $variants  = DB::fetchAll("SELECT id, sku_code, name, size FROM variants WHERE concern_id = ? AND status = 'active'", [$cid]);

        $this->render('concern/request', [
            'pageTitle'     => 'Customer Product Requests',
            'currentModule' => 'request',
            'requests'      => $requests,
            'customers'     => $customers,
            'variants'      => $variants,
        ]);
    }

    public function storeRequest(): void {
        $this->validateCsrf();
        $cid = Auth::concernId();

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

        $batches = DB::fetchAll(
            "SELECT b.*, v.name AS variant_name, v.sku_code, v.size, v.weight_unit
             FROM batches b
             JOIN variants v ON b.variant_id = v.id
             WHERE b.concern_id = ?
             ORDER BY b.id DESC",
            [$cid]
        );

        $variants = DB::fetchAll("SELECT id, sku_code, name, size FROM variants WHERE concern_id = ? AND status = 'active'", [$cid]);

        $this->render('concern/batch', [
            'pageTitle'     => 'Batch Allocation & Lot Tracking',
            'currentModule' => 'batch',
            'batches'       => $batches,
            'variants'      => $variants,
        ]);
    }

    public function storeBatch(): void {
        $this->validateCsrf();
        $cid = Auth::concernId();

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

    // 4. BILLING (Sales Invoices)
    public function billing(): void {
        $cid = Auth::concernId();

        $invoices = DB::fetchAll(
            "SELECT si.*, c.name AS customer_name,
                    (SELECT COUNT(*) FROM sales_invoice_items sii WHERE sii.sales_invoice_id = si.id) AS items_count
             FROM sales_invoices si
             JOIN customers c ON si.customer_id = c.id
             WHERE si.concern_id = ?
             ORDER BY si.id DESC",
            [$cid]
        );

        $customers = DB::fetchAll("SELECT id, name FROM customers WHERE concern_id = ? AND status = 'active'", [$cid]);
        $variants  = DB::fetchAll(
            "SELECT v.id, v.sku_code, v.name, v.price, v.mrp, v.size, COALESCE(s.qty, 0) as available_qty
             FROM variants v
             LEFT JOIN stocks s ON v.id = s.variant_id AND s.concern_id = ?
             WHERE v.concern_id = ? AND v.status = 'active'",
            [$cid, $cid]
        );

        $this->render('concern/billing', [
            'pageTitle'     => 'Sales Billing & Tax Invoices',
            'currentModule' => 'billing',
            'invoices'      => $invoices,
            'customers'     => $customers,
            'variants'      => $variants,
        ]);
    }

    public function storeInvoice(): void {
        $this->validateCsrf();
        $cid = Auth::concernId();

        $customerId = (int)($_POST['customer_id'] ?? 0);
        $invDate    = $_POST['invoice_date'] ?: date('Y-m-d');
        $variantId  = (int)($_POST['variant_id'] ?? 0);
        $qty        = (float)($_POST['qty'] ?? 0);
        $unitPrice  = (float)($_POST['unit_price'] ?? 0);
        $payMethod  = $_POST['payment_method'] ?? 'cash';
        $payStatus  = $_POST['payment_status'] ?? 'paid';
        $notes      = trim($_POST['notes'] ?? '');

        if (!$customerId || !$variantId || $qty <= 0 || $unitPrice <= 0) {
            $this->flash('error', 'Customer, Variant, Quantity, and Unit Price are required.');
            $this->redirect('/m/billing');
        }

        $totalAmount    = $qty * $unitPrice;
        $taxAmount      = $totalAmount * 0.05;
        $discountAmount = 0.00;
        $netAmount      = $totalAmount + $taxAmount;

        DB::beginTransaction();
        try {
            $invoiceNo = NumberSeries::getNext($cid, 'INVOICE');

            DB::query(
                "INSERT INTO sales_invoices (concern_id, invoice_no, customer_id, invoice_date, total_amount, tax_amount, discount_amount, net_amount, payment_method, payment_status, notes, created_by, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
                [$cid, $invoiceNo, $customerId, $invDate, $totalAmount, $taxAmount, $discountAmount, $netAmount, $payMethod, $payStatus, $notes, Auth::id()]
            );
            $invoiceId = (int)DB::lastInsertId();

            DB::query(
                "INSERT INTO sales_invoice_items (concern_id, sales_invoice_id, variant_id, qty, unit_price, total_price)
                 VALUES (?, ?, ?, ?, ?, ?)",
                [$cid, $invoiceId, $variantId, $qty, $unitPrice, $totalAmount]
            );

            // If paid by cash, credit to Cash Account
            if ($payStatus === 'paid' && in_array($payMethod, ['cash', 'upi', 'bank_transfer'])) {
                $acc = DB::fetch("SELECT id FROM cash_accounts WHERE concern_id = ? LIMIT 1", [$cid]);
                if ($acc) {
                    CashService::postTransaction(
                        $cid,
                        $acc['id'],
                        $invDate,
                        'credit',
                        $netAmount,
                        'Billing',
                        $invoiceId,
                        "Payment for Invoice {$invoiceNo}",
                        Auth::id()
                    );
                }
            }

            DB::commit();

            $this->flash('success', "Sales Invoice '{$invoiceNo}' generated for ₹" . number_format($netAmount, 2) . ".");
        } catch (Exception $e) {
            DB::rollBack();
            $this->flash('error', 'Invoice generation failed: ' . $e->getMessage());
        }

        $this->redirect('/m/billing');
    }

    // 5. OUTWARD
    public function outward(): void {
        $cid = Auth::concernId();

        $outwards = DB::fetchAll(
            "SELECT o.*, c.name AS customer_name, si.invoice_no,
                    (SELECT COUNT(*) FROM outward_items oi WHERE oi.outward_id = o.id) AS items_count,
                    (SELECT COALESCE(SUM(qty), 0) FROM outward_items oi WHERE oi.outward_id = o.id) AS total_qty
             FROM outwards o
             JOIN customers c ON o.customer_id = c.id
             LEFT JOIN sales_invoices si ON o.sales_invoice_id = si.id
             WHERE o.concern_id = ?
             ORDER BY o.id DESC",
            [$cid]
        );

        $customers = DB::fetchAll("SELECT id, name FROM customers WHERE concern_id = ? AND status = 'active'", [$cid]);
        $invoices  = DB::fetchAll("SELECT id, invoice_no FROM sales_invoices WHERE concern_id = ?", [$cid]);
        $variants  = DB::fetchAll(
            "SELECT v.id, v.sku_code, v.name, v.size, COALESCE(s.qty, 0) AS stock_qty
             FROM variants v
             LEFT JOIN stocks s ON v.id = s.variant_id AND s.concern_id = ?
             WHERE v.concern_id = ? AND v.status = 'active'",
            [$cid, $cid]
        );

        $this->render('concern/outward', [
            'pageTitle'     => 'Outward Gate Pass & Packing',
            'currentModule' => 'outward',
            'outwards'      => $outwards,
            'customers'     => $customers,
            'invoices'      => $invoices,
            'variants'      => $variants,
        ]);
    }

    public function storeOutward(): void {
        $this->validateCsrf();
        $cid = Auth::concernId();

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

        $dispatches = DB::fetchAll(
            "SELECT d.*, o.outward_no, c.name AS customer_name
             FROM dispatches d
             JOIN outwards o ON d.outward_id = o.id
             JOIN customers c ON o.customer_id = c.id
             WHERE d.concern_id = ?
             ORDER BY d.id DESC",
            [$cid]
        );

        $outwards = DB::fetchAll(
            "SELECT id, outward_no FROM outwards WHERE concern_id = ? AND status = 'prepared'",
            [$cid]
        );

        $this->render('concern/dispatch', [
            'pageTitle'     => 'Dispatch & Logistics Tracking',
            'currentModule' => 'dispatch',
            'dispatches'    => $dispatches,
            'outwards'      => $outwards,
        ]);
    }

    public function storeDispatch(): void {
        $this->validateCsrf();
        $cid = Auth::concernId();

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

        $returns = DB::fetchAll(
            "SELECT re.*, c.name AS customer_name, v1.name AS returned_variant_name, v1.sku_code AS returned_sku,
                    v2.name AS exchange_variant_name, v2.sku_code AS exchange_sku
             FROM returns_exchanges re
             JOIN customers c ON re.customer_id = c.id
             JOIN variants v1 ON re.returned_variant_id = v1.id
             LEFT JOIN variants v2 ON re.exchange_variant_id = v2.id
             WHERE re.concern_id = ?
             ORDER BY re.id DESC",
            [$cid]
        );

        $customers = DB::fetchAll("SELECT id, name FROM customers WHERE concern_id = ? AND status = 'active'", [$cid]);
        $variants  = DB::fetchAll("SELECT id, sku_code, name, size FROM variants WHERE concern_id = ? AND status = 'active'", [$cid]);

        $this->render('concern/return_exchange', [
            'pageTitle'     => 'Customer Returns & Exchanges',
            'currentModule' => 'return_exchange',
            'returns'       => $returns,
            'customers'     => $customers,
            'variants'      => $variants,
        ]);
    }

    public function storeReturn(): void {
        $this->validateCsrf();
        $cid = Auth::concernId();

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
