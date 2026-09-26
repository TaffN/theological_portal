<?php
/*
 * Shared "About you" form, used on My Profile and on the admin's user card.
 * Expects: $formRole, $formProfile (user_profiles row), $formAction, $formNote.
 */
?>
<form method="post" action="<?= $formAction ?>" class="about-form">
    <?php foreach (profile_field_groups($formRole) as $section => $group): ?>
        <fieldset class="about-section">
            <legend><span class="stat-icon stat-icon-sm bg-soft-navy"><?= icon($group[0], 15) ?></span> <?= html_escape($section) ?></legend>
            <div class="row g-3">
            <?php foreach ($group[1] as $key => $f): ?>
                <?php
                    $val = isset($formProfile[$key]) ? (string) $formProfile[$key] : '';
                    if ($key === 'country' && $val === '') { $val = setting('org_country', 'Zimbabwe'); }
                ?>
                <div class="<?= $f[3] ?>">
                    <label class="form-label" for="pf-<?= $key ?>"><?= html_escape($f[0]) ?></label>
                    <?php if ($f[1] === 'select'): ?>
                        <select id="pf-<?= $key ?>" name="<?= $key ?>" class="form-select">
                            <?php if ($val !== '' && ! in_array($val, $f[2], true)): ?><option value="<?= html_escape($val) ?>" selected><?= html_escape($val) ?></option><?php endif; ?>
                            <?php foreach ($f[2] as $opt): ?>
                                <option value="<?= html_escape($opt) ?>" <?= $val === $opt ? 'selected' : '' ?>><?= $opt === '' ? 'Choose…' : html_escape($opt) ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php elseif ($f[1] === 'textarea'): ?>
                        <textarea id="pf-<?= $key ?>" name="<?= $key ?>" rows="3" class="form-control" placeholder="<?= html_escape($f[2]) ?>"><?= html_escape($val) ?></textarea>
                    <?php else: ?>
                        <input type="<?= $f[1] ?>" id="pf-<?= $key ?>" name="<?= $key ?>" class="form-control" value="<?= html_escape($val) ?>"
                               placeholder="<?= html_escape($f[2]) ?>" <?= $f[1] === 'date' ? 'max="' . date('Y-m-d') . '"' : '' ?>>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            </div>
        </fieldset>
    <?php endforeach; ?>
    <div class="d-flex flex-wrap align-items-center gap-3">
        <button type="submit" class="btn btn-primary">Save details</button>
        <small class="text-muted d-inline-flex align-items-center gap-1"><?= icon('lock', 14) ?> <?= $formNote ?></small>
    </div>
</form>
