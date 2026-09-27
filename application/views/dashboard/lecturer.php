<div class="welcome-banner mb-4">
    <div class="position-relative">
        <p class="welcome-kicker mb-1"><?= ! empty($birthday) ? '🎉 Happy birthday! &middot; ' : '' ?><?= date('l, j F Y') ?></p>
        <h2 class="welcome-title"><?= greeting() ?>, <?= html_escape($first_name) ?></h2>
        <p class="welcome-sub mb-3">Post notes and readings, set assignments and exams, and mark work. Students are notified automatically.</p>
        <a href="<?= base_url('lecturer_materials') ?>" class="btn btn-gold btn-sm"><?= icon('plus', 16) ?> Post material</a>
        <a href="<?= base_url('lecturer_assignments') ?>" class="btn btn-light btn-sm ms-1"><?= icon('edit', 16) ?> Assignments</a>
        <a href="<?= base_url('lecturer_exams') ?>" class="btn btn-light btn-sm ms-1"><?= icon('clock', 16) ?> Exams</a>
    </div>
</div>

<?php $this->load->view('dashboard/_announcements'); ?>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="card stat-card">
            <div class="stat-icon bg-soft-navy"><?= icon('book', 22) ?></div>
            <div>
                <div class="stat-value"><?= count($courses) ?></div>
                <div class="stat-label">My courses</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card stat-card">
            <div class="stat-icon bg-soft-green"><?= icon('users', 22) ?></div>
            <div>
                <div class="stat-value"><?= (int) $student_total ?></div>
                <div class="stat-label">Active students</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <a href="<?= base_url('lecturer_exams') ?>" class="card stat-card stat-link">
            <div class="stat-icon bg-soft-gold"><?= icon('clock', 22) ?></div>
            <div>
                <div class="stat-value"><?= (int) $exam_to_mark ?></div>
                <div class="stat-label">Exam scripts to mark</div>
            </div>
        </a>
    </div>
    <div class="col-6 col-xl-3">
        <a href="<?= base_url('lecturer_assignments') ?>" class="card stat-card stat-link">
            <div class="stat-icon bg-soft-red"><?= icon('edit', 22) ?></div>
            <div>
                <div class="stat-value"><?= (int) $to_mark ?></div>
                <div class="stat-label">Work to mark</div>
            </div>
        </a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-body">
                <div class="card-head">
                    <h5 class="card-heading">Students per course</h5>
                    <span class="card-sub">Paid-up, active students</span>
                </div>
                <?php if (empty($courses)): ?>
                    <div class="empty-state">You haven't been assigned to a course yet. Ask the administrator to assign you.</div>
                <?php else: ?>
                    <?= svg_bar_chart(array_column($courses, 'name'), array_column($courses, 'students'), [
                        'color' => 'var(--c2)',
                        'label' => 'Students per course',
                        'empty' => 'No active students in your courses yet.',
                    ]) ?>
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <?php foreach ($courses as $c): ?>
                            <a href="<?= base_url('lecturer_materials/course/' . $c['id']) ?>" class="btn btn-outline-primary btn-sm">
                                <?= html_escape($c['name']) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-body">
                <div class="card-head">
                    <h5 class="card-heading">Recently posted</h5>
                </div>
                <?php if (empty($materials)): ?>
                    <div class="empty-state">Materials you post will be listed here.</div>
                <?php else: ?>
                    <ul class="people-list">
                    <?php foreach ($materials as $m): ?>
                        <li>
                            <span class="stat-icon stat-icon-sm bg-soft-gold"><?= icon('file', 16) ?></span>
                            <div class="flex-grow-1 min-w-0">
                                <a href="<?= base_url('lecturer_materials/course/' . $m['course_id']) ?>" class="fw-semibold text-truncate d-block text-reset text-decoration-none">
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

<?php $this->load->view('dashboard/_campus'); ?>
