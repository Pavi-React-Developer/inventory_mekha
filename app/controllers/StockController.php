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
}
