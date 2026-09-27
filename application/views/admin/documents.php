<?php
    $link = function ($over) use ($tab, $role, $q) {
        $p = array_filter(array_merge(['tab' => $tab, 'role' => $role, 'q' => $q], $over), 'strlen');
        return base_url('admin_documents') . '?' . http_build_query($p);
    };
?>
<div class="page-head">
    <div>
        <h1 class="page-title">Documents</h1>
        <p class="page-sub">Check each copy against the person's details (name, ID number, photo) before verifying. Rejected documents go back to the person with your reason.</p>
    </div>
    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="collapse" data-bs-target="#req-docs"><?= icon('layers', 15) ?> Required documents</button>
</div>

<div class="collapse mb-4" id="req-docs">
    <form method="post" action="<?= base_url('admin_documents/required') ?>" class="card">
        <div class="card-body">
            <div class="row g-4">
                <?php foreach (['student' => 'Students must provide', 'lecturer' => 'Lecturers must provide'] as $r => $label): ?>
                    <div class="col-md-6">
                        <div class="form-label"><?= $label ?></div>
                        <?php foreach ($types as $k => $t): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="rq-<?= $r . $k ?>" name="required_<?= $r ?>[]" value="<?= $k ?>" <?= in_array($k, $required[$r], true) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="rq-<?= $r . $k ?>"><?= html_escape($t) ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="card-body border-top"><button type="submit" class="btn btn-primary btn-sm">Save required documents</button>
            <small class="text-muted ms-2">People see what's required on their My documents page.</small></div>
    </form>
</div>

<div class="board-bar mb-3">
    <nav class="tabs tabs-scroll" aria-label="Status">
        <a href="<?= $link(['tab' => 'pending']) ?>" class="tab <?= $tab === 'pending' ? 'active' : '' ?>">To verify <?php if ($counts['pending']): ?><span class="count-pill"><?= $counts['pending'] ?></span><?php endif; ?></a>
        <a href="<?= $link(['tab' => 'verified']) ?>" class="tab <?= $tab === 'verified' ? 'active' : '' ?>">Verified</a>
        <a href="<?= $link(['tab' => 'rejected']) ?>" class="tab <?= $tab === 'rejected' ? 'active' : '' ?>">Rejected</a>
        <a href="<?= $link(['tab' => 'missing']) ?>" class="tab <?= $tab === 'missing' ? 'active' : '' ?>">Missing</a>
    </nav>
    <form method="get" action="<?= base_url('admin_documents') ?>" class="board-search d-flex gap-2">
        <input type="hidden" name="tab" value="<?= html_escape($tab) ?>">
        <select name="role" class="form-select form-select-sm" onchange="this.form.submit()" aria-label="Who">
            <option value="">Students and lecturers</option><option value="student" <?= $role === 'student' ? 'selected' : '' ?>>Students</option><option value="lecturer" <?= $role === 'lecturer' ? 'selected' : '' ?>>Lecturers</option>
        </select>
        <?php if ($tab !== 'missing'): ?><input type="search" name="q" class="form-control form-control-sm" placeholder="Name or ID number" value="<?= html_escape($q) ?>" aria-label="Search"><?php endif; ?>
    </form>
</div>

