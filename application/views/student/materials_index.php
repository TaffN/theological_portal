<h1 class="page-title mb-4">My Materials</h1>

<?php if (empty($courses)): ?>
    <p class="text-muted">
        You don't have active access to any courses yet. Once your payment is approved,
        your courses will show up here.
    </p>
<?php else: ?>
<div class="row g-3">
<?php foreach ($courses as $course): ?>
    <div class="col-md-6">
        <div class="card h-100 shadow-sm">
            <div class="card-body">
                <h5 class="card-title"><?= html_escape($course['name']) ?></h5>
                <a href="<?= base_url('student_materials/course/' . $course['id']) ?>" class="btn btn-primary">
                    View Materials
                </a>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>
<?php endif; ?>
