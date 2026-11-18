<?php
// app/controllers/InwardController.php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;
use App\Core\Auth;
use App\Services\StockService;
use App\Services\NumberSeries;
use Exception;

class InwardController extends Controller {
    public function index(): void {
        $cid = Auth::concernId();

        $sql = "SELECT i.*, s.name AS supplier_name, pb.bill_no, u.name AS user_name,
                       con.name AS concern_name, con.code AS concern_code,
                       (SELECT COUNT(*) FROM inward_items ii WHERE ii.inward_id = i.id) AS items_count,
                       (SELECT COALESCE(SUM(qty), 0) FROM inward_items ii WHERE ii.inward_id = i.id) AS total_qty
                FROM inwards i
                LEFT JOIN concerns con ON i.concern_id = con.id
                LEFT JOIN suppliers s ON i.supplier_id = s.id
                LEFT JOIN purchase_bills pb ON i.purchase_bill_id = pb.id
                LEFT JOIN users u ON i.created_by = u.id";
        
        $params = [];
        if ($cid) {
            $sql .= " WHERE i.concern_id = ?";
            $params[] = $cid;
        }
        $sql .= " ORDER BY i.id DESC";
        $inwards = DB::fetchAll($sql, $params);

        if ($cid) {
            $suppliers = DB::fetchAll("SELECT id, name FROM suppliers WHERE concern_id = ? AND status = 'active'", [$cid]);
            $variants  = DB::fetchAll("SELECT id, sku_code, name, size, weight_unit FROM variants WHERE concern_id = ? AND status = 'active'", [$cid]);
        } else {
            $suppliers = DB::fetchAll("SELECT id, name FROM suppliers WHERE status = 'active'");
            $variants  = DB::fetchAll("SELECT id, sku_code, name, size, weight_unit FROM variants WHERE status = 'active'");
        }

        $this->render('concern/inward', [
            'pageTitle'     => 'Inward Stock Consignments',
            'currentModule' => 'inward',
            'inwards'       => $inwards,
            'suppliers'     => $suppliers,
            'variants'      => $variants,
            'selectedCid'   => $cid,
        ]);
    }

