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
        $suppliers = DB::fetchAll("SELECT * FROM suppliers WHERE concern_id = ? ORDER BY id DESC", [$cid]);

        $this->render('concern/suppliers', [
            'pageTitle'     => 'Supplier Master',
            'currentModule' => 'suppliers',
            'suppliers'     => $suppliers,
        ]);
    }

    public function storeSupplier(): void {
        $this->validateCsrf();
        $cid = Auth::concernId();

        $name    = trim($_POST['name'] ?? '');
        $company = trim($_POST['company_name'] ?? '');
        $phone   = trim($_POST['phone'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $gstin   = trim($_POST['gstin'] ?? '');
        $address = trim($_POST['address'] ?? '');

        if (empty($name)) {
            $this->flash('error', 'Supplier name is required.');
            $this->redirect('/m/suppliers');
        }

        $code = 'SUP-' . str_pad((string)(DB::fetchValue("SELECT COUNT(*) FROM suppliers WHERE concern_id = ?", [$cid]) + 1), 3, '0', STR_PAD_LEFT);

        DB::query(
            "INSERT INTO suppliers (concern_id, code, name, company_name, phone, email, gstin, address, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')",
            [$cid, $code, $name, $company, $phone, $email, $gstin, $address]
        );

        $this->flash('success', "Supplier '{$name}' registered successfully with code {$code}!");
        $this->redirect('/m/suppliers');
    }

    // 2. PURCHASE BILLING
    public function billing(): void {
        $cid = Auth::concernId();

        $bills = DB::fetchAll(
            "SELECT pb.*, s.name AS supplier_name,
                    (SELECT COUNT(*) FROM purchase_bill_items pbi WHERE pbi.purchase_bill_id = pb.id) AS items_count
             FROM purchase_bills pb
             JOIN suppliers s ON pb.supplier_id = s.id
             WHERE pb.concern_id = ?
             ORDER BY pb.id DESC",
            [$cid]
        );

        $suppliers = DB::fetchAll("SELECT id, name FROM suppliers WHERE concern_id = ? AND status = 'active'", [$cid]);
        $variants  = DB::fetchAll("SELECT id, sku_code, name, price, size FROM variants WHERE concern_id = ? AND status = 'active'", [$cid]);

        $this->render('concern/purchase_billing', [
            'pageTitle'     => 'Purchase Billing (Invoices)',
            'currentModule' => 'purchase_billing',
            'bills'         => $bills,
            'suppliers'     => $suppliers,
            'variants'      => $variants,
        ]);
    }

    public function storeBill(): void {
        $this->validateCsrf();
        $cid = Auth::concernId();

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

        $damages = DB::fetchAll(
            "SELECT pd.*, s.name AS supplier_name, v.name AS variant_name, v.sku_code
             FROM purchase_damages pd
             JOIN suppliers s ON pd.supplier_id = s.id
             JOIN variants v ON pd.variant_id = v.id
             WHERE pd.concern_id = ?
             ORDER BY pd.id DESC",
            [$cid]
        );

        $suppliers = DB::fetchAll("SELECT id, name FROM suppliers WHERE concern_id = ? AND status = 'active'", [$cid]);
        $variants  = DB::fetchAll("SELECT id, sku_code, name FROM variants WHERE concern_id = ? AND status = 'active'", [$cid]);

        $this->render('concern/purchase_damage', [
            'pageTitle'     => 'Purchase Damage & Rejections',
            'currentModule' => 'purchase_damage',
            'damages'       => $damages,
            'suppliers'     => $suppliers,
            'variants'      => $variants,
        ]);
    }

    public function storeDamage(): void {
        $this->validateCsrf();
        $cid = Auth::concernId();

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
