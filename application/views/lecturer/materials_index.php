<h1 class="page-title mb-4">My Modules</h1>

<?php if (empty($modules)): ?>
    <p class="text-muted">You're not assigned to any modules yet. An admin needs to assign you first.</p>
<?php else: ?>
<div class="row g-3">
<?php foreach ($modules as $module): ?>
    <div class="col-md-6">
        <div class="card h-100 shadow-sm">
            <div class="card-body">
                <h5 class="card-title"><?= html_escape($module['name']) ?></h5>
                <p class="card-text text-muted"><?= html_escape($module['description']) ?></p>
                <a href="<?= base_url('lecturer_materials/module/' . $module['id']) ?>" class="btn btn-primary">
                    Manage Materials
                </a>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>
<?php endif; ?>
