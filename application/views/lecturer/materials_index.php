<h1 class="page-title mb-4">My Courses</h1>

<?php if (empty($courses)): ?>
    <p class="text-muted">You're not assigned to any courses yet. An admin needs to assign you first.</p>
<?php else: ?>
<div class="row g-3">
<?php foreach ($courses as $course): ?>
    <div class="col-md-6">
        <div class="card h-100 shadow-sm">
            <div class="card-body">
                <h5 class="card-title"><?= html_escape($course['name']) ?></h5>
                <p class="card-text text-muted"><?= html_escape($course['description']) ?></p>
                <a href="<?= base_url('lecturer_materials/course/' . $course['id']) ?>" class="btn btn-primary">
                    Manage Materials
                </a>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>
<?php endif; ?>
