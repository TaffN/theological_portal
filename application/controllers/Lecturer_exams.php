<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Stage 5: lecturers write exams, publish them, watch them live
 * (invigilation), mark short answers and release results.
 *
 * Every action checks Course_lecturer_model::is_assigned(). Questions are
 * locked once any student has started, so nobody's paper changes under them.
 */
class Lecturer_exams extends Lecturer_Controller
{
    const MAX_OPTIONS = 6;

    public function __construct()
    {
        parent::__construct();
        $this->load->library(['form_validation', 'notifier']);
        $this->load->model(['Exam_model', 'Exam_attempt_model', 'Course_lecturer_model', 'Course_model']);
        $this->load->helper('ui');
    }

    public function index()
    {
        $this->Exam_attempt_model->finalize_expired();

        $groups = [];
        foreach ($this->Course_lecturer_model->courses_for_lecturer($this->current_user_id) as $c) {
            $groups[$c['id']] = ['course' => $c, 'exams' => []];
        }
        foreach ($this->Exam_model->for_lecturer($this->current_user_id) as $e) {
            if (isset($groups[$e['course_id']])) {
                $groups[$e['course_id']]['exams'][] = $e;
            }
        }

        $this->load->view('templates/header', ['title' => 'Exams']);
        $this->load->view('lecturer/exams_index', ['groups' => $groups]);
        $this->load->view('templates/footer');
    }

    /* ------------------------------------------------------ EXAM DETAILS */

    public function create($courseId)
    {
        $course = $this->_my_course($courseId);

        if ($this->input->method() === 'post' && $this->_validate_exam(null)) {
            $data = $this->_exam_data(true);
            $data['course_id']   = $course['id'];
            $data['lecturer_id'] = $this->current_user_id;
            $data['status']      = 'draft';
            $id = $this->Exam_model->create($data);

            $this->audit->log('exam.created', 'exam', $id, 'Created exam "' . $data['title'] . '" (draft) in ' . $course['name']);
            $this->session->set_flashdata('success', 'Exam created as a draft. Now add the questions; students can\'t see it until you publish it.');
            return redirect('lecturer_exams/view/' . $id);
        }

        $this->load->view('templates/header', ['title' => 'New exam']);
        $this->load->view('lecturer/exam_form', ['course' => $course, 'e' => null, 'locked' => false]);
        $this->load->view('templates/footer');
    }

    public function edit($id)
    {
        $exam   = $this->_my_exam($id);
        $locked = $this->Exam_attempt_model->count_for_exam($id) > 0;

        if ($this->input->method() === 'post' && $this->_validate_exam($exam)) {
            $data = $this->_exam_data(! $locked);
            $this->Exam_model->update($id, $data);

            $timesChanged = strtotime($data['opens_at']) !== strtotime($exam['opens_at']) || strtotime($data['closes_at']) !== strtotime($exam['closes_at']);
            $this->audit->log('exam.updated', 'exam', $id, 'Edited exam "' . $data['title'] . '" in ' . $exam['course_name']
                . ($timesChanged ? ', window now ' . date('j M H:i', strtotime($data['opens_at'])) . ' – ' . date('j M H:i', strtotime($data['closes_at'])) : ''));
            if ($timesChanged && $exam['status'] === 'published') {
                $this->notifier->notify_course($exam['course_id'],
                    'Exam time changed: "' . $data['title'] . '" (' . $exam['course_name'] . ') now opens ' . date('D j M, H:i', strtotime($data['opens_at'])),
                    base_url('student_exams/view/' . $id));
            }

            $this->session->set_flashdata('success', 'Exam details saved.' . ($timesChanged && $exam['status'] === 'published' ? ' Students were told about the new times.' : ''));
            return redirect('lecturer_exams/view/' . $id);
        }

        $this->load->view('templates/header', ['title' => 'Edit exam']);
        $this->load->view('lecturer/exam_form', [
            'course' => ['id' => $exam['course_id'], 'name' => $exam['course_name']],
            'e'      => $exam,
            'locked' => $locked,
        ]);
        $this->load->view('templates/footer');
    }

