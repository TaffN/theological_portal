<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * The college library, for every role.
 *   library                 - search and browse (?q=, ?category=, ?module=)
 *   library/upload          - POST (lecturers and administrators)
 *   library/open/{id}       - download the file / open the link (counts it)
 *   library/delete/{id}     - POST (whoever added it, or an administrator)
 *
 * Students can use the library once at least one of their modules is paid up.
 */
class Library extends Auth_Controller
{
    const TYPES  = 'pdf|doc|docx|ppt|pptx|epub|rtf|txt|mp3|m4a|mp4';
    const MAX_KB = 20480;

    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Library_model', 'Module_model']);
        $this->load->helper('ui');
        if (! $this->db->table_exists('library_files')) {   // code updated, database not yet (visit /migrate)
            $this->session->set_flashdata('error', 'The library needs a database update first. An administrator should run the latest update (/migrate).');
            redirect('dashboard');
        }
    }

    public function index()
    {
        $allowed = $this->can_read();
        $q        = trim((string) $this->input->get('q'));
        $category = (string) $this->input->get('category');
        if (! in_array($category, Library_model::$categories, true)) {
            $category = '';
        }
        $moduleId = (int) $this->input->get('module');

        $this->load->view('templates/header', ['title' => 'Library']);
        $this->load->view('library/index', [
            'allowed'    => $allowed,
            'files'      => $allowed ? $this->Library_model->search($q, $category, $moduleId ?: null) : [],
            'counts'     => $allowed ? $this->Library_model->counts() : [],
            'q'          => $q,
            'category'   => $category,
            'moduleId'   => $moduleId,
            'categories' => Library_model::$categories,
            'modules'    => $this->Module_model->for_user($this->current_user_id, $this->current_role === 'student' ? 'student' : 'admin'),
            'canUpload'  => $this->current_role !== 'student',
            'types'      => str_replace('|', ', ', self::TYPES),
            'maxMb'      => self::MAX_KB / 1024,
        ]);
        $this->load->view('templates/footer');
    }

    public function upload()
    {
        if ($this->current_role === 'student' || $this->input->method() !== 'post') {
            return redirect('library');
        }
        $title    = trim((string) $this->input->post('title'));
        $category = (string) $this->input->post('category');
        $link     = trim((string) $this->input->post('external_link'));
        $moduleId = (int) $this->input->post('module_id');

        if ($title === '' || mb_strlen($title) > 200 || ! in_array($category, Library_model::$categories, true)) {
            $this->session->set_flashdata('error', 'Please give a title and choose a category.');
            return redirect('library');
        }
        if ($link !== '' && ! filter_var($link, FILTER_VALIDATE_URL)) {
            $this->session->set_flashdata('error', 'The link should start with https://');
            return redirect('library');
        }
        $file = $this->_store_upload('file', 'library', self::TYPES, self::MAX_KB, $error);
        if ($file === false) {
            $this->session->set_flashdata('error', 'The file wasn\'t saved: ' . strip_tags($error));
            return redirect('library');
        }
        if (! $file && $link === '') {
            $this->session->set_flashdata('error', 'Please choose a file or paste a link.');
            return redirect('library');
        }

        $id = $this->Library_model->create([
            'title'         => $title,
            'author'        => mb_substr(trim((string) $this->input->post('author')), 0, 200) ?: null,
            'category'      => $category,
            'description'   => trim((string) $this->input->post('description')) ?: null,
            'module_id'     => $moduleId && $this->Module_model->find($moduleId) ? $moduleId : null,
            'file_path'     => $file ? $file['path'] : null,
            'original_name' => $file ? $file['name'] : null,
            'file_size'     => $file ? (int) @filesize(FCPATH . $file['path']) : 0,
            'external_link' => $link ?: null,
            'uploaded_by'   => $this->current_user_id,
        ]);
        $this->audit->log('library.added', 'library', $id, 'Added "' . $title . '" to the library (' . $category . ')');
        $this->session->set_flashdata('success', '"' . $title . '" is in the library.');
        redirect('library');
    }

    public function open($id = null)
    {
        if (! $this->can_read()) {
            show_404();
        }
        $item = $this->Library_model->find($id);
        if (! $item) {
            show_404();
        }
        $this->Library_model->count_download($item['id']);
        if ($item['file_path']) {
            $ext = pathinfo($item['file_path'], PATHINFO_EXTENSION);
            return $this->_send_file($item['file_path'], $item['original_name'] ?: $item['title'] . '.' . $ext);
        }
        redirect($item['external_link']);
    }

    public function delete($id = null)
    {
        $item = $this->Library_model->find($id);
        if (! $item) {
            show_404();
        }
        $mine = (int) $item['uploaded_by'] === (int) $this->current_user_id;
        if ($this->input->method() === 'post' && ($this->current_role === 'admin' || ($this->current_role === 'lecturer' && $mine))) {
            $this->Library_model->delete($item['id']);
            if ($item['file_path'] && is_file(FCPATH . $item['file_path'])) {
                @unlink(FCPATH . $item['file_path']);
            }
            $this->audit->log('library.deleted', 'library', $item['id'], 'Removed "' . $item['title'] . '" from the library');
            $this->session->set_flashdata('success', 'Removed from the library.');
        }
        redirect('library');
    }

    /** Staff always; students with at least one paid-up module. */
    private function can_read()
    {
        return $this->current_role !== 'student' || count($this->Module_model->ids_for_user($this->current_user_id, 'student')) > 0;
    }
}
