<?php
    $icons = ['auth' => ['user', 'bg-soft-navy'], 'payment' => ['card', 'bg-soft-green'], 'course' => ['book', 'bg-soft-blue'],
              'material' => ['file', 'bg-soft-gold'], 'assignment' => ['edit', 'bg-soft-gold'], 'submission' => ['upload', 'bg-soft-blue'], 'exam' => ['clock', 'bg-soft-gold'], 'attempt' => ['clock', 'bg-soft-blue'], 'result' => ['award', 'bg-soft-green'], 'grading' => ['layers', 'bg-soft-gold'], 'ezra' => ['message', 'bg-soft-blue'], 'discussion' => ['chat', 'bg-soft-blue'], 'calendar' => ['calendar', 'bg-soft-gold'], 'library' => ['library', 'bg-soft-navy'], 'attendance' => ['check-square', 'bg-soft-green'], 'document' => ['file', 'bg-soft-blue'], 'report' => ['chart', 'bg-soft-navy'], 'user' => ['users', 'bg-soft-navy'], 'profile' => ['user', 'bg-soft-blue'],
              'error' => ['alert', 'bg-soft-red'], 'support' => ['alert', 'bg-soft-red'], 'announcement' => ['bell', 'bg-soft-gold'],
              'audit' => ['shield', 'bg-soft-navy'], 'enrollment' => ['book', 'bg-soft-green']];
    $qsBase = array_filter($f);
    $link = function ($page) use ($qsBase) { return base_url('admin_audit') . '?' . http_build_query(array_merge($qsBase, ['page' => $page])); };
?>
<div class="page-head">
    <div>
        <h1 class="page-title">Audit trail</h1>
        <p class="page-sub">A permanent record of who did what, and when. Entries can't be edited or deleted.</p>
    </div>
    <a href="<?= base_url('admin_audit/export') . ($qsBase ? '?' . http_build_query($qsBase) : '') ?>" class="btn btn-outline-primary"><?= icon('upload', 16) ?> Export CSV</a>
</div>

<form method="get" action="<?= base_url('admin_audit') ?>" class="card mb-3 filter-bar">
    <div class="card-body row g-2 align-items-end">
        <div class="col-md-4"><label class="form-label">Search</label><input type="search" name="q" value="<?= html_escape($f['q']) ?>" class="form-control" placeholder="Name, action or IP"></div>
        <div class="col-6 col-md-2"><label class="form-label">Area</label>
            <select name="group" class="form-select"><option value="">All</option>
                <?php foreach ($groups as $g): ?><option value="<?= html_escape($g) ?>" <?= $f['group'] === $g ? 'selected' : '' ?>><?= html_escape(ucfirst($g)) ?></option><?php endforeach; ?>
            </select></div>
        <div class="col-6 col-md-2"><label class="form-label">From</label><input type="date" name="from" value="<?= html_escape($f['from']) ?>" class="form-control"></div>
        <div class="col-6 col-md-2"><label class="form-label">To</label><input type="date" name="to" value="<?= html_escape($f['to']) ?>" class="form-control"></div>
        <div class="col-6 col-md-2 d-flex gap-2"><button class="btn btn-primary flex-grow-1">Filter</button>
            <?php if ($qsBase): ?><a href="<?= base_url('admin_audit') ?>" class="btn btn-light" title="Clear"><?= icon('x', 16) ?></a><?php endif; ?></div>
    </div>
</form>

<div class="card">
    <?php if (empty($rows)): ?>
        <div class="empty-state"><span class="empty-icon bg-soft-navy"><?= icon('shield', 28) ?></span><br>No activity matches these filters.</div>
    <?php else: ?>
        <ul class="timeline">
        <?php $lastDay = null; foreach ($rows as $r): ?>
            <?php
                $day = date('Y-m-d', strtotime($r['created_at']));
                $grp = strtok($r['action'], '.');
                list($ic, $bg) = isset($icons[$grp]) ? $icons[$grp] : ['grid', 'bg-soft-navy'];
                $bad = strpos($r['action'], 'failed') !== false || strpos($r['action'], 'locked') !== false || strpos($r['action'], 'rejected') !== false;
            ?>
            <?php if ($day !== $lastDay): $lastDay = $day; ?>
                <li class="timeline-day"><?= $day === date('Y-m-d') ? 'Today' : ($day === date('Y-m-d', strtotime('-1 day')) ? 'Yesterday' : date('l, j F Y', strtotime($day))) ?></li>
            <?php endif; ?>
            <li class="timeline-item">
                <span class="stat-icon stat-icon-sm <?= $bad ? 'bg-soft-red' : $bg ?>"><?= icon($bad ? 'alert' : $ic, 15) ?></span>
                <div class="flex-grow-1 min-w-0">
                    <div class="tl-desc"><?= html_escape($r['description']) ?></div>
                    <div class="tl-meta">
                        <?= $r['user_name'] ? html_escape($r['user_name']) . ' (' . html_escape($r['role']) . ')' : 'Guest' ?>
                        &middot; <code><?= html_escape($r['action']) ?></code> &middot; <?= html_escape($r['ip_address']) ?>
                    </div>
                </div>
                <time class="tl-time" title="<?= html_escape($r['created_at']) ?>"><?= date('H:i', strtotime($r['created_at'])) ?></time>
            </li>
        <?php endforeach; ?>
        </ul>
        <div class="d-flex justify-content-between align-items-center p-3 border-top">
            <small class="text-muted"><?= number_format($total) ?> entr<?= $total == 1 ? 'y' : 'ies' ?> &middot; page <?= $page ?> of <?= $pages ?></small>
            <div class="d-flex gap-2">
                <?php if ($page > 1): ?><a class="btn btn-sm btn-light" href="<?= $link($page - 1) ?>">&larr; Newer</a><?php endif; ?>
                <?php if ($page < $pages): ?><a class="btn btn-sm btn-light" href="<?= $link($page + 1) ?>">Older &rarr;</a><?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
