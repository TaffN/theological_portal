<div class="page-head">
    <div>
        <h1 class="page-title">Courses</h1>
        <p class="page-sub">Apply, pay by EcoCash or bank transfer, and your course opens once the payment is approved.</p>
    </div>
</div>

<?php if (empty($courses)): ?>
    <div class="card"><div class="empty-state"><?= icon('book', 32) ?><br>No courses are open for applications right now.</div></div>
<?php endif; ?>

<div class="row g-3">
<?php foreach ($courses as $i => $course): ?>
    <?php
        $mine    = $statusByCourseId[$course['id']] ?? null;
        $payment = ($mine && isset($latestPayments[(int) $mine['enrollment_id']])) ? $latestPayments[(int) $mine['enrollment_id']] : null;
    ?>
    <div class="col-md-6 col-xl-4">
        <div class="card course-card h-100">
            <div class="course-band band-<?= $i % 4 ?>">
                <span class="course-initials"><?= html_escape(initials($course['name'])) ?></span>
                <?php if ($mine): ?><?= status_badge($mine['status']) ?><?php endif; ?>
            </div>
            <div class="card-body d-flex flex-column">
                <h5 class="course-name"><?= html_escape($course['name']) ?></h5>
                <?php if ($course['description']): ?>
                    <p class="text-muted small mb-3"><?= html_escape($course['description']) ?></p>
                <?php endif; ?>

                <ul class="course-meta">
                    <li><?= icon('dollar', 16) ?> <?= money($course['fee_amount']) ?></li>
                    <?php if ($course['duration_text']): ?><li><?= icon('clock', 16) ?> <?= html_escape($course['duration_text']) ?></li><?php endif; ?>
                    <?php if (! empty($course['lecturers'])): ?>
                        <li><?= icon('user', 16) ?> <?= html_escape(implode(', ', array_column($course['lecturers'], 'name'))) ?></li>
                    <?php endif; ?>
                </ul>

                <div class="mt-auto">
                    <?php if (! $mine): ?>
                        <a href="<?= base_url('courses/apply/' . $course['id']) ?>" class="btn btn-primary w-100"
                           data-confirm="Apply for <?= html_escape($course['name']) ?>? You'll then upload your proof of payment (<?= money($course['fee_amount']) ?>)."
                           data-confirm-ok="Apply">Apply now</a>

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
                        <a href="<?= base_url('student_materials/course/' . $course['id']) ?>" class="btn btn-outline-primary w-100">
                            <?= icon('folder', 16) ?> Open course materials
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>
