<div class="page-head">
    <div>
        <h1 class="page-title">Attendance</h1>
        <p class="page-sub">Take the register for each class. Students see their own attendance, and the office sees every module.</p>
    </div>
</div>
<?php if (! $modules): ?>
    <div class="card"><div class="empty-state"><span class="empty-icon bg-soft-navy"><?= icon('check-square', 28) ?></span><br>You aren't assigned to any module yet.</div></div>
<?php else: ?>
    <div class="row g-3">
    <?php foreach ($modules as $c): ?>
        <div class="col-md-6 col-xl-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div class="rate-ring <?= rate_tone($c['rate']) ?>"><?= $c['rate'] === null ? '&ndash;' : html_escape(score_fmt(round($c['rate']))) . '<small>%</small>' ?></div>
                        <div class="min-w-0">
                            <h5 class="card-heading mb-1"><?= html_escape($c['name']) ?></h5>
                            <small class="text-muted"><?= (int) $c['students'] ?> student<?= $c['students'] == 1 ? '' : 's' ?> &middot; <?= (int) $c['sessions'] ?> register<?= $c['sessions'] == 1 ? '' : 's' ?><?= $c['last'] ? ' &middot; last ' . html_escape(date('j M', strtotime($c['last']))) : '' ?></small>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="<?= base_url('lecturer_attendance/take/' . $c['id']) ?>" class="btn btn-primary btn-sm"><?= icon('check-square', 15) ?> Take register</a>
                        <a href="<?= base_url('lecturer_attendance/module/' . $c['id']) ?>" class="btn btn-light btn-sm">View</a>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
<?php endif; ?>
