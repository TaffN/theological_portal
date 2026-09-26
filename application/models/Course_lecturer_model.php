<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Course_lecturer_model extends CI_Model
{
    protected $table = 'course_lecturers';

    public function __construct()
    {
        parent::__construct();
    }

    public function assign($courseId, $userId)
    {
        $existing = $this->db
            ->where('course_id', $courseId)
            ->where('user_id', $userId)
            ->get($this->table)
            ->row_array();

        if ($existing) {
            return true; // already assigned
        }

        return $this->db->insert($this->table, [
            'course_id'  => $courseId,
            'user_id'    => $userId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function courses_for_lecturer($userId)
    {
        return $this->db
            ->select('courses.*')
            ->from('course_lecturers cl')
            ->join('courses', 'courses.id = cl.course_id')
            ->where('cl.user_id', $userId)
            ->get()
            ->result_array();
    }

    /**
     * Is this lecturer actually assigned to this course? Used to stop a
     * lecturer posting materials into a course they don't teach.
     */
    public function is_assigned($courseId, $userId)
    {
        $row = $this->db
            ->where('course_id', $courseId)
            ->where('user_id', $userId)
            ->get($this->table)
            ->row_array();

        return (bool) $row;
    }

    public function unassign($courseId, $userId)
    {
        return $this->db
            ->where('course_id', $courseId)
            ->where('user_id', $userId)
            ->delete($this->table);
    }
}
