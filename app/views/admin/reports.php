<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1 text-dark">Consolidated Multi-Concern Performance Report</h4>
            <p class="text-secondary small mb-0">Cross-tenant inventory valuation, revenue volume, and procurement metrics aggregated across all concerns.</p>
        </div>
        <button class="btn btn-outline-secondary" onclick="window.print()">
            <i class="bi bi-printer me-1"></i> Print Report
        </button>
    </div>

    <!-- Summary Metrics -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-white py-3 border-bottom">
            <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-bar-chart-line me-2 text-primary"></i> Tenant Performance Breakdown</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-custom">
                <thead>
                    <tr>
                        <th>Concern</th>
                        <th>Variants Mastered</th>
                        <th>Stock Units In Hand</th>
                        <th>Live Inventory Valuation</th>
                        <th>Gross Purchases</th>
                        <th>Gross Invoiced Sales</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $grandStockQty = 0;
                    $grandValuation = 0;
                    $grandPurchases = 0;
                    $grandSales = 0;

                    foreach ($concernStats as $cs): 
                        $grandStockQty += $cs['total_stock_qty'];
                        $grandValuation += $cs['stock_valuation'];
                        $grandPurchases += $cs['total_purchases'];
                        $grandSales += $cs['total_sales'];
                    ?>
                        <tr>
                            <td>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($cs['name']) ?></div>
                                <span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($cs['code']) ?></span>
                            </td>
                            <td><?= number_format($cs['total_variants']) ?> items</td>
                            <td>
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1">
                                    <?= number_format($cs['total_stock_qty'], 2) ?>
                                </span>
                            </td>
                            <td class="fw-semibold text-dark">₹<?= number_format($cs['stock_valuation'], 2) ?></td>
                            <td class="text-secondary">₹<?= number_format($cs['total_purchases'], 2) ?></td>
                            <td class="fw-bold text-success">₹<?= number_format($cs['total_sales'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td>TOTAL CONSOLIDATED</td>
                        <td>-</td>
                        <td><?= number_format($grandStockQty, 2) ?></td>
                        <td class="text-primary">₹<?= number_format($grandValuation, 2) ?></td>
                        <td>₹<?= number_format($grandPurchases, 2) ?></td>
                        <td class="text-success">₹<?= number_format($grandSales, 2) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
