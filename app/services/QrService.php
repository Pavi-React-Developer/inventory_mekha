<?php
// app/services/QrService.php - QR Code generator and parser

namespace App\Services;

use App\Core\DB;

class QrService {
    /**
     * Build standard payload: C{cat}-S{subcat}-V{var}
     */
    public static function generatePayload(int $categoryId, int $subcategoryId, int $variantId): string {
        return "C{$categoryId}-S{$subcategoryId}-V{$variantId}";
    }

    /**
     * Parse standard payload: C4-S12-V1087
     */
    public static function parsePayload(string $code): ?array {
        $code = trim($code);
        if (preg_match('/^C(\d+)-S(\d+)-V(\d+)$/i', $code, $matches)) {
            return [
                'category_id'    => (int)$matches[1],
                'subcategory_id' => (int)$matches[2],
                'variant_id'     => (int)$matches[3],
                'raw'            => $code,
            ];
        }
        return null;
    }

    /**
     * Resolve QR scan strictly within session concern boundary
     */
    public static function lookupVariant(string $code, int $concernId): ?array {
        $parsed = self::parsePayload($code);
        if (!$parsed) {
            return null;
        }

        $sql = "SELECT v.*, 
                       c.name AS category_name, 
                       s.name AS subcategory_name, 
                       b.name AS brand_name,
                       COALESCE(stk.total_qty, 0) AS current_stock
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
                WHERE v.id = ? 
                  AND v.category_id = ? 
                  AND v.subcategory_id = ? 
                  AND v.concern_id = ? 
                LIMIT 1";

        $res = DB::fetch($sql, [
            $concernId,
            $parsed['variant_id'],
            $parsed['category_id'],
            $parsed['subcategory_id'],
            $concernId
        ]);

        if ($res && !empty($res['attributes'])) {
            $res['attributes_parsed'] = json_decode($res['attributes'], true) ?: [];
        }

        return $res ?: null;
    }

    /**
     * Generates a clean vector data-uri or SVG QR using pure PHP or frontend script fallback
     */
    public static function getQrImageUrl(string $data, int $size = 150): string {
        // Generates an inline SVG or URL for standard barcode/qr rendering
        $encoded = urlencode($data);
        return "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data={$encoded}";
    }
}
