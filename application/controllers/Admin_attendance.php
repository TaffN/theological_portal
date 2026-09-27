<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Attendance for administrators: every course at a glance, each course's
 * registers and students, and an Excel (CSV) export. Lecturers take registers.
 */
class Admin_attendance extends Admin_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Attendance_model', 'Course_model']);
        $this->load->helper('ui');
        if (! $this->db->table_exists('attendance')) {   // code updated, database not yet (visit /migrate)
            $this->session->set_flashdata('error', 'Attendance needs a database update first. Run the latest update (/migrate).');
            redirect('dashboard');
        }
    }

    public function index()
    {
        $this->load->view('templates/header', ['title' => 'Attendance']);
        $this->load->view('attendance/admin_index', ['courses' => $this->Attendance_model->overview()]);
        $this->load->view('templates/footer');
    }

    public function course($courseId = null)
    {
        $course = $this->Course_model->find($courseId);
        if (! $course) {
            show_404();
        }
        $this->load->view('templates/header', ['title' => 'Attendance: ' . $course['name']]);
        $this->load->view('attendance/course', [
            'course'   => $course,
            'sessions' => $this->Attendance_model->sessions($course['id']),
            'summary'  => $this->Attendance_model->summary($course['id']),
            'canTake'  => false,
            'back'     => 'admin_attendance',
        ]);
        $this->load->view('templates/footer');
    }

    /** One row per student per register, for Excel. */
    public function export($courseId = null)
    {
        $course = $this->Course_model->find($courseId);
        if (! $course) {
            show_404();
        }
        $rows = $this->db->select('s.session_date, s.topic, u.id_number, u.name, a.status, a.note')
            ->from('attendance a')->join('attendance_sessions s', 's.id = a.session_id')->join('users u', 'u.id = a.student_id')
            ->where('s.course_id', $course['id'])->order_by('s.session_date')->order_by('u.name')->get()->result_array();
        $this->audit->log('attendance.exported', 'course', $course['id'], 'Exported the attendance of ' . $course['name'] . ' to CSV');

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="attendance-' . url_title($course['name'], '-', true) . '-' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Date', 'Topic', 'Student no.', 'Student', 'Status', 'Note']);
        foreach ($rows as $r) {
            fputcsv($out, [$r['session_date'], $r['topic'], $r['id_number'], $r['name'], ucfirst($r['status']), $r['note']]);
        }
        fclose($out);
    }
}
