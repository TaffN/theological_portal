<?php
/**
 * v10 dashboard row for students and lecturers: what's coming up in the
 * calendar and what's happening in Discussions. Reads its own data.
 */
$CI =& get_instance();
if ($CI->db->table_exists('calendar_events') && $CI->db->table_exists('discussions')):
    $CI->load->model(['Calendar_model', 'Discussion_model', 'Course_model']);
    $uid  = (int) $CI->session->userdata('user_id');
    $role = $CI->session->userdata('role');
    $cids = $CI->Course_model->ids_for_user($uid, $role);
    $seen = [];   // a holiday week is one line, not one per day
    $events = array_values(array_filter($CI->Calendar_model->upcoming($cids, 14, 30), function ($it) use (&$seen) {
        if ($it['id'] === null || isset($seen[$it['id']])) { return false; }
        return $seen[$it['id']] = true;
    }));
    $events = array_slice($events, 0, 5);
    $talk = $role === 'lecturer' ? $CI->Discussion_model->unanswered($cids, 5) : $CI->Discussion_model->topics($cids, null, '', 5);
?>
<div class="row g-3 mt-1">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="card-head">
                    <h5 class="card-heading"><?= icon('calendar', 18) ?> Coming up</h5>
                    <a href="<?= base_url('calendar') ?>" class="card-link-sm">Calendar <?= icon('arrow', 14) ?></a>
                </div>
                <?php if (! $events): ?>
                    <div class="empty-state">No classes or events in the next two weeks.</div>
                <?php else: ?>
                    <ul class="due-list">
                    <?php foreach ($events as $e): ?>
                        <li>
                            <div class="due-date"><strong><?= date('j', strtotime($e['date'])) ?></strong><small><?= date('M', strtotime($e['date'])) ?></small></div>
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-semibold text-truncate"><?= html_escape($e['title']) ?></div>
                                <small class="text-muted"><?= $e['time'] ? html_escape(date('D', strtotime($e['date'])) . ' ' . $e['time']) : 'All day' ?> &middot; <?= html_escape($e['course'] ?: 'Whole college') ?></small>
                            </div>
                            <?php if ($e['link']): ?><a href="<?= html_escape($e['link']) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">Join</a><?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="card-head">
                    <h5 class="card-heading"><?= icon('chat', 18) ?> <?= $role === 'lecturer' ? 'Questions waiting for a reply' : 'Latest discussions' ?></h5>
                    <a href="<?= base_url('discussions') ?>" class="card-link-sm">Discussions <?= icon('arrow', 14) ?></a>
                </div>
                <?php if (! $talk): ?>
                    <div class="empty-state"><?= $role === 'lecturer' ? 'Every question has a reply.' : 'No discussions yet. Ask the first question!' ?></div>
                <?php else: ?>
                    <ul class="people-list">
                    <?php foreach ($talk as $t): ?>
                        <li>
                            <span class="mini-icon bg-soft-blue"><?= icon('chat', 16) ?></span>
                            <div class="flex-grow-1 min-w-0">
                                <a href="<?= base_url('discussions/view/' . $t['id']) ?>" class="fw-semibold text-truncate d-block text-reset text-decoration-none"><?= html_escape($t['title']) ?></a>
                                <small class="text-muted"><?= html_escape($t['author_name']) ?> &middot; <?= html_escape($t['course_name'] ?: 'General') ?><?= isset($t['reply_count']) ? ' &middot; ' . (int) $t['reply_count'] . ' repl' . ($t['reply_count'] == 1 ? 'y' : 'ies') : '' ?></small>
                            </div>
                        </li>
                    <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
