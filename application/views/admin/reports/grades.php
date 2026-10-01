<?php $this->load->view('admin/reports/_filters', ['show' => ['program', 'module', 'year']]); $g = $data['grades']; ?>
<?php if (! array_sum($g)): ?>
    <div class="card"><div class="empty-state">No published results match these filters yet.</div></div>
<?php else: ?>
<div class="row g-3 mb-3">
    <div class="col-lg-5"><div class="card h-100"><div class="card-body">
        <div class="card-head"><h5 class="card-heading">Grade spread</h5></div>
        <?= svg_donut_chart(array_keys($g), array_values($g), ['center_label' => 'Results']) ?>
    </div></div></div>
    <div class="col-lg-7"><div class="card h-100"><div class="card-body">
        <div class="card-head"><h5 class="card-heading">Pass rate per module</h5></div>
        <?= svg_bar_chart(array_column($data['modules'], 'module'), array_column($data['modules'], 'rate'), ['label' => 'Pass rate per module', 'decimals' => 0]) ?>
    </div></div></div>
</div>
<div class="card"><div class="table-responsive">
    <table class="table report-table mb-0">
        <thead><tr><th>Module</th><th class="text-end">Results</th><th class="text-end">Distinction</th><th class="text-end">Merit</th><th class="text-end">Pass</th><th class="text-end">Fail</th><th class="text-end">Pass rate</th><th class="text-end">Average</th></tr></thead>
        <tbody><?php foreach ($data['modules'] as $c): ?>
            <tr><td><?= html_escape($c['module']) ?></td><td class="text-end"><?= (int) $c['results'] ?></td><td class="text-end"><?= (int) $c['Distinction'] ?></td><td class="text-end"><?= (int) $c['Merit'] ?></td><td class="text-end"><?= (int) $c['Pass'] ?></td><td class="text-end"><?= (int) $c['Fail'] ?></td>
                <td class="text-end"><span class="rate-pill <?= rate_tone($c['rate']) ?>"><?= score_fmt($c['rate']) ?>%</span></td><td class="text-end"><?= score_fmt($c['average']) ?>%</td></tr>
        <?php endforeach; ?></tbody>
    </table>
</div></div>
<?php endif; ?>
