<?php
// app/controllers/PurchaseController.php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;
use App\Core\Auth;
use App\Services\StockService;
use App\Services\NumberSeries;
use App\Services\CloudinaryService;
use Exception;

class PurchaseController extends Controller {
    // 1. SUPPLIERS
    public function suppliers(): void {
        $cid = Auth::concernId();

        $sql = "SELECT s.*, con.name AS concern_name, con.code AS concern_code
                FROM suppliers s
                LEFT JOIN concerns con ON s.concern_id = con.id";
        $params = [];
        if ($cid) {
            $sql .= " WHERE s.concern_id = ?";
            $params[] = $cid;
        }
        $sql .= " ORDER BY s.id DESC";
        $suppliers = DB::fetchAll($sql, $params);

        $allConcerns = [];
        if (Auth::isSuperAdmin()) {
            $allConcerns = DB::fetchAll("SELECT id, name, code FROM concerns ORDER BY id ASC");
        }

        $this->render('concern/suppliers', [
            'pageTitle'     => 'Supplier Master',
            'currentModule' => 'suppliers',
            'suppliers'     => $suppliers,
            'selectedCid'   => $cid,
            'allConcerns'   => $allConcerns,
        ]);
    }

    public function storeSupplier(): void {
        $this->validateCsrf();
        $cid = Auth::concernId() ?: (int)($_POST['concern_id'] ?? 0);

        $name     = trim($_POST['name'] ?? '');
        $company  = trim($_POST['company_name'] ?? '');
        $phone    = preg_replace('/\D/', '', trim($_POST['phone'] ?? ''));
        $email    = trim($_POST['email'] ?? '');
        $gstin    = strtoupper(trim($_POST['gstin'] ?? ''));
        $address  = trim($_POST['address'] ?? '');
        $district = trim($_POST['district'] ?? '');
        $state    = trim($_POST['state'] ?? 'Tamil Nadu');

        // Full Validation Rules
        $errors = [];

        if (!$cid || !DB::fetchValue("SELECT id FROM concerns WHERE id = ?", [$cid])) {
            $errors[] = 'Target Concern: Please select a valid business concern.';
        }

        if (mb_strlen($name) < 2 || mb_strlen($name) > 150) {
            $errors[] = 'Contact Person / Name: Must be between 2 and 150 characters.';
        }

        if (mb_strlen($company) < 2 || mb_strlen($company) > 150) {
            $errors[] = 'Company / Mill Name: Must be between 2 and 150 characters.';
        }

        if (empty($phone) || !preg_match('/^[6-9]\d{9}$/', $phone)) {
            $errors[] = 'Phone: Enter a valid 10-digit Indian mobile number (starting with 6, 7, 8, or 9).';
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email: Enter a valid email address (e.g. vendor@mill.com).';
        }

        if (!empty($gstin) && !preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/i', $gstin)) {
            $errors[] = 'GSTIN: Must follow standard 15-character GST format (e.g. 33AAAAA0000A1Z5).';
        }

        if (mb_strlen($address) < 3) {
            $errors[] = 'Address: Street / Area details are required (at least 3 characters).';
        }

        if (mb_strlen($district) < 2) {
            $errors[] = 'District: District is required (e.g. Karur, Tirupur, Coimbatore).';
        }

        if (mb_strlen($state) < 2) {
            $errors[] = 'State: State is required (e.g. Tamil Nadu).';
        }

        // Check for duplicates within this concern
        if ($cid) {
            if (!empty($phone) && DB::fetchValue("SELECT id FROM suppliers WHERE concern_id = ? AND phone = ? LIMIT 1", [$cid, $phone])) {
                $errors[] = "A supplier with this phone number ({$phone}) is already registered in this concern.";
            }
            if (!empty($gstin) && DB::fetchValue("SELECT id FROM suppliers WHERE concern_id = ? AND gstin = ? LIMIT 1", [$cid, $gstin])) {
                $errors[] = "A supplier with GSTIN ({$gstin}) is already registered in this concern.";
            }
        }

        if (!empty($errors)) {
            $this->flash('error', implode("\n", $errors));
            $this->redirect('/m/suppliers');
            return;
        }

        $code = 'SUP-' . str_pad((string)(DB::fetchValue("SELECT COUNT(*) FROM suppliers WHERE concern_id = ?", [$cid]) + 1), 3, '0', STR_PAD_LEFT);

        DB::query(
            "INSERT INTO suppliers (concern_id, code, name, company_name, phone, email, gstin, address, city, district, state, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')",
            [$cid, $code, $name, $company, $phone, $email, $gstin, $address, $district, $district, $state]
        );

        $this->flash('success', "Supplier '{$name}' ({$company}) registered successfully with code {$code}!");
        $this->redirect('/m/suppliers');
    }

    public function updateSupplier(): void {
        $this->validateCsrf();
        $id = (int)($_POST['supplier_id'] ?? 0);
        $cid = Auth::concernId();

        $supplier = null;
        if ($cid) {
            $supplier = DB::fetch("SELECT * FROM suppliers WHERE id = ? AND concern_id = ?", [$id, $cid]);
        } else {
            $supplier = DB::fetch("SELECT * FROM suppliers WHERE id = ?", [$id]);
            $cid = (int)($supplier['concern_id'] ?? ($_POST['concern_id'] ?? 0));
        }

        if (!$supplier) {
            $this->flash('error', 'Supplier record not found or access denied.');
            $this->redirect('/m/suppliers');
            return;
        }

        $name     = trim($_POST['name'] ?? '');
        $company  = trim($_POST['company_name'] ?? '');
        $phone    = preg_replace('/\D/', '', trim($_POST['phone'] ?? ''));
        $email    = trim($_POST['email'] ?? '');
        $gstin    = strtoupper(trim($_POST['gstin'] ?? ''));
        $address  = trim($_POST['address'] ?? '');
        $district = trim($_POST['district'] ?? '');
        $state    = trim($_POST['state'] ?? 'Tamil Nadu');
        $status   = trim($_POST['status'] ?? 'active');
        if (!in_array($status, ['active', 'inactive'], true)) {
            $status = 'active';
        }

        $errors = [];
        if (mb_strlen($name) < 2 || mb_strlen($name) > 150) {
            $errors[] = 'Contact Person / Name: Must be between 2 and 150 characters.';
        }
        if (mb_strlen($company) < 2 || mb_strlen($company) > 150) {
            $errors[] = 'Company / Mill Name: Must be between 2 and 150 characters.';
        }
        if (empty($phone) || !preg_match('/^[6-9]\d{9}$/', $phone)) {
            $errors[] = 'Phone: Enter a valid 10-digit Indian mobile number (starting with 6, 7, 8, or 9).';
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email: Enter a valid email address.';
        }
        if (!empty($gstin) && !preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/i', $gstin)) {
            $errors[] = 'GSTIN: Must follow standard 15-character GST format.';
        }
        if (mb_strlen($address) < 3) {
            $errors[] = 'Address: Street / Area details are required.';
        }
        if (mb_strlen($district) < 2) {
            $errors[] = 'District: District is required.';
        }
        if (mb_strlen($state) < 2) {
            $errors[] = 'State: State is required.';
        }

        if ($cid) {
            if (!empty($phone) && DB::fetchValue("SELECT id FROM suppliers WHERE concern_id = ? AND phone = ? AND id != ? LIMIT 1", [$cid, $phone, $id])) {
                $errors[] = "A supplier with phone number ({$phone}) is already registered in this concern.";
            }
            if (!empty($gstin) && DB::fetchValue("SELECT id FROM suppliers WHERE concern_id = ? AND gstin = ? AND id != ? LIMIT 1", [$cid, $gstin, $id])) {
                $errors[] = "A supplier with GSTIN ({$gstin}) is already registered in this concern.";
            }
        }

        if (!empty($errors)) {
            $this->flash('error', implode("\n", $errors));
            $this->redirect('/m/suppliers');
            return;
        }

        DB::query(
            "UPDATE suppliers SET name = ?, company_name = ?, phone = ?, email = ?, gstin = ?, address = ?, city = ?, district = ?, state = ?, status = ? WHERE id = ?",
            [$name, $company, $phone, $email, $gstin, $address, $district, $district, $state, $status, $id]
        );

        $this->flash('success', "Supplier '{$name}' ({$company}) updated successfully!");
        $this->redirect('/m/suppliers');
    }

    public function deleteSupplier(): void {
        $this->validateCsrf();
        $id = (int)($_POST['supplier_id'] ?? 0);
        $cid = Auth::concernId();

        $supplier = null;
        if ($cid) {
            $supplier = DB::fetch("SELECT * FROM suppliers WHERE id = ? AND concern_id = ?", [$id, $cid]);
        } else {
            $supplier = DB::fetch("SELECT * FROM suppliers WHERE id = ?", [$id]);
        }

        if (!$supplier) {
            $this->flash('error', 'Supplier record not found or access denied.');
            $this->redirect('/m/suppliers');
            return;
        }

        $hasBills = (int)DB::fetchValue("SELECT COUNT(*) FROM purchase_bills WHERE supplier_id = ?", [$id]);
        if ($hasBills > 0) {
            DB::query("UPDATE suppliers SET status = 'inactive' WHERE id = ?", [$id]);
            $this->flash('success', "Supplier '{$supplier['name']}' has {$hasBills} recorded purchase bills. Status updated to 'Inactive' to preserve financial audit history.");
        } else {
            DB::query("DELETE FROM suppliers WHERE id = ?", [$id]);
            $this->flash('success', "Supplier '{$supplier['name']}' deleted successfully.");
        }

        $this->redirect('/m/suppliers');
    }

