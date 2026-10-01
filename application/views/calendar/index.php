<?php
    $today = date('Y-m-d');
    $prev  = date('Y-m', strtotime($first . ' -1 month'));
    $next  = date('Y-m', strtotime($first . ' +1 month'));
    $kinds = ['class' => 'Class', 'event' => 'Event', 'holiday' => 'Holiday', 'deadline' => 'Deadline', 'other' => 'Other', 'assignment' => 'Assignment due', 'exam' => 'Exam'];
    $canEdit = function ($it) use ($manageable) {
        if (! $it['id']) { return false; }
        if ($manageable === null) { return true; }
        return $it['module_id'] !== null && in_array($it['module_id'], $manageable, true);
    };
?>
<div class="page-head">
    <div>
        <h1 class="page-title">Calendar</h1>
        <p class="page-sub">Classes, college events and holidays, with assignment due dates and exam times added automatically.</p>
    </div>
    <?php if ($canAdd): ?>
        <a href="<?= base_url('calendar/add') ?>" class="btn btn-primary"><?= icon('plus', 16) ?> Add event</a>
    <?php endif; ?>
</div>

<div class="card mb-4">
    <div class="card-body pb-2">
        <div class="cal-toolbar">
            <a href="<?= base_url('calendar?m=' . $prev) ?>" class="btn btn-light btn-sm" aria-label="Previous month">&larr;</a>
            <h2 class="cal-month"><?= html_escape(date('F Y', strtotime($first))) ?></h2>
            <a href="<?= base_url('calendar?m=' . $next) ?>" class="btn btn-light btn-sm" aria-label="Next month">&rarr;</a>
            <?php if ($month !== date('Y-m')): ?><a href="<?= base_url('calendar') ?>" class="btn btn-link btn-sm">Today</a><?php endif; ?>
        </div>
    </div>
    <div class="cal-grid" role="grid" aria-label="<?= html_escape(date('F Y', strtotime($first))) ?>">
        <?php foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $d): ?><div class="cal-dow"><?= $d ?></div><?php endforeach; ?>
        <?php for ($d = strtotime($gridStart); $d <= strtotime($gridEnd); $d = strtotime('+1 day', $d)): ?>
            <?php $day = date('Y-m-d', $d); $list = isset($byDay[$day]) ? $byDay[$day] : []; $inMonth = substr($day, 0, 7) === $month; ?>
            <a href="<?= $inMonth && $list ? '#day-' . $day : ($canAdd && $inMonth ? base_url('calendar/add?date=' . $day) : '#') ?>"
               class="cal-day<?= $inMonth ? '' : ' is-out' ?><?= $day === $today ? ' is-today' : '' ?><?= $list ? ' has-items' : '' ?>">
                <span class="cal-num"><?= (int) date('j', $d) ?></span>
                <?php foreach (array_slice($list, 0, 3) as $it): ?>
                    <span class="cal-chip kind-<?= html_escape($it['kind']) ?>"><?= $it['time'] ? html_escape($it['time']) . ' ' : '' ?><?= html_escape($it['title']) ?></span>
                <?php endforeach; ?>
                <?php if (count($list) > 3): ?><span class="cal-more">+<?= count($list) - 3 ?> more</span><?php endif; ?>
                <?php if ($list): ?><span class="cal-dots"><?php foreach (array_slice($list, 0, 4) as $it): ?><i class="kind-<?= html_escape($it['kind']) ?>"></i><?php endforeach; ?></span><?php endif; ?>
            </a>
        <?php endfor; ?>
    </div>
    <div class="card-body pt-2 cal-legend">
        <?php foreach ($kinds as $k => $label): ?><span><i class="kind-<?= $k ?>"></i><?= $label ?></span><?php endforeach; ?>
    </div>
</div>

<div class="card">
    <div class="card-body pb-0"><div class="card-head"><h5 class="card-heading"><?= icon('calendar', 18) ?> <?= html_escape(date('F', strtotime($first))) ?>, day by day</h5></div></div>
    <?php $any = false; ?>
    <ul class="agenda">
        <?php for ($d = strtotime($first); $d <= strtotime($last); $d = strtotime('+1 day', $d)): ?>
            <?php $day = date('Y-m-d', $d); if (empty($byDay[$day])) { continue; } $any = true; ?>
            <li id="day-<?= $day ?>" class="<?= $day === $today ? 'is-today' : ($day < $today ? 'is-past' : '') ?>">
                <div class="due-date"><strong><?= date('j', $d) ?></strong><small><?= date('D', $d) ?></small></div>
                <div class="flex-grow-1 min-w-0">
                    <?php foreach ($byDay[$day] as $it): ?>
                        <div class="agenda-item kind-<?= html_escape($it['kind']) ?>">
                            <div class="d-flex align-items-start gap-2">
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-semibold">
                                        <?php if ($it['url']): ?><a href="<?= $it['url'] ?>" class="text-reset"><?= html_escape($it['title']) ?></a><?php else: ?><?= html_escape($it['title']) ?><?php endif; ?>
                                    </div>
                                    <small class="text-muted">
                                        <?= $it['time'] ? html_escape($it['time']) . ($it['end'] ? '–' . html_escape($it['end']) : '') : 'All day' ?>
                                        &middot; <?= html_escape($kinds[$it['kind']]) ?> &middot; <?= html_escape($it['module'] ?: 'Whole college') ?>
                                        <?php if ($it['where']): ?>&middot; <?= icon('map-pin', 12) ?> <?= html_escape($it['where']) ?><?php endif; ?>
                                    </small>
                                    <?php if ($it['notes']): ?><div class="small mt-1"><?= post_format($it['notes']) ?></div><?php endif; ?>
                                    <?php if ($it['link']): ?><a href="<?= html_escape($it['link']) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary mt-2"><?= icon('link', 14) ?> Join online</a><?php endif; ?>
                                </div>
                                <?php if ($canEdit($it)): ?><a href="<?= base_url('calendar/edit/' . $it['id']) ?>" class="btn btn-light btn-sm">Edit</a><?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </li>
        <?php endfor; ?>
    </ul>
    <?php if (! $any): ?><div class="empty-state pt-2">Nothing in the calendar for <?= html_escape(date('F', strtotime($first))) ?> yet.</div><?php endif; ?>
</div>
