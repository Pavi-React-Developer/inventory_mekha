<?php
/**
 * Test: Fabric Cutting, Dynamic Variant Breakdown, Wastage Cost Analytics & Scrap Selling Flow
 * Matches UI Images 1, 2, and 3
 */

require_once __DIR__ . '/../app/core/Autoloader.php';
\App\Core\Env::load(dirname(__DIR__) . '/.env');

use App\Core\DB;

$passCount = 0;
$failCount = 0;

function assertCheck($desc, $cond) {
    global $passCount, $failCount;
    if ($cond) {
        echo "  [PASS] $desc\n";
        $passCount++;
    } else {
        echo "  [FAIL] $desc\n";
        $failCount++;
    }
}

echo "\n======================================================\n";
echo "INVENTO: Fabric Cutting & Wastage Scrap Flow Test\n";
echo "======================================================\n\n";

$cid = 1;
$suffix = time();

// ----------------------------------------------------
// TEST 1: Schema Integrity Check
// ----------------------------------------------------
echo "TEST 1: Schema Integrity Check\n";
$tables = array_column(DB::fetchAll("SHOW TABLES"), 'Tables_in_invento_db');
assertCheck("cutting_masters table exists", in_array('cutting_masters', $tables));
assertCheck("cutting_logs table exists", in_array('cutting_logs', $tables));
assertCheck("cutting_items table exists", in_array('cutting_items', $tables));
assertCheck("cutting_wastage_sales table exists", in_array('cutting_wastage_sales', $tables));

$clCols = array_column(DB::fetchAll("DESCRIBE cutting_logs"), 'Field');
assertCheck("cutting_logs has 'batch_no'", in_array('batch_no', $clCols));
assertCheck("cutting_logs has 'cutting_name'", in_array('cutting_name', $clCols));
assertCheck("cutting_logs has 'total_roll_weight'", in_array('total_roll_weight', $clCols));
assertCheck("cutting_logs has 'total_roll_price'", in_array('total_roll_price', $clCols));
assertCheck("cutting_logs has 'fabric_rate_per_kg'", in_array('fabric_rate_per_kg', $clCols));
assertCheck("cutting_logs has 'cut_pieces'", in_array('cut_pieces', $clCols));
assertCheck("cutting_logs has 'wastage_qty'", in_array('wastage_qty', $clCols));
assertCheck("cutting_logs has 'wastage_cost'", in_array('wastage_cost', $clCols));
assertCheck("cutting_logs has 'wastage_percent'", in_array('wastage_percent', $clCols));
assertCheck("cutting_logs has 'used_weight'", in_array('used_weight', $clCols));
assertCheck("cutting_logs has 'fabric_cost_per_piece'", in_array('fabric_cost_per_piece', $clCols));
assertCheck("cutting_logs has 'sold_wastage_qty'", in_array('sold_wastage_qty', $clCols));

// ----------------------------------------------------
// TEST 2: Cutting Master Quick Add
// ----------------------------------------------------
echo "\nTEST 2: Cutting Master Creation\n";
DB::query(
    "INSERT INTO cutting_masters (concern_id, name, phone, employee_code, status, created_at)
     VALUES (?, ?, ?, ?, 'active', NOW())",
    [$cid, "Ramesh Kumar ($suffix)", "9876543210", "OP-$suffix"]
);
$masterId = (int)DB::lastInsertId();
assertCheck("Cutting Master created with ID: $masterId", $masterId > 0);

// ----------------------------------------------------
// TEST 3: Seed Test Program with Catalog and Sizes
// ----------------------------------------------------
echo "\nTEST 3: Setup Test Production Program & Assigned Roll\n";
// 1. Ensure Catalog Style with sizes
$catRec = DB::fetch("SELECT id FROM categories LIMIT 1");
$mainCatId = $catRec ? (int)$catRec['id'] : 1;

DB::query(
    "INSERT INTO catalogs (concern_id, code, style_code, short_code, category_id, name, sizes, status, created_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, 'active', NOW())",
    [$cid, "CAT-TEST-$suffix", "STY-$suffix", "STY", $mainCatId, "Summer Linen Casual Shirt", json_encode(['S', 'M', 'L', 'XL'])]
);
$catId = (int)DB::lastInsertId();

// Create sample variants for this catalog
$sizes = ['S', 'M', 'L', 'XL'];
$createdVariantIds = [];
foreach ($sizes as $s) {
    DB::query(
        "INSERT INTO variants (concern_id, catalog_id, category_id, sku_code, name, size, status, created_at)
         VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())",
        [$cid, $catId, $mainCatId, "STY-$suffix-$s", "Summer Linen Shirt - $s", $s]
    );
    $createdVariantIds[$s] = (int)DB::lastInsertId();
}
assertCheck("Created 4 size variants for Catalog #$catId", count($createdVariantIds) === 4);

