<?php
// app/controllers/ProductionController.php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;
use App\Core\Auth;
use App\Services\StockService;
use Exception;

class ProductionController extends Controller {
    // 1. CATALOG & PROGRAM
    public function catalogProgram(): void {
        $cid = Auth::concernId();

        $sql = "SELECT pp.*, c.name AS catalog_name, v.name AS target_variant_name, v.sku_code,
                       con.name AS concern_name, con.code AS concern_code
                FROM production_programs pp
                LEFT JOIN catalogs c ON pp.catalog_id = c.id
                JOIN variants v ON pp.target_variant_id = v.id
                LEFT JOIN concerns con ON pp.concern_id = con.id";
        $params = [];
        if ($cid) {
            $sql .= " WHERE pp.concern_id = ?";
            $params[] = $cid;
        }
        $sql .= " ORDER BY pp.id DESC";
        $programs = DB::fetchAll($sql, $params);

        if ($cid) {
            $catalogs = DB::fetchAll("SELECT * FROM catalogs WHERE concern_id = ?", [$cid]);
            $variants = DB::fetchAll("SELECT id, sku_code, name FROM variants WHERE concern_id = ? AND status = 'active'", [$cid]);
        } else {
            $catalogs = DB::fetchAll("SELECT * FROM catalogs");
            $variants = DB::fetchAll("SELECT id, sku_code, name FROM variants WHERE status = 'active'");
        }

        $this->render('concern/catalog_program', [
            'pageTitle'     => 'Production Catalog & Scheduling Programs',
            'currentModule' => 'catalog_program',
            'programs'      => $programs,
            'catalogs'      => $catalogs,
            'variants'      => $variants,
            'selectedCid'   => $cid,
        ]);
    }

    public function storeProgram(): void {
        $this->validateCsrf();
        $cid = Auth::concernId() ?: (int)($_POST['concern_id'] ?? 0);

        if (!$cid) {
            $this->flash('error', 'Please choose a concern.');
            $this->redirect('/m/catalog_program');
        }

        $targetVarId = (int)($_POST['target_variant_id'] ?? 0);
        $plannedQty  = (float)($_POST['planned_qty'] ?? 0);
        $startDate   = $_POST['start_date'] ?: date('Y-m-d');
        $targetDate  = $_POST['target_date'] ?: date('Y-m-d', strtotime('+14 days'));
        $catName     = trim($_POST['catalog_name'] ?? 'Production Season 2026');

        if (!$targetVarId || $plannedQty <= 0) {
            $this->flash('error', 'Target Variant and Planned Quantity are required.');
            $this->redirect('/m/catalog_program');
        }

        DB::beginTransaction();
        try {
            // Find or create catalog
            $catalog = DB::fetch("SELECT id FROM catalogs WHERE concern_id = ? AND name = ?", [$cid, $catName]);
            if ($catalog) {
                $catalogId = $catalog['id'];
            } else {
                DB::query("INSERT INTO catalogs (concern_id, code, name) VALUES (?, 'CAT-GEN', ?)", [$cid, $catName]);
                $catalogId = (int)DB::lastInsertId();
            }

            $progNo = 'PRG-' . date('Ymd') . '-' . rand(100, 999);

            DB::query(
                "INSERT INTO production_programs (concern_id, program_no, catalog_id, target_variant_id, planned_qty, start_date, target_date, status, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'in_progress', NOW())",
                [$cid, $progNo, $catalogId, $targetVarId, $plannedQty, $startDate, $targetDate]
            );

            DB::commit();

            $this->flash('success', "Production Program '{$progNo}' scheduled for {$plannedQty} units.");
        } catch (Exception $e) {
            DB::rollBack();
            $this->flash('error', 'Failed to create program: ' . $e->getMessage());
        }

        $this->redirect('/m/catalog_program');
    }

    // 2. CUTTING & WASTAGE
    public function cutting(): void {
        $cid = Auth::concernId();

        $sql = "SELECT cl.*, pp.program_no, v.name AS raw_variant_name, v.sku_code,
                       con.name AS concern_name, con.code AS concern_code
                FROM cutting_logs cl
                LEFT JOIN production_programs pp ON cl.program_id = pp.id
                JOIN variants v ON cl.raw_variant_id = v.id
                LEFT JOIN concerns con ON cl.concern_id = con.id";
        $params = [];
        if ($cid) {
            $sql .= " WHERE cl.concern_id = ?";
            $params[] = $cid;
        }
        $sql .= " ORDER BY cl.id DESC";
        $logs = DB::fetchAll($sql, $params);

        if ($cid) {
            $programs = DB::fetchAll("SELECT id, program_no FROM production_programs WHERE concern_id = ? AND status != 'cancelled'", [$cid]);
            $rawVariants = DB::fetchAll("SELECT id, sku_code, name, weight_unit FROM variants WHERE concern_id = ? AND status = 'active'", [$cid]);
        } else {
            $programs = DB::fetchAll("SELECT id, program_no FROM production_programs WHERE status != 'cancelled'");
            $rawVariants = DB::fetchAll("SELECT id, sku_code, name, weight_unit FROM variants WHERE status = 'active'");
        }

        $this->render('concern/cutting_wastage', [
            'pageTitle'     => 'Fabric Cutting & Wastage Tracking',
            'currentModule' => 'cutting_wastage',
            'logs'          => $logs,
            'programs'      => $programs,
            'rawVariants'   => $rawVariants,
            'selectedCid'   => $cid,
        ]);
    }

