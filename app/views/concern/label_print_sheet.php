<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Print Labels - GRM Inventory') ?></title>
    <link rel="stylesheet" href="<?= \App\Core\Controller::url('/assets/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= \App\Core\Controller::url('/assets/css/bootstrap-icons.min.css') ?>">
    <style>
        body {
            background-color: #f1f5f9;
            margin: 0;
            padding: 1.5rem;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #0f172a;
        }
        .print-toolbar {
            background: #ffffff;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: gap;
            gap: 1rem;
        }
        .label-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            max-width: 980px;
            margin: 0 auto;
        }
        .label-grid.thermal-mode {
            grid-template-columns: 1fr;
            max-width: 420px;
            gap: 16px;
        }
        .label-sticker {
            background: #ffffff;
            border: 1.5px solid #0f172a;
            border-radius: 10px;
            padding: 8px 12px;
            display: flex;
            flex-direction: column;
            page-break-inside: avoid;
            break-inside: avoid;
            box-sizing: border-box;
            min-height: 115px;
            position: relative;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .label-header-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 6px;
            padding-bottom: 4px;
        }
        .concern-check-icon {
            background-color: #0d6efd;
            color: #ffffff;
            width: 17px;
            height: 17px;
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            margin-right: 6px;
            flex-shrink: 0;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        .concern-name-text {
            font-weight: 800;
            font-size: 0.82rem;
            color: #0f172a;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 170px;
        }
        .ref-pill-badge {
            background-color: #e0f2fe;
            color: #0284c7;
            border: 1px solid #bae6fd;
            font-family: monospace;
            font-size: 0.70rem;
            font-weight: 600;
            padding: 1px 7px;
            border-radius: 12px;
            white-space: nowrap;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        .label-divider {
            border-top: 1px dashed #cbd5e1;
            margin: 4px 0 6px 0;
        }
        .label-body-content {
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
        }
        .label-qr-wrapper {
            width: 76px;
            height: 76px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #ffffff;
        }
        .label-qr-box {
            width: 76px;
            height: 76px;
        }
        .label-details-stack {
            flex: 1;
            overflow: hidden;
            line-height: 1.35;
            font-size: 0.76rem;
        }
        .label-item-title {
            font-size: 0.70rem;
            font-weight: 700;
            color: #1e293b;
            text-transform: uppercase;
            margin-bottom: 6px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            letter-spacing: 0.02em;
        }
        .label-detail-row {
            color: #334155;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-size: 0.75rem;
        }
        .label-detail-row .detail-label {
            color: #64748b;
            font-weight: 500;
            margin-right: 3px;
        }
        .label-detail-row .detail-val {
            color: #0f172a;
            font-weight: 600;
        }

        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
            }
            .print-toolbar {
                display: none !important;
            }
            .label-grid {
                max-width: 100% !important;
                gap: 8px !important;
            }
            .label-sticker {
                border: 1.2px solid #000000 !important;
                box-shadow: none !important;
                break-inside: avoid;
            }
            .concern-check-icon {
                background-color: #000000 !important;
                color: #ffffff !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .ref-pill-badge {
                border: 1px solid #000000 !important;
                color: #000000 !important;
                background-color: #f1f5f9 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
</head>
<body>

<?php
$concern = \App\Core\Auth::concern();
$concernName = $labelData['concern_name'] ?? ($concern['name'] ?? 'GRM Inventory');
$refNo       = $labelData['ref_no'] ?? ($labelData['cost_or_ref'] ?? 'REF-001');
$headerTitle = $labelData['header_title'] ?? ($labelData['item_title'] ?? 'Garment Item');
$label1Name  = $labelData['label_1_name'] ?? 'Item';
$label1Val   = $labelData['label_1_val'] ?? ($labelData['item_title'] ?? '-');
$label2Name  = $labelData['label_2_name'] ?? 'Color';
$label2Val   = $labelData['label_2_val'] ?? ($labelData['color_or_spec'] ?? 'STANDARD');
$designVal   = $labelData['design'] ?? ($labelData['label_3_val'] ?? ($labelData['pattern'] ?? 'STANDARD'));
$priceVal    = (float)($labelData['price'] ?? ($labelData['price_mrp'] ?? 0));
$tagCode     = $labelData['tag_code'] ?? ($labelData['item_code'] ?? 'SKU');
$qrText      = $qrCodeText ?? ($labelData['qr_payload'] ?? $tagCode);
$badge       = $labelData['section_badge'] ?? 'STANDARD';
?>

<div class="print-toolbar">
    <div>
        <h5 class="mb-0 fw-bold d-flex align-items-center gap-2">
            <i class="bi bi-printer text-primary"></i> 
            <span>Print Sheet Preview</span>
            <span class="badge bg-primary-subtle text-primary border border-primary fs-6">Job #<?= (int)($jobId ?? 1) ?></span>
        </h5>
        <small class="text-muted">
            <span class="badge bg-light text-dark border me-1"><?= htmlspecialchars($labelData['section_title'] ?? 'PRODUCT LABEL') ?></span>
            <span class="badge bg-primary-subtle text-primary border me-1"><?= htmlspecialchars($badge) ?></span>
            <?php if (!empty($multiLabels)): ?>
                Printing <strong><?= (int)$qty ?> labels</strong> across <strong><?= (int)($variantCount ?? count($multiLabels)) ?> variants</strong> for Batch <strong>#<?= htmlspecialchars($batchNo ?? 'BATCH') ?></strong> &bull; Concern: <strong><?= htmlspecialchars($concernName) ?></strong>
                <?php
                // Build per-variant summary from multiLabels
                $variantSummary = [];
                foreach ($multiLabels as $_lbl) {
                    $sz = $_lbl['variant_size'] ?? '—';
                    if (!isset($variantSummary[$sz])) {
                        $variantSummary[$sz] = ['sku' => $_lbl['sku_code'] ?? '', 'count' => 0];
                    }
                    $variantSummary[$sz]['count']++;
                }
                ?>
                <span class="ms-2">|</span>
                <?php foreach ($variantSummary as $sz => $info): ?>
                    <span style="background:#fbbf24;color:#0f172a;font-weight:700;font-size:0.65rem;padding:1px 7px;border-radius:8px;margin-left:3px;font-family:monospace;">
                        <?= strtoupper(htmlspecialchars($sz)) ?> &times;<?= $info['count'] ?>
                    </span>
                <?php endforeach; ?>
            <?php else: ?>
                Printing <strong><?= (int)$qty ?> copies</strong> of <strong><?= htmlspecialchars(!empty($headerTitle) ? $headerTitle : $label1Val) ?></strong> (<?= htmlspecialchars($tagCode) ?>) &bull; Concern: <strong><?= htmlspecialchars($concernName) ?></strong>
            <?php endif; ?>
        </small>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <button class="btn btn-primary btn-sm fw-bold px-3 py-1.5 shadow-sm" onclick="window.print()">
            <i class="bi bi-printer-fill me-1"></i> Print Labels Now
        </button>
    </div>
</div>

<div class="label-grid" id="labelGrid">
    <?php if (!empty($multiLabels)): ?>
        <?php foreach ($multiLabels as $lbl): 
            $cName     = $lbl['concern_name'] ?? $concernName;
            $rNo       = $lbl['ref_no'] ?? 'REF-001';
            $hTitle    = $lbl['header_title'] ?? ($lbl['item_title'] ?? '');
            $l1Name    = $lbl['label_1_name'] ?? 'Program';
            $l1Val     = $lbl['label_1_val'] ?? '-';
            $l2Name    = $lbl['label_2_name'] ?? 'Color';
            $l2Val     = $lbl['label_2_val'] ?? 'STANDARD';
            $dVal      = $lbl['design'] ?? ($lbl['label_3_val'] ?? 'STANDARD');
            $pVal      = (float)($lbl['price'] ?? ($lbl['price_mrp'] ?? 0));
            $tCode     = $lbl['tag_code'] ?? ($lbl['item_code'] ?? 'SKU');
            $iQr       = $lbl['qr_payload'] ?? $tCode;
        ?>
            <div class="label-sticker">
                <div class="label-header-bar">
                    <div class="d-flex align-items-center">
                        <span class="concern-check-icon"><i class="bi bi-check-lg"></i></span>
                        <span class="concern-name-text"><?= htmlspecialchars($cName) ?></span>
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        <?php if (!empty($lbl['variant_size'])): ?>
                            <span style="background:#fbbf24;color:#0f172a;font-weight:700;font-size:0.65rem;padding:1px 6px;border-radius:8px;font-family:monospace;-webkit-print-color-adjust:exact;print-color-adjust:exact;"><?= strtoupper(htmlspecialchars($lbl['variant_size'])) ?></span>
                        <?php endif; ?>
                        <span class="ref-pill-badge"><?= htmlspecialchars($rNo) ?></span>
                    </div>
                </div>
                <div class="label-divider"></div>
                <div class="label-body-content">
                    <div class="label-qr-wrapper">
                        <div class="label-qr-box qr-container" data-code="<?= htmlspecialchars($iQr) ?>"></div>
                    </div>
                    <div class="label-details-stack">
                        <?php if (!empty($hTitle)): ?>
                            <div class="label-item-title"><?= htmlspecialchars($hTitle) ?></div>
                        <?php endif; ?>
                        <div class="label-detail-row" style="margin-bottom: 2px;">
                            <span class="detail-label">Price:</span>
                            <span class="detail-val fw-bold text-success" style="font-size: 0.82rem;">₹<?= number_format($pVal, 2) ?></span>
                        </div>
                        <div class="label-detail-row">
                            <span class="detail-label">Code:</span>
                            <span class="detail-val font-monospace text-primary fw-bold" style="font-size: 0.80rem;"><?= htmlspecialchars($tCode) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <?php for ($i = 0; $i < $qty; $i++): ?>
            <div class="label-sticker">
                <!-- Top Header: Concern Name Dynamically & Reference Badge (Image 2 Reference) -->
                <div class="label-header-bar">
                    <div class="d-flex align-items-center">
                        <span class="concern-check-icon"><i class="bi bi-check-lg"></i></span>
                        <span class="concern-name-text"><?= htmlspecialchars($concernName) ?></span>
                    </div>
                    <span class="ref-pill-badge"><?= htmlspecialchars($refNo) ?></span>
                </div>

                <!-- Dashed Separator Line -->
                <div class="label-divider"></div>

                <!-- Body: QR on Left, Details Stack on Right -->
                <div class="label-body-content">
                    <div class="label-qr-wrapper">
                        <div class="label-qr-box qr-container" data-code="<?= htmlspecialchars($qrText) ?>"></div>
                    </div>

                    <div class="label-details-stack">
                        <?php if (!empty($headerTitle)): ?>
                            <div class="label-item-title"><?= htmlspecialchars($headerTitle) ?></div>
                        <?php endif; ?>
                        <div class="label-detail-row" style="margin-bottom: 2px;">
                            <span class="detail-label">Price:</span>
                            <span class="detail-val fw-bold text-success" style="font-size: 0.82rem;">₹<?= number_format($priceVal, 2) ?></span>
                        </div>
                        <div class="label-detail-row">
                            <span class="detail-label">Code:</span>
                            <span class="detail-val font-monospace text-primary fw-bold" style="font-size: 0.80rem;"><?= htmlspecialchars($tagCode) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endfor; ?>
    <?php endif; ?>
</div>

<script src="<?= \App\Core\Controller::url('/assets/js/qrcode.min.js') ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const containers = document.querySelectorAll('.qr-container');
    containers.forEach(el => {
        const code = el.getAttribute('data-code') || "<?= htmlspecialchars($qrText ?? '') ?>";
        if (code) {
            new QRCode(el, {
                text: code,
                width: 80,
                height: 80,
                colorDark : "#000000",
                colorLight : "#ffffff",
                correctLevel : QRCode.CorrectLevel.M
            });
        }
    });
});

function setLayout(mode) {
    const grid = document.getElementById('labelGrid');
    const btnA4 = document.getElementById('btnLayoutA4');
    const btnThermal = document.getElementById('btnLayoutThermal');
    if (mode === 'thermal') {
        grid.classList.add('thermal-mode');
        btnThermal.classList.add('active');
        btnA4.classList.remove('active');
    } else {
        grid.classList.remove('thermal-mode');
        btnA4.classList.add('active');
        btnThermal.classList.remove('active');
    }
}
</script>

</body>
</html>
