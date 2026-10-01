<div class="page-head">
    <div>
        <h1 class="page-title">Lecturers</h1>
        <p class="page-sub">Create lecturer accounts, then assign them to modules on the Modules page.</p>
    </div>
</div>

<?php $this->load->view('admin/_credentials'); ?>

<div class="card mb-4">
    <div class="card-body">
        <div class="card-head"><h5 class="card-heading"><span class="stat-icon stat-icon-sm bg-soft-navy"><?= icon('plus', 16) ?></span> Add a lecturer</h5>
            <span class="card-sub">A temporary password is generated for you to send them</span></div>
        <form method="post" action="<?= base_url('admin_users/create_lecturer') ?>" class="row g-2 align-items-end">
            <div class="col-md-4"><label class="form-label">Full name</label><input type="text" name="name" class="form-control" required></div>
            <div class="col-md-4"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
            <div class="col-md-2"><label class="form-label">Phone (WhatsApp)</label><input type="tel" name="phone" class="form-control" placeholder="077…"></div>
            <div class="col-md-2"><button type="submit" class="btn btn-primary w-100">Create</button></div>
        </form>
    </div>
</div>

<div class="card">
    <?php if (empty($lecturers)): ?>
        <div class="empty-state"><span class="empty-icon bg-soft-navy"><?= icon('users', 28) ?></span><br>No lecturers yet. Add your first one above.</div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-clean align-middle mb-0">
            <thead><tr><th>Lecturer</th><th>Phone</th><th>Modules</th><th>Last seen</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            <?php foreach ($lecturers as $l): ?>
                <tr>
                    <td><div class="d-flex align-items-center gap-2"><?= avatar_html($l['name'], $l['id'], $l['photo_path'] ? $l['photo_updated_at'] : null, 'avatar-sm') ?>
                        <div class="min-w-0"><a href="<?= base_url('admin_users/card/' . $l['id']) ?>" class="fw-semibold text-reset text-decoration-none d-block"><?= html_escape($l['name']) ?></a><small class="text-muted"><span class="id-chip"><?= html_escape($l['id_number']) ?></span> <?= html_escape($l['email']) ?></small></div></div></td>
                    <td><?= $l['phone'] ? html_escape($l['phone']) : '<span class="text-muted">—</span>' ?></td>
                    <td><?= (int) $l['module_count'] ?></td>
                    <td class="text-nowrap"><?= $l['last_login_at'] ? html_escape(time_ago($l['last_login_at'])) : '<span class="text-muted">Never</span>' ?></td>
                    <td><?= $l['status'] === 'active' ? '<span class="pill pill-success">Active</span>' : '<span class="pill pill-danger">Deactivated</span>' ?></td>
                    <td class="text-end text-nowrap">
                        <form method="post" action="<?= base_url('admin_users/reset_password/' . $l['id']) ?>" class="d-inline" data-confirm="Create a new temporary password for <?= html_escape($l['name']) ?>?" data-confirm-ok="Reset password">
                            <button type="submit" class="btn btn-sm btn-outline-primary">Reset password</button></form>
                        <form method="post" action="<?= base_url('admin_users/toggle_status/' . $l['id']) ?>" class="d-inline"
                              data-confirm="<?= $l['status'] === 'active' ? 'Deactivate ' . html_escape($l['name']) . '?' : 'Re-activate ' . html_escape($l['name']) . '?' ?>" data-confirm-ok="Confirm" <?= $l['status'] === 'active' ? 'data-confirm-danger' : '' ?>>
                            <button type="submit" class="btn btn-sm btn-light"><?= $l['status'] === 'active' ? 'Deactivate' : 'Activate' ?></button></form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
