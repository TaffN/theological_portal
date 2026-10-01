<div class="page-head">
    <div>
        <h1 class="page-title">Attendance</h1>
        <p class="page-sub">How often students attend, per module. Lecturers take the registers.</p>
    </div>
</div>
<div class="card">
    <?php if (! $modules): ?>
        <div class="empty-state">No modules yet.</div>
    <?php else: ?>
        <ul class="issue-list">
        <?php foreach ($modules as $c): ?>
            <li><a href="<?= base_url('admin_attendance/module/' . $c['id']) ?>">
                <div class="module-dot"><?= html_escape(initials($c['name'])) ?></div>
                <div class="flex-grow-1 min-w-0">
                    <div class="fw-semibold text-truncate"><?= html_escape($c['name']) ?></div>
                    <small class="text-muted"><?= (int) $c['students'] ?> student<?= $c['students'] == 1 ? '' : 's' ?> &middot; <?= (int) $c['sessions'] ?> register<?= $c['sessions'] == 1 ? '' : 's' ?><?= $c['last_date'] ? ' &middot; last ' . html_escape(date('j M Y', strtotime($c['last_date']))) : '' ?></small>
                </div>
                <span class="rate-pill <?= rate_tone($c['rate']) ?>"><?= $c['rate'] === null ? '&ndash;' : html_escape(score_fmt(round($c['rate']))) . '%' ?></span>
            </a></li>
        <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
