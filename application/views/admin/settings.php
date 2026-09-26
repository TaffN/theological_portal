<?php
    $icons = ['Organisation' => ['layers', 'bg-soft-navy'], 'Contact' => ['phone', 'bg-soft-green'], 'Address' => ['grid', 'bg-soft-blue'],
              'Payments' => ['card', 'bg-soft-gold'], 'Documents' => ['file', 'bg-soft-red'], 'Results' => ['award', 'bg-soft-green']];
    $hints = [
        'Organisation' => 'Shown in the menu, on the login page, receipts and ID cards.',
        'Contact'      => 'Shown on the Help page, receipts, the back of ID cards and the ID verification page.',
        'Address'      => 'Printed on receipts and ID cards.',
        'Payments'     => 'Shown to students on the "Submit proof of payment" page.',
        'Documents'    => 'Wording used on generated documents.',
        'Results'      => 'Grade boundaries used when lecturers publish course results, and the note on statements of results.',
    ];
?>
<div class="page-head">
    <div>
        <h1 class="page-title">Organisation settings</h1>
        <p class="page-sub">Your institution's details, used across the portal, receipts and ID cards. Changes apply immediately.</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-3 d-none d-lg-block">
        <nav class="settings-nav">
            <?php foreach ($groups as $name => $fields): ?>
                <a href="#set-<?= strtolower($name) ?>"><?= icon($icons[$name][0], 16) ?> <?= $name ?></a>
            <?php endforeach; ?>
        </nav>
    </div>
    <div class="col-lg-9">
        <form method="post" action="<?= base_url('admin_settings/save') ?>">
            <?php foreach ($groups as $name => $fields): ?>
                <div class="card mb-3" id="set-<?= strtolower($name) ?>">
                    <div class="card-body">
                        <div class="card-head">
                            <h5 class="card-heading"><span class="stat-icon stat-icon-sm <?= $icons[$name][1] ?>"><?= icon($icons[$name][0], 16) ?></span> <?= $name ?></h5>
                            <span class="card-sub"><?= $hints[$name] ?></span>
                        </div>
                        <div class="row g-3">
                            <?php foreach ($fields as $key => $meta): ?>
                                <?php $val = isset($values[$key]) ? $values[$key] : ''; ?>
                                <div class="<?= $meta[2] === 'textarea' ? 'col-12' : 'col-md-6' ?>">
                                    <label class="form-label" for="s-<?= $key ?>"><?= html_escape($meta[0]) ?></label>
                                    <?php if ($meta[2] === 'textarea'): ?>
                                        <textarea id="s-<?= $key ?>" name="<?= $key ?>" rows="6" class="form-control"><?= html_escape($val) ?></textarea>
                                    <?php else: ?>
                                        <input type="<?= $meta[2] ?>" id="s-<?= $key ?>" name="<?= $key ?>" class="form-control" value="<?= html_escape($val) ?>"
                                               placeholder="<?= html_escape($meta[1]) ?>" <?= $meta[2] === 'number' ? ($name === 'Results' ? 'min="1" max="100" step="0.5"' : 'min="1" max="120"') : '' ?> <?= $key === 'org_initials' ? 'maxlength="3"' : '' ?>>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="save-bar">
                <span class="text-muted small d-none d-sm-inline">Every change is recorded in the audit trail.</span>
                <button type="submit" class="btn btn-primary ms-auto">Save settings</button>
            </div>
        </form>
    </div>
</div>
