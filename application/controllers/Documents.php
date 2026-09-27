<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * "My documents" for students and lecturers: upload copies of a national ID,
 * qualifications and certificates for the office to verify.
 *   documents              - your documents + what the Center requires
 *   documents/upload       - POST
 *   documents/file/{id}    - open a file (owner or administrator)
 *   documents/delete/{id}  - POST (owner, before it is verified; admins any time)
 */
class Documents extends Auth_Controller
{
    const TYPES  = 'pdf|jpg|jpeg|png';
    const MAX_KB = 5120;

    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Document_model', 'Notification_model']);
        $this->load->helper('ui');
        if (! $this->db->table_exists('user_documents')) {   // code updated, database not yet (visit /migrate)
            $this->session->set_flashdata('error', 'Documents need a database update first. An administrator should run the latest update (/migrate).');
            redirect('dashboard');
        }
    }

    public function index()
    {
        if ($this->current_role === 'admin') {
            return redirect('admin_documents');
        }
        $this->load->view('templates/header', ['title' => 'My documents']);
        $this->load->view('documents/index', [
            'docs'      => $this->Document_model->for_user($this->current_user_id),
            'checklist' => $this->Document_model->checklist($this->current_user_id, $this->current_role),
            'types'     => Document_model::$types,
            'maxMb'     => self::MAX_KB / 1024,
        ]);
        $this->load->view('templates/footer');
    }

    public function upload()
    {
        if ($this->input->method() !== 'post' || $this->current_role === 'admin') {
            return redirect('documents');
        }
        $type = (string) $this->input->post('doc_type');
        if (! isset(Document_model::$types[$type])) {
            $this->session->set_flashdata('error', 'Please choose what kind of document it is.');
            return redirect('documents');
        }
        $file = $this->_store_upload('file', 'documents', self::TYPES, self::MAX_KB, $error);
        if (! $file) {
            $this->session->set_flashdata('error', $file === null ? 'Please choose the file (a photo or scan).' : 'The file wasn\'t saved: ' . strip_tags($error));
            return redirect('documents');
        }
        $title = mb_substr(trim((string) $this->input->post('title')), 0, 200);
        $id = $this->Document_model->create([
            'user_id'       => $this->current_user_id,
            'doc_type'      => $type,
            'title'         => $title ?: null,
            'file_path'     => $file['path'],
            'original_name' => $file['name'],
            'file_size'     => (int) @filesize(FCPATH . $file['path']),
        ]);
        $label = Document_model::type_label($type);
        $this->audit->log('document.uploaded', 'document', $id, 'Uploaded ' . $label . ($title ? ' ("' . $title . '")' : '') . ' for verification');

        $name = $this->session->userdata('name');
        foreach ($this->db->select('id')->where('role', 'admin')->where('status', 'active')->get('users')->result_array() as $a) {
            $this->Notification_model->create($a['id'], $name . ' uploaded a document to verify: ' . $label, base_url('admin_documents'));
        }
        $this->session->set_flashdata('success', 'Thank you. Your ' . $label . ' was sent to the office to verify.');
        redirect('documents');
    }

    public function file($id = null)
    {
        $doc = $this->Document_model->find($id);
        if (! $doc || ((int) $doc['user_id'] !== (int) $this->current_user_id && $this->current_role !== 'admin')) {
            show_404();
        }
        if ($this->current_role === 'admin' && (int) $doc['user_id'] !== (int) $this->current_user_id) {
            $this->audit->log('document.viewed', 'document', $doc['id'], 'Opened ' . $doc['owner_name'] . '\'s ' . Document_model::type_label($doc['doc_type']));
        }
        $ext = pathinfo($doc['file_path'], PATHINFO_EXTENSION);
        $this->_send_file($doc['file_path'], $doc['owner_name'] . ' - ' . Document_model::type_label($doc['doc_type']) . '.' . $ext);
    }

    public function delete($id = null)
    {
        $doc = $this->Document_model->find($id);
        if (! $doc || $this->input->method() !== 'post') {
            return redirect('documents');
        }
        $mine = (int) $doc['user_id'] === (int) $this->current_user_id;
        if (($mine && $doc['status'] !== 'verified') || $this->current_role === 'admin') {
            $this->Document_model->delete($doc);
            $this->audit->log('document.deleted', 'document', $doc['id'], 'Deleted ' . ($mine ? 'their' : $doc['owner_name'] . '\'s') . ' ' . Document_model::type_label($doc['doc_type']));
            $this->session->set_flashdata('success', 'Document removed.');
        } else {
            $this->session->set_flashdata('error', 'A verified document can only be removed by the office.');
        }
        redirect($this->current_role === 'admin' && ! $mine ? 'admin_documents' : 'documents');
    }
}
