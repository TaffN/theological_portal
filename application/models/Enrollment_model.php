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
     * A student only has real access to a course once their enrollment is 'active'.
     * This is the single access-gate check the rest of the app relies on.
     */
    public function has_active_access($userId, $courseId)
    {
        $row = $this->db
            ->where('user_id', $userId)
            ->where('course_id', $courseId)
            ->where('status', 'active')
            ->get($this->table)
            ->row_array();

        return (bool) $row;
    }

    public function courses_for_student($userId)
    {
        return $this->db
            ->select('courses.*, e.status as enrollment_status, e.id as enrollment_id')
            ->from('enrollments e')
            ->join('courses', 'courses.id = e.course_id')
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
     * Students with active (paid, approved) access to a course - the exact
     * audience for "new material posted" notifications/emails.
     */
    public function active_students_for_course($courseId)
    {
        return $this->db
            ->select('users.id, users.name, users.email')
            ->from('enrollments e')
            ->join('users', 'users.id = e.user_id')
            ->where('e.course_id', $courseId)
            ->where('e.status', 'active')
            ->get()
            ->result_array();
    }
}
