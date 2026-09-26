<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Receipt_model extends CI_Model
{
    public function find($paymentId)
    {
        return $this->db
            ->select('payments.*, users.id AS student_id, users.name AS student_name, users.email AS student_email, users.phone AS student_phone, users.id_number AS student_id_number,
                      courses.name AS course_name, courses.duration_text, reviewer.name AS approved_by')
            ->from('payments')
            ->join('enrollments', 'enrollments.id = payments.enrollment_id')
            ->join('users', 'users.id = enrollments.user_id')
            ->join('courses', 'courses.id = enrollments.course_id')
            ->join('users reviewer', 'reviewer.id = payments.reviewed_by', 'left')
            ->where('payments.id', (int) $paymentId)
            ->get()->row_array();
    }
}
