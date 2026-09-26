<?php
/*
 * Printable ID card. Expects $u (user row) and $cardCourses (array of names).
 */
$roleLabel = ['student' => 'Student', 'lecturer' => 'Lecturer', 'admin' => 'Administrator'][$u['role']];
?>
<div class="id-card" id="id-card">
    <div class="id-card-top">
        <span class="brand-mark brand-mark-sm">TC</span>
        <div class="min-w-0">
            <div class="id-org">Theological Center</div>
            <div class="id-org-sub"><?= $roleLabel ?> identity card</div>
        </div>
        <span class="id-role id-role-<?= $u['role'] ?>"><?= $roleLabel ?></span>
    </div>
    <div class="id-card-body">
        <div class="id-photo">
            <?php if ($u['photo_path']): ?>
                <img src="<?= base_url('photo/view/' . (int) $u['id']) ?>?v=<?= strtotime($u['photo_updated_at']) ?>" alt="Photo of <?= html_escape($u['name']) ?>">
            <?php else: ?>
                <span class="id-photo-empty"><?= icon('user', 40) ?><small>No photo yet</small></span>
            <?php endif; ?>
        </div>
        <div class="id-details min-w-0">
            <div class="id-name"><?= html_escape($u['name']) ?></div>
            <div class="id-label">ID number</div>
            <div class="id-number"><?= html_escape($u['id_number'] ?: '—') ?></div>
            <?php if (! empty($cardCourses)): ?>
                <div class="id-label"><?= $u['role'] === 'lecturer' ? 'Teaches' : 'Enrolled in' ?></div>
                <div class="id-courses"><?= html_escape(implode(', ', array_slice($cardCourses, 0, 3))) ?><?= count($cardCourses) > 3 ? ' +' . (count($cardCourses) - 3) . ' more' : '' ?></div>
            <?php endif; ?>
            <div class="id-label">Member since</div>
            <div class="id-since"><?= html_escape(date('F Y', strtotime($u['created_at']))) ?></div>
        </div>
    </div>
    <div class="id-card-foot">
        <span><?= $u['status'] === 'active' ? 'Valid' : 'Not active' ?> &middot; issued <?= date('j M Y') ?></span>
        <span class="id-barcode" aria-hidden="true"><?= html_escape($u['id_number']) ?></span>
    </div>
</div>
