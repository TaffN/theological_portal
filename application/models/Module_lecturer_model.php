<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Module_lecturer_model extends CI_Model
{
    protected $table = 'module_lecturers';

    public function __construct()
    {
        parent::__construct();
    }

    public function assign($moduleId, $userId)
    {
        $existing = $this->db
            ->where('module_id', $moduleId)
            ->where('user_id', $userId)
            ->get($this->table)
            ->row_array();

        if ($existing) {
            return true; // already assigned
        }

        return $this->db->insert($this->table, [
            'module_id'  => $moduleId,
            'user_id'    => $userId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function modules_for_lecturer($userId)
    {
        return $this->db
            ->select('modules.*')
            ->from('module_lecturers cl')
            ->join('modules', 'modules.id = cl.module_id')
            ->where('cl.user_id', $userId)
            ->get()
            ->result_array();
    }

    /**
     * Is this lecturer actually assigned to this module? Used to stop a
     * lecturer posting materials into a module they don't teach.
     */
    public function is_assigned($moduleId, $userId)
    {
        $row = $this->db
            ->where('module_id', $moduleId)
            ->where('user_id', $userId)
            ->get($this->table)
            ->row_array();

        return (bool) $row;
    }

    public function unassign($moduleId, $userId)
    {
        return $this->db
            ->where('module_id', $moduleId)
            ->where('user_id', $userId)
            ->delete($this->table);
    }
}
