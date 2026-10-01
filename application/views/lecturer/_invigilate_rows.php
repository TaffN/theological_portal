<?php
    // Rendered inside the invigilation page and, every few seconds, on its own by Lecturer_exams::live().
    $CI =& get_instance();
    $CI->load->helper('ui');
    $now = time();
?>
<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3"><div class="card stat-card"><div class="stat-icon bg-soft-navy"><?= icon('users', 22) ?></div><div><div class="stat-value"><?= (int) $counts['not_started'] ?></div><div class="stat-label">Not started</div></div></div></div>
    <div class="col-6 col-lg-3"><div class="card stat-card"><div class="stat-icon bg-soft-gold"><?= icon('edit', 22) ?></div><div><div class="stat-value"><?= (int) $counts['writing'] ?></div><div class="stat-label">Writing now</div></div></div></div>
    <div class="col-6 col-lg-3"><div class="card stat-card"><div class="stat-icon bg-soft-green"><?= icon('check', 22) ?></div><div><div class="stat-value"><?= (int) $counts['handed_in'] ?></div><div class="stat-label">Handed in</div></div></div></div>
    <div class="col-6 col-lg-3"><div class="card stat-card"><div class="stat-icon bg-soft-red"><?= icon('alert', 22) ?></div><div><div class="stat-value"><?= (int) $counts['flagged'] ?></div><div class="stat-label">With flags</div></div></div></div>
</div>

<div class="card">
    <?php if (empty($rows)): ?>
        <div class="empty-state">No students have access to this module yet.</div>
    <?php else: ?>
    <ul class="invigilate-list">
    <?php foreach ($rows as $r): ?>
        <?php
            $total   = $r['att_id'] ? count(json_decode($r['question_ids'], true) ?: []) : 0;
            $writing = $r['att_id'] && ! $r['submitted_at'];
            $left    = $writing ? max(0, strtotime($r['deadline_at']) - $now) : 0;
            $seenAgo = $r['last_seen_at'] ? $now - strtotime($r['last_seen_at']) : null;
            $offline = $writing && $seenAgo !== null && $seenAgo > 75;
        ?>
        <li class="<?= $r['flag_count'] > 0 && $writing ? 'is-flagged' : '' ?>">
            <?= avatar_html($r['name'], $r['student_id'], $r['photo_path'] ? $r['photo_updated_at'] : null, 'avatar-sm') ?>
            <div class="flex-grow-1 min-w-0">
                <div class="fw-semibold text-truncate"><?= html_escape($r['name']) ?></div>
                <small class="text-muted">
                    <?php if (! $r['att_id']): ?>
                        Not started
                    <?php elseif ($writing): ?>
                        <?= (int) $r['answered'] ?>/<?= $total ?> answered &middot;
                        <span class="<?= $left < 300 ? 'text-danger-soft fw-semibold' : '' ?>"><?= floor($left / 60) ?>:<?= str_pad($left % 60, 2, '0', STR_PAD_LEFT) ?> left</span>
                        &middot; <?= $offline ? '<span class="text-warning-soft fw-semibold">no signal for ' . html_escape(time_ago($r['last_seen_at'])) . '</span>' : 'online' ?>
                    <?php else: ?>
                        Handed in <?= html_escape(time_ago($r['submitted_at'])) ?><?= $r['submit_reason'] === 'time_up' ? ' (time ran out)' : '' ?> &middot; <?= (int) $r['answered'] ?>/<?= $total ?> answered
                    <?php endif; ?>
                </small>
            </div>
            <div class="d-flex align-items-center gap-2 flex-shrink-0 flex-wrap justify-content-end">
                <?php if ($r['att_id'] && $r['flag_count'] > 0): ?>
                    <span class="flag-chip" title="Warning signs recorded"><?= icon('alert', 13) ?> <?= (int) $r['flag_count'] ?><?= $r['away_seconds'] > 0 ? ' &middot; away ' . ($r['away_seconds'] >= 60 ? floor($r['away_seconds'] / 60) . 'm ' : '') . ($r['away_seconds'] % 60) . 's' : '' ?></span>
                <?php endif; ?>
                <?php if (! $r['att_id']): ?>
                    <?= status_badge('exam_not_started') ?>
                <?php elseif ($writing): ?>
                    <?= status_badge('exam_writing') ?>
                <?php else: ?>
                    <?= status_badge('exam_handed_in') ?>
                <?php endif; ?>
                <?php if ($r['phone']): ?>
                    <a class="icon-btn" target="_blank" rel="noopener" title="WhatsApp <?= html_escape($r['name']) ?>"
                       href="<?= html_escape(wa_link($r['phone'], 'Hello ' . display_first_name($r['name']) . ', this is about the exam "' . $e['title'] . '".')) ?>"><?= icon('phone', 16) ?></a>
                <?php endif; ?>
                <?php if ($writing): ?>
                    <form method="post" action="<?= base_url('lecturer_exams/reset_device/' . $r['att_id']) ?>" class="d-inline"
                          data-confirm="Let <?= html_escape($r['name']) ?> continue on another phone or computer? The device they're using now will stop working for this exam." data-confirm-ok="Allow new device">
                        <button type="submit" class="btn btn-sm btn-light">New device</button></form>
                <?php endif; ?>
                <?php if ($r['att_id']): ?>
                    <a href="<?= base_url('lecturer_exams/attempt/' . $r['att_id']) ?>" class="btn btn-sm btn-light">Details</a>
                <?php endif; ?>
            </div>
        </li>
    <?php endforeach; ?>
    </ul>
    <?php endif; ?>
</div>
<p class="small text-muted mt-2 mb-0">Updated <?= date('H:i:s') ?>. This page refreshes itself every 10 seconds.</p>
