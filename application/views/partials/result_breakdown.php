<?php
    // $items: the marks a result is based on (Result_model::compute_for_module / module_results.breakdown).
    $items = isset($items) && is_array($items) ? $items : [];
?>
<?php if (empty($items)): ?>
    <p class="small text-muted mb-0 mt-2">No assignments or exams have finished yet.</p>
<?php else: ?>
    <table class="table table-sm breakdown-table mt-2 mb-0">
        <thead><tr><th>Work</th><th class="text-end">Mark</th><th class="text-end">%</th></tr></thead>
        <tbody>
        <?php foreach ($items as $it): ?>
            <tr>
                <td>
                    <span class="breakdown-type"><?= $it['type'] === 'exam' ? 'Exam' : 'Assignment' ?></span>
                    <?= html_escape($it['title']) ?>
                    <?php if (! empty($it['note'])): ?><small class="text-muted">(<?= html_escape($it['note']) ?>)</small><?php endif; ?>
                </td>
                <td class="text-end text-nowrap"><?= $it['score'] === null ? '—' : html_escape(score_fmt($it['score'])) . ($it['max'] ? '/' . html_escape(score_fmt($it['max'])) : '') ?></td>
                <td class="text-end text-nowrap"><?= pct($it['pct']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
