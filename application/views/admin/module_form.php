<?= crumbs([['Programs', 'admin_programs'], [$module['program_name'], 'admin_programs/' . (int) $module['program_id'] . '/modules'], [$module['name']]]) ?>
<div class="page-head"><div><h1 class="page-title">Edit module</h1></div></div>

<?php if (! empty($error)): ?><div class="notice notice-danger mb-3"><?= icon('alert', 16) ?> <?= html_escape($error) ?></div><?php endif; ?>

<div class="card" style="max-width: 720px;">
    <div class="card-body">
        <form method="post" action="<?= base_url('admin_modules/edit/' . (int) $module['id']) ?>">
            <div class="mb-3">
                <label class="form-label" for="program_id">Program</label>
                <select id="program_id" name="program_id" class="form-select" required>
                    <?php foreach ($programs as $p): ?>
                        <option value="<?= (int) $p['id'] ?>" <?= (int) set_value('program_id', $module['program_id']) === (int) $p['id'] ? 'selected' : '' ?>><?= html_escape($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">Moving a module to another program keeps its students, materials, assignments, exams and results.</div>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-8">
                    <label class="form-label" for="name">Module name</label>
                    <input type="text" id="name" name="name" class="form-control" maxlength="200" required value="<?= html_escape(set_value('name', $module['name'])) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="code">Code</label>
                    <input type="text" id="code" name="code" class="form-control" maxlength="30" value="<?= html_escape(set_value('code', $module['code'])) ?>">
                </div>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label" for="credits">Credits</label>
                    <input type="number" min="0" id="credits" name="credits" class="form-control" value="<?= html_escape(set_value('credits', $module['credits'])) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="duration_text">Duration</label>
                    <input type="text" id="duration_text" name="duration_text" class="form-control" maxlength="100" placeholder="e.g. 12 weeks" value="<?= html_escape(set_value('duration_text', $module['duration_text'])) ?>">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label" for="description">Description</label>
                <textarea id="description" name="description" class="form-control" rows="4"><?= html_escape(set_value('description', $module['description'])) ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Save module</button>
            <a href="<?= base_url('admin_programs/' . (int) $module['program_id'] . '/modules') ?>" class="btn btn-light">Cancel</a>
        </form>
    </div>
</div>
