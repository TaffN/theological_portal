<?php
    $CI =& get_instance();
    $me = (int) $CI->session->userdata('user_id');
    $role = $CI->session->userdata('role');
    $icons = ['Books' => 'book', 'Articles' => 'file', 'Commentaries' => 'book', 'Sermons' => 'message', 'Theses' => 'award', 'Audio' => 'headphones', 'Video' => 'video', 'Other' => 'folder'];
    $link = function ($over) use ($q, $category, $moduleId) {
        $p = array_filter(array_merge(['q' => $q, 'category' => $category, 'module' => $moduleId ?: ''], $over), 'strlen');
        return base_url('library') . ($p ? '?' . http_build_query($p) : '');
    };
?>
<div class="page-head">
    <div>
        <h1 class="page-title">Library</h1>
        <p class="page-sub">Books, commentaries, articles, sermons and recordings for every student. Your module notes are under <a href="<?= base_url($role === 'student' ? 'student_materials' : ($role === 'lecturer' ? 'lecturer_materials' : 'admin_modules')) ?>">Materials</a>.</p>
    </div>
    <?php if ($canUpload): ?>
        <button type="button" class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#lib-add" aria-expanded="false"><?= icon('upload', 16) ?> Add to library</button>
    <?php endif; ?>
</div>

<?php if (! $allowed): ?>
    <div class="card"><div class="empty-state">
        <span class="empty-icon bg-soft-navy"><?= icon('book', 28) ?></span><br>
        The library opens once your first module is paid up and approved.
    </div></div>
<?php else: ?>

<?php if ($canUpload): ?>
<div class="collapse mb-4" id="lib-add">
    <form method="post" action="<?= base_url('library/upload') ?>" enctype="multipart/form-data" class="card">
        <div class="card-body">
            <div class="card-head"><h5 class="card-heading"><?= icon('upload', 18) ?> Add to the library</h5></div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="lb-title">Title</label>
                    <input type="text" class="form-control" id="lb-title" name="title" maxlength="200" required placeholder="e.g. Systematic Theology, Volume 1">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="lb-author">Author / speaker (optional)</label>
                    <input type="text" class="form-control" id="lb-author" name="author" maxlength="200">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label" for="lb-cat">Category</label>
                    <select class="form-select" id="lb-cat" name="category"><?php foreach ($categories as $c): ?><option><?= $c ?></option><?php endforeach; ?></select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label" for="lb-module">Recommended for (optional)</label>
                    <select class="form-select" id="lb-module" name="module_id"><option value="">Any module</option>
                        <?php foreach ($modules as $c): ?><option value="<?= (int) $c['id'] ?>"><?= html_escape(module_label($c)) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="lb-file">File (<?= html_escape($types) ?>; up to <?= (int) $maxMb ?> MB)</label>
                    <input type="file" class="form-control" id="lb-file" name="file">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="lb-link">…or a link (optional)</label>
                    <input type="url" class="form-control" id="lb-link" name="external_link" maxlength="500" placeholder="https://…">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="lb-desc">Description (optional)</label>
                    <input type="text" class="form-control" id="lb-desc" name="description" maxlength="1000" placeholder="What it is and who it helps">
                </div>
            </div>
        </div>
        <div class="card-body border-top"><button type="submit" class="btn btn-primary">Add to library</button>
            <small class="text-muted ms-2">Only share material the Center is allowed to share.</small></div>
    </form>
</div>
<?php endif; ?>

