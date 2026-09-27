<?php /* $show = list of filters to show: course, year, by, scope */ ?>
<form method="get" class="report-filters no-print mb-3">
    <?php if (in_array('by', $show, true)): ?>
        <label class="small text-muted">Group by
            <select name="by" class="form-select form-select-sm" onchange="this.form.submit()">
                <?php foreach ($groupings as $k => $label): ?><option value="<?= $k ?>" <?= $by === $k ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?>
            </select></label>
    <?php endif; ?>
    <?php if (in_array('course', $show, true)): ?>
        <label class="small text-muted">Course
            <select name="course" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All courses</option>
                <?php foreach ($courses as $c): ?><option value="<?= (int) $c['id'] ?>" <?= (int) $c['id'] === (int) $courseId ? 'selected' : '' ?>><?= html_escape($c['name']) ?></option><?php endforeach; ?>
            </select></label>
    <?php endif; ?>
    <?php if (in_array('year', $show, true)): ?>
        <label class="small text-muted">Year
            <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value=""><?= in_array('fees', $show, true) ? 'Last 12 months' : 'All years' ?></option>
                <?php $ys = in_array('fees', $show, true) ? range((int) date('Y'), (int) date('Y') - 4) : $years; foreach ($ys as $y): ?><option <?= (int) $y === (int) $year ? 'selected' : '' ?>><?= (int) $y ?></option><?php endforeach; ?>
            </select></label>
    <?php endif; ?>
    <?php if (in_array('scope', $show, true)): ?>
        <label class="small text-muted">Who
            <select name="scope" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="enrolled" <?= $scope === 'enrolled' ? 'selected' : '' ?>>Students with a paid-up course</option>
                <option value="all" <?= $scope === 'all' ? 'selected' : '' ?>>All student accounts</option>
            </select></label>
    <?php endif; ?>
</form>