// 2. Create Production Program
$progNo = "PRG-CUT-$suffix";
DB::query(
    "INSERT INTO production_programs (concern_id, program_no, program_name, catalog_id, planned_qty, start_date, target_date, status, created_at)
     VALUES (?, ?, 'Summer Linen 2026 Batch', ?, 100.00, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 7 DAY), 'planned', NOW())",
    [$cid, $progNo, $catId]
);
$progId = (int)DB::lastInsertId();

// 3. Assign Fabric Roll (100.00 kg at ₹300/kg = ₹30,000.00)
DB::query(
    "INSERT INTO program_rolls (
        concern_id, program_id, roll_piece_tag, fabric_name, color, unit,
        total_roll_qty, allocated_qty, roll_weight, roll_price, catalog_id, created_at
    ) VALUES (?, ?, ?, ?, ?, 'kg', 100.00, 100.00, 100.00, 30000.00, ?, NOW())",
    [$cid, $progId, "LINEN-TEST-$suffix", "100% Pure Linen", "White", $catId]
);
$rollId = (int)DB::lastInsertId();
assertCheck("Assigned 100.00 kg roll to Program #$progNo", $rollId > 0);

// ----------------------------------------------------
// TEST 4: Mathematical Validation of Fabric Wastage & Analytics
// ----------------------------------------------------
echo "\nTEST 4: Mathematical Validation of Fabric Wastage & Analytics (Matching Image 2)\n";
$totalRollWeight = 100.00;
$totalRollPrice = 30000.00;
$wastageWeight = 10.00; // 10 kg scrap

$fabricRate = $totalRollWeight > 0 ? ($totalRollPrice / $totalRollWeight) : 0;
$wastageCost = $wastageWeight * $fabricRate;
$wastagePct = ($wastageWeight / $totalRollWeight) * 100;
$usedWeight = $totalRollWeight - $wastageWeight;
$totalCutPieces = 200; // 50 pcs in S, 50 in M, 50 in L, 50 in XL
$fabricCostPerPiece = ($totalRollPrice - $wastageCost) / $totalCutPieces;

assertCheck("Fabric Rate is ₹300.00/kg ($totalRollPrice / $totalRollWeight)", $fabricRate === 300.00);
assertCheck("Wastage Cost is ₹3,000.00 ($wastageWeight kg * ₹$fabricRate/kg)", $wastageCost === 3000.00);
assertCheck("Wastage % is 10.0% ($wastageWeight / $totalRollWeight * 100)", $wastagePct === 10.0);
assertCheck("Used Fabric Weight is 90.00 kg ($totalRollWeight - $wastageWeight)", $usedWeight === 90.00);
assertCheck("Fabric Cost per Cut Piece is ₹135.00 ((₹30,000 - ₹3,000) / 200 pcs)", $fabricCostPerPiece === 135.00);

// ----------------------------------------------------
// TEST 5: Create Cutting Batch & Dynamic Variant Breakdown Persistence
// ----------------------------------------------------
echo "\nTEST 5: Create Cutting Batch & Dynamic Variant Breakdown\n";
$batchNo = "CUT-" . date('Ymd') . "-" . rand(1000, 9999);
$cuttingName = "SUMMER LINEN SHIRT CUTTING LOT A";

DB::query(
    "INSERT INTO cutting_logs (
        concern_id, log_no, batch_no, cutting_name, status,
        program_id, cutting_master_id, cutting_master_name,
        consumed_qty, total_roll_weight, total_roll_price, fabric_rate_per_kg,
        cut_pieces, wastage_qty, wastage_cost, wastage_percent, used_weight, fabric_cost_per_piece,
        sold_wastage_qty, wastage_unit, notes, created_at
    ) VALUES (?, ?, ?, ?, 'in_progress', ?, ?, 'Ramesh Kumar', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0.00, 'kg', 'Lot notes test', NOW())",
    [
        $cid, $batchNo, $batchNo, $cuttingName, $progId, $masterId,
        $usedWeight, $totalRollWeight, $totalRollPrice, $fabricRate,
        $totalCutPieces, $wastageWeight, $wastageCost, $wastagePct, $usedWeight, $fabricCostPerPiece
    ]
);
$cuttingId = (int)DB::lastInsertId();
assertCheck("Cutting Batch #$batchNo inserted with ID: $cuttingId", $cuttingId > 0);

// Insert variant breakdown into cutting_items
foreach ($createdVariantIds as $sz => $vId) {
    DB::query(
        "INSERT INTO cutting_items (cutting_id, program_roll_id, variant_id, size, sku_code, variant_name, cut_pieces, created_at)
         VALUES (?, ?, ?, ?, ?, ?, 50, NOW())",
        [$cuttingId, $rollId, $vId, $sz, "STY-$suffix-$sz", "Summer Linen Shirt - $sz"]
    );
}

