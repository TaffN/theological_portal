<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Course_model extends CI_Model
{
    protected $table = 'courses';

    public function __construct()
    {
        parent::__construct();
    }

    public function find($id)
    {
        return $this->db->where('id', $id)->get($this->table)->row_array();
    }

    public function all()
    {
        return $this->db->get($this->table)->result_array();
    }

    public function active_courses()
    {
        return $this->db->where('status', 'active')->get($this->table)->result_array();
    }

    public function create($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    public function update($id, $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->where('id', $id)->update($this->table, $data);
    }

    /**
     * Lecturers assigned to a course, via the course_lecturers pivot.
     */
    public function lecturers($courseId)
    {
        return $this->db
            ->select('users.id, users.name, users.email')
            ->from('course_lecturers cl')
            ->join('users', 'users.id = cl.user_id')
            ->where('cl.course_id', $courseId)
            ->get()
            ->result_array();
    }
}
