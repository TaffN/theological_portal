<?= crumbs([['Programs', 'programs'], [$program['name']]]) ?>
<div class="page-head">
    <div>
        <h1 class="page-title"><?= html_escape($program['name']) ?></h1>
        <p class="page-sub">
            <?php if ($program['duration_text']): ?><?= icon('clock', 14) ?> <?= html_escape($program['duration_text']) ?> &middot; <?php endif; ?>
            <?= count($modules) ?> module<?= count($modules) == 1 ? '' : 's' ?>. Apply for the modules you want, pay by EcoCash or bank transfer, and each opens once its payment is approved.
        </p>
    </div>
</div>
<?php if ($program['description']): ?>
    <p class="text-muted mb-4"><?= nl2br(html_escape($program['description'])) ?></p>
<?php endif; ?>

<?php if (empty($modules)): ?>
    <div class="card"><div class="empty-state"><?= icon('book', 32) ?><br>No modules are open for applications in this program right now.</div></div>
<?php endif; ?>

<div class="row g-3">
<?php foreach ($modules as $i => $module): ?>
    <?php
        $mine    = $statusByModuleId[$module['id']] ?? null;
        $payment = ($mine && isset($latestPayments[(int) $mine['enrollment_id']])) ? $latestPayments[(int) $mine['enrollment_id']] : null;
    ?>
    <div class="col-md-6 col-xl-4">
        <div class="card module-card h-100">
            <div class="module-band band-<?= $i % 4 ?>">
                <span class="module-initials"><?= html_escape(initials($module['name'])) ?></span>
                <?php if ($mine): ?><?= status_badge($mine['status']) ?><?php endif; ?>
            </div>
            <div class="card-body d-flex flex-column">
                <h5 class="module-name"><?= html_escape($module['name']) ?></h5>
                <div class="module-code"><?= html_escape($module['code']) ?></div>
                <?php if ($module['description']): ?>
                    <p class="text-muted small mb-3"><?= html_escape($module['description']) ?></p>
                <?php endif; ?>

                <ul class="module-meta">
                    <li><?= icon('dollar', 16) ?> <?= money($module['fee_amount']) ?></li>
                    <?php if ((int) $module['credits'] > 0): ?><li><?= icon('award', 16) ?> <?= (int) $module['credits'] ?> credit<?= $module['credits'] == 1 ? '' : 's' ?></li><?php endif; ?>
                    <?php if ($module['duration_text']): ?><li><?= icon('clock', 16) ?> <?= html_escape($module['duration_text']) ?></li><?php endif; ?>
                    <?php if (! empty($module['lecturers'])): ?>
                        <li><?= icon('user', 16) ?> <?= html_escape(implode(', ', array_column($module['lecturers'], 'name'))) ?></li>
                    <?php endif; ?>
                </ul>

                <div class="mt-auto">
                    <?php if (! $mine): ?>
                        <form method="post" action="<?= base_url('modules/apply/' . $module['id']) ?>"
                              data-confirm="Apply for <?= html_escape($module['name']) ?>? You'll then upload your proof of payment (<?= money($module['fee_amount']) ?>)."
                              data-confirm-ok="Apply">
                            <button type="submit" class="btn btn-primary w-100">Apply now</button>
                        </form>

                    <?php elseif ($mine['status'] === 'pending_payment'): ?>
                        <?php if ($payment && $payment['status'] === 'pending'): ?>
                            <div class="notice notice-info"><?= icon('clock', 16) ?> Proof sent <?= html_escape(time_ago($payment['submitted_at'])) ?>. Awaiting review.</div>
                        <?php else: ?>
                            <?php if ($payment && $payment['status'] === 'rejected'): ?>
                                <div class="notice notice-danger">
                                    <?= icon('alert', 16) ?>
                                    <span>Last proof was not accepted<?= ! empty($payment['admin_note']) ? ': ' . html_escape($payment['admin_note']) : '.' ?></span>
                                </div>
                            <?php endif; ?>
                            <a href="<?= base_url('payments/upload/' . $mine['enrollment_id']) ?>" class="btn btn-gold w-100">
                                <?= icon('upload', 16) ?> <?= $payment ? 'Upload again' : 'Submit proof of payment' ?>
                            </a>
                        <?php endif; ?>

                    <?php elseif ($mine['status'] === 'active'): ?>
                        <a href="<?= base_url('student_materials/module/' . $module['id']) ?>" class="btn btn-outline-primary w-100">
                            <?= icon('folder', 16) ?> Open module materials
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>
