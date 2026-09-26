<?php
    $CI =& get_instance();
    $isPost  = $CI->input->method() === 'post';
    $editing = ! empty($e);
    $val = function ($field, $default) use ($isPost, $CI) {
        return $isPost ? (string) $CI->input->post($field) : $default;
    };
    $dt = function ($v) { return date('Y-m-d\TH:i', strtotime($v)); };

    $opensDefault  = $editing ? $dt($e['opens_at']) : date('Y-m-d', strtotime('+7 days')) . 'T09:00';
    $closesDefault = $editing ? $dt($e['closes_at']) : date('Y-m-d', strtotime('+7 days')) . 'T17:00';
    $shuffle = $isPost ? (bool) $CI->input->post('shuffle') : ($editing ? (bool) $e['shuffle'] : true);
    $action  = $editing ? base_url('lecturer_exams/edit/' . $e['id']) : base_url('lecturer_exams/create/' . $course['id']);
    $back    = $editing ? base_url('lecturer_exams/view/' . $e['id']) : base_url('lecturer_exams');
?>
<p class="mb-3"><a href="<?= $back ?>" class="back-link">&larr; <?= $editing ? html_escape($e['title']) : 'Exams' ?></a></p>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <h1 class="page-title mb-1"><?= $editing ? 'Exam details' : 'New exam' ?></h1>
                <p class="text-muted mb-4"><?= html_escape($course['name']) ?><?= $editing ? '' : ' &middot; you\'ll add the questions next; students only see it once you publish' ?></p>

                <form method="post" action="<?= $action ?>">
                    <div class="mb-3">
                        <label class="form-label" for="e-title">Title</label>
                        <input type="text" id="e-title" name="title" class="form-control" maxlength="200" required
                               placeholder="e.g. Mid-term exam: The Gospels" value="<?= html_escape($val('title', $editing ? $e['title'] : '')) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="e-instructions">Instructions for students</label>
                        <textarea id="e-instructions" name="instructions" class="form-control" rows="4"
                                  placeholder="e.g. Answer all questions. Short answers: 3-5 sentences each."><?= html_escape($val('instructions', $editing ? (string) $e['instructions'] : '')) ?></textarea>
                    </div>

                    <h6 class="form-section">When</h6>
                    <div class="row g-3 mb-2">
                        <div class="col-sm-6">
                            <label class="form-label" for="e-opens">Opens</label>
                            <input type="datetime-local" id="e-opens" name="opens_at" class="form-control" required value="<?= html_escape($val('opens_at', $opensDefault)) ?>">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" for="e-closes">Closes</label>
                            <input type="datetime-local" id="e-closes" name="closes_at" class="form-control" required value="<?= html_escape($val('closes_at', $closesDefault)) ?>">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" for="e-duration">Time allowed (minutes)</label>
                            <input type="number" id="e-duration" name="duration_minutes" class="form-control" min="1" max="600" required value="<?= html_escape($val('duration_minutes', $editing ? $e['duration_minutes'] : '60')) ?>">
                        </div>
                    </div>
                    <p class="form-text mb-4">Students can start any time between opening and closing. Each student then has the time allowed, but everything is handed in automatically at the closing time. For a fixed sitting (everyone at 10:00), make the window as long as the time allowed plus 10 minutes.</p>

                    <h6 class="form-section">Fairness</h6>
                    <?php if (! empty($locked)): ?>
                        <div class="notice notice-info"><?= icon('lock', 16) ?> <span>Students have already started, so the question pool and shuffling can no longer change.</span></div>
                    <?php endif; ?>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="e-shuffle" name="shuffle" value="1" <?= $shuffle ? 'checked' : '' ?> <?= ! empty($locked) ? 'disabled' : '' ?>>
                        <label class="form-check-label" for="e-shuffle">
                            Shuffle questions and choices for each student
                            <small class="d-block text-muted">Everyone gets a different order, so "number 3 is B" is useless to a neighbour.</small>
                        </label>
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-sm-6">
                            <label class="form-label" for="e-count">Questions per student <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="number" id="e-count" name="question_count" class="form-control" min="1" placeholder="All questions"
                                   value="<?= html_escape($val('question_count', $editing && $e['question_count'] ? $e['question_count'] : '')) ?>" <?= ! empty($locked) ? 'disabled' : '' ?>>
                        </div>
                        <div class="col-sm-6 d-flex align-items-end">
                            <small class="text-muted">Write more questions than this and each student gets a random selection: a question pool.</small>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary btn-lg"><?= $editing ? 'Save details' : 'Create &amp; add questions' ?></button>
                        <a href="<?= $back ?>" class="btn btn-light btn-lg">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
