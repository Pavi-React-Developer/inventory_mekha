<?php
// app/views/concern/program_create.php - Create Production Program with Multi-Roll Allocation & Out-of-Qty Tracking
?>
<div class="container-fluid px-0">
    <!-- Breadcrumbs matching Image 2 -->
    <nav aria-label="breadcrumb" class="mb-2">
        <ol class="breadcrumb small mb-1">
            <li class="breadcrumb-item"><a href="<?= \App\Core\Controller::url('/') ?>" class="text-decoration-none text-muted">Home</a></li>
            <li class="breadcrumb-item text-muted">Production</li>
            <li class="breadcrumb-item"><a href="<?= \App\Core\Controller::url('/production/catalog?tab=programs') ?>" class="text-decoration-none text-muted">Programs</a></li>
            <li class="breadcrumb-item active fw-semibold text-dark" aria-current="page">Create Program</li>
        </ol>
    </nav>

    <!-- Header Section matching Image 2 -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3">
            <div class="p-2.5 rounded-3 bg-primary-subtle text-primary border border-primary-subtle shadow-xs">
                <i class="bi bi-calendar-plus fs-4"></i>
            </div>
            <div>
                <h4 class="fw-bold mb-0 text-dark">Create Production Program</h4>
                <p class="text-secondary small mb-0">Set program name, assign multiple raw material rolls with individual 3-tier catalog category hierarchies, and configure schedule dates.</p>
            </div>
        </div>
        <div>
            <a href="<?= \App\Core\Controller::url('/production/catalog?tab=programs') ?>" class="btn btn-outline-secondary rounded-3 d-inline-flex align-items-center gap-1.5 shadow-sm fw-semibold">
                <i class="bi bi-arrow-left"></i> Back to Programs
            </a>
        </div>
    </div>

    <!-- Main Form matching Image 2 layout -->
    <form action="<?= \App\Core\Controller::url('/production/programs/store') ?>" method="POST" id="formCreateProgram" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">

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
                        <small class="text-secondary" style="font-size: 0.78rem;">Define the unique program title and reference code.</small>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-dark mb-1">Program Name <span class="text-danger">*</span></label>
                            <input type="text" name="program_name" id="inputProgramName" class="form-control" placeholder="E.G. SUMMER MATERNITY COLLECTION 2026" required autofocus>
                            <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">Enter a descriptive title for this manufacturing run.</small>
                            <div class="invalid-feedback">Program title is required (minimum 3 characters).</div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-dark mb-1">Program Code / Number <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted font-monospace">#</span>
                                    <input type="text" name="program_no" id="inputProgramNo" class="form-control font-monospace fw-bold bg-light" value="<?= htmlspecialchars($suggestedCode) ?>" readonly required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-dark mb-1">Person Name (Planner / Creator) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted"><i class="bi bi-person-fill"></i></span>
                                    <input type="text" name="planner_name" class="form-control bg-light fw-semibold" value="<?= htmlspecialchars($plannerName) ?>" readonly required>
                                </div>
                                <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">Person creating or planning this production program.</small>
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
                        
                        <!-- Scanner Banner matching Image 2 -->
                        <div class="p-3 rounded-3 border mb-4" style="background: #f0fdf4; border-color: #bbf7d0 !important;">
                            <div class="d-flex justify-content-between align-items-center mb-1.5 flex-wrap gap-1">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-upc-scan text-success fs-5"></i>
                                    <span class="fw-bold small text-dark">Scan Fabric Roll Barcode / QR Tag</span>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-0.5" style="font-size: 0.68rem;">Auto-Add Ready</span>
                                </div>
                                <small class="text-muted" style="font-size: 0.72rem;">Hardware Gun & Camera Scanner (e.g. FB1126141, ROLL #1)</small>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0 text-success"><i class="bi bi-qr-code-scan"></i></span>
                                <input type="text" class="form-control border-start-0 ps-0" id="scanRollInput" placeholder="Scan roll QR or type piece tag (e.g. FB1126141) and press Enter to auto-add..." autocomplete="off">
                                <button type="button" class="btn btn-success fw-semibold px-3" id="btnTriggerScan">
                                    <i class="bi bi-check2-circle me-1"></i> Scan / Add
                                </button>
                            </div>
                            <div class="small text-danger mt-1 d-none" id="scanErrorMsg"></div>
                        </div>

                        <!-- Dynamic Rolls Container -->
                        <div id="rollCardsContainer" class="d-flex flex-column gap-3">
                            <!-- Populated dynamically via JavaScript with Roll #1 -->
                        </div>

                        <div class="small text-danger mt-2 d-none" id="rollValidationMsg">
                            <i class="bi bi-exclamation-circle me-1"></i> Please assign at least one raw material fabric roll with valid quantity.
                        </div>
                    </div>
                </div>

            </div>

            <!-- ========================================== -->
            <!-- RIGHT COLUMN: SECTION 3 TIMELINE           -->
            <!-- (SECTION 4 OMITTED AS HIGHLIGHTED IN RED)  -->
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
                            <input type="date" name="start_date" id="inputStartDate" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold text-dark mb-1">Target Completion / End Date <span class="text-danger">*</span></label>
                            <input type="date" name="target_date" id="inputTargetDate" class="form-control" value="<?= date('Y-m-d', strtotime('+14 days')) ?>" required>
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

        <!-- BOTTOM ACTIONS BAR matching Image 2 -->
        <div class="d-flex justify-content-between align-items-center p-3 bg-white rounded-3 shadow-sm border mt-2">
            <a href="<?= \App\Core\Controller::url('/production/catalog?tab=programs') ?>" class="btn btn-light border px-4 py-2 fw-semibold text-secondary">
                Cancel
            </a>
            <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold shadow-sm d-inline-flex align-items-center gap-2" id="btnSaveProgram">
                <i class="bi bi-check2-circle fs-5"></i> Save Production Program
            </button>
        </div>
    </form>
</div>

<script>
// Available Rolls JSON data from backend with out-of-qty calculations
const availableRollsData = <?= json_encode($availableRolls, JSON_UNESCAPED_UNICODE) ?>;
const categoriesData = <?= json_encode($categories, JSON_UNESCAPED_UNICODE) ?>;
const catalogsData = <?= json_encode($catalogs, JSON_UNESCAPED_UNICODE) ?>;

let rollCardCounter = 0;

function createRollCardHtml(index, preselectedRollId = null) {
    let rollOptionsHtml = '<option value="">-- Select a Purchased Roll --</option>';
    availableRollsData.forEach(r => {
        const isSel = (preselectedRollId && preselectedRollId == r.id) ? 'selected' : '';
        const tag = r.item_code ? `[${r.item_code}] ` : '';
        const sup = r.supplier_name ? `(${r.supplier_name}) ` : '';
        rollOptionsHtml += `
            <option value="${r.id}" 
                    data-code="${r.item_code || ''}" 
                    data-name="${r.item_name || ''}" 
                    data-color="${r.color || ''}" 
                    data-unit="${r.unit || 'kg'}" 
                    data-total="${r.total_qty}" 
                    data-allocated="${r.allocated_qty}" 
                    data-remaining="${r.remaining_qty}"
                    ${isSel}>
                ${tag}${r.item_name} - ${r.color || ''} ${sup}• Available: ${r.remaining_qty.toFixed(2)} ${r.unit} out of ${r.total_qty.toFixed(2)} ${r.unit}
            </option>
        `;
    });

    let catOptionsHtml = '<option value="">-- Select Main Category --</option>';
    categoriesData.forEach(c => {
        catOptionsHtml += `<option value="${c.id}" data-name="${c.name}">${c.name}</option>`;
    });

    let catlgOptionsHtml = '<option value="">-- Select Product Style (Optional) --</option>';
    catalogsData.forEach(cl => {
        catlgOptionsHtml += `<option value="${cl.id}">${cl.style_code ? `[${cl.style_code}] ` : ''}${cl.name}</option>`;
    });

    return `
        <div class="card border rounded-3 p-3 bg-white roll-card shadow-xs" id="rollCard_${index}" data-index="${index}">
            <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                <span class="badge bg-primary font-monospace px-2.5 py-1">Roll #${index}</span>
                <button type="button" class="btn btn-sm btn-outline-danger py-0.5 px-2 border-0 btn-remove-roll" title="Remove this roll">
                    <i class="bi bi-trash me-1"></i> Remove
                </button>
            </div>

            <!-- Roll Selection Dropdown -->
            <div class="mb-3">
                <label class="form-label small fw-semibold text-dark mb-1">Select Purchased Fabric Roll <span class="text-danger">*</span></label>
                <select name="rolls[${index}][purchase_bill_item_id]" class="form-select select-roll-item" required>
                    ${rollOptionsHtml}
                </select>
            </div>

            <!-- Dynamic Out-of-Qty Badge -->
            <div class="roll-balance-banner p-2 rounded-2 mb-3 bg-light border small text-muted d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-1.5">
                    <i class="bi bi-info-circle-fill text-primary"></i>
                    <span class="roll-balance-text">Select a purchased fabric roll above to view available balance.</span>
                </div>
                <span class="badge bg-secondary-subtle text-secondary border roll-out-of-badge">Out of Qty</span>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold text-dark mb-1">Allocated Qty <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="number" step="0.01" min="0.01" name="rolls[${index}][allocated_qty]" class="form-control input-allocated-qty" placeholder="e.g. 50.00" required>
                        <span class="input-group-text bg-white text-muted roll-unit-text">kg</span>
                    </div>
                    <small class="text-danger small d-none roll-exceed-msg" style="font-size: 0.72rem;">Allocated qty exceeds available roll balance!</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold text-dark mb-1">Target Production Pieces</label>
                    <div class="input-group">
                        <input type="number" min="0" step="1" name="rolls[${index}][target_pieces]" class="form-control input-target-pieces" placeholder="e.g. 250">
                        <span class="input-group-text bg-white text-muted">pcs</span>
                    </div>
                </div>
            </div>

            <!-- 3-Tier Catalog Hierarchy per Roll -->
            <div class="border-top pt-2.5 mt-1">
                <label class="form-label small fw-bold text-secondary mb-2 d-flex align-items-center gap-1">
                    <i class="bi bi-diagram-3 text-primary"></i> 3-Tier Catalog Hierarchy for this Roll:
                </label>
                <div class="row g-2 mb-2">
                    <div class="col-md-4">
                        <select name="rolls[${index}][category_id]" class="form-select form-select-sm select-roll-cat" required>
                            ${catOptionsHtml}
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select name="rolls[${index}][subcategory_id]" class="form-select form-select-sm select-roll-subcat">
                            <option value="">-- Subcategory --</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select name="rolls[${index}][sub_sub_category_id]" class="form-select form-select-sm select-roll-subsubcat">
                            <option value="">-- Sub-Subcategory --</option>
                        </select>
                    </div>
                </div>
                <div class="col-12">
                    <select name="rolls[${index}][catalog_id]" class="form-select form-select-sm select-roll-catalog">
                        ${catlgOptionsHtml}
                    </select>
                </div>
            </div>
        </div>
    `;
}

document.addEventListener('DOMContentLoaded', function() {
    const rollCardsContainer = document.getElementById('rollCardsContainer');
    const scanRollInput = document.getElementById('scanRollInput');
    const scanErrorMsg = document.getElementById('scanErrorMsg');
    const rollValidationMsg = document.getElementById('rollValidationMsg');

    // Add first roll card initially
    addRollCard();

    function addRollCard(preselectedRollId = null) {
        rollCardCounter++;
        const cardHtml = createRollCardHtml(rollCardCounter, preselectedRollId);
        rollCardsContainer.insertAdjacentHTML('beforeend', cardHtml);
        const cardElement = document.getElementById(`rollCard_${rollCardCounter}`);
        bindRollCardEvents(cardElement);

        if (preselectedRollId) {
            const selectEl = cardElement.querySelector('.select-roll-item');
            selectEl.dispatchEvent(new Event('change'));
        }
        updateSummary();
    }

    document.getElementById('btnAddRollCard').addEventListener('click', function() {
        addRollCard();
    });

    document.getElementById('btnFocusScanner').addEventListener('click', function() {
        scanRollInput.focus();
        scanRollInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });

    function bindRollCardEvents(card) {
        const rollSelect = card.querySelector('.select-roll-item');
        const balanceBanner = card.querySelector('.roll-balance-banner');
        const balanceText = card.querySelector('.roll-balance-text');
        const outOfBadge = card.querySelector('.roll-out-of-badge');
        const unitText = card.querySelector('.roll-unit-text');
        const allocatedInput = card.querySelector('.input-allocated-qty');
        const targetPiecesInput = card.querySelector('.input-target-pieces');
        const exceedMsg = card.querySelector('.roll-exceed-msg');
        const removeBtn = card.querySelector('.btn-remove-roll');

        const catSelect = card.querySelector('.select-roll-cat');
        const subcatSelect = card.querySelector('.select-roll-subcat');
        const subsubcatSelect = card.querySelector('.select-roll-subsubcat');

        // Roll selection change -> update "Out of Qty" banner
        rollSelect.addEventListener('change', function() {
            const opt = this.selectedOptions[0];
            if (!opt || !opt.value) {
                balanceText.textContent = 'Select a purchased fabric roll above to view available balance.';
                outOfBadge.textContent = 'Out of Qty';
                outOfBadge.className = 'badge bg-secondary-subtle text-secondary border roll-out-of-badge';
                allocatedInput.value = '';
                exceedMsg.classList.add('d-none');
                allocatedInput.classList.remove('is-invalid');
                updateSummary();
                return;
            }

            const remaining = parseFloat(opt.getAttribute('data-remaining') || '0');
            const total = parseFloat(opt.getAttribute('data-total') || '0');
            const allocatedAlready = parseFloat(opt.getAttribute('data-allocated') || '0');
            const unit = opt.getAttribute('data-unit') || 'kg';

            unitText.textContent = unit;

            balanceText.innerHTML = `
                Available Roll Balance: <strong class="text-primary">${remaining.toFixed(2)} ${unit}</strong> 
                out of <strong>${total.toFixed(2)} ${unit}</strong> 
                (${allocatedAlready.toFixed(2)} ${unit} already used in other runs).
            `;

            outOfBadge.textContent = `${remaining.toFixed(2)} / ${total.toFixed(2)} ${unit}`;
            outOfBadge.className = remaining > 0 ? 'badge bg-info-subtle text-info-emphasis border border-info-subtle roll-out-of-badge' : 'badge bg-danger-subtle text-danger border border-danger-subtle roll-out-of-badge';

            // Auto-fill allocated input with remaining if empty
            if (!allocatedInput.value || parseFloat(allocatedInput.value) <= 0) {
                allocatedInput.value = remaining > 0 ? remaining.toFixed(2) : '0.00';
            }
            validateRollAllocation(allocatedInput, remaining, exceedMsg);
            updateSummary();
        });

        // Allocated Qty input change -> live validation against remaining balance
        allocatedInput.addEventListener('input', function() {
            const opt = rollSelect.selectedOptions[0];
            const remaining = opt ? parseFloat(opt.getAttribute('data-remaining') || '0') : 0;
            validateRollAllocation(this, remaining, exceedMsg);
            updateSummary();
        });

        targetPiecesInput.addEventListener('input', updateSummary);

        // 3-Tier category cascade
        catSelect.addEventListener('change', function() {
            const catId = this.value;
            subcatSelect.innerHTML = '<option value="">Loading...</option>';
            subsubcatSelect.innerHTML = '<option value="">-- Sub-Subcategory --</option>';

            if (!catId) {
                subcatSelect.innerHTML = '<option value="">-- Subcategory --</option>';
                return;
            }

            fetch('<?= \App\Core\Controller::url('/api/categories/subcategories?category_id=') ?>' + catId)
                .then(r => r.json())
                .then(res => {
                    if (res.success && res.data.length > 0) {
                        let html = '<option value="">-- Select Subcategory --</option>';
                        res.data.forEach(item => {
                            html += `<option value="${item.id}">${item.name}</option>`;
                        });
                        subcatSelect.innerHTML = html;
                    } else {
                        subcatSelect.innerHTML = '<option value="">-- None --</option>';
                    }
                });
        });

        subcatSelect.addEventListener('change', function() {
            const subId = this.value;
            subsubcatSelect.innerHTML = '<option value="">Loading...</option>';

            if (!subId) {
                subsubcatSelect.innerHTML = '<option value="">-- Sub-Subcategory --</option>';
                return;
            }

            fetch('<?= \App\Core\Controller::url('/api/categories/subsubcategories?subcategory_id=') ?>' + subId)
                .then(r => r.json())
                .then(res => {
                    if (res.success && res.data.length > 0) {
                        let html = '<option value="">-- Select Sub-Subcategory --</option>';
                        res.data.forEach(item => {
                            html += `<option value="${item.id}">${item.name}</option>`;
                        });
                        subsubcatSelect.innerHTML = html;
                    } else {
                        subsubcatSelect.innerHTML = '<option value="">-- None --</option>';
                    }
                });
        });

        // Remove roll button
        removeBtn.addEventListener('click', function() {
            const totalCards = rollCardsContainer.querySelectorAll('.roll-card').length;
            if (totalCards <= 1) {
                alert('A production program must have at least one roll assigned.');
                return;
            }
            card.remove();
            renumberRollCards();
            updateSummary();
        });
    }

    function validateRollAllocation(input, remaining, msgEl) {
        const val = parseFloat(input.value);
        if (isNaN(val) || val <= 0) {
            input.classList.add('is-invalid');
            msgEl.textContent = 'Allocated quantity must be greater than zero.';
            msgEl.classList.remove('d-none');
            return false;
        } else if (val > (remaining + 0.001)) {
            input.classList.add('is-invalid');
            msgEl.textContent = `Exceeds available roll balance! Maximum allowed: ${remaining.toFixed(2)}`;
            msgEl.classList.remove('d-none');
            return false;
        } else {
            input.classList.remove('is-invalid');
            msgEl.classList.add('d-none');
            return true;
        }
    }

    function renumberRollCards() {
        const cards = rollCardsContainer.querySelectorAll('.roll-card');
        cards.forEach((c, idx) => {
            const num = idx + 1;
            c.querySelector('.badge.bg-primary').textContent = `Roll #${num}`;
        });
    }

    function updateSummary() {
        const cards = rollCardsContainer.querySelectorAll('.roll-card');
        let totalRolls = 0;
        let totalQty = 0;
        let totalPieces = 0;

        cards.forEach(c => {
            const rollSelect = c.querySelector('.select-roll-item');
            const qtyInput = c.querySelector('.input-allocated-qty');
            const piecesInput = c.querySelector('.input-target-pieces');

            if (rollSelect && rollSelect.value) {
                totalRolls++;
                const q = parseFloat(qtyInput.value) || 0;
                totalQty += q;
            }
            const p = parseInt(piecesInput.value, 10) || 0;
            totalPieces += p;
        });

        document.getElementById('summaryTotalRolls').textContent = totalRolls;
        document.getElementById('summaryTotalQty').textContent = `${totalQty.toFixed(2)} kg`;
        document.getElementById('summaryTotalPieces').textContent = `${totalPieces} pcs`;

        if (totalRolls > 0) {
            rollValidationMsg.classList.add('d-none');
        }
    }

    // ==========================================
    // BARCODE / QR SCANNER ACTION
    // ==========================================
    function handleScanLookup() {
        const tag = scanRollInput.value.trim();
        if (!tag) return;

        scanErrorMsg.classList.add('d-none');

        fetch('<?= \App\Core\Controller::url('/api/programs/roll-lookup?tag=') ?>' + encodeURIComponent(tag))
            .then(r => r.json())
            .then(res => {
                if (res.success && res.roll) {
                    const roll = res.roll;
                    // Check if already in the list
                    let existingEmptyCard = null;
                    const cards = rollCardsContainer.querySelectorAll('.roll-card');
                    cards.forEach(c => {
                        const sel = c.querySelector('.select-roll-item');
                        if (!sel.value && !existingEmptyCard) {
                            existingEmptyCard = c;
                        }
                    });

                    if (existingEmptyCard) {
                        const sel = existingEmptyCard.querySelector('.select-roll-item');
                        sel.value = roll.id;
                        sel.dispatchEvent(new Event('change'));
                        existingEmptyCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    } else {
                        addRollCard(roll.id);
                    }
                    scanRollInput.value = '';
                    scanRollInput.focus();
                } else {
                    scanErrorMsg.textContent = res.message || 'Roll piece tag not found.';
                    scanErrorMsg.classList.remove('d-none');
                }
            })
            .catch(() => {
                scanErrorMsg.textContent = 'Network error looking up roll.';
                scanErrorMsg.classList.remove('d-none');
            });
    }

    document.getElementById('btnTriggerScan').addEventListener('click', handleScanLookup);
    scanRollInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            handleScanLookup();
        }
    });

    // ==========================================
    // FINAL FORM VALIDATION
    // ==========================================
    document.getElementById('formCreateProgram').addEventListener('submit', function(e) {
        let valid = true;

        const nameInput = document.getElementById('inputProgramName');
        if (!nameInput.value.trim() || nameInput.value.trim().length < 3) {
            nameInput.classList.add('is-invalid');
            valid = false;
        } else {
            nameInput.classList.remove('is-invalid');
        }

        const startDate = document.getElementById('inputStartDate').value;
        const targetDate = document.getElementById('inputTargetDate').value;
        if (new Date(targetDate) < new Date(startDate)) {
            document.getElementById('inputTargetDate').classList.add('is-invalid');
            valid = false;
        } else {
            document.getElementById('inputTargetDate').classList.remove('is-invalid');
        }

        const cards = rollCardsContainer.querySelectorAll('.roll-card');
        if (cards.length === 0) {
            rollValidationMsg.classList.remove('d-none');
            valid = false;
        }

        let hasValidRoll = false;
        cards.forEach(c => {
            const sel = c.querySelector('.select-roll-item');
            const qtyInput = c.querySelector('.input-allocated-qty');
            const exceedMsg = c.querySelector('.roll-exceed-msg');

            if (!sel.value) {
                sel.classList.add('is-invalid');
                valid = false;
            } else {
                sel.classList.remove('is-invalid');
                const opt = sel.selectedOptions[0];
                const rem = parseFloat(opt.getAttribute('data-remaining') || '0');
                if (!validateRollAllocation(qtyInput, rem, exceedMsg)) {
                    valid = false;
                } else {
                    hasValidRoll = true;
                }
            }
        });

        if (!hasValidRoll) {
            rollValidationMsg.classList.remove('d-none');
            valid = false;
        }

        if (!valid) {
            e.preventDefault();
            e.stopPropagation();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    });
});
</script>
