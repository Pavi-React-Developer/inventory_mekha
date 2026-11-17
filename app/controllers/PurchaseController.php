<?php
// app/controllers/PurchaseController.php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;
use App\Core\Auth;
use App\Services\StockService;
use App\Services\NumberSeries;
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
            $suppliers = DB::fetchAll("SELECT id, name FROM suppliers WHERE concern_id = ? AND status = 'active'", [$cid]);
            $variants  = DB::fetchAll("SELECT id, sku_code, name, price, size FROM variants WHERE concern_id = ? AND status = 'active'", [$cid]);
        } else {
            $suppliers = DB::fetchAll("SELECT id, name FROM suppliers WHERE status = 'active'");
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

    public function storeBill(): void {
        $this->validateCsrf();
        $cid = Auth::concernId() ?: (int)($_POST['concern_id'] ?? 0);

        if (!$cid) {
            $this->flash('error', 'Please choose a concern for this purchase bill.');
            $this->redirect('/m/purchase_billing');
        }

        $supplierId = (int)($_POST['supplier_id'] ?? 0);
        $billDate   = $_POST['bill_date'] ?: date('Y-m-d');
        $variantId  = (int)($_POST['variant_id'] ?? 0);
        $qty        = (float)($_POST['qty'] ?? 0);
        $unitPrice  = (float)($_POST['unit_price'] ?? 0);
        $notes      = trim($_POST['notes'] ?? '');

        if (!$supplierId || !$variantId || $qty <= 0 || $unitPrice <= 0) {
            $this->flash('error', 'Supplier, Variant, Quantity, and Unit Price are required.');
            $this->redirect('/m/purchase_billing');
        }

        $totalAmount = $qty * $unitPrice;
        $taxAmount   = $totalAmount * 0.05; // 5% GST
        $netAmount   = $totalAmount + $taxAmount;

        DB::beginTransaction();
        try {
            $billNo = NumberSeries::getNext($cid, 'PO');

            DB::query(
                "INSERT INTO purchase_bills (concern_id, bill_no, supplier_id, bill_date, total_amount, tax_amount, net_amount, payment_status, inward_status, notes, created_by, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'unpaid', 'pending', ?, ?, NOW())",
                [$cid, $billNo, $supplierId, $billDate, $totalAmount, $taxAmount, $netAmount, $notes, Auth::id()]
            );
            $billId = (int)DB::lastInsertId();

            DB::query(
                "INSERT INTO purchase_bill_items (concern_id, purchase_bill_id, variant_id, qty, unit_price, total_price)
                 VALUES (?, ?, ?, ?, ?, ?)",
                [$cid, $billId, $variantId, $qty, $unitPrice, $totalAmount]
            );

            DB::commit();

            $this->flash('success', "Purchase Bill '{$billNo}' created for ₹" . number_format($netAmount, 2) . ".");
        } catch (Exception $e) {
            DB::rollBack();
            $this->flash('error', 'Error generating purchase bill: ' . $e->getMessage());
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
}
