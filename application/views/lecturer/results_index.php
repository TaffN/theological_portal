<div class="page-head">
    <div>
        <h1 class="page-title">Results</h1>
        <p class="page-sub">Each student's overall result per course, worked out from their assignment and exam marks. Check it, add remarks, then publish.</p>
    </div>
</div>

<?php if (empty($courses)): ?>
    <div class="card"><div class="empty-state">
        <span class="empty-icon bg-soft-navy"><?= icon('award', 28) ?></span><br>
        You haven't been assigned to a course yet.
    </div></div>
<?php else: ?>
    <div class="card">
        <ul class="issue-list">
        <?php foreach ($courses as $c): ?>
            <li>
                <a href="<?= base_url('lecturer_results/course/' . $c['id']) ?>">
                    <span class="course-dot course-dot-sm"><?= html_escape(initials($c['name'])) ?></span>
                    <span class="flex-grow-1 min-w-0">
                        <span class="issue-title"><?= html_escape($c['name']) ?></span>
                        <span class="issue-meta"><?= (int) $c['students'] ?> student<?= $c['students'] == 1 ? '' : 's' ?> &middot; assignments <?= (int) $c['weights'][0] ?>% / exams <?= (int) $c['weights'][1] ?>%</span>
                    </span>
                    <?php if ($c['published'] > 0): ?>
                        <span class="pill pill-success"><?= (int) $c['published'] ?> published</span>
                    <?php else: ?>
                        <span class="pill pill-muted">Not published</span>
                    <?php endif; ?>
                </a>
            </li>
        <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