    // 2. PURCHASE BILLING
    public function billing(): void {
        $cid = Auth::concernId();

        $sql = "SELECT pb.*, s.name AS supplier_name, con.name AS concern_name, con.code AS concern_code,
                       (SELECT COUNT(*) FROM purchase_bill_items pbi WHERE pbi.purchase_bill_id = pb.id) AS items_count
                FROM purchase_bills pb
                JOIN suppliers s ON pb.supplier_id = s.id
                LEFT JOIN concerns con ON pb.concern_id = con.id";
        $params = [];
        if ($cid) {
            $sql .= " WHERE pb.concern_id = ?";
            $params[] = $cid;
        }
        $sql .= " ORDER BY pb.id DESC";
        $bills = DB::fetchAll($sql, $params);

        if ($cid) {
            $suppliers = DB::fetchAll("SELECT id, name, company_name FROM suppliers WHERE concern_id = ? AND status = 'active'", [$cid]);
            $variants  = DB::fetchAll("SELECT id, sku_code, name, price, size FROM variants WHERE concern_id = ? AND status = 'active'", [$cid]);
        } else {
            $suppliers = DB::fetchAll("SELECT id, name, company_name FROM suppliers WHERE status = 'active'");
            $variants  = DB::fetchAll("SELECT id, sku_code, name, price, size FROM variants WHERE status = 'active'");
        }

        $this->render('concern/purchase_billing', [
            'pageTitle'     => 'Purchase Billing (Invoices)',
            'currentModule' => 'purchase_billing',
            'bills'         => $bills,
            'suppliers'     => $suppliers,
            'variants'      => $variants,
            'selectedCid'   => $cid,
        ]);
    }

    public function createBill(): void {
        $cid = Auth::concernId();

        if ($cid) {
            $suppliers = DB::fetchAll("SELECT id, code, name, company_name, district, state FROM suppliers WHERE concern_id = ? AND status = 'active' ORDER BY name ASC", [$cid]);
            $variants  = DB::fetchAll("SELECT id, sku_code, name, price, size FROM variants WHERE concern_id = ? AND status = 'active' ORDER BY name ASC", [$cid]);
            $hsnList   = DB::fetchAll("SELECT hsn_code, description, gst_rate FROM hsn_masters WHERE concern_id = ? OR concern_id IS NULL ORDER BY hsn_code ASC", [$cid]);
        } else {
            $suppliers = DB::fetchAll("SELECT id, concern_id, code, name, company_name, district, state FROM suppliers WHERE status = 'active' ORDER BY name ASC");
            $variants  = DB::fetchAll("SELECT id, concern_id, sku_code, name, price, size FROM variants WHERE status = 'active' ORDER BY name ASC");
            $hsnList   = DB::fetchAll("SELECT hsn_code, description, gst_rate FROM hsn_masters ORDER BY hsn_code ASC");
        }

        $allConcerns = [];
        if (Auth::isSuperAdmin()) {
            $allConcerns = DB::fetchAll("SELECT id, name, code FROM concerns ORDER BY id ASC");
        }

        $this->render('concern/purchase_billing_create', [
            'pageTitle'     => 'Record Production Purchase',
            'currentModule' => 'purchase_billing',
            'suppliers'     => $suppliers,
            'variants'      => $variants,
            'selectedCid'   => $cid,
            'allConcerns'   => $allConcerns,
            'hsnList'       => $hsnList,
        ]);
    }

    public function editBill(): void {
        $cid = Auth::concernId();
        $id = (int)($_GET['id'] ?? 0);

        $sql = "SELECT pb.*, s.name AS supplier_name, s.company_name AS supplier_company
                FROM purchase_bills pb
                JOIN suppliers s ON pb.supplier_id = s.id
                WHERE pb.id = ?";
        $params = [$id];
        if ($cid) {
            $sql .= " AND pb.concern_id = ?";
            $params[] = $cid;
        }

        $bill = DB::fetch($sql, $params);
        if (!$bill) {
            $this->flash('error', 'Purchase Bill not found or access denied.');
            $this->redirect('/m/purchase_billing');
            return;
        }

        $items = DB::fetchAll("SELECT * FROM purchase_bill_items WHERE purchase_bill_id = ? ORDER BY id ASC", [$id]);

        if ($cid) {
            $suppliers = DB::fetchAll("SELECT id, code, name, company_name, district, state FROM suppliers WHERE concern_id = ? AND status = 'active' ORDER BY name ASC", [$cid]);
            $variants  = DB::fetchAll("SELECT id, sku_code, name, price, size FROM variants WHERE concern_id = ? AND status = 'active' ORDER BY name ASC", [$cid]);
            $hsnList   = DB::fetchAll("SELECT hsn_code, description, gst_rate FROM hsn_masters WHERE concern_id = ? OR concern_id IS NULL ORDER BY hsn_code ASC", [$cid]);
        } else {
            $suppliers = DB::fetchAll("SELECT id, concern_id, code, name, company_name, district, state FROM suppliers WHERE status = 'active' ORDER BY name ASC");
            $variants  = DB::fetchAll("SELECT id, concern_id, sku_code, name, price, size FROM variants WHERE status = 'active' ORDER BY name ASC");
            $hsnList   = DB::fetchAll("SELECT hsn_code, description, gst_rate FROM hsn_masters ORDER BY hsn_code ASC");
        }

        $allConcerns = [];
        if (Auth::isSuperAdmin()) {
            $allConcerns = DB::fetchAll("SELECT id, name, code FROM concerns ORDER BY id ASC");
        }

        $this->render('concern/purchase_billing_create', [
            'pageTitle'     => 'Edit Purchase Bill: #' . $bill['bill_no'],
            'currentModule' => 'purchase_billing',
            'suppliers'     => $suppliers,
            'variants'      => $variants,
            'selectedCid'   => $cid,
            'allConcerns'   => $allConcerns,
            'hsnList'       => $hsnList,
            'bill'          => $bill,
            'existingItems' => $items,
        ]);
    }

