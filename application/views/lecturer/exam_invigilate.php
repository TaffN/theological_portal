<p class="mb-3"><a href="<?= base_url('lecturer_exams/view/' . $e['id']) ?>" class="back-link">&larr; <?= html_escape($e['title']) ?></a></p>

<div class="page-head">
    <div class="min-w-0">
        <h1 class="page-title">Invigilation</h1>
        <p class="page-sub mb-0"><?= html_escape($e['title']) ?> &middot; <?= html_escape($e['module_name']) ?> &middot;
            closes <?= html_escape(date('D j M, H:i', strtotime($e['closes_at']))) ?> (<?= html_escape(due_in($e['closes_at'])) ?>)</p>
    </div>
    <span class="live-dot" data-live-status>Live</span>
</div>

<div data-invigilate data-live-url="<?= base_url('lecturer_exams/live/' . $e['id']) ?>" data-interval="10">
    <?php $this->load->view('lecturer/_invigilate_rows'); ?>
</div>

<div class="card mt-4">
    <div class="card-body">
        <div class="card-head"><h5 class="card-heading"><?= icon('shield', 18) ?> Reading the flags</h5></div>
        <ul class="small text-muted mb-0 ps-3">
            <li><strong>Left the page</strong>: the student switched to another app or tab (WhatsApp, a browser, a call). A short visit can be innocent; many long ones are worth a conversation.</li>
            <li><strong>Pasted</strong>: they tried to paste text into an answer. Pasting is blocked, but the attempt is recorded.</li>
            <li><strong>Text appeared without typing</strong>: a large block of text was inserted at once, for example with a "Force paste" tool. It was undone, and the text they tried to insert is shown in their script's activity log.</li>
            <li><strong>Other device</strong>: someone tried to open this student's exam on a second phone or computer. It was blocked.</li>
            <li>If a student's phone dies, use <strong>New device</strong> so they can continue on another one. Their saved answers are kept and the timer doesn't stop.</li>
            <li>Flags are evidence, not proof. Open a student's script to see exactly what happened and when.</li>
        </ul>
    </div>
</div>
<script src="<?= base_url('assets/js/exam.js') ?>?v=2"></script>
