<?php
    $CI =& get_instance();
    $draft = $CI->session->flashdata('answer_draft');
    $answerValue = $draft !== null ? $draft : ($sub ? (string) $sub['answer_text'] : '');
    $past = strtotime($a['due_at']) < time();
?>
<p class="mb-3"><a href="<?= base_url('student_assignments') ?>" class="back-link">&larr; Assignments</a></p>

<div class="page-head">
    <div class="min-w-0">
        <h1 class="page-title"><?= html_escape($a['title']) ?></h1>
        <p class="page-sub mb-0"><?= html_escape($a['course_name']) ?> &middot;
            <?= $past ? 'Was due' : 'Due' ?> <strong class="text-body"><?= html_escape(date('l j M Y, H:i', strtotime($a['due_at']))) ?></strong>
            (<?= html_escape(due_in($a['due_at'])) ?>) &middot; out of <?= (int) $a['max_score'] ?></p>
    </div>
    <?= status_badge($state) ?>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <?php if ($state === 'graded'): ?>
            <div class="card result-card mb-3">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="result-score">
                            <span><?= html_escape(score_fmt($sub['score'])) ?></span><small>/<?= (int) $a['max_score'] ?></small>
                        </div>
                        <div>
                            <h5 class="card-heading mb-1">Your mark</h5>
                            <small class="text-muted"><?= (int) round($sub['score'] / max(1, $a['max_score']) * 100) ?>% &middot; marked <?= html_escape(time_ago($sub['graded_at'])) ?></small>
                        </div>
                    </div>
                    <?php if ($sub['feedback']): ?>
                        <div class="feedback-box mt-3">
                            <div class="pay-label mb-1">Feedback from your lecturer</div>
                            <?= nl2br(html_escape($sub['feedback'])) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="card mb-3">
            <div class="card-body">
                <div class="card-head"><h5 class="card-heading">Instructions</h5></div>
                <?php if ($a['instructions']): ?>
                    <div class="prose"><?= nl2br(html_escape($a['instructions'])) ?></div>
                <?php elseif (! $a['attachment_path']): ?>
                    <p class="text-muted mb-0">Your lecturer didn't add written instructions. Check the course materials or ask them.</p>
                <?php endif; ?>
                <?php if ($a['attachment_path']): ?>
                    <a href="<?= base_url('student_assignments/attachment/' . $a['id']) ?>" target="_blank" class="attach-row mt-3">
                        <?= icon('file', 18) ?> <span class="text-truncate"><?= html_escape($a['attachment_name']) ?></span> <span class="ms-auto small text-muted text-nowrap">Download</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <?php if ($sub): ?>
            <div class="card mb-3">
                <div class="card-body">
                    <div class="card-head">
                        <h5 class="card-heading"><?= icon('check', 18) ?> What you handed in</h5>
                        <?php if ($sub['is_late']): ?><?= status_badge('late') ?><?php endif; ?>
                    </div>
                    <p class="small text-muted"><?= html_escape(date('D j M Y, H:i', strtotime($sub['submitted_at']))) ?><?= $sub['attempts'] > 1 ? ' &middot; version ' . (int) $sub['attempts'] : '' ?></p>
                    <?php if ($sub['file_path']): ?>
                        <a href="<?= base_url('student_assignments/my_file/' . $a['id']) ?>" target="_blank" class="attach-row mb-2">
                            <?= icon('file', 18) ?> <span class="text-truncate"><?= html_escape($sub['original_name']) ?></span>
                        </a>
                    <?php endif; ?>
                    <?php if ($sub['answer_text']): ?>
                        <div class="answer-box"><?= nl2br(html_escape($sub['answer_text'])) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($canSubmit): ?>
            <div class="card" id="hand-in">
                <div class="card-body">
                    <div class="card-head"><h5 class="card-heading"><?= icon('upload', 18) ?> <?= $sub ? 'Hand in a new version' : 'Hand in your work' ?></h5></div>
                    <?php if ($state === 'overdue'): ?>
                        <div class="notice notice-danger"><?= icon('alert', 16) ?> <span>The due date has passed. You can still hand in, but it will be marked as late.</span></div>
                    <?php elseif ($sub): ?>
                        <p class="small text-muted">You can replace your work until the due date. Your lecturer only sees the latest version.</p>
                    <?php endif; ?>

                    <form method="post" action="<?= base_url('student_assignments/submit/' . $a['id']) ?>" enctype="multipart/form-data">
                        <label class="form-label">Your document</label>
                        <label class="dropzone" data-dropzone data-max-mb="10">
                            <input type="file" name="work" accept=".pdf,.doc,.docx,.odt,.rtf,.txt,.ppt,.pptx,.jpg,.jpeg,.png,.zip">
                            <div class="dz-empty">
                                <span class="dz-icon"><?= icon('upload', 26) ?></span>
                                <strong>Tap to choose a file</strong>
                                <small>Word, PDF or photos of your pages, up to 10MB</small>
                            </div>
                            <div class="dz-filled">
                                <img class="dz-preview" alt="">
                                <span class="dz-file-icon"><?= icon('file', 26) ?></span>
                                <div class="min-w-0">
                                    <strong class="dz-name text-truncate d-block"></strong>
                                    <small class="dz-size text-muted"></small>
                                </div>
                                <span class="dz-change">Change</span>
                            </div>
                            <div class="dz-error"></div>
                        </label>
                        <?php if ($sub && $sub['file_path']): ?>
                            <label class="form-check small mt-2">
                                <input type="checkbox" class="form-check-input" name="keep_file" value="1" checked>
                                Keep <strong><?= html_escape($sub['original_name']) ?></strong> if I don't choose a new file
                            </label>
                        <?php endif; ?>

                        <label class="form-label mt-3" for="answer_text">Or type your answer <span class="text-muted fw-normal">(optional)</span></label>
                        <textarea id="answer_text" name="answer_text" class="form-control" rows="6" placeholder="You can type your answer here instead of, or as well as, attaching a document."><?= html_escape($answerValue) ?></textarea>

                        <button type="submit" class="btn btn-primary btn-lg w-100 mt-3"><?= $sub ? 'Replace my work' : 'Hand in' ?></button>
                    </form>
                </div>
            </div>
        <?php elseif ($state === 'missed'): ?>
            <div class="card"><div class="card-body">
                <div class="notice notice-danger mb-0"><?= icon('alert', 16) ?> <span>The due date has passed and this assignment no longer accepts work. If you had a good reason, speak to your lecturer.</span></div>
            </div></div>
        <?php elseif ($state === 'submitted'): ?>
            <div class="notice notice-info"><?= icon('clock', 16) ?> <span>Your work is with your lecturer. You'll get an alert when it's marked.</span></div>
        <?php endif; ?>
    </div>
</div>
