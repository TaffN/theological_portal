<h1 class="auth-title">Forgot your password?</h1>
<p class="auth-sub">Enter your email. The administrator will reset it and send you a new password on WhatsApp or by phone.</p>

<form method="post" action="<?= base_url('support/forgot') ?>">
    <div class="mb-4">
        <label class="form-label" for="f-email">Email</label>
        <input type="email" id="f-email" name="email" class="form-control form-control-lg" autocomplete="email" required autofocus>
    </div>
    <button type="submit" class="btn btn-primary btn-lg w-100">Request a reset</button>
</form>

<p class="auth-alt"><a href="<?= base_url('auth/login') ?>">&larr; Back to log in</a></p>
