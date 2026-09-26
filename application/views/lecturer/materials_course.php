<p class="mb-3"><a href="<?= base_url('lecturer_materials') ?>" class="back-link">&larr; My Courses</a></p>
<h1 class="page-title mb-4"><?= html_escape($course['name']) ?> &mdash; Materials</h1>

<div class="card mb-4">
    <div class="card-body">
        <h5 class="card-title">Post New Material</h5>
        <form method="post" action="<?= base_url('lecturer_materials/course/' . $course['id']) ?>" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label">Title</label>
                <input type="text" name="title" class="form-control" value="<?= html_escape(set_value('title')) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3"><?= html_escape(set_value('description')) ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">File (optional)</label>
                <input type="file" name="file" class="form-control">
                <div class="form-text">PDF, Word, PowerPoint, Excel, image or ZIP, up to 20MB.</div>
            </div>
            <div class="mb-3">
                <label class="form-label">External Link (optional)</label>
                <input type="url" name="external_link" class="form-control" placeholder="https://..." value="<?= html_escape(set_value('external_link')) ?>">
            </div>
            <button type="submit" class="btn btn-primary">Post &amp; Notify Students</button>
        </form>
    </div>
</div>

<h5>Posted Materials</h5>
<?php if (empty($materials)): ?>
    <p class="text-muted">Nothing posted yet.</p>
<?php else: ?>
    <div class="list-group">
    <?php foreach ($materials as $m): ?>
        <div class="list-group-item">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <strong><?= html_escape($m['title']) ?></strong>
                    <p class="mb-1 text-muted"><?= html_escape($m['description']) ?></p>
                    <?php if ($m['file_path']): ?>
                        <span class="badge bg-secondary">File attached</span>
                    <?php endif; ?>
                    <?php if ($m['external_link']): ?>
                        <a href="<?= html_escape($m['external_link']) ?>" target="_blank" class="badge bg-info text-dark text-decoration-none">Link</a>
                    <?php endif; ?>
                    <div class="small text-muted mt-1"><?= html_escape(date('d M Y, H:i', strtotime($m['created_at']))) ?></div>
                </div>
                <form method="post" action="<?= base_url('lecturer_materials/delete/' . $m['id']) ?>" data-confirm="Remove this material? Students will no longer see it." data-confirm-ok="Remove" data-confirm-danger>
                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
<?php endif; ?>
