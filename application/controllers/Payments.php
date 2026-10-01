<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Payments extends Student_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('form_validation');
        $this->load->model(['Program_enrollment_model', 'Payment_model']);
        $this->load->helper('ui');
    }

    /** Student's own payment history, with receipts for approved ones. */
    public function index()
    {
        $this->load->view('templates/header', ['title' => 'My Payments']);
        $this->load->view('student/payments', ['payments' => $this->Payment_model->for_student($this->current_user_id)]);
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

    /** Proof of payment for a program enrolment ($peId = program_enrollments.id). */
    public function upload($peId = 0)
    {
        $pe = $this->Program_enrollment_model->find($peId);

        if (! $pe || (int) $pe['user_id'] !== (int) $this->current_user_id) {
            show_404();
        }

        if ($pe['status'] !== 'pending_payment') {
            $this->session->set_flashdata('error', 'This enrolment is not awaiting payment.');
            return redirect('programs/' . $pe['program_slug']);
        }

        $latest = $this->Payment_model->latest_by_program_enrollment_for_student($this->current_user_id);
        $latest = isset($latest[(int) $peId]) ? $latest[(int) $peId] : null;

        if ($latest && $latest['status'] === 'pending') {
            $this->session->set_flashdata('success', 'Your proof of payment for this program is already waiting for review.');
            return redirect('programs/' . $pe['program_slug']);
        }
        $path = null;
        $originalName = null;

        if ($this->input->method() === 'post') {
            $this->form_validation->set_rules('method', 'Payment method', 'required|in_list[ecocash,bank_transfer]');

            if ($this->form_validation->run()) {
                if (! $this->_handle_upload($path, $originalName)) {
                    // error message already flashed - reload the form to show it
                    return redirect('payments/upload/' . $peId);
                }

                $paymentId = $this->Payment_model->create([
                    'program_enrollment_id' => $peId,
                    'amount'               => $pe['fee_amount'],
                    'method'               => $this->input->post('method'),
                    'proof_file_path'      => $path,
                    'proof_original_name'  => $originalName,
                    'status'               => 'pending',
                    'submitted_at'         => date('Y-m-d H:i:s'),
                ]);

                $this->audit->log('payment.submitted', 'payment', $paymentId, 'Submitted proof of payment (' . money($pe['fee_amount']) . ') for ' . $pe['program_name']);
                $this->session->set_flashdata('success', 'Proof of payment submitted. An admin will review it shortly.');
                return redirect('programs/' . $pe['program_slug']);
            }
        }

        $this->load->model('Module_model');
        $this->load->view('templates/header', ['title' => 'Submit Proof of Payment']);
        $this->load->view('student/upload_payment', [
            'pe'           => $pe,
            'module_count' => count($this->Module_model->for_program($pe['program_id'])),
            'latest'       => $latest,
            'pay'          => $this->_payment_details(),
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
        return $this->settings->all();
    }
}
