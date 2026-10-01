<?php
    $letters = 'ABCDEF';
    $perStudent = $e['question_count'] && $e['question_count'] < count($questions) ? (int) $e['question_count'] : count($questions);
?>
<p class="mb-3"><a href="<?= base_url('lecturer_exams') ?>" class="back-link">&larr; Exams</a></p>

<div class="page-head">
    <div class="min-w-0">
        <h1 class="page-title"><?= html_escape($e['title']) ?></h1>
        <p class="page-sub mb-0"><?= html_escape($e['module_name']) ?> &middot;
            <?= html_escape(date('D j M, H:i', strtotime($e['opens_at']))) ?> – <?= html_escape(date('D j M, H:i', strtotime($e['closes_at']))) ?>
            &middot; <?= (int) $e['duration_minutes'] ?> minutes</p>
    </div>
    <div class="d-flex flex-wrap gap-2 align-items-center">
        <?= $e['results_released'] ? '<span class="pill pill-success">Results out</span>' : status_badge('exam_' . $phase) ?>
        <?php if ($phase === 'open' || ($phase === 'closed' && $stats['started'] > 0)): ?>
            <a href="<?= base_url('lecturer_exams/invigilate/' . $e['id']) ?>" class="btn btn-gold"><?= icon('eye', 16) ?> Invigilate live</a>
        <?php endif; ?>
        <a href="<?= base_url('lecturer_exams/edit/' . $e['id']) ?>" class="btn btn-outline-primary"><?= icon('edit', 16) ?> Details</a>
    </div>
</div>

<?php if ($e['status'] === 'draft'): ?>
    <div class="card publish-card mb-4">
        <div class="card-body d-flex flex-wrap align-items-center gap-3">
            <span class="stat-icon bg-soft-gold"><?= icon('lock', 22) ?></span>
            <div class="flex-grow-1 min-w-0">
                <h5 class="card-heading mb-1">Draft: students can't see this exam yet</h5>
                <?php if ($problems): ?>
                    <ul class="small text-muted mb-0 ps-3"><?php foreach ($problems as $p): ?><li><?= html_escape($p) ?></li><?php endforeach; ?></ul>
                <?php else: ?>
                    <small class="text-muted">Ready. Publishing notifies students and shows it on their Exams page. They can start it at the opening time.</small>
                <?php endif; ?>
            </div>
            <form method="post" action="<?= base_url('lecturer_exams/publish/' . $e['id']) ?>"
                  data-confirm="Publish this exam? Students will be notified and can start it from <?= html_escape(date('D j M, H:i', strtotime($e['opens_at']))) ?>." data-confirm-ok="Publish">
                <button type="submit" class="btn btn-primary" <?= $problems ? 'disabled' : '' ?>>Publish exam</button>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php if ($e['status'] === 'published'): ?>
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3"><div class="card stat-card"><div class="stat-icon bg-soft-navy"><?= icon('users', 22) ?></div>
        <div><div class="stat-value"><?= (int) $stats['started'] ?><small class="stat-of">/<?= (int) $stats['students'] ?></small></div><div class="stat-label">Started</div></div></div></div>
    <div class="col-6 col-xl-3"><div class="card stat-card"><div class="stat-icon bg-soft-green"><?= icon('check', 22) ?></div>
        <div><div class="stat-value"><?= (int) $stats['submitted'] ?></div><div class="stat-label">Handed in</div></div></div></div>
    <div class="col-6 col-xl-3"><div class="card stat-card"><div class="stat-icon bg-soft-gold"><?= icon('edit', 22) ?></div>
        <div><div class="stat-value"><?= (int) $stats['to_mark'] ?></div><div class="stat-label">Short answers to mark</div></div></div></div>
    <div class="col-6 col-xl-3"><div class="card stat-card"><div class="stat-icon bg-soft-blue"><?= icon('award', 22) ?></div>
        <div><div class="stat-value"><?= $stats['average'] === null ? '—' : (int) round($stats['average']) . '%' ?></div><div class="stat-label">Average (marked)</div></div></div></div>
</div>

