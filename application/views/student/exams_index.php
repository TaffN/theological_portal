<div class="page-head">
    <div>
        <h1 class="page-title">Exams</h1>
        <p class="page-sub">Timed exams from your lecturers. Once you start, the clock runs even if you close the page, so start when you're ready and have a good signal.</p>
    </div>
</div>

<?php if (! $hasModules): ?>
    <div class="card"><div class="empty-state">
        <span class="empty-icon bg-soft-navy"><?= icon('clock', 28) ?></span><br>
        Exams appear once you're enrolled in a module and your payment has been approved.<br>
        <a href="<?= base_url('programs') ?>" class="btn btn-primary btn-sm mt-3">Browse programs</a>
    </div></div>
<?php else: ?>
    <?php
        $sections = [
            'now'      => ['Open now', 'No exam is open right now.'],
            'upcoming' => ['Coming up', 'No exams scheduled yet.'],
            'done'     => ['Finished', 'Exams you have sat will be listed here with your results.'],
        ];
        $iconBg = ['open' => 'bg-soft-green', 'writing' => 'bg-soft-gold', 'scheduled' => 'bg-soft-navy', 'waiting' => 'bg-soft-navy', 'result' => 'bg-soft-green', 'missed' => 'bg-soft-red'];
    ?>
    <?php foreach ($sections as $key => $s): ?>
        <?php if ($key === 'now' && empty($groups['now'])) { continue; } ?>
        <div class="card mb-4 <?= $key === 'now' ? 'exam-now-card' : '' ?>">
            <div class="card-body pb-0">
                <div class="card-head"><h5 class="card-heading"><?= $s[0] ?></h5></div>
            </div>
            <?php if (empty($groups[$key])): ?>
                <div class="empty-state pt-2"><?= $s[1] ?></div>
            <?php else: ?>
                <ul class="issue-list">
                <?php foreach ($groups[$key] as $e): ?>
                    <li>
                        <a href="<?= base_url('student_exams/view/' . $e['id']) ?>">
                            <span class="stat-icon stat-icon-sm <?= $iconBg[$e['state']] ?>"><?= icon($e['state'] === 'result' ? 'award' : 'clock', 16) ?></span>
                            <span class="flex-grow-1 min-w-0">
                                <span class="issue-title"><?= html_escape($e['title']) ?></span>
                                <span class="issue-meta">
                                    <?= html_escape($e['module_name']) ?> &middot; <?= (int) $e['duration_minutes'] ?> min &middot;
                                    <?php if ($e['state'] === 'scheduled'): ?>
                                        opens <?= html_escape(date('D j M, H:i', strtotime($e['opens_at']))) ?> (<?= html_escape(due_in($e['opens_at'])) ?>)
                                    <?php elseif ($e['state'] === 'open'): ?>
                                        closes <?= html_escape(date('D j M, H:i', strtotime($e['closes_at']))) ?>
                                    <?php elseif ($e['state'] === 'writing'): ?>
                                        in progress: <?= (int) floor(max(0, strtotime($e['att_deadline_at']) - time()) / 60) ?> min left
                                    <?php elseif ($e['state'] === 'missed'): ?>
                                        closed <?= html_escape(time_ago($e['closes_at'])) ?>
                                    <?php else: ?>
                                        sat <?= html_escape(time_ago($e['att_started_at'])) ?>
                                    <?php endif; ?>
                                </span>
                            </span>
                            <?php if ($e['state'] === 'result'): ?>
                                <span class="score-chip"><?= html_escape(score_fmt($e['att_total_score'])) ?>/<?= html_escape(score_fmt($e['att_max_score'])) ?></span>
                            <?php else: ?>
                                <?= status_badge('exam_' . $e['state']) ?>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
