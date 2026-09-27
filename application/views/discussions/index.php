<?php
    $boardName = 'All boards';
    if ($board === 'general') { $boardName = 'General'; }
    foreach ($courses as $c) { if ((string) $c['id'] === $board) { $boardName = $c['name']; } }
    $qs = function ($b) use ($q) { $p = array_filter(['board' => $b, 'q' => $q], 'strlen'); return $p ? '?' . http_build_query($p) : ''; };
?>
<div class="page-head">
    <div>
        <h1 class="page-title">Discussions</h1>
        <p class="page-sub">Ask questions, share insights and talk about what you're studying. Each course has its own board; <strong>General</strong> is for the whole college.</p>
    </div>
    <?php if ($general || $courses): ?>
        <button type="button" class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#new-topic" aria-expanded="false"><?= icon('plus', 16) ?> New topic</button>
    <?php endif; ?>
</div>

<?php if (! $general && ! $courses): ?>
    <div class="card"><div class="empty-state">
        <span class="empty-icon bg-soft-navy"><?= icon('message', 28) ?></span><br>
        Discussions open once your first course is paid up and approved.
    </div></div>
<?php else: ?>

<div class="collapse mb-4" id="new-topic">
    <form method="post" action="<?= base_url('discussions/create') ?>" class="card">
        <div class="card-body">
            <div class="card-head"><h5 class="card-heading"><?= icon('edit', 18) ?> Start a topic</h5></div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label" for="d-board">Board</label>
                    <select class="form-select" id="d-board" name="board" required>
                        <?php if ($general): ?><option value="general" <?= $board === 'general' ? 'selected' : '' ?>>General (whole college)</option><?php endif; ?>
                        <?php foreach ($courses as $c): ?>
                            <option value="<?= (int) $c['id'] ?>" <?= (string) $c['id'] === $board ? 'selected' : '' ?>><?= html_escape($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-8">
                    <label class="form-label" for="d-title">Title</label>
                    <input type="text" class="form-control" id="d-title" name="title" maxlength="200" required placeholder="e.g. What does Paul mean by 'the flesh' in Romans 8?">
                </div>
                <div class="col-12">
                    <label class="form-label" for="d-body">Message</label>
                    <textarea class="form-control" id="d-body" name="body" rows="5" maxlength="5000" required></textarea>
                    <div class="form-text">Be kind and respectful. Links are clickable.</div>
                </div>
            </div>
        </div>
        <div class="card-body border-top"><button type="submit" class="btn btn-primary"><?= icon('send', 16) ?> Post topic</button></div>
    </form>
</div>

<div class="board-bar mb-3">
    <nav class="tabs tabs-scroll" aria-label="Boards">
        <a href="<?= base_url('discussions') . $qs('') ?>" class="tab <?= $board === '' ? 'active' : '' ?>">All</a>
        <?php if ($general): ?><a href="<?= base_url('discussions') . $qs('general') ?>" class="tab <?= $board === 'general' ? 'active' : '' ?>">General</a><?php endif; ?>
        <?php foreach ($courses as $c): ?>
            <a href="<?= base_url('discussions') . $qs((string) $c['id']) ?>" class="tab <?= (string) $c['id'] === $board ? 'active' : '' ?>"><?= html_escape($c['name']) ?></a>
        <?php endforeach; ?>
    </nav>
    <form method="get" action="<?= base_url('discussions') ?>" class="board-search">
        <?php if ($board): ?><input type="hidden" name="board" value="<?= html_escape($board) ?>"><?php endif; ?>
        <input type="search" name="q" class="form-control form-control-sm" placeholder="Search topics…" value="<?= html_escape($q) ?>" aria-label="Search topics">
    </form>
</div>

<div class="card">
    <?php if (! $topics): ?>
        <div class="empty-state">
            <span class="empty-icon bg-soft-navy"><?= icon('message', 28) ?></span><br>
            <?= $q !== '' ? 'No topics match "' . html_escape($q) . '".' : 'No topics on ' . html_escape($boardName) . ' yet. Start the first one!' ?>
        </div>
    <?php else: ?>
        <ul class="issue-list topic-list">
        <?php foreach ($topics as $t): ?>
            <li>
                <a href="<?= base_url('discussions/view/' . $t['id']) ?>">
                    <?= avatar_html($t['author_name'], $t['user_id'], $t['photo_path'] ? $t['photo_updated_at'] : null, 'avatar-sm') ?>
                    <div class="flex-grow-1 min-w-0">
                        <div class="fw-semibold topic-title">
                            <?php if ($t['is_pinned']): ?><span class="topic-flag" title="Pinned"><?= icon('pin', 14) ?></span><?php endif; ?>
                            <?php if ($t['is_locked']): ?><span class="topic-flag" title="Closed"><?= icon('lock', 14) ?></span><?php endif; ?>
                            <?= html_escape($t['title']) ?>
                        </div>
                        <small class="text-muted">
                            <?= html_escape($t['author_name']) ?><?= $t['author_role'] !== 'student' ? ' <span class="role-tag">' . html_escape(ucfirst($t['author_role'])) . '</span>' : '' ?>
                            &middot; <?= html_escape($t['course_name'] ?: 'General') ?> &middot; <?= html_escape(time_ago($t['last_activity_at'])) ?>
                        </small>
                    </div>
                    <span class="reply-count <?= $t['reply_count'] ? '' : 'is-zero' ?>" title="Replies"><?= icon('message', 14) ?> <?= (int) $t['reply_count'] ?></span>
                </a>
            </li>
        <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
<?php endif; ?>
