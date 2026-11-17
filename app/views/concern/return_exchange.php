<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1 text-dark">Customer Returns & Exchanges</h4>
            <p class="text-secondary small mb-0">Process customer returns and replacement exchanges. Re-enters returned stock into warehouse balance via <code>StockService::move()</code>.</p>
        </div>
        <button class="btn btn-warning shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAddReturn">
            <i class="bi bi-arrow-left-right me-1"></i> New Return / Exchange
        </button>
    </div>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-arrow-counterclockwise me-2 text-warning"></i> Return Transactions</h6>
            <span class="badge bg-light text-dark border"><?= count($returns) ?> Records</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-custom">
                <thead>
                    <tr>
                        <th>Return No</th>
                        <th>Type</th>
                        <th>Customer</th>
                        <th>Returned Variant</th>
                        <th>Restocked (+Qty)</th>
                        <th>Exchange Replacement</th>
                        <th>Reason</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($returns)): ?>
                        <tr><td colspan="8" class="text-center py-4 text-muted">No returns or exchanges recorded.</td></tr>
                    <?php else: ?>
                        <?php foreach ($returns as $r): ?>
                            <tr>
                                <td><span class="badge bg-dark font-monospace"><?= htmlspecialchars($r['return_no']) ?></span></td>
                                <td>
                                    <span class="badge <?= $r['type'] === 'exchange' ? 'bg-info text-dark' : 'bg-secondary' ?>">
                                        <?= strtoupper($r['type']) ?>
                                    </span>
                                </td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($r['customer_name']) ?></td>
                                <td>
                                    <div class="fw-semibold"><?= htmlspecialchars($r['returned_variant_name']) ?></div>
                                    <small class="badge bg-light text-dark border"><?= htmlspecialchars($r['returned_sku']) ?></small>
                                </td>
                                <td><span class="text-success fw-bold fs-6">+<?= number_format($r['returned_qty'], 2) ?></span></td>
                                <td>
                                    <?php if ($r['type'] === 'exchange' && $r['exchange_variant_name']): ?>
                                        <div class="text-dark small fw-semibold"><?= htmlspecialchars($r['exchange_variant_name']) ?></div>
                                        <small class="text-danger fw-bold">-<?= number_format($r['exchange_qty'], 2) ?></small>
                                    <?php else: ?>
                                        <span class="text-muted small">None (Pure Return)</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted"><?= htmlspecialchars($r['reason'] ?: '-') ?></td>
                                <td><?= date('d M Y', strtotime($r['return_date'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAddReturn" tabindex="-1">
    <div class="modal-dialog">
        <form action="<?= \App\Core\Controller::url('/m/return_exchange/store') ?>" method="POST" class="modal-content">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Process Return / Exchange</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Transaction Type</label>
                        <select name="type" id="ret_type" class="form-select" onchange="toggleExchangeFields(this)">
                            <option value="return">Customer Return (Restock)</option>
                            <option value="exchange">Exchange (Swap Variant)</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Customer *</label>
                        <select name="customer_id" class="form-select" required>
                            <option value="">Select Customer</option>
                            <?php foreach ($customers as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Returned Variant (Adds back to Stock) *</label>
                    <select name="returned_variant_id" class="form-select" required>
                        <option value="">Select Variant Returned</option>
                        <?php foreach ($variants as $v): ?>
                            <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['sku_code']) ?> &bull; <?= htmlspecialchars($v['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Returned Quantity *</label>
                    <input type="number" step="0.01" name="returned_qty" class="form-control" required placeholder="0.00">
                </div>

                <div id="exchange_group" style="display: none;" class="p-3 bg-light rounded border mb-3">
                    <div class="fw-bold small text-primary mb-2">Replacement Exchange Item:</div>
                    <div class="mb-2">
                        <label class="form-label small">Exchange Variant Out</label>
                        <select name="exchange_variant_id" class="form-select">
                            <option value="">Select Replacement Variant</option>
                            <?php foreach ($variants as $v): ?>
                                <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['sku_code']) ?> &bull; <?= htmlspecialchars($v['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label small">Exchange Quantity Out</label>
                        <input type="number" step="0.01" name="exchange_qty" class="form-control" placeholder="0.00">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Return Reason</label>
                    <textarea name="reason" class="form-control" rows="2" placeholder="Wrong size, color issue, transit damage"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Process Return & Rebalance Stock</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleExchangeFields(sel) {
    document.getElementById('exchange_group').style.display = sel.value === 'exchange' ? 'block' : 'none';
}
</script>
