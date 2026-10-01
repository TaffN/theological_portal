<p class="mb-3"><a href="<?= base_url('lecturer_attendance/module/' . $module['id']) ?>" class="back-link">&larr; <?= html_escape($module['name']) ?></a></p>
<div class="page-head">
    <div>
        <h1 class="page-title"><?= $session ? 'Correct the register' : 'Take the register' ?></h1>
        <p class="page-sub mb-0"><?= html_escape($module['name']) ?>. Everyone starts as present: tap the others.</p>
    </div>
</div>

<?php if (! $students): ?>
    <div class="card"><div class="empty-state">No students have access to this module yet, so there is no one to mark.</div></div>
<?php else: ?>
<form method="post" class="card" data-register>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-6 col-md-3">
                <label class="form-label" for="at-date">Date of the class</label>
                <input type="date" class="form-control" id="at-date" name="session_date" required max="<?= date('Y-m-d', strtotime('+1 day')) ?>" value="<?= html_escape($session ? $session['session_date'] : date('Y-m-d')) ?>">
            </div>
            <div class="col-md-9">
                <label class="form-label" for="at-topic">Topic (optional)</label>
                <input type="text" class="form-control" id="at-topic" name="topic" maxlength="200" value="<?= html_escape($session ? (string) $session['topic'] : '') ?>" placeholder="e.g. Acts 2: Pentecost">
            </div>
        </div>
    </div>
    <div class="card-body border-top py-2 d-flex flex-wrap align-items-center gap-2">
        <small class="text-muted me-auto" data-register-count></small>
        <button type="button" class="btn btn-light btn-sm" data-mark-all="present">All present</button>
        <button type="button" class="btn btn-light btn-sm" data-mark-all="absent">All absent</button>
    </div>
    <ul class="register">
        <?php foreach ($students as $s): ?>
            <?php $cur = isset($marks[$s['id']]) ? $marks[$s['id']]['status'] : 'present'; $note = isset($marks[$s['id']]) ? (string) $marks[$s['id']]['note'] : ''; ?>
            <li>
                <div class="register-who">
                    <?= avatar_html($s['name'], $s['id'], $s['photo_path'] ? $s['photo_updated_at'] : null, 'avatar-sm') ?>
                    <div class="min-w-0"><div class="fw-semibold text-truncate"><?= html_escape($s['name']) ?></div><small class="text-muted"><?= html_escape($s['id_number']) ?></small></div>
                </div>
                <div class="register-marks" role="radiogroup" aria-label="<?= html_escape($s['name']) ?>">
                    <?php foreach ($statuses as $k => $label): ?>
                        <label class="mark-choice mark-<?= $k ?>"><input type="radio" name="status[<?= (int) $s['id'] ?>]" value="<?= $k ?>" <?= $cur === $k ? 'checked' : '' ?>><span><?= $label ?></span></label>
                    <?php endforeach; ?>
                </div>
                <input type="text" class="form-control form-control-sm register-note" name="note[<?= (int) $s['id'] ?>]" maxlength="255" value="<?= html_escape($note) ?>" placeholder="Note (optional)" aria-label="Note for <?= html_escape($s['name']) ?>">
            </li>
        <?php endforeach; ?>
    </ul>
    <div class="card-body border-top d-flex flex-wrap gap-2">
        <button type="submit" class="btn btn-primary"><?= icon('check', 16) ?> Save register</button>
        <a href="<?= base_url('lecturer_attendance/module/' . $module['id']) ?>" class="btn btn-light">Cancel</a>
        <?php if ($session): ?><button type="submit" form="del-session" class="btn btn-link text-danger-soft ms-auto">Delete this register</button><?php endif; ?>
    </div>
</form>
<?php if ($session): ?>
    <form method="post" action="<?= base_url('lecturer_attendance/delete/' . $session['id']) ?>" id="del-session" class="d-none" data-confirm="Delete this register and all its marks?" data-confirm-ok="Delete" data-confirm-danger></form>
<?php endif; ?>
<script>
(function () {
    var form = document.querySelector('[data-register]');
    if (!form) { return; }
    function count() {
        var c = { present: 0, late: 0, absent: 0, excused: 0 };
        form.querySelectorAll('.register input[type=radio]:checked').forEach(function (r) { c[r.value]++; });
        form.querySelector('[data-register-count]').textContent = c.present + ' present · ' + c.late + ' late · ' + c.absent + ' absent · ' + c.excused + ' excused';
    }
    form.addEventListener('change', count);
    form.querySelectorAll('[data-mark-all]').forEach(function (b) {
        b.addEventListener('click', function () {
            form.querySelectorAll('.register input[value="' + b.getAttribute('data-mark-all') + '"]').forEach(function (r) { r.checked = true; });
            count();
        });
    });
    count();
})();
</script>
<?php endif; ?>
