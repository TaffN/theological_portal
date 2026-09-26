<div class="page-head">
    <div>
        <h1 class="page-title">Announcements</h1>
        <p class="page-sub">Notices shown at the top of dashboards, e.g. exam dates, fee deadlines, public holidays.</p>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-body">
                <div class="card-head"><h5 class="card-heading"><span class="stat-icon stat-icon-sm bg-soft-gold"><?= icon('bell', 16) ?></span> New announcement</h5></div>
                <form method="post" action="<?= base_url('admin_announcements/create') ?>">
                    <div class="mb-3"><label class="form-label">Title</label><input name="title" class="form-control" maxlength="150" required placeholder="e.g. Semester 2 fees due 30 October"></div>
                    <div class="mb-3"><label class="form-label">Message</label><textarea name="body" class="form-control" rows="4" maxlength="2000" required></textarea></div>
                    <div class="row g-2 mb-3">
                        <div class="col-6"><label class="form-label">Who sees it</label>
                            <select name="audience" class="form-select"><option value="all">Everyone</option><option value="student">Students</option><option value="lecturer">Lecturers</option></select></div>
                        <div class="col-6"><label class="form-label">Show for</label>
                            <select name="days" class="form-select"><option value="0">Until I hide it</option><option value="3">3 days</option><option value="7" selected>1 week</option><option value="14">2 weeks</option><option value="30">1 month</option></select></div>
                    </div>
                    <label class="form-label">Style</label>
                    <div class="choice-group choice-group-3 mb-3">
                        <label class="choice"><input type="radio" name="tone" value="info" checked><span><strong>Info</strong><small>Blue</small></span></label>
                        <label class="choice"><input type="radio" name="tone" value="success"><span><strong>Good news</strong><small>Green</small></span></label>
                        <label class="choice"><input type="radio" name="tone" value="warning"><span><strong>Important</strong><small>Gold</small></span></label>
                    </div>
                    <button class="btn btn-primary w-100">Publish</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <?php if (empty($rows)): ?>
            <div class="card"><div class="empty-state"><span class="empty-icon bg-soft-gold"><?= icon('bell', 28) ?></span><br>No announcements yet.</div></div>
        <?php endif; ?>
        <?php foreach ($rows as $a): ?>
            <?php $expired = $a['expires_at'] && strtotime($a['expires_at']) < time(); $live = $a['is_active'] && ! $expired; ?>
            <div class="announcement announcement-<?= $a['tone'] ?> mb-3 <?= $live ? '' : 'is-off' ?>">
                <div class="flex-grow-1 min-w-0">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                        <strong><?= html_escape($a['title']) ?></strong>
                        <span class="pill pill-muted"><?= $a['audience'] === 'all' ? 'Everyone' : ucfirst($a['audience']) . 's' ?></span>
                        <?= $live ? '<span class="pill pill-success">Live</span>' : '<span class="pill pill-muted">' . ($expired ? 'Expired' : 'Hidden') . '</span>' ?>
                    </div>
                    <div class="announcement-body"><?= nl2br(html_escape($a['body'])) ?></div>
                    <small class="text-muted d-block mt-2"><?= html_escape($a['author'] ?: 'Admin') ?> &middot; <?= html_escape(time_ago($a['created_at'])) ?>
                        <?= $a['expires_at'] ? ' &middot; until ' . date('j M', strtotime($a['expires_at'])) : '' ?></small>
                </div>
                <div class="d-flex flex-column gap-1">
                    <form method="post" action="<?= base_url('admin_announcements/toggle/' . $a['id']) ?>"><button class="btn btn-sm btn-light w-100"><?= $a['is_active'] ? 'Hide' : 'Show' ?></button></form>
                    <form method="post" action="<?= base_url('admin_announcements/delete/' . $a['id']) ?>" data-confirm="Delete this announcement?" data-confirm-ok="Delete" data-confirm-danger><button class="btn btn-sm btn-outline-danger w-100">Delete</button></form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
