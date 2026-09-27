<?php
    $org      = setting('org_name', 'Theological Center');
    $active   = $u && $u['status'] === 'active';
    $months   = (int) setting('id_card_valid_months', '12');
    $roleName = $u ? ['student' => 'Student', 'lecturer' => 'Lecturer', 'admin' => 'Staff'][$u['role']] : '';
    $name     = $u ? trim(($profile['title'] ? $profile['title'] . ' ' : '') . $u['name']) : '';
?><!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>ID verification &middot; <?= html_escape($org) ?></title>
    <script>(function(){try{var p=localStorage.getItem('tc-theme')||'system';var d=p==='dark'||(p==='system'&&matchMedia('(prefers-color-scheme: dark)').matches);document.documentElement.setAttribute('data-bs-theme',d?'dark':'light');}catch(e){}})();</script>
    <?php if (file_exists(FCPATH . 'assets/css/bootstrap.min.css')): ?>
        <link href="<?= base_url('assets/css/bootstrap.min.css') ?>" rel="stylesheet">
    <?php else: ?>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php endif; ?>
    <link href="<?= base_url('assets/css/app.css') ?>?v=9" rel="stylesheet">
    <link rel="icon" type="image/svg+xml" href="<?= base_url('assets/img/favicon.svg') ?>">
</head>
<body class="auth-body">
<main class="verify-wrap">
    <div class="verify-brand"><span class="brand-mark brand-mark-sm"><?= html_escape(setting('org_initials', 'TC')) ?></span><strong><?= html_escape($org) ?></strong></div>

    <?php if (! $u): ?>
        <div class="card verify-card">
            <div class="verify-status verify-bad"><?= icon('x', 30) ?></div>
            <h1 class="h4 fw-bold">Card not recognised</h1>
            <p class="text-muted mb-0">This QR code doesn't match any current ID card. The card may have been replaced or cancelled, or it may not be genuine.</p>
        </div>
    <?php else: ?>
        <div class="card verify-card">
            <div class="verify-status <?= $active ? 'verify-ok' : 'verify-bad' ?>"><?= icon($active ? 'check' : 'alert', 30) ?></div>
            <div class="verify-verdict <?= $active ? 'text-success-soft' : 'text-danger-soft' ?>"><?= $active ? 'Valid ID card' : 'Not currently active' ?></div>

            <?php if ($isStaff && $u['photo_path']): ?>
                <img class="verify-photo" src="<?= base_url('photo/view/' . (int) $u['id']) ?>?v=<?= strtotime($u['photo_updated_at']) ?>" alt="Photo of <?= html_escape($u['name']) ?>">
            <?php endif; ?>

            <h1 class="h4 fw-bold mb-1"><?= html_escape($name) ?></h1>
            <div class="verify-id"><?= html_escape($u['id_number']) ?></div>
            <span class="pill <?= $u['role'] === 'lecturer' ? 'pill-muted' : 'pill-warning' ?> mt-2"><?= $roleName ?></span>

            <dl class="facts text-start mt-4">
                <?php if ($isStaff && ! empty($courses)): ?>
                    <dt><?= $u['role'] === 'lecturer' ? 'Teaches' : 'Enrolled in' ?></dt><dd><?= html_escape(implode(', ', $courses)) ?></dd>
                <?php endif; ?>
                <dt>Member since</dt><dd><?= html_escape(date('F Y', strtotime($u['created_at']))) ?></dd>
                <dt>Checked</dt><dd><?= date('j M Y, H:i') ?></dd>
            </dl>

            <?php if (! $isStaff): ?>
                <p class="small text-muted mt-3 mb-0">Compare the name and ID number with the card. Staff who are logged in also see the holder's photo here.</p>
            <?php elseif (! empty($isAdmin)): ?>
                <a href="<?= base_url('admin_users/card/' . (int) $u['id']) ?>" class="btn btn-outline-primary btn-sm mt-3">Open full record</a>
            <?php endif; ?>
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
