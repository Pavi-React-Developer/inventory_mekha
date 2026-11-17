<div class="container-fluid px-0">
    <!-- Top Breadcrumb & Page Header (Exact Replica of Image 1) -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <div class="text-secondary small mb-1">
                <a href="<?= \App\Core\Controller::url('/') ?>" class="text-decoration-none text-muted">Home</a>
                <span class="mx-1">/</span>
                <span class="text-muted">Shop</span>
                <span class="mx-1">/</span>
                <span class="text-dark fw-semibold">POS Terminal</span>
            </div>
            <div class="d-flex align-items-center gap-3 mt-1">
                <a href="<?= \App\Core\Controller::url('/dashboard') ?>" class="btn btn-light rounded-circle shadow-sm border p-0 d-inline-flex align-items-center justify-content-center" style="width: 38px; height: 38px;" title="Back to Dashboard">
                    <i class="bi bi-arrow-left fs-6 text-dark"></i>
                </a>
                <div style="width: 44px; height: 44px; background: #e0f2fe; color: #0284c7; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.45rem; flex-shrink: 0;">
                    <i class="bi bi-qr-code"></i>
                </div>
                <div>
                    <h4 class="fw-bold mb-0 text-dark" style="letter-spacing: -0.01em;">POS Billing Terminal</h4>
                    <small class="text-muted">
                        <i class="bi bi-shop me-1 text-primary"></i> <?= htmlspecialchars($concernName) ?> &bull; 
                        <span class="text-dark">Staff:</span> <?= htmlspecialchars($staffName) ?>
                    </small>
                </div>
            </div>
        </div>

        <div class="d-flex align-items-center gap-2">
            <span class="badge rounded-pill px-3 py-2 fw-semibold d-inline-flex align-items-center gap-2" style="background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; font-size: 0.82rem;">
                <span style="color: #10b981; font-size: 0.75rem;">●</span> QR Scanner Active
            </span>
            <button class="btn btn-outline-secondary btn-sm shadow-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#modalInvoiceHistory">
                <i class="bi bi-receipt"></i> Invoices History (<?= count($invoices) ?>)
            </button>
        </div>
    </div>

    <!-- Main Terminal Grid (Left: Scanner / Shortcuts, Right: Customer & Cart) -->
    <form id="pos_billing_form" action="<?= \App\Core\Controller::url('/m/billing/complete') ?>" method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
        <input type="hidden" name="concern_id" value="<?= (int)($selectedCid ?: ($currentConcern['id'] ?? 1)) ?>">
        <input type="hidden" name="cart_items_json" id="cart_items_json" value="[]">
        <input type="hidden" name="payment_method" id="input_payment_method" value="cash">

        <div class="row g-3">
            <!-- ============================================== -->
            <!-- LEFT COLUMN: SCANNER & FAST SHORTCUTS (IMAGE 1)-->
            <!-- ============================================== -->
            <div class="col-lg-4 col-md-5">
                <!-- Scan Product QR Code Card -->
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-3" style="border: 1px solid #e2e8f0 !important;">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <i class="bi bi-upc-scan text-primary fs-5"></i>
                        <h6 class="fw-bold text-dark mb-0">Scan Product QR Code</h6>
                    </div>

                    <!-- Large Input Box with Blue Add Button -->
                    <div class="d-flex align-items-stretch mb-2" style="border: 1.5px solid #3b82f6; border-radius: 12px; overflow: hidden; background: #ffffff;">
                        <div style="background: #2563eb; color: #ffffff; width: 48px; display: flex; align-items: center; justify-content: center; font-size: 1.35rem; flex-shrink: 0;">
                            <i class="bi bi-qr-code"></i>
                        </div>
                        <input type="text" id="pos_qr_input" list="pos_catalog_list" class="form-control border-0 shadow-none px-3 text-dark" placeholder="Point QR scanner or type SKU / name" style="font-size: 0.95rem; border-radius: 0;" autocomplete="off" autofocus>
                        <datalist id="pos_catalog_list">
                            <?php foreach ($variants as $v): ?>
                                <option value="<?= htmlspecialchars($v['sku_code']) ?>"><?= htmlspecialchars($v['name']) ?> (₹<?= number_format($v['price'], 0) ?>)</option>
                            <?php endforeach; ?>
                        </datalist>
                        <button type="button" id="btn_add_qr" class="btn text-white px-3 fw-bold d-flex align-items-center gap-1" style="background: #4f46e5; border-radius: 0; border: none; font-size: 0.92rem; white-space: nowrap;">
                            <i class="bi bi-arrow-right-circle"></i> Add
                        </button>
                    </div>

                    <!-- Subtext below scanner input -->
                    <div class="d-flex justify-content-between align-items-center small px-1 mb-2">
                        <span class="text-muted" style="font-size: 0.76rem;">
                            <i class="bi bi-info-circle me-1"></i> Each scanned piece is added one by one.
                        </span>
                        <span class="text-primary fw-semibold" id="scanner_status_text" style="font-size: 0.76rem;">
                            Ready for next scan...
                        </span>
                    </div>

                    <!-- Fast POS Shortcuts Box (Inside Card, Exactly matching Image 1) -->
                    <div class="p-2 px-3 mt-3 rounded-3 d-flex align-items-center gap-2" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                        <i class="bi bi-keyboard text-secondary fs-5 flex-shrink-0"></i>
                        <div style="font-size: 0.77rem; color: #475569; line-height: 1.35;">
                            <span class="fw-bold text-dark">Fast POS Shortcuts:</span> Press <kbd class="bg-primary text-white px-1 rounded" style="font-size: 0.72rem;">F2</kbd> to Complete Sale &bull; Piece serial QR barcodes are automatically verified one by one.
                        </div>
                    </div>

                    <!-- Subtle Collapsible Catalog Quick-Pick & Camera for testing/non-scanner environments -->
                    <div class="pt-2 mt-2 border-top">
                        <div class="d-flex justify-content-between align-items-center">
                            <a class="text-decoration-none text-muted small fw-semibold d-flex align-items-center gap-1" data-bs-toggle="collapse" href="#collapseQuickPick" role="button" aria-expanded="false" style="font-size: 0.74rem;">
                                <i class="bi bi-grid-3x3-gap"></i> Quick Catalog Pick <i class="bi bi-chevron-down" style="font-size: 0.65rem;"></i>
                            </a>
                            <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none text-muted" id="btnToggleCam" style="font-size: 0.74rem;">
                                <i class="bi bi-camera me-1"></i> Live Camera
                            </button>
                        </div>
                        
                        <div id="cam_scanner_box" class="d-none mt-2 border rounded p-2 bg-light text-center">
                            <div id="qr_reader" style="width: 100%; max-height: 200px;"></div>
                            <small class="text-muted d-block mt-1">Point live camera at printed garment QR code.</small>
                        </div>

                        <div class="collapse show mt-2" id="collapseQuickPick">
                            <div class="d-flex flex-wrap gap-1" style="max-height: 140px; overflow-y: auto;">
                                <?php if (empty($variants)): ?>
                                    <span class="text-muted small">No product variants in catalog.</span>
                                <?php else: ?>
                                    <?php foreach (array_slice($variants, 0, 10) as $var): ?>
                                        <button type="button" class="btn btn-sm btn-light border text-start d-flex justify-content-between align-items-center p-1 px-2 btn-quick-var" 
                                                data-sku="<?= htmlspecialchars($var['sku_code']) ?>"
                                                style="font-size: 0.78rem; border-radius: 8px; flex: 1 1 calc(50% - 4px);">
                                            <span class="text-truncate fw-semibold text-dark me-1"><?= htmlspecialchars($var['sku_code']) ?></span>
                                            <span class="text-primary fw-bold font-monospace">₹<?= number_format($var['price'], 0) ?></span>
                                        </button>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============================================== -->
            <!-- RIGHT COLUMN: CUSTOMER, CART & PAYMENT (IMAGE 1 & 2) -->
            <!-- ============================================== -->
            <div class="col-lg-8 col-md-7">
                <!-- Customer & Billed By Card (Image 1) -->
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-3" style="border: 1px solid #e2e8f0 !important;">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-person-vcard text-primary fs-5"></i>
                            <div>
                                <h6 class="fw-bold text-dark mb-0">Customer & Billed By</h6>
                                <span class="text-muted" style="font-size: 0.76rem;">Mandatory fields marked with *</span>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge rounded-pill px-2 py-1 fw-bold" style="background: #fee2e2; color: #ef4444; font-size: 0.74rem;">
                                * Name, Phone, Address
                            </span>
                            <button type="button" class="btn btn-sm px-2 py-1 fw-bold rounded-pill" id="btn_walkin" style="background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; font-size: 0.74rem;">
                                <i class="bi bi-person"></i> Walk-in
                            </button>
                        </div>
                    </div>

                    <!-- 2x2 Form Inputs Grid + GST Type -->
                    <div class="row g-2 mb-2">
                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-bold mb-1" style="font-size: 0.78rem;">
                                <i class="bi bi-phone text-muted me-1"></i> MOBILE NUMBER *
                            </label>
                            <input type="tel" name="customer_mobile" id="cust_mobile" class="form-control" placeholder="10-digit mobile number *" maxlength="10" required style="border-radius: 8px; font-size: 0.88rem;">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-bold mb-1" style="font-size: 0.78rem;">
                                <i class="bi bi-person text-muted me-1"></i> CUSTOMER NAME *
                            </label>
                            <input type="text" name="customer_name" id="cust_name" class="form-control" placeholder="Customer full name *" required style="border-radius: 8px; font-size: 0.88rem;">
                        </div>
                    </div>

                    <div class="row g-2 mb-2">
                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-bold mb-1" style="font-size: 0.78rem;">
                                <i class="bi bi-geo-alt text-muted me-1"></i> CUSTOMER ADDRESS *
                            </label>
                            <input type="text" name="customer_address" id="cust_address" class="form-control" placeholder="Billing address / city *" required style="border-radius: 8px; font-size: 0.88rem;">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-bold mb-1" style="font-size: 0.78rem;">
                                <i class="bi bi-person-badge text-muted me-1"></i> BILLED BY STAFF
                            </label>
                            <input type="text" class="form-control bg-light text-muted" value="<?= htmlspecialchars($staffName) ?>" readonly style="border-radius: 8px; font-size: 0.88rem;">
                        </div>
                    </div>

                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-bold mb-1" style="font-size: 0.78rem;">
                                <i class="bi bi-receipt text-muted me-1"></i> GST TYPE
                            </label>
                            <select name="gst_type" id="gst_type" class="form-select" style="border-radius: 8px; font-size: 0.88rem;">
                                <option value="both" selected>Both CGST & SGST</option>
                                <option value="igst">IGST (Inter-state)</option>
                                <option value="exempt">None / Exempt (0%)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Billing Cart Table (Image 1 & 2) -->
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-3" style="border: 1px solid #e2e8f0 !important;">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="cart_table" style="font-size: 0.84rem;">
                            <thead>
                                <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0; color: #475569; font-size: 0.75rem; text-transform: uppercase;">
                                    <th style="width: 4%;">#</th>
                                    <th style="width: 32%;">ITEM DETAILS</th>
                                    <th style="width: 14%;">HSN CODE</th>
                                    <th style="width: 13%;">BASE PRICE</th>
                                    <th style="width: 11%;">GST %</th>
                                    <th style="width: 11%;">DISCOUNT</th>
                                    <th style="width: 12%;" class="text-end">BILLED PRICE</th>
                                    <th style="width: 3%;" class="text-center"></th>
                                </tr>
                            </thead>
                            <tbody id="cart_table_body">
                                <!-- Populated dynamically or Empty State -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Cart Empty State (Exact Image 1 Replica) -->
                    <div id="cart_empty_state" class="py-5 text-center">
                        <div style="width: 64px; height: 64px; background: #eff6ff; color: #4f46e5; border-radius: 18px; display: inline-flex; align-items: center; justify-content: center; font-size: 1.8rem; margin-bottom: 0.75rem;">
                            <i class="bi bi-bag-plus"></i>
                        </div>
                        <h5 class="fw-bold text-dark mb-1">Billing Cart is Empty</h5>
                        <p class="text-muted small mb-0">Point your scanner at garment QR codes or type SKU on the left to start billing.</p>
                    </div>

                    <!-- Cart Footer Summary Row (Image 2 Top) -->
                    <div class="d-flex justify-content-between align-items-center pt-3 border-top mt-2">
                        <span class="badge rounded-pill px-3 py-2 fw-semibold" id="badge_items_count" style="background: #e0f2fe; color: #0284c7; font-size: 0.8rem;">
                            <i class="bi bi-boxes me-1"></i> <span id="display_items_count">0</span> Items Scanned
                        </span>
                        <div class="text-end">
                            <span class="text-secondary small fw-bold me-2" style="font-size: 0.78rem;">TAXABLE BASE SUBTOTAL</span>
                            <span class="fw-bold text-dark font-monospace fs-6" id="display_taxable_subtotal">₹0.00</span>
                        </div>
                    </div>
                </div>

                <!-- Two Summary Cards (Image 2) -->
                <div class="row g-3 mb-3">
                    <!-- GST Inclusive Card -->
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100" style="border: 1px solid #e2e8f0 !important;">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-bold text-dark small"><i class="bi bi-receipt text-primary me-1"></i> GST (Inclusive)</span>
                                <span class="badge rounded-pill px-2 py-1 fw-bold" id="badge_gst_incl" style="background: #dcfce7; color: #15803d; font-size: 0.74rem;">Incl. ₹0.00</span>
                            </div>
                            <div class="small d-flex justify-content-between text-muted mb-1 font-monospace">
                                <span>CGST: <strong id="display_cgst" class="text-dark">₹0.00</strong></span>
                                <span>SGST: <strong id="display_sgst" class="text-dark">₹0.00</strong></span>
                            </div>
                            <small class="text-muted" style="font-size: 0.74rem;">
                                <i class="bi bi-info-circle me-1"></i> Auto-calculated from product GST %
                            </small>
                        </div>
                    </div>

                    <!-- Total Discount Given Card -->
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100" style="border: 1px solid #e2e8f0 !important;">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-bold text-dark small"><i class="bi bi-tag text-danger me-1"></i> Total Discount Given</span>
                                <span class="badge rounded-pill px-2 py-1 fw-bold" id="badge_discount" style="background: #fee2e2; color: #dc2626; font-size: 0.74rem;">-₹0.00</span>
                            </div>
                            <div class="small text-muted mb-1">
                                Total Customer Savings: <strong id="display_savings" class="text-dark font-monospace">₹0.00</strong>
                            </div>
                            <small class="text-success fw-semibold" style="font-size: 0.74rem;">
                                <i class="bi bi-check-lg me-1"></i> Discount compared against MRP
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Net Payable Amount Dark Banner (Image 2 Middle) -->
                <div class="p-3 mb-3 d-flex justify-content-between align-items-center shadow-sm" style="background: #0f172a; border-radius: 14px; color: #ffffff;">
                    <div>
                        <div class="text-uppercase small fw-bold tracking-wider text-light mb-1" style="letter-spacing: 0.05em; font-size: 0.8rem;">
                            NET PAYABLE AMOUNT
                        </div>
                        <div class="text-secondary small d-flex align-items-center gap-1" style="font-size: 0.76rem;">
                            <i class="bi bi-shield-check text-info"></i> Includes GST & Item discounts
                        </div>
                    </div>
                    <div class="fw-bold font-monospace" id="display_net_payable" style="font-size: 2.2rem; color: #38bdf8;">
                        ₹0.00
                    </div>
                </div>

                <!-- Payment Method Section (Image 2) -->
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-3" style="border: 1px solid #e2e8f0 !important;">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="fw-bold text-dark small"><i class="bi bi-credit-card text-primary me-1"></i> PAYMENT METHOD</span>
                        <span class="text-muted small" style="font-size: 0.75rem;">SELECT CUSTOMER PAYMENT MODE</span>
                    </div>

                    <!-- 4 Payment Cards -->
                    <div class="row g-2 mb-3">
                        <div class="col-3">
                            <div class="pay-method-btn active text-center p-2 rounded-3 border" data-method="cash" style="cursor: pointer; transition: all 0.2s;">
                                <i class="bi bi-cash-stack fs-4 d-block mb-1"></i>
                                <span class="fw-bold small d-block">Cash</span>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="pay-method-btn text-center p-2 rounded-3 border" data-method="upi" style="cursor: pointer; transition: all 0.2s;">
                                <i class="bi bi-qr-code fs-4 d-block mb-1"></i>
                                <span class="fw-bold small d-block">UPI / QR</span>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="pay-method-btn text-center p-2 rounded-3 border" data-method="card" style="cursor: pointer; transition: all 0.2s;">
                                <i class="bi bi-credit-card fs-4 d-block mb-1"></i>
                                <span class="fw-bold small d-block">Card / POS</span>
                            </div>
                        </div>
                        <div class="col-3">
                            <div class="pay-method-btn text-center p-2 rounded-3 border" data-method="split" style="cursor: pointer; transition: all 0.2s;">
                                <i class="bi bi-pie-chart fs-4 d-block mb-1"></i>
                                <span class="fw-bold small d-block">Cash + UPI</span>
                            </div>
                        </div>
                    </div>

                    <!-- Online section toggle -->
                    <div class="form-check form-switch pt-1">
                        <input class="form-check-input" type="checkbox" id="toggle_online" name="is_online" value="1">
                        <label class="form-check-label fw-semibold text-dark small" for="toggle_online" style="font-size: 0.82rem;">
                            Show this sale in the Online section
                        </label>
                        <div class="text-muted" style="font-size: 0.74rem;">
                            Classification only; the selected payment method and payment records stay unchanged.
                        </div>
                    </div>
                </div>

                <!-- Cash Received & Change Box (Image 2) -->
                <div id="cash_received_section" class="card border-0 shadow-sm rounded-4 p-3 mb-3" style="background: #f0fdf4; border: 1.5px solid #bbf7d0 !important;">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <i class="bi bi-cash-coin text-success fs-5"></i>
                        <h6 class="fw-bold text-dark mb-0">Cash Received & Change</h6>
                    </div>
                    <small class="text-muted d-block mb-3" style="font-size: 0.78rem;">Enter the number of notes or coins received from the customer.</small>

                    <!-- 9 Denomination Fields (Row 1: 6, Row 2: 3) -->
                    <div class="row g-2 mb-2">
                        <div class="col-4 col-md-2">
                            <div class="bg-white border rounded-3 p-1 px-2 text-center" style="border-color: #cbd5e1 !important;">
                                <small class="text-muted d-block fw-bold" style="font-size: 0.74rem;">₹500 notes</small>
                                <input type="number" min="0" step="1" name="cash_denom_500" class="form-control form-control-sm border-0 text-center fw-bold shadow-none denom-cash-input" value="0" data-value="500">
                            </div>
                        </div>
                        <div class="col-4 col-md-2">
                            <div class="bg-white border rounded-3 p-1 px-2 text-center" style="border-color: #cbd5e1 !important;">
                                <small class="text-muted d-block fw-bold" style="font-size: 0.74rem;">₹200 notes</small>
                                <input type="number" min="0" step="1" name="cash_denom_200" class="form-control form-control-sm border-0 text-center fw-bold shadow-none denom-cash-input" value="0" data-value="200">
                            </div>
                        </div>
                        <div class="col-4 col-md-2">
                            <div class="bg-white border rounded-3 p-1 px-2 text-center" style="border-color: #cbd5e1 !important;">
                                <small class="text-muted d-block fw-bold" style="font-size: 0.74rem;">₹100 notes</small>
                                <input type="number" min="0" step="1" name="cash_denom_100" class="form-control form-control-sm border-0 text-center fw-bold shadow-none denom-cash-input" value="0" data-value="100">
                            </div>
                        </div>
                        <div class="col-4 col-md-2">
                            <div class="bg-white border rounded-3 p-1 px-2 text-center" style="border-color: #cbd5e1 !important;">
                                <small class="text-muted d-block fw-bold" style="font-size: 0.74rem;">₹50 notes</small>
                                <input type="number" min="0" step="1" name="cash_denom_50" class="form-control form-control-sm border-0 text-center fw-bold shadow-none denom-cash-input" value="0" data-value="50">
                            </div>
                        </div>
                        <div class="col-4 col-md-2">
                            <div class="bg-white border rounded-3 p-1 px-2 text-center" style="border-color: #cbd5e1 !important;">
                                <small class="text-muted d-block fw-bold" style="font-size: 0.74rem;">₹20 notes</small>
                                <input type="number" min="0" step="1" name="cash_denom_20" class="form-control form-control-sm border-0 text-center fw-bold shadow-none denom-cash-input" value="0" data-value="20">
                            </div>
                        </div>
                        <div class="col-4 col-md-2">
                            <div class="bg-white border rounded-3 p-1 px-2 text-center" style="border-color: #cbd5e1 !important;">
                                <small class="text-muted d-block fw-bold" style="font-size: 0.74rem;">₹10 notes</small>
                                <input type="number" min="0" step="1" name="cash_denom_10" class="form-control form-control-sm border-0 text-center fw-bold shadow-none denom-cash-input" value="0" data-value="10">
                            </div>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-4 col-md-2">
                            <div class="bg-white border rounded-3 p-1 px-2 text-center" style="border-color: #cbd5e1 !important;">
                                <small class="text-muted d-block fw-bold" style="font-size: 0.74rem;">₹5 notes / coins</small>
                                <input type="number" min="0" step="1" name="cash_denom_5" class="form-control form-control-sm border-0 text-center fw-bold shadow-none denom-cash-input" value="0" data-value="5">
                            </div>
                        </div>
                        <div class="col-4 col-md-2">
                            <div class="bg-white border rounded-3 p-1 px-2 text-center" style="border-color: #cbd5e1 !important;">
                                <small class="text-muted d-block fw-bold" style="font-size: 0.74rem;">₹2 coins</small>
                                <input type="number" min="0" step="1" name="cash_denom_2" class="form-control form-control-sm border-0 text-center fw-bold shadow-none denom-cash-input" value="0" data-value="2">
                            </div>
                        </div>
                        <div class="col-4 col-md-2">
                            <div class="bg-white border rounded-3 p-1 px-2 text-center" style="border-color: #cbd5e1 !important;">
                                <small class="text-muted d-block fw-bold" style="font-size: 0.74rem;">₹1 coins</small>
                                <input type="number" min="0" step="1" name="cash_denom_1" class="form-control form-control-sm border-0 text-center fw-bold shadow-none denom-cash-input" value="0" data-value="1">
                            </div>
                        </div>
                    </div>

                    <!-- Summary Reconciliation row -->
                    <div class="row text-center pt-2 border-top border-success border-opacity-25 g-2">
                        <div class="col-4">
                            <span class="text-muted small d-block" style="font-size: 0.74rem;">Customer Gave</span>
                            <span class="fw-bold font-monospace fs-6 text-dark" id="display_customer_gave">₹0.00</span>
                        </div>
                        <div class="col-4">
                            <span class="text-muted small d-block" style="font-size: 0.74rem;">Amount Due</span>
                            <span class="fw-bold font-monospace fs-6 text-danger" id="display_amount_due">₹0.00</span>
                        </div>
                        <div class="col-4">
                            <span class="text-muted small d-block" style="font-size: 0.74rem;">Change to Return</span>
                            <span class="fw-bold font-monospace fs-6 text-success" id="display_change_return">₹0.00</span>
                        </div>
                    </div>

                    <div class="text-center pt-2 mt-2 border-top" style="font-size: 0.76rem;">
                        <span class="text-muted">Suggested change notes / coins:</span>
                        <strong class="text-secondary ms-1" id="display_change_hint">No change required</strong>
                        <div class="text-muted mt-1">Enter the cash denominations received.</div>
                    </div>
                </div>

                <!-- Complete Sale Action Button & Version (Image 2 Bottom) -->
                <button type="submit" id="btn_complete_sale" class="btn btn-secondary w-100 py-3 rounded-4 fw-bold fs-6 shadow-sm d-flex align-items-center justify-content-center gap-2 mb-1" style="border: none; transition: all 0.2s; background: #94a3b8; color: #ffffff;">
                    <i class="bi bi-printer fs-5"></i> Complete Sale & Print Invoice 
                    <span class="badge bg-white bg-opacity-25 px-2 py-1 font-monospace">F2</span>
                </button>
                <div id="checkout_helper_hint" class="text-center small py-1 mb-2" style="font-size: 0.76rem;">
                    <span class="text-muted"><i class="bi bi-info-circle me-1"></i> Scan or select products above to begin billing.</span>
                </div>

                <div class="text-end text-muted small" style="font-size: 0.74rem;">
                    v1.0
                </div>
            </div>
        </div>
    </form>
