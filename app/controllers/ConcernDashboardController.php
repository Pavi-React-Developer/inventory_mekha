<?php
// app/controllers/ConcernDashboardController.php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;
use App\Core\Auth;

class ConcernDashboardController extends Controller {
    public function index(): void {
        $cid = Auth::concernId();

        // High-level KPIs
        $variantsCount = (int)DB::fetchValue("SELECT COUNT(*) FROM variants WHERE concern_id = ?", [$cid]);
        $totalStockQty = (float)DB::fetchValue("SELECT COALESCE(SUM(qty), 0) FROM stocks WHERE concern_id = ?", [$cid]);
        $stockValuation = (float)DB::fetchValue(
            "SELECT COALESCE(SUM(s.qty * v.price), 0) FROM stocks s JOIN variants v ON s.variant_id = v.id WHERE s.concern_id = ?",
            [$cid]
        );
        $totalCustomers = (int)DB::fetchValue("SELECT COUNT(*) FROM customers WHERE concern_id = ?", [$cid]);
        $totalSuppliers = (int)DB::fetchValue("SELECT COUNT(*) FROM suppliers WHERE concern_id = ?", [$cid]);
        $cashBalance = (float)DB::fetchValue("SELECT COALESCE(SUM(balance), 0) FROM cash_accounts WHERE concern_id = ?", [$cid]);

        // Recent Stock Ledger
        $recentLedger = DB::fetchAll(
            "SELECT sl.*, v.name AS variant_name, v.sku_code, u.name AS user_name
             FROM stock_ledger sl
             JOIN variants v ON sl.variant_id = v.id
             LEFT JOIN users u ON sl.created_by = u.id
             WHERE sl.concern_id = ?
             ORDER BY sl.id DESC LIMIT 8",
            [$cid]
        );

        // Low stock alerts (< 20 units)
        $lowStocks = DB::fetchAll(
            "SELECT s.*, v.name AS variant_name, v.sku_code, v.size, v.weight_unit
             FROM stocks s
             JOIN variants v ON s.variant_id = v.id
             WHERE s.concern_id = ? AND s.qty < 50
             ORDER BY s.qty ASC LIMIT 5",
            [$cid]
        );

        $this->render('concern/dashboard', [
            'pageTitle'       => 'Concern Dashboard',
            'currentModule'   => 'concern_dashboard',
            'variantsCount'   => $variantsCount,
            'totalStockQty'   => $totalStockQty,
            'stockValuation'  => $stockValuation,
            'totalCustomers'  => $totalCustomers,
            'totalSuppliers'  => $totalSuppliers,
            'cashBalance'     => $cashBalance,
            'recentLedger'    => $recentLedger,
            'lowStocks'       => $lowStocks,
        ]);
    }
}
