<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin_courses extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('form_validation');
        $this->load->model(['Course_model', 'Course_lecturer_model', 'User_model']);
    }

    public function index()
    {
        $courses = $this->Course_model->all();

        foreach ($courses as &$course) {
            $course['lecturers'] = $this->Course_model->lecturers($course['id']);
        }
        unset($course);

        $this->load->view('templates/header', ['title' => 'Manage Courses']);
        $this->load->view('admin/courses', [
            'courses'   => $courses,
            'lecturers' => $this->User_model->find_lecturers(),
        ]);
        $this->load->view('templates/footer');
    }

    public function create_course()
    {
        $this->form_validation->set_rules('name', 'Course name', 'required|max_length[200]');
        $this->form_validation->set_rules('fee_amount', 'Fee', 'required|numeric|greater_than_equal_to[0]');

        if ($this->form_validation->run()) {
            $courseId = $this->Course_model->create([
                'name'          => $this->input->post('name'),
                'description'   => $this->input->post('description'),
                'fee_amount'    => $this->input->post('fee_amount'),
                'duration_text' => $this->input->post('duration_text'),
                'status'        => 'active',
            ]);
            $this->audit->log('course.created', 'course', $courseId, 'Created course "' . $this->input->post('name') . '"');
            $this->session->set_flashdata('success', 'Course created.');
        } else {
            $this->session->set_flashdata('error', strip_tags(validation_errors()));
        }

        redirect('admin_courses');
    }

    public function edit($id)
    {
        $course = $this->Course_model->find($id);
        if (! $course) {
            show_404();
        }

        if ($this->input->method() === 'post') {
            $this->form_validation->set_rules('name', 'Course name', 'required|max_length[200]');
            $this->form_validation->set_rules('fee_amount', 'Fee', 'required|numeric|greater_than_equal_to[0]');
            if ($this->form_validation->run()) {
                $data = [
                    'name'          => $this->input->post('name'),
                    'description'   => $this->input->post('description'),
                    'fee_amount'    => $this->input->post('fee_amount'),
                    'duration_text' => $this->input->post('duration_text'),
                ];
                $this->Course_model->update($id, $data);
                $this->audit->log('course.updated', 'course', $id, 'Edited course "' . $data['name'] . '"'
                    . ((float) $course['fee_amount'] !== (float) $data['fee_amount'] ? ' (fee ' . $course['fee_amount'] . ' -> ' . $data['fee_amount'] . ')' : ''));
                $this->session->set_flashdata('success', 'Course updated.');
                return redirect('admin_courses');
            }
        }

        $this->load->helper('ui');
        $this->load->view('templates/header', ['title' => 'Edit course']);
        $this->load->view('admin/course_edit', ['course' => $course]);
        $this->load->view('templates/footer');
    }

    public function toggle($id)
    {
        $course = $this->Course_model->find($id);
        if (! $course) {
            show_404();
        }
        $status = $course['status'] === 'active' ? 'inactive' : 'active';
        $this->Course_model->update($id, ['status' => $status]);
        $this->audit->log('course.' . ($status === 'active' ? 'opened' : 'closed'), 'course', $id,
            ($status === 'active' ? 'Re-opened' : 'Closed') . ' course "' . $course['name'] . '" for new applications');
        $this->session->set_flashdata('success', $status === 'active'
            ? $course['name'] . ' is open for applications again.'
            : $course['name'] . ' is closed to new applications. Existing students keep their access.');
        redirect('admin_courses');
    }

    public function assign()
    {
        $courseId = $this->input->post('course_id');
        $userId   = $this->input->post('user_id');

        if ($courseId && $userId) {
            $this->Course_lecturer_model->assign($courseId, $userId);
            $this->audit->log('course.lecturer_assigned', 'course', $courseId, 'Assigned lecturer #' . (int) $userId . ' to course #' . (int) $courseId);
            $this->session->set_flashdata('success', 'Lecturer assigned.');
        } else {
            $this->session->set_flashdata('error', 'Choose both a course and a lecturer.');
        }

        redirect('admin_courses');
    }

    public function unassign($courseId, $userId)
    {
        $this->Course_lecturer_model->unassign($courseId, $userId);
        $this->audit->log('course.lecturer_removed', 'course', $courseId, 'Removed lecturer #' . (int) $userId . ' from course #' . (int) $courseId);
        $this->session->set_flashdata('success', 'Lecturer removed from course.');
        redirect('admin_courses');
    }
}
