<div class="page-head">
    <div>
        <h1 class="page-title">Programs</h1>
        <p class="page-sub">Choose a program to see its modules. You apply and pay for one module at a time.</p>
    </div>
</div>

<?php if (empty($programs)): ?>
    <div class="card"><div class="empty-state"><?= icon('book', 32) ?><br>No programs are open for applications right now.</div></div>
<?php endif; ?>

<div class="row g-3">
<?php foreach ($programs as $i => $p): ?>
    <?php
        $pid  = (int) $p['id'];
        $mine = isset($applied[$pid]) ? $applied[$pid] : 0;
        $live = isset($active[$pid]) ? $active[$pid] : 0;
    ?>
    <div class="col-md-6 col-xl-4">
        <div class="card module-card program-card h-100">
            <?php if (! empty($p['thumbnail_path'])): ?>
                <div class="program-thumb"><img src="<?= base_url('programs/thumbnail/' . $pid) ?>" alt="" loading="lazy"></div>
            <?php else: ?>
                <div class="module-band band-<?= $i % 4 ?>"><span class="module-initials"><?= html_escape(initials($p['name'])) ?></span></div>
            <?php endif; ?>
            <div class="card-body d-flex flex-column">
                <h5 class="module-name"><?= html_escape($p['name']) ?></h5>
                <?php if ($p['description']): ?>
                    <p class="text-muted small mb-3"><?= html_escape(mb_strimwidth($p['description'], 0, 160, '...')) ?></p>
                <?php endif; ?>
                <ul class="module-meta">
                    <li><?= icon('layers', 16) ?> <?= (int) $p['open_modules'] ?> module<?= $p['open_modules'] == 1 ? '' : 's' ?> open</li>
                    <?php if ($p['duration_text']): ?><li><?= icon('clock', 16) ?> <?= html_escape($p['duration_text']) ?></li><?php endif; ?>
                    <?php if ($p['fee_min'] !== null): ?>
                        <li><?= icon('dollar', 16) ?> <?= $p['fee_min'] == $p['fee_max'] ? money($p['fee_min']) : money($p['fee_min']) . ' to ' . money($p['fee_max']) ?> per module</li>
                    <?php endif; ?>
                    <?php if ($mine): ?><li><?= icon('check', 16) ?> Applied for <?= $mine ?> module<?= $mine == 1 ? '' : 's' ?><?= $live > 0 ? ', ' . $live . ' open to you' : ', none opened yet' ?></li><?php endif; ?>
                </ul>
                <div class="mt-auto">
                    <a href="<?= base_url('programs/' . $p['slug']) ?>" class="btn btn-primary w-100">See the modules</a>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>
