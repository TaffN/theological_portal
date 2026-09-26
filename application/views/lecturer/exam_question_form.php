<?php
    $CI =& get_instance();
    $isPost  = $CI->input->method() === 'post';
    $editing = ! empty($q);

    $type    = $isPost ? ($CI->input->post('type') === 'short' ? 'short' : 'mcq')
                       : ($editing ? $q['type'] : ($CI->input->get('type') === 'short' ? 'short' : 'mcq'));
    $prompt  = $isPost ? (string) $CI->input->post('prompt') : ($editing ? $q['prompt'] : '');
    $marks   = $isPost ? (string) $CI->input->post('marks') : ($editing ? score_fmt($q['marks']) : ($type === 'short' ? '5' : '1'));
    $options = $isPost ? (array) $CI->input->post('options') : ($editing ? Exam_model::options($q) : []);
    $correct = $isPost ? $CI->input->post('correct') : ($editing && $q['correct_option'] !== null ? (string) $q['correct_option'] : null);
    $letters = 'ABCDEF';
?>
<p class="mb-3"><a href="<?= base_url('lecturer_exams/view/' . $e['id']) ?>#questions" class="back-link">&larr; <?= html_escape($e['title']) ?></a></p>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <h1 class="page-title mb-1"><?= $editing ? 'Edit question' : 'Question ' . (int) $number ?></h1>
                <p class="text-muted mb-4"><?= html_escape($e['title']) ?></p>

                <?php if ($formError): ?>
                    <div class="notice notice-danger"><?= icon('alert', 16) ?> <span><?= html_escape($formError) ?></span></div>
                <?php endif; ?>

                <form method="post" action="<?= base_url('lecturer_exams/question/' . $e['id'] . ($editing ? '/' . $q['id'] : '')) ?>" data-question-form>
                    <label class="form-label">Type</label>
                    <div class="choice-group mb-4">
                        <label class="choice">
                            <input type="radio" name="type" value="mcq" <?= $type === 'mcq' ? 'checked' : '' ?>>
                            <span><strong>Multiple choice</strong><small>Marked automatically</small></span>
                        </label>
                        <label class="choice">
                            <input type="radio" name="type" value="short" <?= $type === 'short' ? 'checked' : '' ?>>
                            <span><strong>Short answer</strong><small>You mark it</small></span>
                        </label>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="q-prompt">Question</label>
                        <textarea id="q-prompt" name="prompt" class="form-control" rows="4" required><?= html_escape($prompt) ?></textarea>
                    </div>

                    <div class="mcq-only mb-3" <?= $type === 'short' ? 'hidden' : '' ?>>
                        <label class="form-label">Choices <span class="text-muted fw-normal">(tick the correct one; leave spare boxes empty)</span></label>
                        <?php for ($i = 0; $i < $maxOptions; $i++): ?>
                            <div class="option-row">
                                <input class="form-check-input" type="radio" name="correct" value="<?= $i ?>" id="c-<?= $i ?>" <?= $correct !== null && (string) $correct === (string) $i ? 'checked' : '' ?> aria-label="Correct answer <?= $letters[$i] ?>">
                                <label class="q-letter" for="c-<?= $i ?>"><?= $letters[$i] ?></label>
                                <input type="text" name="options[]" class="form-control" maxlength="500" value="<?= html_escape(isset($options[$i]) ? $options[$i] : '') ?>" placeholder="<?= $i < 2 ? 'Choice ' . $letters[$i] : 'Optional' ?>">
                            </div>
                        <?php endfor; ?>
                    </div>

                    <div class="short-only notice notice-info" <?= $type === 'mcq' ? 'hidden' : '' ?>>
                        <?= icon('edit', 16) ?> <span>Students type their answer. You'll mark it after they hand in, on each student's script.</span>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-sm-4">
                            <label class="form-label" for="q-marks">Marks</label>
                            <input type="number" id="q-marks" name="marks" class="form-control" min="0.5" max="100" step="0.5" required value="<?= html_escape($marks) ?>">
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <?php if (! $editing): ?>
                            <button type="submit" name="then" value="another" class="btn btn-primary">Save &amp; add another</button>
                            <button type="submit" name="then" value="done" class="btn btn-outline-primary">Save &amp; finish</button>
                        <?php else: ?>
                            <button type="submit" class="btn btn-primary">Save question</button>
                        <?php endif; ?>
                        <a href="<?= base_url('lecturer_exams/view/' . $e['id']) ?>#questions" class="btn btn-light">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script src="<?= base_url('assets/js/exam.js') ?>?v=1"></script>
