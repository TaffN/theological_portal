<div class="<?= $guest ? '' : 'row justify-content-center' ?>">
<div class="<?= $guest ? '' : 'col-lg-7' ?>">
    <?php if (! $guest): ?><p class="mb-3"><a href="<?= base_url('dashboard') ?>" class="back-link">&larr; Dashboard</a></p><?php endif; ?>
    <div class="<?= $guest ? '' : 'card' ?>">
        <div class="<?= $guest ? '' : 'card-body p-4' ?>">
            <h1 class="<?= $guest ? 'auth-title' : 'page-title mb-1' ?>">Report a problem</h1>
            <p class="<?= $guest ? 'auth-sub' : 'text-muted mb-4' ?>">Tell us what went wrong and we'll look into it. You'll get a reference number.</p>
            <?php if ($ref): ?>
                <div class="notice notice-info mb-3"><?= icon('alert', 16) ?> <span>This is about error <strong><?= html_escape($ref) ?></strong>, which has already been logged automatically.</span></div>
            <?php endif; ?>
            <form method="post" action="<?= base_url('support/report') ?>">
                <input type="hidden" name="page_url" value="<?= html_escape($from) ?>">
                <input type="hidden" name="related_ref" value="<?= html_escape($ref) ?>">
                <label class="form-label">What's it about?</label>
                <div class="choice-group choice-group-3 mb-3">
                    <?php $first = true; foreach ($categories as $key => $c): ?>
                        <label class="choice"><input type="radio" name="category" value="<?= $key ?>" <?= $first ? 'checked' : '' ?>><span><strong><?= html_escape($c[0]) ?></strong></span></label>
                    <?php $first = false; endforeach; ?>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="r-desc">What happened?</label>
                    <textarea id="r-desc" name="description" class="form-control" rows="4" required minlength="5"
                              placeholder="e.g. I uploaded my EcoCash screenshot but the page went blank"></textarea>
                </div>
                <?php if ($guest): ?>
                    <div class="mb-3">
                        <label class="form-label" for="r-email">Your email or phone (so we can reply)</label>
                        <input id="r-email" name="email" class="form-control" placeholder="you@example.com or +263 7...">
                    </div>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary <?= $guest ? 'btn-lg w-100' : '' ?>">Send report</button>
            </form>
        </div>
    </div>
</div>
</div>
