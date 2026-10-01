<div class="page-head">
    <div>
        <h1 class="page-title">Programs</h1>
        <p class="page-sub">A program is a qualification such as a Diploma. It holds the modules students apply and pay for, each with its own lecturers, materials, assignments and exams.</p>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <h5 class="card-title">Add a program</h5>
        <form method="post" action="<?= base_url('admin_programs/create') ?>" enctype="multipart/form-data" class="row g-2">
            <div class="col-md-5">
                <input type="text" name="name" class="form-control" placeholder="Program name, e.g. Diploma in Theology" maxlength="200" required>
            </div>
            <div class="col-md-3">
                <input type="text" name="duration_text" class="form-control" placeholder="Duration, e.g. 3 years" maxlength="100">
            </div>
            <div class="col-md-4">
                <input type="file" name="thumbnail" class="form-control" accept="image/png,image/jpeg" title="Optional picture (JPG or PNG, up to 1 MB)">
            </div>
            <div class="col-12">
                <textarea name="description" class="form-control" rows="2" placeholder="Short description (optional)"></textarea>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">Add program</button>
                <span class="text-muted small ms-2">You add its modules next.</span>
            </div>
        </form>
    </div>
</div>

<?php if (empty($programs)): ?>
    <div class="card"><div class="empty-state"><?= icon('layers', 32) ?><br>No programs yet. Add the first one above, then add its modules.</div></div>
<?php endif; ?>

<div class="row g-3">
<?php foreach ($programs as $i => $p): ?>
    <div class="col-md-6 col-xl-4">
        <div class="card module-card program-card h-100">
            <?php if (! empty($p['thumbnail_path'])): ?>
                <div class="program-thumb"><img src="<?= base_url('programs/thumbnail/' . (int) $p['id']) ?>" alt="" loading="lazy"></div>
            <?php else: ?>
                <div class="module-band band-<?= $i % 4 ?>">
                    <span class="module-initials"><?= html_escape(initials($p['name'])) ?></span>
                    <?= status_badge($p['status']) ?>
                </div>
            <?php endif; ?>
            <div class="card-body d-flex flex-column">
                <h5 class="module-name"><?= html_escape($p['name']) ?>
                    <?php if (! empty($p['thumbnail_path'])): ?><?= status_badge($p['status']) ?><?php endif; ?></h5>
                <ul class="module-meta">
                    <li><?= icon('layers', 16) ?> <?= (int) $p['module_count'] ?> module<?= $p['module_count'] == 1 ? '' : 's' ?> (<?= (int) $p['open_modules'] ?> open)</li>
                    <li><?= icon('users', 16) ?> <?= (int) $p['students'] ?> student<?= $p['students'] == 1 ? '' : 's' ?> with access</li>
                    <?php if ($p['duration_text']): ?><li><?= icon('clock', 16) ?> <?= html_escape($p['duration_text']) ?></li><?php endif; ?>
                </ul>
                <div class="mt-auto d-flex flex-wrap gap-2">
                    <a href="<?= base_url('admin_programs/' . (int) $p['id'] . '/modules') ?>" class="btn btn-primary btn-sm flex-grow-1">Modules</a>
                    <a href="<?= base_url('admin_programs/edit/' . (int) $p['id']) ?>" class="btn btn-light btn-sm">Edit</a>
                    <form method="post" action="<?= base_url('admin_programs/toggle/' . (int) $p['id']) ?>" class="d-inline"
                          data-confirm="<?= $p['status'] === 'active' ? 'Close this program to new applications? Students already enrolled keep their access.' : 'Open this program for applications again?' ?>"
                          data-confirm-ok="<?= $p['status'] === 'active' ? 'Close' : 'Open' ?>">
                        <button class="btn btn-light btn-sm"><?= $p['status'] === 'active' ? 'Close' : 'Open' ?></button>
                    </form>
                    <?php if ((int) $p['module_count'] === 0): ?>
                        <form method="post" action="<?= base_url('admin_programs/delete/' . (int) $p['id']) ?>" class="d-inline"
                              data-confirm="Delete this empty program?" data-confirm-ok="Delete" data-confirm-danger>
                            <button class="btn btn-outline-danger btn-sm">Delete</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>
