<?= crumbs([['Programs', 'admin_programs'], [$program['name']]]) ?>
<div class="page-head">
    <div>
        <h1 class="page-title"><?= html_escape($program['name']) ?> <?= status_badge($program['status']) ?></h1>
        <p class="page-sub">Modules in this program. Each has its own fee, lecturers, students, materials, assignments and exams.</p>
    </div>
    <a href="<?= base_url('admin_programs/edit/' . (int) $program['id']) ?>" class="btn btn-light btn-sm">Edit program</a>
</div>

<div class="card mb-4">
    <div class="card-body">
        <h5 class="card-title">Add a module to <?= html_escape($program['name']) ?></h5>
        <form method="post" action="<?= base_url('admin_programs/' . (int) $program['id'] . '/modules/create') ?>" class="row g-2">
            <div class="col-md-5">
                <input type="text" name="name" class="form-control" placeholder="Module name, e.g. Old Testament Survey" maxlength="200" required>
            </div>
            <div class="col-md-3">
                <input type="text" name="code" class="form-control" placeholder="Code (optional, e.g. OT101)" maxlength="30">
            </div>
            <div class="col-md-2">
                <input type="number" step="0.01" min="0" name="fee_amount" class="form-control" placeholder="Fee ($)" required>
            </div>
            <div class="col-md-2">
                <input type="number" min="0" name="credits" class="form-control" placeholder="Credits">
            </div>
            <div class="col-md-5">
                <input type="text" name="duration_text" class="form-control" placeholder="Duration, e.g. 12 weeks" maxlength="100">
            </div>
            <div class="col-md-7">
                <input type="text" name="description" class="form-control" placeholder="Short description">
            </div>
            <div class="col-12"><button type="submit" class="btn btn-primary">Add module</button></div>
        </form>
    </div>
</div>

<h5>Modules (<?= count($modules) ?>)</h5>
<?php if (empty($modules)): ?>
    <p class="text-muted">No modules yet. Add the first one above.</p>
<?php else: ?>
<div class="row g-3">
<?php foreach ($modules as $n => $module): ?>
    <div class="col-md-6">
        <div class="card h-100 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div>
                        <h5 class="card-title mb-0"><?= ($n + 1) ?>. <?= html_escape($module['name']) ?> <?= status_badge($module['status']) ?></h5>
                        <div class="module-code"><?= html_escape($module['code']) ?><?php if ((int) $module['credits'] > 0): ?> &middot; <?= (int) $module['credits'] ?> credits<?php endif; ?></div>
                    </div>
                    <div class="d-flex gap-1">
                        <?php if ($n > 0): ?>
                            <form method="post" action="<?= base_url('admin_modules/move/' . (int) $module['id'] . '/up') ?>"><button class="btn btn-light btn-sm" title="Move up" aria-label="Move up">&uarr;</button></form>
                        <?php endif; ?>
                        <?php if ($n < count($modules) - 1): ?>
                            <form method="post" action="<?= base_url('admin_modules/move/' . (int) $module['id'] . '/down') ?>"><button class="btn btn-light btn-sm" title="Move down" aria-label="Move down">&darr;</button></form>
                        <?php endif; ?>
                    </div>
                </div>
                <p class="text-muted mb-2 mt-1">$<?= html_escape(number_format($module['fee_amount'], 2)) ?>
                    <?php if ($module['duration_text']): ?> &middot; <?= html_escape($module['duration_text']) ?><?php endif; ?>
                    &middot; <?= (int) $module['students'] ?> student<?= $module['students'] == 1 ? '' : 's' ?>
                </p>

                <strong class="d-block mb-1">Lecturers:</strong>
                <?php if (empty($module['lecturers'])): ?>
                    <span class="text-muted d-block mb-2">None assigned yet.</span>
                <?php else: ?>
                    <ul class="list-unstyled mb-2">
                    <?php foreach ($module['lecturers'] as $lec): ?>
                        <li class="d-flex justify-content-between align-items-center">
                            <span><?= html_escape($lec['name']) ?></span>
                            <form method="post" action="<?= base_url('admin_modules/unassign/' . (int) $module['id'] . '/' . (int) $lec['id']) ?>"
                                  data-confirm="Remove this lecturer from the module?" data-confirm-ok="Remove" data-confirm-danger>
                                <button class="btn btn-sm btn-outline-danger">Remove</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <?php if (! empty($lecturers)): ?>
                    <form method="post" action="<?= base_url('admin_modules/assign') ?>" class="d-flex gap-2 mb-3">
                        <input type="hidden" name="module_id" value="<?= (int) $module['id'] ?>">
                        <select name="user_id" class="form-select form-select-sm" required aria-label="Lecturer to assign">
                            <option value="">Assign a lecturer...</option>
                            <?php foreach ($lecturers as $l): ?>
                                <option value="<?= (int) $l['id'] ?>"><?= html_escape($l['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn btn-primary btn-sm">Assign</button>
                    </form>
                <?php else: ?>
                    <p class="small text-muted">No lecturer accounts yet &mdash; <a href="<?= base_url('admin_users/lecturers') ?>">create one first</a>.</p>
                <?php endif; ?>

                <div class="d-flex flex-wrap gap-2">
                    <a href="<?= base_url('admin_modules/edit/' . (int) $module['id']) ?>" class="btn btn-light btn-sm">Edit</a>
                    <form method="post" action="<?= base_url('admin_modules/toggle/' . (int) $module['id']) ?>"
                          data-confirm="<?= $module['status'] === 'active' ? 'Close this module to new applications? Existing students keep their access.' : 'Open this module for applications again?' ?>"
                          data-confirm-ok="<?= $module['status'] === 'active' ? 'Close' : 'Open' ?>">
                        <button class="btn btn-light btn-sm"><?= $module['status'] === 'active' ? 'Close' : 'Open' ?></button>
                    </form>
                    <form method="post" action="<?= base_url('admin_modules/delete/' . (int) $module['id']) ?>"
                          data-confirm="Delete this module? This only works for a module with no students, materials, assignments or exams." data-confirm-ok="Delete" data-confirm-danger>
                        <button class="btn btn-outline-danger btn-sm">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>
<?php endif; ?>
