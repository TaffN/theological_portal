<?php
    $suggestions = [
        'What do I have due this week?',
        'Help me plan my study time for this week',
        'Explain the Trinity in simple words',
        'What is the baptism in the Holy Spirit?',
        'How do I approach my next assignment?',
        'Explain my lecturer\'s feedback to me',
    ];
?>
<div class="page-head ezra-head">
    <div class="d-flex align-items-center gap-3 min-w-0">
        <span class="ezra-avatar ezra-avatar-lg" aria-hidden="true">E</span>
        <div class="min-w-0">
            <h1 class="page-title mb-0">Ezra</h1>
            <p class="page-sub mb-0 d-none d-md-block">Your study assistant. Ask about your courses, the Bible and theology, or how to use the portal.</p>
        </div>
    </div>
    <form method="post" action="<?= base_url('ezra/new_thread') ?>" class="<?= $messages ? '' : 'd-none' ?>" data-ezra-new>
        <button type="submit" class="btn btn-outline-primary btn-sm text-nowrap"><?= icon('plus', 16) ?> New conversation</button>
    </form>
</div>

<div class="card ezra-card">
    <div class="ezra-log" id="ezra-log" aria-live="polite">
        <?php if (! $messages): ?>
            <div class="ezra-intro" data-ezra-intro>
                <span class="ezra-avatar ezra-avatar-lg mb-3 d-none d-md-inline-grid" aria-hidden="true">E</span>
                <h2 class="h5 fw-bold mb-1"><?= html_escape(greeting()) ?>, <?= html_escape($firstName) ?>. I'm Ezra.</h2>
                <p class="text-muted small mb-3">I know your courses, due dates, exams and published marks, and I follow the college's statement of faith.
                    I can explain, guide and quiz you, but I won't write your assignments or answer exam questions for you.</p>
                <?php if ($canAsk): ?>
                    <div class="ezra-chips">
                        <?php foreach ($suggestions as $i => $s): ?>
                            <button type="button" class="ezra-chip<?= $i >= 4 ? ' d-none d-md-inline-block' : '' ?>" data-ezra-suggest><?= html_escape($s) ?></button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <?php foreach ($messages as $m): ?>
                <?php if ($m['role'] === 'user'): ?>
                    <div class="ezra-msg is-user"><div class="ezra-bubble"><?= nl2br(html_escape($m['content'])) ?></div></div>
                <?php else: ?>
                    <div class="ezra-msg is-ezra<?= $m['status'] !== 'ok' ? ' is-problem' : '' ?>">
                        <span class="ezra-avatar" aria-hidden="true">E</span>
                        <div class="ezra-bubble"><?= $m['content'] === '' ? '<em class="text-muted">(removed after the retention period)</em>' : ezra_format($m['content']) ?></div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="ezra-compose">
        <?php if (! $canAsk): ?>
            <div class="notice notice-info mb-0"><?= icon('clock', 16) ?> <span><?= html_escape($reason) ?></span></div>
        <?php else: ?>
            <form method="post" action="<?= base_url('ezra/ask') ?>" data-ezra-form>
                <div class="ezra-input">
                    <textarea name="question" rows="1" maxlength="<?= (int) $maxLength ?>" required placeholder="Ask Ezra a question..." aria-label="Your question" data-ezra-input></textarea>
                    <button type="submit" class="btn btn-primary ezra-send" aria-label="Send"><?= icon('send', 18) ?></button>
                </div>
            </form>
            <div class="ezra-foot">
                <span>Ezra can make mistakes. Check important things with your lecturer and your Bible.</span>
                <?php if ($left !== null): ?><span class="text-nowrap" data-ezra-left><?= (int) $left ?> question<?= $left == 1 ? '' : 's' ?> left today</span><?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="<?= base_url('assets/js/ezra.js') ?>?v=1"></script>
