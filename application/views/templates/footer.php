<?php
    $CI =& get_instance();
    $CI->load->helper('ui');
    extract(layout_context());
?>
<?php if ($userId): ?>

        </main>
    </div><!-- /.app-content -->

    <!-- ============ Bottom tabs (phones) ============ -->
    <nav class="bottom-nav d-lg-none" aria-label="Main menu">
        <?php foreach ($navItems as $item): ?>
            <?php if (empty($item['mobile'])) { continue; } ?>
            <?php $count = isset($item['badge']) ? $badges[$item['badge']] : 0; ?>
            <a href="<?= base_url($item['url']) ?>" class="<?= nav_is_active($item, $uri, $segment) ? 'active' : '' ?>">
                <span class="bottom-icon">
                    <?= icon($item['icon'], 21) ?>
                    <?php if ($count > 0): ?><span class="dot-badge"><?= (int) $count ?></span><?php endif; ?>
                </span>
                <span><?= html_escape($item['label']) ?></span>
            </a>
        <?php endforeach; ?>
        <?php if (count(array_filter($navItems, function ($i) { return empty($i['mobile']) && empty($i['soon']); })) > 0): ?>
            <a href="#" data-palette-open>
                <span class="bottom-icon"><?= icon('grid', 21) ?><?php if ($badges['errors'] > 0): ?><span class="dot-badge"><?= (int) $badges['errors'] ?></span><?php endif; ?></span>
                <span>More</span>
            </a>
        <?php endif; ?>
    </nav>

</div><!-- /.app-shell -->

<!-- ============ Ctrl+K quick search ============ -->
<div class="palette-backdrop" id="palette" aria-hidden="true">
    <div class="palette" role="dialog" aria-modal="true" aria-label="Search">
        <div class="palette-input-wrap">
            <?= icon('search', 18) ?>
            <input type="text" class="palette-input" placeholder="Search pages and actions…" autocomplete="off" spellcheck="false" aria-label="Search">
            <span class="kbd">Esc</span>
        </div>
        <ul class="palette-list" role="listbox">
            <?php $lastGroup = null; ?>
            <?php foreach (palette_items($role) as $p): ?>
                <?php if ($p[0] !== $lastGroup): $lastGroup = $p[0]; ?>
                    <li class="palette-group" data-group="<?= html_escape($p[0]) ?>"><?= html_escape($p[0]) ?></li>
                <?php endif; ?>
                <li>
                    <a href="<?= html_escape($p[2]) ?>" class="palette-item" data-group="<?= html_escape($p[0]) ?>" role="option">
                        <?= icon($p[3], 17) ?> <span><?= html_escape($p[1]) ?></span>
                        <?php if ($p[4] !== ''): ?><span class="palette-hint"><?= html_escape($p[4]) ?></span><?php endif; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
        <div class="palette-empty d-none">Nothing matches that.</div>
        <div class="palette-foot"><span>↑ ↓ to move</span><span>Enter to open</span><span class="ms-auto">Ctrl K anywhere</span></div>
    </div>
</div>

<?php else: ?>

        </main>
        <p class="auth-footer">&copy; <?= date('Y') ?> <?= html_escape(setting('org_name', 'Theological Center')) ?> &middot; <a href="<?= base_url('support/report') ?>">Report a problem</a></p>
    </section>
</div><!-- /.auth-split -->

<?php endif; ?>

<!-- ============ Report a problem ============ -->
<div class="modal fade" id="reportModal" tabindex="-1" aria-hidden="true" aria-labelledby="reportTitle">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content report-modal" method="post" action="<?= base_url('support/report') ?>" data-ajax-report>
            <div class="modal-body p-4">
                <div class="d-flex align-items-start gap-3 mb-3">
                    <span class="empty-icon bg-soft-red"><?= icon('alert', 24) ?></span>
                    <div>
                        <h5 class="fw-bold mb-1" id="reportTitle">Report a problem</h5>
                        <p class="text-muted small mb-0">We'll attach the page you're on so the team can see exactly where it happened.</p>
                    </div>
                </div>
                <input type="hidden" name="page_url" value="">
                <label class="form-label">What's it about?</label>
                <select name="category" class="form-select mb-3">
                    <option value="bug">Something isn't working</option>
                    <option value="payment">Payment or fees</option>
                    <option value="access">Can't see a course or material</option>
                    <option value="account">Login or account</option>
                    <option value="idea">Suggestion</option>
                    <option value="other">Something else</option>
                </select>
                <label class="form-label">What happened?</label>
                <textarea name="description" class="form-control" rows="4" required minlength="5" placeholder="e.g. The Download button does nothing on my phone"></textarea>
                <?php if (! $userId): ?>
                    <label class="form-label mt-3">Your email or phone</label>
                    <input name="email" class="form-control" placeholder="So we can reply">
                <?php endif; ?>
                <div class="report-result d-none"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Send report</button>
            </div>
        </form>
    </div>
</div>

<!-- ============ Confirm dialog ============ -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content confirm-modal">
            <div class="modal-body">
                <span class="empty-icon bg-soft-navy mb-3"><?= icon('alert', 24) ?></span>
                <p class="confirm-message mb-0"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" data-confirm-yes>Continue</button>
            </div>
        </div>
    </div>
</div>

<?php if (file_exists(FCPATH . 'assets/js/bootstrap.bundle.min.js')): ?>
    <script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>
<?php else: ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php endif; ?>
<script>window.TC = { base: <?= json_encode(base_url()) ?>, loggedIn: <?= $userId ? 'true' : 'false' ?> };</script>
<script src="<?= base_url('assets/js/app.js') ?>?v=5"></script>
<script src="<?= base_url('assets/js/qr.js') ?>?v=1"></script>
</body>
</html>
