<?php
    $CI =& get_instance();
    $reports = [
        'index' => ['Overview', 'grid'], 'pass_rates' => ['Pass rates by region', 'award'], 'grades' => ['Grades', 'layers'],
        'students' => ['Students', 'users'], 'attendance' => ['Attendance', 'check-square'], 'fees' => ['Fees', 'dollar'], 'documents' => ['Documents', 'file'],
    ];
    $qs = $_SERVER['QUERY_STRING'] ?? '';
    $exportable = in_array($current, ['pass_rates', 'grades', 'students', 'attendance', 'fees'], true);
?>
<div class="page-head">
    <div>
        <h1 class="page-title"><?= html_escape($title) ?></h1>
        <p class="page-sub"><?= $current === 'index' ? 'Figures for the Center\'s board and partners. Every report can be printed or exported to Excel.' : 'Printed ' . date('j F Y') . ' &middot; ' . html_escape(setting('org_name', 'Theological Center')) ?></p>
    </div>
    <?php if ($current !== 'index'): ?>
        <div class="d-flex gap-2 no-print">
            <button type="button" class="btn btn-light btn-sm" onclick="window.print()"><?= icon('file', 15) ?> Print / PDF</button>
            <?php if ($exportable): ?><a href="<?= base_url('admin_reports/export/' . $current) . ($qs ? '?' . html_escape($qs) : '') ?>" class="btn btn-outline-primary btn-sm"><?= icon('upload', 15) ?> Export CSV</a><?php endif; ?>
        </div>
    <?php endif; ?>
</div>
<nav class="tabs tabs-scroll mb-3 no-print" aria-label="Reports">
    <?php foreach ($reports as $k => $r): ?>
        <a href="<?= base_url('admin_reports' . ($k === 'index' ? '' : '/' . $k)) ?>" class="tab <?= $k === $current ? 'active' : '' ?>"><?= $r[0] ?></a>
    <?php endforeach; ?>
</nav>
