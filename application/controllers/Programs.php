<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * What students browse and apply through.
 *   programs                       - every open program (cards)
 *   programs/{slug}                - one program and its modules (also programs/{slug}/modules)
 *   programs/thumbnail/{id}        - the program's picture
 * Applying to a module is Modules::apply.
 */
class Programs extends Student_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Program_model', 'Module_model', 'Enrollment_model', 'Payment_model']);
        $this->load->helper('ui');
    }

    public function index()
    {
        $programs = $this->Program_model->with_counts(true);
        $mine = $this->Enrollment_model->modules_for_student($this->current_user_id);

        // How many of each program's modules this student has applied for / has access to.
        $applied = $active = [];
        foreach ($mine as $m) {
            $pid = (int) $m['program_id'];
            $applied[$pid] = (isset($applied[$pid]) ? $applied[$pid] : 0) + 1;
            if ($m['enrollment_status'] === 'active') {
                $active[$pid] = (isset($active[$pid]) ? $active[$pid] : 0) + 1;
            }
        }

        $this->load->view('templates/header', ['title' => 'Programs']);
        $this->load->view('student/programs', ['programs' => $programs, 'applied' => $applied, 'active' => $active]);
        $this->load->view('templates/footer');
    }

    public function view($slug = '')
    {
        $program = $this->Program_model->find_by_slug($slug);
        if (! $program || $program['status'] !== 'active') {
            $this->session->set_flashdata('error', 'That program is not available.');
            return redirect('programs');
        }

        $modules = $this->Module_model->for_program($program['id'], true);
        foreach ($modules as &$module) {
            $module['lecturers'] = $this->Module_model->lecturers($module['id']);
        }
        unset($module);

        $status = [];
        foreach ($this->Enrollment_model->modules_for_student($this->current_user_id) as $mc) {
            $status[$mc['id']] = ['status' => $mc['enrollment_status'], 'enrollment_id' => $mc['enrollment_id']];
        }

        $this->load->view('templates/header', ['title' => $program['name']]);
        $this->load->view('student/program_view', [
            'program'          => $program,
            'modules'          => $modules,
            'statusByModuleId' => $status,
            'latestPayments'   => $this->Payment_model->latest_by_enrollment_for_student($this->current_user_id),
        ]);
        $this->load->view('templates/footer');
    }

    public function thumbnail($id = 0)
    {
        $program = $this->Program_model->find($id);
        if (! $program || empty($program['thumbnail_path'])) {
            show_404();
        }
        $this->_send_file($program['thumbnail_path']);
    }
}