    /** The exam's home: details, questions, publish button, and everyone's results. */
    public function view($id)
    {
        $exam = $this->_my_exam($id);
        $this->Exam_attempt_model->finalize_expired($id);

        $questions = $this->Exam_model->questions($id);
        $roster    = $this->Exam_attempt_model->roster($exam);
        $started = 0; $submitted = 0; $toMark = 0; $scores = [];
        foreach ($roster as $r) {
            if ($r['att_id']) {
                $started++;
                if ($r['submitted_at']) {
                    $submitted++;
                    if ($r['graded_at']) {
                        $scores[] = $r['max_score'] > 0 ? $r['total_score'] / $r['max_score'] * 100 : 0;
                    } else {
                        $toMark++;
                    }
                }
            }
        }

        $this->load->view('templates/header', ['title' => $exam['title']]);
        $this->load->view('lecturer/exam_view', [
            'e'         => $exam,
            'phase'     => Exam_model::phase($exam),
            'questions' => $questions,
            'poolMarks' => $this->Exam_model->pool_marks($questions),
            'locked'    => $started > 0,
            'problems'  => $exam['status'] === 'draft' ? $this->Exam_model->publish_problems($exam) : [],
            'roster'    => $roster,
            'stats'     => ['students' => count($roster), 'started' => $started, 'submitted' => $submitted, 'to_mark' => $toMark,
                            'average' => $scores ? array_sum($scores) / count($scores) : null],
        ]);
        $this->load->view('templates/footer');
    }

    public function publish($id)
    {
        $exam = $this->_my_exam($id, true);
        if ($exam['status'] === 'published') {
            return redirect('lecturer_exams/view/' . $id);
        }
        $problems = $this->Exam_model->publish_problems($exam);
        if ($problems) {
            $this->session->set_flashdata('error', 'Not published yet: ' . implode(' ', $problems));
            return redirect('lecturer_exams/view/' . $id);
        }

        $this->Exam_model->update($id, ['status' => 'published', 'published_at' => date('Y-m-d H:i:s')]);
        $this->audit->log('exam.published', 'exam', $id, 'Published exam "' . $exam['title'] . '" in ' . $exam['course_name']
            . ', open ' . date('j M H:i', strtotime($exam['opens_at'])) . ' – ' . date('j M H:i', strtotime($exam['closes_at'])));
        $sent = $this->notifier->notify_course($exam['course_id'],
            'New exam in ' . $exam['course_name'] . ': "' . $exam['title'] . '", opens ' . date('D j M, H:i', strtotime($exam['opens_at']))
                . ' (' . (int) $exam['duration_minutes'] . ' minutes)',
            base_url('student_exams/view/' . $id));

        $this->session->set_flashdata('success', 'Exam published. ' . $sent . ' student' . ($sent == 1 ? ' was' : 's were') . ' notified.');
        redirect('lecturer_exams/view/' . $id);
    }

    /** Back to draft, e.g. to fix a question. Only before anyone starts. */
    public function unpublish($id)
    {
        $exam = $this->_my_exam($id, true);
        if ($this->Exam_attempt_model->count_for_exam($id) > 0) {
            $this->session->set_flashdata('error', 'Students have already started this exam, so it can\'t go back to draft.');
            return redirect('lecturer_exams/view/' . $id);
        }
        $this->Exam_model->update($id, ['status' => 'draft']);
        $this->audit->log('exam.unpublished', 'exam', $id, 'Moved exam "' . $exam['title'] . '" back to draft');
        $this->session->set_flashdata('success', 'The exam is a draft again and hidden from students.');
        redirect('lecturer_exams/view/' . $id);
    }

