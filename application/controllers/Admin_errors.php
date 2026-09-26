<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin_errors extends Admin_Controller
{
    private $sources = [
        'user'       => ['Reported by users', 'user'],
        'exception'  => ['Crashes', 'alert'],
        'php'        => ['PHP warnings', 'file'],
        'database'   => ['Database', 'layers'],
        'javascript' => ['Browser (JS)', 'monitor'],
        'not_found'  => ['Broken links', 'arrow'],
    ];

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Error_model');
        $this->load->helper('ui');
    }

    public function index($status = 'open')
    {
        if (! in_array($status, ['open', 'resolved', 'ignored'], true)) {
            $status = 'open';
        }
        $source = $this->input->get('source');
        if ($source && ! isset($this->sources[$source])) {
            $source = null;
        }

        $this->load->view('templates/header', ['title' => 'Error reports']);
        $this->load->view('admin/errors', [
            'status'  => $status,
            'source'  => $source,
            'sources' => $this->sources,
            'errors'  => $this->Error_model->listing($status, $source),
            'counts'  => $this->Error_model->counts(),
        ]);
        $this->load->view('templates/footer');
    }

    public function view($id)
    {
        $error = $this->Error_model->find($id);
        if (! $error) {
            show_404();
        }

        $this->load->view('templates/header', ['title' => 'Error ' . $error['reference']]);
        $this->load->view('admin/error_view', ['e' => $error, 'sources' => $this->sources]);
        $this->load->view('templates/footer');
    }

    public function update($id)
    {
        $error  = $this->Error_model->find($id);
        $status = $this->input->post('status');
        if (! $error || ! in_array($status, ['open', 'resolved', 'ignored'], true)) {
            show_404();
        }

        $note = trim((string) $this->input->post('note'));
        $this->Error_model->set_status($id, $status, $this->current_user_id, $note !== '' ? $note : null);
        $this->audit->log('error.' . $status, 'error_report', $id, 'Marked ' . $error['reference'] . ' as ' . $status);

        // Close the loop with whoever reported it.
        if ($status === 'resolved' && $error['source'] === 'user' && $error['user_id']) {
            $this->load->library('notifier');
            $this->notifier->notify_user(
                $error['user_id'],
                'Your problem report ' . $error['reference'] . ' has been resolved' . ($note !== '' ? ': ' . $note : '.'),
                base_url('dashboard')
            );
        }

        $this->session->set_flashdata('success', $error['reference'] . ' marked as ' . $status . '.');
        redirect($status === 'open' ? 'admin_errors/view/' . $id : 'admin_errors');
    }
}