    public function storeCutting(): void {
        $this->validateCsrf();
        $cid = Auth::concernId() ?: (int)($_POST['concern_id'] ?? 0);

        if (!$cid) {
            $this->flash('error', 'Please choose a concern.');
            $this->redirect('/m/cutting_wastage');
        }

        $progId      = !empty($_POST['program_id']) ? (int)$_POST['program_id'] : null;
        $rawVarId    = (int)($_POST['raw_variant_id'] ?? 0);
        $consumedQty = (float)($_POST['consumed_qty'] ?? 0);
        $cutPieces   = (int)($_POST['cut_pieces'] ?? 0);
        $wastageQty  = (float)($_POST['wastage_qty'] ?? 0);
        $notes       = trim($_POST['notes'] ?? '');

        if (!$rawVarId || $consumedQty <= 0 || $cutPieces <= 0) {
            $this->flash('error', 'Raw Variant, Consumed Quantity, and Cut Pieces count are required.');
            $this->redirect('/m/cutting_wastage');
        }

        DB::beginTransaction();
        try {
            $logNo = 'CUT-' . date('Ymd') . '-' . rand(100, 999);

            DB::query(
                "INSERT INTO cutting_logs (concern_id, log_no, program_id, raw_variant_id, consumed_qty, cut_pieces, wastage_qty, wastage_unit, notes, created_by, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'kg', ?, ?, NOW())",
                [$cid, $logNo, $progId, $rawVarId, $consumedQty, $cutPieces, $wastageQty, $notes, Auth::id()]
            );
            $cutId = (int)DB::lastInsertId();

            // Single stock gateway: Consumes raw fabric variant from stocks
            StockService::move(
                $cid,
                $rawVarId,
                'Main Store',
                0.00,
                $consumedQty,
                'Cutting & Wastage',
                $cutId,
                "Cutting job {$logNo}: consumed for {$cutPieces} cut pieces",
                Auth::id()
            );

            DB::commit();

            $this->flash('success', "Cutting Log '{$logNo}' recorded. {$consumedQty} units consumed, {$cutPieces} cut panels ready.");
        } catch (Exception $e) {
            DB::rollBack();
            $this->flash('error', 'Cutting operation failed: ' . $e->getMessage());
        }

        $this->redirect('/m/cutting_wastage');
    }

    // 3. STITCHING & WASTAGE
    public function stitching(): void {
        $cid = Auth::concernId();

        $sql = "SELECT sl.*, pp.program_no, v.name AS produced_variant_name, v.sku_code,
                       con.name AS concern_name, con.code AS concern_code
                FROM stitching_logs sl
                LEFT JOIN production_programs pp ON sl.program_id = pp.id
                JOIN variants v ON sl.produced_variant_id = v.id
                LEFT JOIN concerns con ON sl.concern_id = con.id";
        $params = [];
        if ($cid) {
            $sql .= " WHERE sl.concern_id = ?";
            $params[] = $cid;
        }
        $sql .= " ORDER BY sl.id DESC";
        $logs = DB::fetchAll($sql, $params);

        if ($cid) {
            $programs = DB::fetchAll("SELECT id, program_no FROM production_programs WHERE concern_id = ?", [$cid]);
            $variants = DB::fetchAll("SELECT id, sku_code, name, size FROM variants WHERE concern_id = ? AND status = 'active'", [$cid]);
        } else {
            $programs = DB::fetchAll("SELECT id, program_no FROM production_programs");
            $variants = DB::fetchAll("SELECT id, sku_code, name, size FROM variants WHERE status = 'active'");
        }

        $this->render('concern/stitching_wastage', [
            'pageTitle'     => 'Stitching & Production Wastage',
            'currentModule' => 'stitching_wastage',
            'logs'          => $logs,
            'programs'      => $programs,
            'variants'      => $variants,
            'selectedCid'   => $cid,
        ]);
    }

