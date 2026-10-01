<div class="page-head">
    <div>
        <h1 class="page-title">Assignments</h1>
        <p class="page-sub">Work set by your lecturers. Hand it in here before the due date, then come back for your mark and feedback.</p>
    </div>
</div>

<?php if (! $hasModules): ?>
    <div class="card"><div class="empty-state">
        <span class="empty-icon bg-soft-navy"><?= icon('edit', 28) ?></span><br>
        Assignments appear once you're enrolled in a module and your payment has been approved.<br>
        <a href="<?= base_url('programs') ?>" class="btn btn-primary btn-sm mt-3">Browse programs</a>
    </div></div>
<?php else: ?>

    <?php
        $sections = [
            'todo' => ['To hand in', 'Nothing to hand in right now. Well done!'],
            'done' => ['Handed in & marked', 'Work you hand in will be listed here with your mark.'],
        ];
    ?>
    <?php foreach ($sections as $key => $s): ?>
        <div class="card mb-4">
            <div class="card-body pb-0">
                <div class="card-head">
                    <h5 class="card-heading"><?= $s[0] ?></h5>
                    <?php if (! empty($groups[$key])): ?><span class="card-sub"><?= count($groups[$key]) ?></span><?php endif; ?>
                </div>
            </div>
            <?php if (empty($groups[$key])): ?>
                <div class="empty-state pt-2"><?= $s[1] ?></div>
            <?php else: ?>
                <ul class="issue-list">
                <?php foreach ($groups[$key] as $a): ?>
                    <?php $iconBg = ['todo' => 'bg-soft-gold', 'overdue' => 'bg-soft-red', 'missed' => 'bg-soft-red', 'submitted' => 'bg-soft-navy', 'graded' => 'bg-soft-green']; ?>
                    <li>
                        <a href="<?= base_url('student_assignments/view/' . $a['id']) ?>">
                            <span class="stat-icon stat-icon-sm <?= $iconBg[$a['state']] ?>"><?= icon($a['state'] === 'graded' ? 'award' : 'edit', 16) ?></span>
                            <span class="flex-grow-1 min-w-0">
                                <span class="issue-title"><?= html_escape($a['title']) ?></span>
                                <span class="issue-meta">
                                    <?= html_escape($a['module_name']) ?> &middot;
                                    <?php if ($a['state'] === 'submitted' || $a['state'] === 'graded'): ?>
                                        handed in <?= html_escape(time_ago($a['sub_submitted_at'])) ?>
                                    <?php else: ?>
                                        due <?= html_escape(date('D j M, H:i', strtotime($a['due_at']))) ?> (<?= html_escape(due_in($a['due_at'])) ?>)
                                    <?php endif; ?>
                                </span>
                            </span>
                            <?php if ($a['state'] === 'graded'): ?>
                                <span class="score-chip"><?= html_escape(score_fmt($a['sub_score'])) ?>/<?= (int) $a['max_score'] ?></span>
                            <?php else: ?>
                                <?= status_badge($a['state']) ?>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

<?php endif; ?>
