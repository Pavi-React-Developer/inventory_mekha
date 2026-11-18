<?php
namespace App\Services;

use App\Core\DB;

class RollRegistryService {

    private static bool $tableChecked = false;

    /**
     * Ensure fabric_roll_specs table exists
     */
    public static function initTable(): void {
        if (self::$tableChecked || DB::pdo()->inTransaction()) {
            return;
        }
        self::$tableChecked = true;
        DB::query("CREATE TABLE IF NOT EXISTS `fabric_roll_specs` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `concern_id` INT NOT NULL,
            `color` VARCHAR(100) NOT NULL,
            `design_name` VARCHAR(150) NOT NULL,
            `qty` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            `net_weight` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            `unit` VARCHAR(50) NOT NULL DEFAULT 'Rolls',
            `last_unit_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            `total_value` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            `bills_count` INT NOT NULL DEFAULT 1,
            `last_bill_no` VARCHAR(100) NULL,
            `last_supplier_name` VARCHAR(150) NULL,
            `hsn_code` VARCHAR(50) NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `uk_concern_color_design` (`concern_id`, `color`, `design_name`),
            INDEX `idx_frs_concern` (`concern_id`),
            CONSTRAINT `fk_frs_concern` FOREIGN KEY (`concern_id`) REFERENCES `concerns`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    /**
     * Synchronize and consolidate fabric_roll_specs from purchase_bill_items.
     * Consolidates duplicate color & design entries by accumulating their quantities and weights.
     */
    public static function syncSpecs(?int $concernId = null): void {
        self::initTable();

        $whereClause = "";
        $params = [];
        if ($concernId) {
            $whereClause = "AND pbi.concern_id = ?";
            $params[] = $concernId;
        }

        // Aggregate unique (concern_id, color, design_name) from purchase_bill_items
        $sql = "SELECT pbi.concern_id,
                       UPPER(TRIM(pbi.color)) AS color,
                       UPPER(TRIM(COALESCE(NULLIF(pbi.pattern, ''), pbi.item_name))) AS design_name,
                       SUM(pbi.qty) AS total_qty,
                       SUM(COALESCE(pbi.net_weight, 0)) AS total_weight,
                       SUBSTRING_INDEX(GROUP_CONCAT(COALESCE(NULLIF(TRIM(pbi.unit), ''), 'Rolls') ORDER BY pbi.id DESC), ',', 1) AS unit,
                       SUBSTRING_INDEX(GROUP_CONCAT(pbi.unit_price ORDER BY pbi.id DESC), ',', 1) AS last_price,
                       SUM(pbi.total_price) AS total_val,
                       COUNT(DISTINCT pb.id) AS bills_count,
                       SUBSTRING_INDEX(GROUP_CONCAT(pb.bill_no ORDER BY pb.id DESC), ',', 1) AS last_bill,
                       SUBSTRING_INDEX(GROUP_CONCAT(COALESCE(s.name, '') ORDER BY pb.id DESC), ',', 1) AS last_supplier,
                       SUBSTRING_INDEX(GROUP_CONCAT(COALESCE(pbi.hsn_code, '') ORDER BY pbi.id DESC), ',', 1) AS last_hsn
                FROM purchase_bill_items pbi
                JOIN purchase_bills pb ON pbi.purchase_bill_id = pb.id
                LEFT JOIN suppliers s ON pb.supplier_id = s.id
                WHERE pbi.section_type = 'rolls'
                  AND pbi.color IS NOT NULL AND TRIM(pbi.color) != ''
                  AND (pbi.pattern IS NOT NULL AND TRIM(pbi.pattern) != '' OR pbi.item_name IS NOT NULL)
                  {$whereClause}
                GROUP BY pbi.concern_id, UPPER(TRIM(pbi.color)), UPPER(TRIM(COALESCE(NULLIF(pbi.pattern, ''), pbi.item_name)))";

        $rows = DB::fetchAll($sql, $params);

        if ($concernId) {
            DB::query("DELETE FROM fabric_roll_specs WHERE concern_id = ?", [$concernId]);
        } else {
            DB::query("DELETE FROM fabric_roll_specs");
        }

        foreach ($rows as $r) {
            $c = trim($r['color']);
            $d = trim($r['design_name']);
            if (empty($c) || empty($d)) continue;

            $unit = ($r['unit'] === 'RQ' || empty($r['unit'])) ? 'Rolls' : $r['unit'];

            DB::query(
                "INSERT INTO fabric_roll_specs 
                 (concern_id, color, design_name, qty, net_weight, unit, last_unit_price, total_value, bills_count, last_bill_no, last_supplier_name, hsn_code, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                 ON DUPLICATE KEY UPDATE 
                    qty = VALUES(qty),
                    net_weight = VALUES(net_weight),
                    unit = VALUES(unit),
                    last_unit_price = VALUES(last_unit_price),
                    total_value = VALUES(total_value),
                    bills_count = VALUES(bills_count),
                    last_bill_no = VALUES(last_bill_no),
                    last_supplier_name = VALUES(last_supplier_name),
                    hsn_code = VALUES(hsn_code),
                    updated_at = NOW()",
                [
                    (int)$r['concern_id'],
                    $c,
                    $d,
                    (float)$r['total_qty'],
                    (float)$r['total_weight'],
                    $unit,
                    (float)$r['last_price'],
                    (float)$r['total_val'],
                    (int)$r['bills_count'],
                    $r['last_bill'] ?: null,
                    $r['last_supplier'] ?: null,
                    $r['last_hsn'] ?: null,
                ]
            );
        }
    }

    /**
     * Record a newly entered roll item into the registry.
     * If the same Color & Design already exists for this concern,
     * the new quantity and weight will add to the previous count.
     */
    public static function recordRoll(
        int $concernId,
        string $color,
        string $designName,
        float $qty,
        float $netWeight,
        float $unitPrice,
        string $billNo = '',
        string $supplierName = '',
        string $hsnCode = '',
        string $unit = 'Rolls'
    ): array {
        self::initTable();

        $c = strtoupper(trim($color));
        $d = strtoupper(trim($designName));
        if (empty($c) || empty($d) || $qty <= 0) {
            return ['success' => false, 'message' => 'Invalid color, design or qty'];
        }

        if ($unit === 'RQ' || empty($unit)) {
            $unit = 'Rolls';
        }

        // Fetch existing record to calculate previous count
        $existing = DB::fetch(
            "SELECT * FROM fabric_roll_specs WHERE concern_id = ? AND color = ? AND design_name = ?",
            [$concernId, $c, $d]
        );

        $prevQty = $existing ? (float)$existing['qty'] : 0.0;
        $prevWeight = $existing ? (float)$existing['net_weight'] : 0.0;
        $prevValue = $existing ? (float)$existing['total_value'] : 0.0;
        $prevBills = $existing ? (int)$existing['bills_count'] : 0;

        $newQty = $prevQty + $qty;
        $newWeight = $prevWeight + $netWeight;
        $newValue = $prevValue + ($qty * $unitPrice);
        $newBills = $prevBills + 1;

        DB::query(
            "INSERT INTO fabric_roll_specs 
             (concern_id, color, design_name, qty, net_weight, unit, last_unit_price, total_value, bills_count, last_bill_no, last_supplier_name, hsn_code, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
             ON DUPLICATE KEY UPDATE 
                qty = qty + VALUES(qty),
                net_weight = net_weight + VALUES(net_weight),
                total_value = total_value + VALUES(total_value),
                last_unit_price = VALUES(last_unit_price),
                bills_count = bills_count + 1,
                last_bill_no = VALUES(last_bill_no),
                last_supplier_name = VALUES(last_supplier_name),
                hsn_code = COALESCE(VALUES(hsn_code), hsn_code),
                updated_at = NOW()",
            [
                $concernId,
                $c,
                $d,
                $qty,
                $netWeight,
                $unit,
                $unitPrice,
                ($qty * $unitPrice),
                1,
                $billNo ?: null,
                $supplierName ?: null,
                $hsnCode ?: null,
            ]
        );

        return [
            'success'       => true,
            'color'         => $c,
            'design_name'   => $d,
            'previous_qty'  => $prevQty,
            'added_qty'     => $qty,
            'new_total_qty' => $newQty,
            'new_weight'    => $newWeight,
        ];
    }

    /**
     * Get all saved unique roll specifications for quick selection and auto-fill in Purchase Billing Create
     */
    public static function getSavedSpecs(int $concernId): array {
        self::initTable();
        return DB::fetchAll(
            "SELECT id, color, design_name, qty, net_weight, unit, last_unit_price, hsn_code, bills_count, last_bill_no, last_supplier_name 
             FROM fabric_roll_specs 
             WHERE concern_id = ? 
             ORDER BY design_name ASC, color ASC",
            [$concernId]
        );
    }

    /**
     * Create or update a roll design specification on the fly.
     */
    public static function createOrUpdateSpec(
        int $concernId,
        string $color,
        string $designName,
        float $unitPrice = 0.0,
        string $hsnCode = '',
        float $initialQty = 0.0,
        float $netWeight = 0.0,
        string $supplierName = ''
    ): array {
        self::initTable();

        $c = strtoupper(trim($color));
        $d = strtoupper(trim($designName));
        if (empty($c) || empty($d)) {
            return ['success' => false, 'message' => 'Both Color / Shade and Design Name / Pattern are required.'];
        }

        $existing = DB::fetch(
            "SELECT * FROM fabric_roll_specs WHERE concern_id = ? AND color = ? AND design_name = ?",
            [$concernId, $c, $d]
        );

        if ($existing) {
            $updates = ["updated_at = NOW()"];
            $params = [];
            if ($unitPrice > 0) {
                $updates[] = "last_unit_price = ?";
                $params[] = $unitPrice;
            }
            if (!empty($hsnCode)) {
                $updates[] = "hsn_code = ?";
                $params[] = $hsnCode;
            }
            if ($initialQty > 0) {
                $updates[] = "qty = qty + ?";
                $params[] = $initialQty;
            }
            if ($netWeight > 0) {
                $updates[] = "net_weight = net_weight + ?";
                $params[] = $netWeight;
            }
            if (!empty($supplierName)) {
                $updates[] = "last_supplier_name = ?";
                $params[] = $supplierName;
            }
            $params[] = $existing['id'];
            DB::query("UPDATE fabric_roll_specs SET " . implode(', ', $updates) . " WHERE id = ?", $params);
            $record = DB::fetch("SELECT * FROM fabric_roll_specs WHERE id = ?", [$existing['id']]);
            return ['success' => true, 'action' => 'updated', 'spec' => $record];
        } else {
            DB::query(
                "INSERT INTO fabric_roll_specs 
                 (concern_id, color, design_name, qty, net_weight, unit, last_unit_price, total_value, bills_count, last_supplier_name, hsn_code, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, 'Rolls', ?, ?, ?, ?, ?, NOW(), NOW())",
                [
                    $concernId,
                    $c,
                    $d,
                    $initialQty,
                    $netWeight,
                    $unitPrice,
                    ($initialQty * $unitPrice),
                    $initialQty > 0 ? 1 : 0,
                    $supplierName ?: null,
                    $hsnCode ?: null,
                ]
            );
            $newId = (int)DB::lastInsertId();
            $record = DB::fetch("SELECT * FROM fabric_roll_specs WHERE id = ?", [$newId]);
            return ['success' => true, 'action' => 'created', 'spec' => $record];
        }
    }

    /**
     * Get list of unique colors for autocomplete
     */
    public static function getSavedColors(int $concernId): array {
        self::initTable();
        return DB::fetchAll(
            "SELECT DISTINCT color FROM (
                SELECT color FROM fabric_roll_specs WHERE concern_id = ?
                UNION
                SELECT UPPER(TRIM(color)) as color FROM purchase_bill_items WHERE concern_id = ? AND color IS NOT NULL AND TRIM(color) != ''
            ) t ORDER BY color ASC",
            [$concernId, $concernId]
        );
    }

    /**
     * Get list of unique designs / patterns for autocomplete
     */
    public static function getSavedDesigns(int $concernId): array {
        self::initTable();
        return DB::fetchAll(
            "SELECT DISTINCT design_name FROM (
                SELECT design_name FROM fabric_roll_specs WHERE concern_id = ?
                UNION
                SELECT UPPER(TRIM(pattern)) as design_name FROM purchase_bill_items WHERE concern_id = ? AND pattern IS NOT NULL AND TRIM(pattern) != ''
            ) t ORDER BY design_name ASC",
            [$concernId, $concernId]
        );
    }

    /**
     * Fetch consolidated roll stock records for Purchase Stocks module
     */
    public static function getConsolidatedStock(?int $concernId = null, string $search = ''): array {
        self::initTable();

        $where = [];
        $params = [];
        if ($concernId) {
            $where[] = "frs.concern_id = ?";
            $params[] = $concernId;
        }

        if (!empty($search)) {
            $like = "%{$search}%";
            $where[] = "(frs.color LIKE ? OR frs.design_name LIKE ? OR frs.last_bill_no LIKE ? OR frs.last_supplier_name LIKE ? OR frs.hsn_code LIKE ?)";
            $params = array_merge($params, [$like, $like, $like, $like, $like]);
        }

        $whereClause = !empty($where) ? ("WHERE " . implode(' AND ', $where)) : "";

        return DB::fetchAll(
            "SELECT frs.*, c.name AS concern_name, c.code AS concern_code
             FROM fabric_roll_specs frs
             LEFT JOIN concerns c ON frs.concern_id = c.id
             {$whereClause}
             ORDER BY frs.qty DESC, frs.design_name ASC, frs.color ASC",
            $params
        );
    }
}
