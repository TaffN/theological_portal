<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Lecturer_materials extends Lecturer_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library(['form_validation', 'notifier']);
        $this->load->model(['Course_lecturer_model', 'Material_model']);
    }

    /**
     * List the courses this lecturer teaches, so they can pick one to manage.
     */
    public function index()
    {
        $courses = $this->Course_lecturer_model->courses_for_lecturer($this->current_user_id);

        $this->load->view('templates/header', ['title' => 'My Courses']);
        $this->load->view('lecturer/materials_index', ['courses' => $courses]);
        $this->load->view('templates/footer');
    }

    /**
     * Materials for one course, plus the form to post a new one.
     * Guarded so a lecturer can't manage a course they're not assigned to.
     */
    public function course($courseId)
    {
        if (! $this->Course_lecturer_model->is_assigned($courseId, $this->current_user_id)) {
            show_error('You are not assigned to that course.', 403);
        }

        $this->load->model('Course_model');
        $course    = $this->Course_model->find($courseId);
        $materials = $this->Material_model->for_course($courseId);

        if ($this->input->method() === 'post') {
            $this->form_validation->set_rules('title', 'Title', 'required|max_length[200]');

            if ($this->form_validation->run()) {
                $filePath = $this->_handle_optional_upload();

                $materialId = $this->Material_model->create([
                    'course_id'      => $courseId,
                    'lecturer_id'    => $this->current_user_id,
                    'title'          => $this->input->post('title'),
                    'description'    => $this->input->post('description'),
                    'file_path'      => $filePath,
                    'external_link'  => $this->input->post('external_link') ?: null,
                ]);

                $this->audit->log('material.posted', 'material', $materialId, 'Posted "' . $this->input->post('title') . '" to ' . $course['name']);
                $this->notifier->notify_course(
                    $courseId,
                    'New material posted in ' . $course['name'] . ': ' . $this->input->post('title'),
                    base_url('student_materials/course/' . $courseId)
                );

                $this->session->set_flashdata('success', 'Material posted and students notified.');
                return redirect('lecturer_materials/course/' . $courseId);
            }
        }

        $this->load->view('templates/header', ['title' => $course['name']]);
        $this->load->view('lecturer/materials_course', [
            'course'    => $course,
            'materials' => $materials,
        ]);
        $this->load->view('templates/footer');
    }

    public function delete($materialId)
    {
        $material = $this->Material_model->find($materialId);

        if (! $material || ! $this->Course_lecturer_model->is_assigned($material['course_id'], $this->current_user_id)) {
            show_error('Not found.', 404);
        }

        $this->Material_model->delete($materialId);
        $this->audit->log('material.deleted', 'material', $materialId, 'Deleted material "' . $material['title'] . '"');
        $this->session->set_flashdata('success', 'Material removed.');
        redirect('lecturer_materials/course/' . $material['course_id']);
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
