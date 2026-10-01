<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Class registers for lecturers.
 *   lecturer_attendance                       - your modules with their attendance
 *   lecturer_attendance/module/{id}           - registers taken + each student's rate
 *   lecturer_attendance/take/{module}[/{session}] - take (or correct) a register; POST saves
 *   lecturer_attendance/delete/{session}      - POST
 */
class Lecturer_attendance extends Lecturer_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Attendance_model', 'Module_model', 'Module_lecturer_model', 'Enrollment_model']);
        $this->load->helper('ui');
        if (! $this->db->table_exists('attendance')) {   // code updated, database not yet (visit /migrate)
            $this->session->set_flashdata('error', 'Attendance needs a database update first. An administrator should run the latest update (/migrate).');
            redirect('dashboard');
        }
    }

    public function index()
    {
        $modules = $this->Module_model->for_user($this->current_user_id, 'lecturer');
        foreach ($modules as &$c) {
            $sessions = $this->Attendance_model->sessions($c['id']);
            $p = $l = $a = 0;
            foreach ($sessions as $s) { $p += $s['present']; $l += $s['late']; $a += $s['absent']; }
            $c['sessions'] = count($sessions);
            $c['last']     = $sessions ? $sessions[0]['session_date'] : null;
            $c['rate']     = Attendance_model::rate($p, $l, $a);
            $c['students'] = count($this->Enrollment_model->active_students_for_module($c['id']));
        }
        unset($c);
        $this->load->view('templates/header', ['title' => 'Attendance']);
        $this->load->view('attendance/lecturer_index', ['modules' => $modules]);
        $this->load->view('templates/footer');
    }

    public function module($moduleId = null)
    {
        $module = $this->module_or_404($moduleId);
        $this->load->view('templates/header', ['title' => 'Attendance: ' . $module['name']]);
        $this->load->view('attendance/module', [
            'module'   => $module,
            'sessions' => $this->Attendance_model->sessions($module['id']),
            'summary'  => $this->Attendance_model->summary($module['id']),
            'canTake'  => true,
            'back'     => 'lecturer_attendance',
        ]);
        $this->load->view('templates/footer');
    }

    public function take($moduleId = null, $sessionId = null)
    {
        $module  = $this->module_or_404($moduleId);
        $session = null;
        if ($sessionId) {
            $session = $this->Attendance_model->find_session($sessionId);
            if (! $session || (int) $session['module_id'] !== (int) $module['id']) {
                show_404();
            }
        }
        $students = $this->db->select('u.id, u.name, u.id_number, u.photo_path, u.photo_updated_at')->from('enrollments e')->join('users u', 'u.id = e.user_id')
            ->where('e.module_id', $module['id'])->where('e.status', 'active')->order_by('u.name')->get()->result_array();

        if ($this->input->method() === 'post') {
            $date  = (string) $this->input->post('session_date');
            $topic = mb_substr(trim((string) $this->input->post('topic')), 0, 200);
            if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || ! strtotime($date) || $date > date('Y-m-d', strtotime('+1 day'))) {
                $this->session->set_flashdata('error', 'Please choose the date of the class (not in the future).');
                return redirect('lecturer_attendance/take/' . $module['id'] . ($session ? '/' . $session['id'] : ''));
            }
            $posted = (array) $this->input->post('status');
            $notes  = (array) $this->input->post('note');
            $marks = [];
            foreach ($students as $s) {
                $st = isset($posted[$s['id']]) ? (string) $posted[$s['id']] : 'present';
                $marks[(int) $s['id']] = [
                    'status' => isset(Attendance_model::$statuses[$st]) ? $st : 'present',
                    'note'   => mb_substr(trim(isset($notes[$s['id']]) ? (string) $notes[$s['id']] : ''), 0, 255),
                ];
            }
            $id = $this->Attendance_model->save_session($module['id'], $date, $topic ?: null, $this->current_user_id, $marks, $session ? $session['id'] : null);
            $count = array_count_values(array_column($marks, 'status'));
            $this->audit->log($session ? 'attendance.updated' : 'attendance.taken', 'attendance', $id,
                ($session ? 'Corrected' : 'Took') . ' the register for ' . $module['name'] . ' on ' . date('j M Y', strtotime($date))
                . ' (' . (isset($count['present']) ? $count['present'] : 0) . ' present, ' . (isset($count['absent']) ? $count['absent'] : 0) . ' absent)');
            $this->session->set_flashdata('success', 'Register saved for ' . date('l j F', strtotime($date)) . '.');
            return redirect('lecturer_attendance/module/' . $module['id']);
        }

        $this->load->view('templates/header', ['title' => 'Take attendance']);
        $this->load->view('attendance/take', [
            'module'   => $module,
            'session'  => $session,
            'students' => $students,
            'marks'    => $session ? $this->Attendance_model->marks($session['id']) : [],
            'statuses' => Attendance_model::$statuses,
        ]);
        $this->load->view('templates/footer');
    }

    public function delete($sessionId = null)
    {
        $session = $this->Attendance_model->find_session($sessionId);
        if (! $session) {
            show_404();
        }
        $module = $this->module_or_404($session['module_id']);
        if ($this->input->method() === 'post') {
            $this->Attendance_model->delete_session($session['id']);
            $this->audit->log('attendance.deleted', 'attendance', $session['id'], 'Deleted the ' . $module['name'] . ' register of ' . date('j M Y', strtotime($session['session_date'])));
            $this->session->set_flashdata('success', 'Register deleted.');
        }
        redirect('lecturer_attendance/module/' . $module['id']);
    }

    private function module_or_404($moduleId)
    {
        $module = $this->Module_model->find($moduleId);
        if (! $module || ! $this->Module_lecturer_model->is_assigned($module['id'], $this->current_user_id)) {
            show_404();
        }
        return $module;
    }
}
