<?php $stateText = ['verified' => 'Verified', 'pending' => 'Waiting for the office', 'rejected' => 'Not accepted: upload a new copy', 'missing' => 'Not uploaded yet']; ?>
<div class="page-head">
    <div>
        <h1 class="page-title">My documents</h1>
        <p class="page-sub">Copies of your ID and qualifications, so the Center can confirm who you are. Only you and the office can open them.</p>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-body">
                <div class="card-head"><h5 class="card-heading"><?= icon('check-square', 18) ?> What the Center needs</h5></div>
                <?php if (! $checklist): ?>
                    <p class="text-muted small mb-0">Nothing is required at the moment. You can still upload documents for your records.</p>
                <?php else: ?>
                    <ul class="doc-checklist">
                        <?php foreach ($checklist as $type => $state): ?>
                            <li class="is-<?= $state ?>">
                                <span class="doc-state-icon"><?= icon($state === 'verified' ? 'check' : ($state === 'pending' ? 'clock' : 'alert'), 16) ?></span>
                                <div class="min-w-0"><div class="fw-semibold"><?= html_escape($types[$type]) ?></div><small class="text-muted"><?= $stateText[$state] ?></small></div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <form method="post" action="<?= base_url('documents/upload') ?>" enctype="multipart/form-data" class="card h-100">
            <div class="card-body">
                <div class="card-head"><h5 class="card-heading"><?= icon('upload', 18) ?> Upload a document</h5></div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="dc-type">What is it?</label>
                        <select class="form-select" id="dc-type" name="doc_type" required>
                            <?php $first = $checklist ? array_keys(array_filter($checklist, function ($s) { return $s === 'missing' || $s === 'rejected'; })) : []; $first = $first ? $first[0] : ''; ?>
                            <?php foreach ($types as $k => $label): ?><option value="<?= $k ?>" <?= $k === $first ? 'selected' : '' ?>><?= html_escape($label) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="dc-title">Description (optional)</label>
                        <input type="text" class="form-control" id="dc-title" name="title" maxlength="200" placeholder="e.g. Diploma in Theology, 2019">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="dc-file">Photo or scan (PDF, JPG or PNG, up to <?= (int) $maxMb ?> MB)</label>
                        <input type="file" class="form-control" id="dc-file" name="file" required accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/*">
                        <div class="form-text">On a phone you can take a photo. Make sure every word is readable and nothing is cut off.</div>
                    </div>
                </div>
            </div>
            <div class="card-body border-top"><button type="submit" class="btn btn-primary"><?= icon('upload', 16) ?> Send to the office</button></div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body pb-0"><div class="card-head"><h5 class="card-heading"><?= icon('file', 18) ?> Uploaded</h5></div></div>
    <?php if (! $docs): ?>
        <div class="empty-state pt-2">You haven't uploaded anything yet.</div>
    <?php else: ?>
        <ul class="issue-list">
        <?php foreach ($docs as $d): ?>
            <li><div class="issue-row doc-row">
                <span class="mini-icon bg-soft-navy"><?= icon(preg_match('/\.pdf$/i', $d['file_path']) ? 'file' : 'image', 16) ?></span>
                <div class="flex-grow-1 min-w-0">
                    <div class="fw-semibold text-truncate"><?= html_escape(Document_model::type_label($d['doc_type'])) ?><?= $d['title'] ? ' <span class="text-muted fw-normal">&middot; ' . html_escape($d['title']) . '</span>' : '' ?></div>
                    <small class="text-muted">Uploaded <?= html_escape(time_ago($d['uploaded_at'])) ?>
                        <?php if ($d['status'] === 'rejected' && $d['review_note']): ?> &middot; <span class="text-danger-soft">Reason: <?= html_escape($d['review_note']) ?></span><?php endif; ?>
                        <?php if ($d['status'] === 'verified'): ?> &middot; verified <?= html_escape(time_ago($d['reviewed_at'])) ?><?php endif; ?>
                    </small>
                </div>
                <div class="doc-actions d-flex align-items-center gap-2">
                <?= status_badge($d['status']) ?>
                <a href="<?= base_url('documents/file/' . $d['id']) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-light">Open</a>
                <?php if ($d['status'] !== 'verified'): ?>
                    <form method="post" action="<?= base_url('documents/delete/' . $d['id']) ?>" data-confirm="Remove this document?" data-confirm-ok="Remove" data-confirm-danger>
                        <button class="btn btn-link btn-sm p-0 text-muted" aria-label="Remove"><?= icon('x', 15) ?></button>
                    </form>
                <?php endif; ?>
                </div>
            </div></li>
        <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
