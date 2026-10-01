<?= crumbs([['Programs', 'admin_programs'], [$program['name']]]) ?>
<div class="page-head"><div><h1 class="page-title">Edit program</h1></div></div>

<?php if (! empty($error)): ?><div class="notice notice-danger mb-3"><?= icon('alert', 16) ?> <?= html_escape($error) ?></div><?php endif; ?>

<div class="card" style="max-width: 720px;">
    <div class="card-body">
        <form method="post" action="<?= base_url('admin_programs/edit/' . (int) $program['id']) ?>" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label" for="name">Program name</label>
                <input type="text" id="name" name="name" class="form-control" maxlength="200" required value="<?= html_escape(set_value('name', $program['name'])) ?>">
                <div class="form-text">Web address: <code>/programs/<?= html_escape($program['slug']) ?></code> (it changes only if you rename the program).</div>
            </div>
            <div class="mb-3">
                <label class="form-label" for="duration_text">Duration</label>
                <input type="text" id="duration_text" name="duration_text" class="form-control" maxlength="100" placeholder="e.g. 3 years" value="<?= html_escape(set_value('duration_text', $program['duration_text'])) ?>">
            </div>
            <div class="mb-3">
                <label class="form-label" for="description">Description</label>
                <textarea id="description" name="description" class="form-control" rows="4"><?= html_escape(set_value('description', $program['description'])) ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label" for="thumbnail">Picture</label>
                <?php if (! empty($program['thumbnail_path'])): ?>
                    <div class="mb-2"><img src="<?= base_url('programs/thumbnail/' . (int) $program['id']) ?>" alt="" class="program-thumb-preview"></div>
                <?php endif; ?>
                <input type="file" id="thumbnail" name="thumbnail" class="form-control" accept="image/png,image/jpeg">
                <div class="form-text">JPG or PNG, up to 1 MB. Leave empty to keep the current picture.</div>
            </div>
            <button type="submit" class="btn btn-primary">Save program</button>
            <a href="<?= base_url('admin_programs') ?>" class="btn btn-light">Cancel</a>
        </form>
    </div>
</div>
