<div class="page-head">
    <div>
        <h1 class="page-title">Assignments</h1>
        <p class="page-sub">Set work for your courses, then mark what students hand in. Students are notified at each step.</p>
    </div>
    <?php if ($to_mark > 0): ?>
        <span class="pill pill-warning"><?= (int) $to_mark ?> waiting to be marked</span>
    <?php endif; ?>
</div>

<?php if (empty($groups)): ?>
    <div class="card"><div class="empty-state">
        <span class="empty-icon bg-soft-navy"><?= icon('book', 28) ?></span><br>
        You haven't been assigned to a course yet. Ask the administrator to assign you, then you can set assignments here.
    </div></div>
<?php endif; ?>

<?php foreach ($groups as $g): ?>
    <?php $c = $g['course']; ?>
    <div class="card mb-4">
        <div class="card-body pb-0">
            <div class="card-head">
                <h5 class="card-heading"><span class="course-dot course-dot-sm"><?= html_escape(initials($c['name'])) ?></span> <?= html_escape($c['name']) ?></h5>
                <a href="<?= base_url('lecturer_assignments/create/' . $c['id']) ?>" class="btn btn-primary btn-sm"><?= icon('plus', 16) ?> New assignment</a>
            </div>
        </div>
        <?php if (empty($g['assignments'])): ?>
            <div class="empty-state pt-2">No assignments in this course yet.</div>
        <?php else: ?>
            <ul class="issue-list">
            <?php foreach ($g['assignments'] as $a): ?>
                <?php $past = strtotime($a['due_at']) < time(); ?>
                <li>
                    <a href="<?= base_url('lecturer_assignments/view/' . $a['id']) ?>">
                        <span class="stat-icon stat-icon-sm <?= $past ? 'bg-soft-navy' : 'bg-soft-gold' ?>"><?= icon('edit', 16) ?></span>
                        <span class="flex-grow-1 min-w-0">
                            <span class="issue-title"><?= html_escape($a['title']) ?></span>
                            <span class="issue-meta">
                                <?= $past ? 'Closed' : 'Due' ?> <?= html_escape(date('D j M, H:i', strtotime($a['due_at']))) ?> &middot; <?= html_escape(due_in($a['due_at'])) ?>
                                &middot; <?= (int) $a['submitted'] ?> handed in
                            </span>
                        </span>
                        <?php if ($a['to_mark'] > 0): ?>
                            <span class="pill pill-warning"><?= (int) $a['to_mark'] ?> to mark</span>
                        <?php elseif ($a['submitted'] > 0): ?>
                            <span class="pill pill-success">All marked</span>
                        <?php endif; ?>
                    </a>
                </li>
            <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
