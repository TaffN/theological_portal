<?php
    $past     = strtotime($a['due_at']) < time();
    $students = count($roster);
?>
<p class="mb-3"><a href="<?= base_url('lecturer_assignments') ?>" class="back-link">&larr; Assignments</a></p>

<div class="page-head">
    <div class="min-w-0">
        <h1 class="page-title"><?= html_escape($a['title']) ?></h1>
        <p class="page-sub mb-0"><?= html_escape($a['course_name']) ?> &middot;
            <?= $past ? 'Closed' : 'Due' ?> <?= html_escape(date('l j M Y, H:i', strtotime($a['due_at']))) ?> (<?= html_escape(due_in($a['due_at'])) ?>)
            &middot; out of <?= (int) $a['max_score'] ?> &middot; <?= $a['allow_late'] ? 'late work accepted' : 'no late work' ?></p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= base_url('lecturer_assignments/edit/' . $a['id']) ?>" class="btn btn-outline-primary"><?= icon('edit', 16) ?> Edit</a>
        <?php if ($submitted === 0): ?>
            <form method="post" action="<?= base_url('lecturer_assignments/delete/' . $a['id']) ?>" data-confirm="Delete this assignment? Students will no longer see it." data-confirm-ok="Delete" data-confirm-danger>
                <button type="submit" class="btn btn-light">Delete</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3"><div class="card stat-card"><div class="stat-icon bg-soft-navy"><?= icon('users', 22) ?></div>
        <div><div class="stat-value"><?= (int) $submitted ?><small class="stat-of">/<?= (int) $students ?></small></div><div class="stat-label">Handed in</div></div></div></div>
    <div class="col-6 col-xl-3"><div class="card stat-card"><div class="stat-icon bg-soft-gold"><?= icon('edit', 22) ?></div>
        <div><div class="stat-value"><?= (int) ($submitted - $marked) ?></div><div class="stat-label">Waiting to be marked</div></div></div></div>
    <div class="col-6 col-xl-3"><div class="card stat-card"><div class="stat-icon bg-soft-green"><?= icon('check', 22) ?></div>
        <div><div class="stat-value"><?= (int) $marked ?></div><div class="stat-label">Marked</div></div></div></div>
    <div class="col-6 col-xl-3"><div class="card stat-card"><div class="stat-icon bg-soft-blue"><?= icon('award', 22) ?></div>
        <div><div class="stat-value"><?= $average === null ? '—' : html_escape(score_fmt(round($average, 1))) ?></div><div class="stat-label">Average mark (of <?= (int) $a['max_score'] ?>)</div></div></div></div>
</div>