<?php if ($tab === 'missing'): ?>
    <div class="card">
        <?php if (! $missing): ?>
            <div class="empty-state"><span class="empty-icon bg-soft-green"><?= icon('check', 28) ?></span><br>Everyone has provided the required documents (or they are waiting to be verified).</div>
        <?php else: ?>
            <ul class="issue-list">
            <?php foreach ($missing as $u): ?>
                <li><div class="issue-row">
                    <?= avatar_html($u['name'], $u['id'], $u['photo_path'] ? $u['photo_updated_at'] : null, 'avatar-sm') ?>
                    <div class="flex-grow-1 min-w-0">
                        <a href="<?= base_url('admin_users/card/' . $u['id']) ?>" class="fw-semibold text-reset"><?= html_escape($u['name']) ?></a>
                        <small class="text-muted d-block"><span class="id-chip"><?= html_escape($u['id_number']) ?></span> <?= html_escape(ucfirst($u['role'])) ?> &middot; missing: <?= html_escape(implode(', ', array_map(function ($t) use ($types) { return $types[$t]; }, $u['lacking']))) ?></small>
                    </div>
                    <?php if ($u['phone']): ?>
                        <a href="<?= wa_link($u['phone'], 'Hello ' . $u['name'] . ', please upload your ' . implode(' and ', array_map(function ($t) use ($types) { return $types[$t]; }, $u['lacking'])) . ' on the portal under My documents. Thank you.') ?>" target="_blank" rel="noopener" class="btn btn-sm btn-light"><?= icon('message', 14) ?> Remind</a>
                    <?php endif; ?>
                </div></li>
            <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
<?php elseif (! $docs): ?>
    <div class="card"><div class="empty-state"><?= $tab === 'pending' ? 'Nothing waiting to be verified.' : 'Nothing here yet.' ?></div></div>
<?php else: ?>
    <div class="row g-3">
    <?php foreach ($docs as $d): $isImg = ! preg_match('/\.pdf$/i', $d['file_path']); ?>
        <div class="col-md-6 col-xl-4">
            <div class="card h-100">
                <a href="<?= base_url('documents/file/' . $d['id']) ?>" target="_blank" rel="noopener" class="doc-preview">
                    <?php if ($isImg): ?><img src="<?= base_url('documents/file/' . $d['id']) ?>" alt="<?= html_escape(Document_model::type_label($d['doc_type'])) ?> of <?= html_escape($d['owner_name']) ?>" loading="lazy">
                    <?php else: ?><span class="doc-pdf"><?= icon('file', 34) ?><small>PDF &middot; open</small></span><?php endif; ?>
                </a>
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <?= avatar_html($d['owner_name'], $d['user_id'], $d['photo_path'] ? $d['photo_updated_at'] : null, 'avatar-sm') ?>
                        <div class="min-w-0">
                            <a href="<?= base_url('admin_users/card/' . $d['user_id']) ?>" class="fw-semibold text-reset d-block text-truncate"><?= html_escape($d['owner_name']) ?></a>
                            <small class="text-muted"><?= html_escape($d['id_number']) ?> &middot; <?= html_escape(ucfirst($d['owner_role'])) ?></small>
                        </div>
                    </div>
                    <div class="fw-semibold"><?= html_escape(Document_model::type_label($d['doc_type'])) ?></div>
                    <?php if ($d['title']): ?><div class="small text-muted"><?= html_escape($d['title']) ?></div><?php endif; ?>
                    <small class="text-muted d-block">Uploaded <?= html_escape(time_ago($d['uploaded_at'])) ?><?= $d['review_note'] ? ' &middot; note: ' . html_escape($d['review_note']) : '' ?></small>
                </div>
                <?php if ($tab === 'pending'): ?>
                    <form method="post" action="<?= base_url('admin_documents/review/' . $d['id']) ?>" class="card-body border-top">
                        <input type="text" name="note" class="form-control form-control-sm mb-2" maxlength="500" placeholder="Reason if rejecting, e.g. photo is blurred">
                        <div class="d-flex gap-2">
                            <button type="submit" name="action" value="verify" class="btn btn-success btn-sm flex-grow-1"><?= icon('check', 15) ?> Verify</button>
                            <button type="submit" name="action" value="reject" class="btn btn-outline-danger btn-sm flex-grow-1">Reject</button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="card-body border-top py-2 small text-muted d-flex align-items-center gap-2"><?= status_badge($d['status']) ?> <?= $d['reviewed_at'] ? html_escape(time_ago($d['reviewed_at'])) : '' ?></div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
<?php endif; ?>