    public function saveHsnAjax(): void {
        header('Content-Type: application/json; charset=utf-8');
        $cid = Auth::concernId();
        $code = strtoupper(trim($_POST['hsn_code'] ?? ''));
        $desc = trim($_POST['description'] ?? '');
        $rate = isset($_POST['gst_rate']) && $_POST['gst_rate'] !== '' ? (float)$_POST['gst_rate'] : 0.0;

        if (empty($code)) {
            echo json_encode(['success' => false, 'message' => 'HSN Code is required.']);
            exit;
        }

        try {
            $existing = DB::fetch(
                "SELECT id FROM hsn_masters WHERE hsn_code = ? AND (concern_id = ? OR concern_id IS NULL)",
                [$code, $cid]
            );

            if (!$existing) {
                DB::query(
                    "INSERT INTO hsn_masters (concern_id, hsn_code, description, gst_rate, created_at) VALUES (?, ?, ?, ?, NOW())",
                    [$cid, $code, $desc, $rate]
                );
            } else {
                DB::query(
                    "UPDATE hsn_masters SET description = ?, gst_rate = ? WHERE id = ?",
                    [$desc, $rate, $existing['id']]
                );
            }

            echo json_encode([
                'success' => true,
                'hsn_code' => $code,
                'description' => $desc,
                'gst_rate' => $rate,
                'label' => $code . ($desc ? ' - ' . $desc : '')
            ]);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function viewBill(): void {
        $cid = Auth::concernId();
        $id = (int)($_GET['id'] ?? 0);

        $sql = "SELECT pb.*, s.name AS supplier_name, s.company_name AS supplier_company, s.phone AS supplier_phone,
                       s.gstin AS supplier_gstin, s.district AS supplier_district, s.state AS supplier_state,
                       con.name AS concern_name, con.code AS concern_code
                FROM purchase_bills pb
                JOIN suppliers s ON pb.supplier_id = s.id
                LEFT JOIN concerns con ON pb.concern_id = con.id
                WHERE pb.id = ?";
        $params = [$id];
        if ($cid) {
            $sql .= " AND pb.concern_id = ?";
            $params[] = $cid;
        }

        $bill = DB::fetch($sql, $params);
        if (!$bill) {
            $this->flash('error', 'Purchase Bill not found.');
            $this->redirect('/m/purchase_billing');
            return;
        }

        $items = DB::fetchAll("SELECT * FROM purchase_bill_items WHERE purchase_bill_id = ? ORDER BY id ASC", [$id]);

        $this->render('concern/purchase_billing_view', [
            'pageTitle'     => 'Purchase Bill #' . $bill['bill_no'],
            'currentModule' => 'purchase_billing',
            'bill'          => $bill,
            'items'         => $items,
        ]);
    }

    public function downloadItems(): void {
        $cid = Auth::concernId();
        $id = (int)($_GET['id'] ?? 0);

        $sql = "SELECT pb.*, s.name AS supplier_name, s.company_name AS supplier_company, s.phone AS supplier_phone,
                       s.gstin AS supplier_gstin, s.district AS supplier_district, s.state AS supplier_state,
                       con.name AS concern_name, con.code AS concern_code
                FROM purchase_bills pb
                JOIN suppliers s ON pb.supplier_id = s.id
                LEFT JOIN concerns con ON pb.concern_id = con.id
                WHERE pb.id = ?";
        $params = [$id];
        if ($cid) {
            $sql .= " AND pb.concern_id = ?";
            $params[] = $cid;
        }

        $bill = DB::fetch($sql, $params);
        if (!$bill) {
            $this->flash('error', 'Purchase Bill not found.');
            $this->redirect('/m/purchase_billing');
            return;
        }

        $items = DB::fetchAll("SELECT * FROM purchase_bill_items WHERE purchase_bill_id = ? ORDER BY id ASC", [$id]);

        $cleanBillNo = preg_replace('/[^A-Za-z0-9_\-]/', '_', $bill['bill_no']);
        $filename = "Purchase_Items_{$cleanBillNo}_" . date('Ymd_His') . ".csv";

        // Send headers for CSV download
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        // Add UTF-8 BOM so Excel opens it with proper encoding
        fwrite($out, "\xEF\xBB\xBF");

        // Bill Header Information
        fputcsv($out, ['INVENTO MULTI-CONCERN ERP - PURCHASE BILL ITEMS']);
        fputcsv($out, ['Bill No', $bill['bill_no'], 'Purchase Date', date('d-m-Y', strtotime($bill['bill_date']))]);
        fputcsv($out, ['Supplier Name', $bill['supplier_name'], 'Company', $bill['supplier_company'] ?: '-']);
        fputcsv($out, ['Supplier Invoice No', $bill['supplier_invoice_no'] ?: '-', 'GSTIN', $bill['supplier_gstin'] ?: '-']);
        fputcsv($out, ['Payment Status', ucfirst($bill['payment_status']), 'Payment Method', ucfirst($bill['payment_method'] ?? 'credit')]);
        fputcsv($out, ['Inward Status', ucfirst($bill['inward_status']), 'Total Net Value (INR)', number_format($bill['net_amount'], 2, '.', '')]);
        fputcsv($out, []); // Blank separator line

        // Items Column Headers
        fputcsv($out, [
            'S.No',
            'Section / Category',
            'Item Code / Roll No',
            'Item / Product Name',
            'HSN Code',
            'Color / Shade',
            'Design / Pattern',
            'Quantity',
            'Unit',
            'Weight (kg)',
            'Unit Price (INR)',
            'Tax Mode',
            'GST Type',
            'GST Rate (%)',
            'Taxable Base (INR)',
            'Tax Amount (INR)',
            'Total Value (INR)'
        ]);

        $totalBase = 0.0;
        $totalTax  = 0.0;
        $totalNet  = 0.0;
        $totalQty  = 0.0;
        $totalWt   = 0.0;

        foreach ($items as $idx => $item) {
            $sectionLabel = match ($item['section_type'] ?? '') {
                'rolls'          => 'Fabric Rolls',
                'garment_other'  => 'Other Garment Supplies',
                'finished_goods' => 'Finished Goods',
                default          => ucfirst(str_replace('_', ' ', $item['section_type'] ?? 'General'))
            };

            $qty       = (float)($item['qty'] ?? 1);
            $weight    = (float)($item['net_weight'] ?? 0);
            $unitPrice = (float)($item['unit_price'] ?? 0);
            $baseVal   = (float)($item['total_price'] ?? 0);
            $taxVal    = (float)($item['tax_amount'] ?? 0);
            $totalVal  = $baseVal + $taxVal;
            $gstRate   = (float)($item['gst_rate'] ?? 0);

            $totalQty  += $qty;
            $totalWt   += $weight;
            $totalBase += $baseVal;
            $totalTax  += $taxVal;
            $totalNet  += $totalVal;

            fputcsv($out, [
                $idx + 1,
                $sectionLabel,
                $item['item_code'] ?: '-',
                $item['item_name'] ?: '-',
                $item['hsn_code'] ?: '-',
                $item['color'] ?: ($item['specification'] ?: '-'),
                $item['pattern'] ?: '-',
                $qty,
                $item['unit'] ?: 'Pcs',
                number_format($weight, 3, '.', ''),
                number_format($unitPrice, 2, '.', ''),
                $item['tax_mode'] ?: 'Exclude GST',
                $item['gst_type'] ?: 'Exempted',
                number_format($gstRate, 2, '.', '') . '%',
                number_format($baseVal, 2, '.', ''),
                number_format($taxVal, 2, '.', ''),
                number_format($totalVal, 2, '.', '')
            ]);
        }

        // Summary row
        fputcsv($out, []);
        fputcsv($out, [
            'TOTAL',
            '',
            '',
            '',
            '',
            '',
            '',
            number_format($totalQty, 2, '.', ''),
            '',
            number_format($totalWt, 3, '.', ''),
            '',
            '',
            '',
            '',
            number_format($totalBase, 2, '.', ''),
            number_format($totalTax, 2, '.', ''),
            number_format($totalNet, 2, '.', '')
        ]);

        fclose($out);
        exit;
    }

    private function calculateLineTax(
        float $qty,
        float $price,
        string $taxMode,
        string $gstType,
        string $taxType,
        float $sgstVal,
        float $cgstVal,
        float $igstVal,
        float $fallbackRate = 0.0
    ): array {
        $grossVal = round($qty * $price, 2);
        if ($gstType === 'Exempted') {
            return [
                'itemBase' => $grossVal,
                'itemTax'  => 0.0,
                'gstRate'  => 0.0,
                'total'    => $grossVal
            ];
        }

        $isAmount = ($taxType === 'Amount' || $taxType === 'Amount (₹)');
        $rate = 0.0;
        $tax = 0.0;

        if ($gstType === 'Both (CGST and SGST)') {
            if ($isAmount) {
                $tax = round($sgstVal + $cgstVal, 2);
            } else {
                $rate = round($sgstVal + $cgstVal, 2);
            }
        } else if ($gstType === 'CGST') {
            if ($isAmount) {
                $tax = round($cgstVal, 2);
            } else {
                $rate = round($cgstVal, 2);
            }
        } else if ($gstType === 'SGST') {
            if ($isAmount) {
                $tax = round($sgstVal, 2);
            } else {
                $rate = round($sgstVal, 2);
            }
        } else { // IGST
            if ($isAmount) {
                $tax = round($igstVal, 2);
            } else {
                $rate = round($igstVal, 2);
            }
        }

        if (!$isAmount && $rate === 0.0 && $fallbackRate > 0) {
            $rate = $fallbackRate;
        }

        if ($taxMode === 'Include GST') {
            $total = $grossVal;
            if ($isAmount) {
                $tax = min($tax, $grossVal);
                $base = round($grossVal - $tax, 2);
                $rate = ($base > 0) ? round(($tax / $base) * 100, 2) : 0.0;
            } else {
                $base = $rate > 0 ? round($grossVal / (1 + ($rate / 100)), 2) : $grossVal;
                $tax = round($grossVal - $base, 2);
            }
        } else {
            $base = $grossVal;
            if ($isAmount) {
                $rate = ($base > 0) ? round(($tax / $base) * 100, 2) : 0.0;
            } else {
                $tax = round($base * ($rate / 100), 2);
            }
            $total = round($base + $tax, 2);
        }

        return [
            'itemBase' => $base,
            'itemTax'  => $tax,
            'gstRate'  => $rate,
            'total'    => $total
        ];
    }

    public function storeBill(): void {
        $this->validateCsrf();
        $cid = Auth::concernId() ?: (int)($_POST['concern_id'] ?? 0);
        $supplierId = (int)($_POST['supplier_id'] ?? 0);

        if (!$cid && $supplierId) {
            $cid = (int)DB::fetchValue("SELECT concern_id FROM suppliers WHERE id = ?", [$supplierId]);
        }

        if (!$cid) {
            $this->flash('error', 'Please choose a target concern or valid supplier.');
            $this->redirect('/m/purchase_billing/create');
            return;
        }

        if (!$supplierId) {
            $this->flash('error', 'Please select a registered supplier.');
            $this->redirect('/m/purchase_billing/create');
            return;
        }

        $billDate          = !empty($_POST['bill_date']) ? $_POST['bill_date'] : date('Y-m-d');
        $supplierInvoiceNo = trim($_POST['supplier_invoice_no'] ?? '');
        $fabricDescription = trim($_POST['fabric_description'] ?? '');
        $notes             = trim($_POST['notes'] ?? '');
        $taxRate           = isset($_POST['tax_rate']) ? (float)$_POST['tax_rate'] : 5.0;

        // Payment & Inward Settings
        $paymentStatus    = trim($_POST['payment_status'] ?? 'unpaid');
        $paymentMethod    = trim($_POST['payment_method'] ?? 'credit');
        $paymentReference = trim($_POST['payment_reference'] ?? '');
        $paidAmount       = (float)($_POST['paid_amount'] ?? 0.0);
        $inwardStatus     = trim($_POST['inward_status'] ?? 'pending');

        if (!in_array($paymentStatus, ['unpaid', 'partial', 'paid'], true)) {
            $paymentStatus = 'unpaid';
        }
        if (!in_array($inwardStatus, ['pending', 'completed'], true)) {
            $inwardStatus = 'pending';
        }

        // Attachment handling (Cloudinary first, local disk fallback)
        $attachmentPath = null;
        if (!empty($_FILES['attachment']['name']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $saved = CloudinaryService::saveUploadedFile($_FILES['attachment'], 'bills', ['jpg', 'jpeg', 'png', 'webp', 'pdf']);
            if ($saved) {
                $attachmentPath = $saved;
            }
        }

        // Process the 3 Inline Sections
        $rollsRaw        = $_POST['rolls'] ?? [];
        $garmentOtherRaw = $_POST['garment_other'] ?? [];
        $finishedRaw     = $_POST['finished_goods'] ?? [];

        $itemsToInsert = [];
        $totalAmount   = 0.0;
        $totalTax      = 0.0;

        // 1. Fabric Rolls (Reference Image 1)
        if (is_array($rollsRaw)) {
            foreach ($rollsRaw as $idx => $r) {
                $netWt = (float)($r['weight'] ?? ($r['net_weight'] ?? 0));
                $price = (float)($r['price'] ?? ($r['rate'] ?? 0));
                $rq    = max(1, (int)($r['rq'] ?? 1));

                if ($rq > 0 && $price > 0) {
                    $taxMode  = trim($r['tax_mode'] ?? 'Exclude GST');
                    $gstType  = trim($r['gst'] ?? ($r['gst_type'] ?? 'Exempted'));
                    $taxType  = trim($r['tax_input_type'] ?? 'Percentage');
                    $sgstVal  = (float)($r['sgst'] ?? 0);
                    $cgstVal  = (float)($r['cgst'] ?? 0);
                    $igstVal  = (float)($r['igst'] ?? 0);
                    $fallback = (float)($r['gst_rate'] ?? 0);

                    $calc = $this->calculateLineTax($rq, $price, $taxMode, $gstType, $taxType, $sgstVal, $cgstVal, $igstVal, $fallback);
                    $itemBase = $calc['itemBase'];
                    $itemTax  = $calc['itemTax'];
                    $gstRate  = $calc['gstRate'];

                    $totalAmount += $itemBase;
                    $totalTax    += $itemTax;

                    // Handle roll sample photo upload if present (Cloudinary first, local fallback)
                    $rollPhotoPath = !empty($r['existing_photo']) ? trim($r['existing_photo']) : null;
                    $rollFile = CloudinaryService::normalizeNestedFile($_FILES['rolls'] ?? [], $idx, 'photo');
                    if ($rollFile) {
                        $savedPhoto = CloudinaryService::saveUploadedFile($rollFile, 'rolls', ['jpg', 'jpeg', 'png', 'webp']);
                        if ($savedPhoto) {
                            $rollPhotoPath = $savedPhoto;
                        }
                    }

                    $itemsToInsert[] = [
                        'section_type'  => 'rolls',
                        'variant_id'    => !empty($r['variant_id']) ? (int)$r['variant_id'] : null,
                        'item_name'     => trim($r['roll_code'] ?? ('ROLL #' . $idx)),
                        'item_code'     => trim($r['roll_code'] ?? ('ROLL #' . $idx)),
                        'hsn_code'      => trim($r['hsn'] ?? ($r['hsn_code'] ?? '')),
                        'specification' => trim($r['shade'] ?? ($r['color'] ?? '')),
                        'color'         => trim($r['color'] ?? ($r['shade'] ?? '')),
                        'pattern'       => trim($r['pattern'] ?? ($r['design_name'] ?? '')),
                        'designs_count' => 1,
                        'rq_qty'        => $rq,
                        'unit'          => 'RQ',
                        'gross_weight'  => $netWt,
                        'net_weight'    => $netWt,
                        'qty'           => $rq,
                        'unit_price'    => $price,
                        'gst_rate'      => $gstRate,
                        'gst_type'      => $gstType,
                        'tax_mode'      => $taxMode,
                        'total_price'   => $itemBase,
                        'tax_amount'    => $itemTax,
                        'photo_path'    => $rollPhotoPath,
                        'category_name' => 'Fabric Rolls',
                        'product_type'  => 'Roll',
                        'mrp'           => null,
                        'selling_price' => null,
                    ];
                }
            }
        }

        // 2. Other Garment Supplies (Exact same GST and HSN fields)
        if (is_array($garmentOtherRaw)) {
            foreach ($garmentOtherRaw as $idx => $g) {
                $qty   = (float)($g['quantity'] ?? ($g['qty'] ?? 0));
                $price = (float)($g['unit_price'] ?? ($g['rate'] ?? 0));

                if ($qty > 0 && $price > 0) {
                    $taxMode  = trim($g['tax_mode'] ?? 'Exclude GST');
                    $gstType  = trim($g['gst'] ?? ($g['gst_type'] ?? 'Exempted'));
                    $taxType  = trim($g['tax_input_type'] ?? 'Percentage');
                    $sgstVal  = (float)($g['sgst'] ?? 0);
                    $cgstVal  = (float)($g['cgst'] ?? 0);
                    $igstVal  = (float)($g['igst'] ?? 0);
                    $fallback = (float)($g['gst_rate'] ?? 0);

                    $calc = $this->calculateLineTax($qty, $price, $taxMode, $gstType, $taxType, $sgstVal, $cgstVal, $igstVal, $fallback);
                    $itemBase = $calc['itemBase'];
                    $itemTax  = $calc['itemTax'];
                    $gstRate  = $calc['gstRate'];

                    $totalAmount += $itemBase;
                    $totalTax    += $itemTax;

                    $itemsToInsert[] = [
                        'section_type'  => 'garment_other',
                        'variant_id'    => !empty($g['variant_id']) ? (int)$g['variant_id'] : null,
                        'item_name'     => trim($g['item_name'] ?? 'Garment Supply'),
                        'item_code'     => trim($g['product_no'] ?? ($g['item_code'] ?? '')),
                        'hsn_code'      => trim($g['hsn'] ?? ''),
                        'specification' => trim($g['item_name'] ?? ''),
                        'color'         => null,
                        'pattern'       => null,
                        'designs_count' => 1,
                        'rq_qty'        => 1,
                        'unit'          => trim($g['unit'] ?? 'Pieces'),
                        'gross_weight'  => null,
                        'net_weight'    => null,
                        'qty'           => $qty,
                        'unit_price'    => $price,
                        'gst_rate'      => $gstRate,
                        'gst_type'      => $gstType,
                        'tax_mode'      => $taxMode,
                        'total_price'   => $itemBase,
                        'tax_amount'    => $itemTax,
                        'photo_path'    => null,
                        'category_name' => 'Garment Other Supplies',
                        'product_type'  => 'Supply / Trim',
                        'mrp'           => null,
                        'selling_price' => null,
                    ];
                }
            }
        }

        // 3. Finished Goods (Exact same GST and HSN fields)
        if (is_array($finishedRaw)) {
            foreach ($finishedRaw as $idx => $f) {
                $qty   = (float)($f['quantity'] ?? ($f['qty'] ?? 0));
                $price = (float)($f['supplier_cost'] ?? ($f['unit_price'] ?? 0));

                if ($qty > 0 && $price > 0) {
                    $taxMode  = trim($f['tax_mode'] ?? 'Exclude GST');
                    $gstType  = trim($f['gst'] ?? ($f['gst_type'] ?? 'Exempted'));
                    $taxType  = trim($f['tax_input_type'] ?? 'Percentage');
                    $sgstVal  = (float)($f['sgst'] ?? 0);
                    $cgstVal  = (float)($f['cgst'] ?? 0);
                    $igstVal  = (float)($f['igst'] ?? 0);
                    $fallback = (float)($f['gst_rate'] ?? 0);

                    $calc = $this->calculateLineTax($qty, $price, $taxMode, $gstType, $taxType, $sgstVal, $cgstVal, $igstVal, $fallback);
                    $itemBase = $calc['itemBase'];
                    $itemTax  = $calc['itemTax'];
                    $gstRate  = $calc['gstRate'];

                    $totalAmount += $itemBase;
                    $totalTax    += $itemTax;

                    $itemsToInsert[] = [
                        'section_type'  => 'finished_goods',
                        'variant_id'    => !empty($f['variant_id']) ? (int)$f['variant_id'] : null,
                        'item_name'     => trim($f['product'] ?? ($f['item_name'] ?? 'Finished Garment')),
                        'item_code'     => trim($f['sku'] ?? ($f['item_code'] ?? '')),
                        'hsn_code'      => trim($f['hsn'] ?? ''),
                        'specification' => trim($f['variant'] ?? ($f['size'] ?? '')),
                        'color'         => null,
                        'pattern'       => null,
                        'designs_count' => 1,
                        'rq_qty'        => 1,
                        'unit'          => 'Pieces',
                        'gross_weight'  => null,
                        'net_weight'    => (float)($f['weight'] ?? 0),
                        'qty'           => $qty,
                        'unit_price'    => $price,
                        'gst_rate'      => $gstRate,
                        'gst_type'      => $gstType,
                        'tax_mode'      => $taxMode,
                        'total_price'   => $itemBase,
                        'tax_amount'    => $itemTax,
                        'photo_path'    => null,
                        'category_name' => trim($f['category'] ?? 'Apparel Finished Goods'),
                        'product_type'  => trim($f['product_type'] ?? 'Individual Piece'),
                        'mrp'           => (float)($f['mrp'] ?? 0),
                        'selling_price' => (float)($f['selling_price'] ?? 0),
                    ];
                }
            }
        }

        // Backward compatibility fallback for single-item modal submissions
        if (empty($itemsToInsert) && !empty($_POST['variant_id']) && (float)($_POST['qty'] ?? 0) > 0) {
            $varId = (int)$_POST['variant_id'];
            $qty   = (float)$_POST['qty'];
            $price = (float)$_POST['unit_price'];
            $varName = DB::fetchValue("SELECT name FROM variants WHERE id = ?", [$varId]) ?: 'Product Item';
            $itemTotal = round($qty * $price, 2);
            $totalAmount += $itemTotal;
            $itemsToInsert[] = [
                'section_type'  => 'rolls',
                'variant_id'    => $varId,
                'item_name'     => $varName,
                'item_code'     => 'SKU-' . $varId,
                'hsn_code'      => null,
                'specification' => '',
                'color'         => null,
                'pattern'       => null,
                'designs_count' => 1,
                'rq_qty'        => 1,
                'unit'          => 'pcs',
                'gross_weight'  => null,
                'net_weight'    => null,
                'qty'           => $qty,
                'unit_price'    => $price,
                'gst_rate'      => 0.00,
                'gst_type'      => 'Exempted',
                'tax_mode'      => 'Exclude GST',
                'total_price'   => $itemTotal,
                'tax_amount'    => 0.00,
                'photo_path'    => null,
                'category_name' => 'General',
                'product_type'  => 'Item',
                'mrp'           => null,
                'selling_price' => null,
            ];
        }

        if (empty($itemsToInsert)) {
            $this->flash('error', 'Please enter at least one item (Roll, Garment Supply, or Finished Good) with quantity and price.');
            $this->redirect('/m/purchase_billing/create');
            return;
        }

        $netAmount = round($totalAmount + $totalTax, 2);
        if ($paymentStatus === 'paid' && $paidAmount <= 0) {
            $paidAmount = $netAmount;
        }

        DB::beginTransaction();
        try {
            $billNo = NumberSeries::getNext($cid, 'PO');

            DB::query(
                "INSERT INTO purchase_bills (
                    concern_id, bill_no, supplier_invoice_no, supplier_id, bill_date,
                    total_amount, tax_amount, net_amount, payment_status, payment_method, payment_reference, paid_amount,
                    inward_status, notes, attachment_path, fabric_description, created_by, created_at
                 ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
                [
                    $cid, $billNo, $supplierInvoiceNo, $supplierId, $billDate,
                    $totalAmount, $totalTax, $netAmount, $paymentStatus, $paymentMethod, $paymentReference, $paidAmount,
                    $inwardStatus, $notes, $attachmentPath, $fabricDescription, Auth::id()
                ]
            );
            $billId = (int)DB::lastInsertId();

            foreach ($itemsToInsert as $item) {
                DB::query(
                    "INSERT INTO purchase_bill_items (
                        concern_id, purchase_bill_id, variant_id, section_type, item_name, item_code,
                        hsn_code, specification, color, pattern, designs_count, rq_qty, unit,
                        gross_weight, net_weight, qty, unit_price, gst_rate, gst_type, tax_mode, total_price, tax_amount,
                        photo_path, category_name, product_type, mrp, selling_price
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $cid,
                        $billId,
                        $item['variant_id'],
                        $item['section_type'],
                        $item['item_name'],
                        $item['item_code'],
                        $item['hsn_code'],
                        $item['specification'],
                        $item['color'],
                        $item['pattern'],
                        $item['designs_count'],
                        $item['rq_qty'],
                        $item['unit'],
                        $item['gross_weight'],
                        $item['net_weight'],
                        $item['qty'],
                        $item['unit_price'],
                        $item['gst_rate'],
                        $item['gst_type'],
                        $item['tax_mode'],
                        $item['total_price'],
                        $item['tax_amount'],
                        $item['photo_path'],
                        $item['category_name'],
                        $item['product_type'],
                        $item['mrp'],
                        $item['selling_price'],
                    ]
                );

                // Ensure newly recorded HSN code is preserved in HSN master for future use
                if (!empty($item['hsn_code']) && $item['hsn_code'] !== '-') {
                    $existingHsn = DB::fetchValue("SELECT id FROM hsn_masters WHERE hsn_code = ? AND (concern_id = ? OR concern_id IS NULL)", [$item['hsn_code'], $cid]);
                    if (!$existingHsn) {
                        DB::query("INSERT INTO hsn_masters (concern_id, hsn_code, description, gst_rate, created_at) VALUES (?, ?, ?, ?, NOW())", [
                            $cid,
                            $item['hsn_code'],
                            $item['item_name'] ?? 'Recorded HSN',
                            $item['gst_rate'] ?? 5.0
                        ]);
                    }
                }
            }

            DB::commit();

            $this->flash('success', "Production Purchase '{$billNo}' recorded successfully for ₹" . number_format($netAmount, 2) . " (" . count($itemsToInsert) . " items)!");
            $this->redirect('/m/purchase_billing');
        } catch (Exception $e) {
            DB::rollBack();
            $this->flash('error', 'Error recording production purchase: ' . $e->getMessage());
            $this->redirect('/m/purchase_billing/create');
        }
    }

    public function updateBill(): void {
        $this->validateCsrf();
        $billId = (int)($_POST['bill_id'] ?? 0);
        $cid = Auth::concernId() ?: (int)($_POST['concern_id'] ?? 0);

        if (!$billId) {
            $this->flash('error', 'Invalid purchase bill ID.');
            $this->redirect('/m/purchase_billing');
            return;
        }

        $bill = null;
        if ($cid) {
            $bill = DB::fetch("SELECT * FROM purchase_bills WHERE id = ? AND concern_id = ?", [$billId, $cid]);
        } else {
            $bill = DB::fetch("SELECT * FROM purchase_bills WHERE id = ?", [$billId]);
            if ($bill) {
                $cid = (int)$bill['concern_id'];
            }
        }

        if (!$bill) {
            $this->flash('error', 'Purchase Bill not found or access denied.');
            $this->redirect('/m/purchase_billing');
            return;
        }

        $supplierId = (int)($_POST['supplier_id'] ?? $bill['supplier_id']);
        if (!$supplierId) {
            $this->flash('error', 'Please select a registered supplier.');
            $this->redirect('/m/purchase_billing/edit?id=' . $billId);
            return;
        }

        $billDate          = !empty($_POST['bill_date']) ? $_POST['bill_date'] : $bill['bill_date'];
        $supplierInvoiceNo = trim($_POST['supplier_invoice_no'] ?? '');
        $fabricDescription = trim($_POST['fabric_description'] ?? '');
        $notes             = trim($_POST['notes'] ?? '');
        $paymentStatus     = trim($_POST['payment_status'] ?? ($bill['payment_status'] ?? 'unpaid'));
        $paymentMethod     = trim($_POST['payment_method'] ?? ($bill['payment_method'] ?? 'credit'));
        $paymentReference  = trim($_POST['payment_reference'] ?? ($bill['payment_reference'] ?? ''));
        $paidAmount        = isset($_POST['paid_amount']) ? (float)$_POST['paid_amount'] : (float)($bill['paid_amount'] ?? 0.0);
        $inwardStatus      = trim($_POST['inward_status'] ?? ($bill['inward_status'] ?? 'pending'));

        if (!in_array($paymentStatus, ['unpaid', 'partial', 'paid'], true)) {
            $paymentStatus = 'unpaid';
        }
        if (!in_array($inwardStatus, ['pending', 'completed'], true)) {
            $inwardStatus = 'pending';
        }

        // Attachment handling (Cloudinary first, local disk fallback): keep existing if no new file uploaded
        $attachmentPath = $bill['attachment_path'];
        if (!empty($_FILES['attachment']['name']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $saved = CloudinaryService::saveUploadedFile($_FILES['attachment'], 'bills', ['jpg', 'jpeg', 'png', 'webp', 'pdf']);
            if ($saved) {
                $attachmentPath = $saved;
            }
        }

        // Process sections
        $rollsRaw        = $_POST['rolls'] ?? [];
        $garmentOtherRaw = $_POST['garment_other'] ?? [];
        $finishedRaw     = $_POST['finished_goods'] ?? [];

        $itemsToInsert = [];
        $totalAmount   = 0.0;
        $totalTax      = 0.0;

        // 1. Rolls
        if (is_array($rollsRaw)) {
            foreach ($rollsRaw as $idx => $r) {
                $netWt = (float)($r['weight'] ?? ($r['net_weight'] ?? 0));
                $price = (float)($r['price'] ?? ($r['rate'] ?? 0));
                $rq    = max(1, (int)($r['rq'] ?? 1));

                if ($rq > 0 && $price > 0) {
                    $taxMode  = trim($r['tax_mode'] ?? 'Exclude GST');
                    $gstType  = trim($r['gst'] ?? ($r['gst_type'] ?? 'Exempted'));
                    $taxType  = trim($r['tax_input_type'] ?? 'Percentage');
                    $sgstVal  = (float)($r['sgst'] ?? 0);
                    $cgstVal  = (float)($r['cgst'] ?? 0);
                    $igstVal  = (float)($r['igst'] ?? 0);
                    $fallback = (float)($r['gst_rate'] ?? 0);

                    $calc = $this->calculateLineTax($rq, $price, $taxMode, $gstType, $taxType, $sgstVal, $cgstVal, $igstVal, $fallback);
                    $itemBase = $calc['itemBase'];
                    $itemTax  = $calc['itemTax'];
                    $gstRate  = $calc['gstRate'];

                    $totalAmount += $itemBase;
                    $totalTax    += $itemTax;

                    // Handle roll photo upload (Cloudinary first, local disk fallback)
                    $rollPhotoPath = !empty($r['existing_photo']) ? trim($r['existing_photo']) : null;
                    $rollFile = CloudinaryService::normalizeNestedFile($_FILES['rolls'] ?? [], $idx, 'photo');
                    if ($rollFile) {
                        $savedPhoto = CloudinaryService::saveUploadedFile($rollFile, 'rolls', ['jpg', 'jpeg', 'png', 'webp']);
                        if ($savedPhoto) {
                            $rollPhotoPath = $savedPhoto;
                        }
                    }

                    $itemsToInsert[] = [
                        'section_type'  => 'rolls',
                        'variant_id'    => !empty($r['variant_id']) ? (int)$r['variant_id'] : null,
                        'item_name'     => trim($r['roll_code'] ?? ('ROLL #' . $idx)),
                        'item_code'     => trim($r['roll_code'] ?? ('ROLL #' . $idx)),
                        'hsn_code'      => trim($r['hsn'] ?? ($r['hsn_code'] ?? '')),
                        'specification' => trim($r['shade'] ?? ($r['color'] ?? '')),
                        'color'         => trim($r['color'] ?? ($r['shade'] ?? '')),
                        'pattern'       => trim($r['pattern'] ?? ($r['design_name'] ?? '')),
                        'designs_count' => 1,
                        'rq_qty'        => $rq,
                        'unit'          => 'RQ',
                        'gross_weight'  => $netWt,
                        'net_weight'    => $netWt,
                        'qty'           => $rq,
                        'unit_price'    => $price,
                        'gst_rate'      => $gstRate,
                        'gst_type'      => $gstType,
                        'tax_mode'      => $taxMode,
                        'total_price'   => $itemBase,
                        'tax_amount'    => $itemTax,
                        'photo_path'    => $rollPhotoPath,
                        'category_name' => 'Fabric Rolls',
                        'product_type'  => 'Roll',
                        'mrp'           => null,
                        'selling_price' => null,
                    ];
                }
            }
        }

        // 2. Garment Other (Exact same GST and HSN fields)
        if (is_array($garmentOtherRaw)) {
            foreach ($garmentOtherRaw as $idx => $g) {
                $qty   = (float)($g['quantity'] ?? ($g['qty'] ?? 0));
                $price = (float)($g['unit_price'] ?? ($g['rate'] ?? 0));

                if ($qty > 0 && $price > 0) {
                    $taxMode  = trim($g['tax_mode'] ?? 'Exclude GST');
                    $gstType  = trim($g['gst'] ?? ($g['gst_type'] ?? 'Exempted'));
                    $taxType  = trim($g['tax_input_type'] ?? 'Percentage');
                    $sgstVal  = (float)($g['sgst'] ?? 0);
                    $cgstVal  = (float)($g['cgst'] ?? 0);
                    $igstVal  = (float)($g['igst'] ?? 0);
                    $fallback = (float)($g['gst_rate'] ?? 0);

                    $calc = $this->calculateLineTax($qty, $price, $taxMode, $gstType, $taxType, $sgstVal, $cgstVal, $igstVal, $fallback);
                    $itemBase = $calc['itemBase'];
                    $itemTax  = $calc['itemTax'];
                    $gstRate  = $calc['gstRate'];

                    $totalAmount += $itemBase;
                    $totalTax    += $itemTax;

                    $itemsToInsert[] = [
                        'section_type'  => 'garment_other',
                        'variant_id'    => !empty($g['variant_id']) ? (int)$g['variant_id'] : null,
                        'item_name'     => trim($g['item_name'] ?? 'Garment Supply'),
                        'item_code'     => trim($g['product_no'] ?? ($g['item_code'] ?? '')),
                        'hsn_code'      => trim($g['hsn'] ?? ''),
                        'specification' => trim($g['item_name'] ?? ''),
                        'color'         => null,
                        'pattern'       => null,
                        'designs_count' => 1,
                        'rq_qty'        => 1,
                        'unit'          => trim($g['unit'] ?? 'Pieces'),
                        'gross_weight'  => null,
                        'net_weight'    => null,
                        'qty'           => $qty,
                        'unit_price'    => $price,
                        'gst_rate'      => $gstRate,
                        'gst_type'      => $gstType,
                        'tax_mode'      => $taxMode,
                        'total_price'   => $itemBase,
                        'tax_amount'    => $itemTax,
                        'photo_path'    => null,
                        'category_name' => 'Garment Other Supplies',
                        'product_type'  => 'Supply / Trim',
                        'mrp'           => null,
                        'selling_price' => null,
                    ];
                }
            }
        }

        // 3. Finished Goods (Exact same GST and HSN fields)
        if (is_array($finishedRaw)) {
            foreach ($finishedRaw as $idx => $f) {
                $qty   = (float)($f['quantity'] ?? ($f['qty'] ?? 0));
                $price = (float)($f['supplier_cost'] ?? ($f['unit_price'] ?? 0));

                if ($qty > 0 && $price > 0) {
                    $taxMode  = trim($f['tax_mode'] ?? 'Exclude GST');
                    $gstType  = trim($f['gst'] ?? ($f['gst_type'] ?? 'Exempted'));
                    $taxType  = trim($f['tax_input_type'] ?? 'Percentage');
                    $sgstVal  = (float)($f['sgst'] ?? 0);
                    $cgstVal  = (float)($f['cgst'] ?? 0);
                    $igstVal  = (float)($f['igst'] ?? 0);
                    $fallback = (float)($f['gst_rate'] ?? 0);

                    $calc = $this->calculateLineTax($qty, $price, $taxMode, $gstType, $taxType, $sgstVal, $cgstVal, $igstVal, $fallback);
                    $itemBase = $calc['itemBase'];
                    $itemTax  = $calc['itemTax'];
                    $gstRate  = $calc['gstRate'];

                    $totalAmount += $itemBase;
                    $totalTax    += $itemTax;

                    $itemsToInsert[] = [
                        'section_type'  => 'finished_goods',
                        'variant_id'    => !empty($f['variant_id']) ? (int)$f['variant_id'] : null,
                        'item_name'     => trim($f['product'] ?? ($f['item_name'] ?? 'Finished Garment')),
                        'item_code'     => trim($f['sku'] ?? ($f['item_code'] ?? '')),
                        'hsn_code'      => trim($f['hsn'] ?? ''),
                        'specification' => trim($f['variant'] ?? ($f['size'] ?? '')),
                        'color'         => null,
                        'pattern'       => null,
                        'designs_count' => 1,
                        'rq_qty'        => 1,
                        'unit'          => 'Pieces',
                        'gross_weight'  => null,
                        'net_weight'    => (float)($f['weight'] ?? 0),
                        'qty'           => $qty,
                        'unit_price'    => $price,
                        'gst_rate'      => $gstRate,
                        'gst_type'      => $gstType,
                        'tax_mode'      => $taxMode,
                        'total_price'   => $itemBase,
                        'tax_amount'    => $itemTax,
                        'photo_path'    => null,
                        'category_name' => trim($f['category'] ?? 'Apparel Finished Goods'),
                        'product_type'  => trim($f['product_type'] ?? 'Individual Piece'),
                        'mrp'           => (float)($f['mrp'] ?? 0),
                        'selling_price' => (float)($f['selling_price'] ?? 0),
                    ];
                }
            }
        }

        if (empty($itemsToInsert)) {
            $this->flash('error', 'Please enter at least one item with valid quantity and price.');
            $this->redirect('/m/purchase_billing/edit?id=' . $billId);
            return;
        }

        $netAmount = round($totalAmount + $totalTax, 2);
        if ($paymentStatus === 'paid' && $paidAmount <= 0) {
            $paidAmount = $netAmount;
        }

        DB::beginTransaction();
        try {
            DB::query(
                "UPDATE purchase_bills
                 SET supplier_id = ?, bill_date = ?, supplier_invoice_no = ?, notes = ?,
                     payment_status = ?, payment_method = ?, payment_reference = ?, paid_amount = ?,
                     inward_status = ?, attachment_path = ?, fabric_description = ?,
                     total_amount = ?, tax_amount = ?, net_amount = ?
                 WHERE id = ?",
                [
                    $supplierId, $billDate, $supplierInvoiceNo, $notes,
                    $paymentStatus, $paymentMethod, $paymentReference, $paidAmount,
                    $inwardStatus, $attachmentPath, $fabricDescription,
                    $totalAmount, $totalTax, $netAmount,
                    $billId
                ]
            );

            // Re-insert items
            DB::query("DELETE FROM purchase_bill_items WHERE purchase_bill_id = ?", [$billId]);

            foreach ($itemsToInsert as $item) {
                DB::query(
                    "INSERT INTO purchase_bill_items (
                        concern_id, purchase_bill_id, variant_id, section_type, item_name, item_code,
                        hsn_code, specification, color, pattern, designs_count, rq_qty, unit,
                        gross_weight, net_weight, qty, unit_price, gst_rate, gst_type, tax_mode, total_price, tax_amount,
                        photo_path, category_name, product_type, mrp, selling_price
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $cid,
                        $billId,
                        $item['variant_id'],
                        $item['section_type'],
                        $item['item_name'],
                        $item['item_code'],
                        $item['hsn_code'],
                        $item['specification'],
                        $item['color'],
                        $item['pattern'],
                        $item['designs_count'],
                        $item['rq_qty'],
                        $item['unit'],
                        $item['gross_weight'],
                        $item['net_weight'],
                        $item['qty'],
                        $item['unit_price'],
                        $item['gst_rate'],
                        $item['gst_type'],
                        $item['tax_mode'],
                        $item['total_price'],
                        $item['tax_amount'],
                        $item['photo_path'],
                        $item['category_name'],
                        $item['product_type'],
                        $item['mrp'],
                        $item['selling_price'],
                    ]
                );

                if (!empty($item['hsn_code']) && $item['hsn_code'] !== '-') {
                    $existingHsn = DB::fetchValue("SELECT id FROM hsn_masters WHERE hsn_code = ? AND (concern_id = ? OR concern_id IS NULL)", [$item['hsn_code'], $cid]);
                    if (!$existingHsn) {
                        DB::query("INSERT INTO hsn_masters (concern_id, hsn_code, description, gst_rate, created_at) VALUES (?, ?, ?, ?, NOW())", [
                            $cid,
                            $item['hsn_code'],
                            $item['item_name'] ?? 'Recorded HSN',
                            $item['gst_rate'] ?? 5.0
                        ]);
                    }
                }
            }

            DB::commit();

            $this->flash('success', "Purchase Bill '#{$bill['bill_no']}' updated successfully for ₹" . number_format($netAmount, 2) . " (" . count($itemsToInsert) . " items)!");
            $this->redirect('/m/purchase_billing');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->flash('error', 'Error updating purchase bill: ' . $e->getMessage());
            $this->redirect('/m/purchase_billing/edit?id=' . $billId);
        }
    }

    public function updateBillStatus(): void {
        $this->validateCsrf();
        $id = (int)($_POST['bill_id'] ?? 0);
        $cid = Auth::concernId();

        $bill = null;
        if ($cid) {
            $bill = DB::fetch("SELECT * FROM purchase_bills WHERE id = ? AND concern_id = ?", [$id, $cid]);
        } else {
            $bill = DB::fetch("SELECT * FROM purchase_bills WHERE id = ?", [$id]);
        }

        if (!$bill) {
            $this->flash('error', 'Purchase Bill not found or access denied.');
            $this->redirect('/m/purchase_billing');
            return;
        }

        $paymentStatus = trim($_POST['payment_status'] ?? 'unpaid');
        $inwardStatus  = trim($_POST['inward_status'] ?? 'pending');
        $notes         = trim($_POST['notes'] ?? '');

        if (!in_array($paymentStatus, ['unpaid', 'partial', 'paid'], true)) {
            $paymentStatus = 'unpaid';
        }
        if (!in_array($inwardStatus, ['pending', 'completed'], true)) {
            $inwardStatus = 'pending';
        }

        DB::query(
            "UPDATE purchase_bills SET payment_status = ?, inward_status = ?, notes = ? WHERE id = ?",
            [$paymentStatus, $inwardStatus, $notes, $id]
        );

        $this->flash('success', "Purchase Bill #{$bill['bill_no']} updated: Payment [{$paymentStatus}], Inward [{$inwardStatus}].");
        $this->redirect('/m/purchase_billing');
    }

    public function deleteBill(): void {
        $this->validateCsrf();
        $id = (int)($_POST['bill_id'] ?? 0);
        $cid = Auth::concernId();

        $bill = null;
        if ($cid) {
            $bill = DB::fetch("SELECT * FROM purchase_bills WHERE id = ? AND concern_id = ?", [$id, $cid]);
        } else {
            $bill = DB::fetch("SELECT * FROM purchase_bills WHERE id = ?", [$id]);
        }

        if (!$bill) {
            $this->flash('error', 'Purchase Bill not found or access denied.');
            $this->redirect('/m/purchase_billing');
            return;
        }

        DB::beginTransaction();
        try {
            DB::query("DELETE FROM purchase_bill_items WHERE purchase_bill_id = ?", [$id]);
            DB::query("DELETE FROM purchase_bills WHERE id = ?", [$id]);
            DB::commit();

            $this->flash('success', "Purchase Bill #{$bill['bill_no']} deleted successfully.");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->flash('error', 'Failed to delete purchase bill: ' . $e->getMessage());
        }

        $this->redirect('/m/purchase_billing');
    }

    // 3. PURCHASE DAMAGE
    public function damage(): void {
        $cid = Auth::concernId();

        $sql = "SELECT pd.*, s.name AS supplier_name, v.name AS variant_name, v.sku_code,
                       con.name AS concern_name, con.code AS concern_code
                FROM purchase_damages pd
                JOIN suppliers s ON pd.supplier_id = s.id
                JOIN variants v ON pd.variant_id = v.id
                LEFT JOIN concerns con ON pd.concern_id = con.id";
        $params = [];
        if ($cid) {
            $sql .= " WHERE pd.concern_id = ?";
            $params[] = $cid;
        }
        $sql .= " ORDER BY pd.id DESC";
        $damages = DB::fetchAll($sql, $params);

        if ($cid) {
            $suppliers = DB::fetchAll("SELECT id, name FROM suppliers WHERE concern_id = ? AND status = 'active'", [$cid]);
            $variants  = DB::fetchAll("SELECT id, sku_code, name FROM variants WHERE concern_id = ? AND status = 'active'", [$cid]);
        } else {
            $suppliers = DB::fetchAll("SELECT id, name FROM suppliers WHERE status = 'active'");
            $variants  = DB::fetchAll("SELECT id, sku_code, name FROM variants WHERE status = 'active'");
        }

        $this->render('concern/purchase_damage', [
            'pageTitle'     => 'Purchase Damage & Rejections',
            'currentModule' => 'purchase_damage',
            'damages'       => $damages,
            'suppliers'     => $suppliers,
            'variants'      => $variants,
            'selectedCid'   => $cid,
        ]);
    }

    public function storeDamage(): void {
        $this->validateCsrf();
        $cid = Auth::concernId() ?: (int)($_POST['concern_id'] ?? 0);

        if (!$cid) {
            $this->flash('error', 'Please choose a concern for this purchase damage.');
            $this->redirect('/m/purchase_damage');
        }

        $supplierId = (int)($_POST['supplier_id'] ?? 0);
        $variantId  = (int)($_POST['variant_id'] ?? 0);
        $qty        = (float)($_POST['qty'] ?? 0);
        $location   = trim($_POST['location'] ?? 'Main Store');
        $reason     = trim($_POST['reason'] ?? '');
        $action     = trim($_POST['action_taken'] ?? 'Debit Note / Return to Supplier');

        if (!$supplierId || !$variantId || $qty <= 0) {
            $this->flash('error', 'Supplier, Variant and valid quantity are required.');
            $this->redirect('/m/purchase_damage');
        }

        DB::beginTransaction();
        try {
            $damageNo = NumberSeries::getNext($cid, 'DAMAGE');

            DB::query(
                "INSERT INTO purchase_damages (concern_id, damage_no, supplier_id, variant_id, qty, location, reason, action_taken, created_by, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
                [$cid, $damageNo, $supplierId, $variantId, $qty, $location, $reason, $action, Auth::id()]
            );
            $damageId = (int)DB::lastInsertId();

            // Deduct damaged quantity from live stock via StockService
            StockService::move(
                $cid,
                $variantId,
                $location,
                0.00,
                $qty,
                'Purchase Damage',
                $damageId,
                "Supplier Damage {$damageNo}: {$reason}",
                Auth::id()
            );

            DB::commit();

            $this->flash('success', "Purchase Damage '{$damageNo}' recorded and {$qty} units deducted from stock.");
        } catch (Exception $e) {
            DB::rollBack();
            $this->flash('error', 'Failed to record damage: ' . $e->getMessage());
        }

        $this->redirect('/m/purchase_damage');
    }

    public function updateDamage(): void {
        $this->validateCsrf();
        $id = (int)($_POST['id'] ?? 0);
        $dmg = DB::fetch("SELECT * FROM purchase_damages WHERE id = ?", [$id]);
        if (!$dmg) {
            $this->flash('error', 'Purchase damage record not found.');
            $this->redirect('/m/purchase_damage');
        }

        $supplierId  = !empty($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : $dmg['supplier_id'];
        $location    = trim($_POST['location'] ?? $dmg['location']);
        $reason      = trim($_POST['reason'] ?? $dmg['reason']);
        $actionTaken = trim($_POST['action_taken'] ?? $dmg['action_taken']);
        $newQty      = (float)($_POST['qty'] ?? $dmg['qty']);

        DB::beginTransaction();
        try {
            $diff = $newQty - (float)$dmg['qty'];
            if ($diff != 0) {
                if ($diff > 0) {
                    StockService::move($dmg['concern_id'], $dmg['variant_id'], $location, 0.00, $diff, 'Purchase Damage', $id, "Updated Purchase Damage [{$dmg['damage_no']}]: Extra deduction", Auth::id());
                } else {
                    StockService::move($dmg['concern_id'], $dmg['variant_id'], $location, abs($diff), 0.00, 'Purchase Damage', $id, "Updated Purchase Damage [{$dmg['damage_no']}]: Restored partial stock", Auth::id());
                }
            }

            DB::query(
                "UPDATE purchase_damages SET supplier_id = ?, qty = ?, location = ?, reason = ?, action_taken = ? WHERE id = ?",
                [$supplierId, $newQty, $location, $reason, $actionTaken, $id]
            );

            DB::commit();
            $this->flash('success', "Purchase damage '{$dmg['damage_no']}' updated successfully.");
        } catch (Exception $e) {
            DB::rollBack();
            $this->flash('error', 'Failed to update purchase damage: ' . $e->getMessage());
        }

        $this->redirect('/m/purchase_damage');
    }

    public function deleteDamage(): void {
        $this->validateCsrf();
        $id = (int)($_POST['id'] ?? 0);
        $dmg = DB::fetch("SELECT * FROM purchase_damages WHERE id = ?", [$id]);
        if (!$dmg) {
            $this->flash('error', 'Purchase damage record not found.');
            $this->redirect('/m/purchase_damage');
        }

        DB::beginTransaction();
        try {
            // Restore deducted stock
            StockService::move(
                $dmg['concern_id'],
                $dmg['variant_id'],
                $dmg['location'],
                (float)$dmg['qty'],
                0.00,
                'Purchase Damage',
                $id,
                "Reversal of deleted Purchase Damage [{$dmg['damage_no']}]",
                Auth::id()
            );

            DB::query("DELETE FROM purchase_damages WHERE id = ?", [$id]);
            DB::commit();
            $this->flash('success', "Purchase damage '{$dmg['damage_no']}' deleted and {$dmg['qty']} units restored to stock.");
        } catch (Exception $e) {
            DB::rollBack();
            $this->flash('error', 'Failed to delete purchase damage: ' . $e->getMessage());
        }

        $this->redirect('/m/purchase_damage');
    }

    public function exportSuppliers(): void {
        $cid = Auth::concernId();
        $format = strtolower($_GET['format'] ?? 'excel');
        $period = $_GET['period'] ?? 'all';
        $singleDate = $_GET['single_date'] ?? null;
        $fromDate = $_GET['from_date'] ?? null;
        $toDate = $_GET['to_date'] ?? null;

        $dateScope = \App\Services\ExportService::resolveDateRange($period, $singleDate, $fromDate, $toDate);

        $sql = "SELECT s.*, con.name AS concern_name, con.code AS concern_code
                FROM suppliers s
                LEFT JOIN concerns con ON s.concern_id = con.id";
        $params = [];
        $where = [];
        if ($cid) {
            $where[] = "s.concern_id = ?";
            $params[] = $cid;
        }
        if ($dateScope['start_date'] && $dateScope['end_date']) {
            $where[] = "DATE(s.created_at) BETWEEN ? AND ?";
            $params[] = $dateScope['start_date'];
            $params[] = $dateScope['end_date'];
        }
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        $sql .= " ORDER BY s.id DESC";
        $suppliers = DB::fetchAll($sql, $params);

        $headers = [
            'ID', 'Company / Mill Name', 'Contact Person', 'Phone', 'Email', 
            'GSTIN', 'District', 'State', 'Opening Bal (₹)', 'Current Bal (₹)', 'Status', 'Concern'
        ];

        $rows = [];
        $totOpening = 0;
        $totCurrent = 0;
        foreach ($suppliers as $s) {
            $totOpening += (float)($s['opening_balance'] ?? 0);
            $totCurrent += (float)($s['current_balance'] ?? 0);
            $rows[] = [
                'SUP-' . str_pad($s['id'], 4, '0', STR_PAD_LEFT),
                $s['company_name'] ?? $s['name'],
                $s['name'],
                $s['phone'] ?? '-',
                $s['email'] ?? '-',
                $s['gstin'] ?? '-',
                $s['district'] ?? '-',
                $s['state'] ?? 'Tamil Nadu',
                '₹' . number_format((float)($s['opening_balance'] ?? 0), 2),
                '₹' . number_format((float)($s['current_balance'] ?? 0), 2),
                ucfirst($s['status'] ?? 'Active'),
                $s['concern_name'] ?? 'Consolidated'
            ];
        }

        $totals = ['Total Suppliers: ' . count($suppliers), '', '', '', '', '', '', '', '₹' . number_format($totOpening, 2), '₹' . number_format($totCurrent, 2), '', ''];
        $metadata = [
            'Report Title' => 'Registered Suppliers & Vendor Master Directory',
            'Scope / Period' => $dateScope['label'],
            'Generated At' => date('d M Y, h:i A'),
            'Total Vendors' => count($suppliers),
            'Total Outstanding' => '₹' . number_format($totCurrent, 2)
        ];

        $filename = 'suppliers_master_' . date('Ymd_His');

        if ($format === 'pdf') {
            \App\Services\ExportService::renderPdfView('Supplier & Vendor Directory Report', $metadata, $headers, $rows, $totals);
        } elseif ($format === 'csv') {
            \App\Services\ExportService::downloadCsv($filename, $headers, $rows, $totals);
        } else {
            \App\Services\ExportService::downloadExcel($filename, 'Supplier & Vendor Directory Report', $metadata, $headers, $rows, $totals);
        }
    }

    public function exportBills(): void {
        $cid = Auth::concernId();
        $format = strtolower($_GET['format'] ?? 'excel');
        $period = $_GET['period'] ?? 'all';
        $singleDate = $_GET['single_date'] ?? null;
        $fromDate = $_GET['from_date'] ?? null;
        $toDate = $_GET['to_date'] ?? null;

        $dateScope = \App\Services\ExportService::resolveDateRange($period, $singleDate, $fromDate, $toDate);

        $sql = "SELECT pb.*, s.name AS supplier_name, s.company_name, con.name AS concern_name, con.code AS concern_code
                FROM purchase_bills pb
                JOIN suppliers s ON pb.supplier_id = s.id
                LEFT JOIN concerns con ON pb.concern_id = con.id";
        $params = [];
        $where = [];
        if ($cid) {
            $where[] = "pb.concern_id = ?";
            $params[] = $cid;
        }
        if ($dateScope['start_date'] && $dateScope['end_date']) {
            $where[] = "DATE(pb.bill_date) BETWEEN ? AND ?";
            $params[] = $dateScope['start_date'];
            $params[] = $dateScope['end_date'];
        }
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        $sql .= " ORDER BY pb.id DESC";
        $bills = DB::fetchAll($sql, $params);

        $headers = [
            'Bill No', 'Date', 'Supplier / Mill', 'Invoice No', 'Terms', 
            'Subtotal (₹)', 'Tax (₹)', 'Grand Total (₹)', 'Paid (₹)', 'Balance (₹)', 'Status', 'Concern'
        ];

        $rows = [];
        $totSub = 0; $totTax = 0; $totGrand = 0; $totPaid = 0; $totBal = 0;
        foreach ($bills as $b) {
            $sub = (float)($b['total_amount'] ?? 0);
            $tax = (float)($b['tax_amount'] ?? 0);
            $grand = (float)($b['grand_total'] ?? 0);
            $paid = (float)($b['paid_amount'] ?? 0);
            $bal = (float)($b['balance_amount'] ?? ($grand - $paid));

            $totSub += $sub; $totTax += $tax; $totGrand += $grand; $totPaid += $paid; $totBal += $bal;

            $rows[] = [
                $b['bill_no'],
                date('d M Y', strtotime($b['bill_date'])),
                $b['company_name'] ?: $b['supplier_name'],
                $b['invoice_no'] ?: '-',
                $b['payment_terms'] ?: 'Net 30',
                '₹' . number_format($sub, 2),
                '₹' . number_format($tax, 2),
                '₹' . number_format($grand, 2),
                '₹' . number_format($paid, 2),
                '₹' . number_format($bal, 2),
                strtoupper($b['status'] ?? 'PENDING'),
                $b['concern_name'] ?? 'Consolidated'
            ];
        }

        $totals = [
            'Total Bills: ' . count($bills), '', '', '', '',
            '₹' . number_format($totSub, 2),
            '₹' . number_format($totTax, 2),
            '₹' . number_format($totGrand, 2),
            '₹' . number_format($totPaid, 2),
            '₹' . number_format($totBal, 2),
            '', ''
        ];

        $metadata = [
            'Report Title' => 'Purchase Billing Register & Invoices',
            'Scope / Period' => $dateScope['label'],
            'Generated At' => date('d M Y, h:i A'),
            'Total Bills' => count($bills),
            'Grand Total' => '₹' . number_format($totGrand, 2),
            'Total Balance Due' => '₹' . number_format($totBal, 2)
        ];

        $filename = 'purchase_bills_' . date('Ymd_His');

        if ($format === 'pdf') {
            \App\Services\ExportService::renderPdfView('Purchase Invoices & Billing Register', $metadata, $headers, $rows, $totals);
        } elseif ($format === 'csv') {
            \App\Services\ExportService::downloadCsv($filename, $headers, $rows, $totals);
        } else {
            \App\Services\ExportService::downloadExcel($filename, 'Purchase Invoices & Billing Register', $metadata, $headers, $rows, $totals);
        }
    }

    public function exportDamage(): void {
        $cid = Auth::concernId();
        $format = strtolower($_GET['format'] ?? 'excel');
        $period = $_GET['period'] ?? 'all';
        $singleDate = $_GET['single_date'] ?? null;
        $fromDate = $_GET['from_date'] ?? null;
        $toDate = $_GET['to_date'] ?? null;

        $dateScope = \App\Services\ExportService::resolveDateRange($period, $singleDate, $fromDate, $toDate);

        $sql = "SELECT pd.*, s.name AS supplier_name, s.company_name, v.name AS variant_name, v.sku_code,
                       con.name AS concern_name, con.code AS concern_code
                FROM purchase_damages pd
                JOIN suppliers s ON pd.supplier_id = s.id
                JOIN variants v ON pd.variant_id = v.id
                LEFT JOIN concerns con ON pd.concern_id = con.id";
        $params = [];
        $where = [];
        if ($cid) {
            $where[] = "pd.concern_id = ?";
            $params[] = $cid;
        }
        if ($dateScope['start_date'] && $dateScope['end_date']) {
            $where[] = "DATE(pd.created_at) BETWEEN ? AND ?";
            $params[] = $dateScope['start_date'];
            $params[] = $dateScope['end_date'];
        }
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        $sql .= " ORDER BY pd.id DESC";
        $damages = DB::fetchAll($sql, $params);

        $headers = [
            'Damage No', 'Date', 'Supplier', 'SKU Code', 'Variant / Item', 
            'Damaged Qty', 'Location', 'Damage Reason', 'Action Taken', 'Concern'
        ];

        $rows = [];
        $totQty = 0;
        foreach ($damages as $d) {
            $totQty += (float)$d['qty'];
            $rows[] = [
                $d['damage_no'],
                date('d M Y', strtotime($d['created_at'])),
                $d['company_name'] ?: $d['supplier_name'],
                $d['sku_code'],
                $d['variant_name'],
                (float)$d['qty'],
                $d['location'] ?? 'Receiving Bay',
                $d['reason'] ?? '-',
                $d['action_taken'] ?? '-',
                $d['concern_name'] ?? 'Consolidated'
            ];
        }

        $totals = ['Total Records: ' . count($damages), '', '', '', '', $totQty, '', '', '', ''];
        $metadata = [
            'Report Title' => 'Purchase Inbound Damage & Supplier Rejections',
            'Scope / Period' => $dateScope['label'],
            'Generated At' => date('d M Y, h:i A'),
            'Total Incident Records' => count($damages),
            'Total Damaged Units' => number_format($totQty, 2)
        ];

        $filename = 'purchase_damages_' . date('Ymd_His');

        if ($format === 'pdf') {
            \App\Services\ExportService::renderPdfView('Purchase Damage & Rejection Audit Report', $metadata, $headers, $rows, $totals);
        } elseif ($format === 'csv') {
            \App\Services\ExportService::downloadCsv($filename, $headers, $rows, $totals);
        } else {
            \App\Services\ExportService::downloadExcel($filename, 'Purchase Damage & Rejection Audit Report', $metadata, $headers, $rows, $totals);
        }
    }
}
