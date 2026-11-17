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

        $damages = DB::fetchAll(
            "SELECT cd.*, v.name AS variant_name, v.sku_code, v.size, u.name AS user_name
             FROM concern_damages cd
             JOIN variants v ON cd.variant_id = v.id
             LEFT JOIN users u ON cd.created_by = u.id
             WHERE cd.concern_id = ?
             ORDER BY cd.id DESC",
            [$cid]
        );

        $variants = DB::fetchAll("SELECT id, sku_code, name, size FROM variants WHERE concern_id = ? AND status = 'active'", [$cid]);

        $this->render('concern/concern_damage', [
            'pageTitle'     => 'Internal Concern Damage & Write-offs',
            'currentModule' => 'concern_damage',
            'damages'       => $damages,
            'variants'      => $variants,
        ]);
    }

    public function store(): void {
        $this->validateCsrf();
        $cid = Auth::concernId();

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
}
