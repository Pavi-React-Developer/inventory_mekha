<?php
// app/controllers/InwardController.php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;
use App\Core\Auth;
use App\Services\StockService;
use App\Services\NumberSeries;
use App\Services\AuditLog;
use Exception;

class InwardController extends Controller {
    public function index(): void {
        $cid = Auth::concernId();

        $inwards = DB::fetchAll(
            "SELECT i.*, s.name AS supplier_name, pb.bill_no, u.name AS user_name,
                    (SELECT COUNT(*) FROM inward_items ii WHERE ii.inward_id = i.id) AS items_count,
                    (SELECT COALESCE(SUM(qty), 0) FROM inward_items ii WHERE ii.inward_id = i.id) AS total_qty
             FROM inwards i
             LEFT JOIN suppliers s ON i.supplier_id = s.id
             LEFT JOIN purchase_bills pb ON i.purchase_bill_id = pb.id
             LEFT JOIN users u ON i.created_by = u.id
             WHERE i.concern_id = ?
             ORDER BY i.id DESC",
            [$cid]
        );

        $suppliers = DB::fetchAll("SELECT id, name FROM suppliers WHERE concern_id = ? AND status = 'active'", [$cid]);
        $variants  = DB::fetchAll("SELECT id, sku_code, name, size, weight_unit FROM variants WHERE concern_id = ? AND status = 'active'", [$cid]);

        $this->render('concern/inward', [
            'pageTitle'     => 'Inward Stock Consignments',
            'currentModule' => 'inward',
            'inwards'       => $inwards,
            'suppliers'     => $suppliers,
            'variants'      => $variants,
        ]);
    }

    public function store(): void {
        $this->validateCsrf();
        $cid = Auth::concernId();

        $supplierId = !empty($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : null;
        $inwardDate = $_POST['inward_date'] ?: date('Y-m-d');
        $location   = trim($_POST['location'] ?? 'Main Store');
        $remarks    = trim($_POST['remarks'] ?? '');

        $variantId = (int)($_POST['variant_id'] ?? 0);
        $qty       = (float)($_POST['qty'] ?? 0);
        $unitCost  = (float)($_POST['unit_cost'] ?? 0);

        if (!$variantId || $qty <= 0) {
            $this->flash('error', 'Please select a valid variant and specify a quantity greater than zero.');
            $this->redirect('/m/inward');
        }

        DB::beginTransaction();
        try {
            $inwardNo = NumberSeries::getNext($cid, 'INWARD');

            DB::query(
                "INSERT INTO inwards (concern_id, inward_no, supplier_id, inward_date, location, remarks, created_by, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW())",
                [$cid, $inwardNo, $supplierId, $inwardDate, $location, $remarks, Auth::id()]
            );
            $inwardId = (int)DB::lastInsertId();

            DB::query(
                "INSERT INTO inward_items (concern_id, inward_id, variant_id, qty, unit_cost) VALUES (?, ?, ?, ?, ?)",
                [$cid, $inwardId, $variantId, $qty, $unitCost]
            );

            // Single stock gateway: Inward adds quantity
            StockService::move(
                $cid,
                $variantId,
                $location,
                $qty,
                0.00,
                'Inward',
                $inwardId,
                "Inward consignment {$inwardNo}" . ($remarks ? ": {$remarks}" : ""),
                Auth::id()
            );

            DB::commit();

            $this->flash('success', "Inward consignment '{$inwardNo}' recorded successfully and {$qty} units added to live stock!");
        } catch (Exception $e) {
            DB::rollBack();
            $this->flash('error', 'Failed to record inward: ' . $e->getMessage());
        }

        $this->redirect('/m/inward');
    }
}
