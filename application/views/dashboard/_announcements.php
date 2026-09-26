<?php if (! empty($announcements)): ?>
    <?php $toneIcon = ['info' => 'bell', 'success' => 'check', 'warning' => 'alert']; ?>
    <?php foreach ($announcements as $a): ?>
        <div class="announcement announcement-<?= $a['tone'] ?> mb-3" data-announcement="<?= (int) $a['id'] ?>">
            <span class="announcement-icon"><?= icon($toneIcon[$a['tone']], 18) ?></span>
            <div class="flex-grow-1 min-w-0">
                <strong class="d-block"><?= html_escape($a['title']) ?></strong>
                <div class="announcement-body"><?= nl2br(html_escape($a['body'])) ?></div>
                <small class="text-muted"><?= html_escape(time_ago($a['created_at'])) ?></small>
            </div>
            <button type="button" class="flash-close" data-dismiss-announcement aria-label="Dismiss"><?= icon('x', 16) ?></button>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
