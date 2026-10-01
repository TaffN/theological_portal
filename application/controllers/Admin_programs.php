<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Programs (admin): create, edit, open/close, delete empty ones.
 *   admin_programs                    - list + "Add a program"
 *   admin_programs/edit/{id}          - edit form (POST saves)
 *   admin_programs/{id}/modules       - that program's modules (Admin_modules)
 */
class Admin_programs extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('form_validation');
        $this->load->model(['Program_model', 'Module_model']);
        $this->load->helper('ui');
    }

    public function index()
    {
        $this->load->view('templates/header', ['title' => 'Programs']);
        $this->load->view('admin/programs', ['programs' => $this->Program_model->with_counts()]);
        $this->load->view('templates/footer');
    }

    public function create()
    {
        if ($this->input->method() !== 'post') {
            return redirect('admin_programs');
        }
        $data = $this->validated();
        if ($data === false) {
            $this->session->set_flashdata('error', $this->error);
            return redirect('admin_programs');
        }
        $id = $this->Program_model->create($data + ['status' => 'active']);
        $this->audit->log('program.created', 'program', $id, 'Created program "' . $data['name'] . '"');
        $this->session->set_flashdata('success', 'Program created. Now add its modules.');
        redirect('admin_programs/' . $id . '/modules');
    }

    public function edit($id = 0)
    {
        $program = $this->Program_model->find($id);
        if (! $program) {
            show_404();
        }
        $error = '';
        if ($this->input->method() === 'post') {
            $data = $this->validated();
            if ($data === false) {
                $error = $this->error;
            } else {
                $this->Program_model->update($program['id'], $data);
                if (isset($data['thumbnail_path']) && ! empty($program['thumbnail_path']) && is_file(FCPATH . $program['thumbnail_path'])) {
                    @unlink(FCPATH . $program['thumbnail_path']);   // the old picture is replaced
                }
                $this->audit->log('program.updated', 'program', $program['id'], 'Edited program "' . $data['name'] . '"');
                $this->session->set_flashdata('success', 'Program updated.');
                return redirect('admin_programs');
            }
        }
        $this->load->view('templates/header', ['title' => 'Edit program']);
        $this->load->view('admin/program_form', ['program' => $program, 'error' => $error]);
        $this->load->view('templates/footer');
    }

    public function toggle($id = 0)
    {
        $program = $this->Program_model->find($id);
        if (! $program) {
            show_404();
        }
        if ($this->input->method() === 'post') {
            $status = $program['status'] === 'active' ? 'inactive' : 'active';
            $this->Program_model->update($program['id'], ['status' => $status]);
            $this->audit->log('program.' . ($status === 'active' ? 'opened' : 'closed'), 'program', $program['id'],
                ($status === 'active' ? 'Re-opened' : 'Closed') . ' program "' . $program['name'] . '" for new applications');
            $this->session->set_flashdata('success', $status === 'active'
                ? $program['name'] . ' is open for applications again.'
                : $program['name'] . ' is closed to new applications. Students already enrolled keep their access.');
        }
        redirect('admin_programs');
    }

    public function delete($id = 0)
    {
        $program = $this->Program_model->find($id);
        if (! $program) {
            show_404();
        }
        if ($this->input->method() === 'post') {
            if ($this->Program_model->delete($program['id'])) {
                $this->audit->log('program.deleted', 'program', $program['id'], 'Deleted the empty program "' . $program['name'] . '"');
                $this->session->set_flashdata('success', 'Program deleted.');
            } else {
                $this->session->set_flashdata('error', 'A program that still has modules cannot be deleted. Move or remove its modules first, or close the program instead.');
            }
        }
        redirect('admin_programs');
    }

    /* ------------------------------------------------------------------ */

    protected $error = '';

    /** The posted fields (and the thumbnail, if one was chosen), or false with $this->error set. */
    private function validated()
    {
        $this->form_validation->set_rules('name', 'Program name', 'required|max_length[200]');
        $this->form_validation->set_rules('duration_text', 'Duration', 'max_length[100]');
        if (! $this->form_validation->run()) {
            $this->error = strip_tags(validation_errors());
            return false;
        }
        $data = [
            'name'          => trim($this->input->post('name')),
            'description'   => trim($this->input->post('description')) ?: null,
            'duration_text' => trim($this->input->post('duration_text')) ?: null,
        ];
        $file = $this->_store_upload('thumbnail', 'programs', 'jpg|jpeg|png', 1024, $err);
        if ($file === false) {
            $this->error = 'The picture could not be saved: ' . $err;
            return false;
        }
        if ($file) {
            $data['thumbnail_path'] = $file['path'];
        }
        return $data;
    }
}
