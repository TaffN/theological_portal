<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * What students browse and apply through.
 *   programs                       - every open program (cards)
 *   programs/{slug}                - one program and its modules (also programs/{slug}/modules)
 *   programs/thumbnail/{id}        - the program's picture
 * programs/apply/{id}          - POST: apply to the program (one fee for every module in it)
 */
class Programs extends Student_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Program_model', 'Module_model', 'Enrollment_model', 'Payment_model', 'Program_enrollment_model']);
        $this->load->helper('ui');
    }

    public function index()
    {
        $programs = $this->Program_model->with_counts(true);
        $mine = [];
        foreach ($this->Program_enrollment_model->for_student($this->current_user_id) as $pe) {
            $mine[(int) $pe['program_id']] = $pe;
        }

        $this->load->view('templates/header', ['title' => 'Programs']);
        $this->load->view('student/programs', ['programs' => $programs, 'mine' => $mine]);
        $this->load->view('templates/footer');
    }

    public function view($slug = '')
    {
        $program = $this->Program_model->find_by_slug($slug);
        $pe = $program ? $this->Program_enrollment_model->find_for($this->current_user_id, $program['id']) : null;
        // A closed program is hidden from new applicants, but students already in it can still open its page.
        if (! $program || ($program['status'] !== 'active' && ! $pe)) {
            $this->session->set_flashdata('error', 'That program is not available.');
            return redirect('programs');
        }

        $modules = $this->Module_model->for_program($program['id']);
        foreach ($modules as &$module) {
            $module['lecturers'] = $this->Module_model->lecturers($module['id']);
        }
        unset($module);

        $access = [];                                   // module id => true when the student can open it
        foreach ($this->Enrollment_model->modules_for_student($this->current_user_id) as $mc) {
            if ($mc['enrollment_status'] === 'active') {
                $access[(int) $mc['id']] = true;
            }
        }
        $latest = $pe ? $this->Payment_model->latest_by_program_enrollment_for_student($this->current_user_id) : [];

        $this->load->view('templates/header', ['title' => $program['name']]);
        $this->load->view('student/program_view', [
            'program' => $program,
            'modules' => $modules,
            'pe'      => $pe,
            'payment' => $pe && isset($latest[(int) $pe['id']]) ? $latest[(int) $pe['id']] : null,
            'access'  => $access,
        ]);
        $this->load->view('templates/footer');
    }

    /**
     * Student applies to a program. A paid program waits for proof of payment; a free one opens at once.
     */
    public function apply($programId = 0)
    {
        $program = $this->Program_model->find($programId);
        if (! $program) {
            show_404();
        }
        $back = 'programs/' . $program['slug'];
        if ($this->input->method() !== 'post') {   // applying changes data, so it only happens from the form's button
            return redirect($back);
        }
        if ($program['status'] !== 'active') {
            $this->session->set_flashdata('error', $program['name'] . ' is not open for applications.');
            return redirect('programs');
        }
        if ($this->Program_enrollment_model->find_for($this->current_user_id, $program['id'])) {
            $this->session->set_flashdata('error', 'You have already applied for this program.');
            return redirect($back);
        }

        $peId = $this->Program_enrollment_model->create_pending($this->current_user_id, $program['id']);
        $this->audit->log('enrollment.applied', 'enrollment', $peId, 'Applied for the program ' . $program['name'] . ' (' . money($program['fee_amount']) . ')');

        if ((float) $program['fee_amount'] <= 0) {
            $opened = $this->Program_enrollment_model->activate($peId);
            $this->audit->log('enrollment.activated', 'enrollment', $peId, 'Free program ' . $program['name'] . ': opened ' . $opened . ' module(s)');
            $this->session->set_flashdata('success', 'You are enrolled in ' . $program['name'] . '. All its modules are open.');
            return redirect($back);
        }
        $this->session->set_flashdata('success', 'Application received. Please submit your proof of payment (' . money($program['fee_amount']) . ').');
        redirect('payments/upload/' . $peId);
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
