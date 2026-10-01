<?php $withData = array_values(array_filter($rows, function ($r) { return $r['rate'] !== null; })); ?>
<div class="card mb-3"><div class="card-body">
    <div class="card-head"><h5 class="card-heading">Attendance rate per module</h5><span class="card-sub">(present + late) out of every mark</span></div>
    <?= svg_bar_chart(array_column($withData, 'name'), array_column($withData, 'rate'), ['label' => 'Attendance rate per module', 'empty' => 'No registers taken yet.']) ?>
</div></div>
<div class="card"><div class="table-responsive">
    <table class="table report-table mb-0">
        <thead><tr><th>Module</th><th class="text-end">Students</th><th class="text-end">Registers</th><th>Last register</th><th class="text-end">Attendance</th></tr></thead>
        <tbody><?php foreach ($rows as $r): ?><tr><td><a href="<?= base_url('admin_attendance/module/' . $r['id']) ?>"><?= html_escape($r['name']) ?></a></td><td class="text-end"><?= (int) $r['students'] ?></td><td class="text-end"><?= (int) $r['sessions'] ?></td><td><?= $r['last_date'] ? html_escape(date('j M Y', strtotime($r['last_date']))) : '&ndash;' ?></td>
            <td class="text-end"><span class="rate-pill <?= rate_tone($r['rate']) ?>"><?= $r['rate'] === null ? '&ndash;' : score_fmt($r['rate']) . '%' ?></span></td></tr><?php endforeach; ?></tbody>
    </table>
</div></div>
