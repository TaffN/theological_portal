<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Discussion boards, for every role.
 *   discussions               - topics on the boards you can see (?board=general|{module id}, ?q=search)
 *   discussions/create        - POST: new topic
 *   discussions/view/{id}     - a topic and its replies
 *   discussions/reply/{id}    - POST
 *   discussions/delete/{id}, delete_reply/{id}, pin/{id}, lock/{id} - POST
 *
 * Students see the General board and their paid-up modules; lecturers their
 * modules (and moderate them); administrators everything.
 */
class Discussions extends Auth_Controller
{
    const MAX_BODY = 5000;

    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Discussion_model', 'Module_model', 'Module_lecturer_model']);
        $this->load->library('notifier');
        $this->load->helper('ui');
        if (! $this->db->table_exists('discussions')) {   // code updated, database not yet (visit /migrate)
            $this->session->set_flashdata('error', 'Discussions need a database update first. An administrator should run the latest update (/migrate).');
            redirect('dashboard');
        }
    }

    public function index()
    {
        $modules = $this->Module_model->for_user($this->current_user_id, $this->current_role);
        $board   = (string) $this->input->get('board');
        if ($board !== 'general' && $board !== '' && ! in_array((int) $board, array_map('intval', array_column($modules, 'id')), true)) {
            $board = '';
        }
        $q = trim((string) $this->input->get('q'));

        $this->load->view('templates/header', ['title' => 'Discussions']);
        $this->load->view('discussions/index', [
            'modules'  => $modules,
            'board'    => $board,
            'q'        => $q,
            'topics'   => $this->can_use_general() || $modules ? $this->Discussion_model->topics(array_column($modules, 'id'), $board ?: null, $q) : [],
            'general'  => $this->can_use_general(),
        ]);
        $this->load->view('templates/footer');
    }

    public function create()
    {
        if ($this->input->method() !== 'post') {
            return redirect('discussions');
        }
        $board = (string) $this->input->post('board');
        $title = trim((string) $this->input->post('title'));
        $body  = trim((string) $this->input->post('body'));
        $moduleId = $board === 'general' ? null : (int) $board;

        if ($moduleId === null ? ! $this->can_use_general() : ! $this->Module_model->user_can_see($moduleId, $this->current_user_id, $this->current_role)) {
            $this->session->set_flashdata('error', 'You can\'t post on that board.');
            return redirect('discussions');
        }
        if ($title === '' || mb_strlen($title) > 200 || $body === '' || mb_strlen($body) > self::MAX_BODY) {
            $this->session->set_flashdata('error', 'Please give your topic a title (up to 200 characters) and a message (up to ' . self::MAX_BODY . ' characters).');
            return redirect('discussions' . ($board ? '?board=' . $board : ''));
        }

        $id = $this->Discussion_model->create($moduleId, $this->current_user_id, $title, $body);
        $boardName = $moduleId ? $this->module_name($moduleId) : 'General';
        $this->audit->log('discussion.created', 'discussion', $id, 'Started the topic "' . $title . '" on ' . $boardName);

        // Tell the people who should know.
        $name = $this->session->userdata('name');
        if ($moduleId && $this->current_role !== 'student') {
            $this->notifier->notify_module($moduleId, $name . ' started a discussion in ' . $boardName . ': "' . $title . '"', base_url('discussions/view/' . $id));
        } elseif ($moduleId) {
            foreach ($this->Module_model->lecturers($moduleId) as $l) {
                $this->notifier->notify_user($l['id'], $name . ' asked in ' . $boardName . ': "' . $title . '"', base_url('discussions/view/' . $id));
            }
        }

        $this->session->set_flashdata('success', 'Your topic is posted.');
        redirect('discussions/view/' . $id);
    }

    public function view($id = null)
    {
        $topic = $this->find_topic($id);
        $this->load->view('templates/header', ['title' => $topic['title']]);
        $this->load->view('discussions/view', [
            'topic'    => $topic,
            'replies'  => $this->Discussion_model->replies($topic['id']),
            'moderate' => $this->can_moderate($topic),
            'maxBody'  => self::MAX_BODY,
        ]);
        $this->load->view('templates/footer');
    }

    public function reply($id = null)
    {
        $topic = $this->find_topic($id);
        if ($this->input->method() !== 'post') {
            return redirect('discussions/view/' . $topic['id']);
        }
        if ($topic['is_locked'] && ! $this->can_moderate($topic)) {
            $this->session->set_flashdata('error', 'This topic is closed for replies.');
            return redirect('discussions/view/' . $topic['id']);
        }
        $body = trim((string) $this->input->post('body'));
        if ($body === '' || mb_strlen($body) > self::MAX_BODY) {
            $this->session->set_flashdata('error', 'Please write a reply (up to ' . self::MAX_BODY . ' characters).');
            return redirect('discussions/view/' . $topic['id'] . '#reply');
        }

        $replyId = $this->Discussion_model->add_reply($topic['id'], $this->current_user_id, $body);
        $this->audit->log('discussion.replied', 'discussion', $topic['id'], 'Replied to "' . $topic['title'] . '"');

        $name = $this->session->userdata('name');
        foreach ($this->Discussion_model->participants($topic['id']) as $uid) {
            if ($uid !== (int) $this->current_user_id) {
                $this->notifier->notify_user($uid, $name . ' replied to "' . $topic['title'] . '"', base_url('discussions/view/' . $topic['id'] . '#r' . $replyId));
            }
        }
        redirect('discussions/view/' . $topic['id'] . '#r' . $replyId);
    }

    public function delete($id = null)
    {
        $topic = $this->find_topic($id);
        if ($this->input->method() !== 'post' || ! ($this->can_moderate($topic) || (int) $topic['user_id'] === (int) $this->current_user_id)) {
            return redirect('discussions/view/' . $topic['id']);
        }
        $this->Discussion_model->delete($topic['id']);
        $this->audit->log('discussion.deleted', 'discussion', $topic['id'], 'Deleted the topic "' . $topic['title'] . '" (' . $topic['reply_count'] . ' replies)');
        $this->session->set_flashdata('success', 'The topic was deleted.');
        redirect('discussions' . ($topic['module_id'] ? '?board=' . $topic['module_id'] : '?board=general'));
    }

    public function delete_reply($replyId = null)
    {
        $reply = $this->Discussion_model->find_reply($replyId);
        if (! $reply) {
            show_404();
        }
        $topic = $this->find_topic($reply['discussion_id']);
        if ($this->input->method() === 'post' && ($this->can_moderate($topic) || (int) $reply['user_id'] === (int) $this->current_user_id)) {
            $this->Discussion_model->delete_reply($reply);
            $this->audit->log('discussion.reply_deleted', 'discussion', $topic['id'], 'Deleted a reply in "' . $topic['title'] . '"');
            $this->session->set_flashdata('success', 'The reply was deleted.');
        }
        redirect('discussions/view/' . $topic['id']);
    }

    public function pin($id = null)
    {
        $this->toggle($id, 'is_pinned', 'Pinned', 'Unpinned');
    }

    public function lock($id = null)
    {
        $this->toggle($id, 'is_locked', 'Closed replies on', 'Reopened');
    }

    /* ------------------------------------------------------------------ */

    private function toggle($id, $field, $onWord, $offWord)
    {
        $topic = $this->find_topic($id);
        if ($this->input->method() === 'post' && $this->can_moderate($topic)) {
            $value = ! $topic[$field];
            $this->Discussion_model->set_flag($topic['id'], $field, $value);
            $this->audit->log('discussion.' . ($field === 'is_pinned' ? 'pinned' : 'locked'), 'discussion', $topic['id'], ($value ? $onWord : $offWord) . ' "' . $topic['title'] . '"');
        }
        redirect('discussions/view/' . $topic['id']);
    }

    /** The topic, if this person may see its board; otherwise 404. */
    private function find_topic($id)
    {
        $topic = $this->Discussion_model->find($id);
        if (! $topic) {
            show_404();
        }
        $ok = $topic['module_id'] === null ? $this->can_use_general()
            : $this->Module_model->user_can_see($topic['module_id'], $this->current_user_id, $this->current_role);
        if (! $ok) {
            show_404();
        }
        return $topic;
    }

    /** Staff always; students once they have at least one paid-up module. */
    private function can_use_general()
    {
        return $this->current_role !== 'student' || count($this->Module_model->ids_for_user($this->current_user_id, 'student')) > 0;
    }

    /** Admins everywhere; lecturers on their modules' boards and on General. */
    private function can_moderate($topic)
    {
        if ($this->current_role === 'admin') {
            return true;
        }
        if ($this->current_role !== 'lecturer') {
            return false;
        }
        return $topic['module_id'] === null || $this->Module_lecturer_model->is_assigned($topic['module_id'], $this->current_user_id);
    }

    private function module_name($moduleId)
    {
        $c = $this->Module_model->find($moduleId);
        return $c ? $c['name'] : 'a module';
    }
}
