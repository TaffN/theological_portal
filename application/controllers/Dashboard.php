<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Everyone lands on /dashboard after login; what they see depends on role.
 */
class Dashboard extends Auth_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Dashboard_model', 'Enrollment_model', 'Error_model']);
        $this->load->helper(['ui', 'chart']);
    }

    public function index()
    {
        switch ($this->current_role) {
            case 'admin':
                $view = 'dashboard/admin';
                $data = $this->_admin_data();
                break;
            case 'lecturer':
                $view = 'dashboard/lecturer';
                $data = $this->_lecturer_data();
                break;
            default:
                $view = 'dashboard/student';
                $data = $this->_student_data();
        }

        $data['first_name'] = display_first_name($this->session->userdata('name'), $this->current_role);
        $data['announcements'] = $this->Dashboard_model->announcements_for($this->current_role);

        // A small touch: greet people on their birthday.
        $data['birthday'] = false;
        if ($this->db->table_exists('user_profiles')) {
            $p = $this->db->select('date_of_birth')->where('user_id', $this->current_user_id)->get('user_profiles')->row_array();
            $data['birthday'] = $p && $p['date_of_birth'] && date('m-d', strtotime($p['date_of_birth'])) === date('m-d');
        }

        $this->load->view('templates/header', ['title' => 'Dashboard']);
        $this->load->view($view, $data);
        $this->load->view('templates/footer');
    }

    private function _admin_data()
    {
        return [
            'stats'         => $this->Dashboard_model->admin_stats(),
            'fees'          => $this->Dashboard_model->fees_by_month(6),
            'by_course'     => $this->Dashboard_model->enrollments_by_course(),
            'status_counts' => $this->Dashboard_model->enrollment_status_counts(),
            'payments'      => $this->Dashboard_model->recent_payments(6),
            'new_students'  => $this->Dashboard_model->recent_students(5),
            'checklist'     => $this->Dashboard_model->admin_checklist($this->current_user_id),
            'activity'      => $this->Dashboard_model->recent_activity(8),
            'logins_today'  => $this->Dashboard_model->logins_today(),
            'failed_today'  => $this->Dashboard_model->failed_logins_today(),
            'errors'        => $this->Error_model->counts(),
            'resets'        => $this->db->where('role !=', 'admin')->where('reset_requested_at IS NOT NULL', null, false)->count_all_results('users'),
        ];
    }

    private function _student_data()
    {
        $uid     = $this->current_user_id;
        $courses = $this->Enrollment_model->courses_for_student($uid);

        $active = 0;
        $awaiting = 0;
        foreach ($courses as $c) {
            if ($c['enrollment_status'] === 'active') {
                $active++;
            } elseif ($c['enrollment_status'] === 'pending_payment') {
                $awaiting++;
            }
        }

        return [
            'courses'        => $courses,
            'active_count'   => $active,
            'awaiting_count' => $awaiting,
            'proof_pending'  => $this->Dashboard_model->student_enrollments_with_pending_proof($uid),
            'new_materials'  => $this->Dashboard_model->student_new_materials_count($uid, 7),
            'unread'         => $this->Dashboard_model->unread_notifications($uid),
            'materials'      => $this->Dashboard_model->student_recent_materials($uid, 5),
            'checklist'      => $this->Dashboard_model->student_checklist($uid, $courses),
            'due'            => $this->_assignments() ? array_slice($this->Assignment_model->student_outstanding($uid), 0, 5) : [],
            'exams'          => $this->_student_exams($uid),
        ];
    }

    private function _lecturer_data()
    {
        $uid     = $this->current_user_id;
        $courses = $this->Dashboard_model->lecturer_courses_with_counts($uid);

        return [
            'courses'        => $courses,
            'student_total'  => array_sum(array_column($courses, 'students')),
            'material_count' => $this->Dashboard_model->lecturer_materials_count($uid),
            'materials'      => $this->Dashboard_model->lecturer_recent_materials($uid, 5),
            'unread'         => $this->Dashboard_model->unread_notifications($uid),
            'to_mark'        => $this->_assignments() ? $this->Assignment_model->to_mark_count($uid) : 0,
            'exam_to_mark'   => $this->_exams() ? $this->Exam_model->to_mark_count($uid) : 0,
        ];
    }

    /** Exams open now, in progress or coming up (not finished ones), soonest first. */
    private function _student_exams($uid)
    {
        if (! $this->_exams()) {
            return [];
        }
        $this->Exam_attempt_model->finalize_expired(null, $uid);
        $out = [];
        foreach ($this->Exam_model->for_student($uid) as $e) {
            $e['state'] = Exam_model::student_state($e);
            if (in_array($e['state'], ['open', 'writing', 'scheduled'], true)) {
                $out[] = $e;
            }
        }
        return array_slice($out, 0, 5);
    }

    /** Loads the exam models, once migration 016 has been run. */
    private function _exams()
    {
        if (! $this->db->table_exists('exams')) {
            return false;
        }
        $this->load->model(['Exam_model', 'Exam_attempt_model']);
        return true;
    }

    /** Loads the assignments model, once migration 015 has been run. */
    private function _assignments()
    {
        if (! $this->db->table_exists('assignments')) {
            return false;
        }
        $this->load->model('Assignment_model');
        return true;
    }
}
