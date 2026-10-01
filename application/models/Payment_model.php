<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Payment_model extends CI_Model
{
    protected $table = 'payments';

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
     * Pending payments, with student name/email and module name joined in,
     * for the admin review screen.
     */
    public function pending_with_details()
    {
        return $this->db
            ->select('payments.*, enrollments.id as enrollment_id, users.name as student_name, users.email as student_email, modules.name as module_name')
            ->from('payments')
            ->join('enrollments', 'enrollments.id = payments.enrollment_id')
            ->join('users', 'users.id = enrollments.user_id')
            ->join('modules', 'modules.id = enrollments.module_id')
            ->where('payments.status', 'pending')
            ->order_by('payments.submitted_at', 'ASC')
            ->get()
            ->result_array();
    }

    public function approve($paymentId, $reviewerId)
    {
        return $this->db->where('id', $paymentId)->update($this->table, [
            'status'      => 'approved',
            'reviewed_by' => $reviewerId,
            'reviewed_at' => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);
    }

    public function reject($paymentId, $reviewerId, $note = null)
    {
        return $this->db->where('id', $paymentId)->update($this->table, [
            'status'      => 'rejected',
            'admin_note'  => $note,
            'reviewed_by' => $reviewerId,
            'reviewed_at' => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Reviewed payments (approved + rejected), newest first, for the
     * admin's History tab.
     */
    public function history($limit = 50)
    {
        return $this->db
            ->select('payments.*, users.name as student_name, users.email as student_email, modules.name as module_name, reviewer.name as reviewer_name')
            ->from('payments')
            ->join('enrollments', 'enrollments.id = payments.enrollment_id')
            ->join('users', 'users.id = enrollments.user_id')
            ->join('modules', 'modules.id = enrollments.module_id')
            ->join('users reviewer', 'reviewer.id = payments.reviewed_by', 'left')
            ->where_in('payments.status', ['approved', 'rejected'])
            ->order_by('payments.reviewed_at', 'DESC')
            ->limit($limit)
            ->get()
            ->result_array();
    }

    /**
     * Latest payment row per enrollment for one student, keyed by
     * enrollment_id - lets the module page say "rejected: <reason>".
     */
    public function latest_by_enrollment_for_student($userId)
    {
        $rows = $this->db
            ->select('payments.*')
            ->from('payments')
            ->join('enrollments', 'enrollments.id = payments.enrollment_id')
            ->where('enrollments.user_id', $userId)
            ->order_by('payments.id', 'ASC')
            ->get()
            ->result_array();

        $latest = [];
        foreach ($rows as $r) {
            $latest[(int) $r['enrollment_id']] = $r; // later rows overwrite earlier
        }
        return $latest;
    }

    public function pending_count()
    {
        return $this->db->where('status', 'pending')->count_all_results($this->table);
    }
}
