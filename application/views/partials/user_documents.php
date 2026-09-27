<?php
/** Admin user card: this person's documents, with verify / reject. Reads its own data. */
$CI =& get_instance();
if ($CI->db->table_exists('user_documents') && $u['role'] !== 'admin'):
    $CI->load->model('Document_model');
    $docs = $CI->Document_model->for_user($u['id']);
    $check = $CI->Document_model->checklist($u['id'], $u['role']);
?>
<div class="card no-print mb-4" id="documents">
    <div class="card-body pb-0">
        <div class="card-head">
            <h5 class="card-heading"><span class="stat-icon stat-icon-sm bg-soft-blue"><?= icon('file', 16) ?></span> Documents</h5>
            <span class="card-sub">
                <?php foreach ($check as $t => $state): ?><span class="me-2"><?= html_escape(Document_model::type_label($t)) ?>: <?= status_badge($state === 'rejected' ? 'missing' : $state) ?></span><?php endforeach; ?>
            </span>
        </div>
    </div>
    <?php if (! $docs): ?>
        <div class="empty-state pt-2">No documents uploaded yet.</div>
    <?php else: ?>
        <ul class="issue-list">
        <?php foreach ($docs as $d): ?>
            <li><div class="issue-row flex-wrap">
                <span class="mini-icon bg-soft-navy"><?= icon(preg_match('/\.pdf$/i', $d['file_path']) ? 'file' : 'image', 16) ?></span>
                <div class="flex-grow-1 min-w-0">
                    <div class="fw-semibold"><?= html_escape(Document_model::type_label($d['doc_type'])) ?><?= $d['title'] ? ' <span class="text-muted fw-normal">&middot; ' . html_escape($d['title']) . '</span>' : '' ?></div>
                    <small class="text-muted">Uploaded <?= html_escape(time_ago($d['uploaded_at'])) ?><?= $d['review_note'] ? ' &middot; ' . html_escape($d['review_note']) : '' ?><?= $d['reviewer_name'] ? ' &middot; by ' . html_escape($d['reviewer_name']) : '' ?></small>
                </div>
                <?= status_badge($d['status']) ?>
                <a href="<?= base_url('documents/file/' . $d['id']) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-light">Open</a>
                <?php if ($d['status'] === 'pending'): ?>
                    <form method="post" action="<?= base_url('admin_documents/review/' . $d['id']) ?>" class="d-flex gap-1 align-items-center">
                        <input type="hidden" name="back" value="card">
                        <input type="text" name="note" class="form-control form-control-sm" style="max-width:180px" maxlength="500" placeholder="Reason if rejecting">
                        <button name="action" value="verify" class="btn btn-sm btn-success">Verify</button>
                        <button name="action" value="reject" class="btn btn-sm btn-outline-danger">Reject</button>
                    </form>
                <?php endif; ?>
            </div></li>
        <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
<?php endif; ?>
