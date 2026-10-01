<?php
    $p = function ($key) use ($pay) { return isset($pay[$key]) ? trim((string) $pay[$key]) : ''; };
    $hasEcocash = $p('pay_ecocash_number') !== '';
    $hasBank    = $p('pay_bank_account') !== '';
?>
<p class="mb-3"><a href="<?= base_url('programs') ?>" class="back-link">&larr; Modules</a></p>

<div class="row g-3 justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-body">
                <h1 class="page-title mb-1">Submit proof of payment</h1>
                <p class="text-muted mb-4"><?= html_escape($module['name']) ?> &middot; <strong class="text-body"><?= money($module['fee_amount']) ?></strong></p>

                <?php if ($latest && $latest['status'] === 'rejected'): ?>
                    <div class="notice notice-danger mb-4">
                        <?= icon('alert', 16) ?>
                        <span>Your previous proof was not accepted<?= ! empty($latest['admin_note']) ? ': <strong>' . html_escape($latest['admin_note']) . '</strong>' : '.' ?> Please upload a clearer or correct one.</span>
                    </div>
                <?php endif; ?>

                <form method="post" action="<?= base_url('payments/upload/' . $enrollment['id']) ?>" enctype="multipart/form-data" data-loading>
                    <label class="form-label">How did you pay?</label>
                    <div class="choice-group mb-4">
                        <label class="choice">
                            <input type="radio" name="method" value="ecocash" <?= set_radio('method', 'ecocash', true) ?>>
                            <span><strong>EcoCash</strong><small>Mobile money</small></span>
                        </label>
                        <label class="choice">
                            <input type="radio" name="method" value="bank_transfer" <?= set_radio('method', 'bank_transfer') ?>>
                            <span><strong>Bank transfer</strong><small>Deposit or EFT</small></span>
                        </label>
                    </div>

                    <label class="form-label">Proof of payment</label>
                    <label class="dropzone" data-dropzone data-max-mb="4">
                        <input type="file" name="proof" accept=".jpg,.jpeg,.png,.pdf" required>
                        <div class="dz-empty">
                            <span class="dz-icon"><?= icon('upload', 26) ?></span>
                            <strong>Tap to choose a file</strong>
                            <small>or drag it here &middot; screenshot or PDF, up to 4MB</small>
                        </div>
                        <div class="dz-filled">
                            <img class="dz-preview" alt="">
                            <span class="dz-file-icon"><?= icon('file', 26) ?></span>
                            <div class="min-w-0">
                                <strong class="dz-name text-truncate d-block"></strong>
                                <small class="dz-size text-muted"></small>
                            </div>
                            <span class="dz-change">Change</span>
                        </div>
                        <div class="dz-error"></div>
                    </label>

                    <button type="submit" class="btn btn-primary btn-lg w-100 mt-4">Submit for review</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card pay-info">
            <div class="card-body">
                <div class="card-head"><h5 class="card-heading"><?= icon('card', 18) ?> Where to pay</h5></div>
                <?php if (! $hasEcocash && ! $hasBank): ?>
                    <p class="text-muted small mb-0">Use the EcoCash number or bank details <?= html_escape(setting('org_short_name', 'the Center')) ?> shared with you, then upload your confirmation here.</p>
                <?php endif; ?>
                <?php if ($hasEcocash): ?>
                    <div class="pay-block">
                        <div class="pay-label">EcoCash</div>
                        <div class="pay-value"><?= html_escape($p('pay_ecocash_number')) ?></div>
                        <?php if ($p('pay_ecocash_name') !== ''): ?><div class="small text-muted"><?= html_escape($p('pay_ecocash_name')) ?></div><?php endif; ?>
                    </div>
                <?php endif; ?>
                <?php if ($hasBank): ?>
                    <div class="pay-block">
                        <div class="pay-label">Bank transfer</div>
                        <?php if ($p('pay_bank_name') !== ''): ?><div class="small"><?= html_escape($p('pay_bank_name')) ?><?= $p('pay_bank_branch') !== '' ? ', ' . html_escape($p('pay_bank_branch')) : '' ?></div><?php endif; ?>
                        <div class="pay-value"><?= html_escape($p('pay_bank_account')) ?></div>
                        <?php if ($p('pay_bank_holder') !== ''): ?><div class="small text-muted"><?= html_escape($p('pay_bank_holder')) ?></div><?php endif; ?>
                    </div>
                <?php endif; ?>
                <?php if ($p('pay_reference_hint') !== ''): ?>
                    <div class="notice notice-info mt-3 mb-0"><?= icon('alert', 16) ?> <span><?= html_escape($p('pay_reference_hint')) ?></span></div>
                <?php endif; ?>
                <ol class="steps mt-3 mb-0">
                    <li>Pay <?= money($module['fee_amount']) ?> using either option.</li>
                    <li>Screenshot the confirmation message or slip.</li>
                    <li>Upload it here. Your module opens once it's approved.</li>
                </ol>
            </div>
        </div>
    </div>
</div>
