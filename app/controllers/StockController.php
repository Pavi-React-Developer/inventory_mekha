<?php
// app/controllers/StockController.php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;
use App\Core\Auth;
use App\Services\StockService;

class StockController extends Controller {
    public function index(): void {
        $cid = Auth::concernId();

        $sql = "SELECT s.*, v.name AS variant_name, v.sku_code, v.size, v.weight, v.weight_unit, v.price, v.mrp,
                       c.name AS category_name, sub.name AS subcategory_name,
                       con.name AS concern_name, con.code AS concern_code
                FROM stocks s
                JOIN variants v ON s.variant_id = v.id
                LEFT JOIN concerns con ON s.concern_id = con.id
                LEFT JOIN categories c ON v.category_id = c.id
                LEFT JOIN subcategories sub ON v.subcategory_id = sub.id";
        
        $params = [];
        if ($cid) {
            $sql .= " WHERE s.concern_id = ?";
            $params[] = $cid;
        }
        $sql .= " ORDER BY s.qty DESC";
        $stocks = DB::fetchAll($sql, $params);

        $totalUnits = array_sum(array_column($stocks, 'qty'));
        $totalValuation = array_reduce($stocks, fn($sum, $item) => $sum + ($item['qty'] * $item['price']), 0);

        $this->render('concern/stocks', [
            'pageTitle'      => 'Live Inventory Stocks',
            'currentModule'  => 'stocks',
            'stocks'         => $stocks,
            'totalUnits'     => $totalUnits,
            'totalValuation' => $totalValuation,
            'selectedCid'    => $cid,
        ]);
    }

    public function ledger(): void {
        $cid = Auth::concernId();

        $sql = "SELECT sl.*, v.name AS variant_name, v.sku_code, v.weight_unit, u.name AS user_name,
                       con.name AS concern_name, con.code AS concern_code
                FROM stock_ledger sl
                JOIN variants v ON sl.variant_id = v.id
                LEFT JOIN concerns con ON sl.concern_id = con.id
                LEFT JOIN users u ON sl.created_by = u.id";
        
        $params = [];
        if ($cid) {
            $sql .= " WHERE sl.concern_id = ?";
            $params[] = $cid;
        }
        $sql .= " ORDER BY sl.id DESC";
        $ledger = DB::fetchAll($sql, $params);

        $this->render('concern/stock_ledger', [
            'pageTitle'     => 'Stock Movement Ledger (Audit Trail)',
            'currentModule' => 'stocks',
            'ledger'        => $ledger,
            'selectedCid'   => $cid,
        ]);
    }

    public function exportStocks(): void {
        $cid = Auth::concernId();
        $format = strtolower($_GET['format'] ?? 'excel');
        $period = $_GET['period'] ?? 'all';
        $singleDate = $_GET['single_date'] ?? null;
        $fromDate = $_GET['from_date'] ?? null;
        $toDate = $_GET['to_date'] ?? null;

        $dateScope = \App\Services\ExportService::resolveDateRange($period, $singleDate, $fromDate, $toDate);

        $sql = "SELECT s.*, v.name AS variant_name, v.sku_code, v.size, v.weight, v.weight_unit, v.price, v.mrp,
                       c.name AS category_name, sub.name AS subcategory_name,
                       con.name AS concern_name, con.code AS concern_code
                FROM stocks s
                JOIN variants v ON s.variant_id = v.id
                LEFT JOIN concerns con ON s.concern_id = con.id
                LEFT JOIN categories c ON v.category_id = c.id
                LEFT JOIN subcategories sub ON v.subcategory_id = sub.id";
        
        $params = [];
        $where = [];
        if ($cid) {
            $where[] = "s.concern_id = ?";
            $params[] = $cid;
        }
        if ($dateScope['start_date'] && $dateScope['end_date']) {
            $where[] = "DATE(s.updated_at) BETWEEN ? AND ?";
            $params[] = $dateScope['start_date'];
            $params[] = $dateScope['end_date'];
        }
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        $sql .= " ORDER BY s.qty DESC";
        $stocks = DB::fetchAll($sql, $params);

        $headers = [
            'SKU Code', 'Variant Name', 'Category', 'Subcategory', 'Quantity', 'Unit', 
            'Cost Price (₹)', 'Selling MRP (₹)', 'Valuation (₹)', 'Concern'
        ];

        $rows = [];
        $totalQty = 0;
        $totalVal = 0;
        foreach ($stocks as $s) {
            $val = (float)$s['qty'] * (float)$s['price'];
            $totalQty += (float)$s['qty'];
            $totalVal += $val;
            $rows[] = [
                $s['sku_code'],
                $s['variant_name'],
                $s['category_name'] ?? 'General',
                $s['subcategory_name'] ?? '-',
                (float)$s['qty'],
                $s['weight_unit'] ?: 'Pcs',
                '₹' . number_format((float)$s['price'], 2),
                '₹' . number_format((float)$s['mrp'], 2),
                '₹' . number_format($val, 2),
                $s['concern_name'] ?? 'Consolidated'
            ];
        }

        $totals = ['Total', '', '', '', $totalQty, '', '', '', '₹' . number_format($totalVal, 2), ''];
        $metadata = [
            'Report Title' => 'Current Live Stock Balances & Valuation',
            'Scope / Period' => $dateScope['label'],
            'Generated At' => date('d M Y, h:i A'),
            'Total SKUs' => count($stocks),
            'Total Units' => number_format($totalQty, 2),
            'Total Valuation' => '₹' . number_format($totalVal, 2)
        ];

        $filename = 'stocks_report_' . date('Ymd_His');

        if ($format === 'pdf') {
            \App\Services\ExportService::renderPdfView('Stocks Inventory & Valuation Report', $metadata, $headers, $rows, $totals);
        } elseif ($format === 'csv') {
            \App\Services\ExportService::downloadCsv($filename, $headers, $rows, $totals);
        } else {
            \App\Services\ExportService::downloadExcel($filename, 'Stocks Inventory & Valuation Report', $metadata, $headers, $rows, $totals);
        }
    }

