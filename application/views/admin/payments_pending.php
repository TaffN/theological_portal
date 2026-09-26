<?php
    $CI =& get_instance();
    $pendingCount = $tab === 'pending' ? count($payments) : (int) $pending;
?>
<div class="page-head">
    <div>
        <h1 class="page-title">Payments</h1>
        <p class="page-sub">Check each proof against your EcoCash or bank statement before approving.</p>
    </div>
</div>

<div class="tabs mb-4">
    <a href="<?= base_url('admin_payments') ?>" class="tab <?= $tab === 'pending' ? 'active' : '' ?>">
        To review <?php if ($pendingCount > 0): ?><span class="count-pill"><?= $pendingCount ?></span><?php endif; ?>
    </a>
    <a href="<?= base_url('admin_payments/history') ?>" class="tab <?= $tab === 'history' ? 'active' : '' ?>">History</a>
</div>

<?php if ($tab === 'pending'): ?>

    <?php if (empty($payments)): ?>
        <div class="card"><div class="empty-state">
            <span class="empty-icon bg-soft-green"><?= icon('check', 28) ?></span>
            <strong class="d-block text-body mt-2">All caught up</strong>
            No payments are waiting for review.
        </div></div>
    <?php endif; ?>

    <div class="row g-3">
    <?php foreach ($payments as $p): ?>
        <?php
            $ext     = strtolower(pathinfo($p['proof_file_path'], PATHINFO_EXTENSION));
            $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
            $proof   = base_url('admin_payments/view_proof/' . $p['id']);
        ?>
        <div class="col-md-6 col-xl-4">
            <div class="card payment-card h-100">
                <a href="<?= $proof ?>" target="_blank" class="proof-thumb" title="Open full size">
                    <?php if ($isImage): ?>
                        <img src="<?= $proof ?>" alt="Proof of payment" loading="lazy">
                    <?php else: ?>
                        <span class="proof-pdf"><?= icon('file', 34) ?><small>PDF &middot; tap to open</small></span>
                    <?php endif; ?>
                </a>
                <div class="card-body d-flex flex-column">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="avatar avatar-sm"><?= html_escape(initials($p['student_name'])) ?></span>
                        <div class="min-w-0">
                            <div class="fw-semibold text-truncate"><?= html_escape($p['student_name']) ?></div>
                            <small class="text-muted text-truncate d-block"><?= html_escape($p['student_email']) ?></small>
                        </div>
                    </div>
                    <ul class="course-meta mb-3">
                        <li><?= icon('book', 16) ?> <?= html_escape($p['course_name']) ?></li>
                        <li><?= icon('dollar', 16) ?> <strong><?= money($p['amount']) ?></strong> &middot; <?= $p['method'] === 'ecocash' ? 'EcoCash' : 'Bank transfer' ?></li>
                        <li><?= icon('clock', 16) ?> <?= html_escape(time_ago($p['submitted_at'])) ?></li>
                    </ul>

                    <div class="mt-auto">
                        <form method="post" action="<?= base_url('admin_payments/approve/' . $p['id']) ?>" data-loading
                              data-confirm="Approve <?= money($p['amount']) ?> from <?= html_escape($p['student_name']) ?>? Their course opens immediately."
                              data-confirm-ok="Approve">
                            <button type="submit" class="btn btn-success w-100 mb-2"><?= icon('check', 16) ?> Approve</button>
                        </form>

                        <details class="reject-box">
                            <summary class="btn btn-outline-danger w-100">Reject&hellip;</summary>
                            <form method="post" action="<?= base_url('admin_payments/reject/' . $p['id']) ?>" class="mt-2" data-loading>
                                <label class="form-label small">Reason (the student will see this)</label>
                                <textarea name="note" class="form-control mb-2" rows="2" required
                                          placeholder="e.g. Amount doesn't match the fee, or the screenshot is unreadable"></textarea>
                                <button type="submit" class="btn btn-danger w-100">Reject payment</button>
                            </form>
                        </details>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    </div>

<?php else: ?>

    <div class="card">
        <div class="card-body">
            <?php if (empty($payments)): ?>
                <div class="empty-state">No payments have been reviewed yet.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-clean align-middle mb-0">
                        <thead><tr><th>Student</th><th>Course</th><th>Amount</th><th>Status</th><th>Reviewed</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($payments as $p): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?= html_escape($p['student_name']) ?></div>
                                    <small class="text-muted"><?= html_escape($p['student_email']) ?></small>
                                </td>
                                <td><?= html_escape($p['course_name']) ?></td>
                                <td><?= money($p['amount']) ?><br><small class="text-muted"><?= $p['method'] === 'ecocash' ? 'EcoCash' : 'Bank' ?></small></td>
                                <td>
                                    <?= status_badge($p['status']) ?>
                                    <?php if ($p['status'] === 'rejected' && ! empty($p['admin_note'])): ?>
                                        <div class="small text-muted mt-1"><?= html_escape($p['admin_note']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= html_escape(time_ago($p['reviewed_at'])) ?>
                                    <?php if (! empty($p['reviewer_name'])): ?><br><small class="text-muted">by <?= html_escape($p['reviewer_name']) ?></small><?php endif; ?>
                                </td>
                                <td class="text-nowrap"><a href="<?= base_url('admin_payments/view_proof/' . $p['id']) ?>" target="_blank" class="btn btn-sm btn-light">Proof</a>
                                    <?php if ($p['status'] === 'approved'): ?><a href="<?= base_url('admin_payments/receipt/' . $p['id']) ?>" class="btn btn-sm btn-outline-primary">Receipt</a><?php endif; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php endif; ?>
