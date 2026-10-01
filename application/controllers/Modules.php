<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Student applies for a module. (Browsing is on the Programs pages.)
 */
class Modules extends Student_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Module_model', 'Enrollment_model']);
        $this->load->helper('ui');
    }

    /** The old "Courses" address now lands on the programs. */
    public function index()
    {
        redirect('programs');
    }

    /**
     * Student clicks "Apply" on a module: creates a pending_payment
     * enrollment if one doesn't already exist. The module and its
     * program must both be open for applications.
     */
    public function apply($moduleId = 0)
    {
        $module = $this->Module_model->find($moduleId);

        if (! $module) {
            show_404();
        }
        $back = 'programs/' . $module['program_slug'];

        if ($this->input->method() !== 'post') {   // applying changes data, so it only happens from the form's button
            return redirect($back);
        }

        if (! $this->Module_model->is_open_for_applications($module)) {
            $this->session->set_flashdata('error', $module['name'] . ' is not open for applications.');
            return redirect($back);
        }

        foreach ($this->Enrollment_model->modules_for_student($this->current_user_id) as $e) {
            if ($e['id'] == $moduleId) {
                $this->session->set_flashdata('error', 'You have already applied for this module.');
                return redirect($back);
            }
        }

        $enrollmentId = $this->Enrollment_model->create([
            'user_id'   => $this->current_user_id,
            'module_id' => $module['id'],
            'status'    => 'pending_payment',
        ]);

        $this->audit->log('enrollment.applied', 'enrollment', $enrollmentId, 'Applied for ' . $module['name'] . ' (' . $module['program_name'] . ')');
        $this->session->set_flashdata('success', 'Application received. Please submit your proof of payment.');
        redirect('payments/upload/' . $enrollmentId);
    }
}
