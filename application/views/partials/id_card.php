<?php
/*
 * Two-sided ID card. Expects $u (user row), $cardModules, optional $profile.
 * On screen: tap/click to flip. Printed: front and back side by side.
 */
$roleLabel = ['student' => 'Student', 'lecturer' => 'Lecturer', 'admin' => 'Staff'][$u['role']];
$title     = isset($profile['title']) && $profile['title'] ? $profile['title'] . ' ' : '';
$months    = max(1, (int) setting('id_card_valid_months', '12'));
$validTo   = date('M Y', strtotime('+' . $months . ' months'));
$verifyUrl = ! empty($u['verify_token']) ? base_url('verify/' . $u['verify_token']) : '';
$orgName   = setting('org_name', 'Theological Center');
$initials  = setting('org_initials', 'TC');
$contact   = array_filter([setting('org_phone'), setting('org_email')]);
$address   = array_filter([setting('org_address'), setting('org_city'), setting('org_country')]);
?>
<div class="id-flip" data-id-flip tabindex="0" role="button" aria-label="ID card - press to see the other side">
    <div class="id-flip-inner">

        <!-- FRONT -->
        <div class="id-card id-face" id="id-card">
            <div class="id-card-top">
                <span class="brand-mark brand-mark-sm"><?= html_escape($initials) ?></span>
                <div class="min-w-0">
                    <div class="id-org"><?= html_escape(setting('org_short_name', $orgName)) ?></div>
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
                    <div class="id-name"><?= html_escape($title . $u['name']) ?></div>
                    <div class="id-label">ID number</div>
                    <div class="id-number"><?= html_escape($u['id_number'] ?: '—') ?></div>
                    <?php if (! empty($cardModules)): ?>
                        <div class="id-label"><?= $u['role'] === 'lecturer' ? 'Teaches' : 'Enrolled in' ?></div>
                        <div class="id-modules"><?= html_escape(implode(', ', array_slice($cardModules, 0, 2))) ?><?= count($cardModules) > 2 ? ' +' . (count($cardModules) - 2) : '' ?></div>
                    <?php endif; ?>
                    <div class="id-label">Valid until</div>
                    <div class="id-since"><?= html_escape($validTo) ?></div>
                </div>
            </div>
            <div class="id-card-foot">
                <span><?= $u['status'] === 'active' ? 'Issued ' . date('j M Y') : 'NOT ACTIVE' ?></span>
                <span class="id-flip-hint">Scan the back to verify <?= icon('arrow', 11) ?></span>
            </div>
        </div>

        <!-- BACK -->
        <div class="id-card id-card-back id-face">
            <div class="id-back-body">
                <div class="id-qr-wrap">
                    <?php if ($verifyUrl): ?>
                        <div class="id-qr" data-qr="<?= html_escape($verifyUrl) ?>" data-qr-margin="1"></div>
                        <div class="id-qr-caption">Scan to verify</div>
                    <?php else: ?>
                        <div class="id-qr id-qr-missing"><?= icon('alert', 22) ?><small>Run the database update to enable</small></div>
                    <?php endif; ?>
                </div>
                <div class="id-back-text min-w-0">
                    <div class="id-back-org"><?= html_escape($orgName) ?></div>
                    <?php if ($address): ?><div class="id-back-line"><?= html_escape(implode(', ', $address)) ?></div><?php endif; ?>
                    <?php if ($contact): ?><div class="id-back-line"><?= html_escape(implode(' · ', $contact)) ?></div><?php endif; ?>
                    <?php if (setting('org_website')): ?><div class="id-back-line"><?= html_escape(preg_replace('#^https?://#', '', setting('org_website'))) ?></div><?php endif; ?>
                    <div class="id-back-note"><?= html_escape(setting('id_card_note', 'If found, please return this card to the address above.')) ?></div>
                </div>
            </div>
            <div class="id-back-foot">
                <span class="id-sign">Authorised signature</span>
                <span class="id-barcode"><?= html_escape($u['id_number']) ?></span>
            </div>
        </div>

    </div>
</div>
