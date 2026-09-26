<h1 class="page-title mb-4">Manage Courses</h1>

<div class="card mb-4">
    <div class="card-body">
        <h5 class="card-title">Add a Course</h5>
        <form method="post" action="<?= base_url('admin_courses/create_course') ?>" class="row g-2">
            <div class="col-md-4">
                <input type="text" name="name" class="form-control" placeholder="Course name" required>
            </div>
            <div class="col-md-4">
                <input type="text" name="description" class="form-control" placeholder="Short description">
            </div>
            <div class="col-md-2">
                <input type="number" step="0.01" name="fee_amount" class="form-control" placeholder="Fee ($)" required>
            </div>
            <div class="col-md-2">
                <input type="text" name="duration_text" class="form-control" placeholder="e.g. 1 Year">
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">Add Course</button>
            </div>
        </form>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <h5 class="card-title">Assign a Lecturer to a Course</h5>
        <?php if (empty($lecturers)): ?>
            <p class="text-muted mb-0">
                No lecturer accounts yet &mdash;
                <a href="<?= base_url('admin_users/lecturers') ?>">create one first</a>.
            </p>
        <?php else: ?>
        <form method="post" action="<?= base_url('admin_courses/assign') ?>" class="row g-2">
            <div class="col-md-5">
                <select name="course_id" class="form-select" required>
                    <option value="">Choose a course...</option>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= html_escape($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-5">
                <select name="user_id" class="form-select" required>
                    <option value="">Choose a lecturer...</option>
                    <?php foreach ($lecturers as $l): ?>
                        <option value="<?= $l['id'] ?>"><?= html_escape($l['name']) ?> (<?= html_escape($l['email']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Assign</button>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<h5>Courses</h5>
<?php if (empty($courses)): ?>
    <p class="text-muted">No courses yet.</p>
<?php else: ?>
<div class="row g-3">
<?php foreach ($courses as $course): ?>
    <div class="col-md-6">
        <div class="card h-100 shadow-sm">
            <div class="card-body">
                <h5 class="card-title"><?= html_escape($course['name']) ?></h5>
                <p class="text-muted mb-2">$<?= html_escape(number_format($course['fee_amount'], 2)) ?>
                    <?php if ($course['duration_text']): ?> &middot; <?= html_escape($course['duration_text']) ?><?php endif; ?>
                </p>

                <strong class="d-block mb-1">Lecturers:</strong>
                <?php if (empty($course['lecturers'])): ?>
                    <span class="text-muted">None assigned yet.</span>
                <?php else: ?>
                    <ul class="list-unstyled mb-0">
                    <?php foreach ($course['lecturers'] as $lec): ?>
                        <li class="d-flex justify-content-between align-items-center">
                            <span><?= html_escape($lec['name']) ?></span>
                            <a href="<?= base_url('admin_courses/unassign/' . $course['id'] . '/' . $lec['id']) ?>"
                               class="btn btn-sm btn-outline-danger"
                               data-confirm="Remove this lecturer from the course?" data-confirm-ok="Remove" data-confirm-danger>
                                Remove
                            </a>
                        </li>
                    <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>
<?php endif; ?>