    public function exportLedger(): void {
        $cid = Auth::concernId();
        $format = strtolower($_GET['format'] ?? 'excel');
        $period = $_GET['period'] ?? 'all';
        $singleDate = $_GET['single_date'] ?? null;
        $fromDate = $_GET['from_date'] ?? null;
        $toDate = $_GET['to_date'] ?? null;

        $dateScope = \App\Services\ExportService::resolveDateRange($period, $singleDate, $fromDate, $toDate);

        $sql = "SELECT sl.*, v.name AS variant_name, v.sku_code, v.weight_unit, u.name AS user_name,
                       con.name AS concern_name, con.code AS concern_code
                FROM stock_ledger sl
                JOIN variants v ON sl.variant_id = v.id
                LEFT JOIN concerns con ON sl.concern_id = con.id
                LEFT JOIN users u ON sl.created_by = u.id";

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
        $ledger = DB::fetchAll($sql, $params);

        $headers = [
            'ID', 'Date & Time', 'SKU Code', 'Variant Name', 'Tx Type', 
            'Qty In', 'Qty Out', 'Balance', 'Ref Type', 'Ref ID', 'User', 'Concern'
        ];

        $rows = [];
        $totIn = 0;
        $totOut = 0;
        foreach ($ledger as $l) {
            $in = (float)($l['qty_in'] ?? 0);
            $out = (float)($l['qty_out'] ?? 0);
            $totIn += $in;
            $totOut += $out;
            $rows[] = [
                '#' . $l['id'],
                date('d M Y, h:i A', strtotime($l['created_at'])),
                $l['sku_code'],
                $l['variant_name'],
                $l['ref_module'] ?: 'Movement',
                $in,
                $out,
                $l['location'] ?? 'Main Store',
                $l['ref_id'] ? '#' . $l['ref_id'] : '-',
                $l['note'] ?: '-',
                $l['user_name'] ?? 'System',
                $l['concern_name'] ?? 'Consolidated'
            ];
        }

        $headers = [
            'ID', 'Date & Time', 'SKU Code', 'Variant Name', 'Module', 
            'Qty In (+)', 'Qty Out (-)', 'Location', 'Ref ID', 'Notes', 'User', 'Concern'
        ];

        $totals = ['Total: ' . count($ledger) . ' Records', '', '', '', '', $totIn, $totOut, '', '', '', '', ''];
        $metadata = [
            'Report Title' => 'Stock Movement Ledger (Audit Trail)',
            'Scope / Period' => $dateScope['label'],
            'Generated At' => date('d M Y, h:i A'),
            'Total Transactions' => count($ledger)
        ];

        $filename = 'stock_ledger_' . date('Ymd_His');

        if ($format === 'pdf') {
            \App\Services\ExportService::renderPdfView('Stock Movement Ledger Report', $metadata, $headers, $rows, $totals);
        } elseif ($format === 'csv') {
            \App\Services\ExportService::downloadCsv($filename, $headers, $rows, $totals);
        } else {
            \App\Services\ExportService::downloadExcel($filename, 'Stock Movement Ledger Report', $metadata, $headers, $rows, $totals);
        }
    }
}
