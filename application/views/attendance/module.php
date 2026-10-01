<?= module_crumbs($module, 'Attendance') ?: '<p class="mb-3"><a href="' . base_url($back) . '" class="back-link">&larr; Attendance</a></p>' ?>
<div class="page-head">
    <div class="min-w-0">
        <h1 class="page-title"><?= html_escape($module['name']) ?></h1>
        <p class="page-sub mb-0"><?= count($sessions) ?> register<?= count($sessions) == 1 ? '' : 's' ?> taken. Rate = present + late, out of every class the student was marked for (excused absences don't count).</p>
    </div>
    <div class="d-flex gap-2">
        <?php if ($canTake): ?><a href="<?= base_url('lecturer_attendance/take/' . $module['id']) ?>" class="btn btn-primary"><?= icon('check-square', 16) ?> Take register</a><?php endif; ?>
        <?php if (! $canTake): ?><a href="<?= base_url('admin_attendance/export/' . $module['id']) ?>" class="btn btn-outline-primary"><?= icon('upload', 16) ?> Export CSV</a><?php endif; ?>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-body pb-0"><div class="card-head"><h5 class="card-heading"><?= icon('users', 18) ?> Students</h5><span class="card-sub">Lowest attendance first</span></div></div>
            <?php if (! $summary): ?>
                <div class="empty-state pt-2">No students have access to this module yet.</div>
            <?php else: ?>
                <?php usort($summary, function ($a, $b) { return ($a['rate'] === null ? 101 : $a['rate']) <=> ($b['rate'] === null ? 101 : $b['rate']); }); ?>
                <ul class="issue-list">
                <?php foreach ($summary as $s): ?>
                    <li><div class="issue-row">
                        <?= avatar_html($s['name'], $s['id'], $s['photo_path'] ? $s['photo_updated_at'] : null, 'avatar-sm') ?>
                        <div class="flex-grow-1 min-w-0">
                            <div class="fw-semibold text-truncate"><?= html_escape($s['name']) ?></div>
                            <small class="text-muted"><span class="id-chip"><?= html_escape($s['id_number']) ?></span>
                                <?= (int) $s['present'] ?> present &middot; <?= (int) $s['late'] ?> late &middot; <?= (int) $s['absent'] ?> absent<?= $s['excused'] ? ' &middot; ' . (int) $s['excused'] . ' excused' : '' ?></small>
                        </div>
                        <?php if ($canTake && $s['rate'] !== null && $s['rate'] < 75 && $s['phone']): ?>
                            <a href="<?= wa_link($s['phone'], 'Hello ' . $s['name'] . ', we have missed you in ' . $module['name'] . '. Is everything well?') ?>" target="_blank" rel="noopener" class="btn btn-sm btn-light" title="Check in on WhatsApp"><?= icon('message', 14) ?></a>
                        <?php endif; ?>
                        <span class="rate-pill <?= rate_tone($s['rate']) ?>"><?= $s['rate'] === null ? '&ndash;' : html_escape(score_fmt(round($s['rate']))) . '%' ?></span>
                    </div></li>
                <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-body pb-0"><div class="card-head"><h5 class="card-heading"><?= icon('calendar', 18) ?> Registers</h5></div></div>
            <?php if (! $sessions): ?>
                <div class="empty-state pt-2">No registers yet.</div>
            <?php else: ?>
                <ul class="issue-list">
                <?php foreach ($sessions as $s): ?>
                    <li><div class="issue-row">
                        <div class="due-date"><strong><?= date('j', strtotime($s['session_date'])) ?></strong><small><?= date('M', strtotime($s['session_date'])) ?></small></div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="fw-semibold text-truncate"><?= html_escape($s['topic'] ?: date('l', strtotime($s['session_date']))) ?></div>
                            <small class="text-muted"><?= (int) $s['present'] ?> present &middot; <?= (int) $s['late'] ?> late &middot; <?= (int) $s['absent'] ?> absent<?= $s['excused'] ? ' &middot; ' . (int) $s['excused'] . ' excused' : '' ?></small>
                        </div>
                        <?php if ($canTake): ?><a href="<?= base_url('lecturer_attendance/take/' . $module['id'] . '/' . $s['id']) ?>" class="btn btn-sm btn-light">Edit</a><?php endif; ?>
                    </div></li>
                <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>
