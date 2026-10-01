<h1 class="page-title mb-4">My Materials</h1>

<?php if (empty($modules)): ?>
    <p class="text-muted">
        You don't have active access to any modules yet. Once your payment is approved,
        your modules will show up here.
    </p>
<?php else: ?>
<div class="row g-3">
<?php foreach ($modules as $module): ?>
    <div class="col-md-6">
        <div class="card h-100 shadow-sm">
            <div class="card-body">
                <h5 class="card-title"><?= html_escape($module['name']) ?></h5>
                <a href="<?= base_url('student_materials/module/' . $module['id']) ?>" class="btn btn-primary">
                    View Materials
                </a>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>
<?php endif; ?>
