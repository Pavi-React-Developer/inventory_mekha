<?php
// app/controllers/DamageController.php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;
use App\Core\Auth;
use App\Services\StockService;
use App\Services\NumberSeries;
use Exception;

class DamageController extends Controller {
    public function index(): void {
        $cid = Auth::concernId();

        $sql = "SELECT cd.*, v.name AS variant_name, v.sku_code, v.size, u.name AS user_name,
                       con.name AS concern_name, con.code AS concern_code
                FROM concern_damages cd
                JOIN variants v ON cd.variant_id = v.id
                LEFT JOIN concerns con ON cd.concern_id = con.id
                LEFT JOIN users u ON cd.created_by = u.id";
        $params = [];
        if ($cid) {
            $sql .= " WHERE cd.concern_id = ?";
            $params[] = $cid;
        }
        $sql .= " ORDER BY cd.id DESC";
        $damages = DB::fetchAll($sql, $params);

        if ($cid) {
            $variants = DB::fetchAll("SELECT id, sku_code, name, size FROM variants WHERE concern_id = ? AND status = 'active'", [$cid]);
        } else {
            $variants = DB::fetchAll("SELECT id, sku_code, name, size FROM variants WHERE status = 'active'");
        }

        $this->render('concern/concern_damage', [
            'pageTitle'     => 'Internal Concern Damage & Write-offs',
            'currentModule' => 'concern_damage',
            'damages'       => $damages,
            'variants'      => $variants,
            'selectedCid'   => $cid,
        ]);
    }

    public function store(): void {
        $this->validateCsrf();
        $cid = Auth::concernId() ?: (int)($_POST['concern_id'] ?? 0);

        if (!$cid) {
            $this->flash('error', 'Please choose a concern for this internal damage.');
            $this->redirect('/m/concern_damage');
        }

        $variantId = (int)($_POST['variant_id'] ?? 0);
        $qty       = (float)($_POST['qty'] ?? 0);
        $location  = trim($_POST['location'] ?? 'Main Store');
        $reason    = trim($_POST['reason'] ?? '');

        if (!$variantId || $qty <= 0) {
            $this->flash('error', 'Variant and valid quantity are required.');
            $this->redirect('/m/concern_damage');
        }

        DB::beginTransaction();
        try {
            $damageNo = NumberSeries::getNext($cid, 'DAMAGE');

            DB::query(
                "INSERT INTO concern_damages (concern_id, damage_no, variant_id, qty, location, reason, created_by, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW())",
                [$cid, $damageNo, $variantId, $qty, $location, $reason, Auth::id()]
            );
            $dmgId = (int)DB::lastInsertId();

            // Deducts stock via StockService
            StockService::move(
                $cid,
                $variantId,
                $location,
                0.00,
                $qty,
                'Concern Damage',
                $dmgId,
                "Internal Damage Write-off [{$damageNo}]: {$reason}",
                Auth::id()
            );

            DB::commit();

            $this->flash('success', "Internal damage '{$damageNo}' logged and {$qty} units deducted from live stock.");
        } catch (Exception $e) {
            DB::rollBack();
            $this->flash('error', 'Failed to record damage: ' . $e->getMessage());
        }

        $this->redirect('/m/concern_damage');
    }

    public function update(): void {
        $this->validateCsrf();
        $id = (int)($_POST['id'] ?? 0);
        $dmg = DB::fetch("SELECT * FROM concern_damages WHERE id = ?", [$id]);
        if (!$dmg) {
            $this->flash('error', 'Damage record not found.');
            $this->redirect('/m/concern_damage');
        }

        $location = trim($_POST['location'] ?? $dmg['location']);
        $reason   = trim($_POST['reason'] ?? $dmg['reason']);
        $newQty   = (float)($_POST['qty'] ?? $dmg['qty']);

        DB::beginTransaction();
        try {
            $diff = $newQty - (float)$dmg['qty'];
            if ($diff != 0) {
                // If diff > 0, deduct more stock. If diff < 0, return stock.
                if ($diff > 0) {
                    StockService::move($dmg['concern_id'], $dmg['variant_id'], $location, 0.00, $diff, 'Concern Damage', $id, "Updated Damage [{$dmg['damage_no']}]: Additional deduction", Auth::id());
                } else {
                    StockService::move($dmg['concern_id'], $dmg['variant_id'], $location, abs($diff), 0.00, 'Concern Damage', $id, "Updated Damage [{$dmg['damage_no']}]: Restored partial stock", Auth::id());
                }
            }

            DB::query("UPDATE concern_damages SET qty = ?, location = ?, reason = ? WHERE id = ?", [$newQty, $location, $reason, $id]);
            DB::commit();
            $this->flash('success', "Internal damage '{$dmg['damage_no']}' updated successfully.");
        } catch (Exception $e) {
            DB::rollBack();
            $this->flash('error', 'Failed to update damage: ' . $e->getMessage());
        }

        $this->redirect('/m/concern_damage');
    }

