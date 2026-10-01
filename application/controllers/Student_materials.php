<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Student_materials extends Student_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('download');
        $this->load->model(['Enrollment_model', 'Module_model', 'Material_model']);
    }

    /**
     * List the student's modules with active access - materials for anything
     * still pending_payment simply aren't linked here.
     */
    public function index()
    {
        $modules = $this->Enrollment_model->modules_for_student($this->current_user_id);
        $active  = array_filter($modules, function ($c) {
            return $c['enrollment_status'] === 'active';
        });

        $this->load->view('templates/header', ['title' => 'My Materials']);
        $this->load->view('student/materials_index', ['modules' => $active]);
        $this->load->view('templates/footer');
    }

    /**
     * This is the access gate in practice: has_active_access() is checked
     * before a single material is shown, no matter how this URL is reached.
     */
    public function module($moduleId)
    {
        if (! $this->Enrollment_model->has_active_access($this->current_user_id, $moduleId)) {
            $this->session->set_flashdata('error', 'You do not have access to that module yet.');
            return redirect('programs');
        }

        $module    = $this->Module_model->find($moduleId);
        $materials = $this->Material_model->for_module($moduleId);

        $this->load->view('templates/header', ['title' => $module['name']]);
        $this->load->view('student/materials_module', [
            'module'    => $module,
            'materials' => $materials,
        ]);
        $this->load->view('templates/footer');
    }

    /**
     * Streams an uploaded material file, re-checking access on every
     * download rather than trusting a previously-rendered link.
     */
    public function download($materialId)
    {
        $material = $this->Material_model->find($materialId);

        if (! $material || ! $material['file_path']) {
            show_404();
        }

        if (! $this->Enrollment_model->has_active_access($this->current_user_id, $material['module_id'])) {
            show_error('You do not have access to that module yet.', 403);
        }

        $fullPath = FCPATH . $material['file_path'];
        if (! file_exists($fullPath)) {
            show_404();
        }

        force_download($fullPath, null);
    }
}