    public function delete($id)
    {
        $exam = $this->_my_exam($id, true);
        if ($this->Exam_attempt_model->count_for_exam($id) > 0) {
            $this->session->set_flashdata('error', 'Students have already sat this exam, so it can\'t be deleted.');
            return redirect('lecturer_exams/view/' . $id);
        }
        $this->Exam_model->delete($id);
        $this->audit->log('exam.deleted', 'exam', $id, 'Deleted exam "' . $exam['title'] . '" from ' . $exam['course_name']);
        $this->session->set_flashdata('success', 'Exam deleted.');
        redirect('lecturer_exams');
    }

    /* -------------------------------------------------------- QUESTIONS */

    /** Add a question (no $questionId) or edit one. */
    public function question($examId, $questionId = null)
    {
        $exam = $this->_my_exam($examId);
        $q    = null;
        if ($questionId) {
            $q = $this->Exam_model->find_question($questionId);
            if (! $q || (int) $q['exam_id'] !== (int) $exam['id']) {
                show_404();
            }
        }
        if ($this->Exam_attempt_model->count_for_exam($examId) > 0) {
            $this->session->set_flashdata('error', 'Students have started this exam, so its questions are locked.');
            return redirect('lecturer_exams/view/' . $examId);
        }

        $formError = null;
        if ($this->input->method() === 'post') {
            list($data, $formError) = $this->_question_data();
            if (! $formError) {
                if ($q) {
                    $this->Exam_model->update_question($q['id'], $data);
                } else {
                    $this->Exam_model->add_question($exam['id'], $data);
                }
                $this->session->set_flashdata('success', $q ? 'Question updated.' : 'Question added.');
                if (! $q && $this->input->post('then') === 'another') {
                    return redirect('lecturer_exams/question/' . $exam['id'] . '?type=' . $data['type']);
                }
                return redirect('lecturer_exams/view/' . $exam['id'] . '#questions');
            }
        }

        $count = count($this->Exam_model->questions($exam['id']));
        $this->load->view('templates/header', ['title' => $q ? 'Edit question' : 'Add a question']);
        $this->load->view('lecturer/exam_question_form', [
            'e'         => $exam,
            'q'         => $q,
            'number'    => $q ? null : $count + 1,
            'formError' => $formError,
            'maxOptions'=> self::MAX_OPTIONS,
        ]);
        $this->load->view('templates/footer');
    }

    public function delete_question($id)
    {
        list($q, $exam) = $this->_my_question($id);
        $this->Exam_model->delete_question($id);
        $this->session->set_flashdata('success', 'Question deleted.');
        redirect('lecturer_exams/view/' . $exam['id'] . '#questions');
    }

    public function move_question($id, $direction = 'up')
    {
        list($q, $exam) = $this->_my_question($id);
        $this->Exam_model->move_question($q, $direction === 'up' ? -1 : 1);
        redirect('lecturer_exams/view/' . $exam['id'] . '#q-' . $q['id']);
    }

    /* ----------------------------------------------------- INVIGILATION */

    /** Live view while the exam runs. The table refreshes itself (see live()). */
    public function invigilate($id)
    {
        $exam = $this->_my_exam($id);
        $this->load->view('templates/header', ['title' => 'Invigilate: ' . $exam['title']]);
        $this->load->view('lecturer/exam_invigilate', ['e' => $exam, 'phase' => Exam_model::phase($exam)] + $this->_live_data($exam));
        $this->load->view('templates/footer');
    }

    /** Just the table rows, fetched every few seconds by the invigilation page. */
    public function live($id)
    {
        $exam = $this->_my_exam($id);
        $this->output->set_header('Cache-Control: no-store');
        $this->load->view('lecturer/_invigilate_rows', ['e' => $exam] + $this->_live_data($exam));
    }