<?php if ($a['instructions'] || $a['attachment_path']): ?>
<div class="card mb-4">
    <div class="card-body">
        <div class="card-head"><h5 class="card-heading">Instructions</h5></div>
        <?php if ($a['instructions']): ?><div class="prose"><?= nl2br(html_escape($a['instructions'])) ?></div><?php endif; ?>
        <?php if ($a['attachment_path']): ?>
            <a href="<?= base_url('lecturer_assignments/attachment/' . $a['id']) ?>" target="_blank" class="attach-row mt-3"><?= icon('file', 18) ?> <span class="text-truncate"><?= html_escape($a['attachment_name']) ?></span></a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-body pb-0">
        <div class="card-head">
            <h5 class="card-heading">Students</h5>
            <span class="card-sub">Work waiting for a mark is listed first</span>
        </div>
    </div>
    <?php if (empty($roster)): ?>
        <div class="empty-state pt-2">No students have access to this course yet. They appear here once their payment is approved.</div>
    <?php else: ?>
    <ul class="submission-list">
    <?php foreach ($roster as $r): ?>
        <?php
            $has    = (bool) $r['sub_id'];
            $isOpen = $has && ! $r['graded_at'];
        ?>
        <li id="<?= $has ? 'sub-' . (int) $r['sub_id'] : 'student-' . (int) $r['student_id'] ?>">
            <div class="submission-row">
                <?= avatar_html($r['name'], $r['student_id'], $r['photo_path'] ? $r['photo_updated_at'] : null, 'avatar-sm') ?>
                <div class="flex-grow-1 min-w-0">
                    <div class="fw-semibold text-truncate"><?= html_escape($r['name']) ?></div>
                    <small class="text-muted">
                        <span class="id-chip"><?= html_escape($r['id_number']) ?></span>
                        <?php if ($has): ?>
                            Handed in <?= html_escape(time_ago($r['submitted_at'])) ?><?= $r['attempts'] > 1 ? ' &middot; version ' . (int) $r['attempts'] : '' ?>
                        <?php else: ?>
                            Not handed in
                        <?php endif; ?>
                    </small>
                </div>
                <div class="d-flex align-items-center gap-2 flex-shrink-0">
                    <?php if ($has && $r['is_late']): ?><?= status_badge('late') ?><?php endif; ?>
                    <?php if ($has && $r['graded_at']): ?>
                        <span class="score-chip"><?= html_escape(score_fmt($r['score'])) ?>/<?= (int) $a['max_score'] ?></span>
                    <?php elseif ($isOpen): ?>
                        <?= status_badge('to_mark') ?>
                    <?php elseif (! $has && $past): ?>
                        <?= status_badge($a['allow_late'] ? 'overdue' : 'missed') ?>
                    <?php endif; ?>

                    <?php if ($has): ?>
                        <button type="button" class="btn btn-sm <?= $isOpen ? 'btn-primary' : 'btn-light' ?>" data-bs-toggle="collapse" data-bs-target="#mark-<?= (int) $r['sub_id'] ?>" aria-expanded="false">
                            <?= $isOpen ? 'Mark' : 'View' ?>
                        </button>
                    <?php elseif ($r['phone']): ?>
                        <a class="btn btn-sm btn-light" target="_blank" rel="noopener" title="Send a WhatsApp reminder"
                           href="<?= html_escape(wa_link($r['phone'], 'Hello ' . display_first_name($r['name']) . ', a reminder that "' . $a['title'] . '" (' . $a['course_name'] . ') was due ' . date('D j M, H:i', strtotime($a['due_at'])) . '. Please hand it in on the portal: ' . base_url('student_assignments/view/' . $a['id']))) ?>">Remind</a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($has): ?>
            <div class="collapse" id="mark-<?= (int) $r['sub_id'] ?>">
                <div class="submission-detail">
                    <?php if ($r['file_path']): ?>
                        <a href="<?= base_url('lecturer_assignments/submission_file/' . $r['sub_id']) ?>" target="_blank" class="attach-row mb-3">
                            <?= icon('file', 18) ?> <span class="text-truncate"><?= html_escape($r['original_name']) ?></span> <span class="ms-auto small text-muted text-nowrap">Open</span>
                        </a>
                    <?php endif; ?>
                    <?php if ($r['answer_text']): ?>
                        <div class="answer-box mb-3"><?= nl2br(html_escape($r['answer_text'])) ?></div>
                    <?php endif; ?>
                    <p class="small text-muted mb-3">Handed in <?= html_escape(date('D j M Y, H:i', strtotime($r['submitted_at']))) ?><?= $r['is_late'] ? ' (after the due date)' : '' ?>.</p>

                    <form method="post" action="<?= base_url('lecturer_assignments/grade/' . $r['sub_id']) ?>" class="row g-2">
                        <div class="col-sm-3">
                            <label class="form-label">Mark (out of <?= (int) $a['max_score'] ?>)</label>
                            <input type="number" name="score" class="form-control" min="0" max="<?= (int) $a['max_score'] ?>" step="any" required
                                   value="<?= $r['graded_at'] ? html_escape(score_fmt($r['score'])) : '' ?>" inputmode="decimal">
                        </div>
                        <div class="col-sm-9">
                            <label class="form-label">Feedback for the student</label>
                            <textarea name="feedback" class="form-control" rows="3" placeholder="What was good, and what to work on next time"><?= html_escape((string) $r['feedback']) ?></textarea>
                        </div>
                        <div class="col-12 d-flex align-items-center gap-3">
                            <button type="submit" class="btn btn-primary"><?= $r['graded_at'] ? 'Update mark' : 'Save mark &amp; notify student' ?></button>
                            <?php if ($r['graded_at']): ?><small class="text-muted">Marked <?= html_escape(time_ago($r['graded_at'])) ?></small><?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </li>
    <?php endforeach; ?>
    </ul>
    <?php endif; ?>
</div>
