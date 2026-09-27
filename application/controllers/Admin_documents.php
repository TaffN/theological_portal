<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Verifying students' and lecturers' documents.
 *   admin_documents?tab=pending|verified|rejected|missing&role=&q=
 *   admin_documents/review/{id}   - POST action=verify|reject, note
 *   admin_documents/required      - POST: which documents each role must provide
 */
class Admin_documents extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Document_model');
        $this->load->library('notifier');
        $this->load->helper('ui');
        if (! $this->db->table_exists('user_documents')) {   // code updated, database not yet (visit /migrate)
            $this->session->set_flashdata('error', 'Documents need a database update first. Run the latest update (/migrate).');
            redirect('dashboard');
        }
    }

    public function index()
    {
        $tab  = (string) $this->input->get('tab');
        $tab  = in_array($tab, ['pending', 'verified', 'rejected', 'missing'], true) ? $tab : 'pending';
        $role = (string) $this->input->get('role');
        $role = in_array($role, ['student', 'lecturer'], true) ? $role : '';
        $q    = trim((string) $this->input->get('q'));

        $this->load->view('templates/header', ['title' => 'Documents']);
        $this->load->view('admin/documents', [
            'tab'      => $tab,
            'role'     => $role,
            'q'        => $q,
            'counts'   => $this->Document_model->counts(),
            'docs'     => $tab === 'missing' ? [] : $this->Document_model->queue($tab, $role, $q),
            'missing'  => $tab === 'missing' ? $this->Document_model->missing($role) : [],
            'types'    => Document_model::$types,
            'required' => ['student' => $this->Document_model->required_for('student'), 'lecturer' => $this->Document_model->required_for('lecturer')],
        ]);
        $this->load->view('templates/footer');
    }

    public function review($id = null)
    {
        $doc = $this->Document_model->find($id);
        if (! $doc || $this->input->method() !== 'post') {
            return redirect('admin_documents');
        }
        $action = (string) $this->input->post('action');
        $note   = mb_substr(trim((string) $this->input->post('note')), 0, 500);
        $back   = (string) $this->input->post('back') === 'card' ? 'admin_users/card/' . $doc['user_id'] : 'admin_documents';
        if ($action === 'reject' && $note === '') {
            $this->session->set_flashdata('error', 'Please say why the document is rejected, so ' . $doc['owner_name'] . ' can fix it.');
            return redirect($back);
        }
        if (! in_array($action, ['verify', 'reject'], true)) {
            return redirect($back);
        }
        $status = $action === 'verify' ? 'verified' : 'rejected';
        $this->Document_model->review($doc['id'], $status, $note, $this->current_user_id);
        $label = Document_model::type_label($doc['doc_type']);
        $this->audit->log('document.' . $status, 'document', $doc['id'], ucfirst($status) . ' ' . $doc['owner_name'] . '\'s ' . $label . ($note ? ': ' . $note : ''));
        $this->notifier->notify_user($doc['user_id'], $status === 'verified'
            ? 'Your ' . $label . ' has been verified. Thank you.'
            : 'Your ' . $label . ' could not be accepted: ' . $note . '. Please upload a new copy.', base_url('documents'));

        $this->session->set_flashdata('success', $doc['owner_name'] . '\'s ' . $label . ' is ' . $status . '.');
        redirect($back);
    }

    public function required()
    {
        if ($this->input->method() !== 'post') {
            return redirect('admin_documents');
        }
        $save = [];
        foreach (['student', 'lecturer'] as $r) {
            $picked = array_values(array_intersect((array) $this->input->post('required_' . $r), array_keys(Document_model::$types)));
            $save['docs_required_' . $r] = implode(',', $picked);
        }
        $this->settings->save($save);
        $this->audit->log('settings.documents', null, null, 'Changed required documents (students: ' . ($save['docs_required_student'] ?: 'none') . '; lecturers: ' . ($save['docs_required_lecturer'] ?: 'none') . ')');
        $this->session->set_flashdata('success', 'Required documents saved.');
        redirect('admin_documents?tab=missing');
    }
}
