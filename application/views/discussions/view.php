<?php $me = (int) get_instance()->session->userdata('user_id'); ?>
<p class="mb-3"><a href="<?= base_url('discussions?board=' . ($topic['module_id'] ?: 'general')) ?>" class="back-link">&larr; <?= html_escape($topic['module_name'] ?: 'General') ?></a></p>

<div class="page-head">
    <div class="min-w-0">
        <h1 class="page-title">
            <?php if ($topic['is_pinned']): ?><span class="topic-flag" title="Pinned"><?= icon('pin', 18) ?></span><?php endif; ?>
            <?= html_escape($topic['title']) ?>
        </h1>
        <p class="page-sub mb-0"><?= (int) $topic['reply_count'] ?> repl<?= $topic['reply_count'] == 1 ? 'y' : 'ies' ?> &middot; <?= html_escape($topic['module_name'] ?: 'General board') ?><?= $topic['is_locked'] ? ' &middot; closed for replies' : '' ?></p>
    </div>
    <?php if ($moderate || (int) $topic['user_id'] === $me): ?>
        <div class="d-flex flex-wrap gap-2">
            <?php if ($moderate): ?>
                <form method="post" action="<?= base_url('discussions/pin/' . $topic['id']) ?>"><button class="btn btn-light btn-sm"><?= icon('pin', 15) ?> <?= $topic['is_pinned'] ? 'Unpin' : 'Pin' ?></button></form>
                <form method="post" action="<?= base_url('discussions/lock/' . $topic['id']) ?>"><button class="btn btn-light btn-sm"><?= icon('lock', 15) ?> <?= $topic['is_locked'] ? 'Reopen' : 'Close replies' ?></button></form>
            <?php endif; ?>
            <form method="post" action="<?= base_url('discussions/delete/' . $topic['id']) ?>" data-confirm="Delete this topic and all its replies? This can't be undone." data-confirm-ok="Delete" data-confirm-danger>
                <button class="btn btn-light btn-sm text-danger-soft"><?= icon('x', 15) ?> Delete</button>
            </form>
        </div>
    <?php endif; ?>
</div>

<div class="post-thread">
    <article class="post is-op">
        <?= avatar_html($topic['author_name'], $topic['user_id'], $topic['photo_path'] ? $topic['photo_updated_at'] : null) ?>
        <div class="post-main">
            <div class="post-meta">
                <strong><?= html_escape($topic['author_name']) ?></strong>
                <?= $topic['author_role'] !== 'student' ? '<span class="role-tag">' . html_escape(ucfirst($topic['author_role'])) . '</span>' : '' ?>
                <span class="text-muted">&middot; <?= html_escape(time_ago($topic['created_at'])) ?></span>
            </div>
            <div class="post-body"><?= post_format($topic['body']) ?></div>
        </div>
    </article>

    <?php foreach ($replies as $r): ?>
        <article class="post" id="r<?= (int) $r['id'] ?>">
            <?= avatar_html($r['author_name'], $r['user_id'], $r['photo_path'] ? $r['photo_updated_at'] : null, 'avatar-sm') ?>
            <div class="post-main">
                <div class="post-meta">
                    <strong><?= html_escape($r['author_name']) ?></strong>
                    <?= $r['author_role'] !== 'student' ? '<span class="role-tag">' . html_escape(ucfirst($r['author_role'])) . '</span>' : '' ?>
                    <span class="text-muted">&middot; <?= html_escape(time_ago($r['created_at'])) ?></span>
                    <?php if ($moderate || (int) $r['user_id'] === $me): ?>
                        <form method="post" action="<?= base_url('discussions/delete_reply/' . $r['id']) ?>" class="ms-auto" data-confirm="Delete this reply?" data-confirm-ok="Delete" data-confirm-danger>
                            <button class="btn btn-link btn-sm p-0 text-muted" aria-label="Delete reply"><?= icon('x', 14) ?></button>
                        </form>
                    <?php endif; ?>
                </div>
                <div class="post-body"><?= post_format($r['body']) ?></div>
            </div>
        </article>
    <?php endforeach; ?>
</div>

<?php if (! $topic['is_locked'] || $moderate): ?>
    <form method="post" action="<?= base_url('discussions/reply/' . $topic['id']) ?>" class="card mt-3" id="reply">
        <div class="card-body">
            <label class="form-label" for="reply-body"><?= $topic['is_locked'] ? 'Reply (the topic is closed; only staff can reply)' : 'Your reply' ?></label>
            <textarea class="form-control" id="reply-body" name="body" rows="4" maxlength="<?= (int) $maxBody ?>" required></textarea>
            <button type="submit" class="btn btn-primary mt-3"><?= icon('send', 16) ?> Reply</button>
        </div>
    </form>
<?php else: ?>
    <div class="notice notice-info mt-3"><?= icon('lock', 16) ?> <span>This topic is closed for replies.</span></div>
<?php endif; ?>
