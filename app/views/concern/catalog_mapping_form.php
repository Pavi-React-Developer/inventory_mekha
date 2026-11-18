<?php
// app/views/concern/catalog_mapping_form.php - Map Finished Good Item to 3-Tier Hierarchy & Attributes
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
                <p class="text-secondary small mb-0">Define 3-tier category hierarchy, attributes (sizes, colors), and catalog details for purchased product <strong><?= htmlspecialchars($item['item_name']) ?></strong>.</p>
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

    <!-- Mapping Form -->
    <form action="<?= \App\Core\Controller::url('/production/catalog-mapping/save') ?>" method="POST" id="formCatalogMapping" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
        <input type="hidden" name="purchase_bill_item_id" value="<?= (int)$item['id'] ?>">

        <div class="row g-4">
            <!-- ========================================== -->
            <!-- LEFT COLUMN: BASELINE & CATEGORY HIERARCHY -->
            <!-- ========================================== -->
            <div class="col-lg-7">
                
                <!-- 1. PURCHASED PRODUCT BASELINE (READ-ONLY REFERENCE) -->
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-receipt-cutoff text-primary"></i>
                            <h6 class="mb-0 fw-bold text-dark">1. Purchased Product Baseline</h6>
                        </div>
                        <span class="badge bg-light text-dark border font-monospace">Purchase Bill #<?= htmlspecialchars($item['bill_no']) ?></span>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="small text-muted mb-1">Product Name on Bill</div>
                                <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($item['item_name']) ?></div>
                                <?php if (!empty($item['item_code'])): ?>
                                    <span class="badge bg-light text-dark border font-monospace mt-1">Item Code: <?= htmlspecialchars($item['item_code']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <div class="small text-muted mb-1">Supplier & Purchase Date</div>
                                <div class="fw-semibold text-dark"><i class="bi bi-building me-1 text-muted"></i><?= htmlspecialchars($item['supplier_name'] ?? 'Supplier') ?></div>
                                <div class="small text-muted"><i class="bi bi-calendar-event me-1"></i><?= date('d M Y', strtotime($item['bill_date'])) ?></div>
                            </div>
                            <div class="col-md-4">
                                <div class="small text-muted mb-1">Purchased Quantity</div>
                                <div class="fs-5 fw-bold text-primary font-monospace"><?= number_format((float)$item['qty']) ?> <span class="fs-6 fw-normal text-muted"><?= htmlspecialchars($item['unit'] ?: 'Pcs') ?></span></div>
                            </div>
                            <div class="col-md-4">
                                <div class="small text-muted mb-1">Supplier Cost</div>
                                <div class="fs-5 fw-bold text-dark font-monospace">₹<?= number_format((float)$item['unit_price'], 2) ?></div>
                            </div>
                            <div class="col-md-4">
                                <div class="small text-muted mb-1">Bill HSN & GST</div>
                                <div class="fw-semibold font-monospace text-secondary"><?= htmlspecialchars($item['hsn_code'] ?: '6109') ?> &bull; <?= number_format((float)($item['gst_rate'] ?? 5.00), 2) ?>% GST</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- MAPPING MODE SELECTOR -->
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-body p-3">
                        <label class="form-label small fw-semibold text-dark mb-2">Select Mapping Action:</label>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <div class="form-check p-3 border rounded-3 bg-light">
                                    <input class="form-check-input" type="radio" name="mapping_mode" id="modeNewStyle" value="new_style" checked onchange="toggleMappingMode('new_style')">
                                    <label class="form-check-label fw-bold text-dark d-block" for="modeNewStyle">
                                        <i class="bi bi-plus-circle text-primary me-1"></i> Create New Catalog Style
                                        <small class="text-secondary fw-normal d-block mt-1">Configure fresh 3-tier hierarchy, style code, and size variants.</small>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check p-3 border rounded-3 bg-light">
                                    <input class="form-check-input" type="radio" name="mapping_mode" id="modeExistingStyle" value="existing_style" onchange="toggleMappingMode('existing_style')">
                                    <label class="form-check-label fw-bold text-dark d-block" for="modeExistingStyle">
                                        <i class="bi bi-link-45deg text-success me-1"></i> Link to Existing Catalog Style
                                        <small class="text-secondary fw-normal d-block mt-1">Attach to an already created style in the production catalog.</small>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Existing Style Dropdown (Hidden by default) -->
                        <div id="containerExistingStyle" class="mt-3 d-none">
                            <label class="form-label small fw-semibold text-dark">Select Existing Catalog Style <span class="text-danger">*</span></label>
                            <select name="existing_catalog_id" id="selectExistingCatalog" class="form-select">
                                <option value="">-- Choose Existing Style --</option>
                                <?php foreach ($catalogs as $c): ?>
                                    <option value="<?= $c['id'] ?>">
                                        [<?= htmlspecialchars($c['style_code']) ?>] <?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['hierarchy_path'] ?: 'General') ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- 2. CATEGORY HIERARCHY (3-TIER) MATCHING IMAGE 1 -->
                <div class="card border-0 shadow-sm rounded-3 mb-4" id="cardCategoryHierarchy">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-diagram-3-fill text-primary"></i>
                            <h6 class="mb-0 fw-bold text-dark">2. Category Hierarchy (3-Tier)</h6>
                        </div>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Required</span>
                    </div>
                    <div class="card-body p-4">
                        
                        <!-- 3 Dropdowns Row -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label small fw-semibold text-dark mb-0">Main Category <span class="text-danger">*</span></label>
                                    <button type="button" class="btn btn-link btn-sm p-0 text-primary text-decoration-none small" data-bs-toggle="modal" data-bs-target="#modalAddCategory">
                                        <i class="bi bi-plus-circle"></i> New
                                    </button>
                                </div>
                                <select name="category_id" id="selectMainCategory" class="form-select form-select-sm" required onchange="handleMainCategoryChange(this.value)">
                                    <option value="">-- Select Category --</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>" data-code="<?= htmlspecialchars($cat['code'] ?? '') ?>" data-hsn="<?= htmlspecialchars($cat['default_hsn'] ?? '6109') ?>" data-gst="<?= (float)($cat['default_gst_rate'] ?? 5.00) ?>" <?= (!empty($item['mapped_cat_id']) && $item['mapped_cat_id'] == $cat['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cat['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label small fw-semibold text-dark mb-0">Sub Category</label>
                                    <button type="button" class="btn btn-link btn-sm p-0 text-primary text-decoration-none small" data-bs-toggle="modal" data-bs-target="#modalAddSubcategory">
                                        <i class="bi bi-plus-circle"></i> New
                                    </button>
                                </div>
                                <select name="subcategory_id" id="selectSubCategory" class="form-select form-select-sm" onchange="handleSubCategoryChange(this.value)">
                                    <option value="">-- Select Category First --</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label small fw-semibold text-dark mb-0">Sub Sub Category</label>
                                    <button type="button" class="btn btn-link btn-sm p-0 text-primary text-decoration-none small" data-bs-toggle="modal" data-bs-target="#modalAddSubSubcategory">
                                        <i class="bi bi-plus-circle"></i> New
                                    </button>
                                </div>
                                <select name="sub_sub_category_id" id="selectSubSubCategory" class="form-select form-select-sm" onchange="handleSubSubCategoryChange(this.value)">
                                    <option value="">-- Select Sub Category First --</option>
                                </select>
                            </div>
                        </div>

                        <!-- Hierarchy Path Live Banner -->
                        <div class="p-2.5 rounded-3 bg-light border mb-4 d-flex align-items-center gap-2">
                            <i class="bi bi-folder2-open text-primary"></i>
                            <div class="small text-secondary">
                                Hierarchy Path: <strong class="text-dark" id="hierarchyPathText">Select a category above...</strong>
                            </div>
                        </div>

                        <!-- Category Short Codes & Whole Code matching Image 1 -->
                        <div class="mb-4">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="bi bi-qr-code text-primary"></i>
                                <strong class="small text-dark">Category Short Codes & Whole Code for QR Code</strong>
                            </div>
                            <div class="row g-2">
                                <div class="col-md-3">
                                    <label class="form-label small text-muted mb-1">Main Category Code</label>
                                    <input type="text" name="main_category_code" id="inputMainCatCode" class="form-control form-control-sm font-monospace text-uppercase" placeholder="E.G. W" oninput="updateWholeCode()">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small text-muted mb-1">Sub Category Code</label>
                                    <input type="text" name="sub_category_code" id="inputSubCatCode" class="form-control form-control-sm font-monospace text-uppercase" placeholder="E.G. M" oninput="updateWholeCode()">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small text-muted mb-1">Sub Sub Category Code</label>
                                    <input type="text" name="sub_sub_category_code" id="inputSubSubCatCode" class="form-control form-control-sm font-monospace text-uppercase" placeholder="E.G. F" oninput="updateWholeCode()">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small text-muted mb-1">Whole Code for QR Code</label>
                                    <input type="text" name="whole_code" id="inputWholeCode" class="form-control form-control-sm font-monospace text-uppercase fw-bold text-primary" placeholder="E.G. WMF">
                                </div>
                            </div>
                        </div>

                        <!-- HSN Code & GST Tax Classification matching Image 1 -->
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-file-earmark-spreadsheet text-success"></i>
                                    <strong class="small text-dark">HSN Code & GST Tax Classification</strong>
                                </div>
                                <span class="badge bg-light text-secondary border" style="font-size: 0.72rem;">Dual HSN: Category Default & Product-Specific Override</span>
                            </div>
                            
                            <div class="alert alert-info py-2 px-3 small d-flex justify-content-between align-items-center mb-3">
                                <div><i class="bi bi-info-circle me-1"></i> <span id="categoryDefaultHsnText">Category Default: <strong><?= htmlspecialchars($item['hsn_code'] ?: '6109') ?></strong> (5.00% GST)</span></div>
                                <button type="button" class="btn btn-sm btn-primary py-0.5 px-2" id="btnApplyCategoryHsn" onclick="applyCategoryHsn()">
                                    <i class="bi bi-check me-1"></i> Apply Category HSN
                                </button>
                            </div>

                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label small text-dark mb-1">HSN Code <span class="text-danger">*</span></label>
                                    <input type="text" name="hsn_code" id="inputHsnCode" class="form-control form-control-sm font-monospace fw-bold" value="<?= htmlspecialchars($item['hsn_code'] ?: '6109') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-dark mb-1">Default GST Rate (%)</label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" step="0.01" name="gst_rate" id="inputGstRate" class="form-control font-monospace" value="<?= number_format((float)($item['gst_rate'] ?? 5.00), 2) ?>">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

            </div>

            <!-- ========================================== -->
            <!-- RIGHT COLUMN: PRODUCT ATTRIBUTES & SIZES   -->
            <!-- ========================================== -->
            <div class="col-lg-5">
                
                <!-- 3. PRODUCT ATTRIBUTES MATCHING IMAGE 1 -->
                <div class="card border-0 shadow-sm rounded-3 mb-4" id="cardProductAttributes">
                    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center gap-2">
                        <i class="bi bi-tags-fill text-primary"></i>
                        <h6 class="mb-0 fw-bold text-dark">3. Product Attributes</h6>
                    </div>
                    <div class="card-body p-4">
                        
                        <!-- Style Code & Name -->
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-dark mb-1">Product Style Code <span class="text-danger">*</span></label>
                            <input type="text" name="style_code" id="inputStyleCode" class="form-control font-monospace fw-bold" value="<?= htmlspecialchars($suggestedStyleCode) ?>" required>
                            <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">Master style reference code used for barcode and variant SKUs.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-dark mb-1">Product Style Name <span class="text-danger">*</span></label>
                            <input type="text" name="style_name" id="inputStyleName" class="form-control" value="<?= htmlspecialchars($item['item_name']) ?>" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-semibold text-dark mb-1">Color / Fabric Variation</label>
                            <input type="text" name="color" class="form-control form-control-sm" value="<?= htmlspecialchars($item['color'] ?? '') ?>" placeholder="e.g. Red, Navy Blue, Maroon">
                        </div>

                        <!-- Size Attributes Matching Image 1 -->
                        <div class="mb-4 pt-3 border-top">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label small fw-semibold text-dark mb-0">
                                    <i class="bi bi-aspect-ratio text-primary me-1"></i> Size Attributes <span class="text-danger">*</span>
                                </label>
                                <div class="d-flex align-items-center gap-1">
                                    <button type="button" class="btn btn-outline-secondary btn-sm py-0.5 px-2" style="font-size: 0.75rem;" onclick="selectAllSizes()">Select All</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm py-0.5 px-2" style="font-size: 0.75rem;" onclick="clearAllSizes()">Clear</button>
                                </div>
                            </div>
                            
                            <div class="d-flex flex-wrap gap-2 mb-3" id="sizesContainer">
                                <?php foreach ($sizes as $s): ?>
                                    <label class="size-pill-label border rounded-3 p-2 d-inline-flex align-items-center gap-2 cursor-pointer bg-light">
                                        <input type="checkbox" name="sizes[]" value="<?= htmlspecialchars($s) ?>" class="form-check-input size-checkbox mt-0" checked onchange="handleSizeCheckboxChange(this)">
                                        <span class="fw-bold font-monospace size-text text-dark"><?= htmlspecialchars($s) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>

                            <!-- Add Custom Size Input -->
                            <div class="input-group input-group-sm">
                                <input type="text" id="inputCustomSize" class="form-control font-monospace" placeholder="Add custom size (e.g. 4XL, 32, 34)...">
                                <button type="button" class="btn btn-outline-primary" onclick="addCustomSizePill()">
                                    <i class="bi bi-plus-lg me-1"></i> Add Size
                                </button>
                            </div>
                        </div>

                        <!-- Pricing Configuration -->
                        <div class="pt-3 border-top">
                            <label class="form-label small fw-semibold text-dark mb-2">Pricing Configuration (₹)</label>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label small text-muted mb-1">MRP (₹)</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">₹</span>
                                        <input type="number" step="0.01" name="mrp" class="form-control font-monospace" placeholder="0.00" value="<?= number_format((float)($item['mrp'] ?? 0), 2, '.', '') ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-muted mb-1">Selling Price (₹)</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">₹</span>
                                        <input type="number" step="0.01" name="selling_price" class="form-control font-monospace" placeholder="0.00" value="<?= number_format((float)($item['selling_price'] ?? 0), 2, '.', '') ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- LIVE MAPPING SUMMARY CARD -->
                <div class="card border-0 shadow-sm rounded-3 mb-4 bg-light">
                    <div class="card-header bg-transparent py-3 border-bottom">
                        <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                            <i class="bi bi-layers text-primary"></i> Mapping Generation Preview
                        </h6>
                    </div>
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <span class="small text-secondary">Target Inward Quantity:</span>
                            <span class="fw-bold font-monospace text-primary fs-6"><?= number_format($item['qty']) ?> Pcs</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <span class="small text-secondary">Selected Size Variants:</span>
                            <span class="fw-bold font-monospace text-success fs-6" id="summarySelectedSizesCount"><?= count($sizes) ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center py-2">
                            <span class="small text-secondary">Master Stock Status:</span>
                            <span class="badge bg-success-subtle text-success border border-success-subtle">Ready to Inward</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- BOTTOM ACTIONS BAR -->
        <div class="d-flex justify-content-between align-items-center p-3 bg-white rounded-3 shadow-sm border mt-2">
            <a href="<?= \App\Core\Controller::url('/production/catalog-mapping') ?>" class="btn btn-light border px-4 py-2 fw-semibold text-secondary">
                Cancel
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
                        <input type="text" name="default_hsn" class="form-control font-monospace" value="6109">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">GST Rate (%)</label>
                        <input type="number" step="0.01" name="default_gst_rate" class="form-control font-monospace" value="5.00">
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

