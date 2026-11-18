<?php
// app/controllers/LabelController.php - QR Label Generation and Scanning

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;
use App\Core\Auth;
use App\Models\Variant;
use App\Services\QrService;
use App\Services\AuditLog;
use Exception;

class LabelController extends Controller {
    public function index(): void {
        $cid = Auth::concernId();
        $variantModel = new Variant();
        $variants = $variantModel->allWithDetails();

        $sql = "SELECT lj.*, v.name AS variant_name, v.sku_code, u.name AS user_name,
                       v.category_id, v.subcategory_id, con.name AS concern_name, con.code AS concern_code
                FROM label_jobs lj
                JOIN variants v ON lj.variant_id = v.id
                LEFT JOIN concerns con ON lj.concern_id = con.id
                LEFT JOIN users u ON lj.printed_by = u.id";
        $params = [];
        if ($cid) {
            $sql .= " WHERE lj.concern_id = ?";
            $params[] = $cid;
        }
        $sql .= " ORDER BY lj.id DESC LIMIT 10";
        $recentJobs = DB::fetchAll($sql, $params);

        $this->render('concern/label_generation', [
            'pageTitle'     => 'QR Label Generation & Barcode Print',
            'currentModule' => 'label_generation',
            'variants'      => $variants,
            'recentJobs'    => $recentJobs,
            'selectedCid'   => $cid,
        ]);
    }

    public function generate(): void {
        $this->validateCsrf();
        $variantId = (int)($_POST['variant_id'] ?? 0);
        $qty       = max(1, min(500, (int)($_POST['qty'] ?? 1)));

        $varModel = new Variant();
        $variant = $varModel->findWithDetails($variantId);

        if (!$variant) {
            $this->flash('error', 'Selected variant was not found or belongs to another concern.');
            $this->redirect('/m/label_generation');
        }

        $cid = Auth::concernId() ?: (int)$variant['concern_id'];

        // Save job for audit
        DB::query(
            "INSERT INTO label_jobs (concern_id, variant_id, qty, printed_by, created_at) VALUES (?, ?, ?, ?, NOW())",
            [$cid, $variantId, $qty, Auth::id()]
        );
        $jobId = (int)DB::lastInsertId();

        AuditLog::log('GENERATE_LABELS', 'label_jobs', $jobId, null, [
            'variant_id' => $variantId,
            'qty'        => $qty,
            'sku_code'   => $variant['sku_code']
        ]);

        $qrCodeText = QrService::generatePayload($variant['category_id'], $variant['subcategory_id'], $variant['id']);

        // Render printable sheet view without sidebar/navbar
        $this->render('concern/label_print_sheet', [
            'pageTitle'   => "Print Labels - {$variant['sku_code']}",
            'noLayout'    => true,
            'variant'     => $variant,
            'qty'         => $qty,
            'qrCodeText'  => $qrCodeText,
            'jobId'       => $jobId,
        ]);
    }

    public function scanPage(): void {
        $this->render('concern/scan', [
            'pageTitle'     => 'Live QR / Barcode Scanner',
            'currentModule' => 'stocks',
        ]);
    }

    /**
     * AJAX endpoint to lookup scanned QR code
     */
    public function lookup(): void {
        $cid = Auth::concernId();
        $code = trim($_GET['code'] ?? $_POST['code'] ?? '');

        if (empty($code)) {
            $this->renderJson(['success' => false, 'message' => 'Empty barcode/QR code'], 400);
        }

        // If Super Admin has not filtered to a single concern, detect variant concern
        if (Auth::isSuperAdmin() && !$cid) {
            $parsed = QrService::parsePayload($code);
            if ($parsed) {
                $varCid = DB::fetchValue("SELECT concern_id FROM variants WHERE id = ?", [$parsed['variant_id']]);
                if ($varCid) {
                    $cid = (int)$varCid;
                }
            }
        }

        if (!$cid) {
            $this->renderJson(['success' => false, 'message' => 'Variant not found.'], 404);
        }

        $variant = QrService::lookupVariant($code, $cid);

        if (!$variant) {
            $this->renderJson([
                'success' => false,
                'message' => "QR Code '{$code}' not found or belongs to another concern under tenant isolation."
            ], 404);
        }

        $this->renderJson([
            'success' => true,
            'data'    => $variant,
        ]);
    }

    public function exportJobs(): void {
        $cid = Auth::concernId();
        $format = strtolower($_GET['format'] ?? 'excel');
        $period = $_GET['period'] ?? 'all';
        $singleDate = $_GET['single_date'] ?? null;
        $fromDate = $_GET['from_date'] ?? null;
        $toDate = $_GET['to_date'] ?? null;

        $dateScope = \App\Services\ExportService::resolveDateRange($period, $singleDate, $fromDate, $toDate);

        $sql = "SELECT lj.*, v.name AS variant_name, v.sku_code, u.name AS user_name,
                       con.name AS concern_name, con.code AS concern_code
                FROM label_jobs lj
                JOIN variants v ON lj.variant_id = v.id
                LEFT JOIN concerns con ON lj.concern_id = con.id
                LEFT JOIN users u ON lj.printed_by = u.id";
        $params = [];
        $where = [];
        if ($cid) {
            $where[] = "lj.concern_id = ?";
            $params[] = $cid;
        }
        if ($dateScope['start_date'] && $dateScope['end_date']) {
            $where[] = "DATE(lj.created_at) BETWEEN ? AND ?";
            $params[] = $dateScope['start_date'];
            $params[] = $dateScope['end_date'];
        }
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        $sql .= " ORDER BY lj.id DESC";
        $jobs = DB::fetchAll($sql, $params);

        $headers = [
            'Job ID', 'Date & Time', 'SKU Code', 'Variant Name', 'Quantity', 
            'Barcode Type', 'Printed By', 'Concern'
        ];

        $rows = [];
        $totQty = 0;
        foreach ($jobs as $j) {
            $qty = (int)$j['quantity'];
            $totQty += $qty;
            $rows[] = [
                '#JOB-' . str_pad($j['id'], 4, '0', STR_PAD_LEFT),
                date('d M Y, h:i A', strtotime($j['created_at'])),
                $j['sku_code'],
                $j['variant_name'],
                $qty,
                strtoupper($j['barcode_type'] ?? 'QR CODE'),
                $j['user_name'] ?? 'System',
                $j['concern_name'] ?? 'Consolidated'
            ];
        }

        $totals = ['Total Jobs: ' . count($jobs), '', '', '', $totQty, '', '', ''];
        $metadata = [
            'Report Title' => 'QR Label & Barcode Generation Batch History',
            'Scope / Period' => $dateScope['label'],
            'Generated At' => date('d M Y, h:i A'),
            'Total Jobs' => count($jobs),
            'Total Labels Printed' => number_format($totQty)
        ];

        $filename = 'label_jobs_' . date('Ymd_His');

        if ($format === 'pdf') {
            \App\Services\ExportService::renderPdfView('Barcode & Label Print Jobs Report', $metadata, $headers, $rows, $totals);
        } elseif ($format === 'csv') {
            \App\Services\ExportService::downloadCsv($filename, $headers, $rows, $totals);
        } else {
            \App\Services\ExportService::downloadExcel($filename, 'Barcode & Label Print Jobs Report', $metadata, $headers, $rows, $totals);
        }
    }
}
