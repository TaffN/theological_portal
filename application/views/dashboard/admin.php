<div class="welcome-banner mb-4">
    <div class="position-relative">
        <p class="welcome-kicker mb-1"><?= ! empty($birthday) ? '🎉 Happy birthday! &middot; ' : '' ?><?= date('l, j F Y') ?></p>
        <h2 class="welcome-title"><?= greeting() ?>, <?= html_escape($first_name) ?></h2>
        <p class="welcome-sub mb-3">Here's what's happening at <?= html_escape(setting('org_short_name', 'the Center')) ?> today.</p>
        <div class="d-flex flex-wrap gap-2">
            <?php if ($stats['pending_payments'] > 0): ?>
                <a href="<?= base_url('admin_payments') ?>" class="btn btn-gold btn-sm">
                    <?= icon('card', 16) ?> Review <?= (int) $stats['pending_payments'] ?> pending payment<?= $stats['pending_payments'] == 1 ? '' : 's' ?>
                </a>
            <?php endif; ?>
            <a href="<?= base_url('admin_modules') ?>" class="btn btn-glass btn-sm"><?= icon('plus', 16) ?> Add module</a>
            <a href="<?= base_url('admin_users/lecturers') ?>" class="btn btn-glass btn-sm"><?= icon('users', 16) ?> Add lecturer</a>
        </div>
    </div>
</div>

