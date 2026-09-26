<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Student_materials extends Student_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('download');
        $this->load->model(['Enrollment_model', 'Course_model', 'Material_model']);
    }

    /**
     * List the student's courses with active access - materials for anything
     * still pending_payment simply aren't linked here.
     */
    public function index()
    {
        $courses = $this->Enrollment_model->courses_for_student($this->current_user_id);
        $active  = array_filter($courses, function ($c) {
            return $c['enrollment_status'] === 'active';
        });

        $this->load->view('templates/header', ['title' => 'My Materials']);
        $this->load->view('student/materials_index', ['courses' => $active]);
        $this->load->view('templates/footer');
    }

    /**
     * This is the access gate in practice: has_active_access() is checked
     * before a single material is shown, no matter how this URL is reached.
     */
    public function course($courseId)
    {
        if (! $this->Enrollment_model->has_active_access($this->current_user_id, $courseId)) {
            $this->session->set_flashdata('error', 'You do not have access to that course yet.');
            return redirect('courses');
        }

        $course    = $this->Course_model->find($courseId);
        $materials = $this->Material_model->for_course($courseId);

        $this->load->view('templates/header', ['title' => $course['name']]);
        $this->load->view('student/materials_course', [
            'course'    => $course,
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

        if (! $this->Enrollment_model->has_active_access($this->current_user_id, $material['course_id'])) {
            show_error('You do not have access to that course yet.', 403);
        }

        $fullPath = FCPATH . $material['file_path'];
        if (! file_exists($fullPath)) {
            show_404();
        }

        force_download($fullPath, null);
    }
}
