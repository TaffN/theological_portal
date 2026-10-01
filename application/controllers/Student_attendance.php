<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** A student's own attendance in each paid-up module. */
class Student_attendance extends Student_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Attendance_model', 'Module_model']);
        $this->load->helper('ui');
        if (! $this->db->table_exists('attendance')) {   // code updated, database not yet (visit /migrate)
            $this->session->set_flashdata('error', 'Attendance needs a database update first. An administrator should run the latest update (/migrate).');
            redirect('dashboard');
        }
    }

    public function index()
    {
        $modules = $this->Module_model->for_user($this->current_user_id, 'student');
        $this->load->view('templates/header', ['title' => 'My attendance']);
        $this->load->view('attendance/student', [
            'modules'  => $modules,
            'data'     => $this->Attendance_model->for_student($this->current_user_id, array_column($modules, 'id'), 1000),
            'statuses' => Attendance_model::$statuses,
        ]);
        $this->load->view('templates/footer');
    }
}
