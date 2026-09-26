<div class="page-head">
    <div>
        <h1 class="page-title">Administrators</h1>
        <p class="page-sub">People who can approve payments and manage the whole portal. Keep at least two, so one can always help the other get back in.</p>
    </div>
</div>

<?php $this->load->view('admin/_credentials'); ?>

<?php if (count($admins) < 2): ?>
    <div class="notice notice-info mb-4"><?= icon('alert', 16) ?> <span>You're the only administrator. If you forget your password, nobody can reset it for you. Add a second administrator below.</span></div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-body">
        <div class="card-head"><h5 class="card-heading"><span class="stat-icon stat-icon-sm bg-soft-navy"><?= icon('plus', 16) ?></span> Add an administrator</h5>
            <span class="card-sub">A temporary password is generated for you to send them</span></div>
        <form method="post" action="<?= base_url('admin_users/create_admin') ?>" class="row g-2 align-items-end"
              data-confirm="Administrators can see every student's details and approve payments. Create this account?" data-confirm-ok="Create administrator">
            <div class="col-md-4"><label class="form-label">Full name</label><input type="text" name="name" class="form-control" required></div>
            <div class="col-md-4"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
            <div class="col-md-2"><label class="form-label">Phone (WhatsApp)</label><input type="tel" name="phone" class="form-control" placeholder="077…"></div>
            <div class="col-md-2"><button type="submit" class="btn btn-primary w-100">Create</button></div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-clean align-middle mb-0">
            <thead><tr><th>Administrator</th><th>Phone</th><th>Last seen</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            <?php foreach ($admins as $a): ?>
                <?php $isMe = (int) $a['id'] === $me; ?>
                <tr>
                    <td><div class="d-flex align-items-center gap-2"><?= avatar_html($a['name'], $a['id'], $a['photo_path'] ? $a['photo_updated_at'] : null, 'avatar-sm') ?>
                        <div class="min-w-0"><span class="fw-semibold d-block"><?= html_escape($a['name']) ?><?= $isMe ? ' <span class="pill pill-muted">You</span>' : '' ?></span>
                            <small class="text-muted"><span class="id-chip"><?= html_escape($a['id_number']) ?></span> <?= html_escape($a['email']) ?></small></div></div></td>
                    <td><?= $a['phone'] ? html_escape($a['phone']) : '<span class="text-muted">—</span>' ?></td>
                    <td class="text-nowrap"><?= $a['last_login_at'] ? html_escape(time_ago($a['last_login_at'])) : '<span class="text-muted">Never</span>' ?></td>
                    <td><?= $a['status'] === 'active' ? '<span class="pill pill-success">Active</span>' : '<span class="pill pill-danger">Deactivated</span>' ?></td>
                    <td class="text-end text-nowrap">
                        <button type="button" class="btn btn-sm btn-light" data-bs-toggle="collapse" data-bs-target="#edit-admin-<?= (int) $a['id'] ?>">Edit</button>
                        <?php if ($isMe): ?>
                            <a href="<?= base_url('profile') ?>#password" class="btn btn-sm btn-outline-primary">Change my password</a>
                        <?php else: ?>
                            <form method="post" action="<?= base_url('admin_users/reset_admin_password/' . $a['id']) ?>" class="d-inline" data-confirm="Create a new temporary password for <?= html_escape($a['name']) ?>?" data-confirm-ok="Reset password">
                                <button type="submit" class="btn btn-sm btn-outline-primary">Reset password</button></form>
                            <form method="post" action="<?= base_url('admin_users/toggle_admin_status/' . $a['id']) ?>" class="d-inline"
                                  data-confirm="<?= $a['status'] === 'active' ? 'Deactivate ' . html_escape($a['name']) . '? They will no longer be able to log in.' : 'Re-activate ' . html_escape($a['name']) . '?' ?>" data-confirm-ok="Confirm" <?= $a['status'] === 'active' ? 'data-confirm-danger' : '' ?>>
                                <button type="submit" class="btn btn-sm btn-light"><?= $a['status'] === 'active' ? 'Deactivate' : 'Activate' ?></button></form>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr class="collapse" id="edit-admin-<?= (int) $a['id'] ?>">
                    <td colspan="5">
                        <form method="post" action="<?= base_url('admin_users/update_admin/' . $a['id']) ?>" class="row g-2 align-items-end py-1">
                            <div class="col-md-4"><label class="form-label">Full name</label><input type="text" name="name" class="form-control" value="<?= html_escape($a['name']) ?>" required></div>
                            <div class="col-md-4"><label class="form-label">Email (used to log in)</label><input type="email" name="email" class="form-control" value="<?= html_escape($a['email']) ?>" required></div>
                            <div class="col-md-2"><label class="form-label">Phone</label><input type="tel" name="phone" class="form-control" value="<?= html_escape($a['phone']) ?>"></div>
                            <div class="col-md-2"><button type="submit" class="btn btn-primary w-100">Save</button></div>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
