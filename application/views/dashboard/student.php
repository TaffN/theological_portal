<div class="welcome-banner mb-4">
    <div class="position-relative">
        <p class="welcome-kicker mb-1"><?= date('l, j F Y') ?></p>
        <h2 class="welcome-title"><?= greeting() ?>, <?= html_escape($first_name) ?></h2>
        <?php if ($active_count > 0): ?>
            <p class="welcome-sub mb-3">You're enrolled in <?= (int) $active_count ?> course<?= $active_count == 1 ? '' : 's' ?>. Keep going!</p>
            <a href="<?= base_url('student_materials') ?>" class="btn btn-gold btn-sm"><?= icon('folder', 16) ?> Open my materials</a>
        <?php else: ?>
            <p class="welcome-sub mb-3">Start by choosing a course and submitting your proof of payment.</p>
            <a href="<?= base_url('courses') ?>" class="btn btn-gold btn-sm"><?= icon('book', 16) ?> Browse courses</a>
        <?php endif; ?>
    </div>
</div>

<?php $this->load->view('dashboard/_announcements'); ?>
<?php $this->load->view('dashboard/_checklist', ['heading' => 'Getting started']); ?>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="card stat-card">
            <div class="stat-icon bg-soft-navy"><?= icon('book', 22) ?></div>
            <div>
                <div class="stat-value"><?= (int) $active_count ?></div>
                <div class="stat-label">Active courses</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card stat-card">
            <div class="stat-icon bg-soft-gold"><?= icon('card', 22) ?></div>
            <div>
                <div class="stat-value"><?= (int) $awaiting_count ?></div>
                <div class="stat-label">Awaiting payment</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <a href="<?= base_url('student_materials') ?>" class="card stat-card stat-link">
            <div class="stat-icon bg-soft-green"><?= icon('file', 22) ?></div>
            <div>
                <div class="stat-value"><?= (int) $new_materials ?></div>
                <div class="stat-label">New materials this week</div>
            </div>
        </a>
    </div>
    <div class="col-6 col-xl-3">
        <a href="<?= base_url('notifications') ?>" class="card stat-card stat-link">
            <div class="stat-icon bg-soft-red"><?= icon('bell', 22) ?></div>
            <div>
                <div class="stat-value"><?= (int) $unread ?></div>
                <div class="stat-label">Unread alerts</div>
            </div>
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-body">
                <div class="card-head">
                    <h5 class="card-heading">My courses</h5>
                    <a href="<?= base_url('courses') ?>" class="card-link-sm">All courses <?= icon('arrow', 14) ?></a>
                </div>
                <?php if (empty($courses)): ?>
                    <div class="empty-state">
                        You haven't applied for a course yet.<br>
                        <a href="<?= base_url('courses') ?>" class="btn btn-primary btn-sm mt-3">Browse courses</a>
                    </div>
                <?php else: ?>
                    <ul class="course-list">
                    <?php foreach ($courses as $c): ?>
                        <li>
                            <div class="course-dot"><?= html_escape(initials($c['name'])) ?></div>
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-semibold text-truncate"><?= html_escape($c['name']) ?></div>
                                <?= status_badge($c['enrollment_status']) ?>
                            </div>
                            <?php if ($c['enrollment_status'] === 'active'): ?>
                                <a href="<?= base_url('student_materials/course/' . $c['id']) ?>" class="btn btn-outline-primary btn-sm">Open</a>
                            <?php elseif ($c['enrollment_status'] === 'pending_payment'): ?>
                                <?php if (in_array((int) $c['enrollment_id'], $proof_pending, true)): ?>
                                    <span class="small text-muted text-end">Proof sent,<br>awaiting review</span>
                                <?php else: ?>
                                    <a href="<?= base_url('payments/upload/' . $c['enrollment_id']) ?>" class="btn btn-gold btn-sm">Pay</a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-body">
                <div class="card-head">
                    <h5 class="card-heading">Latest materials</h5>
                </div>
                <?php if (empty($materials)): ?>
                    <div class="empty-state">New notes, outlines and readings from your lecturers will show up here.</div>
                <?php else: ?>
                    <ul class="people-list">
                    <?php foreach ($materials as $m): ?>
                        <li>
                            <span class="stat-icon stat-icon-sm bg-soft-green"><?= icon('file', 16) ?></span>
                            <div class="flex-grow-1 min-w-0">
                                <a href="<?= base_url('student_materials/course/' . $m['course_id']) ?>" class="fw-semibold text-truncate d-block text-reset text-decoration-none">
                                    <?= html_escape($m['title']) ?>
                                </a>
                                <small class="text-muted"><?= html_escape($m['course_name']) ?> &middot; <?= html_escape(time_ago($m['created_at'])) ?></small>
                            </div>
                        </li>
                    <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card h-100 card-soon">
            <div class="card-body">
                <div class="card-head">
                    <h5 class="card-heading"><?= icon('edit', 18) ?> Assignments due</h5>
                    <span class="pill pill-muted">Coming soon</span>
                </div>
                <div class="empty-state">Assignments from your lecturers, with due dates, will appear here.</div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100 card-soon">
            <div class="card-body">
                <div class="card-head">
                    <h5 class="card-heading"><?= icon('clock', 18) ?> Upcoming exams</h5>
                    <span class="pill pill-muted">Coming soon</span>
                </div>
                <div class="empty-state">Exam dates and times will appear here, with a button to start when they open.</div>
            </div>
        </div>
    </div>
</div>
