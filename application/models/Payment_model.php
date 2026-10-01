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
     * The base query for every payment list: the payment, who paid, and for which program (and, for
     * payments made before programs had a fee, which module). Payments always belong to a program enrolment.
     */
    protected function with_details()
    {
        return $this->db
            ->select('payments.*, pe.user_id AS student_id, pe.id AS pe_id, users.name AS student_name, users.email AS student_email,
                      programs.id AS program_id, programs.name AS program_name, programs.slug AS program_slug, programs.fee_amount AS program_fee,
                      lm.name AS legacy_module_name, (SELECT COUNT(*) FROM modules mc WHERE mc.program_id = programs.id) AS module_count', false)
            ->from('payments')
            ->join('program_enrollments pe', 'pe.id = payments.program_enrollment_id')
            ->join('users', 'users.id = pe.user_id')
            ->join('programs', 'programs.id = pe.program_id')
            ->join('enrollments le', 'le.id = payments.enrollment_id', 'left')
            ->join('modules lm', 'lm.id = le.module_id', 'left');
    }

    /** Pending payments for the admin review screen, oldest first. */
    public function pending_with_details()
    {
        return $this->with_details()->where('payments.status', 'pending')->order_by('payments.submitted_at', 'ASC')->get()->result_array();
    }

    /** One payment with its details (used for receipts and approval messages). */
    public function find_with_details($id)
    {
        return $this->with_details()->where('payments.id', (int) $id)->get()->row_array();
    }

    /** A student's own payments, newest first. */
    public function for_student($userId)
    {
        return $this->with_details()->where('pe.user_id', (int) $userId)->order_by('payments.submitted_at', 'DESC')->get()->result_array();
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

    /** Reviewed payments (approved + rejected), newest first, for the admin's History tab. */
    public function history($limit = 50)
    {
        return $this->with_details()->select('reviewer.name AS reviewer_name', false)
            ->join('users reviewer', 'reviewer.id = payments.reviewed_by', 'left')
            ->where_in('payments.status', ['approved', 'rejected'])
            ->order_by('payments.reviewed_at', 'DESC')->limit($limit)->get()->result_array();
    }

    /**
     * Latest payment row per program enrolment for one student, keyed by program_enrollment_id, so a
     * program page can say "rejected: <reason>".
     */
    public function latest_by_program_enrollment_for_student($userId)
    {
        $rows = $this->db->select('payments.*')->from('payments')
            ->join('program_enrollments pe', 'pe.id = payments.program_enrollment_id')
            ->where('pe.user_id', (int) $userId)->order_by('payments.id', 'ASC')->get()->result_array();
        $latest = [];
        foreach ($rows as $r) {
            $latest[(int) $r['program_enrollment_id']] = $r;   // later rows overwrite earlier
        }
        return $latest;
    }

    public function pending_count()
    {
        return $this->db->where('status', 'pending')->count_all_results($this->table);
    }
}
