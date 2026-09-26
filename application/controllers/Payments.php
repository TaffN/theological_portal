<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Payments extends Student_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('form_validation');
        $this->load->model(['Enrollment_model', 'Course_model', 'Payment_model']);
        $this->load->helper('ui');
    }

    /** Student's own payment history, with receipts for approved ones. */
    public function index()
    {
        $rows = $this->db->select('payments.*, courses.name AS course_name')
            ->from('payments')
            ->join('enrollments', 'enrollments.id = payments.enrollment_id')
            ->join('courses', 'courses.id = enrollments.course_id')
            ->where('enrollments.user_id', $this->current_user_id)
            ->order_by('payments.submitted_at', 'DESC')
            ->get()->result_array();

        $this->load->view('templates/header', ['title' => 'My Payments']);
        $this->load->view('student/payments', ['payments' => $rows]);
        $this->load->view('templates/footer');
    }

    public function receipt($paymentId)
    {
        $this->load->model('Receipt_model');
        $r = $this->Receipt_model->find($paymentId);
        if (! $r || (int) $r['student_id'] !== (int) $this->current_user_id || $r['status'] !== 'approved') {
            show_404();
        }
        $this->load->view('templates/header', ['title' => 'Receipt ' . receipt_no($r['id'])]);
        $this->load->view('payments/receipt', ['r' => $r]);
        $this->load->view('templates/footer');
    }

    public function upload($enrollmentId)
    {
        $enrollment = $this->Enrollment_model->find($enrollmentId);

        if (! $enrollment || $enrollment['user_id'] != $this->current_user_id) {
            show_404();
        }

        if ($enrollment['status'] !== 'pending_payment') {
            $this->session->set_flashdata('error', 'This enrollment is not awaiting payment.');
            return redirect('courses');
        }

        $course = $this->Course_model->find($enrollment['course_id']);
        $latest = $this->Payment_model->latest_by_enrollment_for_student($this->current_user_id);
        $latest = isset($latest[(int) $enrollmentId]) ? $latest[(int) $enrollmentId] : null;

        if ($latest && $latest['status'] === 'pending') {
            $this->session->set_flashdata('success', 'Your proof of payment for this course is already waiting for review.');
            return redirect('courses');
        }
        $path = null;
        $originalName = null;

        if ($this->input->method() === 'post') {
            $this->form_validation->set_rules('method', 'Payment method', 'required|in_list[ecocash,bank_transfer]');

            if ($this->form_validation->run()) {
                if (! $this->_handle_upload($path, $originalName)) {
                    // error message already flashed - reload the form to show it
                    return redirect('payments/upload/' . $enrollmentId);
                }

                $paymentId = $this->Payment_model->create([
                    'enrollment_id'        => $enrollmentId,
                    'amount'               => $course['fee_amount'],
                    'method'               => $this->input->post('method'),
                    'proof_file_path'      => $path,
                    'proof_original_name'  => $originalName,
                    'status'               => 'pending',
                    'submitted_at'         => date('Y-m-d H:i:s'),
                ]);

                $this->audit->log('payment.submitted', 'payment', $paymentId, 'Submitted proof of payment (' . money($course['fee_amount']) . ') for ' . $course['name']);
                $this->session->set_flashdata('success', 'Proof of payment submitted. An admin will review it shortly.');
                return redirect('courses');
            }
        }

        $this->load->view('templates/header', ['title' => 'Submit Proof of Payment']);
        $this->load->view('student/upload_payment', [
            'enrollment' => $enrollment,
            'course'     => $course,
            'latest'     => $latest,
            'pay'        => $this->_payment_details(),
        ]);
        $this->load->view('templates/footer');
    }

    /**
     * Handles the proof-of-payment file upload. Sets $path/$originalName
     * by reference and returns true on success.
     */
    private function _handle_upload(&$path, &$originalName)
    {
        $uploadDir = FCPATH . 'uploads/proofs/';

        if (! is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $config['upload_path']   = $uploadDir;
        $config['allowed_types'] = 'jpg|jpeg|png|pdf';
        $config['max_size']      = 4096; // KB
        $config['encrypt_name']  = true;

        $this->load->library('upload', $config);

        if (! $this->upload->do_upload('proof')) {
            $this->session->set_flashdata('error', $this->upload->display_errors('', ''));
            return false;
        }

        $data           = $this->upload->data();
        $path           = 'uploads/proofs/' . $data['file_name'];
        $originalName   = $data['client_name'] ?? $data['orig_name'];

        return true;
    }

    private function _payment_details()
    {
        $this->config->load('portal', true, true);
        $cfg = $this->config->item('portal');
        return is_array($cfg) ? $cfg : [];
    }
}