    /** The student's phone died: let them continue on another device. */
    public function reset_device($attemptId)
    {
        list($attempt, $exam) = $this->_my_attempt($attemptId, true);
        if ($attempt['submitted_at']) {
            $this->session->set_flashdata('error', 'That attempt has already been handed in.');
        } else {
            $this->Exam_attempt_model->reset_device($attempt);
            $this->load->model('User_model');
            $student = $this->User_model->find($attempt['student_id']);
            $this->audit->log('attempt.device_reset', 'attempt', $attempt['id'], 'Allowed ' . $student['name'] . ' to continue "' . $exam['title'] . '" on a new device');
            $this->session->set_flashdata('success', $student['name'] . ' can now open the exam on another phone or computer. Their saved answers are kept, and the timer keeps running.');
        }
        redirect($this->input->post('back') === 'attempt' ? 'lecturer_exams/attempt/' . $attempt['id'] : 'lecturer_exams/invigilate/' . $exam['id']);
    }

    /* ---------------------------------------------------------- MARKING */

    /** One student's script: answers, marking of short answers, and the activity log. */
    public function attempt($attemptId)
    {
        list($attempt, $exam) = $this->_my_attempt($attemptId);
        $this->Exam_attempt_model->finalize_expired($exam['id']);
        $attempt = $this->Exam_attempt_model->find($attemptId);

        $this->load->model('User_model');
        $student = $this->User_model->find($attempt['student_id']);

        if ($this->input->method() === 'post') {
            if (! $attempt['submitted_at']) {
                $this->session->set_flashdata('error', 'The student is still writing. You can mark it once it is handed in.');
                return redirect('lecturer_exams/attempt/' . $attemptId);
            }
            $wasGraded = (bool) $attempt['graded_at'];
            $oldTotal  = $attempt['total_score'];
            list($ok, $msg) = $this->Exam_attempt_model->mark($attempt, (array) $this->input->post('marks'), trim((string) $this->input->post('feedback')), $this->current_user_id);
            if (! $ok) {
                $this->session->set_flashdata('error', $msg);
                return redirect('lecturer_exams/attempt/' . $attemptId);
            }
            $attempt = $this->Exam_attempt_model->find($attemptId);
            if ($attempt['graded_at']) {
                $this->audit->log('attempt.marked', 'attempt', $attemptId, ($wasGraded ? 'Changed mark for ' : 'Marked ') . $student['name'] . '\'s "' . $exam['title'] . '": '
                    . score_fmt($attempt['total_score']) . '/' . score_fmt($attempt['max_score']) . ($wasGraded && $oldTotal !== null ? ' (was ' . score_fmt($oldTotal) . ')' : ''));
                if ($exam['results_released']) {   // results already out: tell the student straight away
                    $this->notifier->notify_user($student['id'], 'Your result for "' . $exam['title'] . '" ' . ($wasGraded ? 'was updated' : 'is ready') . ': '
                        . score_fmt($attempt['total_score']) . '/' . score_fmt($attempt['max_score']), base_url('student_exams/view/' . $exam['id']));
                }
            }
            $this->session->set_flashdata('success', $msg ? $msg : 'Marks saved for ' . $student['name'] . '.');
            return redirect($this->input->post('next') ? 'lecturer_exams/attempt/' . (int) $this->input->post('next') : 'lecturer_exams/attempt/' . $attemptId);
        }

        // "Next script to mark" button.
        $next = $this->db->select('id')->where('exam_id', $exam['id'])->where('id !=', $attemptId)
            ->where('submitted_at IS NOT NULL', null, false)->where('graded_at IS NULL', null, false)
            ->order_by('id', 'ASC')->limit(1)->get('exam_attempts')->row_array();

        $this->load->view('templates/header', ['title' => $student['name'] . ' – ' . $exam['title']]);
        $this->load->view('lecturer/exam_attempt', [
            'e'         => $exam,
            'a'         => $attempt,
            'student'   => $student,
            'questions' => $this->Exam_attempt_model->questions_for($attempt, true),
            'answers'   => $this->Exam_attempt_model->answers($attemptId),
            'events'    => $this->Exam_attempt_model->events($attemptId),
            'nextId'    => $next ? (int) $next['id'] : null,
        ]);
        $this->load->view('templates/footer');
    }

