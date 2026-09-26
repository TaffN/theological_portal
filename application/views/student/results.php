<div class="page-head">
    <div>
        <h1 class="page-title">My results</h1>
        <p class="page-sub">Your overall result for each course, once your lecturer publishes it. Marks for single assignments and exams are on their own pages.</p>
    </div>
    <?php if (! empty($results)): ?>
        <a href="<?= base_url('student_results/statement') ?>" class="btn btn-primary"><?= icon('file', 16) ?> Statement of results</a>
    <?php endif; ?>
</div>

<?php if (empty($results)): ?>
    <div class="card"><div class="empty-state">
        <span class="empty-icon bg-soft-navy"><?= icon('award', 28) ?></span><br>
        No results yet. When your lecturer publishes your overall result for a course, it appears here and you'll get an alert.
    </div></div>
<?php else: ?>
    <div class="row g-3">
    <?php foreach ($results as $r): ?>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div class="result-score"><span><?= html_escape(score_fmt(round($r['final_pct'], 1))) ?></span><small>%</small></div>
                        <div class="min-w-0">
                            <h5 class="card-heading mb-1"><?= html_escape($r['course_name']) ?></h5>
                            <?= grade_badge($r['grade']) ?>
                            <small class="text-muted d-block mt-1">Published <?= html_escape(date('j F Y', strtotime($r['published_at']))) ?></small>
                        </div>
                    </div>
                    <dl class="facts mb-3">
                        <dt>Assignments</dt><dd><?= pct($r['assignment_pct']) ?> <small class="text-muted">(counts <?= $r['exam_pct'] === null ? 100 : (int) $r['assignment_weight'] ?>%)</small></dd>
                        <dt>Exams</dt><dd><?= pct($r['exam_pct']) ?> <small class="text-muted">(counts <?= $r['assignment_pct'] === null ? 100 : (int) $r['exam_weight'] ?>%)</small></dd>
                        <dt>Final</dt><dd class="fw-bold"><?= pct($r['final_pct']) ?></dd>
                    </dl>
                    <?php if ($r['remarks']): ?>
                        <div class="feedback-box mb-3"><div class="pay-label mb-1">Lecturer's remarks</div><?= nl2br(html_escape($r['remarks'])) ?></div>
                    <?php endif; ?>
                    <details class="result-breakdown">
                        <summary class="small">Marks this is based on</summary>
                        <?php $this->load->view('partials/result_breakdown', ['items' => json_decode((string) $r['breakdown'], true)]); ?>
                    </details>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
    <p class="small text-muted mt-3">Grades: <?php $t = []; foreach ($bands as $b) { $t[] = $b[0] === 'Fail' ? 'Fail below ' . score_fmt($bands[2][1]) . '%' : $b[0] . ' ' . score_fmt($b[1]) . '% and above'; } echo html_escape(implode(' · ', $t)); ?>.</p>
<?php endif; ?>
