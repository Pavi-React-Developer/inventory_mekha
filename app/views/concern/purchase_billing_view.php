<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-dark font-monospace fs-6 px-3 py-2"><?= htmlspecialchars($bill['bill_no']) ?></span>
                <span class="badge bg-success-subtle text-success border px-2 py-1"><?= ucfirst($bill['payment_status']) ?></span>
                <span class="badge bg-info-subtle text-info border px-2 py-1"><?= ucfirst($bill['inward_status']) ?> Inward</span>
            </div>
            <h4 class="fw-bold mt-2 mb-1 text-dark">Production Purchase Details</h4>
            <p class="text-secondary small mb-0">Recorded on <?= date('d M Y', strtotime($bill['bill_date'])) ?> &bull; Supplier Invoice: <strong><?= htmlspecialchars($bill['supplier_invoice_no'] ?: 'N/A') ?></strong></p>
        </div>
        <div class="d-flex gap-2 d-print-none">
            <button type="button" class="btn btn-outline-primary btn-sm px-3 shadow-sm d-flex align-items-center gap-1" onclick="triggerInvoicePrint()">
                <i class="bi bi-printer me-1"></i> Print Invoice
            </button>
            <a href="<?= \App\Core\Controller::url('/m/purchase_billing/download_items?id=' . $bill['id']) ?>" class="btn btn-success btn-sm px-3 shadow-sm d-flex align-items-center gap-1 text-white" style="background:#10b981; border-color:#10b981;">
                <i class="bi bi-download me-1"></i> Download Purchase Items
            </a>
            <a href="<?= \App\Core\Controller::url('/m/purchase_billing') ?>" class="btn btn-outline-secondary btn-sm px-3 shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> Back to Bills
            </a>
        </div>
    </div>

    <!-- View Mode Switcher -->
    <ul class="nav nav-pills p-1 bg-light rounded-3 gap-1 d-inline-flex mb-4 d-print-none" id="viewModeTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active px-3 py-2 fw-semibold d-flex align-items-center gap-2" id="tabInteractiveBtn" data-bs-toggle="pill" data-bs-target="#paneInteractive" type="button" role="tab">
                <i class="bi bi-layout-text-window-reverse"></i> Interactive Breakdown
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link px-3 py-2 fw-semibold d-flex align-items-center gap-2" id="tabInvoicePrintBtn" data-bs-toggle="pill" data-bs-target="#paneInvoicePrint" type="button" role="tab">
                <i class="bi bi-receipt-cutoff"></i> Tax Invoice Print Format
            </button>
        </li>
    </ul>

    <div class="tab-content" id="viewModeTabContent">
        <!-- PANE 1: INTERACTIVE BREAKDOWN -->
        <div class="tab-pane fade show active" id="paneInteractive" role="tabpanel">

    <!-- Overview Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-3 h-100 p-3">
                <span class="text-muted small text-uppercase fw-semibold mb-1">Supplier Details</span>
                <h6 class="fw-bold text-dark mb-1"><?= htmlspecialchars($bill['supplier_name']) ?></h6>
                <div class="small text-secondary"><?= htmlspecialchars($bill['supplier_company'] ?: '') ?></div>
                <div class="small text-muted mt-2">
                    <i class="bi bi-telephone me-1"></i> <?= htmlspecialchars($bill['supplier_phone'] ?: '-') ?><br>
                    <i class="bi bi-geo-alt me-1"></i> <?= htmlspecialchars($bill['supplier_district'] ?: '-') ?>, <?= htmlspecialchars($bill['supplier_state'] ?: 'Tamil Nadu') ?><br>
                    <span class="font-monospace">GSTIN: <?= htmlspecialchars($bill['supplier_gstin'] ?: 'N/A') ?></span>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-3 h-100 p-3">
                <span class="text-muted small text-uppercase fw-semibold mb-1">Purchase & Payment Info</span>
                <?php if (!empty($bill['fabric_description'])): ?>
                    <div class="fw-semibold text-dark mb-1"><?= htmlspecialchars($bill['fabric_description']) ?></div>
                <?php endif; ?>
                <div class="small text-muted mt-1">
                    <strong>Purchase Date:</strong> <?= date('d-m-Y', strtotime($bill['bill_date'])) ?><br>
                    <strong>Supplier Invoice #:</strong> <?= htmlspecialchars($bill['supplier_invoice_no'] ?: '-') ?><br>
                    <strong>Payment Method:</strong> <span class="badge bg-light text-dark border text-capitalize"><?= htmlspecialchars(str_replace('_', ' ', $bill['payment_method'] ?? 'credit')) ?></span><br>
                    <?php if (!empty($bill['payment_reference'])): ?>
                        <strong>Payment Ref / UTR:</strong> <span class="font-monospace text-dark"><?= htmlspecialchars($bill['payment_reference']) ?></span><br>
                    <?php endif; ?>
                    <strong>Paid Amount:</strong> ₹<?= number_format((float)($bill['paid_amount'] ?? 0), 2) ?><br>
                    <strong>Notes:</strong> <?= htmlspecialchars($bill['notes'] ?: 'None') ?>
                </div>
                <?php if (!empty($bill['attachment_path'])): ?>
                    <div class="mt-3">
                        <a href="<?= htmlspecialchars(\App\Services\CloudinaryService::url($bill['attachment_path'])) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-paperclip me-1"></i> View Attached Invoice
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-3 h-100 p-3 bg-light-subtle">
                <span class="text-muted small text-uppercase fw-semibold mb-1">Financial Summary</span>
                <div class="d-flex justify-content-between small text-secondary mb-1">
                    <span>Subtotal (Base Value):</span>
                    <strong class="text-dark">₹<?= number_format($bill['total_amount'], 2) ?></strong>
                </div>
                <div class="d-flex justify-content-between small text-secondary mb-2">
                    <span>Taxes (GST):</span>
                    <strong class="text-dark">₹<?= number_format($bill['tax_amount'], 2) ?></strong>
                </div>
                <hr class="my-1">
                <div class="d-flex justify-content-between align-items-center mt-2">
                    <span class="fw-bold text-dark fs-6">Total Price:</span>
                    <h4 class="fw-bold mb-0" style="color: #16a34a;">₹<?= number_format($bill['net_amount'], 2) ?></h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Items Tables by Section -->
    <?php
    $rolls = array_filter($items, fn($i) => ($i['section_type'] ?? '') === 'rolls');
    $other = array_filter($items, fn($i) => ($i['section_type'] ?? '') === 'garment_other');
    $finished = array_filter($items, fn($i) => ($i['section_type'] ?? '') === 'finished_goods');
    ?>

    <!-- 1. ROLLS TABLE (EXACT COLUMNS AS IMAGE 1) -->
    <?php if (!empty($rolls)): ?>
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-layers text-primary fs-5"></i>
                    <h6 class="mb-0 fw-bold text-dark">Fabric Rolls Inventory & Details Table (<?= count($rolls) ?> Rolls)</h6>
                </div>
                <a href="<?= \App\Core\Controller::url('/m/purchase_billing/download_items?id=' . $bill['id']) ?>" class="btn btn-outline-success btn-sm py-1 px-3 d-flex align-items-center gap-1 shadow-sm">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i> Download Items (CSV)
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle mb-0 text-center small">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 40px;">#</th>
                            <th style="width: 60px;">PHOTO</th>
                            <th>ROLL CODE</th>
                            <th>HSN</th>
                            <th>COLOR / SHADE</th>
                            <th>DESIGN / PATTERN</th>
                            <th>WEIGHT (KG)</th>
                            <th>UNIT PRICE</th>
                            <th>QTY</th>
                            <th>TOTAL</th>
                            <th>GST TYPE</th>
                            <th>TAX</th>
                            <th class="table-success text-success fw-bold">TOTAL VALUE (TAX+TOTAL)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $idx = 1; foreach ($rolls as $r): ?>
                            <?php
                            $gstRate = (float)($r['gst_rate'] ?? 0);
                            $taxVal = (float)($r['tax_amount'] ?? 0);
                            $baseVal = (float)($r['total_price'] ?? 0);
                            $totalVal = $baseVal + $taxVal;
                            $weightKg = (float)($r['net_weight'] ?? 0);
                            $qtyRq = (int)($r['rq_qty'] ?: ($r['qty'] ?: 1));
                            $unitPrice = (float)($r['unit_price'] ?? 0);
                            $gstType = $r['gst_type'] ?? ($gstRate == 0 ? 'Exempted' : 'GST');
                            ?>
                            <tr>
                                <td><?= $idx++ ?></td>
                                <td>
                                    <?php if (!empty($r['photo_path'])): ?>
                                        <a href="<?= htmlspecialchars(\App\Services\CloudinaryService::url($r['photo_path'])) ?>" target="_blank" title="View Full Image">
                                            <img src="<?= htmlspecialchars(\App\Services\CloudinaryService::url($r['photo_path'])) ?>" class="rounded border shadow-sm" style="width: 38px; height: 38px; object-fit: cover;" alt="Roll Image">
                                        </a>
                                    <?php else: ?>
                                        <i class="bi bi-camera text-muted fs-6" title="No photo"></i>
                                    <?php endif; ?>
                                </td>
                                <td class="fw-bold font-monospace text-dark"><?= htmlspecialchars($r['item_code']) ?></td>
                                <td class="font-monospace"><?= htmlspecialchars($r['hsn_code'] ?: '-') ?></td>
                                <td><?= htmlspecialchars($r['color'] ?: ($r['specification'] ?: '-')) ?></td>
                                <td><?= htmlspecialchars($r['pattern'] ?: '-') ?></td>
                                <td class="fw-semibold text-primary"><?= number_format($weightKg, 2) ?> kg</td>
                                <td>₹<?= number_format($unitPrice, 2) ?></td>
                                <td class="fw-semibold"><?= $qtyRq ?></td>
                                <td class="fw-semibold">₹<?= number_format($baseVal, 2) ?></td>
                                <td>
                                    <?php if ($gstRate == 0 || $gstType === 'Exempted'): ?>
                                        <span class="badge bg-light text-dark border">EXEMPTED</span>
                                    <?php else: ?>
                                        <span class="badge bg-primary-subtle text-primary border"><?= htmlspecialchars($gstType) ?> (<?= $gstRate ?>%)</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($gstType === 'Both (CGST and SGST)' && $taxVal > 0): ?>
                                        <div class="fw-bold">₹<?= number_format($taxVal, 2) ?></div>
                                        <small class="text-muted d-block" style="font-size: 0.72rem; white-space: nowrap;">
                                            CGST: ₹<?= number_format($taxVal / 2, 2) ?> + SGST: ₹<?= number_format($taxVal / 2, 2) ?>
                                        </small>
                                    <?php else: ?>
                                        <div class="fw-bold">₹<?= number_format($taxVal, 2) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="table-success fw-bold text-success">₹<?= number_format($totalVal, 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- 2. OTHER GARMENT SUPPLIES (EXACT COLUMNS AS IMAGE 2) -->
    <?php if (!empty($other)): ?>
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-box-seam text-success fs-5"></i>
                    <h6 class="mb-0 fw-bold text-dark">Other Garment Supplies Details Table (<?= count($other) ?> Items)</h6>
                </div>
                <a href="<?= \App\Core\Controller::url('/m/purchase_billing/download_items?id=' . $bill['id']) ?>" class="btn btn-outline-success btn-sm py-1 px-3 d-flex align-items-center gap-1 shadow-sm">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i> Download Items (CSV)
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle mb-0 text-center small">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 40px;">#</th>
                            <th>OTHER PRODUCT NO/CODE</th>
                            <th>ITEM NAME / DESCRIPTION</th>
                            <th>HSN</th>
                            <th>QUANTITY</th>
                            <th>UNIT</th>
                            <th>UNIT PRICE</th>
                            <th>BASE VALUE</th>
                            <th>GST TYPE</th>
                            <th>TAX</th>
                            <th class="table-success text-success fw-bold">TOTAL VALUE</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $idx = 1; foreach ($other as $o): ?>
                            <?php
                            $gstRate = (float)($o['gst_rate'] ?? 0);
                            $taxVal = (float)($o['tax_amount'] ?? 0);
                            $totalVal = (float)($o['total_price'] ?? 0) + $taxVal;
                            ?>
                            <tr>
                                <td><?= $idx++ ?></td>
                                <td class="font-monospace fw-semibold text-dark"><?= htmlspecialchars($o['item_code'] ?: '-') ?></td>
                                <td class="fw-semibold text-dark text-start"><?= htmlspecialchars($o['item_name']) ?></td>
                                <td class="font-monospace"><?= htmlspecialchars($o['hsn_code'] ?: '-') ?></td>
                                <td class="fw-semibold"><?= number_format($o['qty'], 2) ?></td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($o['unit']) ?></span></td>
                                <td>₹<?= number_format($o['unit_price'], 2) ?></td>
                                <td>₹<?= number_format($o['total_price'], 2) ?></td>
                                <td>
                                    <?php if ($gstRate == 0): ?>
                                        <span class="badge bg-light text-dark border">EXEMPTED</span>
                                    <?php else: ?>
                                        <span class="badge bg-success-subtle text-success border"><?= $gstRate ?>%</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($gstType === 'Both (CGST and SGST)' && $taxVal > 0): ?>
                                        <div class="fw-bold">₹<?= number_format($taxVal, 2) ?></div>
                                        <small class="text-muted d-block" style="font-size: 0.72rem; white-space: nowrap;">
                                            CGST: ₹<?= number_format($taxVal / 2, 2) ?> + SGST: ₹<?= number_format($taxVal / 2, 2) ?>
                                        </small>
                                    <?php else: ?>
                                        <div class="fw-bold">₹<?= number_format($taxVal, 2) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="table-success fw-bold text-success">₹<?= number_format($totalVal, 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- 3. FINISHED GOODS (EXACT COLUMNS AS IMAGE 3) -->
    <?php if (!empty($finished)): ?>
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-tag text-purple fs-5" style="color:#8b5cf6;"></i>
                    <h6 class="mb-0 fw-bold text-dark">Finished Goods Inventory & Details Table (<?= count($finished) ?> Styles)</h6>
                </div>
                <a href="<?= \App\Core\Controller::url('/m/purchase_billing/download_items?id=' . $bill['id']) ?>" class="btn btn-outline-success btn-sm py-1 px-3 d-flex align-items-center gap-1 shadow-sm">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i> Download Items (CSV)
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle mb-0 text-center small">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 40px;">#</th>
                            <th>PRODUCT</th>
                            <th>HSN</th>
                            <th>WEIGHT (KG)</th>
                            <th>QTY</th>
                            <th>SUPPLIER COST (₹)</th>
                            <th>GST TYPE</th>
                            <th>TAX</th>
                            <th class="table-success text-success fw-bold">TOTAL VALUE</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $idx = 1; foreach ($finished as $f): ?>
                            <?php
                            $gstRate = (float)($f['gst_rate'] ?? 0);
                            $taxVal = (float)($f['tax_amount'] ?? 0);
                            $totalVal = (float)($f['total_price'] ?? 0) + $taxVal;
                            ?>
                            <tr>
                                <td><?= $idx++ ?></td>
                                <td class="fw-bold text-dark text-start"><?= htmlspecialchars($f['item_name']) ?></td>
                                <td class="font-monospace"><?= htmlspecialchars($f['hsn_code'] ?: '-') ?></td>
                                <td><?= number_format($f['net_weight'] ?? 0, 3) ?></td>
                                <td class="fw-bold"><?= (int)$f['qty'] ?></td>
                                <td>₹<?= number_format($f['unit_price'], 2) ?></td>
                                <td>
                                    <?php if ($gstRate == 0): ?>
                                        <span class="badge bg-light text-dark border">EXEMPTED</span>
                                    <?php else: ?>
                                        <span class="badge bg-purple-subtle text-purple border" style="background:#ede9fe; color:#6d28d9;"><?= $gstRate ?>%</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($gstType === 'Both (CGST and SGST)' && $taxVal > 0): ?>
                                        <div class="fw-bold">₹<?= number_format($taxVal, 2) ?></div>
                                        <small class="text-muted d-block" style="font-size: 0.72rem; white-space: nowrap;">
                                            CGST: ₹<?= number_format($taxVal / 2, 2) ?> + SGST: ₹<?= number_format($taxVal / 2, 2) ?>
                                        </small>
                                    <?php else: ?>
                                        <div class="fw-bold">₹<?= number_format($taxVal, 2) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="table-success fw-bold text-success">₹<?= number_format($totalVal, 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
    </div><!-- End #paneInteractive -->

    <!-- ============================================== -->
    <!-- PANE 2: OFFICIAL TAX INVOICE PRINT FORMAT      -->
    <!-- ============================================== -->
    <div class="tab-pane fade" id="paneInvoicePrint" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-3 p-4 bg-white invoice-print-container" id="printableInvoiceCard">
            <!-- Screen Toolbar: Print & Download Actions -->
            <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom d-print-none">
                <div>
                    <span class="badge bg-primary-subtle text-primary border px-2 py-1"><i class="bi bi-printer me-1"></i> Standard Purchase Tax Invoice</span>
                    <span class="text-muted small ms-2">Official printable format with GST audit ledger and sign-off columns.</span>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-primary btn-sm px-3 shadow-sm d-flex align-items-center gap-1" onclick="window.print()">
                        <i class="bi bi-printer-fill me-1"></i> Print / Save as PDF
                    </button>
                    <a href="<?= \App\Core\Controller::url('/m/purchase_billing/download_items?id=' . $bill['id']) ?>" class="btn btn-outline-success btn-sm px-3 shadow-sm d-flex align-items-center gap-1">
                        <i class="bi bi-download me-1"></i> Download CSV
                    </a>
                </div>
            </div>

            <!-- Printable Document Body -->
            <div class="invoice-box border p-4 rounded-2 bg-white text-dark">
                <!-- Header: Concern Info & Invoice Identity -->
                <div class="row pb-3 mb-3 border-bottom align-items-center">
                    <div class="col-8">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <i class="bi bi-building fs-3 text-primary"></i>
                            <h3 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($bill['concern_name'] ?: 'INVENTO MULTI-CONCERN ERP') ?></h3>
                        </div>
                        <div class="text-muted small">
                            <strong>Concern Unit:</strong> <?= htmlspecialchars($bill['concern_code'] ?: 'INVENTO') ?> &bull; 
                            <strong>Receiving Facility:</strong> Central Production Unit & Warehousing &bull; Tamil Nadu
                        </div>
                        <div class="mt-2">
                            <span class="badge bg-dark font-monospace text-uppercase px-2 py-1" style="font-size: 0.75rem;">PURCHASE TAX INVOICE & GOODS INWARD VOUCHER</span>
                        </div>
                    </div>
                    <div class="col-4 text-end">
                        <div class="text-muted small text-uppercase fw-bold">Purchase Bill No.</div>
                        <h4 class="fw-bold font-monospace text-primary mb-1"><?= htmlspecialchars($bill['bill_no']) ?></h4>
                        <div class="small text-muted"><strong>Bill Date:</strong> <?= date('d M Y', strtotime($bill['bill_date'])) ?></div>
                        <div class="small text-muted"><strong>Supplier Inv #:</strong> <?= htmlspecialchars($bill['supplier_invoice_no'] ?: 'N/A') ?></div>
                    </div>
                </div>

                <!-- Parties: Supplier vs Consignee -->
                <div class="row g-3 pb-3 mb-3 border-bottom small">
                    <div class="col-6 border-end">
                        <div class="fw-bold text-uppercase text-muted mb-1" style="font-size: 0.72rem; letter-spacing: 0.05em;">Supplier / Vendor (Billed By)</div>
                        <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($bill['supplier_name']) ?></div>
                        <?php if (!empty($bill['supplier_company'])): ?>
                            <div class="text-secondary fw-semibold"><?= htmlspecialchars($bill['supplier_company']) ?></div>
                        <?php endif; ?>
                        <div class="text-muted mt-1">
                            Phone: <?= htmlspecialchars($bill['supplier_phone'] ?: '-') ?><br>
                            Location: <?= htmlspecialchars($bill['supplier_district'] ?: '-') ?>, <?= htmlspecialchars($bill['supplier_state'] ?: 'Tamil Nadu') ?><br>
                            <span class="font-monospace fw-semibold text-dark">GSTIN: <?= htmlspecialchars($bill['supplier_gstin'] ?: 'N/A') ?></span>
                        </div>
                    </div>
                    <div class="col-6 ps-3">
                        <div class="fw-bold text-uppercase text-muted mb-1" style="font-size: 0.72rem; letter-spacing: 0.05em;">Billed & Delivered To (Consignee)</div>
                        <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($bill['concern_name'] ?: 'INVENTO Enterprise') ?></div>
                        <div class="text-muted mt-1">
                            Destination: Central Fabric & Trim Store, Inward Bay<br>
                            <strong>Payment Status:</strong> <span class="badge bg-light text-dark border text-uppercase"><?= htmlspecialchars($bill['payment_status']) ?></span> &bull; 
                            <strong>Method:</strong> <span class="text-capitalize fw-semibold text-dark"><?= htmlspecialchars(str_replace('_', ' ', $bill['payment_method'] ?? 'credit')) ?></span><br>
                            <?php if (!empty($bill['payment_reference'])): ?>
                                <strong>Payment Ref:</strong> <span class="font-monospace text-dark"><?= htmlspecialchars($bill['payment_reference']) ?></span> &bull; 
                            <?php endif; ?>
                            <strong>Paid Amount:</strong> ₹<?= number_format((float)($bill['paid_amount'] ?? 0), 2) ?><br>
                            <strong>Inward Status:</strong> <span class="badge bg-light text-dark border text-uppercase"><?= htmlspecialchars($bill['inward_status']) ?></span>
                        </div>
                    </div>
                </div>

                <!-- Consolidated Items Table -->
                <div class="table-responsive mb-3">
                    <table class="table table-bordered table-sm align-middle text-center small mb-0 print-items-table">
                        <thead class="table-light">
                            <tr class="fw-bold text-uppercase" style="font-size: 0.72rem;">
                                <th style="width: 35px;">#</th>
                                <th>Section</th>
                                <th>Item Code / Roll #</th>
                                <th class="text-start">Description / Specification</th>
                                <th>HSN</th>
                                <th>Qty</th>
                                <th>Unit</th>
                                <th>Weight</th>
                                <th>Rate (₹)</th>
                                <th>Taxable Base (₹)</th>
                                <th>GST</th>
                                <th>Tax (₹)</th>
                                <th class="text-end">Total (₹)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $printIdx = 1;
                            $printBaseTotal = 0.0;
                            $printTaxTotal = 0.0;
                            $printNetTotal = 0.0;
                            $printQtyTotal = 0.0;
                            $printWtTotal = 0.0;

                            foreach ($items as $it):
                                $secName = match ($it['section_type'] ?? '') {
                                    'rolls' => 'Fabric Rolls',
                                    'garment_other' => 'Other Supplies',
                                    'finished_goods' => 'Finished Goods',
                                    default => 'General'
                                };
                                $qty = (float)($it['qty'] ?? 1);
                                $wt = (float)($it['net_weight'] ?? 0);
                                $price = (float)($it['unit_price'] ?? 0);
                                $base = (float)($it['total_price'] ?? 0);
                                $tax = (float)($it['tax_amount'] ?? 0);
                                $lineTotal = $base + $tax;
                                $gstRate = (float)($it['gst_rate'] ?? 0);

                                $printBaseTotal += $base;
                                $printTaxTotal += $tax;
                                $printNetTotal += $lineTotal;
                                $printQtyTotal += $qty;
                                $printWtTotal += $wt;

                                $desc = htmlspecialchars($it['item_name']);
                                if (!empty($it['pattern'])) $desc .= ' &bull; ' . htmlspecialchars($it['pattern']);
                                if (!empty($it['color'])) $desc .= ' (' . htmlspecialchars($it['color']) . ')';
                                elseif (!empty($it['specification'])) $desc .= ' (' . htmlspecialchars($it['specification']) . ')';
                            ?>
                                <tr>
                                    <td><?= $printIdx++ ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= $secName ?></span></td>
                                    <td class="font-monospace fw-semibold"><?= htmlspecialchars($it['item_code'] ?: '-') ?></td>
                                    <td class="text-start"><?= $desc ?></td>
                                    <td class="font-monospace"><?= htmlspecialchars($it['hsn_code'] ?: '-') ?></td>
                                    <td class="fw-bold"><?= $qty ?></td>
                                    <td><?= htmlspecialchars($it['unit'] ?: 'Pcs') ?></td>
                                    <td><?= $wt > 0 ? number_format($wt, 2) . ' kg' : '-' ?></td>
                                    <td>₹<?= number_format($price, 2) ?></td>
                                    <td>₹<?= number_format($base, 2) ?></td>
                                    <td>
                                        <?php if ($gstRate == 0 || ($it['gst_type'] ?? '') === 'Exempted'): ?>
                                            <span class="badge bg-light text-dark border">EXEMPT</span>
                                        <?php else: ?>
                                            <?= htmlspecialchars($it['gst_type'] ?: 'GST') ?> (<?= $gstRate ?>%)
                                        <?php endif; ?>
                                    </td>
                                    <td>₹<?= number_format($tax, 2) ?></td>
                                    <td class="text-end fw-bold">₹<?= number_format($lineTotal, 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td colspan="5" class="text-end text-uppercase">Totals:</td>
                                <td><?= number_format($printQtyTotal, 2) ?></td>
                                <td></td>
                                <td><?= $printWtTotal > 0 ? number_format($printWtTotal, 2) . ' kg' : '-' ?></td>
                                <td></td>
                                <td>₹<?= number_format($printBaseTotal, 2) ?></td>
                                <td></td>
                                <td>₹<?= number_format($printTaxTotal, 2) ?></td>
                                <td class="text-end text-success fs-6">₹<?= number_format($printNetTotal, 2) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Financial Calculation & Declarations -->
                <div class="row pt-2 align-items-start">
                    <div class="col-7">
                        <div class="border p-2 rounded bg-light small mb-2">
                            <div class="fw-bold mb-1">Declaration & Terms:</div>
                            <p class="mb-0 text-muted" style="font-size: 0.72rem; line-height: 1.4;">
                                1. Goods received in sound condition and verified against supplier delivery memo.<br>
                                2. Production raw materials subject to internal QA/QC testing prior to fabric batch release.<br>
                                3. Certified that all taxable inputs and statutory GST amounts correspond to official invoicing.
                            </p>
                        </div>

                        <?php if (!empty($bill['notes'])): ?>
                            <div class="small text-muted mb-2">
                                <strong>Audit / Consignment Notes:</strong> <?= htmlspecialchars($bill['notes']) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="col-5">
                        <div class="border rounded p-2 bg-light small">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Taxable Subtotal (Base):</span>
                                <span class="fw-semibold">₹<?= number_format($bill['total_amount'], 2) ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Total Tax Amount (GST):</span>
                                <span class="fw-semibold">₹<?= number_format($bill['tax_amount'], 2) ?></span>
                            </div>
                            <hr class="my-1">
                            <div class="d-flex justify-content-between align-items-center py-1">
                                <span class="fw-bold fs-6 text-dark">Invoice Grand Total:</span>
                                <span class="fw-bold fs-5 text-success">₹<?= number_format($bill['net_amount'], 2) ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Signature Block -->
                <div class="row text-center pt-4 mt-3 border-top small text-muted">
                    <div class="col-4">
                        <div class="pt-4 border-top border-dark mx-3 fw-semibold text-dark">Store In-Charge / Receiver</div>
                    </div>
                    <div class="col-4">
                        <div class="pt-4 border-top border-dark mx-3 fw-semibold text-dark">Quality / QC Inspector</div>
                    </div>
                    <div class="col-4">
                        <div class="pt-4 border-top border-dark mx-3 fw-semibold text-dark">Authorized Signatory</div>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- End #paneInvoicePrint -->
    </div><!-- End #viewModeTabContent -->
</div>

<!-- Print Styles & Scripts -->
<style>
@media print {
    /* Hide layout navigation, sidebars and headers */
    #sidebar,
    .top-navbar,
    .d-print-none,
    nav,
    header,
    .btn,
    .nav-pills,
    .badge.bg-dark,
    .alert {
        display: none !important;
    }

    #main-content {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
    }

    body {
        background: #ffffff !important;
        color: #000000 !important;
        font-size: 10pt !important;
    }

    .container-fluid {
        padding: 0 !important;
    }

    /* Force invoice print card to be visible even if other tab was active */
    #paneInteractive {
        display: none !important;
    }

    #paneInvoicePrint {
        display: block !important;
        opacity: 1 !important;
    }

    .invoice-print-container {
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
    }

    .invoice-box {
        border: 1px solid #000000 !important;
        padding: 15px !important;
    }

    .print-items-table th,
    .print-items-table td {
        border: 1px solid #333333 !important;
        color: #000000 !important;
    }

    @page {
        size: A4 portrait;
        margin: 10mm;
    }
}
</style>

<script>
function triggerInvoicePrint() {
    const printTab = document.getElementById('tabInvoicePrintBtn');
    if (printTab) {
        printTab.click();
    }
    setTimeout(() => {
        window.print();
    }, 200);
}
</script>
