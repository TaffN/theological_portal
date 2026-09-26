<?php
    list($aw, $ew) = $calc['weights'];
    $ready = 0; $blocked = 0; $publishedCount = 0; $changed = 0;
    foreach ($calc['rows'] as $r) {
        $isPub = $r['published'] && $r['published']['status'] === 'published';
        if ($isPub) { $publishedCount++; }
        if ($r['blockers']) { $blocked++; } else { $ready++; }
        if ($isPub && ! $r['blockers'] && ($r['grade'] !== $r['published']['grade'] || abs((float) $r['final_pct'] - (float) $r['published']['final_pct']) >= 0.01)) { $changed++; }
    }
    $bandText = [];
    foreach ($bands as $b) { if ($b[0] !== 'Fail') { $bandText[] = $b[0] . ' ' . score_fmt($b[1]) . '%+'; } }
    $bandText[] = 'Fail below ' . score_fmt($bands[2][1]) . '%';
?>
<p class="mb-3"><a href="<?= base_url('lecturer_results') ?>" class="back-link">&larr; Results</a></p>

<div class="page-head">
    <div class="min-w-0">
        <h1 class="page-title"><?= html_escape($course['name']) ?></h1>
        <p class="page-sub mb-0"><?= (int) $calc['assignments'] ?> assignment<?= $calc['assignments'] == 1 ? '' : 's' ?> and <?= (int) $calc['exams'] ?> exam<?= $calc['exams'] == 1 ? '' : 's' ?> have finished and count towards the result.</p>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-body">
                <div class="card-head"><h5 class="card-heading"><?= icon('layers', 18) ?> Weighting</h5></div>
                <form method="post" action="<?= base_url('lecturer_results/weights/' . $course['id']) ?>" data-weights>
                    <label class="form-label" for="aw">Assignments count for</label>
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <input type="range" class="form-range flex-grow-1" id="aw" name="assignment_weight" min="0" max="100" step="5" value="<?= (int) $aw ?>"
                               oninput="this.form.querySelector('[data-aw]').textContent=this.value;this.form.querySelector('[data-ew]').textContent=100-this.value">
                        <span class="weight-chip"><span data-aw><?= (int) $aw ?></span>%</span>
                    </div>
                    <p class="small text-muted mb-3">Exams count for <strong><span data-ew><?= (int) $ew ?></span>%</strong>. If a course has only assignments or only exams, that part counts 100%.</p>
                    <button type="submit" class="btn btn-outline-primary btn-sm">Save weighting</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-body">
                <div class="card-head"><h5 class="card-heading"><?= icon('award', 18) ?> How results are worked out</h5></div>
                <ul class="small text-muted mb-0 ps-3">
                    <li><strong>Assignments:</strong> the average of the student's percentage on each assignment whose due date has passed. Not handed in counts as 0%.</li>
                    <li><strong>Exams:</strong> the average of their percentage on each exam that has closed. Not sat counts as 0%.</li>
                    <li><strong>Final</strong> = assignments &times; <?= (int) $aw ?>% + exams &times; <?= (int) $ew ?>%.</li>
                    <li><strong>Grades:</strong> <?= html_escape(implode(' · ', $bandText)) ?> (set by the administrator in Settings).</li>
                    <li>Work that is handed in but not marked, or an exam whose results aren't released, holds that student's result back until it's done.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php if ($changed): ?>
    <div class="notice notice-info mb-3"><?= icon('alert', 16) ?> <span><?= $changed ?> published result<?= $changed == 1 ? ' is' : 's are' ?> different from the marks now (a mark was changed after publishing). They're ticked below: publish again to update them.</span></div>
<?php endif; ?>

