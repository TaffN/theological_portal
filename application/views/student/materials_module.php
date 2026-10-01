<?= module_crumbs($module, 'Materials') ?: '<p class="mb-3"><a href="' . base_url('student_materials') . '" class="back-link">&larr; My Materials</a></p>' ?>
<h1 class="page-title mb-4"><?= html_escape($module['name']) ?></h1>

<?php if (empty($materials)): ?>
    <p class="text-muted">No materials posted yet.</p>
<?php else: ?>
    <div class="list-group">
    <?php foreach ($materials as $m): ?>
        <div class="list-group-item">
            <strong><?= html_escape($m['title']) ?></strong>
            <p class="mb-1 text-muted"><?= html_escape($m['description']) ?></p>
            <?php if ($m['file_path']): ?>
                <a href="<?= base_url('student_materials/download/' . $m['id']) ?>" class="btn btn-sm btn-outline-primary">Download</a>
            <?php endif; ?>
            <?php if ($m['external_link']): ?>
                <a href="<?= html_escape($m['external_link']) ?>" target="_blank" class="btn btn-sm btn-outline-info">Open Link</a>
            <?php endif; ?>
            <div class="small text-muted mt-2"><?= html_escape(date('d M Y, H:i', strtotime($m['created_at']))) ?></div>
        </div>
    <?php endforeach; ?>
    </div>
<?php endif; ?>
