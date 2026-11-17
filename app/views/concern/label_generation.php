<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1 text-dark">Barcode & QR Label Generation</h4>
            <p class="text-secondary small mb-0">
                Generate production labels embedding standard format <code>C{cat}-S{subcat}-V{var}</code>. Repeating N prints across standard A4 sheet or thermal roll.
            </p>
        </div>
        <a href="<?= \App\Core\Controller::url('/scan') ?>" class="btn btn-outline-primary">
            <i class="bi bi-qr-code-scan me-1"></i> Open Live QR Scanner
        </a>
    </div>

    <div class="row g-4 mb-4">
        <!-- Generator Form Card -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-printer me-2 text-primary"></i> Print Job Configuration</h6>
                </div>
                <form action="<?= \App\Core\Controller::url('/m/label_generation/generate') ?>" method="POST" target="_blank" class="card-body p-4">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Select Product Variant *</label>
                        <select name="variant_id" id="variant_select" class="form-select" required onchange="updateVariantPreview()">
                            <option value="">-- Choose Variant --</option>
                            <?php foreach ($variants as $v): ?>
                                <option value="<?= $v['id'] ?>" 
                                        data-sku="<?= htmlspecialchars($v['sku_code']) ?>"
                                        data-name="<?= htmlspecialchars($v['name']) ?>"
                                        data-size="<?= htmlspecialchars($v['size'] ?: 'Standard') ?>"
                                        data-mrp="<?= htmlspecialchars($v['mrp']) ?>"
                                        data-price="<?= htmlspecialchars($v['price']) ?>"
                                        data-qr="C<?= $v['category_id'] ?>-S<?= $v['subcategory_id'] ?>-V<?= $v['id'] ?>"
                                        <?= (isset($_GET['variant_id']) && $_GET['variant_id'] == $v['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($v['sku_code']) ?> &bull; <?= htmlspecialchars($v['name']) ?> (<?= htmlspecialchars($v['size'] ?: 'Free') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-semibold">Number of Label Copies (N Prints) *</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-files"></i></span>
                            <input type="number" name="qty" class="form-control" min="1" max="500" value="12" required>
                            <span class="input-group-text bg-light">Labels</span>
                        </div>
                        <small class="text-muted">Standard A4 page accommodates 12 or 24 sticker labels.</small>
                    </div>

                    <!-- Live Label Spec Preview -->
                    <div class="p-3 border rounded bg-light mb-4">
                        <div class="fw-bold text-dark small mb-2"><i class="bi bi-eye me-1"></i> Label Payload Preview:</div>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="font-monospace text-primary fw-bold fs-6" id="preview_qr">C?-S?-V?</div>
                                <div class="text-muted small" id="preview_name">Select a variant to preview label</div>
                            </div>
                            <span class="badge bg-dark font-monospace" id="preview_mrp">MRP: ₹0.00</span>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold shadow-sm">
                        <i class="bi bi-file-earmark-pdf me-1"></i> Generate Printable Sheet (N Labels)
                    </button>
                </form>
            </div>
        </div>

        <!-- Recent Label Print Jobs -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-clock-history me-2 text-primary"></i> Recent Label Generation Jobs (Audit)</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 table-custom">
                        <thead>
                            <tr>
                                <th>Job ID</th>
                                <th>Printed Variant</th>
                                <th>QR Code Reference</th>
                                <th>Copies</th>
                                <th>Printed By</th>
                                <th>Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentJobs)): ?>
                                <tr><td colspan="6" class="text-center py-4 text-muted">No label jobs printed yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($recentJobs as $job): 
                                    $qr = \App\Services\QrService::generatePayload($job['category_id'], $job['subcategory_id'], $job['variant_id']);
                                ?>
                                    <tr>
                                        <td>#<?= (int)$job['id'] ?></td>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= htmlspecialchars($job['variant_name']) ?></div>
                                            <small class="badge bg-light text-dark border"><?= htmlspecialchars($job['sku_code']) ?></small>
                                        </td>
                                        <td><code class="text-primary font-monospace"><?= htmlspecialchars($qr) ?></code></td>
                                        <td><span class="badge bg-info text-dark font-monospace"><?= (int)$job['qty'] ?> Prints</span></td>
                                        <td class="small text-muted"><?= htmlspecialchars($job['user_name'] ?? 'System') ?></td>
                                        <td class="small text-muted font-monospace"><?= date('d M H:i', strtotime($job['created_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function updateVariantPreview() {
    const sel = document.getElementById('variant_select');
    const opt = sel.options[sel.selectedIndex];
    if (opt && opt.value) {
        document.getElementById('preview_qr').innerText = opt.dataset.qr;
        document.getElementById('preview_name').innerText = opt.dataset.name + ' (' + opt.dataset.size + ')';
        document.getElementById('preview_mrp').innerText = 'MRP: ₹' + parseFloat(opt.dataset.mrp).toFixed(2);
    } else {
        document.getElementById('preview_qr').innerText = 'C?-S?-V?';
        document.getElementById('preview_name').innerText = 'Select a variant to preview label';
        document.getElementById('preview_mrp').innerText = 'MRP: ₹0.00';
    }
}
document.addEventListener('DOMContentLoaded', updateVariantPreview);
</script>
