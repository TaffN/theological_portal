<?php
    $CI =& get_instance();
    $cred = $CI->session->flashdata('credentials');
    if (! $cred) { return; }

    $digits = preg_replace('/\D+/', '', (string) $cred['phone']);
    if (strpos($digits, '0') === 0) { $digits = '263' . substr($digits, 1); }   // 077... -> 26377...
    $msg = "Hello {$cred['name']}, here are your Theological Center Portal login details:\n\n"
         . "Login: {$cred['login']}\nEmail: {$cred['email']}\nPassword: {$cred['password']}\n"
         . (! empty($cred['id_number']) ? "Your ID number: {$cred['id_number']}\n" : '') . "\n"
         . "Please change your password after logging in (My Profile > Change password).";
?>
<div class="card credential-card mb-4">
    <div class="card-body">
        <div class="d-flex align-items-start gap-3 flex-wrap">
            <span class="stat-icon bg-soft-green"><?= icon('check', 22) ?></span>
            <div class="flex-grow-1 min-w-0">
                <h5 class="card-heading mb-1"><?= html_escape($cred['headline']) ?></h5>
                <p class="text-muted small mb-3">This password is shown <strong>only once</strong>. Send it now; it can't be looked up later (only reset again).</p>
                <div class="cred-grid">
                    <div><span class="pay-label">Email</span><div class="fw-semibold text-break"><?= html_escape($cred['email']) ?></div></div>
                    <?php if (! empty($cred['id_number'])): ?><div><span class="pay-label">ID number</span><div class="fw-semibold"><?= html_escape($cred['id_number']) ?></div></div><?php endif; ?>
                    <div>
                        <span class="pay-label">Temporary password</span>
                        <div class="d-flex align-items-center gap-2">
                            <code class="cred-password"><?= html_escape($cred['password']) ?></code>
                            <button type="button" class="icon-btn" data-copy="<?= html_escape($cred['password']) ?>" title="Copy password"><?= icon('file', 16) ?></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2 mt-3">
            <?php if ($digits !== ''): ?>
                <a class="btn btn-whatsapp" target="_blank" rel="noopener" href="https://wa.me/<?= $digits ?>?text=<?= rawurlencode($msg) ?>">Send on WhatsApp</a>
            <?php endif; ?>
            <button type="button" class="btn btn-outline-primary" data-copy="<?= html_escape($msg) ?>">Copy full message</button>
        </div>
    </div>
</div>