    public function delete(): void {
        $this->validateCsrf();
        $id = (int)($_POST['id'] ?? 0);
        $dmg = DB::fetch("SELECT * FROM concern_damages WHERE id = ?", [$id]);
        if (!$dmg) {
            $this->flash('error', 'Damage record not found.');
            $this->redirect('/m/concern_damage');
        }

        DB::beginTransaction();
        try {
            // Restore deducted stock
            StockService::move($dmg['concern_id'], $dmg['variant_id'], $dmg['location'], (float)$dmg['qty'], 0.00, 'Concern Damage', $id, "Reversal of deleted Damage [{$dmg['damage_no']}]", Auth::id());
            DB::query("DELETE FROM concern_damages WHERE id = ?", [$id]);
            DB::commit();
            $this->flash('success', "Damage record '{$dmg['damage_no']}' deleted and {$dmg['qty']} units restored to live stock.");
        } catch (Exception $e) {
            DB::rollBack();
            $this->flash('error', 'Failed to delete damage: ' . $e->getMessage());
        }

        $this->redirect('/m/concern_damage');
    }

    public function exportConcernDamage(): void {
        $cid = Auth::concernId();
        $format = strtolower($_GET['format'] ?? 'excel');
        $period = $_GET['period'] ?? 'all';
        $singleDate = $_GET['single_date'] ?? null;
        $fromDate = $_GET['from_date'] ?? null;
        $toDate = $_GET['to_date'] ?? null;

        $dateScope = \App\Services\ExportService::resolveDateRange($period, $singleDate, $fromDate, $toDate);

        $sql = "SELECT cd.*, v.name AS variant_name, v.sku_code, v.size, v.price, u.name AS user_name,
                       con.name AS concern_name, con.code AS concern_code
                FROM concern_damages cd
                JOIN variants v ON cd.variant_id = v.id
                LEFT JOIN concerns con ON cd.concern_id = con.id
                LEFT JOIN users u ON cd.created_by = u.id";
        $params = [];
        $where = [];
        if ($cid) {
            $where[] = "cd.concern_id = ?";
            $params[] = $cid;
        }
        if ($dateScope['start_date'] && $dateScope['end_date']) {
            $where[] = "DATE(cd.created_at) BETWEEN ? AND ?";
            $params[] = $dateScope['start_date'];
            $params[] = $dateScope['end_date'];
        }
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        $sql .= " ORDER BY cd.id DESC";
        $damages = DB::fetchAll($sql, $params);

        $headers = [
            'Damage No', 'Date', 'SKU Code', 'Variant Name', 'Size', 
            'Damaged Qty', 'Unit Cost (₹)', 'Est. Loss (₹)', 'Location', 'Reason', 'Logged By', 'Concern'
        ];

        $rows = [];
        $totQty = 0;
        $totLoss = 0;
        foreach ($damages as $d) {
            $loss = (float)$d['qty'] * (float)($d['price'] ?? 0);
            $totQty += (float)$d['qty'];
            $totLoss += $loss;
            $rows[] = [
                $d['damage_no'],
                date('d M Y, h:i A', strtotime($d['created_at'])),
                $d['sku_code'],
                $d['variant_name'],
                $d['size'] ?? '-',
                (float)$d['qty'],
                '₹' . number_format((float)($d['price'] ?? 0), 2),
                '₹' . number_format($loss, 2),
                $d['location'] ?? 'Main Store',
                $d['reason'] ?? '-',
                $d['user_name'] ?? 'System',
                $d['concern_name'] ?? 'Consolidated'
            ];
        }

        $totals = ['Total: ' . count($damages) . ' Records', '', '', '', '', $totQty, '', '₹' . number_format($totLoss, 2), '', '', '', ''];
        $metadata = [
            'Report Title' => 'Internal Concern Damages & Scrap Write-offs',
            'Scope / Period' => $dateScope['label'],
            'Generated At' => date('d M Y, h:i A'),
            'Total Incident Records' => count($damages),
            'Total Damaged Units' => number_format($totQty, 2),
            'Total Valuation Loss' => '₹' . number_format($totLoss, 2)
        ];

        $filename = 'concern_damages_' . date('Ymd_His');

        if ($format === 'pdf') {
            \App\Services\ExportService::renderPdfView('Internal Damage & Write-offs Report', $metadata, $headers, $rows, $totals);
        } elseif ($format === 'csv') {
            \App\Services\ExportService::downloadCsv($filename, $headers, $rows, $totals);
        } else {
            \App\Services\ExportService::downloadExcel($filename, 'Internal Damage & Write-offs Report', $metadata, $headers, $rows, $totals);
        }
    }
}
