<?php
/**
 * View: Edit Cutting Batch
 * Location: inventory_mekha/app/views/concern/cutting_edit.php
 */
?>
<div class="container-fluid px-4 py-3">

    <!-- BREADCRUMBS -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0 small">
            <li class="breadcrumb-item"><a href="<?= \App\Core\Controller::url('/dashboard') ?>" class="text-decoration-none text-muted">Home</a></li>
            <li class="breadcrumb-item"><a href="<?= \App\Core\Controller::url('/production/catalog') ?>" class="text-decoration-none text-muted">Production</a></li>
            <li class="breadcrumb-item"><a href="<?= \App\Core\Controller::url('/production/cutting') ?>" class="text-decoration-none text-muted">Cutting</a></li>
            <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Edit: <?= htmlspecialchars($batch['display_batch_no'] ?: $batch['log_no']) ?></li>
        </ol>
    </nav>

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
        <div>
            <h4 class="fw-bold mb-1 text-dark d-flex align-items-center gap-2">
                <i class="bi bi-pencil-square text-primary"></i>
                Edit Cutting Batch
            </h4>
            <p class="text-secondary small mb-0">
                Update batch details, production program, and cut-output counts for
                <strong class="text-dark font-monospace"><?= htmlspecialchars($batch['display_batch_no'] ?: $batch['log_no']) ?></strong>.
                Changing the program reassigns cut items to that program’s available rolls; fabric weights and wastage stay unchanged.
            </p>
        </div>
        <a href="<?= \App\Core\Controller::url('/production/cutting/view?id=' . (int)$batch['id']) ?>" class="btn btn-light border shadow-sm fw-semibold">
            <i class="bi bi-eye me-1"></i> View Batch
        </a>
    </div>

    <form action="<?= \App\Core\Controller::url('/production/cutting/update') ?>" method="POST" id="formEditCutting">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
        <input type="hidden" name="id" value="<?= (int)$batch['id'] ?>">
        <input type="hidden" id="availableFabricCost" value="<?= max(0.00, (float)$batch['total_roll_price'] - (float)$batch['wastage_cost']) ?>">

        <div class="row g-4">
            <!-- LEFT: BATCH DETAILS -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                            <i class="bi bi-card-checklist text-primary"></i>
                            1. Cutting Batch Details
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-dark mb-1">
                                Cutting Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="cutting_name" class="form-control fw-semibold"
                                   value="<?= htmlspecialchars($batch['cutting_name'] ?? '') ?>" required>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <label class="form-label small fw-semibold text-dark mb-1">Batch Number</label>
                                <input type="text" class="form-control bg-light fw-bold text-secondary font-monospace"
                                       value="<?= htmlspecialchars($batch['display_batch_no'] ?: $batch['log_no']) ?>" readonly>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label small fw-semibold text-dark mb-1">Status</label>
                                <select name="status" class="form-select fw-semibold">
                                    <option value="in_progress" <?= ($batch['status'] ?? '') === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                    <option value="planned" <?= ($batch['status'] ?? '') === 'planned' ? 'selected' : '' ?>>Planned</option>
                                    <option value="completed" <?= ($batch['status'] ?? '') === 'completed' ? 'selected' : '' ?>>Completed</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-dark mb-1">
                                Production Program <span class="text-danger">*</span>
                            </label>
                            <select name="program_id" id="programSelect" class="form-select" required>
                                <option value="">-- Choose Production Program --</option>
                                <?php foreach (($programs ?? []) as $prog): ?>
                                    <?php $isCurrentProgram = (int)$prog['id'] === (int)($batch['program_id'] ?? 0); ?>
                                    <?php
                                        $catalogLabel = trim(
                                            (($prog['catalog_code'] ?? '') ? '[' . $prog['catalog_code'] . '] ' : '')
                                            . ($prog['catalog_name'] ?? '')
                                        );
                                    ?>
                                    <option value="<?= (int)$prog['id'] ?>"
                                            data-catalog="<?= htmlspecialchars($catalogLabel) ?>"
                                            data-rolls="<?= (int)$prog['rolls_count'] ?>"
                                            <?= $isCurrentProgram ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($prog['program_no']) ?> • <?= htmlspecialchars($prog['program_name']) ?>
                                        (<?= $isCurrentProgram ? 'Current' : (int)$prog['rolls_count'] . ' Available Roll' . ((int)$prog['rolls_count'] === 1 ? '' : 's') ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div id="selectedProgramCatalogInfo" class="mt-2 d-none">
                                <div class="p-2 border rounded-3 bg-light d-flex align-items-center justify-content-between">
                                    <div class="small">
                                        <span class="text-muted"><i class="bi bi-tag-fill text-primary me-1"></i> Catalog:</span>
                                        <strong id="selectedProgCatalogName" class="text-primary ms-1"></strong>
                                    </div>
                                    <span id="selectedProgRollsCount" class="badge bg-secondary text-white"></span>
                                </div>
                            </div>
                            <small class="text-muted" style="font-size: 0.75rem;">
                                Newly created programs with available rolls appear here automatically.
                            </small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-dark mb-1">Cutting Master / Operator</label>
                            <select name="cutting_master_id" class="form-select">
                                <option value="">Select Cutting Master</option>
                                <?php foreach ($cuttingMasters as $cm): ?>
                                    <option value="<?= (int)$cm['id'] ?>" <?= (int)($batch['cutting_master_id'] ?? 0) === (int)$cm['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cm['name'] . ($cm['phone'] ? ' (' . $cm['phone'] . ')' : '')) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-0">
                            <label class="form-label small fw-semibold text-dark mb-1">Notes & Remarks</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="Cutting instructions, lot details, etc..."><?= htmlspecialchars($batch['notes'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT: CUT OUTPUT BREAKDOWN -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                            <i class="bi bi-grid-3x3-gap-fill text-primary"></i>
                            2. Cut Output by Variant / Size
                        </h6>
                        <span class="badge bg-success px-3 py-1.5 fw-bold" id="badgeTotalCutPcs">
                            <?= (int)$batch['cut_pieces'] ?> Cut pcs
                        </span>
                    </div>

                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light small text-uppercase text-secondary">
                                    <tr>
                                        <th class="ps-3">Roll / Catalog</th>
                                        <th>Variant / Size</th>
                                        <th>SKU Code</th>
                                        <th class="text-end pe-3" style="width: 150px;">Cut Output</th>
                                    </tr>
                                </thead>
                                <tbody class="small">
                                    <?php if (empty($items)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-muted">No cut items recorded for this batch.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($items as $it): ?>
                                            <?php
                                                $hierarchy = array_filter([
                                                    $it['category_name'] ?? '',
                                                    $it['subcategory_name'] ?? '',
                                                    $it['subsub_name'] ?? '',
                                                ]);
                                                $catalogLabel = $it['catalog_name']
                                                    ?: (implode(' > ', $hierarchy) ?: ($batch['catalog_name'] ?? ''));
                                            ?>
                                            <tr>
                                                <td class="ps-3">
                                                    <span class="badge bg-light text-dark border font-monospace">
                                                        <?= htmlspecialchars($it['roll_piece_tag'] ?: 'Roll') ?>
                                                    </span>
                                                    <div class="text-muted mt-0.5" style="font-size: 0.75rem;">
                                                        <?= htmlspecialchars($catalogLabel ?: ($it['fabric_name'] ?? 'Fabric')) ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="fw-bold text-dark"><?= htmlspecialchars($it['size'] ?: 'Standard') ?></div>
                                                    <div class="text-muted" style="font-size: 0.75rem;">
                                                        <?= htmlspecialchars($it['variant_name'] ?: ($it['fabric_name'] ?? '')) ?>
                                                    </div>
                                                </td>
                                                <td class="font-monospace text-muted">
                                                    <?= htmlspecialchars($it['sku_code'] ?: '-') ?>
                                                </td>
                                                <td class="text-end pe-3">
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" step="1" min="0"
                                                               name="items[<?= (int)$it['id'] ?>][cut_pieces]"
                                                               class="form-control text-end fw-bold input-cut-pcs"
                                                               value="<?= (int)$it['cut_pieces'] ?>" required>
                                                        <span class="input-group-text bg-light text-muted">pcs</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card-footer bg-white border-top py-3">
                        <div class="row g-3 small">
                            <div class="col-sm-4">
                                <div class="text-muted">Total Wastage</div>
                                <div class="fw-bold text-danger"><?= number_format((float)$batch['wastage_qty'], 2) ?> kg</div>
                            </div>
                            <div class="col-sm-4">
                                <div class="text-muted">Total Cut Output</div>
                                <div class="fw-bold text-success" id="summaryCutPcs"><?= (int)$batch['cut_pieces'] ?> pcs</div>
                            </div>
                            <div class="col-sm-4">
                                <div class="text-muted">Fabric Cost / Pc</div>
                                <div class="fw-bold text-primary font-monospace" id="summaryCostPerPc">₹<?= number_format((float)$batch['fabric_cost_per_piece'], 2) ?></div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center pt-3 mt-3 border-top">
                            <a href="<?= \App\Core\Controller::url('/production/cutting') ?>" class="btn btn-light border px-4 fw-semibold text-secondary">
                                <i class="bi bi-x-circle me-1"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary px-4 fw-semibold shadow-sm">
                                <i class="bi bi-check2-circle me-1"></i> Update Cutting Batch
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const programSelect = document.getElementById('programSelect');
    const programInfo = document.getElementById('selectedProgramCatalogInfo');
    const programCatalog = document.getElementById('selectedProgCatalogName');
    const programRolls = document.getElementById('selectedProgRollsCount');

    function updateProgramInfo() {
        if (!programSelect || !programInfo) return;
        const option = programSelect.options[programSelect.selectedIndex];
        if (!option || !option.value) {
            programInfo.classList.add('d-none');
            return;
        }
        programInfo.classList.remove('d-none');
        if (programCatalog) programCatalog.textContent = option.dataset.catalog || 'No catalog assigned';
        if (programRolls) {
            const count = parseInt(option.dataset.rolls, 10) || 0;
            programRolls.textContent = count > 0 ? count + ' roll' + (count === 1 ? '' : 's') : 'Current';
        }
    }

    if (programSelect) {
        programSelect.addEventListener('change', updateProgramInfo);
        updateProgramInfo();
    }

    const inputs = Array.from(document.querySelectorAll('.input-cut-pcs'));
    const badge = document.getElementById('badgeTotalCutPcs');
    const summaryPcs = document.getElementById('summaryCutPcs');
    const summaryCost = document.getElementById('summaryCostPerPc');
    const fabricCost = parseFloat(document.getElementById('availableFabricCost').value) || 0;

    function recalculate() {
        if (!inputs.length) return;
        const total = inputs.reduce((sum, input) => sum + (parseInt(input.value, 10) || 0), 0);
        const costPerPc = total > 0 ? fabricCost / total : 0;
        if (badge) badge.textContent = `${total} Cut pcs`;
        if (summaryPcs) summaryPcs.textContent = `${total} pcs`;
        if (summaryCost) summaryCost.textContent = `₹${costPerPc.toFixed(2)}`;
    }

    inputs.forEach(input => input.addEventListener('input', recalculate));
    recalculate();

    document.getElementById('formEditCutting').addEventListener('submit', function(e) {
        const total = inputs.reduce((sum, input) => sum + (parseInt(input.value, 10) || 0), 0);
        if (inputs.length && total <= 0) {
            e.preventDefault();
            alert('Enter cut pieces for at least one variant/size.');
            if (inputs.length) inputs[0].focus();
        }
    });
});
</script>
