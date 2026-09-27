<?php $this->load->view('admin/reports/_filters', ['show' => ['by', 'course', 'scope']]); $label = $groupings[$by]; $total = array_sum(array_column($rows, 'students')); ?>
<?php if (! $rows): ?>
    <div class="card"><div class="empty-state">No students match these filters.</div></div>
<?php else: ?>
<div class="row g-3">
    <div class="col-lg-6"><div class="card h-100"><div class="card-body">
        <div class="card-head"><h5 class="card-heading"><?= $total ?> students by <?= strtolower($label) ?></h5></div>
        <?= svg_pie_chart(array_column($rows, 'group'), array_column($rows, 'students'), ['label' => 'Students by ' . strtolower($label)]) ?>
    </div></div></div>
    <div class="col-lg-6"><div class="card h-100"><div class="table-responsive">
        <table class="table report-table mb-0">
            <thead><tr><th><?= $label ?></th><th class="text-end">Students</th><th class="text-end">Share</th></tr></thead>
            <tbody><?php foreach ($rows as $r): ?><tr><td><?= html_escape($r['group']) ?></td><td class="text-end"><?= (int) $r['students'] ?></td><td class="text-end"><?= round($r['students'] / $total * 100) ?>%</td></tr><?php endforeach; ?></tbody>
            <tfoot><tr><th>All</th><th class="text-end"><?= $total ?></th><th class="text-end">100%</th></tr></tfoot>
        </table>
    </div></div></div>
</div>
<p class="small text-muted mt-2">From students' profiles (My profile &rarr; About you). A large "Not given" share means students still need to complete their profiles.</p>
<?php endif; ?>
