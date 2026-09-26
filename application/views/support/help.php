<?php
    $org   = setting('org_name', 'Theological Center');
    $phone = setting('org_phone');
    $wa    = setting('org_whatsapp', $phone);
    $email = setting('org_email');
    $addr  = array_filter([setting('org_address'), setting('org_city'), setting('org_province'), setting('org_country')]);
    $faqs = [
        ['I paid, but my course hasn\'t opened yet.', 'An administrator checks every proof of payment against the EcoCash or bank statement, usually within one working day. You\'ll get a notification the moment it\'s approved. If your proof was rejected, the reason is shown on your course card with an Upload again button.'],
        ['How do I pay my fees?', 'Pay by EcoCash or bank transfer using the details on the payment page, screenshot the confirmation, then upload it from Courses → Submit proof of payment.' . (setting('pay_reference_hint') ? ' ' . setting('pay_reference_hint') : '')],
        ['I forgot my password.', 'Tap "Forgot password?" on the login page. The administrator will reset it and send you a new one on WhatsApp.'],
        ['Where do I find my course notes?', 'Open Materials from the menu. You\'ll also get a notification whenever your lecturer posts something new.'],
        ['Where is my student ID card?', 'Open My Profile. Your card is there with a QR code that staff can scan to confirm it\'s genuine. Add a clear photo first.'],
        ['Can I use the portal on my phone?', 'Yes. It\'s designed for phones. In your browser menu choose "Add to Home screen" and it opens like an app.'],
        ['Something isn\'t working.', 'Use Report a problem (in your account menu, or the button below). The page you\'re on is attached automatically so the team can fix it quickly.'],
    ];
?>
<div class="page-head">
    <div>
        <h1 class="page-title">Help &amp; contact</h1>
        <p class="page-sub">Quick answers, and how to reach <?= html_escape($org) ?>.</p>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card contact-card mb-3">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <span class="brand-mark"><?= html_escape(setting('org_initials', 'TC')) ?></span>
                    <div class="min-w-0">
                        <div class="fw-bold"><?= html_escape($org) ?></div>
                        <?php if (setting('org_registration_no')): ?><small class="text-muted">Reg. <?= html_escape(setting('org_registration_no')) ?></small><?php endif; ?>
                    </div>
                </div>

                <?php if ($wa): ?>
                    <a class="btn btn-whatsapp w-100 mb-2" target="_blank" rel="noopener" href="<?= html_escape(wa_link($wa, 'Hello ' . $org . ', I need help with the learning portal.')) ?>">Chat on WhatsApp</a>
                <?php endif; ?>

                <ul class="contact-list">
                    <?php if ($phone): ?><li><?= icon('phone', 18) ?><div><small>Phone</small><a href="tel:<?= html_escape(preg_replace('/[^\d+]/', '', $phone)) ?>"><?= html_escape($phone) ?></a></div></li><?php endif; ?>
                    <?php if ($email): ?><li><?= icon('file', 18) ?><div><small>Email</small><a href="mailto:<?= html_escape($email) ?>"><?= html_escape($email) ?></a></div></li><?php endif; ?>
                    <?php if ($addr): ?><li><?= icon('grid', 18) ?><div><small>Address</small><a target="_blank" rel="noopener" href="https://www.google.com/maps/search/?api=1&query=<?= rawurlencode(implode(', ', $addr)) ?>"><?= html_escape(implode(', ', $addr)) ?></a></div></li><?php endif; ?>
                    <?php if (setting('org_postal')): ?><li><?= icon('file', 18) ?><div><small>Postal</small><span><?= html_escape(setting('org_postal')) ?></span></div></li><?php endif; ?>
                    <?php if (setting('office_hours')): ?><li><?= icon('clock', 18) ?><div><small>Office hours</small><span><?= html_escape(setting('office_hours')) ?></span></div></li><?php endif; ?>
                    <?php if (setting('org_website')): ?><li><?= icon('monitor', 18) ?><div><small>Website</small><a target="_blank" rel="noopener" href="<?= html_escape(setting('org_website')) ?>"><?= html_escape(preg_replace('#^https?://#', '', setting('org_website'))) ?></a></div></li><?php endif; ?>
                </ul>
                <?php if (! $phone && ! $email && ! $addr && $role === 'admin'): ?>
                    <div class="notice notice-info mb-0"><?= icon('alert', 16) ?> <span>No contact details yet. <a href="<?= base_url('admin_settings') ?>">Add them in Organisation settings</a>.</span></div>
                <?php endif; ?>
            </div>
        </div>
        <div class="card">
            <div class="card-body d-flex align-items-center gap-3">
                <span class="stat-icon bg-soft-red"><?= icon('alert', 20) ?></span>
                <div class="flex-grow-1"><strong class="d-block">Something not working?</strong><small class="text-muted">We'll get back to you with a reference number.</small></div>
                <?php if ($loggedIn): ?>
                    <button type="button" class="btn btn-outline-primary btn-sm" data-report-open>Report</button>
                <?php else: ?>
                    <a href="<?= base_url('support/report') ?>" class="btn btn-outline-primary btn-sm">Report</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <h6 class="section-label">Frequently asked questions</h6>
        <div class="faq">
            <?php foreach ($faqs as $i => $f): ?>
                <details <?= $i === 0 ? 'open' : '' ?>>
                    <summary><?= html_escape($f[0]) ?><span class="faq-chev"><?= icon('arrow', 16) ?></span></summary>
                    <p><?= html_escape($f[1]) ?></p>
                </details>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="card mt-3" id="privacy">
    <div class="card-body">
        <div class="card-head"><h5 class="card-heading"><span class="stat-icon stat-icon-sm bg-soft-navy"><?= icon('shield', 16) ?></span> Privacy notice</h5>
            <span class="card-sub">How <?= html_escape($org) ?> looks after your information</span></div>
        <div class="privacy-text"><?= nl2br(html_escape(setting('privacy_notice', 'Your details are used only to run your studies and are never sold or shared.'))) ?></div>
    </div>
</div>