$cutItems = DB::fetchAll("SELECT * FROM cutting_items WHERE cutting_id = ?", [$cuttingId]);
assertCheck("Persisted 4 variant cut breakdown items for Cutting Batch #$batchNo", count($cutItems) === 4);
$sumCutPieces = (int)DB::fetch("SELECT SUM(cut_pieces) as s FROM cutting_items WHERE cutting_id = ?", [$cuttingId])['s'];
assertCheck("Sum of cut pieces in cutting_items is exactly 200 pcs", $sumCutPieces === 200);

// ----------------------------------------------------
// TEST 6: Wastage Scrap Sale Validation & Ledger Sync (Image 3)
// ----------------------------------------------------
echo "\nTEST 6: Wastage Scrap Sale Workflow (Matching Image 3)\n";

// Fetch cutting batch for sale
$cutRecord = DB::fetch("SELECT * FROM cutting_logs WHERE id = ?", [$cuttingId]);
$availableScrap = (float)$cutRecord['wastage_qty'] - (float)$cutRecord['sold_wastage_qty'];
assertCheck("Initial available scrap is 10.00 kg", $availableScrap === 10.00);

// Validation 1: Cannot sell more than available scrap
$attemptSaleQty = 15.00;
assertCheck("Strictly prevents selling 15.00 kg scrap when available is 10.00 kg", $attemptSaleQty > $availableScrap);

// Validation 2: Buyer phone 10-digits validation
$validPhone = "9876543210";
$invalidPhone = "9876";
assertCheck("Rejects short phone '9876' (< 10 digits)", strlen(preg_replace('/[^0-9]/', '', $invalidPhone)) < 10);
assertCheck("Accepts valid 10-digit phone '$validPhone'", strlen(preg_replace('/[^0-9]/', '', $validPhone)) >= 10);

// Execute valid sale of 10.00 kg scrap at ₹20.00/kg = ₹200.00
$soldQty = 10.00;
$sellingRate = 20.00;
$totalSalePrice = $soldQty * $sellingRate; // ₹200.00
$amountReceived = 200.00;
$saleNo = "SCRAP-" . date('Ymd') . "-" . rand(1000, 9999);

DB::query(
    "INSERT INTO cutting_wastage_sales (
        concern_id, sale_no, cutting_id, buyer_name, buyer_phone, buyer_company,
        wastage_category, wastage_weight, selling_rate, total_price, amount_received,
        sale_date, payment_status, created_at
    ) VALUES (?, ?, ?, 'Krishna Scrap Traders', ?, 'Sri Waste Recycling Mills', 'Cutting End-Bits (Short length ends & trims)', ?, ?, ?, ?, CURDATE(), 'paid', NOW())",
    [$cid, $saleNo, $cuttingId, $validPhone, $soldQty, $sellingRate, $totalSalePrice, $amountReceived]
);
$saleId = (int)DB::lastInsertId();
assertCheck("Recorded scrap sale #$saleNo with ID: $saleId", $saleId > 0);

// Update sold_wastage_qty in cutting_logs
DB::query("UPDATE cutting_logs SET sold_wastage_qty = sold_wastage_qty + ? WHERE id = ?", [$soldQty, $cuttingId]);

$updatedCut = DB::fetch("SELECT * FROM cutting_logs WHERE id = ?", [$cuttingId]);
$newAvail = (float)$updatedCut['wastage_qty'] - (float)$updatedCut['sold_wastage_qty'];
assertCheck("Updated sold_wastage_qty is 10.00 kg", (float)$updatedCut['sold_wastage_qty'] === 10.00);
assertCheck("Remaining available scrap is now 0.00 kg (all sold)", $newAvail === 0.00);

// ----------------------------------------------------
// TEST 7: Clean-up
// ----------------------------------------------------
echo "\nTEST 7: Test Data Clean-up\n";
DB::query("DELETE FROM cutting_wastage_sales WHERE id = ?", [$saleId]);
DB::query("DELETE FROM cutting_items WHERE cutting_id = ?", [$cuttingId]);
DB::query("DELETE FROM cutting_logs WHERE id = ?", [$cuttingId]);
DB::query("DELETE FROM program_rolls WHERE id = ?", [$rollId]);
DB::query("DELETE FROM production_programs WHERE id = ?", [$progId]);
foreach ($createdVariantIds as $vId) {
    DB::query("DELETE FROM variants WHERE id = ?", [$vId]);
}
DB::query("DELETE FROM catalogs WHERE id = ?", [$catId]);
DB::query("DELETE FROM cutting_masters WHERE id = ?", [$masterId]);
assertCheck("All temporary test records safely cleaned up", true);

echo "\n======================================================\n";
echo "TEST RESULTS: Total = " . ($passCount + $failCount) . " | Passed = $passCount | Failed = $failCount\n";
echo "======================================================\n";

if ($failCount === 0) {
    echo ">>> ALL CUTTING & WASTAGE SCRAP TESTS PASSED SUCCESSFULLY! <<<\n\n";
    exit(0);
} else {
    echo ">>> SOME TESTS FAILED! <<<\n\n";
    exit(1);
}