<?php $this->load->view('dashboard/_announcements'); ?>
<?php $this->load->view('dashboard/_checklist', ['heading' => 'Set up your portal']); ?>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="card stat-card">
            <div class="stat-icon bg-soft-navy"><?= icon('users', 22) ?></div>
            <div>
                <div class="stat-value"><?= (int) $stats['students'] ?></div>
                <div class="stat-label">Students</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card stat-card">
            <div class="stat-icon bg-soft-green"><?= icon('check', 22) ?></div>
            <div>
                <div class="stat-value"><?= (int) $stats['active_enrollments'] ?></div>
                <div class="stat-label">Active enrollments</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <a href="<?= base_url('admin_payments') ?>" class="card stat-card stat-link">
            <div class="stat-icon bg-soft-gold"><?= icon('clock', 22) ?></div>
            <div>
                <div class="stat-value"><?= (int) $stats['pending_payments'] ?></div>
                <div class="stat-label">Payments to review</div>
            </div>
        </a>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card stat-card">
            <div class="stat-icon bg-soft-blue"><?= icon('dollar', 22) ?></div>
            <div>
                <div class="stat-value"><?= money($stats['fees_total']) ?></div>
                <div class="stat-label">Fees collected &middot; <?= money($stats['fees_month']) ?> this month</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-body">
                <div class="card-head">
                    <h5 class="card-heading">Fees collected</h5>
                    <span class="card-sub">Approved payments, last 6 months</span>
                </div>
                <?= svg_bar_chart($fees['labels'], $fees['values'], [
                    'prefix' => '$',
                    'label'  => 'Fees collected per month',
                    'empty'  => 'No approved payments in the last 6 months yet.',
                ]) ?>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-body">
                <div class="card-head">
                    <h5 class="card-heading">Enrollments by module</h5>
                    <span class="card-sub">Paid-up students per module</span>
                </div>
                <?= svg_donut_chart($by_module['labels'], $by_module['values'], [
                    'center_label' => 'students',
                    'empty'        => 'No paid enrollments yet.',
                ]) ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-body">
                <div class="card-head">
                    <h5 class="card-heading">Recent payments</h5>
                    <a href="<?= base_url('admin_payments') ?>" class="card-link-sm">Review pending <?= icon('arrow', 14) ?></a>
                </div>
                <?php if (empty($payments)): ?>
                    <div class="empty-state"><span class="empty-icon bg-soft-blue"><?= icon('card', 24) ?></span><br>No payments submitted yet.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-clean align-middle mb-0">
                            <thead><tr><th>Student</th><th>Module</th><th>Amount</th><th>Status</th></tr></thead>
                            <tbody>
                            <?php foreach ($payments as $p): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?= html_escape($p['student_name']) ?></div>
                                        <small class="text-muted"><?= html_escape(time_ago($p['submitted_at'])) ?></small>
                                    </td>
                                    <td><?= html_escape($p['module_name']) ?></td>
                                    <td><?= money($p['amount']) ?></td>
                                    <td><?= status_badge($p['status']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card mb-3">
            <div class="card-body">
                <div class="card-head">
                    <h5 class="card-heading">Enrollment pipeline</h5>
                </div>
                <?php
                    $pipeline = [
                        'pending_payment' => ['Awaiting payment', 'var(--tc-gold)'],
                        'active'          => ['Active', 'var(--tc-green)'],
                        'completed'       => ['Completed', 'var(--tc-navy)'],
                        'suspended'       => ['Suspended', 'var(--tc-red)'],
                    ];
                    $pipeTotal = max(1, array_sum($status_counts));
                ?>
                <?php foreach ($pipeline as $key => $meta): ?>
                    <div class="pipe-row">
                        <div class="d-flex justify-content-between small mb-1">
                            <span><?= $meta[0] ?></span>
                            <strong><?= (int) $status_counts[$key] ?></strong>
                        </div>
                        <div class="pipe-track">
                            <div class="pipe-fill" style="width: <?= round($status_counts[$key] / $pipeTotal * 100) ?>%; background: <?= $meta[1] ?>;"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="card-head">
                    <h5 class="card-heading">Newest students</h5>
                    <span class="card-sub"><?= (int) $stats['programs'] ?> program<?= $stats['programs'] == 1 ? '' : 's' ?> &middot; <?= (int) $stats['modules'] ?> modules &middot; <?= (int) $stats['lecturers'] ?> lecturers</span>
                </div>
                <?php if (empty($new_students)): ?>
                    <div class="empty-state">No students have registered yet.</div>
                <?php else: ?>
                    <ul class="people-list">
                    <?php foreach ($new_students as $s): ?>
                        <li>
                            <span class="avatar avatar-sm"><?= html_escape(initials($s['name'])) ?></span>
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-semibold text-truncate"><?= html_escape($s['name']) ?></div>
                                <small class="text-muted text-truncate d-block"><?= html_escape($s['email']) ?></small>
                            </div>
                            <small class="text-muted"><?= html_escape(time_ago($s['created_at'])) ?></small>
                        </li>
                    <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
    $actIcons = ['auth' => ['user', 'bg-soft-navy'], 'payment' => ['card', 'bg-soft-green'], 'module' => ['book', 'bg-soft-blue'],
                 'material' => ['file', 'bg-soft-gold'], 'assignment' => ['edit', 'bg-soft-gold'], 'submission' => ['upload', 'bg-soft-blue'], 'exam' => ['clock', 'bg-soft-gold'], 'attempt' => ['clock', 'bg-soft-blue'], 'result' => ['award', 'bg-soft-green'], 'grading' => ['layers', 'bg-soft-gold'], 'ezra' => ['message', 'bg-soft-blue'], 'discussion' => ['chat', 'bg-soft-blue'], 'calendar' => ['calendar', 'bg-soft-gold'], 'library' => ['library', 'bg-soft-navy'], 'attendance' => ['check-square', 'bg-soft-green'], 'document' => ['file', 'bg-soft-blue'], 'report' => ['chart', 'bg-soft-navy'], 'user' => ['users', 'bg-soft-navy'], 'enrollment' => ['book', 'bg-soft-green'],
                 'announcement' => ['bell', 'bg-soft-gold'], 'support' => ['alert', 'bg-soft-red'], 'error' => ['alert', 'bg-soft-red']];
?>
<div class="row g-3 mt-1">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-body">
                <div class="card-head">
                    <h5 class="card-heading">Recent activity</h5>
                    <a href="<?= base_url('admin_audit') ?>" class="card-link-sm">Full audit trail <?= icon('arrow', 14) ?></a>
                </div>
                <?php if (empty($activity)): ?>
                    <div class="empty-state">Activity will appear here as people use the portal.</div>
                <?php else: ?>
                    <ul class="people-list">
                    <?php foreach ($activity as $a): ?>
                        <?php
                            $g = strtok($a['action'], '.');
                            $bad = strpos($a['action'], 'failed') !== false || strpos($a['action'], 'locked') !== false;
                            list($ic, $bg) = $bad ? ['alert', 'bg-soft-red'] : (isset($actIcons[$g]) ? $actIcons[$g] : ['grid', 'bg-soft-navy']);
                        ?>
                        <li>
                            <span class="stat-icon stat-icon-sm <?= $bg ?>"><?= icon($ic, 15) ?></span>
                            <div class="flex-grow-1 min-w-0">
                                <div class="text-truncate"><?= html_escape($a['description']) ?></div>
                                <small class="text-muted"><?= $a['user_name'] ? html_escape($a['user_name']) : 'Guest' ?> &middot; <?= html_escape(time_ago($a['created_at'])) ?></small>
                            </div>
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
                    <h5 class="card-heading">System health</h5>
                    <?= $errors['critical_open'] > 0 ? '<span class="pill pill-danger">Needs attention</span>' : '<span class="pill pill-success">All good</span>' ?>
                </div>
                <div class="health-grid">
                    <a href="<?= base_url('admin_errors') ?>" class="health-tile">
                        <span class="health-value <?= $errors['open'] ? 'text-danger-soft' : '' ?>"><?= (int) $errors['open'] ?></span>
                        <span class="health-label">Open errors<?= $errors['critical_open'] ? ' &middot; ' . (int) $errors['critical_open'] . ' serious' : '' ?></span>
                    </a>
                    <a href="<?= base_url('admin_users/students') ?>" class="health-tile">
                        <span class="health-value <?= $resets ? 'text-warning-soft' : '' ?>"><?= (int) $resets ?></span>
                        <span class="health-label">Password reset requests</span>
                    </a>
                    <a href="<?= base_url('admin_audit') . '?group=auth&from=' . date('Y-m-d') ?>" class="health-tile">
                        <span class="health-value"><?= (int) $logins_today ?></span>
                        <span class="health-label">People logged in today</span>
                    </a>
                    <a href="<?= base_url('admin_audit') . '?group=auth&from=' . date('Y-m-d') ?>" class="health-tile">
                        <span class="health-value <?= $failed_today >= 10 ? 'text-danger-soft' : '' ?>"><?= (int) $failed_today ?></span>
                        <span class="health-label">Failed login attempts today</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
