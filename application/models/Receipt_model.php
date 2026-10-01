<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Receipt_model extends CI_Model
{
    public function find($paymentId)
    {
        return $this->db
            ->select('payments.*, users.id AS student_id, users.name AS student_name, users.email AS student_email, users.phone AS student_phone, users.id_number AS student_id_number,
                      programs.name AS program_name, programs.duration_text, lm.name AS legacy_module_name, reviewer.name AS approved_by,
                      (SELECT COUNT(*) FROM modules mc WHERE mc.program_id = programs.id) AS module_count', false)
            ->from('payments')
            ->join('program_enrollments pe', 'pe.id = payments.program_enrollment_id')
            ->join('users', 'users.id = pe.user_id')
            ->join('programs', 'programs.id = pe.program_id')
            ->join('enrollments le', 'le.id = payments.enrollment_id', 'left')
            ->join('modules lm', 'lm.id = le.module_id', 'left')
            ->join('users reviewer', 'reviewer.id = payments.reviewed_by', 'left')
            ->where('payments.id', (int) $paymentId)
            ->get()->row_array();
    }
}