    /** Students see their marks only after this. */
    public function release($id)
    {
        $exam = $this->_my_exam($id, true);
        $this->Exam_attempt_model->finalize_expired($id);

        $inProgress = $this->db->where('exam_id', $id)->where('submitted_at IS NULL', null, false)->count_all_results('exam_attempts');
        $unmarked   = $this->db->where('exam_id', $id)->where('submitted_at IS NOT NULL', null, false)->where('graded_at IS NULL', null, false)->count_all_results('exam_attempts');
        if ($exam['results_released']) {
            return redirect('lecturer_exams/view/' . $id);
        }
        if ($inProgress || $unmarked) {
            $this->session->set_flashdata('error', 'Not yet: ' . ($inProgress ? $inProgress . ' still writing. ' : '') . ($unmarked ? $unmarked . ' script' . ($unmarked == 1 ? '' : 's') . ' still to mark.' : ''));
            return redirect('lecturer_exams/view/' . $id);
        }

        $this->Exam_model->update($id, ['results_released' => 1, 'released_at' => date('Y-m-d H:i:s')]);
        $attempts = $this->db->where('exam_id', $id)->where('graded_at IS NOT NULL', null, false)->get('exam_attempts')->result_array();
        foreach ($attempts as $a) {
            $this->notifier->notify_user($a['student_id'], 'Your result for "' . $exam['title'] . '" is out: ' . score_fmt($a['total_score']) . '/' . score_fmt($a['max_score']),
                base_url('student_exams/view/' . $id));
        }
        $this->audit->log('exam.results_released', 'exam', $id, 'Released results of "' . $exam['title'] . '" to ' . count($attempts) . ' student' . (count($attempts) == 1 ? '' : 's'));
        $this->session->set_flashdata('success', 'Results released. ' . count($attempts) . ' student' . (count($attempts) == 1 ? ' was' : 's were') . ' notified.');
        redirect('lecturer_exams/view/' . $id);
    }

    /* ------------------------------------------------------------ */

    private function _live_data(array $exam)
    {
        $this->Exam_attempt_model->finalize_expired($exam['id']);
        $rows = $this->Exam_attempt_model->roster($exam);
        $counts = ['not_started' => 0, 'writing' => 0, 'handed_in' => 0, 'flagged' => 0];
        foreach ($rows as $r) {
            if (! $r['att_id']) {
                $counts['not_started']++;
            } elseif (! $r['submitted_at']) {
                $counts['writing']++;
            } else {
                $counts['handed_in']++;
            }
            if ($r['flag_count'] > 0) {
                $counts['flagged']++;
            }
        }
        return ['rows' => $rows, 'counts' => $counts];
    }

    private function _my_course($courseId)
    {
        $course = $this->Course_model->find($courseId);
        if (! $course || ! $this->Course_lecturer_model->is_assigned($courseId, $this->current_user_id)) {
            show_error('You are not assigned to that course.', 403);
        }
        return $course;
    }

    /** $postOnly: actions that change things only through a button (POST), never a plain link. */
    private function _my_exam($id, $postOnly = false)
    {
        $exam = $this->Exam_model->find($id);
        if (! $exam || ! $this->Course_lecturer_model->is_assigned($exam['course_id'], $this->current_user_id)
            || ($postOnly && $this->input->method() !== 'post')) {
            show_404();
        }
        return $exam;
    }

    private function _my_question($id)
    {
        $q = $this->Exam_model->find_question($id);
        if (! $q || $this->input->method() !== 'post') {
            show_404();
        }
        $exam = $this->_my_exam($q['exam_id']);
        if ($this->Exam_attempt_model->count_for_exam($exam['id']) > 0) {
            $this->session->set_flashdata('error', 'Students have started this exam, so its questions are locked.');
            redirect('lecturer_exams/view/' . $exam['id']);
        }
        return [$q, $exam];
    }

