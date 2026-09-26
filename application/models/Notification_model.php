<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Notification_model extends CI_Model
{
    protected $table = 'notifications';

    public function __construct()
    {
        parent::__construct();
    }

    public function create($userId, $message, $link = null)
    {
        return $this->db->insert($this->table, [
            'user_id'    => $userId,
            'message'    => $message,
            'link'       => $link,
            'is_read'    => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function for_user($userId, $limit = 30)
    {
        return $this->db
            ->where('user_id', $userId)
            ->order_by('created_at', 'DESC')
            ->limit($limit)
            ->get($this->table)
            ->result_array();
    }

    public function unread_count($userId)
    {
        return $this->db
            ->where('user_id', $userId)
            ->where('is_read', 0)
            ->count_all_results($this->table);
    }

    public function mark_read($id, $userId)
    {
        // scoped to user_id too, so one user can't mark another's notification read
        return $this->db
            ->where('id', $id)
            ->where('user_id', $userId)
            ->update($this->table, ['is_read' => 1]);
    }

    public function mark_all_read($userId)
    {
        return $this->db
            ->where('user_id', $userId)
            ->where('is_read', 0)
            ->update($this->table, ['is_read' => 1]);
    }
}
