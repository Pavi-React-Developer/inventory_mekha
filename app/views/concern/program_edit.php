<?php
// app/views/concern/program_edit.php - Edit Production Program with Multi-Roll Allocation & Out-of-Qty Tracking
?>
<div class="container-fluid px-0">
    <!-- Breadcrumbs -->
    <nav aria-label="breadcrumb" class="mb-2">
        <ol class="breadcrumb small mb-1">
            <li class="breadcrumb-item"><a href="<?= \App\Core\Controller::url('/') ?>" class="text-decoration-none text-muted">Home</a></li>
            <li class="breadcrumb-item text-muted">Production</li>
            <li class="breadcrumb-item"><a href="<?= \App\Core\Controller::url('/production/catalog?tab=programs') ?>" class="text-decoration-none text-muted">Programs</a></li>
            <li class="breadcrumb-item active fw-semibold text-dark" aria-current="page">Edit Program</li>
        </ol>
    </nav>

    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3">
            <div class="p-2.5 rounded-3 bg-primary-subtle text-primary border border-primary-subtle shadow-xs">
                <i class="bi bi-pencil-square fs-4"></i>
            </div>
            <div>
                <h4 class="fw-bold mb-0 text-dark">Edit Production Program</h4>
                <p class="text-secondary small mb-0">Update program parameters, adjust roll allocations with live out-of-qty tracking, and manage lifecycle status.</p>
            </div>
        </div>
        <div>
            <a href="<?= \App\Core\Controller::url('/production/catalog?tab=programs') ?>" class="btn btn-outline-secondary rounded-3 d-inline-flex align-items-center gap-1.5 shadow-sm fw-semibold">
                <i class="bi bi-arrow-left"></i> Back to Programs
            </a>
        </div>
    </div>

    <!-- Main Edit Form -->
    <form action="<?= \App\Core\Controller::url('/production/programs/update') ?>" method="POST" id="formEditProgram" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
        <input type="hidden" name="id" value="<?= (int)$program['id'] ?>">

        <div class="row g-4">
            <!-- ========================================== -->
            <!-- LEFT COLUMN: SECTION 1 & SECTION 2         -->
            <!-- ========================================== -->
            <div class="col-lg-7">
                
                <!-- 1. PROGRAM OVERVIEW -->
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-tag-fill text-primary"></i>
                            <h6 class="mb-0 fw-bold text-dark">1. Program Overview</h6>
                        </div>
                        <small class="text-secondary" style="font-size: 0.78rem;">Define the unique program title, code, and status.</small>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-dark mb-1">Program Name <span class="text-danger">*</span></label>
                            <input type="text" name="program_name" id="inputProgramName" class="form-control" value="<?= htmlspecialchars($program['program_name'] ?? '') ?>" placeholder="E.G. SUMMER MATERNITY COLLECTION 2026" required autofocus>
                            <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">Descriptive title for this manufacturing run.</small>
                            <div class="invalid-feedback">Program title is required (minimum 3 characters).</div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold text-dark mb-1">Program Code / Number <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted font-monospace">#</span>
                                    <input type="text" name="program_no" id="inputProgramNo" class="form-control font-monospace fw-bold bg-light" value="<?= htmlspecialchars($program['program_no']) ?>" readonly required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold text-dark mb-1">Planner / Creator <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted"><i class="bi bi-person-fill"></i></span>
                                    <input type="text" name="planner_name" class="form-control bg-light fw-semibold" value="<?= htmlspecialchars($program['planner_name'] ?? 'System Administrator') ?>" readonly required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold text-dark mb-1">Lifecycle Status <span class="text-danger">*</span></label>
                                <select name="status" class="form-select fw-semibold" required>
                                    <option value="planned" <?= $program['status'] === 'planned' ? 'selected' : '' ?>>Planned</option>
                                    <option value="in_progress" <?= $program['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                    <option value="completed" <?= $program['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                    <option value="cancelled" <?= $program['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. RAW MATERIAL ROLLS & PER-ROLL CATALOG HIERARCHY -->
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-layers-fill text-primary"></i>
                                <h6 class="mb-0 fw-bold text-dark">2. Raw Material Rolls & Per-Roll Catalog Hierarchy</h6>
                            </div>
                            <small class="text-secondary" style="font-size: 0.78rem;">Assign fabric rolls and configure 3-tier catalog categories for each roll.</small>
                        </div>
                        <div class="d-flex align-items-center gap-1.5">
                            <button type="button" class="btn btn-sm btn-outline-success rounded-3 fw-semibold" id="btnFocusScanner">
                                <i class="bi bi-upc-scan me-1"></i> Scan Roll
                            </button>
                            <button type="button" class="btn btn-sm btn-primary rounded-3 fw-semibold shadow-xs" id="btnAddRollCard">
                                <i class="bi bi-plus-circle me-1"></i> Add Roll
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        
                        <!-- BARCODE SCANNER BAR -->
                        <div class="p-3 mb-4 rounded-3 border border-success-subtle bg-success-subtle bg-opacity-10">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-upc-scan text-success fs-5"></i>
                                    <strong class="text-dark small">Scan Fabric Roll Barcode / QR Tag</strong>
                                    <span class="badge bg-success text-white py-0.5 px-2" style="font-size: 0.7rem;">Auto-Add Ready</span>
                                </div>
                                <span class="small text-muted" style="font-size: 0.75rem;">Hardware Gun & Camera Scanner</span>
                            </div>
                            <div class="input-group">
                                <input type="text" id="scannerInput" class="form-control" placeholder="Scan roll QR or type piece tag (e.g. FB1126141) and press Enter to auto-add..." autocomplete="off">
                                <button type="button" class="btn btn-success px-3 fw-semibold" id="btnTriggerScan">
                                    <i class="bi bi-qr-code-scan me-1"></i> Scan / Add
                                </button>
                            </div>
                            <div id="scannerFeedback" class="small mt-1 text-danger d-none"></div>
                        </div>

                        <!-- CONTAINER FOR DYNAMIC ROLL CARDS -->
                        <div id="rollsContainer" class="d-flex flex-column gap-3">
                            <!-- Populated dynamically via JS from existingRolls -->
                        </div>

                    </div>
                </div>

            </div>

            <!-- ========================================== -->
            <!-- RIGHT COLUMN: SECTION 3 & SUMMARY CARD     -->
            <!-- ========================================== -->
            <div class="col-lg-5">
                
                <!-- 3. PROGRAMMING TIMELINE & SCHEDULING -->
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-calendar-range-fill text-primary"></i>
                            <h6 class="mb-0 fw-bold text-dark">3. Programming Timeline & Scheduling</h6>
                        </div>
                        <small class="text-secondary" style="font-size: 0.78rem;">Specify the manufacturing start and completion target dates.</small>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-dark mb-1">Programming Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" id="inputStartDate" class="form-control" value="<?= htmlspecialchars($program['start_date']) ?>" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold text-dark mb-1">Target Completion / End Date <span class="text-danger">*</span></label>
                            <input type="date" name="target_date" id="inputTargetDate" class="form-control" value="<?= htmlspecialchars($program['target_date']) ?>" required>
                            <div class="invalid-feedback">Target date cannot precede start date.</div>
                        </div>
                    </div>
                </div>

                <!-- LIVE FABRIC ROLLS SUMMARY CARD -->
                <div class="card border-0 shadow-sm rounded-3 mb-4 bg-light">
                    <div class="card-header bg-transparent py-3 border-bottom">
                        <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                            <i class="bi bi-calculator text-primary"></i> Program Allocation Summary
                        </h6>
                    </div>
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <span class="small text-secondary">Total Rolls Assigned:</span>
                            <span class="fw-bold font-monospace text-dark" id="summaryTotalRolls">0</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <span class="small text-secondary">Total Allocated Qty:</span>
                            <span class="fw-bold font-monospace text-primary fs-6" id="summaryTotalQty">0.00 kg</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center py-2">
                            <span class="small text-secondary">Target Production Pieces:</span>
                            <span class="fw-bold font-monospace text-success fs-6" id="summaryTotalPieces">0 pcs</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- BOTTOM ACTIONS BAR -->
        <div class="d-flex justify-content-between align-items-center p-3 bg-white rounded-3 shadow-sm border mt-2">
            <a href="<?= \App\Core\Controller::url('/production/catalog?tab=programs') ?>" class="btn btn-light border px-4 py-2 fw-semibold text-secondary">
                Cancel
            </a>
            <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold shadow-sm d-inline-flex align-items-center gap-2" id="btnSaveProgram">
                <i class="bi bi-check2-circle fs-5"></i> Update Production Program
            </button>
        </div>
    </form>
</div>

<script>
// Data from server
const AVAILABLE_ROLLS = <?= json_encode($availableRolls) ?>;
const CATEGORIES = <?= json_encode($categories) ?>;
const SUBCATEGORIES = <?= json_encode($subcategories ?? []) ?>;
const SUBSUBCATEGORIES = <?= json_encode($subSubcategories ?? []) ?>;
const CATALOGS = <?= json_encode($catalogs) ?>;
const EXISTING_ROLLS = <?= json_encode($existingRolls) ?>;

let rollCardCounter = 0;

function createRollCardHtml(index, rollData = null) {
    const cardId = `rollCard_${index}`;
    const selectedRollId = rollData ? rollData.purchase_bill_item_id : '';
    const allocatedQtyVal = rollData ? parseFloat(rollData.allocated_qty) : '';
    const targetPiecesVal = rollData ? parseInt(rollData.target_pieces) : 0;
    const selectedCatId = rollData ? rollData.category_id : '';
    const selectedSubcatId = rollData ? rollData.subcategory_id : '';
    const selectedSubSubId = rollData ? rollData.sub_sub_category_id : '';
    const selectedCatalogId = rollData ? rollData.catalog_id : '';

    let rollOptionsHtml = '<option value="">-- Select Purchased Fabric Roll --</option>';
    let selectedRollObject = null;

    AVAILABLE_ROLLS.forEach(r => {
        const isSelected = selectedRollId && (parseInt(r.id) === parseInt(selectedRollId));
        if (isSelected) selectedRollObject = r;
        const colorText = r.color ? ` - ${r.color}` : '';
        const tagText = r.item_code ? `[${r.item_code}] ` : '';
        rollOptionsHtml += `<option value="${r.id}" ${isSelected ? 'selected' : ''} data-remaining="${r.remaining_qty}" data-total="${r.total_qty}" data-unit="${r.unit}" data-color="${r.color || ''}" data-name="${r.item_name || ''}">
            ${tagText}${r.item_name}${colorText} (${r.out_of_label})
        </option>`;
    });

    let catOptionsHtml = '<option value="">-- Select Main Category --</option>';
    CATEGORIES.forEach(c => {
        const sel = (selectedCatId && parseInt(c.id) === parseInt(selectedCatId)) ? 'selected' : '';
        catOptionsHtml += `<option value="${c.id}" ${sel}>${c.name} (${c.code || 'CAT'})</option>`;
    });

    let catalogOptionsHtml = '<option value="">-- Select Catalog Style (Optional) --</option>';
    CATALOGS.forEach(cat => {
        const sel = (selectedCatalogId && parseInt(cat.id) === parseInt(selectedCatalogId)) ? 'selected' : '';
        catalogOptionsHtml += `<option value="${cat.id}" ${sel}>[${cat.style_code}] ${cat.name}</option>`;
    });

    return `
    <div class="card border rounded-3 p-3 position-relative roll-card shadow-xs" id="${cardId}" data-index="${index}">
        <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary text-white font-monospace px-2 py-1 roll-index-badge">Roll #${index + 1}</span>
                <span class="badge bg-light text-dark border font-monospace roll-tag-badge" id="tagBadge_${index}">${rollData ? (rollData.roll_piece_tag || 'Purchased Roll') : 'New Roll'}</span>
            </div>
            <button type="button" class="btn btn-outline-danger btn-sm py-1 px-2.5 rounded-2 btn-remove-roll" onclick="removeRollCard('${cardId}')">
                <i class="bi bi-trash me-1"></i> Remove
            </button>
        </div>

        <div class="row g-3">
            <div class="col-md-7">
                <label class="form-label small fw-semibold text-dark mb-1">Select Purchased Fabric Roll <span class="text-danger">*</span></label>
                <select name="rolls[${index}][purchase_bill_item_id]" class="form-select form-select-sm select-roll-item" id="rollSelect_${index}" onchange="handleRollSelectionChange(${index})" required>
                    ${rollOptionsHtml}
                </select>
                <div class="mt-1.5 d-flex align-items-center gap-1.5" id="outOfQtyContainer_${index}">
                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle py-1 px-2 out-of-qty-badge font-monospace" id="outOfQtyBadge_${index}">
                        ${selectedRollObject ? selectedRollObject.out_of_label + ' available' : 'Select a roll to view out of qty balance'}
                    </span>
                </div>
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-semibold text-dark mb-1">Allocated Qty <span class="text-danger">*</span></label>
                <div class="input-group input-group-sm">
                    <input type="number" step="0.01" min="0.01" name="rolls[${index}][allocated_qty]" id="allocatedQty_${index}" class="form-control font-monospace fw-bold input-allocated-qty" value="${allocatedQtyVal}" placeholder="0.00" oninput="validateRollAllocatedQty(${index})" required>
                    <span class="input-group-text roll-unit-label" id="unitLabel_${index}">${selectedRollObject ? selectedRollObject.unit : 'kg'}</span>
                </div>
                <div class="text-danger small mt-0.5 d-none qty-error-feedback" id="qtyError_${index}"></div>
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-semibold text-dark mb-1">Target Pcs</label>
                <input type="number" min="0" step="1" name="rolls[${index}][target_pieces]" id="targetPieces_${index}" class="form-control form-control-sm font-monospace input-target-pieces" value="${targetPiecesVal}" oninput="recalcSummary()">
            </div>

            <!-- 3-TIER CATEGORY HIERARCHY -->
            <div class="col-12 mt-2 pt-2 border-top">
                <div class="small fw-semibold text-secondary mb-2 d-flex align-items-center gap-1">
                    <i class="bi bi-diagram-3 text-primary"></i> 3-Tier Catalog Category Configuration (This Roll)
                </div>
                <div class="row g-2">
                    <div class="col-md-3">
                        <select name="rolls[${index}][category_id]" id="catSelect_${index}" class="form-select form-select-sm select-category" onchange="loadSubcategoriesForRoll(${index})">
                            ${catOptionsHtml}
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="rolls[${index}][subcategory_id]" id="subcatSelect_${index}" class="form-select form-select-sm select-subcategory" onchange="loadSubSubcategoriesForRoll(${index})">
                            <option value="">-- Subcategory --</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="rolls[${index}][sub_sub_category_id]" id="subsubSelect_${index}" class="form-select form-select-sm select-subsubcategory">
                            <option value="">-- Sub-Subcategory --</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="rolls[${index}][catalog_id]" id="catalogSelect_${index}" class="form-select form-select-sm select-catalog">
                            ${catalogOptionsHtml}
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
    `;
}

function addRollCard(rollData = null) {
    const container = document.getElementById('rollsContainer');
    const index = rollCardCounter++;
    container.insertAdjacentHTML('beforeend', createRollCardHtml(index, rollData));
    
    // If existing subcategory/sub-sub were selected, populate them
    if (rollData && rollData.category_id) {
        loadSubcategoriesForRoll(index, rollData.subcategory_id, rollData.sub_sub_category_id);
    }
    
    recalcSummary();
    renumberCards();
}

function removeRollCard(cardId) {
    const el = document.getElementById(cardId);
    if (!el) return;
    const cards = document.querySelectorAll('.roll-card');
    if (cards.length <= 1) {
        alert('A production program must have at least one assigned raw material roll.');
        return;
    }
    el.remove();
    renumberCards();
    recalcSummary();
}

function renumberCards() {
    const cards = document.querySelectorAll('.roll-card');
    cards.forEach((card, idx) => {
        const badge = card.querySelector('.roll-index-badge');
        if (badge) badge.textContent = `Roll #${idx + 1}`;
    });
}

function handleRollSelectionChange(index) {
    const select = document.getElementById(`rollSelect_${index}`);
    const selectedOption = select.options[select.selectedIndex];
    const badge = document.getElementById(`outOfQtyBadge_${index}`);
    const unitLabel = document.getElementById(`unitLabel_${index}`);
    const tagBadge = document.getElementById(`tagBadge_${index}`);
    const input = document.getElementById(`allocatedQty_${index}`);

    if (!selectedOption || !selectedOption.value) {
        badge.textContent = 'Select a roll to view out of qty balance';
        badge.className = 'badge bg-secondary-subtle text-secondary border py-1 px-2 font-monospace';
        tagBadge.textContent = 'Purchased Roll';
        unitLabel.textContent = 'kg';
        input.removeAttribute('max');
        return;
    }

    const remaining = parseFloat(selectedOption.dataset.remaining || 0);
    const total = parseFloat(selectedOption.dataset.total || 0);
    const unit = selectedOption.dataset.unit || 'kg';
    const tag = selectedOption.textContent.match(/\[(.*?)\]/);

    tagBadge.textContent = tag ? tag[1] : `Roll #${selectedOption.value}`;
    unitLabel.textContent = unit;
    badge.textContent = `${remaining.toFixed(2)} ${unit} available out of ${total.toFixed(2)} ${unit} total`;
    badge.className = 'badge bg-success-subtle text-success border border-success-subtle py-1 px-2 font-monospace';

    input.setAttribute('max', remaining);
    validateRollAllocatedQty(index);
}

function validateRollAllocatedQty(index) {
    const input = document.getElementById(`allocatedQty_${index}`);
    const select = document.getElementById(`rollSelect_${index}`);
    const errorDiv = document.getElementById(`qtyError_${index}`);
    const badge = document.getElementById(`outOfQtyBadge_${index}`);

    if (!input || !select) return;

    const val = parseFloat(input.value) || 0;
    const selectedOption = select.options[select.selectedIndex];
    if (!selectedOption || !selectedOption.value) return;

    const remaining = parseFloat(selectedOption.dataset.remaining || 0);
    const total = parseFloat(selectedOption.dataset.total || 0);
    const unit = selectedOption.dataset.unit || 'kg';

    if (val > remaining) {
        input.classList.add('is-invalid');
        errorDiv.classList.remove('d-none');
        errorDiv.textContent = `Allocated qty (${val} ${unit}) exceeds available balance (${remaining.toFixed(2)} ${unit} out of ${total.toFixed(2)} ${unit})!`;
        badge.className = 'badge bg-danger-subtle text-danger border border-danger-subtle py-1 px-2 font-monospace';
    } else {
        input.classList.remove('is-invalid');
        errorDiv.classList.add('d-none');
        badge.className = 'badge bg-success-subtle text-success border border-success-subtle py-1 px-2 font-monospace';
    }

    recalcSummary();
}

function recalcSummary() {
    let totalRolls = 0;
    let totalQty = 0;
    let totalPieces = 0;

    const cards = document.querySelectorAll('.roll-card');
    cards.forEach(card => {
        totalRolls++;
        const qtyInput = card.querySelector('.input-allocated-qty');
        const pcsInput = card.querySelector('.input-target-pieces');

        if (qtyInput) totalQty += parseFloat(qtyInput.value) || 0;
        if (pcsInput) totalPieces += parseInt(pcsInput.value) || 0;
    });

    document.getElementById('summaryTotalRolls').textContent = totalRolls;
    document.getElementById('summaryTotalQty').textContent = `${totalQty.toFixed(2)} kg`;
    document.getElementById('summaryTotalPieces').textContent = `${totalPieces} pcs`;
}

function loadSubcategoriesForRoll(index, preselectedSubId = null, preselectedSubSubId = null) {
    const catSelect = document.getElementById(`catSelect_${index}`);
    const subSelect = document.getElementById(`subcatSelect_${index}`);
    const subSubSelect = document.getElementById(`subsubSelect_${index}`);
    const catId = catSelect.value;

    subSelect.innerHTML = '<option value="">-- Subcategory --</option>';
    subSubSelect.innerHTML = '<option value="">-- Sub-Subcategory --</option>';

    if (!catId) return;

    fetch(`<?= \App\Core\Controller::url('/api/categories/subcategories?category_id=') ?>${catId}`)
        .then(r => r.json())
        .then(data => {
            if (Array.isArray(data)) {
                data.forEach(item => {
                    const sel = (preselectedSubId && parseInt(item.id) === parseInt(preselectedSubId)) ? 'selected' : '';
                    subSelect.innerHTML += `<option value="${item.id}" ${sel}>${item.name} (${item.code || ''})</option>`;
                });
                if (preselectedSubId) {
                    loadSubSubcategoriesForRoll(index, preselectedSubSubId);
                }
            }
        });
}

function loadSubSubcategoriesForRoll(index, preselectedSubSubId = null) {
    const subSelect = document.getElementById(`subcatSelect_${index}`);
    const subSubSelect = document.getElementById(`subsubSelect_${index}`);
    const subId = subSelect.value;

    subSubSelect.innerHTML = '<option value="">-- Sub-Subcategory --</option>';
    if (!subId) return;

    fetch(`<?= \App\Core\Controller::url('/api/categories/subsubcategories?subcategory_id=') ?>${subId}`)
        .then(r => r.json())
        .then(data => {
            if (Array.isArray(data)) {
                data.forEach(item => {
                    const sel = (preselectedSubSubId && parseInt(item.id) === parseInt(preselectedSubSubId)) ? 'selected' : '';
                    subSubSelect.innerHTML += `<option value="${item.id}" ${sel}>${item.name}</option>`;
                });
            }
        });
}

// Scanner handling
document.getElementById('btnTriggerScan').addEventListener('click', () => {
    const input = document.getElementById('scannerInput');
    const tag = input.value.trim();
    if (!tag) return;

    const feedback = document.getElementById('scannerFeedback');
    feedback.classList.add('d-none');

    fetch(`<?= \App\Core\Controller::url('/api/programs/roll-lookup?tag=') ?>${encodeURIComponent(tag)}`)
        .then(r => r.json())
        .then(res => {
            if (!res.success) {
                feedback.textContent = res.message || 'Roll piece tag not found.';
                feedback.classList.remove('d-none');
                return;
            }
            addRollCard({
                purchase_bill_item_id: res.roll.id,
                allocated_qty: res.roll.remaining_qty,
                target_pieces: 0,
                roll_piece_tag: res.roll.item_code
            });
            input.value = '';
            input.focus();
        })
        .catch(err => {
            feedback.textContent = 'Network error looking up roll.';
            feedback.classList.remove('d-none');
        });
});

document.getElementById('scannerInput').addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
        e.preventDefault();
        document.getElementById('btnTriggerScan').click();
    }
});

document.getElementById('btnAddRollCard').addEventListener('click', () => {
    addRollCard();
});

document.getElementById('btnFocusScanner').addEventListener('click', () => {
    const input = document.getElementById('scannerInput');
    input.focus();
    input.select();
});

// Initialize existing rolls from server
document.addEventListener('DOMContentLoaded', () => {
    if (EXISTING_ROLLS && EXISTING_ROLLS.length > 0) {
        EXISTING_ROLLS.forEach(r => {
            addRollCard(r);
        });
    } else {
        addRollCard();
    }
});
</script>
