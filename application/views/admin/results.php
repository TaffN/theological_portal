<div class="page-head">
    <div>
        <h1 class="page-title">Results</h1>
        <p class="page-sub">Overall course results published by lecturers. Lecturers set the weighting and publish; grade boundaries are under <a href="<?= base_url('admin_settings') ?>#set-results">Settings</a>.</p>
    </div>
    <a href="<?= base_url('admin_results/export') ?>" class="btn btn-outline-primary"><?= icon('upload', 16) ?> Export all (CSV)</a>
</div>

<div class="card">
    <?php if (empty($courses)): ?>
        <div class="empty-state">No courses yet.</div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-clean align-middle mb-0">
            <thead><tr><th>Course</th><th class="text-end">Students</th><th class="text-end">Published</th><th class="text-end">Average</th><th>Last published</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($courses as $c): ?>
                <tr>
                    <td class="fw-semibold"><?= html_escape($c['name']) ?><?= $c['status'] !== 'active' ? ' <span class="pill pill-muted">Inactive</span>' : '' ?></td>
                    <td class="text-end"><?= (int) $c['students'] ?></td>
                    <td class="text-end"><?= (int) $c['published'] ?></td>
                    <td class="text-end"><?= $c['average'] !== null ? pct($c['average']) : '—' ?></td>
                    <td class="text-nowrap"><?= $c['last_published'] ? html_escape(time_ago($c['last_published'])) : '<span class="text-muted">—</span>' ?></td>
                    <td class="text-end"><?php if ($c['published'] > 0): ?><a href="<?= base_url('admin_results/course/' . $c['id']) ?>" class="btn btn-sm btn-light">View</a><?php endif; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
<p class="small text-muted mt-2">Grades: <?php $t = []; foreach ($bands as $b) { $t[] = $b[0] === 'Fail' ? 'Fail below ' . score_fmt($bands[2][1]) . '%' : $b[0] . ' ' . score_fmt($b[1]) . '%+'; } echo html_escape(implode(' · ', $t)); ?></p>
