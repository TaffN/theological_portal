<div class="row g-3 mb-4">
    <?php foreach ([
        ['Active students', number_format($h['students']), 'users', 'bg-soft-navy', number_format((int) $h['enrolled']) . ' with a paid-up course'],
        ['Published results', number_format($h['results']), 'award', 'bg-soft-green', 'across all courses'],
        ['Pass rate', $h['passrate'] === null ? '&ndash;' : score_fmt($h['passrate']) . '%', 'check', 'bg-soft-gold', 'of published results'],
        ['Fees this year', money($h['fees']), 'dollar', 'bg-soft-blue', 'approved payments in ' . date('Y')],
    ] as $k): ?>
        <div class="col-6 col-xl-3">
            <div class="card stat-card">
                <div class="stat-icon <?= $k[3] ?>"><?= icon($k[2], 22) ?></div>
                <div><div class="stat-value"><?= $k[1] ?></div><div class="stat-label"><?= $k[0] ?><br><small class="text-muted"><?= html_escape($k[4]) ?></small></div></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<div class="row g-3">
    <?php foreach ([
        ['pass_rates', 'award', 'Pass rates by region', 'Which provinces our students pass in, as a pie chart and bars. Also by gender, denomination or education.'],
        ['grades', 'layers', 'Grades', 'How many Distinctions, Merits, Passes and Fails, and each course\'s pass rate and average.'],
        ['students', 'users', 'Students', 'Where our students come from and who they are: province, gender, city, denomination, education.'],
        ['attendance', 'check-square', 'Attendance', 'Attendance rate per course from the lecturers\' registers.'],
        ['fees', 'dollar', 'Fees', 'Fees collected per month and per course, and payments still to check.'],
        ['documents', 'file', 'Documents', 'Identity documents and qualifications: verified, waiting and missing.'],
    ] as $r): ?>
        <div class="col-md-6 col-xl-4">
            <a href="<?= base_url('admin_reports/' . $r[0]) ?>" class="card h-100 stat-link text-reset text-decoration-none">
                <div class="card-body d-flex gap-3">
                    <span class="lib-icon"><?= icon($r[1], 22) ?></span>
                    <div><div class="fw-bold mb-1"><?= $r[2] ?></div><small class="text-muted"><?= $r[3] ?></small></div>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
</div>
