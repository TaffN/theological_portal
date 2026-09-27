<?php
/*
 * Ezra, the floating study companion: a green button at the bottom right of
 * every signed-in page (included from templates/footer.php) that opens a small
 * chat window. Talks to api/ezra_chat (Ezra::chat) via assets/js/ezra-widget.js.
 * Always dark, whatever the portal theme. Hidden while a student writes an exam.
 */
$ezwUser = (int) $this->session->userdata('user_id');
?>
<div class="ezw" id="ezw" data-endpoint="<?= base_url('api/ezra_chat') ?>" data-user="<?= $ezwUser ?>" data-role="<?= html_escape($this->session->userdata('role')) ?>">
    <section class="ezw-window" id="ezwWindow" role="dialog" aria-label="Ezra, study companion" aria-hidden="true">
        <header class="ezw-head">
            <div class="ezw-head-title">
                <span class="ezw-head-emoji" aria-hidden="true">📜</span>
                <div>
                    <div class="ezw-name">Ezra - Study Companion</div>
                    <div class="ezw-status"><span aria-hidden="true">●</span> Online</div>
                </div>
            </div>
            <div class="ezw-head-actions">
                <button type="button" class="ezw-icon-btn" data-ezw-clear title="Start a new chat" aria-label="Start a new chat"><?= icon('edit', 16) ?></button>
                <button type="button" class="ezw-icon-btn" data-ezw-close title="Close" aria-label="Close"><?= icon('x', 18) ?></button>
            </div>
        </header>

        <div class="ezw-chips" aria-label="Quick questions">
            <button type="button" class="ezw-chip" data-ezw-ask="When are my exams, and how do I write one?">📅 Exams</button>
            <button type="button" class="ezw-chip" data-ezw-ask="How do I upload my ID?">🪪 Upload ID</button>
            <button type="button" class="ezw-chip" data-ezw-ask="Explain Romans 8 simply">📖 Romans 8</button>
        </div>

        <div class="ezw-messages" id="ezwMessages" aria-live="polite"></div>

        <form class="ezw-input" id="ezwForm" autocomplete="off">
            <input type="text" id="ezwText" maxlength="1000" placeholder="Ask Ezra anything..." aria-label="Your question">
            <button type="submit" class="ezw-send" aria-label="Send"><?= icon('arrow-up', 20) ?></button>
        </form>
    </section>

    <button type="button" class="ezw-launcher" id="ezwLauncher" aria-controls="ezwWindow" aria-expanded="false" aria-label="Ask Ezra - Your Bible Study Helper">
        <span class="ezw-launcher-icon ezw-launcher-open"><?= icon('message', 28) ?></span>
        <span class="ezw-launcher-icon ezw-launcher-close"><?= icon('x', 28) ?></span>
        <span class="ezw-tooltip" role="tooltip">Ask Ezra - Your Bible Study Helper</span>
    </button>
</div>
<script src="<?= base_url('assets/js/ezra-widget.js') ?>?v=1"></script>
