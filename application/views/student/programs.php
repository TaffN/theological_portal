<div class="page-head">
    <div>
        <h1 class="page-title">Programs</h1>
        <p class="page-sub">Choose a program. You pay one fee and every module in it opens to you.</p>
    </div>
</div>

<?php if (empty($programs)): ?>
    <div class="card"><div class="empty-state"><?= icon('book', 32) ?><br>No programs are open for applications right now.</div></div>
<?php endif; ?>

<div class="row g-3">
<?php foreach ($programs as $i => $p): ?>
    <?php
        $pid  = (int) $p['id'];
        $pe = isset($mine[$pid]) ? $mine[$pid] : null;
    ?>
    <div class="col-md-6 col-xl-4">
        <div class="card module-card program-card h-100">
            <?php if (! empty($p['thumbnail_path'])): ?>
                <div class="program-thumb"><img src="<?= base_url('programs/thumbnail/' . $pid) ?>" alt="" loading="lazy"></div>
            <?php else: ?>
                <div class="module-band band-<?= $i % 4 ?>"><span class="module-initials"><?= html_escape(initials($p['name'])) ?></span><?php if ($pe): ?><?= status_badge($pe['status']) ?><?php endif; ?></div>
            <?php endif; ?>
            <div class="card-body d-flex flex-column">
                <h5 class="module-name"><?= html_escape($p['name']) ?></h5>
                <?php if ($p['description']): ?>
                    <p class="text-muted small mb-3"><?= html_escape(mb_strimwidth($p['description'], 0, 160, '...')) ?></p>
                <?php endif; ?>
                <ul class="module-meta">
                    <li><?= icon('dollar', 16) ?> <strong><?= (float) $p['fee_amount'] > 0 ? money($p['fee_amount']) : 'Free' ?></strong> for the whole program</li>
                    <li><?= icon('layers', 16) ?> <?= (int) $p['open_modules'] ?> module<?= $p['open_modules'] == 1 ? '' : 's' ?></li>
                    <?php if ($p['duration_text']): ?><li><?= icon('clock', 16) ?> <?= html_escape($p['duration_text']) ?></li><?php endif; ?>
                    <?php if ($pe): ?>
                        <li><?= icon('check', 16) ?> <?= $pe['status'] === 'pending_payment' ? 'Applied, waiting for payment' : ($pe['status'] === 'active' ? 'You are enrolled' : 'Enrolment ' . html_escape(str_replace('_', ' ', $pe['status']))) ?></li>
                    <?php endif; ?>
                </ul>
                <div class="mt-auto">
                    <a href="<?= base_url('programs/' . $p['slug']) ?>" class="btn btn-primary w-100"><?= $pe ? 'Open' : 'See the modules' ?></a>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>
