<?php
    $used    = $usage['cost'];
    $pctUsed = $cap > 0 ? min(100, $used / $cap * 100) : 0;
    $barTone = $pctUsed >= 100 ? 'is-full' : ($pctUsed >= 80 ? 'is-high' : '');
    $labels  = [];
    foreach (array_keys($usage['months']) as $ym) { $labels[] = date('M', strtotime($ym . '-01')); }
    $daysIn  = (int) date('t'); $dayNow = (int) date('j');
    $projected = $dayNow ? $used / $dayNow * $daysIn : 0;
?>
<div class="page-head">
    <div>
        <h1 class="page-title">Ezra (AI assistant)</h1>
        <p class="page-sub">What Ezra costs, how much it's used, and how it behaves. Conversations are private: you see counts, not what students typed.</p>
    </div>
    <?php if ($configured): ?>
        <form method="post" action="<?= base_url('admin_ezra/test') ?>">
            <button type="submit" class="btn btn-outline-primary btn-sm"><?= icon('check', 16) ?> Test connection</button>
        </form>
    <?php endif; ?>
</div>

<?php if (! function_exists('curl_init')): ?>
    <div class="notice notice-info mb-3"><?= icon('alert', 16) ?> <span>PHP's <strong>cURL</strong> extension is switched off, and Ezra needs it to reach the AI. In XAMPP: open <code>php.ini</code> (Config button next to Apache), remove the <code>;</code> in front of <code>extension=curl</code>, save and restart Apache.</span></div>
<?php endif; ?>
<?php if (! $configured): ?>
    <div class="card mb-4">
        <div class="card-body">
            <div class="card-head"><h5 class="card-heading"><?= icon('lock', 18) ?> Ezra needs an API key</h5></div>
            <p class="small mb-2">Ezra uses Anthropic's Claude AI, paid per question from credit you buy in advance. To switch it on:</p>
            <ol class="small mb-0 ps-3">
                <li>Create an account at <strong>console.anthropic.com</strong>, add a payment card and buy credit (e.g. $20 to start). Under <em>Limits</em>, you can also set a monthly spend limit there as a second safety net.</li>
                <li>Create an <strong>API key</strong> (Settings &rarr; API keys) and copy it. Treat it like a password.</li>
                <li>On the server, copy <code>application/config/ezra.sample.php</code> to <code>application/config/ezra.php</code> and paste the key between the quotes of <code>$config['ezra_api_key']</code>.</li>
                <li>Reload this page and press <strong>Test connection</strong>.</li>
            </ol>
        </div>
    </div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-body">
                <div class="card-head">
                    <h5 class="card-heading">This month</h5>
                    <?php if ($s['enabled'] !== '1'): ?><span class="pill pill-muted">Switched off</span>
                    <?php elseif (! $configured): ?><span class="pill pill-warning">Not set up</span>
                    <?php elseif ($pctUsed >= 100): ?><span class="pill pill-danger">Limit reached</span>
                    <?php else: ?><span class="pill pill-success">Answering</span><?php endif; ?>
                </div>
                <div class="d-flex align-items-baseline gap-2 mb-1">
                    <span class="stat-value">$<?= number_format($used, 2) ?></span>
                    <span class="text-muted small">of $<?= number_format($cap, 2) ?> limit</span>
                </div>
                <div class="spend-bar <?= $barTone ?> mb-2" role="progressbar" aria-valuenow="<?= round($pctUsed) ?>" aria-valuemin="0" aria-valuemax="100"><span style="width: <?= round($pctUsed, 1) ?>%"></span></div>
                <p class="small text-muted mb-3">At this pace, about <strong>$<?= number_format($projected, 2) ?></strong> by the end of <?= date('F') ?>.
                    Ezra pauses for everyone when the limit is reached and starts again on the 1st. You get an alert at 80%.</p>
                <dl class="facts mb-0">
                    <dt>Answers</dt><dd><?= number_format($usage['answers']) ?><?= $usage['problems'] ? ' <small class="text-muted">(' . (int) $usage['problems'] . ' not answered: errors or declined)</small>' : '' ?></dd>
                    <dt>Students using it</dt><dd><?= (int) $usage['users'] ?></dd>
                    <dt>Questions today</dt><dd><?= (int) $usage['today'] ?></dd>
                    <dt>Average per answer</dt><dd><?= $usage['answers'] ? '$' . number_format($usage['avg'], 4) : '&ndash;' ?></dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-body">
                <div class="card-head">
                    <h5 class="card-heading">Spending per month</h5>
                    <span class="card-sub">Last 6 months, US dollars</span>
                </div>
                <?= svg_bar_chart($labels, array_values($usage['months']), [
                    'prefix' => '$', 'decimals' => 2, 'label' => 'Ezra spending per month', 'empty' => 'No questions asked yet.',
                ]) ?>
            </div>
        </div>
    </div>
