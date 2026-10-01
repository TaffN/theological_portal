<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Discussion boards: one per module, plus the college-wide "General" board
 * (module_id NULL) that every student with a paid-up module and all staff can use.
 */
class Discussion_model extends CI_Model
{
    /** Topics on the boards this person can see, newest activity first (pinned first). */
    public function topics(array $moduleIds, $moduleId = null, $search = '', $limit = 50)
    {
        $this->db->select('d.*, u.name AS author_name, u.role AS author_role, u.id_number, u.photo_path, u.photo_updated_at, c.name AS module_name', false)
            ->from('discussions d')
            ->join('users u', 'u.id = d.user_id')
            ->join('modules c', 'c.id = d.module_id', 'left');

        if ($moduleId === 'general') {
            $this->db->where('d.module_id IS NULL', null, false);
        } elseif ($moduleId) {
            $this->db->where('d.module_id', (int) $moduleId);
        } else {
            $this->db->group_start()->where('d.module_id IS NULL', null, false);
            if ($moduleIds) {
                $this->db->or_where_in('d.module_id', $moduleIds);
            }
            $this->db->group_end();
        }
        if ($search !== '') {
            $this->db->group_start()->like('d.title', $search)->or_like('d.body', $search)->group_end();
        }
        return $this->db->order_by('d.is_pinned', 'DESC')->order_by('d.last_activity_at', 'DESC')->limit($limit)->get()->result_array();
    }

    public function find($id)
    {
        return $this->db->select('d.*, u.name AS author_name, u.role AS author_role, u.id_number, u.photo_path, u.photo_updated_at, c.name AS module_name', false)
            ->from('discussions d')->join('users u', 'u.id = d.user_id')->join('modules c', 'c.id = d.module_id', 'left')
            ->where('d.id', (int) $id)->get()->row_array();
    }

    public function replies($discussionId)
    {
        return $this->db->select('r.*, u.name AS author_name, u.role AS author_role, u.photo_path, u.photo_updated_at')
            ->from('discussion_replies r')->join('users u', 'u.id = r.user_id')
            ->where('r.discussion_id', (int) $discussionId)->order_by('r.id', 'ASC')->get()->result_array();
    }

    public function find_reply($id)
    {
        return $this->db->where('id', (int) $id)->get('discussion_replies')->row_array();
    }

    public function create($moduleId, $userId, $title, $body)
    {
        $now = date('Y-m-d H:i:s');
        $this->db->insert('discussions', [
            'module_id' => $moduleId ?: null, 'user_id' => $userId, 'title' => $title, 'body' => $body,
            'last_activity_at' => $now, 'created_at' => $now, 'updated_at' => $now,
        ]);
        return $this->db->insert_id();
    }

    public function add_reply($discussionId, $userId, $body)
    {
        $now = date('Y-m-d H:i:s');
        $this->db->insert('discussion_replies', ['discussion_id' => $discussionId, 'user_id' => $userId, 'body' => $body, 'created_at' => $now]);
        $id = $this->db->insert_id();
        $this->recount($discussionId, $now);
        return $id;
    }

    public function delete_reply($reply)
    {
        $this->db->where('id', $reply['id'])->delete('discussion_replies');
        $this->recount($reply['discussion_id']);
    }

    public function delete($id)
    {
        $this->db->where('id', (int) $id)->delete('discussions');   // replies go with it (foreign key cascade)
    }

    public function set_flag($id, $field, $value)
    {
        if (in_array($field, ['is_pinned', 'is_locked'], true)) {
            $this->db->where('id', (int) $id)->update('discussions', [$field => $value ? 1 : 0, 'updated_at' => date('Y-m-d H:i:s')]);
        }
    }

    /** Everyone who took part in a topic (author + repliers), for "new reply" alerts. */
    public function participants($discussionId)
    {
        $d = $this->db->select('user_id')->where('id', (int) $discussionId)->get('discussions')->row_array();
        $ids = array_column($this->db->distinct()->select('user_id')->where('discussion_id', (int) $discussionId)->get('discussion_replies')->result_array(), 'user_id');
        if ($d) {
            $ids[] = $d['user_id'];
        }
        return array_values(array_unique(array_map('intval', $ids)));
    }

    /** Topics in these modules with no reply yet (for the lecturer's dashboard and Ezra). */
    public function unanswered(array $moduleIds, $limit = 10)
    {
        if (! $moduleIds) {
            return [];
        }
        return $this->db->select('d.id, d.title, d.created_at, c.name AS module_name, u.name AS author_name')
            ->from('discussions d')->join('modules c', 'c.id = d.module_id')->join('users u', 'u.id = d.user_id')
            ->where_in('d.module_id', $moduleIds)->where('d.reply_count', 0)
            ->order_by('d.created_at', 'DESC')->limit($limit)->get()->result_array();
    }

    private function recount($discussionId, $activity = null)
    {
        $count = $this->db->where('discussion_id', (int) $discussionId)->count_all_results('discussion_replies');
        $data = ['reply_count' => $count];
        if ($activity) {
            $data['last_activity_at'] = $activity;
        }
        $this->db->where('id', (int) $discussionId)->update('discussions', $data);
    }
}