<div class="card">
    <div class="card-body pb-0">
        <div class="card-head">
            <h5 class="card-heading">Students</h5>
            <span class="card-sub"><?= $ready ?> ready &middot; <?= $blocked ?> not ready &middot; <?= $publishedCount ?> published</span>
        </div>
    </div>
    <?php if (empty($calc['rows'])): ?>
        <div class="empty-state pt-2">No students have access to this course yet.</div>
    <?php else: ?>
    <form method="post" action="<?= base_url('lecturer_results/publish/' . $course['id']) ?>" id="publish-form"
          data-confirm="Publish the ticked results? Each student will be notified and will see their grade, marks and your remarks." data-confirm-ok="Publish results">
        <ul class="result-list">
        <?php foreach ($calc['rows'] as $r): ?>
            <?php
                $pub     = $r['published'] && $r['published']['status'] === 'published' ? $r['published'] : null;
                $differs = $pub && ! $r['blockers'] && ($r['grade'] !== $pub['grade'] || abs((float) $r['final_pct'] - (float) $pub['final_pct']) >= 0.01);
                // Pre-tick new results and ones whose marks changed; never a withdrawn one (the lecturer re-ticks it deliberately).
                $tick    = ! $r['blockers'] && (! $r['published'] || $differs);
                $remark  = $r['published'] ? (string) $r['published']['remarks'] : '';
            ?>
            <li class="<?= $r['blockers'] ? 'is-blocked' : '' ?>">
                <div class="result-row">
                    <input type="checkbox" class="form-check-input result-tick" name="students[]" value="<?= (int) $r['student_id'] ?>" <?= $tick ? 'checked' : '' ?> <?= $r['blockers'] ? 'disabled' : '' ?>
                           aria-label="Publish <?= html_escape($r['name']) ?>">
                    <?= avatar_html($r['name'], $r['student_id'], $r['photo_path'] ? $r['photo_updated_at'] : null, 'avatar-sm') ?>
                    <div class="flex-grow-1 min-w-0">
                        <div class="fw-semibold text-truncate"><?= html_escape($r['name']) ?></div>
                        <small class="text-muted"><span class="id-chip"><?= html_escape($r['id_number']) ?></span>
                            Assignments <?= pct($r['assignment_pct']) ?> &middot; Exams <?= pct($r['exam_pct']) ?></small>
                    </div>
                    <div class="result-final text-end">
                        <div class="fw-bold"><?= pct($r['final_pct']) ?></div>
                        <?= $r['grade'] ? grade_badge($r['grade']) : '' ?>
                    </div>
                </div>
                <div class="result-detail">
                    <?php if ($r['blockers']): ?>
                        <div class="small text-warning-soft fw-semibold mb-2"><?= icon('clock', 14) ?> Not ready: <?= html_escape(implode('; ', $r['blockers'])) ?></div>
                    <?php elseif ($pub): ?>
                        <div class="small mb-2 <?= $differs ? 'text-warning-soft fw-semibold' : 'text-muted' ?>">
                            <?= icon($differs ? 'alert' : 'check', 14) ?>
                            Published <?= html_escape(time_ago($pub['published_at'])) ?> as <?= html_escape($pub['grade']) ?> (<?= pct($pub['final_pct']) ?>)<?= $differs ? ': marks have changed since, publish again to update' : '' ?>
                        </div>
                    <?php elseif ($r['published'] && $r['published']['status'] === 'withdrawn'): ?>
                        <div class="small text-muted mb-2"><?= icon('x', 14) ?> Withdrawn: the student can't see it. Publish again when ready.</div>
                    <?php endif; ?>

                    <details class="result-breakdown">
                        <summary class="small">Show the <?= count($r['items']) ?> mark<?= count($r['items']) == 1 ? '' : 's' ?> this is based on</summary>
                        <?php $this->load->view('partials/result_breakdown', ['items' => $r['items']]); ?>
                    </details>

                    <?php if (! $r['blockers']): ?>
                        <input type="text" class="form-control form-control-sm mt-2" name="remarks[<?= (int) $r['student_id'] ?>]" maxlength="1000"
                               placeholder="Remarks for the student (optional), e.g. Excellent grasp of the Gospels" value="<?= html_escape($remark) ?>">
                    <?php endif; ?>
                    <?php if ($pub): ?>
                        <button type="submit" form="withdraw-<?= (int) $pub['id'] ?>" class="btn btn-link btn-sm text-danger-soft px-0 mt-1">Withdraw this result</button>
                    <?php endif; ?>
                </div>
            </li>
        <?php endforeach; ?>
        </ul>
        <div class="card-body border-top d-flex flex-wrap align-items-center gap-3">
            <button type="submit" class="btn btn-primary" <?= $ready ? '' : 'disabled' ?>><?= icon('award', 16) ?> Publish ticked results</button>
            <small class="text-muted">Students are notified. Publishing again later updates a result and tells the student it changed.</small>
        </div>
    </form>
    <?php foreach ($calc['rows'] as $r): ?>
        <?php if ($r['published'] && $r['published']['status'] === 'published'): ?>
            <form method="post" action="<?= base_url('lecturer_results/withdraw/' . $r['published']['id']) ?>" id="withdraw-<?= (int) $r['published']['id'] ?>" class="d-none"
                  data-confirm="Withdraw <?= html_escape($r['name']) ?>'s result? They will no longer see it and will be told it is under review." data-confirm-ok="Withdraw" data-confirm-danger></form>
        <?php endif; ?>
    <?php endforeach; ?>
    <?php endif; ?>
</div>
