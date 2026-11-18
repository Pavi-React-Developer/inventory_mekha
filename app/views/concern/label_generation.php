<?php 
use App\Services\QrService; 
use App\Core\Auth;
use App\Core\DB;

$concern = Auth::concern();
if (!$concern && !empty($selectedCid)) {
    $concern = DB::fetch("SELECT * FROM concerns WHERE id = ?", [$selectedCid]);
}
$concernName = $concern['name'] ?? 'INVENTO ERP';
?>
<div class="container-fluid px-0">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1 text-dark">Barcode & QR Label Generation</h4>
            <p class="text-secondary small mb-0">
                Generate production & inventory barcode labels across 4 core business stages: Purchase Raw Materials, Purchase Finished Goods, Production After-Stitching, and Customer Returns.
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= \App\Core\Controller::url('/scan') ?>" class="btn btn-outline-primary shadow-sm rounded-2 d-flex align-items-center gap-2">
                <i class="bi bi-qr-code-scan fs-6"></i> Open Live QR Scanner
            </a>
            <a href="<?= \App\Core\Controller::url('/m/purchase_stocks') ?>" class="btn btn-outline-success shadow-sm rounded-2 d-flex align-items-center gap-2">
                <i class="bi bi-boxes fs-6"></i> Purchase Stocks
            </a>
        </div>
    </div>

    <?php 
    $curTab = $activeTab ?? 'purchase_raw'; 
    ?>

    <!-- Workflow Banner if redirected directly from completing a purchase bill -->
    <?php if (!empty($activeBill)): ?>
        <div class="alert alert-success border-success-subtle shadow-sm rounded-3 d-flex flex-wrap align-items-center justify-content-between p-3 mb-4 gap-3 bg-white border-start border-4 border-success">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle p-2 d-flex align-items-center justify-content-center text-white" style="background-color: #10b981; width: 44px; height: 44px;">
                    <i class="bi bi-box-arrow-in-down fs-4"></i>
                </div>
                <div>
                    <h6 class="fw-bold mb-0 text-dark">
                        Purchase Bill #<?= htmlspecialchars($activeBill['bill_no']) ?> Recorded!
                        <?php if (!empty($activeBill['supplier_company']) || !empty($activeBill['supplier_name'])): ?>
                            <span class="fw-normal text-muted small">&bull; Supplier: <?= htmlspecialchars($activeBill['supplier_company'] ?: $activeBill['supplier_name']) ?></span>
                        <?php endif; ?>
                    </h6>
                    <p class="text-secondary small mb-0">
                        Items are loaded for label generation. Review, print barcode/QR stickers, then click <strong>Add to Stock</strong> to inward into Purchase Stocks inventory.
                    </p>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <?php if (($activeBill['inward_status'] ?? '') !== 'completed'): ?>
                    <form action="<?= \App\Core\Controller::url('/m/label/add_to_stock') ?>" method="POST" class="d-inline">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                        <input type="hidden" name="bill_id" value="<?= (int)$activeBill['id'] ?>">
                        <input type="hidden" name="tab" value="<?= ($curTab === 'purchase_fg') ? 'finished_goods' : 'rolls' ?>">
                        <button type="submit" class="btn btn-success fw-bold px-3 py-2 shadow-sm d-flex align-items-center gap-1.5 rounded-2">
                            <i class="bi bi-box-arrow-in-down fs-6"></i> Add to Stock &rarr; Purchase Stocks
                        </button>
                    </form>
                <?php else: ?>
                    <span class="badge bg-success-subtle text-success border border-success p-2">
                        <i class="bi bi-check-circle-fill me-1"></i> Already In Stock
                    </span>
                    <a href="<?= \App\Core\Controller::url('/m/purchase_stocks?tab=' . (($curTab === 'purchase_fg') ? 'finished_goods' : 'rolls')) ?>" class="btn btn-outline-success fw-bold px-3 py-2 rounded-2">
                        View in Purchase Stocks &rarr;
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- 4-Section Navigation Tabs -->
    <ul class="nav nav-pills nav-fill bg-white p-2 rounded-3 shadow-sm mb-4 border" id="labelSectionTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= ($curTab === 'purchase_raw') ? 'active' : '' ?> fw-semibold d-flex align-items-center justify-content-center gap-2 py-2" id="tab-purchase-raw-btn" data-bs-toggle="pill" data-bs-target="#tab-purchase-raw" type="button" role="tab">
                <i class="bi bi-truck fs-5 text-primary"></i>
                <span>1. Purchase Label</span>
                <span class="badge bg-primary-subtle text-primary border border-primary ms-1"><?= count($purchaseRawItems) ?></span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= ($curTab === 'purchase_fg') ? 'active' : '' ?> fw-semibold d-flex align-items-center justify-content-center gap-2 py-2" id="tab-purchase-fg-btn" data-bs-toggle="pill" data-bs-target="#tab-purchase-fg" type="button" role="tab">
                <i class="bi bi-box-seam fs-5 text-success"></i>
                <span>2. Purchase Finished Goods</span>
                <span class="badge bg-success-subtle text-success border border-success ms-1"><?= count($purchaseFgItems) ?></span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= ($curTab === 'production') ? 'active' : '' ?> fw-semibold d-flex align-items-center justify-content-center gap-2 py-2" id="tab-production-btn" data-bs-toggle="pill" data-bs-target="#tab-production" type="button" role="tab">
                <i class="bi bi-scissors fs-5 text-warning"></i>
                <span>3. Production Label</span>
                <span class="badge bg-warning-subtle text-warning border border-warning ms-1"><?= count($productionItems) ?></span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= ($curTab === 'return') ? 'active' : '' ?> fw-semibold d-flex align-items-center justify-content-center gap-2 py-2" id="tab-return-btn" data-bs-toggle="pill" data-bs-target="#tab-return" type="button" role="tab">
                <i class="bi bi-arrow-left-right fs-5 text-danger"></i>
                <span>4. Return Label</span>
                <span class="badge bg-danger-subtle text-danger border border-danger ms-1"><?= count($returnItems) ?></span>
            </button>
        </li>
    </ul>

    <!-- Tab Contents -->
    <div class="tab-content mb-4" id="labelSectionTabContent">
        
        <!-- ======================================================== -->
        <!-- TAB 1: PURCHASE LABEL (Rolls & Other Garments Products) -->
        <!-- ======================================================== -->
        <div class="tab-pane fade <?= ($curTab === 'purchase_raw') ? 'show active' : '' ?>" id="tab-purchase-raw" role="tabpanel">
            
            <!-- Sub-Navigation for Purchase Label: Fabric Rolls vs Other Garments Products -->
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 p-2 bg-white rounded-3 shadow-sm border gap-2">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="text-muted small fw-bold text-uppercase px-2" style="font-size: 0.72rem; letter-spacing: 0.05em;">
                        <i class="bi bi-funnel me-1"></i> Purchase Options:
                    </span>
                    <ul class="nav nav-pills gap-1" id="purchaseSubTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?= ($activeSubTab === 'rolls') ? 'active' : '' ?> btn-sm py-1.5 px-3 fw-bold d-flex align-items-center gap-2" id="subtab-rolls-btn" data-bs-toggle="pill" data-bs-target="#subtab-rolls-pane" type="button" role="tab">
                                <i class="bi bi-stack fs-6 text-primary"></i>
                                <span>Fabric Rolls</span>
                                <span class="badge bg-primary-subtle text-primary border border-primary"><?= count($purchaseRollItems) ?></span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?= ($activeSubTab === 'garment_other') ? 'active' : '' ?> btn-sm py-1.5 px-3 fw-bold d-flex align-items-center gap-2" id="subtab-other-btn" data-bs-toggle="pill" data-bs-target="#subtab-other-pane" type="button" role="tab">
                                <i class="bi bi-scissors fs-6 text-info"></i>
                                <span>Other Garments Products / Trims</span>
                                <span class="badge bg-info-subtle text-info border border-info"><?= count($purchaseOtherItems) ?></span>
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="text-muted small pe-2 d-none d-lg-block" style="font-size: 0.78rem;">
                    <i class="bi bi-info-circle me-1 text-primary"></i> Separate configuration and product lists for Fabric Rolls and Garment Trims.
                </div>
            </div>

            <!-- Purchase Label Sub-Tab Contents -->
            <div class="tab-content" id="purchaseSubTabContent">

                <!-- ---------------------------------------------------- -->
                <!-- SUB-PANE 1A: FABRIC ROLLS                            -->
                <!-- ---------------------------------------------------- -->
                <div class="tab-pane fade <?= ($activeSubTab === 'rolls') ? 'show active' : '' ?>" id="subtab-rolls-pane" role="tabpanel">
                    <div class="row g-4">
                        <!-- Fabric Roll Generator Config Card -->
                        <div class="col-lg-5">
                            <div class="card border-0 shadow-sm rounded-3 h-100">
                                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                                    <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                                        <i class="bi bi-stack text-primary fs-5"></i>
                                        <span>Fabric Roll Label Configuration</span>
                                    </h6>
                                    <span class="badge bg-primary-subtle text-primary border border-primary">Rolls Only</span>
                                </div>
                                <form action="<?= \App\Core\Controller::url('/m/label_generation/generate') ?>" method="POST" target="_blank" class="card-body p-4 d-flex flex-column">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                    <input type="hidden" name="label_type" value="purchase_raw">

                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold">Select Fabric Roll <span class="text-danger">*</span></label>
                                        <select name="item_id" id="roll_item_select" class="form-select" required onchange="updateRollPreview()">
                                            <option value="">-- Choose Fabric Roll --</option>
                                            <?php foreach ($purchaseRollItems as $raw): 
                                                $code = $raw['item_code'] ?: ('ROLL-' . $raw['id']);
                                                $tagCode = QrService::generateTagCode(
                                                    $raw['item_name'] ?: 'Fabric',
                                                    $raw['color'] ?? '',
                                                    $raw['pattern'] ?: ($raw['specification'] ?? ''),
                                                    (string)$raw['id']
                                                );
                                            ?>
                                                <option value="<?= $raw['id'] ?>"
                                                        data-name="<?= htmlspecialchars($raw['item_name']) ?>"
                                                        data-code="<?= htmlspecialchars($code) ?>"
                                                        data-tag="<?= htmlspecialchars($tagCode) ?>"
                                                        data-type="Fabric Roll"
                                                        data-isroll="1"
                                                        data-vendor="<?= htmlspecialchars($raw['supplier_company'] ?: ($raw['supplier_name'] ?: 'Vendor Mill')) ?>"
                                                        data-color="<?= htmlspecialchars($raw['color'] ?: 'STANDARD') ?>"
                                                        data-pattern="<?= htmlspecialchars($raw['pattern'] ?: '') ?>"
                                                        data-spec="<?= htmlspecialchars($raw['specification'] ?? '') ?>"
                                                        data-qty="<?= htmlspecialchars($raw['qty']) ?>"
                                                        data-unit="<?= htmlspecialchars($raw['unit'] ?: 'Rolls') ?>"
                                                        data-rq="<?= htmlspecialchars($raw['rq_qty'] ?: 1) ?>"
                                                        data-net="<?= htmlspecialchars($raw['net_weight'] ?? '') ?>"
                                                        data-bill="<?= htmlspecialchars($raw['bill_no']) ?>"
                                                        data-qr="<?= htmlspecialchars($tagCode) ?>">
                                                    [ROLL] <?= htmlspecialchars($raw['item_name']) ?> (<?= htmlspecialchars($tagCode) ?>) &bull; <?= number_format((float)$raw['qty'], 2) ?> <?= htmlspecialchars($raw['unit'] ?: 'Rolls') ?> &bull; Bill #<?= htmlspecialchars($raw['bill_no']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <small class="text-muted">Directly sourced from Purchase Billing Fabric Rolls.</small>
                                    </div>

                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <label class="form-label small fw-semibold mb-0">Number of Label Copies (N Prints) <span class="text-danger">*</span></label>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <button type="button" class="btn btn-outline-secondary py-0 px-2" onclick="setCopies('roll_qty', 1)">1</button>
                                                <button type="button" class="btn btn-outline-secondary py-0 px-2" onclick="setCopies('roll_qty', 2)">2</button>
                                                <button type="button" class="btn btn-outline-secondary py-0 px-2" onclick="setCopies('roll_qty', 4)">4</button>
                                                <button type="button" class="btn btn-outline-secondary py-0 px-2" onclick="setCopies('roll_qty', 12)">12</button>
                                                <button type="button" class="btn btn-outline-primary py-0 px-2" onclick="matchRollQty()">Match RQ</button>
                                            </div>
                                        </div>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light"><i class="bi bi-printer"></i></span>
                                            <input type="number" name="qty" id="roll_qty" class="form-control" min="1" max="500" value="12" required>
                                            <span class="input-group-text bg-light">Stickers</span>
                                        </div>
                                    </div>

                                    <!-- Live Roll Sticker Preview -->
                                    <div class="p-3 border rounded-3 bg-light mb-4">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="fw-bold text-dark small"><i class="bi bi-eye text-primary me-1"></i> Live Roll Sticker Preview</span>
                                            <span class="badge bg-primary-subtle text-primary border border-primary font-monospace" id="roll_preview_badge">RAW MATERIAL</span>
                                        </div>
                                        <div class="border rounded-3 p-3 bg-white shadow-sm" style="border: 1.5px solid #0f172a !important;">
                                            <div class="d-flex align-items-center justify-content-between pb-2 border-bottom border-secondary-subtle" style="border-style: dashed !important;">
                                                <div class="d-flex align-items-center">
                                                    <span style="background-color: #0d6efd; color: #ffffff; width: 17px; height: 17px; border-radius: 4px; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; margin-right: 6px;">
                                                        <i class="bi bi-check-lg"></i>
                                                    </span>
                                                    <span class="fw-bold text-dark text-uppercase small" style="letter-spacing: 0.04em;"><?= htmlspecialchars($concernName) ?></span>
                                                </div>
                                                <span class="badge font-monospace px-2 py-1" style="background-color: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd; font-size: 0.70rem;" id="roll_preview_ref">PO-REF</span>
                                            </div>

                                            <div class="d-flex align-items-center gap-3 pt-2">
                                                <div class="d-flex align-items-center justify-content-center bg-white flex-shrink-0" style="width: 76px; height: 76px;" id="roll_qr_box"></div>
                                                <div class="flex-grow-1 overflow-hidden" style="line-height: 1.35; font-size: 0.76rem;">
                                                    <div class="fw-bold text-dark text-uppercase mb-1 text-truncate" style="font-size: 0.86rem;" id="roll_preview_title">ROLL #1</div>
                                                    <div class="text-truncate"><span class="text-secondary">Fabric:</span> <strong class="text-dark" id="roll_preview_fabric">-</strong></div>
                                                    <div class="text-truncate"><span class="text-secondary">Color:</span> <strong class="text-dark text-uppercase" id="roll_preview_color">RED</strong></div>
                                                    <div class="text-truncate"><span class="text-secondary">Weight:</span> <strong class="text-dark" id="roll_preview_weight">10.00 kg</strong></div>
                                                    <div class="text-truncate"><span class="text-secondary">Code:</span> <strong class="font-monospace text-primary fw-bold" id="roll_preview_code_tag">FAB-R-FL</strong></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex gap-2 mt-auto">
                                        <button type="submit" class="btn btn-primary flex-grow-1 py-2 fw-semibold shadow-sm">
                                            <i class="bi bi-printer-fill me-1"></i> Generate Printable Sheet
                                        </button>
                                        <button type="button" class="btn btn-success py-2 fw-semibold shadow-sm px-3" onclick="submitRollToStock()" title="Inward selected roll directly into Purchase Stocks">
                                            <i class="bi bi-box-arrow-in-down me-1"></i> Add to Stock
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Separate Fabric Rolls Table -->
                        <div class="col-lg-7">
                            <div class="card border-0 shadow-sm rounded-3 h-100">
                                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                                        <i class="bi bi-stack text-primary"></i>
                                        <span>Purchase Fabric Rolls Directory</span>
                                    </h6>
                                    <input type="text" id="search_rolls_table" class="form-control form-control-sm" style="width: 220px;" placeholder="Search roll, fabric, bill...">
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0 table-custom" id="tableRollItems">
                                        <thead class="table-light text-muted small">
                                            <tr>
                                                <th>#</th>
                                                <th>Roll & Fabric</th>
                                                <th>Color & Design</th>
                                                <th>Bill & Supplier</th>
                                                <th>Weight / RQ</th>
                                                <th class="text-end pe-3">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($purchaseRollItems)): ?>
                                                <tr><td colspan="6" class="text-center py-4 text-muted">No fabric rolls found. Record purchase bills with fabric rolls to see items here.</td></tr>
                                            <?php else: ?>
                                                <?php $idx = 1; foreach ($purchaseRollItems as $item): 
                                                    $code = $item['item_code'] ?: ('ROLL-' . $item['id']);
                                                ?>
                                                    <tr class="roll-row" data-search="<?= strtolower(htmlspecialchars($item['item_name'] . ' ' . $code . ' ' . ($item['color'] ?? '') . ' ' . ($item['pattern'] ?? '') . ' ' . $item['bill_no'] . ' ' . ($item['supplier_name'] ?? ''))) ?>">
                                                        <td class="text-muted small"><?= $idx++ ?></td>
                                                        <td>
                                                            <div class="fw-bold text-dark"><?= htmlspecialchars($item['item_name']) ?></div>
                                                            <div class="small text-muted d-flex align-items-center gap-1 mt-1">
                                                                <span class="badge bg-primary-subtle text-primary border" style="font-size: 0.7rem;">FABRIC ROLL</span>
                                                                <span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($code) ?></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="fw-semibold text-dark"><?= htmlspecialchars($item['color'] ?: 'STANDARD') ?></div>
                                                            <small class="text-muted"><?= htmlspecialchars($item['pattern'] ?: ($item['specification'] ?: '-')) ?></small>
                                                        </td>
                                                        <td>
                                                            <div class="small fw-semibold text-dark">Bill #<?= htmlspecialchars($item['bill_no']) ?></div>
                                                            <small class="text-muted"><?= htmlspecialchars($item['supplier_company'] ?: ($item['supplier_name'] ?: 'Vendor')) ?></small>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-dark font-monospace"><?= number_format((float)$item['qty'], 2) ?> <?= htmlspecialchars($item['unit'] ?: 'Rolls') ?></span>
                                                            <?php if (!empty($item['net_weight'])): ?>
                                                                <small class="d-block text-muted"><?= number_format((float)$item['net_weight'], 2) ?> kg</small>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="text-end pe-3 text-nowrap">
                                                            <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2 fw-semibold me-1" onclick="selectRollItem(<?= (int)$item['id'] ?>)">
                                                                <i class="bi bi-printer me-1"></i> Print
                                                            </button>
                                                            <?php $isInStock = (($item['inward_status'] ?? '') === 'completed'); ?>
                                                            <?php if ($isInStock): ?>
                                                                <a href="<?= \App\Core\Controller::url('/m/purchase_stocks?tab=rolls') ?>" class="btn btn-sm btn-outline-success py-1 px-2 fw-semibold" title="In Stock - View in Purchase Stocks">
                                                                    <i class="bi bi-check-circle-fill text-success me-1"></i> In Stock
                                                                </a>
                                                            <?php else: ?>
                                                                <form action="<?= \App\Core\Controller::url('/m/label/add_to_stock') ?>" method="POST" class="d-inline">
                                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                                                    <input type="hidden" name="item_id" value="<?= (int)$item['id'] ?>">
                                                                    <input type="hidden" name="tab" value="rolls">
                                                                    <button type="submit" class="btn btn-sm btn-success py-1 px-2 fw-semibold" title="Inward roll directly into Purchase Stocks">
                                                                        <i class="bi bi-box-arrow-in-down me-1"></i> Add to Stock
                                                                    </button>
                                                                </form>
                                                            <?php endif; ?>
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

                <!-- ---------------------------------------------------- -->
                <!-- SUB-PANE 1B: OTHER GARMENTS PRODUCTS & TRIMS         -->
                <!-- ---------------------------------------------------- -->
                <div class="tab-pane fade <?= ($activeSubTab === 'garment_other') ? 'show active' : '' ?>" id="subtab-other-pane" role="tabpanel">
                    <div class="row g-4">
                        <!-- Other Garments Generator Config Card -->
                        <div class="col-lg-5">
                            <div class="card border-0 shadow-sm rounded-3 h-100">
                                <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                                    <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                                        <i class="bi bi-scissors text-info fs-5"></i>
                                        <span>Other Garments Label Configuration</span>
                                    </h6>
                                    <span class="badge bg-info-subtle text-info border border-info">Trims & Supplies</span>
                                </div>
                                <form action="<?= \App\Core\Controller::url('/m/label_generation/generate') ?>" method="POST" target="_blank" class="card-body p-4 d-flex flex-column">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                    <input type="hidden" name="label_type" value="purchase_raw">

                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold">Select Garment Supply / Trim Item <span class="text-danger">*</span></label>
                                        <select name="item_id" id="other_item_select" class="form-select" required onchange="updateOtherPreview()">
                                            <option value="">-- Choose Other Garment Product --</option>
                                            <?php foreach ($purchaseOtherItems as $other): 
                                                $code = $other['item_code'] ?: ('TRIM-' . $other['id']);
                                                $tagCode = QrService::generateTagCode(
                                                    $other['item_name'] ?: 'Trims',
                                                    $other['color'] ?? '',
                                                    $other['pattern'] ?: ($other['specification'] ?? ''),
                                                    (string)$other['id']
                                                );
                                            ?>
                                                <option value="<?= $other['id'] ?>"
                                                        data-name="<?= htmlspecialchars($other['item_name']) ?>"
                                                        data-code="<?= htmlspecialchars($code) ?>"
                                                        data-tag="<?= htmlspecialchars($tagCode) ?>"
                                                        data-type="<?= htmlspecialchars($other['product_type'] ?: 'Garment Supply') ?>"
                                                        data-isroll="0"
                                                        data-vendor="<?= htmlspecialchars($other['supplier_company'] ?: ($other['supplier_name'] ?: 'Vendor Mill')) ?>"
                                                        data-color="<?= htmlspecialchars($other['color'] ?: 'STANDARD') ?>"
                                                        data-pattern="<?= htmlspecialchars($other['pattern'] ?: '') ?>"
                                                        data-spec="<?= htmlspecialchars($other['specification'] ?? '') ?>"
                                                        data-qty="<?= htmlspecialchars($other['qty']) ?>"
                                                        data-unit="<?= htmlspecialchars($other['unit'] ?: 'pcs') ?>"
                                                        data-bill="<?= htmlspecialchars($other['bill_no']) ?>"
                                                        data-qr="<?= htmlspecialchars($tagCode) ?>">
                                                    [TRIM] <?= htmlspecialchars($other['item_name']) ?> (<?= htmlspecialchars($tagCode) ?>) &bull; <?= number_format((float)$other['qty'], 2) ?> <?= htmlspecialchars($other['unit']) ?> &bull; Bill #<?= htmlspecialchars($other['bill_no']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <small class="text-muted">Directly sourced from Purchase Billing Garment Other Supplies & Trims.</small>
                                    </div>

                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <label class="form-label small fw-semibold mb-0">Number of Label Copies (N Prints) <span class="text-danger">*</span></label>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <button type="button" class="btn btn-outline-secondary py-0 px-2" onclick="setCopies('other_qty', 1)">1</button>
                                                <button type="button" class="btn btn-outline-secondary py-0 px-2" onclick="setCopies('other_qty', 2)">2</button>
                                                <button type="button" class="btn btn-outline-secondary py-0 px-2" onclick="setCopies('other_qty', 4)">4</button>
                                                <button type="button" class="btn btn-outline-secondary py-0 px-2" onclick="setCopies('other_qty', 12)">12</button>
                                                <button type="button" class="btn btn-outline-primary py-0 px-2" onclick="matchOtherQty()">Match Qty</button>
                                            </div>
                                        </div>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light"><i class="bi bi-printer"></i></span>
                                            <input type="number" name="qty" id="other_qty" class="form-control" min="1" max="500" value="12" required>
                                            <span class="input-group-text bg-light">Stickers</span>
                                        </div>
                                    </div>

                                    <!-- Live Other Garment Sticker Preview -->
                                    <div class="p-3 border rounded-3 bg-light mb-4">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="fw-bold text-dark small"><i class="bi bi-eye text-info me-1"></i> Live Trim Sticker Preview</span>
                                            <span class="badge bg-info-subtle text-info border border-info font-monospace" id="other_preview_badge">PURCHASE TRIM</span>
                                        </div>
                                        <div class="border rounded-3 p-3 bg-white shadow-sm" style="border: 1.5px solid #0f172a !important;">
                                            <div class="d-flex align-items-center justify-content-between pb-2 border-bottom border-secondary-subtle" style="border-style: dashed !important;">
                                                <div class="d-flex align-items-center">
                                                    <span style="background-color: #0284c7; color: #ffffff; width: 17px; height: 17px; border-radius: 4px; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; margin-right: 6px;">
                                                        <i class="bi bi-check-lg"></i>
                                                    </span>
                                                    <span class="fw-bold text-dark text-uppercase small" style="letter-spacing: 0.04em;"><?= htmlspecialchars($concernName) ?></span>
                                                </div>
                                                <span class="badge font-monospace px-2 py-1" style="background-color: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd; font-size: 0.70rem;" id="other_preview_ref">TRIM-REF</span>
                                            </div>

                                            <div class="d-flex align-items-center gap-3 pt-2">
                                                <div class="d-flex align-items-center justify-content-center bg-white flex-shrink-0" style="width: 76px; height: 76px;" id="other_qr_box"></div>
                                                <div class="flex-grow-1 overflow-hidden" style="line-height: 1.35; font-size: 0.76rem;">
                                                    <div class="fw-bold text-dark text-uppercase mb-1 text-truncate" style="font-size: 0.86rem;" id="other_preview_title">SEWING THREAD</div>
                                                    <div class="text-truncate"><span class="text-secondary">Item:</span> <strong class="text-dark" id="other_preview_item">-</strong></div>
                                                    <div class="text-truncate"><span class="text-secondary">Color / Spec:</span> <strong class="text-dark text-uppercase" id="other_preview_color">-</strong></div>
                                                    <div class="text-truncate"><span class="text-secondary">Quantity:</span> <strong class="text-dark" id="other_preview_qty">50.00 spools</strong></div>
                                                    <div class="text-truncate"><span class="text-secondary">Code:</span> <strong class="font-monospace text-primary fw-bold" id="other_preview_code_tag">TR-TH-WHT</strong></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex gap-2 mt-auto">
                                        <button type="submit" class="btn btn-info text-white flex-grow-1 py-2 fw-semibold shadow-sm">
                                            <i class="bi bi-printer-fill me-1"></i> Generate Printable Sheet
                                        </button>
                                        <button type="button" class="btn btn-success py-2 fw-semibold shadow-sm px-3" onclick="submitOtherToStock()" title="Inward selected trim directly into Purchase Stocks">
                                            <i class="bi bi-box-arrow-in-down me-1"></i> Add to Stock
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Separate Other Garments Products Table -->
                        <div class="col-lg-7">
                            <div class="card border-0 shadow-sm rounded-3 h-100">
                                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                                        <i class="bi bi-scissors text-info"></i>
                                        <span>Purchase Other Garments & Trims Directory</span>
                                    </h6>
                                    <input type="text" id="search_other_table" class="form-control form-control-sm" style="width: 220px;" placeholder="Search trims, supplies, bill...">
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0 table-custom" id="tableOtherItems">
                                        <thead class="table-light text-muted small">
                                            <tr>
                                                <th>#</th>
                                                <th>Garment Supply Item</th>
                                                <th>Specification & Color</th>
                                                <th>Bill & Supplier</th>
                                                <th>Inwarded Qty</th>
                                                <th class="text-end pe-3">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($purchaseOtherItems)): ?>
                                                <tr><td colspan="6" class="text-center py-4 text-muted">No other garment supplies or trims found. Record purchase bills with trims to see items here.</td></tr>
                                            <?php else: ?>
                                                <?php $idx = 1; foreach ($purchaseOtherItems as $item): 
                                                    $code = $item['item_code'] ?: ('TRIM-' . $item['id']);
                                                ?>
                                                    <tr class="other-row" data-search="<?= strtolower(htmlspecialchars($item['item_name'] . ' ' . $code . ' ' . ($item['color'] ?? '') . ' ' . ($item['specification'] ?? '') . ' ' . $item['bill_no'] . ' ' . ($item['supplier_name'] ?? ''))) ?>">
                                                        <td class="text-muted small"><?= $idx++ ?></td>
                                                        <td>
                                                            <div class="fw-bold text-dark"><?= htmlspecialchars($item['item_name']) ?></div>
                                                            <div class="small text-muted d-flex align-items-center gap-1 mt-1">
                                                                <span class="badge bg-info-subtle text-info border" style="font-size: 0.7rem;"><?= strtoupper($item['product_type'] ?: 'TRIM / SUPPLY') ?></span>
                                                                <span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($code) ?></span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="fw-semibold text-dark"><?= htmlspecialchars($item['specification'] ?: '-') ?></div>
                                                            <?php if (!empty($item['color'])): ?>
                                                                <small class="text-muted">Color: <?= htmlspecialchars($item['color']) ?></small>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <div class="small fw-semibold text-dark">Bill #<?= htmlspecialchars($item['bill_no']) ?></div>
                                                            <small class="text-muted"><?= htmlspecialchars($item['supplier_company'] ?: ($item['supplier_name'] ?: 'Vendor')) ?></small>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-dark font-monospace"><?= number_format((float)$item['qty'], 2) ?> <?= htmlspecialchars($item['unit'] ?: 'pcs') ?></span>
                                                        </td>
                                                        <td class="text-end pe-3 text-nowrap">
                                                            <button type="button" class="btn btn-sm btn-outline-info py-1 px-2 fw-semibold me-1" onclick="selectOtherItem(<?= (int)$item['id'] ?>)">
                                                                <i class="bi bi-printer me-1"></i> Print
                                                            </button>
                                                            <?php $isInStock = (($item['inward_status'] ?? '') === 'completed'); ?>
                                                            <?php if ($isInStock): ?>
                                                                <a href="<?= \App\Core\Controller::url('/m/purchase_stocks?tab=garment_other') ?>" class="btn btn-sm btn-outline-success py-1 px-2 fw-semibold" title="In Stock - View in Purchase Stocks">
                                                                    <i class="bi bi-check-circle-fill text-success me-1"></i> In Stock
                                                                </a>
                                                            <?php else: ?>
                                                                <form action="<?= \App\Core\Controller::url('/m/label/add_to_stock') ?>" method="POST" class="d-inline">
                                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                                                    <input type="hidden" name="item_id" value="<?= (int)$item['id'] ?>">
                                                                    <input type="hidden" name="tab" value="garment_other">
                                                                    <button type="submit" class="btn btn-sm btn-success py-1 px-2 fw-semibold" title="Inward item directly into Purchase Stocks">
                                                                        <i class="bi bi-box-arrow-in-down me-1"></i> Add to Stock
                                                                    </button>
                                                                </form>
                                                            <?php endif; ?>
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

            </div>
        </div>

        <!-- ======================================================== -->
        <!-- TAB 2: PURCHASE FINISHED GOODS (Mapped with Catalog)     -->
        <!-- ======================================================== -->
        <div class="tab-pane fade <?= ($curTab === 'purchase_fg') ? 'show active' : '' ?>" id="tab-purchase-fg" role="tabpanel">
            <div class="row g-4">
                <!-- Generator Config Card -->
                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm rounded-3 h-100">
                        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                            <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                                <i class="bi bi-box-seam text-success fs-5"></i>
                                <span>Purchase Finished Goods Label</span>
                            </h6>
                            <span class="badge bg-success-subtle text-success border border-success">Catalog Mapped</span>
                        </div>
                        <form action="<?= \App\Core\Controller::url('/m/label_generation/generate') ?>" method="POST" target="_blank" class="card-body p-4 d-flex flex-column" id="form_fg_label">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                            <input type="hidden" name="label_type" value="purchase_fg">

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Select Purchased Finished Good <span class="text-danger">*</span></label>
                                <select name="item_id" id="fg_item_select" class="form-select" required onchange="updateFgPreview()">
                                    <option value="">-- Choose Finished Goods Item --</option>
                                    <?php foreach ($purchaseFgItems as $fg): 
                                        $isMapped = !empty($fg['variant_id']);
                                        $varName = $fg['variant_name'] ?: $fg['item_name'];
                                        $sku = $fg['sku_code'] ?: ($fg['item_code'] ?: 'FG-' . $fg['id']);
                                        $tagCode = QrService::generateTagCode(
                                            $varName,
                                            $fg['color'] ?? '',
                                            $fg['specification'] ?? '',
                                            (string)$fg['id']
                                        );
                                        $mrp = $fg['variant_mrp'] ?: ($fg['mrp'] ?: $fg['unit_price']);
                                        $size = $fg['variant_size'] ?: ($fg['specification'] ?: 'Free');
                                        $cat = $fg['category_name'] ?: 'Apparel';
                                    ?>
                                        <option value="<?= $fg['id'] ?>"
                                                data-name="<?= htmlspecialchars($varName) ?>"
                                                data-sku="<?= htmlspecialchars($sku) ?>"
                                                data-tag="<?= htmlspecialchars($tagCode) ?>"
                                                data-mrp="<?= htmlspecialchars($mrp) ?>"
                                                data-size="<?= htmlspecialchars($size) ?>"
                                                data-cat="<?= htmlspecialchars($cat) ?>"
                                                data-brand="<?= htmlspecialchars($fg['brand_name'] ?: ($fg['supplier_company'] ?: 'INVENTO')) ?>"
                                                data-color="<?= htmlspecialchars($fg['color'] ?: 'STANDARD') ?>"
                                                data-mapped="<?= $isMapped ? '1' : '0' ?>"
                                                data-varid="<?= htmlspecialchars($fg['variant_id'] ?? '') ?>"
                                                data-qty="<?= htmlspecialchars($fg['qty']) ?>"
                                                data-unit="<?= htmlspecialchars($fg['unit'] ?: 'pcs') ?>"
                                                data-bill="<?= htmlspecialchars($fg['bill_no']) ?>"
                                                data-qr="<?= htmlspecialchars($tagCode) ?>">
                                            <?= htmlspecialchars($fg['item_name']) ?> (<?= htmlspecialchars($tagCode) ?>) &bull; <?= number_format((float)$fg['qty'], 0) ?> pcs &bull; Bill #<?= htmlspecialchars($fg['bill_no']) ?> <?= $isMapped ? " [Mapped: {$sku}]" : " [Unmapped]" ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Catalog Mapping Status Banner -->
                            <div class="mb-3" id="fg_mapping_card" style="display: none;">
                                <div class="p-3 border rounded-3 bg-light" id="fg_mapped_box">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi bi-check-circle-fill text-success fs-5"></i>
                                            <div>
                                                <div class="fw-bold text-dark small" id="fg_mapped_title">Mapped to Catalog</div>
                                                <small class="text-muted font-monospace" id="fg_mapped_sku">SKU</small>
                                            </div>
                                        </div>
                                        <span class="badge bg-success-subtle text-success border border-success">Verified</span>
                                    </div>
                                </div>
                                <div class="p-3 border border-warning-subtle rounded-3 bg-warning-subtle" id="fg_unmapped_box" style="display: none;">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <i class="bi bi-exclamation-triangle-fill text-warning fs-5"></i>
                                        <div class="fw-bold text-dark small">Catalog Mapping Recommended</div>
                                    </div>
                                    <p class="small text-secondary mb-2">This item has not yet been linked to a master product variant. Link it now to print scannable retail POS barcode labels.</p>
                                    <div class="input-group input-group-sm">
                                        <select id="fg_map_variant_select" class="form-select">
                                            <option value="">-- Choose Catalog Variant to Map --</option>
                                            <?php foreach ($allVariants as $av): ?>
                                                <option value="<?= $av['id'] ?>">
                                                    <?= htmlspecialchars($av['sku_code']) ?> &bull; <?= htmlspecialchars($av['name']) ?> (<?= htmlspecialchars($av['size']) ?>) &bull; MRP ₹<?= number_format($av['mrp'], 2) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="button" class="btn btn-success fw-semibold" onclick="doMapVariant()">
                                            <i class="bi bi-link-45deg"></i> Link Variant
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label small fw-semibold mb-0">Number of Label Copies (N Prints) <span class="text-danger">*</span></label>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" class="btn btn-outline-secondary py-0 px-2" onclick="setCopies('fg_qty', 10)">10</button>
                                        <button type="button" class="btn btn-outline-secondary py-0 px-2" onclick="setCopies('fg_qty', 25)">25</button>
                                        <button type="button" class="btn btn-outline-secondary py-0 px-2" onclick="setCopies('fg_qty', 50)">50</button>
                                        <button type="button" class="btn btn-outline-primary py-0 px-2" onclick="matchFgQty()">Match Purchase Qty</button>
                                    </div>
                                </div>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-printer"></i></span>
                                    <input type="number" name="qty" id="fg_qty" class="form-control" min="1" max="500" value="24" required>
                                    <span class="input-group-text bg-light">Price Tags</span>
                                </div>
                            </div>

                            <!-- Live Sticker Preview (Image 2 Reference Layout) -->
                            <div class="p-3 border rounded-3 bg-light mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-bold text-dark small"><i class="bi bi-eye text-success me-1"></i> Retail Tag Preview</span>
                                    <span class="badge bg-success-subtle text-success border border-success font-monospace" id="fg_preview_badge">READY-MADE</span>
                                </div>
                                <div class="border rounded-3 p-3 bg-white shadow-sm" style="border: 1.5px solid #0f172a !important;">
                                    <!-- Top Header: Concern Name Dynamically & Reference Badge -->
                                    <div class="d-flex align-items-center justify-content-between pb-2 border-bottom border-secondary-subtle" style="border-style: dashed !important;">
                                        <div class="d-flex align-items-center">
                                            <span style="background-color: #0d6efd; color: #ffffff; width: 17px; height: 17px; border-radius: 4px; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; margin-right: 6px;">
                                                <i class="bi bi-check-lg"></i>
                                            </span>
                                            <span class="fw-bold text-dark text-uppercase small" style="letter-spacing: 0.04em;" id="fg_preview_concern"><?= htmlspecialchars($concernName) ?></span>
                                        </div>
                                        <span class="badge font-monospace px-2 py-1" style="background-color: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd; font-size: 0.70rem;" id="fg_preview_ref">PO-REF</span>
                                    </div>

                                    <!-- Body: Left QR, Right Details Stack -->
                                    <div class="d-flex align-items-center gap-3 pt-2">
                                        <div class="d-flex align-items-center justify-content-center bg-white flex-shrink-0" style="width: 76px; height: 76px;" id="fg_qr_box"></div>
                                        <div class="flex-grow-1 overflow-hidden" style="line-height: 1.35; font-size: 0.76rem;">
                                            <div class="fw-bold text-dark text-uppercase mb-1 text-truncate" style="font-size: 0.86rem;" id="fg_preview_title">Finished Good</div>
                                            <div class="text-truncate"><span class="text-secondary">Product:</span> <strong class="text-dark" id="fg_preview_cat">Apparel</strong></div>
                                            <div class="text-truncate"><span class="text-secondary">Color:</span> <strong class="text-dark text-uppercase" id="fg_preview_color">STANDARD</strong></div>
                                            <div class="text-truncate"><span class="text-secondary">Size / Qty:</span> <strong class="text-dark" id="fg_preview_size_qty">Free Size</strong></div>
                                            <div class="text-truncate"><span class="text-secondary">Code:</span> <strong class="font-monospace text-primary fw-bold" id="fg_preview_code_tag">FG-0-01</strong></div>
                                            <div class="text-danger fw-bold mt-1" style="font-size: 0.72rem;" id="fg_preview_mrp">MRP ₹0.00</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex gap-2 mt-auto">
                                <button type="submit" class="btn btn-success flex-grow-1 py-2 fw-semibold shadow-sm">
                                    <i class="bi bi-printer-fill me-1"></i> Generate Printable Sheet
                                </button>
                                <button type="button" class="btn btn-outline-success py-2 fw-semibold shadow-sm px-3" onclick="submitFgToStock()" title="Inward selected finished goods directly into Purchase Stocks">
                                    <i class="bi bi-box-arrow-in-down me-1"></i> Add to Stock
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Purchase Finished Goods Directory Table -->
                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm rounded-3 h-100">
                        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                                <i class="bi bi-list-check text-success"></i>
                                <span>Purchased Finished Goods Directory</span>
                            </h6>
                            <input type="text" id="search_fg_table" class="form-control form-control-sm" style="width: 220px;" placeholder="Search finished goods...">
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 table-custom" id="tableFgItems">
                                <thead class="table-light text-muted small">
                                    <tr>
                                        <th>#</th>
                                        <th>Finished Garment</th>
                                        <th>Bill & Supplier</th>
                                        <th>Catalog Status</th>
                                        <th>Qty & MRP</th>
                                        <th class="text-end pe-3">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($purchaseFgItems)): ?>
                                        <tr><td colspan="6" class="text-center py-4 text-muted">No finished goods recorded in purchase billing.</td></tr>
                                    <?php else: ?>
                                        <?php $idx = 1; foreach ($purchaseFgItems as $fg): 
                                            $isMapped = !empty($fg['variant_id']);
                                            $sku = $fg['sku_code'] ?: ($fg['item_code'] ?: 'FG-' . $fg['id']);
                                        ?>
                                            <tr class="fg-row" data-search="<?= strtolower(htmlspecialchars($fg['item_name'] . ' ' . $sku . ' ' . $fg['bill_no'])) ?>">
                                                <td class="text-muted small"><?= $idx++ ?></td>
                                                <td>
                                                    <div class="fw-bold text-dark"><?= htmlspecialchars($fg['variant_name'] ?: $fg['item_name']) ?></div>
                                                    <div class="small text-muted d-flex align-items-center gap-1 mt-1">
                                                        <span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($sku) ?></span>
                                                        <span class="badge bg-dark" style="font-size: 0.7rem;"><?= htmlspecialchars($fg['variant_size'] ?: ($fg['specification'] ?: 'Free')) ?></span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="small fw-semibold text-dark">Bill #<?= htmlspecialchars($fg['bill_no']) ?></div>
                                                    <small class="text-muted"><?= htmlspecialchars($fg['supplier_name'] ?: 'Vendor') ?></small>
                                                </td>
                                                <td>
                                                    <?php if ($isMapped): ?>
                                                        <span class="badge bg-success-subtle text-success border border-success">
                                                            <i class="bi bi-check-circle me-1"></i> Mapped
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning-subtle text-warning border border-warning">
                                                            <i class="bi bi-link-45deg me-1"></i> Unmapped
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="fw-semibold text-dark"><?= number_format((float)$fg['qty'], 0) ?> pcs</div>
                                                    <div class="small text-danger fw-bold">MRP ₹<?= number_format((float)($fg['variant_mrp'] ?: ($fg['mrp'] ?: $fg['unit_price'])), 2) ?></div>
                                                </td>
                                                <td class="text-end pe-3 text-nowrap">
                                                    <button type="button" class="btn btn-sm btn-outline-success py-1 px-2 fw-semibold me-1" onclick="selectFgItem(<?= (int)$fg['id'] ?>)">
                                                        <i class="bi bi-printer me-1"></i> Print
                                                    </button>
                                                    <?php $isFgInStock = (($fg['inward_status'] ?? '') === 'completed'); ?>
                                                    <?php if ($isFgInStock): ?>
                                                        <a href="<?= \App\Core\Controller::url('/m/purchase_stocks?tab=finished_goods') ?>" class="btn btn-sm btn-outline-success py-1 px-2 fw-semibold" title="Already in Stock - Click to view in Purchase Stocks">
                                                            <i class="bi bi-check-circle-fill text-success me-1"></i> In Stock
                                                        </a>
                                                    <?php else: ?>
                                                        <form action="<?= \App\Core\Controller::url('/m/label/add_to_stock') ?>" method="POST" class="d-inline">
                                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                                            <input type="hidden" name="item_id" value="<?= (int)$fg['id'] ?>">
                                                            <input type="hidden" name="tab" value="finished_goods">
                                                            <button type="submit" class="btn btn-sm btn-success py-1 px-2 fw-semibold" title="Inward finished goods directly into Purchase Stocks">
                                                                <i class="bi bi-box-arrow-in-down me-1"></i> Add to Stock
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
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
        <!-- TAB 3: PRODUCTION LABEL (After Stitching Products)       -->
        <!-- ======================================================== -->
        <div class="tab-pane fade <?= ($curTab === 'production') ? 'show active' : '' ?>" id="tab-production" role="tabpanel">
            <div class="row g-4">
                <!-- Generator Config Card -->
                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm rounded-3 h-100">
                        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                            <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                                <i class="bi bi-scissors text-warning fs-5"></i>
                                <span>Production Label Configuration</span>
                            </h6>
                            <span class="badge bg-warning-subtle text-warning border border-warning">After Stitching</span>
                        </div>
                        <form action="<?= \App\Core\Controller::url('/m/label_generation/generate') ?>" method="POST" target="_blank" class="card-body p-4 d-flex flex-column">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                            <input type="hidden" name="label_type" value="production">

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Select Stitched Production Batch <span class="text-danger">*</span></label>
                                <select name="item_id" id="prod_item_select" class="form-select" required onchange="updateProdPreview()">
                                    <option value="">-- Choose Stitched Production Batch --</option>
                                    <?php foreach ($productionItems as $prod): 
                                        $tagCode = QrService::generateTagCode(
                                            $prod['variant_name'],
                                            '',
                                            $prod['program_no'] ?? '',
                                            (string)$prod['produced_variant_id']
                                        );
                                    ?>
                                        <option value="<?= $prod['id'] ?>"
                                                data-name="<?= htmlspecialchars($prod['variant_name']) ?>"
                                                data-sku="<?= htmlspecialchars($prod['sku_code']) ?>"
                                                data-tag="<?= htmlspecialchars($tagCode) ?>"
                                                data-mrp="<?= htmlspecialchars($prod['variant_mrp']) ?>"
                                                data-size="<?= htmlspecialchars($prod['variant_size'] ?: 'Standard') ?>"
                                                data-cat="<?= htmlspecialchars($prod['category_name'] ?: 'Apparel') ?>"
                                                data-brand="<?= htmlspecialchars($prod['brand_name'] ?: 'INVENTO Production') ?>"
                                                data-log="<?= htmlspecialchars($prod['log_no']) ?>"
                                                data-pcs="<?= htmlspecialchars($prod['stitched_pieces']) ?>"
                                                data-program="<?= htmlspecialchars($prod['program_no'] ?: 'Standard Program') ?>"
                                                data-qr="<?= htmlspecialchars($tagCode) ?>">
                                            Log #<?= htmlspecialchars($prod['log_no']) ?> &bull; <?= htmlspecialchars($prod['variant_name']) ?> (<?= htmlspecialchars($tagCode) ?>) &bull; <?= (int)$prod['stitched_pieces'] ?> pcs stitched
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Populated automatically from production assembly logs after stitching.</small>
                            </div>

                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label small fw-semibold mb-0">Number of Label Copies (N Prints) <span class="text-danger">*</span></label>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" class="btn btn-outline-secondary py-0 px-2" onclick="setCopies('prod_qty', 12)">12</button>
                                        <button type="button" class="btn btn-outline-secondary py-0 px-2" onclick="setCopies('prod_qty', 24)">24</button>
                                        <button type="button" class="btn btn-outline-secondary py-0 px-2" onclick="setCopies('prod_qty', 50)">50</button>
                                        <button type="button" class="btn btn-outline-warning py-0 px-2" onclick="matchProdQty()">Match Stitched Pcs</button>
                                    </div>
                                </div>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-printer"></i></span>
                                    <input type="number" name="qty" id="prod_qty" class="form-control" min="1" max="500" value="24" required>
                                    <span class="input-group-text bg-light">Stickers</span>
                                </div>
                            </div>

                            <!-- Live Sticker Preview (Image 2 Reference Layout) -->
                            <div class="p-3 border rounded-3 bg-light mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-bold text-dark small"><i class="bi bi-eye text-warning me-1"></i> Production Tag Preview</span>
                                    <span class="badge bg-warning-subtle text-warning border border-warning font-monospace" id="prod_preview_badge">IN-HOUSE STITCHED</span>
                                </div>
                                <div class="border rounded-3 p-3 bg-white shadow-sm" style="border: 1.5px solid #0f172a !important;">
                                    <!-- Top Header: Concern Name Dynamically & Reference Badge -->
                                    <div class="d-flex align-items-center justify-content-between pb-2 border-bottom border-secondary-subtle" style="border-style: dashed !important;">
                                        <div class="d-flex align-items-center">
                                            <span style="background-color: #0d6efd; color: #ffffff; width: 17px; height: 17px; border-radius: 4px; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; margin-right: 6px;">
                                                <i class="bi bi-check-lg"></i>
                                            </span>
                                            <span class="fw-bold text-dark text-uppercase small" style="letter-spacing: 0.04em;" id="prod_preview_concern"><?= htmlspecialchars($concernName) ?></span>
                                        </div>
                                        <span class="badge font-monospace px-2 py-1" style="background-color: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd; font-size: 0.70rem;" id="prod_preview_ref">PP-REF</span>
                                    </div>

                                    <!-- Body: Left QR, Right Details Stack -->
                                    <div class="d-flex align-items-center gap-3 pt-2">
                                        <div class="d-flex align-items-center justify-content-center bg-white flex-shrink-0" style="width: 76px; height: 76px;" id="prod_qr_box"></div>
                                        <div class="flex-grow-1 overflow-hidden" style="line-height: 1.35; font-size: 0.76rem;">
                                            <div class="fw-bold text-dark text-uppercase mb-1 text-truncate" style="font-size: 0.86rem;" id="prod_preview_title">Production Garment</div>
                                            <div class="text-truncate"><span class="text-secondary">Program:</span> <strong class="text-dark" id="prod_preview_program">In-House</strong></div>
                                            <div class="text-truncate"><span class="text-secondary">Color:</span> <strong class="text-dark text-uppercase" id="prod_preview_color">STANDARD</strong></div>
                                            <div class="text-truncate"><span class="text-secondary">Size:</span> <strong class="text-dark" id="prod_preview_size">Standard</strong></div>
                                            <div class="text-truncate"><span class="text-secondary">Code:</span> <strong class="font-monospace text-primary fw-bold" id="prod_preview_code_tag">PRO-S-01</strong></div>
                                            <div class="text-danger fw-bold mt-1" style="font-size: 0.72rem;" id="prod_preview_mrp">MRP ₹0.00</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-warning text-dark w-100 py-2 fw-semibold shadow-sm mt-auto">
                                <i class="bi bi-printer-fill me-1"></i> Generate Printable Sheet (N Labels)
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Production Stitched Items Directory Table -->
                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm rounded-3 h-100">
                        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                                <i class="bi bi-list-check text-warning"></i>
                                <span>Recent Stitching Production Batches</span>
                            </h6>
                            <input type="text" id="search_prod_table" class="form-control form-control-sm" style="width: 220px;" placeholder="Search stitched batch...">
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 table-custom" id="tableProdItems">
                                <thead class="table-light text-muted small">
                                    <tr>
                                        <th>#</th>
                                        <th>Log & Program</th>
                                        <th>Assembled Garment</th>
                                        <th>Stitched Pcs</th>
                                        <th class="text-end pe-3">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($productionItems)): ?>
                                        <tr><td colspan="5" class="text-center py-4 text-muted">No stitched production batches found. Complete a stitching log in Production Line.</td></tr>
                                    <?php else: ?>
                                        <?php $idx = 1; foreach ($productionItems as $p): ?>
                                            <tr class="prod-row" data-search="<?= strtolower(htmlspecialchars($p['log_no'] . ' ' . $p['variant_name'] . ' ' . $p['sku_code'])) ?>">
                                                <td class="text-muted small"><?= $idx++ ?></td>
                                                <td>
                                                    <span class="badge bg-dark font-monospace"><?= htmlspecialchars($p['log_no']) ?></span>
                                                    <div class="small text-muted mt-1"><?= htmlspecialchars($p['program_no'] ?: 'Standard Program') ?></div>
                                                </td>
                                                <td>
                                                    <div class="fw-bold text-dark"><?= htmlspecialchars($p['variant_name']) ?></div>
                                                    <div class="small text-muted d-flex align-items-center gap-1 mt-1">
                                                        <span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($p['sku_code']) ?></span>
                                                        <span class="badge bg-secondary" style="font-size: 0.7rem;"><?= htmlspecialchars($p['variant_size'] ?: 'Free') ?></span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-success font-monospace fs-6"><?= (int)$p['stitched_pieces'] ?> pcs</span>
                                                    <small class="d-block text-muted"><?= date('d M Y', strtotime($p['created_at'])) ?></small>
                                                </td>
                                                <td class="text-end pe-3">
                                                    <button type="button" class="btn btn-sm btn-outline-warning text-dark py-1 px-2 fw-semibold" onclick="selectProdItem(<?= (int)$p['id'] ?>)">
                                                        <i class="bi bi-printer me-1"></i> Print
                                                    </button>
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
        <!-- TAB 4: RETURN LABEL (Returns & Exchanges)                -->
        <!-- ======================================================== -->
        <div class="tab-pane fade <?= ($curTab === 'return') ? 'show active' : '' ?>" id="tab-return" role="tabpanel">
            <div class="row g-4">
                <!-- Generator Config Card -->
                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm rounded-3 h-100">
                        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                            <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                                <i class="bi bi-arrow-left-right text-danger fs-5"></i>
                                <span>Return & Exchange Label Configuration</span>
                            </h6>
                            <span class="badge bg-danger-subtle text-danger border border-danger">Restock Tag</span>
                        </div>
                        <form action="<?= \App\Core\Controller::url('/m/label_generation/generate') ?>" method="POST" target="_blank" class="card-body p-4 d-flex flex-column">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                            <input type="hidden" name="label_type" value="return">

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Select Return / Exchange Record <span class="text-danger">*</span></label>
                                <select name="item_id" id="ret_item_select" class="form-select" required onchange="updateRetPreview()">
                                    <option value="">-- Choose Return Record --</option>
                                    <?php foreach ($returnItems as $ret): 
                                        $retTag = QrService::generateTagCode(
                                            $ret['ret_variant_name'],
                                            '',
                                            'RET',
                                            (string)$ret['returned_variant_id']
                                        );
                                        $hasExchange = !empty($ret['exchange_variant_id']);
                                        $excTag = $hasExchange ? QrService::generateTagCode(
                                            $ret['exc_variant_name'] ?? 'Exchange',
                                            '',
                                            'EXC',
                                            (string)$ret['exchange_variant_id']
                                        ) : '';
                                    ?>
                                        <option value="<?= $ret['id'] ?>"
                                                data-return-no="<?= htmlspecialchars($ret['return_no']) ?>"
                                                data-type="<?= htmlspecialchars($ret['type']) ?>"
                                                data-customer="<?= htmlspecialchars($ret['customer_name'] ?: 'Retail Customer') ?>"
                                                data-invoice="<?= htmlspecialchars($ret['invoice_no'] ?: 'Direct Return') ?>"
                                                data-ret-name="<?= htmlspecialchars($ret['ret_variant_name']) ?>"
                                                data-ret-sku="<?= htmlspecialchars($ret['ret_sku']) ?>"
                                                data-ret-tag="<?= htmlspecialchars($retTag) ?>"
                                                data-ret-size="<?= htmlspecialchars($ret['ret_size'] ?: 'Free') ?>"
                                                data-ret-mrp="<?= htmlspecialchars($ret['ret_mrp']) ?>"
                                                data-ret-qty="<?= htmlspecialchars($ret['returned_qty']) ?>"
                                                data-ret-qr="<?= htmlspecialchars($retTag) ?>"
                                                data-has-exchange="<?= $hasExchange ? '1' : '0' ?>"
                                                data-exc-name="<?= htmlspecialchars($ret['exc_variant_name'] ?? '') ?>"
                                                data-exc-sku="<?= htmlspecialchars($ret['exc_sku'] ?? '') ?>"
                                                data-exc-tag="<?= htmlspecialchars($excTag) ?>"
                                                data-exc-size="<?= htmlspecialchars($ret['exc_size'] ?? '') ?>"
                                                data-exc-mrp="<?= htmlspecialchars($ret['exc_mrp'] ?? 0) ?>"
                                                data-exc-qty="<?= htmlspecialchars($ret['exchange_qty'] ?? 1) ?>"
                                                data-exc-qr="<?= htmlspecialchars($excTag) ?>">
                                            Return #<?= htmlspecialchars($ret['return_no']) ?> &bull; <?= htmlspecialchars($ret['customer_name'] ?: 'Customer') ?> &bull; <?= htmlspecialchars($ret['ret_variant_name']) ?> (<?= htmlspecialchars($retTag) ?>) &bull; <?= number_format((float)$ret['returned_qty'], 0) ?> pcs
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Target Item Radio: Return item vs Exchange item -->
                            <div class="mb-3" id="ret_target_wrapper" style="display: none;">
                                <label class="form-label small fw-semibold">Target Tag to Print</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="item_target" id="target_returned" value="returned" checked onchange="updateRetPreview()">
                                        <label class="form-check-label small fw-semibold text-danger" for="target_returned">
                                            <i class="bi bi-arrow-return-left"></i> Returned Item (Restock)
                                        </label>
                                    </div>
                                    <div class="form-check" id="target_exchange_check" style="display: none;">
                                        <input class="form-check-input" type="radio" name="item_target" id="target_exchange" value="exchange" onchange="updateRetPreview()">
                                        <label class="form-check-label small fw-semibold text-primary" for="target_exchange">
                                            <i class="bi bi-arrow-repeat"></i> Exchange Replacement Item
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label small fw-semibold mb-0">Number of Label Copies (N Prints) <span class="text-danger">*</span></label>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" class="btn btn-outline-secondary py-0 px-2" onclick="setCopies('ret_qty', 1)">1</button>
                                        <button type="button" class="btn btn-outline-secondary py-0 px-2" onclick="setCopies('ret_qty', 2)">2</button>
                                        <button type="button" class="btn btn-outline-secondary py-0 px-2" onclick="setCopies('ret_qty', 5)">5</button>
                                        <button type="button" class="btn btn-outline-danger py-0 px-2" onclick="matchRetQty()">Match Returned Qty</button>
                                    </div>
                                </div>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-printer"></i></span>
                                    <input type="number" name="qty" id="ret_qty" class="form-control" min="1" max="500" value="1" required>
                                    <span class="input-group-text bg-light">Stickers</span>
                                </div>
                            </div>

                            <!-- Live Sticker Preview (Image 2 Reference Layout) -->
                            <div class="p-3 border rounded-3 bg-light mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-bold text-dark small"><i class="bi bi-eye text-danger me-1"></i> Restock Label Preview</span>
                                    <span class="badge bg-danger-subtle text-danger border border-danger font-monospace" id="ret_preview_badge">RESTOCK ITEM</span>
                                </div>
                                <div class="border rounded-3 p-3 bg-white shadow-sm" style="border: 1.5px solid #0f172a !important;">
                                    <!-- Top Header: Concern Name Dynamically & Reference Badge -->
                                    <div class="d-flex align-items-center justify-content-between pb-2 border-bottom border-secondary-subtle" style="border-style: dashed !important;">
                                        <div class="d-flex align-items-center">
                                            <span style="background-color: #0d6efd; color: #ffffff; width: 17px; height: 17px; border-radius: 4px; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; margin-right: 6px;">
                                                <i class="bi bi-check-lg"></i>
                                            </span>
                                            <span class="fw-bold text-dark text-uppercase small" style="letter-spacing: 0.04em;" id="ret_preview_concern"><?= htmlspecialchars($concernName) ?></span>
                                        </div>
                                        <span class="badge font-monospace px-2 py-1" style="background-color: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd; font-size: 0.70rem;" id="ret_preview_ref">RET-REF</span>
                                    </div>

                                    <!-- Body: Left QR, Right Details Stack -->
                                    <div class="d-flex align-items-center gap-3 pt-2">
                                        <div class="d-flex align-items-center justify-content-center bg-white flex-shrink-0" style="width: 76px; height: 76px;" id="ret_qr_box"></div>
                                        <div class="flex-grow-1 overflow-hidden" style="line-height: 1.35; font-size: 0.76rem;">
                                            <div class="fw-bold text-dark text-uppercase mb-1 text-truncate" style="font-size: 0.86rem;" id="ret_preview_title">Returned Product</div>
                                            <div class="text-truncate"><span class="text-secondary">Customer:</span> <strong class="text-dark" id="ret_preview_customer">Retail</strong></div>
                                            <div class="text-truncate"><span class="text-secondary">Color:</span> <strong class="text-dark text-uppercase" id="ret_preview_color">STANDARD</strong></div>
                                            <div class="text-truncate"><span class="text-secondary">Size / Qty:</span> <strong class="text-dark" id="ret_preview_size">Free Size</strong></div>
                                            <div class="text-truncate"><span class="text-secondary">Code:</span> <strong class="font-monospace text-primary fw-bold" id="ret_preview_code_tag">RET-C-01</strong></div>
                                            <div class="text-danger fw-bold mt-1" style="font-size: 0.72rem;" id="ret_preview_mrp">MRP ₹0.00</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-danger w-100 py-2 fw-semibold shadow-sm mt-auto">
                                <i class="bi bi-printer-fill me-1"></i> Generate Printable Sheet (N Labels)
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Returns & Exchange Items Directory Table -->
                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm rounded-3 h-100">
                        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                                <i class="bi bi-list-check text-danger"></i>
                                <span>Customer Returns & Exchanges Directory</span>
                            </h6>
                            <input type="text" id="search_ret_table" class="form-control form-control-sm" style="width: 220px;" placeholder="Search returns...">
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 table-custom" id="tableRetItems">
                                <thead class="table-light text-muted small">
                                    <tr>
                                        <th>#</th>
                                        <th>Return No & Customer</th>
                                        <th>Returned Garment</th>
                                        <th>Type & Qty</th>
                                        <th class="text-end pe-3">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($returnItems)): ?>
                                        <tr><td colspan="5" class="text-center py-4 text-muted">No return or exchange records found.</td></tr>
                                    <?php else: ?>
                                        <?php $idx = 1; foreach ($returnItems as $r): ?>
                                            <tr class="ret-row" data-search="<?= strtolower(htmlspecialchars($r['return_no'] . ' ' . $r['customer_name'] . ' ' . $r['ret_variant_name'])) ?>">
                                                <td class="text-muted small"><?= $idx++ ?></td>
                                                <td>
                                                    <span class="badge bg-dark font-monospace"><?= htmlspecialchars($r['return_no']) ?></span>
                                                    <div class="small fw-semibold text-dark mt-1"><?= htmlspecialchars($r['customer_name'] ?: 'Retail Customer') ?></div>
                                                </td>
                                                <td>
                                                    <div class="fw-bold text-dark"><?= htmlspecialchars($r['ret_variant_name']) ?></div>
                                                    <div class="small text-muted d-flex align-items-center gap-1 mt-1">
                                                        <span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($r['ret_sku']) ?></span>
                                                        <span class="badge bg-secondary" style="font-size: 0.7rem;"><?= htmlspecialchars($r['ret_size'] ?: 'Free') ?></span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge <?= $r['type'] === 'exchange' ? 'bg-primary-subtle text-primary border' : 'bg-danger-subtle text-danger border' ?>" style="font-size: 0.7rem;">
                                                        <?= strtoupper($r['type']) ?>
                                                    </span>
                                                    <div class="small fw-bold text-dark mt-1"><?= number_format((float)$r['returned_qty'], 0) ?> pcs</div>
                                                </td>
                                                <td class="text-end pe-3">
                                                    <button type="button" class="btn btn-sm btn-outline-danger py-1 px-2 fw-semibold" onclick="selectRetItem(<?= (int)$r['id'] ?>)">
                                                        <i class="bi bi-printer me-1"></i> Print
                                                    </button>
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

    </div>

    <!-- ======================================================== -->
    <!-- AUDIT TRAIL: RECENT LABEL GENERATION JOBS                -->
    <!-- ======================================================== -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                <i class="bi bi-clock-history text-primary fs-5"></i>
                <span>Recent Label Generation Jobs (Audit Trail)</span>
            </h6>
            <span class="badge bg-light text-dark border"><?= count($recentJobs) ?> Recent Print Jobs</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-custom">
                <thead class="table-light text-muted small">
                    <tr>
                        <th style="width: 50px;">Job ID</th>
                        <th>Stage / Section</th>
                        <th>Printed Item & Code</th>
                        <th>QR Code Reference</th>
                        <th>Copies</th>
                        <th>Printed By</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentJobs)): ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">No label print jobs recorded yet. Choose an item above to generate labels.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentJobs as $job): 
                            $type = $job['label_type'] ?? 'variant';
                            $title = $job['item_title'] ?: ($job['variant_name'] ?? 'Garment Item');
                            $code = $job['item_code'] ?: ($job['sku_code'] ?? 'SKU');
                            $qr = $job['qr_payload'] ?: (!empty($job['category_id']) ? QrService::generatePayload((int)$job['category_id'], (int)$job['subcategory_id'], (int)$job['variant_id']) : 'C?-S?-V?');
                            
                            $badgeClass = 'bg-primary-subtle text-primary border-primary';
                            $badgeLabel = 'Purchase Roll';
                            if ($type === 'purchase_fg') {
                                $badgeClass = 'bg-success-subtle text-success border-success';
                                $badgeLabel = 'Finished Goods';
                            } elseif ($type === 'production') {
                                $badgeClass = 'bg-warning-subtle text-warning border-warning';
                                $badgeLabel = 'Production Stitched';
                            } elseif ($type === 'return') {
                                $badgeClass = 'bg-danger-subtle text-danger border-danger';
                                $badgeLabel = 'Return Restock';
                            } elseif ($type === 'variant') {
                                $badgeClass = 'bg-secondary-subtle text-secondary border-secondary';
                                $badgeLabel = 'Catalog Variant';
                            }
                        ?>
                            <tr>
                                <td class="fw-bold font-monospace text-dark">#<?= (int)$job['id'] ?></td>
                                <td>
                                    <span class="badge border <?= $badgeClass ?>" style="font-size: 0.72rem;"><?= $badgeLabel ?></span>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= htmlspecialchars($title) ?></div>
                                    <small class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($code) ?></small>
                                </td>
                                <td><code class="text-primary font-monospace"><?= htmlspecialchars($qr) ?></code></td>
                                <td><span class="badge bg-info-subtle text-info border border-info font-monospace"><?= (int)$job['qty'] ?> Prints</span></td>
                                <td class="small text-muted"><?= htmlspecialchars($job['user_name'] ?? 'System') ?></td>
                                <td class="small text-muted font-monospace"><?= date('d M Y, H:i', strtotime($job['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="<?= \App\Core\Controller::url('/assets/js/qrcode.min.js') ?>"></script>
<script>
// Helper: Build simplified tag code (first 3 letters product - first letter color - 2 letters design)
function makeTagCode(prod, color, design, fallbackId) {
    let p = (prod || 'PRD').toUpperCase().replace(/[^A-Z0-9]/g, '');
    if (p.length < 3) p = (p + 'XXX').substring(0, 3);
    const prod3 = p.substring(0, 3);

    let c = (color || '').toUpperCase().replace(/[^A-Z0-9]/g, '');
    const col1 = c.length > 0 ? c.substring(0, 1) : 'X';

    let d = (design || '').toUpperCase().replace(/[^A-Z0-9]/g, '');
    if (!d) {
        let cleanId = String(fallbackId || '').replace(/[^A-Z0-9]/g, '');
        d = cleanId.length >= 2 ? cleanId.substring(0, 2) : (cleanId.length === 1 ? '0' + cleanId : '01');
    } else if (d.length === 1) {
        d = '0' + d;
    } else {
        d = d.substring(0, 2);
    }
    const des2 = d;

    return `${prod3}-${col1}-${des2}`;
}

// Helper: Render crisp client-side QR Code into target box
function renderQr(containerId, text) {
    const el = document.getElementById(containerId);
    if (!el || typeof QRCode === 'undefined') return;
    el.innerHTML = '';
    new QRCode(el, {
        text: text,
        width: 76,
        height: 76,
        colorDark: "#000000",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.M
    });
}

// 1A. Fabric Roll Preview Handler
function updateRollPreview() {
    const sel = document.getElementById('roll_item_select');
    if (!sel) return;
    const opt = sel.options[sel.selectedIndex];
    if (opt && opt.value) {
        const tag = opt.dataset.tag || makeTagCode(opt.dataset.name, opt.dataset.color, opt.dataset.pattern || opt.dataset.spec, opt.value);
        const title = 'ROLL #' + (opt.dataset.code || opt.value);
        const fabric = opt.dataset.name || 'Fabric Material';
        const color = (opt.dataset.color || 'STANDARD').toUpperCase();
        const weight = (opt.dataset.net && parseFloat(opt.dataset.net) > 0)
            ? (parseFloat(opt.dataset.net).toFixed(2) + ' kg')
            : (parseFloat(opt.dataset.qty || 0).toFixed(2) + ' ' + (opt.dataset.unit || 'Rolls'));
        const ref = opt.dataset.bill ? ('PO-' + opt.dataset.bill) : 'PO-REF';

        document.getElementById('roll_preview_ref').textContent = ref;
        document.getElementById('roll_preview_title').textContent = title;
        document.getElementById('roll_preview_fabric').textContent = fabric;
        document.getElementById('roll_preview_color').textContent = color;
        document.getElementById('roll_preview_weight').textContent = weight;
        document.getElementById('roll_preview_code_tag').textContent = tag;
        document.getElementById('roll_preview_badge').textContent = 'RAW MATERIAL';

        renderQr('roll_qr_box', tag);
    } else {
        document.getElementById('roll_preview_ref').textContent = 'PO-REF';
        document.getElementById('roll_preview_title').textContent = 'ROLL #1';
        document.getElementById('roll_preview_fabric').textContent = 'Select a fabric roll';
        document.getElementById('roll_preview_color').textContent = 'RED';
        document.getElementById('roll_preview_weight').textContent = '10.00 kg';
        document.getElementById('roll_preview_code_tag').textContent = 'FAB-R-FL';
        document.getElementById('roll_preview_badge').textContent = 'RAW MATERIAL';
        renderQr('roll_qr_box', 'FAB-R-FL');
    }
}

function selectRollItem(id) {
    const sel = document.getElementById('roll_item_select');
    if (!sel) return;
    sel.value = id;
    updateRollPreview();
    matchRollQty();
    sel.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function matchRollQty() {
    const sel = document.getElementById('roll_item_select');
    if (!sel) return;
    const opt = sel.options[sel.selectedIndex];
    if (opt && opt.value) {
        const rq = parseInt(opt.dataset.rq || opt.dataset.qty || 1, 10);
        document.getElementById('roll_qty').value = rq > 0 ? rq : 1;
    }
}

function submitRollToStock() {
    const select = document.getElementById('roll_item_select');
    if (!select || !select.value) {
        alert('Please select a fabric roll first.');
        return;
    }
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?= \App\Core\Controller::url('/m/label/add_to_stock') ?>';
    form.innerHTML = `
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
        <input type="hidden" name="item_id" value="${select.value}">
        <input type="hidden" name="tab" value="rolls">
    `;
    document.body.appendChild(form);
    form.submit();
}

// 1B. Other Garments Products & Trims Preview Handler
function updateOtherPreview() {
    const sel = document.getElementById('other_item_select');
    if (!sel) return;
    const opt = sel.options[sel.selectedIndex];
    if (opt && opt.value) {
        const tag = opt.dataset.tag || makeTagCode(opt.dataset.name, opt.dataset.color, opt.dataset.spec, opt.value);
        const ref = opt.dataset.bill ? ('BILL #' + opt.dataset.bill) : 'TRIM-REF';
        const colorSpec = (opt.dataset.color ? opt.dataset.color + ' • ' : '') + (opt.dataset.spec || '-');

        document.getElementById('other_preview_ref').textContent = ref;
        document.getElementById('other_preview_title').textContent = opt.dataset.name || 'Garment Supply';
        document.getElementById('other_preview_item').textContent = opt.dataset.name || 'Trim Item';
        document.getElementById('other_preview_color').textContent = colorSpec.toUpperCase();
        document.getElementById('other_preview_qty').textContent = parseFloat(opt.dataset.qty || 0).toFixed(2) + ' ' + (opt.dataset.unit || 'pcs');
        document.getElementById('other_preview_code_tag').textContent = tag;
        document.getElementById('other_preview_badge').textContent = 'PURCHASE TRIM';

        renderQr('other_qr_box', tag);
    } else {
        document.getElementById('other_preview_ref').textContent = 'TRIM-REF';
        document.getElementById('other_preview_title').textContent = 'SEWING THREAD';
        document.getElementById('other_preview_item').textContent = 'Select a trim item';
        document.getElementById('other_preview_color').textContent = 'STANDARD';
        document.getElementById('other_preview_qty').textContent = '50.00 spools';
        document.getElementById('other_preview_code_tag').textContent = 'TR-TH-WHT';
        document.getElementById('other_preview_badge').textContent = 'PURCHASE TRIM';
        renderQr('other_qr_box', 'TR-TH-WHT');
    }
}

function selectOtherItem(id) {
    const sel = document.getElementById('other_item_select');
    if (!sel) return;
    sel.value = id;
    updateOtherPreview();
    matchOtherQty();
    sel.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function matchOtherQty() {
    const sel = document.getElementById('other_item_select');
    if (!sel) return;
    const opt = sel.options[sel.selectedIndex];
    if (opt && opt.value) {
        const qty = parseInt(opt.dataset.qty || 1, 10);
        document.getElementById('other_qty').value = qty > 0 ? qty : 1;
    }
}

function submitOtherToStock() {
    const select = document.getElementById('other_item_select');
    if (!select || !select.value) {
        alert('Please select an other garment product item first.');
        return;
    }
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?= \App\Core\Controller::url('/m/label/add_to_stock') ?>';
    form.innerHTML = `
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
        <input type="hidden" name="item_id" value="${select.value}">
        <input type="hidden" name="tab" value="garment_other">
    `;
    document.body.appendChild(form);
    form.submit();
}

// Backward compatibility alias for tests/legacy handlers
function updateRawPreview() {
    updateRollPreview();
    updateOtherPreview();
}
function selectRawItem(id) {
    selectRollItem(id);
    selectOtherItem(id);
}
function matchRawQty() {
    matchRollQty();
}
function submitRawToStock() {
    submitRollToStock();
}

// Tab 2: Purchase FG Preview Handler
function updateFgPreview() {
    const sel = document.getElementById('fg_item_select');
    const opt = sel.options[sel.selectedIndex];
    const mappingCard = document.getElementById('fg_mapping_card');
    const mappedBox = document.getElementById('fg_mapped_box');
    const unmappedBox = document.getElementById('fg_unmapped_box');

    if (opt && opt.value) {
        const tag = opt.dataset.tag || makeTagCode(opt.dataset.name, opt.dataset.color, '', opt.value);
        const ref = opt.dataset.bill ? ('BILL #' + opt.dataset.bill) : (opt.dataset.sku || 'PO-FG');
        const sizeQty = (opt.dataset.size || 'Free Size') + ' (' + (parseInt(opt.dataset.qty || 1, 10)) + ' ' + (opt.dataset.unit || 'pcs') + ')';

        document.getElementById('fg_preview_ref').textContent = ref;
        document.getElementById('fg_preview_title').textContent = opt.dataset.name || 'Finished Good';
        document.getElementById('fg_preview_cat').textContent = opt.dataset.cat || 'Apparel';
        document.getElementById('fg_preview_color').textContent = (opt.dataset.color || 'STANDARD').toUpperCase();
        document.getElementById('fg_preview_size_qty').textContent = sizeQty;
        document.getElementById('fg_preview_code_tag').textContent = tag;
        document.getElementById('fg_preview_mrp').textContent = 'MRP ₹' + parseFloat(opt.dataset.mrp || 0).toFixed(2);

        renderQr('fg_qr_box', tag);

        mappingCard.style.display = 'block';
        if (opt.dataset.mapped === '1') {
            mappedBox.style.display = 'block';
            unmappedBox.style.display = 'none';
            document.getElementById('fg_mapped_title').textContent = 'Mapped to ' + opt.dataset.name;
            document.getElementById('fg_mapped_sku').textContent = 'Catalog SKU: ' + opt.dataset.sku;
        } else {
            mappedBox.style.display = 'none';
            unmappedBox.style.display = 'block';
        }
    } else {
        document.getElementById('fg_preview_ref').textContent = 'PO-FG';
        document.getElementById('fg_preview_title').textContent = 'Select Finished Good';
        document.getElementById('fg_preview_cat').textContent = 'Apparel';
        document.getElementById('fg_preview_color').textContent = 'STANDARD';
        document.getElementById('fg_preview_size_qty').textContent = 'Free Size';
        document.getElementById('fg_preview_code_tag').textContent = 'GAR-S-01';
        document.getElementById('fg_preview_mrp').textContent = 'MRP ₹0.00';
        renderQr('fg_qr_box', 'GAR-S-01');
        mappingCard.style.display = 'none';
    }
}

function selectFgItem(id) {
    const sel = document.getElementById('fg_item_select');
    sel.value = id;
    updateFgPreview();
    matchFgQty();
    sel.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function matchFgQty() {
    const sel = document.getElementById('fg_item_select');
    const opt = sel.options[sel.selectedIndex];
    if (opt && opt.value) {
        const qty = parseInt(opt.dataset.qty || 24, 10);
        document.getElementById('fg_qty').value = qty > 0 ? qty : 24;
    }
}

function doMapVariant() {
    const itemSel = document.getElementById('fg_item_select');
    const itemId = itemSel.value;
    const varSel = document.getElementById('fg_map_variant_select');
    const variantId = varSel.value;

    if (!itemId) {
        alert('Please select a purchase finished good item first.');
        return;
    }
    if (!variantId) {
        alert('Please choose a catalog variant to map.');
        return;
    }

    const formData = new FormData();
    formData.append('csrf_token', '<?= htmlspecialchars($csrfToken) ?>');
    formData.append('item_id', itemId);
    formData.append('variant_id', variantId);

    fetch('<?= \App\Core\Controller::url('/m/label_generation/map_variant') ?>', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            alert(res.message);
            const opt = itemSel.options[itemSel.selectedIndex];
            opt.dataset.mapped = '1';
            opt.dataset.name = res.variant_name;
            opt.dataset.sku = res.sku_code;
            opt.dataset.mrp = res.mrp;
            opt.dataset.size = res.size;
            opt.dataset.tag = makeTagCode(res.variant_name, '', '', res.sku_code);
            opt.dataset.qr = opt.dataset.tag;
            updateFgPreview();
        } else {
            alert('Error: ' + res.message);
        }
    })
    .catch(err => {
        alert('Failed to link variant: ' + err.message);
    });
}

// Tab 3: Production Preview Handler
function updateProdPreview() {
    const sel = document.getElementById('prod_item_select');
    const opt = sel.options[sel.selectedIndex];
    if (opt && opt.value) {
        const tag = opt.dataset.tag || makeTagCode(opt.dataset.name, '', opt.dataset.program, opt.value);
        const ref = opt.dataset.program || opt.dataset.log || 'PP-REF';
        const sizePcs = (opt.dataset.size || 'Standard') + ' (' + (parseInt(opt.dataset.pcs || 1, 10)) + ' pcs)';

        document.getElementById('prod_preview_ref').textContent = ref;
        document.getElementById('prod_preview_title').textContent = opt.dataset.name || 'Production Garment';
        document.getElementById('prod_preview_program').textContent = opt.dataset.program || 'In-House';
        document.getElementById('prod_preview_color').textContent = 'STANDARD';
        document.getElementById('prod_preview_size').textContent = sizePcs;
        document.getElementById('prod_preview_code_tag').textContent = tag;
        document.getElementById('prod_preview_mrp').textContent = 'MRP ₹' + parseFloat(opt.dataset.mrp || 0).toFixed(2);

        renderQr('prod_qr_box', tag);
    } else {
        document.getElementById('prod_preview_ref').textContent = 'PP-REF';
        document.getElementById('prod_preview_title').textContent = 'Select Stitched Batch';
        document.getElementById('prod_preview_program').textContent = 'In-House';
        document.getElementById('prod_preview_color').textContent = 'STANDARD';
        document.getElementById('prod_preview_size').textContent = 'Standard';
        document.getElementById('prod_preview_code_tag').textContent = 'PRO-S-01';
        document.getElementById('prod_preview_mrp').textContent = 'MRP ₹0.00';
        renderQr('prod_qr_box', 'PRO-S-01');
    }
}

function selectProdItem(id) {
    const sel = document.getElementById('prod_item_select');
    sel.value = id;
    updateProdPreview();
    matchProdQty();
    sel.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function matchProdQty() {
    const sel = document.getElementById('prod_item_select');
    const opt = sel.options[sel.selectedIndex];
    if (opt && opt.value) {
        const pcs = parseInt(opt.dataset.pcs || 24, 10);
        document.getElementById('prod_qty').value = pcs > 0 ? pcs : 24;
    }
}

// Tab 4: Return Preview Handler
function updateRetPreview() {
    const sel = document.getElementById('ret_item_select');
    const opt = sel.options[sel.selectedIndex];
    const targetWrapper = document.getElementById('ret_target_wrapper');
    const excCheck = document.getElementById('target_exchange_check');
    const isExchangeTarget = document.getElementById('target_exchange') && document.getElementById('target_exchange').checked;

    if (opt && opt.value) {
        targetWrapper.style.display = 'block';
        if (opt.dataset.hasExchange === '1') {
            excCheck.style.display = 'block';
        } else {
            excCheck.style.display = 'none';
            document.getElementById('target_returned').checked = true;
        }

        if (isExchangeTarget && opt.dataset.hasExchange === '1') {
            const tag = opt.dataset.excTag || makeTagCode(opt.dataset.excName, '', 'EXC', opt.value);
            const ref = 'EXC #' + (opt.dataset.returnNo || '00');
            const sizeQty = (opt.dataset.excSize || 'Free') + ' (' + (parseInt(opt.dataset.excQty || 1, 10)) + ' pcs)';

            document.getElementById('ret_preview_ref').textContent = ref;
            document.getElementById('ret_preview_title').textContent = opt.dataset.excName || 'Exchange Replacement';
            document.getElementById('ret_preview_customer').textContent = opt.dataset.customer || 'Retail Customer';
            document.getElementById('ret_preview_color').textContent = 'STANDARD';
            document.getElementById('ret_preview_size').textContent = sizeQty;
            document.getElementById('ret_preview_code_tag').textContent = tag;
            document.getElementById('ret_preview_mrp').textContent = 'MRP ₹' + parseFloat(opt.dataset.excMrp || 0).toFixed(2);
            document.getElementById('ret_preview_badge').textContent = 'EXCHANGE DISPATCH';

            renderQr('ret_qr_box', tag);
        } else {
            const tag = opt.dataset.retTag || makeTagCode(opt.dataset.retName, '', 'RET', opt.value);
            const ref = 'RET #' + (opt.dataset.returnNo || '00');
            const sizeQty = (opt.dataset.retSize || 'Free') + ' (' + (parseInt(opt.dataset.retQty || 1, 10)) + ' pcs)';

            document.getElementById('ret_preview_ref').textContent = ref;
            document.getElementById('ret_preview_title').textContent = opt.dataset.retName || 'Returned Product';
            document.getElementById('ret_preview_customer').textContent = opt.dataset.customer || 'Retail Customer';
            document.getElementById('ret_preview_color').textContent = 'STANDARD';
            document.getElementById('ret_preview_size').textContent = sizeQty;
            document.getElementById('ret_preview_code_tag').textContent = tag;
            document.getElementById('ret_preview_mrp').textContent = 'MRP ₹' + parseFloat(opt.dataset.retMrp || 0).toFixed(2);
            document.getElementById('ret_preview_badge').textContent = 'RESTOCK ITEM';

            renderQr('ret_qr_box', tag);
        }
    } else {
        targetWrapper.style.display = 'none';
        document.getElementById('ret_preview_ref').textContent = 'RET-00';
        document.getElementById('ret_preview_title').textContent = 'Select Return Item';
        document.getElementById('ret_preview_customer').textContent = 'Retail Customer';
        document.getElementById('ret_preview_color').textContent = 'STANDARD';
        document.getElementById('ret_preview_size').textContent = 'Free Size';
        document.getElementById('ret_preview_code_tag').textContent = 'RET-S-01';
        document.getElementById('ret_preview_mrp').textContent = 'MRP ₹0.00';
        document.getElementById('ret_preview_badge').textContent = 'RESTOCK ITEM';
        renderQr('ret_qr_box', 'RET-S-01');
    }
}

function selectRetItem(id) {
    const sel = document.getElementById('ret_item_select');
    sel.value = id;
    updateRetPreview();
    matchRetQty();
    sel.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function matchRetQty() {
    const sel = document.getElementById('ret_item_select');
    const opt = sel.options[sel.selectedIndex];
    if (opt && opt.value) {
        const qty = parseInt(opt.dataset.retQty || 1, 10);
        document.getElementById('ret_qty').value = qty > 0 ? qty : 1;
    }
}

function setCopies(inputId, count) {
    document.getElementById(inputId).value = count;
}

function submitRawToStock() {
    const select = document.getElementById('raw_item_select');
    if (!select || !select.value) {
        alert('Please select a fabric roll or trim item first.');
        return;
    }
    const opt = select.options[select.selectedIndex];
    const isRoll = opt ? (opt.dataset.isroll === '1') : true;
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?= \App\Core\Controller::url('/m/label/add_to_stock') ?>';
    form.innerHTML = `
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
        <input type="hidden" name="item_id" value="${select.value}">
        <input type="hidden" name="tab" value="${isRoll ? 'rolls' : 'garment_other'}">
    `;
    document.body.appendChild(form);
    form.submit();
}

function submitFgToStock() {
    const select = document.getElementById('fg_item_select');
    if (!select || !select.value) {
        alert('Please select a finished goods item first.');
        return;
    }
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?= \App\Core\Controller::url('/m/label/add_to_stock') ?>';
    form.innerHTML = `
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
        <input type="hidden" name="item_id" value="${select.value}">
        <input type="hidden" name="tab" value="finished_goods">
    `;
    document.body.appendChild(form);
    form.submit();
}

// Client-side search filters for tables
function setupTableSearch(inputId, rowClass) {
    const input = document.getElementById(inputId);
    if (!input) return;
    input.addEventListener('input', function() {
        const q = (this.value || '').trim().toLowerCase();
        document.querySelectorAll('.' + rowClass).forEach(function(row) {
            const text = row.dataset.search || '';
            row.style.display = (!q || text.includes(q)) ? '' : 'none';
        });
    });
}

document.addEventListener('DOMContentLoaded', function() {
    setupTableSearch('search_rolls_table', 'roll-row');
    setupTableSearch('search_other_table', 'other-row');
    setupTableSearch('search_raw_table', 'raw-row');
    setupTableSearch('search_fg_table', 'fg-row');
    setupTableSearch('search_prod_table', 'prod-row');
    setupTableSearch('search_ret_table', 'ret-row');

    // Initialize previews
    updateRollPreview();
    updateOtherPreview();
    updateFgPreview();
    updateProdPreview();
    updateRetPreview();
});
</script>
