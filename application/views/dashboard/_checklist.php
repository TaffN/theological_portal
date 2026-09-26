<?php
    $done  = count(array_filter($checklist, function ($c) { return $c[1]; }));
    $total = count($checklist);
    if ($done >= $total) { return; }
    $pct = round($done / $total * 100);
    $nextShown = false;
?>
<div class="card checklist-card mb-4" data-checklist>
    <div class="card-body">
        <div class="d-flex align-items-center gap-3 mb-3">
            <div class="ring" style="--p: <?= $pct ?>"><span><?= $done ?>/<?= $total ?></span></div>
            <div class="flex-grow-1 min-w-0">
                <h5 class="card-heading mb-1"><?= html_escape($heading) ?></h5>
                <div class="text-muted small"><?= $pct ?>% done &middot; this card disappears once everything is ticked off</div>
            </div>
        </div>
        <ol class="checklist">
            <?php foreach ($checklist as $c): ?>
                <?php $isNext = ! $c[1] && ! $nextShown; if ($isNext) { $nextShown = true; } ?>
                <li class="<?= $c[1] ? 'is-done' : ($isNext ? 'is-next' : '') ?>">
                    <span class="check-dot"><?= $c[1] ? icon('check', 14) : '' ?></span>
                    <span class="flex-grow-1 min-w-0">
                        <span class="check-label"><?= html_escape($c[0]) ?></span>
                        <?php if (! $c[1] && $c[3] !== ''): ?><small class="text-muted d-block"><?= html_escape($c[3]) ?></small><?php endif; ?>
                    </span>
                    <?php if (! $c[1] && $c[2]): ?>
                        <a href="<?= $c[2] ?>" class="btn btn-sm <?= $isNext ? 'btn-primary' : 'btn-light' ?>"><?= $isNext ? 'Start' : 'Go' ?></a>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ol>
    </div>
</div>
