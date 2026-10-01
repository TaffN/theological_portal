<?php
    $groups = ['Today' => [], 'Earlier' => []];
    $today  = date('Y-m-d');
    foreach ($notifications as $n) {
        $groups[substr($n['created_at'], 0, 10) === $today ? 'Today' : 'Earlier'][] = $n;
    }

    // Pick an icon/colour from what the message is about.
    $style = function ($msg) {
        $m = strtolower($msg);
        if (strpos($m, 'not accepted') !== false || strpos($m, 'reject') !== false) return ['alert', 'bg-soft-red'];
        if (strpos($m, 'approved') !== false)  return ['check', 'bg-soft-green'];
        if (strpos($m, 'material') !== false)  return ['file', 'bg-soft-gold'];
        if (strpos($m, 'payment') !== false)   return ['card', 'bg-soft-blue'];
        return ['bell', 'bg-soft-navy'];
    };
?>
<div class="page-head">
    <div>
        <h1 class="page-title">Notifications</h1>
        <p class="page-sub">Updates about your payments, modules and new materials.</p>
    </div>
</div>

<?php if (empty($notifications)): ?>
    <div class="card"><div class="empty-state">
        <span class="empty-icon bg-soft-navy"><?= icon('bell', 28) ?></span>
        <strong class="d-block text-body mt-2">You're all caught up</strong>
        New updates will appear here.
    </div></div>
<?php endif; ?>

<?php foreach ($groups as $label => $items): ?>
    <?php if (empty($items)) { continue; } ?>
    <h6 class="section-label"><?= $label ?></h6>
    <div class="card mb-4">
        <ul class="notif-list">
        <?php foreach ($items as $n): ?>
            <?php list($ic, $bg) = $style($n['message']); ?>
            <li>
                <a href="<?= base_url('notifications/open/' . $n['id']) ?>" class="<?= $n['is_read'] ? '' : 'is-unread' ?>">
                    <span class="stat-icon stat-icon-sm <?= $bg ?>"><?= icon($ic, 16) ?></span>
                    <span class="flex-grow-1 min-w-0">
                        <span class="notif-text"><?= html_escape($n['message']) ?></span>
                        <small class="text-muted"><?= html_escape(time_ago($n['created_at'])) ?></small>
                    </span>
                    <?php if (! $n['is_read']): ?><span class="unread-dot"></span><?php endif; ?>
                </a>
            </li>
        <?php endforeach; ?>
        </ul>
    </div>
<?php endforeach; ?>
