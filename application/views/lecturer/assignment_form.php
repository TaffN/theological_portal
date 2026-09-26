<?php
    $CI =& get_instance();
    $isPost  = $CI->input->method() === 'post';
    $editing = ! empty($a);
    $val = function ($field, $default) use ($isPost, $CI) {
        return $isPost ? (string) $CI->input->post($field) : $default;
    };

    $dueDefault = $editing ? date('Y-m-d\TH:i', strtotime($a['due_at'])) : date('Y-m-d', strtotime('+7 days')) . 'T17:00';
    $allowLate  = $isPost ? (bool) $CI->input->post('allow_late') : ($editing ? (bool) $a['allow_late'] : true);
    $action     = $editing ? base_url('lecturer_assignments/edit/' . $a['id']) : base_url('lecturer_assignments/create/' . $course['id']);
?>
<p class="mb-3">
    <a href="<?= $editing ? base_url('lecturer_assignments/view/' . $a['id']) : base_url('lecturer_assignments') ?>" class="back-link">&larr; <?= $editing ? html_escape($a['title']) : 'Assignments' ?></a>
</p>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <h1 class="page-title mb-1"><?= $editing ? 'Edit assignment' : 'New assignment' ?></h1>
                <p class="text-muted mb-4"><?= html_escape($course['name']) ?><?= $editing ? '' : ' &middot; students with access to this course are notified when you save' ?></p>

                <?php if (! empty($uploadError)): ?>
                    <div class="notice notice-danger"><?= icon('alert', 16) ?> <span><?= html_escape($uploadError) ?> Please choose the file again.</span></div>
                <?php endif; ?>
                <form method="post" action="<?= $action ?>" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label" for="a-title">Title</label>
                        <input type="text" id="a-title" name="title" class="form-control" maxlength="200" required
                               placeholder="e.g. Essay 1: The Sermon on the Mount" value="<?= html_escape($val('title', $editing ? $a['title'] : '')) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="a-instructions">Instructions</label>
                        <textarea id="a-instructions" name="instructions" class="form-control" rows="6"
                                  placeholder="What should students do? Length, format, which passages or readings to use…"><?= html_escape($val('instructions', $editing ? (string) $a['instructions'] : '')) ?></textarea>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-sm-7">
                            <label class="form-label" for="a-due">Due date and time</label>
                            <input type="datetime-local" id="a-due" name="due_at" class="form-control" required value="<?= html_escape($val('due_at', $dueDefault)) ?>">
                        </div>
                        <div class="col-sm-5">
                            <label class="form-label" for="a-max">Marked out of</label>
                            <input type="number" id="a-max" name="max_score" class="form-control" min="1" max="1000" step="1" required value="<?= html_escape($val('max_score', $editing ? $a['max_score'] : '100')) ?>">
                        </div>
                    </div>

                    <div class="form-check form-switch mb-4">
                        <input class="form-check-input" type="checkbox" role="switch" id="a-late" name="allow_late" value="1" <?= $allowLate ? 'checked' : '' ?>>
                        <label class="form-check-label" for="a-late">
                            Accept late work
                            <small class="d-block text-muted">Students who haven't handed in can still do so after the due date. It is marked "Late".</small>
                        </label>
                    </div>

                    <label class="form-label">Question paper or brief <span class="text-muted fw-normal">(optional)</span></label>
                    <?php if ($editing && $a['attachment_path']): ?>
                        <div class="attach-row mb-2">
                            <?= icon('file', 18) ?>
                            <a href="<?= base_url('lecturer_assignments/attachment/' . $a['id']) ?>" target="_blank" class="text-truncate"><?= html_escape($a['attachment_name']) ?></a>
                            <label class="form-check ms-auto mb-0 small text-nowrap"><input type="checkbox" class="form-check-input" name="remove_attachment" value="1"> Remove</label>
                        </div>
                    <?php endif; ?>
                    <label class="dropzone" data-dropzone data-max-mb="20">
                        <input type="file" name="attachment" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.jpg,.jpeg,.png,.zip,.txt">
                        <div class="dz-empty">
                            <span class="dz-icon"><?= icon('upload', 26) ?></span>
                            <strong><?= $editing && $a['attachment_path'] ? 'Replace the file' : 'Tap to attach a file' ?></strong>
                            <small>PDF, Word, PowerPoint, Excel, image or ZIP, up to 20MB</small>
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

                    <div class="d-flex flex-wrap gap-2 mt-4">
                        <button type="submit" class="btn btn-primary btn-lg"><?= $editing ? 'Save changes' : 'Set assignment &amp; notify students' ?></button>
                        <a href="<?= $editing ? base_url('lecturer_assignments/view/' . $a['id']) : base_url('lecturer_assignments') ?>" class="btn btn-light btn-lg">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
