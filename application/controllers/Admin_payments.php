<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin_payments extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Payment_model', 'Program_enrollment_model']);
        $this->load->library('notifier');
        $this->load->helper('ui');
    }

    public function index()
    {
        $this->load->helper('ui');

        $this->load->view('templates/header', ['title' => 'Payments']);
        $this->load->view('admin/payments_pending', [
            'tab'      => 'pending',
            'payments' => $this->Payment_model->pending_with_details(),
        ]);
        $this->load->view('templates/footer');
    }

    public function history()
    {
        $this->load->helper('ui');

        $this->load->view('templates/header', ['title' => 'Payments']);
        $this->load->view('admin/payments_pending', [
            'tab'      => 'history',
            'payments' => $this->Payment_model->history(50),
            'pending'  => $this->Payment_model->pending_count(),
        ]);
        $this->load->view('templates/footer');
    }

    public function receipt($paymentId)
    {
        $this->load->helper('ui');
        $this->load->model('Receipt_model');
        $r = $this->Receipt_model->find($paymentId);
        if (! $r || $r['status'] !== 'approved') {
            show_404();
        }
        $this->load->view('templates/header', ['title' => 'Receipt ' . receipt_no($r['id'])]);
        $this->load->view('payments/receipt', ['r' => $r]);
        $this->load->view('templates/footer');
    }

    public function approve($paymentId)
    {
        $payment = $this->Payment_model->find_with_details($paymentId);

        if (! $payment) {
            show_404();
        }

        // Approving a payment marks it approved AND activates the program enrolment,
        // which opens every module of the program. This is the access gate opening.
        if ($payment['status'] !== 'pending') {
            $this->session->set_flashdata('error', 'That payment was already ' . $payment['status'] . '.');
            return redirect('admin_payments');
        }
        $this->Payment_model->approve($paymentId, $this->current_user_id);
        $opened = $this->Program_enrollment_model->activate($payment['program_enrollment_id']);

        $this->audit->log('payment.approved', 'payment', $paymentId, 'Approved ' . money($payment['amount']) . ' payment #' . $paymentId . ' for ' . $payment['program_name'] . ' (' . $opened . ' modules opened)');
        $this->notifier->notify_user(
            $payment['student_id'],
            'Your payment was approved - ' . $payment['program_name'] . ' is now open to you.',
            base_url('programs/' . $payment['program_slug'])
        );

        $msg = 'Payment approved. The student now has every module in ' . $payment['program_name'] . '.';
        if ((float) $payment['amount'] < (float) $payment['program_fee']) {
            $msg .= ' Note: the program fee is now ' . money($payment['program_fee']) . ', more than this payment.';
        }
        $this->session->set_flashdata('success', $msg);
        redirect('admin_payments');
    }

    public function reject($paymentId)
    {
        $payment = $this->Payment_model->find_with_details($paymentId);

        if (! $payment) {
            show_404();
        }

        if ($payment['status'] !== 'pending') {
            $this->session->set_flashdata('error', 'That payment was already ' . $payment['status'] . '.');
            return redirect('admin_payments');
        }
        $note = trim((string) $this->input->post('note'));
        $this->Payment_model->reject($paymentId, $this->current_user_id, $note);
        $this->audit->log('payment.rejected', 'payment', $paymentId, 'Rejected payment #' . $paymentId . ($note !== '' ? ': ' . $note : ''));

        $this->notifier->notify_user(
            $payment['student_id'],
            'Your proof of payment was not accepted' . ($note !== '' ? ': ' . $note : '') . '. Please upload it again.',
            base_url('payments/upload/' . $payment['program_enrollment_id'])
        );

        $this->session->set_flashdata('success', 'Payment rejected.');
        redirect('admin_payments');
    }

    /**
     * Streams the uploaded proof file so only logged-in admins can view it,
     * rather than the file being reachable by a guessable public URL.
     */
    public function view_proof($paymentId)
    {
        $payment = $this->Payment_model->find($paymentId);

        if (! $payment) {
            show_404();
        }

        $fullPath = FCPATH . $payment['proof_file_path'];

        if (! file_exists($fullPath)) {
            show_404();
        }

        $mime = mime_content_type($fullPath);
        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename="' . basename($fullPath) . '"');
        readfile($fullPath);
        exit;
    }
}