<div class="card mb-4">
    <div class="card-body pb-0">
        <div class="card-head">
            <h5 class="card-heading">Students</h5>
            <?php if (! $e['results_released']): ?>
                <form method="post" action="<?= base_url('lecturer_exams/release/' . $e['id']) ?>"
                      data-confirm="Release results? Every student who sat the exam will see their mark and the correct answers." data-confirm-ok="Release results">
                    <button type="submit" class="btn btn-sm btn-primary" <?= ($stats['submitted'] === 0 || $stats['to_mark'] > 0 || $stats['submitted'] < $stats['started']) ? 'disabled' : '' ?>
                            title="Available once everyone who started has handed in and all scripts are marked"><?= icon('award', 14) ?> Release results</button>
                </form>
            <?php else: ?>
                <span class="card-sub">Released <?= html_escape(time_ago($e['released_at'])) ?></span>
            <?php endif; ?>
        </div>
    </div>
    <?php if (empty($roster)): ?>
        <div class="empty-state pt-2">No students have access to this module yet.</div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-clean align-middle mb-0">
            <thead><tr><th>Student</th><th>Status</th><th>Flags</th><th class="text-end">Mark</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($roster as $r): ?>
                <tr>
                    <td><div class="d-flex align-items-center gap-2"><?= avatar_html($r['name'], $r['student_id'], $r['photo_path'] ? $r['photo_updated_at'] : null, 'avatar-sm') ?>
                        <div class="min-w-0"><span class="fw-semibold d-block text-truncate"><?= html_escape($r['name']) ?></span><span class="id-chip"><?= html_escape($r['id_number']) ?></span></div></div></td>
                    <td>
                        <?php if (! $r['att_id']): ?>
                            <?= status_badge($phase === 'closed' ? 'exam_absent' : 'exam_not_started') ?>
                        <?php elseif (! $r['submitted_at']): ?>
                            <?= status_badge('exam_writing') ?>
                        <?php elseif (! $r['graded_at']): ?>
                            <?= status_badge('to_mark') ?>
                        <?php else: ?>
                            <?= status_badge('graded') ?>
                        <?php endif; ?>
                    </td>
                    <td><?= $r['att_id'] && $r['flag_count'] > 0 ? '<span class="flag-chip">' . icon('alert', 13) . ' ' . (int) $r['flag_count'] . '</span>' : '<span class="text-muted">—</span>' ?></td>
                    <td class="text-end text-nowrap"><?= $r['graded_at'] ? '<span class="score-chip">' . html_escape(score_fmt($r['total_score'])) . '/' . html_escape(score_fmt($r['max_score'])) . '</span>' : '<span class="text-muted">—</span>' ?></td>
                    <td class="text-end"><?php if ($r['att_id']): ?><a href="<?= base_url('lecturer_exams/attempt/' . $r['att_id']) ?>" class="btn btn-sm <?= $r['submitted_at'] && ! $r['graded_at'] ? 'btn-primary' : 'btn-light' ?>"><?= $r['submitted_at'] && ! $r['graded_at'] ? 'Mark' : 'Open' ?></a><?php endif; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($e['instructions']): ?>
<div class="card mb-4"><div class="card-body">
    <div class="card-head"><h5 class="card-heading">Instructions for students</h5></div>
    <div class="prose"><?= nl2br(html_escape($e['instructions'])) ?></div>
</div></div>
<?php endif; ?>

