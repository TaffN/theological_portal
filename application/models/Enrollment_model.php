<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Enrollment_model extends CI_Model
{
    protected $table = 'enrollments';

    public function __construct()
    {
        parent::__construct();
    }

    public function find($id)
    {
        return $this->db->where('id', $id)->get($this->table)->row_array();
    }

    public function create($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    /**
     * A student only has real access to a module once their enrollment is 'active'.
     * This is the single access-gate check the rest of the app relies on.
     */
    public function has_active_access($userId, $moduleId)
    {
        $row = $this->db
            ->where('user_id', $userId)
            ->where('module_id', $moduleId)
            ->where('status', 'active')
            ->get($this->table)
            ->row_array();

        return (bool) $row;
    }

    public function modules_for_student($userId)
    {
        return $this->db
            ->select('modules.*, programs.name AS program_name, programs.slug AS program_slug, e.status as enrollment_status, e.id as enrollment_id')
            ->from('enrollments e')
            ->join('modules', 'modules.id = e.module_id')
            ->join('programs', 'programs.id = modules.program_id')
            ->where('e.user_id', $userId)
            ->get()
            ->result_array();
    }

    public function activate($enrollmentId)
    {
        return $this->db->where('id', $enrollmentId)->update($this->table, [
            'status'      => 'active',
            'enrolled_at' => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Students with active (paid, approved) access to a module - the exact
     * audience for "new material posted" notifications/emails.
     */
    public function active_students_for_module($moduleId)
    {
        return $this->db
            ->select('users.id, users.name, users.email')
            ->from('enrollments e')
            ->join('users', 'users.id = e.user_id')
            ->where('e.module_id', $moduleId)
            ->where('e.status', 'active')
            ->get()
            ->result_array();
    }
}
