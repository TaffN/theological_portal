<div class="page-head">
    <div>
        <h1 class="page-title">My attendance</h1>
        <p class="page-sub">Your attendance in each course, from your lecturers' registers. Excused absences don't count against you.</p>
    </div>
</div>
<?php if (! $courses): ?>
    <div class="card"><div class="empty-state"><span class="empty-icon bg-soft-navy"><?= icon('check-square', 28) ?></span><br>Your attendance appears here once your course is paid up and your lecturer starts taking registers.</div></div>
<?php else: ?>
    <div class="row g-3">
    <?php foreach ($courses as $c): $d = $data[(int) $c['id']]; ?>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div class="rate-ring <?= rate_tone($d['rate']) ?>"><?= $d['rate'] === null ? '&ndash;' : html_escape(score_fmt(round($d['rate']))) . '<small>%</small>' ?></div>
                        <div class="min-w-0">
                            <h5 class="card-heading mb-1"><?= html_escape($c['name']) ?></h5>
                            <small class="text-muted"><?= $d['sessions'] ? (int) $d['present'] . ' present &middot; ' . (int) $d['late'] . ' late &middot; ' . (int) $d['absent'] . ' absent' . ($d['excused'] ? ' &middot; ' . (int) $d['excused'] . ' excused' : '') : 'No registers taken yet.' ?></small>
                        </div>
                    </div>
                    <?php if ($d['all']): ?>
                        <ul class="mark-list">
                        <?php foreach ($d['all'] as $m): ?>
                            <li>
                                <span class="text-muted small mark-date"><?= html_escape(date('D j M', strtotime($m['session_date']))) ?></span>
                                <span class="flex-grow-1 min-w-0 text-truncate small"><?= html_escape($m['topic'] ?: 'Class') ?><?= $m['note'] ? ' <span class="text-muted">(' . html_escape($m['note']) . ')</span>' : '' ?></span>
                                <span class="mark-tag mark-<?= html_escape($m['status']) ?>"><?= html_escape($statuses[$m['status']]) ?></span>
                            </li>
                        <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
<?php endif; ?>