    private function _my_attempt($attemptId, $postOnly = false)
    {
        $attempt = $this->Exam_attempt_model->find($attemptId);
        if (! $attempt) {
            show_404();
        }
        $exam = $this->_my_exam($attempt['exam_id'], $postOnly);
        return [$attempt, $exam];
    }

    private function _validate_exam($exam)
    {
        $this->form_validation->set_rules('title', 'Title', 'required|max_length[200]');
        $this->form_validation->set_rules('opens_at', 'Opens', 'required|callback__valid_datetime');
        $this->form_validation->set_rules('closes_at', 'Closes', 'required|callback__valid_datetime|callback__after_open');
        $this->form_validation->set_rules('duration_minutes', 'Time allowed', 'required|is_natural_no_zero|less_than_equal_to[600]');
        $this->form_validation->set_rules('question_count', 'Questions per student', 'is_natural_no_zero|less_than_equal_to[500]');
        return $this->form_validation->run();
    }

    public function _valid_datetime($value)
    {
        if (strtotime((string) $value) === false) {
            $this->form_validation->set_message('_valid_datetime', 'Please pick a valid date and time for {field}.');
            return false;
        }
        return true;
    }

    public function _after_open($value)
    {
        $open = strtotime((string) $this->input->post('opens_at'));
        if ($open !== false && strtotime((string) $value) <= $open) {
            $this->form_validation->set_message('_after_open', 'The exam must close after it opens.');
            return false;
        }
        return true;
    }

    /** $includeStructure: pool size and shuffling can't change once anyone has started. */
    private function _exam_data($includeStructure)
    {
        $data = [
            'title'            => trim($this->input->post('title')),
            'instructions'     => trim((string) $this->input->post('instructions')),
            'opens_at'         => date('Y-m-d H:i:s', strtotime($this->input->post('opens_at'))),
            'closes_at'        => date('Y-m-d H:i:s', strtotime($this->input->post('closes_at'))),
            'duration_minutes' => (int) $this->input->post('duration_minutes'),
        ];
        if ($includeStructure) {
            $data['question_count'] = $this->input->post('question_count') ? (int) $this->input->post('question_count') : null;
            $data['shuffle']        = $this->input->post('shuffle') ? 1 : 0;
        }
        return $data;
    }

    /** Reads and checks the question form. Returns [data, error or null]. */
    private function _question_data()
    {
        $type   = $this->input->post('type') === 'short' ? 'short' : 'mcq';
        $prompt = trim((string) $this->input->post('prompt'));
        $marks  = trim((string) $this->input->post('marks'));

        if ($prompt === '') {
            return [null, 'Please type the question.'];
        }
        if (! is_numeric($marks) || $marks <= 0 || $marks > 100) {
            return [null, 'Marks must be a number between 0.5 and 100.'];
        }

        $data = ['type' => $type, 'prompt' => $prompt, 'marks' => round((float) $marks, 2), 'options' => null, 'correct_option' => null];
        if ($type === 'mcq') {
            $raw     = (array) $this->input->post('options');
            $correct = $this->input->post('correct');
            $options = [];
            $correctIndex = null;
            for ($i = 0; $i < self::MAX_OPTIONS; $i++) {
                $text = isset($raw[$i]) ? trim((string) $raw[$i]) : '';
                if ($text === '') {
                    continue;
                }
                if ($correct !== null && (string) $correct === (string) $i) {
                    $correctIndex = count($options);
                }
                $options[] = mb_substr($text, 0, 500);
            }
            if (count($options) < 2) {
                return [null, 'A multiple-choice question needs at least two choices.'];
            }
            if ($correctIndex === null) {
                return [null, 'Tick the correct answer (the circle next to it).'];
            }
            $data['options']        = json_encode($options, JSON_UNESCAPED_UNICODE);
            $data['correct_option'] = $correctIndex;
        }
        return [$data, null];
    }
}
