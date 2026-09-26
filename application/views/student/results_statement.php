<?php
    $orgAddr = array_filter([setting('org_address'), setting('org_city'), setting('org_country')]);
    $orgCont = array_filter([setting('org_phone'), setting('org_email'), setting('org_website')]);
    $name    = trim((! empty($profile['title']) ? $profile['title'] . ' ' : '') . $u['name']);
?>
<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <a href="<?= base_url('student_results') ?>" class="back-link">&larr; My results</a>
    <button type="button" class="btn btn-primary" onclick="window.print()"><?= icon('file', 16) ?> Print / Save as PDF</button>
</div>

<div class="receipt statement card mx-auto">
    <div class="receipt-head">
        <div class="d-flex align-items-start gap-2 min-w-0">
            <span class="brand-mark"><?= html_escape(setting('org_initials', 'TC')) ?></span>
            <div class="min-w-0">
                <div class="fw-bold"><?= html_escape(setting('org_name', 'Theological Center')) ?></div>
                <?php if ($orgAddr): ?><small class="text-muted d-block"><?= html_escape(implode(', ', $orgAddr)) ?></small><?php endif; ?>
                <?php if ($orgCont): ?><small class="text-muted d-block"><?= html_escape(implode(' · ', $orgCont)) ?></small><?php endif; ?>
                <?php if (setting('org_registration_no')): ?><small class="text-muted d-block">Reg. no. <?= html_escape(setting('org_registration_no')) ?></small><?php endif; ?>
            </div>
        </div>
        <div class="text-end">
            <div class="receipt-label">Statement of results</div>
            <div class="small text-muted">Issued <?= date('j F Y') ?></div>
        </div>
    </div>

    <dl class="facts receipt-facts">
        <dt>Student</dt><dd class="fw-semibold"><?= html_escape($name) ?></dd>
        <dt>Student number</dt><dd><span class="id-chip"><?= html_escape($u['id_number']) ?></span></dd>
        <?php if (! empty($profile['date_of_birth'])): ?><dt>Date of birth</dt><dd><?= html_escape(date('j F Y', strtotime($profile['date_of_birth']))) ?></dd><?php endif; ?>
    </dl>

    <div class="table-responsive">
        <table class="table statement-table align-middle mb-0">
            <thead><tr><th>Course</th><th class="text-end">Assignments</th><th class="text-end">Exams</th><th class="text-end">Final</th><th>Grade</th><th class="text-end">Published</th></tr></thead>
            <tbody>
            <?php foreach ($results as $r): ?>
                <tr>
                    <td><span class="fw-semibold"><?= html_escape($r['course_name']) ?></span><?= $r['duration_text'] ? '<small class="text-muted d-block">' . html_escape($r['duration_text']) . '</small>' : '' ?></td>
                    <td class="text-end"><?= pct($r['assignment_pct']) ?></td>
                    <td class="text-end"><?= pct($r['exam_pct']) ?></td>
                    <td class="text-end fw-bold"><?= pct($r['final_pct']) ?></td>
                    <td><?= grade_badge($r['grade']) ?></td>
                    <td class="text-end text-nowrap small"><?= html_escape(date('j M Y', strtotime($r['published_at']))) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="statement-foot">
        <div class="statement-qr" data-qr="<?= html_escape($verifyUrl) ?>" data-qr-margin="1" aria-label="QR code to verify this statement"></div>
        <div class="min-w-0">
            <p class="receipt-foot mb-1"><?= html_escape(setting('statement_note', 'This statement lists results published by the Center. Scan the QR code to confirm it is genuine.')) ?></p>
            <small class="text-muted text-break d-block">Or open: <?= html_escape($verifyUrl) ?></small>
        </div>
    </div>
</div>
