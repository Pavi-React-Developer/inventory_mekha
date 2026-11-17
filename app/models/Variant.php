<?php
// app/models/Variant.php
namespace App\Models;
use App\Core\Model;
use App\Core\DB;
use App\Core\Auth;

class Variant extends Model {
    protected string $table = 'variants';
    protected bool $tenantScoped = true;

    public function allWithDetails(): array {
        $cid = Auth::concernId();
        $sql = "SELECT v.*, 
                       c.name AS category_name, 
                       s.name AS subcategory_name, 
                       b.name AS brand_name,
                       COALESCE(stk.total_qty, 0) AS total_stock
                FROM variants v
                LEFT JOIN categories c ON v.category_id = c.id
                LEFT JOIN subcategories s ON v.subcategory_id = s.id
                LEFT JOIN brands b ON v.brand_id = b.id
                LEFT JOIN (
                    SELECT variant_id, SUM(qty) AS total_qty 
                    FROM stocks 
                    WHERE concern_id = ? 
                    GROUP BY variant_id
                ) stk ON v.id = stk.variant_id
                WHERE v.concern_id = ?
                ORDER BY v.id DESC";
        return DB::fetchAll($sql, [$cid, $cid]);
    }

    public function findWithDetails(int $id): ?array {
        $cid = Auth::concernId();
        $sql = "SELECT v.*, 
                       c.name AS category_name, 
                       s.name AS subcategory_name, 
                       b.name AS brand_name,
                       COALESCE(stk.total_qty, 0) AS total_stock
                FROM variants v
                LEFT JOIN categories c ON v.category_id = c.id
                LEFT JOIN subcategories s ON v.subcategory_id = s.id
                LEFT JOIN brands b ON v.brand_id = b.id
                LEFT JOIN (
                    SELECT variant_id, SUM(qty) AS total_qty 
                    FROM stocks 
                    WHERE concern_id = ? 
                    GROUP BY variant_id
                ) stk ON v.id = stk.variant_id
                WHERE v.id = ? AND v.concern_id = ?
                LIMIT 1";
        return DB::fetch($sql, [$cid, $id, $cid]);
    }
}