</div>

<!-- ======================================================== -->
<!-- MODAL: INVOICE HISTORY                                    -->
<!-- ======================================================== -->
<div class="modal fade" id="modalInvoiceHistory" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 18px;">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-clock-history me-2 text-primary"></i> Generated POS Sales Invoices</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 table-custom">
                        <thead>
                            <tr>
                                <th>Invoice No</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Items</th>
                                <th>Net Amount (₹)</th>
                                <th>Payment Mode</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($invoices)): ?>
                                <tr><td colspan="7" class="text-center py-4 text-muted">No sales invoices generated yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($invoices as $inv): ?>
                                    <tr>
                                        <td><span class="badge bg-dark font-monospace"><?= htmlspecialchars($inv['invoice_no']) ?></span></td>
                                        <td class="fw-semibold text-dark"><?= htmlspecialchars($inv['customer_name']) ?></td>
                                        <td class="small text-muted font-monospace"><?= date('d M Y', strtotime($inv['invoice_date'])) ?></td>
                                        <td><span class="badge bg-light text-dark border"><?= (int)$inv['items_count'] ?> items</span></td>
                                        <td class="fw-bold text-success font-monospace">₹<?= number_format($inv['net_amount'], 2) ?></td>
                                        <td><span class="badge bg-primary bg-opacity-10 text-primary text-uppercase"><?= htmlspecialchars($inv['payment_method']) ?></span></td>
                                        <td class="text-end">
                                            <a href="<?= \App\Core\Controller::url('/m/billing?print_id=' . $inv['id']) ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-printer"></i> Print
                                            </a>
                                        </td>
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

