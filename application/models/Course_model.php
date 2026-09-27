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

    /**
     * The courses a person can see in the shared modules (discussions,
     * calendar, library, attendance): a student's paid-up courses, a
     * lecturer's assigned courses, or every course for an administrator.
     */
    public function for_user($userId, $role)
    {
        if ($role === 'admin') {
            return $this->db->order_by('name')->get($this->table)->result_array();
        }
        if ($role === 'lecturer') {
            return $this->db->select('courses.*')->from('course_lecturers cl')->join('courses', 'courses.id = cl.course_id')
                ->where('cl.user_id', $userId)->order_by('courses.name')->get()->result_array();
        }
        return $this->db->select('courses.*')->from('enrollments e')->join('courses', 'courses.id = e.course_id')
            ->where('e.user_id', $userId)->where('e.status', 'active')->order_by('courses.name')->get()->result_array();
    }

    /** Just the ids of for_user(). */
    public function ids_for_user($userId, $role)
    {
        return array_map('intval', array_column($this->for_user($userId, $role), 'id'));
    }

    /** May this person see this course in the shared modules? */
    public function user_can_see($courseId, $userId, $role)
    {
        return in_array((int) $courseId, $this->ids_for_user($userId, $role), true);
    }

    /** May this person run this course (add events, take registers, moderate)? Lecturers on it and admins. */
    public function user_can_manage($courseId, $userId, $role)
    {
        if ($role === 'admin') {
            return true;
        }
        return $role === 'lecturer' && $this->db->where('course_id', $courseId)->where('user_id', $userId)->count_all_results('course_lecturers') > 0;
    }
}