<div class="board-bar mb-3">
    <nav class="tabs tabs-scroll" aria-label="Categories">
        <a href="<?= $link(['category' => '']) ?>" class="tab <?= $category === '' ? 'active' : '' ?>">All <span class="count-pill"><?= array_sum($counts) ?></span></a>
        <?php foreach ($categories as $c): if (empty($counts[$c])) { continue; } ?>
            <a href="<?= $link(['category' => $c]) ?>" class="tab <?= $category === $c ? 'active' : '' ?>"><?= $c ?> <span class="count-pill"><?= (int) $counts[$c] ?></span></a>
        <?php endforeach; ?>
    </nav>
    <form method="get" action="<?= base_url('library') ?>" class="board-search d-flex gap-2">
        <?php if ($category): ?><input type="hidden" name="category" value="<?= html_escape($category) ?>"><?php endif; ?>
        <select name="module" class="form-select form-select-sm" onchange="this.form.submit()" aria-label="Module">
            <option value="">All modules</option>
            <?php foreach ($modules as $c): ?><option value="<?= (int) $c['id'] ?>" <?= (int) $c['id'] === $moduleId ? 'selected' : '' ?>><?= html_escape(module_label($c)) ?></option><?php endforeach; ?>
        </select>
        <input type="search" name="q" class="form-control form-control-sm" placeholder="Title, author…" value="<?= html_escape($q) ?>" aria-label="Search the library">
    </form>
</div>

<?php if (! $files): ?>
    <div class="card"><div class="empty-state">
        <span class="empty-icon bg-soft-navy"><?= icon('book', 28) ?></span><br>
        <?= $q !== '' || $category !== '' || $moduleId ? 'Nothing matches that search.' : 'The library is empty for now.' . ($canUpload ? ' Add the first book or recording.' : '') ?>
    </div></div>
<?php else: ?>
    <div class="row g-3">
    <?php foreach ($files as $f): ?>
        <div class="col-md-6 col-xl-4">
            <div class="card h-100 lib-card">
                <div class="card-body d-flex gap-3">
                    <span class="lib-icon cat-<?= strtolower($f['category']) ?>"><?= icon(isset($icons[$f['category']]) ? $icons[$f['category']] : 'folder', 22) ?></span>
                    <div class="flex-grow-1 min-w-0">
                        <div class="fw-semibold lib-title"><?= html_escape($f['title']) ?></div>
                        <?php if ($f['author']): ?><div class="small text-muted"><?= html_escape($f['author']) ?></div><?php endif; ?>
                        <div class="small mt-1 d-flex flex-wrap gap-1 align-items-center">
                            <span class="pill pill-muted"><?= html_escape($f['category']) ?></span>
                            <?php if ($f['module_name']): ?><span class="pill pill-navy"><?= html_escape($f['module_name']) ?></span><?php endif; ?>
                        </div>
                        <?php if ($f['description']): ?><p class="small text-muted mt-2 mb-0"><?= html_escape($f['description']) ?></p><?php endif; ?>
                    </div>
                </div>
                <div class="card-body border-top py-2 d-flex align-items-center gap-2">
                    <a href="<?= base_url('library/open/' . $f['id']) ?>" class="btn btn-sm btn-outline-primary text-nowrap flex-shrink-0" <?= $f['file_path'] ? '' : 'target="_blank" rel="noopener"' ?>>
                        <?= icon($f['file_path'] ? 'download' : 'link', 14) ?> <?= $f['file_path'] ? 'Open' : 'Open link' ?>
                    </a>
                    <small class="text-muted min-w-0"><?= $f['file_path'] ? html_escape(strtoupper(pathinfo($f['file_path'], PATHINFO_EXTENSION))) . ' · ' . html_escape(bytes_fmt($f['file_size'])) . ' · ' : '' ?><?= (int) $f['downloads'] ?> opened</small>
                    <?php if ($role === 'admin' || ($role === 'lecturer' && (int) $f['uploaded_by'] === $me)): ?>
                        <form method="post" action="<?= base_url('library/delete/' . $f['id']) ?>" class="ms-auto" data-confirm="Remove &quot;<?= html_escape($f['title']) ?>&quot; from the library?" data-confirm-ok="Remove" data-confirm-danger>
                            <button class="btn btn-link btn-sm p-0 text-muted" aria-label="Remove"><?= icon('x', 15) ?></button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php endif; ?>