<!-- ======================================================== -->
<!-- MODAL: INSTANT THERMAL / TAX RECEIPT PRINT VIEW           -->
<!-- ======================================================== -->
<?php if ($printInvoice): ?>
<div class="modal fade show d-block" id="modalPrintReceipt" tabindex="-1" style="background: rgba(0,0,0,0.65);" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
        <div class="modal-content border-0 shadow-lg p-3 bg-white" style="border-radius: 16px;">
            <div class="modal-header border-0 pb-1">
                <span class="badge bg-success text-uppercase fw-bold"><i class="bi bi-check-circle me-1"></i> Sale Completed</span>
                <a href="<?= \App\Core\Controller::url('/m/billing') ?>" class="btn-close" aria-label="Close"></a>
            </div>

            <!-- Printable Receipt Area -->
            <div class="p-3 border rounded-3 bg-light font-monospace small" id="thermal_receipt_print_area">
                <div class="text-center pb-2 border-bottom">
                    <h5 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($printInvoice['concern_name']) ?></h5>
                    <div style="font-size: 0.72rem;"><?= htmlspecialchars($printInvoice['concern_address'] ?: 'Retail Store Outlet') ?></div>
                    <div style="font-size: 0.72rem;">GSTIN: <?= htmlspecialchars($printInvoice['concern_gstin'] ?: '33AAACG1234F1Z8') ?></div>
                    <div style="font-size: 0.72rem;">TAX INVOICE / RETAIL RECEIPT</div>
                </div>

                <div class="py-2 border-bottom" style="font-size: 0.76rem;">
                    <div><strong>Inv No:</strong> <?= htmlspecialchars($printInvoice['invoice_no']) ?></div>
                    <div><strong>Date:</strong> <?= date('d M Y, h:i A', strtotime($printInvoice['created_at'])) ?></div>
                    <div><strong>Customer:</strong> <?= htmlspecialchars($printInvoice['customer_name']) ?> (<?= htmlspecialchars($printInvoice['customer_phone'] ?: '-') ?>)</div>
                    <div><strong>Payment:</strong> <?= strtoupper($printInvoice['payment_method']) ?> (PAID)</div>
                </div>

                <div class="py-2 border-bottom">
                    <table class="w-100" style="font-size: 0.75rem;">
                        <thead>
                            <tr class="text-muted border-bottom">
                                <th>Item</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Price</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($printItems as $it): ?>
                                <tr>
                                    <td><?= htmlspecialchars($it['item_name']) ?> <small>(<?= htmlspecialchars($it['size'] ?: '-') ?>)</small></td>
                                    <td class="text-center"><?= (int)$it['qty'] ?></td>
                                    <td class="text-end">₹<?= number_format($it['unit_price'], 2) ?></td>
                                    <td class="text-end">₹<?= number_format($it['total_price'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="py-2 border-bottom" style="font-size: 0.78rem;">
                    <div class="d-flex justify-content-between">
                        <span>Taxable Subtotal:</span>
                        <span>₹<?= number_format($printInvoice['total_amount'], 2) ?></span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>GST Tax Amount:</span>
                        <span>₹<?= number_format($printInvoice['tax_amount'], 2) ?></span>
                    </div>
                    <?php if ((float)$printInvoice['discount_amount'] > 0): ?>
                        <div class="d-flex justify-content-between text-danger">
                            <span>Discount Given:</span>
                            <span>-₹<?= number_format($printInvoice['discount_amount'], 2) ?></span>
                        </div>
                    <?php endif; ?>
                    <hr class="my-1">
                    <div class="d-flex justify-content-between fw-bold fs-6 text-dark">
                        <span>NET AMOUNT:</span>
                        <span>₹<?= number_format($printInvoice['net_amount'], 2) ?></span>
                    </div>
                </div>

                <div class="text-center pt-2 text-muted" style="font-size: 0.7rem;">
                    Thank you for shopping with us!<br>
                    Goods once sold are exchangeable within 7 days.
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 pt-3">
                <a href="<?= \App\Core\Controller::url('/m/billing') ?>" class="btn btn-outline-secondary px-3" style="border-radius: 9999px;">
                    Close
                </a>
                <button type="button" class="btn btn-primary px-4 fw-bold shadow-sm" onclick="printReceiptArea()" style="border-radius: 9999px; background: #4f46e5;">
                    <i class="bi bi-printer me-1"></i> Print Receipt
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ======================================================== -->
<!-- INTERACTIVE POS JAVASCRIPT LOGIC                         -->
<!-- ======================================================== -->
<style>
.pay-method-btn {
    background: #ffffff;
    border-color: #e2e8f0 !important;
    color: #475569;
}
.pay-method-btn:hover {
    border-color: #93c5fd !important;
    background: #f8fafc;
}
.pay-method-btn.active {
    border-color: #3b82f6 !important;
    background: #eff6ff !important;
    color: #1d4ed8 !important;
    box-shadow: 0 0 0 1px #3b82f6;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let cart = []; // Cart items array
    let currentPaymentMethod = 'cash';

    const qrInput = document.getElementById('pos_qr_input');
    const btnAddQr = document.getElementById('btn_add_qr');
    const cartTableBody = document.getElementById('cart_table_body');
    const cartEmptyState = document.getElementById('cart_empty_state');
    const cartItemsJson = document.getElementById('cart_items_json');
    const btnCompleteSale = document.getElementById('btn_complete_sale');
    const checkoutHelperHint = document.getElementById('checkout_helper_hint');
    const inputPaymentMethod = document.getElementById('input_payment_method');
    const gstTypeSelect = document.getElementById('gst_type');

    // Display fields
    const displayItemsCount = document.getElementById('display_items_count');
    const displayTaxableSubtotal = document.getElementById('display_taxable_subtotal');
    const badgeGstIncl = document.getElementById('badge_gst_incl');
    const displayCgst = document.getElementById('display_cgst');
    const displaySgst = document.getElementById('display_sgst');
    const badgeDiscount = document.getElementById('badge_discount');
    const displaySavings = document.getElementById('display_savings');
    const displayNetPayable = document.getElementById('display_net_payable');

    // Cash received fields
    const cashSection = document.getElementById('cash_received_section');
    const displayCustomerGave = document.getElementById('display_customer_gave');
    const displayAmountDue = document.getElementById('display_amount_due');
    const displayChangeReturn = document.getElementById('display_change_return');
    const displayChangeHint = document.getElementById('display_change_hint');
    const denomCashInputs = document.querySelectorAll('.denom-cash-input');

    // Customer fields
    const custMobile = document.getElementById('cust_mobile');
    const custName = document.getElementById('cust_name');
    const custAddress = document.getElementById('cust_address');
    const btnWalkin = document.getElementById('btn_walkin');

    // 1. Walk-in Customer Auto-Fill
    if (btnWalkin) {
        btnWalkin.addEventListener('click', function() {
            custMobile.value = '9999999999';
            custName.value = 'Walk-in Retail Customer';
            custAddress.value = 'Counter Walk-in / Local Store';
        });
    }

    // 2. Mobile Number Auto-Lookup
    if (custMobile) {
        custMobile.addEventListener('input', function() {
            const phone = this.value.trim();
            if (phone.length === 10) {
                fetch('<?= \App\Core\Controller::url('/api/pos/customer-lookup?phone=') ?>' + encodeURIComponent(phone))
                    .then(res => res.json())
                    .then(data => {
                        if (data.found && data.customer) {
                            custName.value = data.customer.name || '';
                            custAddress.value = data.customer.address || data.customer.city || '';
                        }
                    })
                    .catch(() => {});
            }
        });
    }

    // 3. Scan & Add Product Function
    function addProductByQuery(query) {
        if (!query) return;
        query = query.trim();
        if (!query) return;

        const scannerStatus = document.getElementById('scanner_status_text');
        if (scannerStatus) scannerStatus.textContent = 'Searching item...';

        fetch('<?= \App\Core\Controller::url('/api/pos/variant-lookup?q=') ?>' + encodeURIComponent(query))
            .then(res => res.json())
            .then(data => {
                if (data.success && data.variant) {
                    const v = data.variant;
                    // Check if already in cart
                    const existingIndex = cart.findIndex(item => item.id === v.id);
                    if (existingIndex > -1) {
                        cart[existingIndex].qty += 1;
                    } else {
                        cart.push({
                            id: v.id,
                            name: v.name,
                            sku_code: v.sku_code,
                            size: v.size,
                            hsn_code: v.hsn_code || '6109',
                            price: v.price,
                            mrp: v.mrp,
                            gst_pct: v.gst_pct || 5.0,
                            discount: 0.00,
                            qty: 1
                        });
                    }

                    if (scannerStatus) {
                        scannerStatus.textContent = 'Added: ' + v.sku_code;
                        setTimeout(() => { scannerStatus.textContent = 'Ready for next scan...'; }, 2000);
                    }

                    qrInput.value = '';
                    renderCart();
                } else {
                    alert(data.message || 'Product not found in this concern catalog.');
                    if (scannerStatus) scannerStatus.textContent = 'Item not found.';
                }
            })
            .catch(err => {
                alert('Failed to lookup product: ' + err);
            });
    }

    // QR Input Submit triggers
    if (btnAddQr && qrInput) {
        btnAddQr.addEventListener('click', () => addProductByQuery(qrInput.value));
        qrInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                addProductByQuery(this.value);
            }
        });
    }

    // Quick add garment buttons
    document.querySelectorAll('.btn-quick-var').forEach(btn => {
        btn.addEventListener('click', function() {
            const sku = this.getAttribute('data-sku');
            addProductByQuery(sku);
        });
    });

    // 4. Render Cart Function
    window.renderCart = function() {
        if (!cartTableBody) return;

        cartTableBody.innerHTML = '';

        if (cart.length === 0) {
            cartEmptyState.classList.remove('d-none');
            btnCompleteSale.style.background = '#94a3b8';
            btnCompleteSale.style.color = '#ffffff';
            btnCompleteSale.style.cursor = 'not-allowed';
            btnCompleteSale.style.boxShadow = 'none';
            if (checkoutHelperHint) {
                checkoutHelperHint.innerHTML = '<span class="text-secondary"><i class="bi bi-info-circle me-1"></i> Scan or click a product on the left to begin billing.</span>';
            }
        } else {
            cartEmptyState.classList.add('d-none');
            btnCompleteSale.style.background = '#4f46e5';
            btnCompleteSale.style.color = '#ffffff';
            btnCompleteSale.style.cursor = 'pointer';
            btnCompleteSale.style.boxShadow = '0 4px 6px -1px rgba(79, 70, 229, 0.2), 0 2px 4px -2px rgba(79, 70, 229, 0.2)';
            if (checkoutHelperHint) {
                checkoutHelperHint.innerHTML = '<span class="text-success fw-semibold"><i class="bi bi-check-circle me-1"></i> Ready to checkout. Press F2 or click above.</span>';
            }

            const gstType = gstTypeSelect ? gstTypeSelect.value : 'both';

            cart.forEach((item, index) => {
                const tr = document.createElement('tr');
                const effectiveGstPct = gstType === 'exempt' ? 0.00 : item.gst_pct;
                const unitPrice = parseFloat(item.price) || 0;
                const qty = item.qty || 1;
                const discount = parseFloat(item.discount) || 0;
                const itemBaseSubtotal = Math.max(0, (unitPrice * qty) - discount);
                const itemGst = (itemBaseSubtotal * effectiveGstPct) / 100.0;
                const billedPrice = itemBaseSubtotal + itemGst;

                tr.innerHTML = `
                    <td class="text-muted fw-bold">${index + 1}</td>
                    <td>
                        <strong class="text-dark d-block">${escapeHtml(item.name)}</strong>
                        <small class="text-muted font-monospace">${escapeHtml(item.sku_code)} &bull; Size: ${escapeHtml(item.size)}</small>
                    </td>
                    <td><span class="badge bg-light text-dark border font-monospace">${escapeHtml(item.hsn_code)}</span></td>
                    <td class="fw-bold font-monospace">₹${unitPrice.toFixed(2)}</td>
                    <td>
                        <span class="badge bg-light text-primary border">${effectiveGstPct}%</span>
                    </td>
                    <td>
                        <input type="number" min="0" step="1" class="form-control form-control-sm text-end font-monospace item-discount-input" 
                               value="${discount.toFixed(0)}" data-index="${index}" style="width: 75px; font-size: 0.8rem; padding: 0.2rem 0.4rem;">
                    </td>
                    <td class="text-end fw-bold font-monospace text-dark fs-6">
                        ₹${billedPrice.toFixed(2)}
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="removeCartItem(${index})" title="Remove item">
                            <i class="bi bi-x-circle-fill"></i>
                        </button>
                    </td>
                `;
                cartTableBody.appendChild(tr);
            });

            // Attach discount change listeners
            document.querySelectorAll('.item-discount-input').forEach(input => {
                input.addEventListener('input', function() {
                    const idx = parseInt(this.getAttribute('data-index'));
                    cart[idx].discount = parseFloat(this.value) || 0;
                    calculateCartTotals();
                });
            });
        }

        calculateCartTotals();
    };

    window.removeCartItem = function(index) {
        cart.splice(index, 1);
        renderCart();
    };

    function escapeHtml(text) {
        if (!text) return '';
        return String(text).replace(/[&<>"']/g, function(m) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
        });
    }

    // 5. Calculate Cart Totals
    function calculateCartTotals() {
        const gstType = gstTypeSelect ? gstTypeSelect.value : 'both';
        let taxableSubtotal = 0.00;
        let totalGst = 0.00;
        let totalDiscount = 0.00;
        let totalItemsCount = 0;

        cart.forEach(item => {
            const qty = item.qty || 1;
            const unitPrice = parseFloat(item.price) || 0;
            const discount = parseFloat(item.discount) || 0;
            const effectiveGstPct = gstType === 'exempt' ? 0.00 : item.gst_pct;

            const itemSub = Math.max(0, (unitPrice * qty) - discount);
            const itemTax = (itemSub * effectiveGstPct) / 100.0;

            taxableSubtotal += itemSub;
            totalGst += itemTax;
            totalDiscount += discount;
            totalItemsCount += qty;
        });

        const netPayable = taxableSubtotal + totalGst;

        // Update displays
        if (displayItemsCount) displayItemsCount.textContent = totalItemsCount;
        if (displayTaxableSubtotal) displayTaxableSubtotal.textContent = '₹' + taxableSubtotal.toFixed(2);
        
        if (badgeGstIncl) badgeGstIncl.textContent = 'Incl. ₹' + totalGst.toFixed(2);
        if (displayCgst) displayCgst.textContent = '₹' + (totalGst / 2.0).toFixed(2);
        if (displaySgst) displaySgst.textContent = '₹' + (totalGst / 2.0).toFixed(2);

        if (badgeDiscount) badgeDiscount.textContent = '-₹' + totalDiscount.toFixed(2);
        if (displaySavings) displaySavings.textContent = '₹' + totalDiscount.toFixed(2);

        if (displayNetPayable) displayNetPayable.textContent = '₹' + netPayable.toFixed(2);

        // Update hidden JSON
        if (cartItemsJson) cartItemsJson.value = JSON.stringify(cart);

        // Update cash received change calculation
        updateCashChange(netPayable);
    }

    if (gstTypeSelect) {
        gstTypeSelect.addEventListener('change', () => renderCart());
    }

    // 6. Payment Method Selection (Cards)
    document.querySelectorAll('.pay-method-btn').forEach(card => {
        card.addEventListener('click', function() {
            document.querySelectorAll('.pay-method-btn').forEach(c => c.classList.remove('active'));
            this.classList.add('active');
            currentPaymentMethod = this.getAttribute('data-method');
            if (inputPaymentMethod) inputPaymentMethod.value = currentPaymentMethod;

            // Show or hide Cash Received section
            if (cashSection) {
                if (currentPaymentMethod === 'cash' || currentPaymentMethod === 'split') {
                    cashSection.style.display = 'block';
                } else {
                    cashSection.style.display = 'none';
                }
            }
        });
    });

    // 7. Cash Received & Change Calculator
    function calculateCashGiven() {
        let total = 0;
        denomCashInputs.forEach(input => {
            const count = parseFloat(input.value) || 0;
            const multiplier = parseFloat(input.getAttribute('data-value')) || 1;
            total += count * multiplier;
        });
        return total;
    }

    function updateCashChange(netPayable) {
        const cashGiven = calculateCashGiven();
        if (displayCustomerGave) displayCustomerGave.textContent = '₹' + cashGiven.toFixed(2);

        if (netPayable <= 0) {
            if (displayAmountDue) displayAmountDue.textContent = '₹0.00';
            if (displayChangeReturn) displayChangeReturn.textContent = '₹0.00';
            if (displayChangeHint) {
                if (cashGiven > 0) {
                    displayChangeHint.textContent = 'Cart is empty (₹0.00 bill) — Add products to cart';
                    displayChangeHint.className = 'text-danger fw-semibold ms-1';
                } else {
                    displayChangeHint.textContent = 'No change required';
                    displayChangeHint.className = 'text-secondary ms-1';
                }
            }
            return;
        }

        const amountDue = Math.max(0, netPayable - cashGiven);
        if (displayAmountDue) displayAmountDue.textContent = '₹' + amountDue.toFixed(2);

        const changeReturn = Math.max(0, cashGiven - netPayable);
        if (displayChangeReturn) displayChangeReturn.textContent = '₹' + changeReturn.toFixed(2);

        if (displayChangeHint) {
            if (changeReturn === 0) {
                displayChangeHint.textContent = 'No change required';
                displayChangeHint.className = 'text-secondary ms-1';
            } else {
                displayChangeHint.textContent = 'Return ₹' + changeReturn.toFixed(2) + ' change to customer';
                displayChangeHint.className = 'text-success fw-bold ms-1';
            }
        }
    }

    denomCashInputs.forEach(input => {
        input.addEventListener('input', function() {
            const currentNet = parseFloat(displayNetPayable.textContent.replace('₹', '')) || 0;
            updateCashChange(currentNet);
        });
    });

    // 8. Complete Sale Validation & F2 Keyboard Shortcut
    if (btnCompleteSale) {
        btnCompleteSale.addEventListener('click', function(e) {
            if (cart.length === 0) {
                e.preventDefault();
                alert('⚠️ Billing Cart is Empty!\n\nPlease scan a garment QR code, type an SKU, or select an item from the Quick Catalog Pick on the left before completing the sale.');
                if (qrInput) qrInput.focus();
                return false;
            }

            // Customer validation
            if (!custMobile.value.trim() || !custName.value.trim()) {
                const autoWalkin = confirm('Customer details are empty.\n\nClick OK to bill as "Walk-in Retail Customer", or Cancel to type customer info.');
                if (autoWalkin && btnWalkin) {
                    btnWalkin.click();
                } else {
                    e.preventDefault();
                    if (custMobile) custMobile.focus();
                    return false;
                }
            }

            // Cash reconciliation check
            const currentNet = parseFloat(displayNetPayable.textContent.replace('₹', '')) || 0;
            if (currentPaymentMethod === 'cash') {
                const cashGiven = calculateCashGiven();
                if (cashGiven > 0 && cashGiven < currentNet) {
                    const proceed = confirm(`Customer gave ₹${cashGiven.toFixed(2)}, but total net amount is ₹${currentNet.toFixed(2)}.\n\nRemaining amount due is ₹${(currentNet - cashGiven).toFixed(2)}. Complete sale anyway?`);
                    if (!proceed) {
                        e.preventDefault();
                        return false;
                    }
                }
            }
        });
    }

    window.addEventListener('keydown', function(e) {
        if (e.key === 'F2') {
            e.preventDefault();
            if (btnCompleteSale) {
                btnCompleteSale.click();
            }
        }
    });

    // 9. Camera Scanner Toggle
    let html5QrScanner = null;
    const btnToggleCam = document.getElementById('btnToggleCam');
    const camBox = document.getElementById('cam_scanner_box');

    if (btnToggleCam && camBox) {
        btnToggleCam.addEventListener('click', function() {
            if (camBox.classList.contains('d-none')) {
                camBox.classList.remove('d-none');
                if (typeof Html5Qrcode !== 'undefined') {
                    html5QrScanner = new Html5Qrcode("qr_reader");
                    html5QrScanner.start(
                        { facingMode: "environment" },
                        { fps: 10, qrbox: { width: 200, height: 200 } },
                        (decodedText) => {
                            addProductByQuery(decodedText);
                        },
                        () => {}
                    ).catch(err => {
                        console.log("Camera access error:", err);
                    });
                }
            } else {
                camBox.classList.add('d-none');
                if (html5QrScanner) {
                    html5QrScanner.stop().catch(() => {});
                }
            }
        });
    }

    // Initialize Empty State
    renderCart();
});

// Thermal Receipt Print Helper
function printReceiptArea() {
    const printContents = document.getElementById('thermal_receipt_print_area').innerHTML;
    const originalContents = document.body.innerHTML;
    document.body.innerHTML = '<div style="width: 320px; margin: auto; padding: 20px; font-family: monospace;">' + printContents + '</div>';
    window.print();
    document.body.innerHTML = originalContents;
    window.location.href = '<?= \App\Core\Controller::url('/m/billing') ?>';
}
</script>
