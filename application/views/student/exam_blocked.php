<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card mt-3">
            <div class="card-body text-center py-5">
                <span class="empty-icon bg-soft-red mb-3"><?= icon('lock', 28) ?></span>
                <h1 class="page-title mb-2">This exam is open on another device</h1>
                <p class="text-muted mb-4">
                    "<?= html_escape($e['title']) ?>" was started on a different phone or browser, and can only be written there.
                    This attempt to open it has been recorded.
                </p>
                <p class="small text-muted mb-4">If your phone died or you had to switch devices, contact your lecturer now. They can allow this device, and your saved answers will still be there. The timer keeps running in the meantime.</p>
                <a href="<?= base_url('student_exams/take/' . $e['id']) ?>" class="btn btn-primary">Try again</a>
                <a href="<?= base_url('student_exams') ?>" class="btn btn-light">Back to exams</a>
            </div>
        </div>
    </div>
</div>
