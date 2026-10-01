<?php $this->load->view('admin/reports/_filters', ['show' => ['year', 'fees']]); $labels = array_map(function ($ym) { return date('M', strtotime($ym . '-01')); }, array_keys($data['months'])); ?>
<div class="row g-3 mb-3">
    <div class="col-6 col-lg-4"><div class="card stat-card"><div class="stat-icon bg-soft-green"><?= icon('dollar', 22) ?></div><div><div class="stat-value"><?= money(array_sum($data['months'])) ?></div><div class="stat-label"><?= $year ? 'Collected in ' . (int) $year : 'Last 12 months' ?></div></div></div></div>
    <div class="col-6 col-lg-4"><div class="card stat-card"><div class="stat-icon bg-soft-gold"><?= icon('clock', 22) ?></div><div><div class="stat-value"><?= (int) $data['pending'] ?></div><div class="stat-label">Proofs waiting to be checked</div></div></div></div>
</div>
<div class="card mb-3"><div class="card-body">
    <div class="card-head"><h5 class="card-heading">Fees collected per month</h5><span class="card-sub">Approved payments</span></div>
    <?= svg_bar_chart($labels, array_values($data['months']), ['prefix' => '$', 'label' => 'Fees per month', 'empty' => 'No approved payments in this period.']) ?>
</div></div>
<div class="card"><div class="table-responsive">
    <table class="table report-table mb-0">
        <thead><tr><th>Module</th><th class="text-end">Approved payments</th><th class="text-end">Total</th></tr></thead>
        <tbody><?php foreach ($data['modules'] as $c): ?><tr><td><?= html_escape($c['name']) ?></td><td class="text-end"><?= (int) $c['payments'] ?></td><td class="text-end"><?= money($c['total']) ?></td></tr><?php endforeach; ?></tbody>
        <tfoot><tr><th>All modules</th><th></th><th class="text-end"><?= money($data['total']) ?></th></tr></tfoot>
    </table>
</div></div>
