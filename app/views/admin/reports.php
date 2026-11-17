<div class="container-fluid px-0">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <div class="text-secondary small mb-1">
                <a href="<?= \App\Core\Controller::url('/admin/dashboard') ?>" class="text-decoration-none text-muted">Super Admin</a>
                <span class="mx-1">/</span>
                <span class="text-dark fw-semibold">Consolidated Reports</span>
            </div>
            <h4 class="fw-bold mb-1 text-dark">Consolidated Multi-Concern Performance Report</h4>
            <p class="text-secondary small mb-0">Cross-tenant inventory valuation, revenue volume, and procurement metrics aggregated across all concerns.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge rounded-pill px-3 py-2 fw-semibold" style="background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;">
                <i class="bi bi-calendar-check me-1"></i> <?= htmlspecialchars($rangeLabel ?? 'All Time') ?>
            </span>
        </div>
    </div>

    <!-- Export & Date Range Filter Toolbar -->
    <div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-4" style="border: 1px solid #e2e8f0 !important;">
        <form method="GET" action="<?= \App\Core\Controller::url('/admin/reports') ?>" id="report_filter_form" class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-3">
                <div style="width: 44px; height: 44px; background: #ecfdf5; color: #16a34a; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.35rem; flex-shrink: 0; border: 1px solid #bbf7d0;">
                    <i class="bi bi-file-earmark-excel"></i>
                </div>
                <div>
                    <h6 class="fw-bold text-dark mb-0">Export & Date Scope Filter</h6>
                    <small class="text-muted">Generate reports as per single day, monthly, 3/6 months, or custom period</small>
                </div>
            </div>

            <div class="d-flex flex-wrap align-items-center gap-2">
                <!-- Period Dropdown -->
                <div class="d-flex align-items-center gap-1">
                    <label class="small text-muted mb-0 fw-semibold" style="font-size: 0.76rem;">Period:</label>
                    <select name="period" id="admin_report_period" class="form-select form-select-sm" style="width: 170px; border-radius: 8px; font-size: 0.85rem;">
                        <option value="all" <?= ($period === 'all') ? 'selected' : '' ?>>All Time</option>
                        <option value="single_day" <?= ($period === 'single_day') ? 'selected' : '' ?>>Single Day</option>
                        <option value="this_month" <?= ($period === 'this_month') ? 'selected' : '' ?>>This Month</option>
                        <option value="last_month" <?= ($period === 'last_month') ? 'selected' : '' ?>>Last Month</option>
                        <option value="3_months" <?= ($period === '3_months') ? 'selected' : '' ?>>Particular 3 Months</option>
                        <option value="6_months" <?= ($period === '6_months') ? 'selected' : '' ?>>Six Months</option>
                        <option value="custom" <?= ($period === 'custom') ? 'selected' : '' ?>>Custom Date Range</option>
                    </select>
                </div>

                <!-- Single Date Picker -->
                <div id="admin_single_date_box" class="<?= ($period === 'single_day') ? 'd-flex' : 'd-none' ?> align-items-center gap-1">
                    <input type="date" name="single_date" id="admin_single_date" class="form-control form-control-sm" value="<?= htmlspecialchars($singleDate ?: date('Y-m-d')) ?>" style="width: 130px; border-radius: 8px; font-size: 0.82rem;">
                </div>

                <!-- Custom Range Pickers -->
                <div id="admin_custom_date_box" class="<?= ($period === 'custom') ? 'd-flex' : 'd-none' ?> align-items-center gap-1">
                    <input type="date" name="from_date" id="admin_from_date" class="form-control form-control-sm" value="<?= htmlspecialchars($fromDate ?: date('Y-m-01')) ?>" style="width: 125px; border-radius: 8px; font-size: 0.82rem;">
                    <span class="text-muted small">to</span>
                    <input type="date" name="to_date" id="admin_to_date" class="form-control form-control-sm" value="<?= htmlspecialchars($toDate ?: date('Y-m-d')) ?>" style="width: 125px; border-radius: 8px; font-size: 0.82rem;">
                </div>

                <!-- Apply Filter Button -->
                <button type="submit" class="btn btn-outline-primary btn-sm px-3 fw-bold" style="border-radius: 8px; height: 34px;">
                    <i class="bi bi-funnel"></i> Apply Filter
                </button>

                <!-- Export Excel Button -->
                <button type="button" class="btn btn-success btn-sm px-3 fw-bold d-flex align-items-center gap-1 shadow-sm" onclick="triggerAdminExport('excel')" style="border-radius: 8px; height: 34px;">
                    <i class="bi bi-file-earmark-excel"></i> Export Excel
                </button>

                <!-- Export PDF Button -->
                <button type="button" class="btn btn-danger btn-sm px-3 fw-bold d-flex align-items-center gap-1 shadow-sm" onclick="triggerAdminExport('pdf')" style="border-radius: 8px; height: 34px;">
                    <i class="bi bi-file-earmark-pdf"></i> Export PDF
                </button>
            </div>
        </form>
    </div>

    <!-- Summary Metrics Table -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden" style="border: 1px solid #e2e8f0 !important;">
        <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-bar-chart-line me-2 text-primary"></i> Tenant Performance Breakdown</h6>
            <span class="badge bg-light text-dark border"><?= count($concernStats) ?> Active Concerns</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-custom">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0; color: #475569; font-size: 0.74rem; text-transform: uppercase; letter-spacing: 0.05em;">
                        <th>Concern</th>
                        <th>Variants Mastered</th>
                        <th>Stock Units In Hand</th>
                        <th>Live Inventory Valuation</th>
                        <th>Gross Purchases in Period</th>
                        <th>Gross Invoiced Sales in Period</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $grandStockQty = 0;
                    $grandValuation = 0;
                    $grandPurchases = 0;
                    $grandSales = 0;

                    foreach ($concernStats as $cs): 
                        $grandStockQty += (float)$cs['total_stock_qty'];
                        $grandValuation += (float)$cs['stock_valuation'];
                        $grandPurchases += (float)$cs['total_purchases'];
                        $grandSales += (float)$cs['total_sales'];
                    ?>
                        <tr>
                            <td>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($cs['name']) ?></div>
                                <span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($cs['code']) ?></span>
                            </td>
                            <td><?= number_format($cs['total_variants']) ?> items</td>
                            <td>
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1 font-monospace">
                                    <?= number_format((float)$cs['total_stock_qty'], 2) ?>
                                </span>
                            </td>
                            <td class="fw-semibold text-dark font-monospace">₹<?= number_format((float)$cs['stock_valuation'], 2) ?></td>
                            <td class="text-secondary font-monospace">₹<?= number_format((float)$cs['total_purchases'], 2) ?></td>
                            <td class="fw-bold text-success font-monospace">₹<?= number_format((float)$cs['total_sales'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td>TOTAL CONSOLIDATED</td>
                        <td>-</td>
                        <td class="font-monospace"><?= number_format($grandStockQty, 2) ?></td>
                        <td class="text-primary font-monospace">₹<?= number_format($grandValuation, 2) ?></td>
                        <td class="font-monospace">₹<?= number_format($grandPurchases, 2) ?></td>
                        <td class="text-success font-monospace">₹<?= number_format($grandSales, 2) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const periodSelect = document.getElementById('admin_report_period');
    const singleDateBox = document.getElementById('admin_single_date_box');
    const customDateBox = document.getElementById('admin_custom_date_box');

    if (periodSelect) {
        periodSelect.addEventListener('change', function() {
            const p = this.value;
            if (p === 'single_day') {
                if (singleDateBox) { singleDateBox.classList.remove('d-none'); singleDateBox.classList.add('d-flex'); }
                if (customDateBox) { customDateBox.classList.add('d-none'); customDateBox.classList.remove('d-flex'); }
            } else if (p === 'custom') {
                if (customDateBox) { customDateBox.classList.remove('d-none'); customDateBox.classList.add('d-flex'); }
                if (singleDateBox) { singleDateBox.classList.add('d-none'); singleDateBox.classList.remove('d-flex'); }
            } else {
                if (singleDateBox) { singleDateBox.classList.add('d-none'); singleDateBox.classList.remove('d-flex'); }
                if (customDateBox) { customDateBox.classList.add('d-none'); customDateBox.classList.remove('d-flex'); }
            }
        });
    }
});

function triggerAdminExport(format) {
    const period = document.getElementById('admin_report_period').value;
    const singleDate = document.getElementById('admin_single_date') ? document.getElementById('admin_single_date').value : '';
    const fromDate = document.getElementById('admin_from_date') ? document.getElementById('admin_from_date').value : '';
    const toDate = document.getElementById('admin_to_date') ? document.getElementById('admin_to_date').value : '';

    const url = '<?= \App\Core\Controller::url('/admin/reports/export') ?>' +
                '?format=' + encodeURIComponent(format) +
                '&period=' + encodeURIComponent(period) +
                '&single_date=' + encodeURIComponent(singleDate) +
                '&from_date=' + encodeURIComponent(fromDate) +
                '&to_date=' + encodeURIComponent(toDate);

    if (format === 'pdf') {
        window.open(url, '_blank');
    } else {
        window.location.href = url;
    }
}
</script>
