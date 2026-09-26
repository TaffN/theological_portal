<div class="card profile-hero mb-4">
    <div class="profile-cover"></div>
    <div class="card-body pt-0">
        <div class="profile-id">
            <a href="#photo" class="profile-photo-link" title="Change photo">
                <?= avatar_html($user['name'], $user['id'], $user['photo_path'] ? $user['photo_updated_at'] : null, 'avatar-xl') ?>
                <span class="photo-badge"><?= icon('camera', 14) ?></span>
            </a>
            <div class="min-w-0 pb-1">
                <h1 class="h4 fw-bold mb-1 text-truncate"><?= html_escape($user['name']) ?></h1>
                <div class="text-muted small"><?= html_escape($user['email']) ?><?= $user['id_number'] ? ' &middot; <strong class="text-body">' . html_escape($user['id_number']) . '</strong>' : '' ?></div>
            </div>
            <div class="ms-md-auto mb-1 d-flex align-items-center gap-3 flex-wrap">
                <a href="#details" class="completeness" title="<?= $complete['missing'] ? 'Missing: ' . html_escape(implode(', ', $complete['missing'])) : 'All done' ?>">
                    <span class="ring ring-sm" style="--p: <?= (int) $complete['percent'] ?>"><span><?= (int) $complete['percent'] ?>%</span></span>
                    <span class="min-w-0"><strong class="d-block"><?= $complete['percent'] >= 100 ? 'Profile complete' : 'Profile ' . (int) $complete['percent'] . '% complete' ?></strong>
                    <small class="text-muted"><?= $complete['missing'] ? 'Next: ' . html_escape($complete['missing'][0]) : 'Thank you!' ?></small></span>
                </a>
                <span class="pill pill-muted"><?= html_escape(ucfirst($user['role'])) ?> &middot; since <?= html_escape(date('M Y', strtotime($user['created_at']))) ?></span>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-5" id="photo">
        <div class="card h-100">
            <div class="card-body">
                <div class="card-head"><h5 class="card-heading"><span class="stat-icon stat-icon-sm bg-soft-blue"><?= icon('camera', 16) ?></span> Profile photo</h5></div>
                <p class="text-muted small">Used on your ID card so the Center can recognise you. Use a clear, front-facing photo, like a passport photo.</p>
                <form method="post" action="<?= base_url('photo/upload') ?>" enctype="multipart/form-data" data-photo-form>
                    <label class="photo-picker">
                        <input type="file" name="photo" accept="image/*" capture="user" required data-photo-input>
                        <span class="photo-picker-preview"><?= avatar_html($user['name'], $user['id'], $user['photo_path'] ? $user['photo_updated_at'] : null, 'avatar-xl') ?></span>
                        <span><strong><?= $user['photo_path'] ? 'Take or choose a new photo' : 'Take or choose a photo' ?></strong><small class="d-block text-muted">Opens your camera on a phone</small></span>
                    </label>
                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn btn-primary" data-photo-save disabled>Save photo</button>
                    </div>
                </form>
                <?php if ($user['photo_path']): ?>
                    <form method="post" action="<?= base_url('photo/remove') ?>" class="mt-2" data-confirm="Remove your profile photo?" data-confirm-ok="Remove" data-confirm-danger>
                        <button type="submit" class="btn btn-link btn-sm text-danger p-0">Remove photo</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-body">
                <div class="card-head">
                    <h5 class="card-heading"><span class="stat-icon stat-icon-sm bg-soft-gold"><?= icon('id-card', 16) ?></span> My ID card</h5>
                    <button type="button" class="btn btn-sm btn-outline-primary no-print" data-print-card>Print</button>
                </div>
                <div class="print-only-card"><?php $this->load->view('partials/id_card'); ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3" id="details">
    <div class="card-body">
        <div class="card-head">
            <h5 class="card-heading"><span class="stat-icon stat-icon-sm bg-soft-green"><?= icon('user', 16) ?></span> About you</h5>
            <span class="card-sub">For enrolment records, your ID card and emergencies</span>
        </div>
        <?php $this->load->view('profile/_about_form', [
            'formRole'    => $user['role'],
            'formProfile' => $profile,
            'formAction'  => base_url('profile/save_more'),
            'formNote'    => 'Only the administrators can see these details.',
        ]); ?>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="card-head"><h5 class="card-heading"><span class="stat-icon stat-icon-sm bg-soft-navy"><?= icon('user', 16) ?></span> Account details</h5></div>
                <form method="post" action="<?= base_url('profile/update_details') ?>">
                    <div class="mb-3">
                        <label class="form-label" for="p-name">Full name</label>
                        <input type="text" id="p-name" name="name" class="form-control" value="<?= html_escape($user['name']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" value="<?= html_escape($user['email']) ?>" disabled>
                        <div class="form-text">Your email is your login. Ask the administrator if it needs changing.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="p-phone">Phone (WhatsApp)</label>
                        <input type="tel" id="p-phone" name="phone" class="form-control" value="<?= html_escape($user['phone']) ?>" placeholder="+263 7...">
                    </div>
                    <button type="submit" class="btn btn-primary">Save details</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card mb-3">
            <div class="card-body">
                <div class="card-head">
                    <h5 class="card-heading"><span class="stat-icon stat-icon-sm bg-soft-gold"><?= icon('moon', 16) ?></span> Appearance</h5>
                    <span class="card-sub">Saved on this device</span>
                </div>
                <div class="theme-cards" role="group" aria-label="Theme">
                    <button type="button" class="theme-card" data-theme-choice="light">
                        <span class="theme-preview tp-light"><i class="tp-side"></i><i class="tp-card"></i></span>Light
                    </button>
                    <button type="button" class="theme-card" data-theme-choice="dark">
                        <span class="theme-preview tp-dark"><i class="tp-side"></i><i class="tp-card"></i></span>Dark
                    </button>
                    <button type="button" class="theme-card" data-theme-choice="system">
                        <span class="theme-preview tp-system"></span>Automatic
                    </button>
                </div>
                <p class="text-muted small mt-3 mb-0">Automatic follows your phone or computer's own light/dark setting.</p>
            </div>
        </div>

        <div class="card" id="password">
            <div class="card-body">
                <div class="card-head"><h5 class="card-heading"><span class="stat-icon stat-icon-sm bg-soft-red"><?= icon('lock', 16) ?></span> Change password</h5></div>
                <form method="post" action="<?= base_url('profile/change_password') ?>">
                    <div class="mb-3">
                        <label class="form-label" for="pw-current">Current password</label>
                        <div class="password-field">
                            <input type="password" id="pw-current" name="current_password" class="form-control" required autocomplete="current-password">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="pw-new">New password</label>
                        <div class="password-field">
                            <input type="password" id="pw-new" name="new_password" class="form-control" minlength="6" required autocomplete="new-password" data-strength>
                        </div>
                        <div class="strength-meter"><span></span></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="pw-confirm">Confirm new password</label>
                        <div class="password-field">
                            <input type="password" id="pw-confirm" name="new_password_confirm" class="form-control" minlength="6" required autocomplete="new-password">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Update password</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="text-center mt-4 d-lg-none">
    <a href="<?= base_url('auth/logout') ?>" class="btn btn-outline-danger"><?= icon('logout', 16) ?> Log out</a>
</div>