</div>

<?php if ($usage['top']): ?>
<div class="card mb-4">
    <div class="card-body">
        <div class="card-head"><h5 class="card-heading">Most active this month</h5><span class="card-sub">Number of questions only</span></div>
        <ul class="people-list">
            <?php foreach ($usage['top'] as $t): ?>
                <li>
                    <div class="flex-grow-1 min-w-0">
                        <div class="fw-semibold text-truncate"><?= html_escape($t['name']) ?></div>
                        <small class="text-muted"><span class="id-chip"><?= html_escape($t['id_number']) ?></span> last asked <?= html_escape(time_ago($t['last_at'])) ?></small>
                    </div>
                    <span class="fw-bold"><?= (int) $t['questions'] ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
<?php endif; ?>

<form method="post" action="<?= base_url('admin_ezra/save') ?>" class="card mb-4" id="settings">
    <div class="card-body">
        <div class="card-head"><h5 class="card-heading"><?= icon('layers', 18) ?> Settings</h5></div>
        <div class="row g-3">
            <div class="col-md-6">
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" role="switch" id="ez-on" name="enabled" value="1" <?= $s['enabled'] === '1' ? 'checked' : '' ?>>
                    <label class="form-check-label fw-semibold" for="ez-on">Ezra is switched on</label>
                </div>
                <div class="form-label mb-1">Who can use Ezra</div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" id="ez-st" name="roles[]" value="student" <?= in_array('student', $s['roles'], true) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="ez-st">Students</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" id="ez-le" name="roles[]" value="lecturer" <?= in_array('lecturer', $s['roles'], true) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="ez-le">Lecturers</label>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label" for="ez-cap">Monthly limit ($)</label>
                <input type="number" class="form-control" id="ez-cap" name="monthly_cap_usd" min="0" max="10000" step="1" value="<?= html_escape($s['cap']) ?>" required>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label" for="ez-daily">Questions per person a day</label>
                <input type="number" class="form-control" id="ez-daily" name="daily_limit" min="0" max="500" step="1" value="<?= html_escape($s['daily']) ?>" required>
                <div class="form-text">0 = no daily limit</div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="ez-model">AI model</label>
                <select class="form-select" id="ez-model" name="model">
                    <?php foreach ($models as $k => $label): ?>
                        <option value="<?= html_escape($k) ?>" <?= $s['model'] === $k ? 'selected' : '' ?>><?= html_escape($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label" for="ez-effort">Answer depth</label>
                <select class="form-select" id="ez-effort" name="effort">
                    <?php foreach (['low' => 'Quick (cheapest)', 'medium' => 'Balanced', 'high' => 'Thorough (costs more)'] as $k => $label): ?>
                        <option value="<?= $k ?>" <?= $s['effort'] === $k ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label" for="ez-bible">Bible version quoted</label>
                <input type="text" class="form-control" id="ez-bible" name="bible_version" maxlength="40" value="<?= html_escape($s['bible']) ?>">
            </div>
            <div class="col-12">
                <label class="form-label" for="ez-faith">Statement of faith Ezra follows</label>
                <textarea class="form-control" id="ez-faith" name="statement_of_faith" rows="10" required minlength="20" maxlength="8000"><?= html_escape($s['faith']) ?></textarea>
                <div class="form-text">Starts from the Assemblies of God Statement of Fundamental Truths. Replace it with the Center's own wording; Ezra answers from within it and explains other views respectfully.</div>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label" for="ez-keep">Keep conversations for (days)</label>
                <input type="number" class="form-control" id="ez-keep" name="retention_days" min="0" max="3650" step="1" value="<?= html_escape($s['retention']) ?>" required>
                <div class="form-text">0 = forever. Older text is removed; costs are kept.</div>
            </div>
        </div>
    </div>
    <div class="card-body border-top d-flex flex-wrap gap-2">
        <button type="submit" class="btn btn-primary">Save settings</button>
        <button type="submit" form="purge-form" class="btn btn-outline-secondary">Remove old conversations now</button>
    </div>
</form>
<form method="post" action="<?= base_url('admin_ezra/purge') ?>" id="purge-form" class="d-none"
      data-confirm="Remove the text of all Ezra conversations older than the retention period? This can't be undone." data-confirm-ok="Remove" data-confirm-danger></form>

<p class="small text-muted">Pricing: <?= html_escape(implode(' · ', array_map(function ($m, $p) { return $m . ': $' . score_fmt($p[0]) . ' per million tokens in, $' . score_fmt($p[1]) . ' out'; }, array_keys(Ezra_ai::$prices), Ezra_ai::$prices))) ?>. A typical answer costs about 2&ndash;4 cents.</p>
