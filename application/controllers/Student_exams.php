<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Stage 5: students sit exams.
 *
 *  - has_active_access() on every page and every AJAX call
 *  - one attempt per student, locked to the device (browser session) that
 *    started it; a second device is blocked and the attempt is flagged
 *  - answers save as the student goes (save), a heartbeat keeps the timer in
 *    sync (ping), and the page reports leaving/pasting (event)
 *  - the server's deadline is final: late answers are refused, and an attempt
 *    whose time ran out is handed in automatically the next time anyone looks
 */
class Student_exams extends Student_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Exam_model', 'Exam_attempt_model', 'Enrollment_model']);
        $this->load->helper('ui');
    }

    public function index()
    {
        $this->Exam_attempt_model->finalize_expired(null, $this->current_user_id);

        $groups = ['now' => [], 'upcoming' => [], 'done' => []];
        foreach ($this->Exam_model->for_student($this->current_user_id) as $e) {
            $e['state'] = Exam_model::student_state($e);
            if (in_array($e['state'], ['open', 'writing'], true)) {
                $groups['now'][] = $e;
            } elseif ($e['state'] === 'scheduled') {
                $groups['upcoming'][] = $e;
            } else {
                $groups['done'][] = $e;
            }
        }
        $groups['done'] = array_reverse($groups['done']);

        $this->load->view('templates/header', ['title' => 'Exams']);
        $this->load->view('student/exams_index', [
            'groups'     => $groups,
            'hasCourses' => $this->db->where('user_id', $this->current_user_id)->where('status', 'active')->count_all_results('enrollments') > 0,
        ]);
        $this->load->view('templates/footer');
    }

    /** Before: rules + pledge + Start. During: Continue. After: waiting, or the result. */
    public function view($id)
    {
        $exam = $this->_my_exam($id);
        $this->Exam_attempt_model->finalize_expired($id, $this->current_user_id);
        $attempt = $this->Exam_attempt_model->for_student($id, $this->current_user_id);

        $data = [
            'e'       => $exam,
            'phase'   => Exam_model::phase($exam),
            'a'       => $attempt,
            'count'   => $exam['question_count'] ?: count($this->Exam_model->questions($id)),
            'result'  => null,
        ];
        if ($attempt && $attempt['graded_at'] && $exam['results_released']) {
            $data['result'] = [
                'questions' => $this->Exam_attempt_model->questions_for($attempt, true),
                'answers'   => $this->Exam_attempt_model->answers($attempt['id']),
            ];
        }

        $this->load->view('templates/header', ['title' => $exam['title']]);
        $this->load->view('student/exam_view', $data);
        $this->load->view('templates/footer');
    }

    public function start($id)
    {
        $exam = $this->_my_exam($id);
        if ($this->input->method() !== 'post') {
            return redirect('student_exams/view/' . $id);
        }
        if ($this->Exam_attempt_model->for_student($id, $this->current_user_id)) {
            return redirect('student_exams/take/' . $id);
        }
        if (Exam_model::phase($exam) !== 'open') {
            $this->session->set_flashdata('error', 'This exam is not open right now.');
            return redirect('student_exams/view/' . $id);
        }
        if (! $this->input->post('pledge')) {
            $this->session->set_flashdata('error', 'Please tick the box to promise you will work on your own.');
            return redirect('student_exams/view/' . $id);
        }

        $token   = bin2hex(random_bytes(16));
        $attempt = $this->Exam_attempt_model->start($exam, $this->current_user_id, $token, $this->input->ip_address(), $this->input->user_agent());
        if (! $attempt) {
            $this->session->set_flashdata('error', 'The exam could not be started. Please try again.');
            return redirect('student_exams/view/' . $id);
        }
        $this->session->set_userdata('exam_lock_' . $attempt['id'], $attempt['session_token']);
        $this->audit->log('attempt.started', 'attempt', $attempt['id'], 'Started exam "' . $exam['title'] . '" (' . $exam['course_name'] . ')');
        redirect('student_exams/take/' . $id);
    }

    /** The exam paper itself. */
    public function take($id)
    {
        $exam    = $this->_my_exam($id);
        $attempt = $this->Exam_attempt_model->for_student($id, $this->current_user_id);
        if (! $attempt) {
            return redirect('student_exams/view/' . $id);
        }
        if (! Exam_attempt_model::is_open($attempt)) {
            $this->Exam_attempt_model->finalize($attempt['id'], 'time_up');
            return redirect('student_exams/view/' . $id);
        }
        if (! $this->_device_ok($attempt)) {
            $this->load->view('templates/header', ['title' => $exam['title']]);
            $this->load->view('student/exam_blocked', ['e' => $exam]);
            $this->load->view('templates/footer');
            return;
        }

        $this->Exam_attempt_model->touch($attempt, $this->input->ip_address());
        $this->output->set_header('Cache-Control: no-store');   // never show an old copy of the paper from the browser cache
        $this->load->view('templates/header', ['title' => $exam['title']]);
        $this->load->view('student/exam_take', [
            'e'         => $exam,
            'a'         => $attempt,
            'questions' => $this->Exam_attempt_model->questions_for($attempt),
            'answers'   => $this->Exam_attempt_model->answers($attempt['id']),
            'left'      => Exam_attempt_model::seconds_left($attempt),
        ]);
        $this->load->view('templates/footer');
    }

    /* --------------------------------------------------------- AJAX */

    /** Autosave: answers[question_id] = value (one or many at once). */
    public function save($attemptId)
    {
        $attempt = $this->_ajax_attempt($attemptId);
        if (! $attempt) {
            return;
        }
        $saved = [];
        foreach ((array) $this->input->post('answers') as $qid => $value) {
            if ($this->Exam_attempt_model->save_answer($attempt, (int) $qid, is_array($value) ? '' : $value)) {
                $saved[] = (int) $qid;
            }
        }
        $this->Exam_attempt_model->touch($attempt, $this->input->ip_address());
        $this->_json(['ok' => true, 'saved' => $saved, 'left' => Exam_attempt_model::seconds_left($attempt)]);
    }

    /** Heartbeat every ~30 s: keeps "online" on the invigilation screen and the timer honest. */
    public function ping($attemptId)
    {
        $attempt = $this->_ajax_attempt($attemptId);
        if (! $attempt) {
            return;
        }
        $this->Exam_attempt_model->touch($attempt, $this->input->ip_address());
        $this->_json(['ok' => true, 'left' => Exam_attempt_model::seconds_left($attempt)]);
    }

    /** Something the page noticed: left / returned / paste / bulk_insert / copy / offline. */
    public function event($attemptId)
    {
        $attempt = $this->_ajax_attempt($attemptId);
        if (! $attempt) {
            return;
        }
        $type = (string) $this->input->post('type');
        if (in_array($type, ['left', 'returned', 'paste', 'bulk_insert', 'copy', 'offline'], true)) {
            $seconds = $this->input->post('seconds');
            $this->Exam_attempt_model->log_event($attempt, $type,
                $this->input->post('detail') ? mb_substr((string) $this->input->post('detail'), 0, 200) : null,
                is_numeric($seconds) ? min(86400, max(0, (int) $seconds)) : null);
        }
        $this->_json(['ok' => true]);
    }

    /** Hand in (the button, or automatically when the timer reaches zero). */
    public function submit($attemptId)
    {
        $attempt = $this->Exam_attempt_model->find($attemptId);
        if (! $attempt || (int) $attempt['student_id'] !== (int) $this->current_user_id || $this->input->method() !== 'post') {
            show_404();
        }
        $exam = $this->_my_exam($attempt['exam_id']);

        if (! $attempt['submitted_at'] && $this->_device_ok($attempt)) {
            if (Exam_attempt_model::is_open($attempt)) {
                // The form carries every answer too, in case an autosave never arrived.
                foreach ((array) $this->input->post('answers') as $qid => $value) {
                    $this->Exam_attempt_model->save_answer($attempt, (int) $qid, is_array($value) ? '' : $value);
                }
            }
            $auto = $this->input->post('auto') || ! Exam_attempt_model::is_open($attempt);
            $this->Exam_attempt_model->finalize($attempt['id'], $auto ? 'time_up' : 'student');
            $this->audit->log('attempt.submitted', 'attempt', $attempt['id'], ($auto ? 'Time ran out on ' : 'Handed in ') . '"' . $exam['title'] . '" (' . $exam['course_name'] . ')');
            $this->session->unset_userdata('exam_lock_' . $attempt['id']);
            $this->session->set_flashdata('success', $auto ? 'Time is up. Your answers have been handed in.' : 'Handed in. Well done! Your result will appear here once your lecturer releases it.');
        }
        redirect('student_exams/view/' . $exam['id']);
    }

    /* ------------------------------------------------------------ */

    private function _my_exam($id)
    {
        $exam = $this->Exam_model->find($id);
        if (! $exam || $exam['status'] !== 'published') {
            show_404();
        }
        if (! $this->Enrollment_model->has_active_access($this->current_user_id, $exam['course_id'])) {
            $this->session->set_flashdata('error', 'You do not have access to that course yet.');
            redirect('courses');
        }
        return $exam;
    }

    /**
     * Is this the device that started the attempt? After a lecturer allows a
     * new device, the first one to open the exam takes it over. Other devices
     * are blocked, and that's recorded (at most once a minute).
     */
    private function _device_ok(array $attempt)
    {
        $key  = 'exam_lock_' . $attempt['id'];
        $mine = $this->session->userdata($key);

        if ($attempt['session_token'] === null) {
            $token = bin2hex(random_bytes(16));
            if ($this->Exam_attempt_model->claim_device($attempt, $token)) {
                $this->session->set_userdata($key, $token);
                return true;
            }
            $attempt = $this->Exam_attempt_model->find($attempt['id']);
        }
        if ($mine && hash_equals((string) $attempt['session_token'], (string) $mine)) {
            return true;
        }

        $recent = $this->db->where('attempt_id', $attempt['id'])->where('type', 'device_blocked')
            ->where('created_at >', date('Y-m-d H:i:s', time() - 60))->count_all_results('exam_events');
        if (! $recent) {
            $this->Exam_attempt_model->log_event($attempt, 'device_blocked', mb_substr((string) $this->input->user_agent(), 0, 200));
        }
        return false;
    }

    /** Checks for the AJAX endpoints. Sends the JSON error itself and returns null if not allowed. */
    private function _ajax_attempt($attemptId)
    {
        $attempt = $this->Exam_attempt_model->find($attemptId);
        if (! $attempt || (int) $attempt['student_id'] !== (int) $this->current_user_id || $this->input->method() !== 'post') {
            $this->_json(['ok' => false, 'error' => 'not_found'], 404);
            return null;
        }
        $exam = $this->Exam_model->find($attempt['exam_id']);
        if (! $exam || ! $this->Enrollment_model->has_active_access($this->current_user_id, $exam['course_id'])) {
            $this->_json(['ok' => false, 'error' => 'no_access'], 403);
            return null;
        }
        if ($attempt['submitted_at']) {
            $this->_json(['ok' => false, 'error' => 'submitted']);
            return null;
        }
        if (! Exam_attempt_model::is_open($attempt)) {
            $this->Exam_attempt_model->finalize($attempt['id'], 'time_up');
            $this->_json(['ok' => false, 'error' => 'time_up']);
            return null;
        }
        if (! $this->_device_ok($attempt)) {
            $this->_json(['ok' => false, 'error' => 'other_device']);
            return null;
        }
        return $attempt;
    }

    private function _json(array $data, $status = 200)
    {
        $this->output->set_status_header($status)
            ->set_header('Cache-Control: no-store')
            ->set_content_type('application/json')
            ->set_output(json_encode($data));
    }
}
