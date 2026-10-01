<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Stage 6: a student's published module results, and a printable statement
 * of results whose QR code opens a public "this is genuine" page.
 */
class Student_results extends Student_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Result_model', 'User_model']);
        $this->load->helper('ui');
        if (! $this->db->table_exists('module_results')) {   // code updated, database not yet (visit /migrate)
            $this->session->set_flashdata('error', 'Results need a database update first. An administrator should run the latest update (/migrate).');
            redirect('dashboard');
        }
    }

    public function index()
    {
        $this->load->view('templates/header', ['title' => 'My results']);
        $this->load->view('student/results', [
            'results' => $this->Result_model->for_student($this->current_user_id),
            'bands'   => $this->Result_model->bands(),
        ]);
        $this->load->view('templates/footer');
    }

    /** One page, printable / save as PDF. */
    public function statement()
    {
        $results = $this->Result_model->for_student($this->current_user_id);
        if (! $results) {
            $this->session->set_flashdata('error', 'You don\'t have any published results yet.');
            return redirect('student_results');
        }
        $user = $this->User_model->find($this->current_user_id);
        $token = $user['results_token'] ?: $this->Result_model->ensure_token($user['id']);

        $this->audit->log('result.statement_viewed', 'user', $user['id'], $user['name'] . ' opened their statement of results');
        $this->load->view('templates/header', ['title' => 'Statement of results']);
        $this->load->view('student/results_statement', [
            'u'         => $user,
            'profile'   => $this->User_model->get_profile($user['id']),
            'results'   => $results,
            'verifyUrl' => base_url('results/verify/' . $token),
        ]);
        $this->load->view('templates/footer');
    }
}
