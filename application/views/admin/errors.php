<?php
    $sevClass = ['critical' => 'pill-danger', 'high' => 'pill-danger', 'medium' => 'pill-warning', 'low' => 'pill-muted'];
    $qs = $source ? '?source=' . $source : '';
?>
<div class="page-head">
    <div>
        <h1 class="page-title">Error reports</h1>
        <p class="page-sub">Problems users reported, plus errors the system caught on its own. Repeats of the same error are grouped.</p>
    </div>
    <a href="<?= base_url('support/report') ?>" class="btn btn-outline-primary"><?= icon('plus', 16) ?> Log an issue</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3"><div class="card stat-card"><div class="stat-icon bg-soft-red"><?= icon('alert', 22) ?></div><div><div class="stat-value"><?= $counts['open'] ?></div><div class="stat-label">Open</div></div></div></div>
    <div class="col-6 col-lg-3"><div class="card stat-card"><div class="stat-icon bg-soft-gold"><?= icon('shield', 22) ?></div><div><div class="stat-value"><?= $counts['critical_open'] ?></div><div class="stat-label">High / critical open</div></div></div></div>
    <div class="col-6 col-lg-3"><div class="card stat-card"><div class="stat-icon bg-soft-green"><?= icon('check', 22) ?></div><div><div class="stat-value"><?= $counts['resolved'] ?></div><div class="stat-label">Resolved</div></div></div></div>
    <div class="col-6 col-lg-3"><div class="card stat-card"><div class="stat-icon bg-soft-navy"><?= icon('x', 22) ?></div><div><div class="stat-value"><?= $counts['ignored'] ?></div><div class="stat-label">Ignored</div></div></div></div>
</div>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <div class="tabs">
        <?php foreach (['open' => 'Open', 'resolved' => 'Resolved', 'ignored' => 'Ignored'] as $k => $label): ?>
            <a href="<?= base_url('admin_errors/index/' . $k . $qs) ?>" class="tab <?= $status === $k ? 'active' : '' ?>"><?= $label ?>
                <?php if ($k === 'open' && $counts['open']): ?><span class="count-pill"><?= $counts['open'] ?></span><?php endif; ?></a>
        <?php endforeach; ?>
    </div>
    <div class="chip-row">
        <a href="<?= base_url('admin_errors/index/' . $status) ?>" class="chip <?= ! $source ? 'active' : '' ?>">All sources</a>
        <?php foreach ($sources as $k => $s): ?>
            <a href="<?= base_url('admin_errors/index/' . $status . '?source=' . $k) ?>" class="chip <?= $source === $k ? 'active' : '' ?>"><?= icon($s[1], 14) ?> <?= $s[0] ?></a>
        <?php endforeach; ?>
    </div>
</div>

<div class="card">
    <?php if (empty($errors)): ?>
        <div class="empty-state">
            <span class="empty-icon bg-soft-green"><?= icon('check', 28) ?></span>
            <strong class="d-block text-body mt-2"><?= $status === 'open' ? 'All clear' : 'Nothing here' ?></strong>
            <?= $status === 'open' ? 'No open problems right now.' : 'No ' . $status . ' reports' . ($source ? ' from this source' : '') . '.' ?>
        </div>
    <?php else: ?>
        <ul class="issue-list">
        <?php foreach ($errors as $e): ?>
            <li>
                <a href="<?= base_url('admin_errors/view/' . $e['id']) ?>">
                    <span class="stat-icon stat-icon-sm <?= $e['source'] === 'user' ? 'bg-soft-blue' : 'bg-soft-red' ?>"><?= icon($sources[$e['source']][1], 16) ?></span>
                    <span class="flex-grow-1 min-w-0">
                        <span class="issue-title"><?= html_escape($e['title']) ?></span>
                        <span class="issue-meta">
                            <code><?= html_escape($e['reference']) ?></code> &middot; <?= $sources[$e['source']][0] ?>
                            <?php if ($e['user_name']): ?> &middot; <?= html_escape($e['user_name']) ?><?php endif; ?>
                            &middot; <?= html_escape(time_ago($e['last_seen'])) ?>
                        </span>
                    </span>
                    <?php if ($e['occurrences'] > 1): ?><span class="occ" title="Times this happened">×<?= (int) $e['occurrences'] ?></span><?php endif; ?>
                    <span class="pill <?= $sevClass[$e['severity']] ?>"><?= ucfirst($e['severity']) ?></span>
                </a>
            </li>
        <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
