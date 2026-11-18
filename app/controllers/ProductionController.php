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

    public function updateProgram(): void {
        $this->validateCsrf();
        $id = (int)($_POST['id'] ?? 0);
        $prog = DB::fetch("SELECT * FROM production_programs WHERE id = ?", [$id]);
        if (!$prog) {
            $this->flash('error', 'Program not found.');
            $this->redirect('/m/catalog_program');
        }

        $plannedQty = (float)($_POST['planned_qty'] ?? $prog['planned_qty']);
        $startDate  = $_POST['start_date'] ?: $prog['start_date'];
        $targetDate = $_POST['target_date'] ?: $prog['target_date'];
        $status     = trim($_POST['status'] ?? $prog['status']);

        try {
            DB::query(
                "UPDATE production_programs SET planned_qty = ?, start_date = ?, target_date = ?, status = ? WHERE id = ?",
                [$plannedQty, $startDate, $targetDate, $status, $id]
            );
            $this->flash('success', "Program '{$prog['program_no']}' updated successfully.");
        } catch (Exception $e) {
            $this->flash('error', 'Failed to update program: ' . $e->getMessage());
        }

        $this->redirect('/m/catalog_program');
    }

    public function deleteProgram(): void {
        $this->validateCsrf();
        $id = (int)($_POST['id'] ?? 0);
        $prog = DB::fetch("SELECT * FROM production_programs WHERE id = ?", [$id]);
        if (!$prog) {
            $this->flash('error', 'Program not found.');
            $this->redirect('/m/catalog_program');
        }

        $hasLogs = (int)DB::fetchValue("SELECT COUNT(*) FROM cutting_logs WHERE program_id = ?", [$id]);
        if ($hasLogs > 0) {
            DB::query("UPDATE production_programs SET status = 'cancelled' WHERE id = ?", [$id]);
            $this->flash('warning', "Program '{$prog['program_no']}' has active cutting logs, so it has been marked as Cancelled.");
        } else {
            DB::query("DELETE FROM production_programs WHERE id = ?", [$id]);
            $this->flash('success', "Program '{$prog['program_no']}' deleted successfully.");
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

    public function updateCutting(): void {
        $this->validateCsrf();
        $id = (int)($_POST['id'] ?? 0);
        $cut = DB::fetch("SELECT * FROM cutting_logs WHERE id = ?", [$id]);
        if (!$cut) {
            $this->flash('error', 'Cutting log not found.');
            $this->redirect('/m/cutting_wastage');
        }

        $cutPieces  = (int)($_POST['cut_pieces'] ?? $cut['cut_pieces']);
        $wastageQty = (float)($_POST['wastage_qty'] ?? $cut['wastage_qty']);
        $notes      = trim($_POST['notes'] ?? $cut['notes']);

        try {
            DB::query(
                "UPDATE cutting_logs SET cut_pieces = ?, wastage_qty = ?, notes = ? WHERE id = ?",
                [$cutPieces, $wastageQty, $notes, $id]
            );
            $this->flash('success', "Cutting log '{$cut['log_no']}' updated successfully.");
        } catch (Exception $e) {
            $this->flash('error', 'Failed to update cutting log: ' . $e->getMessage());
        }

        $this->redirect('/m/cutting_wastage');
    }

    public function deleteCutting(): void {
        $this->validateCsrf();
        $id = (int)($_POST['id'] ?? 0);
        $cut = DB::fetch("SELECT * FROM cutting_logs WHERE id = ?", [$id]);
        if (!$cut) {
            $this->flash('error', 'Cutting log not found.');
            $this->redirect('/m/cutting_wastage');
        }

        DB::beginTransaction();
        try {
            // Restore consumed stock
            StockService::move(
                $cut['concern_id'],
                $cut['raw_variant_id'],
                'Main Store',
                (float)$cut['consumed_qty'],
                0.00,
                'Cutting & Wastage',
                $id,
                "Reversal of deleted cutting job [{$cut['log_no']}]",
                Auth::id()
            );

            DB::query("DELETE FROM cutting_logs WHERE id = ?", [$id]);
            DB::commit();
            $this->flash('success', "Cutting log '{$cut['log_no']}' deleted and consumed stock restored.");
        } catch (Exception $e) {
            DB::rollBack();
            $this->flash('error', 'Failed to delete cutting log: ' . $e->getMessage());
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

    public function updateStitching(): void {
        $this->validateCsrf();
        $id = (int)($_POST['id'] ?? 0);
        $stt = DB::fetch("SELECT * FROM stitching_logs WHERE id = ?", [$id]);
        if (!$stt) {
            $this->flash('error', 'Stitching log not found.');
            $this->redirect('/m/stitching_wastage');
        }

        $stitchedPcs = (int)($_POST['stitched_pieces'] ?? $stt['stitched_pieces']);
        $wastagePcs  = (int)($_POST['wastage_pieces'] ?? $stt['wastage_pieces']);
        $notes       = trim($_POST['notes'] ?? $stt['notes']);

        try {
            DB::query(
                "UPDATE stitching_logs SET stitched_pieces = ?, wastage_pieces = ?, notes = ? WHERE id = ?",
                [$stitchedPcs, $wastagePcs, $notes, $id]
            );
            $this->flash('success', "Stitching log '{$stt['log_no']}' updated successfully.");
        } catch (Exception $e) {
            $this->flash('error', 'Failed to update stitching log: ' . $e->getMessage());
        }

        $this->redirect('/m/stitching_wastage');
    }

    public function deleteStitching(): void {
        $this->validateCsrf();
        $id = (int)($_POST['id'] ?? 0);
        $stt = DB::fetch("SELECT * FROM stitching_logs WHERE id = ?", [$id]);
        if (!$stt) {
            $this->flash('error', 'Stitching log not found.');
            $this->redirect('/m/stitching_wastage');
        }

        try {
            DB::query("DELETE FROM stitching_logs WHERE id = ?", [$id]);
            $this->flash('success', "Stitching log '{$stt['log_no']}' deleted successfully.");
        } catch (Exception $e) {
            $this->flash('error', 'Failed to delete stitching log: ' . $e->getMessage());
        }

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

    public function updateQc(): void {
        $this->validateCsrf();
        $id = (int)($_POST['id'] ?? 0);
        $qc = DB::fetch("SELECT * FROM qc_inspections WHERE id = ?", [$id]);
        if (!$qc) {
            $this->flash('error', 'QC inspection not found.');
            $this->redirect('/m/qc_stocks');
        }

        $inspectedQty = (float)($_POST['inspected_qty'] ?? $qc['inspected_qty']);
        $newPassed    = (float)($_POST['passed_qty'] ?? $qc['passed_qty']);
        $rejectedQty  = (float)($_POST['rejected_qty'] ?? $qc['rejected_qty']);
        $location     = trim($_POST['location'] ?? $qc['location']);
        $defectNotes  = trim($_POST['defect_notes'] ?? $qc['defect_notes']);

        DB::beginTransaction();
        try {
            $diff = $newPassed - (float)$qc['passed_qty'];
            if ($diff != 0) {
                if ($diff > 0) {
                    StockService::move($qc['concern_id'], $qc['variant_id'], $location, $diff, 0.00, 'QC Stocks', $id, "Updated QC Inspection [{$qc['qc_no']}]: Additional approved units", Auth::id());
                } else {
                    StockService::move($qc['concern_id'], $qc['variant_id'], $location, 0.00, abs($diff), 'QC Stocks', $id, "Updated QC Inspection [{$qc['qc_no']}]: Reduced approved units", Auth::id());
                }
            }

            DB::query(
                "UPDATE qc_inspections SET inspected_qty = ?, passed_qty = ?, rejected_qty = ?, location = ?, defect_notes = ? WHERE id = ?",
                [$inspectedQty, $newPassed, $rejectedQty, $location, $defectNotes, $id]
            );

            DB::commit();
            $this->flash('success', "QC Inspection '{$qc['qc_no']}' updated successfully.");
        } catch (Exception $e) {
            DB::rollBack();
            $this->flash('error', 'Failed to update QC inspection: ' . $e->getMessage());
        }

        $this->redirect('/m/qc_stocks');
    }

    public function deleteQc(): void {
        $this->validateCsrf();
        $id = (int)($_POST['id'] ?? 0);
        $qc = DB::fetch("SELECT * FROM qc_inspections WHERE id = ?", [$id]);
        if (!$qc) {
            $this->flash('error', 'QC inspection not found.');
            $this->redirect('/m/qc_stocks');
        }

        DB::beginTransaction();
        try {
            // Reverse approved stock
            if ((float)$qc['passed_qty'] > 0) {
                StockService::move(
                    $qc['concern_id'],
                    $qc['variant_id'],
                    $qc['location'],
                    0.00,
                    (float)$qc['passed_qty'],
                    'QC Stocks',
                    $id,
                    "Reversal of deleted QC Inspection [{$qc['qc_no']}]",
                    Auth::id()
                );
            }

            DB::query("DELETE FROM qc_inspections WHERE id = ?", [$id]);
            DB::commit();
            $this->flash('success', "QC Inspection '{$qc['qc_no']}' deleted and stock reversed.");
        } catch (Exception $e) {
            DB::rollBack();
            $this->flash('error', 'Failed to delete QC inspection: ' . $e->getMessage());
        }

        $this->redirect('/m/qc_stocks');
    }

    public function exportCatalogProgram(): void {
        $cid = Auth::concernId();
        $format = strtolower($_GET['format'] ?? 'excel');
        $period = $_GET['period'] ?? 'all';
        $singleDate = $_GET['single_date'] ?? null;
        $fromDate = $_GET['from_date'] ?? null;
        $toDate = $_GET['to_date'] ?? null;

        $dateScope = \App\Services\ExportService::resolveDateRange($period, $singleDate, $fromDate, $toDate);

        $sql = "SELECT pp.*, c.name AS catalog_name, v.name AS target_variant_name, v.sku_code,
                       con.name AS concern_name, con.code AS concern_code
                FROM production_programs pp
                LEFT JOIN catalogs c ON pp.catalog_id = c.id
                JOIN variants v ON pp.target_variant_id = v.id
                LEFT JOIN concerns con ON pp.concern_id = con.id";
        $params = [];
        $where = [];
        if ($cid) {
            $where[] = "pp.concern_id = ?";
            $params[] = $cid;
        }
        if ($dateScope['start_date'] && $dateScope['end_date']) {
            $where[] = "DATE(pp.created_at) BETWEEN ? AND ?";
            $params[] = $dateScope['start_date'];
            $params[] = $dateScope['end_date'];
        }
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        $sql .= " ORDER BY pp.id DESC";
        $programs = DB::fetchAll($sql, $params);

        $headers = [
            'Program No', 'Catalog / Style', 'SKU Code', 'Target Product', 
            'Planned Qty', 'Produced Qty', 'Wastage Qty', 'Status', 'Start Date', 'End Date', 'Concern'
        ];

        $rows = [];
        $totPlanned = 0; $totProd = 0; $totWaste = 0;
        foreach ($programs as $p) {
            $planned = (int)($p['planned_qty'] ?? 0);
            $produced = (int)($p['produced_qty'] ?? 0);
            $waste = (int)($p['wastage_qty'] ?? 0);
            $totPlanned += $planned;
            $totProd += $produced;
            $totWaste += $waste;

            $endDate = $p['end_date'] ?? $p['target_date'] ?? null;
            $rows[] = [
                $p['program_no'],
                $p['catalog_name'] ?: 'Custom Production',
                $p['sku_code'],
                $p['target_variant_name'],
                $planned,
                $produced,
                $waste,
                strtoupper($p['status']),
                $p['start_date'] ? date('d M Y', strtotime($p['start_date'])) : '-',
                $endDate ? date('d M Y', strtotime($endDate)) : '-',
                $p['concern_name'] ?? 'Consolidated'
            ];
        }

        $totals = ['Total Programs: ' . count($programs), '', '', '', $totPlanned, $totProd, $totWaste, '', '', '', ''];
        $metadata = [
            'Report Title' => 'Production Catalogs & Scheduling Programs',
            'Scope / Period' => $dateScope['label'],
            'Generated At' => date('d M Y, h:i A'),
            'Total Programs' => count($programs),
            'Total Planned Output' => number_format($totPlanned)
        ];

        $filename = 'production_programs_' . date('Ymd_His');

        if ($format === 'pdf') {
            \App\Services\ExportService::renderPdfView('Production Scheduling Programs Report', $metadata, $headers, $rows, $totals);
        } elseif ($format === 'csv') {
            \App\Services\ExportService::downloadCsv($filename, $headers, $rows, $totals);
        } else {
            \App\Services\ExportService::downloadExcel($filename, 'Production Scheduling Programs Report', $metadata, $headers, $rows, $totals);
        }
    }

    public function exportCutting(): void {
        $cid = Auth::concernId();
        $format = strtolower($_GET['format'] ?? 'excel');
        $period = $_GET['period'] ?? 'all';
        $singleDate = $_GET['single_date'] ?? null;
        $fromDate = $_GET['from_date'] ?? null;
        $toDate = $_GET['to_date'] ?? null;

        $dateScope = \App\Services\ExportService::resolveDateRange($period, $singleDate, $fromDate, $toDate);

        $sql = "SELECT cl.*, pp.program_no, v.name AS raw_variant_name, v.sku_code,
                       con.name AS concern_name, con.code AS concern_code
                FROM cutting_logs cl
                LEFT JOIN production_programs pp ON cl.program_id = pp.id
                JOIN variants v ON cl.raw_variant_id = v.id
                LEFT JOIN concerns con ON cl.concern_id = con.id";
        $params = [];
        $where = [];
        if ($cid) {
            $where[] = "cl.concern_id = ?";
            $params[] = $cid;
        }
        if ($dateScope['start_date'] && $dateScope['end_date']) {
            $where[] = "DATE(cl.created_at) BETWEEN ? AND ?";
            $params[] = $dateScope['start_date'];
            $params[] = $dateScope['end_date'];
        }
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        $sql .= " ORDER BY cl.id DESC";
        $logs = DB::fetchAll($sql, $params);

        $headers = [
            'Log No', 'Date', 'Program Ref', 'Raw Fabric / Variant', 'SKU', 
            'Input Fabric (Mtr/Kg)', 'Cut Pieces', 'Wastage (Kg)', 'Wastage %', 'Notes', 'Concern'
        ];

        $rows = [];
        $totInput = 0; $totCut = 0; $totWaste = 0;
        foreach ($logs as $l) {
            $inp = (float)$l['input_fabric_qty'];
            $cut = (int)$l['cut_pieces'];
            $wst = (float)$l['wastage_fabric_qty'];
            $pct = $inp > 0 ? round(($wst / $inp) * 100, 1) . '%' : '0%';

            $totInput += $inp; $totCut += $cut; $totWaste += $wst;

            $rows[] = [
                $l['log_no'],
                date('d M Y, h:i A', strtotime($l['created_at'])),
                $l['program_no'] ?: 'Direct',
                $l['raw_variant_name'],
                $l['sku_code'],
                $inp,
                $cut,
                $wst,
                $pct,
                $l['notes'] ?: '-',
                $l['concern_name'] ?? 'Consolidated'
            ];
        }

        $totals = ['Total Logs: ' . count($logs), '', '', '', '', $totInput, $totCut, $totWaste, '', '', ''];
        $metadata = [
            'Report Title' => 'Fabric Cutting Operations & Material Wastage Audit',
            'Scope / Period' => $dateScope['label'],
            'Generated At' => date('d M Y, h:i A'),
            'Total Cutting Logs' => count($logs),
            'Total Cut Pieces' => number_format($totCut),
            'Total Fabric Input' => number_format($totInput, 2)
        ];

        $filename = 'cutting_wastage_' . date('Ymd_His');

        if ($format === 'pdf') {
            \App\Services\ExportService::renderPdfView('Cutting & Wastage Log Report', $metadata, $headers, $rows, $totals);
        } elseif ($format === 'csv') {
            \App\Services\ExportService::downloadCsv($filename, $headers, $rows, $totals);
        } else {
            \App\Services\ExportService::downloadExcel($filename, 'Cutting & Wastage Log Report', $metadata, $headers, $rows, $totals);
        }
    }

    public function exportStitching(): void {
        $cid = Auth::concernId();
        $format = strtolower($_GET['format'] ?? 'excel');
        $period = $_GET['period'] ?? 'all';
        $singleDate = $_GET['single_date'] ?? null;
        $fromDate = $_GET['from_date'] ?? null;
        $toDate = $_GET['to_date'] ?? null;

        $dateScope = \App\Services\ExportService::resolveDateRange($period, $singleDate, $fromDate, $toDate);

        $sql = "SELECT sl.*, pp.program_no, v.name AS produced_variant_name, v.sku_code,
                       con.name AS concern_name, con.code AS concern_code
                FROM stitching_logs sl
                LEFT JOIN production_programs pp ON sl.program_id = pp.id
                JOIN variants v ON sl.produced_variant_id = v.id
                LEFT JOIN concerns con ON sl.concern_id = con.id";
        $params = [];
        $where = [];
        if ($cid) {
            $where[] = "sl.concern_id = ?";
            $params[] = $cid;
        }
        if ($dateScope['start_date'] && $dateScope['end_date']) {
            $where[] = "DATE(sl.created_at) BETWEEN ? AND ?";
            $params[] = $dateScope['start_date'];
            $params[] = $dateScope['end_date'];
        }
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        $sql .= " ORDER BY sl.id DESC";
        $logs = DB::fetchAll($sql, $params);

        $headers = [
            'Log No', 'Date', 'Program Ref', 'Finished Variant', 'SKU', 
            'Input Panels', 'Stitched Pcs', 'Defective Pcs', 'Defect %', 'Notes', 'Concern'
        ];

        $rows = [];
        $totIn = 0; $totStitch = 0; $totWaste = 0;
        foreach ($logs as $l) {
            $in = (int)$l['input_pieces'];
            $st = (int)$l['stitched_pieces'];
            $ws = (int)$l['wastage_pieces'];
            $pct = $in > 0 ? round(($ws / $in) * 100, 1) . '%' : '0%';

            $totIn += $in; $totStitch += $st; $totWaste += $ws;

            $rows[] = [
                $l['log_no'],
                date('d M Y, h:i A', strtotime($l['created_at'])),
                $l['program_no'] ?: 'Direct',
                $l['produced_variant_name'],
                $l['sku_code'],
                $in,
                $st,
                $ws,
                $pct,
                $l['notes'] ?: '-',
                $l['concern_name'] ?? 'Consolidated'
            ];
        }

        $totals = ['Total Records: ' . count($logs), '', '', '', '', $totIn, $totStitch, $totWaste, '', '', ''];
        $metadata = [
            'Report Title' => 'Garment Stitching Production & Defect Tracking',
            'Scope / Period' => $dateScope['label'],
            'Generated At' => date('d M Y, h:i A'),
            'Total Stitching Logs' => count($logs),
            'Total Finished Garments' => number_format($totStitch)
        ];

        $filename = 'stitching_wastage_' . date('Ymd_His');

        if ($format === 'pdf') {
            \App\Services\ExportService::renderPdfView('Stitching Production & Defect Report', $metadata, $headers, $rows, $totals);
        } elseif ($format === 'csv') {
            \App\Services\ExportService::downloadCsv($filename, $headers, $rows, $totals);
        } else {
            \App\Services\ExportService::downloadExcel($filename, 'Stitching Production & Defect Report', $metadata, $headers, $rows, $totals);
        }
    }

    public function exportQc(): void {
        $cid = Auth::concernId();
        $format = strtolower($_GET['format'] ?? 'excel');
        $period = $_GET['period'] ?? 'all';
        $singleDate = $_GET['single_date'] ?? null;
        $fromDate = $_GET['from_date'] ?? null;
        $toDate = $_GET['to_date'] ?? null;

        $dateScope = \App\Services\ExportService::resolveDateRange($period, $singleDate, $fromDate, $toDate);

        $sql = "SELECT qc.*, v.name AS variant_name, v.sku_code, v.size, u.name AS inspector_name,
                       con.name AS concern_name, con.code AS concern_code
                FROM qc_inspections qc
                JOIN variants v ON qc.variant_id = v.id
                LEFT JOIN concerns con ON qc.concern_id = con.id
                LEFT JOIN users u ON qc.inspector_id = u.id";
        $params = [];
        $where = [];
        if ($cid) {
            $where[] = "qc.concern_id = ?";
            $params[] = $cid;
        }
        if ($dateScope['start_date'] && $dateScope['end_date']) {
            $where[] = "DATE(qc.created_at) BETWEEN ? AND ?";
            $params[] = $dateScope['start_date'];
            $params[] = $dateScope['end_date'];
        }
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        $sql .= " ORDER BY qc.id DESC";
        $inspections = DB::fetchAll($sql, $params);

        $headers = [
            'QC No', 'Date', 'SKU Code', 'Variant Name', 'Size', 
            'Inspected Qty', 'Passed Qty', 'Rejected Qty', 'Pass Rate %', 'Location', 'Inspector', 'Concern'
        ];

        $rows = [];
        $totInsp = 0; $totPass = 0; $totRej = 0;
        foreach ($inspections as $q) {
            $ins = (float)$q['inspected_qty'];
            $pas = (float)$q['passed_qty'];
            $rej = (float)$q['rejected_qty'];
            $rate = $ins > 0 ? round(($pas / $ins) * 100, 1) . '%' : '100%';

            $totInsp += $ins; $totPass += $pas; $totRej += $rej;

            $rows[] = [
                $q['qc_no'],
                date('d M Y, h:i A', strtotime($q['created_at'])),
                $q['sku_code'],
                $q['variant_name'],
                $q['size'] ?? '-',
                $ins,
                $pas,
                $rej,
                $rate,
                $q['location'] ?? 'QC Department',
                $q['inspector_name'] ?? 'QC Officer',
                $q['concern_name'] ?? 'Consolidated'
            ];
        }

        $totals = ['Total Inspections: ' . count($inspections), '', '', '', '', $totInsp, $totPass, $totRej, '', '', '', ''];
        $metadata = [
            'Report Title' => 'Quality Control (QC) & Stock Approval Audit',
            'Scope / Period' => $dateScope['label'],
            'Generated At' => date('d M Y, h:i A'),
            'Total Inspections' => count($inspections),
            'Total Passed Units' => number_format($totPass, 2),
            'Total Rejected Units' => number_format($totRej, 2)
        ];

        $filename = 'qc_inspections_' . date('Ymd_His');

        if ($format === 'pdf') {
            \App\Services\ExportService::renderPdfView('QC & Stock Approval Audit Report', $metadata, $headers, $rows, $totals);
        } elseif ($format === 'csv') {
            \App\Services\ExportService::downloadCsv($filename, $headers, $rows, $totals);
        } else {
            \App\Services\ExportService::downloadExcel($filename, 'QC & Stock Approval Audit Report', $metadata, $headers, $rows, $totals);
        }
    }
}
