<?php $sevClass = ['critical' => 'pill-danger', 'high' => 'pill-danger', 'medium' => 'pill-warning', 'low' => 'pill-muted']; ?>
<p class="mb-3"><a href="<?= base_url('admin_errors') ?>" class="back-link">&larr; Error reports</a></p>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap gap-2 mb-2">
                    <code class="ref-code"><?= html_escape($e['reference']) ?></code>
                    <span class="pill <?= $sevClass[$e['severity']] ?>"><?= ucfirst($e['severity']) ?></span>
                    <?php $st = ['open' => ['Open', 'pill-warning'], 'resolved' => ['Resolved', 'pill-success'], 'ignored' => ['Ignored', 'pill-muted']][$e['status']]; ?>
                    <span class="pill <?= $st[1] ?>"><?= $st[0] ?></span>
                </div>
                <h1 class="h4 fw-bold mb-3"><?= html_escape($e['title']) ?></h1>
                <?php if ($e['details']): ?>
                    <pre class="code-block"><?= html_escape($e['details']) ?></pre>
                <?php endif; ?>
                <?php if ($e['admin_note']): ?>
                    <div class="notice notice-info mt-3 mb-0"><?= icon('file', 16) ?> <span><strong>Note:</strong> <?= html_escape($e['admin_note']) ?></span></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-body">
                <dl class="facts">
                    <dt>Source</dt><dd><?= $sources[$e['source']][0] ?></dd>
                    <dt>Happened</dt><dd><?= (int) $e['occurrences'] ?> time<?= $e['occurrences'] == 1 ? '' : 's' ?></dd>
                    <dt>First seen</dt><dd><?= html_escape(date('d M Y, H:i', strtotime($e['first_seen']))) ?></dd>
                    <dt>Last seen</dt><dd><?= html_escape(time_ago($e['last_seen'])) ?></dd>
                    <dt>User</dt><dd><?= $e['user_name'] ? html_escape($e['user_name']) . '<br><small class="text-muted">' . html_escape($e['user_email']) . '</small>' : '<span class="text-muted">Not logged in</span>' ?></dd>
                    <?php if ($e['url']): ?><dt>Page</dt><dd class="text-break"><a href="<?= html_escape($e['url']) ?>" target="_blank" rel="noopener"><?= html_escape(preg_replace('#^https?://[^/]+#', '', $e['url'])) ?></a></dd><?php endif; ?>
                    <?php if ($e['user_agent']): ?><dt>Device</dt><dd class="small text-muted text-break"><?= html_escape($e['user_agent']) ?></dd><?php endif; ?>
                    <?php if ($e['resolver_name']): ?><dt>Closed by</dt><dd><?= html_escape($e['resolver_name']) ?>, <?= html_escape(time_ago($e['resolved_at'])) ?></dd><?php endif; ?>
                </dl>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <form method="post" action="<?= base_url('admin_errors/update/' . $e['id']) ?>">
                    <label class="form-label">Note <?= $e['source'] === 'user' && $e['user_id'] ? '(sent to the reporter when resolved)' : '(optional)' ?></label>
                    <textarea name="note" class="form-control mb-3" rows="3" placeholder="What was the cause / what did you do?"><?= html_escape($e['admin_note']) ?></textarea>
                    <?php if ($e['status'] === 'open'): ?>
                        <button name="status" value="resolved" class="btn btn-success w-100 mb-2"><?= icon('check', 16) ?> Mark resolved</button>
                        <button name="status" value="ignored" class="btn btn-light w-100">Ignore</button>
                    <?php else: ?>
                        <button name="status" value="open" class="btn btn-outline-primary w-100">Re-open</button>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>
