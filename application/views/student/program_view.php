<?= crumbs([['Programs', 'programs'], [$program['name']]]) ?>
<div class="page-head">
    <div>
        <h1 class="page-title"><?= html_escape($program['name']) ?></h1>
        <p class="page-sub">
            <?php if ($program['duration_text']): ?><?= icon('clock', 14) ?> <?= html_escape($program['duration_text']) ?> &middot; <?php endif; ?>
            <?= count($modules) ?> module<?= count($modules) == 1 ? '' : 's' ?> &middot; <?= (float) $program['fee_amount'] > 0 ? money($program['fee_amount']) . ' for the whole program' : 'Free' ?>
        </p>
    </div>
</div>
<?php if ($program['description']): ?>
    <p class="text-muted mb-4"><?= nl2br(html_escape($program['description'])) ?></p>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-body">
    <?php if (! $pe): ?>
        <div class="d-flex flex-wrap align-items-center gap-3">
            <div class="flex-grow-1">
                <strong>Join this program</strong>
                <div class="text-muted small">Pay <?= (float) $program['fee_amount'] > 0 ? money($program['fee_amount']) : 'nothing' ?> once by EcoCash or bank transfer and all <?= count($modules) ?> module<?= count($modules) == 1 ? '' : 's' ?> open when your payment is approved.</div>
            </div>
            <form method="post" action="<?= base_url('programs/apply/' . (int) $program['id']) ?>"
                  data-confirm="Apply for <?= html_escape($program['name']) ?>? You'll then upload your proof of payment (<?= money($program['fee_amount']) ?>)."
                  data-confirm-ok="Apply">
                <button type="submit" class="btn btn-primary">Apply now</button>
            </form>
        </div>
    <?php elseif ($pe['status'] === 'pending_payment'): ?>
        <?php if ($payment && $payment['status'] === 'pending'): ?>
            <div class="notice notice-info mb-0"><?= icon('clock', 16) ?> Proof of payment sent <?= html_escape(time_ago($payment['submitted_at'])) ?>. Awaiting review.</div>
        <?php else: ?>
            <?php if ($payment && $payment['status'] === 'rejected'): ?>
                <div class="notice notice-danger">
                    <?= icon('alert', 16) ?>
                    <span>Last proof was not accepted<?= ! empty($payment['admin_note']) ? ': ' . html_escape($payment['admin_note']) : '.' ?></span>
                </div>
            <?php endif; ?>
            <div class="d-flex flex-wrap align-items-center gap-3">
                <div class="flex-grow-1"><strong>Payment needed: <?= money($pe['fee_amount']) ?></strong>
                    <div class="text-muted small">Your modules open as soon as the payment is approved.</div></div>
                <a href="<?= base_url('payments/upload/' . (int) $pe['id']) ?>" class="btn btn-gold"><?= icon('upload', 16) ?> <?= $payment ? 'Upload again' : 'Submit proof of payment' ?></a>
            </div>
        <?php endif; ?>
    <?php elseif ($pe['status'] === 'active' || $pe['status'] === 'completed'): ?>
        <div class="d-flex align-items-center gap-2"><?= status_badge($pe['status']) ?> <span>You are enrolled in this program.</span></div>
    <?php else: ?>
        <div class="d-flex align-items-center gap-2"><?= status_badge($pe['status']) ?> <span>Your enrolment is on hold. Please contact the Center.</span></div>
    <?php endif; ?>
    </div>
</div>

<?php if (empty($modules)): ?>
    <div class="card"><div class="empty-state"><?= icon('book', 32) ?><br>No modules have been added to this program yet.</div></div>
<?php endif; ?>

<div class="row g-3">
<?php foreach ($modules as $i => $module): ?>
    <?php
        $open = ! empty($access[(int) $module['id']]);
    ?>
    <div class="col-md-6 col-xl-4">
        <div class="card module-card h-100">
            <div class="module-band band-<?= $i % 4 ?>">
                <span class="module-initials"><?= html_escape(initials($module['name'])) ?></span>
                <?php if ($open): ?><?= status_badge('active') ?><?php endif; ?>
            </div>
            <div class="card-body d-flex flex-column">
                <h5 class="module-name"><?= html_escape($module['name']) ?></h5>
                <div class="module-code"><?= html_escape($module['code']) ?></div>
                <?php if ($module['description']): ?>
                    <p class="text-muted small mb-3"><?= html_escape($module['description']) ?></p>
                <?php endif; ?>

                <ul class="module-meta">
                    <?php if ((int) $module['credits'] > 0): ?><li><?= icon('award', 16) ?> <?= (int) $module['credits'] ?> credit<?= $module['credits'] == 1 ? '' : 's' ?></li><?php endif; ?>
                    <?php if ($module['duration_text']): ?><li><?= icon('clock', 16) ?> <?= html_escape($module['duration_text']) ?></li><?php endif; ?>
                    <?php if (! empty($module['lecturers'])): ?>
                        <li><?= icon('user', 16) ?> <?= html_escape(implode(', ', array_column($module['lecturers'], 'name'))) ?></li>
                    <?php endif; ?>
                </ul>

                <?php if ($open): ?>
                <div class="mt-auto">
                    <a href="<?= base_url('student_materials/module/' . $module['id']) ?>" class="btn btn-outline-primary w-100">
                        <?= icon('folder', 16) ?> Open module materials
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>
