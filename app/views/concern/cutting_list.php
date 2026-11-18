<?php
/**
 * View: Fabric Cutting & Wastage Tracking + Wastage Selling Register
 * Location: inventory_mekha/app/views/concern/cutting_list.php
 * Matches Image 3 (Cutting Wastage Selling Modal & Registry)
 */
?>
<div class="container-fluid px-4 py-3">

    <!-- BREADCRUMBS -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0 small">
            <li class="breadcrumb-item"><a href="<?= \App\Core\Controller::url('/dashboard') ?>" class="text-decoration-none text-muted">Home</a></li>
            <li class="breadcrumb-item"><a href="<?= \App\Core\Controller::url('/production/catalog') ?>" class="text-decoration-none text-muted">Production</a></li>
            <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Fabric Cutting & Wastage</li>
        </ol>
    </nav>

    <!-- HEADER & ACTIONS BAR -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1 text-dark d-flex align-items-center gap-2">
                <i class="bi bi-scissors text-primary"></i> Fabric Cutting & Wastage Tracking
            </h4>
            <p class="text-secondary small mb-0">
                Manage production cutting lots, dynamic variant breakdowns, fabric wastage cost analytics, and scrap sales.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-outline-danger shadow-sm d-inline-flex align-items-center gap-1.5 fw-semibold" 
                    data-bs-toggle="modal" data-bs-target="#modalSellWastage">
                <i class="bi bi-recycle"></i> Record Scrap Sale
            </button>
            <a href="<?= \App\Core\Controller::url('/production/cutting/create') ?>" 
               class="btn btn-primary shadow-sm d-inline-flex align-items-center gap-1.5 fw-semibold">
                <i class="bi bi-plus-circle"></i> New Cutting Batch
            </a>
        </div>
    </div>

    <!-- TABS -->
    <ul class="nav nav-pills mb-4 gap-2 border-bottom pb-2">
        <li class="nav-item">
            <a class="nav-link <?= $activeTab !== 'wastage' ? 'active' : '' ?> fw-semibold d-inline-flex align-items-center gap-2" 
               href="<?= \App\Core\Controller::url('/production/cutting?tab=batches') ?>">
                <i class="bi bi-collection"></i> Cutting Batches
                <span class="badge bg-light text-dark border ms-1"><?= count($batches) ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'wastage' ? 'active' : '' ?> fw-semibold d-inline-flex align-items-center gap-2" 
               href="<?= \App\Core\Controller::url('/production/cutting?tab=wastage') ?>">
                <i class="bi bi-cash-stack"></i> Wastage Selling & Scrap Sales
                <span class="badge bg-light text-dark border ms-1"><?= count($scrapSales) ?></span>
            </a>
        </li>
    </ul>

    <?php if ($activeTab === 'wastage'): ?>
        <!-- ========================================== -->
        <!-- TAB 2: WASTAGE SELLING & SCRAP SALES (IMAGE 3) -->
        <!-- ========================================== -->
        
        <!-- Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                    <small class="text-muted d-block small mb-1">Total Wastage Generated</small>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold fs-5 text-dark"><?= number_format($totalWastageGen, 2) ?> kg</span>
                        <span class="badge bg-danger-subtle text-danger rounded-pill p-2"><i class="bi bi-trash3 fs-6"></i></span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                    <small class="text-muted d-block small mb-1">Total Wastage Sold</small>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold fs-5 text-info"><?= number_format($totalWastageSold, 2) ?> kg</span>
                        <span class="badge bg-info-subtle text-info rounded-pill p-2"><i class="bi bi-check2-all fs-6"></i></span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                    <small class="text-muted d-block small mb-1">Available Scrap to Sell</small>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold fs-5 text-success"><?= number_format($availableWastage, 2) ?> kg</span>
                        <span class="badge bg-success-subtle text-success rounded-pill p-2"><i class="bi bi-recycle fs-6"></i></span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                    <small class="text-muted d-block small mb-1">Total Scrap Revenue</small>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold fs-5 text-success font-monospace">₹<?= number_format($totalScrapRevenue, 2) ?></span>
                        <span class="badge bg-success-subtle text-success rounded-pill p-2"><i class="bi bi-currency-rupee fs-6"></i></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Scrap Sales History Table -->
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                    <i class="bi bi-clock-history text-primary"></i> Wastage Scrap Sales History
                </h6>
                <button class="btn btn-sm btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalSellWastage">
                    <i class="bi bi-plus-circle me-1"></i> New Scrap Sale
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th class="ps-3">Sale No</th>
                            <th>Cutting Batch & Program</th>
                            <th>Buyer Details</th>
                            <th>Wastage Category</th>
                            <th class="text-end">Sold Weight</th>
                            <th class="text-end">Rate (₹/kg)</th>
                            <th class="text-end">Total Price</th>
                            <th class="text-end">Received</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        <?php if (empty($scrapSales)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                                    No scrap sales recorded yet. Click <strong>"Record Scrap Sale"</strong> to sell accumulated cutting wastage.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($scrapSales as $s): ?>
                                <tr>
                                    <td class="ps-3">
                                        <span class="badge bg-dark font-monospace"><?= htmlspecialchars($s['sale_no']) ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($s['batch_no']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($s['cutting_name']) ?> (<?= htmlspecialchars($s['program_no'] ?? '-') ?>)</small>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= htmlspecialchars($s['buyer_name']) ?></div>
                                        <small class="text-muted"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($s['buyer_phone']) ?><?= $s['buyer_company'] ? ' • ' . htmlspecialchars($s['buyer_company']) : '' ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-secondary border"><?= htmlspecialchars($s['wastage_category']) ?></span>
                                    </td>
                                    <td class="text-end fw-bold text-danger">
                                        <?= number_format($s['wastage_weight'], 2) ?> kg
                                    </td>
                                    <td class="text-end font-monospace">
                                        ₹<?= number_format($s['selling_rate'], 2) ?>
                                    </td>
                                    <td class="text-end fw-bold text-success font-monospace">
                                        ₹<?= number_format($s['total_price'], 2) ?>
                                    </td>
                                    <td class="text-end font-monospace">
                                        <span class="badge <?= (float)$s['amount_received'] >= (float)$s['total_price'] ? 'bg-success' : 'bg-warning text-dark' ?>">
                                            ₹<?= number_format($s['amount_received'], 2) ?>
                                        </span>
                                    </td>
                                    <td class="text-muted font-monospace">
                                        <?= date('d M Y', strtotime($s['sale_date'])) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php else: ?>
        <!-- ========================================== -->
        <!-- TAB 1: CUTTING BATCHES REGISTER -->
        <!-- ========================================== -->
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                    <i class="bi bi-scissors text-primary"></i> Production Cutting Lots Register
                </h6>
                <span class="badge bg-light text-dark border"><?= count($batches) ?> Batches</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th class="ps-3">Batch No</th>
                            <th>Cutting Name & Program</th>
                            <th>Master / Operator</th>
                            <th class="text-end">Consumed Fabric</th>
                            <th class="text-end">Cut Output</th>
                            <th class="text-end">Wastage (kg / %)</th>
                            <th class="text-end">Fabric Cost / Pc</th>
                            <th>Status</th>
                            <th>Created Date</th>
                            <th class="text-end pe-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        <?php if (empty($batches)): ?>
                            <tr>
                                <td colspan="10" class="text-center py-5 text-muted">
                                    <i class="bi bi-scissors fs-2 d-block mb-2 text-secondary"></i>
                                    No cutting batches found. Click <strong>"New Cutting Batch"</strong> to create a production cutting lot.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($batches as $b): 
                                $bNo = $b['batch_no'] ?: $b['log_no'];
                                $availScrap = max(0.00, (float)$b['wastage_qty'] - (float)$b['sold_wastage_qty']);
                            ?>
                                <tr>
                                    <td class="ps-3">
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace px-2 py-1">
                                            <?= htmlspecialchars($bNo) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($b['cutting_name'] ?: 'Lot #' . $b['id']) ?></div>
                                        <small class="text-muted">
                                            <?= htmlspecialchars($b['program_no'] ?? 'Unscheduled') ?> • <?= htmlspecialchars($b['program_name'] ?? '') ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="text-secondary"><?= htmlspecialchars($b['master_name'] ?: 'Not Assigned') ?></span>
                                    </td>
                                    <td class="text-end">
                                        <span class="fw-bold text-dark"><?= number_format($b['consumed_qty'], 2) ?> kg</span>
                                        <small class="text-muted d-block font-monospace">Val: ₹<?= number_format($b['total_roll_price'], 2) ?></small>
                                    </td>
                                    <td class="text-end">
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 fw-bold fs-7">
                                            <?= number_format($b['cut_pieces']) ?> pcs
                                        </span>
                                        <small class="text-muted d-block"><?= $b['variant_count'] ?> Size Variant<?= $b['variant_count'] > 1 ? 's' : '' ?></small>
                                    </td>
                                    <td class="text-end">
                                        <span class="fw-bold text-danger"><?= number_format($b['wastage_qty'], 2) ?> kg</span>
                                        <small class="text-muted d-block">
                                            (<?= number_format($b['wastage_percent'], 1) ?>%) 
                                            <?php if ($availScrap > 0.01): ?>
                                                <span class="text-success fw-semibold">• <?= number_format($availScrap, 2) ?> kg avail</span>
                                            <?php endif; ?>
                                        </small>
                                    </td>
                                    <td class="text-end fw-bold font-monospace text-primary">
                                        ₹<?= number_format($b['fabric_cost_per_piece'], 2) ?>
                                    </td>
                                    <td>
                                        <?php 
                                            $st = $b['status'] ?? 'in_progress';
                                            $stClass = $st === 'completed' ? 'bg-success' : ($st === 'in_progress' ? 'bg-primary' : 'bg-warning text-dark');
                                        ?>
                                        <span class="badge <?= $stClass ?>">
                                            <?= ucfirst(str_replace('_', ' ', $st)) ?>
                                        </span>
                                    </td>
                                    <td class="text-muted font-monospace">
                                        <?= date('d M Y', strtotime($b['created_at'])) ?>
                                    </td>
                                    <td class="text-end pe-3">
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?= \App\Core\Controller::url('/production/cutting/view?id=' . $b['id']) ?>" 
                                               class="btn btn-outline-secondary" title="View Details">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <?php if ($availScrap > 0.01): ?>
                                                <button type="button" class="btn btn-outline-danger btn-sell-scrap" 
                                                        data-batch-id="<?= $b['id'] ?>"
                                                        title="Sell Wastage Scrap">
                                                    <i class="bi bi-recycle"></i>
                                                </button>
                                            <?php endif; ?>
                                            <form action="<?= \App\Core\Controller::url('/production/cutting/delete') ?>" method="POST" class="d-inline"
                                                  onsubmit="return confirm('Are you sure you want to delete cutting batch <?= htmlspecialchars($bNo) ?>?');">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                                <input type="hidden" name="id" value="<?= $b['id'] ?>">
                                                <button type="submit" class="btn btn-outline-danger" title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

</div>

<!-- ======================================================== -->
<!-- MODAL: RECORD CUTTING WASTAGE SCRAP SALE (MATCHING IMAGE 3) -->
<!-- ======================================================== -->
<div class="modal fade" id="modalSellWastage" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form action="<?= \App\Core\Controller::url('/production/cutting/wastage/sell') ?>" method="POST" class="modal-content border-0 shadow-lg rounded-4">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

            <div class="modal-header border-bottom py-3">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2">
                    <i class="bi bi-scissors text-danger"></i> Select Cutting Module Wastage / Scrap Batch <span class="text-danger">*</span>
                </h5>
                <span class="badge bg-light text-secondary border px-2 py-1 small">
                    <i class="bi bi-check2-square me-1"></i> Multi-Batch Checkbox
                </span>
            </div>

            <div class="modal-body p-4">
                
                <!-- Batch Selection Dropdown matching Image 3 -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label small fw-semibold text-dark mb-0">Select Cutting Batch <span class="text-danger">*</span></label>
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5" id="modalBatchCountPill">1 batch</span>
                    </div>
                    <select name="cutting_id" id="modalSelectCuttingBatch" class="form-select" required>
                        <option value="">-- Choose Cutting Batch with Available Scrap --</option>
                        <?php foreach ($availableWastages as $aw): 
                            $bNo = $aw['batch_no'] ?: $aw['log_no'];
                            $avail = (float)$aw['available_wastage_qty'];
                            $rate = (float)$aw['fabric_rate_per_kg'];
                        ?>
                            <option value="<?= $aw['id'] ?>" 
                                    data-batch-no="<?= htmlspecialchars($bNo) ?>"
                                    data-name="<?= htmlspecialchars($aw['cutting_name']) ?>"
                                    data-program="<?= htmlspecialchars($aw['program_no'] ?? '-') ?>"
                                    data-supplier="<?= htmlspecialchars($aw['supplier_display']) ?>"
                                    data-total="<?= number_format($aw['wastage_qty'], 2) ?>"
                                    data-sold="<?= number_format($aw['sold_wastage_qty'], 2) ?>"
                                    data-avail="<?= number_format($avail, 2, '.', '') ?>"
                                    data-rate="<?= number_format($rate, 2, '.', '') ?>">
                                [<?= htmlspecialchars($bNo) ?>] <?= htmlspecialchars($aw['cutting_name']) ?> • Avail: <?= number_format($avail, 2) ?> kg
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <?php if (empty($availableWastages)): ?>
                        <div class="alert alert-info py-2 px-3 small mt-2 mb-0 rounded-3">
                            <i class="bi bi-info-circle me-1"></i> No cutting batches currently have available scrap to sell. Record a cutting batch with wastage first.
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Selected Chip matching Image 3 -->
                <div id="modalSelectedChip" class="mb-3 d-none">
                    <span class="badge bg-light text-dark border px-3 py-1.5 fs-7 d-inline-flex align-items-center gap-2">
                        <i class="bi bi-tag-fill text-primary"></i>
                        <span id="chipText">Selected (1): CUT-20261113-0001 (10.00 kg)</span>
                        <button type="button" class="btn-close btn-close-sm" id="btnRemoveSelectedChip" aria-label="Remove" style="font-size: 0.65rem;"></button>
                    </span>
                </div>

                <!-- Selected Batch Info Box matching Image 3 -->
                <div id="modalBatchInfoBox" class="card border rounded-3 p-3 bg-light mb-3 d-none">
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                        <div class="fw-bold text-dark" id="infoBatchTitle">
                            CUT-20261113-0001 - CUTTING - KIDS DRESS (PRG-20261113-0001)
                        </div>
                        <span class="small text-muted" id="infoBatchSupplier">Supplier: VANAJA J</span>
                    </div>
                    <div class="row g-2 text-center small font-monospace">
                        <div class="col-3">
                            <span class="text-secondary d-block">Total Cut Wastage:</span>
                            <strong class="text-dark" id="infoTotalCutWastage">10.00 kg</strong>
                        </div>
                        <div class="col-3">
                            <span class="text-secondary d-block">Already Sold:</span>
                            <strong class="text-secondary" id="infoAlreadySold">0.00 kg</strong>
                        </div>
                        <div class="col-3">
                            <span class="text-secondary d-block">Available To Sell:</span>
                            <strong class="text-success" id="infoAvailToSell">10.00 kg</strong>
                        </div>
                        <div class="col-3">
                            <span class="text-secondary d-block">Fabric Cost:</span>
                            <strong class="text-primary" id="infoFabricCost">₹20.00/kg</strong>
                        </div>
                    </div>
                </div>

                <!-- Buyer Info Form Fields matching Image 3 -->
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-dark mb-1">Scrap Buyer / Vendor Name <span class="text-danger">*</span></label>
                        <input type="text" name="buyer_name" class="form-control form-control-sm" placeholder="e.g. Krishna Scrap Traders" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-dark mb-1">Buyer Phone / Mobile <span class="text-danger">* (10 digits)</span></label>
                        <input type="tel" name="buyer_phone" id="inputBuyerPhone" class="form-control form-control-sm" placeholder="10-digit mobile (e.g. 9876543210)" pattern="[0-9]{10}" required>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-dark mb-1">Buyer Company / Business Name</label>
                        <input type="text" name="buyer_company" class="form-control form-control-sm" placeholder="e.g. Sri Waste Recycling Mills">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-dark mb-1">Wastage Category <span class="text-danger">*</span></label>
                        <select name="wastage_category" class="form-select form-select-sm" required>
                            <option value="Cutting End-Bits (Short length ends & trims)" selected>Cutting End-Bits (Short length ends & trims)</option>
                            <option value="Fabric Selvage & Edge Trimmings">Fabric Selvage & Edge Trimmings</option>
                            <option value="Chindi / Small Fabric Rags">Chindi / Small Fabric Rags</option>
                            <option value="Defective Fabric Scraps">Defective Fabric Scraps</option>
                            <option value="Mixed Cutting Lay Waste">Mixed Cutting Lay Waste</option>
                        </select>
                    </div>
                </div>

                <!-- SELLING PRICE & QUANTITY (MANUAL ENTRY) matching Image 3 -->
                <div class="card border rounded-3 p-3 bg-light mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2 pb-1 border-bottom">
                        <span class="small fw-bold text-success d-flex align-items-center gap-1.5">
                            <i class="bi bi-calculator"></i> SELLING PRICE & QUANTITY (MANUAL ENTRY) <span class="text-danger">*</span>
                        </span>
                        <span class="small text-muted" style="font-size: 0.75rem;">Type Rate or Total Price directly</span>
                    </div>

                    <!-- Wastage Weight (kg) -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark mb-1">Wastage Weight (kg) <span class="text-danger">*</span></label>
                        <div class="input-group input-group-sm">
                            <input type="number" step="0.01" min="0.01" name="wastage_weight" id="inputSaleWeight" class="form-control fw-bold" placeholder="0.00" required>
                            <span class="input-group-text bg-light text-muted">kg</span>
                        </div>
                        <small class="text-muted" style="font-size: 0.72rem;">Weight being sold to scrap buyer</small>
                    </div>

                    <!-- Selling Rate & Total Sale Price Row -->
                    <div class="row g-3 mb-2">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-dark mb-1">Selling Rate (₹ / kg) <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light text-muted">₹</span>
                                <input type="number" step="0.01" min="0.00" name="selling_rate" id="inputSaleRate" class="form-control fw-bold font-monospace" placeholder="0.00" required>
                                <span class="input-group-text bg-light text-muted">/ kg</span>
                            </div>
                            <small class="text-muted" style="font-size: 0.72rem;">Enter manual scrap rate per kg</small>
                        </div>

                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label small fw-semibold text-dark mb-0">Total Sale Price (₹) <span class="text-danger">*</span></label>
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-1.5 py-0.5" style="font-size: 0.68rem;">Editable</span>
                            </div>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-success-subtle text-success fw-bold">₹</span>
                                <input type="number" step="0.01" min="0.00" name="total_price" id="inputSaleTotalPrice" class="form-control fw-bolder font-monospace text-success bg-white" placeholder="0.00" required>
                            </div>
                            <small class="text-muted" style="font-size: 0.72rem;">Or enter lump-sum total price</small>
                        </div>
                    </div>

                    <!-- Footnote Calculation display matching Image 3 -->
                    <div class="small text-muted font-monospace mt-1" id="saleCalcFootnote" style="font-size: 0.75rem;">
                        Calculated: 0.00 kg × ₹0.00/kg = ₹0.00
                    </div>
                </div>

                <!-- Settlement Row matching Image 3 -->
                <div class="row g-3 mb-0">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-dark mb-1">Amount Received (₹) <span class="text-danger">*</span></label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light text-muted">₹</span>
                            <input type="number" step="0.01" min="0.00" name="amount_received" id="inputAmountReceived" class="form-control font-monospace" value="0.00" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-dark mb-1">Sale Date <span class="text-danger">*</span></label>
                        <input type="date" name="sale_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>

            </div>

            <div class="modal-footer border-top bg-light py-3 d-flex justify-content-between">
                <button type="button" class="btn btn-light border px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success px-4 fw-semibold shadow-sm d-inline-flex align-items-center gap-2">
                    <i class="bi bi-check2-circle fs-5"></i> Confirm Wastage Sale
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modalSelect = document.getElementById('modalSelectCuttingBatch');
    const modalSelectedChip = document.getElementById('modalSelectedChip');
    const chipText = document.getElementById('chipText');
    const btnRemoveSelectedChip = document.getElementById('btnRemoveSelectedChip');

    const modalBatchInfoBox = document.getElementById('modalBatchInfoBox');
    const infoBatchTitle = document.getElementById('infoBatchTitle');
    const infoBatchSupplier = document.getElementById('infoBatchSupplier');
    const infoTotalCutWastage = document.getElementById('infoTotalCutWastage');
    const infoAlreadySold = document.getElementById('infoAlreadySold');
    const infoAvailToSell = document.getElementById('infoAvailToSell');
    const infoFabricCost = document.getElementById('infoFabricCost');

    const inputSaleWeight = document.getElementById('inputSaleWeight');
    const inputSaleRate = document.getElementById('inputSaleRate');
    const inputSaleTotalPrice = document.getElementById('inputSaleTotalPrice');
    const inputAmountReceived = document.getElementById('inputAmountReceived');
    const saleCalcFootnote = document.getElementById('saleCalcFootnote');

    let maxAvailWeight = 0;

    // Handle batch selection
    modalSelect.addEventListener('change', function() {
        const opt = this.selectedOptions[0];
        if (!opt || !opt.value) {
            modalSelectedChip.classList.add('d-none');
            modalBatchInfoBox.classList.add('d-none');
            inputSaleWeight.value = '';
            inputSaleRate.value = '';
            inputSaleTotalPrice.value = '';
            saleCalcFootnote.textContent = 'Calculated: 0.00 kg × ₹0.00/kg = ₹0.00';
            maxAvailWeight = 0;
            return;
        }

        const bNo = opt.dataset.batchNo;
        const name = opt.dataset.name;
        const prog = opt.dataset.program;
        const supplier = opt.dataset.supplier;
        const total = parseFloat(opt.dataset.total || 0);
        const sold = parseFloat(opt.dataset.sold || 0);
        const avail = parseFloat(opt.dataset.avail || 0);
        const rate = parseFloat(opt.dataset.rate || 0);

        maxAvailWeight = avail;

        // Populate chip
        chipText.textContent = `Selected (1): ${bNo} (${avail.toFixed(2)} kg)`;
        modalSelectedChip.classList.remove('d-none');

        // Populate info box
        infoBatchTitle.textContent = `${bNo} - ${name} (${prog})`;
        infoBatchSupplier.textContent = `Supplier: ${supplier}`;
        infoTotalCutWastage.textContent = `${total.toFixed(2)} kg`;
        infoAlreadySold.textContent = `${sold.toFixed(2)} kg`;
        infoAvailToSell.textContent = `${avail.toFixed(2)} kg`;
        infoFabricCost.textContent = `₹${rate.toFixed(2)}/kg`;
        modalBatchInfoBox.classList.remove('d-none');

        // Defaults
        inputSaleWeight.value = avail.toFixed(2);
        inputSaleWeight.max = avail;
        inputSaleRate.value = rate > 0 ? (rate * 0.1).toFixed(2) : '20.00'; // Default scrap benchmark rate

        recalcSalePricing('rate');
    });

    btnRemoveSelectedChip.addEventListener('click', function() {
        modalSelect.value = '';
        modalSelect.dispatchEvent(new Event('change'));
    });

    // Bidirectional pricing calculations matching Image 3
    inputSaleWeight.addEventListener('input', () => recalcSalePricing('rate'));
    inputSaleRate.addEventListener('input', () => recalcSalePricing('rate'));
    inputSaleTotalPrice.addEventListener('input', () => recalcSalePricing('price'));

    function recalcSalePricing(trigger = 'rate') {
        const weight = parseFloat(inputSaleWeight.value) || 0;
        let rate = parseFloat(inputSaleRate.value) || 0;
        let price = parseFloat(inputSaleTotalPrice.value) || 0;

        if (trigger === 'rate') {
            price = weight * rate;
            inputSaleTotalPrice.value = price.toFixed(2);
        } else if (trigger === 'price') {
            rate = weight > 0 ? (price / weight) : 0;
            inputSaleRate.value = rate.toFixed(2);
        }

        inputAmountReceived.value = price.toFixed(2);
        saleCalcFootnote.textContent = `Calculated: ${weight.toFixed(2)} kg × ₹${rate.toFixed(2)}/kg = ₹${price.toFixed(2)}`;
    }

    // Direct trigger from batch row "Sell Scrap" button
    document.querySelectorAll('.btn-sell-scrap').forEach(btn => {
        btn.addEventListener('click', function() {
            const batchId = this.dataset.batchId;
            modalSelect.value = batchId;
            modalSelect.dispatchEvent(new Event('change'));
            const modal = new bootstrap.Modal(document.getElementById('modalSellWastage'));
            modal.show();
        });
    });
});
</script>
