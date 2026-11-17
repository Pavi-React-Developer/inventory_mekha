<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= \App\Core\Controller::url('/assets/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= \App\Core\Controller::url('/assets/css/bootstrap-icons.min.css') ?>">
    <style>
        body {
            background-color: #f1f5f9;
            margin: 0;
            padding: 1.5rem;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
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
        }
        .label-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            max-width: 980px;
            margin: 0 auto;
        }
        .label-sticker {
            background: #ffffff;
            border: 2px dashed #94a3b8;
            border-radius: 8px;
            padding: 10px;
            display: flex;
            gap: 10px;
            align-items: center;
            page-break-inside: avoid;
            box-sizing: border-box;
            height: 145px;
        }
        .label-qr-box {
            width: 85px;
            height: 85px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #ffffff;
        }
        .label-info {
            flex: 1;
            overflow: hidden;
            line-height: 1.25;
        }
        .label-brand {
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            color: #2563eb;
            letter-spacing: 0.05em;
        }
        .label-title {
            font-size: 0.82rem;
            font-weight: 700;
            color: #0f172a;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-top: 1px;
            margin-bottom: 2px;
        }
        .label-sku {
            font-size: 0.72rem;
            font-family: monospace;
            background: #f1f5f9;
            padding: 1px 4px;
            border-radius: 3px;
            display: inline-block;
        }
        .label-meta {
            font-size: 0.7rem;
            color: #64748b;
            margin-top: 3px;
        }
        .label-price-box {
            margin-top: 4px;
            display: flex;
            align-items: baseline;
            gap: 6px;
        }
        .label-mrp {
            font-size: 0.88rem;
            font-weight: 800;
            color: #b91c1c;
        }
        .label-code-tag {
            font-size: 0.65rem;
            font-family: monospace;
            color: #475569;
            text-align: center;
            margin-top: 3px;
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
                border: 1px solid #000000 !important;
                break-inside: avoid;
            }
        }
    </style>
</head>
<body>

<div class="print-toolbar">
    <div>
        <h5 class="mb-0 fw-bold"><i class="bi bi-printer text-primary me-2"></i> Print Sheet Preview (Job #<?= (int)$jobId ?>)</h5>
        <small class="text-muted"><?= (int)$qty ?> label copies generated for <strong><?= htmlspecialchars($variant['name']) ?></strong></small>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary" onclick="window.close()">Close</button>
        <button class="btn btn-primary fw-bold" onclick="window.print()">
            <i class="bi bi-printer-fill me-1"></i> Print Labels Now
        </button>
    </div>
</div>

<div class="label-grid">
    <?php 
    $concern = \App\Core\Auth::concern();
    $attrs = !empty($variant['attributes']) ? json_decode($variant['attributes'], true) : [];
    for ($i = 0; $i < $qty; $i++): 
    ?>
        <div class="label-sticker">
            <div>
                <div class="label-qr-box qr-container" data-code="<?= htmlspecialchars($qrCodeText) ?>"></div>
                <div class="label-code-tag"><?= htmlspecialchars($qrCodeText) ?></div>
            </div>

            <div class="label-info">
                <div class="label-brand"><?= htmlspecialchars($concern['name'] ?? 'INVENTO') ?></div>
                <div class="label-title" title="<?= htmlspecialchars($variant['name']) ?>"><?= htmlspecialchars($variant['name']) ?></div>
                <div class="d-flex align-items-center gap-1 mb-1">
                    <span class="label-sku"><?= htmlspecialchars($variant['sku_code']) ?></span>
                    <span class="badge bg-dark" style="font-size: 0.65rem;"><?= htmlspecialchars($variant['size'] ?: 'Free') ?></span>
                </div>
                <div class="label-meta">
                    Cat: <?= htmlspecialchars($variant['category_name'] ?? 'General') ?> &bull; 
                    <?= number_format($variant['weight'], 3) ?> <?= htmlspecialchars($variant['weight_unit']) ?>
                </div>
                <div class="label-price-box">
                    <span class="label-mrp">MRP ₹<?= number_format($variant['mrp'], 2) ?></span>
                    <span class="text-muted small" style="font-size: 0.68rem;">(Incl. of all taxes)</span>
                </div>
            </div>
        </div>
    <?php endfor; ?>
</div>

<script src="<?= \App\Core\Controller::url('/assets/js/qrcode.min.js') ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const containers = document.querySelectorAll('.qr-container');
    const code = "<?= htmlspecialchars($qrCodeText) ?>";
    containers.forEach(el => {
        new QRCode(el, {
            text: code,
            width: 80,
            height: 80,
            colorDark : "#000000",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.M
        });
    });
});
</script>

</body>
</html>
