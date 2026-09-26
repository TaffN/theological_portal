<?php $letters = 'ABCDEF'; ?>
<div class="exam-take" data-exam-take
     data-attempt="<?= (int) $a['id'] ?>"
     data-left="<?= (int) $left ?>"
     data-save-url="<?= base_url('student_exams/save/' . $a['id']) ?>"
     data-ping-url="<?= base_url('student_exams/ping/' . $a['id']) ?>"
     data-event-url="<?= base_url('student_exams/event/' . $a['id']) ?>"
     data-exam-url="<?= base_url('student_exams/view/' . $e['id']) ?>">

    <div class="exam-bar">
        <div class="min-w-0">
            <div class="exam-bar-title text-truncate"><?= html_escape($e['title']) ?></div>
            <div class="exam-bar-meta"><span data-answered>0</span>/<?= count($questions) ?> answered &middot; <span data-save-status>All answers saved</span></div>
        </div>
        <div class="exam-timer" data-timer aria-live="off" title="Time left">--:--</div>
    </div>

    <?php if ($e['instructions']): ?>
        <details class="card exam-instructions mb-3">
            <summary class="card-body py-2"><?= icon('file', 16) ?> Instructions</summary>
            <div class="card-body pt-0 prose"><?= nl2br(html_escape($e['instructions'])) ?></div>
        </details>
    <?php endif; ?>

    <form method="post" action="<?= base_url('student_exams/submit/' . $a['id']) ?>" id="exam-form"
          data-confirm="Hand in now? You won't be able to change your answers afterwards." data-confirm-ok="Hand in">
        <input type="hidden" name="auto" value="0">

        <?php foreach ($questions as $i => $q): ?>
            <?php $saved = isset($answers[$q['id']]) ? $answers[$q['id']]['answer'] : null; ?>
            <fieldset class="card exam-q mb-3" data-question="<?= (int) $q['id'] ?>">
                <div class="card-body">
                    <legend class="exam-q-head">
                        <span class="q-number"><?= $i + 1 ?></span>
                        <span class="small text-muted"><?= html_escape(score_fmt($q['marks'])) ?> mark<?= (float) $q['marks'] == 1 ? '' : 's' ?></span>
                    </legend>
                    <div class="q-prompt mb-3"><?= nl2br(html_escape($q['prompt'])) ?></div>

                    <?php if ($q['type'] === 'mcq'): ?>
                        <div class="exam-options">
                        <?php $n = 0; foreach ($q['display_options'] as $origIdx => $text): ?>
                            <label class="exam-option">
                                <input type="radio" name="answers[<?= (int) $q['id'] ?>]" value="<?= (int) $origIdx ?>" <?= $saved !== null && (string) $saved === (string) $origIdx ? 'checked' : '' ?>>
                                <span class="q-letter"><?= $letters[$n++] ?></span>
                                <span class="exam-option-text"><?= html_escape($text) ?></span>
                            </label>
                        <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <textarea name="answers[<?= (int) $q['id'] ?>]" class="form-control exam-answer" rows="5" spellcheck="true"
                                  placeholder="Type your answer…"><?= html_escape((string) $saved) ?></textarea>
                    <?php endif; ?>
                </div>
            </fieldset>
        <?php endforeach; ?>

        <div class="card mb-4">
            <div class="card-body">
                <p class="small text-muted mb-3">Check your answers, then hand in. If you don't, everything is handed in automatically when the timer reaches zero.</p>
                <button type="submit" class="btn btn-primary btn-lg w-100"><?= icon('check', 18) ?> Hand in</button>
            </div>
        </div>
    </form>
</div>
<script src="<?= base_url('assets/js/exam.js') ?>?v=2"></script>
