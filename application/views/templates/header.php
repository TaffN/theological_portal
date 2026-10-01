<?php
    $CI =& get_instance();
    $CI->load->helper('ui');
    extract(layout_context());
    $pageTitle   = isset($title) ? $title : 'Portal';
    $orgShort    = setting('org_short_name', 'Theological Center');
    $orgInitials = setting('org_initials', 'TC');
    $orgTagline  = setting('org_tagline', 'Learning Portal');
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#F5F7FB" id="themeColorMeta">
    <title><?= html_escape($pageTitle) ?> &middot; <?= html_escape($orgShort) ?></title>

    <script>
        // Runs before anything paints, so dark mode never flashes white.
        (function () {
            try {
                var pref = localStorage.getItem('tc-theme') || 'system';
                var dark = pref === 'dark' || (pref === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                document.documentElement.setAttribute('data-bs-theme', dark ? 'dark' : 'light');
                document.getElementById('themeColorMeta').setAttribute('content', dark ? '#0A1020' : '#F5F7FB');
                if (localStorage.getItem('tc-sidebar') === 'collapsed') {
                    document.documentElement.classList.add('sidebar-collapsed');
                }
            } catch (e) {}
        })();
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?php if (file_exists(FCPATH . 'assets/css/bootstrap.min.css')): ?>
        <link href="<?= base_url('assets/css/bootstrap.min.css') ?>" rel="stylesheet">
    <?php else: ?>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php endif; ?>
    <link href="<?= base_url('assets/css/app.css') ?>?v=12" rel="stylesheet">

    <link rel="icon" type="image/svg+xml" href="<?= base_url('assets/img/favicon.svg') ?>">
    <link rel="icon" type="image/png" sizes="192x192" href="<?= base_url('assets/img/icon-192.png') ?>">
    <link rel="apple-touch-icon" href="<?= base_url('assets/img/apple-touch-icon.png') ?>">
    <link rel="manifest" href="<?= base_url('assets/manifest.json') ?>">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="<?= html_escape($orgInitials) ?> Portal">
    <meta name="mobile-web-app-capable" content="yes">
</head>
<body class="<?= $userId ? 'app-body' : 'auth-body' ?>">
<div class="top-progress" id="topProgress" aria-hidden="true"></div>
<div class="offline-banner" id="offlineBanner" role="status" hidden><?= icon('alert', 16) ?> You're offline. Pages you open will load once your connection is back.</div>

<?php if ($userId): ?>

<div class="app-shell">

    <!-- ============ Sidebar (desktop) ============ -->
    <aside class="app-sidebar d-none d-lg-flex" aria-label="Main menu">
        <div class="sidebar-top">
            <a href="<?= base_url('dashboard') ?>" class="sidebar-brand">
                <span class="brand-mark"><?= html_escape($orgInitials) ?></span>
                <span class="brand-text"><?= html_escape($orgShort) ?><small><?= html_escape($orgTagline) ?></small></span>
            </a>
        </div>

        <nav class="sidebar-nav">
            <?php $lastSection = null; ?>
            <?php foreach ($navItems as $item): ?>
                <?php $section = ! empty($item['soon']) ? 'Coming soon' : $item['section']; ?>
                <?php if ($section !== $lastSection): $lastSection = $section; ?><div class="sidebar-section"><?= html_escape($section) ?></div><?php endif; ?>
                <?php if (! empty($item['soon'])): ?>
                    <span class="sidebar-link is-soon" title="<?= html_escape($item['label']) ?> (coming soon)">
                        <?= icon($item['icon']) ?><span><?= html_escape($item['label']) ?></span>
                        <span class="soon-tag">Soon</span>
                    </span>
                <?php else: ?>
                    <?php $count = isset($item['badge']) ? $badges[$item['badge']] : 0; $active = nav_is_active($item, $uri, $segment); ?>
                    <a href="<?= base_url($item['url']) ?>" class="sidebar-link <?= $active ? 'active' : '' ?>"
                       title="<?= html_escape($item['label']) ?>" <?= $active ? 'aria-current="page"' : '' ?>>
                        <?= icon($item['icon']) ?><span><?= html_escape($item['label']) ?></span>
                        <?php if ($count > 0): ?><span class="count-pill <?= isset($item['badge']) && $item['badge'] === 'errors' ? 'count-pill-danger' : '' ?>"><?= (int) $count ?></span><?php endif; ?>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>

        <div class="sidebar-user">
            <a href="<?= base_url('profile') ?>" class="sidebar-user-link <?= $segment === 'profile' ? 'active' : '' ?>" title="My profile">
                <?= avatar_html($userName, $userId, $photoVer) ?>
                <span class="min-w-0 flex-grow-1">
                    <span class="sidebar-user-name text-truncate d-block"><?= html_escape($userName) ?></span>
                    <span class="sidebar-user-role d-block"><?= $idNumber ? html_escape($idNumber) : html_escape(ucfirst((string) $role)) ?></span>
                </span>
            </a>
            <a href="<?= base_url('auth/logout') ?>" class="sidebar-logout" title="Log out"><?= icon('logout', 18) ?></a>
        </div>
    </aside>

    <div class="app-content">

        <!-- ============ Top bar ============ -->
        <header class="app-topbar">
            <button type="button" class="icon-btn ghost d-none d-lg-inline-grid" data-sidebar-toggle title="Collapse / expand menu" aria-label="Toggle menu">
                <?= icon('sidebar', 19) ?>
            </button>
            <a href="<?= base_url('dashboard') ?>" class="brand-mark brand-mark-sm d-lg-none" aria-label="Dashboard"><?= html_escape($orgInitials) ?></a>

            <div class="crumbs min-w-0">
                <span class="crumb-root d-none d-sm-inline">Portal</span>
                <span class="crumb-sep d-none d-sm-inline">/</span>
                <span class="crumb-current"><?= html_escape($pageTitle) ?></span>
            </div>

            <div class="ms-auto d-flex align-items-center gap-2">
                <button type="button" class="search-trigger d-none d-md-flex" data-palette-open aria-label="Search">
                    <?= icon('search', 16) ?> <span>Search or jump to…</span> <span class="kbd">Ctrl K</span>
                </button>
                <button type="button" class="icon-btn d-md-none" data-palette-open aria-label="Search"><?= icon('search', 18) ?></button>

                <button type="button" class="icon-btn theme-toggle" data-theme-toggle title="Switch light / dark" aria-label="Switch light or dark mode">
                    <span class="i-moon"><?= icon('moon', 18) ?></span>
                    <span class="i-sun"><?= icon('sun', 18) ?></span>
                </button>

                <a href="<?= base_url('notifications') ?>" class="icon-btn" title="Notifications" aria-label="Notifications">
                    <?= icon('bell', 18) ?>
                    <?php if ($badges['notifications'] > 0): ?><span class="dot-badge"><?= (int) $badges['notifications'] ?></span><?php endif; ?>
                </a>

                <div class="dropdown">
                    <button type="button" class="avatar-btn ms-1" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Account menu">
                        <?= avatar_html($userName, $userId, $photoVer, 'avatar-sm') ?>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                        <div class="menu-head">
                            <?= avatar_html($userName, $userId, $photoVer) ?>
                            <div class="min-w-0">
                                <div class="fw-bold text-truncate"><?= html_escape($userName) ?></div>
                                <small class="text-muted"><?= html_escape(ucfirst((string) $role)) ?><?= $idNumber ? ' &middot; ' . html_escape($idNumber) : '' ?></small>
                            </div>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="<?= base_url('profile') ?>"><?= icon('user', 17) ?> My profile</a>
                        <a class="dropdown-item" href="<?= base_url('profile') ?>#id-card"><?= icon('id-card', 17) ?> My ID card</a>
                        <a class="dropdown-item" href="<?= base_url('notifications') ?>"><?= icon('bell', 17) ?> Notifications</a>
                        <button type="button" class="dropdown-item" data-report-open><?= icon('alert', 17) ?> Report a problem</button>
                        <div class="dropdown-divider"></div>
                        <div class="menu-label">Appearance</div>
                        <div class="segmented" role="group" aria-label="Theme">
                            <button type="button" data-theme-choice="light"><?= icon('sun', 14) ?> Light</button>
                            <button type="button" data-theme-choice="dark"><?= icon('moon', 14) ?> Dark</button>
                            <button type="button" data-theme-choice="system"><?= icon('monitor', 14) ?> Auto</button>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item text-danger" href="<?= base_url('auth/logout') ?>"><?= icon('logout', 17) ?> Log out</a>
                    </div>
                </div>
            </div>
        </header>

        <main class="app-main" id="main">

<?php else: ?>

<div class="auth-split">
    <aside class="auth-hero">
        <a href="<?= base_url() ?>" class="auth-brand">
            <span class="brand-mark"><?= html_escape($orgInitials) ?></span>
            <span class="brand-text"><?= html_escape($orgShort) ?><small><?= html_escape($orgTagline) ?></small></span>
        </a>

        <div>
            <h2 class="hero-title">Grow in knowledge.<br><span>Study from anywhere.</span></h2>
            <ul class="hero-list">
                <li><span class="hero-ic"><?= icon('phone', 18) ?></span><div><strong>Made for your phone</strong>Read notes, submit work and sit exams on the go.</div></li>
                <li><span class="hero-ic"><?= icon('folder', 18) ?></span><div><strong>Everything in one place</strong>No more hunting through WhatsApp for module materials.</div></li>
                <li><span class="hero-ic"><?= icon('card', 18) ?></span><div><strong>Simple fee payments</strong>Pay by EcoCash or bank transfer and upload your proof.</div></li>
            </ul>
        </div>

        <blockquote class="hero-quote mb-0">
            Study to shew thyself approved unto God, a workman that needeth not to be ashamed, rightly dividing the word of truth.
            <cite>2 Timothy 2:15 (KJV)</cite>
        </blockquote>
    </aside>

    <section class="auth-panel">
        <div class="auth-panel-top">
            <a href="<?= base_url() ?>" class="auth-brand">
                <span class="brand-mark brand-mark-sm"><?= html_escape($orgInitials) ?></span>
                <span class="brand-text"><?= html_escape($orgShort) ?><small><?= html_escape($orgTagline) ?></small></span>
            </a>
            <button type="button" class="icon-btn theme-toggle" data-theme-toggle title="Switch light / dark" aria-label="Switch light or dark mode">
                <span class="i-moon"><?= icon('moon', 18) ?></span>
                <span class="i-sun"><?= icon('sun', 18) ?></span>
            </button>
        </div>
        <main class="auth-panel-inner<?= (isset($title) && $title === 'Register') ? ' wide' : '' ?>" id="main">

<?php endif; ?>

<?php
    $flashes = [];
    if ($CI->session->flashdata('success')) { $flashes[] = ['success', 'check', html_escape($CI->session->flashdata('success'))]; }
    if ($CI->session->flashdata('error'))   { $flashes[] = ['error', 'alert', html_escape($CI->session->flashdata('error'))]; }
    if (validation_errors())                { $flashes[] = ['error', 'alert', validation_errors('<span class="d-block">', '</span>')]; }
?>
<?php foreach ($flashes as $f): ?>
    <div class="flash flash-<?= $f[0] ?>" role="<?= $f[0] === 'error' ? 'alert' : 'status' ?>">
        <span class="flash-icon"><?= icon($f[1], 18) ?></span>
        <div class="flash-body"><?= $f[2] ?></div>
        <button type="button" class="flash-close" aria-label="Dismiss"><?= icon('x', 16) ?></button>
    </div>
<?php endforeach; ?>
