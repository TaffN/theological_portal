<div class="page-head">
    <div>
        <h1 class="page-title">Exams</h1>
        <p class="page-sub">Write timed exams with multiple-choice and short-answer questions. Multiple choice is marked automatically; you mark the short answers.</p>
    </div>
</div>

<?php if (empty($groups)): ?>
    <div class="card"><div class="empty-state">
        <span class="empty-icon bg-soft-navy"><?= icon('clock', 28) ?></span><br>
        You haven't been assigned to a course yet. Ask the administrator to assign you, then you can set exams here.
    </div></div>
<?php endif; ?>

<?php foreach ($groups as $g): ?>
    <?php $c = $g['course']; ?>
    <div class="card mb-4">
        <div class="card-body pb-0">
            <div class="card-head">
                <h5 class="card-heading"><span class="course-dot course-dot-sm"><?= html_escape(initials($c['name'])) ?></span> <?= html_escape($c['name']) ?></h5>
                <a href="<?= base_url('lecturer_exams/create/' . $c['id']) ?>" class="btn btn-primary btn-sm"><?= icon('plus', 16) ?> New exam</a>
            </div>
        </div>
        <?php if (empty($g['exams'])): ?>
            <div class="empty-state pt-2">No exams in this course yet.</div>
        <?php else: ?>
            <ul class="issue-list">
            <?php foreach ($g['exams'] as $e): ?>
                <?php $phase = Exam_model::phase($e); ?>
                <li>
                    <a href="<?= base_url('lecturer_exams/view/' . $e['id']) ?>">
                        <span class="stat-icon stat-icon-sm <?= $phase === 'open' ? 'bg-soft-green' : ($phase === 'draft' ? 'bg-soft-navy' : 'bg-soft-gold') ?>"><?= icon('clock', 16) ?></span>
                        <span class="flex-grow-1 min-w-0">
                            <span class="issue-title"><?= html_escape($e['title']) ?></span>
                            <span class="issue-meta">
                                <?= html_escape(date('D j M, H:i', strtotime($e['opens_at']))) ?> – <?= html_escape(date('D j M, H:i', strtotime($e['closes_at']))) ?>
                                &middot; <?= (int) $e['duration_minutes'] ?> min &middot; <?= (int) $e['question_total'] ?> question<?= $e['question_total'] == 1 ? '' : 's' ?>
                                <?php if ($e['attempts'] > 0): ?>&middot; <?= (int) $e['attempts'] ?> sat<?php endif; ?>
                            </span>
                        </span>
                        <?php if ($e['to_mark'] > 0): ?><span class="pill pill-warning"><?= (int) $e['to_mark'] ?> to mark</span><?php endif; ?>
                        <?php if ($e['results_released']): ?>
                            <span class="pill pill-success">Results out</span>
                        <?php else: ?>
                            <?= status_badge('exam_' . $phase) ?>
                        <?php endif; ?>
                    </a>
                </li>
            <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
