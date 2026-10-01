<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Lecturer_materials extends Lecturer_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library(['form_validation', 'notifier']);
        $this->load->model(['Module_lecturer_model', 'Material_model']);
    }

    /**
     * List the modules this lecturer teaches, so they can pick one to manage.
     */
    public function index()
    {
        $modules = $this->Module_lecturer_model->modules_for_lecturer($this->current_user_id);

        $this->load->view('templates/header', ['title' => 'My Modules']);
        $this->load->view('lecturer/materials_index', ['modules' => $modules]);
        $this->load->view('templates/footer');
    }

    /**
     * Materials for one module, plus the form to post a new one.
     * Guarded so a lecturer can't manage a module they're not assigned to.
     */
    public function module($moduleId)
    {
        if (! $this->Module_lecturer_model->is_assigned($moduleId, $this->current_user_id)) {
            show_error('You are not assigned to that module.', 403);
        }

        $this->load->model('Module_model');
        $module    = $this->Module_model->find($moduleId);
        $materials = $this->Material_model->for_module($moduleId);

        if ($this->input->method() === 'post') {
            $this->form_validation->set_rules('title', 'Title', 'required|max_length[200]');

            if ($this->form_validation->run()) {
                $filePath = $this->_handle_optional_upload();

                $materialId = $this->Material_model->create([
                    'module_id'      => $moduleId,
                    'lecturer_id'    => $this->current_user_id,
                    'title'          => $this->input->post('title'),
                    'description'    => $this->input->post('description'),
                    'file_path'      => $filePath,
                    'external_link'  => $this->input->post('external_link') ?: null,
                ]);

                $this->audit->log('material.posted', 'material', $materialId, 'Posted "' . $this->input->post('title') . '" to ' . $module['name']);
                $this->notifier->notify_module(
                    $moduleId,
                    'New material posted in ' . $module['name'] . ': ' . $this->input->post('title'),
                    base_url('student_materials/module/' . $moduleId)
                );

                $this->session->set_flashdata('success', 'Material posted and students notified.');
                return redirect('lecturer_materials/module/' . $moduleId);
            }
        }

        $this->load->view('templates/header', ['title' => $module['name']]);
        $this->load->view('lecturer/materials_module', [
            'module'    => $module,
            'materials' => $materials,
        ]);
        $this->load->view('templates/footer');
    }

    public function delete($materialId)
    {
        $material = $this->Material_model->find($materialId);

        if (! $material || ! $this->Module_lecturer_model->is_assigned($material['module_id'], $this->current_user_id)) {
            show_error('Not found.', 404);
        }

        $this->Material_model->delete($materialId);
        $this->audit->log('material.deleted', 'material', $materialId, 'Deleted material "' . $material['title'] . '"');
        $this->session->set_flashdata('success', 'Material removed.');
        redirect('lecturer_materials/module/' . $material['module_id']);
    }

    /**
     * The file is optional - a lecturer might just post an external link
     * (e.g. a YouTube video) instead of/alongside a file.
     */
    private function _handle_optional_upload()
    {
        if (empty($_FILES['file']['name'])) {
            return null;
        }

        $uploadDir = FCPATH . 'uploads/materials/';
        if (! is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $config['upload_path']   = $uploadDir;
        $config['allowed_types'] = 'jpg|jpeg|png|pdf|doc|docx|ppt|pptx|xls|xlsx|zip';
        $config['max_size']      = 20480; // KB
        $config['encrypt_name']  = true;

        $this->load->library('upload', $config);

        if (! $this->upload->do_upload('file')) {
            $this->session->set_flashdata('error', $this->upload->display_errors('', ''));
            return null;
        }

        return 'uploads/materials/' . $this->upload->data('file_name');
    }
}