    public function store(): void {
        $this->validateCsrf();
        $cid = Auth::concernId() ?: (int)($_POST['concern_id'] ?? 0);

        if (!$cid) {
            $this->flash('error', 'Please select a concern for this inward consignment.');
            $this->redirect('/m/inward');
        }

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

    public function update(): void {
        $this->validateCsrf();
        $id = (int)($_POST['id'] ?? 0);
        $inward = DB::fetch("SELECT * FROM inwards WHERE id = ?", [$id]);
        if (!$inward) {
            $this->flash('error', 'Inward record not found.');
            $this->redirect('/m/inward');
        }

        $supplierId = !empty($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : $inward['supplier_id'];
        $location   = trim($_POST['location'] ?? $inward['location']);
        $remarks    = trim($_POST['remarks'] ?? $inward['remarks']);
        $inwardDate = $_POST['inward_date'] ?: $inward['inward_date'];

        DB::beginTransaction();
        try {
            DB::query(
                "UPDATE inwards SET supplier_id = ?, location = ?, remarks = ?, inward_date = ? WHERE id = ?",
                [$supplierId, $location, $remarks, $inwardDate, $id]
            );
            DB::commit();
            $this->flash('success', "Inward consignment '{$inward['inward_no']}' updated successfully.");
        } catch (Exception $e) {
            DB::rollBack();
            $this->flash('error', 'Failed to update inward: ' . $e->getMessage());
        }

        $this->redirect('/m/inward');
    }

    public function delete(): void {
        $this->validateCsrf();
        $id = (int)($_POST['id'] ?? 0);
        $inward = DB::fetch("SELECT * FROM inwards WHERE id = ?", [$id]);
        if (!$inward) {
            $this->flash('error', 'Inward record not found.');
            $this->redirect('/m/inward');
        }

        $items = DB::fetchAll("SELECT * FROM inward_items WHERE inward_id = ?", [$id]);

        DB::beginTransaction();
        try {
            // Deduct added quantities from stock
            foreach ($items as $item) {
                StockService::move(
                    $inward['concern_id'],
                    $item['variant_id'],
                    $inward['location'],
                    0.00,
                    (float)$item['qty'],
                    'Inward',
                    $id,
                    "Reversal of deleted Inward [{$inward['inward_no']}]",
                    Auth::id()
                );
            }

            DB::query("DELETE FROM inward_items WHERE inward_id = ?", [$id]);
            DB::query("DELETE FROM inwards WHERE id = ?", [$id]);

            DB::commit();
            $this->flash('success', "Inward consignment '{$inward['inward_no']}' deleted and stock reversed.");
        } catch (Exception $e) {
            DB::rollBack();
            $this->flash('error', 'Failed to delete inward: ' . $e->getMessage());
        }

        $this->redirect('/m/inward');
    }

    public function exportInward(): void {
        $cid = Auth::concernId();
        $format = strtolower($_GET['format'] ?? 'excel');
        $period = $_GET['period'] ?? 'all';
        $singleDate = $_GET['single_date'] ?? null;
        $fromDate = $_GET['from_date'] ?? null;
        $toDate = $_GET['to_date'] ?? null;

        $dateScope = \App\Services\ExportService::resolveDateRange($period, $singleDate, $fromDate, $toDate);

        $sql = "SELECT i.*, s.name AS supplier_name, pb.bill_no, u.name AS user_name,
                       con.name AS concern_name, con.code AS concern_code,
                       (SELECT COUNT(*) FROM inward_items ii WHERE ii.inward_id = i.id) AS items_count,
                       (SELECT COALESCE(SUM(qty), 0) FROM inward_items ii WHERE ii.inward_id = i.id) AS total_qty
                FROM inwards i
                LEFT JOIN concerns con ON i.concern_id = con.id
                LEFT JOIN suppliers s ON i.supplier_id = s.id
                LEFT JOIN purchase_bills pb ON i.purchase_bill_id = pb.id
                LEFT JOIN users u ON i.created_by = u.id";
        
        $params = [];
        $where = [];
        if ($cid) {
            $where[] = "i.concern_id = ?";
            $params[] = $cid;
        }
        if ($dateScope['start_date'] && $dateScope['end_date']) {
            $where[] = "DATE(i.inward_date) BETWEEN ? AND ?";
            $params[] = $dateScope['start_date'];
            $params[] = $dateScope['end_date'];
        }
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        $sql .= " ORDER BY i.id DESC";
        $inwards = DB::fetchAll($sql, $params);

        $headers = [
            'Inward No', 'Date', 'Supplier / Vendor', 'Purchase Bill Ref', 'Items Count', 
            'Total Received Qty', 'Carrier / Ref', 'Received By', 'Concern'
        ];

        $rows = [];
        $totItems = 0;
        $totQty = 0;
        foreach ($inwards as $in) {
            $totItems += (int)($in['items_count'] ?? 0);
            $totQty += (float)($in['total_qty'] ?? 0);
            $rows[] = [
                $in['inward_no'],
                date('d M Y', strtotime($in['inward_date'])),
                $in['supplier_name'] ?: 'Direct Consignment',
                $in['bill_no'] ?: '-',
                (int)($in['items_count'] ?? 0),
                (float)($in['total_qty'] ?? 0),
                $in['reference_no'] ?? '-',
                $in['user_name'] ?? 'System',
                $in['concern_name'] ?? 'Consolidated'
            ];
        }

        $totals = ['Total Consignments: ' . count($inwards), '', '', '', $totItems, $totQty, '', '', ''];
        $metadata = [
            'Report Title' => 'Inward Stock Consignments & Material Receipts',
            'Scope / Period' => $dateScope['label'],
            'Generated At' => date('d M Y, h:i A'),
            'Total Consignments' => count($inwards),
            'Total Units Received' => number_format($totQty, 2)
        ];

        $filename = 'inwards_report_' . date('Ymd_His');

        if ($format === 'pdf') {
            \App\Services\ExportService::renderPdfView('Inward Stock Consignments Report', $metadata, $headers, $rows, $totals);
        } elseif ($format === 'csv') {
            \App\Services\ExportService::downloadCsv($filename, $headers, $rows, $totals);
        } else {
            \App\Services\ExportService::downloadExcel($filename, 'Inward Stock Consignments Report', $metadata, $headers, $rows, $totals);
        }
    }
}
