<div class="page-head">
    <div>
        <h1 class="page-title">My Payments</h1>
        <p class="page-sub">Everything you've submitted, with official receipts once approved.</p>
    </div>
    <a href="<?= base_url('programs') ?>" class="btn btn-outline-primary"><?= icon('book', 16) ?> Programs</a>
</div>

<div class="card">
    <?php if (empty($payments)): ?>
        <div class="empty-state"><span class="empty-icon bg-soft-green"><?= icon('card', 28) ?></span><br>You haven't submitted any payments yet.<br>
            <a href="<?= base_url('programs') ?>" class="btn btn-primary btn-sm mt-3">Browse programs</a></div>
    <?php else: ?>
        <ul class="issue-list">
        <?php foreach ($payments as $p): ?>
            <li><div class="issue-row">
                <span class="stat-icon stat-icon-sm <?= $p['status'] === 'approved' ? 'bg-soft-green' : ($p['status'] === 'rejected' ? 'bg-soft-red' : 'bg-soft-gold') ?>"><?= icon($p['status'] === 'approved' ? 'check' : ($p['status'] === 'rejected' ? 'alert' : 'clock'), 16) ?></span>
                <span class="flex-grow-1 min-w-0">
                    <span class="issue-title"><?= html_escape($p['module_name']) ?> &middot; <?= money($p['amount']) ?></span>
                    <span class="issue-meta"><?= $p['method'] === 'ecocash' ? 'EcoCash' : 'Bank transfer' ?> &middot; sent <?= html_escape(time_ago($p['submitted_at'])) ?>
                        <?php if ($p['status'] === 'rejected' && $p['admin_note']): ?><br><span class="text-danger-soft">Reason: <?= html_escape($p['admin_note']) ?></span><?php endif; ?></span>
                </span>
                <?= status_badge($p['status']) ?>
                <?php if ($p['status'] === 'approved'): ?>
                    <a href="<?= base_url('payments/receipt/' . $p['id']) ?>" class="btn btn-sm btn-outline-primary">Receipt</a>
                <?php elseif ($p['status'] === 'rejected'): ?>
                    <a href="<?= base_url('payments/upload/' . $p['enrollment_id']) ?>" class="btn btn-sm btn-gold">Upload again</a>
                <?php endif; ?>
            </div></li>
        <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
