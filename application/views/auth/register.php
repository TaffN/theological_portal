<h1 class="auth-title">Create your account</h1>
<p class="auth-sub">Register once, then apply for any module.</p>

<form method="post" action="<?= base_url('auth/register') ?>" data-loading>
    <div class="mb-3">
        <label class="form-label" for="name">Full name</label>
        <input type="text" id="name" name="name" class="form-control" autocomplete="name" required value="<?= html_escape(set_value('name')) ?>">
    </div>
    <div class="row g-3 mb-3">
        <div class="col-sm-6">
            <label class="form-label" for="email">Email</label>
            <input type="email" id="email" name="email" class="form-control" autocomplete="email" required value="<?= html_escape(set_value('email')) ?>">
        </div>
        <div class="col-sm-6">
            <label class="form-label" for="phone">Phone (WhatsApp)</label>
            <input type="tel" id="phone" name="phone" class="form-control" autocomplete="tel" placeholder="+263 7..." value="<?= html_escape(set_value('phone')) ?>">
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label" for="password">Password</label>
        <div class="password-field">
            <input type="password" id="password" name="password" class="form-control" minlength="6" autocomplete="new-password" required data-strength>
        </div>
        <div class="strength-meter"><span></span></div>
        <div class="form-text">At least 6 characters. Mixing letters and numbers makes it stronger.</div>
    </div>
    <div class="mb-4">
        <label class="form-label" for="password_confirm">Confirm password</label>
        <div class="password-field">
            <input type="password" id="password_confirm" name="password_confirm" class="form-control" minlength="6" autocomplete="new-password" required>
        </div>
    </div>
    <div class="consent-box mb-4">
    <label class="form-check">
        <input class="form-check-input" type="checkbox" name="consent" value="1" required <?= set_checkbox('consent', '1') ?>>
        <span class="form-check-label">I agree to <?= html_escape(setting('org_short_name', 'the Center')) ?> keeping my details as described in the privacy notice.</span>
    </label>
    <details class="consent-details">
        <summary>Read the privacy notice</summary>
        <div class="consent-text"><?= nl2br(html_escape(setting('privacy_notice', 'Your details are used only to run your studies and are never sold or shared.'))) ?></div>
    </details>
</div>
<button type="submit" class="btn btn-primary btn-lg w-100">Create account</button>
</form>

<p class="auth-alt">Already have an account? <a href="<?= base_url('auth/login') ?>">Log in</a></p>
