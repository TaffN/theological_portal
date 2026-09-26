<?php
    $letters   = 'ABCDEF';
    $done      = (bool) $a['submitted_at'];
    $hasShort  = count(array_filter($questions, function ($q) { return $q['type'] === 'short'; })) > 0;
    $answered  = 0;
    foreach ($questions as $q) {
        if (isset($answers[$q['id']]) && $answers[$q['id']]['answer'] !== null && $answers[$q['id']]['answer'] !== '') { $answered++; }
    }
    $flagTypes = Exam_attempt_model::$flag_types;
?>
<p class="mb-3"><a href="<?= base_url('lecturer_exams/view/' . $e['id']) ?>" class="back-link">&larr; <?= html_escape($e['title']) ?></a></p>

<div class="page-head">
    <div class="d-flex align-items-center gap-3 min-w-0">
        <?= avatar_html($student['name'], $student['id'], $student['photo_path'] ? $student['photo_updated_at'] : null, 'avatar-lg') ?>
        <div class="min-w-0">
            <h1 class="page-title mb-0"><?= html_escape($student['name']) ?></h1>
            <p class="page-sub mb-0"><span class="id-chip"><?= html_escape($student['id_number']) ?></span> <?= html_escape($e['title']) ?></p>
        </div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <?php if ($a['graded_at']): ?>
            <span class="score-chip score-chip-lg"><?= html_escape(score_fmt($a['total_score'])) ?>/<?= html_escape(score_fmt($a['max_score'])) ?></span>
        <?php elseif ($done): ?>
            <?= status_badge('to_mark') ?>
        <?php else: ?>
            <?= status_badge('exam_writing') ?>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <?php if (! $done): ?>
            <div class="notice notice-info mb-3"><?= icon('clock', 16) ?> <span>Still writing: <?= (int) floor(Exam_attempt_model::seconds_left($a) / 60) ?> min left. You can mark once it's handed in.</span></div>
        <?php endif; ?>

        <form method="post" action="<?= base_url('lecturer_exams/attempt/' . $a['id']) ?>">
        <?php foreach ($questions as $i => $q): ?>
            <?php
                $ans   = isset($answers[$q['id']]) ? $answers[$q['id']] : null;
                $given = $ans ? $ans['answer'] : null;
            ?>
            <div class="card mb-3 script-q">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                        <span class="small text-muted fw-semibold">Question <?= $i + 1 ?> &middot; <?= $q['type'] === 'mcq' ? 'Multiple choice' : 'Short answer' ?> &middot; <?= html_escape(score_fmt($q['marks'])) ?> mark<?= (float) $q['marks'] == 1 ? '' : 's' ?></span>
                        <?php if ($q['type'] === 'mcq' && $done): ?>
                            <span class="pill <?= $ans && $ans['is_correct'] ? 'pill-success' : 'pill-danger' ?>"><?= $ans && $ans['is_correct'] ? 'Correct' : ($given === null || $given === '' ? 'No answer' : 'Wrong') ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="q-prompt mb-2"><?= nl2br(html_escape($q['prompt'])) ?></div>

                    <?php if ($q['type'] === 'mcq'): ?>
                        <ul class="q-options">
                        <?php foreach (Exam_model::options($q) as $oi => $opt): ?>
                            <?php $isGiven = $given !== null && $given !== '' && (int) $given === $oi; $isRight = (int) $q['correct_option'] === $oi; ?>
                            <li class="<?= $isRight ? 'is-correct' : ($isGiven ? 'is-wrong' : '') ?>">
                                <span class="q-letter"><?= $letters[$oi] ?></span> <?= html_escape($opt) ?>
                                <?php if ($isGiven): ?><span class="ms-auto small fw-semibold"><?= $isRight ? 'Their answer ✓' : 'Their answer' ?></span><?php elseif ($isRight): ?><span class="ms-auto small">Correct</span><?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="answer-box mb-3"><?= $given !== null && $given !== '' ? nl2br(html_escape($given)) : '<span class="text-muted">No answer</span>' ?></div>
                        <?php if ($done): ?>
                            <div class="d-flex align-items-center gap-2">
                                <label class="form-label mb-0" for="m-<?= (int) $q['id'] ?>">Mark</label>
                                <input type="number" id="m-<?= (int) $q['id'] ?>" name="marks[<?= (int) $q['id'] ?>]" class="form-control mark-input" min="0" max="<?= html_escape(score_fmt($q['marks'])) ?>" step="any" inputmode="decimal"
                                       value="<?= $ans && $ans['marks_awarded'] !== null ? html_escape(score_fmt($ans['marks_awarded'])) : ($given === null || $given === '' ? '0' : '') ?>">
                                <span class="text-muted">/ <?= html_escape(score_fmt($q['marks'])) ?></span>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if ($done): ?>
            <div class="card mb-3">
                <div class="card-body">
                    <label class="form-label" for="feedback">Feedback for the student <span class="text-muted fw-normal">(shown with their result)</span></label>
                    <textarea id="feedback" name="feedback" class="form-control mb-3" rows="3"><?= html_escape((string) $a['feedback']) ?></textarea>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary"><?= $hasShort ? 'Save marks' : 'Save feedback' ?></button>
                        <?php if ($nextId): ?>
                            <button type="submit" name="next" value="<?= (int) $nextId ?>" class="btn btn-outline-primary">Save &amp; next script</button>
                        <?php endif; ?>
                    </div>
                    <?php if (! $hasShort): ?><small class="text-muted d-block mt-2">This paper is all multiple choice, so it was marked automatically.</small><?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        </form>
    </div>

    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-body">
                <div class="card-head"><h5 class="card-heading">Attempt</h5></div>
                <dl class="facts">
                    <dt>Started</dt><dd><?= html_escape(date('D j M, H:i:s', strtotime($a['started_at']))) ?></dd>
                    <dt>Deadline</dt><dd><?= html_escape(date('H:i:s', strtotime($a['deadline_at']))) ?></dd>
                    <dt>Handed in</dt><dd><?= $done ? html_escape(date('H:i:s', strtotime($a['submitted_at']))) . ($a['submit_reason'] === 'time_up' ? ' (time ran out)' : '') : '—' ?></dd>
                    <dt>Answered</dt><dd><?= $answered ?>/<?= count($questions) ?></dd>
                    <dt>Flags</dt><dd><?= (int) $a['flag_count'] ?><?= $a['away_seconds'] ? ', away ' . ($a['away_seconds'] >= 60 ? floor($a['away_seconds'] / 60) . ' min ' : '') . ($a['away_seconds'] % 60) . ' s in total' : '' ?></dd>
                    <dt>Device</dt><dd class="small text-break"><?= html_escape((string) $a['user_agent']) ?></dd>
                </dl>
                <?php if (! $done): ?>
                    <form method="post" action="<?= base_url('lecturer_exams/reset_device/' . $a['id']) ?>" class="mt-3"
                          data-confirm="Let <?= html_escape($student['name']) ?> continue on another phone or computer?" data-confirm-ok="Allow new device">
                        <input type="hidden" name="back" value="attempt">
                        <button type="submit" class="btn btn-sm btn-light w-100">Allow a new device</button></form>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="card-head"><h5 class="card-heading"><?= icon('shield', 18) ?> Activity</h5></div>
                <?php if (empty($events)): ?>
                    <p class="text-muted small mb-0">Nothing recorded.</p>
                <?php else: ?>
                    <ul class="event-list">
                    <?php foreach ($events as $ev): ?>
                        <li class="<?= in_array($ev['type'], $flagTypes, true) ? 'is-flag' : '' ?>">
                            <span class="event-time"><?= html_escape(date('H:i:s', strtotime($ev['created_at']))) ?></span>
                            <span>
                                <?= html_escape(Exam_attempt_model::event_label($ev['type'])) ?><?= $ev['seconds'] ? ' after ' . ($ev['seconds'] >= 60 ? floor($ev['seconds'] / 60) . ' min ' : '') . ($ev['seconds'] % 60) . ' s' : '' ?>
                                <?php if ($ev['detail']): ?><small class="d-block text-muted text-break"><?= html_escape($ev['detail']) ?></small><?php endif; ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
