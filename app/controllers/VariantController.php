<?php
// app/controllers/VariantController.php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;
use App\Core\Auth;
use App\Models\Variant;
use App\Services\QrService;
use Exception;

class VariantController extends Controller {
    public function index(): void {
        $variantModel = new Variant();
        $variants = $variantModel->allWithDetails();

        $cid = Auth::concernId();
        $categories = DB::fetchAll("SELECT * FROM categories WHERE concern_id = ? AND status = 'active'", [$cid]);
        $subcategories = DB::fetchAll("SELECT * FROM subcategories WHERE concern_id = ? AND status = 'active'", [$cid]);
        $brands = DB::fetchAll("SELECT * FROM brands WHERE concern_id = ? AND status = 'active'", [$cid]);

        $this->render('concern/variants', [
            'pageTitle'     => 'Product Variants & Master Catalog',
            'currentModule' => 'stocks',
            'variants'      => $variants,
            'categories'    => $categories,
            'subcategories' => $subcategories,
            'brands'        => $brands,
        ]);
    }

    public function store(): void {
        $this->validateCsrf();
        $cid = Auth::concernId();

        $sku          = trim($_POST['sku_code'] ?? '');
        $name         = trim($_POST['name'] ?? '');
        $categoryId   = (int)($_POST['category_id'] ?? 0);
        $subcatId     = (int)($_POST['subcategory_id'] ?? 0);
        $brandId      = !empty($_POST['brand_id']) ? (int)$_POST['brand_id'] : null;
        $size         = trim($_POST['size'] ?? '');
        $weight       = (float)($_POST['weight'] ?? 0);
        $weightUnit   = trim($_POST['weight_unit'] ?? 'pcs');
        $price        = (float)($_POST['price'] ?? 0);
        $mrp          = (float)($_POST['mrp'] ?? 0);
        $creationDate = $_POST['creation_date'] ?: date('Y-m-d');
        $expiryDate   = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;

        // Custom JSON attributes
        $color  = trim($_POST['attr_color'] ?? '');
        $fabric = trim($_POST['attr_fabric'] ?? '');
        $gsm    = trim($_POST['attr_gsm'] ?? '');
        $attributes = [];
        if ($color)  $attributes['color'] = $color;
        if ($fabric) $attributes['fabric'] = $fabric;
        if ($gsm)    $attributes['gsm'] = $gsm;

        if (empty($sku) || empty($name) || !$categoryId || !$subcatId) {
            $this->flash('error', 'SKU, Product Name, Category and Subcategory are required.');
            $this->redirect('/m/variants');
        }

        try {
            $varModel = new Variant();
            $varId = $varModel->insert([
                'sku_code'       => $sku,
                'name'           => $name,
                'category_id'    => $categoryId,
                'subcategory_id' => $subcatId,
                'brand_id'       => $brandId,
                'size'           => $size,
                'weight'         => $weight,
                'weight_unit'    => $weightUnit,
                'attributes'     => !empty($attributes) ? json_encode($attributes) : null,
                'price'          => $price,
                'mrp'            => $mrp,
                'creation_date'  => $creationDate,
                'expiry_date'    => $expiryDate,
                'status'         => 'active',
            ]);

            $this->flash('success', "Variant '{$name}' ({$sku}) created successfully! ID: {$varId}");
        } catch (Exception $e) {
            $this->flash('error', 'Failed to save variant: ' . $e->getMessage());
        }

        $this->redirect('/m/variants');
    }
}