    public function storeStitching(): void {
        $this->validateCsrf();
        $cid = Auth::concernId() ?: (int)($_POST['concern_id'] ?? 0);

        if (!$cid) {
            $this->flash('error', 'Please choose a concern.');
            $this->redirect('/m/stitching_wastage');
        }

        $progId        = !empty($_POST['program_id']) ? (int)$_POST['program_id'] : null;
        $producedVarId = (int)($_POST['produced_variant_id'] ?? 0);
        $inputPieces   = (int)($_POST['input_pieces'] ?? 0);
        $stitchedPcs   = (int)($_POST['stitched_pieces'] ?? 0);
        $wastagePcs    = (int)($_POST['wastage_pieces'] ?? 0);
        $notes         = trim($_POST['notes'] ?? '');

        if (!$producedVarId || $inputPieces <= 0 || $stitchedPcs <= 0) {
            $this->flash('error', 'Finished Variant, Input Panels, and Stitched count are required.');
            $this->redirect('/m/stitching_wastage');
        }

        $logNo = 'STT-' . date('Ymd') . '-' . rand(100, 999);

        DB::query(
            "INSERT INTO stitching_logs (concern_id, log_no, program_id, input_pieces, stitched_pieces, wastage_pieces, produced_variant_id, notes, created_by, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
            [$cid, $logNo, $progId, $inputPieces, $stitchedPcs, $wastagePcs, $producedVarId, $notes, Auth::id()]
        );

        $this->flash('success', "Stitching record '{$logNo}' saved. {$stitchedPcs} garments forwarded to QC Inspection.");
        $this->redirect('/m/stitching_wastage');
    }

    // 4. QC STOCKS
    public function qc(): void {
        $cid = Auth::concernId();

        $sql = "SELECT qc.*, v.name AS variant_name, v.sku_code, v.size, u.name AS inspector_name,
                       con.name AS concern_name, con.code AS concern_code
                FROM qc_inspections qc
                JOIN variants v ON qc.variant_id = v.id
                LEFT JOIN concerns con ON qc.concern_id = con.id
                LEFT JOIN users u ON qc.inspector_id = u.id";
        $params = [];
        if ($cid) {
            $sql .= " WHERE qc.concern_id = ?";
            $params[] = $cid;
        }
        $sql .= " ORDER BY qc.id DESC";
        $inspections = DB::fetchAll($sql, $params);

        if ($cid) {
            $variants = DB::fetchAll("SELECT id, sku_code, name, size FROM variants WHERE concern_id = ? AND status = 'active'", [$cid]);
        } else {
            $variants = DB::fetchAll("SELECT id, sku_code, name, size FROM variants WHERE status = 'active'");
        }

        $this->render('concern/qc_stocks', [
            'pageTitle'     => 'Quality Control & Stock Approval',
            'currentModule' => 'qc_stocks',
            'inspections'   => $inspections,
            'variants'      => $variants,
            'selectedCid'   => $cid,
        ]);
    }

    public function storeQc(): void {
        $this->validateCsrf();
        $cid = Auth::concernId() ?: (int)($_POST['concern_id'] ?? 0);

        if (!$cid) {
            $this->flash('error', 'Please choose a concern.');
            $this->redirect('/m/qc_stocks');
        }

        $variantId    = (int)($_POST['variant_id'] ?? 0);
        $inspectedQty = (float)($_POST['inspected_qty'] ?? 0);
        $passedQty    = (float)($_POST['passed_qty'] ?? 0);
        $rejectedQty  = (float)($_POST['rejected_qty'] ?? 0);
        $location     = trim($_POST['location'] ?? 'Main Store');
        $defectNotes  = trim($_POST['defect_notes'] ?? '');

        if (!$variantId || $inspectedQty <= 0 || $passedQty < 0) {
            $this->flash('error', 'Variant, Inspected Qty, and Passed Qty are required.');
            $this->redirect('/m/qc_stocks');
        }

        DB::beginTransaction();
        try {
            $qcNo = 'QC-' . date('Ymd') . '-' . rand(100, 999);

            DB::query(
                "INSERT INTO qc_inspections (concern_id, qc_no, variant_id, inspected_qty, passed_qty, rejected_qty, location, defect_notes, inspector_id, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
                [$cid, $qcNo, $variantId, $inspectedQty, $passedQty, $rejectedQty, $location, $defectNotes, Auth::id()]
            );
            $qcId = (int)DB::lastInsertId();

            // Single stock gateway: QC passed units move directly into Stocks!
            if ($passedQty > 0) {
                StockService::move(
                    $cid,
                    $variantId,
                    $location,
                    $passedQty,
                    0.00,
                    'QC Stocks',
                    $qcId,
                    "QC Inspection Passed [{$qcNo}]: {$passedQty} units approved",
                    Auth::id()
                );
            }

            DB::commit();

            $this->flash('success', "QC Inspection '{$qcNo}' approved! {$passedQty} units moved to live stock.");
        } catch (Exception $e) {
            DB::rollBack();
            $this->flash('error', 'QC processing error: ' . $e->getMessage());
        }

        $this->redirect('/m/qc_stocks');
    }
}
