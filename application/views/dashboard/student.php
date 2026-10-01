<div class="welcome-banner mb-4">
    <div class="position-relative">
        <p class="welcome-kicker mb-1"><?= ! empty($birthday) ? '🎉 Happy birthday! &middot; ' : '' ?><?= date('l, j F Y') ?></p>
        <h2 class="welcome-title"><?= greeting() ?>, <?= html_escape($first_name) ?></h2>
        <?php if ($active_count > 0): ?>
            <p class="welcome-sub mb-3">You're enrolled in <?= (int) $active_count ?> module<?= $active_count == 1 ? '' : 's' ?>. Keep going!</p>
            <a href="<?= base_url('student_materials') ?>" class="btn btn-gold btn-sm"><?= icon('folder', 16) ?> Open my materials</a>
        <?php else: ?>
            <p class="welcome-sub mb-3">Start by choosing a module and submitting your proof of payment.</p>
            <a href="<?= base_url('programs') ?>" class="btn btn-gold btn-sm"><?= icon('book', 16) ?> Browse programs</a>
        <?php endif; ?>
    </div>
</div>

<?php $CI =& get_instance(); $CI->load->library('ezra_knowledge'); ?>
<div class="ezra-tip mb-4">
    <span class="ezra-tip-icon" aria-hidden="true"><?= icon('bulb', 22) ?></span>
    <div class="min-w-0">
        <div class="ezra-tip-kicker">Ezra tip of the day</div>
        <div class="ezra-tip-text"><?= ezra_format($CI->ezra_knowledge->tip_of_the_day()) ?></div>
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
                <div class="stat-label">Active modules</div>
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
                    <h5 class="card-heading">My modules</h5>
                    <a href="<?= base_url('programs') ?>" class="card-link-sm">All programs <?= icon('arrow', 14) ?></a>
                </div>
                <?php if (empty($modules)): ?>
                    <div class="empty-state">
                        You haven't applied for a module yet.<br>
                        <a href="<?= base_url('programs') ?>" class="btn btn-primary btn-sm mt-3">Browse programs</a>
                    </div>
                <?php else: ?>
                    <ul class="module-list">
                    <?php foreach ($modules as $c): ?>
                        <li>
                            <div class="module-dot"><?= html_escape(initials($c['name'])) ?></div>
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-semibold text-truncate"><?= html_escape($c['name']) ?></div>
                                <div class="small text-muted text-truncate"><?= html_escape($c['program_name']) ?></div>
                                <?= status_badge($c['enrollment_status']) ?>
                            </div>
                            <?php if ($c['enrollment_status'] === 'active'): ?>
                                <a href="<?= base_url('student_materials/module/' . $c['id']) ?>" class="btn btn-outline-primary btn-sm">Open</a>
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
                                <a href="<?= base_url('student_materials/module/' . $m['module_id']) ?>" class="fw-semibold text-truncate d-block text-reset text-decoration-none">
                                    <?= html_escape($m['title']) ?>
                                </a>
                                <small class="text-muted"><?= html_escape($m['module_name']) ?> &middot; <?= html_escape(time_ago($m['created_at'])) ?></small>
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
        <div class="card h-100">
            <div class="card-body">
                <div class="card-head">
                    <h5 class="card-heading"><?= icon('edit', 18) ?> Assignments due</h5>
                    <a href="<?= base_url('student_assignments') ?>" class="card-link-sm">All assignments <?= icon('arrow', 14) ?></a>
                </div>
                <?php if (empty($due)): ?>
                    <div class="empty-state"><?= $active_count > 0 ? 'Nothing to hand in right now.' : 'Assignments from your lecturers, with due dates, will appear here.' ?></div>
                <?php else: ?>
                    <ul class="due-list">
                    <?php foreach ($due as $d): ?>
                        <li>
                            <div class="due-date <?= $d['state'] === 'overdue' ? 'is-overdue' : '' ?>">
                                <strong><?= date('j', strtotime($d['due_at'])) ?></strong><small><?= date('M', strtotime($d['due_at'])) ?></small>
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <a href="<?= base_url('student_assignments/view/' . $d['id']) ?>" class="fw-semibold text-truncate d-block text-reset text-decoration-none"><?= html_escape($d['title']) ?></a>
                                <small class="text-muted"><?= html_escape($d['module_name']) ?> &middot; <?= $d['state'] === 'overdue' ? 'was due ' : 'due ' ?><?= html_escape(due_in($d['due_at'])) ?></small>
                            </div>
                            <a href="<?= base_url('student_assignments/view/' . $d['id']) ?>" class="btn btn-sm <?= $d['state'] === 'overdue' ? 'btn-outline-danger' : 'btn-outline-primary' ?>">Open</a>
                        </li>
                    <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="card-head">
                    <h5 class="card-heading"><?= icon('clock', 18) ?> Upcoming exams</h5>
                    <a href="<?= base_url('student_exams') ?>" class="card-link-sm">All exams <?= icon('arrow', 14) ?></a>
                </div>
                <?php if (empty($exams)): ?>
                    <div class="empty-state"><?= $active_count > 0 ? 'No exams coming up.' : 'Exam dates and times will appear here, with a button to start when they open.' ?></div>
                <?php else: ?>
                    <ul class="due-list">
                    <?php foreach ($exams as $x): ?>
                        <li>
                            <div class="due-date"><strong><?= date('j', strtotime($x['opens_at'])) ?></strong><small><?= date('M', strtotime($x['opens_at'])) ?></small></div>
                            <div class="flex-grow-1 min-w-0">
                                <a href="<?= base_url('student_exams/view/' . $x['id']) ?>" class="fw-semibold text-truncate d-block text-reset text-decoration-none"><?= html_escape($x['title']) ?></a>
                                <small class="text-muted"><?= html_escape($x['module_name']) ?> &middot;
                                    <?= $x['state'] === 'scheduled' ? 'opens ' . html_escape(due_in($x['opens_at'])) : ($x['state'] === 'writing' ? 'in progress' : 'open until ' . html_escape(date('H:i', strtotime($x['closes_at'])))) ?></small>
                            </div>
                            <?php if ($x['state'] === 'scheduled'): ?>
                                <?= status_badge('exam_scheduled') ?>
                            <?php else: ?>
                                <a href="<?= base_url('student_exams/view/' . $x['id']) ?>" class="btn btn-sm btn-primary"><?= $x['state'] === 'writing' ? 'Continue' : 'Start' ?></a>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php $this->load->view('dashboard/_campus'); ?>
