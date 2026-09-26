<?php $CI =& get_instance(); ?>
<h1 class="auth-title">Welcome back</h1>
<p class="auth-sub">Log in to continue your studies.</p>

<form method="post" action="<?= base_url('auth/login') ?>" data-loading>
    <div class="mb-3">
        <label class="form-label" for="email">Email</label>
        <input type="email" id="email" name="email" class="form-control form-control-lg" autocomplete="email" required autofocus
               value="<?= html_escape(set_value('email') ?: (string) $CI->session->flashdata('old_email')) ?>">
    </div>
    <div class="mb-4">
        <div class="d-flex justify-content-between align-items-baseline">
            <label class="form-label" for="password">Password</label>
            <a href="<?= base_url('support/forgot') ?>" class="small fw-semibold">Forgot password?</a>
        </div>
        <div class="password-field">
            <input type="password" id="password" name="password" class="form-control form-control-lg" autocomplete="current-password" required>
        </div>
    </div>
    <button type="submit" class="btn btn-primary btn-lg w-100">Log in</button>
</form>

<p class="auth-alt">New to <?= html_escape(setting('org_short_name', 'the Center')) ?>? <a href="<?= base_url('auth/register') ?>">Create an account</a></p>
