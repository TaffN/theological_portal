<?php
    $v = function ($k, $d = '') use ($e) { return $e && isset($e[$k]) && $e[$k] !== null ? $e[$k] : $d; };
    $allDay  = $e ? (bool) $e['all_day'] : false;
    $start   = $e && ! $allDay ? date('H:i', strtotime($e['starts_at'])) : '09:00';
    $end     = $e && $e['ends_at'] && ! $allDay ? date('H:i', strtotime($e['ends_at'])) : '';
    $endDate = $e && $e['ends_at'] ? date('Y-m-d', strtotime($e['ends_at'])) : '';
    if ($endDate === $date) { $endDate = ''; }
?>
<p class="mb-3"><a href="<?= base_url('calendar?m=' . substr($date, 0, 7)) ?>" class="back-link">&larr; Calendar</a></p>
<div class="page-head"><div><h1 class="page-title"><?= $e ? 'Edit event' : 'Add an event' ?></h1>
    <p class="page-sub">Students on the module are told about new events and changed times.</p></div></div>

<form method="post" action="<?= base_url('calendar/save' . ($e ? '/' . $e['id'] : '')) ?>" class="card">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label" for="ce-title">Title</label>
                <input type="text" class="form-control" id="ce-title" name="title" maxlength="200" required value="<?= html_escape($v('title')) ?>" placeholder="e.g. Live class: The Book of Acts">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="ce-type">Kind</label>
                <select class="form-select" id="ce-type" name="event_type">
                    <?php foreach ($types as $k => $label): ?><option value="<?= $k ?>" <?= $v('event_type', 'class') === $k ? 'selected' : '' ?>><?= html_escape($label) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="ce-module">For</label>
                <select class="form-select" id="ce-module" name="module_id">
                    <?php if ($college): ?><option value="" <?= $e && $e['module_id'] === null ? 'selected' : '' ?>>The whole college</option><?php endif; ?>
                    <?php foreach ($modules as $c): ?><option value="<?= (int) $c['id'] ?>" <?= $e && (int) $e['module_id'] === (int) $c['id'] ? 'selected' : '' ?>><?= html_escape(module_label($c)) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label" for="ce-date">Date</label>
                <input type="date" class="form-control" id="ce-date" name="date" required value="<?= html_escape($date) ?>">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label" for="ce-end-date">Until (optional)</label>
                <input type="date" class="form-control" id="ce-end-date" name="end_date" value="<?= html_escape($endDate) ?>">
            </div>
            <div class="col-12">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="ce-allday" name="all_day" value="1" <?= $allDay ? 'checked' : '' ?>
                           onchange="document.querySelectorAll('[data-times] input').forEach(function(i){i.disabled=this.checked;}, this)">
                    <label class="form-check-label" for="ce-allday">All day (for example a holiday or a closing date)</label>
                </div>
            </div>
            <div class="col-6 col-md-3" data-times>
                <label class="form-label" for="ce-start">Starts</label>
                <input type="time" class="form-control" id="ce-start" name="start_time" value="<?= html_escape($start) ?>" <?= $allDay ? 'disabled' : '' ?>>
            </div>
            <div class="col-6 col-md-3" data-times>
                <label class="form-label" for="ce-end">Ends (optional)</label>
                <input type="time" class="form-control" id="ce-end" name="end_time" value="<?= html_escape($end) ?>" <?= $allDay ? 'disabled' : '' ?>>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="ce-where">Where (optional)</label>
                <input type="text" class="form-control" id="ce-where" name="location" maxlength="200" value="<?= html_escape($v('location')) ?>" placeholder="e.g. Main hall, or Online">
            </div>
            <div class="col-12">
                <label class="form-label" for="ce-link">Online meeting link (optional)</label>
                <input type="url" class="form-control" id="ce-link" name="meeting_link" maxlength="500" value="<?= html_escape($v('meeting_link')) ?>" placeholder="https://meet.google.com/…  or a Zoom / WhatsApp link">
            </div>
            <div class="col-12">
                <label class="form-label" for="ce-desc">Details (optional)</label>
                <textarea class="form-control" id="ce-desc" name="description" rows="3"><?= html_escape($v('description')) ?></textarea>
            </div>
        </div>
    </div>
    <div class="card-body border-top d-flex flex-wrap gap-2">
        <button type="submit" class="btn btn-primary"><?= $e ? 'Save changes' : 'Add to calendar' ?></button>
        <a href="<?= base_url('calendar?m=' . substr($date, 0, 7)) ?>" class="btn btn-light">Cancel</a>
        <?php if ($e): ?><button type="submit" form="del-event" class="btn btn-link text-danger-soft ms-auto">Remove event</button><?php endif; ?>
    </div>
</form>
<?php if ($e): ?>
    <form method="post" action="<?= base_url('calendar/delete/' . $e['id']) ?>" id="del-event" class="d-none" data-confirm="Remove this event from the calendar?" data-confirm-ok="Remove" data-confirm-danger></form>
<?php endif; ?>
