<?php
// app/views/concern/catalog_mapping_form.php - Map Finished Good Item to 3-Tier Hierarchy & Attributes (All Details in Table Format)
?>
<div class="container-fluid px-0">
    <!-- Breadcrumbs -->
    <nav aria-label="breadcrumb" class="mb-2">
        <ol class="breadcrumb small mb-1">
            <li class="breadcrumb-item"><a href="<?= \App\Core\Controller::url('/') ?>" class="text-decoration-none text-muted">Home</a></li>
            <li class="breadcrumb-item text-muted">Production</li>
            <li class="breadcrumb-item"><a href="<?= \App\Core\Controller::url('/production/catalog-mapping') ?>" class="text-decoration-none text-muted">Catalog Mapping</a></li>
            <li class="breadcrumb-item active fw-semibold text-dark" aria-current="page">Map Finished Good</li>
        </ol>
    </nav>

    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3">
            <div class="p-2.5 rounded-3 bg-primary-subtle text-primary border border-primary-subtle shadow-xs">
                <i class="bi bi-diagram-3-fill fs-4"></i>
            </div>
            <div>
                <h4 class="fw-bold mb-0 text-dark">Map Finished Good to Catalog</h4>
                <p class="text-secondary small mb-0">Define 3-tier category hierarchy, attributes (sizes, colors), HSN classification, retail pricing, and variant matrix for purchased product <strong><?= htmlspecialchars($item['item_name']) ?></strong>.</p>
            </div>
        </div>
        <div>
            <a href="<?= \App\Core\Controller::url('/production/catalog-mapping') ?>" class="btn btn-outline-secondary rounded-3 d-inline-flex align-items-center gap-1.5 shadow-sm fw-semibold">
                <i class="bi bi-arrow-left"></i> Back to Register
            </a>
        </div>
    </div>

    <?php if (!empty($flashSuccess)): ?>
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill fs-5 me-2"></i>
            <div><?= nl2br(htmlspecialchars($flashSuccess)) ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($flashError)): ?>
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center shadow-sm mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-5 me-2"></i>
            <div><?= nl2br(htmlspecialchars($flashError)) ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Main Mapping Form (All Details in Table Format) -->
    <form action="<?= \App\Core\Controller::url('/production/catalog-mapping/save') ?>" method="POST" id="formCatalogMapping" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
        <input type="hidden" name="purchase_bill_item_id" value="<?= (int)$item['id'] ?>">

        <!-- ================================================================= -->
        <!-- 1. PURCHASED PRODUCT BASELINE DETAILS (TABLE FORMAT)              -->
        <!-- ================================================================= -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-receipt-cutoff text-primary fs-5"></i>
                    <h6 class="mb-0 fw-bold text-dark">1. Purchased Product Baseline Details</h6>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="badge bg-light text-dark border font-monospace px-2.5 py-1.5">
                        <i class="bi bi-receipt me-1 text-primary"></i>Purchase Bill #<?= htmlspecialchars($item['bill_no']) ?>
                    </span>
                    <?php if (!empty($item['supplier_invoice_no'])): ?>
                        <span class="badge bg-light text-secondary border font-monospace px-2.5 py-1.5">
                            <i class="bi bi-file-earmark-text me-1"></i>Supplier Inv: <?= htmlspecialchars($item['supplier_invoice_no']) ?>
                        </span>
                    <?php endif; ?>
                    <?php if (!empty($item['payment_status'])): ?>
                        <?php if ($item['payment_status'] === 'paid'): ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1.5">
                                <i class="bi bi-check-circle-fill me-1"></i>Paid
                            </span>
                        <?php elseif ($item['payment_status'] === 'partial'): ?>
                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2.5 py-1.5">
                                <i class="bi bi-hourglass-split me-1"></i>Partial
                            </span>
                        <?php else: ?>
                            <span class="badge bg-secondary-subtle text-secondary border px-2.5 py-1.5">
                                <i class="bi bi-dash-circle me-1"></i>Unpaid
                            </span>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php if (!empty($item['bill_inward_status']) || !empty($item['inward_status'])): ?>
                        <?php $inw = $item['inward_status'] ?? ($item['bill_inward_status'] ?? 'pending'); ?>
                        <?php if ($inw === 'completed'): ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1.5">
                                <i class="bi bi-box-seam me-1"></i>Inward Completed
                            </span>
                        <?php else: ?>
                            <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2.5 py-1.5">
                                <i class="bi bi-clock-history me-1"></i>Pending Inward
                            </span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body p-4">
                <!-- Table 1.1: Purchased Product Identity & Supplier Profile Table -->
                <div class="table-responsive mb-4">
                    <table class="table table-bordered table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 28%;"><i class="bi bi-box-seam text-primary me-1"></i> Product Identity</th>
                                <th style="width: 36%;"><i class="bi bi-building text-primary me-1"></i> Supplier Profile</th>
                                <th style="width: 36%;"><i class="bi bi-calendar-event text-primary me-1"></i> Invoice & Accounting References</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark fs-6 mb-1"><?= htmlspecialchars($item['item_name']) ?></div>
                                    <div class="d-flex align-items-center gap-1.5 flex-wrap">
                                        <?php if (!empty($item['item_code'])): ?>
                                            <span class="badge bg-light text-dark border font-monospace">Item Code: <?= htmlspecialchars($item['item_code']) ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-dark border font-monospace">Item Code: FG-<?= sprintf('%04d', $item['id']) ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($resolvedColor)): ?>
                                            <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle">
                                                <i class="bi bi-palette me-1"></i><?= htmlspecialchars($resolvedColor) ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if (!empty($item['specification'])): ?>
                                            <span class="badge bg-light text-secondary border"><?= htmlspecialchars($item['specification']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><i class="bi bi-shop me-1 text-primary"></i><?= htmlspecialchars($item['supplier_name'] ?? 'Supplier') ?></div>
                                    <div class="small text-secondary mt-1">
                                        <?php if (!empty($item['supplier_phone'])): ?>
                                            <span class="me-2"><i class="bi bi-telephone me-1 text-muted"></i><?= htmlspecialchars($item['supplier_phone']) ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($item['supplier_gstin'])): ?>
                                            <span class="font-monospace text-muted"><i class="bi bi-shield-check me-1"></i>GSTIN: <?= htmlspecialchars($item['supplier_gstin']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($item['supplier_city']) || !empty($item['supplier_state'])): ?>
                                        <div class="small text-muted mt-0.5">
                                            <i class="bi bi-geo-alt me-1 text-muted"></i><?= htmlspecialchars(implode(', ', array_filter([$item['supplier_city'] ?? '', $item['supplier_state'] ?? '']))) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="small text-dark mb-1">
                                        <span>Purchase Date: <strong class="fw-bold"><?= date('d M Y', strtotime($item['bill_date'])) ?></strong></span>
                                        &bull; <span>Bill Ref: <strong class="font-monospace text-primary">#<?= htmlspecialchars($item['bill_no']) ?></strong></span>
                                    </div>
                                    <div class="small text-secondary">
                                        <span>Payment Method: <strong class="text-dark"><?= htmlspecialchars(ucfirst($item['payment_method'] ?? 'Credit')) ?></strong></span>
                                        <?php if (!empty($item['paid_amount']) && (float)$item['paid_amount'] > 0): ?>
                                            &bull; <span>Paid: <strong class="text-success">₹<?= number_format((float)$item['paid_amount'], 2) ?></strong></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($item['supplier_invoice_no'])): ?>
                                        <div class="small text-muted font-monospace mt-0.5">
                                            Vendor Inv: <strong><?= htmlspecialchars($item['supplier_invoice_no']) ?></strong>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Table 1.2: Purchase Quantities & Financial Accounting Valuation Table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle text-center mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Purchased Qty</th>
                                <th>Unit</th>
                                <th>Supplier Unit Cost</th>
                                <th>Tax Mode</th>
                                <th>HSN Code</th>
                                <th>GST Rate</th>
                                <th>Tax Amount</th>
                                <th class="table-success text-success-emphasis">Total Line Valuation</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="fw-bold text-primary font-monospace fs-6"><?= number_format((float)$item['qty']) ?></td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($item['unit'] ?: 'Pieces') ?></span></td>
                                <td class="fw-bold font-monospace text-dark">₹<?= number_format((float)$item['unit_price'], 2) ?></td>
                                <td><span class="badge bg-light text-secondary border"><?= htmlspecialchars($item['tax_mode'] ?: 'Exclude GST') ?></span></td>
                                <td class="font-monospace fw-semibold"><?= htmlspecialchars($item['hsn_code'] ?: '6109') ?></td>
                                <td class="font-monospace text-secondary"><?= number_format((float)($item['gst_rate'] ?? 5.00), 2) ?>% GST</td>
                                <td class="font-monospace text-muted">₹<?= number_format((float)($item['tax_amount'] ?? 0), 2) ?></td>
                                <td class="table-success fw-bold text-success font-monospace fs-6">₹<?= number_format((float)($item['total_price'] ?? 0) + (float)($item['tax_amount'] ?? 0), 2) ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ================================================================= -->
        <!-- 2. CATEGORY HIERARCHY (3-TIER) & HSN CLASSIFICATION (TABLE FORMAT)-->
        <!-- ================================================================= -->
        <div class="card border-0 shadow-sm rounded-3 mb-4" id="cardCategoryHierarchy">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-diagram-3-fill text-primary fs-5"></i>
                    <h6 class="mb-0 fw-bold text-dark">2. Category Hierarchy (3-Tier) & HSN Classification</h6>
                </div>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1">Required</span>
            </div>
            <div class="card-body p-4">
                
                <!-- Mapping Mode Selector (Create New vs Link Existing) -->
                <div class="mb-4">
                    <label class="form-label small fw-semibold text-dark mb-2 text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">Select Mapping Action:</label>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-check p-3 border rounded-3 bg-light cursor-pointer h-100">
                                <input class="form-check-input" type="radio" name="mapping_mode" id="modeNewStyle" value="new_style" checked onchange="toggleMappingMode('new_style')">
                                <label class="form-check-label fw-bold text-dark d-block cursor-pointer" for="modeNewStyle">
                                    <i class="bi bi-plus-circle text-primary me-1"></i> Create New Catalog Style
                                    <small class="text-secondary fw-normal d-block mt-1">Configure fresh 3-tier hierarchy, style code, and size variants.</small>
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check p-3 border rounded-3 bg-light cursor-pointer h-100">
                                <input class="form-check-input" type="radio" name="mapping_mode" id="modeExistingStyle" value="existing_style" onchange="toggleMappingMode('existing_style')">
                                <label class="form-check-label fw-bold text-dark d-block cursor-pointer" for="modeExistingStyle">
                                    <i class="bi bi-link-45deg text-success me-1"></i> Link to Existing Catalog Style
                                    <small class="text-secondary fw-normal d-block mt-1">Attach to an already created style in the production catalog.</small>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Existing Style Dropdown (Hidden by default) -->
                    <div id="containerExistingStyle" class="mt-3 p-3 bg-light border rounded-3 d-none">
                        <label class="form-label small fw-semibold text-dark mb-1">Select Existing Catalog Style <span class="text-danger">*</span></label>
                        <select name="existing_catalog_id" id="selectExistingCatalog" class="form-select">
                            <option value="">-- Choose Existing Style --</option>
                            <?php foreach ($catalogs as $c): ?>
                                <option value="<?= $c['id'] ?>">
                                    [<?= htmlspecialchars($c['style_code']) ?>] <?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['hierarchy_path'] ?: 'General') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="small text-muted mt-1">When linked, this finished good will adopt the existing style's 3-tier hierarchy and attributes.</div>
                    </div>
                </div>

                <!-- 3-Tier Hierarchy Content (Table Format for New Style) -->
                <div id="cardCategoryHierarchyContent">
                    
                    <!-- Table 2.1: 3-Tier Category Hierarchy Matrix Table -->
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 22%;"><i class="bi bi-diagram-3 text-primary me-1"></i> Hierarchy Tier</th>
                                    <th style="width: 44%;"><i class="bi bi-check2-square text-primary me-1"></i> Category Selection</th>
                                    <th style="width: 14%;"><i class="bi bi-qr-code text-primary me-1"></i> Short Code</th>
                                    <th style="width: 20%;"><i class="bi bi-link text-primary me-1"></i> Level Purpose</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Tier 1: Main Category -->
                                <tr>
                                    <td>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle me-1.5">Tier 1</span>
                                        <strong class="text-dark">Main Category <span class="text-danger">*</span></strong>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <select name="category_id" id="selectMainCategory" class="form-select form-select-sm" required onchange="handleMainCategoryChange(this.value)">
                                                <option value="">-- Select Category --</option>
                                                <?php foreach ($categories as $cat): ?>
                                                    <option value="<?= $cat['id'] ?>" data-code="<?= htmlspecialchars($cat['code'] ?? '') ?>" data-hsn="<?= htmlspecialchars($cat['default_hsn'] ?? '6109') ?>" data-gst="<?= (float)($cat['default_gst_rate'] ?? 5.00) ?>" <?= (!empty($item['mapped_cat_id']) && $item['mapped_cat_id'] == $cat['id']) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($cat['name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="button" class="btn btn-outline-primary btn-sm py-0.5 px-2 text-nowrap" data-bs-toggle="modal" data-bs-target="#modalAddCategory">
                                                <i class="bi bi-plus-circle"></i> New
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="text" name="main_category_code" id="inputMainCatCode" class="form-control form-control-sm font-monospace text-uppercase" placeholder="E.G. W" oninput="updateWholeCode()">
                                    </td>
                                    <td><small class="text-muted">Primary division (e.g. Menswear, Women, Kids)</small></td>
                                </tr>

                                <!-- Tier 2: Sub Category -->
                                <tr>
                                    <td>
                                        <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle me-1.5">Tier 2</span>
                                        <strong class="text-dark">Sub Category</strong>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <select name="subcategory_id" id="selectSubCategory" class="form-select form-select-sm" onchange="handleSubCategoryChange(this.value)">
                                                <option value="">-- Select Category First --</option>
                                            </select>
                                            <button type="button" class="btn btn-outline-primary btn-sm py-0.5 px-2 text-nowrap" data-bs-toggle="modal" data-bs-target="#modalAddSubcategory">
                                                <i class="bi bi-plus-circle"></i> New
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="text" name="sub_category_code" id="inputSubCatCode" class="form-control form-control-sm font-monospace text-uppercase" placeholder="E.G. M" oninput="updateWholeCode()">
                                    </td>
                                    <td><small class="text-muted">Garment classification (e.g. Shirts, Trousers)</small></td>
                                </tr>

                                <!-- Tier 3: Sub-Sub Category -->
                                <tr>
                                    <td>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle me-1.5">Tier 3</span>
                                        <strong class="text-dark">Sub Sub Category</strong>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <select name="sub_sub_category_id" id="selectSubSubCategory" class="form-select form-select-sm" onchange="handleSubSubCategoryChange(this.value)">
                                                <option value="">-- Select Sub Category First --</option>
                                            </select>
                                            <button type="button" class="btn btn-outline-primary btn-sm py-0.5 px-2 text-nowrap" data-bs-toggle="modal" data-bs-target="#modalAddSubSubcategory">
                                                <i class="bi bi-plus-circle"></i> New
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="text" name="sub_sub_category_code" id="inputSubSubCatCode" class="form-control form-control-sm font-monospace text-uppercase" placeholder="E.G. F" oninput="updateWholeCode()">
                                    </td>
                                    <td><small class="text-muted">Styling variation (e.g. Formal, Casual, Party)</small></td>
                                </tr>

                                <!-- Hierarchy Summary Row -->
                                <tr class="table-light">
                                    <td class="fw-bold text-dark"><i class="bi bi-folder2-open text-primary me-1"></i> Full Hierarchy Path</td>
                                    <td colspan="2">
                                        <div class="fw-bold text-primary font-monospace" id="hierarchyPathText">Select a category above...</div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-1.5">
                                            <span class="small text-muted">QR Key:</span>
                                            <input type="text" name="whole_code" id="inputWholeCode" class="form-control form-control-sm font-monospace text-uppercase fw-bold text-primary" placeholder="E.G. WMF" style="max-width: 110px;">
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Hidden HSN and GST Inputs -->
                    <input type="hidden" name="hsn_code" id="inputHsnCode" value="<?= htmlspecialchars($item['hsn_code'] ?: '6109') ?>">
                    <input type="hidden" name="gst_rate" id="inputGstRate" value="<?= number_format((float)($item['gst_rate'] ?? 5.00), 2, '.', '') ?>">
                    <input type="hidden" name="tax_mode" value="<?= htmlspecialchars($item['tax_mode'] ?: 'Exclude GST') ?>">
                    <input type="hidden" name="gst_type" value="<?= htmlspecialchars($item['gst_type'] ?: 'Both (CGST & SGST)') ?>">

                </div>

            </div>
        </div>

        <!-- ================================================================= -->
        <!-- 3. PRODUCT ATTRIBUTES & VARIATIONS (TABLE FORMAT)                 -->
        <!-- ================================================================= -->
        <div class="card border-0 shadow-sm rounded-3 mb-4" id="cardProductAttributes">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-tags-fill text-primary fs-5"></i>
                    <h6 class="mb-0 fw-bold text-dark">3. Product Attributes & Variations</h6>
                </div>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1">Required</span>
            </div>
            <div class="card-body p-4">
                
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 26%;"><i class="bi bi-tag text-primary me-1"></i> Attribute Name</th>
                                <th style="width: 48%;"><i class="bi bi-input-cursor-text text-primary me-1"></i> Configured Value / Selection Input</th>
                                <th style="width: 26%;"><i class="bi bi-info-circle text-primary me-1"></i> Attribute Description & Usage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Row 1: Product Style Code -->
                            <tr>
                                <td>
                                    <strong class="text-dark">Product Style Code <span class="text-danger">*</span></strong>
                                    <small class="text-muted d-block">Master Parent SKU Reference</small>
                                </td>
                                <td>
                                    <input type="text" name="style_code" id="inputStyleCode" class="form-control font-monospace fw-bold" value="<?= htmlspecialchars($suggestedStyleCode) ?>" required oninput="renderVariantsTable();">
                                </td>
                                <td>
                                    <small class="text-muted">Master style reference code used for barcode generation and variant SKUs.</small>
                                </td>
                            </tr>

                            <!-- Row 2: Product Style Name -->
                            <tr>
                                <td>
                                    <strong class="text-dark">Product Style Name <span class="text-danger">*</span></strong>
                                    <small class="text-muted d-block">Retail Catalog Product Title</small>
                                </td>
                                <td>
                                    <input type="text" name="style_name" id="inputStyleName" class="form-control fw-semibold" value="<?= htmlspecialchars($item['item_name']) ?>" required oninput="renderVariantsTable();">
                                </td>
                                <td>
                                    <small class="text-muted">Pre-filled from bill product name. Editable for catalog display.</small>
                                </td>
                            </tr>

                            <!-- Row 3: Color / Fabric Variation -->
                            <tr>
                                <td>
                                    <strong class="text-dark">Color / Fabric Variation</strong>
                                    <small class="text-muted d-block">Visual / Aesthetic Attribute</small>
                                </td>
                                <td>
                                    <input type="text" name="color" id="inputColor" class="form-control" value="<?= htmlspecialchars($resolvedColor ?? '') ?>" placeholder="e.g. Red, Navy Blue, Maroon" oninput="renderVariantsTable();">
                                </td>
                                <td>
                                    <small class="text-muted">Automatically extracted from item specifications or product name.</small>
                                </td>
                            </tr>

                            <!-- Row 4: Size Attributes (Multi-Select Pills Table Row) -->
                            <tr>
                                <td>
                                    <strong class="text-dark">Size Attributes <span class="text-danger">*</span></strong>
                                    <small class="text-muted d-block">Variant Sizing Matrix</small>
                                </td>
                                <td>
                                    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                                        <span class="small fw-semibold text-secondary">Check all sizes to generate catalog variants:</span>
                                        <div class="d-flex align-items-center gap-1">
                                            <button type="button" class="btn btn-outline-secondary btn-sm py-0.5 px-2" style="font-size: 0.75rem;" onclick="selectAllSizes()">Select All</button>
                                            <button type="button" class="btn btn-outline-secondary btn-sm py-0.5 px-2" style="font-size: 0.75rem;" onclick="clearAllSizes()">Clear</button>
                                        </div>
                                    </div>
                                    <div class="d-flex flex-wrap gap-2 mb-3" id="sizesContainer">
                                        <?php foreach ($sizes as $s): ?>
                                            <label class="size-pill-label border rounded-3 p-2 d-inline-flex align-items-center gap-2 cursor-pointer bg-light">
                                                <input type="checkbox" name="sizes[]" value="<?= htmlspecialchars($s) ?>" class="form-check-input size-checkbox mt-0" onchange="handleSizeCheckboxChange(this)">
                                                <span class="fw-bold font-monospace size-text text-dark"><?= htmlspecialchars($s) ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>

                                    <!-- Add Custom Size Input -->
                                    <div class="input-group input-group-sm" style="max-width: 420px;">
                                        <input type="text" id="inputCustomSize" class="form-control font-monospace" placeholder="Add custom size (e.g. 4XL, 32, 34)...">
                                        <button type="button" class="btn btn-outline-primary fw-semibold" onclick="addCustomSizePill()">
                                            <i class="bi bi-plus-lg me-1"></i> Add Size
                                        </button>
                                    </div>
                                </td>
                                <td>
                                    <div class="p-2.5 rounded-3 bg-light border">
                                        <div class="small fw-bold text-primary mb-1"><i class="bi bi-cpu me-1"></i>Auto SKU Generation:</div>
                                        <small class="text-muted d-block mb-1">Check one or more sizes above to generate editable variant rows in the table below.</small>
                                        <span class="badge bg-secondary-subtle text-secondary border font-monospace" id="attrSizeCountBadge">0 Sizes Active</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>

        <!-- ================================================================= -->
        <!-- 4. PRODUCT VARIANTS MATRIX TABLE (EDITABLE FORMAT)                -->
        <!-- ================================================================= -->
        <div class="card border-0 shadow-sm rounded-3 mb-4" id="cardProductVariants">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-grid-3x3-gap-fill text-primary fs-5"></i>
                    <h6 class="mb-0 fw-bold text-dark">4. Product Variants Matrix Table (Editable Format)</h6>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary font-monospace px-2.5 py-1.5" id="variantsCountBadge">0 Variants</span>
                    <span class="badge bg-success-subtle text-success border border-success-subtle font-monospace">Editable SKU & Pricing</span>
                </div>
            </div>

            <!-- Quick Batch Pricing Filler Bar -->
            <div class="bg-light border-bottom p-3 d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-magic text-primary"></i>
                    <span class="small fw-bold text-dark">Batch Price Setter:</span>
                    <span class="small text-muted">Quickly apply default prices to all active size rows:</span>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <div class="input-group input-group-sm" style="max-width: 170px;">
                        <span class="input-group-text bg-white font-monospace">MRP ₹</span>
                        <input type="number" step="0.01" id="batchMrpInput" class="form-control font-monospace" placeholder="0.00" value="<?= number_format((float)($item['mrp'] ?? ((float)$item['unit_price'] * 2.0)), 2, '.', '') ?>">
                    </div>
                    <div class="input-group input-group-sm" style="max-width: 190px;">
                        <span class="input-group-text bg-white font-monospace text-success">Selling ₹</span>
                        <input type="number" step="0.01" id="batchSellingInput" class="form-control font-monospace fw-bold text-success" placeholder="0.00" value="<?= number_format((float)($item['selling_price'] ?? ((float)$item['unit_price'] * 1.5)), 2, '.', '') ?>">
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary fw-semibold px-3" onclick="applyBatchPrices()">
                        <i class="bi bi-check-all me-1"></i> Apply to All
                    </button>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-sm align-middle mb-0" id="tableProductVariants">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 3%;">#</th>
                                <th style="width: 13%;"><i class="bi bi-upc-scan text-primary me-1"></i> Variant SKU Code *</th>
                                <th style="width: 13%;"><i class="bi bi-tag text-primary me-1"></i> Product Variant Name *</th>
                                <th style="width: 10%; text-align: center;"><i class="bi bi-qr-code text-primary me-1"></i> Hierarchy QR Key</th>
                                <th style="width: 8%;"><i class="bi bi-palette text-primary me-1"></i> Color</th>
                                <th style="width: 5%; text-align: center;"><i class="bi bi-aspect-ratio text-primary me-1"></i> Size</th>
                                <th style="width: 6%; text-align: center;"><i class="bi bi-boxes text-primary me-1"></i> Qty *</th>
                                <th style="width: 7%; text-align: center;"><i class="bi bi-speedometer2 text-primary me-1"></i> Weight (kg)</th>
                                <th style="width: 7%; text-align: center;"><i class="bi bi-file-earmark-spreadsheet text-primary me-1"></i> HSN Code</th>
                                <th style="width: 6%; text-align: right;"><i class="bi bi-receipt text-primary me-1"></i> Cost (₹)</th>
                                <th style="width: 7%; text-align: right;"><i class="bi bi-tag-fill text-primary me-1"></i> MRP (₹) *</th>
                                <th style="width: 8%; text-align: right;"><i class="bi bi-cash text-primary me-1"></i> Selling Price (₹) *</th>
                                <th style="width: 7%; text-align: center;"><i class="bi bi-graph-up text-primary me-1"></i> Profit</th>
                            </tr>
                        </thead>
                        <tbody id="variantsTableBody">
                            <!-- Populated dynamically via renderVariantsTable() in JS -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-light py-2 px-3 d-flex justify-content-between align-items-center text-muted small">
                <span><i class="bi bi-pencil-square me-1"></i> Edit individual SKU codes, names, MRP, and selling prices directly in any row above.</span>
                <span class="font-monospace text-primary fw-semibold" id="variantsAllocationNotice">Equal allocation ready across <?= (int)$item['qty'] ?> purchased pieces.</span>
            </div>
        </div>

        <!-- Hidden Fallback Pricing Inputs for Form Submissions -->
        <input type="hidden" name="mrp" id="inputMrp" value="<?= number_format((float)($item['mrp'] ?? ((float)$item['unit_price'] * 2.0)), 2, '.', '') ?>">
        <input type="hidden" name="selling_price" id="inputSellingPrice" value="<?= number_format((float)($item['selling_price'] ?? ((float)$item['unit_price'] * 1.5)), 2, '.', '') ?>">

        <!-- ================================================================= -->
        <!-- 7. BOTTOM ACTIONS BAR                                             -->
        <!-- ================================================================= -->
        <div class="d-flex justify-content-between align-items-center p-3 bg-white rounded-3 shadow-sm border mb-4">
            <a href="<?= \App\Core\Controller::url('/production/catalog-mapping') ?>" class="btn btn-light border px-4 py-2 fw-semibold text-secondary">
                <i class="bi bi-arrow-left me-1"></i> Cancel
            </a>
            <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold shadow-sm d-inline-flex align-items-center gap-2" id="btnSaveMapping">
                <i class="bi bi-check2-circle fs-5"></i> Save & Map to Catalog
            </button>
        </div>
    </form>
</div>

<!-- MODAL: ADD TIER-1 CATEGORY -->
<div class="modal fade" id="modalAddCategory" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="<?= \App\Core\Controller::url('/production/catalog-mapping/quick-category') ?>" method="POST" id="formAddCategory" class="modal-content border-0 shadow">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
            <input type="hidden" name="tier" value="category">
            <input type="hidden" name="return_url" value="<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '') ?>">
            <div class="modal-header bg-light">
                <h6 class="modal-title fw-bold text-dark"><i class="bi bi-folder-plus text-primary me-1.5"></i> Add Main Category</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div id="alertAddCategory" class="d-none alert alert-danger py-2 px-3 small mb-3"></div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Category Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. Menswear, Kids, Maternity" required>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Short Code</label>
                        <input type="text" name="code" class="form-control font-monospace" placeholder="e.g. M, K, W" maxlength="10">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Default HSN</label>
                        <input type="text" name="default_hsn" class="form-control font-monospace" placeholder="e.g. 6109">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">GST Rate (%)</label>
                        <input type="number" step="0.01" name="default_gst_rate" class="form-control font-monospace" placeholder="e.g. 5.00">
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary btn-sm px-3" id="btnSubmitAddCategory">Save Category</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: ADD TIER-2 SUBCATEGORY -->
<div class="modal fade" id="modalAddSubcategory" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="<?= \App\Core\Controller::url('/production/catalog-mapping/quick-category') ?>" method="POST" id="formAddSubcategory" class="modal-content border-0 shadow">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
            <input type="hidden" name="tier" value="subcategory">
            <input type="hidden" name="return_url" value="<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '') ?>">
            <div class="modal-header bg-light">
                <h6 class="modal-title fw-bold text-dark"><i class="bi bi-diagram-2 text-info me-1.5"></i> Add Subcategory</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div id="alertAddSubcategory" class="d-none alert alert-danger py-2 px-3 small mb-3"></div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Parent Main Category <span class="text-danger">*</span></label>
                    <select name="category_id" id="modalSubcatParentSelect" class="form-select" required>
                        <option value="">-- Select Parent Category --</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Subcategory Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. Shirts, Baby Dress, T-Shirts" required>
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-semibold">Short Code</label>
                    <input type="text" name="code" class="form-control font-monospace" placeholder="e.g. SH, BD, TS" maxlength="10">
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-info text-white btn-sm px-3" id="btnSubmitAddSubcategory">Save Subcategory</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: ADD TIER-3 SUB-SUBCATEGORY -->
<div class="modal fade" id="modalAddSubSubcategory" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="<?= \App\Core\Controller::url('/production/catalog-mapping/quick-category') ?>" method="POST" id="formAddSubSubcategory" class="modal-content border-0 shadow">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
            <input type="hidden" name="tier" value="subsubcategory">
            <input type="hidden" name="return_url" value="<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '') ?>">
            <div class="modal-header bg-light">
                <h6 class="modal-title fw-bold text-dark"><i class="bi bi-diagram-3 text-success me-1.5"></i> Add Sub-Subcategory</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div id="alertAddSubSubcategory" class="d-none alert alert-danger py-2 px-3 small mb-3"></div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Parent Main Category <span class="text-danger">*</span></label>
                    <select name="category_id" id="modalTier3CatSelect" class="form-select" required onchange="loadSubcatsForModal(this.value)">
                        <option value="">-- Select Parent Category --</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Parent Subcategory <span class="text-danger">*</span></label>
                    <select name="subcategory_id" id="modalTier3SubcatSelect" class="form-select" required>
                        <option value="">-- Select Subcategory First --</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Sub-Subcategory Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. Formal, Sleeveless, Printed Frock" required>
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-semibold">Short Code</label>
                    <input type="text" name="code" class="form-control font-monospace" placeholder="e.g. F, SL, PF" maxlength="10">
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success btn-sm px-3" id="btnSubmitAddSubSubcategory">Save Tier-3</button>
            </div>
        </form>
    </div>
</div>

<script>
let currentCategoryDefaultHsn = '6109';
let currentCategoryDefaultGst = 5.00;
const supplierUnitCost = <?= (float)($item['unit_price'] ?? 0) ?>;
const purchasedBatchQty = <?= (float)($item['qty'] ?? 0) ?>;

function escapeHtml(text) {
    if (!text) return '';
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function toggleMappingMode(mode) {
    const contExisting = document.getElementById('containerExistingStyle');
    const contentCat = document.getElementById('cardCategoryHierarchyContent');
    const selectCat = document.getElementById('selectExistingCatalog');

    if (mode === 'existing_style') {
        contExisting.classList.remove('d-none');
        contentCat.classList.add('d-none');
        selectCat.setAttribute('required', 'required');
    } else {
        contExisting.classList.add('d-none');
        contentCat.classList.remove('d-none');
        selectCat.removeAttribute('required');
    }
}

function handleMainCategoryChange(catId, preselectSubId = null, preselectSubSubId = null) {
    const subSelect = document.getElementById('selectSubCategory');
    const subSubSelect = document.getElementById('selectSubSubCategory');
    const mainCatSelect = document.getElementById('selectMainCategory');
    const selectedOption = mainCatSelect.options[mainCatSelect.selectedIndex];

    subSelect.innerHTML = '<option value="">-- Loading... --</option>';
    subSubSelect.innerHTML = '<option value="">-- Select Sub Category First --</option>';

    if (!catId) {
        subSelect.innerHTML = '<option value="">-- Select Category First --</option>';
        document.getElementById('inputMainCatCode').value = '';
        updateHierarchyPath();
        updateWholeCode();
        return;
    }

    // Set code and HSN
    const code = selectedOption ? (selectedOption.dataset.code || '') : '';
    document.getElementById('inputMainCatCode').value = code;

    currentCategoryDefaultHsn = (selectedOption && selectedOption.dataset.hsn) || '6109';
    currentCategoryDefaultGst = parseFloat((selectedOption && selectedOption.dataset.gst) || 5.00);
    const catHsnText = document.getElementById('categoryDefaultHsnText');
    if (catHsnText) {
        catHsnText.textContent = currentCategoryDefaultHsn;
    }
    const catGstText = document.getElementById('categoryDefaultGstText');
    if (catGstText) {
        catGstText.textContent = currentCategoryDefaultGst.toFixed(2) + '%';
    }

    updateHierarchyPath();
    updateWholeCode();

    fetch('<?= \App\Core\Controller::url('/api/categories/subcategories?category_id=') ?>' + encodeURIComponent(catId))
        .then(r => r.json())
        .then(data => {
            const items = Array.isArray(data) ? data : (data.data || []);
            if (items.length === 0) {
                subSelect.innerHTML = '<option value="">-- No Subcategories Found --</option>';
            } else {
                subSelect.innerHTML = '<option value="">-- Select Sub Category --</option>';
                items.forEach(item => {
                    const isSelected = preselectSubId && (String(item.id) === String(preselectSubId)) ? 'selected' : '';
                    subSelect.innerHTML += `<option value="${item.id}" data-code="${item.code || ''}" ${isSelected}>${item.name}</option>`;
                });
            }
            if (preselectSubId) {
                subSelect.value = preselectSubId;
                handleSubCategoryChange(preselectSubId, preselectSubSubId);
            } else {
                updateHierarchyPath();
                updateWholeCode();
            }
        })
        .catch(err => {
            console.error('Failed to load subcategories:', err);
            subSelect.innerHTML = '<option value="">-- Error Loading Subcategories --</option>';
        });
}

function handleSubCategoryChange(subId, preselectSubSubId = null) {
    const subSelect = document.getElementById('selectSubCategory');
    const subSubSelect = document.getElementById('selectSubSubCategory');
    const selectedOption = subSelect.options[subSelect.selectedIndex];

    subSubSelect.innerHTML = '<option value="">-- Loading... --</option>';

    if (!subId) {
        subSubSelect.innerHTML = '<option value="">-- Select Sub Category First --</option>';
        document.getElementById('inputSubCatCode').value = '';
        updateHierarchyPath();
        updateWholeCode();
        return;
    }

    const code = selectedOption ? (selectedOption.dataset.code || '') : '';
    document.getElementById('inputSubCatCode').value = code;

    updateHierarchyPath();
    updateWholeCode();

    fetch('<?= \App\Core\Controller::url('/api/categories/subsubcategories?subcategory_id=') ?>' + encodeURIComponent(subId))
        .then(r => r.json())
        .then(data => {
            const items = Array.isArray(data) ? data : (data.data || []);
            if (items.length === 0) {
                subSubSelect.innerHTML = '<option value="">-- No Sub-Subcategories Found --</option>';
            } else {
                subSubSelect.innerHTML = '<option value="">-- Select Sub Sub Category --</option>';
                items.forEach(item => {
                    const isSelected = preselectSubSubId && (String(item.id) === String(preselectSubSubId)) ? 'selected' : '';
                    subSubSelect.innerHTML += `<option value="${item.id}" data-code="${item.code || ''}" ${isSelected}>${item.name}</option>`;
                });
            }
            if (preselectSubSubId) {
                subSubSelect.value = preselectSubSubId;
                handleSubSubCategoryChange(preselectSubSubId);
            } else {
                updateHierarchyPath();
                updateWholeCode();
            }
        })
        .catch(err => {
            console.error('Failed to load sub-subcategories:', err);
            subSubSelect.innerHTML = '<option value="">-- Error Loading Sub-Subcategories --</option>';
        });
}

function handleSubSubCategoryChange(subSubId) {
    const subSubSelect = document.getElementById('selectSubSubCategory');
    const selectedOption = subSubSelect.options[subSubSelect.selectedIndex];
    const code = selectedOption ? (selectedOption.dataset.code || '') : '';
    document.getElementById('inputSubSubCatCode').value = code;

    updateHierarchyPath();
    updateWholeCode();
}

function updateHierarchyPath() {
    const catSelect = document.getElementById('selectMainCategory');
    const subSelect = document.getElementById('selectSubCategory');
    const subSubSelect = document.getElementById('selectSubSubCategory');

    const catName = catSelect && catSelect.selectedIndex > 0 ? catSelect.options[catSelect.selectedIndex].text : '';
    const subName = subSelect && subSelect.selectedIndex > 0 ? subSelect.options[subSelect.selectedIndex].text : '';
    const subSubName = subSubSelect && subSubSelect.selectedIndex > 0 ? subSubSelect.options[subSubSelect.selectedIndex].text : '';

    const parts = [catName, subName, subSubName].filter(Boolean);
    const path = parts.length > 0 ? parts.join(' > ') : 'Select a category above...';

    const elem = document.getElementById('hierarchyPathText');
    if (elem) elem.textContent = path;

    const summaryElem = document.getElementById('summaryHierarchyText');
    if (summaryElem) summaryElem.textContent = path;

    // Update all variant rows in the table with the new hierarchy path
    document.querySelectorAll('.variant-hierarchy-text').forEach(el => {
        el.textContent = path;
        el.title = path;
    });
}

function updateWholeCode() {
    const c1 = (document.getElementById('inputMainCatCode')?.value || '').trim();
    const c2 = (document.getElementById('inputSubCatCode')?.value || '').trim();
    const c3 = (document.getElementById('inputSubSubCatCode')?.value || '').trim();
    const wholeCode = (c1 + c2 + c3).toUpperCase();

    const whole = document.getElementById('inputWholeCode');
    if (whole) whole.value = wholeCode;

    const summaryWhole = document.getElementById('summaryWholeCode');
    if (summaryWhole) summaryWhole.textContent = wholeCode || 'WMF';

    // Update all variant QR key badges in the matrix table
    document.querySelectorAll('.variant-qr-key-badge').forEach(badge => {
        badge.textContent = wholeCode || '-';
    });
}

function applyCategoryHsn() {
    document.getElementById('inputHsnCode').value = currentCategoryDefaultHsn;
    document.getElementById('inputGstRate').value = currentCategoryDefaultGst.toFixed(2);
    renderVariantsTable();
}

function handleSizeCheckboxChange(checkbox) {
    const label = checkbox.closest('.size-pill-label');
    if (!label) return;
    if (checkbox.checked) {
        label.classList.add('border-primary', 'bg-primary-subtle');
        label.classList.remove('bg-light');
    } else {
        label.classList.remove('border-primary', 'bg-primary-subtle');
        label.classList.add('bg-light');
    }
    updateSelectedSizesCount();
    renderVariantsTable();
}

function selectAllSizes() {
    document.querySelectorAll('.size-checkbox').forEach(cb => {
        cb.checked = true;
        handleSizeCheckboxChange(cb);
    });
}

function clearAllSizes() {
    document.querySelectorAll('.size-checkbox').forEach(cb => {
        cb.checked = false;
        handleSizeCheckboxChange(cb);
    });
}

function updateSelectedSizesCount() {
    const checkedBoxes = document.querySelectorAll('.size-checkbox:checked');
    const count = checkedBoxes.length;

    const elem = document.getElementById('summarySelectedSizesCount');
    if (elem) elem.textContent = count;

    const badge = document.getElementById('variantsCountBadge');
    if (badge) badge.textContent = `${count} Variants`;

    const attrBadge = document.getElementById('attrSizeCountBadge');
    if (attrBadge) attrBadge.textContent = `${count} Sizes Active`;

    const sizeValues = Array.from(checkedBoxes).map(cb => cb.value);
    const summaryList = document.getElementById('summarySizesList');
    if (summaryList) {
        summaryList.textContent = sizeValues.length > 0 ? sizeValues.join(', ') : 'None selected';
    }
}

function addCustomSizePill() {
    const input = document.getElementById('inputCustomSize');
    const val = input.value.trim().toUpperCase();
    if (!val) return;

    let exists = false;
    document.querySelectorAll('.size-checkbox').forEach(cb => {
        if (cb.value.toUpperCase() === val) exists = true;
    });

    if (exists) {
        alert(`Size "${val}" is already present.`);
        input.value = '';
        return;
    }

    const container = document.getElementById('sizesContainer');
    const html = `
        <label class="size-pill-label border border-primary bg-primary-subtle rounded-3 p-2 d-inline-flex align-items-center gap-2 cursor-pointer">
            <input type="checkbox" name="sizes[]" value="${val}" class="form-check-input size-checkbox mt-0" checked onchange="handleSizeCheckboxChange(this)">
            <span class="fw-bold font-monospace size-text text-dark">${val}</span>
        </label>
    `;
    container.insertAdjacentHTML('beforeend', html);
    input.value = '';
    updateSelectedSizesCount();
    renderVariantsTable();
}

function updatePricingMargin() {
    const sp = parseFloat(document.getElementById('inputSellingPrice')?.value || 0);
    const mrp = parseFloat(document.getElementById('inputMrp')?.value || 0);
    const textEl = document.getElementById('pricingMarginText');

    const mrpMarkup = supplierUnitCost > 0 && mrp > 0 ? (((mrp - supplierUnitCost) / supplierUnitCost) * 100).toFixed(1) : '0.0';
    const sellingMarkup = supplierUnitCost > 0 && sp > 0 ? (((sp - supplierUnitCost) / supplierUnitCost) * 100).toFixed(1) : '0.0';
    const profitPerUnit = sp > 0 && supplierUnitCost > 0 ? (sp - supplierUnitCost) : 0;
    const profitMarginPct = sp > 0 ? ((profitPerUnit / sp) * 100).toFixed(1) : '0.0';

    const costTotal = (supplierUnitCost * purchasedBatchQty);
    const mrpTotal = (mrp * purchasedBatchQty);
    const sellingTotal = (sp * purchasedBatchQty);
    const batchProfit = (profitPerUnit * purchasedBatchQty);

    // Update Table Elements
    const elCostTot = document.getElementById('tableCostTotal');
    if (elCostTot) elCostTot.textContent = '₹' + costTotal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    const elMrpMarkup = document.getElementById('tableMrpMarkup');
    if (elMrpMarkup) elMrpMarkup.textContent = `+${mrpMarkup}% vs Cost`;

    const elMrpTot = document.getElementById('tableMrpTotal');
    if (elMrpTot) elMrpTot.textContent = '₹' + mrpTotal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    const elSellMargin = document.getElementById('tableSellingMargin');
    if (elSellMargin) elSellMargin.textContent = `+${sellingMarkup}% (+₹${profitPerUnit.toFixed(2)})`;

    const elSellTot = document.getElementById('tableSellingTotal');
    if (elSellTot) elSellTot.textContent = '₹' + sellingTotal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    const elProfitUnit = document.getElementById('tableProfitPerUnit');
    if (elProfitUnit) elProfitUnit.textContent = `${profitPerUnit >= 0 ? '+' : ''}₹${profitPerUnit.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

    const elProfitMargin = document.getElementById('tableProfitMarginPct');
    if (elProfitMargin) elProfitMargin.textContent = `${profitMarginPct}% Profit Margin`;

    const elBatchProfit = document.getElementById('tableBatchProfit');
    if (elBatchProfit) elBatchProfit.textContent = `${batchProfit >= 0 ? '+' : ''}₹${batchProfit.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

    if (textEl) {
        if (supplierUnitCost > 0 && sp > 0) {
            textEl.innerHTML = `Markup over cost: <strong class="${profitPerUnit >= 0 ? 'text-success' : 'text-danger'} font-monospace">${profitPerUnit >= 0 ? '+' : ''}${sellingMarkup}%</strong> (+₹${profitPerUnit.toFixed(2)}/pc)`;
        } else {
            textEl.textContent = 'Projected markup over purchase cost.';
        }
    }

    // Update Summary Section Elements
    const sumMrp = document.getElementById('summaryMrp');
    if (sumMrp) sumMrp.textContent = '₹' + mrp.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    const sumSp = document.getElementById('summarySellingPrice');
    if (sumSp) sumSp.textContent = '₹' + sp.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    const sumMargin = document.getElementById('summaryMargin');
    if (sumMargin) {
        sumMargin.textContent = `${profitPerUnit >= 0 ? '+' : ''}₹${profitPerUnit.toFixed(2)} (${sellingMarkup}%)`;
        sumMargin.className = profitPerUnit >= 0 ? 'badge bg-success font-monospace' : 'badge bg-danger font-monospace';
    }
}

// Render dynamic editable variants table based on current style code, name, color, prices & selected sizes
function renderVariantsTable() {
    const tbody = document.getElementById('variantsTableBody');
    if (!tbody) return;

    const styleCode = (document.getElementById('inputStyleCode')?.value || 'GRM-STY-0001').trim();
    const styleName = (document.getElementById('inputStyleName')?.value || 'Product Style').trim();
    const defaultColor = (document.getElementById('inputColor')?.value || '').trim() || 'Standard';
    const defaultHsn = (document.getElementById('inputHsnCode')?.value || '6109').trim();

    // Current category hierarchy path
    const catSelect = document.getElementById('selectMainCategory');
    const subSelect = document.getElementById('selectSubCategory');
    const subSubSelect = document.getElementById('selectSubSubCategory');

    const catName = catSelect && catSelect.selectedIndex > 0 ? catSelect.options[catSelect.selectedIndex].text : '';
    const subName = subSelect && subSelect.selectedIndex > 0 ? subSelect.options[subSelect.selectedIndex].text : '';
    const subSubName = subSubSelect && subSubSelect.selectedIndex > 0 ? subSubSelect.options[subSubSelect.selectedIndex].text : '';
    const parts = [catName, subName, subSubName].filter(Boolean);
    const hierarchyPath = parts.length > 0 ? parts.join(' > ') : (document.getElementById('hierarchyPathText')?.textContent || 'Apparel & Garments');
    const qrKey = (document.getElementById('inputWholeCode')?.value || '').trim() || '-';
    const defaultWeight = parseFloat('<?= (float)($item['net_weight'] ?? 0.0) ?>') || 0.0;

    const batchMrp = parseFloat(document.getElementById('batchMrpInput')?.value || document.getElementById('inputMrp')?.value || 0.0);
    const batchSp = parseFloat(document.getElementById('batchSellingInput')?.value || document.getElementById('inputSellingPrice')?.value || 0.0);

    const checkedBoxes = Array.from(document.querySelectorAll('.size-checkbox:checked'));
    const checkedSizes = checkedBoxes.map(cb => cb.value.trim());

    if (checkedSizes.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="13" class="text-center py-5 text-muted">
                    <div class="py-3">
                        <i class="bi bi-tag text-secondary fs-1 d-block mb-2"></i>
                        <h6 class="fw-bold text-dark">No Size Attributes Selected</h6>
                        <p class="small text-muted mb-0">Please check one or more Size Attributes above (e.g. S, M, L, XL) to generate editable variant rows.</p>
                    </div>
                </td>
            </tr>
        `;
        return;
    }

    const totalPurchasedQty = parseFloat(purchasedBatchQty) || 1;
    const equalQty = Math.max(1, Math.round(totalPurchasedQty / checkedSizes.length));

    let rowsHtml = '';
    checkedSizes.forEach((size, idx) => {
        const curSkuInput = document.querySelector(`input[name="variant_skus[${size}]"]`);
        const curNameInput = document.querySelector(`input[name="variant_names[${size}]"]`);
        const curColorInput = document.querySelector(`input[name="variant_colors[${size}]"]`);
        const curQtyInput = document.querySelector(`input[name="variant_qtys[${size}]"]`);
        const curWeightInput = document.querySelector(`input[name="variant_weights[${size}]"]`);
        const curHsnInput = document.querySelector(`input[name="variant_hsns[${size}]"]`);
        const curMrpInput = document.querySelector(`input[name="variant_mrps[${size}]"]`);
        const curSpInput = document.querySelector(`input[name="variant_selling_prices[${size}]"]`);

        const sku = curSkuInput ? curSkuInput.value : `${styleCode}-${size}`;
        const varName = curNameInput ? curNameInput.value : `${styleName} - ${size}`;
        const varColor = curColorInput ? curColorInput.value : defaultColor;
        const varQty = curQtyInput ? curQtyInput.value : equalQty;
        const varWeight = curWeightInput ? curWeightInput.value : (defaultWeight > 0 ? defaultWeight.toFixed(3) : '0.000');
        const varHsn = curHsnInput ? curHsnInput.value : defaultHsn;
        const mrp = curMrpInput && curMrpInput.value !== '' ? parseFloat(curMrpInput.value) : batchMrp;
        const sp = curSpInput && curSpInput.value !== '' ? parseFloat(curSpInput.value) : batchSp;

        const unitProfit = sp - supplierUnitCost;
        const profitMargin = sp > 0 ? ((unitProfit / sp) * 100) : 0;
        const profitBadgeClass = unitProfit >= 0 ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle';

        rowsHtml += `
            <tr id="variant_row_${escapeHtml(size)}">
                <td class="text-muted small">${idx + 1}</td>
                <td>
                    <input type="text" name="variant_skus[${escapeHtml(size)}]" class="form-control form-control-sm font-monospace fw-bold text-primary variant-sku-input" value="${escapeHtml(sku)}" required>
                </td>
                <td>
                    <input type="text" name="variant_names[${escapeHtml(size)}]" class="form-control form-control-sm fw-semibold text-dark variant-name-input" value="${escapeHtml(varName)}" required>
                </td>
                <td class="text-center">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace fw-bold variant-qr-key-badge px-2 py-1" style="font-size: 0.82rem;" title="Hierarchy QR Key: ${escapeHtml(hierarchyPath)}">
                        ${escapeHtml(qrKey)}
                    </span>
                </td>
                <td>
                    <input type="text" name="variant_colors[${escapeHtml(size)}]" class="form-control form-control-sm text-dark variant-color-input" value="${escapeHtml(varColor)}" placeholder="Color" required>
                </td>
                <td class="text-center">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace fs-6 px-2.5 py-1">${escapeHtml(size)}</span>
                </td>
                <td>
                    <input type="number" name="variant_qtys[${escapeHtml(size)}]" class="form-control form-control-sm font-monospace text-center fw-bold variant-qty-input" value="${escapeHtml(String(varQty))}" min="1" required oninput="updateAllocatedQtySummary()">
                </td>
                <td>
                    <input type="number" step="0.001" min="0" name="variant_weights[${escapeHtml(size)}]" class="form-control form-control-sm font-monospace text-center variant-weight-input" value="${escapeHtml(String(varWeight))}" placeholder="0.000">
                </td>
                <td>
                    <input type="text" name="variant_hsns[${escapeHtml(size)}]" class="form-control form-control-sm font-monospace text-center variant-hsn-input" value="${escapeHtml(varHsn)}" required>
                </td>
                <td class="text-end font-monospace text-dark">
                    ₹${supplierUnitCost.toFixed(2)}
                </td>
                <td class="text-end">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text font-monospace p-1">₹</span>
                        <input type="number" step="0.01" name="variant_mrps[${escapeHtml(size)}]" class="form-control form-control-sm font-monospace text-end fw-semibold variant-mrp-input" value="${mrp.toFixed(2)}" required oninput="updateVariantRowProfit('${escapeHtml(size)}')">
                    </div>
                </td>
                <td class="text-end">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text font-monospace p-1 text-success fw-bold">₹</span>
                        <input type="number" step="0.01" name="variant_selling_prices[${escapeHtml(size)}]" class="form-control form-control-sm font-monospace text-end fw-bold text-success variant-sp-input" value="${sp.toFixed(2)}" required oninput="updateVariantRowProfit('${escapeHtml(size)}')">
                    </div>
                </td>
                <td class="text-center">
                    <span id="profitBadge_${escapeHtml(size)}" class="badge ${profitBadgeClass} font-monospace fs-7 px-2 py-1">
                        ${unitProfit >= 0 ? '+' : ''}₹${unitProfit.toFixed(2)} (${profitMargin.toFixed(1)}%)
                    </span>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = rowsHtml;
    updateAllocatedQtySummary();
}

function updateAllocatedQtySummary() {
    let totalAlloc = 0;
    document.querySelectorAll('.variant-qty-input').forEach(inp => {
        totalAlloc += parseInt(inp.value) || 0;
    });
    const notice = document.getElementById('variantsAllocationNotice');
    if (notice) {
        notice.textContent = `Total allocated: ${totalAlloc} / ${purchasedBatchQty} purchased pieces.`;
    }
}

function updateVariantRowProfit(size) {
    const spInput = document.querySelector(`input[name="variant_selling_prices[${size}]"]`);
    const badge = document.getElementById(`profitBadge_${size}`);
    if (!spInput || !badge) return;

    const sp = parseFloat(spInput.value) || 0.0;
    const profit = sp - supplierUnitCost;
    const margin = sp > 0 ? ((profit / sp) * 100) : 0;

    badge.className = profit >= 0 
        ? 'badge bg-success-subtle text-success border border-success-subtle font-monospace fs-7 px-2 py-1' 
        : 'badge bg-danger-subtle text-danger border border-danger-subtle font-monospace fs-7 px-2 py-1';
    badge.textContent = `${profit >= 0 ? '+' : ''}₹${profit.toFixed(2)} (${margin.toFixed(1)}%)`;

    // Sync primary hidden inputs
    const firstMrpInput = document.querySelector('.variant-mrp-input');
    const firstSpInput = document.querySelector('.variant-sp-input');
    if (firstMrpInput && document.getElementById('inputMrp')) {
        document.getElementById('inputMrp').value = firstMrpInput.value;
    }
    if (firstSpInput && document.getElementById('inputSellingPrice')) {
        document.getElementById('inputSellingPrice').value = firstSpInput.value;
    }
}

function applyBatchPrices() {
    const batchMrp = parseFloat(document.getElementById('batchMrpInput')?.value || 0.0);
    const batchSp = parseFloat(document.getElementById('batchSellingInput')?.value || 0.0);

    document.querySelectorAll('.variant-mrp-input').forEach(inp => {
        inp.value = batchMrp.toFixed(2);
    });
    document.querySelectorAll('.variant-sp-input').forEach(inp => {
        inp.value = batchSp.toFixed(2);
    });

    document.querySelectorAll('.size-checkbox:checked').forEach(cb => {
        updateVariantRowProfit(cb.value);
    });

    if (document.getElementById('inputMrp')) document.getElementById('inputMrp').value = batchMrp.toFixed(2);
    if (document.getElementById('inputSellingPrice')) document.getElementById('inputSellingPrice').value = batchSp.toFixed(2);
}

function loadSubcatsForModal(catId, preselectSubcatId = null) {
    const subSelect = document.getElementById('modalTier3SubcatSelect');
    if (!subSelect) return;
    subSelect.innerHTML = '<option value="">-- Loading... --</option>';
    if (!catId) {
        subSelect.innerHTML = '<option value="">-- Select Category First --</option>';
        return;
    }

    fetch('<?= \App\Core\Controller::url('/api/categories/subcategories?category_id=') ?>' + encodeURIComponent(catId))
        .then(r => r.json())
        .then(data => {
            const items = Array.isArray(data) ? data : (data.data || []);
            if (items.length === 0) {
                subSelect.innerHTML = '<option value="">-- No Subcategories Available --</option>';
            } else {
                subSelect.innerHTML = '<option value="">-- Select Subcategory --</option>';
                items.forEach(item => {
                    const isSelected = preselectSubcatId && (String(item.id) === String(preselectSubcatId)) ? 'selected' : '';
                    subSelect.innerHTML += `<option value="${item.id}" ${isSelected}>${item.name}</option>`;
                });
            }
            if (preselectSubcatId) {
                subSelect.value = preselectSubcatId;
            }
        })
        .catch(err => {
            console.error('Failed to load subcategories for modal:', err);
            subSelect.innerHTML = '<option value="">-- Error Loading --</option>';
        });
}

// AJAX Quick Category Modal Submission
function handleQuickCategorySubmit(e, formId, modalId, tier) {
    e.preventDefault();
    const form = document.getElementById(formId);
    const alertBox = form.querySelector('.alert-danger');
    if (alertBox) alertBox.classList.add('d-none');

    const submitBtn = form.querySelector('button[type="submit"]');
    const origBtnHtml = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';

    const formData = new FormData(form);
    formData.append('ajax', '1');

    fetch(form.action, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = origBtnHtml;

        if (res.success && res.item) {
            const item = res.item;
            const modalEl = document.getElementById(modalId);
            const modalInstance = bootstrap.Modal.getInstance(modalEl);
            if (modalInstance) {
                modalInstance.hide();
            }
            form.reset();

            if (tier === 'category') {
                const select = document.getElementById('selectMainCategory');
                const opt = document.createElement('option');
                opt.value = item.id;
                opt.textContent = item.name;
                opt.dataset.code = item.code || '';
                opt.dataset.hsn = item.default_hsn || '6109';
                opt.dataset.gst = item.default_gst_rate || 5.00;
                select.appendChild(opt);
                select.value = item.id;

                // Sync with modal selects
                ['modalSubcatParentSelect', 'modalTier3CatSelect'].forEach(id => {
                    const s = document.getElementById(id);
                    if (s) {
                        const o = document.createElement('option');
                        o.value = item.id;
                        o.textContent = item.name;
                        s.appendChild(o);
                    }
                });

                handleMainCategoryChange(item.id);
            } else if (tier === 'subcategory') {
                const select = document.getElementById('selectSubCategory');
                if (select.querySelector('option[value=""]')) {
                    select.querySelector('option[value=""]').textContent = '-- Select Sub Category --';
                }
                const opt = document.createElement('option');
                opt.value = item.id;
                opt.textContent = item.name;
                opt.dataset.code = item.code || '';
                select.appendChild(opt);
                select.value = item.id;

                // Sync with modalTier3SubcatSelect
                const modalTier3Sub = document.getElementById('modalTier3SubcatSelect');
                if (modalTier3Sub) {
                    const o = document.createElement('option');
                    o.value = item.id;
                    o.textContent = item.name;
                    modalTier3Sub.appendChild(o);
                }

                handleSubCategoryChange(item.id);
            } else if (tier === 'subsubcategory') {
                const select = document.getElementById('selectSubSubCategory');
                const opt = document.createElement('option');
                opt.value = item.id;
                opt.textContent = item.name;
                opt.dataset.code = item.code || '';
                select.appendChild(opt);
                select.value = item.id;

                handleSubSubCategoryChange(item.id);
            }
        } else {
            if (alertBox) {
                alertBox.textContent = res.message || 'Failed to save category.';
                alertBox.classList.remove('d-none');
            } else {
                alert(res.message || 'Failed to save category.');
            }
        }
    })
    .catch(err => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = origBtnHtml;
        console.error('AJAX Quick category error:', err);
        if (alertBox) {
            alertBox.textContent = 'Server connection error. Please try again.';
            alertBox.classList.remove('d-none');
        } else {
            alert('Server connection error. Please try again.');
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    // Check initial sizes
    document.querySelectorAll('.size-checkbox').forEach(cb => {
        handleSizeCheckboxChange(cb);
    });
    updateSelectedSizesCount();
    updatePricingMargin();
    renderVariantsTable();

    // Modal show event listeners to pre-populate parent categories
    const modalSubcat = document.getElementById('modalAddSubcategory');
    if (modalSubcat) {
        modalSubcat.addEventListener('show.bs.modal', function () {
            const currentCatId = document.getElementById('selectMainCategory').value;
            const parentSel = document.getElementById('modalSubcatParentSelect');
            if (currentCatId && parentSel) {
                parentSel.value = currentCatId;
            }
            const alertBox = modalSubcat.querySelector('.alert-danger');
            if (alertBox) alertBox.classList.add('d-none');
        });
    }

    const modalSubSub = document.getElementById('modalAddSubSubcategory');
    if (modalSubSub) {
        modalSubSub.addEventListener('show.bs.modal', function () {
            const currentCatId = document.getElementById('selectMainCategory').value;
            const currentSubId = document.getElementById('selectSubCategory').value;
            const parentCatSel = document.getElementById('modalTier3CatSelect');
            if (currentCatId && parentCatSel) {
                parentCatSel.value = currentCatId;
                loadSubcatsForModal(currentCatId, currentSubId);
            }
            const alertBox = modalSubSub.querySelector('.alert-danger');
            if (alertBox) alertBox.classList.add('d-none');
        });
    }

    // Modal AJAX form submit bindings
    const formAddCat = document.getElementById('formAddCategory');
    if (formAddCat) {
        formAddCat.addEventListener('submit', e => handleQuickCategorySubmit(e, 'formAddCategory', 'modalAddCategory', 'category'));
    }

    const formAddSub = document.getElementById('formAddSubcategory');
    if (formAddSub) {
        formAddSub.addEventListener('submit', e => handleQuickCategorySubmit(e, 'formAddSubcategory', 'modalAddSubcategory', 'subcategory'));
    }

    const formAddSubSub = document.getElementById('formAddSubSubcategory');
    if (formAddSubSub) {
        formAddSubSub.addEventListener('submit', e => handleQuickCategorySubmit(e, 'formAddSubSubcategory', 'modalAddSubSubcategory', 'subsubcategory'));
    }

    // Main form client validation
    const mainForm = document.getElementById('formCatalogMapping');
    if (mainForm) {
        mainForm.addEventListener('submit', function (e) {
            const mode = document.querySelector('input[name="mapping_mode"]:checked')?.value || 'new_style';
            if (mode === 'new_style') {
                const cat = document.getElementById('selectMainCategory');
                if (!cat || !cat.value) {
                    e.preventDefault();
                    alert('Please select a Main Category.');
                    cat.focus();
                    return false;
                }
                const styleCode = document.getElementById('inputStyleCode');
                if (!styleCode || !styleCode.value.trim()) {
                    e.preventDefault();
                    alert('Product Style Code is required.');
                    styleCode.focus();
                    return false;
                }
                const styleName = document.getElementById('inputStyleName');
                if (!styleName || !styleName.value.trim()) {
                    e.preventDefault();
                    alert('Product Style Name is required.');
                    styleName.focus();
                    return false;
                }
                const checkedSizes = document.querySelectorAll('.size-checkbox:checked');
                if (checkedSizes.length === 0) {
                    e.preventDefault();
                    alert('Please select at least one Size Attribute.');
                    return false;
                }
            } else {
                const existCat = document.getElementById('selectExistingCatalog');
                if (!existCat || !existCat.value) {
                    e.preventDefault();
                    alert('Please select an existing catalog style to link.');
                    existCat.focus();
                    return false;
                }
            }
        });
    }

    // Trigger initial category load if pre-selected
    const initCatVal = document.getElementById('selectMainCategory')?.value;
    if (initCatVal) {
        handleMainCategoryChange(initCatVal, '<?= (int)($item['mapped_subcat_id'] ?? 0) ?>', '<?= (int)($item['mapped_subsub_id'] ?? 0) ?>');
    }
});
</script>
