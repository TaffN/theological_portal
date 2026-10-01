<?= module_crumbs($module, 'Results') ?: '<p class="mb-3"><a href="' . base_url('admin_results') . '" class="back-link">&larr; Results</a></p>' ?>

<div class="page-head">
    <div>
        <h1 class="page-title"><?= html_escape($module['name']) ?></h1>
        <p class="page-sub"><?= count($rows) ?> published result<?= count($rows) == 1 ? '' : 's' ?>.</p>
    </div>
    <a href="<?= base_url('admin_results/export/' . $module['id']) ?>" class="btn btn-outline-primary"><?= icon('upload', 16) ?> Export (CSV)</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-clean align-middle mb-0">
            <thead><tr><th>Student</th><th class="text-end">Assignments</th><th class="text-end">Exams</th><th class="text-end">Final</th><th>Grade</th><th>Remarks</th><th>Published</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><span class="fw-semibold d-block"><?= html_escape($r['student_name']) ?></span><span class="id-chip"><?= html_escape($r['id_number']) ?></span></td>
                    <td class="text-end"><?= pct($r['assignment_pct']) ?> <small class="text-muted">&times;<?= (int) $r['assignment_weight'] ?>%</small></td>
                    <td class="text-end"><?= pct($r['exam_pct']) ?> <small class="text-muted">&times;<?= (int) $r['exam_weight'] ?>%</small></td>
                    <td class="text-end fw-bold"><?= pct($r['final_pct']) ?></td>
                    <td><?= grade_badge($r['grade']) ?></td>
                    <td class="small"><?= $r['remarks'] ? html_escape($r['remarks']) : '<span class="text-muted">—</span>' ?></td>
                    <td class="small text-nowrap"><?= html_escape(date('j M Y', strtotime($r['published_at']))) ?><small class="text-muted d-block"><?= html_escape((string) $r['publisher_name']) ?></small></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
