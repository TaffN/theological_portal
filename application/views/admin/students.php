<?php $resetCount = count(array_filter($students, function ($s) { return ! empty($s['reset_requested_at']); })); ?>
<div class="page-head">
    <div>
        <h1 class="page-title">Students</h1>
        <p class="page-sub"><?= count($students) ?> registered<?= $resetCount ? ' &middot; <strong class="text-body">' . $resetCount . ' waiting for a password reset</strong>' : '' ?></p>
    </div>
    <div class="search-inline">
        <?= icon('search', 16) ?>
        <input type="search" class="form-control" placeholder="Search name, ID, email or phone…" data-table-filter="#students-table">
    </div>
</div>

<?php $this->load->view('admin/_credentials'); ?>

<div class="card">
    <?php if (empty($students)): ?>
        <div class="empty-state"><span class="empty-icon bg-soft-navy"><?= icon('users', 28) ?></span><br>No students have registered yet.</div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-clean align-middle mb-0" id="students-table">
            <thead><tr><th>Student</th><th>Phone</th><th>Courses</th><th>Last seen</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            <?php foreach ($students as $s): ?>
                <tr class="<?= $s['reset_requested_at'] ? 'row-attention' : '' ?>">
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <?= avatar_html($s['name'], $s['id'], $s['photo_path'] ? $s['photo_updated_at'] : null, 'avatar-sm') ?>
                            <div class="min-w-0">
                                <a href="<?= base_url('admin_users/card/' . $s['id']) ?>" class="fw-semibold text-reset text-decoration-none d-block"><?= html_escape($s['name']) ?></a>
                                <small class="text-muted"><span class="id-chip"><?= html_escape($s['id_number']) ?></span> <?= html_escape($s['email']) ?></small>
                            </div>
                        </div>
                    </td>
                    <td class="text-nowrap"><?= $s['phone'] ? html_escape($s['phone']) : '<span class="text-muted">—</span>' ?></td>
                    <td class="text-nowrap">
                        <?= (int) $s['active_courses'] ?> active
                        <?php if ($s['awaiting']): ?><br><small class="text-muted"><?= (int) $s['awaiting'] ?> awaiting payment</small><?php endif; ?>
                    </td>
                    <td class="text-nowrap"><?= $s['last_login_at'] ? html_escape(time_ago($s['last_login_at'])) : '<span class="text-muted">Never</span>' ?></td>
                    <td>
                        <?php if ($s['reset_requested_at']): ?>
                            <span class="pill pill-warning">Reset requested</span>
                        <?php else: ?>
                            <?= $s['status'] === 'active' ? '<span class="pill pill-success">Active</span>' : '<span class="pill pill-danger">Deactivated</span>' ?>
                        <?php endif; ?>
                    </td>
                    <td class="text-end text-nowrap">
                        <form method="post" action="<?= base_url('admin_users/reset_password/' . $s['id']) ?>" class="d-inline"
                              data-confirm="Create a new temporary password for <?= html_escape($s['name']) ?>? Their old password stops working." data-confirm-ok="Reset password">
                            <button type="submit" class="btn btn-sm <?= $s['reset_requested_at'] ? 'btn-gold' : 'btn-outline-primary' ?>">Reset password</button>
                        </form>
                        <form method="post" action="<?= base_url('admin_users/toggle_status/' . $s['id']) ?>" class="d-inline"
                              data-confirm="<?= $s['status'] === 'active' ? 'Deactivate ' . html_escape($s['name']) . '? They won\'t be able to log in.' : 'Re-activate ' . html_escape($s['name']) . '?' ?>"
                              data-confirm-ok="<?= $s['status'] === 'active' ? 'Deactivate' : 'Activate' ?>" <?= $s['status'] === 'active' ? 'data-confirm-danger' : '' ?>>
                            <button type="submit" class="btn btn-sm btn-light"><?= $s['status'] === 'active' ? 'Deactivate' : 'Activate' ?></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div class="empty-state d-none" data-filter-empty>No students match your search.</div>
    </div>
    <?php endif; ?>
</div>
