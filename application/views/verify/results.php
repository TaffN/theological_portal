<?php $org = setting('org_name', 'Theological Center'); ?><!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Results verification &middot; <?= html_escape($org) ?></title>
    <script>(function(){try{var p=localStorage.getItem('tc-theme')||'system';var d=p==='dark'||(p==='system'&&matchMedia('(prefers-color-scheme: dark)').matches);document.documentElement.setAttribute('data-bs-theme',d?'dark':'light');}catch(e){}})();</script>
    <?php if (file_exists(FCPATH . 'assets/css/bootstrap.min.css')): ?>
        <link href="<?= base_url('assets/css/bootstrap.min.css') ?>" rel="stylesheet">
    <?php else: ?>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php endif; ?>
    <link href="<?= base_url('assets/css/app.css') ?>?v=12" rel="stylesheet">
    <link rel="icon" type="image/svg+xml" href="<?= base_url('assets/img/favicon.svg') ?>">
</head>
<body class="auth-body">
<main class="verify-wrap">
    <div class="verify-brand"><span class="brand-mark brand-mark-sm"><?= html_escape(setting('org_initials', 'TC')) ?></span><strong><?= html_escape($org) ?></strong></div>

    <?php if (! $u): ?>
        <div class="card verify-card">
            <div class="verify-status verify-bad"><?= icon('x', 30) ?></div>
            <h1 class="h4 fw-bold">Statement not recognised</h1>
            <p class="text-muted mb-0">This QR code doesn't match any statement of results issued by <?= html_escape($org) ?>. The statement may not be genuine.</p>
        </div>
    <?php else: ?>
        <div class="card verify-card verify-card-wide">
            <div class="verify-status <?= $results ? 'verify-ok' : 'verify-bad' ?>"><?= icon($results ? 'check' : 'alert', 30) ?></div>
            <div class="verify-verdict <?= $results ? 'text-success-soft' : 'text-danger-soft' ?>"><?= $results ? 'Genuine results' : 'No results currently published' ?></div>
            <h1 class="h4 fw-bold mb-1"><?= html_escape($name) ?></h1>
            <div class="verify-id"><?= html_escape($u['id_number']) ?></div>

            <?php if ($results): ?>
                <p class="small text-muted mt-3 mb-2">These are the results <?= html_escape($org) ?> has published for this student today. They should match the printed statement exactly.</p>
                <table class="table statement-table text-start align-middle mb-0">
                    <thead><tr><th>Module</th><th class="text-end">Final</th><th>Grade</th></tr></thead>
                    <tbody>
                    <?php foreach ($results as $r): ?>
                        <tr>
                            <td><span class="fw-semibold"><?= html_escape($r['module_name']) ?></span><small class="text-muted d-block">Published <?= html_escape(date('j M Y', strtotime($r['published_at']))) ?></small></td>
                            <td class="text-end fw-bold"><?= pct($r['final_pct']) ?></td>
                            <td><?= grade_badge($r['grade']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-muted mt-3 mb-0">This student has no published results at the moment. A result may have been withdrawn for review.</p>
            <?php endif; ?>
            <p class="small text-muted mt-3 mb-0">Checked <?= date('j M Y, H:i') ?></p>
        </div>
    <?php endif; ?>

    <p class="verify-contact">
        Questions? Contact <?= html_escape($org) ?>
        <?php if (setting('org_phone')): ?> &middot; <?= html_escape(setting('org_phone')) ?><?php endif; ?>
        <?php if (setting('org_email')): ?> &middot; <a href="mailto:<?= html_escape(setting('org_email')) ?>"><?= html_escape(setting('org_email')) ?></a><?php endif; ?>
    </p>
</main>
</body>
</html>