function toggleMappingMode(mode) {
    const contExisting = document.getElementById('containerExistingStyle');
    const cardCat = document.getElementById('cardCategoryHierarchy');
    const cardAttr = document.getElementById('cardProductAttributes');
    const selectCat = document.getElementById('selectExistingCatalog');

    if (mode === 'existing_style') {
        contExisting.classList.remove('d-none');
        cardCat.classList.add('d-none');
        cardAttr.classList.add('d-none');
        selectCat.setAttribute('required', 'required');
    } else {
        contExisting.classList.add('d-none');
        cardCat.classList.remove('d-none');
        cardAttr.classList.remove('d-none');
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
        catHsnText.innerHTML = `Category Default: <strong>${currentCategoryDefaultHsn}</strong> (${currentCategoryDefaultGst.toFixed(2)}% GST)`;
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
    const elem = document.getElementById('hierarchyPathText');
    if (elem) {
        elem.textContent = parts.length > 0 ? parts.join(' > ') : 'Select a category above...';
    }
}

function updateWholeCode() {
    const c1 = (document.getElementById('inputMainCatCode')?.value || '').trim();
    const c2 = (document.getElementById('inputSubCatCode')?.value || '').trim();
    const c3 = (document.getElementById('inputSubSubCatCode')?.value || '').trim();
    const whole = document.getElementById('inputWholeCode');
    if (whole) {
        whole.value = (c1 + c2 + c3).toUpperCase();
    }
}

function applyCategoryHsn() {
    document.getElementById('inputHsnCode').value = currentCategoryDefaultHsn;
    document.getElementById('inputGstRate').value = currentCategoryDefaultGst.toFixed(2);
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
    const count = document.querySelectorAll('.size-checkbox:checked').length;
    const elem = document.getElementById('summarySelectedSizesCount');
    if (elem) elem.textContent = count;
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
                // Remove placeholder if present
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
