<?php $back = $u['role'] === 'lecturer' ? 'admin_users/lecturers' : 'admin_users/students'; ?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 no-print">
    <a href="<?= base_url($back) ?>" class="back-link">&larr; <?= $u['role'] === 'lecturer' ? 'Lecturers' : 'Students' ?></a>
    <div class="d-flex gap-2">
        <form method="post" action="<?= base_url('admin_users/reissue_card/' . (int) $u['id']) ?>"
              data-confirm="Issue a new ID card for <?= html_escape($u['name']) ?>? The QR code on their current card will stop working (use this if a card is lost or stolen)." data-confirm-ok="Issue new card">
            <button type="submit" class="btn btn-light"><?= icon('id-card', 16) ?> Re-issue card</button>
        </form>
        <button type="button" class="btn btn-primary" data-print-card><?= icon('file', 16) ?> Print ID card</button>
    </div>
</div>

<div class="card profile-hero mb-4">
    <div class="profile-cover"></div>
    <div class="card-body pt-0">
        <div class="profile-id">
            <?= avatar_html($u['name'], $u['id'], $u['photo_path'] ? $u['photo_updated_at'] : null, 'avatar-xl') ?>
            <div class="min-w-0 pb-1">
                <h1 class="h4 fw-bold mb-1 text-truncate"><?= html_escape(trim(($profile['title'] ? $profile['title'] . ' ' : '') . $u['name'])) ?></h1>
                <div class="text-muted small"><strong class="text-body"><?= html_escape($u['id_number']) ?></strong> &middot; <?= html_escape($u['email']) ?></div>
            </div>
            <div class="ms-md-auto mb-1 d-flex align-items-center gap-3 flex-wrap">
                <span class="completeness" title="<?= $complete['missing'] ? 'Missing: ' . html_escape(implode(', ', $complete['missing'])) : 'All done' ?>">
                    <span class="ring ring-sm" style="--p: <?= (int) $complete['percent'] ?>"><span><?= (int) $complete['percent'] ?>%</span></span>
                    <span class="min-w-0"><strong class="d-block">Record <?= (int) $complete['percent'] ?>% complete</strong>
                    <small class="text-muted"><?= $complete['missing'] ? count($complete['missing']) . ' item' . (count($complete['missing']) == 1 ? '' : 's') . ' missing' : 'Nothing missing' ?></small></span>
                </span>
                <?= $u['status'] === 'active' ? '<span class="pill pill-success">Active</span>' : '<span class="pill pill-danger">Deactivated</span>' ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="card-head"><h5 class="card-heading"><span class="stat-icon stat-icon-sm bg-soft-gold"><?= icon('id-card', 16) ?></span> ID card</h5>
                    <span class="card-sub">Tap the card to see the back</span></div>
                <?php $this->load->view('partials/id_card'); ?>
            </div>
        </div>
    </div>
    <div class="col-xl-6 no-print">
        <div class="card mb-3">
            <div class="card-body">
                <div class="card-head"><h5 class="card-heading"><span class="stat-icon stat-icon-sm bg-soft-blue"><?= icon('camera', 16) ?></span> <?= $u['photo_path'] ? 'Replace photo' : 'Add a photo' ?></h5></div>
                <form method="post" action="<?= base_url('photo/upload_for/' . (int) $u['id']) ?>" enctype="multipart/form-data" data-photo-form>
                    <label class="photo-picker">
                        <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" required data-photo-input>
                        <span class="photo-picker-preview"><?= avatar_html($u['name'], $u['id'], $u['photo_path'] ? $u['photo_updated_at'] : null, 'avatar-xl') ?></span>
                        <span><strong>Choose a clear, front-facing photo</strong><small class="d-block text-muted">e.g. from their enrolment form. Cropped automatically.</small></span>
                    </label>
                    <button type="submit" class="btn btn-primary mt-3" data-photo-save disabled>Save photo</button>
                </form>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <div class="card-head"><h5 class="card-heading"><span class="stat-icon stat-icon-sm bg-soft-navy"><?= icon('user', 16) ?></span> Account</h5>
                    <span class="card-sub">Last seen <?= $u['last_login_at'] ? html_escape(time_ago($u['last_login_at'])) : 'never' ?></span></div>
                <form method="post" action="<?= base_url('admin_users/update_account/' . (int) $u['id']) ?>" class="row g-2">
                    <div class="col-12"><label class="form-label">Full name</label><input name="name" class="form-control" value="<?= html_escape($u['name']) ?>" required></div>
                    <div class="col-md-7"><label class="form-label">Email (their login)</label><input type="email" name="email" class="form-control" value="<?= html_escape($u['email']) ?>" required></div>
                    <div class="col-md-5"><label class="form-label">Phone (WhatsApp)</label><input type="tel" name="phone" class="form-control" value="<?= html_escape($u['phone']) ?>"></div>
                    <div class="col-12 d-flex flex-wrap gap-2 align-items-center mt-2">
                        <button class="btn btn-primary">Save account</button>
                        <?php if ($u['phone']): ?>
                            <a class="btn btn-whatsapp" target="_blank" rel="noopener" href="<?= wa_link($u['phone']) ?>">WhatsApp</a>
                            <a class="btn btn-light" href="<?= tel_link($u['phone']) ?>">Call</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card no-print" id="details">
    <div class="card-body">
        <div class="card-head">
            <h5 class="card-heading"><span class="stat-icon stat-icon-sm bg-soft-green"><?= icon('user', 16) ?></span> Personal details</h5>
            <span class="card-sub"><?= ! empty($profile['privacy_consent_at']) ? 'Agreed to the privacy notice ' . html_escape(date('j M Y', strtotime($profile['privacy_consent_at']))) : 'Registered before consent was recorded' ?></span>
        </div>
        <?php $this->load->view('profile/_about_form', [
            'formRole'    => $u['role'],
            'formProfile' => $profile,
            'formAction'  => base_url('admin_users/save_profile/' . (int) $u['id']),
            'formNote'    => 'Every change here is recorded in the audit trail.',
        ]); ?>
    </div>
</div>
