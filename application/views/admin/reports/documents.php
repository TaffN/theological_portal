<?php $c = $data['counts']; ?>
<div class="row g-3 mb-3">
    <div class="col-lg-5"><div class="card h-100"><div class="card-body">
        <div class="card-head"><h5 class="card-heading">All documents</h5><a href="<?= base_url('admin_documents') ?>" class="card-link-sm no-print">Verify <?= icon('arrow', 14) ?></a></div>
        <?= svg_donut_chart(['Verified', 'Waiting', 'Rejected'], [$c['verified'], $c['pending'], $c['rejected']], ['center_label' => 'Documents', 'empty' => 'No documents uploaded yet.']) ?>
        <p class="small mb-0 mt-3"><strong><?= (int) $data['missing'] ?></strong> <?= $data['missing'] == 1 ? 'person is' : 'people are' ?> still missing a required document. <a href="<?= base_url('admin_documents?tab=missing') ?>" class="no-print">See who</a></p>
    </div></div></div>
    <div class="col-lg-7"><div class="card h-100"><div class="table-responsive">
        <table class="table report-table mb-0">
            <thead><tr><th>Document</th><th class="text-end">Verified</th><th class="text-end">Waiting</th><th class="text-end">Rejected</th></tr></thead>
            <tbody><?php foreach ($data['types'] as $t): ?><tr><td><?= html_escape(isset($types[$t['doc_type']]) ? $types[$t['doc_type']] : $t['doc_type']) ?></td><td class="text-end"><?= (int) $t['verified'] ?></td><td class="text-end"><?= (int) $t['pending'] ?></td><td class="text-end"><?= (int) $t['rejected'] ?></td></tr><?php endforeach; ?>
            <?php if (! $data['types']): ?><tr><td colspan="4" class="text-muted">Nothing uploaded yet.</td></tr><?php endif; ?></tbody>
        </table>
    </div></div></div>
</div>