<div class="card" id="questions">
    <div class="card-body pb-0">
        <div class="card-head">
            <h5 class="card-heading">Questions</h5>
            <span class="card-sub">
                <?= count($questions) ?> in the pool &middot; <?= html_escape(score_fmt($poolMarks)) ?> marks
                <?php if ($perStudent < count($questions)): ?>&middot; each student gets <?= $perStudent ?> at random<?php endif; ?>
                <?php if ($e['shuffle']): ?>&middot; shuffled<?php endif; ?>
            </span>
        </div>
        <?php if ($locked): ?>
            <div class="notice notice-info"><?= icon('lock', 16) ?> <span>Students have started, so questions are locked to keep every paper fair.</span></div>
        <?php else: ?>
            <div class="d-flex flex-wrap gap-2 mb-3">
                <a href="<?= base_url('lecturer_exams/question/' . $e['id'] . '?type=mcq') ?>" class="btn btn-primary btn-sm"><?= icon('plus', 14) ?> Multiple choice</a>
                <a href="<?= base_url('lecturer_exams/question/' . $e['id'] . '?type=short') ?>" class="btn btn-outline-primary btn-sm"><?= icon('plus', 14) ?> Short answer</a>
            </div>
            <p class="small text-muted">Tip: short answers that ask students to <em>apply</em> or <em>explain</em> ("How would you explain Romans 8:1 to a new believer?") are much harder to copy than questions a search engine can answer.</p>
        <?php endif; ?>
    </div>

    <?php if (empty($questions)): ?>
        <div class="empty-state pt-2">No questions yet. Add your first one above.</div>
    <?php else: ?>
    <ol class="question-list">
        <?php foreach ($questions as $i => $q): ?>
        <li id="q-<?= (int) $q['id'] ?>">
            <div class="d-flex gap-3">
                <span class="q-number"><?= $i + 1 ?></span>
                <div class="flex-grow-1 min-w-0">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                        <span class="pill pill-muted"><?= $q['type'] === 'mcq' ? 'Multiple choice' : 'Short answer' ?></span>
                        <small class="text-muted"><?= html_escape(score_fmt($q['marks'])) ?> mark<?= (float) $q['marks'] == 1 ? '' : 's' ?></small>
                    </div>
                    <div class="q-prompt"><?= nl2br(html_escape($q['prompt'])) ?></div>
                    <?php if ($q['type'] === 'mcq'): ?>
                        <ul class="q-options">
                        <?php foreach (Exam_model::options($q) as $oi => $opt): ?>
                            <li class="<?= (int) $q['correct_option'] === $oi ? 'is-correct' : '' ?>">
                                <span class="q-letter"><?= $letters[$oi] ?></span> <?= html_escape($opt) ?>
                                <?php if ((int) $q['correct_option'] === $oi): ?><span class="ms-auto"><?= icon('check', 15) ?></span><?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
                <?php if (! $locked): ?>
                <div class="q-actions">
                    <a href="<?= base_url('lecturer_exams/question/' . $e['id'] . '/' . $q['id']) ?>" class="icon-btn" title="Edit"><?= icon('edit', 16) ?></a>
                    <?php if ($i > 0): ?>
                        <form method="post" action="<?= base_url('lecturer_exams/move_question/' . $q['id'] . '/up') ?>"><button type="submit" class="icon-btn" title="Move up">↑</button></form>
                    <?php endif; ?>
                    <?php if ($i < count($questions) - 1): ?>
                        <form method="post" action="<?= base_url('lecturer_exams/move_question/' . $q['id'] . '/down') ?>"><button type="submit" class="icon-btn" title="Move down">↓</button></form>
                    <?php endif; ?>
                    <form method="post" action="<?= base_url('lecturer_exams/delete_question/' . $q['id']) ?>" data-confirm="Delete question <?= $i + 1 ?>?" data-confirm-ok="Delete" data-confirm-danger>
                        <button type="submit" class="icon-btn" title="Delete"><?= icon('x', 16) ?></button></form>
                </div>
                <?php endif; ?>
            </div>
        </li>
        <?php endforeach; ?>
    </ol>
    <?php endif; ?>
</div>

<?php if (! $locked): ?>
<div class="d-flex flex-wrap gap-2 mt-4">
    <?php if ($e['status'] === 'published'): ?>
        <form method="post" action="<?= base_url('lecturer_exams/unpublish/' . $e['id']) ?>" data-confirm="Hide this exam from students and make it a draft again?" data-confirm-ok="Back to draft">
            <button type="submit" class="btn btn-light">Back to draft</button></form>
    <?php endif; ?>
    <form method="post" action="<?= base_url('lecturer_exams/delete/' . $e['id']) ?>" data-confirm="Delete this exam and all its questions?" data-confirm-ok="Delete exam" data-confirm-danger>
        <button type="submit" class="btn btn-light text-danger-soft">Delete exam</button></form>
</div>
<?php endif; ?>
