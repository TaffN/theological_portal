<?php
    $this->load->view('admin/reports/_filters', ['show' => ['by', 'course', 'year']]);
    $rows = $data['rows']; $t = $data['total'];
    $label = $groupings[$by];
    $legend = array_map(function ($r) { return (int) $r['passed'] . ' passed <small>of ' . (int) $r['results'] . ' (' . score_fmt($r['rate']) . '%)</small>'; }, $rows);
?>
<?php if (! $rows): ?>
    <div class="card"><div class="empty-state"><span class="empty-icon bg-soft-navy"><?= icon('award', 28) ?></span><br>No published results match these filters yet. Pass rates appear once lecturers publish results.</div></div>
<?php else: ?>
<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3"><div class="card stat-card"><div class="stat-icon bg-soft-navy"><?= icon('users', 22) ?></div><div><div class="stat-value"><?= (int) $t['results'] ?></div><div class="stat-label">Published results</div></div></div></div>
    <div class="col-6 col-lg-3"><div class="card stat-card"><div class="stat-icon bg-soft-green"><?= icon('check', 22) ?></div><div><div class="stat-value"><?= (int) $t['passed'] ?></div><div class="stat-label">Passed</div></div></div></div>
    <div class="col-6 col-lg-3"><div class="card stat-card"><div class="stat-icon bg-soft-gold"><?= icon('award', 22) ?></div><div><div class="stat-value"><?= score_fmt($t['rate']) ?>%</div><div class="stat-label">Overall pass rate</div></div></div></div>
    <div class="col-6 col-lg-3"><div class="card stat-card"><div class="stat-icon bg-soft-blue"><?= icon('layers', 22) ?></div><div><div class="stat-value"><?= score_fmt($t['average']) ?>%</div><div class="stat-label">Average final mark</div></div></div></div>
</div>
<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="card h-100"><div class="card-body">
            <div class="card-head"><h5 class="card-heading">Where the passes come from</h5><span class="card-sub">Share of all passes, by <?= strtolower($label) ?></span></div>
            <?= svg_pie_chart(array_column($rows, 'group'), array_column($rows, 'passed'), ['legend' => $legend, 'label' => 'Passes by ' . strtolower($label), 'empty' => 'Nobody has passed yet with these filters.']) ?>
        </div></div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100"><div class="card-body">
            <div class="card-head"><h5 class="card-heading">Pass rate per <?= strtolower($label) ?></h5><span class="card-sub">Passed out of results published</span></div>
            <?php $max = 100; ?>
            <ul class="hbar-list">
                <?php foreach ($rows as $r): ?>
                    <li>
                        <span class="hbar-label"><?= html_escape($r['group']) ?></span>
                        <span class="hbar-track"><span class="hbar-fill <?= rate_tone($r['rate']) ?>" style="width: <?= max(2, $r['rate']) ?>%"></span></span>
                        <span class="hbar-value"><?= score_fmt($r['rate']) ?>%</span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <p class="small text-muted mb-0 mt-2">Small groups swing a lot: 1 student failing out of 2 is a 50% pass rate. Check the numbers in the table.</p>
        </div></div>
    </div>
</div>
<div class="card">
    <div class="table-responsive">
        <table class="table report-table mb-0">
            <thead><tr><th><?= $label ?></th><th class="text-end">Results</th><th class="text-end">Passed</th><th class="text-end">Failed</th><th class="text-end">Pass rate</th><th class="text-end">Average</th></tr></thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr><td><?= html_escape($r['group']) ?></td><td class="text-end"><?= (int) $r['results'] ?></td><td class="text-end"><?= (int) $r['passed'] ?></td><td class="text-end"><?= (int) $r['failed'] ?></td>
                        <td class="text-end"><span class="rate-pill <?= rate_tone($r['rate']) ?>"><?= score_fmt($r['rate']) ?>%</span></td><td class="text-end"><?= score_fmt($r['average']) ?>%</td></tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot><tr><th>All</th><th class="text-end"><?= (int) $t['results'] ?></th><th class="text-end"><?= (int) $t['passed'] ?></th><th class="text-end"><?= (int) ($t['results'] - $t['passed']) ?></th><th class="text-end"><?= score_fmt($t['rate']) ?>%</th><th class="text-end"><?= score_fmt($t['average']) ?>%</th></tr></tfoot>
        </table>
    </div>
</div>
<p class="small text-muted mt-2">"Not given" = students who haven't filled in their <?= strtolower($label) ?> under My profile &rarr; About you. The pass mark comes from Settings &rarr; Results.</p>
<?php endif; ?>
