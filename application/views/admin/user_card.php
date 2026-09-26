<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <a href="<?= base_url($u['role'] === 'lecturer' ? 'admin_users/lecturers' : 'admin_users/students') ?>" class="back-link">&larr; <?= $u['role'] === 'lecturer' ? 'Lecturers' : 'Students' ?></a>
    <button type="button" class="btn btn-primary" data-print-card><?= icon('file', 16) ?> Print ID card</button>
</div>

<div class="row g-4 align-items-start">
    <div class="col-lg-6 print-only-card">
        <?php $this->load->view('partials/id_card'); ?>
    </div>
    <div class="col-lg-6 no-print">
        <div class="card mb-3">
            <div class="card-body">
                <div class="card-head"><h5 class="card-heading"><span class="stat-icon stat-icon-sm bg-soft-navy"><?= icon('camera', 16) ?></span> <?= $u['photo_path'] ? 'Replace photo' : 'Add a photo' ?></h5></div>
                <p class="text-muted small">Use a clear, front-facing photo, e.g. from their enrollment form. It's cropped to a square automatically.</p>
                <form method="post" action="<?= base_url('photo/upload_for/' . (int) $u['id']) ?>" enctype="multipart/form-data" data-photo-form>
                    <label class="photo-picker">
                        <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" required data-photo-input>
                        <span class="photo-picker-preview"><?= avatar_html($u['name'], $u['id'], $u['photo_path'] ? $u['photo_updated_at'] : null, 'avatar-xl') ?></span>
                        <span><strong>Choose photo</strong><small class="d-block text-muted">JPG, PNG or WebP</small></span>
                    </label>
                    <button type="submit" class="btn btn-primary mt-3" data-photo-save disabled>Save photo</button>
                </form>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <dl class="facts">
                    <dt>Email</dt><dd class="text-break"><?= html_escape($u['email']) ?></dd>
                    <dt>Phone</dt><dd><?= $u['phone'] ? html_escape($u['phone']) : '<span class="text-muted">—</span>' ?></dd>
                    <dt>Last seen</dt><dd><?= $u['last_login_at'] ? html_escape(time_ago($u['last_login_at'])) : '<span class="text-muted">Never</span>' ?></dd>
                    <dt>Status</dt><dd><?= $u['status'] === 'active' ? '<span class="pill pill-success">Active</span>' : '<span class="pill pill-danger">Deactivated</span>' ?></dd>
                </dl>
            </div>
        </div>
    </div>
</div>
