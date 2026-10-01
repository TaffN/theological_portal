<?php
    $letters  = 'ABCDEF';
    $writing  = $a && ! $a['submitted_at'];
    $deadline = min(strtotime($e['closes_at']), time() + (int) $e['duration_minutes'] * 60);
    $shortOfTime = ! $a && $phase === 'open' && $deadline < time() + (int) $e['duration_minutes'] * 60;
?>
<p class="mb-3"><a href="<?= base_url('student_exams') ?>" class="back-link">&larr; Exams</a></p>

<div class="page-head">
    <div class="min-w-0">
        <h1 class="page-title"><?= html_escape($e['title']) ?></h1>
        <p class="page-sub mb-0"><?= html_escape($e['module_name']) ?> &middot; <?= (int) $e['duration_minutes'] ?> minutes &middot; <?= (int) $count ?> question<?= $count == 1 ? '' : 's' ?></p>
    </div>
</div>

<div class="row g-3 justify-content-center">
    <div class="col-lg-8">

    <?php if ($result): ?>
        <?php $pct = $a['max_score'] > 0 ? round($a['total_score'] / $a['max_score'] * 100) : 0; ?>
        <div class="card result-card mb-3">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="result-score"><span><?= html_escape(score_fmt($a['total_score'])) ?></span><small>/<?= html_escape(score_fmt($a['max_score'])) ?></small></div>
                    <div>
                        <h5 class="card-heading mb-1">Your result: <?= (int) $pct ?>%</h5>
                        <small class="text-muted">Handed in <?= html_escape(date('D j M, H:i', strtotime($a['submitted_at']))) ?></small>
                    </div>
                </div>
                <?php if ($a['feedback']): ?>
                    <div class="feedback-box mt-3"><div class="pay-label mb-1">Feedback from your lecturer</div><?= nl2br(html_escape($a['feedback'])) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <h5 class="mb-3 mt-4">Your answers</h5>
        <?php foreach ($result['questions'] as $i => $q): ?>
            <?php $ans = isset($result['answers'][$q['id']]) ? $result['answers'][$q['id']] : null; $given = $ans ? $ans['answer'] : null; ?>
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                        <span class="small text-muted fw-semibold">Question <?= $i + 1 ?></span>
                        <span class="score-chip <?= $ans && (float) $ans['marks_awarded'] < (float) $q['marks'] ? 'score-chip-muted' : '' ?>"><?= html_escape(score_fmt($ans ? $ans['marks_awarded'] : 0)) ?>/<?= html_escape(score_fmt($q['marks'])) ?></span>
                    </div>
                    <div class="q-prompt mb-2"><?= nl2br(html_escape($q['prompt'])) ?></div>
                    <?php if ($q['type'] === 'mcq'): ?>
                        <ul class="q-options">
                        <?php foreach (Exam_model::options($q) as $oi => $opt): ?>
                            <?php $isGiven = $given !== null && $given !== '' && (int) $given === $oi; $isRight = (int) $q['correct_option'] === $oi; ?>
                            <li class="<?= $isRight ? 'is-correct' : ($isGiven ? 'is-wrong' : '') ?>">
                                <span class="q-letter"><?= $letters[$oi] ?></span> <?= html_escape($opt) ?>
                                <?php if ($isGiven): ?><span class="ms-auto small fw-semibold">Your answer</span><?php elseif ($isRight): ?><span class="ms-auto small">Correct answer</span><?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="answer-box"><?= $given !== null && $given !== '' ? nl2br(html_escape($given)) : '<span class="text-muted">No answer</span>' ?></div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

    <?php elseif ($a && ! $writing): ?>
        <div class="card"><div class="card-body text-center py-5">
            <span class="empty-icon bg-soft-green mb-3"><?= icon('check', 28) ?></span>
            <h5 class="card-heading justify-content-center mb-2">Handed in</h5>
            <p class="text-muted mb-0">
                <?= html_escape(date('D j M Y, H:i', strtotime($a['submitted_at']))) ?><?= $a['submit_reason'] === 'time_up' ? ' (handed in automatically when time ran out)' : '' ?>.<br>
                Your result will appear here, and you'll get an alert, once your lecturer releases the results.
            </p>
        </div></div>

    <?php elseif ($writing): ?>
        <div class="card exam-now-card"><div class="card-body text-center py-5">
            <span class="empty-icon bg-soft-gold mb-3"><?= icon('clock', 28) ?></span>
            <h5 class="card-heading justify-content-center mb-2">You're in the middle of this exam</h5>
            <p class="text-muted mb-4"><?= (int) floor(Exam_attempt_model::seconds_left($a) / 60) ?> minutes left. Your answers so far are saved.</p>
            <a href="<?= base_url('student_exams/take/' . $e['id']) ?>" class="btn btn-primary btn-lg">Continue the exam</a>
        </div></div>

    <?php elseif ($phase === 'closed'): ?>
        <div class="card"><div class="card-body">
            <div class="notice notice-danger mb-0"><?= icon('alert', 16) ?> <span>This exam closed <?= html_escape(date('D j M, H:i', strtotime($e['closes_at']))) ?> and you didn't sit it. If you had a good reason, speak to your lecturer.</span></div>
        </div></div>

    <?php else: ?>
        <div class="card">
            <div class="card-body">
                <?php if ($e['instructions']): ?>
                    <div class="card-head"><h5 class="card-heading">Instructions</h5></div>
                    <div class="prose mb-4"><?= nl2br(html_escape($e['instructions'])) ?></div>
                <?php endif; ?>

                <div class="card-head"><h5 class="card-heading"><?= icon('shield', 18) ?> Before you start</h5></div>
                <ul class="exam-rules">
                    <li><?= icon('clock', 16) ?> <span>You have <strong><?= (int) $e['duration_minutes'] ?> minutes</strong> once you press Start. The timer keeps running if you close the page or lose signal, and <strong>everything is handed in automatically</strong> when time is up<?= $phase === 'open' ? ' or at ' . html_escape(date('H:i', strtotime($e['closes_at']))) . ' when the exam closes' : '' ?>.</span></li>
                    <li><?= icon('check', 16) ?> <span>Your answers save as you go. If the connection drops, keep writing: they're sent when the signal comes back.</span></li>
                    <li><?= icon('lock', 16) ?> <span>You get <strong>one attempt</strong>, on <strong>this device only</strong>. Opening it on another phone is blocked.</span></li>
                    <li><?= icon('eye', 16) ?> <span>This exam is <strong>monitored</strong>: leaving the exam page (for WhatsApp, another app or a website) and pasting text are recorded and shown to your lecturer.</span></li>
                </ul>

                <?php if ($phase === 'scheduled'): ?>
                    <div class="notice notice-info mt-3 mb-0"><?= icon('clock', 16) ?> <span>Opens <strong><?= html_escape(date('l j M, H:i', strtotime($e['opens_at']))) ?></strong> (<?= html_escape(due_in($e['opens_at'])) ?>) and closes <?= html_escape(date('D j M, H:i', strtotime($e['closes_at']))) ?>. Come back then to start.</span></div>
                <?php else: ?>
                    <?php if ($shortOfTime): ?>
                        <div class="notice notice-danger mt-3"><?= icon('alert', 16) ?> <span>The exam closes at <?= html_escape(date('H:i', strtotime($e['closes_at']))) ?>, so if you start now you'll only have <strong><?= (int) floor(($deadline - time()) / 60) ?> minutes</strong>.</span></div>
                    <?php endif; ?>
                    <form method="post" action="<?= base_url('student_exams/start/' . $e['id']) ?>" class="mt-4"
                          data-confirm="Start the exam now? The <?= (int) $e['duration_minutes'] ?>-minute timer begins straight away and can't be paused." data-confirm-ok="Start now">
                        <label class="pledge">
                            <input type="checkbox" class="form-check-input" name="pledge" value="1" required>
                            <span>I promise to answer on my own, without notes, books, websites or help from anyone else. I understand that my activity during the exam is recorded.</span>
                        </label>
                        <button type="submit" class="btn btn-primary btn-lg w-100 mt-3"><?= icon('clock', 18) ?> Start the exam</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    </div>
</div>
