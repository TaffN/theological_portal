<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Courses extends Student_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Course_model', 'Enrollment_model', 'Payment_model']);
        $this->load->helper('ui');
    }

    /**
     * Shows every active course, with the student's enrollment status
     * against each one (not enrolled / pending payment / active).
     */
    public function index()
    {
        $courses     = $this->Course_model->active_courses();
        $myCourses   = $this->Enrollment_model->courses_for_student($this->current_user_id);
        $statusByCourseId = [];

        foreach ($myCourses as $mc) {
            $statusByCourseId[$mc['id']] = [
                'status'        => $mc['enrollment_status'],
                'enrollment_id' => $mc['enrollment_id'],
            ];
        }

        foreach ($courses as &$course) {
            $course['lecturers'] = $this->Course_model->lecturers($course['id']);
        }
        unset($course);

        $this->load->view('templates/header', ['title' => 'Courses']);
        $this->load->view('student/courses', [
            'courses'          => $courses,
            'statusByCourseId' => $statusByCourseId,
            'latestPayments'   => $this->Payment_model->latest_by_enrollment_for_student($this->current_user_id),
        ]);
        $this->load->view('templates/footer');
    }

    /**
     * Student clicks "Apply" on a course: creates a pending_payment
     * enrollment if one doesn't already exist.
     */
    public function apply($courseId)
    {
        $course = $this->Course_model->find($courseId);

        if (! $course) {
            show_404();
        }

        $existing = $this->Enrollment_model->courses_for_student($this->current_user_id);
        foreach ($existing as $e) {
            if ($e['id'] == $courseId) {
                $this->session->set_flashdata('error', 'You have already applied for this course.');
                return redirect('courses');
            }
        }

        $enrollmentId = $this->Enrollment_model->create([
            'user_id'   => $this->current_user_id,
            'course_id' => $courseId,
            'status'    => 'pending_payment',
        ]);

        $this->audit->log('enrollment.applied', 'enrollment', $enrollmentId, 'Applied for ' . $course['name']);
        $this->session->set_flashdata('success', 'Application received. Please submit your proof of payment.');
        redirect('payments/upload/' . $enrollmentId);
    }
}
